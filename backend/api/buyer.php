<?php
session_start();
require_once '../config/db.php';
require_once '../includes/helpers.php';
authCheck();
header('Content-Type: application/json');

$rows = fetchRows($conn,
  "SELECT b.buyer_id, b.buyer_name, b.country, b.contact_name,
   b.email, b.phone, b.brand_name,
   (SELECT COUNT(*) FROM BUYER_FACTORY bf
    WHERE bf.buyer_id = b.buyer_id AND bf.contract_status = 'Active') AS active_contracts
   FROM BUYER b ORDER BY b.buyer_id"
);

jsonResponse(['success' => true, 'data' => $rows]);
