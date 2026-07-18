<?php
session_start();
require_once '../config/db.php';
require_once '../includes/helpers.php';

$method = $_SERVER['REQUEST_METHOD'];

// Handle GET: Fetch users (Admin only allowed)
if ($method === 'GET') {
  authCheck(['admin']);
  header('Content-Type: application/json');

  $role = $_GET['role'] ?? '';
  $status = $_GET['status'] ?? '';
  $search = $_GET['search'] ?? '';

  $sql = "SELECT u.user_id, u.username, u.role, u.full_name, u.factory_id, u.email, u.status,
                 NVL(f.factory_name, 'N/A') AS factory_name
          FROM USER_ u
          LEFT JOIN FACTORY f ON u.factory_id = f.factory_id
          WHERE 1=1";
  $binds = [];

  if (!empty($role) && $role !== 'All') {
    $sql .= " AND u.role = :role";
    $binds[':role'] = $role;
  }
  if (!empty($status) && $status !== 'All') {
    $sql .= " AND u.status = :status";
    $binds[':status'] = $status;
  }
  if (!empty($search)) {
    $sql .= " AND (LOWER(u.full_name) LIKE :search OR LOWER(u.username) LIKE :search)";
    $binds[':search'] = '%' . strtolower($search) . '%';
  }

  $sql .= " ORDER BY u.user_id";
  $rows = fetchRows($conn, $sql, $binds);
  jsonResponse(['success' => true, 'data' => $rows]);
}

// Handle POST: Create user (Admin only) OR Change Password (any logged in user)
elseif ($method === 'POST') {
  header('Content-Type: application/json');

  if (isset($_GET['action']) && $_GET['action'] === 'change_password') {
    // Change password for currently logged-in user
    if (!isset($_SESSION['user_id'])) {
      jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $current_password = $data['current_password'] ?? '';
    $new_password = $data['new_password'] ?? '';
    $confirm_password = $data['confirm_password'] ?? '';

    if (empty($current_password) || empty($new_password)) {
      jsonResponse(['success' => false, 'message' => 'Current and new passwords are required'], 400);
    }
    if ($new_password !== $confirm_password) {
      jsonResponse(['success' => false, 'message' => 'New passwords do not match'], 400);
    }

    $user_id = $_SESSION['user_id'];
    $sql = "SELECT password_hash FROM USER_ WHERE user_id = :user_id";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':user_id', $user_id);
    oci_execute($stmt);
    $row = oci_fetch_assoc($stmt);

    if (!$row || !password_verify($current_password, $row['PASSWORD_HASH'])) {
      jsonResponse(['success' => false, 'message' => 'Incorrect current password'], 400);
    }

    $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
    $update_sql = "UPDATE USER_ SET password_hash = :new_hash WHERE user_id = :user_id";
    $update_stmt = oci_parse($conn, $update_sql);
    oci_bind_by_name($update_stmt, ':new_hash', $new_hash);
    oci_bind_by_name($update_stmt, ':user_id', $user_id);

    if (oci_execute($update_stmt)) {
      jsonResponse(['success' => true, 'message' => 'Password changed successfully']);
    } else {
      $err = oci_error($update_stmt);
      jsonResponse(['success' => false, 'message' => 'Database error: ' . $err['message']], 500);
    }
  } else {
    // Normal User Creation (Admin only)
    authCheck(['admin']);

    $data = json_decode(file_get_contents('php://input'), true);
    $username = $data['username'] ?? '';
    $password = $data['password'] ?? '';
    $role = $data['role'] ?? '';
    $full_name = $data['full_name'] ?? '';
    $factory_id = !empty($data['factory_id']) ? intval($data['factory_id']) : null;
    $email = $data['email'] ?? '';
    $status = $data['status'] ?? 'Active';

    if (empty($username) || empty($password) || empty($role) || empty($full_name) || empty($email)) {
      jsonResponse(['success' => false, 'message' => 'Missing required fields'], 400);
    }

    // Check if username already exists
    $check_sql = "SELECT COUNT(*) AS cnt FROM USER_ WHERE username = :username";
    $check_stmt = oci_parse($conn, $check_sql);
    oci_bind_by_name($check_stmt, ':username', $username);
    oci_execute($check_stmt);
    $check_row = oci_fetch_assoc($check_stmt);
    if ($check_row['CNT'] > 0) {
      jsonResponse(['success' => false, 'message' => 'Username already exists'], 400);
    }

    $next_id = getNextId($conn, 'USER_', 'user_id');
    $hash = password_hash($password, PASSWORD_DEFAULT);

    $sql = "INSERT INTO USER_ (user_id, username, password_hash, role, full_name, factory_id, email, status) 
            VALUES (:user_id, :username, :password_hash, :role, :full_name, :factory_id, :email, :status)";

    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':user_id', $next_id);
    oci_bind_by_name($stmt, ':username', $username);
    oci_bind_by_name($stmt, ':password_hash', $hash);
    oci_bind_by_name($stmt, ':role', $role);
    oci_bind_by_name($stmt, ':full_name', $full_name);
    oci_bind_by_name($stmt, ':factory_id', $factory_id);
    oci_bind_by_name($stmt, ':email', $email);
    oci_bind_by_name($stmt, ':status', $status);

    if (oci_execute($stmt)) {
      jsonResponse(['success' => true, 'message' => 'User created successfully', 'user_id' => $next_id]);
    } else {
      $err = oci_error($stmt);
      jsonResponse(['success' => false, 'message' => 'Database error: ' . $err['message']], 500);
    }
  }
}

