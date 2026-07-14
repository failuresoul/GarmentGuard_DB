<?php
// Direct access security guard
if (!isset($activePage) || $activePage !== 'audits') {
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
        'equipment' => ['🧯 Safety Equipment', 'equipment.php'],
        'reports'   => ['📈 Reports',          'reports.php'],
    ];
} elseif ($role === 'buyer_user' || $role === 'buyer') {
    $navMenu = [
        'dashboard' => ['📊 Dashboard',       'dashboard.php'],
        'factories' => ['🏭 Factories',        'factories.php'],
        'audits'    => ['📋 Audits',           'audits.php'],
        'certifications' => ['🏅 Certifications', 'certifications.php'],
        'reports'   => ['📈 Reports',          'reports.php'],
    ];
}

$canSchedule = in_array($role, ['admin', 'compliance_officer']);
$canRecord   = in_array($role, ['admin', 'inspector']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GarmentGuard – Audit Management</title>
  <meta name="description" content="View and manage factory compliance audits, schedule inspections, and record audit scores.">
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

    /* Filters bar styling */
    .filters-bar {
      display: flex;
      flex-wrap: wrap;
      gap: 14px;
      margin-bottom: 20px;
      align-items: flex-end;
    }
    .filter-group {
      display: flex;
      flex-direction: column;
      gap: 6px;
      min-width: 150px;
      flex-grow: 1;
    }
    .filter-group.search-group {
      flex-grow: 3;
    }
    .filter-select {
      background: var(--bg-secondary);
      border: 1px solid var(--border-color);
      border-radius: 8px;
      color: var(--text-primary);
      padding: 10px 13px;
      font-family: var(--font-family);
      font-size: 14px;
      outline: none;
      cursor: pointer;
      transition: border-color var(--transition-speed);
      width: 100%;
    }
    .filter-select:focus {
      border-color: var(--green);
    }

    /* Table custom overrides */
    .table td {
      vertical-align: middle;
    }

    /* Accordion styles */
    .accordion-details {
      transition: all 0.3s ease;
    }
    .accordion-content h4 {
      font-weight: 600;
    }

    /* Modal styling */
    .modal-overlay {
      position: fixed;
      inset: 0;
      z-index: 1000;
      display: none;
      align-items: center;
      justify-content: center;
      background: rgba(10, 15, 30, 0.65);
      backdrop-filter: blur(4px);
    }
    .modal-overlay.open {
      display: flex;
    }
    .modal-box {
      background: var(--bg-secondary);
      border: 1px solid var(--border-color);
      border-radius: 16px;
      box-shadow: 0 24px 60px rgba(0, 0, 0, 0.5);
      width: min(580px, 96vw);
      max-height: 90vh;
      display: flex;
      flex-direction: column;
      animation: slideUp 0.25s ease;
    }
    @keyframes slideUp {
      from { opacity: 0; transform: translateY(24px); }
      to   { opacity: 1; transform: translateY(0); }
    }
    .modal-header {
      padding: 20px 24px;
      border-bottom: 1px solid var(--border-color);
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-shrink: 0;
    }
    .modal-header h3 {
      font-size: 18px;
      font-weight: 700;
      color: var(--text-primary);
    }
    .close-btn {
      background: none;
      border: none;
      font-size: 26px;
      line-height: 1;
      color: var(--text-secondary);
      cursor: pointer;
      transition: color var(--transition-speed);
    }
    .close-btn:hover {
      color: var(--red);
    }
    .modal-body {
      padding: 24px;
      overflow-y: auto;
      flex-grow: 1;
      display: flex;
      flex-direction: column;
      gap: 16px;
    }
    .modal-footer {
      padding: 20px 24px;
      border-top: 1px solid var(--border-color);
      display: flex;
      justify-content: flex-end;
      gap: 10px;
      flex-shrink: 0;
      background: rgba(30, 41, 59, 0.9);
    }
    .form-grid-2 {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 14px;
    }
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
             class="nav-link <?php echo $key === 'audits' ? 'active' : ''; ?>">
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
      <h2 class="page-title">Audit Management</h2>
      <div class="user-profile-menu">
        <span style="font-weight: 500; color: var(--text-secondary);">
          <?php echo htmlspecialchars($fullName); ?> (<?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $role))); ?>)
        </span>
        <div class="user-avatar"><?php echo strtoupper(substr($fullName, 0, 1)); ?></div>
      </div>
    </div>

    <div class="card">
      <!-- Filters Bar -->
      <div class="filters-bar">
        <div class="filter-group">
          <label class="form-label" for="factory-filter">Factory</label>
          <select class="filter-select" id="factory-filter">
            <option value="">All Factories</option>
          </select>
        </div>

        <div class="filter-group">
          <label class="form-label" for="result-filter">Result</label>
          <select class="filter-select" id="result-filter">
            <option value="All">All Results</option>
            <option value="Pass">Pass</option>
            <option value="Fail">Fail</option>
            <option value="Pending">Pending</option>
          </select>
        </div>

        <div class="filter-group">
          <label class="form-label" for="date-from">From Date</label>
          <input type="date" class="filter-select" id="date-from">
        </div>

        <div class="filter-group">
          <label class="form-label" for="date-to">To Date</label>
          <input type="date" class="filter-select" id="date-to">
        </div>

        <div class="filter-group search-group">
          <label class="form-label" for="search-input">Search</label>
          <input type="text" class="search-input" id="search-input" placeholder="Search by factory or inspector name…">
        </div>

        <?php if ($canSchedule): ?>
        <div class="filter-group" style="min-width: auto; flex-grow: 0;">
          <button class="btn btn-primary" onclick="openScheduleModal()">📅 Schedule Audit</button>
        </div>
        <?php endif; ?>
      </div>

      <!-- Audits Table -->
      <div class="table-responsive">
        <table class="table" id="audits-table">
          <thead>
            <tr>
              <th class="sortable-header active" data-sort="AUDIT_ID">Audit ID <span class="sort-indicator">▼</span></th>
              <th class="sortable-header" data-sort="FACTORY_NAME">Factory <span class="sort-indicator">▲▼</span></th>
              <th class="sortable-header" data-sort="INSPECTOR_NAME">Inspector <span class="sort-indicator">▲▼</span></th>
              <th class="sortable-header" data-sort="AUDIT_DATE_RAW">Audit Date <span class="sort-indicator">▲▼</span></th>
              <th class="sortable-header" data-sort="SCORE">Score <span class="sort-indicator">▲▼</span></th>
              <th class="sortable-header" data-sort="RESULT">Result <span class="sort-indicator">▲▼</span></th>
              <th class="sortable-header" data-sort="NEXT_SCHEDULED_RAW">Next Scheduled <span class="sort-indicator">▲▼</span></th>
              <th style="text-align: right;">Actions</th>
            </tr>
          </thead>
          <tbody id="audits-tbody">
            <tr>
              <td colspan="8" style="text-align: center; color: var(--text-secondary); padding: 32px;">
                Loading compliance audits…
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- ══════════════════════════════════════════════════
     SCHEDULE AUDIT MODAL (role-gated)
     ══════════════════════════════════════════════════ -->
