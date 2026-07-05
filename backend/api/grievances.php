<?php
session_start();
require_once '../config/db.php';
require_once '../includes/helpers.php';
authCheck();
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

// ─── GET (Fetch grievances with filters) ──────────────────────────────────
if ($method === 'GET') {
    $factory_id = $_GET['factory_id'] ?? null;
    $category = $_GET['category'] ?? null;
    $search = $_GET['search'] ?? null;

    $sql = "SELECT g.grievance_id, g.worker_id, w.full_name AS worker_name,
            f.factory_id, f.factory_name, g.category, g.description,
            TO_CHAR(g.submitted_date,'DD-Mon-YYYY') AS submitted_date,
            g.status, TO_CHAR(g.resolved_date,'DD-Mon-YYYY') AS resolved_date,
            g.resolution_notes, fn_grievance_days(g.grievance_id) AS days_open
            FROM GRIEVANCE g 
            JOIN WORKER w ON g.worker_id = w.worker_id
            JOIN FACTORY f ON w.factory_id = f.factory_id
            WHERE 1=1";

    $binds = [];
    if ($factory_id) {
        $sql .= " AND f.factory_id = :fid";
        $binds[':fid'] = $factory_id;
    }
    if ($category && $category !== 'All') {
        $sql .= " AND g.category = :category";
        $binds[':category'] = $category;
    }
    if ($search) {
        $sql .= " AND UPPER(g.description) LIKE UPPER('%'||:search||'%')";
        $binds[':search'] = $search;
    }
    $sql .= " ORDER BY g.submitted_date DESC";

    $rows = fetchRows($conn, $sql, $binds);
    jsonResponse(['success' => true, 'data' => $rows]);
}

// ─── POST (Submit grievance) ───────────────────────────────────────────────
if ($method === 'POST') {
    authCheck(['compliance_officer', 'worker']);
    $data = json_decode(file_get_contents('php://input'), true);

    $worker_id = $data['worker_id'] ?? null;
    $category = $data['category'] ?? null;
    $description = $data['description'] ?? null;

    if (!$worker_id || !$category || !$description) {
        jsonResponse(['success' => false, 'message' => 'Missing required fields'], 400);
    }

    if (strlen($description) < 20) {
        jsonResponse(['success' => false, 'message' => 'Description must be at least 20 characters long'], 400);
    }

    $next_id = getNextId($conn, 'GRIEVANCE', 'grievance_id');

    $sql = "BEGIN sp_submit_grievance(:id, :wid, :cat, :desc); END;";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':id', $next_id);
    oci_bind_by_name($stmt, ':wid', $worker_id);
    oci_bind_by_name($stmt, ':cat', $category);
    oci_bind_by_name($stmt, ':desc', $description);

    $result = @oci_execute($stmt);
    if (!$result) {
        $err = oci_error($stmt);
        jsonResponse(['success' => false, 'message' => $err['message'] ?? 'Failed to submit grievance'], 500);
    }

    jsonResponse(['success' => true, 'grievance_id' => $next_id, 'message' => 'Grievance submitted successfully']);
}

// ─── PATCH (Update status & notes) ──────────────────────────────────────────
if ($method === 'PATCH') {
    $data = json_decode(file_get_contents('php://input'), true);

    $grievance_id = $data['grievance_id'] ?? null;
    $status = $data['status'] ?? null;
    $notes = $data['resolution_notes'] ?? null;

    if (!$grievance_id || !$status) {
        jsonResponse(['success' => false, 'message' => 'Missing required fields'], 400);
    }

    $allowed = ['Open', 'In Progress', 'Resolved'];
    if (!in_array($status, $allowed)) {
        jsonResponse(['success' => false, 'message' => 'Invalid status value'], 400);
    }

    $sql = "UPDATE GRIEVANCE SET status = :status, resolution_notes = :notes WHERE grievance_id = :id";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':status', $status);
    oci_bind_by_name($stmt, ':notes', $notes);
    oci_bind_by_name($stmt, ':id', $grievance_id);

    $result = @oci_execute($stmt);
    if (!$result) {
        $err = oci_error($stmt);
        jsonResponse(['success' => false, 'message' => $err['message'] ?? 'Failed to update grievance'], 500);
    }

    jsonResponse(['success' => true, 'message' => 'Grievance updated successfully']);
}
?>
