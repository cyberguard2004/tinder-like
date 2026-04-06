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
  <title>Sign Up - MatchFace Campus</title>
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
      position: relative;
      overflow-x: hidden;
    }

    /* Animated background particles */
    .bg-particles {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      overflow: hidden;
      z-index: 0;
    }

    .particle {
      position: absolute;
      background: rgba(255, 255, 255, 0.15);
      border-radius: 50%;
      pointer-events: none;
      animation: floatParticle linear infinite;
    }

    @keyframes floatParticle {
      0% {
        transform: translateY(100vh) rotate(0deg);
        opacity: 0;
      }
      10% { opacity: 0.5; }
      90% { opacity: 0.5; }
      100% {
        transform: translateY(-20vh) rotate(360deg);
        opacity: 0;
      }
    }

    /* Auth wrapper */
    .auth-wrapper {
      position: relative;
      z-index: 10;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 2rem 1rem;
    }

    /* Main card */
    .auth-card {
      background: rgba(255, 255, 255, 0.98);
      backdrop-filter: blur(10px);
      border-radius: 2rem;
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.35);
      padding: 2rem 2rem;
      width: 100%;
      max-width: 580px;
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
      margin-bottom: 1rem;
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

    /* Progress bar */
    .progress-bar {
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 2rem;
      gap: 0;
    }

    .progress-step {
      display: flex;
      flex-direction: column;
      align-items: center;
      position: relative;
      z-index: 1;
    }

    .progress-step .step-dot {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background: #e8ecf0;
      color: #aaa;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      font-size: 1rem;
      transition: all 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
      border: 2.5px solid #e8ecf0;
    }

    .progress-step .step-label {
      font-size: 0.7rem;
      color: #aaa;
      margin-top: 0.4rem;
      font-weight: 600;
      text-align: center;
      letter-spacing: 0.3px;
    }

    .progress-step.active .step-dot {
      background: linear-gradient(135deg, #fd5068, #fc7c45);
      color: #fff;
      border-color: #fd5068;
      box-shadow: 0 4px 14px rgba(253, 80, 104, 0.4);
      transform: scale(1.05);
    }

    .progress-step.active .step-label {
      color: #fd5068;
      font-weight: 700;
    }

    .progress-step.done .step-dot {
      background: #48bb78;
      color: #fff;
      border-color: #48bb78;
    }

    .progress-step.done .step-label {
      color: #48bb78;
    }

    .progress-connector {
      flex: 1;
      height: 3px;
      background: #e8ecf0;
      max-width: 70px;
      margin-bottom: 1.5rem;
      transition: background 0.4s ease;
    }

    .progress-connector.done {
      background: linear-gradient(90deg, #48bb78, #68d391);
    }

    /* Form steps */
    .form-step {
      display: none;
      animation: fadeInUp 0.4s ease-out;
    }

    .form-step.active {
      display: block;
    }

    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(15px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .form-step h3 {
      font-size: 1.2rem;
      font-weight: 700;
      color: #1a1a2e;
      margin-bottom: 0.3rem;
    }

    .form-step > p.step-desc {
      font-size: 0.85rem;
      color: #888;
      margin-bottom: 1.5rem;
    }

    /* Form groups */
    .form-group {
      margin-bottom: 1rem;
    }

    .form-group label {
      display: block;
      font-size: 0.8rem;
      font-weight: 600;
      color: #444;
      margin-bottom: 0.4rem;
    }

    .form-group label i {
      margin-right: 0.35rem;
      color: #fd5068;
    }

    .form-group input,
    .form-group select {
      width: 100%;
      padding: 0.75rem 1rem;
      border: 2px solid #e8ecf0;
      border-radius: 12px;
      font-family: inherit;
      font-size: 0.9rem;
      color: #1a1a2e;
      background: #fff;
      transition: all 0.2s ease;
      box-sizing: border-box;
    }

    .form-group input:focus,
    .form-group select:focus {
      outline: none;
      border-color: #fd5068;
      box-shadow: 0 0 0 3px rgba(253, 80, 104, 0.1);
      transform: scale(1.01);
    }

    .form-group select {
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='7' viewBox='0 0 12 7'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%23999' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 1rem center;
      padding-right: 2.5rem;
      cursor: pointer;
    }

    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 0.75rem;
    }

    .field-error {
      font-size: 0.75rem;
      color: #e53e3e;
      margin-top: 0.3rem;
      display: none;
    }

    .field-error.visible {
      display: block;
      animation: shake 0.3s ease;
    }

    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      25% { transform: translateX(-4px); }
      75% { transform: translateX(4px); }
    }

    .form-group.has-error input,
    .form-group.has-error select {
      border-color: #e53e3e;
      animation: shake 0.3s ease;
    }

    /* Interests section */
    .interets-counter {
      font-size: 0.85rem;
      font-weight: 700;
      color: #fd5068;
      background: #fff5f6;
      border: 1.5px solid #ffd6dc;
      border-radius: 30px;
      padding: 0.4rem 1.2rem;
      margin-bottom: 1rem;
      display: inline-block;
      transition: all 0.2s ease;
    }

    .interets-grid {
      max-height: 340px;
      overflow-y: auto;
      padding-right: 6px;
    }

    .interets-grid::-webkit-scrollbar {
      width: 5px;
    }

    .interets-grid::-webkit-scrollbar-track {
      background: #f0f0f0;
      border-radius: 10px;
    }

    .interets-grid::-webkit-scrollbar-thumb {
      background: #fdcdd3;
      border-radius: 10px;
    }

    .interet-category {
      margin-bottom: 1.2rem;
    }

    .interet-category h4 {
      font-size: 0.7rem;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      color: #aaa;
      margin-bottom: 0.5rem;
    }

    .interets-buttons {
      display: flex;
      flex-wrap: wrap;
      gap: 0.5rem;
    }

    .interet-btn {
      padding: 0.45rem 0.9rem;
      border: 2px solid #e8ecf0;
      border-radius: 30px;
      background: #fff;
      font-family: inherit;
      font-size: 0.8rem;
      font-weight: 500;
      color: #555;
      cursor: pointer;
      transition: all 0.2s cubic-bezier(0.2, 0.9, 0.4, 1.1);
    }

    .interet-btn:hover {
      border-color: #fd5068;
      color: #fd5068;
      transform: translateY(-2px);
    }

    .interet-btn.selected {
      background: linear-gradient(135deg, #fd5068, #fc7c45);
      border-color: transparent;
      color: #fff;
      transform: scale(1.02);
      box-shadow: 0 4px 10px rgba(253, 80, 104, 0.3);
    }

    .interets-min-hint {
      font-size: 0.75rem;
      color: #aaa;
      margin-top: 0.75rem;
    }

    /* Face scan container */
    .face-scan-container {
      position: relative;
      width: 100%;
      max-width: 340px;
      margin: 0 auto 1rem auto;
      border-radius: 20px;
      overflow: hidden;
      background: #0d0d0d;
      aspect-ratio: 4/3;
      box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.2);
    }

    .face-scan-container video {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }

    .face-scan-container canvas.overlay {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      pointer-events: none;
    }

    .face-cam-placeholder {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      height: 100%;
      color: #888;
      gap: 0.75rem;
      font-size: 0.85rem;
    }

    .face-cam-placeholder i {
      font-size: 2.5rem;
      color: #555;
    }

    .cam-spinner {
      width: 32px;
      height: 32px;
      border: 3px solid rgba(255, 255, 255, 0.1);
      border-top-color: #fd5068;
      border-radius: 50%;
      animation: spin 0.8s linear infinite;
    }

    @keyframes spin {
      to { transform: rotate(360deg); }
    }

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

    .camera-tips {
      background: linear-gradient(135deg, #fffbf0, #fff8e7);
      border: 1.5px solid #fbd38d;
      border-radius: 14px;
      padding: 0.85rem 1rem;
      margin-bottom: 1rem;
    }

    .camera-tips h4 {
      font-size: 0.8rem;
      font-weight: 800;
      color: #744210;
      margin-bottom: 0.4rem;
    }

    .camera-tips ul {
      margin: 0;
      padding-left: 1.2rem;
    }

    .camera-tips li {
      font-size: 0.75rem;
      color: #7d6127;
      line-height: 1.5;
    }

    /* Buttons */
    .form-actions {
      display: flex;
      gap: 0.75rem;
      margin-top: 1.5rem;
    }

    .btn-primary, .btn-outline {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
      padding: 0.85rem 1.5rem;
      border-radius: 60px;
      font-family: inherit;
      font-size: 0.9rem;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.25s ease;
      border: none;
      text-decoration: none;
    }

    .btn-primary {
      flex: 2;
      background: linear-gradient(135deg, #fd5068, #fc7c45);
      color: #fff;
      box-shadow: 0 4px 12px rgba(253, 80, 104, 0.3);
    }

    .btn-primary:hover:not(:disabled) {
      transform: translateY(-2px);
      box-shadow: 0 8px 20px rgba(253, 80, 104, 0.4);
    }

    .btn-primary:active:not(:disabled) {
      transform: translateY(0);
    }

    .btn-primary:disabled {
      opacity: 0.6;
      cursor: not-allowed;
      transform: none;
    }

    .btn-outline {
      flex: 1;
      background: transparent;
      color: #666;
      border: 2px solid #e0e0e0;
    }

    .btn-outline:hover {
      border-color: #fd5068;
      color: #fd5068;
      background: #fff5f6;
      transform: translateY(-2px);
    }

    /* Loading state */
    .loading-interets {
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 2rem;
      gap: 0.75rem;
      color: #aaa;
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

    /* Pulse animation */
    @keyframes pulse {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.05); }
    }

    .pulse {
      animation: pulse 0.3s ease;
    }

    /* Responsive */
    @media (max-width: 560px) {
      .auth-card {
        padding: 1.5rem;
      }
      .form-row {
        grid-template-columns: 1fr;
      }
      .auth-card h2 {
        font-size: 1.4rem;
      }
      .progress-step .step-label {
        font-size: 0.6rem;
      }
    }
  </style>
</head>
<body>

<div class="bg-particles" id="particles"></div>

<div class="auth-wrapper">
  <div class="auth-card">
    <div class="auth-brand">
      <span>👥</span> MatchFace Campus
    </div>
    <h2>Create your account</h2>
    <p class="auth-subtitle">Join thousands of people connecting every day</p>

    <!-- Progress Bar -->
    <div class="progress-bar">
      <div class="progress-step active" id="progressStep1">
        <div class="step-dot">1</div>
        <span class="step-label">INFO</span>
      </div>
      <div class="progress-connector" id="connector1"></div>
      <div class="progress-step" id="progressStep2">
        <div class="step-dot">2</div>
        <span class="step-label">INTERESTS</span>
      </div>
      <div class="progress-connector" id="connector2"></div>
      <div class="progress-step" id="progressStep3">
        <div class="step-dot">3</div>
        <span class="step-label">FACE</span>
      </div>
    </div>

    <!-- STEP 1 - Personal Information -->
    <div class="form-step active" id="step1">
      <h3><i class="fas fa-user" style="color:#fd5068;margin-right:0.4rem"></i> Your information</h3>
      <p class="step-desc">Fill in your personal details to create your profile.</p>

      <div class="form-group" id="grp-nom">
        <label><i class="fas fa-user"></i> Full name</label>
        <input type="text" id="nom" placeholder="John Doe" autocomplete="name">
        <span class="field-error" id="err-nom">Name is required (min. 2 characters).</span>
      </div>

      <div class="form-group" id="grp-email">
        <label><i class="fas fa-envelope"></i> Email</label>
        <input type="email" id="email" placeholder="john@example.com" autocomplete="email">
        <span class="field-error" id="err-email">Please enter a valid email address.</span>
      </div>

      <div class="form-row">
        <div class="form-group" id="grp-password">
          <label><i class="fas fa-lock"></i> Password</label>
          <input type="password" id="password" placeholder="Min. 8 characters" autocomplete="new-password">
          <span class="field-error" id="err-password">At least 8 characters required.</span>
        </div>
        <div class="form-group" id="grp-confirmPassword">
          <label><i class="fas fa-lock"></i> Confirm password</label>
          <input type="password" id="confirmPassword" placeholder="Repeat password" autocomplete="new-password">
          <span class="field-error" id="err-confirmPassword">Passwords do not match.</span>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group" id="grp-telephone">
          <label><i class="fas fa-phone"></i> Phone</label>
          <input type="tel" id="telephone" placeholder="+1 234 567 8900" autocomplete="tel">
          <span class="field-error" id="err-telephone">Invalid phone number.</span>
        </div>
        <div class="form-group" id="grp-sexe">
          <label><i class="fas fa-venus-mars"></i> Gender</label>
          <select id="sexe">
            <option value="">-- Select --</option>
            <option value="male">Male</option>
            <option value="female">Female</option>
            <option value="other">Other</option>
          </select>
          <span class="field-error" id="err-sexe">Please select your gender.</span>
        </div>
      </div>

      <div class="form-row">
        <div class="form-group" id="grp-date_naissance">
          <label><i class="fas fa-birthday-cake"></i> Date of birth</label>
          <input type="date" id="date_naissance" autocomplete="bday">
          <span class="field-error" id="err-date_naissance">You must be at least 18 years old.</span>
        </div>
        <div class="form-group" id="grp-ville">
          <label><i class="fas fa-map-marker-alt"></i> City</label>
          <input type="text" id="ville" placeholder="New York" autocomplete="address-level2">
          <span class="field-error" id="err-ville">City is required.</span>
        </div>
      </div>

      <div class="form-actions">
        <button class="btn-primary" onclick="nextStep(1)">
          Next <i class="fas fa-arrow-right"></i>
        </button>
      </div>
    </div>

    <!-- STEP 2 - Interests -->
    <div class="form-step" id="step2">
      <h3><i class="fas fa-heart" style="color:#fd5068;margin-right:0.4rem"></i> Interests</h3>
      <p class="step-desc">Choose at least 3 interests to personalize your matches.</p>

      <div class="interets-counter" id="interetsCounter">Selected: 0 / 10</div>

      <div class="interets-grid" id="interetsGrid">
        <div class="loading-interets">
          <div class="cam-spinner"></div>
          <span>Loading interests...</span>
        </div>
      </div>

      <p class="interets-min-hint"><i class="fas fa-info-circle"></i> Minimum 3, maximum 10 interests.</p>

      <div class="form-actions">
        <button class="btn-outline" onclick="prevStep(2)">
          <i class="fas fa-arrow-left"></i> Back
        </button>
        <button class="btn-primary" onclick="nextStep(2)">
          Next <i class="fas fa-arrow-right"></i>
        </button>
      </div>
    </div>

    <!-- STEP 3 - Face Scan -->
    <div class="form-step" id="step3">
      <h3><i class="fas fa-camera" style="color:#fd5068;margin-right:0.4rem"></i> Facial recognition</h3>
      <p class="step-desc">Take a photo of your face to secure and personalize your account.</p>

      <div class="face-scan-container" id="faceScanContainer">
        <div class="face-cam-placeholder" id="facePlaceholder">
          <div class="cam-spinner"></div>
          <span>Starting camera...</span>
        </div>
        <video id="video" autoplay muted playsinline style="display:none"></video>
        <canvas id="overlay" class="overlay"></canvas>
      </div>

      <div class="scan-status" id="scanStatus">Waiting for camera...</div>

      <div class="camera-tips">
        <h4><i class="fas fa-lightbulb"></i> Tips for a good scan</h4>
        <ul>
          <li>Make sure you're in a well-lit area</li>
          <li>Look directly at the camera</li>
          <li>Remove glasses and hat if possible</li>
          <li>Stay still for a few seconds</li>
        </ul>
      </div>

      <div class="form-actions">
        <button class="btn-outline" onclick="prevStep(3)" id="backToStep2">
          <i class="fas fa-arrow-left"></i> Back
        </button>
        <button class="btn-primary" id="captureFace" onclick="captureAndRegister()" disabled>
          <i class="fas fa-camera"></i> Capture & Create Account
        </button>
      </div>
    </div>

    <p class="auth-link">Already have an account? <a href="login.php">Sign in</a></p>
  </div>
</div>

<script src="../assets/js/face-recognition.js"></script>
<script>
/* ============================================================
   PARTICLES BACKGROUND
   ============================================================ */
function createParticles() {
  const container = document.getElementById('particles');
  const particleCount = 25;
  for (let i = 0; i < particleCount; i++) {
    const particle = document.createElement('div');
    particle.classList.add('particle');
    const size = Math.random() * 60 + 20;
    particle.style.width = size + 'px';
    particle.style.height = size + 'px';
    particle.style.left = Math.random() * 100 + '%';
    particle.style.animationDuration = Math.random() * 15 + 10 + 's';
    particle.style.animationDelay = Math.random() * 8 + 's';
    particle.style.opacity = Math.random() * 0.3 + 0.1;
    container.appendChild(particle);
  }
}
createParticles();

/* ============================================================
   GLOBAL STATE
   ============================================================ */
const faceRecognition = new FaceRecognition();
let currentStep = 1;
let selectedInterets = [];
let _cameraStarted = false;

/* ============================================================
   STEP NAVIGATION
   ============================================================ */
function nextStep(step) {
  if (step === 1 && !validateStep1()) return;
  if (step === 2 && !validateStep2()) return;

  document.getElementById('step' + step).classList.remove('active');
  const ps = document.getElementById('progressStep' + step);
  ps.classList.remove('active');
  ps.classList.add('done');
  if (step < 3) document.getElementById('connector' + step).classList.add('done');

  const next = step + 1;
  document.getElementById('step' + next).classList.add('active');
  document.getElementById('progressStep' + next).classList.add('active');
  currentStep = next;

  if (next === 3 && !_cameraStarted) {
    initFaceScan();
  }
}

function prevStep(step) {
  document.getElementById('step' + step).classList.remove('active');
  document.getElementById('progressStep' + step).classList.remove('active');

  const prev = step - 1;
  document.getElementById('step' + prev).classList.add('active');
  const ps = document.getElementById('progressStep' + prev);
  ps.classList.remove('done');
  ps.classList.add('active');
  if (prev < 3) document.getElementById('connector' + prev).classList.remove('done');

  currentStep = prev;

  if (step === 3 && _cameraStarted) {
    faceRecognition.stopDetectionLoop();
    faceRecognition.stopCamera();
    _cameraStarted = false;
  }
}

/* ============================================================
   VALIDATION - STEP 1
   ============================================================ */
function validateStep1() {
  let valid = true;

  const fields = [
    { id: 'nom', errId: 'err-nom', grpId: 'grp-nom', check: v => v.trim().length >= 2, msg: 'Name is required (min. 2 characters).' },
    { id: 'email', errId: 'err-email', grpId: 'grp-email', check: v => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v), msg: 'Please enter a valid email address.' },
    { id: 'password', errId: 'err-password', grpId: 'grp-password', check: v => v.length >= 8, msg: 'Password must be at least 8 characters.' },
    { id: 'sexe', errId: 'err-sexe', grpId: 'grp-sexe', check: v => v !== '', msg: 'Please select your gender.' },
    { id: 'date_naissance', errId: 'err-date_naissance', grpId: 'grp-date_naissance', check: v => checkAge(v), msg: 'You must be at least 18 years old.' },
    { id: 'ville', errId: 'err-ville', grpId: 'grp-ville', check: v => v.trim().length >= 2, msg: 'City is required.' },
  ];

  fields.forEach(f => {
    const el = document.getElementById(f.id);
    const err = document.getElementById(f.errId);
    const grp = document.getElementById(f.grpId);
    const ok = f.check(el.value);
    err.textContent = f.msg;
    err.classList.toggle('visible', !ok);
    grp.classList.toggle('has-error', !ok);
    if (!ok) valid = false;
  });

  const pw = document.getElementById('password').value;
  const cpw = document.getElementById('confirmPassword').value;
  const cpwErr = document.getElementById('err-confirmPassword');
  const cpwGrp = document.getElementById('grp-confirmPassword');
  const cpwOk = pw === cpw && pw.length >= 8;
  cpwErr.textContent = pw !== cpw ? 'Passwords do not match.' : 'Please confirm your password.';
  cpwErr.classList.toggle('visible', !cpwOk);
  cpwGrp.classList.toggle('has-error', !cpwOk);
  if (!cpwOk) valid = false;

  return valid;
}

function checkAge(dateStr) {
  if (!dateStr) return false;
  const birth = new Date(dateStr);
  const today = new Date();
  let age = today.getFullYear() - birth.getFullYear();
  const m = today.getMonth() - birth.getMonth();
  if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) age--;
  return age >= 18;
}

