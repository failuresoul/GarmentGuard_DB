<?php
// Direct access security guard
if (!isset($activePage) || $activePage !== 'grievances') {
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
$canSubmit = ($role === 'compliance_officer');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GarmentGuard – Grievance Management</title>
  <meta name="description" content="View worker complaints, manage progress via Kanban board, and log resolutions.">
  <link rel="stylesheet" href="../../assets/css/style.css">
  <style>
    /* Filters and Top Bar */
    .filters-bar {
      display: flex;
      flex-wrap: wrap;
      gap: 14px;
      margin-bottom: 24px;
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

    /* Kanban Board Layout */
    .kanban-board {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 20px;
      align-items: start;
      margin-top: 10px;
    }
    
    @media (max-width: 900px) {
      .kanban-board {
        grid-template-columns: 1fr;
      }
    }

    .kanban-column {
      background-color: var(--bg-secondary);
      border: 1px solid var(--border-color);
      border-radius: var(--border-radius);
      padding: 16px;
      min-height: 600px;
      display: flex;
      flex-direction: column;
      gap: 14px;
      transition: background-color var(--transition-speed) ease, border-color var(--transition-speed) ease;
    }
    
    .kanban-column.drag-over {
      background-color: rgba(29, 158, 117, 0.05);
      border-color: var(--green);
    }

    .kanban-column-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding-bottom: 12px;
      border-bottom: 2px solid var(--border-color);
      margin-bottom: 4px;
    }

    .kanban-column-title {
      font-size: 15px;
      font-weight: 700;
      display: flex;
      align-items: center;
      gap: 8px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .kanban-column-count {
      background-color: rgba(255, 255, 255, 0.08);
      color: var(--text-primary);
      font-size: 12px;
      font-weight: 700;
      padding: 2px 8px;
      border-radius: 999px;
    }

    /* Kanban Cards */
    .kanban-cards-container {
      display: flex;
      flex-direction: column;
      gap: 12px;
      min-height: 500px;
    }

    .kanban-card {
      background-color: var(--bg-primary);
      border: 1px solid var(--border-color);
      border-radius: 8px;
      padding: 16px;
      cursor: grab;
      transition: transform var(--transition-speed) ease, box-shadow var(--transition-speed) ease, border-color var(--transition-speed) ease;
      user-select: none;
      display: flex;
      flex-direction: column;
      gap: 8px;
    }

    .kanban-card:active {
      cursor: grabbing;
    }

    .kanban-card.dragging {
      opacity: 0.3;
      border-style: dashed;
      box-shadow: none;
      transform: scale(0.98);
    }

    .kanban-card:hover:not(.dragging) {
      box-shadow: 0 8px 16px rgba(0, 0, 0, 0.4);
      border-color: var(--text-secondary);
      transform: translateY(-2px);
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
             class="nav-link <?php echo $key === 'grievances' ? 'active' : ''; ?>">
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
      <h2 class="page-title">Grievance Management</h2>
      <div class="user-profile-menu">
        <span style="font-weight: 500; color: var(--text-secondary);">
          <?php echo htmlspecialchars($fullName); ?> (<?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $role))); ?>)
        </span>
        <div class="user-avatar"><?php echo strtoupper(substr($fullName, 0, 1)); ?></div>
      </div>
    </div>

    <!-- Top Filters & Submit Bar -->
    <div class="card">
      <div class="filters-bar">
        <div class="filter-group">
          <label class="form-label" for="factory-filter">Factory</label>
          <select class="filter-select" id="factory-filter">
            <option value="">All Factories</option>
          </select>
        </div>

        <div class="filter-group">
          <label class="form-label" for="category-filter">Category</label>
          <select class="filter-select" id="category-filter">
            <option value="All">All Categories</option>
            <option value="Salary">Salary</option>
            <option value="Safety">Safety</option>
            <option value="Harassment">Harassment</option>
            <option value="Leave">Leave</option>
            <option value="Other">Other</option>
          </select>
        </div>

        <div class="filter-group search-group">
          <label class="form-label" for="search-input">Search Description</label>
          <input type="text" class="search-input" id="search-input" placeholder="Search grievance description…">
        </div>

        <?php if ($canSubmit): ?>
        <div class="filter-group" style="min-width: auto; flex-grow: 0;">
          <button class="btn btn-primary" onclick="openSubmitModal()">📣 Submit Grievance</button>
        </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Kanban Board Grid -->
    <div class="kanban-board">
      <!-- Column Open -->
      <div class="kanban-column" id="col-open" ondragover="handleDragOver(event)" ondragleave="handleDragLeave(event)" ondrop="handleDrop(event, 'Open')">
        <div class="kanban-column-header" style="border-bottom-color: var(--red);">
          <span class="kanban-column-title" style="color: var(--red);">🔴 Open</span>
          <span class="kanban-column-count" id="count-open">0</span>
        </div>
        <div class="kanban-cards-container" id="col-open-cards"></div>
      </div>

      <!-- Column In Progress -->
      <div class="kanban-column" id="col-progress" ondragover="handleDragOver(event)" ondragleave="handleDragLeave(event)" ondrop="handleDrop(event, 'In Progress')">
        <div class="kanban-column-header" style="border-bottom-color: var(--amber);">
          <span class="kanban-column-title" style="color: var(--amber);">🟡 In Progress</span>
          <span class="kanban-column-count" id="count-progress">0</span>
        </div>
        <div class="kanban-cards-container" id="col-progress-cards"></div>
      </div>

      <!-- Column Resolved -->
      <div class="kanban-column" id="col-resolved" ondragover="handleDragOver(event)" ondragleave="handleDragLeave(event)" ondrop="handleDrop(event, 'Resolved')">
        <div class="kanban-column-header" style="border-bottom-color: var(--green);">
          <span class="kanban-column-title" style="color: var(--green);">🟢 Resolved</span>
          <span class="kanban-column-count" id="count-resolved">0</span>
        </div>
        <div class="kanban-cards-container" id="col-resolved-cards"></div>
      </div>
    </div>
  </div>
