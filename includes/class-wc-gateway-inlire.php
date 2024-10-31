<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WC_Gateway_InLire class.
 *
 * @extends WC_Payment_Gateway
 */
class WC_Gateway_InLire extends WC_InLire_Payment_Gateway {
	/**
	 * in-Lire service url.
	 *
	 * @var string
	 */
	public $inlireServiceUrl;
	
	/**
	 * in-Lire token key.
	 *
	 * @var string
	 */
	public $inlireTokenKey;
	
	/**
	 * in-Lire token key.
	 *
	 * @var int
	 */
	public $inlireMaximumAmount;
	
	/**
	 * Constructor
	 */
	public function __construct() {
		$this->retry_interval = 1;
		$this->id             = 'inlire';
		$this->method_title   = __( 'in-Lire', 'payment-inlire-gateway' );
		$this->method_description = sprintf( __( 'in-Lire works by adding payment fields on the checkout and then sending the details to in-Lire for verification.', 'payment-inlire-gateway' ) );
		$this->has_fields         = true;
		$this->supports           = array(
			'products'
		);

		// Load the form fields.
		$this->init_form_fields();

		// Load the settings.
		$this->init_settings();

		// Get setting values.
		$this->title                       = $this->get_option( 'title' );
		$this->description                 = $this->get_option( 'description' );
		$this->enabled                     = $this->get_option( 'enabled' );
		//$this->testmode                    = 'yes' === $this->get_option( 'testmode' );
		$this->inlireServiceUrl            = $this->get_option( 'payment_service_url' );
		$this->inlireTokenKey              = $this->get_option( 'payment_token_key' );
		$this->inlireMaximumAmount         = $this->get_option( 'order_amout' );

		// Hooks.
		add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
		add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
	}

	/**
	 * Checks if gateway should be available to use.
	 *
	 * @since 4.0.2
	 */
	public function is_available() {
		
		return parent::is_available();
		//return true;
	}

	/**
	 * Get_icon function.
	 *
	 * @since 1.0.0
	 * @version 4.0.0
	 * @return string
	 */
	public function get_icon() {
		$icons = $this->payment_icons();

		$icons_str = '';

		$icons_str .= isset( $icons['inlire'] ) ? $icons['inlire'] : '';
		
		$icons_str .= sprintf( '&nbsp;<a href="%1$s" class="about_inlire" onclick="javascript:window.open(\'%1$s\',\'InLire\',\'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, width=1060, height=700\'); return false;">' . esc_attr__( 'What is in-Lire?', 'payment-inlire-gateway' ) . '</a>', esc_url( $this->get_payment_icon_url() ) );

		return apply_filters( 'woocommerce_gateway_icon', $icons_str, $this->id );
	}

	/**
	 * Initialise Gateway Settings Form Fields
	 */
	public function init_form_fields() {
		$this->form_fields = require( dirname( __FILE__ ) . '/admin/inlire-settings.php' );
	}

	/**
	 * Payment form on checkout page
	 */
	public function payment_fields() {
		$total                = WC()->cart->total;
		$description          = $this->get_description();
		$description          = ! empty( $description ) ? $description : '';
		
		// If paying from order, we need to get total from order not cart.
		if ( isset( $_GET['pay_for_order'] ) && ! empty( $_GET['key'] ) ) {
			$order      = wc_get_order( wc_get_order_id_by_order_key( wc_clean( $_GET['key'] ) ) );
			$total      = $order->get_total();
		}
		
		if ( $total >= $this->inlireMaximumAmount  ) {
			$description .= ' ' . sprintf(__( 'Attention. The maximum amout for this payment method is %s', 'payment-inlire-gateway' ), wc_price( $this->inlireMaximumAmount ));
		}

		ob_start();
		
		echo apply_filters( 'wc_inlire_description', wpautop( wp_kses_post( $description ) ), $this->id );
		
		$this->elements_form();

		ob_end_flush();
	}

