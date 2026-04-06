let currentMatchId = null;
let messageInterval = null;

document.addEventListener("DOMContentLoaded", () => {
  loadConversations();
});

async function loadConversations() {
  try {
    const response = await fetch("/api/get-conversations.php");
    const data = await response.json();

    if (data.success) {
      displayConversations(data.conversations);
    }
  } catch (error) {
    console.error("Erreur:", error);
  }
}

function displayConversations(conversations) {
  const list = document.getElementById("conversationsList");

  if (conversations.length === 0) {
    list.innerHTML = `
            <div class="no-conversations">
                <p>Aucune conversation pour le moment</p>
                <a href="dashboard.php" class="btn-primary">Découvrir des profils</a>
            </div>
        `;
    return;
  }

  list.innerHTML = conversations
    .map(
      (conv) => `
        <div class="conversation-item ${conv.unread_count > 0 ? "unread" : ""}"
             onclick="loadChat(${conv.match_id}, ${conv.user_id}, '${conv.user_name}', '${conv.user_photo}')">
            <img src="${conv.user_photo || "../assets/images/default-avatar.jpg"}"
                 alt="${conv.user_name}" class="conversation-avatar">
            <div class="conversation-info">
                <h4>${conv.user_name}</h4>
                <p class="last-message">${conv.last_message || "Nouveau match !"}</p>
            </div>
            ${conv.unread_count > 0 ? `<span class="unread-badge">${conv.unread_count}</span>` : ""}
        </div>
    `,
    )
    .join("");
}

function loadChat(matchId, userId, userName, userPhoto) {
  currentMatchId = matchId;

  // Mettre à jour l'UI
  document.getElementById("chatArea").innerHTML = `
        <div class="chat-header">
            <img src="${userPhoto || "../assets/images/default-avatar.jpg"}"
                 alt="${userName}" class="chat-avatar">
            <h3>${userName}</h3>
        </div>

        <div class="messages-container" id="messagesContainer">
            <div class="loading">Chargement des messages...</div>
        </div>

        <div class="message-input-container">
            <textarea id="messageInput" placeholder="Écrivez votre message..."
                      rows="1" onkeydown="handleMessageKey(event)"></textarea>
            <button onclick="sendMessage()" class="send-btn">
                <i class="fas fa-paper-plane"></i>
            </button>
        </div>
    `;

  // Charger les messages
  loadMessages(matchId);

  // Démarrer le rafraîchissement automatique
  if (messageInterval) {
    clearInterval(messageInterval);
  }
  messageInterval = setInterval(() => loadMessages(matchId), 3000);
}

async function loadMessages(matchId) {
  try {
    const response = await fetch(`/api/get-messages.php?match_id=${matchId}`);
    const data = await response.json();

    if (data.success) {
      displayMessages(data.messages);
    }
  } catch (error) {
    console.error("Erreur chargement messages:", error);
  }
}

function displayMessages(messages) {
  const container = document.getElementById("messagesContainer");
  if (!container) return;

  container.innerHTML = messages
    .map(
      (msg) => `
        <div class="message ${msg.sender_id == currentUserId ? "sent" : "received"}">
            <div class="message-content">
                <p>${escapeHtml(msg.message)}</p>
                <span class="message-time">${formatTime(msg.date_envoi)}</span>
            </div>
        </div>
    `,
    )
    .join("");

  // Scroll en bas
  container.scrollTop = container.scrollHeight;
}

async function sendMessage() {
  const input = document.getElementById("messageInput");
  const message = input.value.trim();

  if (!message || !currentMatchId) return;

  try {
    const response = await fetch("/api/send-message.php", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({
        match_id: currentMatchId,
        message: message,
      }),
    });

    const data = await response.json();

    if (data.success) {
      input.value = "";
      loadMessages(currentMatchId);
    }
  } catch (error) {
    console.error("Erreur envoi message:", error);
  }
}

function handleMessageKey(event) {
  if (event.key === "Enter" && !event.shiftKey) {
    event.preventDefault();
    sendMessage();
  }
}

function formatTime(datetime) {
  const date = new Date(datetime);
  const now = new Date();

  if (date.toDateString() === now.toDateString()) {
    return date.toLocaleTimeString([], { hour: "2-digit", minute: "2-digit" });
  } else {
    return date.toLocaleDateString([], { day: "2-digit", month: "2-digit" });
  }
}

function escapeHtml(text) {
  const div = document.createElement("div");
  div.textContent = text;
  return div.innerHTML;
}
