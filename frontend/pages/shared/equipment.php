<?php
// Direct access security guard
if (!isset($activePage) || $activePage !== 'equipment') {
    header("HTTP/1.1 403 Forbidden");
    exit("Direct access forbidden");
}
if (session_status() === PHP_SESSION_NONE) session_start();
$role     = $_SESSION['role']      ?? '';
$fullName = $_SESSION['full_name'] ?? 'User';

$navMenu = [];
if ($role === 'admin') {
    $navMenu = [
        'dashboard'      => ['📊 Dashboard',       'dashboard.php'],
        'factories'      => ['🏭 Factories',        'factories.php'],
        'workers'        => ['👷 Workers',          'workers.php'],
        'audits'         => ['📋 Audits',           'audits.php'],
        'grievances'     => ['📣 Grievances',       'grievances.php'],
        'salary'         => ['💰 Salaries',         'salary.php'],
        'certifications' => ['🏅 Certifications',   'certifications.php'],
        'equipment'      => ['🧯 Safety Equipment', 'equipment.php'],
        'buyer'          => ['🛒 Buyers',           'buyer.php'],
        'reports'        => ['📈 Reports',          'reports.php'],
        'users'          => ['👤 Users',            'users.php'],
    ];
} elseif ($role === 'compliance_officer') {
    $navMenu = [
        'dashboard'      => ['📊 Dashboard',       'dashboard.php'],
        'factories'      => ['🏭 Factories',        'factories.php'],
        'workers'        => ['👷 Workers',          'workers.php'],
        'audits'         => ['📋 Audits',           'audits.php'],
        'grievances'     => ['📣 Grievances',       'grievances.php'],
        'salary'         => ['💰 Salaries',         'salary.php'],
        'certifications' => ['🏅 Certifications',   'certifications.php'],
        'equipment'      => ['🧯 Safety Equipment', 'equipment.php'],
        'buyer'          => ['🛒 Buyers',           'buyer.php'],
        'reports'        => ['📈 Reports',          'reports.php'],
    ];
} elseif ($role === 'inspector') {
    $navMenu = [
        'dashboard'  => ['📊 Dashboard',       'dashboard.php'],
        'factories'  => ['🏭 Factories',        'factories.php'],
        'audits'     => ['📋 Audits',           'audits.php'],
        'equipment'  => ['🧯 Safety Equipment', 'equipment.php'],
    ];
}

