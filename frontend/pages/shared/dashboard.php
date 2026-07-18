<?php
// Direct access security guard
if (!isset($activePage) || $activePage !== 'dashboard') {
    header("HTTP/1.1 403 Forbidden");
    exit("Direct access forbidden");
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$role     = $_SESSION['role']      ?? '';
$fullName = $_SESSION['full_name'] ?? 'User';

// Navigation menu by role
$navMenu = [];
if ($role === 'admin') {
    $navMenu = [
        'dashboard'      => ['📊 Dashboard',        'dashboard.php'],
        'factories'      => ['🏭 Factories',         'factories.php'],
        'workers'        => ['👷 Workers',           'workers.php'],
        'audits'         => ['📋 Audits',            'audits.php'],
        'grievances'     => ['📣 Grievances',        'grievances.php'],
        'salary'         => ['💰 Salaries',          'salary.php'],
        'certifications' => ['🏅 Certifications',    'certifications.php'],
        'equipment'      => ['🧯 Safety Equipment',  'equipment.php'],
        'buyer'          => ['🛒 Buyers',            'buyer.php'],
        'reports'        => ['📈 Reports',           'reports.php'],
        'users'          => ['👤 Users',             'users.php'],
    ];
} elseif ($role === 'compliance_officer') {
    $navMenu = [
        'dashboard'      => ['📊 Dashboard',        'dashboard.php'],
        'factories'      => ['🏭 Factories',         'factories.php'],
        'workers'        => ['👷 Workers',           'workers.php'],
        'audits'         => ['📋 Audits',            'audits.php'],
        'grievances'     => ['📣 Grievances',        'grievances.php'],
        'salary'         => ['💰 Salaries',          'salary.php'],
        'certifications' => ['🏅 Certifications',    'certifications.php'],
        'equipment'      => ['🧯 Safety Equipment',  'equipment.php'],
        'buyer'          => ['🛒 Buyers',            'buyer.php'],
        'reports'        => ['📈 Reports',           'reports.php'],
    ];
} elseif ($role === 'inspector') {
    $navMenu = [
        'dashboard' => ['📊 Dashboard',       'dashboard.php'],
        'factories' => ['🏭 Factories',        'factories.php'],
        'audits'    => ['📋 Audits',           'audits.php'],
        'certifications' => ['🏅 Certifications', 'certifications.php'],
        'equipment' => ['🧯 Safety Equipment', 'equipment.php'],
    ];
} elseif ($role === 'buyer_user' || $role === 'buyer') {
    $navMenu = [
        'dashboard' => ['📊 Dashboard',       'dashboard.php'],
        'factories' => ['🏭 Factories',        'factories.php'],
        'audits'    => ['📋 Audits',           'audits.php'],
        'certifications' => ['🏅 Certifications', 'certifications.php'],
        'reports'   => ['📈 Reports',          'reports.php'],
    ];
} elseif ($role === 'worker') {
    $navMenu = [
        'dashboard'  => ['📊 Dashboard',       'dashboard.php'],
        'grievances' => ['📣 Grievances',      'grievances.php'],
        'salary'     => ['💰 Salaries',        'salary.php'],
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GarmentGuard – Dashboard</title>
  <link rel="stylesheet" href="../../assets/css/style.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    .dashboard-row {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
      gap: 24px;
      margin-bottom: 24px;
    }
    @media (max-width: 992px) {
      .dashboard-row { grid-template-columns: 1fr; }
    }
    .clickable-row { cursor: pointer; transition: background-color var(--transition-speed); }
    .clickable-row:hover { background-color: rgba(255,255,255,0.05) !important; }
    .activity-dot { display: inline-block; width: 12px; height: 12px; border-radius: 50%; vertical-align: middle; }
    .activity-dot.grievance { background-color: var(--amber); box-shadow: 0 0 8px var(--amber); }
    .activity-dot.audit { background-color: var(--blue); box-shadow: 0 0 8px var(--blue); }
  </style>
</head>
<body>
<div class="app-container">

  <!-- ── Sidebar ──────────────────────────────────────── -->
  <div class="sidebar" id="sidebar">
    <div class="brand">
      <span class="brand-title">GarmentGuard</span>
      <span class="brand-subtitle">Compliance System</span>
    </div>
    <ul class="nav-menu">
      <?php foreach ($navMenu as $key => $item): ?>
        <li>
          <a href="<?php echo htmlspecialchars($item[1]); ?>"
             class="nav-link <?php echo $key === 'dashboard' ? 'active' : ''; ?>">
            <?php echo htmlspecialchars($item[0]); ?>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
    <div class="nav-footer">
      <a href="../../../backend/auth/logout.php" class="nav-link">🚪 Logout</a>
    </div>
  </div>

  <!-- ── Main Content ──────────────────────────────────── -->
  <div class="main-content">
    <div class="top-bar">
      <h2 class="page-title">Dashboard</h2>
      <div class="user-profile-menu">
        <span style="font-weight:500;color:var(--text-secondary);">
          <?php echo htmlspecialchars($fullName); ?> (<?php echo htmlspecialchars(ucwords(str_replace('_',' ',$role))); ?>)
        </span>
        <div class="user-avatar"><?php echo strtoupper(substr($fullName,0,1)); ?></div>
      </div>
    </div>

    <!-- SECTION 1 — TOP METRIC CARDS -->
    <div class="stats-grid" style="margin-bottom: 24px;">
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
        <div class="stat-icon" style="color:var(--amber);">⚠️</div>
        <div class="stat-value" id="card-equipment-expiring">—</div>
        <div class="stat-label">Equipment Expiring (30 days)</div>
      </div>
    </div>

    <!-- SECTION 2 — CHARTS ROW -->
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

    <!-- SECTION 3 — TWO TABLES SIDE BY SIDE -->
    <div class="dashboard-row">
      <!-- Left Table: Factory Compliance Ranking -->
      <div class="card">
        <h3 class="card-title">🏆 Top 5 Factories by Compliance Score</h3>
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th style="width:50px; text-align:center;">Rank</th>
                <th>Factory</th>
                <th>District</th>
                <th>Score</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody id="top-factories-tbody">
              <tr><td colspan="5" style="text-align:center;color:var(--text-secondary);padding:24px;">Loading rankings…</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <!-- Right Table: Recent Activity Feed -->
      <div class="card">
        <h3 class="card-title">🔔 Recent Activity</h3>
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th style="width:60px; text-align:center;">Type</th>
                <th>Description</th>
                <th>Date</th>
              </tr>
            </thead>
            <tbody id="recent-activity-tbody">
              <tr><td colspan="3" style="text-align:center;color:var(--text-secondary);padding:24px;">Loading activity feed…</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="../../assets/js/toast.js"></script>
  <script src="../../assets/js/table-utils.js"></script>
<script>
document.addEventListener("DOMContentLoaded", () => {
  function scoreColor(s) {
    if (s >= 75) return 'var(--green)';
    if (s >= 40) return 'var(--amber)';
    return 'var(--red)';
  }

  function badgeClass(status) {
    return {
      'Compliant': 'badge-green',
      'At Risk': 'badge-amber',
      'Non-Compliant': 'badge-red',
      'Review Needed': 'badge-amber',
      'Pending': 'badge-gray'
    }[status] || 'badge-gray';
  }

  fetch('/backend/api/dashboard.php')
    .then(r => r.json())
    .then(res => {
      if (!res.success) {
        showToast('Failed to load dashboard metrics', 'error');
        return;
      }
      const data = res.data;

      // Populate Metric Cards
      document.getElementById('card-total-factories').textContent = data.totalFactories || '0';
      document.getElementById('card-compliant-factories').textContent = `${data.compliantCount || '0'} / ${data.totalFactories || '0'}`;
      document.getElementById('card-open-grievances').textContent = data.openGrievances || '0';
      document.getElementById('card-equipment-expiring').textContent = data.equipmentExpiring || '0';

      // ── Chart 1: Compliance Distribution (Donut Chart) ──
      const donutCtx = document.getElementById('compliance-donut-chart').getContext('2d');
      
      // Calculate counts from distribution
      let complCount = 0;
      let riskCount = 0;
      let nonComplCount = 0;
      let pendCount = 0;
      
      if (data.complianceDistribution) {
        data.complianceDistribution.forEach(d => {
          const status = String(d.STATUS || '').trim();
          const count = parseInt(d.CNT || 0);
          if (status === 'Compliant') complCount = count;
          else if (status === 'At Risk') riskCount = count;
          else if (status === 'Non-Compliant') nonComplCount = count;
          else if (status === 'Pending' || status === 'Review Needed') pendCount += count;
        });
      }
      
      new Chart(donutCtx, {
        type: 'doughnut',
        data: {
          labels: ['Compliant', 'At Risk', 'Non-Compliant', 'Pending'],
          datasets: [{
            data: [complCount, riskCount, nonComplCount, pendCount],
            backgroundColor: ['#1D9E75', '#BA7517', '#E24B4A', '#888780'],
            borderWidth: 0
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'bottom',
              labels: {
                color: '#94A3B8',
                font: { family: 'Outfit', size: 12 }
              }
            }
          }
        }
      });

      // ── Chart 2: Audit Score Trend (Line Chart) ──
      const lineCtx = document.getElementById('audit-trend-line-chart').getContext('2d');
      const trendData = data.auditTrend || [];
      const labels = trendData.map(a => a.AUDIT_DATE);
      const scores = trendData.map(a => parseFloat(a.SCORE || 0));
      const pointColors = scores.map(s => s >= 75 ? '#1D9E75' : s >= 40 ? '#BA7517' : '#E24B4A');

      new Chart(lineCtx, {
        type: 'line',
        data: {
          labels,
          datasets: [{
            label: 'Score',
            data: scores,
            borderColor: '#378ADD',
            backgroundColor: 'rgba(55,138,221,0.1)',
            borderWidth: 2.5,
            fill: true,
            tension: 0.35,
            pointBackgroundColor: pointColors,
            pointBorderColor: pointColors,
            pointRadius: 6,
            pointHoverRadius: 8
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            y: {
              min: 0,
              max: 100,
              grid: { color: 'rgba(255,255,255,0.05)' },
              ticks: { color: '#94A3B8' }
            },
            x: {
              grid: { display: false },
              ticks: { color: '#94A3B8' }
            }
          },
          plugins: {
            legend: { display: false }
          }
        },
        plugins: [{
          id: 'thresholdLines',
          afterDraw(chart) {
            const { ctx, chartArea: { left, right }, scales: { y } } = chart;
            ctx.save();
            ctx.setLineDash([5, 5]);
            ctx.lineWidth = 1.5;
            
            // 75 line (Green)
            ctx.strokeStyle = '#1D9E75';
            ctx.beginPath();
            ctx.moveTo(left, y.getPixelForValue(75));
            ctx.lineTo(right, y.getPixelForValue(75));
            ctx.stroke();
            ctx.fillStyle = '#1D9E75';
            ctx.font = '10px Outfit,sans-serif';
            ctx.fillText('Target (75)', left + 6, y.getPixelForValue(75) - 6);
            
            // 40 line (Red)
            ctx.strokeStyle = '#E24B4A';
            ctx.beginPath();
            ctx.moveTo(left, y.getPixelForValue(40));
            ctx.lineTo(right, y.getPixelForValue(40));
            ctx.stroke();
            ctx.fillStyle = '#E24B4A';
            ctx.fillText('Critical (40)', left + 6, y.getPixelForValue(40) - 6);
            
            ctx.restore();
          }
        }]
      });

      // ── Populate Table Left: Top Factories ──
      const topBody = document.getElementById('top-factories-tbody');
      if (data.topFactories && data.topFactories.length > 0) {
        topBody.innerHTML = data.topFactories.map((f, idx) => {
          const score = parseFloat(f.COMPLIANCE_SCORE) || 0;
          const color = scoreColor(score);
          return `
            <tr class="clickable-row" onclick="window.location.href='factory_detail.php?id=${f.FACTORY_ID}'">
              <td style="font-weight:700; color:var(--text-secondary); text-align:center;">${idx + 1}</td>
              <td><strong>${escHtml(f.FACTORY_NAME)}</strong></td>
              <td>${escHtml(f.DISTRICT)}</td>
              <td>
                <div style="display:flex; align-items:center; gap:8px;">
                  <span style="width:9px; height:9px; border-radius:50%; background:${color}; display:inline-block; flex-shrink:0;"></span>
                  <span style="font-weight:700; color:${color};">${score}%</span>
                </div>
              </td>
              <td><span class="badge ${badgeClass(f.COMPLIANCE_STATUS)}">${f.COMPLIANCE_STATUS}</span></td>
            </tr>
          `;
        }).join('');
      } else {
        topBody.innerHTML = `<tr><td colspan="5" style="text-align:center; color:var(--text-secondary); padding:24px;">No factory records found.</td></tr>`;
      }

      // ── Populate Table Right: Recent Activity Feed ──
      const activityBody = document.getElementById('recent-activity-tbody');
      if (data.recentActivity && data.recentActivity.length > 0) {
        activityBody.innerHTML = data.recentActivity.map(item => `
          <tr>
            <td style="text-align:center; vertical-align:middle;">
              <span class="activity-dot ${item.TYPE}"></span>
            </td>
            <td>${escHtml(item.DESCRIPTION)}</td>
            <td style="color:var(--text-secondary); font-size:13px; white-space:nowrap;">${item.EVENT_DATE}</td>
          </tr>
        `).join('');
      } else {
        activityBody.innerHTML = `<tr><td colspan="3" style="text-align:center; color:var(--text-secondary); padding:24px;">No recent activity found.</td></tr>`;
      }
    })
    .catch(err => {
      showToast('Failed to load dashboard metrics', 'error');
      console.error(err);
    });
});

function escHtml(s) {
  return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>
</body>
</html>
