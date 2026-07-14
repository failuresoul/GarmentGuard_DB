<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Access Forbidden - GarmentGuard</title>
  <link rel="stylesheet" href="/frontend/assets/css/style.css">
  <style>
    .forbidden-container {
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      width: 100vw;
      background-color: var(--bg-primary);
      padding: 24px;
    }
    .forbidden-card {
      max-width: 480px;
      width: 100%;
      text-align: center;
      background: var(--bg-secondary);
      border: 1px solid var(--border-color);
      border-radius: 16px;
      padding: 48px 32px;
      box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.4);
    }
    .forbidden-icon {
      font-size: 64px;
      margin-bottom: 24px;
      display: inline-block;
      animation: pulse 2s infinite ease-in-out;
    }
    .forbidden-title {
      font-size: 28px;
      font-weight: 800;
      color: var(--red);
      margin-bottom: 12px;
    }
    .forbidden-text {
      color: var(--text-secondary);
      font-size: 15px;
      line-height: 1.6;
      margin-bottom: 32px;
    }
    @keyframes pulse {
      0%, 100% { transform: scale(1); }
      50% { transform: scale(1.08); }
    }
  </style>
</head>
<body>
  <div class="forbidden-container">
    <div class="forbidden-card">
      <div class="forbidden-icon">🔒</div>
      <h1 class="forbidden-title">403 - Access Forbidden</h1>
      <p class="forbidden-text">
        You do not have the required administrative permissions to access this page. If you believe this is an error, please contact your administrator.
      </p>
      <a href="javascript:history.back()" class="btn btn-primary" style="display: inline-block;">↩ Go Back</a>
    </div>
  </div>
</body>
</html>
