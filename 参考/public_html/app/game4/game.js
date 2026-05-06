// ゲーム設定
const MAP_WIDTH_TILES = 10; 
const MAP_HEIGHT_TILES = 10; 
let TILE_SIZE = 16; 

const canvas = document.getElementById('game-canvas');
const ctx = canvas.getContext('2d');

const messageBox = document.getElementById('message-box');
const messageText = document.getElementById('message-text');
const endroll = document.getElementById('endroll');
const restartButton = document.getElementById('restart-button');

const codeInputModal = document.getElementById('code-input-modal');
const codeInput = document.getElementById('code-input');
const submitCodeButton = document.getElementById('submit-code');

const swipeArea = document.getElementById('swipe-area');

const tvOverlay = document.getElementById('tv-overlay');
const boardOverlay = document.getElementById('board-overlay');

/* Confirm Modalの要素取得 */
const confirmModal = document.getElementById('confirm-modal');
const confirmText = document.getElementById('confirm-text');
const confirmYesButton = document.getElementById('confirm-yes');
const confirmNoButton = document.getElementById('confirm-no');

/* 画像のプリロード */
const images = {};
const imageSources = {
    floor: 'assets/floor.png',
    player: 'assets/player.png',
    npc: 'assets/npc.png',
    npc2: 'assets/npc2.png',
    board: 'assets/board.png',      // デフォルト（未使用かも）
    board1: 'assets/board1.png',
    board2: 'assets/board2.png',
    board3: 'assets/board3.png',
    board4: 'assets/board4.png',    // ★追加
    board5: 'assets/board5.png',    // ★追加
    board6: 'assets/board6.png',    // ★追加
    tv: 'assets/tv.png',
    door: 'assets/door.png',
    wall: 'assets/wall.png',
    //endrollBackground: 'assets/endroll-background.png',
    //endrollText: 'assets/endroll-text.png',
    item: 'assets/item.png',
    item2: 'assets/item2.png',      // ★追加
    tvGif: 'assets/tv.gif',
    pc: 'assets/pc.png',            // PC画像の追加
};

let imagesLoaded = 0;
const totalImages = Object.keys(imageSources).length;

for (let key in imageSources) {
    images[key] = new Image();
    images[key].src = imageSources[key];
    images[key].onload = () => {
        imagesLoaded++;
        console.log(`${key} loaded (${imagesLoaded}/${totalImages})`);
        if (imagesLoaded === totalImages) {
            console.log("All images loaded. Initializing game...");
            init();
        }
    };
    images[key].onerror = () => {
        console.error(`Failed to load image: ${imageSources[key]}`);
    };
}

/* サウンド */
const sounds = {};
const soundSources = {
    use: 'assets/use.mp3',
    collect: 'assets/collect.mp3',
    door: 'assets/door.mp3',
    ed: 'assets/ed.mp3',
    bgm: 'assets/bgm.mp3',
};

let soundsLoadedCount = 0;
const totalSounds = Object.keys(soundSources).length;

for (let key in soundSources) {
    sounds[key] = new Audio(soundSources[key]);
    // BGMサウンドをループ設定
    if (key === 'bgm' || key === 'ed') {
        sounds[key].loop = true; // ループ再生を有効化
        sounds[key].volume = 0.3;    // 音量を30%に設定
    }
    
    sounds[key].oncanplaythrough = () => {
        soundsLoadedCount++;
        console.log(`${key} sound loaded (${soundsLoadedCount}/${totalSounds})`);
        if (soundsLoadedCount === totalSounds) {
            console.log("All sounds loaded.");
        }
    };
    sounds[key].onerror = () => {
        console.error(`Failed to load sound: ${soundSources[key]}`);
    };
}

function playSound(key) {
    if (sounds[key]) {
        sounds[key].currentTime = 0;
        sounds[key].play();
    }
}

function stopSound(key) {
    if (sounds[key]) {
        sounds[key].pause();        // 再生を一時停止
        sounds[key].currentTime = 0; // 再生位置をリセット
    }
}

