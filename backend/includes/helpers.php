<?php
function jsonResponse($data, $code = 200) {
  http_response_code($code);
  header('Content-Type: application/json');
  echo json_encode($data);
  exit;
}

function getNextId($conn, $table, $id_col) {
  $sql = "SELECT NVL(MAX($id_col), 0) + 1 AS next_id FROM $table";
  $stmt = oci_parse($conn, $sql);
  oci_execute($stmt);
  $row = oci_fetch_assoc($stmt);
  return $row['NEXT_ID'];
}

function authCheck($allowed_roles = []) {
  if (!isset($_SESSION['user_id'])) {
    jsonResponse(['success' => false, 'message' => 'Unauthorized'], 401);
  }
  if (!empty($allowed_roles) && !in_array($_SESSION['role'], $allowed_roles)) {
    jsonResponse(['success' => false, 'message' => 'Forbidden'], 403);
  }
}

function fetchRows($conn, $sql, $binds = []) {
  $stmt = oci_parse($conn, $sql);
  foreach ($binds as $key => $val) {
    oci_bind_by_name($stmt, $key, $binds[$key]);
  }
  oci_execute($stmt);
  $rows = [];
  while ($row = oci_fetch_assoc($stmt)) {
    
    foreach ($row as $k => $v) {
      if (is_object($v) && method_exists($v, 'load')) {
        $row[$k] = $v->load();
      }
    }
    $rows[] = $row;
  }
  return $rows;
}

