<?php
// Direct access security guard
if (!isset($activePage) || $activePage !== 'salary') {
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
}

$canProcess = in_array($role, ['admin', 'compliance_officer']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GarmentGuard – Salary Management</title>
  <link rel="stylesheet" href="../../assets/css/style.css">
  <style>
    .filters-bar { display:flex; flex-wrap:wrap; gap:10px; margin-bottom:20px; align-items:center; }
    .filter-group { display:flex; flex-direction:column; gap:4px; min-width:110px; flex-grow:1; }
    .filter-group.search-group { flex-grow:1.5; min-width:150px; }
    .filter-select {
      background:var(--bg-secondary); border:1px solid var(--border-color);
      border-radius:8px; color:var(--text-primary); padding:9px 13px;
      font-family:var(--font-family); font-size:14px; outline:none;
      cursor:pointer; transition:border-color var(--transition-speed);
    }
    .filter-select:focus { border-color:var(--green); }

    /* Modal overlay */
    .modal-overlay {
      position:fixed; inset:0; z-index:1000; display:none;
      align-items:center; justify-content:center;
      background:rgba(10,15,30,.65); backdrop-filter:blur(4px);
    }
    .modal-overlay.open { display:flex; }
    .modal-box {
      background:var(--bg-secondary); border:1px solid var(--border-color);
      border-radius:16px; box-shadow:0 24px 60px rgba(0,0,0,.5);
      width:min(540px,96vw); max-height:90vh;
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
    .form-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:14px; }

    /* Calculation Preview styles */
    .preview-box {
      background:var(--bg-tertiary); border:1px solid var(--border-color);
      border-radius:10px; padding:16px; margin-top:8px; display:flex; flex-direction:column; gap:10px;
    }
    .preview-row { display:flex; justify-content:space-between; font-size:14px; }
    .preview-row.total-row {
      border-top:1px solid var(--border-color); padding-top:8px; margin-top:4px;
      font-weight:700; color:var(--green); font-size:16px;
    }
    .preview-label { color:var(--text-secondary); }
    .preview-val { color:var(--text-primary); font-weight:600; }
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
             class="nav-link <?php echo $key === 'salary' ? 'active' : ''; ?>">
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
      <h2 class="page-title">Salary Management</h2>
      <div class="user-profile-menu">
        <span style="font-weight:500;color:var(--text-secondary);">
          <?php echo htmlspecialchars($fullName); ?> (<?php echo htmlspecialchars(ucwords(str_replace('_',' ',$role))); ?>)
        </span>
        <div class="user-avatar"><?php echo strtoupper(substr($fullName,0,1)); ?></div>
      </div>
    </div>

    <!-- Monthly Summary Cards -->
    <div class="stats-grid" style="margin-bottom: 24px;">
      <div class="stat-card" style="border-left:4px solid var(--blue);">
        <div class="stat-icon" style="color:var(--blue);">💵</div>
        <div class="stat-value" id="summary-payroll">৳ 0</div>
        <div class="stat-label">Total Payroll (This Month)</div>
      </div>
      <div class="stat-card" style="border-left:4px solid var(--green);">
        <div class="stat-icon" style="color:var(--green);">👥</div>
        <div class="stat-value" id="summary-paid">0</div>
        <div class="stat-label">Total Workers Paid</div>
      </div>
      <div class="stat-card" style="border-left:4px solid var(--amber);">
        <div class="stat-icon" style="color:var(--amber);">⏳</div>
        <div class="stat-value" id="summary-pending">0</div>
        <div class="stat-label">Total Pending</div>
      </div>
      <div class="stat-card" style="border-left:4px solid var(--purple);">
        <div class="stat-icon" style="color:var(--purple);">🕒</div>
        <div class="stat-value" id="summary-ot-hours">0h</div>
        <div class="stat-label">Total OT Hours</div>
      </div>
    </div>

    <div class="card">
      <!-- Top Filter Bar -->
      <div class="filters-bar">
        <div class="filter-group">
          <label class="form-label" for="factory-filter">Factory</label>
          <select class="filter-select" id="factory-filter">
            <option value="">All Factories</option>
          </select>
        </div>
        <div class="filter-group search-group">
          <label class="form-label" for="search-input">Worker Name</label>
          <input type="text" class="search-input" id="search-input" placeholder="Search worker name…">
        </div>
        <div class="filter-group">
          <label class="form-label" for="month-filter">Month</label>
          <select class="filter-select" id="month-filter">
            <option value="">All Months</option>
            <option value="1">January</option>
            <option value="2">February</option>
            <option value="3">March</option>
            <option value="4">April</option>
            <option value="5">May</option>
            <option value="6">June</option>
            <option value="7">July</option>
            <option value="8">August</option>
            <option value="9">September</option>
            <option value="10">October</option>
            <option value="11">November</option>
            <option value="12">December</option>
          </select>
        </div>
        <div class="filter-group">
          <label class="form-label" for="year-filter">Year</label>
          <input type="number" class="filter-select" id="year-filter" value="<?php echo date('Y'); ?>" placeholder="e.g. 2026">
        </div>
        <div class="filter-group">
          <label class="form-label" for="status-filter">Payment Status</label>
          <select class="filter-select" id="status-filter">
            <option value="All">All Statuses</option>
            <option value="Pending">Pending</option>
            <option value="Paid">Paid</option>
          </select>
        </div>
        <?php if ($canProcess): ?>
          <div class="filter-group" style="min-width:auto; flex-grow:0; align-self:flex-end;">
            <button class="btn btn-primary" onclick="openProcessModal()">⚙️ Process Salary</button>
          </div>
        <?php endif; ?>
      </div>

      <!-- Salary Table -->
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>Worker Name</th>
              <th>Factory</th>
              <th>Month/Year</th>
              <th>Base Amount</th>
              <th>OT Hours</th>
              <th>OT Paid</th>
              <th>Deductions</th>
              <th>Net Salary</th>
              <th>Status</th>
              <th style="text-align:right;">Actions</th>
            </tr>
          </thead>
          <tbody id="salary-tbody">
            <tr><td colspan="10" style="text-align:center;color:var(--text-secondary);padding:32px;">Loading salary records…</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Process Salary Modal -->
<div class="modal-overlay" id="process-modal" onclick="closeModal('process-modal', event)">
  <div class="modal-box">
    <div class="modal-header">
      <h3>Process New Salary</h3>
      <button class="close-btn" onclick="document.getElementById('process-modal').classList.remove('open')">&times;</button>
    </div>
    <form id="process-form" onsubmit="submitProcessSalary(event)">
      <div class="modal-body">
        <div class="filter-group">
          <label class="form-label" for="modal-worker-select">Select Worker <span style="color:var(--red)">*</span></label>
          <select class="filter-select" id="modal-worker-select" name="worker_id" onchange="calculateLivePreview()" required>
            <option value="" disabled selected>Choose a worker…</option>
          </select>
        </div>
        
        <div class="form-grid-2">
          <div class="filter-group">
            <label class="form-label" for="modal-month">Month <span style="color:var(--red)">*</span></label>
            <select class="filter-select" id="modal-month" name="month" required>
              <option value="1" <?php if(date('n') == 1) echo 'selected'; ?>>January</option>
              <option value="2" <?php if(date('n') == 2) echo 'selected'; ?>>February</option>
              <option value="3" <?php if(date('n') == 3) echo 'selected'; ?>>March</option>
              <option value="4" <?php if(date('n') == 4) echo 'selected'; ?>>April</option>
              <option value="5" <?php if(date('n') == 5) echo 'selected'; ?>>May</option>
              <option value="6" <?php if(date('n') == 6) echo 'selected'; ?>>June</option>
              <option value="7" <?php if(date('n') == 7) echo 'selected'; ?>>July</option>
              <option value="8" <?php if(date('n') == 8) echo 'selected'; ?>>August</option>
              <option value="9" <?php if(date('n') == 9) echo 'selected'; ?>>September</option>
              <option value="10" <?php if(date('n') == 10) echo 'selected'; ?>>October</option>
              <option value="11" <?php if(date('n') == 11) echo 'selected'; ?>>November</option>
              <option value="12" <?php if(date('n') == 12) echo 'selected'; ?>>December</option>
            </select>
          </div>
          <div class="filter-group">
            <label class="form-label" for="modal-year">Year <span style="color:var(--red)">*</span></label>
            <input type="number" class="filter-select" id="modal-year" name="year" value="<?php echo date('Y'); ?>" min="2000" max="2100" required>
          </div>
        </div>

        <div class="form-grid-2">
          <div class="filter-group">
            <label class="form-label" for="modal-ot-hours">Overtime Hours (Max 60) <span style="color:var(--red)">*</span></label>
            <input type="number" class="filter-select" id="modal-ot-hours" name="overtime_hours" min="0" max="60" step="0.01" value="0" oninput="calculateLivePreview()" required>
          </div>
          <div class="filter-group">
            <label class="form-label" for="modal-deductions">Deductions (৳) <span style="color:var(--red)">*</span></label>
            <input type="number" class="filter-select" id="modal-deductions" name="deductions" min="0" step="0.01" value="0" oninput="calculateLivePreview()" required>
          </div>
        </div>

        <div>
          <label class="form-label">Calculation Preview</label>
          <div class="preview-box">
            <div class="preview-row">
              <span class="preview-label">Base Salary:</span>
              <span class="preview-val" id="preview-base">৳ 0.00</span>
            </div>
            <div class="preview-row">
              <span class="preview-label">OT Hourly Rate:</span>
              <span class="preview-val" id="preview-ot-rate">৳ 0.00 / hr</span>
            </div>
            <div class="preview-row">
              <span class="preview-label">OT Earnings:</span>
              <span class="preview-val" id="preview-ot-earnings">৳ 0.00</span>
            </div>
            <div class="preview-row">
              <span class="preview-label">Deductions:</span>
              <span class="preview-val" id="preview-deductions" style="color:var(--red)">-৳ 0.00</span>
            </div>
            <div class="preview-row total-row">
              <span class="preview-label" style="color:var(--green)">Net Salary:</span>
              <span class="preview-val" id="preview-net">৳ 0.00</span>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('process-modal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary" id="process-submit-btn">Process Salary</button>
      </div>
    </form>
  </div>
</div>

<script src="../../assets/js/toast.js"></script>
<script src="../../assets/js/table-utils.js"></script>
<script>
const months = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
let salaryRecords = [];
let factories = [];
let workers = [];

document.addEventListener('DOMContentLoaded', () => {
  // Load initial data
  fetchFactories();
  fetchWorkersList();
  fetchSalaryData();

  // Setup UI listeners
  document.getElementById('factory-filter').addEventListener('change', renderSalariesTable);
  document.getElementById('search-input').addEventListener('input', renderSalariesTable);
  document.getElementById('month-filter').addEventListener('change', fetchSalaryData);
  document.getElementById('year-filter').addEventListener('input', fetchSalaryData);
  document.getElementById('status-filter').addEventListener('change', fetchSalaryData);
});

// Fetch factories list for filter
function fetchFactories() {
  fetch('/backend/api/factories.php')
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        factories = res.data;
        const select = document.getElementById('factory-filter');
        factories.forEach(f => {
          const opt = document.createElement('option');
          opt.value = f.FACTORY_ID;
          opt.textContent = f.FACTORY_NAME;
          select.appendChild(opt);
        });
      }
    })
    .catch(err => console.error('Failed to load factories:', err));
}