/* タイルIDと画像の対応 */
const tileImages = {
    0: images.floor,
    1: images.wall,
    2: images.door,
    4: images.item,
    5: images.tv,
    6: images.board,
    7: images.item2, // item2.png
    8: images.pc,    // PC
};

/* マップデータ */
let maps = [
    // マップ1
    {
        id: 1,
        layout: Array(MAP_WIDTH_TILES * MAP_HEIGHT_TILES).fill(0),
        door: { x: 5, y: 0, code: '2025' },
        npcs: [ // 配列として定義
            {
                x: 5,
                y: 5,
                dialog: ['年賀状ありがとう！今年もよろしく！2025！'],
                imageKey: 'npc', // 画像キー
            },
        ],
        items: [
            { type: 'board', x: 4, y: 5, variant: 'board1', collected: false },
        ],
        flags: {}
    },
    // マップ2
    {
        id: 2,
        layout: Array(MAP_WIDTH_TILES * MAP_HEIGHT_TILES).fill(0),
        door: { x: 5, y: 0, code: '1686' },
        npcs: [ // 配列として定義
            {
                x: 3,
                y: 6,
                dialog: ['ゲームやりたいよ～'],
                postItemDialog: ['ありがとう！ちなみにかけ算が好き！かけ算！'],
                imageKey: 'npc', // 画像キー
            },
        ],
        items: [
            { type: 'board', x: 2, y: 2, variant: 'board2', collected: false }, 
            { type: 'board', x: 4, y: 2, variant: 'board3', collected: false },
            { type: 'item',  x: 8, y: 4, collected: false }, 
        ],
        flags: {}
    },
    // マップ3
    {
        id: 3,
        layout: Array(MAP_WIDTH_TILES * MAP_HEIGHT_TILES).fill(0),
        door: { x: 5, y: 0, code: '4649' },
        npcs: [ // 配列として定義
            {
                x: 3,
                y: 5,
                dialog: ['甘いお菓子がたくさん食べたいよ～'],
                postItemDialog: ['ありがとう！ちなみに好きな野菜の順番は、トマト、玉ねぎ、ジャガイモ、にんじん！'],
                imageKey: 'npc2', // 画像キー
            },
        ],
        items: [
            { type: 'board',  x: 6, y: 5, variant: 'board4', collected: false }, // ★board4
            { type: 'tv',     x: 3, y: 2, collected: false },
            { type: 'item2',  x: 8, y: 1, collected: false },                   // ★item2
            { type: 'item2',  x: 7, y: 8, collected: false },                   // ★item2
            { type: 'item2',  x: 8, y: 7, collected: false },                   // ★item2
        ],
        flags: {}
    },
    // マップ4
    {
        id: 4,
        layout: Array(MAP_WIDTH_TILES * MAP_HEIGHT_TILES).fill(0),
        door: { x: 5, y: 0, code: '3032' },
        npcs: [ // 配列として定義
            {
                x: 2,
                y: 4,
                dialog: ['動かし過ぎた頭をリセット！'],
                imageKey: 'npc', // 画像キー
            },
            {
                x: 3, // 座標を変更して重複を避ける
                y: 4,
                dialog: ['朝ごはんを意識してちゃんと食べよう！'],
                imageKey: 'npc', // 画像キー
            },
            {
                x: 7,
                y: 3,
                dialog: ['「DJ kjの今週の1曲」て知ってる？何番目の曲が好き？そこのパソコンで調べられるよ！'],
                imageKey: 'npc2', // 画像キー
            },
        ],
        items: [
            { type: 'board',  x: 2, y: 5, variant: 'board5', collected: false },
            { type: 'board',  x: 3, y: 5, variant: 'board6', collected: false },
            { type: 'pc',     x: 8, y: 1, collected: false, url: 'https://mapihouse.com/mapiplaylist/' },
        ],
        flags: {}
    },
];

