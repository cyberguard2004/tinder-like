/**
 * FaceRecognition — improved model loading fallback and flexible detection loop
 *
 * This file replaces the previous implementation to:
 *  - Attempt multiple sensible locations for model files (folder variations).
 *  - Provide clearer error messages when model loading fails (including manifest/shard mismatch).
 *  - Support a flexible detection loop signature: startDetectionLoop(callback, ms) OR startDetectionLoop(ms, callback).
 *
 * Usage:
 *   const fr = new FaceRecognition();
 *   await fr.loadModels();
 *   await fr.startVideo(document.getElementById('video'));
 *   fr.startDetectionLoop((detection) => { ... }, 300);
 *
 * Notes:
 *  - face-api.js (0.22.2) and @tensorflow/tfjs must be loaded before this file.
 *  - Model folder (this.modelPath) should contain the expected files produced by face-api's `save` process,
 *    typically files like `tiny_face_detector_model-weights_manifest.json`, `face_recognition_model-weights_manifest.json`,
 *    and corresponding shard files. If model files are incomplete/corrupt you will get descriptive errors.
 */

class FaceRecognition {
  constructor() {
    this.stream = null;
    this.video = null;
    this.modelsLoaded = false;
    this.lastDetection = null;
    // Default model path (served by your webserver). Can be overridden before calling loadModels().
    this.modelPath = "/tinder/models";
    this.detectionInterval = null;
  }

  /**
   * Try to load the required face-api.js nets from a list of candidate base URIs.
   * The method attempts each candidate in order and returns as soon as one succeeds.
   *
   * Candidate URIs tried (in order):
   *  - this.modelPath
   *  - this.modelPath + '/models'
   *  - '/models'
   *
   * If all attempts fail the function throws an Error with detailed information.
   *
   * @returns {Promise<void>}
   */
  async loadModels() {
    if (typeof faceapi === "undefined") {
      throw new Error(
        "face-api.js is not loaded. Include the face-api.js script before this file.",
      );
    }

    // Small helper: try loading from one base path and return if successful,
    // otherwise return the thrown error so we can aggregate messages.
    const tryLoadFrom = async (basePath) => {
      try {
        await Promise.all([
          faceapi.nets.tinyFaceDetector.loadFromUri(basePath),
          faceapi.nets.faceLandmark68Net.loadFromUri(basePath),
          faceapi.nets.faceRecognitionNet.loadFromUri(basePath),
        ]);
        // success
        return { ok: true, basePath };
      } catch (err) {
        // wrap to preserve original message
        return { ok: false, basePath, error: err };
      }
    };

    const candidates = [
      this.modelPath,
      // Some deployments put models under a 'models' subfolder
      (this.modelPath || "") + "/models",
      // Fallback common location
      "/models",
    ].filter(Boolean);

    const errors = [];

    for (const candidate of candidates) {
      try {
        const result = await tryLoadFrom(candidate);
        if (result.ok) {
          this.modelsLoaded = true;
          this.modelPath = candidate; // normalize to the working path
          console.info(
            "[FaceRecognition] Models loaded successfully from:",
            candidate,
          );
          return;
        } else {
          // Collect error reason
          const msg =
            result.error && result.error.message
              ? result.error.message
              : String(result.error);
          errors.push(
            `Attempt at "${candidate}" failed: ${msg.replace(/\n/g, " ")}`,
          );
        }
      } catch (e) {
        errors.push(`Attempt at "${candidate}" threw: ${e.message || e}`);
      }
    }

    // If we reach here no candidate succeeded. Try to provide additional diagnostic hints.
    const hint = [
      "Model loading failed. Checked locations:",
      ...errors.map((e) => ` - ${e}`),
      "",
      "Common causes:",
      " - model files missing from the server (check that files like",
      "   'tiny_face_detector_model-weights_manifest.json' and corresponding shards exist)",
      " - model files are corrupted or partial (shard count mismatch)",
      " - incorrect permissions preventing the webserver from serving files",
      "",
      `Configured modelPath: ${this.modelPath}`,
    ].join("\n");

    // throw a single error with the aggregated diagnostic text
    const finalError = new Error(hint);
    this.modelsLoaded = false;
    throw finalError;
  }

