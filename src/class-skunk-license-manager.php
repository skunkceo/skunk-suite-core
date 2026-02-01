<?php
/**
 * Skunk Suite License Manager
 *
 * Centralised license management for all Skunk products (CRM, Forms, Pages).
 * Supports per-product keys and bundle keys that cover multiple products.
 *
 * Usage:
 *   Skunk_License_Manager::is_pro( 'crm' );
 *   Skunk_License_Manager::activate( 'crm', 'XXXX-XXXX-XXXX-XXXX' );
 *   Skunk_License_Manager::deactivate( 'crm' );
 *   Skunk_License_Manager::get_license( 'crm' );
 *
 * @package SkunkSuiteCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'Skunk_License_Manager' ) ) {
	return;
}

class Skunk_License_Manager {

	/**
	 * Option name for storing all license data
	 */
	const OPTION_NAME = 'skunk_licenses';

	/**
	 * Test license key that always validates
	 */
	const TEST_KEY = 'SKUNK-PRO-2024-DEMO-8F3A2B9C5E7D1A6F';

	/**
	 * Valid product identifiers
	 */
	const PRODUCTS = array( 'crm', 'forms', 'pages' );

	/**
	 * Whether AJAX handlers have been registered
	 *
	 * @var bool
	 */
	private static $ajax_registered = false;

	/**
	 * Boot the license manager — registers AJAX handlers once
	 */
	public static function init() {
		if ( self::$ajax_registered ) {
			return;
		}
		self::$ajax_registered = true;

		add_action( 'wp_ajax_skunk_activate_license',   array( __CLASS__, 'ajax_activate' ) );
		add_action( 'wp_ajax_skunk_deactivate_license', array( __CLASS__, 'ajax_deactivate' ) );
		add_action( 'wp_ajax_skunk_get_license_details', array( __CLASS__, 'ajax_get_details' ) );
	}

	// ------------------------------------------------------------------
	//  Public API
	// ------------------------------------------------------------------

	/**
	 * Check whether a product has a valid Pro licence
	 *
	 * Uses a 1-hour transient cache to avoid hitting the DB on every call.
	 *
	 * @param string $product  Product key (crm|forms|pages).
	 * @return bool
	 */
	public static function is_pro( $product ) {
		$product = sanitize_key( $product );

		// Check 1-hour transient first
		$cache_key = 'skunk_license_cache_' . md5( $product . '_is_pro' );
		$cached    = get_transient( $cache_key );
		if ( false !== $cached ) {
			return $cached === 'yes';
		}

		$license = self::get_license( $product );
		$is_pro  = ( $license && $license['status'] === 'valid' );

		set_transient( $cache_key, $is_pro ? 'yes' : 'no', HOUR_IN_SECONDS );

		return $is_pro;
	}

	/**
	 * Get stored licence data for a product
	 *
	 * Also checks bundle licences — if another product's licence covers this
	 * product via products_covered, it will be returned.
	 *
	 * @param string $product  Product key.
	 * @return array|null  Licence entry or null.
	 */
	public static function get_license( $product ) {
		$product  = sanitize_key( $product );
		$licenses = self::get_all_licenses();

		// Direct licence for this product
		if ( isset( $licenses[ $product ] ) ) {
			return $licenses[ $product ];
		}

		// Check if any other licence covers this product (bundle)
		foreach ( $licenses as $key => $entry ) {
			if ( ! empty( $entry['products_covered'] ) && in_array( $product, $entry['products_covered'], true ) ) {
				return $entry;
			}
		}

		return null;
	}

	/**
	 * Activate a licence key for a product
	 *
	 * @param string $product     Product key.
	 * @param string $license_key Licence key string.
	 * @return array { success: bool, message: string, data?: array }
	 */
	public static function activate( $product, $license_key ) {
		$product     = sanitize_key( $product );
		$license_key = strtoupper( trim( $license_key ) );

		if ( ! in_array( $product, self::PRODUCTS, true ) ) {
			return array( 'success' => false, 'message' => 'Invalid product.' );
		}

		// Validate key format
		if ( $license_key !== self::TEST_KEY && ! preg_match( '/^[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $license_key ) ) {
			return array( 'success' => false, 'message' => 'Invalid license key format.' );
		}

		// Call activation API
		$api_result = self::api_request( 'activate', $license_key );

		if ( ! $api_result['success'] ) {
			return $api_result;
		}

		$data = isset( $api_result['data'] ) ? $api_result['data'] : array();

		// Build licence entry
		$entry = array(
			'key'              => $license_key,
			'status'           => 'valid',
			'product_from_api' => isset( $data['product'] ) ? $data['product'] : $product,
			'plan_type'        => isset( $data['plan_type'] ) ? $data['plan_type'] : '',
			'max_sites'        => isset( $data['max_sites'] ) ? (int) $data['max_sites'] : 1,
			'billing'          => isset( $data['billing'] ) ? $data['billing'] : '',
			'last_check'       => time(),
			'products_covered' => isset( $data['products_covered'] ) ? (array) $data['products_covered'] : array( $product ),
		);

		// Store under the product key
		$licenses = self::get_all_licenses();
		$licenses[ $product ] = $entry;

		// If bundle, also store under each covered product
		if ( ! empty( $entry['products_covered'] ) ) {
			foreach ( $entry['products_covered'] as $covered ) {
				$covered = sanitize_key( $covered );
				if ( in_array( $covered, self::PRODUCTS, true ) ) {
					$licenses[ $covered ] = $entry;
				}
			}
		}

		self::save_all_licenses( $licenses );

		// Clear caches for affected products
		self::clear_caches( $entry['products_covered'] );

		// Set long validation transient (30 days)
		$validation_cache_key = 'skunk_license_cache_' . md5( $license_key . '_validation' );
		set_transient( $validation_cache_key, array(
			'status'     => 'valid',
			'data'       => $data,
			'checked_at' => time(),
		), 30 * DAY_IN_SECONDS );

		return array( 'success' => true, 'message' => 'License activated successfully.', 'data' => $data );
	}

	/**
	 * Deactivate a licence for a product
	 *
	 * @param string $product  Product key.
	 * @return array { success: bool, message: string }
	 */
	public static function deactivate( $product ) {
		$product  = sanitize_key( $product );
		$licenses = self::get_all_licenses();

		if ( ! isset( $licenses[ $product ] ) ) {
			return array( 'success' => true, 'message' => 'No license to deactivate.' );
		}

		$entry       = $licenses[ $product ];
		$license_key = $entry['key'];

		// Call deactivation API (best-effort)
		if ( ! empty( $license_key ) && $license_key !== self::TEST_KEY ) {
			self::api_request( 'deactivate', $license_key );
		}

		// Determine which products to clear
		$products_to_clear = ! empty( $entry['products_covered'] ) ? $entry['products_covered'] : array( $product );

		// Remove licence entries
		foreach ( $products_to_clear as $p ) {
			$p = sanitize_key( $p );
			if ( isset( $licenses[ $p ] ) && $licenses[ $p ]['key'] === $license_key ) {
				unset( $licenses[ $p ] );
			}
		}

		self::save_all_licenses( $licenses );

		// Clear caches
		self::clear_caches( $products_to_clear );
		delete_transient( 'skunk_license_cache_' . md5( $license_key . '_validation' ) );

		return array( 'success' => true, 'message' => 'License deactivated.' );
	}

	/**
	 * Re-validate an existing licence with the API
	 *
	 * @param string $product  Product key.
	 * @param bool   $force    Skip validation cache.
	 * @return array { success: bool, message: string }
	 */
	public static function validate( $product, $force = false ) {
		$product  = sanitize_key( $product );
		$license  = self::get_license( $product );

		if ( ! $license || empty( $license['key'] ) ) {
			return array( 'success' => false, 'message' => 'No license key found.' );
		}

		$license_key = $license['key'];

		// Check validation cache (30 days for valid, 1 day for invalid)
		if ( ! $force ) {
			$validation_cache_key = 'skunk_license_cache_' . md5( $license_key . '_validation' );
			$cached = get_transient( $validation_cache_key );
			if ( false !== $cached ) {
				return array(
					'success' => $cached['status'] === 'valid',
					'message' => $cached['status'] === 'valid' ? 'License is valid (cached).' : 'License is invalid (cached).',
				);
			}
		}

		// Call validation API
		$api_result = self::api_request( 'validate', $license_key );

		$licenses = self::get_all_licenses();

		if ( $api_result['success'] ) {
			$data = isset( $api_result['data'] ) ? $api_result['data'] : array();

			// Update stored licence data
			$license['status']     = 'valid';
			$license['last_check'] = time();

			if ( isset( $data['plan_type'] ) ) {
				$license['plan_type'] = $data['plan_type'];
			}
			if ( isset( $data['max_sites'] ) ) {
				$license['max_sites'] = (int) $data['max_sites'];
			}
			if ( isset( $data['billing'] ) ) {
				$license['billing'] = $data['billing'];
			}
			if ( isset( $data['products_covered'] ) ) {
				$license['products_covered'] = (array) $data['products_covered'];
			}

			$licenses[ $product ] = $license;
			self::save_all_licenses( $licenses );

			// Cache valid for 30 days
			$validation_cache_key = 'skunk_license_cache_' . md5( $license_key . '_validation' );
			set_transient( $validation_cache_key, array(
				'status'     => 'valid',
				'data'       => $data,
				'checked_at' => time(),
			), 30 * DAY_IN_SECONDS );
		} else {
			// Mark as invalid
			$license['status']     = 'invalid';
			$license['last_check'] = time();
			$licenses[ $product ]  = $license;
			self::save_all_licenses( $licenses );

			// Cache invalid for 1 day
			$validation_cache_key = 'skunk_license_cache_' . md5( $license_key . '_validation' );
			set_transient( $validation_cache_key, array(
				'status'     => 'invalid',
				'error'      => $api_result['message'],
				'checked_at' => time(),
			), DAY_IN_SECONDS );
		}

		// Clear is_pro caches
		$products_to_clear = ! empty( $license['products_covered'] ) ? $license['products_covered'] : array( $product );
		self::clear_caches( $products_to_clear );

		return $api_result;
	}

	/**
	 * Get licence info formatted for display
	 *
	 * @param string $product  Product key.
	 * @return array
	 */
	public static function get_license_info( $product ) {
		$license = self::get_license( $product );

		if ( ! $license ) {
			return array(
				'key'        => '',
				'full_key'   => '',
				'status'     => 'inactive',
				'is_pro'     => false,
				'plan_type'  => '',
				'max_sites'  => 1,
				'billing'    => '',
				'last_check' => 0,
				'product'    => $product,
			);
		}

		$key = $license['key'];

		return array(
			'key'              => strlen( $key ) >= 8 ? substr( $key, 0, 4 ) . '-****-****-' . substr( $key, -4 ) : $key,
			'full_key'         => $key,
			'status'           => $license['status'],
			'is_pro'           => $license['status'] === 'valid',
			'plan_type'        => $license['plan_type'],
			'max_sites'        => $license['max_sites'],
			'billing'          => $license['billing'],
			'last_check'       => $license['last_check'],
			'product'          => $product,
			'products_covered' => isset( $license['products_covered'] ) ? $license['products_covered'] : array(),
		);
	}

	// ------------------------------------------------------------------
	//  AJAX handlers
	// ------------------------------------------------------------------

	/**
	 * AJAX: Activate a licence
	 */
	public static function ajax_activate() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		check_ajax_referer( 'skunk_license_nonce', 'nonce' );

		$product     = isset( $_POST['product'] ) ? sanitize_key( $_POST['product'] ) : '';
		$license_key = isset( $_POST['license_key'] ) ? sanitize_text_field( $_POST['license_key'] ) : '';

		if ( empty( $product ) || empty( $license_key ) ) {
			wp_send_json_error( 'Product and license key are required.' );
		}

		$result = self::activate( $product, $license_key );

		if ( $result['success'] ) {
			wp_send_json_success( $result['message'] );
		} else {
			wp_send_json_error( $result['message'] );
		}
	}

	/**
	 * AJAX: Deactivate a licence
	 */
	public static function ajax_deactivate() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		check_ajax_referer( 'skunk_license_nonce', 'nonce' );

		$product = isset( $_POST['product'] ) ? sanitize_key( $_POST['product'] ) : '';

		if ( empty( $product ) ) {
			wp_send_json_error( 'Product is required.' );
		}

		$result = self::deactivate( $product );

		if ( $result['success'] ) {
			wp_send_json_success( $result['message'] );
		} else {
			wp_send_json_error( $result['message'] );
		}
	}

	/**
	 * AJAX: Get licence details for a product
	 */
	public static function ajax_get_details() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Unauthorized' );
		}

		check_ajax_referer( 'skunk_license_nonce', 'nonce' );

		$product = isset( $_POST['product'] ) ? sanitize_key( $_POST['product'] ) : '';

		if ( empty( $product ) ) {
			// Return all products
			$all = array();
			foreach ( self::PRODUCTS as $p ) {
				$all[ $p ] = self::get_license_info( $p );
			}
			wp_send_json_success( $all );
		}

		wp_send_json_success( self::get_license_info( $product ) );
	}

	// ------------------------------------------------------------------
	//  API communication
	// ------------------------------------------------------------------

	/**
	 * Make a request to the licence API
	 *
	 * @param string $action      activate|validate|deactivate
	 * @param string $license_key Key to send.
	 * @return array { success: bool, message: string, data?: array }
	 */
	private static function api_request( $action, $license_key ) {
		if ( empty( $license_key ) ) {
			return array( 'success' => false, 'message' => 'Empty license key.' );
		}

		// Test key always succeeds
		if ( $license_key === self::TEST_KEY ) {
			return array(
				'success' => true,
				'message' => 'Test license ' . $action . 'd.',
				'data'    => array(
					'valid'            => true,
					'product'          => 'bundle',
					'plan_type'        => 'pro_annual',
					'max_sites'        => 999,
					'billing'          => 'annual',
					'products_covered' => array( 'crm', 'forms', 'pages' ),
				),
			);
		}

		// Format validation (skip for deactivate)
		if ( $action !== 'deactivate' && ! preg_match( '/^[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}-[A-Z0-9]{4}$/', $license_key ) ) {
			return array( 'success' => false, 'message' => 'Invalid license key format.' );
		}

		$base_url = self::get_api_base_url();
		$endpoint = $action === 'activate' ? '/api/license/activate' : '/api/license/validate';

		if ( $action === 'deactivate' ) {
			$endpoint = '/api/license/deactivate';
		}

		$site_url = self::get_normalised_site_url();

		$response = wp_remote_post( $base_url . $endpoint, array(
			'body'      => wp_json_encode( array(
				'license_key' => $license_key,
				'site_url'    => $site_url,
			) ),
			'headers'   => array(
				'Content-Type' => 'application/json',
				'User-Agent'   => 'SkunkSuite-WordPress-Plugin',
			),
			'timeout'   => 15,
			'sslverify' => true,
		) );

		if ( is_wp_error( $response ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'Skunk License API Error: ' . $response->get_error_message() );
			}
			return array( 'success' => false, 'message' => 'Unable to connect to license server.' );
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		if ( $code !== 200 || ! $data ) {
			$error = isset( $data['error'] ) ? $data['error'] : ( isset( $data['message'] ) ? $data['message'] : 'License server error.' );
			return array( 'success' => false, 'message' => $error );
		}

		// For activate/validate, check 'valid' field
		if ( $action !== 'deactivate' ) {
			if ( isset( $data['valid'] ) && $data['valid'] ) {
				return array( 'success' => true, 'message' => 'License is valid.', 'data' => $data );
			}
			$msg = isset( $data['message'] ) ? $data['message'] : 'Invalid license key.';
			return array( 'success' => false, 'message' => $msg );
		}

		// For deactivate, check 'success' field
		if ( isset( $data['success'] ) && $data['success'] ) {
			return array( 'success' => true, 'message' => 'License deactivated.' );
		}

		return array( 'success' => false, 'message' => isset( $data['message'] ) ? $data['message'] : 'Deactivation failed.' );
	}

	// ------------------------------------------------------------------
	//  Helpers
	// ------------------------------------------------------------------

	/**
	 * Get all stored licences
	 *
	 * @return array  Associative array keyed by product.
	 */
	private static function get_all_licenses() {
		$raw = get_option( self::OPTION_NAME, array() );
		return is_array( $raw ) ? $raw : array();
	}

	/**
	 * Persist all licences
	 *
	 * @param array $licenses
	 */
	private static function save_all_licenses( $licenses ) {
		update_option( self::OPTION_NAME, $licenses, false );
	}

	/**
	 * Clear is_pro transient caches for given products
	 *
	 * @param array $products
	 */
	private static function clear_caches( $products ) {
		if ( ! is_array( $products ) ) {
			$products = array( $products );
		}
		foreach ( $products as $p ) {
			delete_transient( 'skunk_license_cache_' . md5( sanitize_key( $p ) . '_is_pro' ) );
		}
	}

	/**
	 * Determine API base URL (localhost for dev, production for live)
	 *
	 * @return string
	 */
	private static function get_api_base_url() {
		$site = self::get_normalised_site_url();

		if ( strpos( $site, 'localhost' ) !== false
			|| strpos( $site, '127.0.0.1' ) !== false
			|| strpos( $site, '.local' ) !== false
		) {
			return 'http://localhost:3000';
		}

		return 'https://skunkglobal.com';
	}

	/**
	 * Get normalised site URL (protocol-less, no trailing slash)
	 *
	 * @return string
	 */
	private static function get_normalised_site_url() {
		$url = home_url();
		$url = preg_replace( '#^https?://#', '', $url );
		$url = rtrim( $url, '/' );
		return $url;
	}
}

// Boot AJAX handlers
Skunk_License_Manager::init();
