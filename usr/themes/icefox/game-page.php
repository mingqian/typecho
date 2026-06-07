<?php
/**
 * 摩托车竞速游戏
 *
 * @package custom
 */
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
?>

<!DOCTYPE HTML>
<html lang="zh-CN">
<head>
    <meta charset="<?php $this->options->charset(); ?>">
    <meta name="renderer" content="webkit">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <title><?php $this->archiveTitle([
            'category' => _t('分类 %s 下的文章'),
            'search' => _t('包含关键字 %s 的文章'),
            'tag' => _t('标签 %s 下的文章'),
            'author' => _t('%s 发布的文章')
        ], '', ' - '); ?><?php $this->options->title(); ?></title>

    <?php $this->header("generator=&template=&pingback=&xmlrpc=&wlw=&commentReply=&rss1=&rss2=&atom="); ?>

    <link rel="stylesheet" href="<?php $this->options->themeUrl('assets/css/bulma.min.css'); ?>">
    <link rel="stylesheet" href="<?php $this->options->themeUrl('assets/css/fancybox.css'); ?>">
    <link rel="stylesheet" href="<?php $this->options->themeUrl('assets/css/icefox.css'); ?>">

    <?php if ($this->options->customCss): ?>
        <style><?php $this->options->customCss(); ?></style>
    <?php endif; ?>

    <!-- 全局配置（适配伪静态和非伪静态） -->
    <script>
        window.ICEFOX_CONFIG = {
            actionUrl: '<?php echo Typecho_Common::url('action/icefox', Helper::options()->index); ?>'
        };
    </script>
</head>
<body>

