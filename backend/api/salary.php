<?php
session_start();
require_once '../config/db.php';
require_once '../includes/helpers.php';
authCheck();
header('Content-Type: application/json');

$rows = fetchRows($conn,
  "SELECT sr.record_id, w.full_name AS worker_name, f.factory_name,
   w.designation, sr.month, sr.year, sr.base_amount,
   sr.overtime_hours, sr.overtime_paid, sr.deductions,
   sr.net_salary, sr.payment_status
   FROM SALARY_RECORD sr
   JOIN WORKER w ON sr.worker_id = w.worker_id
   JOIN FACTORY f ON w.factory_id = f.factory_id
   ORDER BY sr.year DESC, sr.month DESC, sr.record_id"
);

jsonResponse(['success' => true, 'data' => $rows]);
