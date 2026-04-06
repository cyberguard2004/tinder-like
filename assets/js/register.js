/**
 * register.js — MatchFace 3-step registration page logic
 *
 * Step 1 — Personal information  (nom, email, telephone, sexe,
 *                                  date_naissance, ville, password)
 * Step 2 — Centres d'intérêt     (minimum 3, maximum 10)
 * Step 3 — Face capture           (webcam scan → photo + face vector)
 *
 * Expects globals injected by PHP:
 *   BASE_URL  {string}   e.g. '/tinder'   (falls back to window.BASE_URL or '/tinder')
 *
 * Required DOM ids / classes (pages/register.php):
 *   #step1, #step2, #step3           — .form-step panels
 *   .progress-step                   — progress-bar indicators
 *   #interetsGrid                    — container rendered by displayInterets()
 *   #interetsCounter                 — "Sélectionnés: X/10" label
 *   #video, #overlay, #scanStatus    — camera elements in step 3
 *   #captureFace                     — capture-and-register button
 *   #nom, #email, #telephone         — text inputs (step 1)
 *   #sexe, #date_naissance, #ville   — select / date / text inputs (step 1)
 *   #password, #password_confirm     — password inputs (step 1, optional)
 *   #bio                             — textarea (step 1, optional)
 */

"use strict";

/* ─────────────────────────── module-level state ─────────────────────────── */

const fr = new FaceRecognition();

/** Which step (1-3) is currently visible. */
let currentStep = 1;

/** Array of interest IDs (integers) the user has selected. */
let selectedInterets = [];

/** True once loadModels() + startVideo() have completed for step 3. */
let cameraInitialized = false;

/** Base64-encoded JPEG photo captured from the webcam. */
let photoBase64 = null;

/** Convenience alias — honours the PHP-injected global or falls back. */
const BASE =
  (typeof BASE_URL !== "undefined" ? BASE_URL : null) ||
  window.BASE_URL ||
  "/tinder";

/* ──────────────────────────── interest loading ─────────────────────────── */

/**
 * Fetch all available centres d'intérêt from the API and render them.
 * Shows a retry button on network / API failure.
 */
async function loadInterets() {
  const grid = document.getElementById("interetsGrid");
  if (grid) {
    grid.innerHTML = `
      <div class="loading-spinner" style="text-align:center;padding:24px">
        <div class="spinner"></div>
        <p>Chargement des centres d'intérêt…</p>
      </div>`;
  }

  try {
    const response = await fetch(`${BASE}/api/get-interets.php`);
    if (!response.ok) {
      throw new Error(`HTTP ${response.status}`);
    }
    const data = await response.json();

    if (data.success && Array.isArray(data.data)) {
      displayInterets(data.data);
    } else {
      throw new Error(data.error || "Réponse invalide du serveur.");
    }
  } catch (err) {
    console.error("[register] loadInterets :", err);
    if (grid) {
      grid.innerHTML = `
        <div class="error-state" style="text-align:center;padding:24px;color:#ef4444">
          <p>Impossible de charger les centres d'intérêt.</p>
          <button class="btn-outline" onclick="loadInterets()" style="margin-top:12px">
            Réessayer
          </button>
        </div>`;
    }
  }
}

/**
 * Group an array of interest objects by their `categorie` field and inject
 * them into #interetsGrid as category sections with toggle buttons.
 *
 * Re-applies any currently-selected IDs (so the UI is consistent after a
 * re-render triggered by a retry).
 *
 * @param {Array<{id:number, nom:string, categorie:string, emoji:string}>} interets
 */
function displayInterets(interets) {
  const grid = document.getElementById("interetsGrid");
  if (!grid) return;

  /* Group by category, preserving insertion order */
  const grouped = {};
  interets.forEach((item) => {
    const cat = item.categorie || "Autre";
    if (!grouped[cat]) grouped[cat] = [];
    grouped[cat].push(item);
  });

  let html = "";
  for (const [category, items] of Object.entries(grouped)) {
    html += `<div class="interet-category">
      <h4 class="category-title">${category}</h4>
      <div class="category-items">`;

    items.forEach((item) => {
      const isSelected = selectedInterets.includes(Number(item.id));
      html += `<button
          type="button"
          class="interet-btn${isSelected ? " selected" : ""}"
          data-id="${item.id}"
          onclick="toggleInteret(this, ${item.id})">
          <span class="interet-emoji">${item.emoji || ""}</span>
          <span class="interet-name">${item.nom}</span>
        </button>`;
    });

    html += `</div></div>`;
  }

  grid.innerHTML = html;
  updateInteretsCounter();
}

