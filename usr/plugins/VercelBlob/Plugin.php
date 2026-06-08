<?php

namespace TypechoPlugin\VercelBlob;

use Typecho\Common;
use Typecho\Config;
use Typecho\Plugin\PluginInterface;
use Typecho\Plugin\Exception;
use Typecho\Widget\Helper\Form;
use Typecho\Widget\Helper\Form\Element\Text;
use Widget\Upload;

if (!defined('__TYPECHO_ROOT_DIR__')) {
    exit;
}

/**
 * Vercel Blob 存储插件
 * 
 * 将 Typecho 的文件上传重定向到 Vercel Blob，解决 Vercel 部署时
 * 只读文件系统无法写入的问题。
 *
 * @package VercelBlob
 * @author Sisyphus
 * @version 1.0.0
 */
class Plugin implements PluginInterface
{
    private const BLOB_API = 'https://blob.vercel-storage.com';

    /**
     * 激活插件
     */
    public static function activate()
    {
        if (version_compare(Common::VERSION, '1.2.0', '<')) {
            throw new Exception('需要 Typecho 1.2.0 或更高版本');
        }

        // 拦截核心上传流程
        \Typecho\Plugin::factory('Widget\Upload')->uploadHandle   = [__CLASS__, 'handleUpload'];
        \Typecho\Plugin::factory('Widget\Upload')->modifyHandle   = [__CLASS__, 'handleModify'];
        \Typecho\Plugin::factory('Widget\Upload')->deleteHandle   = [__CLASS__, 'handleDelete'];
        \Typecho\Plugin::factory('Widget\Upload')->attachmentHandle = [__CLASS__, 'handleAttachmentUrl'];

        return _t('Vercel Blob 插件已启用。请在设置中配置 Blob Token。');
    }

    /**
     * 禁用插件
     */
    public static function deactivate()
    {
        return _t('Vercel Blob 插件已禁用。上传将恢复为本地存储。');
    }

    /**
     * 插件配置面板
     */
    public static function config(Form $form)
    {
        $token = new Text('blobToken', null, self::getToken(), _t('Blob Read/Write Token'));
        $token->input->setAttribute('class', 'w-100 mono');
        $token->input->setAttribute('placeholder', 'vercel_blob_rw_xxxxxxxxxxxx');
        $token->description(_t('在 Vercel 项目设置 → Environment Variables 中创建 %s 并填入此处。也可直接设置环境变量，无需在此填写。', '<code>BLOB_READ_WRITE_TOKEN</code>'));
        $form->addInput($token);
    }

    /**
     * 保存配置
     */
    public static function configHandle(array $config, bool $isInit)
    {
        \Utils\Helper::configPlugin('VercelBlob', $config);
    }

    /**
     * 个人配置（不需要）
     */
    public static function personalConfig(Form $form) {}

    // ─── 核心上传钩子 ──────────────────────────────────────────

    /**
     * 新文件上传 (钩子: Widget\Upload::uploadHandle)
     */
    public static function handleUpload(array $file): ?array
    {
        return self::blobUpload($file);
    }

    /**
     * 文件替换上传 (钩子: Widget\Upload::modifyHandle)
     */
    public static function handleModify(array $content, array $file): ?array
    {
        $result = self::blobUpload($file);
        if ($result) {
            // 删除旧 blob
            if (!empty($content['attachment']->path)) {
                self::blobDelete($content['attachment']->path);
            }
            $result['name'] = $content['attachment']->name;
            return $result;
        }
        return null;
    }

    /**
     * 删除文件 (钩子: Widget\Upload::deleteHandle)
     */
    public static function handleDelete(array $content): bool
    {
        return self::blobDelete($content['attachment']->path);
    }

    /**
     * 返回文件公开 URL (钩子: Widget\Upload::attachmentHandle)
     */
    public static function handleAttachmentUrl(Config $attachment): string
    {
        $path = $attachment->path;

        // 如果已经是完整 URL，直接返回
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        return self::buildPublicUrl($path);
    }

    // ─── 公开静态方法（供其他插件调用） ──────────────────────────

