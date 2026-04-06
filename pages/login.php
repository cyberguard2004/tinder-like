<?php
session_start();
if (isset($_SESSION["user_id"])) {
    header("Location: dashboard.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
  <title>Login - MatchFace Campus</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs@2.0.0/dist/tf.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/face-api.js@0.22.2/dist/face-api.min.js"></script>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Inter', sans-serif;
      min-height: 100vh;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 1.5rem;
      position: relative;
      overflow-x: hidden;
    }

    /* Animated background bubbles */
    body::before {
      content: '';
      position: absolute;
      width: 100%;
      height: 100%;
      top: 0;
      left: 0;
      overflow: hidden;
      z-index: 0;
    }

    .bubble {
      position: absolute;
      background: rgba(255, 255, 255, 0.1);
      border-radius: 50%;
      animation: float 8s infinite ease-in-out;
      pointer-events: none;
    }

    @keyframes float {
      0%, 100% { transform: translateY(0) translateX(0); opacity: 0.3; }
      50% { transform: translateY(-30px) translateX(15px); opacity: 0.6; }
    }

    /* Auth wrapper */
    .auth-wrapper {
      position: relative;
      z-index: 10;
      width: 100%;
      max-width: 460px;
    }

    /* Main card */
    .auth-card {
      background: rgba(255, 255, 255, 0.98);
      backdrop-filter: blur(10px);
      border-radius: 2rem;
      padding: 2rem 1.8rem;
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35);
      transition: transform 0.3s ease, box-shadow 0.3s ease;
      animation: slideUp 0.5s cubic-bezier(0.2, 0.9, 0.4, 1.1);
    }

    .auth-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 30px 55px -12px rgba(0, 0, 0, 0.4);
    }

    @keyframes slideUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    /* Brand */
    .auth-brand {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.6rem;
      font-size: 1.8rem;
      font-weight: 800;
      background: linear-gradient(135deg, #fd5068, #fc7c45);
      -webkit-background-clip: text;
      background-clip: text;
      color: transparent;
      margin-bottom: 1.5rem;
    }

    .auth-brand span {
      font-size: 2rem;
      background: none;
      -webkit-background-clip: unset;
      background-clip: unset;
      color: #fd5068;
    }

    .auth-card h2 {
      font-size: 1.8rem;
      font-weight: 700;
      color: #1a1a2e;
      text-align: center;
      margin-bottom: 0.3rem;
    }

    .auth-subtitle {
      text-align: center;
      color: #888;
      font-size: 0.9rem;
      margin-bottom: 1.8rem;
    }

    /* Tabs */
    .login-tabs {
      display: flex;
      gap: 0.75rem;
      margin-bottom: 1.8rem;
      background: #f1f3f7;
      border-radius: 60px;
      padding: 0.3rem;
    }

    .login-tab {
      flex: 1;
      padding: 0.7rem 0.5rem;
      border: none;
      background: transparent;
      border-radius: 50px;
      cursor: pointer;
      font-family: inherit;
      font-size: 0.85rem;
      font-weight: 600;
      color: #888;
      transition: all 0.25s ease;
    }

    .login-tab i {
      margin-right: 6px;
      font-size: 0.85rem;
    }

    .login-tab.active {
      background: white;
      color: #fd5068;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
    }

    /* Panels */
    .login-panel {
      animation: fadeIn 0.35s ease-out;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
        transform: translateY(8px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    /* Face scan container */
    .face-scan-container {
      position: relative;
      width: 100%;
      max-width: 320px;
      margin: 0 auto 1rem auto;
      border-radius: 20px;
      overflow: hidden;
      background: #0a0a0a;
      aspect-ratio: 4/3;
      box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.2);
    }

    .face-scan-container video {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }

    .face-scan-container .overlay {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      pointer-events: none;
    }

    /* Scan status */
    .scan-status {
      text-align: center;
      font-size: 0.85rem;
      font-weight: 500;
      padding: 0.6rem 1rem;
      background: #f8f9fc;
      border-radius: 40px;
      margin-bottom: 1rem;
      transition: all 0.2s;
    }

    /* Form groups */
    .form-group {
      margin-bottom: 1.2rem;
    }

    .form-group label {
      display: block;
      font-size: 0.8rem;
      font-weight: 600;
      color: #444;
      margin-bottom: 0.4rem;
    }

    .form-group label i {
      margin-right: 0.4rem;
      color: #fd5068;
    }

    .form-group input {
      width: 100%;
      padding: 0.8rem 1rem;
      border: 2px solid #e8ecf0;
      border-radius: 14px;
      font-family: inherit;
      font-size: 0.95rem;
      color: #1a1a2e;
      background: #fff;
      transition: all 0.2s ease;
      box-sizing: border-box;
    }

    .form-group input:focus {
      outline: none;
      border-color: #fd5068;
      box-shadow: 0 0 0 3px rgba(253, 80, 104, 0.12);
      transform: scale(1.01);
    }

    /* Error inline */
    .error-inline {
      background: #fff0f0;
      color: #e53e3e;
      padding: 0.7rem 1rem;
      border-radius: 12px;
      font-size: 0.85rem;
      margin-bottom: 1rem;
      animation: shake 0.3s ease;
    }

    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      25% { transform: translateX(-5px); }
      75% { transform: translateX(5px); }
    }

    /* Buttons */
    .btn-primary {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 0.6rem;
      width: 100%;
      padding: 0.9rem 1.5rem;
      background: linear-gradient(135deg, #fd5068, #fc7c45);
      color: white;
      border: none;
      border-radius: 60px;
      font-family: inherit;
      font-size: 1rem;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.25s ease;
      text-decoration: none;
    }

    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(253, 80, 104, 0.4);
    }

    .btn-primary:active {
      transform: translateY(0);
    }

    .btn-primary:disabled {
      opacity: 0.6;
      cursor: not-allowed;
      transform: none;
    }

    /* Auth link */
    .auth-link {
      text-align: center;
      font-size: 0.85rem;
      color: #888;
      margin-top: 1.5rem;
    }

    .auth-link a {
      color: #fd5068;
      font-weight: 600;
      text-decoration: none;
      transition: color 0.2s;
    }

    .auth-link a:hover {
      text-decoration: underline;
      color: #fc7c45;
    }

    /* Loading animation for camera */
    .face-cam-loading {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      height: 100%;
      color: #aaa;
      gap: 0.75rem;
      font-size: 0.85rem;
      background: #0a0a0a;
    }

    .face-spinner {
      width: 36px;
      height: 36px;
      border: 3px solid rgba(255, 255, 255, 0.2);
      border-top-color: #fd5068;
      border-radius: 50%;
      animation: spin 0.8s linear infinite;
    }

    @keyframes spin {
      to { transform: rotate(360deg); }
    }

    /* Responsive */
    @media (max-width: 480px) {
      .auth-card {
        padding: 1.5rem;
      }
      .auth-card h2 {
        font-size: 1.5rem;
      }
      .login-tab {
        font-size: 0.75rem;
      }
    }
  </style>
</head>
<body>

<!-- Animated bubbles -->
<div id="bubbles"></div>

<div class="auth-wrapper">
  <div class="auth-card">
    <div class="auth-brand">
      <span>👥</span> MatchFace Campus
    </div>

    <h2>Welcome back!</h2>
    <p class="auth-subtitle">Sign in to your account</p>

    <!-- Tabs -->
    <div class="login-tabs">
      <button class="login-tab active" id="tabFace" onclick="switchTab('face')">
        <i class="fas fa-camera"></i> Facial Login
      </button>
      <button class="login-tab" id="tabPassword" onclick="switchTab('password')">
        <i class="fas fa-lock"></i> Password
      </button>
    </div>

    <!-- Face Login Panel -->
    <div id="panelFace" class="login-panel">
      <div class="face-scan-container" id="faceScanContainer">
        <div class="face-cam-loading" id="camLoadingMsg">
          <div class="face-spinner"></div>
          <span>Initializing camera...</span>
        </div>
        <video id="video" autoplay muted playsinline style="display:none"></video>
        <canvas id="overlay" class="overlay"></canvas>
      </div>
      <div class="scan-status" id="scanStatus">Loading AI models...</div>
      <button id="faceLoginBtn" class="btn-primary" onclick="loginWithFace()" disabled>
        <i class="fas fa-sign-in-alt"></i> Login with Face
      </button>
    </div>

    <!-- Password Login Panel -->
    <div id="panelPassword" class="login-panel" style="display:none">
      <div class="form-group">
        <label><i class="fas fa-envelope"></i> Email</label>
        <input type="email" id="loginEmail" placeholder="your@email.com" autocomplete="email">
      </div>
      <div class="form-group">
        <label><i class="fas fa-lock"></i> Password</label>
        <input type="password" id="loginPassword" placeholder="Your password" autocomplete="current-password">
      </div>
      <div id="loginError" class="error-inline" style="display:none"></div>
      <button class="btn-primary" onclick="loginWithPassword()">
        <i class="fas fa-sign-in-alt"></i> Sign In
      </button>
    </div>

    <p class="auth-link">Don't have an account? <a href="register.php">Sign up for free</a></p>
  </div>
</div>

<script src="../assets/js/face-recognition.js"></script>
<script>
/* ============================================================
   ANIMATED BUBBLES
   ============================================================ */
function createBubbles() {
  const container = document.getElementById('bubbles');
  const bubbleCount = 12;
  for (let i = 0; i < bubbleCount; i++) {
    const bubble = document.createElement('div');
    bubble.classList.add('bubble');
    const size = Math.random() * 80 + 30;
    bubble.style.width = size + 'px';
    bubble.style.height = size + 'px';
    bubble.style.left = Math.random() * 100 + '%';
    bubble.style.bottom = '-' + (Math.random() * 20 + 10) + 'px';
    bubble.style.animationDuration = Math.random() * 8 + 6 + 's';
    bubble.style.animationDelay = Math.random() * 5 + 's';
    container.appendChild(bubble);
  }
}
createBubbles();

/* ============================================================
   TAB SWITCHING
   ============================================================ */
function switchTab(tab) {
  const isFace = (tab === 'face');

  document.getElementById('panelFace').style.display = isFace ? 'block' : 'none';
  document.getElementById('panelPassword').style.display = isFace ? 'none' : 'block';
  document.getElementById('tabFace').classList.toggle('active', isFace);
  document.getElementById('tabPassword').classList.toggle('active', !isFace);

  if (isFace && window._faceLoginReady) {
    startFaceCamera();
  }
  if (!isFace && window._faceLoginRecognition) {
    if (window._faceLoginRecognition.stopDetectionLoop) window._faceLoginRecognition.stopDetectionLoop();
    if (window._faceLoginRecognition.stopCamera) window._faceLoginRecognition.stopCamera();
  }
}

/* ============================================================
   FACE LOGIN
   ============================================================ */
let _faceRecognition = null;
let _faceDetected = false;
window._faceLoginReady = false;
window._faceLoginRecognition = null;

async function initFaceLogin() {
  setStatus('Loading AI models...', 'loading');
  try {
    _faceRecognition = new FaceRecognition();
    window._faceLoginRecognition = _faceRecognition;

    await _faceRecognition.loadModels();
    setStatus('Models ready. Starting camera...', 'loading');
    await startFaceCamera();
    window._faceLoginReady = true;
  } catch (err) {
    console.error('initFaceLogin error:', err);
    setStatus('Init error: ' + err.message, 'error');
  }
}

async function startFaceCamera() {
  const video = document.getElementById('video');
  const canvas = document.getElementById('overlay');
  const loading = document.getElementById('camLoadingMsg');

  const started = await _faceRecognition.startVideo(video);
  if (!started) {
    setStatus('Cannot access camera. Check permissions.', 'error');
    return;
  }

  loading.style.display = 'none';
  video.style.display = 'block';

  setStatus('Position your face in the frame...', 'info');

  _faceRecognition.startDetectionLoop(async (result) => {
    if (result && result.descriptor) {
      _faceDetected = true;
      document.getElementById('faceLoginBtn').disabled = false;
      setStatus('✅ Face detected — click "Login with Face"', 'success');
      _faceRecognition.drawFaceDetection(canvas);
    } else {
      _faceDetected = false;
      document.getElementById('faceLoginBtn').disabled = true;
      setStatus('👤 Place your face in front of the camera', 'info');
      const ctx = canvas.getContext('2d');
      ctx.clearRect(0, 0, canvas.width, canvas.height);
    }
  }, 600);
}

async function loginWithFace() {
  if (!_faceRecognition) return;

  const btn = document.getElementById('faceLoginBtn');
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Identifying...';

  try {
    const face = await _faceRecognition.detectFace();
    if (!face || !face.descriptor) {
      setStatus('No face detected. Try again.', 'error');
      btn.disabled = false;
      btn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Login with Face';
      return;
    }

    const descriptorArray = Array.from(face.descriptor);

    const response = await fetch('/tinder/api/login-face.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ face_vector: descriptorArray })
    });

    const data = await response.json();

    if (data.success) {
      setStatus('✅ Login successful! Redirecting...', 'success');
      if (_faceRecognition.stopDetectionLoop) _faceRecognition.stopDetectionLoop();
      if (_faceRecognition.stopCamera) _faceRecognition.stopCamera();
      setTimeout(() => {
        window.location.href = 'dashboard.php';
      }, 800);
    } else {
      setStatus('❌ ' + (data.error || 'Face not recognized.'), 'error');
      btn.disabled = false;
      btn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Login with Face';
    }
  } catch (err) {
    console.error('loginWithFace error:', err);
    setStatus('Network error: ' + err.message, 'error');
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Login with Face';
  }
}

