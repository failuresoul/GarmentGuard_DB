<?php
session_start();
require_once '../config/db.php';
require_once '../includes/helpers.php';
authCheck();
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

// ─── GET ───────────────────────────────────────────────────────────────────
if ($method === 'GET') {
    $factory_id = $_GET['factory_id'] ?? null;
    $status     = $_GET['status']     ?? null;
    $search     = $_GET['search']     ?? null;

    $sql = "SELECT w.worker_id, w.full_name, w.national_id, w.designation, w.shift,
            w.base_salary, w.status, TO_CHAR(w.join_date,'DD-Mon-YYYY') AS join_date,
            w.phone, w.email, w.factory_id, f.factory_name,
            fn_worker_ytd_salary(w.worker_id, EXTRACT(YEAR FROM SYSDATE)) AS ytd_salary
            FROM WORKER w JOIN FACTORY f ON w.factory_id = f.factory_id WHERE 1=1";

    $binds = [];
    if ($factory_id) {
        $sql .= " AND w.factory_id = :fid";
        $binds[':fid'] = $factory_id;
    }
    if ($status) {
        $sql .= " AND w.status = :status";
        $binds[':status'] = $status;
    }
    if ($search) {
        $sql .= " AND (UPPER(w.full_name) LIKE UPPER('%'||:search||'%') OR UPPER(w.national_id) LIKE UPPER('%'||:search||'%'))";
        $binds[':search'] = $search;
    }
    $sql .= " ORDER BY w.full_name";

    $rows = fetchRows($conn, $sql, $binds);
    jsonResponse(['success' => true, 'data' => $rows]);
}

// ─── POST ──────────────────────────────────────────────────────────────────
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $factory_id  = $data['factory_id']  ?? null;
    $full_name   = $data['full_name']   ?? null;
    $national_id = $data['national_id'] ?? null;
    $designation = $data['designation'] ?? null;
    $join_date   = $data['join_date']   ?? null;
    $base_salary = $data['base_salary'] ?? null;
    $shift       = $data['shift']       ?? null;
    $phone       = $data['phone']       ?? '';
    $email       = $data['email']       ?? '';

    if (!$factory_id || !$full_name || !$national_id || !$designation || !$join_date || !$base_salary || !$shift) {
        jsonResponse(['success' => false, 'message' => 'Missing required fields'], 400);
    }

    $next_id = getNextId($conn, 'WORKER', 'worker_id');
    $join_date_oracle = date('d-M-Y', strtotime($join_date));

    $sql = "BEGIN sp_hire_worker(:id,:fid,:name,:nid,:desig,TO_DATE(:jdate,'DD-Mon-YYYY'),:salary,:shift,:phone,:email); END;";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':id',     $next_id);
    oci_bind_by_name($stmt, ':fid',    $factory_id);
    oci_bind_by_name($stmt, ':name',   $full_name);
    oci_bind_by_name($stmt, ':nid',    $national_id);
    oci_bind_by_name($stmt, ':desig',  $designation);
    oci_bind_by_name($stmt, ':jdate',  $join_date_oracle);
    oci_bind_by_name($stmt, ':salary', $base_salary);
    oci_bind_by_name($stmt, ':shift',  $shift);
    oci_bind_by_name($stmt, ':phone',  $phone);
    oci_bind_by_name($stmt, ':email',  $email);

    $result = @oci_execute($stmt);
    if (!$result) {
        $err = oci_error($stmt);
        // ORA-20001 = Non-Compliant factory restriction
        if (isset($err['code']) && $err['code'] == 20001) {
            jsonResponse(['success' => false, 'code' => 20001, 'message' => 'Factory is Non-Compliant. Cannot hire worker.']);
        }
        jsonResponse(['success' => false, 'message' => $err['message'] ?? 'Failed to register worker'], 500);
    }

    jsonResponse(['success' => true, 'message' => 'Worker registered successfully']);
}

// ─── PATCH ─────────────────────────────────────────────────────────────────
if ($method === 'PATCH') {
    $data      = json_decode(file_get_contents('php://input'), true);
    $worker_id = $data['worker_id'] ?? null;
    $status    = $data['status']    ?? null;

    if (!$worker_id || !$status) {
        jsonResponse(['success' => false, 'message' => 'worker_id and status are required'], 400);
    }

    $allowed = ['Active', 'Inactive', 'Terminated'];
    if (!in_array($status, $allowed)) {
        jsonResponse(['success' => false, 'message' => 'Invalid status value'], 400);
    }

    $stmt = oci_parse($conn, "UPDATE WORKER SET status = :status WHERE worker_id = :id");
    oci_bind_by_name($stmt, ':status', $status);
    oci_bind_by_name($stmt, ':id',     $worker_id);
    $result = oci_execute($stmt);

    if (!$result) {
        $err = oci_error($stmt);
        jsonResponse(['success' => false, 'message' => $err['message'] ?? 'Update failed'], 500);
    }

    jsonResponse(['success' => true, 'message' => 'Worker status updated']);
}

jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
