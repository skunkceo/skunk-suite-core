<?php
/**
 * Skunk Suite Menu - Unified WordPress admin menu registration
 *
 * Provides the shared "Skunk" top-level menu and allows each plugin
 * to register its own submenu items underneath it.
 *
 * @package SkunkSuiteCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'Skunk_Menu' ) ) {
	return;
}

class Skunk_Menu {

	/**
	 * Main menu slug — shared across all Skunk plugins
	 */
	const MENU_SLUG = 'skunk-dashboard';

	/**
	 * Capability required to see the menu
	 */
	const CAPABILITY = 'manage_options';

	/**
	 * Registered plugins
	 *
	 * @var array
	 */
	private static $plugins = array();

	/**
	 * Whether hooks have been attached
	 *
	 * @var bool
	 */
	private static $hooked = false;

	/**
	 * Register a plugin with the suite menu
	 *
	 * @param string $id      Plugin identifier (crm, forms, pages)
	 * @param array  $config  {
	 *     @type string   $name       Display name
	 *     @type string   $slug       Submenu page slug
	 *     @type callable $callback   Page render callback
	 *     @type int      $position   Menu position (lower = higher in list)
	 *     @type string   $capability Required capability (default: manage_options)
	 *     @type array    $subpages   Optional hidden sub-pages array of arrays:
	 *                                { title, slug, callback, capability }
	 * }
	 */
	public static function register_plugin( $id, $config ) {
		$config = wp_parse_args( $config, array(
			'name'       => ucfirst( $id ),
			'slug'       => '',
			'callback'   => '',
			'position'   => 50,
			'capability' => self::CAPABILITY,
			'subpages'   => array(),
		) );

		self::$plugins[ $id ] = $config;

		// Attach hooks once
		if ( ! self::$hooked ) {
			self::$hooked = true;
			add_action( 'admin_menu', array( __CLASS__, 'build_menu' ), 5 );
		}
	}

	/**
	 * Get registered plugins
	 *
	 * @return array
	 */
	public static function get_plugins() {
		return self::$plugins;
	}

	/**
	 * Build the admin menu
	 *
	 * Called at admin_menu priority 5 (early) so plugins adding submenus at
	 * default priority 10 can add under this parent.
	 */
	public static function build_menu() {
		// Set global flag so plugins know unified menu is active
		if ( ! defined( 'SKUNK_UNIFIED_MENU' ) ) {
			define( 'SKUNK_UNIFIED_MENU', true );
		}

		// SVG icon for the menu
		$icon_svg = 'data:image/svg+xml;base64,' . base64_encode(
			'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><text x="50%" y="50%" dominant-baseline="central" text-anchor="middle" font-family="Arial, sans-serif" font-weight="bold" font-size="14" fill="currentColor">S</text></svg>'
		);

		// Main menu page — renders the suite dashboard
		add_menu_page(
			'Skunk Suite',
			'Skunk',
			self::CAPABILITY,
			self::MENU_SLUG,
			array( 'Skunk_Dashboard', 'render' ),
			$icon_svg,
			30
		);

		// Dashboard submenu (replaces auto-generated duplicate)
		add_submenu_page(
			self::MENU_SLUG,
			'Dashboard',
			'Dashboard',
			self::CAPABILITY,
			self::MENU_SLUG,
			array( 'Skunk_Dashboard', 'render' )
		);

		// Sort registered plugins by position
		$sorted = self::$plugins;
		uasort( $sorted, function( $a, $b ) {
			return $a['position'] - $b['position'];
		} );

		// Add each plugin's submenu
		foreach ( $sorted as $id => $config ) {
			if ( ! empty( $config['slug'] ) && ! empty( $config['callback'] ) ) {
				add_submenu_page(
					self::MENU_SLUG,
					$config['name'],
					$config['name'],
					$config['capability'],
					$config['slug'],
					$config['callback']
				);
			}

			// Register hidden sub-pages
			if ( ! empty( $config['subpages'] ) ) {
				foreach ( $config['subpages'] as $sub ) {
					$sub = wp_parse_args( $sub, array(
						'title'      => '',
						'slug'       => '',
						'callback'   => '',
						'capability' => $config['capability'],
					) );

					if ( $sub['slug'] && $sub['callback'] ) {
						add_submenu_page(
							null, // Hidden
							$sub['title'],
							$sub['title'],
							$sub['capability'],
							$sub['slug'],
							$sub['callback']
						);
					}
				}
			}
		}

		// Settings — always last
		add_submenu_page(
			self::MENU_SLUG,
			'Settings',
			'Settings',
			self::CAPABILITY,
			'skunk-settings',
			array( 'Skunk_Dashboard', 'render_settings' )
		);
	}

	/**
	 * Get the main menu slug
	 *
	 * @return string
	 */
	public static function get_menu_slug() {
		return self::MENU_SLUG;
	}
}
