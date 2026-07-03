<?php
session_start();
require_once '../config/db.php';
require_once '../includes/helpers.php';
authCheck();
header('Content-Type: application/json');

$rows = fetchRows($conn,
  "SELECT factory_id, factory_name, registration_no, district, division,
   total_workers, compliance_status, compliance_score,
   TO_CHAR(last_audit_date,'DD-Mon-YYYY') AS last_audit_date,
   TO_CHAR(next_audit_date,'DD-Mon-YYYY') AS next_audit_date,
   contact_person, phone, email
   FROM FACTORY ORDER BY factory_id"
);

jsonResponse(['success' => true, 'data' => $rows]);