<style>
    /* 游戏专用样式 */
    .game-container {
        max-width: 600px;
        margin: 0 auto;
        padding: 20px;
    }

    /* 顶部信息和按钮区域 */
    .game-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        padding: 15px 20px;
        background: var(--card-bg);
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }

    .game-stats {
        display: flex;
        gap: 20px;
        flex: 1;
    }

    .game-stat {
        text-align: center;
    }

    .game-stat-label {
        font-size: 12px;
        color: var(--text-sub-color);
        margin-bottom: 5px;
    }

    .game-stat-value {
        font-size: 24px;
        font-weight: bold;
        color: var(--link);
    }

    .leaderboard-btn {
        padding: 8px 16px;
        background: var(--link);
        color: white;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 500;
        transition: all 0.2s ease;
        white-space: nowrap;
    }

    .leaderboard-btn:hover {
        opacity: 0.9;
        transform: translateY(-1px);
    }

    /* 游戏画布区域 */
    .game-canvas-wrapper {
        position: relative;
        width: 100%;
        background: #333;
        border-radius: 8px;
        overflow: hidden;
        margin-bottom: 20px;
    }

    .game-canvas {
        width: 100%;
        height: 500px;
        position: relative;
        background: linear-gradient(to bottom, #2c3e50 0%, #34495e 100%);
        overflow: hidden;
    }

    /* 道路 */
    .road {
        position: absolute;
        width: 100%;
        height: 100%;
        display: flex;
        justify-content: space-around;
        padding: 0 10%;
    }

    .lane {
        flex: 1;
        position: relative;
        border-left: 2px dashed rgba(255, 255, 255, 0.3);
    }

    .lane:last-child {
        border-right: 2px dashed rgba(255, 255, 255, 0.3);
    }

    /* 车辆 */
    .vehicle {
        position: absolute;
        font-size: 32px;
        transition: left 0.2s ease;
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .player {
        bottom: 50px;
        filter: drop-shadow(0 0 5px rgba(255, 215, 0, 0.8));
    }

    .obstacle {
        animation: moveDown linear;
    }

    @keyframes moveDown {
        from {
            top: -50px;
        }
        to {
            top: 550px;
        }
    }

    /* 控制区域 */
    .game-controls {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 20px;
    }

    .control-btn {
        width: 80px;
        height: 80px;
        font-size: 32px;
        background: var(--link);
        color: white;
        border: none;
        border-radius: 50%;
        cursor: pointer;
        box-shadow: 0 4px 12px rgba(87, 107, 149, 0.3);
        transition: all 0.2s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        user-select: none;
    }

    .control-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(87, 107, 149, 0.4);
    }

    .control-btn:active {
        transform: translateY(0);
        box-shadow: 0 2px 8px rgba(87, 107, 149, 0.3);
    }

    .control-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    /* 开始按钮 */
    .start-btn {
        width: 120px;
        height: 80px;
        font-size: 18px;
        font-weight: bold;
        background: #07C160;
        color: white;
        border: none;
        border-radius: 40px;
        cursor: pointer;
        box-shadow: 0 4px 12px rgba(7, 193, 96, 0.3);
        transition: all 0.2s ease;
    }

    .start-btn:hover {
        background: #06a950;
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(7, 193, 96, 0.4);
    }

    .start-btn:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    /* 游戏结束弹窗 */
    .game-over-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.95);
        z-index: 1000;
        align-items: center;
        justify-content: center;
    }

    .game-over-modal.show {
        display: flex;
    }

    .game-over-content {
        background: var(--card-bg);
        padding: 30px;
        border-radius: 10px;
        text-align: center;
        max-width: 400px;
        width: 90%;
    }

    .game-over-title {
        font-size: 32px;
        font-weight: bold;
        color: var(--text-color);
        margin-bottom: 20px;
    }

    .game-over-stats {
        margin-bottom: 20px;
    }

    .game-over-stat {
        font-size: 18px;
        color: var(--text-sub-color);
        margin: 10px 0;
    }

    .game-over-stat strong {
        color: var(--link);
        font-size: 24px;
    }

    .game-over-form {
        margin-top: 20px;
    }

    .game-over-tip {
        margin-top: 20px;
        padding: 20px;
        background: linear-gradient(135deg, #fff3cd 0%, #ffeeba 100%);
        border-radius: 10px;
        text-align: center;
    }

    .game-over-tip p {
        margin: 8px 0;
        color: #856404;
        font-size: 16px;
    }

    .game-over-tip strong {
        color: #07C160;
        font-size: 18px;
    }

    .game-over-actions {
        margin-top: 20px;
    }

    .game-over-input {
        width: 100%;
        padding: 12px;
        margin: 10px 0;
        border: 1px solid var(--border-color);
        border-radius: 5px;
        font-size: 16px;
        box-sizing: border-box;
        background: var(--bg-color);
        color: var(--text-color);
    }

    .game-over-btn {
        width: 48%;
        padding: 12px;
        margin: 10px 1%;
        border: none;
        border-radius: 5px;
        font-size: 16px;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.2s ease;
    }

    .submit-score-btn {
        background: #07C160;
        color: white;
    }

    .submit-score-btn:hover {
        background: #06a950;
    }

    .restart-btn {
        background: var(--link);
        color: white;
    }

    .restart-btn:hover {
        opacity: 0.9;
    }

    /* 排行榜弹窗 */
    .leaderboard-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(255, 255, 255, 0.95);
        z-index: 1000;
        align-items: center;
        justify-content: center;
    }

    .leaderboard-modal.show {
        display: flex;
    }

    .leaderboard-content {
        background: var(--card-bg);
        padding: 30px;
        border-radius: 10px;
        max-width: 500px;
        width: 90%;
        max-height: 80vh;
        overflow-y: auto;
    }

    .leaderboard-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .leaderboard-title {
        font-size: 24px;
        font-weight: bold;
        color: var(--text-color);
    }

    .close-modal-btn {
        background: none;
        border: none;
        font-size: 32px;
        color: var(--text-sub-color);
        cursor: pointer;
        line-height: 1;
        padding: 0;
        width: 32px;
        height: 32px;
    }

    .close-modal-btn:hover {
        color: var(--text-color);
    }

    .leaderboard-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .leaderboard-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px;
        margin: 8px 0;
        background: var(--bg-color);
        border-radius: 5px;
    }

    .leaderboard-rank {
        font-size: 18px;
        font-weight: bold;
        color: var(--link);
        width: 40px;
    }

    .leaderboard-name {
        flex: 1;
        font-size: 16px;
        color: var(--text-color);
    }

    .leaderboard-score {
        font-size: 18px;
        font-weight: bold;
        color: var(--text-sub-color);
    }

    /* 响应式 */
    @media (max-width: 768px) {
        .game-canvas {
            height: 400px;
        }

        .control-btn {
            width: 70px;
            height: 70px;
            font-size: 28px;
        }

        .start-btn {
            width: 100px;
            height: 70px;
            font-size: 16px;
        }

        .game-header {
            flex-direction: column;
            gap: 15px;
        }

        .game-stats {
            width: 100%;
            justify-content: space-around;
        }

        .leaderboard-btn {
            width: 100%;
        }
    }

    @media (max-width: 576px) {
        .game-stat-value {
            font-size: 20px;
        }

        .game-stat-label {
            font-size: 11px;
        }
    }
</style>

