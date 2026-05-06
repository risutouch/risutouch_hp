// 要素の取得
const video = document.getElementById('camera');
const frameCanvas = document.getElementById('frame-canvas');
const previewContainer = document.getElementById('preview-container');
const previewCanvas = document.getElementById('preview');
const previewContext = previewCanvas.getContext('2d');
const shutterButton = document.getElementById('shutter-button');
const cancelButton = document.getElementById('cancel');
const shareButton = document.getElementById('share');
const downloadButton = document.getElementById('download');
const switchCameraButton = document.getElementById('switch-camera');
const prevFrameButton = document.getElementById('prev-frame');
const nextFrameButton = document.getElementById('next-frame');
const bgmToggleButton = document.getElementById('bgm-toggle');
const bgmAudio = document.getElementById('bgm');
// シャッター音を5種類取得
const shutterSounds = [
  document.getElementById('shutter-sound1'),
  document.getElementById('shutter-sound2'),
  document.getElementById('shutter-sound3'),
  document.getElementById('shutter-sound4'),
  document.getElementById('shutter-sound5')
];

let currentCamera = 'environment'; // デフォルトはアウトカメラ
let currentFrameSet = 0;
let frameIndex = 0;
let frameInterval;
let isBgmPlaying = false; // BGMの再生状態

// スマホ向けに調整した正方形サイズ
const squareSize = Math.min(window.innerWidth, window.innerHeight) * 0.9; // 90%に調整
const pixelRatio = window.devicePixelRatio || 1; // デバイスピクセル比で解像度向上

// フレーム情報（フレーム数を正確に設定）
const frameData = [
  { folder: 'frame1', count: 1 },
  { folder: 'frame2', count: 1 },
  { folder: 'frame3', count: 1 },
  { folder: 'frame4', count:1 },
  { folder: 'frame5', count:1 },
  { folder: 'frame6', count:1 },
  { folder: 'frame7', count:1 },
  { folder: 'frame8', count:1 },
  { folder: 'frame9', count:1 },
  { folder: 'frame10', count:1 },
  { folder: 'frame11', count:1 },
  { folder: 'frame12', count:1 },
  { folder: 'frame13', count:1 },
  { folder: 'frame14', count:1 },
  { folder: 'frame15', count:1 },
  { folder: 'frame16', count:1 },
  { folder: 'frame17', count:1 },
  { folder: 'frame18', count:1 },
  { folder: 'frame19', count:1 },
  { folder: 'frame20', count:1 },
  { folder: 'frame21', count:1 },
  { folder: 'frame22', count:1 },
  { folder: 'frame23', count:1 },
  { folder: 'frame24', count:1 },
  { folder: 'frame25', count:1 }
];
const preloadedFrames = [];

// カメラを起動
async function startCamera() {
  try {
    const stream = await navigator.mediaDevices.getUserMedia({
      video: {
        facingMode: currentCamera,
        width: { ideal: squareSize },
        height: { ideal: squareSize },
      },
    });
    video.srcObject = stream;

    // 映像とCanvasのサイズを正しく設定
    video.width = squareSize * pixelRatio;
    video.height = squareSize * pixelRatio;
    frameCanvas.width = squareSize * pixelRatio;
    frameCanvas.height = squareSize * pixelRatio;

    // 見た目のサイズを調整
    video.style.width = `${squareSize}px`;
    video.style.height = `${squareSize}px`;
    video.style.objectFit = 'cover'; // 縦横を均等にカバー
    video.style.margin = '0 auto'; // スマホ画面の中央寄せ
    frameCanvas.style.width = `${squareSize}px`;
    frameCanvas.style.height = `${squareSize}px`;

    if (currentCamera === 'user') {
      video.style.transform = 'scaleX(-1)';
      frameCanvas.style.transform = 'scaleX(-1)';
    } else {
      video.style.transform = 'scaleX(1)';
      frameCanvas.style.transform = 'scaleX(1)';
    }
  } catch (err) {
    alert('カメラの起動に失敗しました: ' + err.message);

    // 以下フェールバック処理追加（指示以外のコードは変更しない）
    try {
      const devices = await navigator.mediaDevices.enumerateDevices();
      const videoDevices = devices.filter(d => d.kind === 'videoinput');
      let targetDevice = null;
      if (currentCamera === 'user') {
        targetDevice = videoDevices.find(d => d.label.toLowerCase().includes('front')) || videoDevices[0];
      } else {
        targetDevice = videoDevices.find(d => d.label.toLowerCase().includes('back') || d.label.toLowerCase().includes('rear')) || videoDevices[0];
      }

      if (targetDevice) {
        const fallbackStream = await navigator.mediaDevices.getUserMedia({
          video: {
            deviceId: { exact: targetDevice.deviceId },
            width: { ideal: squareSize },
            height: { ideal: squareSize },
          },
        });
        video.srcObject = fallbackStream;

        // 映像とCanvasのサイズを正しく設定（既存処理を再利用）
        video.width = squareSize * pixelRatio;
        video.height = squareSize * pixelRatio;
        frameCanvas.width = squareSize * pixelRatio;
        frameCanvas.height = squareSize * pixelRatio;

        // 見た目のサイズを調整（既存処理を再利用）
        video.style.width = `${squareSize}px`;
        video.style.height = `${squareSize}px`;
        video.style.objectFit = 'cover';
        video.style.margin = '0 auto';
        frameCanvas.style.width = `${squareSize}px`;
        frameCanvas.style.height = `${squareSize}px`;

        if (currentCamera === 'user') {
          video.style.transform = 'scaleX(-1)';
          frameCanvas.style.transform = 'scaleX(-1)';
        } else {
          video.style.transform = 'scaleX(1)';
          frameCanvas.style.transform = 'scaleX(1)';
        }
      } else {
        alert('利用可能なカメラが見つかりませんでした。');
      }
    } catch (fallbackErr) {
      alert('フェールバックでもカメラの起動に失敗しました: ' + fallbackErr.message);
    }
  }
}