/* マップ初期化 */
maps.forEach(map => {
    // 床埋め
    for (let y = 0; y < MAP_HEIGHT_TILES; y++) {
        for (let x = 0; x < MAP_WIDTH_TILES; x++) {
            map.layout[y * MAP_WIDTH_TILES + x] = 0;
        }
    }
    // 周囲を壁に
    for (let y = 0; y < MAP_HEIGHT_TILES; y++) {
        for (let x = 0; x < MAP_WIDTH_TILES; x++) {
            if (x === 0 || x === MAP_WIDTH_TILES - 1 || y === 0 || y === MAP_HEIGHT_TILES - 1) {
                map.layout[y * MAP_WIDTH_TILES + x] = 1;
            }
        }
    }
    // ドア
    const doorIndex = map.door.y * MAP_WIDTH_TILES + map.door.x;
    map.layout[doorIndex] = 2;

    // アイテム配置 (NPC=3は使わず別描画)
    map.items.forEach(item => {
        const idx = item.y * MAP_WIDTH_TILES + item.x;
        if (item.type === 'item') {
            map.layout[idx] = 4;
        } else if (item.type === 'tv') {
            map.layout[idx] = 5;
        } else if (item.type === 'board') {
            map.layout[idx] = 6;
        } else if (item.type === 'item2') {
            map.layout[idx] = 7; // 新規
        } else if (item.type === 'pc') {
            map.layout[idx] = 8; // PC
        }
    });
});

/* プレイヤー */
let currentMapIndex = 0;
let player = {
    x: 1,
    y: 8,
    direction: 'down',
    frame: 0,
    animationLastUpdate: 0,
    moving: false,
    targetX: 1,
    targetY: 8,
    moveProgress: 0,
    lastMoveUpdate: 0
};

let showingMessage = false;
const ANIMATION_SPEED = 200;
const MOVE_DURATION = 300;

/* 初期化 */
function init() {
    console.log("Initializing game...");
    endroll.classList.add('hidden');
    endroll.style.display = 'none';
    tvOverlay.classList.add('hidden');
    boardOverlay.classList.add('hidden');
    messageBox.classList.add('hidden');
    codeInputModal.classList.add('hidden');
    confirmModal.classList.add('hidden'); // 確認モーダルを非表示

    adjustCanvasSize();

    window.addEventListener('keydown', handleKeyDown);
    canvas.addEventListener('click', handleCanvasClick);
    submitCodeButton.addEventListener('click', handleSubmitCode);
    //restartButton.addEventListener('click', restartGame);
    document.getElementById('cancel-code').addEventListener('click', cancelCodeInput);

    messageBox.addEventListener('click', hideMessage);
    tvOverlay.addEventListener('click', hideTvOverlay);
    boardOverlay.addEventListener('click', hideBoardOverlay);

    // Confirm Modalのボタンイベントリスナー
    confirmYesButton.addEventListener('click', handleConfirmYes);
    confirmNoButton.addEventListener('click', handleConfirmNo);

    // スワイプ操作
    let touchStartX = null;
    let touchStartY = null;

    // スワイプ開始
    swipeArea.addEventListener('touchstart', e => {
        // 操作を行うのでスクロール防止
        e.preventDefault(); // ←ここでブラウザスクロールを抑止
        if (e.touches.length === 1 && !showingMessage && !player.moving) {
            touchStartX = e.touches[0].clientX;
            touchStartY = e.touches[0].clientY;
        }
    }, { passive: false });

    // スワイプ移動中も防止
    swipeArea.addEventListener('touchmove', e => {
        e.preventDefault(); // スクロール防止
    }, { passive: false });

    // スワイプ終了
    swipeArea.addEventListener('touchend', e => {
        e.preventDefault(); // スクロール防止
        if (showingMessage || player.moving) return;
        if (touchStartX === null || touchStartY === null) return;

        const touchEndX = e.changedTouches[0].clientX;
        const touchEndY = e.changedTouches[0].clientY;
        const deltaX = touchEndX - touchStartX;
        const deltaY = touchEndY - touchStartY;
        const swipeThreshold = 30;

        let direction = null;
        if (Math.abs(deltaX) > Math.abs(deltaY)) {
            if (deltaX > swipeThreshold) direction = 'right';
            else if (deltaX < -swipeThreshold) direction = 'left';
        } else {
            if (deltaY > swipeThreshold) direction = 'down';
            else if (deltaY < -swipeThreshold) direction = 'up';
        }
        if (direction) {
            initiateMove(direction);
        }
        touchStartX = null;
        touchStartY = null;
    }, { passive: false });

    window.addEventListener('resize', adjustCanvasSize);
    requestAnimationFrame(gameLoop);
}

