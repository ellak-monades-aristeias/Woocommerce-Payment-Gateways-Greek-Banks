<?php
/*
  Plugin Name: National Bank Greece WooCommerce Payment Gateway
  Plugin URI: http://emspace.gr
  Description: National Bank Greece Payment Gateway allows you to accept payment through various channels such as Maestro, Mastercard and Visa cards On your Woocommerce Powered Site.
  Version: 1.0.0
  Author: emspace.gr
  Author URI: http://emspace.gr
  License: GPL-3.0+
  License URI: http://www.gnu.org/licenses/gpl-3.0.txt
 */

if (!defined('ABSPATH'))
    exit;

add_action('plugins_loaded', 'woocommerce_nbg_init', 0);
require_once( 'simplexml.php' );
function woocommerce_nbg_init() {

    if (!class_exists('WC_Payment_Gateway'))
        return;

    load_plugin_textdomain('woocommerce-nbg-payment-gateway', false, dirname(plugin_basename(__FILE__)) . '/languages/');

    /**
     * Gateway class
     */
    class WC_NBG_Gateway extends WC_Payment_Gateway {

        public function __construct() {
            global $woocommerce;

            $this->id = 'nbg_gateway';
            $this->icon = apply_filters('nbg_icon', plugins_url('assets/nbg.png', __FILE__));
            $this->has_fields = false;
            $this->notify_url = WC()->api_request_url('WC_NBG_Gateway');
			$this->method_description = __('National Bank Greece Payment Gateway allows you to accept payment through various channels such as Maestro, Mastercard  and Visa cards On your Woocommerce Powered Site.', 'woocommerce-nbg-payment-gateway');
            $this->redirect_page_id = $this->get_option('redirect_page_id');
			$this->method_title = 'National Bank of Greece Gateway';
			
			// Load the form fields.
			$this->init_form_fields();
		    
            //dhmioyrgia vashs

            global $wpdb;

            if ($wpdb->get_var("SHOW TABLES LIKE '" . $wpdb->prefix . "nbg_transactions'") === $wpdb->prefix . 'nbg_transactions') {
                // The database table exist
            } else {
                // Table does not exist
                $query = 'CREATE TABLE IF NOT EXISTS ' . $wpdb->prefix . 'nbg_transactions (id int(11) unsigned NOT NULL AUTO_INCREMENT,merchantreference varchar(30) not null, reference varchar(100) not null, orderid varchar(100) not null , timestamp datetime default null, PRIMARY KEY (id))';
                $wpdb->query($query);
            }
			

            // Load the settings.
            $this->init_settings();


            // Define user set variables
            $this->title = $this->get_option('title');
            $this->description = $this->get_option('description');
			$this->nbg_Username = $this->get_option('nbg_Username');
            $this->nbg_Password = $this->get_option('nbg_Password');
			
		    $this->nbg_description= $this->get_option('nbg_description');
            $this->mode = $this->get_option('mode');			
            $this->nbg_installments= $this->get_option('nbg_installments');
            //Actions
            add_action('woocommerce_receipt_nbg_gateway', array($this, 'receipt_page'));
            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));

            // Payment listener/API hook
            add_action('woocommerce_api_wc_nbg_gateway', array($this, 'check_nbg_response'));
        }

        /**
         * Admin Panel Options
         * */
        public function admin_options() {
            echo '<h3>' . __('National Bank Greece Gateway', 'woocommerce-nbg-payment-gateway') . '</h3>';
            echo '<p>' . __('National Bank Greece Gateway allows you to accept payment through various channels such as Maestro, Mastercard  and Visa cards.', 'woocommerce-nbg-payment-gateway') . '</p>';


            echo '<table class="form-table">';
            $this->generate_settings_html();
            echo '</table>';
        }

        /**
         * Initialise Gateway Settings Form Fields
         * */
        function init_form_fields() {
            $this->form_fields = array(
                'enabled' => array(
                    'title' => __('Enable/Disable', 'woocommerce-nbg-payment-gateway'),
                    'type' => 'checkbox',
                    'label' => __('Enable National Bank Greece Gateway', 'woocommerce-nbg-payment-gateway'),
                    'description' => __('Enable or disable the gateway.', 'woocommerce-nbg-payment-gateway'),
                    'desc_tip' => true,
                    'default' => 'yes'
                ),
                'title' => array(
                    'title' => __('Title', 'woocommerce-nbg-payment-gateway'),
                    'type' => 'text',
                    'description' => __('This controls the title which the user sees during checkout.', 'woocommerce-nbg-payment-gateway'),
                    'desc_tip' => false,
                    'default' => __('National Bank of Greece Gateway', 'woocommerce-nbg-payment-gateway')
                ),
                'description' => array(
                    'title' => __('Description', 'woocommerce-nbg-payment-gateway'),
                    'type' => 'textarea',
                    'description' => __('This controls the description which the user sees during checkout.', 'woocommerce-nbg-payment-gateway'),
                    'default' => __('Pay Via National Bank Greece: Accepts  Mastercard, Visa cards and etc.', 'woocommerce-nbg-payment-gateway')
                ),'nbg_Username' => array(
                    'title' => __('NBG  Username', 'woocommerce-nbg-payment-gateway'),
                    'type' => 'text',
                    'description' => __('Enter your NBG Username', 'woocommerce-nbg-payment-gateway'),
                    'default' => '',
                    'desc_tip' => true
                ),'nbg_Password' => array(
                    'title' => __('NBG  Password', 'woocommerce-nbg-payment-gateway'),
                    'type' => 'text',
                    'description' => __('Enter your NBG Password', 'woocommerce-nbg-payment-gateway'),
                    'default' => '',
                    'desc_tip' => true
                ), 'nbg_auth' => array(
                    'title' => __('Pre-Authorize', 'woocommerce-nbg-payment-gateway'),
                    'type' => 'checkbox',
                    'label' => __('Default payment method is Authorize, enable for Pre-Authorized payments.', 'woocommerce-nbg-payment-gateway'),
                    'default' => 'yes',
                    'description' => __('Select between Authorize and Pre-Authorize', 'woocommerce-nbg-payment-gateway')
                ),'nbg_description' => array(
                    'title' => __('NBG Transaction Description', 'woocommerce-nbg-payment-gateway'),
                    'type' => 'text',
                    'description' => __('Add a description to the transactions', 'woocommerce-nbg-payment-gateway'),
                    'default' => '',
                    'desc_tip' => true
                ), 'mode' => array(
                    'title' => __('Mode', 'woocommerce-nbg-payment-gateway'),
                    'type' => 'checkbox',
                    'label' => __('Enable Test Mode', 'woocommerce-nbg-payment-gateway'),
                    'default' => 'yes',
                    'description' => __('This controls  the payment mode as TEST or LIVE.', 'woocommerce-nbg-payment-gateway')
                ),
                'redirect_page_id' => array(
                    'title' => __('Return Page', 'woocommerce-nbg-payment-gateway'),
                    'type' => 'select',
                    'options' => $this->nbg_get_pages('Select Page'),
                    'description' => __('URL of success page', 'woocommerce-nbg-payment-gateway')
                )                ,
                'nbg_installments' => array(
                    'title' => __('Max Installments', 'woocommerce-nbg-payment-gateway'),
                    'type' => 'select',
                    'options' => $this->nbg_get_installments('Select Installments'),
                    'description' => __('1 to 24 Installments,1 for one time payment ', 'woocommerce-nbg-payment-gateway')
                )
            );
        }

        function nbg_get_pages($title = false, $indent = true) {
            $wp_pages = get_pages('sort_column=menu_order');
            $page_list = array();
            if ($title)
                $page_list[] = $title;
            foreach ($wp_pages as $page) {
                $prefix = '';
                // show indented child pages?
                if ($indent) {
                    $has_parent = $page->post_parent;
                    while ($has_parent) {
                        $prefix .= ' - ';
                        $next_page = get_page($has_parent);
                        $has_parent = $next_page->post_parent;
                    }
                }
                // add to page list array array
                $page_list[$page->ID] = $prefix . $page->post_title;
            }
			$page_list[-1] = __('Thank you page', 'woocommerce-nbg-payment-gateway');
            return $page_list;
        }
        
        function nbg_get_installments($title = false, $indent = true) {          
           
          
            for($i = 1; $i<=24;$i++) {              
                $installment_list[$i] = $i;
            }
            return $installment_list;
        }

   
        function generate_nbg_form($order_id) {
			global $wpdb;
            $order = new WC_Order($order_id);
			$merchantreference = substr(sha1(rand()), 0, 30);
			
			if ($this->mode == "yes") 
			{//test mode
			$post_url = 'https://accreditation.datacash.com/Transaction/acq_a';
			$page_set_id = '44'	;
			/*
			HPS - HCC  pagesets  - without Installments  (TEST)
			41-NBG-HPS-WithoutInstallmentGRK
			42-NBG-HPS-WithoutInstallmentENG			 

			HPS pagesets  with Installments (TEST)
			43-NBG-HPS-WithInstallmentENG
			44-NBG-HPS-WithInstallmentGRK
			*/		
			
			}
			 else
			 { //live mode
			 $post_url ='https://mars.transaction.datacash.com/Transaction';			 
			 $page_set_id='1438';
			 /*
			 HPS-HCC pagesets  - without Installments (LIVE)
			 1300-NBG_Without_Installment_Greek
			 1301-NBG_Without_Installment_Eng			 

			 HPS  pagesets with Installments (LIVE)
			 1439-NBG-HPS-WithInstallment - English Page
			 1438-NBG-HPS-WithInstallment - Greek Page
			 */
			 
			 }
			 
			if ($this->nbg_auth== "yes")
			{//pre-authorize
			$method="pre";
			}
			else
			{//authorize
			$method="auth";
			}
			
			//make XML
			
			$xml = new SimpleXMLExtended('<?xml version="1.0" encoding="utf-8"?><Request version="2"/>');
			
			$authentication =   $xml->addChild('Authentication');			
			$authentication->addChild('password', $this->nbg_Password);
            $authentication->addChild('client', $this->nbg_Username);			 
            
			$transaction = $xml->addChild('Transaction');	
				$TxnDetails=$transaction->addChild('TxnDetails');
					$TxnDetails->addChild('merchantreference',$merchantreference);
					$TxnDetails->addChild('capturemethod','ecomm');	
					$amount = $TxnDetails->addChild('amount',$order->get_total());	
					$amount->addAttribute('currency','EUR');
					$ThreeDSecure=$TxnDetails->addChild('ThreeDSecure');	
						$Browser=$ThreeDSecure->addChild('Browser');
							$Browser->addChild('device_category','0');
							$Browser->addChild('accept_headers','*/*');
							$Browser->addChild('user_agent',$_SERVER['HTTP_USER_AGENT']);               
						$ThreeDSecure->addChild('purchase_datetime',date('Ymd h:i:s'));
						$ThreeDSecure->addChild('merchant_url',get_site_url());	
						$ThreeDSecure->addChild('purchase_desc',$this->nbg_description);
						$ThreeDSecure->addChild('verify','yes');
					
				$HpsTxn =$transaction->addChild('HpsTxn');
					$HpsTxn->addChild('method', 'setup_full');
					$return_url_str= get_site_url()."?wc-api=WC_NBG_gateway".htmlspecialchars('&')."nbg=success&amp;MerchantReference=".$merchantreference;
					$HpsTxn->addChild('return_url',$return_url_str);
					$HpsTxn->addChild('expiry_url', get_site_url().'?wc-api=WC_NBG_gateway'.htmlspecialchars('&').'nbg=cancel');
					$HpsTxn->addChild('page_set_id', $page_set_id);
					$DynamicData = $HpsTxn->addChild('DynamicData');
						$DynamicData->dyn_data_4=NULL;
						$DynamicData->dyn_data_4->addCData($this->nbg_installments);
						
				$CardTxn=$transaction->addChild('CardTxn');
					$CardTxn->addChild('method', $method);
			

		
	//make XML  curl call 
	$ch = curl_init ($post_url);
    curl_setopt($ch, CURLOPT_POST,1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $xml->asXML()); 
    curl_setopt($ch, CURLOPT_HTTPHEADER, Array("Content-Type: text/xml"));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_NOPROGRESS, 0);
	
    $result = curl_exec ($ch); // result will contain XML reply 
    curl_close ($ch);
    if ( $result == false )
	{
          return __('Could not connect to NBG server, please contact the administrator ', 'woocommerce-nbg-payment-gateway') ;
	} 

	//receive response and parse xml
	$response = simplexml_load_string($result);
	

	if ($response->status !=1)
	{
		//An error occurred
		return __('An error occurred, please contact the administrator ', 'woocommerce-nbg-payment-gateway') ;
	}else
	{	
		//If response success save data in DB and redirect
		$wpdb->insert($wpdb->prefix . 'nbg_transactions', array('reference' => $response->datacash_reference,'merchantreference'=> $merchantreference , 'orderid' => $order_id, 'timestamp' => current_time('mysql', 1)));
		
		
		$requesturl = $response->HpsTxn->hps_url.'?HPS_SessionID='.$response->HpsTxn->session_id;
		
		
		          /* */  wc_enqueue_js('
				$.blockUI({
						message: "' . esc_js(__('Thank you for your order. We are now redirecting you to National Bank Greece to make payment.', 'woocommerce-nbg-payment-gateway')) . '",
						baseZ: 99999,
						overlayCSS:
						{
							background: "#fff",
							opacity: 0.6
						},
						css: {
							padding:        "20px",
							zindex:         "9999999",
							textAlign:      "center",
							color:          "#555",
							border:         "3px solid #aaa",
							backgroundColor:"#fff",
							cursor:         "wait",
							lineHeight:		"24px",
						}
					});
				jQuery("#submit_nbg_payment_form").click();
			');
	
		 return '<form action="' . $requesturl . '" method="post" id="nbg_payment_form" target="_top">
				
					<!-- Button Fallback -->
					<div class="payment_buttons">
						<input type="submit" class="button alt" id="submit_nbg_payment_form" value="' . __('Pay via NBG', 'woocommerce-nbg-payment-gateway') . '" /> <a class="button cancel" href="' . esc_url($order->get_cancel_order_url()) . '">' . __('Cancel order &amp; restore cart', 'woocommerce-nbg-payment-gateway') . '</a>
					</div>
					<script type="text/javascript">
						jQuery(".payment_buttons").hide();
					</script>
				</form>';
	} 
}

        /**
         * Process the payment and return the result
         * */
        function process_payment($order_id) {

            $order = new WC_Order($order_id);
            return array(
                'result' => 'success',
                'redirect' => $order->get_checkout_payment_url(true)
            );
        }

        /**
         * Output for the order received page.
         * */
        function receipt_page($order) {
            echo '<p>' . __('Thank you - your order is now pending payment. You should be automatically redirected to National Bank of Greece to make payment.', 'woocommerce-nbg-payment-gateway') . '</p>';
            echo $this->generate_nbg_form($order);
        }

        /**
         * Verify a successful Payment!
         * */
        function check_nbg_response() {
	

            global $woocommerce;
            global $wpdb;
		
			
		 if (isset($_GET['nbg'])&&($_GET['nbg']==='success')) {	
			
			 $merchantreference= $_GET['MerchantReference'];
			 
			 
			 //query DB
			 
				$ttquery = 'SELECT *
				FROM `' . $wpdb->prefix . 'nbg_transactions`
				WHERE `merchantreference` like "' . $merchantreference . '"	;';
				$ref = $wpdb->get_results($ttquery);
				$orderid=$ref['0']->orderid;
			
			$xml = new SimpleXMLExtended('<?xml version="1.0" encoding="utf-8"?><Request version="2"/>');
				
				$authentication =   $xml->addChild('Authentication');			
				$authentication->addChild('password', $this->nbg_Password);
				$authentication->addChild('client', $this->nbg_Username);			 
				
				$transaction = $xml->addChild('Transaction');	
					$HistoricTxn=$transaction->addChild('HistoricTxn');
						$HistoricTxn->addChild('reference',$ref['0']->reference);
						$HistoricTxn->addChild('method', 'query');
						

			if ($this->mode == "yes") 
			{//test mode
			$post_url = 'https://accreditation.datacash.com/Transaction/acq_a';			
			}
			 else
			 { //live mode
			 $post_url ='https://mars.transaction.datacash.com/Transaction';	
			 }			
						
			 // make CURL request		 
			$ch = curl_init ($post_url);
			curl_setopt($ch, CURLOPT_POST,1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $xml->asXML()); 
			curl_setopt($ch, CURLOPT_HTTPHEADER, Array("Content-Type: text/xml"));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_NOPROGRESS, 0);
			
			$result = curl_exec ($ch); // result will contain XML reply 
			curl_close ($ch);
			if ( $result == false )
			{
				  return __('Could not connect to NBG server, please contact the administrator ', 'woocommerce-nbg-payment-gateway') ;
			} 

			//Response			
			$response = simplexml_load_string($result);
		

			$order = new WC_Order($orderid);
			
			if ($response->status ==1)
			{
		
				if(strcmp($response->reason,'ACCEPTED')==0)
				{
				
				//verified - successful payment
				//complete order			
								if ($order->status == 'processing') {

									$order->add_order_note(__('Payment Via NBG<br />Transaction ID: ', 'woocommerce-nbg-payment-gateway') . $trans_id);

									//Add customer order note
									$order->add_order_note(__('Payment Received.<br />Your order is currently being processed.<br />We will be shipping your order to you soon.<br />NBG Transaction ID: ', 'woocommerce-nbg-payment-gateway') . $trans_id, 1);

									// Reduce stock levels
									$order->reduce_order_stock();

									// Empty cart
									WC()->cart->empty_cart();

									$message = __('Thank you for shopping with us.<br />Your transaction was successful, payment was received.<br />Your order is currently being processed.', 'woocommerce-nbg-payment-gateway');
									$message_type = 'success';
								} else {

									if ($order->has_downloadable_item()) {

										//Update order status
										$order->update_status('completed', __('Payment received, your order is now complete.', 'woocommerce-nbg-payment-gateway'));

										//Add admin order note
										$order->add_order_note(__('Payment Via NBG Payment Gateway<br />Transaction ID: ', 'woocommerce-nbg-payment-gateway') . $trans_id);

										//Add customer order note
										$order->add_order_note(__('Payment Received.<br />Your order is now complete.<br />NBG Transaction ID: ', 'woocommerce-nbg-payment-gateway') . $trans_id, 1);

										$message = __('Thank you for shopping with us.<br />Your transaction was successful, payment was received.<br />Your order is now complete.', 'woocommerce-nbg-payment-gateway');
										$message_type = 'success';
									} else {

										//Update order status
										$order->update_status('processing', __('Payment received, your order is currently being processed.', 'woocommerce-nbg-payment-gateway'));

										//Add admin order note
										$order->add_order_note(__('Payment Via NBG Payment Gateway<br />Transaction ID: ', 'woocommerce-nbg-payment-gateway') . $trans_id);

										//Add customer order note
										$order->add_order_note(__('Payment Received.<br />Your order is currently being processed.<br />We will be shipping your order to you soon.<br />NBG Transaction ID: ', 'woocommerce-nbg-payment-gateway') . $trans_id, 1);

										$message = __('Thank you for shopping with us.<br />Your transaction was successful, payment was received.<br />Your order is currently being processed.', 'woocommerce-nbg-payment-gateway');
										$message_type = 'success';
									}

									$nbg_message = array(
										'message' => $message,
										'message_type' => $message_type
									);

									update_post_meta($order_id, '_nbg_message', $nbg_message);
									// Reduce stock levels
									$order->reduce_order_stock();

									// Empty cart
									WC()->cart->empty_cart();
								} 
				
				}else
				{//payment has failed - retry
					$message = __('Thank you for shopping with us. <br />However, the transaction wasn\'t successful, payment wasn\'t received.', 'woocommerce-nbg-payment-gateway');
						$message_type = 'error';
						$nbg_message = array(
							'message' => $message,
							'message_type' => $message_type
						);
						update_post_meta($order_id, '_nbg_message', $pb_message);

						//Update the order status
						$order->update_status('failed', '');
						$checkout_url = $woocommerce->cart->get_checkout_url();
						wp_redirect($checkout_url);
						exit;
				}
				
			
			}
			else
			{//an error occurred
						$message = __('Thank you for shopping with us. <br />However, an error occurred and the transaction wasn\'t successful, payment wasn\'t received.', 'woocommerce-nbg-payment-gateway');
						$message_type = 'error';
						$nbg_message = array(
							'message' => $message,
							'message_type' => $message_type
						);
						update_post_meta($order_id, '_nbg_message', $pb_message);

						//Update the order status
						$order->update_status('failed', '');
						$checkout_url = $woocommerce->cart->get_checkout_url();
						wp_redirect($checkout_url);
						exit;
			
			
			}

				if ($this->redirect_page_id=="-1"){				
				$redirect_url = $this->get_return_url( $order );	
				}else	
				{							
                $redirect_url = ($this->redirect_page_id == "" || $this->redirect_page_id == 0) ? get_site_url() . "/" : get_permalink($this->redirect_page_id);
                //For wooCoomerce 2.0
                $redirect_url = add_query_arg(array('msg' => urlencode($this->msg['message']), 'type' => $this->msg['class']), $redirect_url);								
				}
				wp_redirect($redirect_url);
               
       

	
	
			exit;			

		
		 }		
		 if(isset($_GET['nbg'])&& ($_GET['nbg']==='cancel')) {	
		
		  $checkout_url = $woocommerce->cart->get_checkout_url();
		  wp_redirect($checkout_url);
          exit;
		  
		 }
			
        }

    }

    function nbg_message() {
        $order_id = absint(get_query_var('order-received'));
        $order = new WC_Order($order_id);
        $payment_method = $order->payment_method;

        if (is_order_received_page() && ( 'nbg_gateway' == $payment_method )) {

            $nbg_message = get_post_meta($order_id, '_nbg_message', true);
            $message = $nbg_message['message'];
            $message_type = $nbg_message['message_type'];

            delete_post_meta($order_id, '_nbg_message');

            if (!empty($nbg_message)) {
                wc_add_notice($message, $message_type);
            }
        }
    }

    add_action('wp', 'nbg_message');

    /**
     * Add National Bank Greeece Gateway to WC
     * */
    function woocommerce_add_nbg_gateway($methods) {
        $methods[] = 'WC_NBG_Gateway';
        return $methods;
    }

    add_filter('woocommerce_payment_gateways', 'woocommerce_add_nbg_gateway');





    /**
     * Add Settings link to the plugin entry in the plugins menu for WC below 2.1
     * */
    if (version_compare(WOOCOMMERCE_VERSION, "2.1") <= 0) {

        add_filter('plugin_action_links', 'nbg_plugin_action_links', 10, 2);

        function nbg_plugin_action_links($links, $file) {
            static $this_plugin;

            if (!$this_plugin) {
                $this_plugin = plugin_basename(__FILE__);
            }

            if ($file == $this_plugin) {
                $settings_link = '<a href="' . get_bloginfo('wpurl') . '/wp-admin/admin.php?page=woocommerce_settings&tab=payment_gateways&section=WC_NBG_Gateway">Settings</a>';
                array_unshift($links, $settings_link);
            }
            return $links;
        }

    }
    /**
     * Add Settings link to the plugin entry in the plugins menu for WC 2.1 and above
     * */ else {
        add_filter('plugin_action_links', 'nbg_plugin_action_links', 10, 2);

        function nbg_plugin_action_links($links, $file) {
            static $this_plugin;

            if (!$this_plugin) {
                $this_plugin = plugin_basename(__FILE__);
            }

            if ($file == $this_plugin) {
                $settings_link = '<a href="' . get_bloginfo('wpurl') . '/wp-admin/admin.php?page=wc-settings&tab=checkout&section=WC_NBG_Gateway">Settings</a>';
                array_unshift($links, $settings_link);
            }
            return $links;
        }

    }
}