<?php if ($canSchedule): ?>
<div id="schedule-modal" class="modal-overlay" onclick="closeModal('schedule-modal', event)">
  <div class="modal-box">
    <div class="modal-header">
      <h3>Schedule Compliance Audit</h3>
      <button class="close-btn" onclick="document.getElementById('schedule-modal').classList.remove('open')">&times;</button>
    </div>
    <form id="schedule-audit-form" onsubmit="submitScheduleAudit(event)" style="display: contents;">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label" for="sa-factory">Factory <span style="color: var(--red)">*</span></label>
          <select class="form-control" id="sa-factory" name="factory_id" required data-required="true">
            <option value="">Select Factory</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label" for="sa-inspector">Inspector <span style="color: var(--red)">*</span></label>
          <select class="form-control" id="sa-inspector" name="inspector_id" required data-required="true">
            <option value="">Select Inspector</option>
          </select>
        </div>

        <div class="form-grid-2">
          <div class="form-group">
            <label class="form-label" for="sa-audit-date">Audit Date <span style="color: var(--red)">*</span></label>
            <input type="date" class="form-control" id="sa-audit-date" name="audit_date" required data-required="true">
          </div>
          <div class="form-group">
            <label class="form-label" for="sa-next-date">Next Scheduled Date</label>
            <input type="date" class="form-control" id="sa-next-date" name="next_scheduled">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('schedule-modal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary" id="schedule-submit-btn">Schedule Audit</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<!-- ══════════════════════════════════════════════════
     RECORD SCORE MODAL (role-gated)
     ══════════════════════════════════════════════════ -->
<?php if ($canRecord): ?>
<div id="score-modal" class="modal-overlay" onclick="closeModal('score-modal', event)">
  <div class="modal-box">
    <div class="modal-header">
      <h3>Record Audit Score</h3>
      <button class="close-btn" onclick="document.getElementById('score-modal').classList.remove('open')">&times;</button>
    </div>
    <form id="record-score-form" onsubmit="submitRecordScore(event)" style="display: contents;">
      <input type="hidden" id="rs-audit-id" name="audit_id">
      <div class="modal-body">
        <div class="form-grid-2">
          <div class="form-group">
            <label class="form-label" for="rs-score">Score (0-100) <span style="color: var(--red)">*</span></label>
            <input type="number" class="form-control" id="rs-score" name="score" min="0" max="100" placeholder="e.g. 85" required data-required="true" data-min="0" data-max="100">
          </div>
          <div class="form-group">
            <label class="form-label">Result <span style="color: var(--red)">*</span></label>
            <div style="display: flex; gap: 16px; margin-top: 10px;">
              <label style="display: inline-flex; align-items: center; gap: 6px; cursor: pointer;">
                <input type="radio" name="result" value="Pass" checked> Pass
              </label>
              <label style="display: inline-flex; align-items: center; gap: 6px; cursor: pointer;">
                <input type="radio" name="result" value="Fail"> Fail
              </label>
            </div>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label" for="rs-findings">Findings</label>
          <textarea class="form-control" id="rs-findings" name="findings" rows="3" placeholder="Describe findings..."></textarea>
        </div>

        <div class="form-group">
          <label class="form-label" for="rs-recommendations">Recommendations</label>
          <textarea class="form-control" id="rs-recommendations" name="recommendations" rows="3" placeholder="Describe recommendations..."></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('score-modal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary" id="score-submit-btn">Submit Score</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<script src="../../assets/js/toast.js"></script>
<script src="../../assets/js/validate.js"></script>
<script src="../../assets/js/table-utils.js"></script>
<script>
// ═══════════════════════════════════════════════════════════════
//  STATE
// ═══════════════════════════════════════════════════════════════
let allRows       = [];
let filteredRows  = [];

const canRecord   = <?php echo $canRecord ? 'true' : 'false'; ?>;

// ═══════════════════════════════════════════════════════════════
//  BOOTSTRAP
// ═══════════════════════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', () => {
  fetchAudits();

  document.getElementById('factory-filter').addEventListener('change', () => applyFilters());
  document.getElementById('result-filter').addEventListener('change', () => applyFilters());
  document.getElementById('date-from').addEventListener('change', () => applyFilters());
  document.getElementById('date-to').addEventListener('change', () => applyFilters());
  document.getElementById('search-input').addEventListener('input', () => applyFilters());

  // Automatically update the radio button based on score value helper
  const scoreInput = document.getElementById('rs-score');
  if (scoreInput) {
    scoreInput.addEventListener('input', function() {
      const val = parseFloat(this.value);
      if (!isNaN(val)) {
        if (val >= 75) {
          document.querySelector('input[name="result"][value="Pass"]').checked = true;
        } else {
          document.querySelector('input[name="result"][value="Fail"]').checked = true;
        }
      }
    });
  }
});