/* キャンバスサイズ調整 */
function adjustCanvasSize() {
    const availableWidth = window.innerWidth * 0.9;
    const availableHeight = window.innerHeight * 0.9;
    const size = Math.floor(Math.min(availableWidth, availableHeight));
    TILE_SIZE = Math.floor(size / MAP_WIDTH_TILES);

    canvas.width = MAP_WIDTH_TILES * TILE_SIZE;
    canvas.height = MAP_HEIGHT_TILES * TILE_SIZE;

    canvas.style.width = canvas.width + 'px';
    canvas.style.height = canvas.height + 'px';
    console.log(`Canvas size set to ${canvas.width}x${canvas.height}`);
}

function gameLoop(timestamp) {
    update(timestamp);
    draw();
    requestAnimationFrame(gameLoop);
}

function update(timestamp) {
    // プレイヤーアニメーション
    if (player.moving) {
        if (player.animationLastUpdate === 0) {
            player.animationLastUpdate = timestamp;
        }
        const playerDelta = timestamp - player.animationLastUpdate;
        if (playerDelta > ANIMATION_SPEED) {
            player.frame = (player.frame + 1) % 2;
            player.animationLastUpdate = timestamp;
        }
    } else {
        player.frame = 0;
    }

    // 移動補間
    if (player.moving) {
        player.moveProgress += (timestamp - player.lastMoveUpdate) / MOVE_DURATION;
        if (player.moveProgress >= 1) {
            player.x = player.targetX;
            player.y = player.targetY;
            player.moving = false;
            player.moveProgress = 0;
            player.animationLastUpdate = 0;
            console.log(`Player moved to (${player.x}, ${player.y})`);
        } else {
            player.lastMoveUpdate = timestamp;
        }
    }
}

function draw() {
    const map = maps[currentMapIndex];
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    // タイル描画 (NPC(3)は非タイル)
    for (let y = 0; y < MAP_HEIGHT_TILES; y++) {
        for (let x = 0; x < MAP_WIDTH_TILES; x++) {
            const tile = map.layout[y * MAP_WIDTH_TILES + x];
            if (tileImages[tile]) {
                ctx.drawImage(tileImages[tile], x * TILE_SIZE, y * TILE_SIZE, TILE_SIZE, TILE_SIZE);
            }
        }
    }

	// NPCは別描画(透過PNG)
	if (map.npcs && Array.isArray(map.npcs)) {
	    map.npcs.forEach(npc => {
	        drawNPC(npc);
	    });
	}

    // プレイヤー描画
    drawPlayerWithMovement();
}

// NPC描画
function drawNPC(npc) {
    if (!npc || !images[npc.imageKey]) return;
    // NPC画像は `npc.imageKey` に基づいて描画
    ctx.drawImage(images[npc.imageKey], npc.x * TILE_SIZE, npc.y * TILE_SIZE, TILE_SIZE, TILE_SIZE);
}

// プレイヤー描画
function drawPlayerWithMovement() {
    const frameWidth = 16;
    const frameHeight = 16;
    let frameIndex = 0;
    let flip = false;

    switch (player.direction) {
        case 'left':
            frameIndex = 4;
            flip = true;
            break;
        case 'up':
            frameIndex = 2;
            break;
        case 'right':
            frameIndex = 4;
            break;
        case 'down':
            frameIndex = 0;
            break;
    }

    if (player.moving) {
        frameIndex += player.frame;
    }

    let renderX = player.x;
    let renderY = player.y;
    if (player.moving) {
        renderX += (player.targetX - player.x) * player.moveProgress;
        renderY += (player.targetY - player.y) * player.moveProgress;
    }

    ctx.save();
    if (flip) {
        ctx.translate((renderX + 1) * TILE_SIZE, renderY * TILE_SIZE);
        ctx.scale(-1, 1);
        ctx.drawImage(images.player,
            frameIndex * frameWidth, 0,
            frameWidth, frameHeight,
            0, 0,
            TILE_SIZE, TILE_SIZE
        );
    } else {
        ctx.drawImage(images.player,
            frameIndex * frameWidth, 0,
            frameWidth, frameHeight,
            renderX * TILE_SIZE, renderY * TILE_SIZE,
            TILE_SIZE, TILE_SIZE
        );
    }
    ctx.restore();
}