<main>
    <div class="game-container">
        <!-- 顶部信息和排行榜按钮 -->
        <div class="game-header">
            <div class="game-stats">
                <div class="game-stat">
                    <div class="game-stat-label">距离</div>
                    <div class="game-stat-value" id="currentDistance">0</div>
                </div>
                <div class="game-stat">
                    <div class="game-stat-label">我的记录</div>
                    <div class="game-stat-value" id="bestDistance">0</div>
                </div>
                <div class="game-stat">
                    <div class="game-stat-label">速度</div>
                    <div class="game-stat-value" id="currentSpeed">1</div>
                </div>
            </div>
            <button class="leaderboard-btn" id="showLeaderboardBtn">🏆 排行榜</button>
        </div>

        <!-- 游戏画布 -->
        <div class="game-canvas-wrapper">
            <div class="game-canvas" id="gameCanvas">
                <div class="road" id="road">
                    <div class="lane"></div>
                    <div class="lane"></div>
                    <div class="lane"></div>
                    <div class="lane"></div>
                    <div class="lane"></div>
                </div>
            </div>
        </div>

        <!-- 控制按钮（开始按钮在中间） -->
        <div class="game-controls">
            <button class="control-btn" id="leftBtn" disabled>←</button>
            <button class="start-btn" id="startBtn">开始游戏</button>
            <button class="control-btn" id="rightBtn" disabled>→</button>
        </div>
    </div>
</main>

<!-- 游戏结束弹窗 -->
<div class="game-over-modal" id="gameOverModal">
    <div class="game-over-content">
        <div class="game-over-title">游戏结束！</div>
        <div class="game-over-stats">
            <div class="game-over-stat">本次距离: <strong id="finalDistance">0</strong>m</div>
            <div class="game-over-stat">最佳记录: <strong id="finalBest">0</strong>m</div>
        </div>
        <!-- 分数不足提示 -->
        <div class="game-over-tip" id="scoreTip" style="display: none;">
            <p>🎯 跑到 <strong>100m</strong> 以上才能上榜哦！</p>
            <p style="font-size: 12px; color: var(--text-sub-color);">继续加油，刷新你的记录！</p>
        </div>
        <!-- 提交表单（分数>=100时显示） -->
        <div class="game-over-form" id="scoreForm">
            <input type="text" class="game-over-input" id="playerName" placeholder="输入昵称（必填）" maxlength="20" required>
            <input type="email" class="game-over-input" id="playerEmail" placeholder="输入邮箱（必填）" maxlength="50" required>
            <div>
                <button class="game-over-btn submit-score-btn" id="submitScoreBtn">提交成绩</button>
                <button class="game-over-btn restart-btn" id="restartBtn">再来一局</button>
            </div>
        </div>
        <!-- 再来一局按钮（分数<100时显示） -->
        <div class="game-over-actions" id="lowScoreActions" style="display: none;">
            <button class="game-over-btn restart-btn" id="restartBtnLow" style="width: 100%;">再来一局</button>
        </div>
    </div>
</div>

<!-- 排行榜弹窗 -->
<div class="leaderboard-modal" id="leaderboardModal">
    <div class="leaderboard-content">
        <div class="leaderboard-header">
            <div class="leaderboard-title">🏆 排行榜</div>
            <button class="close-modal-btn" id="closeLeaderboardBtn">×</button>
        </div>
        <ul class="leaderboard-list" id="leaderboardList">
            <li class="leaderboard-item">
                <span class="leaderboard-rank">-</span>
                <span class="leaderboard-name">暂无记录</span>
                <span class="leaderboard-score">-</span>
            </li>
        </ul>
    </div>
</div>

<script>
// 游戏配置
const GAME_CONFIG = {
    lanes: 5,
    playerStartLane: 2,
    initialSpeed: 2,
    speedIncrement: 0.1,
    speedIncrementInterval: 5000, // 每5秒加速
    obstacleSpawnInterval: 1500, // 初始生成障碍物间隔
    vehicleEmojis: ['🚗', '🚕', '🚙', '🚌', '🚐'],
    playerEmoji: '🏍️'
};

// 游戏状态
let gameState = {
    isRunning: false,
    playerLane: GAME_CONFIG.playerStartLane,
    distance: 0,
    speed: GAME_CONFIG.initialSpeed,
    obstacles: [],
    lastObstacleTime: 0,
    animationFrame: null,
    speedInterval: null,
    distanceInterval: null,
    startTime: 0, // 游戏开始时间
    endTime: 0, // 游戏结束时间
    checkpoints: [] // 游戏检查点（每100分记录一次）
};

// DOM 元素
const elements = {
    startBtn: document.getElementById('startBtn'),
    leftBtn: document.getElementById('leftBtn'),
    rightBtn: document.getElementById('rightBtn'),
    gameCanvas: document.getElementById('gameCanvas'),
    road: document.getElementById('road'),
    currentDistance: document.getElementById('currentDistance'),
    bestDistance: document.getElementById('bestDistance'),
    currentSpeed: document.getElementById('currentSpeed'),
    gameOverModal: document.getElementById('gameOverModal'),
    finalDistance: document.getElementById('finalDistance'),
    finalBest: document.getElementById('finalBest'),
    submitScoreBtn: document.getElementById('submitScoreBtn'),
    restartBtn: document.getElementById('restartBtn'),
    restartBtnLow: document.getElementById('restartBtnLow'),
    playerName: document.getElementById('playerName'),
    playerEmail: document.getElementById('playerEmail'),
    leaderboardModal: document.getElementById('leaderboardModal'),
    showLeaderboardBtn: document.getElementById('showLeaderboardBtn'),
    closeLeaderboardBtn: document.getElementById('closeLeaderboardBtn'),
    leaderboardList: document.getElementById('leaderboardList'),
    scoreForm: document.getElementById('scoreForm'),
    scoreTip: document.getElementById('scoreTip'),
    lowScoreActions: document.getElementById('lowScoreActions')
};