    /**
     * 上传文件到 Vercel Blob
     *
     * @param array $file   $_FILES 元素格式: ['name' => ..., 'tmp_name' => ..., 'size' => ...]
     *                      或 bytes 格式: ['name' => ..., 'bytes' => ..., 'size' => ...]
     * @return array|null   成功返回 ['name', 'path', 'url', 'size', 'type', 'mime']，失败返回 null
     */
    public static function blobUpload(array $file): ?array
    {
        $token = self::getToken();
        if (!$token) {
            return null;  // 无 token，回退到本地存储
        }

        if (empty($file['name'])) {
            return null;
        }

        $name = $file['name'];

        // 确定 MIME 类型
        if (!empty($file['tmp_name'])) {
            $mime = mime_content_type($file['tmp_name']);
        } elseif (!empty($file['type'])) {
            $mime = $file['type'];
        } else {
            $mime = 'application/octet-stream';
        }

        // 读取文件内容
        if (isset($file['tmp_name'])) {
            $body = file_get_contents($file['tmp_name']);
        } elseif (isset($file['bytes'])) {
            $body = $file['bytes'];
        } elseif (isset($file['bits'])) {
            $body = $file['bits'];
        } else {
            return null;
        }

        if ($body === false || $body === '') {
            return null;
        }

        // 生成唯一的 blob 路径
        $ext = pathinfo($name, PATHINFO_EXTENSION);
        $ext = $ext ? '.' . strtolower($ext) : '';
        $pathname = sprintf('%x', crc32(uniqid())) . $ext;

        // PUT 到 Vercel Blob
        $ch = curl_init(self::BLOB_API . '/' . $pathname);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => 'PUT',
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $token,
                'x-api-version: 2023-01-01',
                'x-content-type: ' . $mime,
                'x-content-disposition: inline; filename="' . addcslashes($name, '"\\') . '"',
                'x-cache-control-max-age: 31536000',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($httpCode !== 200 || $curlError) {
            error_log('VercelBlob upload failed: HTTP ' . $httpCode . ' ' . $curlError);
            return null;
        }

        $data = json_decode($response, true);
        if (!$data || empty($data['url'])) {
            error_log('VercelBlob upload: unexpected response: ' . substr($response, 0, 200));
            return null;
        }

        $size = $file['size'] ?? strlen($body);

        return [
            'name' => $name,
            'path' => $data['pathname'] ?? $pathname,   // 存相对路径，由 attachmentHandle 转完整 URL
            'url'  => $data['url'],                       // 完整 URL（供冰狐等直接使用）
            'size' => $size,
            'type' => ltrim($ext, '.'),
            'mime' => $mime,
        ];
    }

    /**
     * 从 Vercel Blob 删除文件
     */
    public static function blobDelete(string $path): bool
    {
        $token = self::getToken();
        if (!$token) {
            return false;
        }

        $url = self::buildPublicUrl($path);

        $ch = curl_init(self::BLOB_API . '/delete');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode(['urls' => [$url]]),
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $token,
                'x-api-version: 2023-01-01',
                'Content-Type: application/json',
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 10,
        ]);

        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $httpCode === 200;
    }

    // ─── 内部工具方法 ──────────────────────────────────────────

    /**
     * 获取 Blob Token（环境变量优先，其次插件配置）
     */
    private static function getToken(): string
    {
        // 优先从环境变量读取（Vercel 原生支持）
        $token = getenv('BLOB_READ_WRITE_TOKEN');
        if ($token) {
            return $token;
        }

        // 其次从插件配置读取
        try {
            $options = \Widget\Options::alloc();
            $config = $options->plugin('VercelBlob');
            if (!empty($config->blobToken)) {
                return $config->blobToken;
            }
        } catch (\Throwable $e) {
            // 配置不可用时忽略
        }

        return '';
    }

    /**
     * 根据 pathname 构建公开访问 URL
     */
    private static function buildPublicUrl(string $path): string
    {
        // 已经是完整 URL
        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        $token = self::getToken();
        if (!$token) {
            return $path;
        }

        // 从 token 中提取 store ID（格式: vercel_blob_rw_<storeId>_<random>）
        if (preg_match('/^vercel_blob_rw_([^_]+)_/', $token, $m)) {
            $storeId = $m[1];
            return 'https://' . $storeId . '.public.blob.vercel-storage.com/' . ltrim($path, '/');
        }

        return $path;
    }
}
