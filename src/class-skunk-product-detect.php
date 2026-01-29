<?php
/**
 * Skunk Suite Product Detection
 *
 * Detects whether each Skunk product is active, installed but inactive, or missing.
 *
 * @package SkunkSuiteCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'Skunk_Product_Detect' ) ) {
	return;
}

class Skunk_Product_Detect {

	/**
	 * Product definitions
	 *
	 * @var array
	 */
	private static $products = array(
		'crm' => array(
			'name'         => 'SkunkCRM',
			'desc'         => 'Contacts, deals & pipeline',
			'icon'         => 'users',
			'color'        => '#E50914',
			'menu_slug'    => 'skunkcrm-contacts',
			'constants'    => array( 'SKUNKCRM_VERSION' ),
			'classes'      => array( 'SkunkCRM' ),
			'plugin_slugs' => array( 'skunkcrm/skunkcrm.php', 'skunkcrm-plugin/skunkcrm.php' ),
			'landing'      => 'https://skunkcrm.com',
		),
		'forms' => array(
			'name'         => 'Skunk Forms',
			'desc'         => 'Form builder & lead capture',
			'icon'         => 'forms',
			'color'        => '#3B82F6',
			'menu_slug'    => 'skunkforms',
			'constants'    => array( 'SKUNKFORMS_VERSION' ),
			'classes'      => array( 'SkunkForms' ),
			'plugin_slugs' => array( 'skunkforms/skunkforms.php', 'skunkforms-free/skunkforms.php', 'skunkforms-free-plugin/skunkforms.php' ),
			'landing'      => 'https://skunkforms.com',
		),
		'pages' => array(
			'name'         => 'Skunk Pages',
			'desc'         => 'Landing page templates',
			'icon'         => 'pages',
			'color'        => '#8B5CF6',
			'menu_slug'    => 'skunk-pages',
			'constants'    => array( 'SKUNKPAGES_FREE_VERSION', 'SKUNKPAGES_VERSION' ),
			'classes'      => array( 'SkunkPages_Free' ),
			'plugin_slugs' => array( 'skunkpages/skunkpages.php', 'skunkpages-free/skunkpages.php', 'skunkpages-free-plugin/skunkpages.php' ),
			'landing'      => 'https://skunkpages.com',
		),
	);

	/**
	 * Get product info
	 *
	 * @param string $key Product key (crm, forms, pages)
	 * @return array|null
	 */
	public static function get_product( $key ) {
		return isset( self::$products[ $key ] ) ? self::$products[ $key ] : null;
	}

	/**
	 * Get all product definitions
	 *
	 * @return array
	 */
	public static function get_all_products() {
		return self::$products;
	}

	/**
	 * Detect a product's install state
	 *
	 * @param string $key Product key (crm, forms, pages)
	 * @return array { state: 'active'|'installed'|'missing', url: string }
	 */
	public static function detect( $key ) {
		if ( ! isset( self::$products[ $key ] ) ) {
			return array( 'state' => 'missing', 'url' => '#' );
		}

		$info = self::$products[ $key ];

		// Check if active
		foreach ( $info['constants'] as $const ) {
			if ( defined( $const ) ) {
				return array( 'state' => 'active', 'url' => '' );
			}
		}
		foreach ( $info['classes'] as $cls ) {
			if ( class_exists( $cls ) ) {
				return array( 'state' => 'active', 'url' => '' );
			}
		}

		// Check if installed but inactive
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		$all_plugins = get_plugins();

		foreach ( $info['plugin_slugs'] as $slug ) {
			if ( isset( $all_plugins[ $slug ] ) ) {
				$activate_url = wp_nonce_url(
					admin_url( 'plugins.php?action=activate&plugin=' . urlencode( $slug ) ),
					'activate-plugin_' . $slug
				);
				return array( 'state' => 'installed', 'url' => $activate_url );
			}
		}

		// Not installed
		return array( 'state' => 'missing', 'url' => $info['landing'] );
	}

	/**
	 * Check if a product is active
	 *
	 * @param string $key Product key
	 * @return bool
	 */
	public static function is_active( $key ) {
		$state = self::detect( $key );
		return $state['state'] === 'active';
	}

	/**
	 * Get map of all product states
	 *
	 * @return array { crm: bool, forms: bool, pages: bool }
	 */
	public static function get_active_map() {
		return array(
			'crm'   => self::is_active( 'crm' ),
			'forms' => self::is_active( 'forms' ),
			'pages' => self::is_active( 'pages' ),
		);
	}
}
