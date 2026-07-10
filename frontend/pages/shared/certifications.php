<?php
// Direct access security guard
if (!isset($activePage) || $activePage !== 'certifications') {
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
}

$canEdit = in_array($role, ['admin', 'compliance_officer']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="Manage factory certifications, track expiry dates, and compliance status.">
  <title>GarmentGuard – Certifications</title>
  <link rel="stylesheet" href="../../assets/css/style.css">
  <style>
    .filters-bar { display:flex; flex-wrap:wrap; gap:10px; margin-bottom:20px; align-items:flex-end; }
    .filter-group { display:flex; flex-direction:column; gap:4px; min-width:140px; flex-grow:1; }
    .filter-group.search-group { flex-grow:2; min-width:180px; }
    .filter-select, .search-input {
      background:var(--bg-secondary); border:1px solid var(--border-color);
      border-radius:8px; color:var(--text-primary); padding:9px 13px;
      font-family:var(--font-family); font-size:14px; outline:none;
      width:100%; box-sizing:border-box;
      cursor:pointer; transition:border-color var(--transition-speed);
    }
    .filter-select:focus, .search-input:focus { border-color:var(--green); }

    /* Summary cards */
    .cert-summary { display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:18px; margin-bottom:28px; }
    .stat-card {
      background:var(--bg-secondary); border:1px solid var(--border-color);
      border-radius:14px; padding:22px 20px; display:flex; flex-direction:column; gap:8px;
      transition:transform var(--transition-speed), box-shadow var(--transition-speed);
    }
    .stat-card:hover { transform:translateY(-3px); box-shadow:0 10px 30px rgba(0,0,0,.25); }
    .stat-icon { font-size:28px; }
    .stat-value { font-size:30px; font-weight:800; color:var(--text-primary); line-height:1; }
    .stat-label { font-size:13px; color:var(--text-secondary); font-weight:500; }

    /* Days left colors */
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
      width:min(560px,96vw); max-height:92vh;
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
    }
    .form-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:14px; }

    /* Edit modal */
    #edit-modal .modal-box { width:min(480px,96vw); }
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
             class="nav-link <?php echo $key === 'certifications' ? 'active' : ''; ?>">
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
      <h2 class="page-title">Certifications</h2>
      <div class="user-profile-menu">
        <span style="font-weight:500;color:var(--text-secondary);">
          <?php echo htmlspecialchars($fullName); ?> (<?php echo htmlspecialchars(ucwords(str_replace('_',' ',$role))); ?>)
        </span>
        <div class="user-avatar"><?php echo strtoupper(substr($fullName,0,1)); ?></div>
      </div>
    </div>

    <!-- Summary Cards -->
    <div class="cert-summary">
      <div class="stat-card" style="border-left:4px solid var(--blue);">
        <div class="stat-icon">🏅</div>
        <div class="stat-value" id="sum-total">—</div>
        <div class="stat-label">Total Certifications</div>
      </div>
      <div class="stat-card" style="border-left:4px solid var(--green);">
        <div class="stat-icon">✅</div>
        <div class="stat-value" id="sum-active">—</div>
        <div class="stat-label">Active</div>
      </div>
      <div class="stat-card" style="border-left:4px solid var(--red);">
        <div class="stat-icon">❌</div>
        <div class="stat-value" id="sum-expired">—</div>
        <div class="stat-label">Expired</div>
      </div>
      <div class="stat-card" style="border-left:4px solid var(--amber);">
        <div class="stat-icon">⚠️</div>
        <div class="stat-value" id="sum-expiring">—</div>
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
          <label class="form-label" for="status-filter">Status</label>
          <select class="filter-select" id="status-filter">
            <option value="All">All Statuses</option>
            <option value="Active">Active</option>
            <option value="Expired">Expired</option>
            <option value="Revoked">Revoked</option>
          </select>
        </div>
        <div class="filter-group search-group">
          <label class="form-label" for="cert-search">Cert Name</label>
          <input type="text" class="search-input" id="cert-search" placeholder="Search certification name…">
        </div>
        <?php if ($canEdit): ?>
          <div class="filter-group" style="min-width:auto;flex-grow:0;align-self:flex-end;">
            <button class="btn btn-primary" id="add-cert-btn" onclick="openAddModal()">➕ Add Certification</button>
          </div>
        <?php endif; ?>
      </div>

      <!-- Table -->
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>Factory</th>
              <th>Cert Name</th>
              <th>Issuing Body</th>
              <th>Issue Date</th>
              <th>Expiry Date</th>
              <th>Days Left</th>
              <th>Status</th>
              <?php if ($canEdit): ?><th>Actions</th><?php endif; ?>
            </tr>
          </thead>
          <tbody id="cert-tbody">
            <tr><td colspan="8" style="text-align:center;color:var(--text-secondary);padding:32px;">Loading certifications…</td></tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<?php if ($canEdit): ?>