function setStatus(msg, type) {
  const el = document.getElementById('scanStatus');
  el.textContent = msg;
  el.style.background = type === 'success' ? '#e8f5e9'
                       : type === 'error' ? '#ffebee'
                       : type === 'loading' ? '#f1f3f7'
                       : '#f8f9fc';
  el.style.color = type === 'success' ? '#27ae60'
                 : type === 'error' ? '#e53e3e'
                 : '#555';
}

/* ============================================================
   PASSWORD LOGIN
   ============================================================ */
async function loginWithPassword() {
  const email = document.getElementById('loginEmail').value.trim();
  const password = document.getElementById('loginPassword').value;
  const errEl = document.getElementById('loginError');

  errEl.style.display = 'none';
  errEl.textContent = '';

  if (!email || !password) {
    showLoginError('Please fill in all fields.');
    return;
  }

  const btn = document.querySelector('#panelPassword .btn-primary');
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Signing in...';

  try {
    const response = await fetch('/tinder/api/login-password.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email, password })
    });

    const data = await response.json();

    if (data.success) {
      window.location.href = 'dashboard.php';
    } else {
      showLoginError(data.error || 'Incorrect email or password.');
      btn.disabled = false;
      btn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Sign In';
    }
  } catch (err) {
    showLoginError('Network error. Please try again.');
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-sign-in-alt"></i> Sign In';
  }
}

function showLoginError(msg) {
  const el = document.getElementById('loginError');
  el.textContent = msg;
  el.style.display = 'block';
  setTimeout(() => {
    if (el.style.display === 'block') el.style.opacity = '1';
  }, 10);
}

document.addEventListener('DOMContentLoaded', () => {
  document.getElementById('loginPassword').addEventListener('keydown', (e) => {
    if (e.key === 'Enter') loginWithPassword();
  });
  document.getElementById('loginEmail').addEventListener('keydown', (e) => {
    if (e.key === 'Enter') loginWithPassword();
  });

  initFaceLogin();
});
</script>
</body>
</html>