/* ============================================================
   VALIDATION - STEP 2
   ============================================================ */
function validateStep2() {
  if (selectedInterets.length < 3) {
    const counter = document.getElementById('interetsCounter');
    counter.classList.add('pulse');
    setTimeout(() => counter.classList.remove('pulse'), 300);
    alert('Please select at least 3 interests.');
    return false;
  }
  return true;
}

/* ============================================================
   INTERESTS - LOAD & DISPLAY
   ============================================================ */
async function loadInterets() {
  try {
    const response = await fetch('/tinder/api/get-interets.php');
    if (!response.ok) throw new Error('HTTP ' + response.status);
    const result = await response.json();
    const data = result.success ? (result.data || []) : result;
    displayInterets(Array.isArray(data) && data.length ? data : getStaticInterets());
  } catch (err) {
    console.warn('API failed, using fallback:', err);
    displayInterets(getStaticInterets());
  }
}

function getStaticInterets() {
  return [
    { id: 1, nom: 'Sports', categorie: 'Activities', emoji: '⚽' },
    { id: 2, nom: 'Music', categorie: 'Culture', emoji: '🎵' },
    { id: 3, nom: 'Movies', categorie: 'Culture', emoji: '🎬' },
    { id: 4, nom: 'Reading', categorie: 'Culture', emoji: '📚' },
    { id: 5, nom: 'Travel', categorie: 'Leisure', emoji: '✈️' },
    { id: 6, nom: 'Cooking', categorie: 'Leisure', emoji: '🍳' },
    { id: 7, nom: 'Photography', categorie: 'Arts', emoji: '📷' },
    { id: 8, nom: 'Dancing', categorie: 'Arts', emoji: '💃' },
    { id: 9, nom: 'Hiking', categorie: 'Sports', emoji: '🥾' },
    { id: 10, nom: 'Yoga', categorie: 'Wellness', emoji: '🧘' },
    { id: 11, nom: 'Gaming', categorie: 'Leisure', emoji: '🎮' },
    { id: 12, nom: 'Painting', categorie: 'Arts', emoji: '🎨' },
    { id: 13, nom: 'Swimming', categorie: 'Sports', emoji: '🏊' },
    { id: 14, nom: 'Meditation', categorie: 'Wellness', emoji: '🌿' },
    { id: 15, nom: 'Theater', categorie: 'Arts', emoji: '🎭' },
    { id: 16, nom: 'Animals', categorie: 'Leisure', emoji: '🐾' },
    { id: 17, nom: 'Astronomy', categorie: 'Science', emoji: '🔭' },
    { id: 18, nom: 'Fashion', categorie: 'Lifestyle', emoji: '👗' },
  ];
}

