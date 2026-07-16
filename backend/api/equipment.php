<?php
session_start();
require_once '../config/db.php';
require_once '../includes/helpers.php';
$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'GET') {
    authCheck(['admin', 'compliance_officer', 'inspector', 'worker']);
} else {
    authCheck(['admin', 'compliance_officer', 'inspector']);
}
header('Content-Type: application/json');

// ─── GET ────────────────────────────────────────────────────────────────────
if ($method === 'GET') {
    $factory_id       = $_GET['factory_id']       ?? null;
    $condition_status = $_GET['condition_status'] ?? null;
    $expiry_days      = $_GET['expiry_days']      ?? null;

    $sql = "SELECT e.equipment_id, e.factory_id, f.factory_name, e.equipment_type,
                   e.quantity,
                   TO_CHAR(e.purchase_date,   'YYYY-MM-DD') AS purchase_date,
                   TO_CHAR(e.expiry_date,     'YYYY-MM-DD') AS expiry_date,
                   TO_CHAR(e.last_inspection, 'YYYY-MM-DD') AS last_inspection,
                   e.condition_status, e.location,
                   ROUND(e.expiry_date - SYSDATE) AS days_to_expiry
            FROM SAFETY_EQUIPMENT e
            JOIN FACTORY f ON e.factory_id = f.factory_id
            WHERE 1=1";

    $binds = [];
    if ($factory_id && $factory_id !== '') {
        $sql .= " AND e.factory_id = :factory_id";
        $binds[':factory_id'] = intval($factory_id);
    }
    if ($condition_status && $condition_status !== '' && $condition_status !== 'All') {
        $sql .= " AND e.condition_status = :cond";
        $binds[':cond'] = $condition_status;
    }
    if ($expiry_days && $expiry_days !== '' && $expiry_days !== 'All') {
        $sql .= " AND e.expiry_date BETWEEN SYSDATE AND SYSDATE + :days";
        $binds[':days'] = intval($expiry_days);
    }
    if ($_SESSION['role'] === 'worker') {
        $sql .= " AND e.factory_id = :session_fid";
        $binds[':session_fid'] = intval($_SESSION['factory_id']);
    }
    $sql .= " ORDER BY e.expiry_date ASC, e.equipment_id ASC";

    $rows = fetchRows($conn, $sql, $binds);
    jsonResponse(['success' => true, 'data' => $rows]);
}

// ─── POST ───────────────────────────────────────────────────────────────────
if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    $factory_id       = $data['factory_id']       ?? null;
    $equipment_type   = $data['equipment_type']   ?? null;
    $quantity         = $data['quantity']         ?? 0;
    $purchase_date    = $data['purchase_date']    ?? null;
    $expiry_date      = $data['expiry_date']      ?? null;
    $condition_status = $data['condition_status'] ?? 'Good';
    $location         = $data['location']         ?? null;

    if (!$factory_id || !$equipment_type) {
        jsonResponse(['success' => false, 'message' => 'Factory and equipment type are required.'], 400);
    }
    if (!in_array($condition_status, ['Good', 'Fair', 'Poor', 'Critical'])) {
        jsonResponse(['success' => false, 'message' => 'Invalid condition status.'], 400);
    }

    $next_id = getNextId($conn, 'SAFETY_EQUIPMENT', 'equipment_id');

    $sql = "INSERT INTO SAFETY_EQUIPMENT
                (equipment_id, factory_id, equipment_type, quantity, purchase_date, expiry_date, condition_status, location)
            VALUES
                (:id, :fid, :etype, :qty,
                 TO_DATE(:pdate, 'YYYY-MM-DD'),
                 TO_DATE(:edate, 'YYYY-MM-DD'),
                 :cond, :loc)";

    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':id',    $next_id);
    oci_bind_by_name($stmt, ':fid',   $factory_id);
    oci_bind_by_name($stmt, ':etype', $equipment_type);
    oci_bind_by_name($stmt, ':qty',   $quantity);
    oci_bind_by_name($stmt, ':pdate', $purchase_date);
    oci_bind_by_name($stmt, ':edate', $expiry_date);
    oci_bind_by_name($stmt, ':cond',  $condition_status);
    oci_bind_by_name($stmt, ':loc',   $location);

    if (!oci_execute($stmt)) {
        $err = oci_error($stmt);
        jsonResponse(['success' => false, 'message' => $err['message'] ?? 'Insert failed'], 500);
    }

    jsonResponse(['success' => true, 'message' => 'Safety equipment added successfully.', 'equipment_id' => $next_id]);
}

// ─── PATCH ──────────────────────────────────────────────────────────────────
if ($method === 'PATCH') {
    $data         = json_decode(file_get_contents('php://input'), true);
    $equipment_id = $data['equipment_id']    ?? null;
    $action       = $data['action']          ?? 'inspect';
    $new_condition= $data['condition_status'] ?? null;

    if (!$equipment_id) {
        jsonResponse(['success' => false, 'message' => 'equipment_id is required.'], 400);
    }

    if ($action === 'inspect') {
        // Mark inspected: update last_inspection = today, optionally update condition
        $sql = "UPDATE SAFETY_EQUIPMENT SET last_inspection = SYSDATE";
        $binds = [];
        if ($new_condition && in_array($new_condition, ['Good', 'Fair', 'Poor', 'Critical'])) {
            $sql .= ", condition_status = :cond";
            $binds[':cond'] = $new_condition;
        }
        $sql .= " WHERE equipment_id = :id";
        $binds[':id'] = $equipment_id;

        $stmt = oci_parse($conn, $sql);
        foreach ($binds as $k => $v) {
            oci_bind_by_name($stmt, $k, $binds[$k]);
        }
        if (!oci_execute($stmt)) {
            $err = oci_error($stmt);
            jsonResponse(['success' => false, 'message' => $err['message'] ?? 'Update failed'], 500);
        }
        jsonResponse(['success' => true, 'message' => 'Inspection recorded successfully.']);
    }

    jsonResponse(['success' => false, 'message' => 'Unknown action.'], 400);
}

jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
