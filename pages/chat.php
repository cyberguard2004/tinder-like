<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
  <title>Messages - MatchFace Campus</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700;14..32,800&display=swap" rel="stylesheet">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Inter', sans-serif;
      background: #f8f9fc;
      overflow: hidden;
    }

    /* Layout */
    .chat-layout {
      display: flex;
      height: 100vh;
      overflow: hidden;
    }

    /* Sidebar */
    .chat-sidebar {
      width: 360px;
      min-width: 360px;
      background: #fff;
      border-right: 1px solid #f0f0f5;
      display: flex;
      flex-direction: column;
      height: 100vh;
      overflow: hidden;
      transition: transform 0.3s cubic-bezier(0.2, 0.9, 0.4, 1.1);
      z-index: 10;
    }

    .chat-sidebar-header {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      padding: 1.2rem 1.5rem;
      border-bottom: 1px solid #f0f0f5;
      background: #fff;
      flex-shrink: 0;
    }

    .chat-sidebar-header h2 {
      font-size: 1.3rem;
      font-weight: 800;
      color: #1a1a2e;
      margin: 0;
      flex: 1;
    }

    .back-discover-btn {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.5rem 1rem;
      background: linear-gradient(135deg, #fff5f6, #ffe8eb);
      color: #fd5068;
      border: none;
      border-radius: 40px;
      font-size: 0.85rem;
      font-weight: 600;
      font-family: inherit;
      cursor: pointer;
      text-decoration: none;
      transition: all 0.2s;
    }

    .back-discover-btn:hover {
      background: linear-gradient(135deg, #fd5068, #fc7c45);
      color: #fff;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(253, 80, 104, 0.3);
    }

    /* Conversations List */
    .conversations-list {
      flex: 1;
      overflow-y: auto;
    }

    .conversations-list::-webkit-scrollbar {
      width: 5px;
    }

    .conversations-list::-webkit-scrollbar-track {
      background: transparent;
    }

    .conversations-list::-webkit-scrollbar-thumb {
      background: #ffd6dc;
      border-radius: 10px;
    }

    .conv-loading {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 3rem;
      gap: 1rem;
      color: #aaa;
    }

    .conv-spinner {
      width: 36px;
      height: 36px;
      border: 3.5px solid #ffd6dc;
      border-top-color: #fd5068;
      border-radius: 50%;
      animation: spin 0.8s linear infinite;
    }

    @keyframes spin {
      to { transform: rotate(360deg); }
    }

    .conversation-item {
      display: flex;
      align-items: center;
      gap: 1rem;
      padding: 1rem 1.5rem;
      cursor: pointer;
      border-bottom: 1px solid #f8f8fb;
      transition: all 0.2s ease;
      position: relative;
    }

    .conversation-item:hover {
      background: #fdf5f6;
      transform: translateX(4px);
    }

    .conversation-item.active {
      background: linear-gradient(135deg, #fff5f6, #ffe8eb);
    }

    .conversation-item.active::before {
      content: '';
      position: absolute;
      left: 0;
      top: 0;
      bottom: 0;
      width: 4px;
      background: linear-gradient(180deg, #fd5068, #fc7c45);
      border-radius: 0 4px 4px 0;
    }

    .conversation-avatar {
      width: 56px;
      height: 56px;
      border-radius: 50%;
      object-fit: cover;
      flex-shrink: 0;
      border: 2px solid #f0f0f0;
      transition: border-color 0.2s;
    }

    .conversation-item.active .conversation-avatar {
      border-color: #fd5068;
    }

    .conversation-info {
      flex: 1;
      min-width: 0;
    }

    .conversation-info h4 {
      font-size: 1rem;
      font-weight: 700;
      color: #1a1a2e;
      margin: 0 0 0.3rem;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .last-message {
      font-size: 0.85rem;
      color: #aaa;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      margin: 0;
    }

    .conversation-item.unread .last-message {
      color: #333;
      font-weight: 600;
    }

    .conv-time {
      font-size: 0.7rem;
      color: #ccc;
      flex-shrink: 0;
      align-self: flex-start;
      padding-top: 0.2rem;
    }

    .unread-badge {
      position: absolute;
      right: 1.5rem;
      bottom: 1rem;
      min-width: 22px;
      height: 22px;
      background: linear-gradient(135deg, #fd5068, #fc7c45);
      color: #fff;
      border-radius: 30px;
      font-size: 0.7rem;
      font-weight: 700;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: 0 7px;
      animation: pulse 1s ease infinite;
    }

    @keyframes pulse {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.08); }
    }

    /* No Conversations */
    .no-conversations {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 3rem 1.5rem;
      text-align: center;
      gap: 1rem;
    }

    .no-conversations i {
      font-size: 3rem;
      color: #e0e0e0;
    }

    .no-conversations h4 {
      font-size: 1.1rem;
      font-weight: 700;
      color: #555;
    }

    .no-conversations p {
      font-size: 0.85rem;
      color: #aaa;
    }

    .no-conversations a {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.7rem 1.5rem;
      background: linear-gradient(135deg, #fd5068, #fc7c45);
      color: #fff;
      border-radius: 40px;
      font-size: 0.9rem;
      font-weight: 600;
      text-decoration: none;
      transition: all 0.2s;
    }

    .no-conversations a:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(253, 80, 104, 0.35);
    }

    /* Chat Main Area */
    .chat-main {
      flex: 1;
      display: flex;
      flex-direction: column;
      height: 100vh;
      overflow: hidden;
      background: #f8f9fc;
    }

    /* Empty State */
    .chat-empty-state {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      height: 100%;
      gap: 1.2rem;
      padding: 2rem;
      text-align: center;
      animation: fadeInUp 0.4s ease;
    }

    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .chat-empty-icon {
      width: 100px;
      height: 100px;
      background: linear-gradient(135deg, #fff0f2, #ffe0e5);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 2.8rem;
    }

    .chat-empty-state h3 {
      font-size: 1.3rem;
      font-weight: 800;
      color: #444;
    }

    .chat-empty-state p {
      font-size: 0.9rem;
      color: #aaa;
      max-width: 280px;
      line-height: 1.5;
    }

    /* Active Chat Area */
    .chat-area {
      display: flex;
      flex-direction: column;
      height: 100%;
      overflow: hidden;
    }

    .chat-top-header {
      display: flex;
      align-items: center;
      gap: 1rem;
      padding: 1rem 1.5rem;
      background: #fff;
      border-bottom: 1px solid #f0f0f5;
      flex-shrink: 0;
      box-shadow: 0 2px 12px rgba(0, 0, 0, 0.04);
    }

    .chat-back-mobile {
      display: none;
      align-items: center;
      justify-content: center;
      width: 38px;
      height: 38px;
      background: transparent;
      border: none;
      color: #fd5068;
      font-size: 1.2rem;
      cursor: pointer;
      border-radius: 50%;
      transition: all 0.2s;
    }

    .chat-back-mobile:hover {
      background: #fff5f6;
    }

    .chat-top-avatar {
      width: 52px;
      height: 52px;
      border-radius: 50%;
      object-fit: cover;
      border: 2.5px solid #ffd6dc;
      flex-shrink: 0;
    }

    .chat-top-info {
      flex: 1;
      min-width: 0;
    }

    .chat-top-info h3 {
      font-size: 1.1rem;
      font-weight: 800;
      color: #1a1a2e;
      margin: 0;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .chat-top-info span {
      font-size: 0.75rem;
      color: #27ae60;
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: 0.3rem;
    }

    .chat-profile-link {
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
      padding: 0.45rem 1rem;
      border: 1.5px solid #e8ecf0;
      border-radius: 40px;
      font-size: 0.8rem;
      font-weight: 600;
      color: #666;
      text-decoration: none;
      transition: all 0.2s;
    }

    .chat-profile-link:hover {
      border-color: #fd5068;
      color: #fd5068;
      background: #fff5f6;
      transform: translateY(-2px);
    }

    /* Messages Container */
    .messages-container {
      flex: 1;
      overflow-y: auto;
      padding: 1.2rem 1.5rem;
      display: flex;
      flex-direction: column;
      gap: 0.3rem;
    }

    .messages-container::-webkit-scrollbar {
      width: 6px;
    }

    .messages-container::-webkit-scrollbar-track {
      background: transparent;
    }

    .messages-container::-webkit-scrollbar-thumb {
      background: #e0e0e0;
      border-radius: 10px;
    }

    .message {
      display: flex;
      margin-bottom: 0.2rem;
      animation: slideIn 0.25s ease;
    }

    @keyframes slideIn {
      from {
        opacity: 0;
        transform: translateY(8px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .message.sent {
      justify-content: flex-end;
    }

    .message.received {
      justify-content: flex-start;
    }

    .message-bubble {
      max-width: 70%;
      padding: 0.7rem 1.1rem;
      border-radius: 22px;
      font-size: 0.95rem;
      line-height: 1.45;
      word-break: break-word;
      position: relative;
    }

    .message.sent .message-bubble {
      background: linear-gradient(135deg, #fd5068, #fc7c45);
      color: #fff;
      border-bottom-right-radius: 6px;
      box-shadow: 0 2px 8px rgba(253, 80, 104, 0.2);
    }

    .message.received .message-bubble {
      background: #fff;
      color: #1a1a2e;
      border-bottom-left-radius: 6px;
      box-shadow: 0 1px 8px rgba(0, 0, 0, 0.06);
    }

    .message-time {
      display: block;
      font-size: 0.65rem;
      margin-top: 0.25rem;
      opacity: 0.7;
    }

    .message.sent .message-time {
      text-align: right;
      color: rgba(255, 255, 255, 0.8);
    }

    .message.received .message-time {
      text-align: left;
      color: #aaa;
    }

    /* Consecutive messages styling */
    .message.sent + .message.sent .message-bubble {
      border-top-right-radius: 14px;
    }

    .message.received + .message.received .message-bubble {
      border-top-left-radius: 14px;
    }

    /* Date Separator */
    .date-separator {
      display: flex;
      align-items: center;
      gap: 1rem;
      margin: 1rem 0;
    }

    .date-separator::before,
    .date-separator::after {
      content: '';
      flex: 1;
      height: 1px;
      background: linear-gradient(90deg, transparent, #e0e0e0, transparent);
    }

    .date-separator span {
      font-size: 0.7rem;
      color: #bbb;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    /* Message Input Area */
    .message-input-area {
      display: flex;
      align-items: flex-end;
      gap: 0.8rem;
      padding: 1rem 1.5rem;
      background: #fff;
      border-top: 1px solid #f0f0f5;
      flex-shrink: 0;
    }

    .message-input-area textarea {
      flex: 1;
      padding: 0.8rem 1.2rem;
      border: 2px solid #e8ecf0;
      border-radius: 28px;
      font-family: inherit;
      font-size: 0.92rem;
      color: #1a1a2e;
      background: #f8f9fc;
      resize: none;
      max-height: 120px;
      min-height: 48px;
      line-height: 1.4;
      transition: all 0.2s;
      outline: none;
    }

    .message-input-area textarea:focus {
      border-color: #fd5068;
      background: #fff;
      box-shadow: 0 0 0 4px rgba(253, 80, 104, 0.08);
    }

    .message-input-area textarea::placeholder {
      color: #c0c0c8;
    }

    .send-btn {
      width: 48px;
      height: 48px;
      border-radius: 50%;
      background: linear-gradient(135deg, #fd5068, #fc7c45);
      color: #fff;
      border: none;
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.1rem;
      transition: all 0.2s cubic-bezier(0.2, 0.9, 0.4, 1.2);
      flex-shrink: 0;
      box-shadow: 0 4px 14px rgba(253, 80, 104, 0.35);
    }

    .send-btn:hover:not(:disabled) {
      transform: scale(1.08);
      box-shadow: 0 6px 20px rgba(253, 80, 104, 0.5);
    }

    .send-btn:active:not(:disabled) {
      transform: scale(0.95);
    }

    .send-btn:disabled {
      opacity: 0.5;
      cursor: not-allowed;
    }

    /* Mobile Responsive */
    @media (max-width: 768px) {
      .chat-sidebar {
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 100%;
        min-width: unset;
        transform: translateX(0);
        z-index: 20;
      }

      .chat-sidebar.chat-open {
        transform: translateX(-100%);
      }

      .chat-main {
        position: absolute;
        left: 0;
        top: 0;
        right: 0;
        bottom: 0;
        transform: translateX(100%);
        transition: transform 0.3s cubic-bezier(0.2, 0.9, 0.4, 1.1);
        z-index: 25;
      }

      .chat-main.chat-open {
        transform: translateX(0);
      }

      .chat-back-mobile {
        display: flex;
      }

      .chat-top-header {
        padding: 0.8rem 1rem;
      }

      .chat-top-avatar {
        width: 44px;
        height: 44px;
      }

      .message-bubble {
        max-width: 85%;
        font-size: 0.88rem;
      }
    }
  </style>
</head>
<body>

<script>
const CURRENT_USER = {
  id: <?= intval($_SESSION["user_id"]) ?>,
  nom: "<?= htmlspecialchars($_SESSION["user_nom"] ?? "", ENT_QUOTES) ?>",
  photo: "<?= htmlspecialchars($_SESSION["user_photo"] ?? "/tinder/assets/images/default-avatar.jpg", ENT_QUOTES) ?>"
};
const BASE_URL = '/tinder';
const OPEN_MATCH_ID = <?= intval($_GET["match_id"] ?? 0) ?>;
</script>

<div class="chat-layout" id="chatLayout">
  <!-- Conversation Sidebar -->
  <div class="chat-sidebar" id="chatSidebar">
    <div class="chat-sidebar-header">
      <a href="dashboard.php" class="back-discover-btn">
        <i class="fas fa-fire"></i> Discover
      </a>
      <h2><i class="fas fa-comment-dots" style="color:#fd5068; margin-right:0.4rem;"></i> Messages</h2>
    </div>
    <div class="conversations-list" id="conversationsList">
      <div class="conv-loading">
        <div class="conv-spinner"></div>
        <span>Loading conversations...</span>
      </div>
    </div>
  </div>

  <!-- Chat Main Area -->
  <div class="chat-main" id="chatMain">
    <div id="chatArea" style="display:flex;flex-direction:column;height:100%;">
      <div class="chat-empty-state">
        <div class="chat-empty-icon">💬</div>
        <h3>Your Messages</h3>
        <p>Select a conversation to start chatting with your matches</p>
      </div>
    </div>
  </div>
</div>

<script>
/* ============================================================
   STATE
   ============================================================ */
let currentMatchId = null;
let currentUserName = '';
let currentUserPhoto = '';
let messageInterval = null;
let lastMessageCount = 0;
let isMobile = window.innerWidth <= 768;

window.addEventListener('resize', () => { isMobile = window.innerWidth <= 768; });

/* ============================================================
   INIT
   ============================================================ */
document.addEventListener('DOMContentLoaded', async () => {
  await loadConversations();
  if (OPEN_MATCH_ID > 0) {
    openMatchById(OPEN_MATCH_ID);
  }
});

/* ============================================================
   LOAD CONVERSATIONS
   ============================================================ */
async function loadConversations() {
  try {
    const resp = await fetch(BASE_URL + '/api/get-conversations.php');
    if (!resp.ok) throw new Error('HTTP ' + resp.status);
    const data = await resp.json();

    if (data.success) {
      renderConversationsList(data.conversations || []);
    } else {
      showConvError(data.error || 'Error loading conversations.');
    }
  } catch (err) {
    console.error('loadConversations:', err);
    showConvError('Unable to load conversations.');
  }
}

/* ============================================================
   RENDER CONVERSATIONS LIST
   ============================================================ */
function renderConversationsList(conversations) {
  const list = document.getElementById('conversationsList');

  if (!conversations || conversations.length === 0) {
    list.innerHTML = `
      <div class="no-conversations">
        <i class="fas fa-comment-slash"></i>
        <h4>No conversations yet</h4>
        <p>You don't have any matches yet. Start swiping!</p>
        <a href="dashboard.php"><i class="fas fa-fire"></i> Discover Profiles</a>
      </div>
    `;
    return;
  }

  list.innerHTML = conversations.map(conv => {
    const unread = parseInt(conv.unread_count) || 0;
    const photo = conv.user_photo || (BASE_URL + '/assets/images/default-avatar.jpg');
    const lastMsg = escapeHtml(conv.last_message || 'New match! 🎉');
    const timeStr = conv.last_time ? formatTime(conv.last_time) : '';
    const isActive = (currentMatchId === conv.match_id);

    return `
      <div class="conversation-item ${unread > 0 ? 'unread' : ''} ${isActive ? 'active' : ''}"
           id="conv-${conv.match_id}"
           data-match-id="${conv.match_id}"
           data-user-id="${conv.user_id}"
           data-user-name="${escapeHtml(conv.user_name)}"
           data-user-photo="${escapeHtml(photo)}">
        <img src="${escapeHtml(photo)}" alt="${escapeHtml(conv.user_name)}" class="conversation-avatar"
             onerror="this.src='${BASE_URL}/assets/images/default-avatar.jpg'">
        <div class="conversation-info">
          <h4>${escapeHtml(conv.user_name)}</h4>
          <p class="last-message">${lastMsg}</p>
        </div>
        ${timeStr ? `<span class="conv-time">${timeStr}</span>` : ''}
        ${unread > 0 ? `<span class="unread-badge">${unread > 99 ? '99+' : unread}</span>` : ''}
      </div>
    `;
  }).join('');

  document.querySelectorAll('.conversation-item').forEach(item => {
    item.addEventListener('click', function() {
      const matchId = this.dataset.matchId;
      const userId = this.dataset.userId;
      const userName = this.dataset.userName;
      const userPhoto = this.dataset.userPhoto;
      if (matchId && userId && userName) {
        openChat(parseInt(matchId), parseInt(userId), userName, userPhoto);
      }
    });
  });
}

/* ============================================================
   OPEN MATCH BY ID
   ============================================================ */
function openMatchById(matchId) {
  if (!matchId) return;
  const el = document.getElementById('conv-' + matchId);
  if (el && typeof el.click === 'function') {
    try {
      el.click();
    } catch (e) {
      fetchAndOpenMatch(matchId);
    }
  } else {
    fetchAndOpenMatch(matchId);
  }
}

async function fetchAndOpenMatch(matchId) {
  try {
    const resp = await fetch(BASE_URL + '/api/get-conversations.php');
    const data = await resp.json();
    if (data.success && data.conversations) {
      const conv = data.conversations.find(c => c.match_id == matchId);
      if (conv) {
        const photo = conv.user_photo || (BASE_URL + '/assets/images/default-avatar.jpg');
        openChat(conv.match_id, conv.user_id, conv.user_name, photo);
      }
    }
  } catch (err) {
    console.error('fetchAndOpenMatch:', err);
  }
}

/* ============================================================
   OPEN CHAT
   ============================================================ */
function openChat(matchId, userId, userName, userPhoto) {
  currentMatchId = matchId;
  currentUserName = userName;
  currentUserPhoto = userPhoto;
  lastMessageCount = 0;

  document.querySelectorAll('.conversation-item').forEach(el => el.classList.remove('active'));
  const convEl = document.getElementById('conv-' + matchId);
  if (convEl) {
    convEl.classList.add('active');
    convEl.classList.remove('unread');
    const badge = convEl.querySelector('.unread-badge');
    if (badge) badge.remove();
  }

  const chatArea = document.getElementById('chatArea');
  chatArea.style.display = 'flex';
  chatArea.innerHTML = `
    <div class="chat-area">
      <div class="chat-top-header">
        <button class="chat-back-mobile" onclick="closeMobileChat()" title="Back">
          <i class="fas fa-arrow-left"></i>
        </button>
        <img src="${escapeHtml(userPhoto)}" alt="${escapeHtml(userName)}" class="chat-top-avatar"
             onerror="this.src='${BASE_URL}/assets/images/default-avatar.jpg'">
        <div class="chat-top-info">
          <h3>${escapeHtml(userName)}</h3>
          <span><i class="fas fa-circle" style="font-size:0.55rem;"></i> Online</span>
        </div>
        <a href="matches.php" class="chat-profile-link">
          <i class="fas fa-heart"></i> View Matches
        </a>
      </div>
      <div class="messages-container" id="messagesContainer">
        <div class="conv-loading">
          <div class="conv-spinner"></div>
          <span>Loading messages...</span>
        </div>
      </div>
      <div class="message-input-area">
        <textarea id="messageInput"
                  placeholder="Type your message..."
                  rows="1"
                  onkeydown="handleMessageKey(event)"
                  oninput="autoResizeTextarea(this)"></textarea>
        <button class="send-btn" id="sendBtn" onclick="sendMessage()" title="Send">
          <i class="fas fa-paper-plane"></i>
        </button>
      </div>
    </div>
  `;

  if (isMobile) {
    document.getElementById('chatSidebar').classList.add('chat-open');
    document.getElementById('chatMain').classList.add('chat-open');
  }

  loadMessages(matchId);

  if (messageInterval) clearInterval(messageInterval);
  messageInterval = setInterval(() => loadMessages(matchId, true), 3000);
}

/* ============================================================
   LOAD MESSAGES
   ============================================================ */
async function loadMessages(matchId, silent = false) {
  if (matchId !== currentMatchId) return;

  try {
    const resp = await fetch(BASE_URL + '/api/get-messages.php?match_id=' + matchId);
    if (!resp.ok) throw new Error('HTTP ' + resp.status);
    const data = await resp.json();

    if (data.success) {
      const msgs = data.messages || [];
      if (!silent || msgs.length !== lastMessageCount) {
        lastMessageCount = msgs.length;
        renderMessages(msgs);
      }
    }
  } catch (err) {
    if (!silent) {
      const container = document.getElementById('messagesContainer');
      if (container) {
        container.innerHTML = `
          <div class="no-conversations">
            <i class="fas fa-exclamation-circle"></i>
            <h4>Error loading messages</h4>
            <p>Unable to load messages.</p>
          </div>
        `;
      }
    }
    console.error('loadMessages:', err);
  }
}

/* ============================================================
   RENDER MESSAGES
   ============================================================ */
function renderMessages(messages) {
  const container = document.getElementById('messagesContainer');
  if (!container) return;

  if (messages.length === 0) {
    container.innerHTML = `
      <div class="chat-empty-state" style="flex:1;">
        <div class="chat-empty-icon">👋</div>
        <h3>Start the conversation!</h3>
        <p>Say hello to ${escapeHtml(currentUserName)}</p>
      </div>
    `;
    return;
  }

  let html = '';
  let lastDate = null;

  messages.forEach((msg, idx) => {
    const isSent = parseInt(msg.sender_id) === CURRENT_USER.id;
    const msgDate = new Date(msg.date_envoi);
    const dateStr = formatDate(msgDate);

    if (dateStr !== lastDate) {
      html += `<div class="date-separator"><span>${escapeHtml(dateStr)}</span></div>`;
      lastDate = dateStr;
    }

    const prevMsg = messages[idx - 1];
    const sameAsPrev = prevMsg && parseInt(prevMsg.sender_id) === parseInt(msg.sender_id);
    const showTime = !sameAsPrev || (idx === messages.length - 1);

    html += `
      <div class="message ${isSent ? 'sent' : 'received'}">
        <div class="message-bubble">
          ${escapeHtml(msg.message)}
          ${showTime ? `<span class="message-time">${formatTimeFull(msgDate)}</span>` : ''}
        </div>
      </div>
    `;
  });

  const wasAtBottom = container.scrollHeight - container.scrollTop <= container.clientHeight + 80;
  container.innerHTML = html;

  if (wasAtBottom || lastMessageCount === 0) {
    container.scrollTop = container.scrollHeight;
  }
}

/* ============================================================
   SEND MESSAGE
   ============================================================ */
async function sendMessage() {
  const input = document.getElementById('messageInput');
  const sendBtn = document.getElementById('sendBtn');
  if (!input) return;

  const message = input.value.trim();
  if (!message || !currentMatchId) return;

  input.value = '';
  input.style.height = '';
  sendBtn.disabled = true;

  try {
    const resp = await fetch(BASE_URL + '/api/send-message.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ match_id: currentMatchId, message }),
    });

    const data = await resp.json();

    if (data.success) {
      await loadMessages(currentMatchId);
    } else {
      input.value = message;
      console.error('sendMessage API error:', data.error);
    }
  } catch (err) {
    input.value = message;
    console.error('sendMessage:', err);
  } finally {
    sendBtn.disabled = false;
    input.focus();
  }
}

/* ============================================================
   HANDLE KEYBOARD
   ============================================================ */
function handleMessageKey(e) {
  if (e.key === 'Enter' && !e.shiftKey) {
    e.preventDefault();
    sendMessage();
  }
}

/* ============================================================
   AUTO-RESIZE TEXTAREA
   ============================================================ */
function autoResizeTextarea(el) {
  el.style.height = '';
  el.style.height = Math.min(el.scrollHeight, 120) + 'px';
}

/* ============================================================
   CLOSE MOBILE CHAT
   ============================================================ */
function closeMobileChat() {
  document.getElementById('chatSidebar').classList.remove('chat-open');
  document.getElementById('chatMain').classList.remove('chat-open');

  if (messageInterval) {
    clearInterval(messageInterval);
    messageInterval = null;
  }
}

/* ============================================================
   SHOW CONVERSATION ERROR
   ============================================================ */
function showConvError(msg) {
  const list = document.getElementById('conversationsList');
  list.innerHTML = `
    <div class="no-conversations">
      <i class="fas fa-exclamation-circle" style="color:#fc4a6d;"></i>
      <h4>Error</h4>
      <p>${escapeHtml(msg)}</p>
      <a href="#" onclick="loadConversations();return false;">
        <i class="fas fa-redo"></i> Try Again
      </a>
    </div>
  `;
}

/* ============================================================
   DATE / TIME HELPERS
   ============================================================ */
function formatDate(date) {
  const today = new Date();
  const yesterday = new Date(today);
  yesterday.setDate(today.getDate() - 1);

  if (date.toDateString() === today.toDateString()) return 'Today';
  if (date.toDateString() === yesterday.toDateString()) return 'Yesterday';

  return date.toLocaleDateString('en-US', { day: 'numeric', month: 'long', year: 'numeric' });
}

function formatTimeFull(date) {
  return date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
}

function formatTime(dateStr) {
  const date = new Date(dateStr);
  const now = new Date();

  if (date.toDateString() === now.toDateString()) {
    return date.toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
  }

  const yesterday = new Date(now);
  yesterday.setDate(now.getDate() - 1);
  if (date.toDateString() === yesterday.toDateString()) return 'Yesterday';

  return date.toLocaleDateString('en-US', { day: '2-digit', month: '2-digit' });
}

/* ============================================================
   UTILITY
   ============================================================ */
function escapeHtml(str) {
  if (str === null || str === undefined) return '';
  const d = document.createElement('div');
  d.textContent = String(str);
  return d.innerHTML;
}
</script>

</body>
</html>
