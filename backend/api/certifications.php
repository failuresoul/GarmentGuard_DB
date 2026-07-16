<?php
session_start();
require_once '../config/db.php';
require_once '../includes/helpers.php';
$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'GET') {
    authCheck(['admin', 'compliance_officer', 'buyer_user', 'inspector']);
} else {
    authCheck(['admin', 'compliance_officer']);
}
header('Content-Type: application/json');

// ─── GET ────────────────────────────────────────────────────────────────────
if ($method === 'GET') {
    $factory_id = $_GET['factory_id'] ?? null;
    $status     = $_GET['status']     ?? null;

    $sql = "SELECT c.cert_id, c.factory_id, f.factory_name, c.cert_name,
                   c.issuing_body,
                   TO_CHAR(c.issue_date,  'YYYY-MM-DD') AS issue_date,
                   TO_CHAR(c.expiry_date, 'YYYY-MM-DD') AS expiry_date,
                   c.status,
                   ROUND(c.expiry_date - SYSDATE) AS days_left
            FROM CERTIFICATION c
            JOIN FACTORY f ON c.factory_id = f.factory_id
            WHERE 1=1";

    $binds = [];
    if ($factory_id && $factory_id !== '') {
        $sql .= " AND c.factory_id = :factory_id";
        $binds[':factory_id'] = intval($factory_id);
    }
    if ($status && $status !== '' && $status !== 'All') {
        $sql .= " AND c.status = :status";
        $binds[':status'] = $status;
    }
    $sql .= " ORDER BY c.expiry_date ASC, c.cert_id DESC";

    $rows = fetchRows($conn, $sql, $binds);
    jsonResponse(['success' => true, 'data' => $rows]);
}

// ─── POST ───────────────────────────────────────────────────────────────────
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $factory_id   = $data['factory_id']   ?? null;
    $cert_name    = $data['cert_name']    ?? null;
    $issuing_body = $data['issuing_body'] ?? null;
    $issue_date   = $data['issue_date']   ?? null;
    $expiry_date  = $data['expiry_date']  ?? null;
    $status       = $data['status']       ?? 'Active';

    if (!$factory_id || !$cert_name || !$expiry_date) {
        jsonResponse(['success' => false, 'message' => 'Factory, certification name, and expiry date are required.'], 400);
    }
    if (!in_array($status, ['Active', 'Expired', 'Revoked'])) {
        jsonResponse(['success' => false, 'message' => 'Invalid status value.'], 400);
    }

    $next_id = getNextId($conn, 'CERTIFICATION', 'cert_id');

    $sql = "INSERT INTO CERTIFICATION (cert_id, factory_id, cert_name, issuing_body, issue_date, expiry_date, status)
            VALUES (:id, :fid, :cname, :ibody,
                    TO_DATE(:idate, 'YYYY-MM-DD'),
                    TO_DATE(:edate, 'YYYY-MM-DD'),
                    :status)";

    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':id',     $next_id);
    oci_bind_by_name($stmt, ':fid',    $factory_id);
    oci_bind_by_name($stmt, ':cname',  $cert_name);
    oci_bind_by_name($stmt, ':ibody',  $issuing_body);
    oci_bind_by_name($stmt, ':idate',  $issue_date);
    oci_bind_by_name($stmt, ':edate',  $expiry_date);
    oci_bind_by_name($stmt, ':status', $status);

    if (!oci_execute($stmt)) {
        $err = oci_error($stmt);
        jsonResponse(['success' => false, 'message' => $err['message'] ?? 'Insert failed'], 500);
    }

    jsonResponse(['success' => true, 'message' => 'Certification added successfully.', 'cert_id' => $next_id]);
}

// ─── PATCH ──────────────────────────────────────────────────────────────────
if ($method === 'PATCH') {
    $data    = json_decode(file_get_contents('php://input'), true);
    $cert_id = $data['cert_id'] ?? null;
    $status  = $data['status']  ?? null;

    if (!$cert_id || !$status) {
        jsonResponse(['success' => false, 'message' => 'cert_id and status are required.'], 400);
    }
    if (!in_array($status, ['Active', 'Expired', 'Revoked'])) {
        jsonResponse(['success' => false, 'message' => 'Invalid status value.'], 400);
    }

    $stmt = oci_parse($conn, "UPDATE CERTIFICATION SET status = :status WHERE cert_id = :id");
    oci_bind_by_name($stmt, ':status', $status);
    oci_bind_by_name($stmt, ':id',     $cert_id);

    if (!oci_execute($stmt)) {
        $err = oci_error($stmt);
        jsonResponse(['success' => false, 'message' => $err['message'] ?? 'Update failed'], 500);
    }

    jsonResponse(['success' => true, 'message' => "Certification status updated to {$status}."]);
}

jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