// キー入力
function handleKeyDown(e) {
    if (showingMessage || player.moving) return;
    let direction = null;
    switch(e.key) {
        case 'ArrowUp': direction = 'up'; break;
        case 'ArrowDown': direction = 'down'; break;
        case 'ArrowLeft': direction = 'left'; break;
        case 'ArrowRight': direction = 'right'; break;
        default: return;
    }
    if (direction) {
        initiateMove(direction);
    }
}

function handleCanvasClick() {
    // 何もしない
}

// 移動開始
function initiateMove(direction) {
    const map = maps[currentMapIndex];
    let newX = player.x;
    let newY = player.y;

    switch (direction) {
        case 'up': newY--; break;
        case 'down': newY++; break;
        case 'left': newX--; break;
        case 'right': newX++; break;
    }
    if (newX < 0 || newX >= MAP_WIDTH_TILES || newY < 0 || newY >= MAP_HEIGHT_TILES) return;

    const tile = map.layout[newY * MAP_WIDTH_TILES + newX];
    if (tile === 1) return; // 壁

    player.direction = direction;

    // ドア
    if (tile === 2) {
        promptCodeInput(map.door.code, () => {
            if (currentMapIndex < maps.length - 1) {
                currentMapIndex++;
                resetPlayerPosition();
                console.log(`Moved to map ${currentMapIndex + 1}`);
                playSound('door');
            } else {
                showEndroll();
                console.log("All maps cleared. Showing endroll.");
            }
        });
        return;
    }

    // Board (6)
    if (tile === 6) {
        console.log("Board encountered.");
        const boardItem = map.items.find(it => it.x === newX && it.y === newY && !it.collected && it.type === 'board');
        if (boardItem) {
            interactWithBoard(boardItem.variant);
        }
        return;
    }

    // TV (5)
    if (tile === 5) {
        console.log("TV encountered.");
        const tvItem = map.items.find(it => it.x === newX && it.y === newY && !it.collected && it.type === 'tv');
        if (tvItem) {
            interactWithTv();
        }
        return;
    }

	// NPC（座標判定） tile ではなく npcs配列内の各NPCのx,yで判定
	if (map.npcs && Array.isArray(map.npcs)) {
	    const encounteredNPC = map.npcs.find(npc => newX === npc.x && newY === npc.y);
	    if (encounteredNPC) {
	        console.log("NPC encountered.");
	        interactWithNPC(encounteredNPC);
	        return;
	    }
	}

    // PC (8)
    if (tile === 8) {
        console.log("PC encountered.");
        const pcItem = map.items.find(it => it.x === newX && it.y === newY && it.type === 'pc' && !it.collected);
        if (pcItem) {
            interactWithPC(pcItem);
        }
        return;
    }

    // アイテム (4) or アイテム2 (7)
    if (tile === 4 || tile === 7) {
        // 対象アイテムを検索
        const item = map.items.find(it => !it.collected && it.x === newX && it.y === newY && (
            (tile === 4 && it.type === 'item') || (tile === 7 && it.type === 'item2')
        ));
        if (item) {
            collectItem(item);
            map.layout[newY * MAP_WIDTH_TILES + newX] = 0;
        }
    }

    // 移動実行
    player.moving = true;
    player.targetX = newX;
    player.targetY = newY;
    player.moveProgress = 0;
    player.lastMoveUpdate = performance.now();
}

