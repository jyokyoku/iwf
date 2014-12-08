<?php

class IWF_Token {
	/**
	 * Session key
	 *
	 * @var string
	 */
	public static $session_key = '_iwf_token';

	/**
	 * Hidden field name
	 *
	 * @var string
	 */
	public static $field_name = '_iwf_token';

	/**
	 * Initialize
	 */
	public static function initialize() {
		if ( session_id() === '' ) {
			if ( headers_sent() ) {
				wp_die( 'Since the header has already been sent, the session can not be started.' );
			}

			session_start();
		}

		if ( ! isset( $_SESSION[ self::$session_key ] ) ) {
			$_SESSION[ self::$session_key ] = array();
		}
	}

	/**
	 * Generate the token
	 *
	 * @param string $action
	 *
	 * @return string
	 */
	public static function generate( $action = - 1 ) {
		self::initialize();

		$token                          = wp_hash( microtime() . mt_rand(), 'nonce' );
		$nonce                          = wp_create_nonce( $action );
		$_SESSION[ self::$session_key ] = array_slice( $_SESSION[ self::$session_key ], - 9, count( $_SESSION[ self::$session_key ] ), true ) + array( $token => $nonce );

		return $token;
	}

	/**
	 * Verify the token
	 *
	 * @param string $token
	 * @param string $action
	 *
	 * @return bool
	 */
	public static function verify( $token, $action = - 1 ) {
		self::initialize();

		if ( ! is_scalar( $token ) || ! iwf_has_array( $_SESSION, self::$session_key . '.' . $token ) ) {
			return false;
		}

		$nonce = iwf_get_array_hard( $_SESSION, self::$session_key . '.' . $token );

		return wp_verify_nonce( $nonce, $action );
	}

	/**
	 * Compare the specified url and referer
	 *
	 * @param null $expect_url
	 *
	 * @return bool
	 */
	public static function check_referer( $expect_url = null ) {
		if ( empty( $expect_url ) || ! is_string( $expect_url ) ) {
			$expect_url = is_admin() ? admin_url() : home_url();
		}

		$referer    = strtolower( wp_get_referer() );
		$expect_url = strtolower( $expect_url );

		return strpos( $referer, $expect_url ) === 0;
	}

	/**
	 * Verify the request with token
	 *
	 * @param string $action
	 * @param string $field_name
	 * @param bool $check_referer
	 *
	 * @return bool
	 */
	public static function verify_request( $action = - 1, $field_name = '', $check_referer = true ) {
		if ( $check_referer && ! self::check_referer( $check_referer ) ) {
			return false;
		}

		if ( empty( $field_name ) ) {
			$field_name = self::$field_name;
		}

		if ( ! $token = iwf_get_array( $_REQUEST, $field_name ) ) {
			return false;
		}

		return self::verify( $token, $action );
	}

	/**
	 * Return the hidden field
	 *
	 * @param string $action
	 * @param string $field_name
	 *
	 * @return string
	 */
	public static function hidden_field( $action = - 1, $field_name = '' ) {
		if ( empty( $field_name ) ) {
			$field_name = self::$field_name;
		}

		return IWF_Form::hidden( $field_name, self::generate( $action ) );
	}

	/**
	 * Return the created url with query string of token
	 *
	 * @param string $url
	 * @param array $args
	 * @param string $action
	 * @param string $field_name
	 *
	 * @return string
	 */
	public static function url( $url, $args = array(), $action = - 1, $field_name = '' ) {
		if ( empty( $field_name ) ) {
			$field_name = self::$field_name;
		}

		return esc_url( iwf_create_url( $url, array_merge( (array) $args, array( $field_name => self::generate( $action ) ) ) ) );
	}
}