// 玩家车辆
let playerVehicle = null;

// 初始化
function init() {
    loadBestScore();
    loadPlayerInfo();
    loadLeaderboard();
    setupEventListeners();
}

// 加载保存的玩家信息
function loadPlayerInfo() {
    const savedName = localStorage.getItem('motoPlayerName');
    const savedEmail = localStorage.getItem('motoPlayerEmail');
    if (savedName) {
        elements.playerName.value = savedName;
    }
    if (savedEmail) {
        elements.playerEmail.value = savedEmail;
    }
}

// 保存玩家信息到本地
function savePlayerInfo(name, email) {
    localStorage.setItem('motoPlayerName', name);
    localStorage.setItem('motoPlayerEmail', email);
}

// 设置事件监听
function setupEventListeners() {
    elements.startBtn.addEventListener('click', startGame);
    elements.leftBtn.addEventListener('click', () => movePlayer(-1));
    elements.rightBtn.addEventListener('click', () => movePlayer(1));
    elements.restartBtn.addEventListener('click', restartGame);
    elements.restartBtnLow.addEventListener('click', restartGame);
    elements.submitScoreBtn.addEventListener('click', submitScore);
    elements.showLeaderboardBtn.addEventListener('click', showLeaderboard);
    elements.closeLeaderboardBtn.addEventListener('click', hideLeaderboard);

    // 点击弹窗背景关闭
    elements.leaderboardModal.addEventListener('click', (e) => {
        if (e.target === elements.leaderboardModal) {
            hideLeaderboard();
        }
    });

    // 键盘控制
    document.addEventListener('keydown', (e) => {
        if (!gameState.isRunning) return;
        if (e.key === 'ArrowLeft') movePlayer(-1);
        if (e.key === 'ArrowRight') movePlayer(1);
    });
}

// 显示排行榜
function showLeaderboard() {
    loadLeaderboard();
    elements.leaderboardModal.classList.add('show');
}

// 隐藏排行榜
function hideLeaderboard() {
    elements.leaderboardModal.classList.remove('show');
}

// 开始游戏
function startGame() {
    // 重置游戏状态
    gameState = {
        isRunning: true,
        playerLane: GAME_CONFIG.playerStartLane,
        distance: 0,
        speed: GAME_CONFIG.initialSpeed,
        obstacles: [],
        lastObstacleTime: 0,
        animationFrame: null,
        speedInterval: null,
        distanceInterval: null,
        startTime: Date.now(), // 记录开始时间
        endTime: 0, // 重置结束时间
        checkpoints: [] // 重置检查点
    };

    // 清空画布
    const obstacles = document.querySelectorAll('.obstacle');
    obstacles.forEach(obs => obs.remove());

    // 创建玩家车辆
    if (playerVehicle) playerVehicle.remove();
    playerVehicle = createVehicle(GAME_CONFIG.playerEmoji, true);
    elements.gameCanvas.appendChild(playerVehicle);
    updatePlayerPosition();

    // 更新UI
    elements.startBtn.textContent = '游戏中';
    elements.startBtn.disabled = true;
    elements.leftBtn.disabled = false;
    elements.rightBtn.disabled = false;
    elements.currentDistance.textContent = '0';
    elements.currentSpeed.textContent = '1';

    // 启动游戏循环
    gameState.animationFrame = requestAnimationFrame(gameLoop);

    // 速度递增
    gameState.speedInterval = setInterval(() => {
        gameState.speed += GAME_CONFIG.speedIncrement;
        elements.currentSpeed.textContent = gameState.speed.toFixed(1);
    }, GAME_CONFIG.speedIncrementInterval);

    // 距离计算
    gameState.distanceInterval = setInterval(() => {
        const prevDistance = gameState.distance;
        gameState.distance += Math.floor(gameState.speed);
        elements.currentDistance.textContent = gameState.distance;

        // 每经过50分记录一个检查点（降低门槛）
        const prevCheckpoint = Math.floor(prevDistance / 50);
        const currentCheckpoint = Math.floor(gameState.distance / 50);

        if (currentCheckpoint > prevCheckpoint && gameState.distance > 0) {
            const timestamp = Date.now();
            gameState.checkpoints.push({
                distance: gameState.distance,
                time: timestamp
            });
        }
    }, 100);
}

