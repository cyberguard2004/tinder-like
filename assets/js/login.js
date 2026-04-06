/**
 * login.js — MatchFace login page logic
 *
 * Handles:
 *  - Face-recognition login (tab: face)
 *  - Email + password login  (tab: password)
 *  - Tab switching with camera lifecycle management
 *  - ?registered=1 success toast
 *
 * Expects globals injected by PHP:
 *   BASE_URL  {string}   e.g. '/tinder'
 *   CURRENT_USER (not needed on the login page, but may be defined)
 *
 * Required DOM ids / classes (in pages/login.php):
 *   #video            <video> element for webcam feed
 *   #scanStatus       Status text below the video
 *   #faceLoginBtn     "Se connecter avec mon visage" button
 *   .login-tab        Tab switcher buttons (data-tab="face" | "password")
 *   #panelFace        Face-login panel
 *   #panelPassword    Password-login panel
 *   #loginEmail       Email input
 *   #loginPassword    Password input
 *   #loginError       Error message div (password tab)
 *   #passwordSubmitBtn  Submit button (password tab)  — falls back to
 *                        querySelector('#panelPassword .btn-primary')
 */

"use strict";

/* ─────────────────────────── module-level state ─────────────────────────── */

const fr = new FaceRecognition();

/** True once a face is visible in the current video frame. */
let faceDetected = false;

/**
 * True after loadModels() + startVideo() have succeeded at least once.
 * Prevents re-downloading the ~6 MB model weights on every tab switch.
 */
let faceLoginInitialized = false;

/* ──────────────────────────── face-login tab ─────────────────────────────── */

/**
 * Shared detection-loop callback.
 * Updates #scanStatus and the enabled state of #faceLoginBtn.
 *
 * @param {object|null} detection  Result from FaceRecognition.detectFace(),
 *                                  or null when no face is visible.
 */
function handleFaceDetection(detection) {
  faceDetected = !!detection;

  const statusEl = document.getElementById("scanStatus");
  const loginBtn = document.getElementById("faceLoginBtn");

  if (detection) {
    const pct = Math.round(detection.score * 100);
    if (statusEl)
      statusEl.textContent = `✅ Visage détecté ! Confiance : ${pct} %`;
    if (loginBtn) loginBtn.disabled = false;
  } else {
    if (statusEl)
      statusEl.textContent = "👤 Placez votre visage devant la caméra";
    if (loginBtn) loginBtn.disabled = true;
  }
}

/**
 * Initialise the face-login panel.
 *
 * - If called for the first time: loads models, starts the webcam, then
 *   begins the detection loop.
 * - If called after a tab switch (camera is still running but the detection
 *   loop was paused): simply restarts the loop.
 * - Idempotent: safe to call repeatedly.
 */
async function initFaceLogin() {
  const statusEl = document.getElementById("scanStatus");
  const loginBtn = document.getElementById("faceLoginBtn");

  /* ── Camera still alive from a previous init ── */
  if (faceLoginInitialized && fr.stream) {
    fr.startDetectionLoop(400, handleFaceDetection);
    return;
  }

  /* ── First run ── */
  if (statusEl) statusEl.textContent = "Chargement des modèles…";
  if (loginBtn) loginBtn.disabled = true;

  try {
    await fr.loadModels();

    const videoEl = document.getElementById("video");
    if (!videoEl) {
      throw new Error('Élément <video id="video"> introuvable dans le DOM.');
    }

    await fr.startVideo(videoEl);

    fr.startDetectionLoop(400, handleFaceDetection);

    faceLoginInitialized = true;
  } catch (err) {
    console.error("[login] initFaceLogin :", err);
    if (statusEl) {
      statusEl.textContent = `❌ ${err.message}`;
      statusEl.classList.add("status-error");
    }
    if (loginBtn) loginBtn.disabled = true;
  }
}

/**
 * Attempt to authenticate the current user via face recognition.
 * POSTs { face_vector } to /api/login-face.php.
 * On success, stops the camera and redirects.
 */