// Handle PATCH: Update user profile or status
elseif ($method === 'PATCH') {
  if (!isset($_SESSION['user_id'])) {
    jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
  }
  header('Content-Type: application/json');

  $data = json_decode(file_get_contents('php://input'), true);
  $id = intval($data['user_id'] ?? 0);

  if ($id <= 0) {
    jsonResponse(['success' => false, 'message' => 'Invalid user ID'], 400);
  }

  $is_admin = ($_SESSION['role'] === 'admin');

  // Non-admins can only update their own profile
  if (!$is_admin && $id !== intval($_SESSION['user_id'])) {
    jsonResponse(['success' => false, 'message' => 'Forbidden'], 403);
  }

  $sql = "UPDATE USER_ SET ";
  $updates = [];
  $binds = [':id' => $id];

  if (isset($data['full_name'])) {
    $updates[] = "full_name = :full_name";
    $binds[':full_name'] = $data['full_name'];
  }

  if (isset($data['email'])) {
    $updates[] = "email = :email";
    $binds[':email'] = $data['email'];
  }

  // Admin-only updates
  if ($is_admin) {
    if (isset($data['role'])) {
      $updates[] = "role = :role";
      $binds[':role'] = $data['role'];
    }
    if (array_key_exists('factory_id', $data)) {
      $updates[] = "factory_id = :factory_id";
      $fid = !empty($data['factory_id']) ? intval($data['factory_id']) : null;
      $binds[':factory_id'] = $fid;
    }
    if (isset($data['status'])) {
      $updates[] = "status = :status";
      $binds[':status'] = $data['status'];
    }
    if (!empty($data['password'])) {
      $updates[] = "password_hash = :password_hash";
      $binds[':password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT);
    }
  }

  if (empty($updates)) {
    jsonResponse(['success' => false, 'message' => 'No fields to update'], 400);
  }

  $sql .= implode(", ", $updates) . " WHERE user_id = :id";

  $stmt = oci_parse($conn, $sql);
  foreach ($binds as $key => $val) {
    oci_bind_by_name($stmt, $key, $binds[$key]);
  }

  if (oci_execute($stmt)) {
    // Update active session variables if the logged-in user updated their own profile
    if ($id === intval($_SESSION['user_id'])) {
      if (isset($data['full_name'])) {
        $_SESSION['full_name'] = $data['full_name'];
      }
    }
    jsonResponse(['success' => true, 'message' => 'User updated successfully']);
  } else {
    $err = oci_error($stmt);
    jsonResponse(['success' => false, 'message' => 'Database error: ' . $err['message']], 500);
  }
}

else {
  jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}
