<?php
session_start();
require_once '../config/db.php';
require_once '../includes/helpers.php';

authCheck();
header('Content-Type: application/json');

$result = [];


$stmt = oci_parse($conn, "SELECT COUNT(*) AS CNT FROM FACTORY");
oci_execute($stmt);
$row = oci_fetch_assoc($stmt);
$result['totalFactories'] = $row['CNT'];


$stmt = oci_parse($conn, "SELECT COUNT(*) AS CNT FROM FACTORY WHERE compliance_status = 'Compliant'");
oci_execute($stmt);
$row = oci_fetch_assoc($stmt);
$result['compliantCount'] = $row['CNT'];


$stmt = oci_parse($conn, "SELECT COUNT(*) AS CNT FROM FACTORY WHERE compliance_status = 'At Risk'");
oci_execute($stmt);
$row = oci_fetch_assoc($stmt);
$result['atRiskCount'] = $row['CNT'];


$stmt = oci_parse($conn, "SELECT COUNT(*) AS CNT FROM FACTORY WHERE compliance_status = 'Non-Compliant'");
oci_execute($stmt);
$row = oci_fetch_assoc($stmt);
$result['nonCompliantCount'] = $row['CNT'];


$stmt = oci_parse($conn, "SELECT COUNT(*) AS CNT FROM GRIEVANCE WHERE status = 'Open'");
oci_execute($stmt);
$row = oci_fetch_assoc($stmt);
$result['openGrievances'] = $row['CNT'];


$stmt = oci_parse($conn, "SELECT COUNT(*) AS CNT FROM SAFETY_EQUIPMENT WHERE expiry_date BETWEEN SYSDATE AND SYSDATE+30");
oci_execute($stmt);
$row = oci_fetch_assoc($stmt);
$result['equipmentExpiring'] = $row['CNT'];


$result['topFactories'] = fetchRows($conn,
  "SELECT * FROM (
     SELECT factory_id, factory_name, district, compliance_score, compliance_status 
     FROM FACTORY ORDER BY compliance_score DESC
   ) WHERE ROWNUM <= 5");


$result['auditTrend'] = fetchRows($conn,
  "SELECT * FROM (
     SELECT TO_CHAR(a.audit_date,'DD-Mon-YY') AS AUDIT_DATE, a.score, f.factory_name
     FROM AUDIT_RECORD a JOIN FACTORY f ON a.factory_id=f.factory_id
     WHERE a.score IS NOT NULL ORDER BY a.audit_date DESC
   ) WHERE ROWNUM <= 6");


$result['complianceDistribution'] = fetchRows($conn,
  "SELECT compliance_status AS STATUS, COUNT(*) AS CNT FROM FACTORY GROUP BY compliance_status");


$result['recentActivity'] = fetchRows($conn,
  "SELECT type, description, event_date FROM (
     SELECT 'grievance' AS type, 
            'Grievance #'||g.grievance_id||' moved to '||g.status AS description,
            TO_CHAR(NVL(g.resolved_date, g.submitted_date),'DD-Mon-YY') AS event_date,
            NVL(g.resolved_date, g.submitted_date) AS raw_date
     FROM GRIEVANCE g WHERE ROWNUM <= 4
     UNION ALL
     SELECT 'audit' AS type,
            'Audit for '||f.factory_name||' scored '||a.score AS description,
            TO_CHAR(a.audit_date,'DD-Mon-YY') AS event_date,
            a.audit_date AS raw_date
     FROM AUDIT_RECORD a JOIN FACTORY f ON a.factory_id=f.factory_id
     WHERE a.score IS NOT NULL AND ROWNUM <= 4
     ORDER BY raw_date DESC
   ) WHERE ROWNUM <= 8");

echo json_encode($result);
