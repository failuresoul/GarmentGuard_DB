<?php
session_start();
require_once '../config/db.php';
require_once '../includes/helpers.php';

// Auth check: allow admin and compliance_officer
authCheck(['admin', 'compliance_officer']);
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

// ─── GET ───────────────────────────────────────────────────────────────────
if ($method === 'GET') {
    $month = $_GET['month'] ?? null;
    $year = $_GET['year'] ?? null;
    $payment_status = $_GET['payment_status'] ?? null;

    $sql = "SELECT sr.record_id, sr.worker_id, sr.month, sr.year, sr.base_amount, 
                   sr.overtime_hours, sr.overtime_paid, sr.deductions, sr.net_salary, 
                   sr.payment_status, w.full_name AS worker_name, w.base_salary, f.factory_name, w.factory_id
            FROM SALARY_RECORD sr 
            JOIN WORKER w ON sr.worker_id = w.worker_id 
            JOIN FACTORY f ON w.factory_id = f.factory_id 
            WHERE 1=1";

    $binds = [];
    if ($month !== null && $month !== '') {
        $sql .= " AND sr.month = :month";
        $binds[':month'] = intval($month);
    }
    if ($year !== null && $year !== '') {
        $sql .= " AND sr.year = :year";
        $binds[':year'] = intval($year);
    }
    if ($payment_status !== null && $payment_status !== '' && $payment_status !== 'All') {
        $sql .= " AND sr.payment_status = :payment_status";
        $binds[':payment_status'] = $payment_status;
    }
    $sql .= " ORDER BY sr.year DESC, sr.month DESC, sr.record_id DESC";

    $rows = fetchRows($conn, $sql, $binds);
    jsonResponse(['success' => true, 'data' => $rows]);
}

// ─── POST ──────────────────────────────────────────────────────────────────
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $worker_id      = $data['worker_id'] ?? null;
    $month          = $data['month'] ?? null;
    $year           = $data['year'] ?? null;
    $overtime_hours = isset($data['overtime_hours']) ? floatval($data['overtime_hours']) : 0;
    $deductions     = isset($data['deductions']) ? floatval($data['deductions']) : 0;

    if (!$worker_id || !$month || !$year) {
        jsonResponse(['success' => false, 'message' => 'Missing required fields: worker_id, month, and year are required.'], 400);
    }

    if ($overtime_hours < 0 || $overtime_hours > 60) {
        jsonResponse(['success' => false, 'message' => 'Overtime hours must be between 0 and 60.'], 400);
    }
    if ($deductions < 0) {
        jsonResponse(['success' => false, 'message' => 'Deductions cannot be negative.'], 400);
    }

    // 1. Check duplicate
    $dup_sql = "SELECT COUNT(*) AS cnt FROM SALARY_RECORD WHERE worker_id = :wid AND month = :m AND year = :y";
    $dup_stmt = oci_parse($conn, $dup_sql);
    oci_bind_by_name($dup_stmt, ':wid', $worker_id);
    oci_bind_by_name($dup_stmt, ':m', $month);
    oci_bind_by_name($dup_stmt, ':y', $year);
    oci_execute($dup_stmt);
    $dup_row = oci_fetch_assoc($dup_stmt);
    if ($dup_row['CNT'] > 0) {
        jsonResponse(['success' => false, 'message' => 'Salary already processed for this month']);
    }

    // 2. Fetch worker base_salary
    $worker_sql = "SELECT base_salary FROM WORKER WHERE worker_id = :wid";
    $worker_stmt = oci_parse($conn, $worker_sql);
    oci_bind_by_name($worker_stmt, ':wid', $worker_id);
    oci_execute($worker_stmt);
    $worker_row = oci_fetch_assoc($worker_stmt);
    if (!$worker_row) {
        jsonResponse(['success' => false, 'message' => 'Worker not found'], 404);
    }
    $base_salary = floatval($worker_row['BASE_SALARY']);

    // Calculate OT Rate, OT Paid, Net Salary
    $ot_rate = $base_salary / 26 / 8 * 1.25;
    $overtime_paid = round($ot_rate * $overtime_hours, 2);
    $net_salary = round($base_salary + $overtime_paid - $deductions, 2);

    // Get next ID
    $next_id = getNextId($conn, 'SALARY_RECORD', 'record_id');

    // Insert Salary Record
    $ins_sql = "INSERT INTO SALARY_RECORD (record_id, worker_id, month, year, base_amount, overtime_hours, overtime_paid, deductions, net_salary, payment_status)
                VALUES (:rid, :wid, :m, :y, :base, :oth, :otp, :ded, :net, 'Pending')";
    
    $ins_stmt = oci_parse($conn, $ins_sql);
    oci_bind_by_name($ins_stmt, ':rid', $next_id);
    oci_bind_by_name($ins_stmt, ':wid', $worker_id);
    oci_bind_by_name($ins_stmt, ':m', $month);
    oci_bind_by_name($ins_stmt, ':y', $year);
    oci_bind_by_name($ins_stmt, ':base', $base_salary);
    oci_bind_by_name($ins_stmt, ':oth', $overtime_hours);
    oci_bind_by_name($ins_stmt, ':otp', $overtime_paid);
    oci_bind_by_name($ins_stmt, ':ded', $deductions);
    oci_bind_by_name($ins_stmt, ':net', $net_salary);

    $res = oci_execute($ins_stmt);
    if (!$res) {
        $err = oci_error($ins_stmt);
        jsonResponse(['success' => false, 'message' => $err['message'] ?? 'Failed to process salary'], 500);
    }

    jsonResponse(['success' => true, 'net_salary' => $net_salary]);
}

// ─── PATCH ─────────────────────────────────────────────────────────────────
if ($method === 'PATCH') {
    $data = json_decode(file_get_contents('php://input'), true);

    $record_id = $data['record_id'] ?? null;
    $status    = $data['status'] ?? null;

    if (!$record_id || !$status) {
        jsonResponse(['success' => false, 'message' => 'record_id and status are required'], 400);
    }

    if ($status !== 'Paid') {
        jsonResponse(['success' => false, 'message' => 'Invalid status value. Only status=Paid is allowed.'], 400);
    }

    $stmt = oci_parse($conn, "UPDATE SALARY_RECORD SET payment_status = 'Paid' WHERE record_id = :id");
    oci_bind_by_name($stmt, ':id', $record_id);
    $result = oci_execute($stmt);

    if (!$result) {
        $err = oci_error($stmt);
        jsonResponse(['success' => false, 'message' => $err['message'] ?? 'Failed to update payment status'], 500);
    }

    jsonResponse(['success' => true, 'message' => 'Salary record marked as Paid successfully']);
}

jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
