<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.Files.FileName

/**
 * Abstract class that will be inherited by all payment methods.
 *
 * @extends WC_Payment_Gateway_CC
 *
 * @since 4.0.0
 */
abstract class WC_InLire_Payment_Gateway extends WC_Payment_Gateway_CC {
	
	/**
	 * Check if we need to make gateways available.
	 *
	 * @since 4.1.3
	 */
	public function is_available() {
		if ( 'yes' === $this->enabled ) {
			return true;
		}

		return parent::is_available();
	}

	/**
	 * All payment icons that work with InLire. Some icons references
	 * WC core icons.
	 *
	 * @since 4.0.0
	 * @since 4.1.0 Changed to using img with svg (colored) instead of fonts.
	 * @return array
	 */
	public function payment_icons() {
		return apply_filters(
			'wc_inlire_payment_icons',
			array(
				'inlire'       => '<img src="' . WC_INLIRE_PLUGIN_URL . '/assets/images/inlire.png" alt="in-Lire" />'
			)
		);
	}
	
	/**
	 * Get the link for the icon based on country.
	 *
	 * @param  nothing
	 * @return string
	 */
	protected function get_payment_icon_url() {
		$url           = 'https://www.in-lire.com/';
		return $url;
	}

	/**
	 * Validates that the order meets the minimum order amount
	 * set by InLire.
	 *
	 * @since 4.0.0
	 * @version 4.0.0
	 * @param object $order
	 */
	public function validate_minimum_order_amount( $order ) {
	}
	
	/**
	 * Validates that the order meets the maximum order amount
	 * set by InLire.
	 *
	 * @since 4.0.0
	 * @version 4.0.0
	 * @param object $order
	 */
	public function validate_maximum_order_amount( $order ) {
		if (empty($this->inlireMaximumAmount) && !is_numeric($this->inlireMaximumAmount)) {
			throw new WC_InLire_Exception( 'Maximum amount not set', __( 'Set an appropriate maximum amount for the orders', 'payment-inlire-gateway' ) );
		} else {
			if ($this->inlireMaximumAmount<=0) {
				throw new WC_InLire_Exception( 'Maximum amount greater then 0', __( 'Maximum amount must be greater then 0', 'payment-inlire-gateway' ) );
			} else {
				if ( $order->get_total() >= $this->inlireMaximumAmount ) {
					throw new WC_InLire_Exception( 'Did not meet maximum amount', sprintf( __( 'Sorry, the maximum allowed order total is %1$s to use this payment method.', 'payment-inlire-gateway' ), wc_price( $this->inlireMaximumAmount ) ) );
				}
			}
		}
	}
	
	/**
	 * Validates that the url is set
	 * set by InLire.
	 *
	 * @since 4.0.0
	 * @version 4.0.0
	 * @param object $url
	 */
	public function validate_url( $url ) {
		if ( empty($url) ) {
			throw new WC_InLire_Exception( 'Url not set', __( 'Before to use this payment method, the in-Lire url must be set', 'payment-inlire-gateway' ) );
		}
	}
	
	/**
	 * Validates the presence of the token
	 * set by InLire.
	 *
	 * @since 4.0.0
	 * @version 4.0.0
	 * @param object $token
	 */
	public function validate_token( $token ) {
		if ( empty($token) ) {
			throw new WC_InLire_Exception( 'Url not set', __( 'Before to use this payment method, the in-Lire token must be set', 'payment-inlire-gateway' ) );
		}
	}
	
	/**
	 * Validates that the username and password is passed correctly
	 *
	 * @since 4.0.0
	 * @version 4.0.0
	 * @param object $username $password
	 */
	public function validate_username_and_password( $username, $password ) {
		if ( (trim($username) === '') || (trim($password) === '') ) {
			if (trim($username) === '') {
				throw new WC_InLire_Exception( 'Invalid username', __( 'Please, enter your in-Lire username', 'payment-inlire-gateway' ));
			} else if (trim($password) === '') {
				throw new WC_InLire_Exception( 'Invalid password', __( 'Please, enter your in-Lire password', 'payment-inlire-gateway' ));
			}
		}
	}
	
	/**
	 * Rise error from inlire
	 *
	 * @since 4.0.0
	 * @version 4.0.0
	 * @param object $order
	 */
	public function validate_response( $resp ) {
		throw new WC_InLire_Exception( 'Error form in-Lire', $resp);
	}

	/**
	 * Sends the failed order email to admin.
	 *
	 * @since 3.1.0
	 * @version 4.0.0
	 * @param int $order_id
	 * @return null
	 */
	public function send_failed_order_email( $order_id ) {
		$emails = WC()->mailer()->get_emails();
		if ( ! empty( $emails ) && ! empty( $order_id ) ) {
			$emails['WC_Email_Failed_Order']->trigger( $order_id );
		}
	}

	/**
	 * Gets the locale with normalization that only InLire accepts.
	 *
	 * @since 4.0.6
	 * @return string $locale
	 */
	public function get_locale() {
		$locale = get_locale();

		/*
		 * InLire expects Norwegian to only be passed NO.
		 * But WP has different dialects.
		 */
		if ( 'NO' === substr( $locale, 3, 2 ) ) {
			$locale = 'no';
		} else {
			$locale = substr( get_locale(), 0, 2 );
		}

		return $locale;
	}
}
