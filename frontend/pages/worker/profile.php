<?php
require_once '../../../backend/includes/auth_check.php';
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "worker") { header("Location: /frontend/index.html"); exit; }
$activePage = 'profile';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GarmentGuard - Profile</title>
  <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
  <div class="app-container">
    <div class="sidebar" id="sidebar">
      <div class="brand">
        <span class="brand-title">GarmentGuard</span>
        <span class="brand-subtitle">Compliance System</span>
      </div>
            <ul class="nav-menu">
        <li><a href="profile.php" class="nav-link <?php echo $activePage === 'profile' ? 'active' : ''; ?>">👤 Profile</a></li>
        <li><a href="grievances.php" class="nav-link <?php echo $activePage === 'grievances' ? 'active' : ''; ?>">📣 Grievances</a></li>
        <li><a href="salary.php" class="nav-link <?php echo $activePage === 'salary' ? 'active' : ''; ?>">💰 Salaries</a></li>
        <li><a href="equipment.php" class="nav-link <?php echo $activePage === 'equipment' ? 'active' : ''; ?>">🧯 Safety Equipment</a></li>
      </ul>
      <div class="nav-footer">
        <a href="../../../backend/auth/logout.php" class="nav-link">Logout</a>
      </div>
    </div>

    <div class="main-content">
      <div class="top-bar">
        <h2 class="page-title">Profile</h2>
        <div class="user-profile-menu">
          <span style="font-weight: 500; color: var(--text-secondary);"><?php echo htmlspecialchars($_SESSION['full_name']); ?> (<?php echo htmlspecialchars($_SESSION['role']); ?>)</span>
          <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?></div>
        </div>
      </div>

      <div class="card">
        <h3 class="card-title">User Profile</h3>
        <p style="color: var(--text-secondary); font-size: 15px; line-height: 1.6;">
          Managing account passwords, email addresses, and specific role authorizations.
        </p>
      </div>
    </div>
  </div>
</body>
</html>
