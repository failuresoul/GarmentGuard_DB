<?php
session_start();
require_once '../config/db.php';
require_once '../includes/helpers.php';
authCheck();
header('Content-Type: application/json');

$rows = fetchRows($conn,
  "SELECT e.equipment_id, f.factory_name, e.equipment_type,
   e.quantity,
   TO_CHAR(e.purchase_date,'DD-Mon-YYYY') AS purchase_date,
   TO_CHAR(e.expiry_date,'DD-Mon-YYYY') AS expiry_date,
   TO_CHAR(e.last_inspection,'DD-Mon-YYYY') AS last_inspection,
   e.condition_status, e.location
   FROM SAFETY_EQUIPMENT e JOIN FACTORY f ON e.factory_id = f.factory_id
   ORDER BY e.equipment_id"
);

jsonResponse(['success' => true, 'data' => $rows]);
