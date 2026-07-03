<?php
require_once '../../../backend/includes/auth_check.php';
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "admin") { header("Location: /frontend/index.html"); exit; }
$activePage = 'dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GarmentGuard - Dashboard</title>
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
        <h2 class="page-title">Dashboard</h2>
        <div class="user-profile-menu">
          <span style="font-weight:500;color:var(--text-secondary);"><?php echo htmlspecialchars($_SESSION['full_name']); ?> (<?php echo htmlspecialchars($_SESSION['role']); ?>)</span>
          <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?></div>
        </div>
      </div>

      <!-- Stat Cards -->
      <div class="stats-grid" id="stats-grid">
        <div class="stat-card"><div class="stat-icon">🏭</div><div class="stat-value" id="stat-factories">…</div><div class="stat-label">Total Factories</div></div>
        <div class="stat-card"><div class="stat-icon">👷</div><div class="stat-value" id="stat-workers">…</div><div class="stat-label">Active Workers</div></div>
        <div class="stat-card"><div class="stat-icon">📋</div><div class="stat-value" id="stat-audits">…</div><div class="stat-label">Total Audits</div></div>
        <div class="stat-card stat-alert"><div class="stat-icon">📣</div><div class="stat-value" id="stat-grievances">…</div><div class="stat-label">Open Grievances</div></div>
        <div class="stat-card stat-alert"><div class="stat-icon">⚠️</div><div class="stat-value" id="stat-alerts">…</div><div class="stat-label">Safety Alerts</div></div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:24px;">
        <!-- Compliance Breakdown -->
        <div class="card">
          <h3 class="card-title">Compliance Status Breakdown</h3>
          <div id="compliance-breakdown"><p style="color:var(--text-secondary)">Loading…</p></div>
        </div>
        <!-- Top Factories -->
        <div class="card">
          <h3 class="card-title">Top Factories by Score</h3>
          <div id="top-factories"><p style="color:var(--text-secondary)">Loading…</p></div>
        </div>
      </div>

      <!-- Recent Audits -->
      <div class="card">
        <h3 class="card-title">Recent Audits</h3>
        <div class="table-responsive">
          <table class="table">
            <thead><tr><th>Factory</th><th>Audit Date</th><th>Score</th><th>Result</th></tr></thead>
            <tbody id="recent-audits-body"><tr><td colspan="4" style="color:var(--text-secondary);text-align:center">Loading…</td></tr></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <script src="../../assets/js/toast.js"></script>
  <script>
    function badgeClass(val) {
      const map = {
        'Compliant':'badge-green','At Risk':'badge-amber','Non-Compliant':'badge-red',
        'Review Needed':'badge-amber','Pending':'badge-gray',
        'Pass':'badge-green','Fail':'badge-red'
      };
      return map[val] || 'badge-gray';
    }

    function scoreColor(s) {
      if (s >= 75) return 'var(--green)';
      if (s >= 50) return 'var(--amber)';
      return 'var(--red)';
    }

    fetch('/backend/api/dashboard.php')
      .then(r => r.json())
      .then(res => {
        if (!res.success) { showToast('Failed to load dashboard', 'error'); return; }
        const d = res.data;

        document.getElementById('stat-factories').textContent   = d.total_factories;
        document.getElementById('stat-workers').textContent     = d.total_workers;
        document.getElementById('stat-audits').textContent      = d.total_audits;
        document.getElementById('stat-grievances').textContent  = d.open_grievances;
        document.getElementById('stat-alerts').textContent      = d.unack_alerts;

        // Compliance breakdown
        const cb = document.getElementById('compliance-breakdown');
        cb.innerHTML = d.compliance_breakdown.map(r =>
          `<div style="display:flex;justify-content:space-between;align-items:center;padding:10px 0;border-bottom:1px solid var(--border-color)">
            <span class="badge ${badgeClass(r.STATUS)}">${r.STATUS}</span>
            <span style="font-weight:600">${r.CNT} factories</span>
          </div>`
        ).join('');

        // Top factories
        const tf = document.getElementById('top-factories');
        tf.innerHTML = d.top_factories.map(r =>
          `<div style="margin-bottom:14px">
            <div style="display:flex;justify-content:space-between;margin-bottom:6px">
              <span style="font-size:14px">${r.FACTORY_NAME}</span>
              <span style="font-weight:700;color:${scoreColor(r.COMPLIANCE_SCORE)}">${r.COMPLIANCE_SCORE}</span>
            </div>
            <div class="score-bar"><div class="score-bar-fill" style="width:${r.COMPLIANCE_SCORE}%;background:${scoreColor(r.COMPLIANCE_SCORE)}"></div></div>
          </div>`
        ).join('');

        // Recent audits
        const tbody = document.getElementById('recent-audits-body');
        tbody.innerHTML = d.recent_audits.map(r =>
          `<tr>
            <td>${r.FACTORY_NAME}</td>
            <td>${r.AUDIT_DATE}</td>
            <td><span style="font-weight:700;color:${scoreColor(r.SCORE || 0)}">${r.SCORE != null ? r.SCORE : 'N/A'}</span></td>
            <td><span class="badge ${badgeClass(r.RESULT)}">${r.RESULT}</span></td>
          </tr>`
        ).join('');
      })
      .catch(() => showToast('Network error loading dashboard', 'error'));
  </script>
</body>
</html>