// ═══════════════════════════════════════════════════════════════
//  DATA FETCH
// ═══════════════════════════════════════════════════════════════
function fetchAudits() {
  fetch('/backend/api/audits.php')
    .then(r => r.json())
    .then(res => {
      if (!res.success) {
        showToast(res.message || 'Failed to load audits', 'error');
        return;
      }
      allRows = res.data.map(r => ({
        ...r,
        AUDIT_DATE_RAW: parseOracleDate(r.AUDIT_DATE),
        NEXT_SCHEDULED_RAW: parseOracleDate(r.NEXT_SCHEDULED)
      }));
      populateFactoryFilter(allRows);

      // Hook search query parameter from global search bar
      const params = new URLSearchParams(window.location.search);
      const searchParam = params.get('search');
      if (searchParam) {
        document.getElementById('search-input').value = searchParam;
      }

      applyFilters();

      // Initialize sort headers using TableUtils
      TableUtils.initSortHeaders('audits-table', allRows, (sorted) => {
        applyFilters(sorted);
      });
    })
    .catch(() => showToast('Network error loading audits', 'error'));
}

function populateFactoryFilter(rows) {
  const select = document.getElementById('factory-filter');
  const currentValue = select.value;
  select.innerHTML = '<option value="">All Factories</option>';
  
  const factoryNames = [...new Set(rows.map(r => r.FACTORY_NAME))].sort();
  factoryNames.forEach(name => {
    const opt = document.createElement('option');
    opt.value = name;
    opt.textContent = name;
    select.appendChild(opt);
  });
  select.value = currentValue;
}