// プレイヤー位置リセット
function resetPlayerPosition() {
    player.x = 1;
    player.y = 8;
    player.direction = 'down';
    player.frame = 0;
    player.animationLastUpdate = 0;
    player.moving = false;
    player.targetX = 1;
    player.targetY = 8;
    player.moveProgress = 0;
    player.lastMoveUpdate = 0;
    hideMessage();
    tvOverlay.classList.add('hidden');
    boardOverlay.classList.add('hidden');
}

/* 暗証番号入力 */
function promptCodeInput(correctCode, onSuccess) {
    showingMessage = true;
    codeInputModal.classList.remove('hidden');
    codeInput.value = '';
    console.log("Displaying code input modal.");

    submitCodeButton.onclick = () => {
        const enteredCode = codeInput.value;
        console.log(`Entered code: ${enteredCode}, Correct code: ${correctCode}`);
        if (enteredCode === correctCode) {
            hideCodeInput();
            console.log("Correct code entered.");
            onSuccess();
        } else {
            showMessage('暗証番号が違います。');
            console.log("Incorrect code entered.");
        }
    };
}

function cancelCodeInput() {
    hideCodeInput();
    console.log("Code input canceled.");
}

function hideCodeInput() {
    codeInputModal.classList.add('hidden');
    hideMessage();
    showingMessage = false;
    console.log("Hiding code input modal.");
}

/* NPC 対話 */
function interactWithNPC(npc) {
    showingMessage = true;
    let dialogs = npc.dialog;
    
    playSound('use');

    if (currentMapIndex === 1) {
        // マップ2のアイテム取得後
        const itemCollected = maps[1].items.some(it => it.type === 'item' && it.collected);
        if (itemCollected && npc.postItemDialog) {
            dialogs = npc.postItemDialog;
        }
    } else if (currentMapIndex === 2) {
        // マップ3の item2 が3つすべて収集された場合
        const allItem2Collected = maps[2].items.filter(it => it.type === 'item2').every(it => it.collected);
        if (allItem2Collected && npc.postItemDialog) {
            dialogs = npc.postItemDialog;
        }
    }

    showMessage(dialogs.join('\n'));
    console.log("Displaying NPC dialog.");
}

/* Board インタラクション */
function interactWithBoard(variant) {
    showingMessage = true;
    let boardImageSrc = '';
    switch (variant) {
        case 'board1': boardImageSrc = 'assets/board1.png'; break;
        case 'board2': boardImageSrc = 'assets/board2.png'; break;
        case 'board3': boardImageSrc = 'assets/board3.png'; break;
        case 'board4': boardImageSrc = 'assets/board4.png'; break;
        case 'board5': boardImageSrc = 'assets/board5.png'; break;
        case 'board6': boardImageSrc = 'assets/board6.png'; break;
        default:
            console.error(`Unknown board variant: ${variant}`);
            return;
    }
    const boardImg = boardOverlay.querySelector('img');
    if (boardImg) {
        boardImg.src = boardImageSrc;
    }

    playSound('use');

    boardOverlay.classList.remove('hidden');
    console.log(`Displaying Board image: ${boardImageSrc}`);
}

/* TV インタラクション */
function interactWithTv() {
    showingMessage = true;
    tvOverlay.classList.remove('hidden');
    console.log("Displaying TV animation.");
    
    playSound('use');
}

/* PCとのインタラクションを処理する関数 */
function interactWithPC(pc) {
    showingMessage = true;
    playSound('use');

    showConfirmModal(
        '「DJ kj」について調べますか？',
        () => handlePCYes(pc.url), // 各PCに対応したURLを渡す
        handlePCNo
    );

    console.log("Displaying PC interaction prompt.");
}

/* Confirm Modalを表示する関数 */
function showConfirmModal(message, onYes, onNo) {
    confirmText.textContent = message;
    confirmModal.classList.remove('hidden');

    // コールバック関数を保存
    confirmModal.onYes = onYes;
    confirmModal.onNo = onNo;
}

/* Confirm Modalを非表示にする関数 */
function hideConfirmModal() {
    confirmModal.classList.add('hidden');
    confirmText.textContent = '';
    confirmModal.onYes = null;
    confirmModal.onNo = null;
}