/**
 * Toggle the selection state of a single interest button.
 * Enforces the maximum of 10 selections.
 *
 * @param {HTMLButtonElement} btn  The clicked button element.
 * @param {number|string}     id   The interest's database ID.
 */
function toggleInteret(btn, id) {
  id = Number(id);
  const idx = selectedInterets.indexOf(id);

  if (idx === -1) {
    /* ── Try to add ── */
    if (selectedInterets.length >= 10) {
      showToast("Maximum 10 centres d'intérêt sélectionnés !", "warning");
      return;
    }
    selectedInterets.push(id);
    btn.classList.add("selected");
  } else {
    /* ── Remove ── */
    selectedInterets.splice(idx, 1);
    btn.classList.remove("selected");
  }

  updateInteretsCounter();
}

/** Refresh the "Sélectionnés: X/10" counter label. */
function updateInteretsCounter() {
  const counter = document.getElementById("interetsCounter");
  if (counter) {
    counter.textContent = `Sélectionnés : ${selectedInterets.length} / 10`;
  }
}

/* ────────────────────────────── step navigation ─────────────────────────── */

/**
 * Advance from the given step to the next one.
 * Validates the current step before animating the transition.
 *
 * @param {1|2} from  The step we are leaving.
 */
function nextStep(from) {
  if (from === 1 && !validateStep1()) return;
  if (from === 2 && !validateStep2()) return;

  goToStep(from, from + 1);
}

/**
 * Go back from the given step to the previous one.
 * Tears down the camera when leaving step 3.
 *
 * @param {2|3} from  The step we are leaving.
 */
function prevStep(from) {
  if (from === 3) {
    /* Release the camera when the user goes back from the scan step */
    fr.stopDetectionLoop();
    fr.stopCamera();
    cameraInitialized = false;
    photoBase64 = null;
  }

  goToStep(from, from - 1);
}

/**
 * Perform the animated transition between two steps.
 * Updates the progress-bar and—if the target is step 3—inits the camera.
 *
 * @param {number} from  Origin step number.
 * @param {number} to    Destination step number.
 */
function goToStep(from, to) {
  const fromEl = document.getElementById(`step${from}`);
  const toEl = document.getElementById(`step${to}`);
  if (!fromEl || !toEl) return;

  /* Slide out */
  fromEl.style.transition = "opacity 0.25s ease, transform 0.25s ease";
  fromEl.style.opacity = "0";
  fromEl.style.transform = to > from ? "translateX(-30px)" : "translateX(30px)";

  setTimeout(() => {
    fromEl.classList.remove("active");
    fromEl.style.display = "none";
    fromEl.style.opacity = "";
    fromEl.style.transform = "";

    /* Slide in */
    toEl.style.display = "block";
    toEl.style.opacity = "0";
    toEl.style.transform = to > from ? "translateX(30px)" : "translateX(-30px)";

    /* Force reflow so the initial state is painted before the transition */
    void toEl.offsetWidth;

    toEl.style.transition = "opacity 0.25s ease, transform 0.25s ease";
    toEl.style.opacity = "1";
    toEl.style.transform = "translateX(0)";
    toEl.classList.add("active");

    setTimeout(() => {
      toEl.style.transition = "";
      toEl.style.opacity = "";
      toEl.style.transform = "";
    }, 260);

    currentStep = to;
    updateProgressBar(to);

    /* Kick off camera on step 3 with a short delay so the DOM is visible */
    if (to === 3) {
      setTimeout(initCamera, 350);
    }
  }, 260);
}

/**
 * Highlight the active and completed steps in the progress bar.
 *
 * @param {number} activeStep  1-based current step index.
 */
function updateProgressBar(activeStep) {
  document.querySelectorAll(".progress-step").forEach((el, idx) => {
    el.classList.remove("active", "completed");
    if (idx + 1 === activeStep) {
      el.classList.add("active");
    } else if (idx + 1 < activeStep) {
      el.classList.add("completed");
    }
  });
}

/* ──────────────────────────── step validation ────────────────────────────── */

/**
 * Validate step 1 (personal information).
 * Shows a toast with the first validation error found.
 *
 * @returns {boolean}  True if all fields pass.
 */
