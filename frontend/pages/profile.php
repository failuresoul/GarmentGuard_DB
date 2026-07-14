<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: /frontend/index.html");
    exit;
}

require_once __DIR__ . '/../../backend/config/db.php';
require_once __DIR__ . '/../../backend/includes/helpers.php';

$user_id = $_SESSION['user_id'];
$sql = "SELECT u.user_id, u.username, u.role, u.full_name, u.email, u.status, u.factory_id,
               NVL(f.factory_name, 'N/A') AS factory_name
        FROM USER_ u
        LEFT JOIN FACTORY f ON u.factory_id = f.factory_id
        WHERE u.user_id = :user_id";
$rows = fetchRows($conn, $sql, [':user_id' => $user_id]);

if (empty($rows)) {
    header("Location: /frontend/index.html");
    exit;
}
$user = $rows[0];

$activePage = 'profile';
$fullName = $user['FULL_NAME'];
$role = $user['ROLE'];

// Detect whether running in subfolder (like worker/profile.php) or directly (profile.php)
$is_subfolder = (strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false ||
                 strpos($_SERVER['SCRIPT_NAME'], '/compliance_officer/') !== false ||
                 strpos($_SERVER['SCRIPT_NAME'], '/inspector/') !== false ||
                 strpos($_SERVER['SCRIPT_NAME'], '/buyer/') !== false ||
                 strpos($_SERVER['SCRIPT_NAME'], '/worker/') !== false);

$prefix = $is_subfolder ? '../' : '';

$navMenu = [];
if ($role === 'admin') {
    $navMenu = [
        'dashboard' => ['📊 Dashboard', $prefix . 'admin/dashboard.php'],
        'factories' => ['🏭 Factories', $prefix . 'admin/factories.php'],
        'workers' => ['👷 Workers', $prefix . 'admin/workers.php'],
        'audits' => ['📋 Audits', $prefix . 'admin/audits.php'],
        'grievances' => ['📣 Grievances', $prefix . 'admin/grievances.php'],
        'salary' => ['💰 Salaries', $prefix . 'admin/salary.php'],
        'certifications' => ['🏅 Certifications', $prefix . 'admin/certifications.php'],
        'equipment' => ['🧯 Safety Equipment', $prefix . 'admin/equipment.php'],
        'buyer' => ['🛒 Buyers', $prefix . 'admin/buyer.php'],
        'reports' => ['📈 Reports', $prefix . 'admin/reports.php'],
        'users' => ['👤 Users', $prefix . 'admin/users.php'],
    ];
} elseif ($role === 'compliance_officer') {
    $navMenu = [
        'dashboard' => ['📊 Dashboard', $prefix . 'compliance_officer/dashboard.php'],
        'factories' => ['🏭 Factories', $prefix . 'compliance_officer/factories.php'],
        'workers' => ['👷 Workers', $prefix . 'compliance_officer/workers.php'],
        'audits' => ['📋 Audits', $prefix . 'compliance_officer/audits.php'],
        'grievances' => ['📣 Grievances', $prefix . 'compliance_officer/grievances.php'],
        'salary' => ['💰 Salaries', $prefix . 'compliance_officer/salary.php'],
        'certifications' => ['🏅 Certifications', $prefix . 'compliance_officer/certifications.php'],
        'equipment' => ['🧯 Safety Equipment', $prefix . 'compliance_officer/equipment.php'],
        'buyer' => ['🛒 Buyers', $prefix . 'compliance_officer/buyer.php'],
        'reports' => ['📈 Reports', $prefix . 'compliance_officer/reports.php'],
    ];
} elseif ($role === 'inspector') {
    $navMenu = [
        'dashboard' => ['📊 Dashboard', $prefix . 'inspector/dashboard.php'],
        'factories' => ['🏭 Factories', $prefix . 'inspector/factories.php'],
        'audits' => ['📋 Audits', $prefix . 'inspector/audits.php'],
        'equipment' => ['🧯 Safety Equipment', $prefix . 'inspector/equipment.php'],
        'reports' => ['📈 Reports', $prefix . 'inspector/reports.php'],
    ];
} elseif ($role === 'buyer_user') {
    $navMenu = [
        'dashboard' => ['📊 Dashboard', $prefix . 'buyer/dashboard.php'],
        'factories' => ['🏭 Factories', $prefix . 'buyer/factories.php'],
        'audits' => ['📋 Audits', $prefix . 'buyer/audits.php'],
        'certifications' => ['🏅 Certifications', $prefix . 'buyer/certifications.php'],
        'reports' => ['📈 Reports', $prefix . 'buyer/reports.php'],
    ];
} elseif ($role === 'worker') {
    $navMenu = [
        'profile' => ['👤 Profile', $prefix . 'worker/profile.php'],
        'grievances' => ['📣 Grievances', $prefix . 'worker/grievances.php'],
        'salary' => ['💰 Salaries', $prefix . 'worker/salary.php'],
        'equipment' => ['🧯 Safety Equipment', $prefix . 'worker/equipment.php'],
    ];
}