function displayInterets(interets) {
  const grid = document.getElementById('interetsGrid');
  if (!grid) return;

  if (!interets || interets.length === 0) {
    grid.innerHTML = '<div class="error-message"><p>No interests available.</p></div>';
    return;
  }

  const grouped = {};
  interets.forEach(item => {
    const cat = item.categorie || 'Other';
    if (!grouped[cat]) grouped[cat] = [];
    grouped[cat].push(item);
  });

  grid.innerHTML = '';
  Object.keys(grouped).sort().forEach(cat => {
    const catDiv = document.createElement('div');
    catDiv.className = 'interet-category';
    catDiv.innerHTML = '<h4>' + escapeHtml(cat.toUpperCase()) + '</h4>';
    const btnsDiv = document.createElement('div');
    btnsDiv.className = 'interets-buttons';
    grouped[cat].forEach(item => {
      const btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'interet-btn';
      btn.dataset.id = item.id;
      btn.innerHTML = (item.emoji ? item.emoji + ' ' : '') + escapeHtml(item.nom);
      btn.addEventListener('click', () => toggleInteret(btn));
      btnsDiv.appendChild(btn);
    });
    catDiv.appendChild(btnsDiv);
    grid.appendChild(catDiv);
  });
}

function toggleInteret(btn) {
  const id = btn.dataset.id;
  const idx = selectedInterets.indexOf(id);

  if (idx === -1) {
    if (selectedInterets.length >= 10) {
      btn.classList.add('pulse');
      setTimeout(() => btn.classList.remove('pulse'), 300);
      return;
    }
    selectedInterets.push(id);
    btn.classList.add('selected');
  } else {
    selectedInterets.splice(idx, 1);
    btn.classList.remove('selected');
  }
  updateInteretsCounter();
}

