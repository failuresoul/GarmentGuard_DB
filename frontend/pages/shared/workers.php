<?php
// Direct access security guard
if (!isset($activePage) || $activePage !== 'workers') {
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
    ];
}

$canAdd = in_array($role, ['admin', 'compliance_officer']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GarmentGuard – Worker Management</title>
  <meta name="description" content="Manage garment factory workers, view details, salary, and compliance status.">
  <link rel="stylesheet" href="../../assets/css/style.css">
  <style>
    /* ── Sortable headers ──────────────────────────────── */
    .sortable-header { cursor:pointer; user-select:none; transition:background-color var(--transition-speed); }
    .sortable-header:hover { background:rgba(255,255,255,.05); }
    .sort-indicator { margin-left:6px; font-size:11px; color:var(--text-secondary); }
    .sortable-header.active { color:var(--green); }
    .sortable-header.active .sort-indicator { color:var(--green); }

    /* ── Filters bar ───────────────────────────────────── */
    .filters-bar { display:flex; flex-wrap:wrap; gap:14px; margin-bottom:20px; align-items:center; }
    .filter-group { display:flex; flex-direction:column; gap:4px; min-width:160px; flex-grow:1; }
    .filter-group.search-group { flex-grow:3; }
    .filter-select {
      background:var(--bg-secondary); border:1px solid var(--border-color);
      border-radius:8px; color:var(--text-primary); padding:9px 13px;
      font-family:var(--font-family); font-size:14px; outline:none;
      cursor:pointer; transition:border-color var(--transition-speed);
    }
    .filter-select:focus { border-color:var(--green); }

    /* ── Pagination bar ────────────────────────────────── */
    .pagination-bar {
      display:flex; align-items:center; justify-content:space-between;
      margin-top:20px; flex-wrap:wrap; gap:12px;
    }
    .pagination-info { font-size:13px; color:var(--text-secondary); }
    .pagination-controls { display:flex; align-items:center; gap:8px; }
    .page-btn {
      background:var(--bg-secondary); border:1px solid var(--border-color);
      border-radius:6px; color:var(--text-primary); padding:6px 14px;
      font-size:13px; cursor:pointer; transition:all var(--transition-speed);
    }
    .page-btn:hover:not(:disabled) { border-color:var(--green); color:var(--green); }
    .page-btn:disabled { opacity:.4; cursor:not-allowed; }
    .per-page-select {
      background:var(--bg-secondary); border:1px solid var(--border-color);
      border-radius:6px; color:var(--text-primary); padding:6px 10px;
      font-size:13px; cursor:pointer; outline:none;
    }

    /* ── Modal overlay ─────────────────────────────────── */
    .modal-overlay {
      position:fixed; inset:0; z-index:1000; display:none;
      align-items:center; justify-content:center;
      background:rgba(10,15,30,.65); backdrop-filter:blur(4px);
    }
    .modal-overlay.open { display:flex; }
    .modal-box {
      background:var(--bg-secondary); border:1px solid var(--border-color);
      border-radius:16px; box-shadow:0 24px 60px rgba(0,0,0,.5);
      width:min(580px,96vw); max-height:90vh;
      display:flex; flex-direction:column; animation:slideUp .25s ease;
    }
    @keyframes slideUp {
      from { opacity:0; transform:translateY(24px); }
      to   { opacity:1; transform:translateY(0); }
    }
    .modal-header {
      padding:20px 24px; border-bottom:1px solid var(--border-color);
      display:flex; justify-content:space-between; align-items:center; flex-shrink:0;
    }
    .modal-header h3 { font-size:18px; font-weight:700; color:var(--text-primary); }
    .close-btn {
      background:none; border:none; font-size:26px; line-height:1;
      color:var(--text-secondary); cursor:pointer; transition:color var(--transition-speed);
    }
    .close-btn:hover { color:var(--red); }
    .modal-body { padding:24px; overflow-y:auto; flex-grow:1; display:flex; flex-direction:column; gap:16px; }
    .modal-footer {
      padding:20px 24px; border-top:1px solid var(--border-color);
      display:flex; justify-content:flex-end; gap:10px; flex-shrink:0;
      background:rgba(30,41,59,.9);
    }

    /* ── Detail modal cards ────────────────────────────── */
    .detail-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
    .detail-card {
      background:var(--bg-tertiary); border:1px solid var(--border-color);
      border-radius:10px; padding:14px 16px;
    }
    .detail-card .label { font-size:11px; color:var(--text-secondary); text-transform:uppercase; letter-spacing:.5px; margin-bottom:4px; }
    .detail-card .value { font-size:15px; font-weight:600; color:var(--text-primary); }
    .detail-card.full { grid-column:1/-1; }
    .ytd-highlight {
      background:linear-gradient(135deg,rgba(16,185,129,.15),rgba(16,185,129,.05));
      border-color:rgba(16,185,129,.4);
    }
    .ytd-highlight .value { color:var(--green); font-size:20px; }

    /* ── Form grid ─────────────────────────────────────── */
    .form-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
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
             class="nav-link <?php echo $key === 'workers' ? 'active' : ''; ?>">
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
      <h2 class="page-title">Worker Management</h2>
      <div class="user-profile-menu">
        <span style="font-weight:500;color:var(--text-secondary);">
          <?php echo htmlspecialchars($fullName); ?> (<?php echo htmlspecialchars(ucwords(str_replace('_',' ',$role))); ?>)
        </span>
        <div class="user-avatar"><?php echo strtoupper(substr($fullName,0,1)); ?></div>
      </div>
    </div>

    <div class="card">
      <!-- Filters Bar -->
      <div class="filters-bar">
        <div class="filter-group search-group">
          <input type="text" class="search-input" id="search-input" placeholder="Search by name, national ID or designation…">
        </div>
        <div class="filter-group">
          <select class="filter-select" id="factory-filter">
            <option value="">All Factories</option>
          </select>
        </div>
        <div class="filter-group">
          <select class="filter-select" id="status-filter">
            <option value="">All Statuses</option>
            <option value="Active">Active</option>
            <option value="Inactive">Inactive</option>
            <option value="Terminated">Terminated</option>
          </select>
        </div>
        <div class="filter-group">
          <select class="filter-select" id="designation-filter">
            <option value="">All Designations</option>
            <option value="Operator">Operator</option>
            <option value="Helper">Helper</option>
            <option value="QC Inspector">QC Inspector</option>
            <option value="Supervisor">Supervisor</option>
            <option value="Manager">Manager</option>
          </select>
        </div>
        <?php if ($canAdd): ?>
          <button class="btn btn-primary" id="add-worker-btn" onclick="openAddModal()">➕ Add Worker</button>
        <?php endif; ?>
      </div>

      <!-- Table -->
      <div class="table-responsive">
        <table class="table" id="workers-table">
          <thead>
            <tr>
              <th style="width:44px">#</th>
              <th class="sortable-header" data-sort="FULL_NAME">Full Name <span class="sort-indicator">▲▼</span></th>
              <th>National ID</th>
              <th>Factory</th>
              <th>Designation</th>
              <th>Shift</th>
              <th class="sortable-header" data-sort="BASE_SALARY">Base Salary <span class="sort-indicator">▲▼</span></th>
              <th>Status</th>
              <th class="sortable-header" data-sort="JOIN_DATE_RAW">Join Date <span class="sort-indicator">▲▼</span></th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody id="workers-tbody">
            <tr><td colspan="10" style="text-align:center;color:var(--text-secondary);padding:32px;">Loading workers…</td></tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="pagination-bar" style="display:flex; justify-content:space-between; align-items:center;">
        <div style="display:flex;align-items:center;gap:10px;">
          <span class="pagination-info">Rows per page:</span>
          <select class="per-page-select" id="per-page"></select>
        </div>
        <div id="pagination-controls-container" style="display: flex; align-items: center;"></div>
      </div>
    </div>
  </div>
</div>

<!-- ══════════════════════════════════════════════════
     WORKER DETAIL MODAL
══════════════════════════════════════════════════ -->
<div id="detail-modal" class="modal-overlay" onclick="closeModal('detail-modal', event)">
  <div class="modal-box">
    <div class="modal-header">
      <h3 id="detail-modal-title">Worker Details</h3>
      <button class="close-btn" onclick="document.getElementById('detail-modal').classList.remove('open')">&times;</button>
    </div>
    <div class="modal-body" id="detail-modal-body">
      <p style="color:var(--text-secondary);">Loading…</p>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="document.getElementById('detail-modal').classList.remove('open')">Close</button>
    </div>
  </div>
</div>

<!-- ══════════════════════════════════════════════════
     EDIT STATUS MODAL  (role-gated)
══════════════════════════════════════════════════ -->
<?php if ($canAdd): ?>
<div id="edit-modal" class="modal-overlay" onclick="closeModal('edit-modal', event)">
  <div class="modal-box" style="width:min(420px,96vw);">
    <div class="modal-header">
      <h3>Update Worker Status</h3>
      <button class="close-btn" onclick="document.getElementById('edit-modal').classList.remove('open')">&times;</button>
    </div>
    <div class="modal-body">
      <p style="color:var(--text-secondary);margin-bottom:4px;" id="edit-worker-name"></p>
      <div class="form-group">
        <label class="form-label" for="edit-status">New Status <span style="color:var(--red)">*</span></label>
        <select class="form-control" id="edit-status">
          <option value="Active">Active</option>
          <option value="Inactive">Inactive</option>
          <option value="Terminated">Terminated</option>
        </select>
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="document.getElementById('edit-modal').classList.remove('open')">Cancel</button>
      <button class="btn btn-primary" onclick="submitStatusEdit()">Save Changes</button>
    </div>
  </div>
</div>

<!-- ══════════════════════════════════════════════════
     ADD WORKER MODAL  (role-gated)
══════════════════════════════════════════════════ -->
<div id="add-modal" class="modal-overlay" onclick="closeModal('add-modal', event)">
  <div class="modal-box">
    <div class="modal-header">
      <h3>Register New Worker</h3>
      <button class="close-btn" onclick="document.getElementById('add-modal').classList.remove('open')">&times;</button>
    </div>
    <form id="add-worker-form" onsubmit="submitAddWorker(event)" style="display:contents;">
      <div class="modal-body">

        <div class="form-group">
          <label class="form-label" for="aw-factory">Factory <span style="color:var(--red)">*</span></label>
          <select class="form-control" id="aw-factory" name="factory_id" data-required="true">
            <option value="">Select Factory</option>
          </select>
        </div>

        <div class="form-grid-2">
          <div class="form-group">
            <label class="form-label" for="aw-name">Full Name <span style="color:var(--red)">*</span></label>
            <input type="text" class="form-control" id="aw-name" name="full_name" placeholder="Worker full name" data-required="true">
          </div>
          <div class="form-group">
            <label class="form-label" for="aw-nid">National ID <span style="color:var(--red)">*</span></label>
            <input type="text" class="form-control" id="aw-nid" name="national_id" placeholder="NID-XXXXX" data-required="true">
          </div>
        </div>

        <div class="form-grid-2">
          <div class="form-group">
            <label class="form-label" for="aw-desig">Designation <span style="color:var(--red)">*</span></label>
            <select class="form-control" id="aw-desig" name="designation" data-required="true">
              <option value="">Select</option>
              <option value="Operator">Operator</option>
              <option value="Helper">Helper</option>
              <option value="QC Inspector">QC Inspector</option>
              <option value="Supervisor">Supervisor</option>
              <option value="Manager">Manager</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label" for="aw-shift">Shift <span style="color:var(--red)">*</span></label>
            <select class="form-control" id="aw-shift" name="shift" data-required="true">
              <option value="">Select</option>
              <option value="Morning">Morning</option>
              <option value="Evening">Evening</option>
              <option value="Night">Night</option>
            </select>
          </div>
        </div>

        <div class="form-grid-2">
          <div class="form-group">
            <label class="form-label" for="aw-join">Join Date <span style="color:var(--red)">*</span></label>
            <input type="date" class="form-control" id="aw-join" name="join_date" data-required="true">
          </div>
          <div class="form-group">
            <label class="form-label" for="aw-salary">Base Salary (৳) <span style="color:var(--red)">*</span></label>
            <input type="number" class="form-control" id="aw-salary" name="base_salary" placeholder="e.g. 12000" min="0" step="0.01" data-required="true" data-min="0">
          </div>
        </div>

        <div class="form-grid-2">
          <div class="form-group">
            <label class="form-label" for="aw-phone">Phone</label>
            <input type="text" class="form-control" id="aw-phone" name="phone" placeholder="01XXXXXXXXX">
          </div>
          <div class="form-group">
            <label class="form-label" for="aw-email">Email</label>
            <input type="email" class="form-control" id="aw-email" name="email" placeholder="worker@example.com">
          </div>
        </div>

      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('add-modal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary" id="add-submit-btn">Register Worker</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<script src="../../assets/js/toast.js"></script>
<script src="../../assets/js/table-utils.js"></script>
<script>
// ═══════════════════════════════════════════════════════════════
//  STATE
// ═══════════════════════════════════════════════════════════════
let allWorkers   = [];
let currentPage  = 1;
let perPage      = 10;
let editingWorkerId = null;

// ═══════════════════════════════════════════════════════════════
//  BOOTSTRAP
// ═══════════════════════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', () => {
  fetchWorkers();

  document.getElementById('search-input')     .addEventListener('input',  () => { currentPage = 1; applyFilters(); });
  document.getElementById('factory-filter')   .addEventListener('change', () => { currentPage = 1; applyFilters(); });
  document.getElementById('status-filter')    .addEventListener('change', () => { currentPage = 1; applyFilters(); });
  document.getElementById('designation-filter').addEventListener('change', () => { currentPage = 1; applyFilters(); });
});

