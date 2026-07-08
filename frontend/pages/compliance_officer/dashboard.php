<?php
require_once '../../../backend/includes/auth_check.php';
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "compliance_officer") { header("Location: /frontend/index.html"); exit; }
$activePage = 'dashboard';
require_once '../shared/dashboard.php';
