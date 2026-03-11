<?php
/**
 * WHMCS Relworx Mobile Money Payment Callback File
 *
 *
 * It verifying that the payment gateway module is active,
 * validating an Invoice ID, checking for the existence of a Transaction ID,
 * Logging the Transaction for debugging and Adding Payment to an Invoice.
 *
 * For more information, please refer to the online documentation.
 *
 * @see https://developers.whmcs.com/payment-gateways/callbacks/
 *
 * @copyright Copyright (c) WHMCS Limited 2017
 * @license http://www.whmcs.com/license/ WHMCS Eula
 */

// Require libraries needed for gateway module functions.
require_once __DIR__ . '/../../../init.php';
require_once __DIR__ . '/../../../includes/gatewayfunctions.php';
require_once __DIR__ . '/../../../includes/invoicefunctions.php';

// Detect module name from filename.
$gatewayModuleName = basename(__FILE__, '.php');

// Fetch gateway configuration parameters.
$gatewayParams = getGatewayVariables($gatewayModuleName);

// Die if module is not active.
if (!$gatewayParams['type']) {
    die("Module Not Activated");
}

$json = file_get_contents('php://input');
$data = json_decode($json,true);

if(isset($data["customer_reference"]) && !empty($data["customer_reference"]))
{
    $invoiceArr = explode('_',$data["customer_reference"]);
}

$command = 'GetInvoice';
$postData = array(
    'invoiceid' => $invoiceArr[1],
);

$adminUsername = '';

$invoiceData = localAPI($command, $postData, $adminUsername);

// Retrieve data returned in payment gateway callback
// Varies per payment gateway
$status = $data["status"];
$invoiceId = $invoiceData['invoiceid'];
$transactionId = $data["customer_reference"];
$paymentAmount = $invoiceData['total'];
$paymentFee = $data['charge'];
//$hash = $_POST["x_hash"];

if($status == 'success')
{
	$transactionStatus = 'Success';
	$success = true;
}
else
{
	$transactionStatus = 'Failure';
	$success = false;
}

//$transactionStatus = $success ? 'Success' : 'Failure';

/**
 * Validate callback authenticity.
 *
 * Most payment gateways provide a method of verifying that a callback
 * originated from them. In the case of our example here, this is achieved by
 * way of a shared secret which is used to build and compare a hash.
 */
/*$secretKey = $gatewayParams['secretKey'];
if ($hash != md5($invoiceId . $transactionId . $paymentAmount . $secretKey)) {
    $transactionStatus = 'Hash Verification Failure';
    $success = false;
}*/

/**
 * Validate Callback Invoice ID.
 *
 * Checks invoice ID is a valid invoice number. Note it will count an
 * invoice in any status as valid.
 *
 * Performs a die upon encountering an invalid Invoice ID.
 *
 * Returns a normalised invoice ID.
 *
 * @param int $invoiceId Invoice ID
 * @param string $gatewayName Gateway Name
 */
$invoiceId = checkCbInvoiceID($invoiceId, $gatewayParams['name']);

/**
 * Check Callback Transaction ID.
 *
 * Performs a check for any existing transactions with the same given
 * transaction number.
 *
 * Performs a die upon encountering a duplicate.
 *
 * @param string $transactionId Unique Transaction ID
 */
checkCbTransID($transactionId);

/**
 * Log Transaction.
 *
 * Add an entry to the Gateway Log for debugging purposes.
 *
 * The debug data can be a string or an array. In the case of an
 * array it will be
 *
 * @param string $gatewayName        Display label
 * @param string|array $debugData    Data to log
 * @param string $transactionStatus  Status
 */
logTransaction($gatewayParams['name'], $data, $transactionStatus);

if ($success) {

    /**
     * Add Invoice Payment.
     *
     * Applies a payment transaction entry to the given invoice ID.
     *
     * @param int $invoiceId         Invoice ID
     * @param string $transactionId  Transaction ID
     * @param float $paymentAmount   Amount paid (defaults to full balance)
     * @param float $paymentFee      Payment fee (optional)
     * @param string $gatewayModule  Gateway module name
     */
    addInvoicePayment(
        $invoiceId,
        $transactionId,
        $paymentAmount,
        $paymentFee,
        $gatewayModuleName
    );
	
	/*$table = "tblinvoiceitems";
	$fields = "relid";
	$where = array("invoiceid"=>$invoiceId);
	$result = select_query($table,$fields,$where);
	while ($data = mysql_fetch_array($result)) 
	{
		$relid = $data['relid'];
		$InsertTable = "tblclients";
		$InsertValues = array("fieldid"=>"2","relid"=>$relid,"value"=>$transactionId);
		$newid = insert_query($Inserttable,$InsertValues);
	}*/

}