<?php
session_start();
require_once '../config/db.php';
require_once '../includes/helpers.php';
authCheck();
header('Content-Type: application/json');

$rows = fetchRows($conn,
  "SELECT w.worker_id, w.full_name, w.national_id, w.designation,
   TO_CHAR(w.join_date,'DD-Mon-YYYY') AS join_date,
   w.base_salary, w.shift, w.status,
   f.factory_name
   FROM WORKER w JOIN FACTORY f ON w.factory_id = f.factory_id
   ORDER BY w.worker_id"
);

jsonResponse(['success' => true, 'data' => $rows]);
