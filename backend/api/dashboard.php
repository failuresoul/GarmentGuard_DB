<?php
session_start();
require_once '../config/db.php';
require_once '../includes/helpers.php';

authCheck();
header('Content-Type: application/json');

$result = [];

// 1. Total Factories
$stmt = oci_parse($conn, "SELECT COUNT(*) AS CNT FROM FACTORY");
oci_execute($stmt);
$row = oci_fetch_assoc($stmt);
$result['totalFactories'] = intval($row['CNT']);

// 2. Compliant Factories
$stmt = oci_parse($conn, "SELECT COUNT(*) AS CNT FROM FACTORY WHERE compliance_status = 'Compliant'");
oci_execute($stmt);
$row = oci_fetch_assoc($stmt);
$result['compliantCount'] = intval($row['CNT']);

// 3. At Risk Factories
$stmt = oci_parse($conn, "SELECT COUNT(*) AS CNT FROM FACTORY WHERE compliance_status = 'At Risk'");
oci_execute($stmt);
$row = oci_fetch_assoc($stmt);
$result['atRiskCount'] = intval($row['CNT']);

// 4. Non-Compliant Factories
$stmt = oci_parse($conn, "SELECT COUNT(*) AS CNT FROM FACTORY WHERE compliance_status = 'Non-Compliant'");
oci_execute($stmt);
$row = oci_fetch_assoc($stmt);
$result['nonCompliantCount'] = intval($row['CNT']);

// 5. Open Grievances
$stmt = oci_parse($conn, "SELECT COUNT(*) AS CNT FROM GRIEVANCE WHERE status = 'Open'");
oci_execute($stmt);
$row = oci_fetch_assoc($stmt);
$result['openGrievances'] = intval($row['CNT']);

// 6. Equipment Expiring Soon (30 days)
$stmt = oci_parse($conn, "SELECT COUNT(*) AS CNT FROM SAFETY_EQUIPMENT WHERE expiry_date <= SYSDATE + 30 AND expiry_date >= SYSDATE");
oci_execute($stmt);
$row = oci_fetch_assoc($stmt);
$result['equipmentExpiring'] = intval($row['CNT']);

// 7. Compliance Distribution
$result['complianceDistribution'] = fetchRows($conn,
  "SELECT compliance_status AS STATUS, COUNT(*) AS CNT FROM FACTORY GROUP BY compliance_status");

// 8. Audit Score Trend (last 6 audits, ordered by audit_date chronologically)
$result['auditTrend'] = fetchRows($conn,
  "SELECT AUDIT_DATE, SCORE, FACTORY_NAME FROM (
     SELECT TO_CHAR(a.audit_date,'DD-Mon-YY') AS AUDIT_DATE, a.score, f.factory_name, a.audit_date AS raw_date
     FROM AUDIT_RECORD a JOIN FACTORY f ON a.factory_id=f.factory_id
     WHERE a.score IS NOT NULL ORDER BY a.audit_date DESC
   ) WHERE ROWNUM <= 6 ORDER BY raw_date ASC");

// 9. Top 5 Factories (sorted by compliance_score DESC)
$result['topFactories'] = fetchRows($conn,
  "SELECT * FROM (
     SELECT factory_id, factory_name, district, compliance_score, compliance_status 
     FROM FACTORY ORDER BY compliance_score DESC
   ) WHERE ROWNUM <= 5");

// 10. Recent Activity Feed
$result['recentActivity'] = fetchRows($conn,
  "SELECT type, description, event_date FROM (
     SELECT type, description, event_date, raw_date FROM (
       SELECT 'grievance' AS type, 
              'Grievance #'||log.grievance_id||' status changed from '||NVL(log.old_status, 'None')||' to '||log.new_status AS description,
              TO_CHAR(log.changed_at,'DD-Mon-YY') AS event_date,
              log.changed_at AS raw_date
       FROM (
         SELECT * FROM GRIEVANCE_AUDIT_LOG ORDER BY changed_at DESC
       ) log
       WHERE ROWNUM <= 5
     )
     UNION ALL
     SELECT type, description, event_date, raw_date FROM (
       SELECT 'audit' AS type,
              'Audit scheduled for '||f.factory_name||' on '||TO_CHAR(a.audit_date,'DD-Mon-YYYY') AS description,
              TO_CHAR(a.audit_date,'DD-Mon-YY') AS event_date,
              a.audit_date AS raw_date
       FROM (
         SELECT * FROM AUDIT_RECORD ORDER BY audit_date DESC
       ) a
       JOIN FACTORY f ON a.factory_id=f.factory_id
       WHERE a.score IS NULL AND ROWNUM <= 3
     )
     ORDER BY raw_date DESC
   ) WHERE ROWNUM <= 8");

jsonResponse(['success' => true, 'data' => $result]);
