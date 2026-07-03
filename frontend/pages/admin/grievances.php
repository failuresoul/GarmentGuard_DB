<?php
require_once '../../../backend/includes/auth_check.php';
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") { header("Location: /frontend/index.html"); exit; }
$activePage = 'grievances';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GarmentGuard - Grievances</title>
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
        <h2 class="page-title">Grievances</h2>
        <div class="user-profile-menu">
          <span style="font-weight:500;color:var(--text-secondary);"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
          <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?></div>
        </div>
      </div>

      <div class="card">
        <div class="search-bar">
          <input type="text" class="search-input" id="search" placeholder="Search by worker, category, status…">
        </div>
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>Worker</th>
                <th>Factory</th>
                <th>Category</th>
                <th>Description</th>
                <th>Submitted</th>
                <th>Status</th>
                <th>Resolved</th>
              </tr>
            </thead>
            <tbody id="tbody">
              <tr><td colspan="7" style="text-align:center;color:var(--text-secondary)">Loading…</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <script src="../../assets/js/toast.js"></script>
  <script>
    function statusBadge(v) { return {'Open':'badge-red','In Progress':'badge-amber','Resolved':'badge-green'}[v] || 'badge-gray'; }

    let allRows = [];
    fetch('/backend/api/grievances.php')
      .then(r => r.json())
      .then(res => {
        if (!res.success) { showToast('Failed to load grievances', 'error'); return; }
        allRows = res.data;
        render(allRows);
      })
      .catch(() => showToast('Network error', 'error'));

    function render(rows) {
      const tbody = document.getElementById('tbody');
      if (!rows.length) { tbody.innerHTML = '<tr><td colspan="7" class="empty-state">No grievances found.</td></tr>'; return; }
      tbody.innerHTML = rows.map(r =>
        `<tr>
          <td><strong>${r.WORKER_NAME}</strong></td>
          <td>${r.FACTORY_NAME}</td>
          <td><span class="badge badge-blue">${r.CATEGORY}</span></td>
          <td style="max-width:200px;font-size:13px;color:var(--text-secondary)">${r.DESCRIPTION ? r.DESCRIPTION.substring(0,70)+'…' : '—'}</td>
          <td>${r.SUBMITTED_DATE}</td>
          <td><span class="badge ${statusBadge(r.STATUS)}">${r.STATUS}</span></td>
          <td>${r.RESOLVED_DATE || '—'}</td>
        </tr>`
      ).join('');
    }

    document.getElementById('search').addEventListener('input', function() {
      const q = this.value.toLowerCase();
      render(allRows.filter(r =>
        r.WORKER_NAME.toLowerCase().includes(q) ||
        r.FACTORY_NAME.toLowerCase().includes(q) ||
        r.CATEGORY.toLowerCase().includes(q) ||
        r.STATUS.toLowerCase().includes(q)
      ));
    });
  </script>
</body>
</html>