</div>

<!-- ══════════════════════════════════════════════════
     SUBMIT GRIEVANCE MODAL
     ══════════════════════════════════════════════════ -->
<?php if ($canSubmit): ?>
<div id="submit-modal" class="modal-overlay" onclick="closeModal('submit-modal', event)">
  <div class="modal-box">
    <div class="modal-header">
      <h3>Submit New Grievance</h3>
      <button class="close-btn" onclick="document.getElementById('submit-modal').classList.remove('open')">&times;</button>
    </div>
    <form id="submit-grievance-form" onsubmit="submitGrievance(event)" style="display: contents;">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label" for="sg-worker">Worker <span style="color: var(--red)">*</span></label>
          <select class="form-control" id="sg-worker" name="worker_id" required>
            <option value="">Select Worker</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label" for="sg-category">Category <span style="color: var(--red)">*</span></label>
          <select class="form-control" id="sg-category" name="category" required>
            <option value="Salary">Salary</option>
            <option value="Safety">Safety</option>
            <option value="Harassment">Harassment</option>
            <option value="Leave">Leave</option>
            <option value="Other" selected>Other</option>
          </select>
        </div>

        <div class="form-group">
          <label class="form-label" for="sg-description">Description <span style="color: var(--red)">*</span></label>
          <textarea class="form-control" id="sg-description" name="description" rows="4" placeholder="Detail the complaint (minimum 20 characters)..." required></textarea>
          <div style="font-size:12px; color:var(--text-secondary); margin-top:4px;" id="desc-char-count">0 characters (min 20)</div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('submit-modal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary" id="sg-submit-btn">Submit Grievance</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<!-- ══════════════════════════════════════════════════
     RESOLUTION NOTES INLINE MODAL
     ══════════════════════════════════════════════════ -->
<div id="notes-modal" class="modal-overlay" onclick="closeModal('notes-modal', event)">
  <div class="modal-box" style="width: min(450px, 96vw);">
    <div class="modal-header">
      <h3>Enter Resolution Notes</h3>
      <button class="close-btn" onclick="cancelResolution()">&times;</button>
    </div>
    <div class="modal-body">
      <div class="form-group">
        <label class="form-label" for="rn-notes">Resolution Summary <span style="color: var(--red)">*</span></label>
        <textarea class="form-control" id="rn-notes" rows="4" placeholder="How was this grievance resolved? (Required)"></textarea>
      </div>
    </div>
    <div class="modal-footer">
      <button type="button" class="btn btn-secondary" onclick="cancelResolution()">Cancel</button>
      <button type="button" class="btn btn-primary" onclick="submitResolution()">Resolve Grievance</button>
    </div>
  </div>
</div>

<script src="../../assets/js/toast.js"></script>
  <script src="../../assets/js/table-utils.js"></script>
<script>
// ═══════════════════════════════════════════════════════════════
//  STATE
// ═══════════════════════════════════════════════════════════════
let allRows = [];
let pendingDragInfo = null;

// ═══════════════════════════════════════════════════════════════
//  BOOTSTRAP
// ═══════════════════════════════════════════════════════════════
document.addEventListener('DOMContentLoaded', () => {
  fetchGrievances();
  loadFactoriesFilter();

  document.getElementById('factory-filter').addEventListener('change', fetchGrievances);
  document.getElementById('category-filter').addEventListener('change', fetchGrievances);
  document.getElementById('search-input').addEventListener('input', debounce(fetchGrievances, 300));

  // Character counter for submit description
  const descArea = document.getElementById('sg-description');
  if (descArea) {
    descArea.addEventListener('input', function() {
      const count = this.value.length;
      const countEl = document.getElementById('desc-char-count');
      countEl.textContent = `${count} character${count !== 1 ? 's' : ''} (min 20)`;
      if (count >= 20) {
        countEl.style.color = 'var(--green)';
      } else {
        countEl.style.color = 'var(--text-secondary)';
      }
    });
  }
});

// ═══════════════════════════════════════════════════════════════
//  DATA FETCH & RENDER
// ═══════════════════════════════════════════════════════════════
function fetchGrievances() {
  const factoryId = document.getElementById('factory-filter').value;
  const category = document.getElementById('category-filter').value;
  const search = document.getElementById('search-input').value.trim();

  let url = '/backend/api/grievances.php?';
  const params = [];
  if (factoryId) params.push(`factory_id=${encodeURIComponent(factoryId)}`);
  if (category) params.push(`category=${encodeURIComponent(category)}`);
  if (search) params.push(`search=${encodeURIComponent(search)}`);

  url += params.join('&');

  fetch(url)
    .then(r => r.json())
    .then(res => {
      if (!res.success) {
        showToast(res.message || 'Failed to load grievances', 'error');
        return;
      }
      allRows = res.data;
      renderBoard();
    })
    .catch(() => showToast('Network error loading grievances', 'error'));
}

function renderBoard() {
  const openContainer = document.getElementById('col-open-cards');
  const progressContainer = document.getElementById('col-progress-cards');
  const resolvedContainer = document.getElementById('col-resolved-cards');

  openContainer.innerHTML = '';
  progressContainer.innerHTML = '';
  resolvedContainer.innerHTML = '';

  let openCount = 0;
  let progressCount = 0;
  let resolvedCount = 0;

  allRows.forEach(r => {
    let daysText = '';
    if (r.STATUS === 'Resolved') {
      daysText = `<span style="font-size: 11px; color: var(--green); font-weight: 500;">Resolved: ${r.RESOLVED_DATE} (${r.DAYS_OPEN} days to resolve)</span>`;
    } else {
      daysText = `<span style="font-size: 11px; color: var(--text-secondary);">Days Open: ${r.DAYS_OPEN}</span>`;
    }

    const cardHtml = `
      <div class="kanban-card" id="card-${r.GRIEVANCE_ID}" draggable="true" ondragstart="handleDragStart(event)" data-id="${r.GRIEVANCE_ID}" data-status="${r.STATUS}">
        <div style="display:flex; justify-content:space-between; align-items:center;">
          ${categoryBadge(r.CATEGORY)}
          <span style="font-size:11px; color:var(--text-secondary);">${r.SUBMITTED_DATE}</span>
        </div>
        <div style="font-size:13px; font-weight:700; color:var(--text-primary); margin-top:4px;">
          ${escHtml(r.WORKER_NAME)}
        </div>
        <div style="font-size:11px; color:var(--text-secondary); margin-top: -2px;">
          🏭 ${escHtml(r.FACTORY_NAME)}
        </div>
        <div style="font-size:13px; color:var(--text-secondary); margin-top:6px; line-height:1.4; word-break:break-word;">
          ${escHtml(truncate(r.DESCRIPTION, 100))}
        </div>
        ${r.STATUS === 'Resolved' && r.RESOLUTION_NOTES ? `
          <div style="margin-top:6px; font-size:12px; color:var(--text-secondary); background:rgba(255,255,255,0.03); border-radius:4px; padding:8px; border-left:2px solid var(--green);">
            <strong style="color:var(--text-primary);">Notes:</strong> ${escHtml(r.RESOLUTION_NOTES)}
          </div>
        ` : ''}
        <div style="margin-top:8px; padding-top:8px; border-top:1px solid var(--border-color); display:flex; justify-content:space-between; align-items:center;">
          ${daysText}
        </div>
      </div>
    `;

    if (r.STATUS === 'Open') {
      openContainer.innerHTML += cardHtml;
      openCount++;
    } else if (r.STATUS === 'In Progress') {
      progressContainer.innerHTML += cardHtml;
      progressCount++;
    } else if (r.STATUS === 'Resolved') {
      resolvedContainer.innerHTML += cardHtml;
      resolvedCount++;
    }
  });

  document.getElementById('count-open').textContent = openCount;
  document.getElementById('count-progress').textContent = progressCount;
  document.getElementById('count-resolved').textContent = resolvedCount;
}

// ═══════════════════════════════════════════════════════════════
//  DRAG & DROP MECHANICS
// ═══════════════════════════════════════════════════════════════
function handleDragStart(e) {
  e.dataTransfer.setData('text/plain', e.target.dataset.id);
  e.target.classList.add('dragging');
}

document.addEventListener('dragend', (e) => {
  if (e.target.classList.contains('kanban-card')) {
    e.target.classList.remove('dragging');
  }
});

function handleDragOver(e) {
  e.preventDefault();
  e.currentTarget.classList.add('drag-over');
}

function handleDragLeave(e) {
  e.currentTarget.classList.remove('drag-over');
}

function handleDrop(e, targetStatus) {
  e.preventDefault();
  const column = e.currentTarget;
  column.classList.remove('drag-over');

  const cardId = e.dataTransfer.getData('text/plain');
  const cardEl = document.getElementById(`card-${cardId}`);
  if (!cardEl) return;

  const currentStatus = cardEl.dataset.status;
  if (currentStatus === targetStatus) return; // Ignore drops in same status

  // Store original details for optimistic UI updates
  pendingDragInfo = {
    cardId: cardId,
    sourceStatus: currentStatus,
    targetStatus: targetStatus,
    originalParent: cardEl.parentElement
  };

  // Optimistic Move
  const cardsContainer = column.querySelector('.kanban-cards-container');
  cardsContainer.appendChild(cardEl);
  cardEl.dataset.status = targetStatus;
  updateColumnCounts();

  if (targetStatus === 'Resolved') {
    // Require notes
    document.getElementById('rn-notes').value = '';
    document.getElementById('notes-modal').classList.add('open');
  } else {
    // In Progress or Open status update immediately
    updateGrievanceStatus(cardId, targetStatus, null);
  }
}

function updateColumnCounts() {
  const openCount = document.getElementById('col-open-cards').children.length;
  const progressCount = document.getElementById('col-progress-cards').children.length;
  const resolvedCount = document.getElementById('col-resolved-cards').children.length;

  document.getElementById('count-open').textContent = openCount;
  document.getElementById('count-progress').textContent = progressCount;
  document.getElementById('count-resolved').textContent = resolvedCount;
}

function updateGrievanceStatus(cardId, newStatus, notes) {
  fetch('/backend/api/grievances.php', {
    method: 'PATCH',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      grievance_id: parseInt(cardId),
      status: newStatus,
      resolution_notes: notes
    })
  })
  .then(r => r.json())
  .then(res => {
    if (res.success) {
      showToast(`Grievance status updated to "${newStatus}"`, 'success');
      fetchGrievances(); // fully refresh dates and details from Oracle
    } else {
      showToast(res.message || 'Status update failed', 'error');
      revertCard();
    }
  })
  .catch(() => {
    showToast('Network error updating grievance status', 'error');
    revertCard();
  });
}

