<?php
require_once '../../../backend/includes/auth_check.php';
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "inspector") { header("Location: /frontend/index.html"); exit; }
$activePage = 'factories';
require_once '../shared/factory_detail.php';
?>
