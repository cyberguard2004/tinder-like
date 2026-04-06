let currentMatches = [];
let currentIndex = 0;

// Charger les profils au chargement
document.addEventListener("DOMContentLoaded", loadPotentialMatches);

async function loadPotentialMatches() {
  try {
    const response = await fetch("/api/get-potential-matches.php");
    const data = await response.json();

    if (data.success) {
      currentMatches = data.matches;
      displayNextProfile();
    } else {
      showError(data.error);
    }
  } catch (error) {
    console.error("Erreur:", error);
    showError("Erreur de chargement");
  }
}

function displayNextProfile() {
  const container = document.getElementById("cardsContainer");

  if (currentIndex >= currentMatches.length) {
    container.innerHTML = `
            <div class="no-more-profiles">
                <i class="fas fa-smile-wink"></i>
                <h3>Plus de profils pour le moment</h3>
                <p>Revenez plus tard ou modifiez vos préférences</p>
                <button onclick="loadPotentialMatches()" class="btn-primary">
                    Rafraîchir
                </button>
            </div>
        `;
    return;
  }

  const match = currentMatches[currentIndex];

  container.innerHTML = `
        <div class="profile-card" id="currentCard">
            <div class="card-image">
                <img src="${match.photo_url || "../assets/images/default-avatar.jpg"}"
                     alt="${match.nom}">
                <div class="card-info">
                    <h2>${match.nom}</h2>
                    <p><i class="fas fa-map-marker-alt"></i> ${match.ville || "Localisation inconnue"}</p>
                </div>
            </div>

            <div class="card-details">
                <div class="interets-section">
                    <h3>Centres d'intérêt communs (${match.interets_communs})</h3>
                    <div class="interets-tags">
                        ${match.liste_interets
                          .slice(0, 5)
                          .map(
                            (interet) => `<span class="tag">${interet}</span>`,
                          )
                          .join("")}
                        ${
                          match.liste_interets.length > 5
                            ? `<span class="tag">+${match.liste_interets.length - 5}</span>`
                            : ""
                        }
                    </div>
                </div>

                ${
                  match.bio
                    ? `
                    <div class="bio-section">
                        <h3>À propos</h3>
                        <p>${match.bio}</p>
                    </div>
                `
                    : ""
                }
            </div>
        </div>
    `;

  // Animation d'entrée
  setTimeout(() => {
    document.getElementById("currentCard").classList.add("show");
  }, 100);
}

async function swipe(action) {
  if (currentIndex >= currentMatches.length) return;

  const currentCard = document.getElementById("currentCard");
  const match = currentMatches[currentIndex];

  // Animation de swipe
  currentCard.style.transform =
    action === "like"
      ? "translateX(200%) rotate(30deg)"
      : "translateX(-200%) rotate(-30deg)";
  currentCard.style.opacity = "0";

  try {
    // Envoyer l'action au serveur
    const response = await fetch("/api/like-user.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        to_user_id: match.id,
        type: action,
      }),
    });

    const data = await response.json();

    if (data.match) {
      // C'est un match ! Afficher une notification
      showMatchNotification(match);
    }
  } catch (error) {
    console.error("Erreur:", error);
  }

  // Passer au profil suivant
  setTimeout(() => {
    currentIndex++;
    displayNextProfile();
  }, 300);
}

function showMatchNotification(match) {
  const notification = document.createElement("div");
  notification.className = "match-notification";
  notification.innerHTML = `
        <div class="match-content">
            <h2>C'est un match ! 🎉</h2>
            <p>Vous et ${match.nom} vous êtes likés</p>
            <div class="match-actions">
                <button onclick="startChat(${match.id})" class="btn-primary">
                    Envoyer un message
                </button>
                <button onclick="closeNotification()" class="btn-outline">
                    Continuer
                </button>
            </div>
        </div>
    `;

  document.body.appendChild(notification);

  // Supprimer après 5 secondes
  setTimeout(() => {
    if (notification.parentNode) {
      notification.remove();
    }
  }, 5000);
}

function startChat(userId) {
  window.location.href = `/pages/chat.php?user=${userId}`;
}

function closeNotification() {
  const notification = document.querySelector(".match-notification");
  if (notification) {
    notification.remove();
  }
}

function showError(message) {
  const container = document.getElementById("cardsContainer");
  container.innerHTML = `
        <div class="error-message">
            <i class="fas fa-exclamation-circle"></i>
            <p>${message}</p>
            <button onclick="loadPotentialMatches()" class="btn-primary">
                Réessayer
            </button>
        </div>
    `;
}

function logout() {
  fetch("/api/logout.php").then(() => {
    window.location.href = "/index.php";
  });
}

// Ajouter les événements clavier pour swiper
document.addEventListener("keydown", (e) => {
  if (e.key === "ArrowLeft") {
    swipe("dislike");
  } else if (e.key === "ArrowRight") {
    swipe("like");
  } else if (e.key === "ArrowUp") {
    swipe("superlike");
  }
});
