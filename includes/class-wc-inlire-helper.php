<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Provides static methods as helpers.
 *
 * @since 4.0.0
 */
class WC_InLire_Helper {
	
	/**
	 * Checks InLire minimum order value authorized per currency
	 */
	public static function get_minimum_amount() {
		// Check order amount
		//
	}

	/**
	 * Gets all the saved setting options from a specific method.
	 * If specific setting is passed, only return that.
	 *
	 * @since 4.0.0
	 * @version 4.0.0
	 * @param string $method The payment method to get the settings from.
	 * @param string $setting The name of the setting to get.
	 */
	public static function get_settings( $method = null, $setting = null ) {
		$all_settings = null === $method ? get_option( 'woocommerce_inlire_settings', array() ) : get_option( 'woocommerce_inlire_' . $method . '_settings', array() );

		if ( null === $setting ) {
			return $all_settings;
		}

		return isset( $all_settings[ $setting ] ) ? $all_settings[ $setting ] : '';
	}

	/**
	 * Check if WC version is pre 3.0.
	 *
	 * @todo Remove in the future.
	 * @since 4.0.0
	 * @deprecated 4.1.11
	 * @return bool
	 */
	public static function is_pre_30() {
		error_log( 'is_pre_30() function has been deprecated since 4.1.11. Please use is_wc_lt( $version ) instead.' );

		return self::is_wc_lt( '3.0' );
	}

	/**
	 * Checks if WC version is less than passed in version.
	 *
	 * @since 4.1.11
	 * @param string $version Version to check against.
	 * @return bool
	 */
	public static function is_wc_lt( $version ) {
		return version_compare( WC_VERSION, $version, '<' );
	}
}
