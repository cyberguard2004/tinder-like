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
  <title>Discover - MatchFace Campus</title>
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
      background: linear-gradient(145deg, #fef9f0 0%, #fff5eb 100%);
      overflow-x: hidden;
    }

    /* App Layout */
    .app-layout {
      display: flex;
      min-height: 100vh;
    }

    /* Sidebar */
    .sidebar {
      width: 280px;
      background: rgba(255, 255, 255, 0.98);
      backdrop-filter: blur(10px);
      box-shadow: 2px 0 20px rgba(0, 0, 0, 0.05);
      display: flex;
      flex-direction: column;
      position: fixed;
      height: 100vh;
      z-index: 100;
      transition: transform 0.3s cubic-bezier(0.2, 0.9, 0.4, 1.1);
    }

    .logo {
      padding: 1.8rem 1.5rem 1rem;
      display: flex;
      align-items: center;
      gap: 0.6rem;
      font-size: 1.4rem;
      font-weight: 800;
      background: linear-gradient(135deg, #fd5068, #fc7c45);
      -webkit-background-clip: text;
      background-clip: text;
      color: transparent;
    }

    .user-profile-mini {
      padding: 1rem 1.5rem;
      border-bottom: 1px solid #f0f0f0;
      margin-bottom: 1rem;
    }

    .user-profile-mini a {
      display: flex;
      align-items: center;
      gap: 0.85rem;
      text-decoration: none;
    }

    .user-profile-mini img {
      width: 52px;
      height: 52px;
      border-radius: 50%;
      object-fit: cover;
      border: 2.5px solid #fd5068;
      transition: transform 0.2s;
    }

    .user-profile-mini img:hover {
      transform: scale(1.05);
    }

    .user-name {
      font-weight: 700;
      font-size: 1rem;
      color: #1a1a2e;
    }

    .sidebar-nav {
      flex: 1;
      padding: 0.5rem 1rem;
    }

    .sidebar-nav a {
      display: flex;
      align-items: center;
      gap: 0.9rem;
      padding: 0.85rem 1rem;
      margin: 0.25rem 0;
      border-radius: 14px;
      color: #666;
      text-decoration: none;
      font-weight: 500;
      font-size: 0.95rem;
      transition: all 0.2s ease;
    }

    .sidebar-nav a i {
      width: 24px;
      font-size: 1.2rem;
    }

    .sidebar-nav a.active {
      background: linear-gradient(135deg, #fff5f6, #ffe8eb);
      color: #fd5068;
      font-weight: 600;
    }

    .sidebar-nav a:hover:not(.active) {
      background: #f8f9fc;
      color: #fd5068;
      transform: translateX(4px);
    }

    .logout-btn {
      margin: 1rem 1.5rem 1.8rem;
      padding: 0.85rem;
      background: transparent;
      border: 2px solid #e8ecf0;
      border-radius: 14px;
      color: #888;
      font-family: inherit;
      font-size: 0.9rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.6rem;
    }

    .logout-btn:hover {
      border-color: #fd5068;
      color: #fd5068;
      background: #fff5f6;
    }

    /* Main Content */
    .main-content {
      flex: 1;
      margin-left: 280px;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      padding: 2rem;
    }

    /* Cards Stack */
    .cards-stack {
      position: relative;
      width: 100%;
      max-width: 480px;
      margin: 0 auto;
      min-height: 560px;
    }

    /* Profile Card */
    .profile-card {
      position: absolute;
      width: 100%;
      border-radius: 28px;
      overflow: hidden;
      background: #fff;
      box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
      cursor: grab;
      transition: box-shadow 0.3s ease;
      transform-origin: center;
    }

    .profile-card:active {
      cursor: grabbing;
    }

    .profile-card.card-2 {
      transform: translateY(12px) scale(0.96);
      z-index: 1;
    }

    .profile-card.card-1 {
      transform: translateY(24px) scale(0.92);
      z-index: 0;
    }

    .profile-card.active {
      z-index: 5;
      position: relative;
      transform: none;
    }

    .profile-card.go-right {
      transition: transform 0.5s cubic-bezier(0.2, 0.9, 0.4, 1.2), opacity 0.4s ease;
      transform: translateX(180%) rotate(30deg) !important;
      opacity: 0;
    }

    .profile-card.go-left {
      transition: transform 0.5s cubic-bezier(0.2, 0.9, 0.4, 1.2), opacity 0.4s ease;
      transform: translateX(-180%) rotate(-30deg) !important;
      opacity: 0;
    }

    .profile-card.go-up {
      transition: transform 0.5s cubic-bezier(0.2, 0.9, 0.4, 1.2), opacity 0.4s ease;
      transform: translateY(-180%) scale(0.85) !important;
      opacity: 0;
    }

    /* Card Photo */
    .card-photo {
      position: relative;
      height: 420px;
      overflow: hidden;
    }

    .card-photo img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform 0.3s ease;
    }

    .profile-card:hover .card-photo img {
      transform: scale(1.02);
    }

    .card-overlay {
      position: absolute;
      inset: 0;
      background: linear-gradient(to top, rgba(0, 0, 0, 0.75) 0%, transparent 50%);
    }

    .card-info {
      position: absolute;
      bottom: 0;
      left: 0;
      right: 0;
      padding: 1.8rem 1.5rem 1.2rem;
      color: #fff;
    }

    .card-info h2 {
      font-size: 1.9rem;
      font-weight: 800;
      margin-bottom: 0.3rem;
      text-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
      letter-spacing: -0.3px;
    }

    .card-info p {
      font-size: 1rem;
      opacity: 0.9;
      display: flex;
      align-items: center;
      gap: 0.4rem;
    }

    /* Swipe Labels */
    .like-label, .nope-label {
      position: absolute;
      top: 1.8rem;
      font-size: 2rem;
      font-weight: 800;
      padding: 0.5rem 1.2rem;
      border-radius: 60px;
      border: 4px solid;
      opacity: 0;
      pointer-events: none;
      z-index: 10;
      letter-spacing: 0.05em;
      backdrop-filter: blur(4px);
      transition: opacity 0.05s;
    }

    .like-label {
      left: 1.5rem;
      color: #27ae60;
      border-color: #27ae60;
      background: rgba(39, 174, 96, 0.15);
      transform: rotate(-12deg);
    }

    .nope-label {
      right: 1.5rem;
      color: #e53e3e;
      border-color: #e53e3e;
      background: rgba(229, 62, 62, 0.15);
      transform: rotate(12deg);
    }

    /* Card Details */
    .card-details {
      padding: 1.2rem 1.5rem 1.5rem;
      max-height: 200px;
      overflow-y: auto;
    }

    .card-details::-webkit-scrollbar {
      width: 4px;
    }

    .card-details::-webkit-scrollbar-track {
      background: #f0f0f0;
      border-radius: 4px;
    }

    .card-details::-webkit-scrollbar-thumb {
      background: #fdcdd3;
      border-radius: 4px;
    }

    .interets-section, .bio-section {
      margin-bottom: 1rem;
    }

    .interets-section h3, .bio-section h3 {
      font-size: 0.85rem;
      font-weight: 800;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      color: #999;
      margin-bottom: 0.6rem;
    }

    .interets-tags {
      display: flex;
      flex-wrap: wrap;
      gap: 0.5rem;
    }

    .tag {
      padding: 0.35rem 0.9rem;
      background: linear-gradient(135deg, #fff5f6, #ffe8eb);
      color: #fd5068;
      border: none;
      border-radius: 40px;
      font-size: 0.85rem;
      font-weight: 600;
      transition: transform 0.2s;
    }

    .tag:hover {
      transform: translateY(-2px);
    }

    .bio-section p {
      font-size: 0.95rem;
      color: #555;
      line-height: 1.5;
    }

    /* Action Buttons */
    .action-buttons {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 1.5rem;
      padding: 1.5rem 1rem 1rem;
      margin-top: 1rem;
    }

    .action-btn {
      display: flex;
      align-items: center;
      justify-content: center;
      border: none;
      border-radius: 50%;
      cursor: pointer;
      transition: all 0.25s cubic-bezier(0.2, 0.9, 0.4, 1.2);
      background: #fff;
      box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
    }

    .action-btn:hover {
      transform: scale(1.12);
    }

    .action-btn:active {
      transform: scale(0.95);
    }

    .action-btn.dislike {
      width: 62px;
      height: 62px;
      color: #fc4a6d;
      font-size: 1.4rem;
      background: #fff;
    }

    .action-btn.dislike:hover {
      box-shadow: 0 8px 28px rgba(252, 74, 109, 0.35);
      color: #fff;
      background: #fc4a6d;
    }

    .action-btn.superlike {
      width: 56px;
      height: 56px;
      color: #4a90e2;
      font-size: 1.2rem;
    }

    .action-btn.superlike:hover {
      box-shadow: 0 8px 28px rgba(74, 144, 226, 0.35);
      color: #fff;
      background: #4a90e2;
    }

    .action-btn.like {
      width: 72px;
      height: 72px;
      background: linear-gradient(135deg, #fd5068, #fc7c45);
      color: #fff;
      font-size: 1.6rem;
      box-shadow: 0 8px 24px rgba(253, 80, 104, 0.4);
    }

    .action-btn.like:hover {
      transform: scale(1.15);
      box-shadow: 0 12px 32px rgba(253, 80, 104, 0.55);
    }

    /* Keyboard Hint */
    .keyboard-hint {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 2rem;
      padding: 1rem 0 0;
      opacity: 0.5;
      font-size: 0.8rem;
      color: #888;
    }

    .keyboard-hint kbd {
      display: inline-block;
      padding: 0.2rem 0.55rem;
      border: 1.5px solid #ccc;
      border-radius: 8px;
      font-family: inherit;
      font-size: 0.75rem;
      background: #f5f5f5;
      font-weight: 600;
    }

    /* No More Cards */
    .no-more-cards {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      text-align: center;
      padding: 3rem 2rem;
      background: #fff;
      border-radius: 28px;
      box-shadow: 0 12px 32px rgba(0, 0, 0, 0.08);
      animation: fadeInUp 0.4s ease;
    }

    .no-more-cards i {
      font-size: 4rem;
      color: #ddd;
      margin-bottom: 1rem;
    }

    .no-more-cards h3 {
      font-size: 1.5rem;
      font-weight: 700;
      color: #444;
      margin-bottom: 0.5rem;
    }

    .no-more-cards p {
      color: #888;
      font-size: 1rem;
      margin-bottom: 1.5rem;
    }

    .btn-refresh {
      display: inline-flex;
      align-items: center;
      gap: 0.6rem;
      padding: 0.85rem 2rem;
      background: linear-gradient(135deg, #fd5068, #fc7c45);
      color: #fff;
      border: none;
      border-radius: 60px;
      font-family: inherit;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
    }

    .btn-refresh:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 24px rgba(253, 80, 104, 0.35);
    }

    /* Match Popup */
    .match-overlay {
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.85);
      backdrop-filter: blur(8px);
      z-index: 1000;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 1rem;
      animation: fadeIn 0.3s ease;
    }

    .match-popup {
      background: #fff;
      border-radius: 32px;
      padding: 2.5rem 2rem 2rem;
      max-width: 420px;
      width: 100%;
      text-align: center;
      animation: popIn 0.5s cubic-bezier(0.34, 1.56, 0.64, 1);
      box-shadow: 0 30px 60px rgba(0, 0, 0, 0.3);
    }

    @keyframes popIn {
      from {
        opacity: 0;
        transform: scale(0.7);
      }
      to {
        opacity: 1;
        transform: scale(1);
      }
    }

    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
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

    .match-popup h2 {
      font-size: 2.2rem;
      font-weight: 800;
      background: linear-gradient(135deg, #fd5068, #fc7c45);
      -webkit-background-clip: text;
      background-clip: text;
      color: transparent;
      margin-bottom: 0.5rem;
    }

    .match-popup > p {
      color: #666;
      font-size: 1rem;
      margin-bottom: 1.5rem;
    }

    .match-avatars {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0;
      margin-bottom: 2rem;
      position: relative;
    }

    .match-avatar {
      width: 100px;
      height: 100px;
      border-radius: 50%;
      object-fit: cover;
      border: 4px solid #fff;
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
    }

    .match-avatar:last-of-type {
      margin-left: -20px;
    }

    .match-heart {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      width: 44px;
      height: 44px;
      background: linear-gradient(135deg, #fd5068, #fc7c45);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.3rem;
      color: #fff;
      border: 4px solid #fff;
      z-index: 1;
      animation: heartbeat 0.6s ease infinite;
    }

    @keyframes heartbeat {
      0%, 100% { transform: translate(-50%, -50%) scale(1); }
      50% { transform: translate(-50%, -50%) scale(1.15); }
    }

    .match-actions {
      display: flex;
      flex-direction: column;
      gap: 0.8rem;
    }

    .btn-chat, .btn-continue {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 0.6rem;
      padding: 0.9rem 1.5rem;
      border-radius: 60px;
      font-family: inherit;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
      border: none;
      text-decoration: none;
    }

    .btn-chat {
      background: linear-gradient(135deg, #fd5068, #fc7c45);
      color: #fff;
    }

    .btn-chat:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 24px rgba(253, 80, 104, 0.4);
    }

    .btn-continue {
      background: transparent;
      color: #888;
      border: 2px solid #e0e0e0;
    }

    .btn-continue:hover {
      border-color: #fd5068;
      color: #fd5068;
      background: #fff5f6;
    }

    /* Loading Skeleton */
    .card-skeleton {
      border-radius: 28px;
      background: linear-gradient(135deg, #f5f5f5, #eee);
      height: 520px;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.8rem;
      color: #aaa;
      font-size: 1rem;
      animation: pulse 1.5s ease infinite;
    }

    @keyframes pulse {
      0%, 100% { opacity: 1; }
      50% { opacity: 0.6; }
    }

    .skel-spinner {
      width: 32px;
      height: 32px;
      border: 3px solid #e0e0e0;
      border-top-color: #fd5068;
      border-radius: 50%;
      animation: spin 0.8s linear infinite;
    }

    @keyframes spin {
      to { transform: rotate(360deg); }
    }

    /* Mobile Header & Bottom Nav */
    .mobile-header {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      background: rgba(255, 255, 255, 0.98);
      backdrop-filter: blur(10px);
      padding: 1rem 1.2rem;
      z-index: 90;
      justify-content: space-between;
      align-items: center;
      box-shadow: 0 2px 12px rgba(0, 0, 0, 0.05);
    }

    .mobile-menu-btn {
      background: none;
      border: none;
      font-size: 1.5rem;
      color: #fd5068;
      cursor: pointer;
    }

    .mobile-notif-btn img {
      width: 38px;
      height: 38px;
      border-radius: 50%;
      object-fit: cover;
      border: 2px solid #fd5068;
    }

    .mobile-bottom-nav {
      display: none;
      position: fixed;
      bottom: 0;
      left: 0;
      right: 0;
      background: rgba(255, 255, 255, 0.98);
      backdrop-filter: blur(10px);
      padding: 0.75rem 1rem;
      justify-content: space-around;
      z-index: 90;
      border-top: 1px solid #f0f0f0;
    }

    .mobile-bottom-nav a {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 0.2rem;
      text-decoration: none;
      color: #aaa;
      font-size: 0.7rem;
      transition: color 0.2s;
    }

    .mobile-bottom-nav a i {
      font-size: 1.3rem;
    }

    .mobile-bottom-nav a.active {
      color: #fd5068;
    }

    .sidebar-overlay {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(0, 0, 0, 0.45);
      z-index: 99;
      backdrop-filter: blur(4px);
    }

    /* Responsive */
    @media (max-width: 768px) {
      .sidebar {
        transform: translateX(-100%);
        width: 280px;
      }

      .sidebar.open {
        transform: translateX(0);
      }

      .sidebar-overlay.active {
        display: block;
      }

      .main-content {
        margin-left: 0;
        padding: 5rem 1rem 5rem;
      }

      .mobile-header {
        display: flex;
      }

      .mobile-bottom-nav {
        display: flex;
      }

      .keyboard-hint {
        display: none;
      }

      .card-photo {
        height: 380px;
      }

      .card-info h2 {
        font-size: 1.6rem;
      }
    }
  </style>
</head>
<body>

<script>
// PHP to JS
const CURRENT_USER = {
  id: <?= intval($_SESSION["user_id"]) ?>,
  nom: "<?= htmlspecialchars($_SESSION["user_nom"] ?? "", ENT_QUOTES) ?>",
  photo: "<?= htmlspecialchars($_SESSION["user_photo"] ?? "/tinder/assets/images/default-avatar.jpg", ENT_QUOTES) ?>"
};
const BASE_URL = '/tinder';
</script>

<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<div class="app-layout">
  <!-- Sidebar -->
  <aside class="sidebar" id="mainSidebar">
    <div class="logo">
      <span>👥</span>
      <span>MatchFace Campus</span>
    </div>

    <div class="user-profile-mini">
      <a href="profile.php">
        <img src="<?= htmlspecialchars($_SESSION["user_photo"] ?? "/tinder/assets/images/default-avatar.jpg", ENT_QUOTES) ?>" alt="Profile" id="sidebarAvatar">
        <div>
          <div class="user-name" id="sidebarName"><?= htmlspecialchars($_SESSION["user_nom"] ?? "User", ENT_QUOTES) ?></div>
          <div style="font-size:0.75rem;color:#aaa;">View profile →</div>
        </div>
      </a>
    </div>

    <nav class="sidebar-nav">
      <a href="dashboard.php" class="active">
        <i class="fas fa-fire"></i> Discover
      </a>
      <a href="matches.php">
        <i class="fas fa-heart"></i> Matches
      </a>
      <a href="chat.php">
        <i class="fas fa-comment-dots"></i> Messages
        <span class="badge" id="unreadBadge" style="display:none;margin-left:auto;">0</span>
      </a>
      <a href="profile.php">
        <i class="fas fa-user"></i> My Profile
      </a>
    </nav>

    <button class="logout-btn" onclick="logout()">
      <i class="fas fa-sign-out-alt"></i> Logout
    </button>
  </aside>

  <!-- Main Content -->
  <main class="main-content">
    <div class="cards-stack" id="cardsStack">
      <div class="card-skeleton" id="loadingSkeleton">
        <div class="skel-spinner"></div>
        <span>Loading profiles...</span>
      </div>
    </div>

    <div class="action-buttons" id="actionButtons">
      <button class="action-btn dislike" id="dislikeBtn" onclick="swipe('dislike')" title="Pass (←)">
        <i class="fas fa-times"></i>
      </button>
      <button class="action-btn superlike" id="superlikeBtn" onclick="swipe('superlike')" title="Super Like (↑)">
        <i class="fas fa-star"></i>
      </button>
      <button class="action-btn like" id="likeBtn" onclick="swipe('like')" title="Like (→)">
        <i class="fas fa-heart"></i>
      </button>
    </div>

    <div class="keyboard-hint">
      <span><kbd>←</kbd> Pass</span>
      <span><kbd>↑</kbd> Super Like</span>
      <span><kbd>→</kbd> Like</span>
    </div>
  </main>
</div>

<!-- Mobile Bottom Nav -->
<nav class="mobile-bottom-nav">
  <a href="dashboard.php" class="active">
    <i class="fas fa-fire"></i>
    <span>Discover</span>
  </a>
  <a href="matches.php">
    <i class="fas fa-heart"></i>
    <span>Matches</span>
  </a>
  <a href="chat.php">
    <i class="fas fa-comment-dots"></i>
    <span>Messages</span>
    <span class="nav-dot" id="mobileUnreadDot" style="display:none;"></span>
  </a>
  <a href="profile.php">
    <i class="fas fa-user"></i>
    <span>Profile</span>
  </a>
</nav>

<!-- Mobile Header -->
<header class="mobile-header">
  <button class="mobile-menu-btn" id="mobileMenuBtn" onclick="toggleSidebar()">
    <i class="fas fa-bars"></i>
  </button>
  <span style="font-weight:700;color:#fd5068;">MatchFace</span>
  <a href="profile.php" class="mobile-notif-btn">
    <img src="<?= htmlspecialchars($_SESSION["user_photo"] ?? "/tinder/assets/images/default-avatar.jpg", ENT_QUOTES) ?>" alt="Profile">
  </a>
</header>

<script>

let profiles = [];
let currentIndex = 0;
let isDragging = false;
let startX = 0, startY = 0, currentX = 0;
let activeCard = null;

document.addEventListener('DOMContentLoaded', () => {
  loadPotentialMatches();
  loadUnreadCount();
});

async function loadPotentialMatches() {
  showSkeleton(true);
  try {
    const resp = await fetch(BASE_URL + '/api/get-potential-matches.php');
    const data = await resp.json();
    if (data.success) {
      profiles = data.matches || [];
      currentIndex = 0;
      renderCards();
    } else {
      showNoMore(data.error || 'Error loading profiles.');
    }
  } catch (err) {
    console.error('loadPotentialMatches:', err);
    showNoMore('Unable to load profiles.');
  } finally {
    showSkeleton(false);
  }
}

function renderCards() {
  const stack = document.getElementById('cardsStack');
  stack.querySelectorAll('.profile-card').forEach(c => c.remove());

  if (profiles.length === 0 || currentIndex >= profiles.length) {
    showNoMore();
    setActionButtonsDisabled(true);
    return;
  }

  setActionButtonsDisabled(false);

  const visible = Math.min(3, profiles.length - currentIndex);
  for (let i = visible - 1; i >= 0; i--) {
    const profile = profiles[currentIndex + i];
    const card = buildCard(profile);

    if (i === 0) {
      card.classList.add('active');
      attachDragListeners(card, profile);
    } else if (i === 1) {
      card.classList.add('card-2');
    } else if (i === 2) {
      card.classList.add('card-1');
    }

    stack.appendChild(card);
  }
}

function buildCard(profile) {
  const age = profile.date_naissance ? calcAge(profile.date_naissance) : null;
  const photo = profile.photo_url || (BASE_URL + '/assets/images/default-avatar.jpg');
  const tags = Array.isArray(profile.liste_interets)
    ? profile.liste_interets
    : (typeof profile.liste_interets === 'string'
        ? profile.liste_interets.split(',').map(s => s.trim()).filter(Boolean)
        : []);

  const card = document.createElement('div');
  card.className = 'profile-card';
  card.dataset.id = profile.id;

  card.innerHTML = `
    <div class="card-photo">
      <img src="${escapeHtml(photo)}" alt="${escapeHtml(profile.nom)}"
           onerror="this.src='${BASE_URL}/assets/images/default-avatar.jpg'">
      <div class="card-overlay"></div>
      <div class="card-info">
        <h2>${escapeHtml(profile.nom)}${age ? '<span style="font-weight:400;font-size:1.3rem;">, ' + age + '</span>' : ''}</h2>
        ${profile.ville ? `<p><i class="fas fa-map-marker-alt"></i> ${escapeHtml(profile.ville)}</p>` : ''}
      </div>
      <div class="like-label">LIKE 💚</div>
      <div class="nope-label">NOPE ✕</div>
    </div>
    <div class="card-details">
      ${tags.length > 0 ? `
        <div class="interets-section">
          <h3>🎯 Common Interests (${profile.interets_communs || 0})</h3>
          <div class="interets-tags">
            ${tags.slice(0, 6).map(t => `<span class="tag">${escapeHtml(t.trim())}</span>`).join('')}
            ${tags.length > 6 ? `<span class="tag">+${tags.length - 6}</span>` : ''}
          </div>
        </div>
      ` : ''}
      ${profile.bio ? `
        <div class="bio-section">
          <h3>📖 About</h3>
          <p>${escapeHtml(profile.bio)}</p>
        </div>
      ` : ''}
    </div>
  `;

  return card;
}

function attachDragListeners(card) {
  card.addEventListener('mousedown', onDragStart);
  card.addEventListener('touchstart', onDragStart, { passive: true });
}

function onDragStart(e) {
  if (e.target.closest('a, button')) return;
  isDragging = true;
  activeCard = e.currentTarget;

  const pt = e.touches ? e.touches[0] : e;
  startX = pt.clientX;
  startY = pt.clientY;

  activeCard.style.transition = 'none';

  document.addEventListener('mousemove', onDragMove);
  document.addEventListener('mouseup', onDragEnd);
  document.addEventListener('touchmove', onDragMove, { passive: false });
  document.addEventListener('touchend', onDragEnd);
}

function onDragMove(e) {
  if (!isDragging || !activeCard) return;
  if (e.cancelable) e.preventDefault();

  const pt = e.touches ? e.touches[0] : e;
  currentX = pt.clientX - startX;
  const currentY = pt.clientY - startY;
  const rotate = currentX * 0.08;

  activeCard.style.transform = `translateX(${currentX}px) translateY(${currentY * 0.3}px) rotate(${rotate}deg)`;

  const likeLabel = activeCard.querySelector('.like-label');
  const nopeLabel = activeCard.querySelector('.nope-label');
  const threshold = 60;

  if (currentX > threshold) {
    likeLabel.style.opacity = Math.min((currentX - threshold) / 60, 1);
    nopeLabel.style.opacity = 0;
  } else if (currentX < -threshold) {
    nopeLabel.style.opacity = Math.min((-currentX - threshold) / 60, 1);
    likeLabel.style.opacity = 0;
  } else {
    likeLabel.style.opacity = 0;
    nopeLabel.style.opacity = 0;
  }
}

function onDragEnd() {
  if (!isDragging || !activeCard) return;
  isDragging = false;

  document.removeEventListener('mousemove', onDragMove);
  document.removeEventListener('mouseup', onDragEnd);
  document.removeEventListener('touchmove', onDragMove);
  document.removeEventListener('touchend', onDragEnd);

  const threshold = 100;
  if (currentX > threshold) {
    finishSwipe('like');
  } else if (currentX < -threshold) {
    finishSwipe('dislike');
  } else {
    activeCard.style.transition = 'transform 0.4s cubic-bezier(0.34, 1.56, 0.64, 1)';
    activeCard.style.transform = '';
    activeCard.querySelector('.like-label').style.opacity = 0;
    activeCard.querySelector('.nope-label').style.opacity = 0;
    activeCard = null;
  }
  currentX = 0;
}

async function swipe(action) {
  if (currentIndex >= profiles.length) return;
  const card = document.querySelector('.profile-card.active');
  if (!card) return;
  finishSwipe(action, card);
}

async function finishSwipe(action, card) {
  const topCard = card || document.querySelector('.profile-card.active');
  if (!topCard) return;

  setActionButtonsDisabled(true);
  const profile = profiles[currentIndex];

  topCard.style.transition = 'transform 0.5s cubic-bezier(0.2, 0.9, 0.4, 1.2), opacity 0.4s ease';
  if (action === 'like' || action === 'superlike') {
    topCard.classList.add(action === 'superlike' ? 'go-up' : 'go-right');
    topCard.querySelector('.like-label').style.opacity = 1;
  } else {
    topCard.classList.add('go-left');
    topCard.querySelector('.nope-label').style.opacity = 1;
  }

  try {
    const endpoint = action === 'dislike'
      ? BASE_URL + '/api/dislike-user.php'
      : BASE_URL + '/api/like-user.php';

    const resp = await fetch(endpoint, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ to_user_id: profile.id, type: action }),
    });
    const data = await resp.json();

    if (data.match) {
      setTimeout(() => showMatchPopup(profile, data), 500);
    }
  } catch (err) {
    console.error('swipe API error:', err);
  }

  setTimeout(() => {
    topCard.remove();
    currentIndex++;
    renderCards();
  }, 450);
}

function showMatchPopup(profile, data) {
  const myPhoto = CURRENT_USER.photo;
  const theirPhoto = profile.photo_url || (BASE_URL + '/assets/images/default-avatar.jpg');

  const overlay = document.createElement('div');
  overlay.className = 'match-overlay';
  overlay.id = 'matchOverlay';

  overlay.innerHTML = `
    <div class="match-popup">
      <h2>🎉 It's a Match!</h2>
      <p>You and <strong>${escapeHtml(profile.nom)}</strong> liked each other</p>
      <div class="match-avatars">
        <img src="${escapeHtml(myPhoto)}" alt="You" class="match-avatar"
             onerror="this.src='${BASE_URL}/assets/images/default-avatar.jpg'">
        <div class="match-heart">💘</div>
        <img src="${escapeHtml(theirPhoto)}" alt="${escapeHtml(profile.nom)}" class="match-avatar"
             onerror="this.src='${BASE_URL}/assets/images/default-avatar.jpg'">
      </div>
      <div class="match-actions">
        <button class="btn-chat" onclick="goToChat()">
          <i class="fas fa-comment-dots"></i> Send a message
        </button>
        <button class="btn-continue" onclick="closeMatchPopup()">
          <i class="fas fa-fire"></i> Keep swiping
        </button>
      </div>
    </div>
  `;

  document.body.appendChild(overlay);
  setTimeout(() => closeMatchPopup(), 8000);
}

function closeMatchPopup() {
  const el = document.getElementById('matchOverlay');
  if (el) el.remove();
}

function goToChat() {
  closeMatchPopup();
  window.location.href = 'chat.php';
}

/* ============================================================
   UNREAD COUNT
   ============================================================ */
async function loadUnreadCount() {
  try {
    const resp = await fetch(BASE_URL + '/api/get-conversations.php');
    const data = await resp.json();
    if (data.success && Array.isArray(data.conversations)) {
      const total = data.conversations.reduce((sum, c) => sum + (parseInt(c.unread_count) || 0), 0);
      const badge = document.getElementById('unreadBadge');
      const dot = document.getElementById('mobileUnreadDot');
      if (total > 0) {
        if (badge) { badge.textContent = total > 99 ? '99+' : total; badge.style.display = 'inline-flex'; }
        if (dot) { dot.style.display = 'block'; }
      }
    }
  } catch (_) {}
}


function toggleSidebar() {
  const sidebar = document.getElementById('mainSidebar');
  const overlay = document.getElementById('sidebarOverlay');
  sidebar.classList.toggle('open');
  overlay.classList.toggle('active');
}

function closeSidebar() {
  document.getElementById('mainSidebar').classList.remove('open');
  document.getElementById('sidebarOverlay').classList.remove('active');
}


async function logout() {
  try {
    await fetch(BASE_URL + '/api/logout.php');
  } catch (_) {}
  window.location.href = '../index.php';
}


document.addEventListener('keydown', e => {
  if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
  if (e.key === 'ArrowLeft') swipe('dislike');
  if (e.key === 'ArrowRight') swipe('like');
  if (e.key === 'ArrowUp') swipe('superlike');
});


function showSkeleton(show) {
  const el = document.getElementById('loadingSkeleton');
  if (el) el.style.display = show ? 'flex' : 'none';
}

function showNoMore(msg) {
  const stack = document.getElementById('cardsStack');
  stack.querySelectorAll('.profile-card').forEach(c => c.remove());
  stack.innerHTML = `
    <div class="no-more-cards">
      <i class="fas fa-smile-wink"></i>
      <h3>No more profiles</h3>
      <p>${escapeHtml(msg || 'Come back later or adjust your preferences.')}</p>
      <button class="btn-refresh" onclick="loadPotentialMatches()">
        <i class="fas fa-redo"></i> Refresh
      </button>
    </div>
  `;
  setActionButtonsDisabled(true);
}

function setActionButtonsDisabled(disabled) {
  ['dislikeBtn', 'superlikeBtn', 'likeBtn'].forEach(id => {
    const btn = document.getElementById(id);
    if (btn) btn.disabled = disabled;
  });
}

function calcAge(dateStr) {
  const birth = new Date(dateStr);
  const today = new Date();
  let age = today.getFullYear() - birth.getFullYear();
  const m = today.getMonth() - birth.getMonth();
  if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) age--;
  return age;
}

function escapeHtml(str) {
  if (!str) return '';
  const d = document.createElement('div');
  d.textContent = String(str);
  return d.innerHTML;
}
</script>

</body>
</html>
