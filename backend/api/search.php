<?php
session_start();
require_once '../config/db.php';
require_once '../includes/helpers.php';
authCheck();

$q = $_GET['q'] ?? '';
if (strlen($q) < 2) {
    jsonResponse([]);
}

$sql = "SELECT 'factory' AS type, factory_id AS id, factory_name AS name, district AS subtitle
        FROM FACTORY WHERE UPPER(factory_name) LIKE UPPER(:q1)
        UNION ALL
        SELECT 'worker', worker_id, full_name, designation
        FROM WORKER WHERE UPPER(full_name) LIKE UPPER(:q2) OR UPPER(national_id) LIKE UPPER(:q3)
        UNION ALL
        SELECT 'audit', audit_id, 'Audit #' || audit_id, result
        FROM AUDIT_RECORD WHERE UPPER(result) LIKE UPPER(:q4)
        FETCH FIRST 10 ROWS ONLY";

$search = '%' . $q . '%';
$stmt = oci_parse($conn, $sql);
oci_bind_by_name($stmt, ':q1', $search);
oci_bind_by_name($stmt, ':q2', $search);
oci_bind_by_name($stmt, ':q3', $search);
oci_bind_by_name($stmt, ':q4', $search);

if (!oci_execute($stmt)) {
    $e = oci_error($stmt);
    jsonResponse(['success' => false, 'message' => $e['message']], 500);
}

$rows = [];
while ($row = oci_fetch_assoc($stmt)) {
    // Map keys to lowercase for standard JS compatibility
    $rows[] = [
        'type' => $row['TYPE'],
        'id' => $row['ID'],
        'name' => $row['NAME'],
        'subtitle' => $row['SUBTITLE']
    ];
}
jsonResponse($rows);