// Fetch active workers to populate modal dropdown
function fetchWorkersList() {
  fetch('/backend/api/workers.php')
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        workers = res.data;
        const select = document.getElementById('modal-worker-select');
        // Populate active workers
        const activeWorkers = workers.filter(w => w.STATUS === 'Active');
        activeWorkers.forEach(w => {
          const opt = document.createElement('option');
          opt.value = w.WORKER_ID;
          opt.textContent = `${w.FULL_NAME} (${w.DESIGNATION} - ${w.FACTORY_NAME})`;
          select.appendChild(opt);
        });
        // Render table to ensure summary cards are updated with loaded worker data
        renderSalariesTable();
      }
    })
    .catch(err => console.error('Failed to load workers:', err));
}

// Fetch salaries data based on filters
function fetchSalaryData() {
  const month = document.getElementById('month-filter').value;
  const year = document.getElementById('year-filter').value;
  const status = document.getElementById('status-filter').value;
  const url = `/backend/api/salary.php?month=${encodeURIComponent(month)}&year=${encodeURIComponent(year)}&payment_status=${encodeURIComponent(status)}`;

  fetch(url)
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        salaryRecords = res.data;
        renderSalariesTable();
      } else {
        showToast(res.message || 'Failed to fetch salary data', 'error');
      }
    })
    .catch(() => showToast('Network error while loading salaries', 'error'));
}