  /**
   * Start the user's webcam and attach it to the provided video element.
   * Resolves when metadata has loaded and playback started.
   *
   * @param {HTMLVideoElement} videoElement
   * @returns {Promise<boolean>} true when successfully playing
   */
  async startVideo(videoElement) {
    if (!videoElement || !(videoElement instanceof HTMLVideoElement)) {
      throw new Error("startVideo requires an HTMLVideoElement.");
    }

    this.video = videoElement;

    let stream = null;

    try {
      stream = await navigator.mediaDevices.getUserMedia({
        video: {
          width: { ideal: 640 },
          height: { ideal: 480 },
          facingMode: "user",
        },
        audio: false,
      });
    } catch (err) {
      // Map common DOMException errors to user-friendly messages.
      const name = err && err.name ? err.name : "";
      if (name === "NotAllowedError" || name === "PermissionDeniedError") {
        throw new Error(
          "Camera access denied. Please allow camera permissions for this site and reload.",
        );
      } else if (name === "NotFoundError" || name === "DevicesNotFoundError") {
        throw new Error("No camera found on this device.");
      } else if (name === "NotReadableError" || name === "TrackStartError") {
        throw new Error("Camera is already in use by another application.");
      } else if (
        name === "OverconstrainedError" ||
        name === "ConstraintNotSatisfiedError"
      ) {
        // Retry with minimal constraints
        try {
          stream = await navigator.mediaDevices.getUserMedia({
            video: true,
            audio: false,
          });
        } catch (retryErr) {
          throw new Error(
            "Requested camera constraints are not supported by this device.",
          );
        }
      } else {
        // generic message
        throw new Error(
          "Unable to access camera: " + (err.message || name || err),
        );
      }
    }

    // attach stream
    this.stream = stream;
    videoElement.srcObject = stream;

    // Ensure metadata loaded
    return new Promise((resolve, reject) => {
      const onLoaded = () => {
        // autoplay may be blocked; try to play
        videoElement
          .play()
          .then(() => {
            // small timeout to ensure dimensions are ready
            setTimeout(() => resolve(true), 50);
          })
          .catch((playErr) => {
            // still treat as success if metadata available; caller can decide
            resolve(true);
          });
      };

      const onError = (e) => {
        reject(new Error("Video element error: " + (e.message || e)));
      };

      videoElement.onloadedmetadata = onLoaded;
      videoElement.onerror = onError;
    });
  }

  /**
   * Stop camera and release tracks.
   */
  stopCamera() {
    if (this.stream) {
      try {
        this.stream.getTracks().forEach((t) => {
          try {
            t.stop();
          } catch (e) {}
        });
      } catch (e) {
        // ignore
      }
      this.stream = null;
    }

    if (this.video) {
      try {
        this.video.srcObject = null;
      } catch (e) {}
    }
    this.lastDetection = null;
  }

  /**
   * Detect a single face (if present) from the current video frame.
   * Returns an object { descriptor: number[], score, detection } or null.
   */
  async detectFace() {
    if (!this.modelsLoaded) {
      // Not ready to detect
      return null;
    }
    if (!this.video) return null;
    if (this.video.readyState < 2 /* HAVE_CURRENT_DATA */) return null;
    if (this.video.videoWidth === 0 || this.video.videoHeight === 0)
      return null;

    try {
      const options = new faceapi.TinyFaceDetectorOptions({
        scoreThreshold: 0.5,
        inputSize: 224,
      });

      const detection = await faceapi
        .detectSingleFace(this.video, options)
        .withFaceLandmarks()
        .withFaceDescriptor();

      if (!detection) {
        this.lastDetection = null;
        return null;
      }

      const result = {
        descriptor: Array.from(detection.descriptor || []),
        score: detection.detection ? detection.detection.score : 0,
        detection: detection,
      };

      this.lastDetection = result;
      return result;
    } catch (err) {
      // If the detection throws due to malformed model files, bubble a helpful message.
      console.warn(
        "[FaceRecognition] detectFace error:",
        err && err.message ? err.message : err,
      );
      return null;
    }
  }