async function loginWithFace() {
  const statusEl = document.getElementById("scanStatus");
  const loginBtn = document.getElementById("faceLoginBtn");

  /* ── Optimistic UI ── */
  if (loginBtn) {
    loginBtn.disabled = true;
    loginBtn.textContent = "Reconnaissance en cours…";
  }

  try {
    const face = await fr.detectFace();

    if (!face) {
      if (statusEl)
        statusEl.textContent = "❌ Aucun visage détecté. Réessayez.";
      if (loginBtn) {
        loginBtn.disabled = false;
        loginBtn.textContent = "Se connecter avec mon visage";
      }
      return;
    }

    const response = await fetch(`${BASE_URL}/api/login-face.php`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ face_vector: face.descriptor }),
    });

    const data = await response.json();

    if (data.success) {
      fr.stopCamera();
      window.location.href = data.redirect || `${BASE_URL}/pages/dashboard.php`;
    } else {
      const msg = data.error || "Visage non reconnu. Veuillez vous inscrire.";
      if (statusEl) statusEl.textContent = `❌ ${msg}`;
      if (loginBtn) {
        loginBtn.disabled = false;
        loginBtn.textContent = "Se connecter avec mon visage";
      }
    }
  } catch (err) {
    console.error("[login] loginWithFace :", err);
    if (statusEl) statusEl.textContent = `❌ Erreur réseau : ${err.message}`;
    if (loginBtn) {
      loginBtn.disabled = false;
      loginBtn.textContent = "Se connecter avec mon visage";
    }
  }
}

/* ─────────────────────────── password-login tab ─────────────────────────── */

/**
 * Show or hide a message in the #loginError element.
 *
 * @param {string}  message  Text to display.  Pass '' to hide.
 * @param {boolean} visible  Whether the element should be visible.
 */
function setLoginError(message, visible = true) {
  const el = document.getElementById("loginError");
  if (!el) return;
  el.textContent = message;
  el.style.display = visible ? "block" : "none";
}

/**
 * Attempt to authenticate via email + password.
 * Reads #loginEmail and #loginPassword.
 * POSTs { email, password } to /api/login-password.php.
 * On success redirects; on error shows #loginError.
 */
async function loginWithPassword() {
  const emailEl = document.getElementById("loginEmail");
  const passwordEl = document.getElementById("loginPassword");
  const submitBtn =
    document.getElementById("passwordSubmitBtn") ||
    document.querySelector("#panelPassword .btn-primary");

  setLoginError("", false);

  const email = emailEl ? emailEl.value.trim() : "";
  const password = passwordEl ? passwordEl.value : "";

  /* ── Basic validation ── */
  if (!email || !password) {
    setLoginError("Veuillez remplir tous les champs.");
    return;
  }

  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!emailRegex.test(email)) {
    setLoginError("Format d'adresse email invalide.");
    return;
  }

  /* ── Loading state ── */
  if (submitBtn) {
    submitBtn.disabled = true;
    submitBtn.textContent = "Connexion…";
  }

  try {
    const response = await fetch(`${BASE_URL}/api/login-password.php`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ email, password }),
    });

    const data = await response.json();

    if (data.success) {
      window.location.href = data.redirect || `${BASE_URL}/pages/dashboard.php`;
    } else {
      setLoginError(data.error || "Email ou mot de passe incorrect.");
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.textContent = "Se connecter";
      }
    }
  } catch (err) {
    console.error("[login] loginWithPassword :", err);
    setLoginError("Erreur réseau. Veuillez réessayer.");
    if (submitBtn) {
      submitBtn.disabled = false;
      submitBtn.textContent = "Se connecter";
    }
  }
}

/* ────────────────────────────── tab switching ─────────────────────────────── */

/**
 * Switch between the 'face' and 'password' login panels.
 *
 * - Activates the matching .login-tab button.
 * - Shows / hides #panelFace and #panelPassword.
 * - Manages camera lifecycle:
 *     entering  face tab → (re)start detection loop via initFaceLogin()
 *     leaving   face tab → stop detection loop (camera stream kept alive)
 *
 * @param {'face'|'password'} tab
 */
function switchTab(tab) {
  const panelFace = document.getElementById("panelFace");
  const panelPassword = document.getElementById("panelPassword");

  /* ── Update tab button active states ── */
  document.querySelectorAll(".login-tab").forEach((btn) => {
    const btnTab =
      btn.dataset.tab ||
      (btn.textContent.toLowerCase().includes("visage") ? "face" : "password");
    btn.classList.toggle("active", btnTab === tab);
  });

  /* ── Show / hide panels ── */
  if (tab === "face") {
    if (panelFace) panelFace.style.display = "block";
    if (panelPassword) panelPassword.style.display = "none";

    /* Re-init or resume the detection loop */
    initFaceLogin();
  } else {
    if (panelFace) panelFace.style.display = "none";
    if (panelPassword) panelPassword.style.display = "block";

    /* Pause detection loop (camera stream stays open to avoid re-requesting
           permission if the user switches back quickly). */
    fr.stopDetectionLoop();

    /* Clear any lingering error highlight on the face panel */
    faceDetected = false;

    /* Focus the email field for keyboard users */
    const emailEl = document.getElementById("loginEmail");
    if (emailEl) emailEl.focus();
  }
}