<!-- ── Add Certification Modal ─────────────────────────────────────────── -->
<div class="modal-overlay" id="add-modal">
  <div class="modal-box">
    <div class="modal-header">
      <h3>➕ Add Certification</h3>
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
            <label class="form-label" for="add-cert-name">Cert Name <span style="color:var(--red)">*</span></label>
            <input type="text" class="form-control" id="add-cert-name" name="cert_name" placeholder="e.g. ISO 9001" required>
          </div>
          <div class="form-group">
            <label class="form-label" for="add-issuing-body">Issuing Body</label>
            <input type="text" class="form-control" id="add-issuing-body" name="issuing_body" placeholder="e.g. Bureau Veritas">
          </div>
        </div>
        <div class="form-grid-2">
          <div class="form-group">
            <label class="form-label" for="add-issue-date">Issue Date</label>
            <input type="date" class="form-control" id="add-issue-date" name="issue_date">
          </div>
          <div class="form-group">
            <label class="form-label" for="add-expiry-date">Expiry Date <span style="color:var(--red)">*</span></label>
            <input type="date" class="form-control" id="add-expiry-date" name="expiry_date" required>
          </div>
        </div>
        <div class="form-group">
          <label class="form-label" for="add-status">Status</label>
          <select class="filter-select" id="add-status" name="status">
            <option value="Active">Active</option>
            <option value="Expired">Expired</option>
            <option value="Revoked">Revoked</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeAddModal()">Cancel</button>
        <button type="submit" class="btn btn-primary" id="add-submit-btn">Add Certification</button>
      </div>
    </form>
  </div>
</div>

<!-- ── Edit / Mark Status Modal ──────────────────────────────────────── -->
<div class="modal-overlay" id="edit-modal">
  <div class="modal-box">
    <div class="modal-header">
      <h3>✏️ Update Certification Status</h3>
      <button class="close-btn" onclick="closeEditModal()">×</button>
    </div>
    <div class="modal-body">
      <div id="edit-cert-info" style="background:var(--bg-tertiary);border:1px solid var(--border-color);border-radius:10px;padding:16px;margin-bottom:8px;"></div>
      <div class="form-group">
        <label class="form-label" for="edit-status">New Status</label>
        <select class="filter-select" id="edit-status">
          <option value="Active">Active</option>
          <option value="Expired">Expired</option>
          <option value="Revoked">Revoked</option>
        </select>
      </div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Cancel</button>
      <button type="button" class="btn btn-primary" id="edit-save-btn" onclick="submitEdit()">Update Status</button>
    </div>
  </div>
</div>
<?php endif; ?>

<script src="../../assets/js/toast.js"></script>
<script>
let allCerts = [];
let factories = [];
let editCertId = null;

document.addEventListener('DOMContentLoaded', () => {
  fetchFactories();
  fetchCerts();
  document.getElementById('factory-filter').addEventListener('change', renderTable);
  document.getElementById('status-filter').addEventListener('change', fetchCerts);
  document.getElementById('cert-search').addEventListener('input', renderTable);
});

