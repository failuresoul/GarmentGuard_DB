<?php
require_once '../../../backend/includes/auth_check.php';
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "buyer_user") { header("Location: /frontend/index.html"); exit; }
$activePage = 'factories';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GarmentGuard - Factories</title>
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
        <li><a href="certifications.php" class="nav-link <?php echo $activePage === 'certifications' ? 'active' : ''; ?>">🏅 Certifications</a></li>
        <li><a href="reports.php" class="nav-link <?php echo $activePage === 'reports' ? 'active' : ''; ?>">📈 Reports</a></li>
      </ul>
      <div class="nav-footer">
        <a href="../../../backend/auth/logout.php" class="nav-link">🚪 Logout</a>
      </div>
    </div>

    <div class="main-content">
      <div class="top-bar">
        <h2 class="page-title">Factories</h2>
        <div class="user-profile-menu">
          <span style="font-weight:500;color:var(--text-secondary);"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
          <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?></div>
        </div>
      </div>

      <div class="card">
        <div class="search-bar">
          <input type="text" class="search-input" id="search" placeholder="Search factories…">
        </div>
        <div class="table-responsive">
          <table class="table" id="factories-table">
            <thead>
              <tr>
                <th>Factory Name</th>
                <th>Reg No</th>
                <th>District</th>
                
                <th>Workers</th>
                <th>Compliance Score</th>
                <th>Status</th>
                <th>Last Audit</th>
                <th>Next Audit</th>
              </tr>
            </thead>
            <tbody id="tbody">
              <tr><td colspan="9" style="text-align:center;color:var(--text-secondary)">Loading…</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <script src="../../assets/js/toast.js"></script>
  <script>
    function badgeClass(v) {
      return {'Compliant':'badge-green','At Risk':'badge-amber','Non-Compliant':'badge-red','Review Needed':'badge-amber','Pending':'badge-gray'}[v] || 'badge-gray';
    }
    function scoreColor(s) { return s >= 75 ? 'var(--green)' : s >= 50 ? 'var(--amber)' : 'var(--red)'; }

    let allRows = [];
    fetch('/backend/api/factories.php')
      .then(r => r.json())
      .then(res => {
        if (!res.success) { showToast('Failed to load factories', 'error'); return; }
        allRows = res.data;
        render(allRows);
      })
      .catch(() => showToast('Network error', 'error'));

    function render(rows) {
      const tbody = document.getElementById('tbody');
      if (!rows.length) {
        tbody.innerHTML = '<tr><td colspan="9" class="empty-state">No factories found.</td></tr>';
        return;
      }
      tbody.innerHTML = rows.map(r =>
        `<tr>
          <td><strong>${r.FACTORY_NAME}</strong></td>
          <td>${r.REGISTRATION_NO}</td>
          <td>${r.DISTRICT}</td>
          
          <td>${r.TOTAL_WORKERS}</td>
          <td>
            <div class="score-bar-container">
              <div class="score-bar"><div class="score-bar-fill" style="width:${r.COMPLIANCE_SCORE}%;background:${scoreColor(r.COMPLIANCE_SCORE)}"></div></div>
              <span style="font-weight:700;color:${scoreColor(r.COMPLIANCE_SCORE)};min-width:36px">${r.COMPLIANCE_SCORE}</span>
            </div>
          </td>
          <td><span class="badge ${badgeClass(r.COMPLIANCE_STATUS)}">${r.COMPLIANCE_STATUS}</span></td>
          <td>${r.LAST_AUDIT_DATE || '—'}</td>
          <td>${r.NEXT_AUDIT_DATE || '—'}</td>
        </tr>`
      ).join('');
    }

    document.getElementById('search').addEventListener('input', function() {
      const q = this.value.toLowerCase();
      render(allRows.filter(r =>
        r.FACTORY_NAME.toLowerCase().includes(q) ||
        r.DISTRICT.toLowerCase().includes(q) ||
        r.COMPLIANCE_STATUS.toLowerCase().includes(q)
      ));
    });
  </script>
</body>
</html>