// ═══════════════════════════════════════════════════════════════
//  DATA FETCH
// ═══════════════════════════════════════════════════════════════
function fetchWorkers() {
  fetch('/backend/api/workers.php')
    .then(r => r.json())
    .then(res => {
      if (!res.success) { showToast(res.message || 'Failed to load workers', 'error'); return; }
      allWorkers = res.data.map(w => ({ ...w, JOIN_DATE_RAW: parseOracleDate(w.JOIN_DATE) }));
      populateFactoryFilter();
      
      // Hook search query parameter from global search bar
      const params = new URLSearchParams(window.location.search);
      const searchParam = params.get('search');
      if (searchParam) {
        document.getElementById('search-input').value = searchParam;
      }
      
      applyFilters();

      // Initialize sort headers using TableUtils
      TableUtils.initSortHeaders('workers-table', allWorkers, (sorted) => {
        applyFilters(sorted);
      });

      // Initialize page size selector using TableUtils
      TableUtils.pageSizeSelector('per-page', (size) => {
        perPage = size;
        currentPage = 1;
        applyFilters();
      });
    })
    .catch(() => showToast('Network error loading workers', 'error'));
}

function populateFactoryFilter() {
  const sel = document.getElementById('factory-filter');
  const seen = new Set();
  allWorkers.forEach(w => {
    if (!seen.has(w.FACTORY_ID)) {
      seen.add(w.FACTORY_ID);
      const opt = document.createElement('option');
      opt.value = w.FACTORY_ID;
      opt.textContent = w.FACTORY_NAME;
      sel.appendChild(opt);
    }
  });

  // Also populate Add Worker factory dropdown if it exists
  const awSel = document.getElementById('aw-factory');
  if (awSel) {
    seen.forEach(fid => {
      const name = allWorkers.find(w => w.FACTORY_ID == fid)?.FACTORY_NAME || fid;
      const opt = document.createElement('option');
      opt.value = fid;
      opt.textContent = name;
      awSel.appendChild(opt);
    });
  }
}