// フレームを事前ロード
function preloadFrames() {
  frameData.forEach(({ folder, count }, setIndex) => {
    preloadedFrames[setIndex] = [];
    for (let i = 0; i < count; i++) {
      const paddedIndex = String(i).padStart(3, '0');
      const img = new Image();
      img.src = `frame/${folder}/${paddedIndex}.png`;
      preloadedFrames[setIndex].push(img);
    }
  });
}

// フレームアニメーションの開始
function startFrameAnimation() {
  clearInterval(frameInterval);
  const frames = preloadedFrames[currentFrameSet];
  frameIndex = 0;

  frameInterval = setInterval(() => {
    const context = frameCanvas.getContext('2d');
    const img = frames[frameIndex];
    context.clearRect(0, 0, frameCanvas.width, frameCanvas.height);
    context.drawImage(img, 0, 0, frameCanvas.width, frameCanvas.height);

    frameIndex = (frameIndex + 1) % frames.length;
  }, 1000 / 15);
}

// ダブルタップによるズームを防止
let lastTouchEnd = 0;
document.addEventListener('touchend', function(event) {
  const now = new Date().getTime();
  if (now - lastTouchEnd <= 300) {
    event.preventDefault();
  }
  lastTouchEnd = now;
}, false);

// ページ全体のスクロールを防止
document.body.addEventListener('touchmove', function(e) {
  e.preventDefault();
}, { passive: false });

// シャッターボタン押下時の動作
shutterButton.addEventListener('click', () => {
  // シャッター音を再生
  const randomIndex = Math.floor(Math.random() * shutterSounds.length);
  const selectedShutterSound = shutterSounds[randomIndex];

  if (selectedShutterSound) {
    selectedShutterSound.currentTime = 0; // 再生位置をリセット
    selectedShutterSound.volume = 1; // 音量を最大に設定
    selectedShutterSound.play().catch(err => {
      console.error('シャッター音の再生に失敗しました:', err);
    });
  }

  previewCanvas.width = squareSize * pixelRatio; // 高解像度に設定
  previewCanvas.height = squareSize * pixelRatio;

  previewContext.setTransform(pixelRatio, 0, 0, pixelRatio, 0, 0); // ピクセル比調整
  previewContext.clearRect(0, 0, squareSize, squareSize);
  previewContext.drawImage(video, 0, 0, squareSize, squareSize);
  previewContext.drawImage(frameCanvas, 0, 0, squareSize, squareSize);

  previewContainer.style.display = 'flex';
});

// フレーム切り替えボタンのイベントリスナーを追加
prevFrameButton.addEventListener('click', () => {
  currentFrameSet = (currentFrameSet - 1 + frameData.length) % frameData.length;
  startFrameAnimation();
});

nextFrameButton.addEventListener('click', () => {
  currentFrameSet = (currentFrameSet + 1) % frameData.length;
  startFrameAnimation();
});

// カメラ切替ボタン
switchCameraButton.addEventListener('click', async () => {
  currentCamera = currentCamera === 'environment' ? 'user' : 'environment';
  startCamera();
});

// BGM再生/停止ボタン
bgmToggleButton.addEventListener('click', () => {
  if (isBgmPlaying) {
    bgmAudio.pause();
    isBgmPlaying = false;
    bgmToggleButton.querySelector('i').classList.remove('fa-stop');
    bgmToggleButton.querySelector('i').classList.add('fa-music');
    bgmToggleButton.nextElementSibling.textContent = 'BGM';
  } else {
    bgmAudio.play().catch(err => {
      console.error('BGMの再生に失敗しました:', err);
    });
    isBgmPlaying = true;
    bgmToggleButton.querySelector('i').classList.remove('fa-music');
    bgmToggleButton.querySelector('i').classList.add('fa-stop');
    bgmToggleButton.nextElementSibling.textContent = '停止';
  }
});

// 戻るボタン
cancelButton.addEventListener('click', () => {
  previewContainer.style.display = 'none';
});

// シェアボタン
shareButton.addEventListener('click', async () => {
  previewCanvas.toBlob(async (blob) => {
    const file = new File([blob], 'photo.png', { type: 'image/png' });
    if (navigator.canShare && navigator.canShare({ files: [file] })) {
      await navigator.share({
        files: [file],
        title: '撮影した写真',
        text: 'この写真を共有します。',
      });
    } else {
      alert('シェア機能がサポートされていません。');
    }
  });
});

// ダウンロードボタンのイベントリスナーを追加
downloadButton.addEventListener('click', () => {
  previewCanvas.toBlob((blob) => {
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = 'photo.png';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    URL.revokeObjectURL(url); // メモリ解放
  }, 'image/png');
});

// 初期化
startCamera();
preloadFrames();
startFrameAnimation();
