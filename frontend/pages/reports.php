<?php
$activePage = 'reports';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../../backend/config/db.php';
require_once '../../backend/includes/helpers.php';
authCheck();

if (!in_array($_SESSION['role'], ['admin', 'compliance_officer'])) {
    header('Location: dashboard.php');
    exit();
}

$role = $_SESSION['role'];
$fullName = $_SESSION['full_name'] ?? 'User';

// Simplified nav menu based on existing logic for this page
$navMenu = [];
if ($role === 'admin') {
    $navMenu = [
        'dashboard' => ['📊 Dashboard', 'dashboard.php'],
        'reports' => ['📈 Reports', 'reports.php'],
    ];
} elseif ($role === 'compliance_officer') {
    $navMenu = [
        'dashboard' => ['📊 Dashboard', 'dashboard.php'],
        'reports' => ['📈 Reports', 'reports.php'],
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Reports & Analytics - GarmentGuard</title>
  <link rel="stylesheet" href="../../assets/css/style.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    .filters-bar {
      display: flex;
      gap: 16px;
      margin-bottom: 24px;
      align-items: flex-end;
      flex-wrap: wrap;
    }
    .filter-group {
      display: flex;
      flex-direction: column;
      gap: 6px;
    }
    .filter-select, .filter-input {
      background-color: var(--bg-secondary);
      border: 1px solid var(--border-color);
      border-radius: 8px;
      color: var(--text-primary);
      padding: 10px 14px;
      font-size: 14px;
      outline: none;
    }
    .charts-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 24px;
      margin-bottom: 32px;
    }
    .chart-card {
      background: var(--bg-secondary);
      border: 1px solid var(--border-color);
      border-radius: 12px;
      padding: 24px;
    }
    .chart-card.full-width {
      grid-column: 1 / -1;
    }
    .export-actions {
      display: flex;
      gap: 12px;
      margin-bottom: 24px;
    }
    .section-title {
      font-size: 18px;
      font-weight: 600;
      margin-bottom: 16px;
    }
  </style>
