<?php
session_start();
require_once '../config/db.php';
header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$username = $data['username'] ?? '';
$password = $data['password'] ?? '';

$sql = "SELECT user_id, username, password_hash, role, full_name, factory_id 
        FROM USER_ WHERE username = :username AND status = 'Active'";
$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ':username', $username);
oci_execute($stmt);
$user = oci_fetch_assoc($stmt);

if (!$user || !password_verify($password, $user['PASSWORD_HASH'])) {
  echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
  exit;
}

$_SESSION['user_id'] = $user['USER_ID'];
$_SESSION['username'] = $user['USERNAME'];
$_SESSION['role'] = $user['ROLE'];
$_SESSION['full_name'] = $user['FULL_NAME'];
$_SESSION['factory_id'] = $user['FACTORY_ID'];

$redirects = [
  'admin' => 'pages/dashboard.php',
  'compliance_officer' => 'pages/dashboard.php',
  'inspector' => 'pages/audits.php',
  'buyer_user' => 'pages/buyer.php',
  'worker' => 'pages/profile.php'
];

echo json_encode([
  'success' => true,
  'role' => $user['ROLE'],
  'redirect' => $redirects[$user['ROLE']] ?? 'pages/dashboard.php'
]);
