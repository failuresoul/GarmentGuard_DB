<?php
session_start();
require_once '../config/db.php';
require_once '../includes/helpers.php';
authCheck(['admin', 'compliance_officer']);
header('Content-Type: application/json');

$role = $_GET['role'] ?? null;
$sql = "SELECT u.user_id, u.username, u.role, u.full_name, u.email, u.status,
        NVL(f.factory_name, 'N/A') AS factory_name
        FROM USER_ u LEFT JOIN FACTORY f ON u.factory_id = f.factory_id WHERE 1=1";
$binds = [];
if ($role) {
    $sql .= " AND u.role = :role";
    $binds[':role'] = $role;
}
$sql .= " ORDER BY u.user_id";

$rows = fetchRows($conn, $sql, $binds);

jsonResponse(['success' => true, 'data' => $rows]);
