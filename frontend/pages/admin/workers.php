<?php
require_once '../../../backend/includes/auth_check.php';
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") { header("Location: /frontend/index.html"); exit; }
$activePage = 'workers';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GarmentGuard - Workers</title>
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
        <h2 class="page-title">Workers</h2>
        <div class="user-profile-menu">
          <span style="font-weight:500;color:var(--text-secondary);"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
          <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?></div>
        </div>
      </div>

      <div class="card">
        <div class="search-bar">
          <input type="text" class="search-input" id="search" placeholder="Search workers…">
        </div>
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>Name</th>
                <th>National ID</th>
                <th>Designation</th>
                <th>Factory</th>
                <th>Shift</th>
                <th>Join Date</th>
                <th>Base Salary</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody id="tbody">
              <tr><td colspan="8" style="text-align:center;color:var(--text-secondary)">Loading…</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <script src="../../assets/js/toast.js"></script>
  <script>
    function statusBadge(v) { return {'Active':'badge-green','Inactive':'badge-amber','Terminated':'badge-red'}[v] || 'badge-gray'; }
    function shiftBadge(v) { return v === 'Day' ? 'badge-blue' : 'badge-purple'; }

    let allRows = [];
    fetch('/backend/api/workers.php')
      .then(r => r.json())
      .then(res => {
        if (!res.success) { showToast('Failed to load workers', 'error'); return; }
        allRows = res.data;
        render(allRows);
      })
      .catch(() => showToast('Network error', 'error'));

    function render(rows) {
      const tbody = document.getElementById('tbody');
      if (!rows.length) { tbody.innerHTML = '<tr><td colspan="8" class="empty-state">No workers found.</td></tr>'; return; }
      tbody.innerHTML = rows.map(r =>
        `<tr>
          <td><strong>${r.FULL_NAME}</strong></td>
          <td style="font-size:13px;color:var(--text-secondary)">${r.NATIONAL_ID}</td>
          <td>${r.DESIGNATION}</td>
          <td>${r.FACTORY_NAME}</td>
          <td><span class="badge ${shiftBadge(r.SHIFT)}">${r.SHIFT}</span></td>
          <td>${r.JOIN_DATE}</td>
          <td>৳ ${Number(r.BASE_SALARY).toLocaleString()}</td>
          <td><span class="badge ${statusBadge(r.STATUS)}">${r.STATUS}</span></td>
        </tr>`
      ).join('');
    }

    document.getElementById('search').addEventListener('input', function() {
      const q = this.value.toLowerCase();
      render(allRows.filter(r =>
        r.FULL_NAME.toLowerCase().includes(q) ||
        r.DESIGNATION.toLowerCase().includes(q) ||
        r.FACTORY_NAME.toLowerCase().includes(q) ||
        r.NATIONAL_ID.toLowerCase().includes(q)
      ));
    });
  </script>
</body>
</html>
