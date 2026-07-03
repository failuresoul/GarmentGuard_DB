<?php
require_once '../../../backend/includes/auth_check.php';
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "inspector") { header("Location: /frontend/index.html"); exit; }
$activePage = 'equipment';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GarmentGuard - Safety Equipment</title>
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
        <li><a href="audits.php" class="nav-link <?php echo $activePage === 'audits' ? 'active' : ''; ?>">📋 Audits</a></li>
        <li><a href="equipment.php" class="nav-link <?php echo $activePage === 'equipment' ? 'active' : ''; ?>">🧯 Safety Equipment</a></li>
        <li><a href="reports.php" class="nav-link <?php echo $activePage === 'reports' ? 'active' : ''; ?>">📈 Reports</a></li>
      </ul>
      <div class="nav-footer">
        <a href="../../../backend/auth/logout.php" class="nav-link">🚪 Logout</a>
      </div>
    </div>

    <div class="main-content">
      <div class="top-bar">
        <h2 class="page-title">Safety Equipment</h2>
        <div class="user-profile-menu">
          <span style="font-weight:500;color:var(--text-secondary);"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
          <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?></div>
        </div>
      </div>

      <div class="card">
        <div class="search-bar">
          <input type="text" class="search-input" id="search" placeholder="Search equipment…">
        </div>
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>Factory</th>
                <th>Equipment Type</th>
                <th>Qty</th>
                <th>Location</th>
                <th>Condition</th>
                <th>Expiry Date</th>
                <th>Last Inspection</th>
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
    function condBadge(v) {
      return {'Good':'badge-green','Fair':'badge-amber','Poor':'badge-red','Critical':'badge-red'}[v] || 'badge-gray';
    }

    let allRows = [];
    fetch('/backend/api/equipment.php')
      .then(r => r.json())
      .then(res => {
        if (!res.success) { showToast('Failed to load equipment', 'error'); return; }
        allRows = res.data;
        render(allRows);
      })
      .catch(() => showToast('Network error', 'error'));

    function render(rows) {
      const tbody = document.getElementById('tbody');
      if (!rows.length) { tbody.innerHTML = '<tr><td colspan="7" class="empty-state">No equipment found.</td></tr>'; return; }
      tbody.innerHTML = rows.map(r =>
        `<tr>
          <td><strong>${r.FACTORY_NAME}</strong></td>
          <td>${r.EQUIPMENT_TYPE}</td>
          <td>${r.QUANTITY}</td>
          <td style="color:var(--text-secondary)">${r.LOCATION || '—'}</td>
          <td><span class="badge ${condBadge(r.CONDITION_STATUS)}">${r.CONDITION_STATUS}</span></td>
          <td>${r.EXPIRY_DATE || '—'}</td>
          <td>${r.LAST_INSPECTION || '—'}</td>
        </tr>`
      ).join('');
    }

    document.getElementById('search').addEventListener('input', function() {
      const q = this.value.toLowerCase();
      render(allRows.filter(r =>
        r.FACTORY_NAME.toLowerCase().includes(q) ||
        r.EQUIPMENT_TYPE.toLowerCase().includes(q) ||
        r.CONDITION_STATUS.toLowerCase().includes(q)
      ));
    });
  </script>
</body>
</html>
