<?php
session_start();
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

// Include database connection
require_once '../config/database.php';

$user_id = $_SESSION["user_id"];
$error = '';
$success = '';

// Fetch user data
$db = new Database();
$conn = $db->getConnection();

$query = "SELECT u.*,
          (SELECT COUNT(*) FROM likes WHERE to_user_id = u.id AND type = 'like') as likes_received,
          (SELECT COUNT(*) FROM matches WHERE user1_id = u.id OR user2_id = u.id) as total_matches
          FROM users u WHERE u.id = :user_id";
$stmt = $conn->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header("Location: login.php");
    exit();
}

// Fetch user interests
$interests_query = "SELECT i.id, i.nom, i.emoji FROM user_interests ui
                    JOIN interests i ON ui.interet_id = i.id
                    WHERE ui.user_id = :user_id";
$stmt = $conn->prepare($interests_query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$user_interests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch photos
$photos_query = "SELECT * FROM user_photos WHERE user_id = :user_id ORDER BY is_primary DESC, id ASC";
$stmt = $conn->prepare($photos_query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$photos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle profile update
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $bio = $_POST['bio'] ?? '';
    $ville = $_POST['ville'] ?? '';
    $telephone = $_POST['telephone'] ?? '';
    $selected_interests = $_POST['interests'] ?? [];

    // Update user info
    $update_query = "UPDATE users SET bio = :bio, ville = :ville, telephone = :telephone WHERE id = :user_id";
    $stmt = $conn->prepare($update_query);
    $stmt->bindParam(':bio', $bio);
    $stmt->bindParam(':ville', $ville);
    $stmt->bindParam(':telephone', $telephone);
    $stmt->bindParam(':user_id', $user_id);

    if ($stmt->execute()) {
        // Update interests
        $delete_interests = "DELETE FROM user_interests WHERE user_id = :user_id";
        $stmt = $conn->prepare($delete_interests);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();

        foreach ($selected_interests as $interest_id) {
            $insert_interest = "INSERT INTO user_interests (user_id, interet_id) VALUES (:user_id, :interest_id)";
            $stmt = $conn->prepare($insert_interest);
            $stmt->bindParam(':user_id', $user_id);
            $stmt->bindParam(':interest_id', $interest_id);
            $stmt->execute();
        }

        $success = "Profile updated successfully!";

        // Refresh user data
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Refresh interests
        $stmt = $conn->prepare($interests_query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $user_interests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $error = "Error updating profile.";
    }
}

// Fetch all available interests for selection
$all_interests_query = "SELECT * FROM interests ORDER BY categorie, nom";
$stmt = $conn->prepare($all_interests_query);
$stmt->execute();
$all_interests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group interests by category
$grouped_interests = [];
foreach ($all_interests as $interest) {
    $cat = $interest['categorie'] ?? 'Other';
    if (!isset($grouped_interests[$cat])) {
        $grouped_interests[$cat] = [];
    }
    $grouped_interests[$cat][] = $interest;
}

// Get user interest IDs for pre-selection
$user_interest_ids = array_column($user_interests, 'id');

// Calculate age
$age = null;
if ($user['date_naissance']) {
    $birth = new DateTime($user['date_naissance']);
    $today = new DateTime();
    $age = $today->diff($birth)->y;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
  <title>My Profile - MatchFace Campus</title>
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
      padding: 2rem;
      max-width: calc(100% - 280px);
    }

    /* Profile Container */
    .profile-container {
      max-width: 900px;
      margin: 0 auto;
    }

    /* Profile Header */
    .profile-header {
      background: #fff;
      border-radius: 28px;
      overflow: hidden;
      box-shadow: 0 12px 32px rgba(0, 0, 0, 0.08);
      margin-bottom: 2rem;
      animation: slideUp 0.4s ease;
    }

    @keyframes slideUp {
      from {
        opacity: 0;
        transform: translateY(20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .cover-photo {
      height: 180px;
      background: linear-gradient(135deg, #fd5068, #fc7c45);
      position: relative;
    }

    .profile-avatar {
      position: absolute;
      bottom: -50px;
      left: 2rem;
    }

    .profile-avatar img {
      width: 120px;
      height: 120px;
      border-radius: 50%;
      object-fit: cover;
      border: 5px solid #fff;
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
      background: #fff;
    }

    .profile-info {
      padding: 3rem 2rem 1.5rem 2rem;
    }

    .profile-name {
      font-size: 1.8rem;
      font-weight: 800;
      color: #1a1a2e;
      margin-bottom: 0.25rem;
    }

    .profile-badge {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      background: #f8f9fc;
      padding: 0.4rem 1rem;
      border-radius: 40px;
      font-size: 0.85rem;
      color: #666;
      margin-right: 0.75rem;
    }

    .profile-stats {
      display: flex;
      gap: 1.5rem;
      margin-top: 1rem;
      padding-top: 1rem;
      border-top: 1px solid #f0f0f0;
    }

    .stat {
      text-align: center;
    }

    .stat-number {
      font-size: 1.3rem;
      font-weight: 800;
      color: #fd5068;
    }

    .stat-label {
      font-size: 0.75rem;
      color: #aaa;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    /* Profile Card */
    .profile-card {
      background: #fff;
      border-radius: 24px;
      padding: 1.8rem;
      margin-bottom: 1.5rem;
      box-shadow: 0 4px 16px rgba(0, 0, 0, 0.05);
      transition: transform 0.2s, box-shadow 0.2s;
    }

    .profile-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 28px rgba(0, 0, 0, 0.1);
    }

    .card-title {
      display: flex;
      align-items: center;
      gap: 0.6rem;
      font-size: 1.2rem;
      font-weight: 700;
      color: #1a1a2e;
      margin-bottom: 1.2rem;
      padding-bottom: 0.75rem;
      border-bottom: 2px solid #f0f0f0;
    }

    .card-title i {
      color: #fd5068;
      font-size: 1.1rem;
    }

    /* Form Styles */
    .form-group {
      margin-bottom: 1.2rem;
    }

    .form-group label {
      display: block;
      font-size: 0.85rem;
      font-weight: 600;
      color: #444;
      margin-bottom: 0.5rem;
    }

    .form-group label i {
      margin-right: 0.4rem;
      color: #fd5068;
    }

    .form-group input,
    .form-group textarea {
      width: 100%;
      padding: 0.85rem 1rem;
      border: 2px solid #e8ecf0;
      border-radius: 14px;
      font-family: inherit;
      font-size: 0.95rem;
      color: #1a1a2e;
      transition: all 0.2s;
    }

    .form-group input:focus,
    .form-group textarea:focus {
      outline: none;
      border-color: #fd5068;
      box-shadow: 0 0 0 3px rgba(253, 80, 104, 0.1);
    }

    textarea {
      resize: vertical;
      min-height: 100px;
    }

    /* Interests Grid */
    .interests-grid {
      display: flex;
      flex-wrap: wrap;
      gap: 0.75rem;
      margin-bottom: 1rem;
    }

    .interest-chip {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.5rem 1rem;
      background: #f8f9fc;
      border: 2px solid #e8ecf0;
      border-radius: 40px;
      font-size: 0.85rem;
      font-weight: 500;
      color: #666;
      cursor: pointer;
      transition: all 0.2s;
    }

    .interest-chip:hover {
      border-color: #fd5068;
      background: #fff5f6;
    }

    .interest-chip.selected {
      background: linear-gradient(135deg, #fd5068, #fc7c45);
      border-color: transparent;
      color: #fff;
    }

    .interest-category {
      margin-bottom: 1.5rem;
    }

    .interest-category h4 {
      font-size: 0.8rem;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.08em;
      color: #aaa;
      margin-bottom: 0.8rem;
    }

    /* Button */
    .btn-save {
      display: inline-flex;
      align-items: center;
      gap: 0.6rem;
      padding: 0.9rem 2rem;
      background: linear-gradient(135deg, #fd5068, #fc7c45);
      color: #fff;
      border: none;
      border-radius: 60px;
      font-family: inherit;
      font-size: 1rem;
      font-weight: 700;
      cursor: pointer;
      transition: all 0.2s;
      width: 100%;
      justify-content: center;
    }

    .btn-save:hover {
      transform: translateY(-2px);
      box-shadow: 0 8px 24px rgba(253, 80, 104, 0.35);
    }

    .alert {
      padding: 1rem;
      border-radius: 14px;
      margin-bottom: 1.5rem;
      display: flex;
      align-items: center;
      gap: 0.6rem;
    }

    .alert-success {
      background: #f0fff4;
      color: #27ae60;
      border: 1px solid #9ae6b4;
    }

    .alert-error {
      background: #fff0f0;
      color: #e53e3e;
      border: 1px solid #feb2b2;
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
        max-width: 100%;
      }

      .mobile-header {
        display: flex;
      }

      .mobile-bottom-nav {
        display: flex;
      }

      .profile-avatar img {
        width: 90px;
        height: 90px;
      }

      .profile-name {
        font-size: 1.4rem;
      }

      .profile-info {
        padding: 2.5rem 1.5rem 1rem 1.5rem;
      }
    }
  </style>
</head>
<body>

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
        <img src="<?= htmlspecialchars($user['photo_url'] ?? '/assets/images/default-avatar.jpg', ENT_QUOTES) ?>" alt="Profile">
        <div>
          <div class="user-name"><?= htmlspecialchars($user['nom'] ?? 'User', ENT_QUOTES) ?></div>
          <div style="font-size:0.75rem;color:#aaa;">Edit profile →</div>
        </div>
      </a>
    </div>

    <nav class="sidebar-nav">
      <a href="dashboard.php">
        <i class="fas fa-fire"></i> Discover
      </a>
      <a href="matches.php">
        <i class="fas fa-heart"></i> My Matches
      </a>
      <a href="chat.php">
        <i class="fas fa-comment-dots"></i> Messages
      </a>
      <a href="profile.php" class="active">
        <i class="fas fa-user"></i> My Profile
      </a>
    </nav>

    <button class="logout-btn" onclick="logout()">
      <i class="fas fa-sign-out-alt"></i> Logout
    </button>
  </aside>

  <!-- Main Content -->
  <main class="main-content">
    <div class="profile-container">

      <!-- Profile Header -->
      <div class="profile-header">
        <div class="cover-photo"></div>
        <div class="profile-avatar">
          <img src="<?= htmlspecialchars($user['photo_url'] ?? '/assets/images/default-avatar.jpg', ENT_QUOTES) ?>"
               alt="<?= htmlspecialchars($user['nom'] ?? 'User', ENT_QUOTES) ?>"
               onerror="this.src='/assets/images/default-avatar.jpg'">
        </div>
        <div class="profile-info">
          <div class="profile-name">
            <?= htmlspecialchars($user['nom'] ?? '', ENT_QUOTES) ?>
            <?php if ($age): ?>
              <span style="font-weight:400; font-size:1.4rem;">, <?= $age ?></span>
            <?php endif; ?>
          </div>
          <div style="margin: 0.5rem 0 0.75rem;">
            <?php if ($user['ville']): ?>
              <span class="profile-badge"><i class="fas fa-map-marker-alt"></i> <?= htmlspecialchars($user['ville'], ENT_QUOTES) ?></span>
            <?php endif; ?>
            <?php if ($user['date_naissance']): ?>
              <span class="profile-badge"><i class="fas fa-birthday-cake"></i> <?= date('F j, Y', strtotime($user['date_naissance'])) ?></span>
            <?php endif; ?>
          </div>
          <div class="profile-stats">
            <div class="stat">
              <div class="stat-number"><?= $user['likes_received'] ?? 0 ?></div>
              <div class="stat-label">Likes</div>
            </div>
            <div class="stat">
              <div class="stat-number"><?= $user['total_matches'] ?? 0 ?></div>
              <div class="stat-label">Matches</div>
            </div>
            <div class="stat">
              <div class="stat-number"><?= count($user_interests) ?></div>
              <div class="stat-label">Interests</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Edit Profile Form -->
      <form method="POST" action="" id="profileForm">
        <?php if ($success): ?>
          <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?>
          </div>
        <?php endif; ?>

        <?php if ($error): ?>
          <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
          </div>
        <?php endif; ?>

        <!-- About Section -->
        <div class="profile-card">
          <div class="card-title">
            <i class="fas fa-user"></i>
            <span>About Me</span>
          </div>

          <div class="form-group">
            <label><i class="fas fa-align-left"></i> Bio</label>
            <textarea name="bio" placeholder="Tell others about yourself..."><?= htmlspecialchars($user['bio'] ?? '', ENT_QUOTES) ?></textarea>
          </div>

          <div class="form-group">
            <label><i class="fas fa-map-marker-alt"></i> City</label>
            <input type="text" name="ville" value="<?= htmlspecialchars($user['ville'] ?? '', ENT_QUOTES)" placeholder="Your city">
          </div>

          <div class="form-group">
            <label><i class="fas fa-phone"></i> Phone</label>
            <input type="tel" name="telephone" value="<?= htmlspecialchars($user['telephone'] ?? '', ENT_QUOTES)" placeholder="Your phone number">
          </div>
        </div>

        <!-- Interests Section -->
        <div class="profile-card">
          <div class="card-title">
            <i class="fas fa-heart"></i>
            <span>My Interests</span>
          </div>

          <div class="interests-container" id="interestsContainer">
            <?php foreach ($grouped_interests as $category => $interests): ?>
              <div class="interest-category">
                <h4><?= htmlspecialchars($category) ?></h4>
                <div class="interests-grid">
                  <?php foreach ($interests as $interest): ?>
                    <button type="button"
                            class="interest-chip <?= in_array($interest['id'], $user_interest_ids) ? 'selected' : '' ?>"
                            data-id="<?= $interest['id'] ?>"
                            onclick="toggleInterest(this)">
                      <?= htmlspecialchars($interest['emoji'] ?? '❤️') ?> <?= htmlspecialchars($interest['nom']) ?>
                    </button>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
          <input type="hidden" name="interests" id="interestsInput" value="<?= implode(',', $user_interest_ids) ?>">
          <p style="font-size:0.75rem; color:#aaa; margin-top:0.8rem;">
            <i class="fas fa-info-circle"></i> Select interests that represent you (minimum 3)
          </p>
        </div>

        <button type="submit" class="btn-save">
          <i class="fas fa-save"></i> Save Changes
        </button>
      </form>

    </div>
  </main>
</div>

<!-- Mobile Bottom Nav -->
<nav class="mobile-bottom-nav">
  <a href="dashboard.php">
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
  </a>
  <a href="profile.php" class="active">
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
  <a href="profile.php">
    <img src="<?= htmlspecialchars($user['photo_url'] ?? '/assets/images/default-avatar.jpg', ENT_QUOTES) ?>"
         style="width:36px;height:36px;border-radius:50%;object-fit:cover;border:2px solid #fd5068;">
  </a>
</header>

<script>
/* ============================================================
   INTERESTS SELECTION
   ============================================================ */
let selectedInterests = <?= json_encode($user_interest_ids) ?>;

function toggleInterest(btn) {
  const id = parseInt(btn.dataset.id);
  const index = selectedInterests.indexOf(id);

  if (index === -1) {
    if (selectedInterests.length >= 15) {
      btn.style.animation = 'pulse 0.3s ease';
      setTimeout(() => { btn.style.animation = ''; }, 300);
      return;
    }
    selectedInterests.push(id);
    btn.classList.add('selected');
  } else {
    selectedInterests.splice(index, 1);
    btn.classList.remove('selected');
  }

  document.getElementById('interestsInput').value = selectedInterests.join(',');
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
  try {
    await fetch(BASE_URL + '/api/logout.php');
  } catch (_) {}
  window.location.href = '../index.php';
}

/* ============================================================
   FORM VALIDATION
   ============================================================ */
document.getElementById('profileForm')?.addEventListener('submit', function(e) {
  if (selectedInterests.length < 3) {
    e.preventDefault();
    alert('Please select at least 3 interests.');
  }
});

/* ============================================================
   BASE URL FOR JS
   ============================================================ */
const BASE_URL = '/tinder';
</script>

<style>
  @keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.08); }
  }
</style>

</body>
</html>