/* Confirm Modalのボタンハンドラー */
function handleConfirmYes() {
    if (confirmModal.onYes && typeof confirmModal.onYes === 'function') {
        confirmModal.onYes();
    }
    hideConfirmModal();
}

function handleConfirmNo() {
    if (confirmModal.onNo && typeof confirmModal.onNo === 'function') {
        confirmModal.onNo();
    }
    hideConfirmModal();
}

/* 「はい」が選択された場合の処理 */
function handlePCYes(url) {
    window.open(url, '_blank');
    console.log(`Opening URL: ${url}`);
    showingMessage = false;
}

/* 「いいえ」が選択された場合の処理 */
function handlePCNo() {
    console.log("PC interaction canceled.");
    showingMessage = false;
}

/* アイテム取得 (item / item2) */
function collectItem(item) {
    if (!item.collected) {
        item.collected = true;
        maps[currentMapIndex].flags.itemCollected = true;

        // メッセージを分けたい場合
        if (item.type === 'item2') {
            showMessage('どんぐりケーキを手に入れた！');
        } else {
            showMessage('ゲーム機を手に入れた！');
        }

        playSound('collect');
        console.log("Item collected.");
    }
}

/* メッセージ表示 */
function showMessage(text) {
    messageText.textContent = text;
    messageBox.classList.remove('hidden');
    showingMessage = true;
    console.log(`Showing message: ${text}`);
}
function hideMessage() {
    messageBox.classList.add('hidden');
    showingMessage = false;
    console.log("Hiding message box.");
}

/* エンドロール全画面 */
function showEndroll() {
    endroll.classList.remove('hidden');
    endroll.style.display = 'flex'; // 表示する
    console.log("Displaying endroll.");
    
    stopSound('bgm');
    playSound('ed');
}

function startEndrollAnimation() {
    const endrollText = document.getElementById('endroll-text');
    // 下から上へ
    requestAnimationFrame(() => {
        endrollText.style.transform = 'translateY(-100%)';
    });
}

/* リスタート */
function restartGame() {
    console.log("Restarting game.");
    currentMapIndex = 0;
    resetPlayerPosition();
    maps.forEach(map => {
        map.items.forEach(it => {
            it.collected = false;
            if (it.type === 'item') {
                map.layout[it.y * MAP_WIDTH_TILES + it.x] = 4;
            } else if (it.type === 'tv') {
                map.layout[it.y * MAP_WIDTH_TILES + it.x] = 5;
            } else if (it.type === 'board') {
                map.layout[it.y * MAP_WIDTH_TILES + it.x] = 6;
            } else if (it.type === 'item2') {
                map.layout[it.y * MAP_WIDTH_TILES + it.x] = 7;
            } else if (it.type === 'pc') {
                map.layout[it.y * MAP_WIDTH_TILES + it.x] = 8;
            }
        });
        map.flags = {};
    });
    tvOverlay.classList.add('hidden');
    boardOverlay.classList.add('hidden');
    endroll.classList.add('hidden');
    endroll.style.display = 'none';
    console.log("Game restarted.");
}

function handleSubmitCode() {
    // promptCodeInput内で処理済
}

/* ドキュメント読み込み後の処理 */
document.addEventListener("DOMContentLoaded", () => {
    const titleScreen = document.getElementById('title-screen');
    const gameContainer = document.getElementById('game-container');

    // タイトル画面のタップイベント
    titleScreen.addEventListener('click', () => {
        // タイトル画面を非表示
        titleScreen.classList.add('hidden');
        // ゲーム画面を表示
        gameContainer.classList.remove('hidden');
        
        playSound('bgm');
        
        // ゲーム初期化
        init();
    });
});

// TVオーバーレイを非表示にする関数
function hideTvOverlay() {
    tvOverlay.classList.add('hidden');
    showingMessage = false;
    console.log("TVオーバーレイを非表示にしました。");
}

// Boardオーバーレイを非表示にする関数
function hideBoardOverlay() {
    boardOverlay.classList.add('hidden');
    showingMessage = false;
    console.log("Boardオーバーレイを非表示にしました。");
}
