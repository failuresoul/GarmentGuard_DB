<?php
$conn = oci_connect('guar', '2022', 'localhost/XE');
if (!$conn) {
  $e = oci_error();
  http_response_code(500);
  echo json_encode(['success' => false, 'message' => $e['message']]);
  exit;
}