$canEdit = in_array($role, ['admin', 'compliance_officer', 'inspector']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Track and manage safety equipment across garment factories.">
  <title>GarmentGuard – Safety Equipment</title>
  <link rel="stylesheet" href="../../assets/css/style.css">
  <style>
    .filters-bar { display:flex; flex-wrap:wrap; gap:10px; margin-bottom:20px; align-items:flex-end; }
    .filter-group { display:flex; flex-direction:column; gap:4px; min-width:140px; flex-grow:1; }
    .filter-select {
      background:var(--bg-secondary); border:1px solid var(--border-color);
      border-radius:8px; color:var(--text-primary); padding:9px 13px;
      font-family:var(--font-family); font-size:14px; outline:none;
      width:100%; box-sizing:border-box;
      cursor:pointer; transition:border-color var(--transition-speed);
    }
    .filter-select:focus { border-color:var(--green); }

    /* Alert banner */
    .alert-banner {
      display:flex; align-items:center; gap:14px; padding:14px 20px;
      background:rgba(239,68,68,.12); border:1px solid var(--red);
      border-radius:12px; margin-bottom:20px; animation:pulse 2s infinite;
    }
    @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:.8} }
    .alert-banner-icon { font-size:24px; }
    .alert-banner-text { font-size:14px; font-weight:600; color:var(--red); }

    /* Summary cards */
    .equip-summary { display:grid; grid-template-columns:repeat(auto-fit, minmax(170px, 1fr)); gap:18px; margin-bottom:28px; }
    .stat-card {
      background:var(--bg-secondary); border:1px solid var(--border-color);
      border-radius:14px; padding:22px 20px; display:flex; flex-direction:column; gap:8px;
      transition:transform var(--transition-speed), box-shadow var(--transition-speed);
    }
    .stat-card:hover { transform:translateY(-3px); box-shadow:0 10px 30px rgba(0,0,0,.25); }
    .stat-icon { font-size:28px; }
    .stat-value { font-size:30px; font-weight:800; color:var(--text-primary); line-height:1; }
    .stat-label { font-size:13px; color:var(--text-secondary); font-weight:500; }

    /* Condition badges */
    .cond-good     { background:rgba(34,197,94,.15);  color:#22c55e; padding:3px 10px; border-radius:6px; font-size:12px; font-weight:700; }
    .cond-fair     { background:rgba(251,191,36,.15); color:#fbbf24; padding:3px 10px; border-radius:6px; font-size:12px; font-weight:700; }
    .cond-poor     { background:rgba(249,115,22,.15); color:#f97316; padding:3px 10px; border-radius:6px; font-size:12px; font-weight:700; }
    .cond-critical { background:rgba(239,68,68,.15);  color:#ef4444; padding:3px 10px; border-radius:6px; font-size:12px; font-weight:700; }

    .days-red   { color:var(--red);   font-weight:700; }
    .days-amber { color:var(--amber); font-weight:700; }
    .days-green { color:var(--green); font-weight:600; }
    .days-na    { color:var(--text-secondary); }

    /* Modal */
    .modal-overlay {
      position:fixed; inset:0; z-index:1000; display:none;
      align-items:center; justify-content:center;
      background:rgba(10,15,30,.65); backdrop-filter:blur(4px);
    }
    .modal-overlay.open { display:flex; }
    .modal-box {
      background:var(--bg-secondary); border:1px solid var(--border-color);
      border-radius:16px; box-shadow:0 24px 60px rgba(0,0,0,.5);
      width:min(580px,96vw); max-height:92vh;
      display:flex; flex-direction:column; animation:slideUp .25s ease;
    }
    #inspect-modal .modal-box { width:min(440px,96vw); }
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
    }
    .form-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
    .form-grid-3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:14px; }
  </style>
</head>
<body>
<div class="app-container">

  <!-- Sidebar -->
  <div class="sidebar" id="sidebar">
    <div class="brand">
      <span class="brand-title">GarmentGuard</span>
      <span class="brand-subtitle">Compliance System</span>
    </div>
    <ul class="nav-menu">
      <?php foreach ($navMenu as $key => $item): ?>
        <li>
          <a href="<?php echo htmlspecialchars($item[1]); ?>"
             class="nav-link <?php echo $key === 'equipment' ? 'active' : ''; ?>">
            <?php echo htmlspecialchars($item[0]); ?>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
    <div class="nav-footer">
      <a href="../../../backend/auth/logout.php" class="nav-link">🚪 Logout</a>
    </div>
  </div>

  <!-- Main Content -->
  <div class="main-content">
    <div class="top-bar">
      <h2 class="page-title">Safety Equipment</h2>
      <div class="user-profile-menu">
        <span style="font-weight:500;color:var(--text-secondary);">
          <?php echo htmlspecialchars($fullName); ?> (<?php echo htmlspecialchars(ucwords(str_replace('_',' ',$role))); ?>)
        </span>
        <div class="user-avatar"><?php echo strtoupper(substr($fullName,0,1)); ?></div>
      </div>
    </div>

    <!-- Alert Banner (hidden by default) -->
    <div class="alert-banner" id="expiry-alert" style="display:none;">
      <div class="alert-banner-icon">🚨</div>
      <div class="alert-banner-text" id="expiry-alert-text">Some equipment is expiring soon!</div>
    </div>

    <!-- Summary Cards -->
    <div class="equip-summary">
      <div class="stat-card" style="border-left:4px solid var(--blue);">
        <div class="stat-icon">🧯</div>
        <div class="stat-value" id="sum-total">—</div>
        <div class="stat-label">Total Equipment</div>
      </div>
      <div class="stat-card" style="border-left:4px solid var(--green);">
        <div class="stat-icon">✅</div>
        <div class="stat-value" id="sum-good">—</div>
        <div class="stat-label">Good Condition</div>
      </div>
      <div class="stat-card" style="border-left:4px solid var(--amber);">
        <div class="stat-icon">⚠️</div>
        <div class="stat-value" id="sum-needscheck">—</div>
        <div class="stat-label">Fair / Poor</div>
      </div>
      <div class="stat-card" style="border-left:4px solid var(--red);">
        <div class="stat-icon">🔴</div>
        <div class="stat-value" id="sum-critical">—</div>
        <div class="stat-label">Critical</div>
      </div>
      <div class="stat-card" style="border-left:4px solid #a855f7;">
        <div class="stat-icon">📅</div>
        <div class="stat-value" id="sum-expiring30">—</div>
        <div class="stat-label">Expiring in 30 Days</div>
      </div>
    </div>

    <div class="card">
      <!-- Filter Bar -->
      <div class="filters-bar">
        <div class="filter-group">
          <label class="form-label" for="factory-filter">Factory</label>
          <select class="filter-select" id="factory-filter">
            <option value="">All Factories</option>
          </select>
        </div>
        <div class="filter-group">
          <label class="form-label" for="cond-filter">Condition</label>
          <select class="filter-select" id="cond-filter">
            <option value="All">All Conditions</option>
            <option value="Good">Good</option>
            <option value="Fair">Fair</option>
            <option value="Poor">Poor</option>
            <option value="Critical">Critical</option>
          </select>
        </div>
        <div class="filter-group">
          <label class="form-label" for="expiry-filter">Expiring Within</label>
          <select class="filter-select" id="expiry-filter">
            <option value="All">All</option>
            <option value="30">30 Days</option>
            <option value="60">60 Days</option>
            <option value="90">90 Days</option>
          </select>
        </div>
        <?php if ($canEdit): ?>
          <div class="filter-group" style="min-width:auto;flex-grow:0;align-self:flex-end;">
            <button class="btn btn-primary" onclick="openAddModal()">➕ Add Equipment</button>
          </div>
        <?php endif; ?>
      </div>

      <!-- Table -->
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>Factory</th>
              <th>Equipment Type</th>
              <th>Qty</th>
              <th>Purchase Date</th>
              <th>Expiry Date</th>
              <th>Last Inspection</th>
              <th>Condition</th>
              <th>Location</th>
              <?php if ($canEdit): ?><th>Actions</th><?php endif; ?>
            </tr>
          </thead>
          <tbody id="equip-tbody">
            <tr><td colspan="9" style="text-align:center;color:var(--text-secondary);padding:32px;">Loading equipment…</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php if ($canEdit): ?>
<!-- ── Add Equipment Modal ─────────────────────────────────────────────── -->
<div class="modal-overlay" id="add-modal">
  <div class="modal-box">
    <div class="modal-header">
      <h3>➕ Add Safety Equipment</h3>
      <button class="close-btn" onclick="closeAddModal()">×</button>
    </div>
    <form id="add-form" onsubmit="submitAdd(event)">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label" for="add-factory">Factory <span style="color:var(--red)">*</span></label>
          <select class="filter-select" id="add-factory" name="factory_id" required>
            <option value="">Select factory…</option>
          </select>
        </div>
        <div class="form-grid-2">
          <div class="form-group">
            <label class="form-label" for="add-etype">Equipment Type <span style="color:var(--red)">*</span></label>
            <input type="text" class="form-control" id="add-etype" name="equipment_type" placeholder="e.g. Fire Extinguisher" required>
          </div>
          <div class="form-group">
            <label class="form-label" for="add-qty">Quantity</label>
            <input type="number" class="form-control" id="add-qty" name="quantity" value="1" min="0">
          </div>
        </div>
        <div class="form-grid-2">
          <div class="form-group">
            <label class="form-label" for="add-purchase">Purchase Date</label>
            <input type="date" class="form-control" id="add-purchase" name="purchase_date">
          </div>
          <div class="form-group">
            <label class="form-label" for="add-expiry">Expiry Date</label>
            <input type="date" class="form-control" id="add-expiry" name="expiry_date">
          </div>
        </div>
        <div class="form-grid-2">
          <div class="form-group">
            <label class="form-label" for="add-cond">Condition Status</label>
            <select class="filter-select" id="add-cond" name="condition_status">
              <option value="Good">Good</option>
              <option value="Fair">Fair</option>
              <option value="Poor">Poor</option>
              <option value="Critical">Critical</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label" for="add-location">Location</label>
            <input type="text" class="form-control" id="add-location" name="location" placeholder="e.g. Floor 2, Block A">
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeAddModal()">Cancel</button>
        <button type="submit" class="btn btn-primary" id="add-submit-btn">Add Equipment</button>
      </div>
    </form>
  </div>
</div>

<!-- ── Mark Inspected Modal ───────────────────────────────────────────── -->
<div class="modal-overlay" id="inspect-modal">
  <div class="modal-box">
    <div class="modal-header">
      <h3>🔍 Mark Inspected</h3>
      <button class="close-btn" onclick="closeInspectModal()">×</button>
    </div>
    <div class="modal-body">
      <div id="inspect-info" style="background:var(--bg-tertiary);border:1px solid var(--border-color);border-radius:10px;padding:16px;margin-bottom:8px;"></div>
      <div class="form-group">
        <label class="form-label" for="inspect-cond">Update Condition Status</label>
        <select class="filter-select" id="inspect-cond">
          <option value="Good">Good</option>
          <option value="Fair">Fair</option>
          <option value="Poor">Poor</option>
          <option value="Critical">Critical</option>
        </select>
      </div>
      <p style="font-size:13px;color:var(--text-secondary);">This will set the Last Inspection date to today and update the condition.</p>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary" onclick="closeInspectModal()">Cancel</button>
      <button type="button" class="btn btn-primary" id="inspect-save-btn" onclick="submitInspect()">Confirm Inspection</button>
    </div>
  </div>
</div>
<?php endif; ?>

<script src="../../assets/js/toast.js"></script>
<script>
let allEquipment = [];
let inspectEquipmentId = null;

document.addEventListener('DOMContentLoaded', () => {
  fetchFactories();
  fetchEquipment();
  document.getElementById('factory-filter').addEventListener('change', renderTable);
  document.getElementById('cond-filter').addEventListener('change', fetchEquipment);
  document.getElementById('expiry-filter').addEventListener('change', fetchEquipment);
});

function fetchFactories() {
  fetch('/backend/api/factories.php')
    .then(r => r.json())
    .then(res => {
      if (!res.success) return;
      const fFilter = document.getElementById('factory-filter');
      const fAdd    = document.getElementById('add-factory');
      res.data.forEach(f => {
        fFilter.appendChild(new Option(f.FACTORY_NAME, f.FACTORY_ID));
        if (fAdd) fAdd.appendChild(new Option(f.FACTORY_NAME, f.FACTORY_ID));
      });
    })
    .catch(e => console.error('Factories load error:', e));
}

function fetchEquipment() {
  const cond   = document.getElementById('cond-filter').value;
  const expiry = document.getElementById('expiry-filter').value;
  let url = '/backend/api/equipment.php?';
  if (cond && cond !== 'All')   url += `condition_status=${encodeURIComponent(cond)}&`;
  if (expiry && expiry !== 'All') url += `expiry_days=${encodeURIComponent(expiry)}&`;

  document.getElementById('equip-tbody').innerHTML =
    `<tr><td colspan="9" style="text-align:center;color:var(--text-secondary);padding:32px;">Loading…</td></tr>`;

  fetch(url)
    .then(r => r.json())
    .then(res => {
      if (!res.success) { showToast('Failed to load equipment', 'error'); return; }
      allEquipment = res.data;
      renderTable();
    })
    .catch(() => showToast('Network error', 'error'));
}

function renderTable() {
  const factoryId = document.getElementById('factory-filter').value;
  let filtered = allEquipment;
  if (factoryId) filtered = filtered.filter(e => String(e.FACTORY_ID) === String(factoryId));

  // Update summary cards
  document.getElementById('sum-total').textContent      = filtered.length;
  document.getElementById('sum-good').textContent       = filtered.filter(e => e.CONDITION_STATUS === 'Good').length;
  document.getElementById('sum-needscheck').textContent = filtered.filter(e => ['Fair','Poor'].includes(e.CONDITION_STATUS)).length;
  document.getElementById('sum-critical').textContent   = filtered.filter(e => e.CONDITION_STATUS === 'Critical').length;

  const expiring30 = filtered.filter(e => {
    const d = parseFloat(e.DAYS_TO_EXPIRY);
    return !isNaN(d) && d >= 0 && d <= 30;
  });
  document.getElementById('sum-expiring30').textContent = expiring30.length;

  // Alert banner for expiring within 7 days
  const expiring7 = filtered.filter(e => {
    const d = parseFloat(e.DAYS_TO_EXPIRY);
    return !isNaN(d) && d >= 0 && d <= 7;
  });
  const alertBanner = document.getElementById('expiry-alert');
  if (expiring7.length > 0) {
    alertBanner.style.display = 'flex';
    document.getElementById('expiry-alert-text').textContent =
      `🚨 URGENT: ${expiring7.length} piece${expiring7.length > 1 ? 's' : ''} of safety equipment will expire within 7 days! Immediate action required.`;
  } else {
    alertBanner.style.display = 'none';
  }

  const canEdit = <?php echo $canEdit ? 'true' : 'false'; ?>;
  const tbody = document.getElementById('equip-tbody');

  if (!filtered.length) {
    tbody.innerHTML = `<tr><td colspan="9" style="text-align:center;color:var(--text-secondary);padding:32px;">No equipment found.</td></tr>`;
    return;
  }

  tbody.innerHTML = filtered.map(e => {
    const days = parseFloat(e.DAYS_TO_EXPIRY);
    let expiryHtml;
    if (!e.EXPIRY_DATE) {
      expiryHtml = `<span class="days-na">—</span>`;
    } else if (isNaN(days) || days < 0) {
      expiryHtml = `<span style="color:var(--text-secondary);">${formatDate(e.EXPIRY_DATE)}</span><br><span class="days-red" style="font-size:11px;">Expired</span>`;
    } else if (days < 30) {
      expiryHtml = `${formatDate(e.EXPIRY_DATE)}<br><span class="days-red" style="font-size:11px;">${Math.round(days)} days left</span>`;
    } else if (days < 60) {
      expiryHtml = `${formatDate(e.EXPIRY_DATE)}<br><span class="days-amber" style="font-size:11px;">${Math.round(days)} days left</span>`;
    } else {
      expiryHtml = `${formatDate(e.EXPIRY_DATE)}<br><span class="days-green" style="font-size:11px;">${Math.round(days)} days left</span>`;
    }

    const condClass = {
      'Good':'cond-good','Fair':'cond-fair','Poor':'cond-poor','Critical':'cond-critical'
    }[e.CONDITION_STATUS] || 'cond-fair';

    const actionsHtml = canEdit ? `
      <div style="display:flex;gap:6px;flex-wrap:nowrap;">
        <button class="btn btn-sm btn-secondary"
          onclick="openInspectModal(${e.EQUIPMENT_ID},'${(e.EQUIPMENT_TYPE||'').replace(/'/g,"\\'")}','${e.FACTORY_NAME||''}','${e.CONDITION_STATUS}')">
          🔍 Inspect
        </button>
      </div>` : '—';

    return `<tr>
      <td style="font-weight:600;">${e.FACTORY_NAME || '—'}</td>
      <td>${e.EQUIPMENT_TYPE || '—'}</td>
      <td style="text-align:center;">${e.QUANTITY ?? '—'}</td>
      <td style="color:var(--text-secondary);">${formatDate(e.PURCHASE_DATE)}</td>
      <td>${expiryHtml}</td>
      <td style="color:var(--text-secondary);">${formatDate(e.LAST_INSPECTION)}</td>
      <td><span class="${condClass}">${e.CONDITION_STATUS || '—'}</span></td>
      <td style="color:var(--text-secondary);">${e.LOCATION || '—'}</td>
      <td>${actionsHtml}</td>
    </tr>`;
  }).join('');
}

function formatDate(d) {
  if (!d) return '—';
  const dt = new Date(d);
  return isNaN(dt) ? d : dt.toLocaleDateString('en-GB', {day:'2-digit', month:'short', year:'numeric'});
}

function openAddModal() {
  document.getElementById('add-form').reset();
  document.getElementById('add-modal').classList.add('open');
}
function closeAddModal() {
  document.getElementById('add-modal').classList.remove('open');
}

function submitAdd(e) {
  e.preventDefault();
  const f = e.target;
  const btn = document.getElementById('add-submit-btn');
  btn.disabled = true; btn.textContent = 'Adding…';

  const payload = {
    factory_id:       f.factory_id.value,
    equipment_type:   f.equipment_type.value.trim(),
    quantity:         parseInt(f.quantity.value) || 0,
    purchase_date:    f.purchase_date.value,
    expiry_date:      f.expiry_date.value,
    condition_status: f.condition_status.value,
    location:         f.location.value.trim(),
  };

  fetch('/backend/api/equipment.php', {
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body: JSON.stringify(payload)
  })
  .then(r => r.json())
  .then(res => {
    btn.disabled = false; btn.textContent = 'Add Equipment';
    if (res.success) {
      showToast('Equipment added!', 'success');
      closeAddModal();
      fetchEquipment();
    } else {
      showToast(res.message || 'Failed to add', 'error');
    }
  })
  .catch(() => { btn.disabled=false; btn.textContent='Add Equipment'; showToast('Network error','error'); });
}

function openInspectModal(id, type, factory, currentCond) {
  inspectEquipmentId = id;
  document.getElementById('inspect-info').innerHTML = `
    <div style="font-size:14px;line-height:1.8;">
      <strong style="font-size:15px;">${type}</strong><br>
      <span style="color:var(--text-secondary);">Factory: ${factory}</span><br>
      <span style="color:var(--text-secondary);">Current Condition: <strong>${currentCond}</strong></span>
    </div>`;
  document.getElementById('inspect-cond').value = currentCond;
  document.getElementById('inspect-modal').classList.add('open');
}
function closeInspectModal() {
  document.getElementById('inspect-modal').classList.remove('open');
  inspectEquipmentId = null;
}

function submitInspect() {
  if (!inspectEquipmentId) return;
  const btn = document.getElementById('inspect-save-btn');
  btn.disabled = true; btn.textContent = 'Saving…';
  const newCond = document.getElementById('inspect-cond').value;

  fetch('/backend/api/equipment.php', {
    method:'PATCH',
    headers:{'Content-Type':'application/json'},
    body: JSON.stringify({ equipment_id: inspectEquipmentId, action:'inspect', condition_status: newCond })
  })
  .then(r => r.json())
  .then(res => {
    btn.disabled = false; btn.textContent = 'Confirm Inspection';
    if (res.success) {
      showToast('Inspection recorded!', 'success');
      closeInspectModal();
      fetchEquipment();
    } else {
      showToast(res.message || 'Failed', 'error');
    }
  })
  .catch(() => { btn.disabled=false; btn.textContent='Confirm Inspection'; showToast('Network error','error'); });
}
</script>
</body>
</html>