  /**
   * Draw detection results (bounding box + landmarks) from the last detection onto canvas.
   * Safe to call repeatedly; nothing is drawn if there is no last detection.
   *
   * @param {HTMLCanvasElement} canvas
   */
  drawFaceDetection(canvas) {
    if (!canvas || !(canvas instanceof HTMLCanvasElement)) return;
    if (!this.video || !this.lastDetection || !this.lastDetection.detection)
      return;

    // Use the video's actual pixel dimensions for drawing accuracy.
    const displaySize = {
      width: this.video.videoWidth || canvas.width || 640,
      height: this.video.videoHeight || canvas.height || 480,
    };

    // Ensure canvas matches display size
    canvas.width = displaySize.width;
    canvas.height = displaySize.height;

    // face-api helpers expect a simple object; use resizeResults if necessary
    let resized;
    try {
      resized = faceapi.resizeResults(
        this.lastDetection.detection,
        displaySize,
      );
    } catch (e) {
      // If resizeResults fails, fall back to using the detection as-is.
      resized = this.lastDetection.detection;
    }

    const ctx = canvas.getContext("2d");
    ctx.clearRect(0, 0, canvas.width, canvas.height);

    const score =
      this.lastDetection.score ||
      (resized.detection && resized.detection.score) ||
      0;
    const color = score >= 0.8 ? "#00c853" : "#ffab00";

    // bounding box
    const box =
      resized.detection && resized.detection.box ? resized.detection.box : null;
    if (box) {
      ctx.save();
      ctx.strokeStyle = color;
      ctx.lineWidth = 2.5;
      ctx.shadowColor = color;
      ctx.shadowBlur = 6;
      ctx.strokeRect(box.x, box.y, box.width, box.height);
      ctx.restore();
    }

    // confidence label
    if (box) {
      const pct = Math.round(score * 100);
      const labelText = `${pct}%`;
      const fontSize = Math.max(12, Math.round((box.height || 40) * 0.06));
      ctx.save();
      ctx.font = `600 ${fontSize}px Poppins, sans-serif`;
      ctx.textBaseline = "middle";
      const padding = 6;
      const textW = ctx.measureText(labelText).width;
      const labelW = textW + padding * 2;
      const labelH = fontSize + padding;
      const labelX = Math.max(6, box.x);
      const labelY = Math.max(6, box.y - labelH - 6);
      // background
      ctx.fillStyle = color;
      this._roundRect(ctx, labelX, labelY, labelW, labelH, 6);
      ctx.fill();
      // text
      ctx.fillStyle = "#fff";
      ctx.fillText(labelText, labelX + padding, labelY + labelH / 2);
      ctx.restore();
    }

    // landmarks
    if (resized.landmarks && Array.isArray(resized.landmarks.positions)) {
      const positions = resized.landmarks.positions;
      ctx.save();
      ctx.fillStyle = color;
      ctx.shadowColor = color;
      ctx.shadowBlur = 4;
      const dotRadius = Math.max(1.2, box ? box.width * 0.006 : 2.0);
      for (const pt of positions) {
        ctx.beginPath();
        ctx.arc(pt.x, pt.y, dotRadius, 0, Math.PI * 2);
        ctx.fill();
      }
      ctx.restore();
    }
  }

  /**
   * Start a continuous detection loop. This function supports two calling signatures:
   *   startDetectionLoop(callback, ms)
   *   startDetectionLoop(ms, callback)
   *
   * callback will be invoked with (detection) where detection is either the detection
   * object returned by detectFace() or null.
   *
   * @param {function|number} a  Either callback or ms
   * @param {number|function} b  Either ms or callback
   */
  startDetectionLoop(a, b) {
    // Normalize to (callback, ms)
    let callback = null;
    let ms = 300;

    if (typeof a === "function") {
      callback = a;
      if (typeof b === "number") ms = b;
    } else if (typeof a === "number") {
      ms = a;
      if (typeof b === "function") callback = b;
    } else {
      // fallback: no callback provided
      if (typeof b === "function") callback = b;
    }

    // Stop previous loop if present
    this.stopDetectionLoop();

    // Minimal validation
    if (typeof ms !== "number" || ms <= 0) ms = 300;
    // Polling implementation uses setInterval; each tick awaits detectFace()
    this.detectionInterval = setInterval(async () => {
      try {
        const detection = await this.detectFace();
        if (typeof callback === "function") {
          // Ensure callback errors don't break the loop
          try {
            callback(detection);
          } catch (cbErr) {
            console.warn("[FaceRecognition] detection callback error:", cbErr);
          }
        }
      } catch (err) {
        console.warn(
          "[FaceRecognition] detection loop error:",
          err && err.message ? err.message : err,
        );
        if (typeof callback === "function") {
          try {
            callback(null);
          } catch (cbErr) {
            // ignore
          }
        }
      }
    }, ms);
  }

  /**
   * Stop the detection loop if running.
   */
  stopDetectionLoop() {
    if (this.detectionInterval !== null) {
      clearInterval(this.detectionInterval);
      this.detectionInterval = null;
    }
  }

  // ----------------- small canvas helpers -----------------

  _roundRect(ctx, x, y, w, h, r) {
    const rad = Math.max(0, r || 4);
    ctx.beginPath();
    ctx.moveTo(x + rad, y);
    ctx.lineTo(x + w - rad, y);
    ctx.quadraticCurveTo(x + w, y, x + w, y + rad);
    ctx.lineTo(x + w, y + h - rad);
    ctx.quadraticCurveTo(x + w, y + h, x + w - rad, y + h);
    ctx.lineTo(x + rad, y + h);
    ctx.quadraticCurveTo(x, y + h, x, y + h - rad);
    ctx.lineTo(x, y + rad);
    ctx.quadraticCurveTo(x, y, x + rad, y);
    ctx.closePath();
  }
}

if (typeof module !== "undefined" && module.exports) {
  module.exports = FaceRecognition;
}
