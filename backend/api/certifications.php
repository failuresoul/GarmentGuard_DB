<?php
session_start();
require_once '../config/db.php';
require_once '../includes/helpers.php';
authCheck();
header('Content-Type: application/json');

$rows = fetchRows($conn,
  "SELECT c.cert_id, f.factory_name, c.cert_name, c.issuing_body,
   TO_CHAR(c.issue_date,'DD-Mon-YYYY') AS issue_date,
   TO_CHAR(c.expiry_date,'DD-Mon-YYYY') AS expiry_date,
   c.status
   FROM CERTIFICATION c JOIN FACTORY f ON c.factory_id = f.factory_id
   ORDER BY c.cert_id"
);

jsonResponse(['success' => true, 'data' => $rows]);