	/**
	 * Renders the InLire elements form.
	 *
	 * @since 4.0.0
	 * @version 4.0.0
	 */
	public function elements_form() {
		?>
		<br/>
		<fieldset id="wc-<?php echo esc_attr( $this->id ); ?>-form" class="wc-credit-card-form wc-payment-form" style="background:transparent;">
			<?php do_action( 'woocommerce_credit_card_form_start', $this->id ); ?>

			<label for="inlire-username"><?php esc_html_e( 'Username', 'payment-inlire-gateway' ); ?> <span class="required"></span></label>
			<div>
				<input type="text" class="input-text " name="inlire-username" id="inlire-username" placeholder="User Name" value="">
			</div>
			<br/>
			<label for="inlire-pwd"><?php esc_html_e( 'Password', 'payment-inlire-gateway' ); ?> <span class="required"></span></label>
			<div>
				<input type="password" class="input-text " name="inlire-pwd" id="inlire-pwd" placeholder="Password" value="">
			</div>

			<!-- Used to display form errors -->
			<div class="inlire-source-errors" role="alert"></div>
			<br />
			<?php do_action( 'woocommerce_credit_card_form_end', $this->id ); ?>
			<div class="clear"></div>
		</fieldset>
		<?php
	}

	/**
	 * Load admin scripts.
	 *
	 * @since 3.1.0
	 * @version 3.1.0
	 */
	public function admin_scripts() {
	}

	/**
	 * Payment_scripts function.
	 *
	 * Outputs scripts used for inlire payment
	 *
	 * @since 3.1.0
	 * @version 4.0.0
	 */
	public function payment_scripts() {
	}

	/**
	 * Process the payment
	 *
	 * @since 1.0.0
	 * @since 4.1.0 Add 4th parameter to track previous error.
	 * @param int  $order_id Reference.
	 * @param bool $retry Should we retry on fail.
	 * @param bool $force_save_source Force save the payment source.
	 * @param mix $previous_error Any error message from previous request.
	 *
	 * @throws Exception If payment will not be accepted.
	 *
	 * @return array|void
	 */
	public function process_payment( $order_id, $retry = true, $force_save_source = false, $previous_error = false ) {
		try {
			$order = wc_get_order( $order_id );
			
			//get username and password
			$inlireUsername = $_POST['inlire-username'];
			$inlirePassword = $_POST['inlire-pwd'];
			
			// This will throw exception if not valid.
			$this->validate_url($this->inlireServiceUrl);

			// This will throw exception if not valid.
			$this->validate_token($this->inlireTokenKey);
			
			// This will throw exception if not valid.
			$this->validate_username_and_password( $inlireUsername,$inlirePassword );
			
			// This will throw exception if not valid.
			$this->validate_maximum_order_amount( $order );
			
			$url=$this->inlireServiceUrl;
			$data = array("token" => $this->inlireTokenKey, "amount" => str_replace(".", ",", $order->get_total()), "UserName" => $inlireUsername, "Password" => $inlirePassword, "Notes" => '');
			$data_string = json_encode($data);
                                                                                                         
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
			curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Content-Type: application/json',
				'Content-Length: ' . strlen($data_string))
			);
			
			$result = curl_exec($ch);
			if (($result!==false) && (trim($result)!="")) {
				$jdata = json_decode($result,true);
				if (!empty($jdata)) {
					if ($jdata['isSuccess']) {
						//save the order
						if ( is_callable( array( $order, 'save' ) ) ) {
							$order->save();
						}
						
						$order->update_status( 'completed' );
						
						//redure the stock
						$order->reduce_order_stock();
						
						//set payment complete
						$order->payment_complete();
						
						//empty the cart
						WC()->cart->empty_cart();
						
						//return thank you page redirect
						return array(
							'result'   => 'success',
							'redirect' => $this->get_return_url( $order )
						);
					} else {
						$this->validate_response($jdata['response_message']);
					}
				}
			} else {
				throw new WC_InLire_Exception( 'No response from in-Lire', __( 'No response from the server' ));
			}
			curl_close($ch);

		} catch ( WC_InLire_Exception $e ) {
			wc_add_notice( $e->getLocalizedMessage(), 'error' );
			WC_InLire_Logger::log( 'Error: ' . $e->getMessage() );

			do_action( 'wc_gateway_inlire_process_payment_error', $e, $order );

			/* translators: error message */
			$order->update_status( 'failed' );

			return array(
				'result'   => 'fail',
				'redirect' => '',
			);
		}
	}
}