// ═══════════════════════════════════════════════════════════════
//  FILTER + SORT + PAGINATE
// ═══════════════════════════════════════════════════════════════
function applyFilters(sortedData) {
  const q    = document.getElementById('search-input').value.toLowerCase().trim();
  const fac  = document.getElementById('factory-filter').value;
  const stat = document.getElementById('status-filter').value;
  const desig= document.getElementById('designation-filter').value;

  // Use sorted data if provided, otherwise sort data using TableUtils current sort config
  const sourceData = sortedData || TableUtils.sortData(
    allWorkers,
    TableUtils.currentSortCol || 'FULL_NAME',
    TableUtils.currentSortOrder || 'asc'
  );

  // Filter using TableUtils
  const filtered = TableUtils.filterData(sourceData, {
    search: q,
    FACTORY_ID: fac,
    STATUS: stat,
    DESIGNATION: desig
  });

  renderPage(filtered);
}

function renderPage(filtered) {
  const paginated = TableUtils.paginate(filtered, currentPage, perPage);
  const total = paginated.total;
  const start = (paginated.page - 1) * paginated.pageSize;

  // Render pagination controls using TableUtils
  TableUtils.renderPagination('pagination-controls-container', paginated, (newPage) => {
    currentPage = newPage;
    applyFilters();
  });

  const tbody = document.getElementById('workers-tbody');
  if (total === 0) {
    tbody.innerHTML = `<tr><td colspan="10" style="text-align:center;color:var(--text-secondary);padding:32px;">No workers match the selected filters.</td></tr>`;
    return;
  }

  tbody.innerHTML = paginated.rows.map((w, idx) => `
    <tr>
      <td style="color:var(--text-secondary);font-size:13px;">${start + idx + 1}</td>
      <td><strong>${escHtml(w.FULL_NAME)}</strong></td>
      <td style="font-size:13px;color:var(--text-secondary);">${escHtml(w.NATIONAL_ID)}</td>
      <td>${escHtml(w.FACTORY_NAME)}</td>
      <td>${escHtml(w.DESIGNATION)}</td>
      <td><span class="badge ${shiftBadge(w.SHIFT)}">${escHtml(w.SHIFT)}</span></td>
      <td>৳ ${Number(w.BASE_SALARY).toLocaleString()}</td>
      <td><span class="badge ${statusBadge(w.STATUS)}">${escHtml(w.STATUS)}</span></td>
      <td>${escHtml(w.JOIN_DATE)}</td>
      <td style="white-space:nowrap;">
        <div style="display:inline-flex;gap:6px;">
          <button class="btn btn-secondary btn-sm" onclick='openDetailModal(${JSON.stringify(w)})'>View</button>
          <?php if ($canAdd): ?>
          <button class="btn btn-secondary btn-sm" onclick='openEditModal(${JSON.stringify(w)})'>Edit</button>
          <?php endif; ?>
        </div>
      </td>
    </tr>
  `).join('');
}