// 游戏循环
function gameLoop(timestamp) {
    if (!gameState.isRunning) return;

    // 生成障碍物
    if (timestamp - gameState.lastObstacleTime > GAME_CONFIG.obstacleSpawnInterval / gameState.speed) {
        spawnObstacle();
        gameState.lastObstacleTime = timestamp;
    }

    // 更新障碍物位置和检测碰撞
    updateObstacles();

    // 继续循环
    gameState.animationFrame = requestAnimationFrame(gameLoop);
}

// 创建车辆元素
function createVehicle(emoji, isPlayer = false) {
    const vehicle = document.createElement('div');
    vehicle.className = isPlayer ? 'vehicle player' : 'vehicle obstacle';
    vehicle.textContent = emoji;
    return vehicle;
}

// 生成障碍物
function spawnObstacle() {
    const lane = Math.floor(Math.random() * GAME_CONFIG.lanes);
    const emoji = GAME_CONFIG.vehicleEmojis[Math.floor(Math.random() * GAME_CONFIG.vehicleEmojis.length)];

    const obstacle = createVehicle(emoji);
    obstacle.style.left = getLanePosition(lane);
    obstacle.style.animationDuration = (3 / gameState.speed) + 's';

    elements.gameCanvas.appendChild(obstacle);
    gameState.obstacles.push({ element: obstacle, lane: lane });

    // 动画结束后移除
    setTimeout(() => {
        obstacle.remove();
        gameState.obstacles = gameState.obstacles.filter(obs => obs.element !== obstacle);
    }, 3000 / gameState.speed);
}

// 更新障碍物并检测碰撞
function updateObstacles() {
    gameState.obstacles.forEach(obstacle => {
        const obstacleRect = obstacle.element.getBoundingClientRect();
        const playerRect = playerVehicle.getBoundingClientRect();

        // 碰撞检测
        if (obstacle.lane === gameState.playerLane) {
            if (!(obstacleRect.bottom < playerRect.top || obstacleRect.top > playerRect.bottom)) {
                gameOver();
            }
        }
    });
}

// 移动玩家
function movePlayer(direction) {
    const newLane = gameState.playerLane + direction;
    if (newLane >= 0 && newLane < GAME_CONFIG.lanes) {
        gameState.playerLane = newLane;
        updatePlayerPosition();
    }
}

// 更新玩家位置
function updatePlayerPosition() {
    if (playerVehicle) {
        playerVehicle.style.left = getLanePosition(gameState.playerLane);
    }
}

// 获取车道位置
function getLanePosition(lane) {
    const roadWidth = elements.road.offsetWidth;
    const laneWidth = roadWidth / GAME_CONFIG.lanes;
    const padding = roadWidth * 0.1;
    const adjustedWidth = roadWidth - padding * 2;
    const adjustedLaneWidth = adjustedWidth / GAME_CONFIG.lanes;
    return (padding + lane * adjustedLaneWidth + adjustedLaneWidth / 2 - 20) + 'px';
}

// 游戏结束
function gameOver() {
    gameState.isRunning = false;

    // 记录游戏结束时间
    gameState.endTime = Date.now();

    // 停止所有循环
    if (gameState.animationFrame) cancelAnimationFrame(gameState.animationFrame);
    if (gameState.speedInterval) clearInterval(gameState.speedInterval);
    if (gameState.distanceInterval) clearInterval(gameState.distanceInterval);

    // 更新最佳成绩
    const bestScore = parseInt(localStorage.getItem('motoBestScore') || '0');
    if (gameState.distance > bestScore) {
        localStorage.setItem('motoBestScore', gameState.distance);
        elements.bestDistance.textContent = gameState.distance;
    }

    // 显示游戏结束弹窗
    elements.finalDistance.textContent = gameState.distance;
    elements.finalBest.textContent = Math.max(gameState.distance, bestScore);

    // 根据分数决定显示提交表单还是提示
    const MIN_SCORE_FOR_LEADERBOARD = 100;
    if (gameState.distance >= MIN_SCORE_FOR_LEADERBOARD) {
        // 分数>=100，显示提交表单
        elements.scoreForm.style.display = 'block';
        elements.scoreTip.style.display = 'none';
        elements.lowScoreActions.style.display = 'none';
    } else {
        // 分数<100，显示提示和再来一局按钮
        elements.scoreForm.style.display = 'none';
        elements.scoreTip.style.display = 'block';
        elements.lowScoreActions.style.display = 'block';
    }

    elements.gameOverModal.classList.add('show');

    // 重置按钮
    elements.startBtn.textContent = '开始游戏';
    elements.startBtn.disabled = false;
    elements.leftBtn.disabled = true;
    elements.rightBtn.disabled = true;
}

