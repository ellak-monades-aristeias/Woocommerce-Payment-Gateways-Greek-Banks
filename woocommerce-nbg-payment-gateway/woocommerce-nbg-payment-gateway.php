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
			

            // Load the settings.
            $this->init_settings();


            // Define user set variables
            $this->title = $this->get_option('title');
            $this->description = $this->get_option('description');
            $this->nbg_PayMerchantId = $this->get_option('nbg_PayMerchantId');
            $this->nbg_AcquirerId = $this->get_option('nbg_AcquirerId');
            $this->nbg_PosId = $this->get_option('nbg_PosId');			
			$this->nbg_Username = $this->get_option('nbg_Username');
            $this->nbg_Password = $this->get_option('nbg_Password');
			
		//	$this->customerMessage= $this->get_option('customerMessage');
            $this->mode = $this->get_option('mode');			
            $this->allowedInstallments= $this->get_option('installments');
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
                ),
                'nbg_PayMerchantId' => array(
                    'title' => __('NBG Merchant ID', 'woocommerce-nbg-payment-gateway'),
                    'type' => 'text',
                    'description' => __('Enter Your NBG Merchant ID', 'woocommerce-nbg-payment-gateway'),
                    'default' => '',
                    'desc_tip' => true
                ),
                'nbg_AcquirerId' => array(
                    'title' => __('NBG Acquirer ID', 'woocommerce-nbg-payment-gateway'),
                    'type' => 'text',
                    'description' => __('Enter Your Piraeus Bank Acquirer ID', 'woocommerce-nbg-payment-gateway'),
                    'default' => '',
                    'desc_tip' => true
                ),
                'nbg_PosId' => array(
                    'title' => __('NBG POS ID', 'woocommerce-nbg-payment-gateway'),
                    'type' => 'text',
                    'description' => __('Enter your NBG  POS ID', 'woocommerce-nbg-payment-gateway'),
                    'default' => '',
                    'desc_tip' => true
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
                'installments' => array(
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
            global $woocommerce;

            $order = new WC_Order($order_id);
	

                    wc_enqueue_js('
				$.blockUI({
						message: "' . esc_js(__('Thank you for your order. We are now redirecting you to Piraeus Bank to make payment.', 'woocommerce-nbg-payment-gateway')) . '",
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
                    return '<form action="' . $requesturl . '/web/checkout?ref=' . $transId . '" method="post" id="nbg_payment_form" target="_top">
				
					<!-- Button Fallback -->
					<div class="payment_buttons">
						<input type="submit" class="button alt" id="submit_nbg_payment_form" value="' . __('Pay via NBG', 'woocommerce-nbg-payment-gateway') . '" /> <a class="button cancel" href="' . esc_url($order->get_cancel_order_url()) . '">' . __('Cancel order &amp; restore cart', 'woocommerce-nbg-payment-gateway') . '</a>
					</div>
					<script type="text/javascript">
						jQuery(".payment_buttons").hide();
					</script>
				</form>';
               
        }

        /**
         * Process the payment and return the result
         * */
        /**/
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
		 if (isset($_GET['nbg'])&&($_GET['nbg']=='success')) {	
		 echo "success";
		
		
		
		 
		 }
		 if(isset($_GET['nbg'])&&($_GET['nbg']=='fail')) {	
		  echo "fail";
		
		  
		 }
		 if(isset($_GET['nbg'])&& ($_GET['nbg']=='cancel')) {	
		//  echo "cancel";
		  
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

