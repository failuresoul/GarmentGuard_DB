<?php
session_start();
require_once '../config/db.php';
require_once '../includes/helpers.php';
authCheck(['admin', 'compliance_officer', 'buyer_user', 'buyer']);
header('Content-Type: application/json');

$type = $_GET['type'] ?? '';

if ($type === '') {
    // Legacy generic report stats for admin/reports.php
    $division_stats = fetchRows($conn,
      "SELECT district AS division,
       COUNT(*) AS total_factories,
       SUM(CASE WHEN compliance_status = 'Compliant' THEN 1 ELSE 0 END) AS compliant,
       ROUND(AVG(compliance_score), 1) AS avg_score
       FROM FACTORY GROUP BY district ORDER BY district"
    );

    $grievance_stats = fetchRows($conn,
      "SELECT category, COUNT(*) AS total,
       SUM(CASE WHEN status = 'Resolved' THEN 1 ELSE 0 END) AS resolved,
       SUM(CASE WHEN status = 'Open' THEN 1 ELSE 0 END) AS open_count
       FROM GRIEVANCE GROUP BY category ORDER BY total DESC"
    );

    $salary_stats = fetchRows($conn,
      "SELECT f.factory_name,
       SUM(sr.net_salary) AS total_net,
       SUM(sr.overtime_paid) AS total_overtime,
       COUNT(sr.record_id) AS worker_count
       FROM SALARY_RECORD sr
       JOIN WORKER w ON sr.worker_id = w.worker_id
       JOIN FACTORY f ON w.factory_id = f.factory_id
       GROUP BY f.factory_name ORDER BY total_net DESC"
    );

    $audit_stats = fetchRows($conn,
      "SELECT result, COUNT(*) AS cnt FROM AUDIT_RECORD GROUP BY result ORDER BY cnt DESC"
    );

    jsonResponse(['success' => true, 'data' => [
      'division_stats'  => $division_stats,
      'grievance_stats' => $grievance_stats,
      'salary_stats'    => $salary_stats,
      'audit_stats'     => $audit_stats,
    ]]);
}

// Below are the new API endpoints for the advanced reports charts

$factory_id = $_GET['factory_id'] ?? null;
$from_date = $_GET['from_date'] ?? null;
$to_date = $_GET['to_date'] ?? null;

switch ($type) {
    case 'audit_trend':
        $sql = "SELECT audit_date, score FROM AUDIT_RECORD WHERE 1=1";
        $params = [];
        if ($factory_id) {
            $sql .= " AND factory_id = :factory_id";
            $params[':factory_id'] = $factory_id;
        }
        if ($from_date) {
            $sql .= " AND audit_date >= TO_DATE(:from_date, 'YYYY-MM-DD')";
            $params[':from_date'] = $from_date;
        }
        if ($to_date) {
            $sql .= " AND audit_date <= TO_DATE(:to_date, 'YYYY-MM-DD')";
            $params[':to_date'] = $to_date;
        }
        $sql .= " ORDER BY audit_date ASC";
        
        $stid = oci_parse($conn, $sql);
        foreach ($params as $key => $val) {
            oci_bind_by_name($stid, $key, $params[$key]);
        }
        oci_execute($stid);
        
        $data = [];
        while ($row = oci_fetch_assoc($stid)) {
            // Need to convert keys to lower case for json response standard
            $rowLower = [];
            foreach ($row as $k => $v) {
                $rowLower[strtolower($k)] = $v;
            }
            $data[] = $rowLower;
        }
        oci_free_statement($stid);
        break;

    case 'salary_summary':
        $sql = "SELECT f.factory_name, SUM(sr.net_salary) as total_net_salary
                FROM SALARY_RECORD sr
                JOIN WORKER w ON sr.worker_id = w.worker_id
                JOIN FACTORY f ON w.factory_id = f.factory_id
                WHERE 1=1";
        $params = [];
        if ($factory_id) {
            $sql .= " AND f.factory_id = :factory_id";
            $params[':factory_id'] = $factory_id;
        }
        if ($from_date) {
            $sql .= " AND sr.payment_date >= TO_DATE(:from_date, 'YYYY-MM-DD')";
            $params[':from_date'] = $from_date;
        }
        if ($to_date) {
            $sql .= " AND sr.payment_date <= TO_DATE(:to_date, 'YYYY-MM-DD')";
            $params[':to_date'] = $to_date;
        }
        $sql .= " GROUP BY f.factory_name ORDER BY total_net_salary DESC";
        
        $stid = oci_parse($conn, $sql);
        foreach ($params as $key => $val) {
            oci_bind_by_name($stid, $key, $params[$key]);
        }
        oci_execute($stid);
        
        $data = [];
        while ($row = oci_fetch_assoc($stid)) {
            $rowLower = [];
            foreach ($row as $k => $v) {
                $rowLower[strtolower($k)] = $v;
            }
            $data[] = $rowLower;
        }
        oci_free_statement($stid);
        break;

    case 'grievance_breakdown':
        $sql = "SELECT category, status, COUNT(*) as cnt
                FROM GRIEVANCE
                WHERE 1=1";
        $params = [];
        if ($factory_id) {
            $sql .= " AND factory_id = :factory_id";
            $params[':factory_id'] = $factory_id;
        }
        if ($from_date) {
            $sql .= " AND report_date >= TO_DATE(:from_date, 'YYYY-MM-DD')";
            $params[':from_date'] = $from_date;
        }
        if ($to_date) {
            $sql .= " AND report_date <= TO_DATE(:to_date, 'YYYY-MM-DD')";
            $params[':to_date'] = $to_date;
        }
        $sql .= " GROUP BY category, status";
        
        $stid = oci_parse($conn, $sql);
        foreach ($params as $key => $val) {
            oci_bind_by_name($stid, $key, $params[$key]);
        }
        oci_execute($stid);
        
        $data = [];
        while ($row = oci_fetch_assoc($stid)) {
            $rowLower = [];
            foreach ($row as $k => $v) {
                $rowLower[strtolower($k)] = $v;
            }
            $data[] = $rowLower;
        }
        oci_free_statement($stid);
        break;

    case 'district_ranking':
        $sql = "SELECT district, COUNT(*) AS cnt, ROUND(AVG(compliance_score),1) AS avg_score,
                SUM(CASE WHEN compliance_status='Compliant' THEN 1 ELSE 0 END) AS compliant,
                SUM(CASE WHEN compliance_status='Non-Compliant' THEN 1 ELSE 0 END) AS non_compliant
                FROM FACTORY GROUP BY district ORDER BY avg_score DESC";
        $stid = oci_parse($conn, $sql);
        oci_execute($stid);
        
        $data = [];
        while ($row = oci_fetch_assoc($stid)) {
            $rowLower = [];
            foreach ($row as $k => $v) {
                $rowLower[strtolower($k)] = $v;
            }
            $data[] = $rowLower;
        }
        oci_free_statement($stid);
        break;
        
    case 'factories_dropdown':
        // Helper to populate the factory dropdown filter
        $data = fetchRows($conn, "SELECT factory_id, factory_name FROM FACTORY ORDER BY factory_name");
        break;

    default:
        jsonResponse(['success' => false, 'message' => 'Invalid report type']);
}

jsonResponse(['success' => true, 'data' => $data]);