function revertCard() {
  if (pendingDragInfo) {
    const cardEl = document.getElementById(`card-${pendingDragInfo.cardId}`);
    if (cardEl) {
      pendingDragInfo.originalParent.appendChild(cardEl);
      cardEl.dataset.status = pendingDragInfo.sourceStatus;
      updateColumnCounts();
    }
  }
  pendingDragInfo = null;
}

function cancelResolution() {
  document.getElementById('notes-modal').classList.remove('open');
  revertCard();
}

function submitResolution() {
  const notes = document.getElementById('rn-notes').value.trim();
  if (!notes) {
    showToast('Resolution notes are required when status is Resolved', 'error');
    return;
  }
  document.getElementById('notes-modal').classList.remove('open');
  if (pendingDragInfo) {
    updateGrievanceStatus(pendingDragInfo.cardId, pendingDragInfo.targetStatus, notes);
  }
}

<?php if ($canSubmit): ?>
// ═══════════════════════════════════════════════════════════════
//  MODAL ACTIONS
// ═══════════════════════════════════════════════════════════════
function openSubmitModal() {
  loadWorkersDropdown();
  document.getElementById('desc-char-count').textContent = '0 characters (min 20)';
  document.getElementById('desc-char-count').style.color = 'var(--text-secondary)';
  document.getElementById('submit-modal').classList.add('open');
}

