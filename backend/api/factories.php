<?php
session_start();
require_once '../config/db.php';
require_once '../includes/helpers.php';
authCheck();
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
  if (isset($_GET['id'])) {
    // Fetch detail of a single factory
    $id = intval($_GET['id']);
    $sql = "SELECT f.factory_id, f.factory_name, f.registration_no, f.address, f.district, 
            f.division, f.total_workers, f.compliance_status, f.compliance_score,
            TO_CHAR(f.last_audit_date,'DD-Mon-YYYY') AS last_audit_date,
            TO_CHAR(f.next_audit_date,'DD-Mon-YYYY') AS next_audit_date,
            f.contact_person, f.phone, f.email,
            fn_compliance_score(f.factory_id) AS calculated_score,
            fn_equipment_alert(f.factory_id) AS equipment_alerts,
            (SELECT COUNT(*) FROM WORKER w WHERE w.factory_id = f.factory_id AND w.status = 'Active') AS active_workers_count,
            (SELECT COUNT(*) FROM GRIEVANCE g JOIN WORKER w ON g.worker_id = w.worker_id WHERE w.factory_id = f.factory_id AND g.status = 'Open') AS open_grievances,
            (SELECT COUNT(*) FROM CERTIFICATION WHERE factory_id = f.factory_id AND status = 'Active' AND expiry_date >= SYSDATE) AS active_certs,
            (SELECT COUNT(*) FROM SAFETY_EQUIPMENT WHERE factory_id = f.factory_id AND expiry_date BETWEEN SYSDATE AND SYSDATE + 30) AS equipment_alerts_count
            FROM FACTORY f WHERE f.factory_id = :id";
    $rows = fetchRows($conn, $sql, [':id' => $id]);
    if (empty($rows)) {
      jsonResponse(['success' => false, 'message' => 'Factory not found'], 404);
    }
    jsonResponse(['success' => true, 'data' => $rows[0]]);
  } else {
    // Fetch all factories
    $sql = "SELECT f.factory_id, f.factory_name, f.registration_no, f.address, f.district, 
            f.division, f.total_workers, f.compliance_status, f.compliance_score,
            TO_CHAR(f.last_audit_date,'DD-Mon-YYYY') AS last_audit_date,
            f.contact_person, f.phone, f.email,
            fn_compliance_score(f.factory_id) AS calculated_score
            FROM FACTORY f ORDER BY f.factory_name";
    $rows = fetchRows($conn, $sql);
    jsonResponse(['success' => true, 'data' => $rows]);
  }
} elseif ($method === 'POST') {
  // Roles that can add factory: admin, compliance_officer
  authCheck(['admin', 'compliance_officer']);
  
  $data = json_decode(file_get_contents('php://input'), true);
  if (!$data) {
    jsonResponse(['success' => false, 'message' => 'Invalid JSON input'], 400);
  }
  
  $name = $data['factory_name'] ?? '';
  $reg_no = $data['registration_no'] ?? '';
  $address = $data['address'] ?? '';
  $district = $data['district'] ?? '';
  $division = $data['division'] ?? '';
  $workers = intval($data['total_workers'] ?? 0);
  $contact = $data['contact_person'] ?? '';
  $phone = $data['phone'] ?? '';
  $email = $data['email'] ?? '';
  
  if (empty($name) || empty($reg_no) || empty($address) || empty($district) || empty($division) || empty($contact) || empty($email)) {
    jsonResponse(['success' => false, 'message' => 'Please fill in all required fields'], 400);
  }
  
  $next_id = getNextId($conn, 'FACTORY', 'factory_id');
  
  $sql = "BEGIN sp_register_factory(:id, :name, :reg, :addr, :dist, :div, :workers, :contact, :phone, :email); END;";
  $stmt = oci_parse($conn, $sql);
  
  oci_bind_by_name($stmt, ':id', $next_id);
  oci_bind_by_name($stmt, ':name', $name);
  oci_bind_by_name($stmt, ':reg', $reg_no);
  oci_bind_by_name($stmt, ':addr', $address);
  oci_bind_by_name($stmt, ':dist', $district);
  oci_bind_by_name($stmt, ':div', $division);
  oci_bind_by_name($stmt, ':workers', $workers);
  oci_bind_by_name($stmt, ':contact', $contact);
  oci_bind_by_name($stmt, ':phone', $phone);
  oci_bind_by_name($stmt, ':email', $email);
  
  $res = oci_execute($stmt);
  if (!$res) {
    $e = oci_error($stmt);
    jsonResponse(['success' => false, 'message' => 'Database error: ' . $e['message']], 500);
  }
  
  jsonResponse(['success' => true, 'message' => 'Factory registered successfully']);
} else {
  jsonResponse(['success' => false, 'message' => 'Method not allowed'], 405);
}
