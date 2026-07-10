<?php
$activePage = 'dashboard';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once '../../backend/config/db.php';
require_once '../../backend/includes/helpers.php';
authCheck();

if ($_SESSION['role'] !== 'buyer_user') {
    header('Location: dashboard.php');
    exit();
}

$fullName = $_SESSION['full_name'] ?? 'Buyer User';
$navMenu = [
    'dashboard' => ['📊 Dashboard', 'buyer.php'],
    'factories' => ['🏭 My Factories', 'factories.php']
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Buyer Dashboard - GarmentGuard</title>
  <link rel="stylesheet" href="../../assets/css/style.css">
  <style>
    .welcome-banner {
      background: linear-gradient(135deg, var(--green), #14b8a6);
      color: #fff;
      padding: 32px;
      border-radius: 12px;
      margin-bottom: 24px;
      box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
    }
    .welcome-banner h1 {
      font-size: 28px;
      margin-bottom: 8px;
    }
    .welcome-banner p {
      font-size: 16px;
      opacity: 0.9;
    }
    
    .section-title {
      font-size: 20px;
      margin: 32px 0 16px;
      font-weight: 600;
      color: var(--text-primary);
    }

    .factory-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
      gap: 20px;
    }
    .factory-card {
      background: var(--bg-secondary);
      border: 1px solid var(--border-color);
      border-radius: 12px;
      padding: 24px;
      transition: transform var(--transition-speed);
      position: relative;
    }
    .factory-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 8px 24px rgba(0,0,0,0.15);
    }
    .factory-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 16px;
    }
    .factory-header h3 {
      font-size: 18px;
      margin-bottom: 4px;
    }
    .factory-header .district {
      font-size: 13px;
      color: var(--text-secondary);
    }
    .score-circle {
      width: 48px;
      height: 48px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: bold;
      color: #fff;
    }
    .factory-stats {
      margin-top: 16px;
      padding-top: 16px;
      border-top: 1px solid var(--border-color);
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 12px;
      font-size: 14px;
    }
    .stat-label {
      color: var(--text-secondary);
      font-size: 12px;
    }
    .stat-value {
      font-weight: 600;
    }
    .view-link {
      display: block;
      margin-top: 16px;
      text-align: center;
      color: var(--green);
      text-decoration: none;
      font-weight: 500;
      padding: 8px;
      border: 1px solid var(--green);
      border-radius: 6px;
      transition: background 0.3s;
    }
    .view-link:hover {
      background: rgba(16, 185, 129, 0.1);
    }
  </style>
