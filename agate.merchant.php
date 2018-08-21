<?php

// Check version requirement dependencies
if (false !== agate_requirements_check()) {
    throw new \Exception('Your server does not meet the minimum requirements to use the agate payment plugin. The requirements check returned this error message: ' . agate_requirements_check());
}

// Load upgrade file
require_once ABSPATH.'wp-admin/includes/upgrade.php';

// Load Javascript from agate.js and jquery
function agate_js_init()
{
    wp_register_script('jquery', "//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js");
    wp_register_script('jquery-ui', "//code.jquery.com/ui/1.11.1/jquery-ui.js");
    wp_register_style('jquery-ui-css', "//code.jquery.com/ui/1.11.1/themes/smoothness/jquery-ui.css");
    wp_enqueue_script('jquery');
    wp_enqueue_script('jquery-ui');
    wp_enqueue_style('jquery-ui-css');
}

add_action('admin_enqueue_scripts', 'agate_js_init');

$nzshpcrt_gateways[$num] = array(
        'name'                                    => __('Payments by Agate', 'wpsc'),
        'api_version'                             => 1.0,
        'image'                                   => WPSC_URL.'/wpsc-merchants/agate/assets/img/logo.png',
        'has_recurring_billing'                   => false,
        'wp_admin_cannot_cancel'                  => true,
        'display_name'                            => __('Pay with Agate', 'wpsc'),
        'user_defined_name[wpsc_merchant_agate]' => 'Pay with Agate',
        'requirements'                            => array('php_version' => 5.4),
        'internalname'                            => 'wpsc_merchant_agate',
        'form'                                    => 'form_agate',
        'submit_function'                         => 'submit_agate',
        'function'                                => 'gateway_agate',
        );

function debug_log($contents)
{
    if (true === isset($contents)) {
        if (true === is_resource($contents)) {
            error_log(serialize($contents));
        } else {
            error_log(var_export($contents, true));
        }
    }
}

function form_agate()
{
    // Access to Wordpress Database
    global $wpdb;

    $output = NULL;

    try {
        if (get_option('agate_error') != null) {
            $output = '<div style="color:#A94442;background-color:#F2DEDE;background-color:#EBCCD1;text-align:center;padding:15px;border:1px solid transparent;border-radius:4px">'.get_option('agate_error').'</div>';
            update_option('agate_error', null);
        }

        // Get Current user's ids
        $user_id = get_current_user_id();

        // Load table storing the tokens
        $table_name = $wpdb->prefix.'agate_keys';
        
        $rows = array();

        $api_key = get_option("api_key");
        $rows[] = array(
            'API KEY',
            '<input name="api_key" type="text" value="'.$api_key.'" placeholder="Enter your api_key"/>',
            '<p class="description">API key is a unique string provided by Agate to the merchant.</p>',
        );

        $agate_redirect = get_option("agate_redirect");
        // Allows the merchant to specify a URL to redirect to upon the customer completing payment on the agate.io
        // invoice page. This is typcially the "Transaction Results" page.
        $rows[] = array(
                        'Redirect URL',
                        '<input name="agate_redirect" type="text" value="'.$agate_redirect.'" />',
                        '<p class="description"><strong>Important!</strong> Put the URL that you want the buyer to be redirected to after payment. This is usually a "Thanks for your order!" page.</p>',
                       );

        $output .= '<tr>' .
            '<td colspan="2">' .
                '<p class="description">' .
                    '<img src="' . WPSC_URL . '/wpsc-merchants/agate/assets/img/logo.png" /><br/>'.
                    'Have more questions? Need assistance? Please visit our website <a href="https://agate.sevices" target="_blank">https://agate.services</a> or send an email to <a href="mailto:support@agate.services" target="_blank">support@agate.services</a> for prompt attention. Thank you for choosing agate!</strong>' .
                '</p>' .
            '</td>' .
        '</tr>';

        foreach ($rows as $r) {
            $output .= '<tr> <td>' . $r[0] . '</td> <td>' . $r[1];

            if (true === isset($r[2])) {
                $output .= $r[2];
            }

            $output .= '</td></tr>';
        }

        return $output;

    } catch (\Exception $e) {
        error_log('[Error] In agate plugin, form_agate() function on line ' . $e->getLine() . ', with the error "' . $e->getMessage() . '" .');
        throw $e;
    }
}

function submit_agate()
{
    global $wpdb;

    try {
        if (true  === isset($_POST['submit'])              &&
            false !== stristr($_POST['submit'], 'Update'))
        {
            $params = array(
                            'api_key',
                            'agate_redirect'
                           );

            foreach ($params as $p) {
                if($_POST[$p]){
                  if ($_POST[$p] != null) {
                    update_option($p, $_POST[$p]);
                    debug_log($_POST[$p]);
                  }
                  else {
                    add_settings_error($p, 'error', __('The setting '. $p.' cannot be blank! Please enter a value for this field', 'wpse'), 'error');
                }
              }
              else{
                update_option($p, NULL);
                debug_log(get_option($p));
              }
            }
        }

        return true;

    } catch (\Exception $e) {
        error_log('[Error] In agate plugin, form_agate() function on line ' . $e->getLine() . ', with the error "' . $e->getMessage() . '" .');
        throw $e;
    }
}

