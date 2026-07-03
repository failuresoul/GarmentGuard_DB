<?php
require_once '../../../backend/includes/auth_check.php';
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "compliance_officer") { header("Location: /frontend/index.html"); exit; }
$activePage = 'buyer';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GarmentGuard - Buyers</title>
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
      </ul>
      <div class="nav-footer">
        <a href="../../../backend/auth/logout.php" class="nav-link">🚪 Logout</a>
      </div>
    </div>

    <div class="main-content">
      <div class="top-bar">
        <h2 class="page-title">Buyers</h2>
        <div class="user-profile-menu">
          <span style="font-weight:500;color:var(--text-secondary);"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
          <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?></div>
        </div>
      </div>

      <div class="card">
        <div class="search-bar">
          <input type="text" class="search-input" id="search" placeholder="Search buyers…">
        </div>
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>Brand</th>
                <th>Company</th>
                <th>Country</th>
                <th>Contact Person</th>
                <th>Email</th>
                <th>Phone</th>
                <th>Active Contracts</th>
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
    let allRows = [];
    fetch('/backend/api/buyer.php')
      .then(r => r.json())
      .then(res => {
        if (!res.success) { showToast('Failed to load buyers', 'error'); return; }
        allRows = res.data;
        render(allRows);
      })
      .catch(() => showToast('Network error', 'error'));

    function render(rows) {
      const tbody = document.getElementById('tbody');
      if (!rows.length) { tbody.innerHTML = '<tr><td colspan="7" class="empty-state">No buyers found.</td></tr>'; return; }
      tbody.innerHTML = rows.map(r =>
        `<tr>
          <td><strong>${r.BRAND_NAME}</strong></td>
          <td>${r.BUYER_NAME}</td>
          <td><span class="badge badge-blue">${r.COUNTRY}</span></td>
          <td>${r.CONTACT_NAME || '—'}</td>
          <td style="color:var(--text-secondary)">${r.EMAIL || '—'}</td>
          <td style="color:var(--text-secondary)">${r.PHONE || '—'}</td>
          <td><span class="badge badge-green">${r.ACTIVE_CONTRACTS} factories</span></td>
        </tr>`
      ).join('');
    }

    document.getElementById('search').addEventListener('input', function() {
      const q = this.value.toLowerCase();
      render(allRows.filter(r =>
        r.BUYER_NAME.toLowerCase().includes(q) ||
        r.BRAND_NAME.toLowerCase().includes(q) ||
        r.COUNTRY.toLowerCase().includes(q)
      ));
    });
  </script>
</body>
</html>
