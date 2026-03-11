<?php
/**
 * WHMCS Relworx Mobile Money Payment Gateway Module
 *
 * Payment Gateway modules allow you to integrate payment solutions with the
 * WHMCS platform.
 *
 * Within the module itself, all functions must be prefixed with the module
 * filename, followed by an underscore, and then the function name. For this
 * example file, the filename is "relworxm" and therefore all functions
 * begin "relworxm_".
 *
 * If your module or third party API does not support a given function, you
 * should not define that function within your module. Only the _config
 * function is required.
 *
 * For more information, please refer to the online documentation.
 *
 * @see https://developers.whmcs.com/payment-gateways/
 *
 * @copyright Copyright (c) WHMCS Limited 2017
 * @license http://www.whmcs.com/license/ WHMCS Eula
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

/**
 * Define module related meta data.
 *
 * Values returned here are used to determine module related capabilities and
 * settings.
 *
 * @see https://developers.whmcs.com/payment-gateways/meta-data-params/
 *
 * @return array
 */
function relworxm_MetaData()
{
    return array(
        'DisplayName' => 'Relworx Mobile Money',
        'APIVersion' => '1.1', // Use API Version 1.1
        'DisableLocalCreditCardInput' => true,
        'TokenisedStorage' => false,
    );
}

/**
 * Define gateway configuration options.
 *
 * The fields you define here determine the configuration options that are
 * presented to administrator users when activating and configuring your
 * payment gateway module for use.
 *
 * Supported field types include:
 * * text
 * * password
 * * yesno
 * * dropdown
 * * radio
 * * textarea
 *
 * Examples of each field type and their possible configuration parameters are
 * provided in the sample function below.
 *
 * @return array
 */
function relworxm_config()
{
    return array(
        // the friendly display name for a payment gateway should be
        // defined here for backwards compatibility
        'FriendlyName' => array(
            'Type' => 'System',
            'Value' => 'Relworx Mobile Money',
        ),
        // a text field type allows for single line text input
        'accountID' => array(
            'FriendlyName' => 'Account ID',
            'Type' => 'text',
            'Size' => '25',
            'Default' => '',
            'Description' => 'Enter your account ID here',
        ),
        // a password field type allows for masked text input
        'secretKey' => array(
            'FriendlyName' => 'Secret Key',
            'Type' => 'password',
            'Size' => '50',
            'Default' => '',
            'Description' => 'Enter secret key here',
        ),
    );
}

/**
 * Payment link.
 *
 * Required by third party payment gateway modules only.
 *
 * Defines the HTML output displayed on an invoice. Typically consists of an
 * HTML form that will take the user to the payment gateway endpoint.
 *
 * @param array $params Payment Gateway Module Parameters
 *
 * @see https://developers.whmcs.com/payment-gateways/third-party-gateway/
 *
 * @return string
 */


