<?php
require_once '../../../backend/includes/auth_check.php';
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") { header("Location: /frontend/index.html"); exit; }
$activePage = 'users';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GarmentGuard - Users</title>
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
        <li><a href="dashboard.php" class="nav-link <?php echo $activePage === 'dashboard' ? 'active' : ''; ?>">📊 Dashboard</a></li>
        <li><a href="factories.php" class="nav-link <?php echo $activePage === 'factories' ? 'active' : ''; ?>">🏭 Factories</a></li>
        <li><a href="workers.php" class="nav-link <?php echo $activePage === 'workers' ? 'active' : ''; ?>">👷 Workers</a></li>
        <li><a href="audits.php" class="nav-link <?php echo $activePage === 'audits' ? 'active' : ''; ?>">📋 Audits</a></li>
        <li><a href="grievances.php" class="nav-link <?php echo $activePage === 'grievances' ? 'active' : ''; ?>">📣 Grievances</a></li>
        <li><a href="salary.php" class="nav-link <?php echo $activePage === 'salary' ? 'active' : ''; ?>">💰 Salaries</a></li>
        <li><a href="certifications.php" class="nav-link <?php echo $activePage === 'certifications' ? 'active' : ''; ?>">🏅 Certifications</a></li>
        <li><a href="equipment.php" class="nav-link <?php echo $activePage === 'equipment' ? 'active' : ''; ?>">🧯 Safety Equipment</a></li>
        <li><a href="buyer.php" class="nav-link <?php echo $activePage === 'buyer' ? 'active' : ''; ?>">🛒 Buyers</a></li>
        <li><a href="reports.php" class="nav-link <?php echo $activePage === 'reports' ? 'active' : ''; ?>">📈 Reports</a></li>
        <li><a href="users.php" class="nav-link <?php echo $activePage === 'users' ? 'active' : ''; ?>">👤 Users</a></li>
      </ul>
      <div class="nav-footer">
        <a href="../../../backend/auth/logout.php" class="nav-link">🚪 Logout</a>
      </div>
    </div>

    <div class="main-content">
      <div class="top-bar">
        <h2 class="page-title">System Users</h2>
        <div class="user-profile-menu">
          <span style="font-weight:500;color:var(--text-secondary);"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
          <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?></div>
        </div>
      </div>

      <div id="access-denied" style="display:none">
        <div class="card">
          <div class="empty-state">
            <div class="empty-state-icon">🔒</div>
            <p>Access restricted to administrators only.</p>
          </div>
        </div>
      </div>

      <div id="users-section">
        <div class="card">
          <div class="search-bar">
            <input type="text" class="search-input" id="search" placeholder="Search users…">
          </div>
          <div class="table-responsive">
            <table class="table">
              <thead>
                <tr>
                  <th>Username</th>
                  <th>Full Name</th>
                  <th>Role</th>
                  <th>Factory</th>
                  <th>Email</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody id="tbody">
                <tr><td colspan="6" style="text-align:center;color:var(--text-secondary)">Loading…</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="../../assets/js/toast.js"></script>
  <script>
    const roleBadge = v => ({
      'admin':'badge-purple','compliance_officer':'badge-blue',
      'inspector':'badge-amber','buyer_user':'badge-green','worker':'badge-gray'
    })[v] || 'badge-gray';
    const statusBadge = v => v === 'Active' ? 'badge-green' : 'badge-red';

    let allRows = [];
    fetch('/backend/api/users.php')
      .then(r => r.json())
      .then(res => {
        if (!res.success) {
          if (res.message === 'Forbidden') {
            document.getElementById('access-denied').style.display = 'block';
            document.getElementById('users-section').style.display = 'none';
          } else {
            showToast(res.message, 'error');
          }
          return;
        }
        allRows = res.data;
        render(allRows);
      })
      .catch(() => showToast('Network error', 'error'));

    function render(rows) {
      const tbody = document.getElementById('tbody');
      if (!rows.length) { tbody.innerHTML = '<tr><td colspan="6" class="empty-state">No users found.</td></tr>'; return; }
      tbody.innerHTML = rows.map(r =>
        `<tr>
          <td><strong>${r.USERNAME}</strong></td>
          <td>${r.FULL_NAME}</td>
          <td><span class="badge ${roleBadge(r.ROLE)}">${r.ROLE}</span></td>
          <td>${r.FACTORY_NAME}</td>
          <td style="color:var(--text-secondary)">${r.EMAIL}</td>
          <td><span class="badge ${statusBadge(r.STATUS)}">${r.STATUS}</span></td>
        </tr>`
      ).join('');
    }

    document.getElementById('search').addEventListener('input', function() {
      const q = this.value.toLowerCase();
      render(allRows.filter(r =>
        r.USERNAME.toLowerCase().includes(q) ||
        r.FULL_NAME.toLowerCase().includes(q) ||
        r.ROLE.toLowerCase().includes(q)
      ));
    });
  </script>
</body>
</html>