function changePage(dir) {
  currentPage += dir;
  applyFilters();
}

// ═══════════════════════════════════════════════════════════════
//  BADGE HELPERS
// ═══════════════════════════════════════════════════════════════
function statusBadge(v) {
  return { Active:'badge-green', Inactive:'badge-amber', Terminated:'badge-red' }[v] || 'badge-gray';
}
function shiftBadge(v) {
  return { Morning:'badge-blue', Evening:'badge-purple', Night:'badge-gray', Day:'badge-blue' }[v] || 'badge-gray';
}
function escHtml(s) {
  return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// Parse Oracle DD-Mon-YYYY date string to timestamp for sorting
// e.g. "12-Jul-2024" → 1720742400000
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

// ═══════════════════════════════════════════════════════════════
//  DETAIL MODAL
// ═══════════════════════════════════════════════════════════════
function openDetailModal(w) {
  document.getElementById('detail-modal-title').textContent = w.FULL_NAME;
  document.getElementById('detail-modal-body').innerHTML = `
    <div class="detail-grid">
      <div class="detail-card ytd-highlight full">
        <div class="label">YTD Salary (Current Year)</div>
        <div class="value">৳ ${Number(w.YTD_SALARY || 0).toLocaleString()}</div>
      </div>
      <div class="detail-card"><div class="label">National ID</div><div class="value">${escHtml(w.NATIONAL_ID)}</div></div>
      <div class="detail-card"><div class="label">Factory</div><div class="value">${escHtml(w.FACTORY_NAME)}</div></div>
      <div class="detail-card"><div class="label">Designation</div><div class="value">${escHtml(w.DESIGNATION)}</div></div>
      <div class="detail-card"><div class="label">Shift</div><div class="value"><span class="badge ${shiftBadge(w.SHIFT)}">${escHtml(w.SHIFT)}</span></div></div>
      <div class="detail-card"><div class="label">Base Salary</div><div class="value">৳ ${Number(w.BASE_SALARY).toLocaleString()}</div></div>
      <div class="detail-card"><div class="label">Status</div><div class="value"><span class="badge ${statusBadge(w.STATUS)}">${escHtml(w.STATUS)}</span></div></div>
      <div class="detail-card"><div class="label">Join Date</div><div class="value">${escHtml(w.JOIN_DATE)}</div></div>
      <div class="detail-card"><div class="label">Phone</div><div class="value">${escHtml(w.PHONE || '—')}</div></div>
      <div class="detail-card"><div class="label">Email</div><div class="value">${escHtml(w.EMAIL || '—')}</div></div>
    </div>`;
  document.getElementById('detail-modal').classList.add('open');
}

// ═══════════════════════════════════════════════════════════════
//  EDIT MODAL
// ═══════════════════════════════════════════════════════════════
function openEditModal(w) {
  editingWorkerId = w.WORKER_ID;
  document.getElementById('edit-worker-name').textContent = `Worker: ${w.FULL_NAME}`;
  document.getElementById('edit-status').value = w.STATUS;
  document.getElementById('edit-modal').classList.add('open');
}

function submitStatusEdit() {
  const newStatus = document.getElementById('edit-status').value;
  fetch('/backend/api/workers.php', {
    method: 'PATCH',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ worker_id: editingWorkerId, status: newStatus })
  })
  .then(r => r.json())
  .then(res => {
    if (res.success) {
      document.getElementById('edit-modal').classList.remove('open');
      showToast('Worker status updated successfully', 'success');
      fetchWorkers();
    } else {
      showToast(res.message || 'Update failed', 'error');
    }
  })
  .catch(() => showToast('Network error', 'error'));
}