// For non-worker roles, add My Profile at the bottom of the sidebar
if ($role !== 'worker') {
    $navMenu['profile'] = ['👤 My Profile', $is_subfolder ? '../profile.php' : 'profile.php'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GarmentGuard - My Profile</title>
  <link rel="stylesheet" href="/frontend/assets/css/style.css">
  <style>
    .profile-layout {
      display: grid;
      grid-template-columns: 320px 1fr;
      gap: 24px;
      align-items: start;
    }
    @media (max-width: 900px) {
      .profile-layout {
        grid-template-columns: 1fr;
      }
    }
    .profile-card {
      text-align: center;
      padding: 32px 24px;
    }
    .big-avatar {
      width: 96px;
      height: 96px;
      border-radius: 50%;
      background: linear-gradient(135deg, var(--green), #14b8a6);
      color: #fff;
      font-size: 36px;
      font-weight: 700;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 20px;
      box-shadow: 0 4px 14px rgba(29, 158, 117, 0.3);
    }
    .profile-name {
      font-size: 20px;
      font-weight: 700;
      margin-bottom: 4px;
      color: var(--text-primary);
    }
    .profile-username {
      font-size: 14px;
      color: var(--text-secondary);
      margin-bottom: 16px;
    }
    .profile-details-list {
      margin-top: 24px;
      text-align: left;
      border-top: 1px solid var(--border-color);
      padding-top: 20px;
      display: flex;
      flex-direction: column;
      gap: 14px;
    }
    .profile-detail-item {
      display: flex;
      flex-direction: column;
      gap: 4px;
    }
    .profile-detail-label {
      font-size: 12px;
      font-weight: 600;
      color: var(--text-secondary);
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    .profile-detail-value {
      font-size: 14px;
      color: var(--text-primary);
      word-break: break-all;
    }
    .form-section {
      margin-bottom: 24px;
    }
    .form-section-title {
      font-size: 16px;
      font-weight: 600;
      margin-bottom: 16px;
      padding-bottom: 8px;
      border-bottom: 1px solid var(--border-color);
    }
    .form-group {
      display: flex;
      flex-direction: column;
      gap: 6px;
      margin-bottom: 16px;
    }
    .form-label {
      font-size: 13px;
      font-weight: 500;
      color: var(--text-secondary);
    }
    .form-control {
      background-color: var(--bg-primary);
      border: 1px solid var(--border-color);
      border-radius: 8px;
      padding: 10px 14px;
      color: var(--text-primary);
      font-family: var(--font-family);
      font-size: 14px;
      outline: none;
      width: 100%;
      transition: border-color var(--transition-speed);
    }
    .form-control:focus {
      border-color: var(--green);
    }
  </style>
</head>
<body>
  <div class="app-container">
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
      <div class="brand">
        <span class="brand-title">GarmentGuard</span>
        <span class="brand-subtitle">Compliance System</span>
      </div>
      <ul class="nav-menu">
        <?php foreach ($navMenu as $key => $item): ?>
          <li>
            <a href="<?php echo htmlspecialchars($item[1]); ?>" 
               class="nav-link <?php echo $key === $activePage ? 'active' : ''; ?>">
              <?php echo htmlspecialchars($item[0]); ?>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
      <div class="nav-footer">
        <a href="/backend/auth/logout.php" class="nav-link">🚪 Logout</a>
      </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
      <div class="top-bar">
        <h2 class="page-title">My Profile</h2>
        <div class="user-profile-menu">
          <span style="font-weight:500;color:var(--text-secondary);" id="top-full-name"><?php echo htmlspecialchars($fullName); ?></span>
          <div class="user-avatar" id="top-avatar"><?php echo strtoupper(substr($fullName, 0, 1)); ?></div>
        </div>
      </div>

      <div class="profile-layout">
        <!-- Left: Profile Summary Card -->
        <div class="card profile-card">
          <div class="big-avatar" id="big-avatar">--</div>
          <div class="profile-name" id="card-full-name"><?php echo htmlspecialchars($user['FULL_NAME']); ?></div>
          <div class="profile-username">@<?php echo htmlspecialchars($user['USERNAME']); ?></div>
          
          <?php
            $roleColors = [
              'admin' => 'badge-purple',
              'compliance_officer' => 'badge-blue',
              'inspector' => 'badge-teal',
              'buyer_user' => 'badge-amber',
              'worker' => 'badge-green'
            ];
            $roleClass = $roleColors[$user['ROLE']] || 'badge-gray';
            
            // Format Role
            $formattedRole = implode(' ', array_map('ucfirst', explode('_', $user['ROLE'])));
          ?>
          <span class="badge <?php echo $roleClass; ?>"><?php echo htmlspecialchars($formattedRole); ?></span>

          <div class="profile-details-list">
            <div class="profile-detail-item">
              <div class="profile-detail-label">Email Address</div>
              <div class="profile-detail-value" id="card-email"><?php echo htmlspecialchars($user['EMAIL']); ?></div>
            </div>
            <div class="profile-detail-item">
              <div class="profile-detail-label">Associated Factory</div>
              <div class="profile-detail-value"><?php echo htmlspecialchars($user['FACTORY_NAME']); ?></div>
            </div>
            <div class="profile-detail-item">
              <div class="profile-detail-label">Account Status</div>
              <div class="profile-detail-value">
                <span class="badge badge-green"><?php echo htmlspecialchars($user['STATUS']); ?></span>
              </div>
            </div>
          </div>
        </div>

        <!-- Right: Editable Forms -->
        <div class="card" style="padding: 32px;">
          <!-- Edit Profile Form -->
          <div class="form-section">
            <h3 class="form-section-title">👤 Edit Profile Information</h3>
            <form id="edit-profile-form" onsubmit="submitProfileUpdate(event)">
              <div class="form-group">
                <label class="form-label" for="profile-full-name">Full Name <span style="color:var(--red)">*</span></label>
                <input type="text" class="form-control" id="profile-full-name" required value="<?php echo htmlspecialchars($user['FULL_NAME']); ?>">
              </div>
              <div class="form-group">
                <label class="form-label" for="profile-email">Email Address <span style="color:var(--red)">*</span></label>
                <input type="email" class="form-control" id="profile-email" required value="<?php echo htmlspecialchars($user['EMAIL']); ?>">
              </div>
              <button type="submit" class="btn btn-primary" id="profile-save-btn">Save Changes</button>
            </form>
          </div>

          <!-- Change Password Section -->
          <div class="form-section" style="margin-top: 32px;">
            <h3 class="form-section-title">🔒 Change Password</h3>
            <form id="change-password-form" onsubmit="submitPasswordChange(event)">
              <div class="form-group">
                <label class="form-label" for="pwd-current">Current Password <span style="color:var(--red)">*</span></label>
                <input type="password" class="form-control" id="pwd-current" required placeholder="••••••••">
              </div>
              <div class="form-group">
                <label class="form-label" for="pwd-new">New Password <span style="color:var(--red)">*</span></label>
                <input type="password" class="form-control" id="pwd-new" required placeholder="••••••••">
              </div>
              <div class="form-group">
                <label class="form-label" for="pwd-confirm">Confirm New Password <span style="color:var(--red)">*</span></label>
                <input type="password" class="form-control" id="pwd-confirm" required placeholder="••••••••">
              </div>
              <button type="submit" class="btn btn-primary" id="password-save-btn">Update Password</button>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="/frontend/assets/js/toast.js"></script>
  <script>
    const sessionUserId = <?php echo intval($user_id); ?>;

    document.addEventListener('DOMContentLoaded', () => {
      updateAvatarInitials();
    });

    function updateAvatarInitials() {
      const name = document.getElementById('profile-full-name').value.trim();
      if (!name) return;
      
      // Calculate initials: name.split(' ').map(n=>n[0]).join('')
      const initials = name.split(' ')
                           .filter(n => n)
                           .map(n => n[0])
                           .join('')
                           .substring(0, 3)
                           .toUpperCase();

      document.getElementById('big-avatar').innerText = initials;
    }

    function submitProfileUpdate(e) {
      e.preventDefault();
      
      const payload = {
        user_id: sessionUserId,
        full_name: document.getElementById('profile-full-name').value.trim(),
        email: document.getElementById('profile-email').value.trim()
      };

      if (!payload.full_name || !payload.email) {
        showToast('Please fill in all required fields', 'error');
        return;
      }

      const btn = document.getElementById('profile-save-btn');
      btn.disabled = true;
      btn.textContent = 'Saving…';

      fetch('/backend/api/users.php', {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      })
      .then(r => r.json())
      .then(res => {
        if (res.success) {
          showToast('Profile updated successfully', 'success');
          // Instantly update the UI
          document.getElementById('card-full-name').innerText = payload.full_name;
          document.getElementById('card-email').innerText = payload.email;
          document.getElementById('top-full-name').innerText = payload.full_name;
          
          const avatarChar = payload.full_name.charAt(0).toUpperCase();
          document.getElementById('top-avatar').innerText = avatarChar;
          
          updateAvatarInitials();
        } else {
          showToast(res.message || 'Update failed', 'error');
        }
      })
      .catch(() => showToast('Network error', 'error'))
      .finally(() => {
        btn.disabled = false;
        btn.textContent = 'Save Changes';
      });
    }

    function submitPasswordChange(e) {
      e.preventDefault();
      
      const currentPwd = document.getElementById('pwd-current').value;
      const newPwd = document.getElementById('pwd-new').value;
      const confirmPwd = document.getElementById('pwd-confirm').value;

      if (!currentPwd || !newPwd || !confirmPwd) {
        showToast('Please fill in all password fields', 'error');
        return;
      }

      if (newPwd !== confirmPwd) {
        showToast('New Password and Confirm New Password do not match', 'error');
        return;
      }

      const btn = document.getElementById('password-save-btn');
      btn.disabled = true;
      btn.textContent = 'Updating…';

      fetch('/backend/api/users.php?action=change_password', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          current_password: currentPwd,
          new_password: newPwd,
          confirm_password: confirmPwd
        })
      })
      .then(r => r.json())
      .then(res => {
        if (res.success) {
          showToast('Password updated successfully', 'success');
          document.getElementById('change-password-form').reset();
        } else {
          showToast(res.message || 'Password update failed', 'error');
        }
      })
      .catch(() => showToast('Network error', 'error'))
      .finally(() => {
        btn.disabled = false;
        btn.textContent = 'Update Password';
      });
    }
  </script>
</body>
</html>
