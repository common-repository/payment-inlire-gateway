<?php
/**
 * Plugin Name: Payment in-Lire Gateway
 * Description: Extension for WooCommerce to add payment with in-Lire
 * Plugin URI: https://www.in-lire.com/
 * Author: Eventi Telematici
 * Author URI: https://www.eventitelematici.com/
 * Version: 0.1
 * Requires at least: 4.4
 * Tested up to: 5.1
 * WC requires at least: 2.6
 * WC tested up to: 3.6
 * Text Domain: payment-inlire-gateway
 * Domain Path: /languages
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.Files.FileName

/**
 * WooCommerce fallback notice.
 *
 * @since 4.1.2
 * @return string
 */
function woocommerce_inlire_wc_missing() {
	/* translators: 1. URL link. */
	echo '<div class="error"><p><strong>' . sprintf( esc_html__( 'in-Lire requires WooCommerce to be installed and active. You can download %s here.', 'payment-inlire-gateway' ), '<a href="https://woocommerce.com/" target="_blank">WooCommerce</a>' ) . '</strong></p></div>';
}

add_action( 'plugins_loaded', 'woocommerce_gateway_inlire_init' );

function woocommerce_gateway_inlire_init() {

	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'woocommerce_inlire_wc_missing' );
		return;
	}

	if ( ! class_exists( 'WC_InLire' ) ) :
		/**
		 * Required minimums and constants
		 */
		define( 'WC_INLIRE_VERSION', '0.1' );
		define( 'WC_INLIRE_MIN_PHP_VER', '5.6.0' );
		define( 'WC_INLIRE_MIN_WC_VER', '2.6.0' );
		define( 'WC_INLIRE_MAIN_FILE', __FILE__ );
		define( 'WC_INLIRE_PLUGIN_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );
		define( 'WC_INLIRE_PLUGIN_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );

		class WC_InLire {

			/**
			 * @var Singleton The reference the *Singleton* instance of this class
			 */
			private static $instance;

			/**
			 * Returns the *Singleton* instance of this class.
			 *
			 * @return Singleton The *Singleton* instance.
			 */
			public static function get_instance() {
				if ( null === self::$instance ) {
					self::$instance = new self();
				}
				return self::$instance;
			}

			/**
			 * Private clone method to prevent cloning of the instance of the
			 * *Singleton* instance.
			 *
			 * @return void
			 */
			private function __clone() {}

			/**
			 * Private unserialize method to prevent unserializing of the *Singleton*
			 * instance.
			 *
			 * @return void
			 */
			private function __wakeup() {}

			/**
			 * Protected constructor to prevent creating a new instance of the
			 * *Singleton* via the `new` operator from outside of this class.
			 */
			private function __construct() {
				add_action( 'admin_init', array( $this, 'install' ) );
				$this->init();
			}

			/**
			 * Init the plugin after plugins_loaded so environment variables are set.
			 *
			 * @since 1.0.0
			 * @version 4.0.0
			 */
			public function init() {
				require_once dirname( __FILE__ ) . '/includes/class-wc-inlire-exception.php';
				require_once dirname( __FILE__ ) . '/includes/class-wc-inlire-logger.php';
				require_once dirname( __FILE__ ) . '/includes/class-wc-inlire-helper.php';
				require_once dirname( __FILE__ ) . '/includes/abstracts/abstract-wc-inlire-payment-gateway.php';
				require_once dirname( __FILE__ ) . '/includes/class-wc-gateway-inlire.php';
				
				if ( is_admin() ) {
					require_once dirname( __FILE__ ) . '/includes/admin/class-wc-inlire-admin-notices.php';
				}

				add_filter( 'woocommerce_payment_gateways', array( $this, 'add_gateways' ) );
				add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'plugin_action_links' ) );

				if ( version_compare( WC_VERSION, '3.4', '<' ) ) {
					add_filter( 'woocommerce_get_sections_checkout', array( $this, 'filter_gateway_order_admin' ) );
				}
			}

			/**
			 * Updates the plugin version in db
			 *
			 * @since 3.1.0
			 * @version 4.0.0
			 */
			public function update_plugin_version() {
				delete_option( 'wc_inlire_version' );
				update_option( 'wc_inlire_version', WC_INLIRE_VERSION );
			}

			/**
			 * Handles upgrade routines.
			 *
			 * @since 3.1.0
			 * @version 3.1.0
			 */
			public function install() {
				if ( ! is_plugin_active( plugin_basename( __FILE__ ) ) ) {
					return;
				}

				if ( ! defined( 'IFRAME_REQUEST' ) && ( WC_INLIRE_VERSION !== get_option( 'wc_inlire_version' ) ) ) {
					do_action( 'woocommerce_inlire_updated' );

					if ( ! defined( 'WC_INLIRE_INSTALLING' ) ) {
						define( 'WC_INLIRE_INSTALLING', true );
					}

					$this->update_plugin_version();
				}
			}

			/**
			 * Adds plugin action links.
			 *
			 * @since 1.0.0
			 * @version 4.0.0
			 */
			public function plugin_action_links( $links ) {
				$plugin_links = array(
					'<a href="admin.php?page=wc-settings&tab=checkout&section=inlire">' . esc_html__( 'Settings', 'payment-inlire-gateway' ) . '</a>'
				);
				return array_merge( $plugin_links, $links );
			}

			/**
			 * Add the gateways to WooCommerce.
			 *
			 * @since 1.0.0
			 * @version 4.0.0
			 */
			public function add_gateways( $methods ) {
				$methods[] = 'WC_Gateway_InLire';
				
				return $methods;
			}

			/**
			 * Modifies the order of the gateways displayed in admin.
			 *
			 * @since 4.0.0
			 * @version 4.0.0
			 */
			public function filter_gateway_order_admin( $sections ) {
				unset( $sections['inlire'] );

				$sections['inlire']            = 'inlire';

				return $sections;
			}
		}

		WC_InLire::get_instance();
	endif;
}
