<?php
/**
 * Skunk Suite Update Checker
 *
 * Centralised update checker for all Skunk plugins. Hooks into WordPress
 * plugin update system and checks registered plugins against the
 * skunkglobal.com update API.
 *
 * Usage from any Skunk plugin:
 *   Skunk_Update_Checker::register( __FILE__, 'crm' );
 *
 * @package SkunkSuiteCore
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'Skunk_Update_Checker' ) ) {
	return;
}

class Skunk_Update_Checker {

	/**
	 * Update API endpoint
	 */
	const API_URL = 'https://skunkglobal.com/api/plugin-updates/check';

	/**
	 * Cache duration (12 hours)
	 */
	const CACHE_TTL = 43200; // 12 * HOUR_IN_SECONDS

	/**
	 * Registered plugins  [ slug => { file, slug, basename, version, product_key } ]
	 *
	 * @var array
	 */
	private static $plugins = array();

	/**
	 * Whether hooks have been wired
	 *
	 * @var bool
	 */
	private static $hooks_registered = false;

	// ------------------------------------------------------------------
	//  Public API
	// ------------------------------------------------------------------

	/**
	 * Register a Skunk plugin for update checks
	 *
	 * @param string $plugin_file  Absolute path to the main plugin file (__FILE__).
	 * @param string $product_key  Product identifier (crm|forms|pages).
	 */
	public static function register( $plugin_file, $product_key ) {
		$plugin_data = get_file_data( $plugin_file, array( 'Version' => 'Version' ) );
		$slug        = basename( dirname( $plugin_file ) );
		$basename    = plugin_basename( $plugin_file );

		self::$plugins[ $slug ] = array(
			'file'        => $plugin_file,
			'slug'        => $slug,
			'basename'    => $basename,
			'version'     => $plugin_data['Version'],
			'product_key' => sanitize_key( $product_key ),
		);

		// Also register the Pro companion if it exists
		$pro_slugs = self::get_pro_slug_map();
		if ( isset( $pro_slugs[ $product_key ] ) ) {
			$pro_slug    = $pro_slugs[ $product_key ];
			$pro_file    = WP_PLUGIN_DIR . '/' . $pro_slug . '/' . $pro_slug . '.php';

			if ( file_exists( $pro_file ) ) {
				$pro_data = get_file_data( $pro_file, array( 'Version' => 'Version' ) );
				self::$plugins[ $pro_slug ] = array(
					'file'        => $pro_file,
					'slug'        => $pro_slug,
					'basename'    => plugin_basename( $pro_file ),
					'version'     => $pro_data['Version'],
					'product_key' => $product_key,
				);
			}
		}

		// Wire hooks once
		self::maybe_register_hooks();
	}

	// ------------------------------------------------------------------
	//  Hooks
	// ------------------------------------------------------------------

	/**
	 * Register WP hooks (once)
	 */
	private static function maybe_register_hooks() {
		if ( self::$hooks_registered ) {
			return;
		}
		self::$hooks_registered = true;

		add_filter( 'pre_set_site_transient_update_plugins', array( __CLASS__, 'check_updates' ) );
		add_filter( 'plugins_api', array( __CLASS__, 'plugin_info' ), 20, 3 );
		add_filter( 'site_transient_update_plugins', array( __CLASS__, 'filter_update_transient' ) );
		add_action( 'upgrader_process_complete', array( __CLASS__, 'after_update' ), 10, 2 );
	}

	/**
	 * Inject update info into the WP update transient
	 *
	 * @param object $transient
	 * @return object
	 */
	public static function check_updates( $transient ) {
		if ( ! is_object( $transient ) ) {
			$transient = new stdClass();
		}
		if ( ! isset( $transient->response ) ) {
			$transient->response = array();
		}
		if ( ! isset( $transient->no_update ) ) {
			$transient->no_update = array();
		}

		foreach ( self::$plugins as $plugin ) {
			$response = self::get_update_info( $plugin );

			if ( ! $response || ! isset( $response->new_version ) ) {
				continue;
			}

			$basename = $plugin['basename'];

			if ( version_compare( $plugin['version'], $response->new_version, '<' ) ) {
				$transient->response[ $basename ] = self::build_update_object( $plugin, $response );
				unset( $transient->no_update[ $basename ] );
			} else {
				unset( $transient->response[ $basename ] );
				$transient->no_update[ $basename ] = (object) array(
					'slug'        => $plugin['slug'],
					'plugin'      => $basename,
					'new_version' => $plugin['version'],
				);
			}
		}

		return $transient;
	}

	/**
	 * Provide plugin info for the WP plugins_api details popup
	 *
	 * @param mixed  $result
	 * @param string $action
	 * @param object $args
	 * @return mixed
	 */
	public static function plugin_info( $result, $action, $args ) {
		if ( $action !== 'plugin_information' || ! isset( $args->slug ) ) {
			return $result;
		}

		if ( ! isset( self::$plugins[ $args->slug ] ) ) {
			return $result;
		}

		$plugin   = self::$plugins[ $args->slug ];
		$response = self::api_request( $plugin );

		if ( ! $response ) {
			return $result;
		}

		return (object) array(
			'name'         => isset( $response->name ) ? $response->name : $plugin['slug'],
			'slug'         => $plugin['slug'],
			'version'      => isset( $response->new_version ) ? $response->new_version : $plugin['version'],
			'author'       => isset( $response->author ) ? $response->author : 'Skunk Global',
			'homepage'     => isset( $response->homepage ) ? $response->homepage : 'https://skunkglobal.com',
			'requires'     => isset( $response->requires ) ? $response->requires : '6.0',
			'tested'       => isset( $response->tested ) ? $response->tested : '6.8.3',
			'requires_php' => isset( $response->requires_php ) ? $response->requires_php : '7.4',
			'last_updated' => isset( $response->last_updated ) ? $response->last_updated : gmdate( 'Y-m-d' ),
			'sections'     => array(
				'description' => isset( $response->description ) ? $response->description : '',
				'changelog'   => isset( $response->changelog ) ? $response->changelog : '',
			),
			'banners'      => isset( $response->banners ) ? (array) $response->banners : array(),
			'icons'        => isset( $response->icons ) ? (array) $response->icons : array(),
		);
	}

	/**
	 * Filter the update transient on read to remove stale entries
	 *
	 * @param object $transient
	 * @return object
	 */
	public static function filter_update_transient( $transient ) {
		if ( ! is_object( $transient ) || ! isset( $transient->response ) ) {
			return $transient;
		}

		foreach ( self::$plugins as $plugin ) {
			$basename = $plugin['basename'];

			if ( isset( $transient->response[ $basename ] ) ) {
				$remote_version  = isset( $transient->response[ $basename ]->new_version ) ? $transient->response[ $basename ]->new_version : '';
				$current_version = $plugin['version'];

				if ( version_compare( $current_version, $remote_version, '>=' ) ) {
					unset( $transient->response[ $basename ] );
					$transient->no_update[ $basename ] = (object) array(
						'slug'        => $plugin['slug'],
						'plugin'      => $basename,
						'new_version' => $current_version,
					);
				}
			}
		}

		return $transient;
	}

	/**
	 * Clear caches after one of our plugins is updated
	 *
	 * @param object $upgrader
	 * @param array  $options
	 */
	public static function after_update( $upgrader, $options ) {
		if ( ! isset( $options['action'] ) || $options['action'] !== 'update' || ! isset( $options['type'] ) || $options['type'] !== 'plugin' ) {
			return;
		}

		$updated_plugins = isset( $options['plugins'] ) ? $options['plugins'] : array();
		$our_basenames   = wp_list_pluck( self::$plugins, 'basename' );

		foreach ( $our_basenames as $basename ) {
			if ( in_array( $basename, $updated_plugins, true ) ) {
				self::clear_update_cache();
				wp_schedule_single_event( time() + 5, 'wp_update_plugins' );
				break;
			}
		}
	}

	// ------------------------------------------------------------------
	//  Internal
	// ------------------------------------------------------------------

	/**
	 * Get cached or fresh update info for a plugin
	 *
	 * @param array $plugin
	 * @return object|false
	 */
	private static function get_update_info( $plugin ) {
		$cache_key = 'skunk_update_' . $plugin['slug'];

		// Force-check on update-core or plugins screen
		$force = isset( $_GET['force-check'] )
			|| ( isset( $_SERVER['REQUEST_URI'] ) && strpos( $_SERVER['REQUEST_URI'], 'update-core.php' ) !== false );

		if ( $force ) {
			delete_transient( $cache_key );
		}

		$cached = get_transient( $cache_key );

		// Re-fetch if cached response has no package (may be stale)
		if ( false !== $cached && isset( $cached->update_available ) && $cached->update_available && empty( $cached->package ) ) {
			delete_transient( $cache_key );
			$cached = false;
		}

		if ( false !== $cached ) {
			return $cached;
		}

		$response = self::api_request( $plugin );

		if ( $response ) {
			set_transient( $cache_key, $response, self::CACHE_TTL );
		}

		return $response;
	}

	/**
	 * Call the update check API
	 *
	 * @param array $plugin
	 * @return object|false
	 */
	private static function api_request( $plugin ) {
		// Determine API URL (use localhost for dev)
		$api_url = self::API_URL;
		$site    = home_url();
		if ( strpos( $site, 'localhost' ) !== false || strpos( $site, '127.0.0.1' ) !== false || strpos( $site, '.local' ) !== false ) {
			$api_url = 'http://localhost:3000/api/plugin-updates/check';
		}

		// Get licence key for this product
		$license_key = '';
		if ( class_exists( 'Skunk_License_Manager' ) ) {
			$license = Skunk_License_Manager::get_license( $plugin['product_key'] );
			if ( $license && ! empty( $license['key'] ) ) {
				$license_key = $license['key'];
			}
		}

		$response = wp_remote_post( $api_url, array(
			'timeout' => 15,
			'headers' => array( 'Content-Type' => 'application/json' ),
			'body'    => wp_json_encode( array(
				'plugin_slug'     => $plugin['slug'],
				'current_version' => $plugin['version'],
				'site_url'        => get_site_url(),
				'license_key'     => $license_key,
			) ),
		) );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		return json_decode( wp_remote_retrieve_body( $response ) );
	}

	/**
	 * Build WP-compatible update object
	 *
	 * @param array  $plugin
	 * @param object $response  API response.
	 * @return object
	 */
	private static function build_update_object( $plugin, $response ) {
		$package = isset( $response->package ) && ! empty( $response->package ) ? $response->package : '';

		// Only deliver package URL for pro plugins when licence is valid
		$is_pro_slug = self::is_pro_slug( $plugin['slug'] );
		if ( $is_pro_slug && class_exists( 'Skunk_License_Manager' ) ) {
			if ( ! Skunk_License_Manager::is_pro( $plugin['product_key'] ) ) {
				$package = ''; // No download without valid licence
			}
		}

		// Append licence key to package URL if available
		if ( $package ) {
			$license_key = '';
			if ( class_exists( 'Skunk_License_Manager' ) ) {
				$license = Skunk_License_Manager::get_license( $plugin['product_key'] );
				if ( $license && ! empty( $license['key'] ) ) {
					$license_key = $license['key'];
				}
			}
			if ( $license_key ) {
				$package = add_query_arg( 'license_key', $license_key, $package );
			}
		}

		return (object) array(
			'slug'           => $plugin['slug'],
			'plugin'         => $plugin['basename'],
			'new_version'    => $response->new_version,
			'url'            => isset( $response->url ) ? $response->url : 'https://skunkglobal.com',
			'package'        => $package,
			'tested'         => isset( $response->tested ) ? $response->tested : '',
			'requires'       => isset( $response->requires ) ? $response->requires : '',
			'requires_php'   => isset( $response->requires_php ) ? $response->requires_php : '',
			'upgrade_notice' => isset( $response->upgrade_notice ) ? $response->upgrade_notice : '',
			'icons'          => isset( $response->icons ) ? (array) $response->icons : array(),
		);
	}

	/**
	 * Check if a slug is a pro plugin
	 *
	 * @param string $slug
	 * @return bool
	 */
	private static function is_pro_slug( $slug ) {
		$pro_slugs = array( 'skunkcrm-pro', 'skunkforms-pro', 'skunkpages-pro' );
		return in_array( $slug, $pro_slugs, true );
	}

	/**
	 * Map product keys to their pro plugin slugs
	 *
	 * @return array
	 */
	private static function get_pro_slug_map() {
		return array(
			'crm'   => 'skunkcrm-pro',
			'forms' => 'skunkforms-pro',
			'pages' => 'skunkpages-pro',
		);
	}

	/**
	 * Clear all Skunk update transients
	 */
	public static function clear_update_cache() {
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_skunk_update_%'" );
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_skunk_update_%'" );
		delete_site_transient( 'update_plugins' );
	}
}