// ═══════════════════════════════════════════════════════════════
//  ADD WORKER MODAL
// ═══════════════════════════════════════════════════════════════
function openAddModal() {
  const modal = document.getElementById('add-modal');
  modal.classList.add('open');
  const form = modal.querySelector('form');
  if (form) {
    form.reset();
    clearErrors(form);
  }
}

function submitAddWorker(e) {
  e.preventDefault();
  const f = e.target;
  
  // Use validateForm for validation
  const result = validateForm(f);
  if (!result.valid) {
    return;
  }

  const payload = {
    factory_id:  f.factory_id.value,
    full_name:   f.full_name.value.trim(),
    national_id: f.national_id.value.trim(),
    designation: f.designation.value,
    join_date:   f.join_date.value,
    base_salary: parseFloat(f.base_salary.value),
    shift:       f.shift.value,
    phone:       f.phone.value.trim(),
    email:       f.email.value.trim()
  };

  const btn = document.getElementById('add-submit-btn');
  btn.disabled = true;
  btn.textContent = 'Registering…';

  fetch('/backend/api/workers.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  })
  .then(r => r.json())
  .then(res => {
    if (res.success) {
      document.getElementById('add-modal').classList.remove('open');
      f.reset();
      showToast('Worker registered successfully', 'success');
      fetchWorkers();
    } else {
      if (res.code === 20001) {
        showToast('Factory is Non-Compliant. Cannot hire worker.', 'error');
      } else {
        showToast(res.message || 'Registration failed', 'error');
      }
    }
  })
  .catch(() => showToast('Network error', 'error'))
  .finally(() => { btn.disabled = false; btn.textContent = 'Register Worker'; });
}

// ═══════════════════════════════════════════════════════════════
//  UTILITY
// ═══════════════════════════════════════════════════════════════
function closeModal(id, event) {
  if (event.target === document.getElementById(id)) {
    document.getElementById(id).classList.remove('open');
  }
}
</script>
</body>
</html>
