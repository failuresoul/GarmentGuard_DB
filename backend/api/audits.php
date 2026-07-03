<?php
session_start();
require_once '../config/db.php';
require_once '../includes/helpers.php';
authCheck();
header('Content-Type: application/json');

$rows = fetchRows($conn,
  "SELECT ar.audit_id, f.factory_name, NVL(u.full_name, 'N/A') AS inspector_name,
   TO_CHAR(ar.audit_date,'DD-Mon-YYYY') AS audit_date,
   TO_CHAR(ar.next_scheduled,'DD-Mon-YYYY') AS next_scheduled,
   ar.score, ar.result, ar.findings, ar.recommendations
   FROM AUDIT_RECORD ar
   JOIN FACTORY f ON ar.factory_id = f.factory_id
   LEFT JOIN USER_ u ON ar.inspector_id = u.user_id
   ORDER BY ar.audit_date DESC"
);

jsonResponse(['success' => true, 'data' => $rows]);
