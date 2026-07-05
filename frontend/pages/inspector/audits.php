<?php
require_once '../../../backend/includes/auth_check.php';
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "inspector") { header("Location: /frontend/index.html"); exit; }
$activePage = 'audits';
require_once '../shared/audits.php';
?>
