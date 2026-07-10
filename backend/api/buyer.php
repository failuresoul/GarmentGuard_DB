<?php
session_start();
require_once '../config/db.php';
require_once '../includes/helpers.php';
authCheck();

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'factories' || $action === 'certifications' || $action === 'audits') {
    if ($_SESSION['role'] !== 'buyer_user') {
        jsonResponse(['success' => false, 'message' => 'Unauthorized']);
    }

    $user_id = $_SESSION['user_id'];
    // Fetch buyer_id for the logged in user
    $buyer_id_result_array = fetchRows($conn, "SELECT b.buyer_id FROM BUYER b JOIN USER_ u ON b.email = u.email WHERE u.user_id = :user_id", [':user_id' => $user_id]);
    $buyer_id_result = !empty($buyer_id_result_array) ? $buyer_id_result_array[0] : null;

    if (!$buyer_id_result) {
        $buyer_id = $_SESSION['buyer_id'] ?? null;
    } else {
        $buyer_id = $buyer_id_result['BUYER_ID'];
    }

    if (!$buyer_id) {
        jsonResponse(['success' => false, 'message' => 'Buyer not found for this user']);
    }

    switch ($action) {
        case 'factories':
            $sql = "SELECT f.factory_id, f.factory_name, f.district, f.compliance_status, f.compliance_score,
                    fn_compliance_score(f.factory_id) AS calc_score,
                    (SELECT COUNT(*) FROM CERTIFICATION c WHERE c.factory_id=f.factory_id AND c.status='Active') AS cert_count,
                    (SELECT MAX(a.score) FROM AUDIT_RECORD a WHERE a.factory_id=f.factory_id) AS last_score,
                    (SELECT MAX(a.audit_date) FROM AUDIT_RECORD a WHERE a.factory_id=f.factory_id) AS last_audit
                    FROM FACTORY f JOIN BUYER_FACTORY bf ON f.factory_id=bf.factory_id
                    WHERE bf.contract_status='Active' AND bf.buyer_id = :buyer_id";
            $data = fetchRows($conn, $sql, [':buyer_id' => $buyer_id]);
            break;

        case 'certifications':
            $sql = "SELECT c.*, f.factory_name, fn_is_cert_valid(c.factory_id, c.cert_name) AS is_valid
                    FROM CERTIFICATION c JOIN FACTORY f ON c.factory_id=f.factory_id
                    JOIN BUYER_FACTORY bf ON f.factory_id=bf.factory_id 
                    WHERE bf.contract_status='Active' AND bf.buyer_id = :buyer_id";
            $data = fetchRows($conn, $sql, [':buyer_id' => $buyer_id]);
            break;

        case 'audits':
            $sql = "SELECT a.*, f.factory_name 
                    FROM AUDIT_RECORD a JOIN FACTORY f ON a.factory_id=f.factory_id
                    JOIN BUYER_FACTORY bf ON f.factory_id=bf.factory_id 
                    WHERE bf.contract_status='Active' AND bf.buyer_id = :buyer_id
                    ORDER BY a.audit_date DESC";
            $data = fetchRows($conn, $sql, [':buyer_id' => $buyer_id]);
            break;
    }
    jsonResponse(['success' => true, 'data' => $data]);

} else {
    // Default fallback to the old list query for admin/buyer.php
    $rows = fetchRows($conn,
      "SELECT b.buyer_id, b.buyer_name, b.country, b.contact_name,
       b.email, b.phone, b.brand_name,
       (SELECT COUNT(*) FROM BUYER_FACTORY bf
        WHERE bf.buyer_id = b.buyer_id AND bf.contract_status = 'Active') AS active_contracts
       FROM BUYER b ORDER BY b.buyer_id"
    );

    jsonResponse(['success' => true, 'data' => $rows]);
}
