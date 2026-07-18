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
        'dashboard' => ['📊 Dashboard', 'dashboard.php'],
        'factories' => ['🏭 Factories', 'factories.php'],
        'audits' => ['📋 Audits', 'audits.php'],
        'certifications' => ['🏅 Certifications', 'certifications.php'],
        'reports' => ['📈 Reports', 'reports.php'],
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GarmentGuard - Factory Management</title>
  <link rel="stylesheet" href="../../assets/css/style.css">
  <style>
    /* Sortable headers style */
    .sortable-header {
      cursor: pointer;
      user-select: none;
      transition: background-color var(--transition-speed);
      position: relative;
    }
    .sortable-header:hover {
      background-color: rgba(255, 255, 255, 0.05);
    }
    .sort-indicator {
      margin-left: 6px;
      font-size: 11px;
      color: var(--text-secondary);
    }
    .sortable-header.active {
      color: var(--green);
    }
    .sortable-header.active .sort-indicator {
      color: var(--green);
    }

    /* Filters Bar Layout */
    .filters-bar {
      display: flex;
      flex-wrap: wrap;
      gap: 16px;
      margin-bottom: 24px;
      align-items: center;
    }
    .filter-group {
      display: flex;
      flex-direction: column;
      gap: 6px;
      min-width: 180px;
      flex-grow: 1;
    }
    .filter-group.search-group {
      flex-grow: 3;
    }
    .filter-select {
      background-color: var(--bg-secondary);
      border: 1px solid var(--border-color);
      border-radius: 8px;
      color: var(--text-primary);
      padding: 10px 14px;
      font-family: var(--font-family);
      font-size: 14px;
      outline: none;
      cursor: pointer;
      transition: border-color var(--transition-speed);
    }
    .filter-select:focus {
      border-color: var(--green);
    }

    /* Score and Status indicators */
    .score-bar-container {
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .score-bar {
      width: 80px;
      height: 8px;
      background-color: var(--bg-tertiary);
      border-radius: 4px;
      overflow: hidden;
    }
    .score-bar-fill {
      height: 100%;
      border-radius: 4px;
    }
    .score-text {
      font-weight: 700;
      font-size: 14px;
    }

    /* Slide-over panel stylesheet */
    .slide-over-container {
      position: fixed;
      top: 0;
      left: 0;
      width: 100vw;
      height: 100vh;
      z-index: 1000;
      display: none;
    }
    .slide-over-container.open {
      display: flex;
    }
    .slide-over-backdrop {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(15, 23, 42, 0.6);
      backdrop-filter: blur(4px);
      opacity: 0;
      transition: opacity 0.3s ease;
    }
    .slide-over-container.open .slide-over-backdrop {
      opacity: 1;
    }
    .slide-over-panel {
      position: absolute;
      top: 0;
      right: -480px;
      width: 480px;
      height: 100%;
      background: var(--bg-secondary);
      border-left: 1px solid var(--border-color);
      box-shadow: -8px 0 24px rgba(0, 0, 0, 0.4);
      display: flex;
      flex-direction: column;
      transition: right 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    }
    .slide-over-container.open .slide-over-panel {
      right: 0;
    }
    .slide-over-header {
      padding: 24px;
      border-bottom: 1px solid var(--border-color);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .slide-over-header h3 {
      font-size: 20px;
      font-weight: 700;
      color: var(--text-primary);
    }
    .close-btn {
      background: none;
      border: none;
      font-size: 28px;
      color: var(--text-secondary);
      cursor: pointer;
      transition: color var(--transition-speed);
      line-height: 1;
    }
    .close-btn:hover {
      color: var(--red);
    }
    .slide-over-body {
      padding: 24px;
      overflow-y: auto;
      flex-grow: 1;
      display: flex;
      flex-direction: column;
      gap: 18px;
    }
    .slide-over-footer {
      padding: 24px;
      border-top: 1px solid var(--border-color);
      display: flex;
      justify-content: flex-end;
      gap: 12px;
      background-color: rgba(30, 41, 59, 0.9);
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
        <h2 class="page-title">Factory Management</h2>
        <div class="user-profile-menu">
          <span style="font-weight:500;color:var(--text-secondary);">
            <?php echo htmlspecialchars($fullName); ?> (<?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $role))); ?>)
          </span>
          <div class="user-avatar"><?php echo strtoupper(substr($fullName, 0, 1)); ?></div>
        </div>
      </div>

      <div class="card">
        <!-- Filters Bar -->
        <div class="filters-bar">
          <div class="filter-group search-group">
            <input type="text" class="search-input" id="search-input" placeholder="Search by factory name or district…">
          </div>
          <div class="filter-group">
            <select class="filter-select" id="district-filter">
              <option value="">All Districts</option>
              <option value="Dhaka">Dhaka</option>
              <option value="Gazipur">Gazipur</option>
              <option value="Chittagong">Chittagong</option>
              <option value="Sylhet">Sylhet</option>
              <option value="Rajshahi">Rajshahi</option>
              <option value="Khulna">Khulna</option>
              <option value="Barishal">Barishal</option>
              <option value="Mymensingh">Mymensingh</option>
            </select>
          </div>
          <div class="filter-group">
            <select class="filter-select" id="status-filter">
              <option value="">All Statuses</option>
              <option value="Compliant">Compliant</option>
              <option value="At Risk">At Risk</option>
              <option value="Non-Compliant">Non-Compliant</option>
              <option value="Review Needed">Review Needed</option>
              <option value="Pending">Pending</option>
            </select>
          </div>
          <?php if (in_array($role, ['admin', 'compliance_officer'])): ?>
            <button class="btn btn-primary" onclick="toggleSlideOver(true)">➕ Add Factory</button>
          <?php endif; ?>
        </div>

        <!-- Factory Table -->
        <div class="table-responsive">
          <table class="table" id="factories-table">
            <thead>
              <tr>
                <th class="sortable-header" data-sort="FACTORY_NAME">Factory Name <span class="sort-indicator">▲▼</span></th>
                <th class="sortable-header" data-sort="DISTRICT">District <span class="sort-indicator">▲▼</span></th>
                <th class="sortable-header" data-sort="DIVISION">Division <span class="sort-indicator">▲▼</span></th>
                <th class="sortable-header" data-sort="CALCULATED_SCORE">Score <span class="sort-indicator">▲▼</span></th>
                <th class="sortable-header" data-sort="COMPLIANCE_STATUS">Status <span class="sort-indicator">▲▼</span></th>
                <th class="sortable-header" data-sort="TOTAL_WORKERS">Workers <span class="sort-indicator">▲▼</span></th>
                <th class="sortable-header" data-sort="LAST_AUDIT_DATE">Last Audit <span class="sort-indicator">▲▼</span></th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody id="factories-tbody">
              <tr>
                <td colspan="8" style="text-align:center;color:var(--text-secondary);padding:32px;">Loading factories data…</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Slide-over Registration Panel -->
  <?php if (in_array($role, ['admin', 'compliance_officer'])): ?>
    <div id="slide-over" class="slide-over-container">
      <div class="slide-over-backdrop" onclick="toggleSlideOver(false)"></div>
      <div class="slide-over-panel">
        <div class="slide-over-header">
          <h3>Register New Factory</h3>
          <button class="close-btn" onclick="toggleSlideOver(false)">&times;</button>
        </div>
        <form id="add-factory-form" onsubmit="submitFactoryForm(event)" style="display:contents;">
          <div class="slide-over-body">
            <div class="form-group">
              <label class="form-label" for="factory_name">Factory Name <span style="color:var(--red)">*</span></label>
              <input type="text" class="form-control" id="factory_name" name="factory_name" placeholder="Enter factory name" data-required="true">
            </div>
            <div class="form-group">
              <label class="form-label" for="registration_no">Registration No <span style="color:var(--red)">*</span></label>
              <input type="text" class="form-control" id="registration_no" name="registration_no" placeholder="Unique Reg No" data-required="true">
            </div>
            <div class="form-group">
              <label class="form-label" for="address">Address <span style="color:var(--red)">*</span></label>
              <input type="text" class="form-control" id="address" name="address" placeholder="Factory address" data-required="true">
            </div>
            
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
              <div class="form-group">
                <label class="form-label" for="district">District <span style="color:var(--red)">*</span></label>
                <select class="form-control" id="district" name="district" data-required="true">
                  <option value="">Select District</option>
                  <option value="Dhaka">Dhaka</option>
                  <option value="Gazipur">Gazipur</option>
                  <option value="Chittagong">Chittagong</option>
                  <option value="Sylhet">Sylhet</option>
                  <option value="Rajshahi">Rajshahi</option>
                  <option value="Khulna">Khulna</option>
                  <option value="Barishal">Barishal</option>
                  <option value="Mymensingh">Mymensingh</option>
                </select>
              </div>
              <div class="form-group">
                <label class="form-label" for="division">Division <span style="color:var(--red)">*</span></label>
                <select class="form-control" id="division" name="division" data-required="true">
                  <option value="">Select Division</option>
                  <option value="Dhaka">Dhaka</option>
                  <option value="Gazipur">Gazipur</option>
                  <option value="Chittagong">Chittagong</option>
                  <option value="Sylhet">Sylhet</option>
                  <option value="Rajshahi">Rajshahi</option>
                  <option value="Khulna">Khulna</option>
                  <option value="Barishal">Barishal</option>
                  <option value="Mymensingh">Mymensingh</option>
                </select>
              </div>
            </div>

            <div class="form-group">
              <label class="form-label" for="total_workers">Total Workers</label>
              <input type="number" class="form-control" id="total_workers" name="total_workers" value="0" min="0" data-min="0">
            </div>
            <div class="form-group">
              <label class="form-label" for="contact_person">Contact Person <span style="color:var(--red)">*</span></label>
              <input type="text" class="form-control" id="contact_person" name="contact_person" placeholder="Primary contact name" data-required="true">
            </div>
            
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
              <div class="form-group">
                <label class="form-label" for="phone">Phone</label>
                <input type="text" class="form-control" id="phone" name="phone" placeholder="e.g. +8801...">
              </div>
              <div class="form-group">
                <label class="form-label" for="email">Email <span style="color:var(--red)">*</span></label>
                <input type="email" class="form-control" id="email" name="email" placeholder="contact@email.com" data-required="true">
              </div>
            </div>
          </div>
          <div class="slide-over-footer">
            <button type="button" class="btn btn-secondary" onclick="toggleSlideOver(false)">Cancel</button>
            <button type="submit" class="btn btn-primary">Register Factory</button>
          </div>
        </form>
      </div>
    </div>
  <?php endif; ?>

  <script src="../../assets/js/toast.js"></script>
  <script src="../../assets/js/validate.js"></script>
  <script src="../../assets/js/table-utils.js"></script>
  <script>
    // State storage variables
    let allFactories = [];
    let sortColumn = 'FACTORY_NAME';
    let sortAscending = true;

    // Load factories data
    function fetchFactories() {
      fetch('/backend/api/factories.php')
        .then(res => res.json())
        .then(res => {
          if (res.success) {
            allFactories = res.data;
            filterAndRender();
            TableUtils.initSortHeaders('factories-table', allFactories, (sortedData) => {
              renderTable(sortedData);
            });
          } else {
            showToast(res.message || 'Failed to load factories data', 'error');
          }
        })
        .catch(() => showToast('Network error loading factories', 'error'));
    }

    // Toggle slideover form panel
    function toggleSlideOver(show) {
      const container = document.getElementById('slide-over');
      if (!container) return;
      if (show) {
        container.classList.add('open');
      } else {
        container.classList.remove('open');
        clearErrors(container.querySelector('form'));
      }
    }

    // Color mapper for compliance scores
    function getScoreColor(score) {
      if (score >= 75) return 'var(--green)';
      if (score >= 40) return 'var(--amber)';
      return 'var(--red)';
    }

    // Class mapper for status badge styling
    function getStatusBadgeClass(status) {
      const map = {
        'Compliant': 'badge-green',
        'At Risk': 'badge-amber',
        'Non-Compliant': 'badge-red',
        'Review Needed': 'badge-amber',
        'Pending': 'badge-gray'
      };
      return map[status] || 'badge-gray';
    }

    // Render table data
    function renderTable(data) {
      const q = document.getElementById('search-input').value.toLowerCase().trim();
      const dist = document.getElementById('district-filter').value;
      const status = document.getElementById('status-filter').value;

      // Filter using TableUtils
      const filtered = TableUtils.filterData(data || allFactories, {
        search: q,
        DISTRICT: dist,
        COMPLIANCE_STATUS: status
      });

      const tbody = document.getElementById('factories-tbody');
      if (filtered.length === 0) {
        tbody.innerHTML = `<tr><td colspan="8" style="text-align:center;color:var(--text-secondary);padding:32px;">No factories match selected criteria.</td></tr>`;
        return;
      }

      tbody.innerHTML = filtered.map(f => {
        const score = f.CALCULATED_SCORE !== null ? parseFloat(f.CALCULATED_SCORE) : parseFloat(f.COMPLIANCE_SCORE);
        const scoreVal = isNaN(score) ? 0 : score;
        const lastAudit = f.LAST_AUDIT_DATE || '—';
        return `
          <tr>
            <td><strong>${f.FACTORY_NAME}</strong></td>
            <td>${f.DISTRICT}</td>
            <td>${f.DIVISION}</td>
            <td>
              <div class="score-bar-container">
                <div class="score-bar">
                  <div class="score-bar-fill" style="width: ${scoreVal}%; background: ${getScoreColor(scoreVal)};"></div>
                </div>
                <span class="score-text" style="color: ${getScoreColor(scoreVal)};">${scoreVal}</span>
              </div>
            </td>
            <td><span class="badge ${getStatusBadgeClass(f.COMPLIANCE_STATUS)}">${f.COMPLIANCE_STATUS}</span></td>
            <td>${f.TOTAL_WORKERS}</td>
            <td>${lastAudit}</td>
            <td>
              <a href="factory_detail.php?id=${f.FACTORY_ID}" class="btn btn-secondary btn-sm">View</a>
            </td>
          </tr>
        `;
      }).join('');
    }

    // Apply active filters and render to table
    function filterAndRender() {
      // Sort using TableUtils state before rendering
      const sorted = TableUtils.sortData(
        allFactories,
        TableUtils.currentSortCol || 'FACTORY_NAME',
        TableUtils.currentSortOrder || 'asc'
      );
      renderTable(sorted);
    }

    // Add Form Submit Handling
    function submitFactoryForm(event) {
      event.preventDefault();
      const form = event.target;
      
      // Perform validation using validateForm
      const result = validateForm(form);
      if (!result.valid) {
        return;
      }

      const payload = {
        factory_name: form.elements['factory_name'].value.trim(),
        registration_no: form.elements['registration_no'].value.trim(),
        address: form.elements['address'].value.trim(),
        district: form.elements['district'].value,
        division: form.elements['division'].value,
        total_workers: parseInt(form.elements['total_workers'].value) || 0,
        contact_person: form.elements['contact_person'].value.trim(),
        phone: form.elements['phone'].value.trim(),
        email: form.elements['email'].value.trim()
      };

      fetch('/backend/api/factories.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      })
      .then(res => res.json())
      .then(res => {
        if (res.success) {
          toggleSlideOver(false);
          form.reset();
          clearErrors(form);
          fetchFactories();
          showToast('Factory registered successfully', 'success');
        } else {
          showToast(res.message || 'Registration failed', 'error');
        }
      })
      .catch(() => showToast('Network error during registration', 'error'));
    }

    // Attach listeners on page load
    document.addEventListener('DOMContentLoaded', () => {
      fetchFactories();

      // Filter events
      document.getElementById('search-input').addEventListener('input', filterAndRender);
      document.getElementById('district-filter').addEventListener('change', filterAndRender);
      document.getElementById('status-filter').addEventListener('change', filterAndRender);
    });
  </script>
</body>
</html>
