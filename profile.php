<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: pages/login.php');
    exit();
}

// Exemples de données de session. Dans votre application réelle, chargez depuis la base de données.
$userName = $_SESSION['user_name'] ?? 'Utilisateur';
$userEmail = $_SESSION['user_email'] ?? 'email@exemple.com';
$userCity = $_SESSION['user_city'] ?? 'Paris';
$userBio = $_SESSION['user_bio'] ?? 'Bienvenue dans votre profil MatchFace !';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Profil - MatchFace</title>
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    .profile-page { padding: 2rem; max-width: 960px; margin: auto; }
    .profile-card { background:#fff; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); padding: 1.6rem; }
    .profile-header { display:flex; gap:1rem; align-items:center; margin-bottom:1rem; }
    .profile-avatar { width:100px; height:100px; border-radius:50%; object-fit:cover; border:3px solid #fd5068; }
    .profile-name { margin:0; font-size:2rem; font-weight:700; }
    .profile-meta { margin:0.2rem 0; color:#666; }
    .profile-actions { margin-top:1.25rem; display:flex; flex-wrap:wrap; gap:0.5rem; }
    .btn-secondary { background:#f2f2f8; color:#333; border:none; border-radius:10px; padding:0.65rem 1rem; font-weight:600; text-decoration:none; display:inline-flex; align-items:center; gap:0.5rem; }
    .btn-secondary:hover { background:#e8e8f1; }
    .profile-section { margin-top:1rem; }
    .profile-section h2 { font-size:1.2rem; margin-bottom:0.6rem; }
    .profile-section p { margin: 0.3rem 0; color:#444; }
  </style>
</head>
<body>
  <main class="profile-page">
    <div class="profile-card">
      <div class="profile-header">
        <img src="assets/images/default-avatar.png" alt="Avatar" class="profile-avatar" onerror="this.src='https://via.placeholder.com/100?text=Avatar'" />
        <div>
          <h1 class="profile-name"><?php echo htmlspecialchars($userName); ?></h1>
          <p class="profile-meta"><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($userEmail); ?></p>
          <p class="profile-meta"><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($userCity); ?></p>
        </div>
      </div>
      <div class="profile-section">
        <h2>À propos</h2>
        <p><?php echo nl2br(htmlspecialchars($userBio)); ?></p>
      </div>
      <div class="profile-section profile-actions">
        <a href="pages/dashboard.php" class="btn-secondary"><i class="fas fa-home"></i> Découvrir</a>
        <a href="pages/matches.php" class="btn-secondary"><i class="fas fa-heart"></i> Mes matchs</a>
        <a href="pages/chat.php" class="btn-secondary"><i class="fas fa-comments"></i> Messagerie</a>
        <a href="pages/logout.php" class="btn-secondary"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
      </div>
    </div>
  </main>
</body>
</html>
