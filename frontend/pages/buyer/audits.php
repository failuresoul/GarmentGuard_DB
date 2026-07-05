<?php
require_once '../../../backend/includes/auth_check.php';
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "buyer_user") { header("Location: /frontend/index.html"); exit; }
$activePage = 'audits';
require_once '../shared/audits.php';
?>
