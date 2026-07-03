<?php
session_start();
require_once '../config/db.php';
require_once '../includes/helpers.php';
authCheck();
header('Content-Type: application/json');


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
