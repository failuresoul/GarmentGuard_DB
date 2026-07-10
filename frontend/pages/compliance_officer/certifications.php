<?php
require_once '../../../backend/includes/auth_check.php';
if (!isset($_SESSION["role"]) || $_SESSION["role"] !== "compliance_officer") { header("Location: /frontend/index.html"); exit; }
$activePage = 'certifications';
require_once '../shared/certifications.php';
