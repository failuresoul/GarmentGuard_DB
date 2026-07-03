<?php
require_once '../../../backend/includes/auth_check.php';
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "inspector") { header("Location: /frontend/index.html"); exit; }
$activePage = 'audits';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GarmentGuard - Audits</title>
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
        <h2 class="page-title">Compliance Audits</h2>
        <div class="user-profile-menu">
          <span style="font-weight:500;color:var(--text-secondary);"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
          <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?></div>
        </div>
      </div>

      <div class="card">
        <div class="search-bar">
          <input type="text" class="search-input" id="search" placeholder="Search by factory, result…">
        </div>
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>Factory</th>
                <th>Inspector</th>
                <th>Audit Date</th>
                <th>Next Scheduled</th>
                <th>Score</th>
                <th>Result</th>
                <th>Findings</th>
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
    function badgeClass(v) { return {'Pass':'badge-green','Fail':'badge-red','Pending':'badge-gray'}[v] || 'badge-gray'; }
    function scoreColor(s) { return s >= 75 ? 'var(--green)' : s >= 50 ? 'var(--amber)' : 'var(--red)'; }

    let allRows = [];
    fetch('/backend/api/audits.php')
      .then(r => r.json())
      .then(res => {
        if (!res.success) { showToast('Failed to load audits', 'error'); return; }
        allRows = res.data;
        render(allRows);
      })
      .catch(() => showToast('Network error', 'error'));

    function render(rows) {
      const tbody = document.getElementById('tbody');
      if (!rows.length) { tbody.innerHTML = '<tr><td colspan="7" class="empty-state">No audits found.</td></tr>'; return; }
      tbody.innerHTML = rows.map(r =>
        `<tr>
          <td><strong>${r.FACTORY_NAME}</strong></td>
          <td>${r.INSPECTOR_NAME}</td>
          <td>${r.AUDIT_DATE}</td>
          <td>${r.NEXT_SCHEDULED || '—'}</td>
          <td><span style="font-weight:700;color:${scoreColor(r.SCORE || 0)}">${r.SCORE != null ? r.SCORE : 'N/A'}</span></td>
          <td><span class="badge ${badgeClass(r.RESULT)}">${r.RESULT}</span></td>
          <td style="max-width:240px;font-size:13px;color:var(--text-secondary)">${r.FINDINGS ? r.FINDINGS.substring(0,80)+'…' : '—'}</td>
        </tr>`
      ).join('');
    }

    document.getElementById('search').addEventListener('input', function() {
      const q = this.value.toLowerCase();
      render(allRows.filter(r =>
        r.FACTORY_NAME.toLowerCase().includes(q) ||
        r.RESULT.toLowerCase().includes(q) ||
        (r.INSPECTOR_NAME && r.INSPECTOR_NAME.toLowerCase().includes(q))
      ));
    });
  </script>
</body>
</html>
