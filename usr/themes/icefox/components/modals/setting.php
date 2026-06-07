<div class="setting-modal" x-cloak x-data="{settingModalShow: false}" x-show="settingModalShow"
     x-transition.opacity.duration.300ms @click.self="settingModalShow = false">
    <div class="setting-container" x-transition.scale.duration.300ms>
        <div>
            <!-- 弹框标题 -->
            <div class="setting-modal-header">
                <div class="setting-modal-title">设置</div>
                <button type="button" class="setting-modal-close" @click="settingModalShow = false">×</button>
            </div>

            <!-- 搜索功能 -->
            <div class="setting-section">
                <div class="setting-section-title">🔍 搜索</div>
                <div class="search-form">
                    <form id="search" method="post" action="<?php $this->options->siteUrl(); ?>" role="search">
                        <input type="text" id="s" name="s" class="text" placeholder="<?php _e('输入关键字搜索'); ?>"/>
                        <button type="submit" class="submit"><?php $this->need('components/svgs/search.php'); ?></button>
                    </form>
                </div>
            </div>

            <!-- 标签列表 -->
            <div class="setting-section">
                <div class="setting-section-title">🏷️ 热门标签</div>
                <div class="setting-tags">
                    <?php
                    // 从数据库获取所有标签，按文章数量排序
                    $tags = \Widget\Metas\Tag\Cloud::alloc();
                    $maxTags = 20; // 最多显示20个标签
                    $allTags = [];

                    // 收集所有标签
                    if ($tags->have()):
                        while ($tags->next()):
                            $allTags[] = [
                                'permalink' => $tags->permalink,
                                'name' => $tags->name
                            ];
                        endwhile;
                    endif;

                    // 如果标签超过20个，随机打乱后取前20个
                    if (count($allTags) > $maxTags) {
                        shuffle($allTags);
                        $allTags = array_slice($allTags, 0, $maxTags);
                    }

                    // 输出标签
                    foreach ($allTags as $tag):
                    ?>
                        <a class="tag" href="<?php echo $tag['permalink']; ?>"><?php echo htmlspecialchars($tag['name'], ENT_QUOTES, 'UTF-8'); ?></a>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- 主题切换 -->
            <div class="setting-section">
                <div class="setting-section-title">🎨 主题模式</div>
                <div class="setting-content">
                    <div class="mode" x-data="{darkMode: document.documentElement.classList.contains('dark')}"
                         @click="darkMode = !darkMode; document.documentElement.classList.toggle('dark', darkMode); localStorage.setItem('icefox_theme_mode', darkMode ? 'dark' : 'light')">
                        <template x-if="!darkMode">
                            <?php $this->need('components/svgs/moon.php'); ?>
                        </template>
                        <template x-if="darkMode">
                            <?php $this->need('components/svgs/sun.php'); ?>
                        </template>
                    </div>
                </div>
            </div>

            <!-- 版权信息 -->
            <div class="copyright">
                <div>&copy; 2019 - <?php echo date('Y'); ?> .
                    <?php _e('Theme by <a href="https://xiaopanglian.com">icefox</a> Powered by <a href="https://typecho.org">Typecho</a>'); ?>
                    .
                    <?php _e('All Rights Reserved'); ?>.
                </div>
                <?php if ($this->options->beianInfo): ?>
                    <a href="https://beian.miit.gov.cn/" target="_blank" rel="noopener noreferrer"><?php echo htmlspecialchars($this->options->beianInfo, ENT_QUOTES, 'UTF-8'); ?></a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>