// ═══════════════════════════════════════════════════════════════
//  FILTER & SORTING LOGIC
// ═══════════════════════════════════════════════════════════════
function applyFilters(sortedData) {
  const factory = document.getElementById('factory-filter').value;
  const result = document.getElementById('result-filter').value;
  const fromVal = document.getElementById('date-from').value;
  const toVal = document.getElementById('date-to').value;
  const search = document.getElementById('search-input').value.trim();

  const fromTime = parseInputDate(fromVal);
  const toTime = parseInputDate(toVal);

  // Use sorted data if available, otherwise sort data using TableUtils current sort config
  const sourceData = sortedData || TableUtils.sortData(
    allRows,
    TableUtils.currentSortCol || 'AUDIT_ID',
    TableUtils.currentSortOrder || 'desc'
  );

  // Filter basic search and exact matches using TableUtils
  let filtered = TableUtils.filterData(sourceData, {
    search: search,
    FACTORY_NAME: factory,
    RESULT: (result === 'All' ? '' : result)
  });

  // Filter custom date range manually
  if (fromTime || toTime) {
    filtered = filtered.filter(r => {
      if (r.AUDIT_DATE) {
        const dateTime = parseOracleDate(r.AUDIT_DATE);
        if (fromTime && dateTime < fromTime) return false;
        if (toTime && dateTime > toTime) return false;
      } else {
        return false;
      }
      return true;
    });
  }

  renderTable(filtered);
}

