<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: /frontend/index.html");
    exit;
}

if ($_SESSION['role'] !== 'admin') {
    header("Location: /frontend/pages/403.php");
    exit;
}

$activePage = 'users';
$fullName = $_SESSION['full_name'] ?? 'Admin';
$role = $_SESSION['role'];

// Detect whether running in subfolder frontend/pages/admin/ or frontend/pages/
$is_admin_folder = (strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false);
$prefix = $is_admin_folder ? '' : 'admin/';

$navMenu = [
  'dashboard' => ['📊 Dashboard', $prefix . 'dashboard.php'],
  'factories' => ['🏭 Factories', $prefix . 'factories.php'],
  'workers' => ['👷 Workers', $prefix . 'workers.php'],
  'audits' => ['📋 Audits', $prefix . 'audits.php'],
  'grievances' => ['📣 Grievances', $prefix . 'grievances.php'],
  'salary' => ['💰 Salaries', $prefix . 'salary.php'],
  'certifications' => ['🏅 Certifications', $prefix . 'certifications.php'],
  'equipment' => ['🧯 Safety Equipment', $prefix . 'equipment.php'],
  'buyer' => ['🛒 Buyers', $prefix . 'buyer.php'],
  'reports' => ['📈 Reports', $prefix . 'reports.php'],
  'users' => ['👤 Users', $is_admin_folder ? 'users.php' : 'users.php'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GarmentGuard - User Management</title>
  <link rel="stylesheet" href="/frontend/assets/css/style.css">
  <style>
    .filters-bar {
      display: flex;
      flex-wrap: wrap;
      gap: 16px;
      margin-bottom: 24px;
      align-items: flex-end;
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
    .filter-select, .filter-input {
      background-color: var(--bg-primary);
      border: 1px solid var(--border-color);
      border-radius: 8px;
      color: var(--text-primary);
      padding: 10px 14px;
      font-family: var(--font-family);
      font-size: 14px;
      outline: none;
      transition: border-color var(--transition-speed);
    }
    .filter-select:focus, .filter-input:focus {
      border-color: var(--green);
    }
    
    /* Modal styles compatibility */
    .modal-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100vw;
      height: 100vh;
      background: rgba(15, 23, 42, 0.6);
      backdrop-filter: blur(4px);
      z-index: 1000;
      display: none;
      align-items: center;
      justify-content: center;
      opacity: 0;
      transition: opacity 0.3s ease;
    }
    .modal-overlay.open {
      display: flex;
      opacity: 1;
    }
    .modal-box {
      background-color: var(--bg-secondary);
      border: 1px solid var(--border-color);
      border-radius: 12px;
      max-width: 520px;
      width: 90%;
      box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.4);
      display: flex;
      flex-direction: column;
      transform: translateY(-20px);
      transition: transform 0.3s ease;
    }
    .modal-overlay.open .modal-box {
      transform: translateY(0);
    }
    .modal-header {
      padding: 20px 24px;
      border-bottom: 1px solid var(--border-color);
      display: flex;
      justify-content: space-between;
      align-items: center;
    }
    .modal-header h3 {
      font-size: 18px;
      font-weight: 600;
      color: var(--text-primary);
    }
    .modal-body {
      padding: 24px;
      display: flex;
      flex-direction: column;
      gap: 16px;
      max-height: 70vh;
      overflow-y: auto;
    }
    .modal-footer {
      padding: 16px 24px;
      border-top: 1px solid var(--border-color);
      display: flex;
      justify-content: flex-end;
      gap: 12px;
      background-color: rgba(30, 41, 59, 0.4);
    }
    .form-group {
      display: flex;
      flex-direction: column;
      gap: 6px;
    }
    .form-label {
      font-size: 13px;
      font-weight: 500;
      color: var(--text-secondary);
    }
    .form-control {
      background-color: var(--bg-primary);
      border: 1px solid var(--border-color);
      border-radius: 8px;
      padding: 10px 14px;
      color: var(--text-primary);
      font-family: var(--font-family);
      font-size: 14px;
      outline: none;
      width: 100%;
    }
    .form-control:focus {
      border-color: var(--green);
    }
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
               class="nav-link <?php echo $key === $activePage ? 'active' : ''; ?>">
              <?php echo htmlspecialchars($item[0]); ?>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
      <div class="nav-footer">
        <a href="/backend/auth/logout.php" class="nav-link">🚪 Logout</a>
      </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
      <div class="top-bar">
        <h2 class="page-title">User Management</h2>
        <div class="user-profile-menu">
          <span style="font-weight:500;color:var(--text-secondary);"><?php echo htmlspecialchars($fullName); ?> (<?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $role))); ?>)</span>
          <div class="user-avatar"><?php echo strtoupper(substr($fullName, 0, 1)); ?></div>
        </div>
      </div>

      <!-- Filters and Actions -->
      <div class="card" style="margin-bottom: 24px;">
        <div class="filters-bar">
          <div class="filter-group search-group">
            <label class="form-label" for="search-input">Search Users</label>
            <input type="text" class="filter-input" id="search-input" placeholder="Search by name or username…">
          </div>
          <div class="filter-group">
            <label class="form-label" for="role-filter">Role</label>
            <select class="filter-select" id="role-filter">
              <option value="All">All Roles</option>
              <option value="admin">Admin</option>
              <option value="compliance_officer">Compliance Officer</option>
              <option value="inspector">Inspector</option>
              <option value="buyer_user">Buyer User</option>
              <option value="worker">Worker</option>
            </select>
          </div>
          <div class="filter-group">
            <label class="form-label" for="status-filter">Status</label>
            <select class="filter-select" id="status-filter">
              <option value="All">All Statuses</option>
              <option value="Active">Active</option>
              <option value="Inactive">Inactive</option>
            </select>
          </div>
          <button class="btn btn-primary" onclick="openAddModal()">➕ Add User</button>
        </div>
      </div>

      <!-- Users Table -->
      <div class="card">
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th style="width: 60px;">#</th>
                <th class="sortable-header" onclick="sortBy('full_name')">Full Name <span id="sort-full_name" class="sort-indicator">↕</span></th>
                <th>Username</th>
                <th class="sortable-header" onclick="sortBy('role')">Role <span id="sort-role" class="sort-indicator">↕</span></th>
                <th>Factory</th>
                <th>Email</th>
                <th class="sortable-header" onclick="sortBy('status')">Status <span id="sort-status" class="sort-indicator">↕</span></th>
                <th style="width: 180px;">Actions</th>
              </tr>
            </thead>
            <tbody id="tbody">
              <tr>
                <td colspan="8" style="text-align: center; color: var(--text-secondary); padding: 32px;">Loading users…</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- Add User Modal -->
  <div id="add-modal" class="modal-overlay" onclick="closeModal('add-modal', event)">
    <div class="modal-box">
      <div class="modal-header">
        <h3>Create New User</h3>
        <button class="close-btn" onclick="document.getElementById('add-modal').classList.remove('open')" style="background:none;border:none;font-size:24px;color:var(--text-secondary);cursor:pointer;">&times;</button>
      </div>
      <form id="add-user-form" onsubmit="submitAddUser(event)">
        <div class="modal-body">
          <div class="form-group">
            <label class="form-label" for="add-full-name">Full Name <span style="color:var(--red)">*</span></label>
            <input type="text" class="form-control" id="add-full-name" required placeholder="John Doe">
          </div>
          <div class="form-group">
            <label class="form-label" for="add-username">Username <span style="color:var(--red)">*</span></label>
            <input type="text" class="form-control" id="add-username" required placeholder="johndoe">
          </div>
          <div class="form-group">
            <label class="form-label" for="add-email">Email <span style="color:var(--red)">*</span></label>
            <input type="email" class="form-control" id="add-email" required placeholder="john@example.com">
          </div>
          <div class="form-group">
            <label class="form-label" for="add-role">Role <span style="color:var(--red)">*</span></label>
            <select class="form-control" id="add-role" required>
              <option value="">Select Role</option>
              <option value="admin">Admin</option>
              <option value="compliance_officer">Compliance Officer</option>
              <option value="inspector">Inspector</option>
              <option value="buyer_user">Buyer User</option>
              <option value="worker">Worker</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label" for="add-factory">Factory (Optional)</label>
            <select class="form-control" id="add-factory">
              <option value="">Select Factory</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label" for="add-password">Password <span style="color:var(--red)">*</span></label>
            <input type="password" class="form-control" id="add-password" required placeholder="••••••••">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" onclick="document.getElementById('add-modal').classList.remove('open')">Cancel</button>
          <button type="submit" class="btn btn-primary">Create User</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Edit User Modal -->
  <div id="edit-modal" class="modal-overlay" onclick="closeModal('edit-modal', event)">
    <div class="modal-box">
      <div class="modal-header">
        <h3>Edit User</h3>
        <button class="close-btn" onclick="document.getElementById('edit-modal').classList.remove('open')" style="background:none;border:none;font-size:24px;color:var(--text-secondary);cursor:pointer;">&times;</button>
      </div>
      <form id="edit-user-form" onsubmit="submitEditUser(event)">
        <input type="hidden" id="edit-user-id">
        <div class="modal-body">
          <div class="form-group">
            <label class="form-label" for="edit-full-name">Full Name <span style="color:var(--red)">*</span></label>
            <input type="text" class="form-control" id="edit-full-name" required placeholder="John Doe">
          </div>
          <div class="form-group">
            <label class="form-label" for="edit-username">Username <span style="color:var(--text-secondary)">(Read-only)</span></label>
            <input type="text" class="form-control" id="edit-username" disabled style="opacity: 0.6;">
          </div>
          <div class="form-group">
            <label class="form-label" for="edit-email">Email <span style="color:var(--red)">*</span></label>
            <input type="email" class="form-control" id="edit-email" required placeholder="john@example.com">
          </div>
          <div class="form-group">
            <label class="form-label" for="edit-role">Role <span style="color:var(--red)">*</span></label>
            <select class="form-control" id="edit-role" required>
              <option value="admin">Admin</option>
              <option value="compliance_officer">Compliance Officer</option>
              <option value="inspector">Inspector</option>
              <option value="buyer_user">Buyer User</option>
              <option value="worker">Worker</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label" for="edit-factory">Factory (Optional)</label>
            <select class="form-control" id="edit-factory">
              <option value="">Select Factory</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label" for="edit-status-val">Status <span style="color:var(--red)">*</span></label>
            <select class="form-control" id="edit-status-val" required>
              <option value="Active">Active</option>
              <option value="Inactive">Inactive</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label" for="edit-password">Password (Optional)</label>
            <input type="password" class="form-control" id="edit-password" placeholder="Leave blank to keep current password">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" onclick="document.getElementById('edit-modal').classList.remove('open')">Cancel</button>
          <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
      </form>
    </div>
  </div>

  <script src="/frontend/assets/js/toast.js"></script>
  <script>
    let allUsers = [];
    let allFactories = [];
    let currentSortCol = 'full_name';
    let currentSortDir = 'asc';

    document.addEventListener('DOMContentLoaded', () => {
      fetchUsers();
      fetchFactories();

      document.getElementById('search-input').addEventListener('input', renderTable);
      document.getElementById('role-filter').addEventListener('change', renderTable);
      document.getElementById('status-filter').addEventListener('change', renderTable);
    });

    function fetchUsers() {
      fetch('/backend/api/users.php')
        .then(r => r.json())
        .then(res => {
          if (res.success) {
            allUsers = res.data;
            renderTable();
            updateSortIndicators();
          } else {
            showToast(res.message || 'Failed to load users', 'error');
          }
        })
        .catch(() => showToast('Connection error', 'error'));
    }

    function fetchFactories() {
      fetch('/backend/api/factories.php')
        .then(r => r.json())
        .then(res => {
          if (res.success) {
            allFactories = res.data;
            populateFactoryDropdowns();
          }
        })
        .catch(() => console.error('Failed to load factories for select options'));
    }

    function populateFactoryDropdowns() {
      const addSelect = document.getElementById('add-factory');
      const editSelect = document.getElementById('edit-factory');
      
      const optionsHtml = allFactories.map(f => 
        `<option value="${f.FACTORY_ID}">${escapeHtml(f.FACTORY_NAME)}</option>`
      ).join('');

      addSelect.innerHTML = '<option value="">Select Factory</option>' + optionsHtml;
      editSelect.innerHTML = '<option value="">Select Factory</option>' + optionsHtml;
    }

    function escapeHtml(str) {
      if (!str) return '';
      return str.replace(/&/g, "&amp;")
                .replace(/</g, "&lt;")
                .replace(/>/g, "&gt;")
                .replace(/"/g, "&quot;")
                .replace(/'/g, "&#039;");
    }

    function formatRole(role) {
      if (!role) return '';
      return role.split('_')
                 .map(word => word.charAt(0).toUpperCase() + word.slice(1))
                 .join(' ');
    }

    function getFilteredUsers() {
      const roleFilter = document.getElementById('role-filter').value;
      const statusFilter = document.getElementById('status-filter').value;
      const searchQuery = document.getElementById('search-input').value.toLowerCase().trim();

      return allUsers.filter(user => {
        if (roleFilter !== 'All' && user.ROLE !== roleFilter) {
          return false;
        }
        if (statusFilter !== 'All' && user.STATUS !== statusFilter) {
          return false;
        }
        if (searchQuery) {
          const uName = (user.USERNAME || '').toLowerCase();
          const fName = (user.FULL_NAME || '').toLowerCase();
          if (!uName.includes(searchQuery) && !fName.includes(searchQuery)) {
            return false;
          }
        }
        return true;
      });
    }

    function sortBy(col) {
      if (currentSortCol === col) {
        currentSortDir = currentSortDir === 'asc' ? 'desc' : 'asc';
      } else {
        currentSortCol = col;
        currentSortDir = 'asc';
      }
      updateSortIndicators();
      renderTable();
    }

    function updateSortIndicators() {
      const cols = ['full_name', 'role', 'status'];
      cols.forEach(c => {
        const indicator = document.getElementById('sort-' + c);
        const th = indicator.parentElement;
        if (c === currentSortCol) {
          indicator.innerText = currentSortDir === 'asc' ? ' ▲' : ' ▼';
          th.classList.add('active');
        } else {
          indicator.innerText = ' ↕';
          th.classList.remove('active');
        }
      });
    }

    function renderTable() {
      let list = getFilteredUsers();
      
      if (currentSortCol) {
        list.sort((a, b) => {
          let valA = (a[currentSortCol.toUpperCase()] || '').toLowerCase();
          let valB = (b[currentSortCol.toUpperCase()] || '').toLowerCase();
          if (valA < valB) return currentSortDir === 'asc' ? -1 : 1;
          if (valA > valB) return currentSortDir === 'asc' ? 1 : -1;
          return 0;
        });
      }

      const tbody = document.getElementById('tbody');
      if (list.length === 0) {
        tbody.innerHTML = `<tr><td colspan="8" class="empty-state">No users found.</td></tr>`;
        return;
      }

      tbody.innerHTML = list.map((user, idx) => {
        const roleColors = {
          'admin': 'badge-purple',
          'compliance_officer': 'badge-blue',
          'inspector': 'badge-teal',
          'buyer_user': 'badge-amber',
          'worker': 'badge-green'
        };
        const roleClass = roleColors[user.ROLE] || 'badge-gray';
        const statusClass = user.STATUS === 'Active' ? 'badge-green' : 'badge-red';
        
        const isAct = user.STATUS === 'Active';
        const toggleText = isAct ? 'Deactivate' : 'Activate';
        const toggleClass = isAct ? 'btn-danger' : 'btn-primary';

        return `
          <tr>
            <td style="color:var(--text-secondary);font-size:13px;">${idx + 1}</td>
            <td><strong>${escapeHtml(user.FULL_NAME)}</strong></td>
            <td>${escapeHtml(user.USERNAME)}</td>
            <td><span class="badge ${roleClass}">${formatRole(user.ROLE)}</span></td>
            <td>${escapeHtml(user.FACTORY_NAME || 'N/A')}</td>
            <td style="color: var(--text-secondary)">${escapeHtml(user.EMAIL)}</td>
            <td><span class="badge ${statusClass}">${user.STATUS}</span></td>
            <td>
              <div style="display:flex; gap:8px;">
                <button class="btn btn-secondary btn-sm" onclick="openEditModal(${user.USER_ID})">Edit</button>
                <button class="btn btn-sm ${toggleClass}" onclick="toggleUserStatus(${user.USER_ID}, '${user.STATUS}')">${toggleText}</button>
              </div>
            </td>
          </tr>
        `;
      }).join('');
    }

    // Modal Control
    function openAddModal() {
      document.getElementById('add-user-form').reset();
      document.getElementById('add-modal').classList.add('open');
    }

    function openEditModal(userId) {
      const user = allUsers.find(u => u.USER_ID == userId);
      if (!user) return;

      document.getElementById('edit-user-form').reset();
      document.getElementById('edit-user-id').value = user.USER_ID;
      document.getElementById('edit-full-name').value = user.FULL_NAME;
      document.getElementById('edit-username').value = user.USERNAME;
      document.getElementById('edit-email').value = user.EMAIL;
      document.getElementById('edit-role').value = user.ROLE;
      document.getElementById('edit-factory').value = user.FACTORY_ID || '';
      document.getElementById('edit-status-val').value = user.STATUS;
      document.getElementById('edit-modal').classList.add('open');
    }

    function closeModal(id, event) {
      if (event.target === document.getElementById(id)) {
        document.getElementById(id).classList.remove('open');
      }
    }

    // Actions API Calls
    function submitAddUser(e) {
      e.preventDefault();
      const payload = {
        full_name: document.getElementById('add-full-name').value.trim(),
        username: document.getElementById('add-username').value.trim(),
        email: document.getElementById('add-email').value.trim(),
        role: document.getElementById('add-role').value,
        factory_id: document.getElementById('add-factory').value || null,
        password: document.getElementById('add-password').value
      };

      fetch('/backend/api/users.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      })
      .then(r => r.json())
      .then(res => {
        if (res.success) {
          document.getElementById('add-modal').classList.remove('open');
          showToast('User created successfully', 'success');
          fetchUsers();
        } else {
          showToast(res.message || 'Creation failed', 'error');
        }
      })
      .catch(() => showToast('Network error', 'error'));
    }

    function submitEditUser(e) {
      e.preventDefault();
      const payload = {
        user_id: document.getElementById('edit-user-id').value,
        full_name: document.getElementById('edit-full-name').value.trim(),
        email: document.getElementById('edit-email').value.trim(),
        role: document.getElementById('edit-role').value,
        factory_id: document.getElementById('edit-factory').value || null,
        status: document.getElementById('edit-status-val').value,
      };

      const pwd = document.getElementById('edit-password').value;
      if (pwd) {
        payload.password = pwd;
      }

      fetch('/backend/api/users.php', {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      })
      .then(r => r.json())
      .then(res => {
        if (res.success) {
          document.getElementById('edit-modal').classList.remove('open');
          showToast('User updated successfully', 'success');
          fetchUsers();
        } else {
          showToast(res.message || 'Update failed', 'error');
        }
      })
      .catch(() => showToast('Network error', 'error'));
    }

    function toggleUserStatus(userId, currentStatus) {
      const newStatus = currentStatus === 'Active' ? 'Inactive' : 'Active';
      const payload = {
        user_id: userId,
        status: newStatus
      };

      fetch('/backend/api/users.php', {
        method: 'PATCH',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      })
      .then(r => r.json())
      .then(res => {
        if (res.success) {
          showToast(`User status updated to ${newStatus}`, 'success');
          fetchUsers();
        } else {
          showToast(res.message || 'Action failed', 'error');
        }
      })
      .catch(() => showToast('Network error', 'error'));
    }
  </script>
</body>
</html>
