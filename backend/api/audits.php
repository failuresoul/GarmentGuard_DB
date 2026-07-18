<?php
session_start();
require_once '../config/db.php';
require_once '../includes/helpers.php';
authCheck();
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

// ─── GET (Fetch audits with filters) ───────────────────────────────────────
if ($method === 'GET') {
    authCheck(['admin', 'compliance_officer', 'inspector', 'buyer', 'buyer_user']);
    $factory_id = $_GET['factory_id'] ?? null;
    $result = $_GET['result'] ?? null;

    $sql = "SELECT a.audit_id, a.factory_id, f.factory_name, a.inspector_id,
            u.full_name AS inspector_name, TO_CHAR(a.audit_date,'DD-Mon-YYYY') AS audit_date,
            TO_CHAR(a.next_scheduled,'DD-Mon-YYYY') AS next_scheduled,
            a.score, a.result, a.findings, a.recommendations
            FROM AUDIT_RECORD a JOIN FACTORY f ON a.factory_id = f.factory_id
            LEFT JOIN USER_ u ON a.inspector_id = u.user_id WHERE 1=1";

    $binds = [];
    if ($factory_id) {
        $sql .= " AND a.factory_id = :fid";
        $binds[':fid'] = $factory_id;
    }
    if ($result && $result !== 'All') {
        $sql .= " AND a.result = :result";
        $binds[':result'] = $result;
    }
    $sql .= " ORDER BY a.audit_date DESC";

    $rows = fetchRows($conn, $sql, $binds);
    jsonResponse(['success' => true, 'data' => $rows]);
}

// ─── POST (Schedule audit) ──────────────────────────────────────────────────
if ($method === 'POST') {
    authCheck(['admin', 'inspector']);

    $data = json_decode(file_get_contents('php://input'), true);

    $factory_id = $data['factory_id'] ?? null;
    $inspector_id = $data['inspector_id'] ?? null;
    $audit_date = $data['audit_date'] ?? null;
    $next_scheduled = $data['next_scheduled'] ?? null;

    if (!$factory_id || !$inspector_id || !$audit_date) {
        jsonResponse(['success' => false, 'message' => 'Missing required fields'], 400);
    }

    $next_id = getNextId($conn, 'AUDIT_RECORD', 'audit_id');
    
    // Format dates to Oracle DD-Mon-YYYY format
    $audit_date_oracle = date('d-M-Y', strtotime($audit_date));
    $next_scheduled_oracle = $next_scheduled ? date('d-M-Y', strtotime($next_scheduled)) : null;

    $sql = "BEGIN sp_schedule_audit(:id, :fid, :ins, TO_DATE(:adate, 'DD-Mon-YYYY'), TO_DATE(:ndate, 'DD-Mon-YYYY')); END;";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':id', $next_id);
    oci_bind_by_name($stmt, ':fid', $factory_id);
    oci_bind_by_name($stmt, ':ins', $inspector_id);
    oci_bind_by_name($stmt, ':adate', $audit_date_oracle);
    oci_bind_by_name($stmt, ':ndate', $next_scheduled_oracle);

    $result = @oci_execute($stmt);
    if (!$result) {
        $err = oci_error($stmt);
        // ORA-20004: Audit already scheduled for this factory this month.
        if (isset($err['code']) && $err['code'] == 20004) {
            jsonResponse(['success' => false, 'code' => 20004, 'message' => 'Audit already scheduled for this factory this month.']);
        }
        jsonResponse(['success' => false, 'message' => $err['message'] ?? 'Failed to schedule audit'], 500);
    }

    jsonResponse(['success' => true, 'message' => 'Audit scheduled successfully']);
}

// ─── PATCH (Record score) ───────────────────────────────────────────────────
if ($method === 'PATCH') {
    authCheck(['admin', 'inspector']);

    $data = json_decode(file_get_contents('php://input'), true);

    $audit_id = $data['audit_id'] ?? null;
    $score = $data['score'] ?? null;
    $result_val = $data['result'] ?? null;
    $findings = $data['findings'] ?? '';
    $recommendations = $data['recommendations'] ?? '';

    if (!$audit_id || $score === null || !$result_val) {
        jsonResponse(['success' => false, 'message' => 'Missing required fields'], 400);
    }

    $sql = "BEGIN sp_record_audit_score(:id, :score, :result, :findings, :recs); END;";
    $stmt = oci_parse($conn, $sql);
    oci_bind_by_name($stmt, ':id', $audit_id);
    oci_bind_by_name($stmt, ':score', $score);
    oci_bind_by_name($stmt, ':result', $result_val);
    oci_bind_by_name($stmt, ':findings', $findings);
    oci_bind_by_name($stmt, ':recs', $recommendations);

    $result = @oci_execute($stmt);
    if (!$result) {
        $err = oci_error($stmt);
        jsonResponse(['success' => false, 'message' => $err['message'] ?? 'Failed to record score'], 500);
    }

    jsonResponse(['success' => true, 'message' => 'Audit score recorded successfully']);
}
?>