/* ─────────────────────────── registered toast ─────────────────────────────── */

/**
 * Display a dismissible success banner at the top of the page.
 * Auto-removes itself after 5 seconds.
 *
 * @param {string} message  Text to display inside the toast.
 */
function showToast(message) {
  /* Avoid stacking duplicate toasts */
  const existing = document.querySelector(".login-toast");
  if (existing) existing.remove();

  const toast = document.createElement("div");
  toast.className = "login-toast";
  toast.setAttribute("role", "alert");
  toast.innerHTML = `
        <span class="login-toast__text">${message}</span>
        <button class="login-toast__close" aria-label="Fermer">&times;</button>
    `;

  Object.assign(toast.style, {
    position: "fixed",
    top: "20px",
    left: "50%",
    transform: "translateX(-50%)",
    background: "#22c55e",
    color: "#fff",
    padding: "14px 24px",
    borderRadius: "10px",
    fontWeight: "600",
    fontSize: "15px",
    zIndex: "9999",
    boxShadow: "0 4px 16px rgba(0,0,0,0.18)",
    display: "flex",
    alignItems: "center",
    gap: "12px",
    maxWidth: "90vw",
    animation: "toastSlideDown 0.35s ease",
  });

  /* Close button styling */
  const closeBtn = toast.querySelector(".login-toast__close");
  Object.assign(closeBtn.style, {
    background: "transparent",
    border: "none",
    color: "#fff",
    fontSize: "20px",
    cursor: "pointer",
    lineHeight: "1",
    padding: "0",
    marginLeft: "4px",
  });

  closeBtn.addEventListener("click", () => removeToast(toast));
  document.body.appendChild(toast);

  /* Auto-dismiss after 5 s */
  setTimeout(() => removeToast(toast), 5000);
}

/** Fade out then remove a toast element. */
function removeToast(toast) {
  if (!toast || !toast.parentNode) return;
  toast.style.transition = "opacity 0.3s ease";
  toast.style.opacity = "0";
  setTimeout(() => toast.remove(), 320);
}

/* ──────────────────────────── DOMContentLoaded ────────────────────────────── */

document.addEventListener("DOMContentLoaded", () => {
  /* ── Inject keyframe animation (once) ── */
  if (!document.getElementById("loginToastStyle")) {
    const style = document.createElement("style");
    style.id = "loginToastStyle";
    style.textContent = `
            @keyframes toastSlideDown {
                from { opacity: 0; transform: translateX(-50%) translateY(-16px); }
                to   { opacity: 1; transform: translateX(-50%) translateY(0);     }
            }
        `;
    document.head.appendChild(style);
  }

  /* ── ?registered=1 ── */
  const params = new URLSearchParams(window.location.search);
  if (params.get("registered") === "1") {
    showToast("Inscription réussie ! Connectez-vous.");
  }

  /* ── Tab switcher buttons ── */
  document.querySelectorAll(".login-tab").forEach((btn) => {
    btn.addEventListener("click", () => {
      const tab =
        btn.dataset.tab ||
        (btn.textContent.toLowerCase().includes("visage")
          ? "face"
          : "password");
      switchTab(tab);
    });
  });

  /* ── Face login button ── */
  const faceLoginBtn = document.getElementById("faceLoginBtn");
  if (faceLoginBtn) {
    faceLoginBtn.addEventListener("click", loginWithFace);
  }

  /* ── Password form: submit via button click or Enter key ── */
  const passwordSubmitBtn =
    document.getElementById("passwordSubmitBtn") ||
    document.querySelector("#panelPassword .btn-primary");
  if (passwordSubmitBtn) {
    passwordSubmitBtn.addEventListener("click", (e) => {
      e.preventDefault();
      loginWithPassword();
    });
  }

  const passwordForm = document.querySelector("#panelPassword form");
  if (passwordForm) {
    passwordForm.addEventListener("submit", (e) => {
      e.preventDefault();
      loginWithPassword();
    });
  }

  /* Allow pressing Enter inside the password field */
  const passwordInput = document.getElementById("loginPassword");
  if (passwordInput) {
    passwordInput.addEventListener("keydown", (e) => {
      if (e.key === "Enter") {
        e.preventDefault();
        loginWithPassword();
      }
    });
  }

  /* Clear the error banner whenever the user starts retyping */
  ["loginEmail", "loginPassword"].forEach((id) => {
    const el = document.getElementById(id);
    if (el) el.addEventListener("input", () => setLoginError("", false));
  });

  /* ── Auto-start face login ── */
  initFaceLogin();
});
