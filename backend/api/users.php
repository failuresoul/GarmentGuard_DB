<?php
session_start();
require_once '../config/db.php';
require_once '../includes/helpers.php';
authCheck(['admin']);
header('Content-Type: application/json');

$rows = fetchRows($conn,
  "SELECT u.user_id, u.username, u.role, u.full_name, u.email, u.status,
   NVL(f.factory_name, 'N/A') AS factory_name
   FROM USER_ u LEFT JOIN FACTORY f ON u.factory_id = f.factory_id
   ORDER BY u.user_id"
);

jsonResponse(['success' => true, 'data' => $rows]);