// Convert the currency to iUSD
function convertCurToIUSD($url, $amount, $api_key, $currencySymbol) {
    debug_log("Entered into Convert amount");
    debug_log($url.'?api_key='.$api_key.'&currency='.$currencySymbol.'&amount='. $amount);
    $ch = curl_init($url.'?api_key='.$api_key.'&currency='.$currencySymbol.'&amount='. $amount);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
      'Content-Type: application/json')
  );

  $result = curl_exec($ch);
  $data = json_decode( $result , true);
  debug_log('Response =>'. var_export($data, TRUE));
  // Return the equivalent iUSD value acquired from Agate server.
  return (float) $data["result"];

  }

  function redirectPayment($baseUri, $amount_iUSD, $amount, $api_key, $currencySymbol) {
    error_log("Entered into Redirect Payment");
    // Using Auto-submit form to redirect
    echo "<form id='form' method='post' action='". $baseUri . "?api_key=" . $api_key . "'>".
            "<input type='hidden' autocomplete='off' name='amount' value='".$amount."'/>".
            "<input type='hidden' autocomplete='off' name='amount_iUSD' value='".$amount_iUSD."'/>".
            "<input type='hidden' autocomplete='off' name='callBackUrl' value='".get_option("agate_redirect")."'/>".
            "<input type='hidden' autocomplete='off' name='api_key' value='".$api_key."'/>".
            "<input type='hidden' autocomplete='off' name='cur' value='".$currencySymbol."'/>".
           "</form>".
           "<script type='text/javascript'>".
                "document.getElementById('form').submit();".
           "</script>";

  }


function gateway_agate($seperator, $sessionid)
{
    global $wpdb;
    global $wpsc_cart;

    try {
        // This grabs the purchase log id from
        // the database that refers to the $sessionid
        $purchase_log = $wpdb->get_row(
                                       "SELECT * FROM `" . WPSC_TABLE_PURCHASE_LOGS .
                                       "` WHERE `sessionid`= " . $sessionid . " LIMIT 1",
                                       ARRAY_A
                                       );

        // price
        $price = number_format($wpsc_cart->total_price, 2, '.', '');

        // Configure the rest of the invoice
        $purchase_log = $wpdb->get_row("SELECT * FROM `" .WPSC_TABLE_PURCHASE_LOGS. "` WHERE `sessionid`= " . $sessionid. " LIMIT 1", ARRAY_A);

        if (true === is_null(get_option('agate_redirect'))) {
            update_option('agate_redirect', get_site_url());
        }

        $baseUri      = "http://gateway.agate.services/" ;
        $convertUrl   = "http://gateway.agate.services/convert/";
        $api_key      = get_option('api_key'); // API Key
        $order_total  = $price;  // Total price
        // get currency symbol
        $currency_id    = get_option('currency_type');
        $sql            = "SELECT * FROM `" . WPSC_TABLE_CURRENCY_LIST . "` WHERE `id`=" . $currency_id;
        $currencyData   = $wpdb->get_results($sql, ARRAY_A);
        $currencySymbol = $currencyData[0]['code'];

        debug_log("Currency => " . $currency_id );

        $amount_iUSD = convertCurToIUSD($convertUrl, $order_total, $api_key, $currencySymbol);

        redirectPayment($baseUri, $amount_iUSD, $order_total, $api_key, $currencySymbol);

    } catch (\Exception $e) {
        error_log('[Error] In agate plugin, form_agate() function on line ' . $e->getLine() . ', with the error "' . $e->getMessage() . '" .');
        throw $e;
    }
}


function agate_requirements_check()
{
    global $wp_version;

    $errors = array();

    // PHP 5.4+ required
    if (true === version_compare(PHP_VERSION, '5.4.0', '<')) {
        $errors[] = 'Your PHP version is too old. The agate payment plugin requires PHP 5.4 or higher to function. Please contact your web server administrator for assistance.';
    }

    // Wordpress 3.9+ required
    if (true === version_compare($wp_version, '3.9', '<')) {
        $errors[] = 'Your WordPress version is too old. The agate payment plugin requires Wordpress 3.9 or higher to function. Please contact your web server administrator for assistance.';
    }

    // GMP or BCMath required
    if (false === extension_loaded('gmp') && false === extension_loaded('bcmath')) {
        $errors[] = 'The agate payment plugin requires the GMP or BC Math extension for PHP in order to function. Please contact your web server administrator for assistance.';
    }

    if (false === empty($errors)) {
        return implode("<br>\n", $errors);
    } else {
        return false;
    }
}
