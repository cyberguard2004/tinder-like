<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
  <title>My Matches - MatchFace Campus</title>
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
      min-height: 100vh;
      padding: 2rem 2rem;
    }

    /* Page Header */
    .matches-header {
      display: flex;
      align-items: center;
      gap: 1rem;
      margin-bottom: 2rem;
      animation: fadeInDown 0.4s ease;
    }

    @keyframes fadeInDown {
      from {
        opacity: 0;
        transform: translateY(-20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .matches-header h1 {
      font-size: 2rem;
      font-weight: 800;
      color: #1a1a2e;
      margin: 0;
    }

    .matches-header h1 i {
      color: #fd5068;
      margin-right: 0.6rem;
    }

    .badge {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-width: 36px;
      height: 36px;
      padding: 0 10px;
      background: linear-gradient(135deg, #fd5068, #fc7c45);
      color: #fff;
      border-radius: 40px;
      font-size: 0.9rem;
      font-weight: 700;
    }

    /* Section Headings */
    .section-heading {
      display: flex;
      align-items: center;
      gap: 0.8rem;
      font-size: 1.1rem;
      font-weight: 700;
      color: #444;
      margin: 0 0 1.2rem 0;
    }

    .section-heading i {
      color: #fd5068;
      font-size: 1rem;
    }

    .count-pill {
      font-size: 0.75rem;
      font-weight: 600;
      color: #fd5068;
      background: #fff5f6;
      border: 1px solid #ffd6dc;
      border-radius: 20px;
      padding: 0.2rem 0.7rem;
    }

    /* New Matches Row */
    .new-matches-section {
      margin-bottom: 2.5rem;
    }

    .new-matches-row {
      display: flex;
      gap: 1.2rem;
      overflow-x: auto;
      padding-bottom: 0.8rem;
    }

    .new-matches-row::-webkit-scrollbar {
      height: 5px;
    }

    .new-matches-row::-webkit-scrollbar-track {
      background: transparent;
    }

    .new-matches-row::-webkit-scrollbar-thumb {
      background: #ffd6dc;
      border-radius: 10px;
    }

    .new-match-bubble {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 0.5rem;
      flex-shrink: 0;
      cursor: pointer;
      text-decoration: none;
      transition: transform 0.2s ease;
    }

    .new-match-bubble:hover {
      transform: translateY(-4px);
    }

    .new-match-ring {
      width: 80px;
      height: 80px;
      border-radius: 50%;
      padding: 3px;
      background: linear-gradient(135deg, #fd5068, #fc7c45);
      transition: all 0.2s;
      position: relative;
    }

    .new-match-ring img {
      width: 100%;
      height: 100%;
      border-radius: 50%;
      object-fit: cover;
      border: 3px solid #fff;
    }

    .new-match-dot {
      position: absolute;
      bottom: 4px;
      right: 4px;
      width: 16px;
      height: 16px;
      background: #27ae60;
      border-radius: 50%;
      border: 2px solid #fff;
    }

    .new-match-name {
      font-size: 0.85rem;
      font-weight: 600;
      color: #555;
      max-width: 85px;
      text-align: center;
      overflow: hidden;
      text-overflow: ellipsis;
      white-space: nowrap;
    }

    .no-new-matches {
      font-size: 0.9rem;
      color: #bbb;
      font-style: italic;
      padding: 0.8rem 0;
    }

    /* Matches Grid */
    .matches-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
      gap: 1.5rem;
      position: relative;
      min-height: 300px;
    }

    /* Match Card */
    .match-card {
      background: #fff;
      border-radius: 20px;
      overflow: hidden;
      box-shadow: 0 6px 20px rgba(0, 0, 0, 0.08);
      cursor: pointer;
      text-decoration: none;
      display: flex;
      flex-direction: column;
      transition: all 0.3s cubic-bezier(0.2, 0.9, 0.4, 1.1);
      position: relative;
    }

    .match-card:hover {
      transform: translateY(-6px);
      box-shadow: 0 16px 32px rgba(253, 80, 104, 0.2);
    }

    .match-photo {
      position: relative;
      height: 220px;
      overflow: hidden;
      background: linear-gradient(135deg, #f0f0f0, #e8e8e8);
    }

    .match-photo img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      transition: transform 0.4s ease;
    }

    .match-card:hover .match-photo img {
      transform: scale(1.08);
    }

    .match-photo-overlay {
      position: absolute;
      inset: 0;
      background: linear-gradient(to top, rgba(0, 0, 0, 0.6) 0%, transparent 50%);
      pointer-events: none;
    }

    .match-unread-badge {
      position: absolute;
      top: 0.8rem;
      right: 0.8rem;
      min-width: 24px;
      height: 24px;
      padding: 0 7px;
      background: linear-gradient(135deg, #fd5068, #fc7c45);
      color: #fff;
      border-radius: 30px;
      font-size: 0.75rem;
      font-weight: 700;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 2px 10px rgba(253, 80, 104, 0.4);
      z-index: 2;
      animation: pulse 1.5s ease infinite;
    }

    @keyframes pulse {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.08); }
    }

    .match-new-tag {
      position: absolute;
      top: 0.8rem;
      left: 0.8rem;
      padding: 0.25rem 0.7rem;
      background: linear-gradient(135deg, #fd5068, #fc7c45);
      color: #fff;
      border-radius: 30px;
      font-size: 0.7rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      z-index: 2;
    }

    .match-info {
      padding: 0.9rem 1rem 0.8rem;
    }

    .match-info h3 {
      font-size: 1.05rem;
      font-weight: 700;
      color: #1a1a2e;
      margin: 0 0 0.25rem;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .match-age-city {
      font-size: 0.8rem;
      color: #aaa;
      margin: 0 0 0.6rem;
      display: flex;
      align-items: center;
      gap: 0.4rem;
    }

    .match-age-city i {
      color: #fd5068;
      font-size: 0.7rem;
    }

    .match-tags {
      display: flex;
      flex-wrap: wrap;
      gap: 0.35rem;
      margin-bottom: 0.5rem;
    }

    .match-tag {
      display: inline-block;
      padding: 0.2rem 0.65rem;
      background: #fff5f6;
      color: #fd5068;
      border: 1px solid #ffd6dc;
      border-radius: 30px;
      font-size: 0.7rem;
      font-weight: 500;
      transition: all 0.2s;
    }

    .match-tag:hover {
      background: #fd5068;
      color: #fff;
      transform: translateY(-1px);
    }

    .match-chat-btn {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
      padding: 0.7rem;
      background: linear-gradient(135deg, #fd5068, #fc7c45);
      color: #fff;
      font-family: inherit;
      font-size: 0.85rem;
      font-weight: 600;
      border: none;
      width: 100%;
      cursor: pointer;
      transition: all 0.2s;
      margin-top: 0.25rem;
    }

    .match-chat-btn:hover {
      opacity: 0.92;
      letter-spacing: 0.3px;
    }

    /* Loading State */
    .loading-overlay {
      position: absolute;
      inset: 0;
      display: flex;
      align-items: center;
      justify-content: center;
      background: rgba(248, 249, 252, 0.9);
      border-radius: 20px;
      z-index: 10;
    }

    .spinner {
      width: 44px;
      height: 44px;
      border: 4px solid #ffd6dc;
      border-top-color: #fd5068;
      border-radius: 50%;
      animation: spin 0.8s linear infinite;
    }

    @keyframes spin {
      to { transform: rotate(360deg); }
    }

    /* Empty State */
    .matches-empty {
      grid-column: 1 / -1;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 3.5rem 2rem;
      text-align: center;
      background: #fff;
      border-radius: 28px;
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

    .matches-empty-icon {
      width: 90px;
      height: 90px;
      background: linear-gradient(135deg, #fff0f2, #ffe0e5);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 2.5rem;
      margin-bottom: 1rem;
    }

    .matches-empty h3 {
      font-size: 1.3rem;
      font-weight: 700;
      color: #444;
      margin: 0 0 0.5rem;
    }

    .matches-empty p {
      color: #aaa;
      font-size: 0.95rem;
      margin: 0 0 1.5rem;
      max-width: 280px;
    }

    .matches-empty a {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.85rem 1.8rem;
      background: linear-gradient(135deg, #fd5068, #fc7c45);
      color: #fff;
      border-radius: 60px;
      font-size: 0.95rem;
      font-weight: 600;
      text-decoration: none;
      transition: all 0.2s;
    }

    .matches-empty a:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 24px rgba(253, 80, 104, 0.35);
    }

    /* Error State */
    .matches-error {
      grid-column: 1 / -1;
      text-align: center;
      padding: 3rem;
      background: #fff;
      border-radius: 28px;
      color: #e53e3e;
    }

    .matches-error i {
      font-size: 2.5rem;
      margin-bottom: 0.75rem;
    }

    .matches-error button {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      margin-top: 1rem;
      padding: 0.7rem 1.5rem;
      background: transparent;
      color: #fd5068;
      border: 2px solid #fd5068;
      border-radius: 60px;
      font-family: inherit;
      font-size: 0.9rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s;
    }

    .matches-error button:hover {
      background: #fd5068;
      color: #fff;
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
      padding: 0.7rem 1rem;
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
      font-size: 1.4rem;
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

      .matches-header h1 {
        font-size: 1.6rem;
      }

      .matches-grid {
        grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
        gap: 1rem;
      }

      .match-photo {
        height: 180px;
      }
    }
  </style>
</head>
<body>

<script>
const CURRENT_USER = {
  id: <?= intval($_SESSION['user_id']) ?>,
  nom: "<?= htmlspecialchars($_SESSION['user_nom'] ?? '', ENT_QUOTES) ?>",
  photo: "<?= htmlspecialchars($_SESSION['user_photo'] ?? '/tinder/assets/images/default-avatar.jpg', ENT_QUOTES) ?>"
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
        <img src="<?= htmlspecialchars($_SESSION['user_photo'] ?? '/tinder/assets/images/default-avatar.jpg', ENT_QUOTES) ?>" alt="Profile" id="sidebarAvatar">
        <div>
          <div class="user-name"><?= htmlspecialchars($_SESSION['user_nom'] ?? 'User', ENT_QUOTES) ?></div>
          <div style="font-size:0.75rem;color:#aaa;">View profile →</div>
        </div>
      </a>
    </div>

    <nav class="sidebar-nav">
      <a href="dashboard.php">
        <i class="fas fa-fire"></i> Discover
      </a>
      <a href="matches.php" class="active">
        <i class="fas fa-heart"></i> My Matches
      </a>
      <a href="chat.php">
        <i class="fas fa-comment-dots"></i> Messages
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
    <div class="matches-header">
      <h1><i class="fas fa-heart"></i> My Matches</h1>
      <span class="badge" id="matchesCount">0</span>
    </div>

    <!-- New Matches Section -->
    <section class="new-matches-section" id="newMatchesSection">
      <h3 class="section-heading">
        <i class="fas fa-star"></i>
        New Matches
        <span class="count-pill" id="newMatchesCount">0</span>
      </h3>
      <div class="new-matches-row" id="newMatchesRow"></div>
    </section>

    <!-- All Matches Grid -->
    <section class="all-matches-section">
      <h3 class="section-heading">
        <i class="fas fa-th-large"></i>
        All Matches
      </h3>
      <div class="matches-grid" id="matchesGrid">
        <div class="loading-overlay">
          <div class="spinner"></div>
        </div>
      </div>
    </section>
  </main>
</div>

<!-- Mobile Bottom Nav -->
<nav class="mobile-bottom-nav">
  <a href="dashboard.php">
    <i class="fas fa-fire"></i>
    <span>Discover</span>
  </a>
  <a href="matches.php" class="active">
    <i class="fas fa-heart"></i>
    <span>Matches</span>
  </a>
  <a href="chat.php">
    <i class="fas fa-comment-dots"></i>
    <span>Messages</span>
  </a>
  <a href="profile.php">
    <i class="fas fa-user"></i>
    <span>Profile</span>
  </a>
</nav>

<!-- Mobile Header -->
<header class="mobile-header">
  <button class="mobile-menu-btn" onclick="toggleSidebar()">
    <i class="fas fa-bars"></i>
  </button>
  <span style="font-weight:700;color:#fd5068;">👥 MatchFace</span>
  <a href="profile.php" class="mobile-notif-btn">
    <img src="<?= htmlspecialchars($_SESSION['user_photo'] ?? '/tinder/assets/images/default-avatar.jpg', ENT_QUOTES) ?>" alt="Profile">
  </a>
</header>

<script>
/* ============================================================
   INIT
   ============================================================ */
document.addEventListener('DOMContentLoaded', () => {
  loadMatches();
});

/* ============================================================
   LOAD MATCHES
   ============================================================ */
async function loadMatches() {
  try {
    const resp = await fetch(BASE_URL + '/api/get-matches.php');
    if (!resp.ok) throw new Error('HTTP ' + resp.status);
    const data = await resp.json();

    if (data.success) {
      const matches = data.matches || [];
      renderAllMatches(matches);
      renderNewMatches(matches.filter(m => m.is_new || !m.last_message));
      updateCount(matches.length);
    } else {
      showError(data.error || 'Unable to load matches.');
    }
  } catch (err) {
    console.error('loadMatches:', err);
    showError('Network error. Please check your connection.');
  }
}

/* ============================================================
   UPDATE TOTAL COUNT
   ============================================================ */
function updateCount(n) {
  const badge = document.getElementById('matchesCount');
  if (badge) badge.textContent = n > 99 ? '99+' : n;
}

/* ============================================================
   RENDER NEW MATCHES ROW
   ============================================================ */
function renderNewMatches(newMatches) {
  const row = document.getElementById('newMatchesRow');
  const count = document.getElementById('newMatchesCount');

  if (count) count.textContent = newMatches.length;

  if (!newMatches || newMatches.length === 0) {
    row.innerHTML = '<span class="no-new-matches">No new matches yet — keep swiping! 🔥</span>';
    return;
  }

  row.innerHTML = newMatches.map(m => {
    const photo = m.photo_url || (BASE_URL + '/assets/images/default-avatar.jpg');
    return `
      <a class="new-match-bubble" href="chat.php?match_id=${encodeURIComponent(m.match_id || m.id)}"
         title="Send a message to ${escapeHtml(m.nom)}">
        <div class="new-match-ring">
          <img src="${escapeHtml(photo)}"
               alt="${escapeHtml(m.nom)}"
               onerror="this.src='${BASE_URL}/assets/images/default-avatar.jpg'">
          <span class="new-match-dot"></span>
        </div>
        <span class="new-match-name">${escapeHtml(m.nom)}</span>
      </a>
    `;
  }).join('');
}

/* ============================================================
   RENDER ALL MATCHES GRID
   ============================================================ */
function renderAllMatches(matches) {
  const grid = document.getElementById('matchesGrid');

  if (!matches || matches.length === 0) {
    grid.innerHTML = `
      <div class="matches-empty">
        <div class="matches-empty-icon">💔</div>
        <h3>No matches yet</h3>
        <p>Swipe on profiles to find people who share your interests!</p>
        <a href="dashboard.php"><i class="fas fa-fire"></i> Discover Profiles</a>
      </div>
    `;
    return;
  }

  grid.innerHTML = matches.map(m => {
    const photo = m.photo_url || (BASE_URL + '/assets/images/default-avatar.jpg');
    const matchId = m.match_id || m.id;
    const age = m.date_naissance ? calcAge(m.date_naissance) : null;
    const unread = parseInt(m.unread_count) || 0;
    const isNew = m.is_new || !m.last_message;
    const tags = Array.isArray(m.interets) ? m.interets
               : (m.liste_interets ? m.liste_interets.split(',').filter(Boolean) : []);

    return `
      <a class="match-card" href="chat.php?match_id=${encodeURIComponent(matchId)}"
         title="Send a message to ${escapeHtml(m.nom)}">
        <div class="match-photo">
          <img src="${escapeHtml(photo)}"
               alt="${escapeHtml(m.nom)}"
               loading="lazy"
               onerror="this.src='${BASE_URL}/assets/images/default-avatar.jpg'">
          <div class="match-photo-overlay"></div>
          ${isNew ? `<span class="match-new-tag">NEW</span>` : ''}
          ${unread > 0 ? `<span class="match-unread-badge">${unread > 99 ? '99+' : unread}</span>` : ''}
        </div>
        <div class="match-info">
          <h3>${escapeHtml(m.nom)}${age ? ', ' + age : ''}</h3>
          ${m.ville ? `
            <p class="match-age-city">
              <i class="fas fa-map-marker-alt"></i>
              ${escapeHtml(m.ville)}
            </p>
          ` : ''}
          ${tags.length > 0 ? `
            <div class="match-tags">
              ${tags.slice(0, 3).map(t => `<span class="match-tag">${escapeHtml(t.trim())}</span>`).join('')}
              ${tags.length > 3 ? `<span class="match-tag">+${tags.length - 3}</span>` : ''}
            </div>
          ` : ''}
        </div>
        <button class="match-chat-btn" onclick="event.preventDefault(); window.location.href='chat.php?match_id=${encodeURIComponent(matchId)}'">
          <i class="fas fa-comment-dots"></i>
          ${unread > 0 ? `Message (${unread})` : 'Send Message'}
        </button>
      </a>
    `;
  }).join('');
}

/* ============================================================
   SHOW ERROR
   ============================================================ */
function showError(msg) {
  document.getElementById('matchesGrid').innerHTML = `
    <div class="matches-error">
      <i class="fas fa-exclamation-circle"></i>
      <p>${escapeHtml(msg)}</p>
      <button onclick="loadMatches()">
        <i class="fas fa-redo"></i> Try Again
      </button>
    </div>
  `;
}

/* ============================================================
   SIDEBAR FUNCTIONS
   ============================================================ */
function toggleSidebar() {
  const sidebar = document.getElementById('mainSidebar');
  const overlay = document.getElementById('sidebarOverlay');
  const isOpen = sidebar.classList.contains('open');
  sidebar.classList.toggle('open', !isOpen);
  overlay.classList.toggle('active', !isOpen);
  document.body.style.overflow = isOpen ? '' : 'hidden';
}

function closeSidebar() {
  document.getElementById('mainSidebar').classList.remove('open');
  document.getElementById('sidebarOverlay').classList.remove('active');
  document.body.style.overflow = '';
}

/* ============================================================
   LOGOUT
   ============================================================ */
async function logout() {
  try { await fetch(BASE_URL + '/api/logout.php'); } catch (_) {}
  window.location.href = '../index.php';
}

/* ============================================================
   HELPERS
   ============================================================ */
function calcAge(dateStr) {
  const birth = new Date(dateStr);
  const today = new Date();
  let age = today.getFullYear() - birth.getFullYear();
  const m = today.getMonth() - birth.getMonth();
  if (m < 0 || (m === 0 && today.getDate() < birth.getDate())) age--;
  return age;
}

function escapeHtml(str) {
  if (str === null || str === undefined) return '';
  const d = document.createElement('div');
  d.textContent = String(str);
  return d.innerHTML;
}
</script>

</body>
</html>