function loadWorkersDropdown() {
  fetch('/backend/api/workers.php')
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        const select = document.getElementById('sg-worker');
        select.innerHTML = '<option value="">Select Worker</option>';
        res.data.forEach(w => {
          const opt = document.createElement('option');
          opt.value = w.WORKER_ID;
          opt.textContent = `${w.FULL_NAME} (${w.FACTORY_NAME})`;
          select.appendChild(opt);
        });
      }
    });
}

function submitGrievance(e) {
  e.preventDefault();
  const f = e.target;
  const payload = {
    worker_id: parseInt(f.worker_id.value),
    category: f.category.value,
    description: f.description.value.trim()
  };

  if (!payload.worker_id || !payload.category || !payload.description) {
    showToast('Please fill in all required fields.', 'error');
    return;
  }

  if (payload.description.length < 20) {
    showToast('Description must be at least 20 characters long.', 'error');
    return;
  }

  const btn = document.getElementById('sg-submit-btn');
  btn.disabled = true;
  btn.textContent = 'Submitting…';

  fetch('/backend/api/grievances.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  })
  .then(r => r.json())
  .then(res => {
    if (res.success) {
      document.getElementById('submit-modal').classList.remove('open');
      f.reset();
      showToast('Grievance submitted successfully!', 'success');
      fetchGrievances();
    } else {
      showToast(res.message || 'Submission failed.', 'error');
    }
  })
  .catch(() => showToast('Network error submitting grievance', 'error'))
  .finally(() => {
    btn.disabled = false;
    btn.textContent = 'Submit Grievance';
  });
}
<?php endif; ?>

// ═══════════════════════════════════════════════════════════════
//  HELPERS & UTILITIES
// ═══════════════════════════════════════════════════════════════
function loadFactoriesFilter() {
  fetch('/backend/api/factories.php')
    .then(r => r.json())
    .then(res => {
      if (res.success) {
        const select = document.getElementById('factory-filter');
        select.innerHTML = '<option value="">All Factories</option>';
        res.data.forEach(f => {
          const opt = document.createElement('option');
          opt.value = f.FACTORY_ID;
          opt.textContent = f.FACTORY_NAME;
          select.appendChild(opt);
        });
      }
    });
}

function categoryBadge(category) {
  const cat = String(category).toLowerCase();
  let badgeClass = 'badge-gray';
  if (cat === 'salary') badgeClass = 'badge-blue';
  else if (cat === 'safety') badgeClass = 'badge-red';
  else if (cat === 'harassment') badgeClass = 'badge-purple';
  else if (cat === 'leave') badgeClass = 'badge-amber';
  return `<span class="badge ${badgeClass}">${category}</span>`;
}

function truncate(str, len = 100) {
  if (!str) return '';
  if (str.length <= len) return str;
  return str.substring(0, len) + '…';
}

function escHtml(s) {
  return String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function closeModal(id, event) {
  if (event.target === document.getElementById(id)) {
    // If closing resolution modal, trigger revert
    if (id === 'notes-modal') {
      cancelResolution();
    } else {
      document.getElementById(id).classList.remove('open');
    }
  }
}

function debounce(func, wait) {
  let timeout;
  return function executedFunction(...args) {
    const later = () => {
      clearTimeout(timeout);
      func(...args);
    };
    clearTimeout(timeout);
    timeout = setTimeout(later, wait);
  };
}
</script>
</body>
</html>