// Render filtered records in UI table
function renderSalariesTable() {
  const factoryId = document.getElementById('factory-filter').value;
  const search = document.getElementById('search-input').value.trim();

  // Filter using TableUtils
  const filtered = TableUtils.filterData(salaryRecords, {
    FACTORY_ID: factoryId,
    search: search
  });

  // Update summary cards based on the active filtered dataset
  calculateSummary(filtered);

  const tbody = document.getElementById('salary-tbody');
  if (filtered.length === 0) {
    tbody.innerHTML = `<tr><td colspan="10" style="text-align:center;color:var(--text-secondary);padding:32px;">No salary records found.</td></tr>`;
    return;
  }

  tbody.innerHTML = filtered.map(r => {
    const isPending = r.PAYMENT_STATUS === 'Pending';
    const statusClass = isPending ? 'badge-amber' : 'badge-green';
    const actionBtn = isPending 
      ? `<button class="btn btn-secondary btn-sm" onclick="markAsPaid(${r.RECORD_ID})">Mark Paid</button>`
      : `<span style="font-size:12px;color:var(--text-secondary)">—</span>`;

    return `
      <tr>
        <td><strong>${escHtml(r.WORKER_NAME)}</strong></td>
        <td>${escHtml(r.FACTORY_NAME)}</td>
        <td>${months[r.MONTH]} ${r.YEAR}</td>
        <td style="white-space: nowrap;">৳ ${Number(r.BASE_AMOUNT).toLocaleString()}</td>
        <td style="white-space: nowrap;">${r.OVERTIME_HOURS} hrs</td>
        <td style="white-space: nowrap;">৳ ${Number(r.OVERTIME_PAID).toLocaleString()}</td>
        <td style="white-space: nowrap; color:var(--red)">৳ ${Number(r.DEDUCTIONS).toLocaleString()}</td>
        <td style="white-space: nowrap; font-weight:700; color:var(--green)">৳ ${Number(r.NET_SALARY).toLocaleString()}</td>
        <td><span class="badge ${statusClass}">${r.PAYMENT_STATUS}</span></td>
        <td style="text-align:right;">${actionBtn}</td>
      </tr>
    `;
  }).join('');
}