</head>
<body>
  <div class="app-container">
    <div class="sidebar" id="sidebar">
      <div class="brand">
        <span class="brand-title">GarmentGuard</span>
        <span class="brand-subtitle">Buyer Portal</span>
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
        <a href="../../backend/auth/logout.php" class="nav-link">🚪 Logout</a>
      </div>
    </div>

    <div class="main-content">
      <div class="top-bar">
        <h2 class="page-title">Dashboard</h2>
        <div class="user-profile-menu">
          <span style="font-weight:500;color:var(--text-secondary);">
            <?php echo htmlspecialchars($fullName); ?> (Buyer)
          </span>
          <div class="user-avatar"><?php echo strtoupper(substr($fullName, 0, 1)); ?></div>
        </div>
      </div>

      <div class="welcome-banner">
        <h1>Welcome, <?php echo htmlspecialchars($fullName); ?></h1>
        <p>Monitor your supply chain compliance and factory performance in real-time.</p>
      </div>

      <h3 class="section-title">My Factories</h3>
      <div class="factory-grid" id="factory-grid">
        <p style="color: var(--text-secondary);">Loading factories...</p>
      </div>

      <h3 class="section-title">Certifications (Read Only)</h3>
      <div class="card">
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>Factory</th>
                <th>Cert Name</th>
                <th>Issuing Body</th>
                <th>Expiry</th>
                <th>Valid?</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody id="certs-tbody">
              <tr><td colspan="6" style="text-align:center;">Loading certifications...</td></tr>
            </tbody>
          </table>
        </div>
      </div>

      <h3 class="section-title">Recent Audits (Read Only)</h3>
      <div class="card">
        <div class="table-responsive">
          <table class="table">
            <thead>
              <tr>
                <th>Factory</th>
                <th>Audit Date</th>
                <th>Score</th>
                <th>Result</th>
                <th>Next Scheduled</th>
              </tr>
            </thead>
            <tbody id="audits-tbody">
              <tr><td colspan="5" style="text-align:center;">Loading audits...</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <script src="../../assets/js/toast.js"></script>
  <script>
    function getScoreColor(score) {
      if (score >= 75) return 'var(--green)';
      if (score >= 40) return 'var(--amber)';
      return 'var(--red)';
    }

    function getStatusBadge(status) {
      const map = {
        'Compliant': 'badge-green',
        'At Risk': 'badge-amber',
        'Non-Compliant': 'badge-red',
        'Active': 'badge-green',
        'Expired': 'badge-red',
        'Revoked': 'badge-gray',
        'Valid': 'badge-green',
        'Invalid': 'badge-red',
        'Pass': 'badge-green',
        'Fail': 'badge-red',
        'Needs Improvement': 'badge-amber'
      };
      return map[status] || 'badge-gray';
    }

    function loadFactories() {
      fetch('/backend/api/buyer.php?action=factories')
        .then(res => res.json())
        .then(res => {
          if (res.success) {
            const grid = document.getElementById('factory-grid');
            if (res.data.length === 0) {
              grid.innerHTML = '<p style="color: var(--text-secondary);">No active factories found.</p>';
              return;
            }
            grid.innerHTML = res.data.map(f => {
              const score = f.CALC_SCORE || f.COMPLIANCE_SCORE || 0;
              const scoreColor = getScoreColor(score);
              return `
                <div class="factory-card">
                  <div class="factory-header">
                    <div>
                      <h3>${f.FACTORY_NAME}</h3>
                      <span class="district">${f.DISTRICT}</span>
                    </div>
                    <div class="score-circle" style="background-color: ${scoreColor}">${score}</div>
                  </div>
                  <div>
                    <span class="badge ${getStatusBadge(f.COMPLIANCE_STATUS)}">${f.COMPLIANCE_STATUS}</span>
                  </div>
                  <div class="factory-stats">
                    <div>
                      <div class="stat-label">Active Certs</div>
                      <div class="stat-value">${f.CERT_COUNT || 0}</div>
                    </div>
                    <div>
                      <div class="stat-label">Last Audit</div>
                      <div class="stat-value">${f.LAST_AUDIT || 'N/A'} (${f.LAST_SCORE || '-'})</div>
                    </div>
                  </div>
                  <a href="factory_detail.php?id=${f.FACTORY_ID}" class="view-link">View Details</a>
                </div>
              `;
            }).join('');
          }
        });
    }

    function loadCertifications() {
      fetch('/backend/api/buyer.php?action=certifications')
        .then(res => res.json())
        .then(res => {
          if (res.success) {
            const tbody = document.getElementById('certs-tbody');
            if (res.data.length === 0) {
              tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;">No certifications found.</td></tr>';
              return;
            }
            tbody.innerHTML = res.data.map(c => {
              const isValid = c.IS_VALID == 1 ? 'Yes' : 'No';
              const validBadge = c.IS_VALID == 1 ? 'badge-green' : 'badge-red';
              return `
                <tr>
                  <td>${c.FACTORY_NAME}</td>
                  <td>${c.CERT_NAME}</td>
                  <td>${c.ISSUING_BODY}</td>
                  <td>${c.EXPIRY_DATE}</td>
                  <td><span class="badge ${validBadge}">${isValid}</span></td>
                  <td><span class="badge ${getStatusBadge(c.STATUS)}">${c.STATUS}</span></td>
                </tr>
              `;
            }).join('');
          }
        });
    }

    function loadAudits() {
      fetch('/backend/api/buyer.php?action=audits')
        .then(res => res.json())
        .then(res => {
          if (res.success) {
            const tbody = document.getElementById('audits-tbody');
            if (res.data.length === 0) {
              tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;">No audits found.</td></tr>';
              return;
            }
            tbody.innerHTML = res.data.map(a => {
              return `
                <tr>
                  <td>${a.FACTORY_NAME}</td>
                  <td>${a.AUDIT_DATE}</td>
                  <td>${a.SCORE}</td>
                  <td><span class="badge ${getStatusBadge(a.RESULT)}">${a.RESULT}</span></td>
                  <td>${a.NEXT_SCHEDULED_DATE || '-'}</td>
                </tr>
              `;
            }).join('');
          }
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
      loadFactories();
      loadCertifications();
      loadAudits();
    });
  </script>
</body>
</html>
