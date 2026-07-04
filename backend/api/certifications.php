<?php
session_start();
require_once '../config/db.php';
require_once '../includes/helpers.php';
authCheck();
header('Content-Type: application/json');

if (isset($_GET['factory_id'])) {
  $factory_id = intval($_GET['factory_id']);
  $rows = fetchRows($conn,
    "SELECT c.cert_id, c.cert_name, c.issuing_body,
     TO_CHAR(c.issue_date,'DD-Mon-YYYY') AS issue_date,
     TO_CHAR(c.expiry_date,'DD-Mon-YYYY') AS expiry_date,
     c.status,
     ROUND(c.expiry_date - SYSDATE) AS days_left,
     fn_is_cert_valid(c.factory_id, c.cert_name) AS is_valid,
     f.factory_name
     FROM CERTIFICATION c
     JOIN FACTORY f ON c.factory_id = f.factory_id
     WHERE c.factory_id = :fid
     ORDER BY c.cert_id",
    [':fid' => $factory_id]
  );
} else {
  $rows = fetchRows($conn,
    "SELECT c.cert_id, f.factory_name, c.cert_name, c.issuing_body,
     TO_CHAR(c.issue_date,'DD-Mon-YYYY') AS issue_date,
     TO_CHAR(c.expiry_date,'DD-Mon-YYYY') AS expiry_date,
     c.status,
     ROUND(c.expiry_date - SYSDATE) AS days_left,
     fn_is_cert_valid(c.factory_id, c.cert_name) AS is_valid
     FROM CERTIFICATION c JOIN FACTORY f ON c.factory_id = f.factory_id
     ORDER BY c.cert_id"
  );
}

jsonResponse(['success' => true, 'data' => $rows]);
