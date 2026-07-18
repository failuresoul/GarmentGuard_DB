<?php
// Direct access security guard
if (!isset($activePage) || $activePage !== 'factories') {
    header("HTTP/1.1 403 Forbidden");
    exit("Direct access forbidden");
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$role = $_SESSION['role'] ?? '';
$fullName = $_SESSION['full_name'] ?? 'User';

// Get factory ID
$factoryId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($factoryId <= 0) {
    header("Location: factories.php");
    exit;
}

// Define the navigation menu items for each role dynamically
$navMenu = [];
if ($role === 'admin') {
    $navMenu = [
        'dashboard' => ['📊 Dashboard', 'dashboard.php'],
        'factories' => ['🏭 Factories', 'factories.php'],
        'workers' => ['👷 Workers', 'workers.php'],
        'audits' => ['📋 Audits', 'audits.php'],
        'grievances' => ['📣 Grievances', 'grievances.php'],
        'salary' => ['💰 Salaries', 'salary.php'],
        'certifications' => ['🏅 Certifications', 'certifications.php'],
        'equipment' => ['🧯 Safety Equipment', 'equipment.php'],
        'buyer' => ['🛒 Buyers', 'buyer.php'],
        'reports' => ['📈 Reports', 'reports.php'],
        'users' => ['👤 Users', 'users.php'],
    ];
} elseif ($role === 'compliance_officer') {
    $navMenu = [
        'dashboard' => ['📊 Dashboard', 'dashboard.php'],
        'factories' => ['🏭 Factories', 'factories.php'],
        'workers' => ['👷 Workers', 'workers.php'],
        'audits' => ['📋 Audits', 'audits.php'],
        'grievances' => ['📣 Grievances', 'grievances.php'],
        'salary' => ['💰 Salaries', 'salary.php'],
        'certifications' => ['🏅 Certifications', 'certifications.php'],
        'equipment' => ['🧯 Safety Equipment', 'equipment.php'],
        'buyer' => ['🛒 Buyers', 'buyer.php'],
        'reports' => ['📈 Reports', 'reports.php'],
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
        'dashboard'      => ['📊 Dashboard',       'dashboard.php'],
        'factories'      => ['🏭 Factories',        'factories.php'],
        'audits'         => ['📋 Audits',           'audits.php'],
        'certifications' => ['🏅 Certifications',   'certifications.php'],
        'reports'        => ['📈 Reports',          'reports.php'],
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GarmentGuard - Factory Detail</title>
  <link rel="stylesheet" href="../../assets/css/style.css">
  <style>
    /* Detail Header Layout */
    .detail-header-card {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 24px;
      flex-wrap: wrap;
    }
    .detail-title-section {
      display: flex;
      flex-direction: column;
      gap: 10px;
    }
    .factory-name-large {
      font-size: 32px;
      font-weight: 800;
      color: var(--text-primary);
    }
    .location-subtitle {
      font-size: 15px;
      color: var(--text-secondary);
      display: flex;
      gap: 8px;
    }

    /* SVG Gauge styling */
    .gauge-wrapper {
      position: relative;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .gauge-label {
      position: absolute;
      text-align: center;
      display: flex;
      flex-direction: column;
    }
    .gauge-score {
      font-size: 26px;
      font-weight: 800;
    }
    .gauge-text {
      font-size: 10px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      color: var(--text-secondary);
      font-weight: 600;
    }

    /* Stats Grid */
    .stats-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 16px;
      margin-bottom: 24px;
    }
    @media (max-width: 992px) {
      .stats-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 576px) {
      .stats-grid { grid-template-columns: 1fr; }
    }
    .stat-card {
      background-color: var(--bg-secondary);
      border: 1px solid var(--border-color);
      border-radius: var(--border-radius);
      padding: 20px;
      display: flex;
      flex-direction: column;
      gap: 8px;
    }
    .stat-title {
      font-size: 13px;
      text-transform: uppercase;
      font-weight: 600;
      color: var(--text-secondary);
      letter-spacing: 0.5px;
    }
    .stat-value {
      font-size: 28px;
      font-weight: 700;
    }

    /* Tabs switching */
    .tabs-bar {
      display: flex;
      border-bottom: 1px solid var(--border-color);
      gap: 8px;
      margin-bottom: 24px;
      overflow-x: auto;
    }
    .tab-btn {
      background: none;
      border: none;
      padding: 14px 24px;
      color: var(--text-secondary);
      font-family: var(--font-family);
      font-size: 15px;
      font-weight: 600;
      cursor: pointer;
      border-bottom: 3px solid transparent;
      transition: all var(--transition-speed);
      white-space: nowrap;
    }
    .tab-btn:hover {
      color: var(--text-primary);
      background-color: rgba(255,255,255,0.02);
    }
    .tab-btn.active {
      color: var(--green);
      border-bottom-color: var(--green);
    }
    .tab-content {
      display: none;
    }
    .tab-content.active {
      display: block;
    }

    /* Overview list details */
    .info-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 24px;
    }
    @media (max-width: 768px) {
      .info-grid { grid-template-columns: 1fr; }
    }
    .info-list {
      list-style: none;
      display: flex;
      flex-direction: column;
      gap: 14px;
    }
    .info-list li {
      display: flex;
      border-bottom: 1px solid rgba(255,255,255,0.03);
      padding-bottom: 10px;
    }
    .info-label {
      width: 150px;
      font-weight: 500;
      color: var(--text-secondary);
      font-size: 14px;
    }
    .info-value {
      flex-grow: 1;
      font-size: 15px;
      color: var(--text-primary);
    }

    /* Equipment Alerts list */
    .alert-box {
      border-radius: 8px;
      padding: 16px;
      margin-top: 12px;
      display: flex;
      flex-direction: column;
      gap: 12px;
    }
    .alert-box.success {
      background-color: rgba(29, 158, 117, 0.08);
      border: 1px solid rgba(29, 158, 117, 0.2);
    }
    .alert-box.warning {
      background-color: rgba(186, 117, 23, 0.08);
      border: 1px solid rgba(186, 117, 23, 0.2);
    }
    .alert-item {
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 14px;
    }

    /* Timeline details */
    .timeline {
      position: relative;
      padding-left: 32px;
      margin-left: 16px;
      border-left: 2px solid var(--border-color);
      display: flex;
      flex-direction: column;
      gap: 24px;
    }
    .timeline-item {
      position: relative;
    }
    .timeline-dot {
      position: absolute;
      left: -41px;
      top: 4px;
      width: 16px;
      height: 16px;
      border-radius: 50%;
      border: 4px solid var(--bg-primary);
    }
    .timeline-card {
      background-color: var(--bg-secondary);
      border: 1px solid var(--border-color);
      border-radius: var(--border-radius);
      padding: 16px 20px;
      cursor: pointer;
      transition: border-color var(--transition-speed);
    }
    .timeline-card:hover {
      border-color: var(--text-secondary);
    }
    .timeline-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      gap: 12px;
    }
    .timeline-date {
      font-weight: 700;
      font-size: 15px;
    }
    .timeline-details-panel {
      margin-top: 14px;
      padding-top: 14px;
      border-top: 1px solid var(--border-color);
      display: none;
      flex-direction: column;
      gap: 12px;
    }
    .timeline-details-panel.open {
      display: flex;
    }

    /* Certifications cards grid */
    .certifications-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 20px;
    }
    .cert-card {
      background-color: var(--bg-secondary);
      border: 1px solid var(--border-color);
      border-radius: var(--border-radius);
      padding: 20px;
      display: flex;
      flex-direction: column;
      gap: 12px;
      position: relative;
    }
    .cert-badge {
      position: absolute;
      top: 20px;
      right: 20px;
    }
    .cert-name {
      font-size: 16px;
      font-weight: 700;
      padding-right: 70px;
    }
    .cert-body {
      font-size: 13px;
      color: var(--text-secondary);
      font-weight: 500;
    }
    .cert-date-info {
      font-size: 14px;
      display: flex;
      flex-direction: column;
      gap: 4px;
      margin-top: 6px;
      border-top: 1px solid rgba(255,255,255,0.03);
      padding-top: 10px;
    }
    .days-left-text {
      font-weight: 600;
      font-size: 12px;
    }
  </style>
