
<?php
require_once '../config/config.php';
require_once '../models/Remittance.php';
require_once '../helpers/session_helper.php';

// Check if user is logged in and has proper role
requireLogin();
requireAnyDepartment(['IT/E-Business', 'Accounts']);

header('Content-Type: application/json');

$remittanceModel = new Remittance();

// Get DataTables parameters
$draw = isset($_POST['draw']) ? intval($_POST['draw']) : 1;
$start = isset($_POST['start']) ? intval($_POST['start']) : 0;
$length = isset($_POST['length']) ? intval($_POST['length']) : 10;
$search = isset($_POST['search']['value']) ? $_POST['search']['value'] : '';
$order_column = isset($_POST['order'][0]['column']) ? intval($_POST['order'][0]['column']) : 0;
$order_dir = isset($_POST['order'][0]['dir']) ? $_POST['order'][0]['dir'] : 'DESC';

// Get paginated data
$remittances = $remittanceModel->getPaginatedRemittances($start, $length, $search, $order_column, $order_dir);

// Format data for DataTables
$data = [];
foreach ($remittances['data'] as $remittance) {
    $isPosted = $remittanceModel->isRemittanceFullyPosted($remittance['remit_id']);
    $status = $isPosted ? 
        '<span class="badge badge-success">Posted</span>' : 
        '<span class="badge badge-warning">Pending</span>';
    
    $actions = '<a href="view_remittance.php?id=' . $remittance['id'] . '" class="btn btn-sm btn-primary"><i class="fas fa-eye"></i></a> ' .
               '<a href="print_remittance.php?id=' . $remittance['id'] . '" class="btn btn-sm btn-info" target="_blank"><i class="fas fa-print"></i></a>';
    
    $data[] = [
        $remittance['remit_id'],
        formatDate($remittance['date']),
        formatCurrency($remittance['amount_paid']),
        $remittance['no_of_receipts'],
        $remittance['category'],
        $remittance['remitting_officer_name'],
        $remittance['posting_officer_name'],
        $status,
        $actions
    ];
}

echo json_encode([
    'draw' => $draw,
    'recordsTotal' => $remittances['total'],
    'recordsFiltered' => $remittances['total'],
    'data' => $data
]);
?>