function updateInteretsCounter() {
  const el = document.getElementById('interetsCounter');
  if (!el) return;
  const n = selectedInterets.length;
  el.textContent = 'Selected: ' + n + ' / 10';
  el.style.color = n >= 3 ? '#27ae60' : '#fd5068';
  el.style.background = n >= 3 ? '#f0fff4' : '#fff5f6';
  el.style.borderColor = n >= 3 ? '#9ae6b4' : '#ffd6dc';
}

/* ============================================================
   FACE SCAN - STEP 3
   ============================================================ */
async function initFaceScan() {
  _cameraStarted = true;
  setScanStatus('Loading AI models...', 'loading');

  try {
    await faceRecognition.loadModels();
    setScanStatus('Starting camera...', 'loading');

    const video = document.getElementById('video');
    const canvas = document.getElementById('overlay');
    const ok = await faceRecognition.startVideo(video);

    if (!ok) {
      setScanStatus('Cannot access camera. Check permissions.', 'error');
      _cameraStarted = false;
      return;
    }

    document.getElementById('facePlaceholder').style.display = 'none';
    video.style.display = 'block';
    setScanStatus('👤 Position your face in the frame...', 'info');

    faceRecognition.startDetectionLoop(async (result) => {
      if (result && result.descriptor) {
        document.getElementById('captureFace').disabled = false;
        setScanStatus('✅ Face detected — click "Capture & Create Account"', 'success');
        faceRecognition.drawFaceDetection(canvas);
      } else {
        document.getElementById('captureFace').disabled = true;
        setScanStatus('👤 No face detected — position yourself in front of the camera', 'info');
        const ctx = canvas.getContext('2d');
        ctx.clearRect(0, 0, canvas.width, canvas.height);
      }
    }, 600);
  } catch (err) {
    console.error('initFaceScan error:', err);
    setScanStatus('Error: ' + err.message, 'error');
    _cameraStarted = false;
  }
}