function fetchFactories() {
  fetch('/backend/api/factories.php')
    .then(r => r.json())
    .then(res => {
      if (!res.success) return;
      factories = res.data;
      const fFilter = document.getElementById('factory-filter');
      const fAdd    = document.getElementById('add-factory');
      factories.forEach(f => {
        const o1 = new Option(f.FACTORY_NAME, f.FACTORY_ID);
        const o2 = new Option(f.FACTORY_NAME, f.FACTORY_ID);
        fFilter.appendChild(o1);
        if (fAdd) fAdd.appendChild(o2);
      });
    })
    .catch(e => console.error('Failed to load factories:', e));
}

function fetchCerts() {
  const status = document.getElementById('status-filter').value;
  let url = '/backend/api/certifications.php';
  if (status && status !== 'All') url += `?status=${encodeURIComponent(status)}`;

  document.getElementById('cert-tbody').innerHTML =
    `<tr><td colspan="8" style="text-align:center;color:var(--text-secondary);padding:32px;">Loading…</td></tr>`;

  fetch(url)
    .then(r => r.json())
    .then(res => {
      if (!res.success) { showToast('Failed to load certifications', 'error'); return; }
      allCerts = res.data;
      renderTable();
    })
    .catch(() => showToast('Network error', 'error'));
}

function renderTable() {
  const factoryId = document.getElementById('factory-filter').value;
  const search    = document.getElementById('cert-search').value.toLowerCase().trim();

  let filtered = allCerts;
  if (factoryId) filtered = filtered.filter(c => String(c.FACTORY_ID) === String(factoryId));
  if (search)    filtered = filtered.filter(c => (c.CERT_NAME||'').toLowerCase().includes(search));

  // Update summary cards
  const now = new Date();
  const in30 = filtered.filter(c => {
    const d = parseFloat(c.DAYS_LEFT);
    return !isNaN(d) && d >= 0 && d <= 30 && c.STATUS === 'Active';
  }).length;
  document.getElementById('sum-total').textContent   = filtered.length;
  document.getElementById('sum-active').textContent  = filtered.filter(c => c.STATUS === 'Active').length;
  document.getElementById('sum-expired').textContent = filtered.filter(c => c.STATUS === 'Expired').length;
  document.getElementById('sum-expiring').textContent= in30;

  const canEdit = <?php echo $canEdit ? 'true' : 'false'; ?>;
  const tbody = document.getElementById('cert-tbody');

  if (!filtered.length) {
    tbody.innerHTML = `<tr><td colspan="8" style="text-align:center;color:var(--text-secondary);padding:32px;">No certifications found.</td></tr>`;
    return;
  }

  tbody.innerHTML = filtered.map(c => {
    const days    = parseFloat(c.DAYS_LEFT);
    let daysHtml;
    if (isNaN(days)) {
      daysHtml = `<span class="days-na">—</span>`;
    } else if (days < 0) {
      daysHtml = `<span class="days-red">Expired</span>`;
    } else if (days < 30) {
      daysHtml = `<span class="days-red">${Math.round(days)} days</span>`;
    } else if (days < 90) {
      daysHtml = `<span class="days-amber">${Math.round(days)} days</span>`;
    } else {
      daysHtml = `<span class="days-green">${Math.round(days)} days</span>`;
    }

    const statusClass = c.STATUS === 'Active' ? 'badge-green' : c.STATUS === 'Expired' ? 'badge-red' : 'badge-gray';

    const actionsHtml = canEdit ? `
      <div style="display:flex;gap:6px;flex-wrap:nowrap;">
        <button class="btn btn-sm btn-secondary" onclick="openEditModal(${c.CERT_ID},'${(c.CERT_NAME||'').replace(/'/g,"\\'")}','${c.FACTORY_NAME||''}','${c.STATUS}')">✏️ Edit</button>
        ${c.STATUS === 'Active' ? `<button class="btn btn-sm" style="background:var(--red);color:#fff;" onclick="quickMarkExpired(${c.CERT_ID})">Mark Expired</button>` : ''}
      </div>` : '—';

    return `<tr>
      <td style="font-weight:600;">${c.FACTORY_NAME || '—'}</td>
      <td>${c.CERT_NAME || '—'}</td>
      <td style="color:var(--text-secondary);">${c.ISSUING_BODY || '—'}</td>
      <td style="color:var(--text-secondary);">${formatDate(c.ISSUE_DATE)}</td>
      <td>${formatDate(c.EXPIRY_DATE)}</td>
      <td>${daysHtml}</td>
      <td><span class="badge ${statusClass}">${c.STATUS}</span></td>
      <td>${actionsHtml}</td>
    </tr>`;
  }).join('');
}

