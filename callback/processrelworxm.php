<?php

if (isset($_POST['Action']) && $_POST['Action'] === 'status') {
    require_once __DIR__ . '/../../whmcs-caystard/init.php';

    $invoiceId = isset($_POST['InvoiceId']) ? (int) $_POST['InvoiceId'] : 0;

    if ($invoiceId <= 0) {
        echo json_encode(array(
            'status' => 'false',
            'message' => 'Invalid invoice reference.'
        ));
        die;
    }

    $invoiceStatus = \WHMCS\Database\Capsule::table('tblinvoices')
        ->where('id', $invoiceId)
        ->value('status');

    if (is_string($invoiceStatus) && strtolower($invoiceStatus) === 'paid') {
        echo json_encode(array(
            'status' => 'success',
            'message' => 'Payment confirmed.'
        ));
        die;
    }

    echo json_encode(array(
        'status' => 'pending',
        'message' => 'Payment is still pending confirmation.'
    ));
    die;
}

if(isset($_POST) && !empty($_POST))
{
    $postAPIData = array(
        'account_no' => $_POST['AccountNo'],
        'reference' => $_POST['Reference'],
        'msisdn' => $_POST['ContactNo'],
        'currency' => $_POST['Currency'],
        'amount' => $_POST['Amount'],
        'description' => $_POST['Description']
    );

    $secretKey = $_POST['Secretkey'];

    // API URL
    $APIURL = 'https://payments.relworx.com/api/mobile-money/request-payment';

    // Create a new cURL resource
    $ch = curl_init($APIURL);

    // Setup request to send json via POST
    $payload = json_encode($postAPIData);

    // Attach encoded JSON string to the POST fields
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

    // Set the content type to application/json
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json',"Authorization: Bearer $secretKey"));

    // Return response instead of outputting
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Execute the POST request
    $resultData = curl_exec($ch);
    $curlError = curl_error($ch);

    // Close cURL resource
    curl_close($ch);

    $result = json_decode($resultData, true);

    if ($resultData === false || !empty($curlError)) {
        $data = array(
            'status' => 'false',
            'message' => 'Relworx request failed. ' . $curlError
        );

        echo json_encode($data);
        die;
    }

    if(isset($result['success']) && $result['success'] == 1)
    {
        
        $sessionId = $_COOKIE['WHMCSy551iLvnhYt7'];

        header("Set-Cookie: WHMCSy551iLvnhYt7=$sessionId; SameSite=None; Secure");
        
        $data = array(
            'status' => 'success',
            'message' => $result['message']
        );

        echo json_encode($data);
        die;
    }
    else
    {
        $data = array(
            'status' => 'false',
            'message' => $result['message']
        );

        echo json_encode($data);
        die;
    }
}

?>
