<?php

$message = 'Unable to verify transaction.';

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

    $message = 'Your transaction is still pending confirmation.';
    if ($verify['tran_status'] === 'success') {
        $message = 'Transaction successful!';
    } elseif ($verify['tran_status'] === 'rejected') {
        $message = 'Your transaction was not successful.';
    }
}

echo '<h1>' . htmlspecialchars($message) . '</h1>';
echo '<p><a href="/frontend/index.html">Back to site</a></p>';

?>
