<?php
session_start();
require_once '../config/db.php';
require_once '../includes/helpers.php';
authCheck();
header('Content-Type: application/json');

// Total active factories
$r = fetchRows($conn, "SELECT COUNT(*) AS CNT FROM FACTORY");
$total_factories = (int)$r[0]['CNT'];

// Total active workers
$r = fetchRows($conn, "SELECT COUNT(*) AS CNT FROM WORKER WHERE status = 'Active'");
$total_workers = (int)$r[0]['CNT'];

// Total audits
$r = fetchRows($conn, "SELECT COUNT(*) AS CNT FROM AUDIT_RECORD");
$total_audits = (int)$r[0]['CNT'];

// Open grievances
$r = fetchRows($conn, "SELECT COUNT(*) AS CNT FROM GRIEVANCE WHERE status = 'Open'");
$open_grievances = (int)$r[0]['CNT'];

// Unacknowledged safety alerts
$r = fetchRows($conn, "SELECT COUNT(*) AS CNT FROM SAFETY_ALERT WHERE is_acknowledged = 'N'");
$unack_alerts = (int)$r[0]['CNT'];

// Compliance breakdown
$compliance_breakdown = fetchRows($conn,
  "SELECT compliance_status AS STATUS, COUNT(*) AS CNT FROM FACTORY GROUP BY compliance_status ORDER BY CNT DESC"
);

// Recent 5 audits
$recent_audits = fetchRows($conn,
  "SELECT * FROM (
   SELECT ar.audit_id, f.factory_name, TO_CHAR(ar.audit_date,'DD-Mon-YYYY') AS audit_date,
   ar.score, ar.result
   FROM AUDIT_RECORD ar JOIN FACTORY f ON ar.factory_id = f.factory_id
   ORDER BY ar.audit_date DESC
  ) WHERE ROWNUM <= 5"
);

// Top factories by score
$top_factories = fetchRows($conn,
  "SELECT * FROM (
   SELECT factory_name, compliance_score, compliance_status
   FROM FACTORY ORDER BY compliance_score DESC
  ) WHERE ROWNUM <= 5"
);

jsonResponse(['success' => true, 'data' => [
  'total_factories'     => $total_factories,
  'total_workers'       => $total_workers,
  'total_audits'        => $total_audits,
  'open_grievances'     => $open_grievances,
  'unack_alerts'        => $unack_alerts,
  'compliance_breakdown'=> $compliance_breakdown,
  'recent_audits'       => $recent_audits,
  'top_factories'       => $top_factories,
]]);