function validateStep1() {
  const val = (id) => {
    const el = document.getElementById(id);
    return el ? el.value.trim() : "";
  };

  const nom = val("nom");
  const email = val("email");
  const sexe = val("sexe");
  const dateNaissance = val("date_naissance");
  const ville = val("ville");
  const password = val("password");
  const passwordConf = val("password_confirm");

  if (!nom) {
    showToast("Le nom complet est requis.", "error");
    focusField("nom");
    return false;
  }

  if (!email) {
    showToast("L'adresse email est requise.", "error");
    focusField("email");
    return false;
  }

  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!emailRegex.test(email)) {
    showToast("Format d'adresse email invalide.", "error");
    focusField("email");
    return false;
  }

  if (!sexe) {
    showToast("Veuillez sélectionner votre sexe.", "error");
    focusField("sexe");
    return false;
  }

  if (!dateNaissance) {
    showToast("La date de naissance est requise.", "error");
    focusField("date_naissance");
    return false;
  }

  if (calculateAge(dateNaissance) < 18) {
    showToast("Vous devez avoir au moins 18 ans pour vous inscrire.", "error");
    focusField("date_naissance");
    return false;
  }

  if (!ville) {
    showToast("La ville est requise.", "error");
    focusField("ville");
    return false;
  }

  /* Password match (both fields must be present and equal when filled) */
  if (password && passwordConf && password !== passwordConf) {
    showToast("Les mots de passe ne correspondent pas.", "error");
    focusField("password_confirm");
    return false;
  }

  return true;
}

/**
 * Validate step 2 (interests selection).
 *
 * @returns {boolean}  True when at least 3 interests are selected.
 */
function validateStep2() {
  if (selectedInterets.length < 3) {
    showToast(
      `Sélectionnez au moins 3 centres d'intérêt (${selectedInterets.length} / 3 minimum).`,
      "warning",
    );
    return false;
  }
  return true;
}

/** Helper: focus a form field by ID, scrolling into view if needed. */
function focusField(id) {
  const el = document.getElementById(id);
  if (el) {
    el.focus();
    el.scrollIntoView({ behavior: "smooth", block: "center" });
  }
}

/* ──────────────────────────── camera (step 3) ────────────────────────────── */

/**
 * Initialise the webcam and start the face-detection loop for step 3.
 * Sets #captureFace enabled/disabled based on detection results.
 * Idempotent: does nothing when the camera is already running.
 */
async function initCamera() {
  if (cameraInitialized && fr.stream) return;

  const statusEl = document.getElementById("scanStatus");
  const captureBtn = document.getElementById("captureFace");

  if (statusEl) statusEl.textContent = "Chargement des modèles…";
  if (captureBtn) captureBtn.disabled = true;

  try {
    await fr.loadModels();

    const videoEl = document.getElementById("video");
    if (!videoEl) throw new Error('<video id="video"> introuvable.');

    await fr.startVideo(videoEl);

    fr.startDetectionLoop(400, (detection) => {
      if (detection) {
        const pct = Math.round(detection.score * 100);
        if (statusEl)
          statusEl.textContent = `✅ Visage détecté ! Confiance : ${pct} %`;
        if (captureBtn) captureBtn.disabled = false;
      } else {
        if (statusEl)
          statusEl.textContent = "👤 Placez votre visage devant la caméra";
        if (captureBtn) captureBtn.disabled = true;
      }
    });

    cameraInitialized = true;
  } catch (err) {
    console.error("[register] initCamera :", err);
    const statusEl = document.getElementById("scanStatus");
    if (statusEl) {
      statusEl.textContent = `❌ ${err.message}`;
    }
    showToast(`Erreur caméra : ${err.message}`, "error");
  }
}

/**
 * Capture the current video frame, extract the face vector, collect all
 * form data, and POST everything to /api/register.php.
 *
 * On success:  stops the camera and redirects to login.php?registered=1
 * On failure:  shows an error toast and re-enables the button.
 */