function formatDate(d) {
  if (!d) return '—';
  const dt = new Date(d);
  return isNaN(dt) ? d : dt.toLocaleDateString('en-GB', {day:'2-digit',month:'short',year:'numeric'});
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
    factory_id:   f.factory_id.value,
    cert_name:    f.cert_name.value.trim(),
    issuing_body: f.issuing_body.value.trim(),
    issue_date:   f.issue_date.value,
    expiry_date:  f.expiry_date.value,
    status:       f.status.value,
  };

  fetch('/backend/api/certifications.php', {
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body: JSON.stringify(payload)
  })
  .then(r => r.json())
  .then(res => {
    btn.disabled = false; btn.textContent = 'Add Certification';
    if (res.success) {
      showToast('Certification added!', 'success');
      closeAddModal();
      fetchCerts();
    } else {
      showToast(res.message || 'Failed to add', 'error');
    }
  })
  .catch(() => { btn.disabled=false; btn.textContent='Add Certification'; showToast('Network error', 'error'); });
}

function openEditModal(certId, certName, factoryName, currentStatus) {
  editCertId = certId;
  document.getElementById('edit-cert-info').innerHTML = `
    <div style="font-size:14px;line-height:1.8;">
      <strong style="font-size:15px;">${certName}</strong><br>
      <span style="color:var(--text-secondary);">Factory: ${factoryName}</span><br>
      <span style="color:var(--text-secondary);">Current Status: <strong>${currentStatus}</strong></span>
    </div>`;
  document.getElementById('edit-status').value = currentStatus;
  document.getElementById('edit-modal').classList.add('open');
}
function closeEditModal() {
  document.getElementById('edit-modal').classList.remove('open');
  editCertId = null;
}

function submitEdit() {
  if (!editCertId) return;
  const newStatus = document.getElementById('edit-status').value;
  const btn = document.getElementById('edit-save-btn');
  btn.disabled = true; btn.textContent = 'Saving…';

  fetch('/backend/api/certifications.php', {
    method:'PATCH',
    headers:{'Content-Type':'application/json'},
    body: JSON.stringify({ cert_id: editCertId, status: newStatus })
  })
  .then(r => r.json())
  .then(res => {
    btn.disabled = false; btn.textContent = 'Update Status';
    if (res.success) {
      showToast(res.message || 'Status updated!', 'success');
      closeEditModal();
      fetchCerts();
    } else {
      showToast(res.message || 'Update failed', 'error');
    }
  })
  .catch(() => { btn.disabled=false; btn.textContent='Update Status'; showToast('Network error', 'error'); });
}

function quickMarkExpired(certId) {
  if (!confirm('Mark this certification as Expired?')) return;
  fetch('/backend/api/certifications.php', {
    method:'PATCH',
    headers:{'Content-Type':'application/json'},
    body: JSON.stringify({ cert_id: certId, status: 'Expired' })
  })
  .then(r => r.json())
  .then(res => {
    if (res.success) { showToast('Marked as Expired', 'success'); fetchCerts(); }
    else showToast(res.message || 'Failed', 'error');
  })
  .catch(() => showToast('Network error', 'error'));
}
</script>
</body>
</html>
