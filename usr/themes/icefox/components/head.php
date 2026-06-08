<section class="top-container">
    <div class="top-container-left">
        <div class="tc-user" data-icon="user"
             @click="$nextTick(() => { document.querySelector('.login-modal')._x_dataStack[0].loginModalShow = true })">
            <?php $this->need("components/svgs/user.php"); ?>
        </div>

        <!--<div class="tc-music" data-icon="music">
            <?php $this->need("components/svgs/music.php"); ?>
        </div>-->
    </div>
    <div class="top-container-right">
        <?php
        $user = \Widget\User::alloc();
        if ($user->hasLogin()):
        ?>
            <a href="<?php echo $this->options->editPageUrl ? $this->options->editPageUrl : '/edit.html'; ?>" class="tc-edit" data-icon="edit">
                <?php $this->need("components/svgs/edit.php"); ?>
            </a>
        <?php endif; ?>
        <div class="tc-setting" data-icon="setting"
             @click="$nextTick(() => { document.querySelector('.setting-modal')._x_dataStack[0].settingModalShow = true })">
            <?php $this->need("components/svgs/setting.php"); ?>
        </div>
    </div>
</section>

<!-- 预加载所有图标，但隐藏起来 -->
<div class="preloaded-icons" style="display: none;">
    <!-- 原始图标 -->
    <div data-icon="user"><?php $this->need("components/svgs/user.php"); ?></div>
    <div data-icon="music"><?php $this->need("components/svgs/music.php"); ?></div>
    <div data-icon="edit"><?php $this->need("components/svgs/edit.php"); ?></div>
    <div data-icon="setting"><?php $this->need("components/svgs/setting.php"); ?></div>
    <!-- outline图标 -->
    <div data-icon="user-outline"><?php $this->need("components/svgs/user-outline.php"); ?></div>
    <div data-icon="music-outline"><?php $this->need("components/svgs/music-outline.php"); ?></div>
    <div data-icon="edit-outline"><?php $this->need("components/svgs/edit-outline.php"); ?></div>
    <div data-icon="setting-outline"><?php $this->need("components/svgs/setting-outline.php"); ?></div>
</div>
<section class="header-container" style="<?php
    // 优先级: 背景视频 > 背景图片 > 默认颜色
    if (empty($this->options->topVideo) && empty($this->options->topImage)) {
        // echo 'background-color: #f1f1f1;';
        echo 'background-image: url(' . htmlspecialchars($this->options->themeUrl . 
        '/assets/images/header-bg.jpg', ENT_QUOTES, 'UTF-8') . ');';
    } elseif (empty($this->options->topVideo) && !empty($this->options->topImage)) {
        echo 'background-image: url(' . htmlspecialchars($this->options->topImage, ENT_QUOTES, 'UTF-8') . ');';
    }
?>">
    <?php if (!empty($this->options->topVideo)): ?>
        <!--顶部背景视频-->
        <video src="<?php echo htmlspecialchars($this->options->topVideo, ENT_QUOTES, 'UTF-8'); ?>" autoplay muted loop playsinline></video>
    <?php endif; ?>

    <div class="header-info">
        <div class="header-user">
            <a href="<?php $this->options->siteUrl(); ?>" class="header-site-title">
                <span><?php echo $this->options->title; ?></span>
            </a>
            <?php if ($this->options->avatarLink): ?>
                <a href="<?php echo htmlspecialchars($this->options->avatarLink, ENT_QUOTES, 'UTF-8'); ?>" class="header-avatar-link">
                    <?php if (!empty($this->options->logoUrl)): ?>
                        <img src="<?php echo htmlspecialchars($this->options->logoUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="logo"/>
                    <?php else: ?>
                        <div class="header-logo-placeholder">Logo</div>
                    <?php endif; ?>
                </a>
            <?php else: ?>
                <div class="header-avatar-nolink">
                    <?php if (!empty($this->options->logoUrl)): ?>
                        <img src="<?php echo htmlspecialchars($this->options->logoUrl, ENT_QUOTES, 'UTF-8'); ?>" alt="logo"/>
                    <?php else: ?>
                        <div class="header-logo-placeholder">Logo</div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="header-description">
            <?php $this->options->description(); ?>
        </div>
    </div>

</section>