function renderTable(filtered) {
  const displayRows = filtered || [];
  const tbody = document.getElementById('audits-tbody');
  
  if (displayRows.length === 0) {
    tbody.innerHTML = `<tr><td colspan="8" style="text-align: center; color: var(--text-secondary); padding: 32px;">No audits found matching the current filters.</td></tr>`;
    return;
  }

  tbody.innerHTML = displayRows.map(r => {
    const scoreVal = r.SCORE !== null ? Math.round(r.SCORE) : null;
    const progressFill = scoreVal !== null ? scoreVal : 0;
    const scoreDisplay = scoreVal !== null ? `${scoreVal}%` : 'N/A';
    const sColor = scoreColor(scoreVal);

    return `
      <tr>
        <td><strong>#${r.AUDIT_ID}</strong></td>
        <td><strong>${escHtml(r.FACTORY_NAME)}</strong></td>
        <td>${escHtml(r.INSPECTOR_NAME || '—')}</td>
        <td>${escHtml(r.AUDIT_DATE)}</td>
        <td style="min-width: 140px;">
          <div class="score-bar-container">
            <div class="score-bar">
              <div class="score-bar-fill" style="width: ${progressFill}%; background-color: ${sColor};"></div>
            </div>
            <span style="font-weight: 700; color: ${sColor}; font-size: 13px;">${scoreDisplay}</span>
          </div>
        </td>
        <td><span class="badge ${badgeClass(r.RESULT)}">${escHtml(r.RESULT)}</span></td>
        <td>${escHtml(r.NEXT_SCHEDULED || '—')}</td>
        <td style="text-align: right;">
          <div style="display: inline-flex; gap: 6px;">
            <button class="btn btn-secondary btn-sm" onclick="toggleDetails(${r.AUDIT_ID})">View</button>
            ${r.RESULT === 'Pending' && canRecord ? `
              <button class="btn btn-primary btn-sm" onclick='openRecordScoreModal(${JSON.stringify(r)})'>Record Score</button>
            ` : ''}
          </div>
        </td>
      </tr>
      <tr id="details-row-${r.AUDIT_ID}" class="accordion-details" style="display: none; background: rgba(255, 255, 255, 0.015);">
        <td colspan="8">
          <div class="accordion-content" style="padding: 16px; display: flex; flex-direction: column; gap: 16px; border-bottom: 1px solid var(--border-color);">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
              <div>
                <h4 style="font-size: 13px; text-transform: uppercase; color: var(--text-secondary); margin-bottom: 6px; letter-spacing: 0.5px;">Findings</h4>
                <div style="background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 8px; padding: 12px; min-height: 80px; white-space: pre-wrap; font-size: 14px; color: var(--text-primary); line-height: 1.5;">${escHtml(r.FINDINGS || 'No findings recorded.')}</div>
              </div>
              <div>
                <h4 style="font-size: 13px; text-transform: uppercase; color: var(--text-secondary); margin-bottom: 6px; letter-spacing: 0.5px;">Recommendations</h4>
                <div style="background: var(--bg-primary); border: 1px solid var(--border-color); border-radius: 8px; padding: 12px; min-height: 80px; white-space: pre-wrap; font-size: 14px; color: var(--text-primary); line-height: 1.5;">${escHtml(r.RECOMMENDATIONS || 'No recommendations recorded.')}</div>
              </div>
            </div>
          </div>
        </td>
      </tr>
    `;
  }).join('');
}

// ═══════════════════════════════════════════════════════════════
//  INTERACTIVE ACTIONS
// ═══════════════════════════════════════════════════════════════
function toggleDetails(auditId) {
  const row = document.getElementById(`details-row-${auditId}`);
  if (row.style.display === 'none') {
    row.style.display = 'table-row';
  } else {
    row.style.display = 'none';
  }
}

<?php if ($canSchedule): ?>
function openScheduleModal() {
  loadScheduleDropdowns();
  const modal = document.getElementById('schedule-modal');
  modal.classList.add('open');
  const form = modal.querySelector('form');
  if (form) {
    form.reset();
    clearErrors(form);
  }
}

function loadScheduleDropdowns() {
  // Load Factories list
  fetch('/backend/api/factories.php')
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        const select = document.getElementById('sa-factory');
        select.innerHTML = '<option value="">Select Factory</option>';
        res.data.forEach(f => {
          const opt = document.createElement('option');
          opt.value = f.FACTORY_ID;
          opt.textContent = f.FACTORY_NAME;
          select.appendChild(opt);
        });
      }
    });

  // Load Inspectors list (only users with role='inspector')
  fetch('/backend/api/users.php?role=inspector')
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        const select = document.getElementById('sa-inspector');
        select.innerHTML = '<option value="">Select Inspector</option>';
        res.data.forEach(u => {
          const opt = document.createElement('option');
          opt.value = u.USER_ID;
          opt.textContent = u.FULL_NAME;
          select.appendChild(opt);
        });
      }
    });
}