// 重新开始
function restartGame() {
    elements.gameOverModal.classList.remove('show');
    elements.playerName.value = '';
    elements.playerEmail.value = '';
    startGame();
}

// 提交成绩
function submitScore() {
    const name = elements.playerName.value.trim();
    const email = elements.playerEmail.value.trim();
    const score = gameState.distance;

    // 验证必填项
    if (!name) {
        alert('请输入昵称');
        elements.playerName.focus();
        return;
    }

    if (!email) {
        alert('请输入邮箱');
        elements.playerEmail.focus();
        return;
    }

    // 验证邮箱格式
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRegex.test(email)) {
        alert('邮箱格式不正确');
        elements.playerEmail.focus();
        return;
    }

    // 计算游戏时长（秒）- 使用游戏结束时间而不是当前时间
    const gameTime = Math.floor((gameState.endTime - gameState.startTime) / 1000);

    // 生成检查点字符串（格式：距离:时间戳,距离:时间戳,...）
    const checkpoints = gameState.checkpoints.map(cp => `${cp.distance}:${cp.time}`).join(',');

    // 生成签名
    const signature = generateSignature(score, gameTime, checkpoints);

    // 禁用按钮防止重复提交
    elements.submitScoreBtn.disabled = true;
    elements.submitScoreBtn.textContent = '提交中...';

    // 提交到后端API
    fetch(`${window.ICEFOX_CONFIG.actionUrl}?do=saveGameScore`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            name: name,
            email: email,
            score: score,
            gameTime: gameTime,
            checkpoints: checkpoints,
            signature: signature
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // 保存玩家信息到本地，下次自动填充
            savePlayerInfo(name, email);
            alert(data.message);
            // 更新排行榜显示
            loadLeaderboard();
            // 关闭弹窗
            elements.gameOverModal.classList.remove('show');
        } else {
            alert(data.message || '提交失败，请稍后再试');
        }
    })
    .catch(error => {
        console.error('提交成绩失败:', error);
        alert('网络错误，请检查网络连接');
    })
    .finally(() => {
        // 恢复按钮状态
        elements.submitScoreBtn.disabled = false;
        elements.submitScoreBtn.textContent = '提交成绩';
    });
}

// 加载最佳成绩
function loadBestScore() {
    const bestScore = localStorage.getItem('motoBestScore') || '0';
    elements.bestDistance.textContent = bestScore;
}

// 加载排行榜
function loadLeaderboard() {
    // 从后端API获取排行榜数据
    fetch(`${window.ICEFOX_CONFIG.actionUrl}?do=getGameLeaderboard&limit=10`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data && data.data.length > 0) {
                elements.leaderboardList.innerHTML = data.data.map((entry, index) => `
                    <li class="leaderboard-item">
                        <span class="leaderboard-rank">${index + 1}</span>
                        <span class="leaderboard-name">${escapeHtml(entry.name)}</span>
                        <span class="leaderboard-score">${entry.score}m</span>
                    </li>
                `).join('');
            } else {
                elements.leaderboardList.innerHTML = `
                    <li class="leaderboard-item">
                        <span class="leaderboard-rank">-</span>
                        <span class="leaderboard-name">暂无记录</span>
                        <span class="leaderboard-score">-</span>
                    </li>
                `;
            }
        })
        .catch(error => {
            console.error('加载排行榜失败:', error);
            elements.leaderboardList.innerHTML = `
                <li class="leaderboard-item">
                    <span class="leaderboard-rank">-</span>
                    <span class="leaderboard-name">加载失败</span>
                    <span class="leaderboard-score">-</span>
                </li>
            `;
        });
}

// HTML转义函数，防止XSS
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

