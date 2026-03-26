<?php

header('Content-Type: application/json');

$depositId = null;
if (!empty($_GET['depositId'])) {
    $depositId = $_GET['depositId'];
} elseif (!empty($_GET['token'])) {
    $depositId = $_GET['token'];
}

if ($depositId !== null) {
    require_once 'classes/PawaPay.php';
    $pawapay = new PawaPay();
    $verify = $pawapay->verifyTransaction($depositId);
    $status = 'pending';

    if ($verify['tran_status'] === 'success') {
        $status = 'success';
    } elseif ($verify['tran_status'] === 'rejected') {
        $status = 'rejected';
    }

    echo json_encode(['status' => $status]);
    exit;
}

http_response_code(400);
echo json_encode(['status' => 'error', 'message' => 'Missing depositId or token']);

?>