function setScanStatus(msg, type) {
  const el = document.getElementById('scanStatus');
  if (!el) return;
  el.textContent = msg;
  el.style.background = type === 'success' ? '#e8f5e9' : type === 'error' ? '#ffebee' : type === 'loading' ? '#f1f3f7' : '#f8f9fc';
  el.style.color = type === 'success' ? '#27ae60' : type === 'error' ? '#e53e3e' : '#555';
}

/* ============================================================
   CAPTURE & REGISTER
   ============================================================ */
async function captureAndRegister() {
  const btn = document.getElementById('captureFace');
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating account...';
  setScanStatus('Analyzing face...', 'loading');

  try {
    const face = await faceRecognition.detectFace();
    if (!face || !face.descriptor) {
      setScanStatus('No face detected. Please try again.', 'error');
      btn.disabled = false;
      btn.innerHTML = '<i class="fas fa-camera"></i> Capture & Create Account';
      return;
    }

    const video = document.getElementById('video');
    const canvas = document.createElement('canvas');
    canvas.width = video.videoWidth || 640;
    canvas.height = video.videoHeight || 480;
    canvas.getContext('2d').drawImage(video, 0, 0, canvas.width, canvas.height);
    const photoBase64 = canvas.toDataURL('image/jpeg', 0.85);

    const userData = {
      nom: document.getElementById('nom').value.trim(),
      email: document.getElementById('email').value.trim(),
      password: document.getElementById('password').value,
      telephone: document.getElementById('telephone').value.trim(),
      sexe: document.getElementById('sexe').value,
      date_naissance: document.getElementById('date_naissance').value,
      ville: document.getElementById('ville').value.trim(),
      interets: selectedInterets,
      face_vector: Array.from(face.descriptor),
      photo: photoBase64,
    };

    setScanStatus('Sending data...', 'loading');

    const response = await fetch('api/register.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(userData),
    });

    const result = await response.json();

    if (result.success) {
      faceRecognition.stopDetectionLoop();
      faceRecognition.stopCamera();
      setScanStatus('✅ Account created! Redirecting...', 'success');
      setTimeout(() => {
        window.location.href = 'login.php?registered=1';
      }, 900);
    } else {
      setScanStatus('Error: ' + (result.error || 'Unknown error.'), 'error');
      btn.disabled = false;
      btn.innerHTML = '<i class="fas fa-camera"></i> Capture & Create Account';
    }
  } catch (err) {
    console.error('captureAndRegister error:', err);
    setScanStatus('Network error: ' + err.message, 'error');
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-camera"></i> Capture & Create Account';
  }
}

/* ============================================================
   UTILITY
   ============================================================ */
function escapeHtml(str) {
  const d = document.createElement('div');
  d.textContent = str;
  return d.innerHTML;
}

/* ============================================================
   INIT
   ============================================================ */
document.addEventListener('DOMContentLoaded', () => {
  loadInterets();
});
</script>
</body>
</html>