function submitScheduleAudit(e) {
  e.preventDefault();
  const f = e.target;

  // Use validateForm for schedule audit form
  const result = validateForm(f);
  if (!result.valid) {
    return;
  }

  const payload = {
    factory_id: parseInt(f.factory_id.value),
    inspector_id: parseInt(f.inspector_id.value),
    audit_date: f.audit_date.value,
    next_scheduled: f.next_scheduled.value || null
  };

  const btn = document.getElementById('schedule-submit-btn');
  btn.disabled = true;
  btn.textContent = 'Scheduling…';

  fetch('/backend/api/audits.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  })
  .then(r => r.json())
  .then(res => {
    if (res.success) {
      document.getElementById('schedule-modal').classList.remove('open');
      f.reset();
      clearErrors(f);
      showToast('Audit scheduled successfully', 'success');
      fetchAudits();
    } else {
      if (res.code === 20004) {
        showToast('Audit already scheduled for this factory this month.', 'error');
      } else {
        showToast(res.message || 'Scheduling failed', 'error');
      }
    }
  })
  .catch(() => showToast('Network error', 'error'))
  .finally(() => {
    btn.disabled = false;
    btn.textContent = 'Schedule Audit';
  });
}
<?php endif; ?>

<?php if ($canRecord): ?>
function openRecordScoreModal(audit) {
  const modal = document.getElementById('score-modal');
  const form = modal.querySelector('form');
  if (form) {
    form.reset();
    clearErrors(form);
  }

  document.getElementById('rs-audit-id').value = audit.AUDIT_ID;
  document.getElementById('rs-score').value = audit.SCORE !== null ? audit.SCORE : '';

  // Select result radio
  const resultVal = audit.RESULT === 'Pending' ? 'Pass' : audit.RESULT;
  const radio = document.querySelector(`input[name="result"][value="${resultVal}"]`);
  if (radio) {
    radio.checked = true;
  }

  document.getElementById('rs-findings').value = audit.FINDINGS || '';
  document.getElementById('rs-recommendations').value = audit.RECOMMENDATIONS || '';

  modal.classList.add('open');
}

function submitRecordScore(e) {
  e.preventDefault();
  const f = e.target;

  // Use validateForm for score modal form
  const result = validateForm(f);
  if (!result.valid) {
    return;
  }

  const payload = {
    audit_id: parseInt(f.audit_id.value),
    score: parseFloat(f.score.value),
    result: f.result.value,
    findings: f.findings.value.trim(),
    recommendations: f.recommendations.value.trim()
  };

  const btn = document.getElementById('score-submit-btn');
  btn.disabled = true;
  btn.textContent = 'Submitting Score…';

  fetch('/backend/api/audits.php', {
    method: 'PATCH',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  })
  .then(r => r.json())
  .then(res => {
    if (res.success) {
      document.getElementById('score-modal').classList.remove('open');
      f.reset();
      clearErrors(f);
      showToast('Audit score recorded successfully', 'success');
      fetchAudits();
    } else {
      showToast(res.message || 'Failed to record audit score', 'error');
    }
  })
  .catch(() => showToast('Network error', 'error'))
  .finally(() => {
    btn.disabled = false;
    btn.textContent = 'Submit Score';
  });
}
<?php endif; ?>

// ═══════════════════════════════════════════════════════════════
//  HELPERS & UTILITIES
// ═══════════════════════════════════════════════════════════════
function badgeClass(v) {
  return { 'Pass': 'badge-green', 'Fail': 'badge-red', 'Pending': 'badge-gray' }[v] || 'badge-gray';
}

function scoreColor(s) {
  if (s === null) return 'var(--text-secondary)';
  return s >= 75 ? 'var(--green)' : s >= 40 ? 'var(--amber)' : 'var(--red)';
}

function escHtml(s) {
  return String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function parseOracleDate(dateStr) {
  if (!dateStr) return 0;
  const months = { Jan:0, Feb:1, Mar:2, Apr:3, May:4, Jun:5,
                   Jul:6, Aug:7, Sep:8, Oct:9, Nov:10, Dec:11 };
  const parts = String(dateStr).split('-');
  if (parts.length !== 3) return 0;
  const day   = parseInt(parts[0], 10);
  const month = months[parts[1]];
  const year  = parseInt(parts[2], 10);
  if (isNaN(day) || month === undefined || isNaN(year)) return 0;
  return new Date(year, month, day).getTime();
}

function parseInputDate(dateStr) {
  if (!dateStr) return null;
  const parts = dateStr.split('-');
  if (parts.length !== 3) return null;
  return new Date(parseInt(parts[0], 10), parseInt(parts[1], 10) - 1, parseInt(parts[2], 10)).getTime();
}

function closeModal(id, event) {
  if (event.target === document.getElementById(id)) {
    document.getElementById(id).classList.remove('open');
  }
}
</script>
</body>
</html>
