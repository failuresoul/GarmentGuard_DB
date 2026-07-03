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
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    .dashboard-row {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
      gap: 24px;
      margin-bottom: 24px;
    }
    @media (max-width: 992px) { .dashboard-row { grid-template-columns: 1fr; } }
    .clickable-row { cursor: pointer; transition: background-color var(--transition-speed); }
    .clickable-row:hover { background-color: rgba(255,255,255,0.05) !important; }
    .activity-dot { display: inline-block; width: 12px; height: 12px; border-radius: 50%; vertical-align: middle; }
    .activity-dot.grievance { background-color: var(--amber); box-shadow: 0 0 8px var(--amber); }
    .activity-dot.audit { background-color: var(--blue); box-shadow: 0 0 8px var(--blue); }
  </style>
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
          <span style="font-weight:500;color:var(--text-secondary);">
            <?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Admin'); ?> (<?php echo htmlspecialchars($_SESSION['role'] ?? 'admin'); ?>)
          </span>
          <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['full_name'] ?? 'A', 0, 1)); ?></div>
        </div>
      </div>

      <!-- Metric Cards -->
      <div class="stats-grid">
        <div class="stat-card" style="border-left:4px solid var(--blue);">
          <div class="stat-icon" style="color:var(--blue);">🏭</div>
          <div class="stat-value" id="card-total-factories">—</div>
          <div class="stat-label">Total Factories</div>
        </div>
        <div class="stat-card" style="border-left:4px solid var(--green);">
          <div class="stat-icon" style="color:var(--green);">✅</div>
          <div class="stat-value" id="card-compliant-factories">—</div>
          <div class="stat-label">Compliant Factories</div>
        </div>
        <div class="stat-card" style="border-left:4px solid var(--red);">
          <div class="stat-icon" style="color:var(--red);">📣</div>
          <div class="stat-value" id="card-open-grievances">—</div>
          <div class="stat-label">Open Grievances</div>
        </div>
        <div class="stat-card" style="border-left:4px solid var(--amber);">
          <div class="stat-icon" style="color:var(--amber);">🧯</div>
          <div class="stat-value" id="card-equipment-expiring">—</div>
          <div class="stat-label">Equipment Expiring (30 days)</div>
        </div>
      </div>

      <!-- Charts Row -->
      <div class="dashboard-row">
        <div class="card">
          <h3 class="card-title">🍩 Compliance Distribution</h3>
          <div style="position:relative;height:260px;display:flex;justify-content:center;align-items:center;">
            <canvas id="compliance-donut-chart"></canvas>
          </div>
        </div>
        <div class="card">
          <h3 class="card-title">📈 Last 6 Audit Scores</h3>
          <div style="position:relative;height:260px;">
            <canvas id="audit-trend-line-chart"></canvas>
          </div>
        </div>
      </div>

      <!-- Tables Row -->
      <div class="dashboard-row">
        <div class="card">
          <h3 class="card-title">🏆 Top 5 Factories by Compliance Score</h3>
          <div class="table-responsive">
            <table class="table">
              <thead><tr><th>Rank</th><th>Factory</th><th>District</th><th>Score</th><th>Status</th></tr></thead>
              <tbody id="top-factories-tbody">
                <tr><td colspan="5" style="text-align:center;color:var(--text-secondary)">Loading…</td></tr>
              </tbody>
            </table>
          </div>
        </div>
        <div class="card">
          <h3 class="card-title">🔔 Recent Activity</h3>
          <div class="table-responsive">
            <table class="table">
              <thead><tr><th style="width:60px">Type</th><th>Description</th><th>Date</th></tr></thead>
              <tbody id="recent-activity-tbody">
                <tr><td colspan="3" style="text-align:center;color:var(--text-secondary)">Loading…</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="../../assets/js/toast.js"></script>
  <script>
    document.addEventListener("DOMContentLoaded", () => {
      function scoreColor(s) {
        if (s >= 75) return 'var(--green)';
        if (s >= 40) return 'var(--amber)';
        return 'var(--red)';
      }
      function badgeClass(status) {
        return {'Compliant':'badge-green','At Risk':'badge-amber','Non-Compliant':'badge-red','Review Needed':'badge-amber','Pending':'badge-gray'}[status] || 'badge-gray';
      }

      fetch('/backend/api/dashboard.php')
        .then(r => r.json())
        .then(res => {
          document.getElementById('card-total-factories').textContent = res.totalFactories || '0';
          document.getElementById('card-compliant-factories').textContent = `${res.compliantCount || '0'} / ${res.totalFactories || '0'}`;
          document.getElementById('card-open-grievances').textContent = res.openGrievances || '0';
          document.getElementById('card-equipment-expiring').textContent = res.equipmentExpiring || '0';

          
          const donutCtx = document.getElementById('compliance-donut-chart').getContext('2d');
          const complCount = parseInt(res.compliantCount || 0);
          const riskCount = parseInt(res.atRiskCount || 0);
          const nonComplCount = parseInt(res.nonCompliantCount || 0);
          const pendCount = Math.max(0, parseInt(res.totalFactories || 0) - (complCount + riskCount + nonComplCount));
          new Chart(donutCtx, {
            type: 'doughnut',
            data: {
              labels: ['Compliant', 'At Risk', 'Non-Compliant', 'Pending'],
              datasets: [{ data: [complCount, riskCount, nonComplCount, pendCount], backgroundColor: ['#1D9E75','#BA7517','#E24B4A','#888780'], borderWidth: 0 }]
            },
            options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right', labels: { color: '#94A3B8', font: { family: 'Outfit', size: 12 } } } } }
          });

          
          const lineCtx = document.getElementById('audit-trend-line-chart').getContext('2d');
          const trendData = [...(res.auditTrend || [])].reverse();
          const labels = trendData.map(a => a.AUDIT_DATE);
          const scores = trendData.map(a => parseFloat(a.SCORE || 0));
          const pointColors = scores.map(s => s >= 75 ? '#1D9E75' : s >= 40 ? '#BA7517' : '#E24B4A');
          new Chart(lineCtx, {
            type: 'line',
            data: { labels, datasets: [{ label: 'Score', data: scores, borderColor: '#378ADD', backgroundColor: 'rgba(55,138,221,0.1)', borderWidth: 2.5, fill: true, tension: 0.35, pointBackgroundColor: pointColors, pointBorderColor: pointColors, pointRadius: 6, pointHoverRadius: 8 }] },
            options: { responsive: true, maintainAspectRatio: false, scales: { y: { min: 0, max: 100, grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#94A3B8' } }, x: { grid: { display: false }, ticks: { color: '#94A3B8' } } }, plugins: { legend: { display: false } } },
            plugins: [{
              id: 'thresholdLines',
              afterDraw(chart) {
                const { ctx, chartArea: { left, right }, scales: { y } } = chart;
                ctx.save();
                ctx.setLineDash([5, 5]);
                ctx.lineWidth = 1.5;
                ctx.strokeStyle = '#1D9E75'; ctx.beginPath(); ctx.moveTo(left, y.getPixelForValue(75)); ctx.lineTo(right, y.getPixelForValue(75)); ctx.stroke();
                ctx.fillStyle = '#1D9E75'; ctx.font = '10px Outfit,sans-serif'; ctx.fillText('Target (75)', left + 6, y.getPixelForValue(75) - 6);
                ctx.strokeStyle = '#E24B4A'; ctx.beginPath(); ctx.moveTo(left, y.getPixelForValue(40)); ctx.lineTo(right, y.getPixelForValue(40)); ctx.stroke();
                ctx.fillStyle = '#E24B4A'; ctx.fillText('Critical (40)', left + 6, y.getPixelForValue(40) - 6);
                ctx.restore();
              }
            }]
          });

          
          const topBody = document.getElementById('top-factories-tbody');
          if (res.topFactories && res.topFactories.length > 0) {
            topBody.innerHTML = res.topFactories.map((f, i) => `
              <tr class="clickable-row" onclick="window.location.href='factory_detail.php?id=${f.FACTORY_ID}'">
                <td><strong>
                <td><strong>${f.FACTORY_NAME}</strong></td>
                <td>${f.DISTRICT}</td>
                <td>
                  <div class="score-bar-container">
                    <div class="score-bar"><div class="score-bar-fill" style="width:${f.COMPLIANCE_SCORE}%;background:${scoreColor(f.COMPLIANCE_SCORE)};"></div></div>
                    <span style="font-weight:700;color:${scoreColor(f.COMPLIANCE_SCORE)};min-width:32px;text-align:right">${f.COMPLIANCE_SCORE}%</span>
                  </div>
                </td>
                <td><span class="badge ${badgeClass(f.COMPLIANCE_STATUS)}">${f.COMPLIANCE_STATUS}</span></td>
              </tr>`).join('');
          } else {
            topBody.innerHTML = '<tr><td colspan="5" class="empty-state">No factory records found.</td></tr>';
          }

          
          const actBody = document.getElementById('recent-activity-tbody');
          if (res.recentActivity && res.recentActivity.length > 0) {
            actBody.innerHTML = res.recentActivity.map(item => `
              <tr>
                <td style="text-align:center;vertical-align:middle"><span class="activity-dot ${item.TYPE}"></span></td>
                <td>${item.DESCRIPTION}</td>
                <td style="color:var(--text-secondary);font-size:13px">${item.EVENT_DATE}</td>
              </tr>`).join('');
          } else {
            actBody.innerHTML = '<tr><td colspan="3" class="empty-state">No recent activity.</td></tr>';
          }
        })
        .catch(err => { showToast('Failed to load dashboard data', 'error'); console.error(err); });
    });
  </script>
</body>
</html>