</head>
<body>
  <div class="app-container">
    <!-- Dynamic Sidebar -->
    <div class="sidebar" id="sidebar">
      <div class="brand">
        <span class="brand-title">GarmentGuard</span>
        <span class="brand-subtitle">Compliance System</span>
      </div>
      <ul class="nav-menu">
        <?php foreach ($navMenu as $key => $item): ?>
          <li>
            <a href="<?php echo htmlspecialchars($item[1]); ?>" 
               class="nav-link <?php echo $key === 'factories' ? 'active' : ''; ?>">
              <?php echo htmlspecialchars($item[0]); ?>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
      <div class="nav-footer">
        <a href="../../../backend/auth/logout.php" class="nav-link">🚪 Logout</a>
      </div>
    </div>

    <!-- Main Content Area -->
    <div class="main-content">
      <div class="top-bar">
        <a href="factories.php" style="color:var(--green);font-weight:600;display:flex;align-items:center;gap:6px;">← Back to Factories</a>
        <div class="user-profile-menu">
          <span style="font-weight:500;color:var(--text-secondary);">
            <?php echo htmlspecialchars($fullName); ?> (<?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $role))); ?>)
          </span>
          <div class="user-avatar"><?php echo strtoupper(substr($fullName, 0, 1)); ?></div>
        </div>
      </div>

      <!-- Factory Overview Card -->
      <div class="card detail-header-card" id="header-loader-card">
        <div style="text-align:center;width:100%;color:var(--text-secondary)">Loading factory header details…</div>
      </div>

      <!-- Stats Grid -->
      <div class="stats-grid" id="stats-grid" style="display:none;">
        <div class="stat-card">
          <span class="stat-title">Total Workers</span>
          <span class="stat-value" id="stat-workers">—</span>
        </div>
        <div class="stat-card">
          <span class="stat-title">Open Grievances</span>
          <span class="stat-value" id="stat-grievances">—</span>
        </div>
        <div class="stat-card">
          <span class="stat-title">Active Certs</span>
          <span class="stat-value" id="stat-certs">—</span>
        </div>
        <div class="stat-card">
          <span class="stat-title">Equipment Alerts</span>
          <span class="stat-value" id="stat-alerts">—</span>
        </div>
      </div>

      <!-- Tabs Navigation -->
      <div class="tabs-bar">
        <button class="tab-btn active" onclick="switchTab('overview')">📋 Overview</button>
        <button class="tab-btn" onclick="switchTab('workers')">👷 Workers</button>
        <button class="tab-btn" onclick="switchTab('audits')">📋 Audits</button>
        <button class="tab-btn" onclick="switchTab('certifications')">🏅 Certifications</button>
      </div>

      <!-- Tab Content Area -->
      <div class="card">
        
        <!-- TAB 1: Overview -->
        <div id="tab-overview" class="tab-content active">
          <div class="info-grid">
            <div>
              <h3 class="card-title">Contact & Location Info</h3>
              <ul class="info-list">
                <li>
                  <span class="info-label">Address</span>
                  <span class="info-value" id="info-address">—</span>
                </li>
                <li>
                  <span class="info-label">Contact Person</span>
                  <span class="info-value" id="info-contact">—</span>
                </li>
                <li>
                  <span class="info-label">Phone</span>
                  <span class="info-value" id="info-phone">—</span>
                </li>
                <li>
                  <span class="info-label">Email</span>
                  <span class="info-value" id="info-email">—</span>
                </li>
              </ul>
            </div>
            <div>
              <h3 class="card-title">Audit Planning</h3>
              <ul class="info-list">
                <li>
                  <span class="info-label">Last Audit Date</span>
                  <span class="info-value" id="info-last-audit">—</span>
                </li>
                <li>
                  <span class="info-label">Next Audit Date</span>
                  <span class="info-value" id="info-next-audit">—</span>
                </li>
              </ul>
              
              <h3 class="card-title" style="margin-top: 24px;">Equipment Safety Alerts</h3>
              <div id="equipment-alerts-box">
                <div class="alert-box success">
                  <div class="alert-item">✅ Checking equipment safety…</div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- TAB 2: Workers -->
        <div id="tab-workers" class="tab-content">
          <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;gap:16px;">
            <h3 class="card-title" style="margin-bottom:0;">Employee Roster</h3>
            <input type="text" class="search-input" id="workers-search" placeholder="Search by name or designation…" style="max-width:300px;">
          </div>
          <div class="table-responsive">
            <table class="table">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Designation</th>
                  <th>Shift</th>
                  <th>Base Salary</th>
                  <th>YTD Salary</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody id="workers-tbody">
                <tr><td colspan="6" style="text-align:center;color:var(--text-secondary)">Loading employees…</td></tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- TAB 3: Audits -->
        <div id="tab-audits" class="tab-content">
          <h3 class="card-title" style="margin-bottom:24px;">Audit History</h3>
          <div class="timeline" id="audits-timeline">
            <div style="color:var(--text-secondary)">Loading audit log…</div>
          </div>
        </div>

        <!-- TAB 4: Certifications -->
        <div id="tab-certifications" class="tab-content">
          <h3 class="card-title" style="margin-bottom:24px;">Certifications Status</h3>
          <div class="certifications-grid" id="certifications-grid">
            <div style="color:var(--text-secondary)">Loading certifications grid…</div>
          </div>
        </div>

      </div>

    </div>
  </div>

  <script src="../../assets/js/toast.js"></script>
  <script src="../../assets/js/table-utils.js"></script>
  <script>
    const factoryId = <?php echo $factoryId; ?>;
    
    // Cache variables for tabs
    let factoryData = null;
    let workersData = null;
    let auditsData = null;
    let certificationsData = null;

    // Format currencies
    function formatCurrency(val) {
      if (val === null || val === undefined) return '—';
      return '৳' + parseFloat(val).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    // Load factory basic details on startup
    function loadFactoryHeader() {
      fetch(`/backend/api/factories.php?id=${factoryId}`)
        .then(res => res.json())
        .then(res => {
          if (res.success) {
            factoryData = res.data;
            renderHeaderAndOverview();
          } else {
            showToast(res.message || 'Factory details not found', 'error');
            document.getElementById('header-loader-card').innerHTML = `<div style="text-align:center;color:var(--red)">Failed to load: ${res.message}</div>`;
          }
        })
        .catch(() => {
          showToast('Network error loading details', 'error');
          document.getElementById('header-loader-card').innerHTML = `<div style="text-align:center;color:var(--red)">Network error loading factory data.</div>`;
        });
    }

    // Render detail header
    function renderHeaderAndOverview() {
      const f = factoryData;
      const score = f.CALCULATED_SCORE !== null ? parseFloat(f.CALCULATED_SCORE) : parseFloat(f.COMPLIANCE_SCORE);
      const scoreVal = isNaN(score) ? 0 : score;
      
      let badgeClass = 'badge-gray';
      if (f.COMPLIANCE_STATUS === 'Compliant') badgeClass = 'badge-green';
      else if (f.COMPLIANCE_STATUS === 'At Risk' || f.COMPLIANCE_STATUS === 'Review Needed') badgeClass = 'badge-amber';
      else if (f.COMPLIANCE_STATUS === 'Non-Compliant') badgeClass = 'badge-red';

      let gaugeColor = 'var(--red)';
      if (scoreVal >= 75) gaugeColor = 'var(--green)';
      else if (scoreVal >= 40) gaugeColor = 'var(--amber)';

      // Circumference for r=50 is 314.16
      const strokeDashoffset = 314.16 - (scoreVal / 100) * 314.16;

      const headerHtml = `
        <div class="detail-title-section">
          <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
            <h1 class="factory-name-large">${f.FACTORY_NAME}</h1>
            <span class="badge ${badgeClass}" style="font-size:14px;padding:6px 12px;">${f.COMPLIANCE_STATUS}</span>
          </div>
          <div class="location-subtitle">
            <span>📍 ${f.ADDRESS}</span>
            <span>•</span>
            <span>${f.DISTRICT}, ${f.DIVISION}</span>
            <span>•</span>
            <span>Reg No: ${f.REGISTRATION_NO}</span>
          </div>
        </div>
        <div class="gauge-wrapper">
          <svg width="120" height="120" viewBox="0 0 120 120">
            <circle cx="60" cy="60" r="50" fill="none" stroke="rgba(255,255,255,0.05)" stroke-width="8" />
            <circle cx="60" cy="60" r="50" fill="none" stroke="${gaugeColor}" stroke-width="8" 
                    stroke-dasharray="314.16" stroke-dashoffset="${strokeDashoffset}" stroke-linecap="round" 
                    transform="rotate(-90 60 60)" style="transition: stroke-dashoffset 0.6s ease;" />
          </svg>
          <div class="gauge-label">
            <span class="gauge-score" style="color: ${gaugeColor};">${scoreVal}</span>
            <span class="gauge-text">Score</span>
          </div>
        </div>
      `;
      
      document.getElementById('header-loader-card').innerHTML = headerHtml;
      document.getElementById('header-loader-card').classList.remove('detail-header-card'); // clean fallback helper

      // Populate counts in mini stat cards
      document.getElementById('stat-workers').innerText = f.TOTAL_WORKERS || 0;
      document.getElementById('stat-grievances').innerText = f.OPEN_GRIEVANCES || 0;
      document.getElementById('stat-certs').innerText = f.ACTIVE_CERTS || 0;
      document.getElementById('stat-alerts').innerText = f.EQUIPMENT_ALERTS_COUNT || 0;
      document.getElementById('stats-grid').style.display = 'grid';

      // Populate Overview tab values
      document.getElementById('info-address').innerText = f.ADDRESS;
      document.getElementById('info-contact').innerText = f.CONTACT_PERSON;
      document.getElementById('info-phone').innerText = f.PHONE || '—';
      document.getElementById('info-email').innerText = f.EMAIL;
      document.getElementById('info-last-audit').innerText = f.LAST_AUDIT_DATE || '—';
      document.getElementById('info-next-audit').innerText = f.NEXT_AUDIT_DATE || '—';

      // Render equipment warning list box
      const alertContainer = document.getElementById('equipment-alerts-box');
      if (f.EQUIPMENT_ALERTS && f.EQUIPMENT_ALERTS !== 'ALL OK' && f.EQUIPMENT_ALERTS !== 'ERROR') {
        const list = f.EQUIPMENT_ALERTS.split(',').filter(x => x.trim().length > 0);
        let itemsHtml = list.map(item => `
          <div class="alert-item">
            <span style="color:var(--amber);font-size:16px;">⚠️</span>
            <span><strong>${item.trim()}</strong> requires inspection (expiring within 30 days)</span>
          </div>
        `).join('');
        alertContainer.innerHTML = `<div class="alert-box warning">${itemsHtml}</div>`;
      } else {
        alertContainer.innerHTML = `
          <div class="alert-box success">
            <div class="alert-item">
              <span style="color:var(--green);font-size:16px;">✅</span>
              <span>All safety equipment are up-to-date and compliant.</span>
            </div>
          </div>
        `;
      }
    }

    // Switch active Tab
    function switchTab(tabName) {
      // Toggle button active states
      const btns = document.querySelectorAll('.tab-btn');
      btns.forEach((btn, idx) => {
        if (btn.innerText.toLowerCase().includes(tabName)) {
          btn.classList.add('active');
        } else {
          btn.classList.remove('active');
        }
      });

      // Toggle views active states
      const contents = document.querySelectorAll('.tab-content');
      contents.forEach(c => {
        if (c.id === `tab-${tabName}`) {
          c.classList.add('active');
        } else {
          c.classList.remove('active');
        }
      });

      // Trigger lazy load
      if (tabName === 'workers') {
        loadWorkers();
      } else if (tabName === 'audits') {
        loadAudits();
      } else if (tabName === 'certifications') {
        loadCertifications();
      }
    }

    // TAB 2: Load and render workers
    function loadWorkers() {
      if (workersData !== null) return; // already loaded
      
      const tbody = document.getElementById('workers-tbody');
      tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:var(--text-secondary)">Loading employees roster…</td></tr>';

      fetch(`/backend/api/workers.php?factory_id=${factoryId}`)
        .then(res => res.json())
        .then(res => {
          if (res.success) {
            workersData = res.data;
            renderWorkers();
          } else {
            tbody.innerHTML = `<tr><td colspan="6" style="text-align:center;color:var(--red)">Failed to load: ${res.message}</td></tr>`;
          }
        })
        .catch(() => {
          tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:var(--red)">Network error loading roster.</td></tr>';
        });
    }

    function renderWorkers() {
      const q = document.getElementById('workers-search').value.toLowerCase().trim();
      const tbody = document.getElementById('workers-tbody');

      const filtered = workersData.filter(w => 
        w.FULL_NAME.toLowerCase().includes(q) || 
        w.DESIGNATION.toLowerCase().includes(q)
      );

      if (filtered.length === 0) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;color:var(--text-secondary)">No workers matched filters.</td></tr>';
        return;
      }

      tbody.innerHTML = filtered.map(w => {
        let badgeClass = 'badge-gray';
        if (w.STATUS === 'Active') badgeClass = 'badge-green';
        else if (w.STATUS === 'Inactive') badgeClass = 'badge-amber';
        else if (w.STATUS === 'Terminated') badgeClass = 'badge-red';

        return `
          <tr>
            <td><strong>${w.FULL_NAME}</strong></td>
            <td>${w.DESIGNATION}</td>
            <td>${w.SHIFT || '—'}</td>
            <td>${formatCurrency(w.BASE_SALARY)}</td>
            <td>${formatCurrency(w.YTD_SALARY)}</td>
            <td><span class="badge ${badgeClass}">${w.STATUS}</span></td>
          </tr>
        `;
      }).join('');
    }

    // Attach local search filtering for workers
    document.getElementById('workers-search').addEventListener('input', renderWorkers);

    // TAB 3: Load and render Audits
    function loadAudits() {
      if (auditsData !== null) return;

      const timeline = document.getElementById('audits-timeline');
      timeline.innerHTML = '<div style="color:var(--text-secondary)">Loading audit log timeline…</div>';

      fetch(`/backend/api/audits.php?factory_id=${factoryId}`)
        .then(res => res.json())
        .then(res => {
          if (res.success) {
            auditsData = res.data;
            renderAudits();
          } else {
            timeline.innerHTML = `<div style="color:var(--red)">Failed to load: ${res.message}</div>`;
          }
        })
        .catch(() => {
          timeline.innerHTML = '<div style="color:var(--red)">Network error loading audits.</div>';
        });
    }

    function toggleAuditExpansion(panelId) {
      const panel = document.getElementById(panelId);
      if (panel) {
        panel.classList.toggle('open');
      }
    }

    function renderAudits() {
      const timeline = document.getElementById('audits-timeline');
      if (auditsData.length === 0) {
        timeline.innerHTML = '<div style="color:var(--text-secondary)">No audit records found for this factory.</div>';
        return;
      }

      timeline.innerHTML = auditsData.map((a, idx) => {
        let resultBadge = 'badge-gray';
        if (a.RESULT === 'Compliant') resultBadge = 'badge-green';
        else if (a.RESULT === 'At Risk' || a.RESULT === 'Review Needed') resultBadge = 'badge-amber';
        else if (a.RESULT === 'Non-Compliant') resultBadge = 'badge-red';

        let dotColor = 'var(--text-secondary)';
        if (a.SCORE >= 75) dotColor = 'var(--green)';
        else if (a.SCORE >= 40) dotColor = 'var(--amber)';
        else dotColor = 'var(--red)';

        const panelId = `audit-panel-${idx}`;

        return `
          <div class="timeline-item">
            <div class="timeline-dot" style="background-color: ${dotColor};"></div>
            <div class="timeline-card" onclick="toggleAuditExpansion('${panelId}')">
              <div class="timeline-header">
                <div>
                  <span class="timeline-date">${a.AUDIT_DATE}</span>
                  <span style="color:var(--text-secondary);font-size:13px;margin-left:10px;">by Inspector: <strong>${a.INSPECTOR_NAME}</strong></span>
                </div>
                <div style="display:flex;align-items:center;gap:12px;">
                  <span style="font-weight:700;color:${dotColor}">Score: ${a.SCORE}</span>
                  <span class="badge ${resultBadge}">${a.RESULT}</span>
                </div>
              </div>
              <div class="timeline-details-panel" id="${panelId}">
                <div>
                  <h4 style="font-size:14px;font-weight:600;margin-bottom:4px;color:var(--text-primary)">Findings:</h4>
                  <p style="font-size:13px;color:var(--text-secondary);line-height:1.5;">${a.FINDINGS || 'No specific findings logged.'}</p>
                </div>
                <div>
                  <h4 style="font-size:14px;font-weight:600;margin-bottom:4px;color:var(--text-primary)">Recommendations:</h4>
                  <p style="font-size:13px;color:var(--text-secondary);line-height:1.5;">${a.RECOMMENDATIONS || 'No recommended actions.'}</p>
                </div>
              </div>
            </div>
          </div>
        `;
      }).join('');
    }

    // TAB 4: Load and render Certifications
    function loadCertifications() {
      if (certificationsData !== null) return;

      const grid = document.getElementById('certifications-grid');
      grid.innerHTML = '<div style="color:var(--text-secondary)">Loading certifications grid cards…</div>';

      fetch(`/backend/api/certifications.php?factory_id=${factoryId}`)
        .then(res => res.json())
        .then(res => {
          if (res.success) {
            certificationsData = res.data;
            renderCertifications();
          } else {
            grid.innerHTML = `<div style="color:var(--red)">Failed to load: ${res.message}</div>`;
          }
        })
        .catch(() => {
          grid.innerHTML = '<div style="color:var(--red)">Network error loading certifications.</div>';
        });
    }

    function renderCertifications() {
      const grid = document.getElementById('certifications-grid');
      if (certificationsData.length === 0) {
        grid.innerHTML = '<div style="color:var(--text-secondary)">No certifications records found.</div>';
        return;
      }

      grid.innerHTML = certificationsData.map(c => {
        // Validity checking based on custom function
        const isValid = c.IS_VALID === 'Y';
        const validityBadge = isValid 
          ? '<span class="badge badge-green cert-badge">Valid</span>'
          : '<span class="badge badge-red cert-badge">Invalid</span>';

        const days = parseInt(c.DAYS_LEFT);
        let daysColor = 'var(--text-secondary)';
        let daysText = '';

        if (isNaN(days)) {
          daysText = 'Expiry date undefined';
        } else if (days < 0) {
          daysColor = 'var(--red)';
          daysText = `Expired ${Math.abs(days)} days ago`;
        } else if (days === 0) {
          daysColor = 'var(--red)';
          daysText = 'Expires today!';
        } else if (days <= 30) {
          daysColor = 'var(--amber)';
          daysText = `Expires in ${days} days (Urgent)`;
        } else {
          daysColor = 'var(--green)';
          daysText = `Expires in ${days} days`;
        }

        return `
          <div class="cert-card">
            ${validityBadge}
            <h4 class="cert-name">${c.CERT_NAME}</h4>
            <span class="cert-body">Issued by: ${c.ISSUING_BODY || 'Unknown'}</span>
            <div class="cert-date-info">
              <span style="font-size:12px;color:var(--text-secondary);">Issued: <strong>${c.ISSUE_DATE || '—'}</strong></span>
              <span style="font-size:12px;color:var(--text-secondary);">Expiry: <strong>${c.EXPIRY_DATE || '—'}</strong></span>
              <span class="days-left-text" style="color: ${daysColor}; margin-top: 6px;">${daysText}</span>
            </div>
          </div>
        `;
      }).join('');
    }

    // Initialize details load on document load
    document.addEventListener('DOMContentLoaded', loadFactoryHeader);
  </script>
</body>
</html>