</head>
<body>
  <div class="app-container">
    <div class="sidebar" id="sidebar">
      <div class="brand">
        <span class="brand-title">GarmentGuard</span>
        <span class="brand-subtitle">Analytics</span>
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
        <a href="../../backend/auth/logout.php" class="nav-link">🚪 Logout</a>
      </div>
    </div>

    <div class="main-content">
      <div class="top-bar">
        <h2 class="page-title">Reports & Analytics</h2>
        <div class="user-profile-menu">
          <span style="font-weight:500;color:var(--text-secondary);">
            <?php echo htmlspecialchars($fullName); ?> (<?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $role))); ?>)
          </span>
          <div class="user-avatar"><?php echo strtoupper(substr($fullName, 0, 1)); ?></div>
        </div>
      </div>

      <div class="card">
        <div class="filters-bar">
          <div class="filter-group">
            <label>Factory</label>
            <select class="filter-select" id="factory-filter">
              <option value="">All Factories</option>
            </select>
          </div>
          <div class="filter-group">
            <label>From Date</label>
            <input type="date" class="filter-input" id="from-date">
          </div>
          <div class="filter-group">
            <label>To Date</label>
            <input type="date" class="filter-input" id="to-date">
          </div>
          <button class="btn btn-primary" onclick="generateReports()">Generate</button>
        </div>

        <div class="export-actions">
          <button class="btn btn-secondary" onclick="exportData('factory')">Export Factory Report (CSV)</button>
          <button class="btn btn-secondary" onclick="exportData('salary')">Export Salary Report (CSV)</button>
          <button class="btn btn-secondary" onclick="exportData('audit')">Export Audit Report (CSV)</button>
        </div>
      </div>

      <div class="charts-grid">
        <div class="chart-card">
          <h3 class="section-title">Audit Score Trend</h3>
          <canvas id="auditTrendChart"></canvas>
        </div>
        <div class="chart-card">
          <h3 class="section-title">Grievance Breakdown</h3>
          <canvas id="grievanceChart"></canvas>
        </div>
        <div class="chart-card full-width">
          <h3 class="section-title">Salary by Factory</h3>
          <canvas id="salaryChart"></canvas>
        </div>
      </div>

      <div class="card">
        <h3 class="section-title">District Ranking</h3>
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>District</th>
                <th>Factories</th>
                <th>Avg Score</th>
                <th>Compliant</th>
                <th>Non-Compliant</th>
              </tr>
            </thead>
            <tbody id="district-tbody">
              <tr><td colspan="5" style="text-align:center;">Loading ranking...</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <script src="../../assets/js/toast.js"></script>
  <script>
    let auditChartInst = null;
    let grievanceChartInst = null;
    let salaryChartInst = null;

    let exportCache = {
      factory: [],
      salary: [],
      audit: []
    };

    function loadFactoriesFilter() {
      fetch('/backend/api/reports.php?type=factories_dropdown')
        .then(res => res.json())
        .then(res => {
          if (res.success && res.data) {
            const select = document.getElementById('factory-filter');
            res.data.forEach(f => {
              const opt = document.createElement('option');
              opt.value = f.FACTORY_ID;
              opt.textContent = f.FACTORY_NAME;
              select.appendChild(opt);
            });
          }
        });
    }

    function buildQueryParams() {
      const f = document.getElementById('factory-filter').value;
      const from = document.getElementById('from-date').value;
      const to = document.getElementById('to-date').value;
      let q = '';
      if (f) q += `&factory_id=${encodeURIComponent(f)}`;
      if (from) q += `&from_date=${encodeURIComponent(from)}`;
      if (to) q += `&to_date=${encodeURIComponent(to)}`;
      return q;
    }

    function generateReports() {
      const q = buildQueryParams();
      
      // 1. Audit Trend (Line Chart)
      fetch(`/backend/api/reports.php?type=audit_trend${q}`)
        .then(res => res.json())
        .then(res => {
          if (res.success) {
            exportCache.audit = res.data;
            const labels = res.data.map(d => d.audit_date);
            const data = res.data.map(d => d.score);
            
            if (auditChartInst) auditChartInst.destroy();
            const ctx = document.getElementById('auditTrendChart').getContext('2d');
            auditChartInst = new Chart(ctx, {
              type: 'line',
              data: {
                labels,
                datasets: [{
                  label: 'Audit Score',
                  data,
                  borderColor: '#10b981',
                  tension: 0.1,
                  fill: false
                }]
              },
              options: {
                responsive: true,
                plugins: {
                  legend: { display: false }
                }
              }
            });
          }
        });

      // 2. Salary Summary (Bar Chart)
      fetch(`/backend/api/reports.php?type=salary_summary${q}`)
        .then(res => res.json())
        .then(res => {
          if (res.success) {
            exportCache.salary = res.data;
            const labels = res.data.map(d => d.factory_name);
            const data = res.data.map(d => d.total_net_salary);
            
            if (salaryChartInst) salaryChartInst.destroy();
            const ctx = document.getElementById('salaryChart').getContext('2d');
            salaryChartInst = new Chart(ctx, {
              type: 'bar',
              data: {
                labels,
                datasets: [{
                  label: 'Total Net Salary',
                  data,
                  backgroundColor: '#3b82f6'
                }]
              },
              options: { responsive: true }
            });
          }
        });

      // 3. Grievance Breakdown (Horizontal Bar)
      fetch(`/backend/api/reports.php?type=grievance_breakdown${q}`)
        .then(res => res.json())
        .then(res => {
          if (res.success) {
            const labels = res.data.map(d => d.category + ' (' + d.status + ')');
            const data = res.data.map(d => d.cnt);
            
            if (grievanceChartInst) grievanceChartInst.destroy();
            const ctx = document.getElementById('grievanceChart').getContext('2d');
            grievanceChartInst = new Chart(ctx, {
              type: 'bar',
              data: {
                labels,
                datasets: [{
                  label: 'Grievance Count',
                  data,
                  backgroundColor: '#f59e0b'
                }]
              },
              options: { 
                responsive: true,
                indexAxis: 'y'
              }
            });
          }
        });

      // 4. District Ranking (Table)
      fetch(`/backend/api/reports.php?type=district_ranking`)
        .then(res => res.json())
        .then(res => {
          if (res.success) {
            exportCache.factory = res.data;
            const tbody = document.getElementById('district-tbody');
            if(res.data.length === 0) {
              tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;">No data found.</td></tr>';
              return;
            }
            tbody.innerHTML = res.data.map(d => `
              <tr>
                <td><strong>${d.district}</strong></td>
                <td>${d.cnt}</td>
                <td>${d.avg_score}</td>
                <td><span class="badge badge-green">${d.compliant}</span></td>
                <td><span class="badge badge-red">${d.non_compliant}</span></td>
              </tr>
            `).join('');
          }
        });
    }

    function exportData(type) {
      const data = exportCache[type];
      if (!data || data.length === 0) {
        showToast('No data to export', 'error');
        return;
      }
      
      const keys = Object.keys(data[0]);
      let csv = keys.join(',') + '\\n';
      
      data.forEach(row => {
        csv += keys.map(k => `"${row[k] || ''}"`).join(',') + '\\n';
      });
      
      const blob = new Blob([csv], { type: 'text/csv' });
      const url = window.URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.setAttribute('hidden', '');
      a.setAttribute('href', url);
      a.setAttribute('download', `${type}_report.csv`);
      document.body.appendChild(a);
      a.click();
      document.body.removeChild(a);
      showToast('Export successful', 'success');
    }

    document.addEventListener('DOMContentLoaded', () => {
      loadFactoriesFilter();
      generateReports(); // Initial load
    });
  </script>
</body>
</html>