function relworxm_link($params)
{
    // Gateway Configuration Parameters
    $accountId = $params['accountID'];
    $secretKey = $params['secretKey'];
    $testMode = $params['testMode'];
    $currency = $params['currency'];

    // Invoice Parameters
    $invoiceId = $params['invoiceid'];
    $description = $params["description"];
    $amount = $params['amount'];
    $currencyCode = $params['currency'];

    // Client Parameters
    $firstname = $params['clientdetails']['firstname'];
    $lastname = $params['clientdetails']['lastname'];
    $email = $params['clientdetails']['email'];
    $address1 = $params['clientdetails']['address1'];
    $address2 = $params['clientdetails']['address2'];
    $city = $params['clientdetails']['city'];
    $state = $params['clientdetails']['state'];
    $postcode = $params['clientdetails']['postcode'];
    $country = $params['clientdetails']['country'];
    $phone = $params['clientdetails']['phonenumber'];
    $customerNo = '+'.$params['clientdetails']['phonecc'].$phone;

    // System Parameters
    $companyName = $params['companyname'];
    $systemUrl = $params['systemurl'];
    $returnUrl = $params['returnurl'];
    $langPayNow = $params['langpaynow'];
    $moduleDisplayName = $params['name'];
    $moduleName = $params['paymentmethod'];
    $whmcsVersion = $params['whmcsVersion'];

    $permitted_chars = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $strength = 10;

    $input_length = strlen($permitted_chars);
    $random_string = '';
    for($i = 0; $i < $strength; $i++) {
        $random_character = $permitted_chars[mt_rand(0, $input_length - 1)];
        $random_string .= $random_character;
    }

    $reference =  $random_string.time();
    
    $command = 'GetConfigurationValue';
    $postData = array(
        'setting' => 'SystemURL',
    );

    $adminUsername = '';
    $configData = localAPI($command, $postData, $adminUsername);

    $ajaxUrl = $configData['value'].'modules/gateways/callback/processrelworxm.php';

    $postfields = array();
    $postfields['account_no'] = $accountId;
    $postfields['secret_key'] = $secretKey;
    $postfields['reference'] = $reference.'_'.$invoiceId;
    $postfields['currency'] = $currency;
    $postfields['amount'] = $amount;
    $postfields['description'] = $description;
    $postfields['invoice_id'] = $invoiceId;
    $postfields['msisdn'] = $customerNo;
    $postfields['ajax_url'] = $ajaxUrl;
    $postfields['return_url'] = $returnUrl;

    $gatewayLogo = rtrim($systemUrl, '/') . '/modules/gateways/relworxm/logo.png';
    $safeDescription = htmlspecialchars($description, ENT_QUOTES, 'UTF-8');
    $safeAmount = htmlspecialchars($amount, ENT_QUOTES, 'UTF-8');
    $safeCurrencyCode = htmlspecialchars($currencyCode, ENT_QUOTES, 'UTF-8');
    $safeCustomerNo = htmlspecialchars($customerNo, ENT_QUOTES, 'UTF-8');
    $safeCompanyName = htmlspecialchars($companyName, ENT_QUOTES, 'UTF-8');
    $safeGatewayLogo = htmlspecialchars($gatewayLogo, ENT_QUOTES, 'UTF-8');

    $htmlOutput = '
    <style>
        .relworx-paycard {
            position: relative;
            overflow: hidden;
            max-width: 620px;
            padding: 28px;
            border: 1px solid rgba(16, 24, 40, 0.08);
            border-radius: 28px;
            background:
                radial-gradient(circle at top right, rgba(39, 174, 96, 0.2), transparent 32%),
                linear-gradient(145deg, #0d1f1a 0%, #123128 44%, #f7fbf9 44%, #f7fbf9 100%);
            box-shadow: 0 28px 70px rgba(15, 23, 42, 0.16);
            color: #0f172a;
            font-family: "Segoe UI", "Helvetica Neue", Arial, sans-serif;
        }

        .relworx-paycard::before {
            content: "";
            position: absolute;
            inset: auto -80px -110px auto;
            width: 240px;
            height: 240px;
            border-radius: 50%;
            background: rgba(39, 174, 96, 0.12);
        }

        .relworx-paycard__inner {
            position: relative;
            z-index: 1;
        }

        .relworx-paycard__top {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            margin-bottom: 24px;
        }

        .relworx-paycard__brand {
            display: flex;
            align-items: center;
            gap: 14px;
            color: #f8fafc;
        }

        .relworx-paycard__brand-copy {
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .relworx-paycard__logo {
            display: block;
            max-height: 32px;
            width: auto;
        }

        .relworx-paycard__eyebrow {
            margin: 0 0 4px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.18em;
            text-transform: uppercase;
            opacity: 0.72;
        }

        .relworx-paycard__title {
            margin: 0;
            font-size: 26px;
            line-height: 1.1;
            font-weight: 800;
            color: #ffffff;
        }

        .relworx-paycard__pill {
            padding: 8px 14px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.14);
            border: 1px solid rgba(255, 255, 255, 0.18);
            color: #ffffff;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .relworx-paycard__body {
            display: grid;
            grid-template-columns: minmax(0, 1.1fr) minmax(260px, 0.9fr);
            gap: 20px;
        }

        .relworx-paycard__panel,
        .relworx-paycard__summary {
            padding: 22px;
            border-radius: 22px;
            backdrop-filter: blur(12px);
        }

        .relworx-paycard__panel {
            background: rgba(255, 255, 255, 0.92);
            border: 1px solid rgba(15, 23, 42, 0.08);
        }

        .relworx-paycard__summary {
            background: rgba(10, 21, 18, 0.9);
            color: #e2f7ea;
            border: 1px solid rgba(39, 174, 96, 0.18);
        }

        .relworx-paycard__label {
            margin: 0 0 8px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.12em;
            text-transform: uppercase;
            color: #3f5d54;
        }

        .relworx-paycard__amount {
            margin: 0;
            font-size: 36px;
            line-height: 1;
            font-weight: 900;
            color: #08130f;
        }

        .relworx-paycard__description {
            margin: 14px 0 0;
            color: #334155;
            font-size: 14px;
            line-height: 1.6;
        }

        .relworx-paycard__meta {
            display: grid;
            gap: 12px;
            margin-top: 20px;
        }

        .relworx-paycard__meta-row {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            padding-bottom: 12px;
            border-bottom: 1px solid rgba(15, 23, 42, 0.08);
            font-size: 14px;
        }

        .relworx-paycard__meta-row:last-child {
            padding-bottom: 0;
            border-bottom: 0;
        }

        .relworx-paycard__meta-key {
            color: #64748b;
        }

        .relworx-paycard__meta-value {
            font-weight: 700;
            color: #0f172a;
            text-align: right;
        }

        .relworx-paycard__summary-title {
            margin: 0;
            font-size: 18px;
            font-weight: 800;
            color: #ffffff;
        }

        .relworx-paycard__summary-copy {
            margin: 12px 0 0;
            font-size: 14px;
            line-height: 1.6;
            color: rgba(226, 247, 234, 0.84);
        }

        .relworx-paycard__notice {
            margin-top: 18px;
            padding: 14px 16px;
            border-radius: 18px;
            background: rgba(39, 174, 96, 0.14);
            color: #f0fdf4;
            font-size: 13px;
            line-height: 1.6;
        }

        .relworx-paycard__status {
            display: none;
            margin-top: 18px;
            padding: 14px 16px;
            border-radius: 18px;
            font-size: 14px;
            font-weight: 600;
            line-height: 1.5;
        }

        .relworx-paycard__status.is-visible {
            display: block;
        }

        .relworx-paycard__status.is-success {
            background: #ecfdf3;
            color: #166534;
            border: 1px solid #bbf7d0;
        }

        .relworx-paycard__status.is-error {
            background: #fef2f2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }

        .relworx-paycard__action {
            margin-top: 22px;
        }

        .relworx-paycard__button {
            position: relative;
            width: 100%;
            padding: 16px 20px;
            border: 0;
            border-radius: 18px;
            background: linear-gradient(135deg, #22c55e 0%, #15803d 100%);
            box-shadow: 0 16px 30px rgba(21, 128, 61, 0.26);
            color: #ffffff;
            font-size: 15px;
            font-weight: 800;
            letter-spacing: 0.02em;
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease, opacity 0.2s ease;
        }

        .relworx-paycard__button:hover,
        .relworx-paycard__button:focus {
            transform: translateY(-1px);
            box-shadow: 0 20px 34px rgba(21, 128, 61, 0.32);
        }

        .relworx-paycard__button[disabled] {
            cursor: wait;
            opacity: 0.85;
            transform: none;
        }

        .relworx-paycard__button-label,
        .relworx-paycard__button-spinner {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .relworx-paycard__button-spinner {
            display: none;
        }

        .relworx-paycard__button.is-loading .relworx-paycard__button-label {
            display: none;
        }

        .relworx-paycard__button.is-loading .relworx-paycard__button-spinner {
            display: inline-flex;
        }

        .relworx-paycard__spinner-ring {
            width: 16px;
            height: 16px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-top-color: #ffffff;
            border-radius: 50%;
            animation: relworxSpin 0.8s linear infinite;
        }

        @keyframes relworxSpin {
            to {
                transform: rotate(360deg);
            }
        }

        @media (max-width: 640px) {
            .relworx-paycard {
                padding: 18px;
                border-radius: 24px;
            }

            .relworx-paycard__body {
                grid-template-columns: 1fr;
            }

            .relworx-paycard__title {
                font-size: 22px;
            }

            .relworx-paycard__amount {
                font-size: 30px;
            }
        }
    </style>
    <div class="relworx-paycard">
        <div class="relworx-paycard__inner">';
    foreach ($postfields as $k => $v) {
        $htmlOutput .= '<input type="hidden" id="' . $k . '" name="' . $k . '" value="' . htmlspecialchars($v, ENT_QUOTES, 'UTF-8') . '" />';
    }

    $htmlOutput .= '
            <div class="relworx-paycard__top">
                <div class="relworx-paycard__brand">
                    <img class="relworx-paycard__logo" src="' . $safeGatewayLogo . '" alt="Relworx logo" />
                    <div class="relworx-paycard__brand-copy">
                        <p class="relworx-paycard__eyebrow">' . $safeCompanyName . '</p>
                        <h3 class="relworx-paycard__title">Mobile Money Checkout</h3>
                    </div>
                </div>
                <div class="relworx-paycard__pill">Secure Collection</div>
            </div>
            <div class="relworx-paycard__body">
                <div class="relworx-paycard__panel">
                    <p class="relworx-paycard__label">Amount Due</p>
                    <p class="relworx-paycard__amount">' . $safeCurrencyCode . ' ' . $safeAmount . '</p>
                    <p class="relworx-paycard__description">' . $safeDescription . '</p>
                    <div class="relworx-paycard__meta">
                        <div class="relworx-paycard__meta-row">
                            <span class="relworx-paycard__meta-key">Invoice</span>
                            <span class="relworx-paycard__meta-value">#' . htmlspecialchars($invoiceId, ENT_QUOTES, 'UTF-8') . '</span>
                        </div>
                        <div class="relworx-paycard__meta-row">
                            <span class="relworx-paycard__meta-key">Customer</span>
                            <span class="relworx-paycard__meta-value">' . htmlspecialchars(trim($firstname . ' ' . $lastname), ENT_QUOTES, 'UTF-8') . '</span>
                        </div>
                        <div class="relworx-paycard__meta-row">
                            <span class="relworx-paycard__meta-key">Charge phone</span>
                            <span class="relworx-paycard__meta-value">' . $safeCustomerNo . '</span>
                        </div>
                    </div>
                </div>
                <div class="relworx-paycard__summary">
                    <h4 class="relworx-paycard__summary-title">How it works</h4>
                    <p class="relworx-paycard__summary-copy">Tap the button below and Relworx will send a mobile money push to your phone. Confirm the prompt on your device to complete payment for this invoice.</p>
                    <div class="relworx-paycard__notice">Use the number shown here: <strong>' . $safeCustomerNo . '</strong>. If it is wrong, update your WHMCS profile before continuing.</div>
                    <div id="relworx-status" class="relworx-paycard__status" aria-live="polite"></div>
                    <div class="relworx-paycard__action">
                        <button id="relworx-pay-button" type="button" class="relworx-paycard__button" onclick="relworxMobile();">
                            <span class="relworx-paycard__button-label">Pay with Mobile Money</span>
                            <span class="relworx-paycard__button-spinner">
                                <span class="relworx-paycard__spinner-ring" aria-hidden="true"></span>
                                Sending request...
                            </span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>';

    $htmlOutput .='<script type="text/javascript">
    function relworxSetStatus(type, message)
    {
        var statusBox = $("#relworx-status");
        statusBox.removeClass("is-success is-error").addClass("is-visible");
        if (type === "success") {
            statusBox.addClass("is-success");
        } else {
            statusBox.addClass("is-error");
        }
        statusBox.text(message);
    }

    function relworxMobile()
    {
        var payButton = $("#relworx-pay-button");
        var AccountNo = $("#account_no").val();
        var Secretkey = $("#secret_key").val();
        var Reference = $("#reference").val();
        var Currency = $("#currency").val();
        var Amount = $("#amount").val();
        var Description = $("#description").val();
        var InvoiceId = $("#invoice_id").val();
        var ContactNo = $("#msisdn").val();
        var AjaxUrl = decodeURIComponent($("#ajax_url").val());
        var ReturnUrl = $("#return_url").val();

        payButton.prop("disabled", true).addClass("is-loading");
        relworxSetStatus("success", "Sending payment prompt to " + ContactNo + ". Please wait.");

        $.ajax({
            url: AjaxUrl,
            type: "POST",
            dataType: "json",
            data: {AccountNo:AccountNo,Secretkey:Secretkey,Reference:Reference,Currency:Currency,Amount:Amount,Description:Description,InvoiceId:InvoiceId,ContactNo:ContactNo},
            success: function(response) {
                var data = JSON.parse(JSON.stringify(response));

                if(data["status"] == "success")
                {
                    relworxSetStatus("success", data["message"]);
                    relworxWatchPayment(AjaxUrl, InvoiceId, ReturnUrl);
                }
                else
                {
                    relworxSetStatus("error", data["message"]);
                }
            },
            error: function() {
                relworxSetStatus("error", "We could not start the mobile money request. Please try again.");
            },
            complete: function(){
                payButton.prop("disabled", false).removeClass("is-loading");
            }
        });
    }

    function relworxWatchPayment(ajaxUrl, invoiceId, returnUrl)
    {
        var attempts = 0;
        var maxAttempts = 24;
        var fallbackRedirectMs = 30000;

        window.setTimeout(function() {
            window.location.href = returnUrl;
        }, fallbackRedirectMs);

        var pollTimer = window.setInterval(function() {
            attempts++;

            $.ajax({
                url: ajaxUrl,
                type: "POST",
                dataType: "json",
                data: {
                    Action: "status",
                    InvoiceId: invoiceId
                },
                success: function(response) {
                    if (response.status === "success") {
                        window.clearInterval(pollTimer);
                        relworxSetStatus("success", "Payment received. Redirecting back to your invoice...");
                        window.location.href = returnUrl;
                    } else if (attempts >= maxAttempts) {
                        window.clearInterval(pollTimer);
                        window.location.href = returnUrl;
                    }
                },
                error: function() {
                    if (attempts >= maxAttempts) {
                        window.clearInterval(pollTimer);
                        window.location.href = returnUrl;
                    }
                }
            });
        }, 5000);
    }

</script>';

    return $htmlOutput;

}