async function captureAndRegister() {
  const statusEl = document.getElementById("scanStatus");
  const captureBtn = document.getElementById("captureFace");

  /* ── Ensure a face is currently visible ── */
  const face = await fr.detectFace();
  if (!face) {
    showToast(
      "Aucun visage détecté. Regardez la caméra et réessayez.",
      "error",
    );
    return;
  }

  /* ── Grab a snapshot from the video element ── */
  const videoEl = document.getElementById("video");
  if (!videoEl) {
    showToast("Élément vidéo introuvable.", "error");
    return;
  }

  const canvas = document.createElement("canvas");
  canvas.width = videoEl.videoWidth || 400;
  canvas.height = videoEl.videoHeight || 300;
  const ctx = canvas.getContext("2d");
  ctx.drawImage(videoEl, 0, 0, canvas.width, canvas.height);
  photoBase64 = canvas.toDataURL("image/jpeg", 0.8);

  /* ── Collect step-1 form data ── */
  const getVal = (id) => {
    const el = document.getElementById(id);
    return el ? el.value.trim() : "";
  };

  const nom = getVal("nom");
  const email = getVal("email");
  const password = getVal("password");
  const telephone = getVal("telephone");
  const sexe = getVal("sexe");
  const dateNaissance = getVal("date_naissance");
  const ville = getVal("ville");
  const bio = getVal("bio");

  /* ── Loading UI ── */
  if (captureBtn) {
    captureBtn.disabled = true;
    captureBtn.textContent = "Création du compte…";
  }
  if (statusEl) statusEl.textContent = "⏳ Envoi des données…";

  const payload = {
    nom,
    email,
    password,
    telephone,
    sexe,
    date_naissance: dateNaissance,
    ville,
    bio,
    photo: photoBase64,
    face_vector:
      face.descriptor /* Already a plain Array per FaceRecognition docs */,
    interets: selectedInterets,
  };

  try {
    const response = await fetch(`${BASE}/api/register.php`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload),
    });

    const data = await response.json();

    if (data.success) {
      fr.stopDetectionLoop();
      fr.stopCamera();
      showToast("Compte créé ! Redirection…", "success");
      setTimeout(() => {
        window.location.href = "login.php?registered=1";
      }, 800);
    } else {
      throw new Error(data.error || "Erreur lors de la création du compte.");
    }
  } catch (err) {
    console.error("[register] captureAndRegister :", err);
    showToast(err.message, "error");

    if (captureBtn) {
      captureBtn.disabled = false;
      captureBtn.textContent = "Capturer et créer mon compte";
    }
    if (statusEl) statusEl.textContent = "👤 Réessayez en regardant la caméra";
  }
}

/* ──────────────────────────── shared utilities ───────────────────────────── */

/**
 * Calculate the integer age (in complete years) from an ISO date string.
 *
 * @param {string} dateNaissance  e.g. "1999-07-21"
 * @returns {number}
 */
function calculateAge(dateNaissance) {
  const birth = new Date(dateNaissance);
  const today = new Date();
  let age = today.getFullYear() - birth.getFullYear();
  const m = today.getMonth() - birth.getMonth();
  if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) {
    age--;
  }
  return age;
}

/**
 * Display a short-lived status toast at the top of the page.
 *
 * @param {string} message
 * @param {'info'|'success'|'warning'|'error'} [type='info']
 */
function showToast(message, type = "info") {
  const palette = {
    info: "#3b82f6",
    success: "#22c55e",
    warning: "#f59e0b",
    error: "#ef4444",
  };

  const toast = document.createElement("div");
  toast.className = `register-toast register-toast--${type}`;
  toast.setAttribute("role", "alert");
  toast.textContent = message;

  Object.assign(toast.style, {
    position: "fixed",
    top: "20px",
    left: "50%",
    transform: "translateX(-50%) translateY(0)",
    background: palette[type] || palette.info,
    color: "#fff",
    padding: "13px 26px",
    borderRadius: "10px",
    fontWeight: "600",
    fontSize: "14px",
    zIndex: "9999",
    boxShadow: "0 4px 16px rgba(0,0,0,0.2)",
    maxWidth: "90vw",
    textAlign: "center",
    opacity: "1",
    transition: "opacity 0.3s ease",
    whiteSpace: "pre-line",
  });

  document.body.appendChild(toast);

  /* Auto-dismiss */
  setTimeout(() => {
    toast.style.opacity = "0";
    setTimeout(() => toast.remove(), 320);
  }, 3200);
}

/* ──────────────────────────── DOMContentLoaded ────────────────────────────── */

document.addEventListener("DOMContentLoaded", () => {
  /* ── Ensure only step 1 is visible on load ── */
  document.querySelectorAll(".form-step").forEach((el, idx) => {
    if (idx === 0) {
      el.style.display = "block";
      el.classList.add("active");
    } else {
      el.style.display = "none";
      el.classList.remove("active");
    }
  });

  updateProgressBar(1);

  /* ── Fetch interests for step 2 ── */
  loadInterets();

  /* ── Update counter label on load (handles the case of 0 selected) ── */
  updateInteretsCounter();
});