// Calculate summary cards
function calculateSummary(records) {
  // Group by WORKER_ID and keep only the latest record (max year and month) for each worker
  const latestRecordsMap = new Map();
  records.forEach(r => {
    const workerId = r.WORKER_ID;
    const recordTime = parseInt(r.YEAR) * 12 + parseInt(r.MONTH);
    
    if (!latestRecordsMap.has(workerId)) {
      latestRecordsMap.set(workerId, r);
    } else {
      const existing = latestRecordsMap.get(workerId);
      const existingTime = parseInt(existing.YEAR) * 12 + parseInt(existing.MONTH);
      if (recordTime > existingTime) {
        latestRecordsMap.set(workerId, r);
      }
    }
  });

  const distinctRecords = Array.from(latestRecordsMap.values());

  const factoryId = document.getElementById('factory-filter').value;
  // Get active workers matching the selected factory filter
  const activeWorkers = workers.filter(w => w.STATUS === 'Active' && (!factoryId || String(w.FACTORY_ID) === String(factoryId)));
  const totalActiveWorkers = activeWorkers.length;

  let totalPayroll = 0;
  let totalPaid = 0;
  let totalOtHours = 0;

  distinctRecords.forEach(r => {
    totalPayroll += parseFloat(r.NET_SALARY || 0);
    totalOtHours += parseFloat(r.OVERTIME_HOURS || 0);
    if (r.PAYMENT_STATUS === 'Paid') {
      totalPaid++;
    }
  });

  const totalPending = Math.max(0, totalActiveWorkers - totalPaid);

  // Render to DOM
  document.getElementById('summary-payroll').textContent = `৳ ${totalPayroll.toLocaleString(undefined, {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
  document.getElementById('summary-paid').textContent = totalPaid;
  document.getElementById('summary-pending').textContent = totalPending;
  document.getElementById('summary-ot-hours').textContent = `${totalOtHours.toFixed(1)}h`;

  // Dynamically update the Payroll card label based on current active month/year filters
  const monthFilter = document.getElementById('month-filter');
  const monthText = monthFilter.options[monthFilter.selectedIndex].text;
  const yearFilter = document.getElementById('year-filter').value;
  
  const payrollLabel = document.querySelector('#summary-payroll').nextElementSibling;
  if (monthFilter.value) {
    payrollLabel.textContent = `Total Payroll (${monthText} ${yearFilter})`;
  } else {
    payrollLabel.textContent = `Total Payroll (All Months ${yearFilter})`;
  }
}

// Mark record as Paid
function markAsPaid(recordId) {
  if (!confirm('Are you sure you want to mark this salary record as Paid?')) return;

  fetch('/backend/api/salary.php', {
    method: 'PATCH',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ record_id: recordId, status: 'Paid' })
  })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        showToast('Salary payment recorded successfully', 'success');
        fetchSalaryData();
      } else {
        showToast(res.message || 'Action failed', 'error');
      }
    })
    .catch(() => showToast('Network error', 'error'));
}

// Open modal for salary processing
function openProcessModal() {
  document.getElementById('process-form').reset();
  calculateLivePreview();
  document.getElementById('process-modal').classList.add('open');
}

// Live preview calculator
function calculateLivePreview() {
  const workerSelect = document.getElementById('modal-worker-select');
  const otInput = document.getElementById('modal-ot-hours');
  const dedInput = document.getElementById('modal-deductions');

  const selectedWorkerId = workerSelect.value;
  const overtimeHours = parseFloat(otInput.value) || 0;
  const deductions = parseFloat(dedInput.value) || 0;

  let baseAmount = 0;
  let otRate = 0;
  let otPaid = 0;
  let netSalary = 0;

  if (selectedWorkerId) {
    const worker = workers.find(w => String(w.WORKER_ID) === String(selectedWorkerId));
    if (worker) {
      baseAmount = parseFloat(worker.BASE_SALARY || 0);
      otRate = baseAmount / 26 / 8 * 1.25;
      otPaid = otRate * overtimeHours;
      netSalary = baseAmount + otPaid - deductions;
    }
  }

  // Populate preview DOM
  document.getElementById('preview-base').textContent = `৳ ${baseAmount.toLocaleString(undefined, {minimumFractionDigits: 2})}`;
  document.getElementById('preview-ot-rate').textContent = `৳ ${otRate.toLocaleString(undefined, {minimumFractionDigits: 2})} / hr`;
  document.getElementById('preview-ot-earnings').textContent = `৳ ${otPaid.toLocaleString(undefined, {minimumFractionDigits: 2})}`;
  document.getElementById('preview-deductions').textContent = `-৳ ${deductions.toLocaleString(undefined, {minimumFractionDigits: 2})}`;
  document.getElementById('preview-net').textContent = `৳ ${netSalary.toLocaleString(undefined, {minimumFractionDigits: 2})}`;
}

// Submit POST process salary
function submitProcessSalary(e) {
  e.preventDefault();
  const f = e.target;
  const overtimeHours = parseFloat(f.overtime_hours.value) || 0;

  // Validate OT hours input (max 60)
  if (overtimeHours < 0 || overtimeHours > 60) {
    showToast('Overtime hours must be between 0 and 60.', 'error');
    return;
  }

  const payload = {
    worker_id: f.worker_id.value,
    month: parseInt(f.month.value),
    year: parseInt(f.year.value),
    overtime_hours: overtimeHours,
    deductions: parseFloat(f.deductions.value) || 0
  };

  const btn = document.getElementById('process-submit-btn');
  btn.disabled = true;
  btn.textContent = 'Processing…';

  fetch('/backend/api/salary.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  })
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        showToast(`Salary processed successfully! Net: ৳ ${res.net_salary.toLocaleString()}`, 'success');
        document.getElementById('process-modal').classList.remove('open');
        f.reset();
        fetchSalaryData();
      } else {
        showToast(res.message || 'Processing failed', 'error');
      }
    })
    .catch(() => showToast('Network error while processing salary', 'error'))
    .finally(() => {
      btn.disabled = false;
      btn.textContent = 'Process Salary';
    });
}

function escHtml(s) {
  return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

function closeModal(id, event) {
  if (event.target === document.getElementById(id)) {
    document.getElementById(id).classList.remove('open');
  }
}
</script>
</body>
</html>
