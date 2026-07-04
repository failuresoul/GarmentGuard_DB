<?php
session_start();
require_once '../config/db.php';
require_once '../includes/helpers.php';
authCheck();
header('Content-Type: application/json');

if (isset($_GET['factory_id'])) {
  $factory_id = intval($_GET['factory_id']);
  $rows = fetchRows($conn,
    "SELECT w.worker_id, w.full_name, w.national_id, w.designation,
     TO_CHAR(w.join_date,'DD-Mon-YYYY') AS join_date,
     w.base_salary, w.shift, w.status,
     f.factory_name,
     fn_worker_ytd_salary(w.worker_id, EXTRACT(YEAR FROM SYSDATE)) AS ytd_salary
     FROM WORKER w JOIN FACTORY f ON w.factory_id = f.factory_id
     WHERE w.factory_id = :factory_id
     ORDER BY w.worker_id",
    [':factory_id' => $factory_id]
  );
} else {
  $rows = fetchRows($conn,
    "SELECT w.worker_id, w.full_name, w.national_id, w.designation,
     TO_CHAR(w.join_date,'DD-Mon-YYYY') AS join_date,
     w.base_salary, w.shift, w.status,
     f.factory_name
     FROM WORKER w JOIN FACTORY f ON w.factory_id = f.factory_id
     ORDER BY w.worker_id"
  );
}

jsonResponse(['success' => true, 'data' => $rows]);
