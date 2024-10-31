<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class that represents admin notices.
 *
 * @since 4.1.0
 */
class WC_InLire_Admin_Notices {
	/**
	 * Notices (array)
	 * @var array
	 */
	public $notices = array();

	/**
	 * Constructor
	 *
	 * @since 4.1.0
	 */
	public function __construct() {
		add_action( 'admin_notices', array( $this, 'admin_notices' ) );
		add_action( 'wp_loaded', array( $this, 'hide_notices' ) );
	}

	/**
	 * Allow this class and other classes to add slug keyed notices (to avoid duplication).
	 *
	 * @since 1.0.0
	 * @version 4.0.0
	 */
	public function add_admin_notice( $slug, $class, $message, $dismissible = false ) {
		$this->notices[ $slug ] = array(
			'class'       => $class,
			'message'     => $message,
			'dismissible' => $dismissible,
		);
	}

	/**
	 * Display any notices we've collected thus far.
	 *
	 * @since 1.0.0
	 * @version 4.0.0
	 */
	public function admin_notices() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		// Main Stripe payment method.
		$this->inlire_check_environment();

		foreach ( (array) $this->notices as $notice_key => $notice ) {
			echo '<div class="' . esc_attr( $notice['class'] ) . '" style="position:relative;">';

			if ( $notice['dismissible'] ) {
				?>
				<a href="<?php echo esc_url( wp_nonce_url( add_query_arg( 'wc-inlire-hide-notice', $notice_key ), 'wc_inlire_hide_notices_nonce', '_wc_inlire_notice_nonce' ) ); ?>" class="woocommerce-message-close notice-dismiss" style="position:relative;float:right;padding:9px 0px 9px 9px 9px;text-decoration:none;"></a>
				<?php
			}

			echo '<p>';
			echo wp_kses( $notice['message'], array( 'a' => array( 'href' => array() ) ) );
			echo '</p></div>';
		}
	}

	/**
	 * The backup sanity check, in case the plugin is activated in a weird way,
	 * or the environment changes after activation. Also handles upgrade routines.
	 *
	 * @since 1.0.0
	 * @version 4.0.0
	 */
	public function inlire_check_environment() {
		$show_phpver_notice = get_option( 'wc_inlire_show_phpver_notice' );
		$show_wcver_notice  = get_option( 'wc_inlire_show_wcver_notice' );
		$show_curl_notice   = get_option( 'wc_inlire_show_curl_notice' );
		$show_urlservice_notice = get_option( 'wc_inlire_show_urlservice_notice' );
		$show_token_notice  = get_option( 'wc_inlire_show_token_notice' );
		$show_maxamount_notice  = get_option( 'wc_inlire_show_maxamount_notice' );
		$options            = get_option( 'woocommerce_inlire_settings' );

		if ( isset( $options['enabled'] ) && 'yes' === $options['enabled'] ) {
			if ( empty( $show_phpver_notice ) ) {
				if ( version_compare( phpversion(), WC_INLIRE_MIN_PHP_VER, '<' ) ) {
					/* translators: 1) int version 2) int version */
					$message = __( 'in-Lire - The minimum PHP version required for this plugin is %1$s. You are running %2$s.', 'payment-inlire-gateway' );

					$this->add_admin_notice( 'phpver', 'error', sprintf( $message, WC_INLIRE_MIN_PHP_VER, phpversion() ), true );

					return;
				}
			}

			if ( empty( $show_wcver_notice ) ) {
				if ( version_compare( WC_VERSION, WC_INLIRE_MIN_WC_VER, '<' ) ) {
					/* translators: 1) int version 2) int version */
					$message = __( 'in-Lire - The minimum WooCommerce version required for this plugin is %1$s. You are running %2$s.', 'payment-inlire-gateway' );

					$this->add_admin_notice( 'wcver', 'notice notice-warning', sprintf( $message, WC_INLIRE_MIN_WC_VER, WC_VERSION ), true );

					return;
				}
			}

			if ( empty( $show_curl_notice ) ) {
				if ( ! function_exists( 'curl_init' ) ) {
					$this->add_admin_notice( 'curl', 'notice notice-warning', __( 'in-Lire - cURL is not installed.', 'payment-inlire-gateway' ), true );
				}
			}
			
			if ( empty( $show_urlservice_notice ) ) {
				if ( empty( $options['payment_service_url'] ) ) {
					$this->add_admin_notice( 'urlservice', 'notice notice-warning', __( 'in-Lire - Insert the url', 'payment-inlire-gateway' ));
				}
			}
			
			if ( empty( $show_token_notice ) ) {
				if ( empty( $options['payment_token_key'] ) ) {
					$this->add_admin_notice( 'token', 'notice notice-warning', __( 'in-Lire - Insert the token', 'payment-inlire-gateway' ));
				}
			}
			
			if ( empty( $show_maxamount_notice ) ) {
				if ( empty( $options['order_amout'] ) && !is_numeric($options['order_amout']) ) {
					$this->add_admin_notice( 'maxamount', 'notice notice-warning', __( 'in-Lire - Insert the maximum amount for the orders', 'payment-inlire-gateway' ));
				} else {
					if (($options['order_amout'] <= 0))
					$this->add_admin_notice( 'maxamount', 'notice notice-warning', __( 'in-Lire - Order amount must be greater then 0', 'payment-inlire-gateway' ));
				}
			}
		}
	}

	/**
	 * Hides any admin notices.
	 *
	 * @since 4.0.0
	 * @version 4.0.0
	 */
	public function hide_notices() {
		if ( isset( $_GET['wc-inlire-hide-notice'] ) && isset( $_GET['_wc_inlire_notice_nonce'] ) ) {
			if ( ! wp_verify_nonce( $_GET['_wc_inlire_notice_nonce'], 'wc_inlire_hide_notices_nonce' ) ) {
				wp_die( __( 'Action failed. Please refresh the page and retry.', 'payment-inlire-gateway' ) );
			}

			if ( ! current_user_can( 'manage_woocommerce' ) ) {
				wp_die( __( 'Cheatin&#8217; huh?', 'payment-inlire-gateway' ) );
			}

			$notice = wc_clean( $_GET['wc-inlire-hide-notice'] );

			switch ( $notice ) {
				case 'phpver':
					update_option( 'wc_inlire_show_phpver_notice', 'no' );
					break;
				case 'wcver':
					update_option( 'wc_inlire_show_wcver_notice', 'no' );
					break;
				case 'curl':
					update_option( 'wc_inlire_show_curl_notice', 'no' );
					break;
				case 'urlservice':
					update_option( 'wc_inlire_show_urlservice_notice', 'no' );
					break;
				case 'token':
					update_option( 'wc_inlire_show_token_notice', 'no' );
					break;
				case 'maxamount':
					update_option( 'wc_inlire_show_maxamount_notice', 'no' );
					break;
			}
		}
	}

	/**
	 * Get setting link.
	 *
	 * @since 1.0.0
	 *
	 * @return string Setting link
	 */
	public function get_setting_link() {
		$use_id_as_section = function_exists( 'WC' ) ? version_compare( WC()->version, '2.6', '>=' ) : false;

		$section_slug = $use_id_as_section ? 'inlire' : strtolower( 'WC_Gateway_InLire' );

		return admin_url( 'admin.php?page=wc-settings&tab=checkout&section=' . $section_slug );
	}
}

new WC_InLire_Admin_Notices();
