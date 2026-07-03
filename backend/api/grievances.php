<?php
session_start();
require_once '../config/db.php';
require_once '../includes/helpers.php';
authCheck();
header('Content-Type: application/json');

$rows = fetchRows($conn,
  "SELECT g.grievance_id, w.full_name AS worker_name, f.factory_name,
   g.category, g.description,
   TO_CHAR(g.submitted_date,'DD-Mon-YYYY') AS submitted_date,
   g.status,
   TO_CHAR(g.resolved_date,'DD-Mon-YYYY') AS resolved_date,
   g.resolution_notes
   FROM GRIEVANCE g
   JOIN WORKER w ON g.worker_id = w.worker_id
   JOIN FACTORY f ON w.factory_id = f.factory_id
   ORDER BY g.submitted_date DESC"
);

jsonResponse(['success' => true, 'data' => $rows]);
