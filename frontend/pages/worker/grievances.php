<?php
require_once '../../../backend/includes/auth_check.php';
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "worker") { header("Location: /frontend/index.html"); exit; }
$activePage = 'grievances';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GarmentGuard - Grievances</title>
  <link rel="stylesheet" href="../../assets/css/style.css">
  <link rel="stylesheet" href="../../assets/css/custom-select.css">
</head>
<body>
  <div class="app-container">
    <div class="sidebar" id="sidebar">
      <div class="brand">
        <span class="brand-title">GarmentGuard</span>
        <span class="brand-subtitle">Compliance System</span>
      </div>
            <ul class="nav-menu">
        <li><a href="profile.php" class="nav-link <?php echo $activePage === 'profile' ? 'active' : ''; ?>">👤 Profile</a></li>
        <li><a href="grievances.php" class="nav-link <?php echo $activePage === 'grievances' ? 'active' : ''; ?>">📣 Grievances</a></li>
        <li><a href="salary.php" class="nav-link <?php echo $activePage === 'salary' ? 'active' : ''; ?>">💰 Salaries</a></li>
        <li><a href="equipment.php" class="nav-link <?php echo $activePage === 'equipment' ? 'active' : ''; ?>">🧯 Safety Equipment</a></li>
      </ul>
      <div class="nav-footer">
        <a href="../../../backend/auth/logout.php" class="nav-link">🚪 Logout</a>
      </div>
    </div>

    <div class="main-content">
      <div class="top-bar">
        <h2 class="page-title">Grievances</h2>
        <div class="user-profile-menu">
          <span style="font-weight:500;color:var(--text-secondary);"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
          <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?></div>
        </div>
      </div>

      <div class="card">
        <div class="search-bar" style="display: flex; gap: 10px;">
          <input type="text" class="search-input" id="search" placeholder="Search by worker, category, status…" style="flex-grow: 1;">
          <button class="btn btn-primary" onclick="openSubmitModal()">📣 Submit Grievance</button>
        </div>
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>Worker</th>
                <th>Factory</th>
                <th>Category</th>
                <th>Description</th>
                <th>Submitted</th>
                <th>Status</th>
                <th>Resolved</th>
                <th>Report</th>
              </tr>
            </thead>
            <tbody id="tbody">
              <tr><td colspan="8" style="text-align:center;color:var(--text-secondary)">Loading…</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

<!-- SUBMIT GRIEVANCE MODAL -->
<div id="submit-modal" class="modal-overlay" onclick="if(event.target===this) this.classList.remove('open')">
  <div class="modal-box">
    <div class="modal-header">
      <h3>Submit New Grievance</h3>
      <button class="close-btn" onclick="document.getElementById('submit-modal').classList.remove('open')">&times;</button>
    </div>
    <form id="submit-grievance-form" onsubmit="submitGrievance(event)" style="display: contents;">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label" for="sg-worker">Worker ID <span style="color: var(--red)">*</span></label>
          <input type="number" class="form-control" id="sg-worker" name="worker_id" placeholder="Enter your Worker ID" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="sg-category">Category <span style="color: var(--red)">*</span></label>
          <select class="form-control custom-select-init" id="sg-category" name="category" required>
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
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="document.getElementById('submit-modal').classList.remove('open')">Cancel</button>
        <button type="submit" class="btn btn-primary" id="sg-submit-btn">Submit Grievance</button>
      </div>
    </form>
  </div>
</div>

  <script src="../../assets/js/toast.js"></script>
  <script src="../../assets/js/table-utils.js"></script>
  <script src="../../assets/js/custom-select.js"></script>
  <script>
    function statusBadge(v) { return {'Open':'badge-red','In Progress':'badge-amber','Resolved':'badge-green'}[v] || 'badge-gray'; }

    let allRows = [];
    fetch('/backend/api/grievances.php')
      .then(r => r.json())
      .then(res => {
        if (!res.success) { showToast('Failed to load grievances', 'error'); return; }
        allRows = res.data;
        render(allRows);
      })
      .catch(() => showToast('Network error', 'error'));

    function render(rows) {
      const tbody = document.getElementById('tbody');
      if (!rows.length) { tbody.innerHTML = '<tr><td colspan="8" class="empty-state">No grievances found.</td></tr>'; return; }
      tbody.innerHTML = rows.map(r => {
        const reportBtn = r.STATUS === 'Resolved' && r.RESOLUTION_NOTES 
          ? `<button class="btn btn-secondary" style="padding: 4px 8px; font-size: 12px;" onclick="alert(decodeURIComponent('${encodeURIComponent(r.RESOLUTION_NOTES)}'))">📄 View Report</button>`
          : '—';
        return `<tr>
          <td><strong>${r.WORKER_NAME}</strong></td>
          <td>${r.FACTORY_NAME}</td>
          <td><span class="badge badge-blue">${r.CATEGORY}</span></td>
          <td style="max-width:200px;font-size:13px;color:var(--text-secondary)">${r.DESCRIPTION ? r.DESCRIPTION.substring(0,70)+'…' : '—'}</td>
          <td>${r.SUBMITTED_DATE}</td>
          <td><span class="badge ${statusBadge(r.STATUS)}">${r.STATUS}</span></td>
          <td>${r.RESOLVED_DATE || '—'}</td>
          <td>${reportBtn}</td>
        </tr>`;
      }).join('');
    }

    document.getElementById('search').addEventListener('input', function() {
      const q = this.value.toLowerCase();
      render(allRows.filter(r =>
        r.WORKER_NAME.toLowerCase().includes(q) ||
        r.FACTORY_NAME.toLowerCase().includes(q) ||
        r.CATEGORY.toLowerCase().includes(q) ||
        r.STATUS.toLowerCase().includes(q)
      ));
    });

    function openSubmitModal() {
      const factoryId = '<?php echo $_SESSION['factory_id'] ?? ''; ?>';
      if (factoryId) {
        fetch(`/backend/api/workers.php?factory_id=${factoryId}`)
          .then(r => r.json())
          .then(res => {
            if (res.success && res.data.length > 0) {
              const workerInput = document.getElementById('sg-worker');
              workerInput.value = res.data[0].WORKER_ID;
              workerInput.readOnly = true; // Prevent editing their own ID
            }
          });
      }
      document.getElementById('submit-modal').classList.add('open');
      setTimeout(initCustomSelects, 100);
    }
    


    function submitGrievance(e) {
      e.preventDefault();
      const f = e.target;
      const payload = {
        worker_id: parseInt(f.worker_id.value),
        category: f.category.value,
        description: f.description.value.trim()
      };
    
      if (!payload.worker_id || !payload.category || payload.description.length < 20) {
        showToast('Description must be at least 20 chars', 'error');
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
          // Reload
          fetch('/backend/api/grievances.php').then(r=>r.json()).then(res2=>{
             if(res2.success) { allRows = res2.data; render(allRows); }
          });
        } else {
          showToast(res.message || 'Submission failed.', 'error');
        }
      })
      .catch(() => showToast('Network error', 'error'))
      .finally(() => {
        btn.disabled = false;
        btn.textContent = 'Submit Grievance';
      });
    }
  </script>
</body>
</html>