// MD5哈希函数（与后端PHP md5()一致）
function md5(string) {
    function rotateLeft(lValue, iShiftBits) {
        return (lValue << iShiftBits) | (lValue >>> (32 - iShiftBits));
    }

    function addUnsigned(lX, lY) {
        const lX8 = lX & 0x80000000;
        const lY8 = lY & 0x80000000;
        const lX4 = lX & 0x40000000;
        const lY4 = lY & 0x40000000;
        const lResult = (lX & 0x3FFFFFFF) + (lY & 0x3FFFFFFF);
        if (lX4 & lY4) return lResult ^ 0x80000000 ^ lX8 ^ lY8;
        if (lX4 | lY4) {
            if (lResult & 0x40000000) return lResult ^ 0xC0000000 ^ lX8 ^ lY8;
            else return lResult ^ 0x40000000 ^ lX8 ^ lY8;
        } else return lResult ^ lX8 ^ lY8;
    }

    function F(x, y, z) { return (x & y) | ((~x) & z); }
    function G(x, y, z) { return (x & z) | (y & (~z)); }
    function H(x, y, z) { return x ^ y ^ z; }
    function I(x, y, z) { return y ^ (x | (~z)); }

    function FF(a, b, c, d, x, s, ac) {
        a = addUnsigned(a, addUnsigned(addUnsigned(F(b, c, d), x), ac));
        return addUnsigned(rotateLeft(a, s), b);
    }
    function GG(a, b, c, d, x, s, ac) {
        a = addUnsigned(a, addUnsigned(addUnsigned(G(b, c, d), x), ac));
        return addUnsigned(rotateLeft(a, s), b);
    }
    function HH(a, b, c, d, x, s, ac) {
        a = addUnsigned(a, addUnsigned(addUnsigned(H(b, c, d), x), ac));
        return addUnsigned(rotateLeft(a, s), b);
    }
    function II(a, b, c, d, x, s, ac) {
        a = addUnsigned(a, addUnsigned(addUnsigned(I(b, c, d), x), ac));
        return addUnsigned(rotateLeft(a, s), b);
    }

    function convertToWordArray(string) {
        let lWordCount;
        const lMessageLength = string.length;
        const lNumberOfWords_temp1 = lMessageLength + 8;
        const lNumberOfWords_temp2 = (lNumberOfWords_temp1 - (lNumberOfWords_temp1 % 64)) / 64;
        const lNumberOfWords = (lNumberOfWords_temp2 + 1) * 16;
        const lWordArray = Array(lNumberOfWords - 1);
        let lBytePosition = 0;
        let lByteCount = 0;
        while (lByteCount < lMessageLength) {
            lWordCount = (lByteCount - (lByteCount % 4)) / 4;
            lBytePosition = (lByteCount % 4) * 8;
            lWordArray[lWordCount] = lWordArray[lWordCount] | (string.charCodeAt(lByteCount) << lBytePosition);
            lByteCount++;
        }
        lWordCount = (lByteCount - (lByteCount % 4)) / 4;
        lBytePosition = (lByteCount % 4) * 8;
        lWordArray[lWordCount] = lWordArray[lWordCount] | (0x80 << lBytePosition);
        lWordArray[lNumberOfWords - 2] = lMessageLength << 3;
        lWordArray[lNumberOfWords - 1] = lMessageLength >>> 29;
        return lWordArray;
    }

    function wordToHex(lValue) {
        let wordToHexValue = "", wordToHexValue_temp = "", lByte, lCount;
        for (lCount = 0; lCount <= 3; lCount++) {
            lByte = (lValue >>> (lCount * 8)) & 255;
            wordToHexValue_temp = "0" + lByte.toString(16);
            wordToHexValue = wordToHexValue + wordToHexValue_temp.substr(wordToHexValue_temp.length - 2, 2);
        }
        return wordToHexValue;
    }

    const x = convertToWordArray(string);
    let a = 0x67452301, b = 0xEFCDAB89, c = 0x98BADCFE, d = 0x10325476;
    const S11 = 7, S12 = 12, S13 = 17, S14 = 22;
    const S21 = 5, S22 = 9, S23 = 14, S24 = 20;
    const S31 = 4, S32 = 11, S33 = 16, S34 = 23;
    const S41 = 6, S42 = 10, S43 = 15, S44 = 21;

    for (let k = 0; k < x.length; k += 16) {
        const AA = a, BB = b, CC = c, DD = d;
        a = FF(a, b, c, d, x[k], S11, 0xD76AA478);
        d = FF(d, a, b, c, x[k + 1], S12, 0xE8C7B756);
        c = FF(c, d, a, b, x[k + 2], S13, 0x242070DB);
        b = FF(b, c, d, a, x[k + 3], S14, 0xC1BDCEEE);
        a = FF(a, b, c, d, x[k + 4], S11, 0xF57C0FAF);
        d = FF(d, a, b, c, x[k + 5], S12, 0x4787C62A);
        c = FF(c, d, a, b, x[k + 6], S13, 0xA8304613);
        b = FF(b, c, d, a, x[k + 7], S14, 0xFD469501);
        a = FF(a, b, c, d, x[k + 8], S11, 0x698098D8);
        d = FF(d, a, b, c, x[k + 9], S12, 0x8B44F7AF);
        c = FF(c, d, a, b, x[k + 10], S13, 0xFFFF5BB1);
        b = FF(b, c, d, a, x[k + 11], S14, 0x895CD7BE);
        a = FF(a, b, c, d, x[k + 12], S11, 0x6B901122);
        d = FF(d, a, b, c, x[k + 13], S12, 0xFD987193);
        c = FF(c, d, a, b, x[k + 14], S13, 0xA679438E);
        b = FF(b, c, d, a, x[k + 15], S14, 0x49B40821);
        a = GG(a, b, c, d, x[k + 1], S21, 0xF61E2562);
        d = GG(d, a, b, c, x[k + 6], S22, 0xC040B340);
        c = GG(c, d, a, b, x[k + 11], S23, 0x265E5A51);
        b = GG(b, c, d, a, x[k], S24, 0xE9B6C7AA);
        a = GG(a, b, c, d, x[k + 5], S21, 0xD62F105D);
        d = GG(d, a, b, c, x[k + 10], S22, 0x2441453);
        c = GG(c, d, a, b, x[k + 15], S23, 0xD8A1E681);
        b = GG(b, c, d, a, x[k + 4], S24, 0xE7D3FBC8);
        a = GG(a, b, c, d, x[k + 9], S21, 0x21E1CDE6);
        d = GG(d, a, b, c, x[k + 14], S22, 0xC33707D6);
        c = GG(c, d, a, b, x[k + 3], S23, 0xF4D50D87);
        b = GG(b, c, d, a, x[k + 8], S24, 0x455A14ED);
        a = GG(a, b, c, d, x[k + 13], S21, 0xA9E3E905);
        d = GG(d, a, b, c, x[k + 2], S22, 0xFCEFA3F8);
        c = GG(c, d, a, b, x[k + 7], S23, 0x676F02D9);
        b = GG(b, c, d, a, x[k + 12], S24, 0x8D2A4C8A);
        a = HH(a, b, c, d, x[k + 5], S31, 0xFFFA3942);
        d = HH(d, a, b, c, x[k + 8], S32, 0x8771F681);
        c = HH(c, d, a, b, x[k + 11], S33, 0x6D9D6122);
        b = HH(b, c, d, a, x[k + 14], S34, 0xFDE5380C);
        a = HH(a, b, c, d, x[k + 1], S31, 0xA4BEEA44);
        d = HH(d, a, b, c, x[k + 4], S32, 0x4BDECFA9);
        c = HH(c, d, a, b, x[k + 7], S33, 0xF6BB4B60);
        b = HH(b, c, d, a, x[k + 10], S34, 0xBEBFBC70);
        a = HH(a, b, c, d, x[k + 13], S31, 0x289B7EC6);
        d = HH(d, a, b, c, x[k], S32, 0xEAA127FA);
        c = HH(c, d, a, b, x[k + 3], S33, 0xD4EF3085);
        b = HH(b, c, d, a, x[k + 6], S34, 0x4881D05);
        a = HH(a, b, c, d, x[k + 9], S31, 0xD9D4D039);
        d = HH(d, a, b, c, x[k + 12], S32, 0xE6DB99E5);
        c = HH(c, d, a, b, x[k + 15], S33, 0x1FA27CF8);
        b = HH(b, c, d, a, x[k + 2], S34, 0xC4AC5665);
        a = II(a, b, c, d, x[k], S41, 0xF4292244);
        d = II(d, a, b, c, x[k + 7], S42, 0x432AFF97);
        c = II(c, d, a, b, x[k + 14], S43, 0xAB9423A7);
        b = II(b, c, d, a, x[k + 5], S44, 0xFC93A039);
        a = II(a, b, c, d, x[k + 12], S41, 0x655B59C3);
        d = II(d, a, b, c, x[k + 3], S42, 0x8F0CCC92);
        c = II(c, d, a, b, x[k + 10], S43, 0xFFEFF47D);
        b = II(b, c, d, a, x[k + 1], S44, 0x85845DD1);
        a = II(a, b, c, d, x[k + 8], S41, 0x6FA87E4F);
        d = II(d, a, b, c, x[k + 15], S42, 0xFE2CE6E0);
        c = II(c, d, a, b, x[k + 6], S43, 0xA3014314);
        b = II(b, c, d, a, x[k + 13], S44, 0x4E0811A1);
        a = II(a, b, c, d, x[k + 4], S41, 0xF7537E82);
        d = II(d, a, b, c, x[k + 11], S42, 0xBD3AF235);
        c = II(c, d, a, b, x[k + 2], S43, 0x2AD7D2BB);
        b = II(b, c, d, a, x[k + 9], S44, 0xEB86D391);
        a = addUnsigned(a, AA);
        b = addUnsigned(b, BB);
        c = addUnsigned(c, CC);
        d = addUnsigned(d, DD);
    }
    return (wordToHex(a) + wordToHex(b) + wordToHex(c) + wordToHex(d)).toLowerCase();
}

// 生成签名（使用MD5）
function generateSignature(score, gameTime, checkpoints) {
    const secretKey = 'icefox_game_secret_key_2024';
    const data = `${score}|${gameTime}|${checkpoints}${secretKey}`;
    return md5(data);
}

// 初始化游戏
init();
</script>

<?php $this->need('footer.php'); ?>

</body>
</html>
