<?php
require_once '../../../backend/includes/auth_check.php';
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "compliance_officer") { header("Location: /frontend/index.html"); exit; }
$activePage = 'reports';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GarmentGuard - Reports</title>
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
        <h2 class="page-title">Reports</h2>
        <div class="user-profile-menu">
          <span style="font-weight:500;color:var(--text-secondary);"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
          <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?></div>
        </div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;margin-bottom:24px;">
        <!-- Compliance by District -->
        <div class="card">
          <h3 class="card-title">📍 Compliance by District</h3>
          <div class="table-responsive">
            <table class="table">
              <thead><tr><th>District</th><th>Factories</th><th>Compliant</th><th>Avg Score</th></tr></thead>
              <tbody id="division-tbody"><tr><td colspan="4" style="color:var(--text-secondary);text-align:center">Loading…</td></tr></tbody>
            </table>
          </div>
        </div>
        <!-- Audit Summary -->
        <div class="card">
          <h3 class="card-title">📋 Audit Result Summary</h3>
          <div id="audit-summary"><p style="color:var(--text-secondary)">Loading…</p></div>
        </div>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">
        <!-- Grievances by Category -->
        <div class="card">
          <h3 class="card-title">📣 Grievances by Category</h3>
          <div class="table-responsive">
            <table class="table">
              <thead><tr><th>Category</th><th>Total</th><th>Resolved</th><th>Open</th></tr></thead>
              <tbody id="grievance-tbody"><tr><td colspan="4" style="color:var(--text-secondary);text-align:center">Loading…</td></tr></tbody>
            </table>
          </div>
        </div>
        <!-- Salary by Factory -->
        <div class="card">
          <h3 class="card-title">💰 Salary Totals by Factory</h3>
          <div class="table-responsive">
            <table class="table">
              <thead><tr><th>Factory</th><th>Workers Paid</th><th>Total Net Salary</th></tr></thead>
              <tbody id="salary-tbody"><tr><td colspan="3" style="color:var(--text-secondary);text-align:center">Loading…</td></tr></tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="../../assets/js/toast.js"></script>
  <script>
    function scoreColor(s) { return s >= 75 ? 'var(--green)' : s >= 50 ? 'var(--amber)' : 'var(--red)'; }
    function fmt(n) { return '৳ ' + Number(n).toLocaleString(); }
    const auditColors = {'Pass':'badge-green','Fail':'badge-red','Pending':'badge-gray'};

    fetch('/backend/api/reports.php')
      .then(r => r.json())
      .then(res => {
        if (!res.success) { showToast('Failed to load reports', 'error'); return; }
        const d = res.data;

        // Division compliance
        document.getElementById('division-tbody').innerHTML = d.division_stats.map(r =>
          `<tr>
            <td>${r.DIVISION}</td>
            <td>${r.TOTAL_FACTORIES}</td>
            <td><span style="color:var(--green);font-weight:600">${r.COMPLIANT}</span></td>
            <td><span style="color:${scoreColor(r.AVG_SCORE)};font-weight:700">${r.AVG_SCORE}</span></td>
          </tr>`
        ).join('');

        // Audit summary
        document.getElementById('audit-summary').innerHTML = d.audit_stats.map(r =>
          `<div style="display:flex;justify-content:space-between;align-items:center;padding:12px 0;border-bottom:1px solid var(--border-color)">
            <span class="badge ${auditColors[r.RESULT] || 'badge-gray'}">${r.RESULT}</span>
            <span style="font-weight:700;font-size:20px">${r.CNT}</span>
          </div>`
        ).join('');

        // Grievances by category
        document.getElementById('grievance-tbody').innerHTML = d.grievance_stats.map(r =>
          `<tr>
            <td><span class="badge badge-blue">${r.CATEGORY}</span></td>
            <td>${r.TOTAL}</td>
            <td style="color:var(--green)">${r.RESOLVED}</td>
            <td style="color:var(--red)">${r.OPEN_COUNT}</td>
          </tr>`
        ).join('');

        // Salary by factory
        document.getElementById('salary-tbody').innerHTML = d.salary_stats.map(r =>
          `<tr>
            <td>${r.FACTORY_NAME}</td>
            <td>${r.WORKER_COUNT}</td>
            <td style="font-weight:700;color:var(--green)">${fmt(r.TOTAL_NET)}</td>
          </tr>`
        ).join('');
      })
      .catch(() => showToast('Network error loading reports', 'error'));
  </script>
</body>
</html>
