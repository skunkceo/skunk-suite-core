<?php
/**
 * Skunk Suite Core - Bootstrap Loader
 *
 * Include this file from any Skunk plugin to bootstrap the shared suite functionality.
 * Uses class_exists() guards so the first plugin to load wins.
 *
 * Usage:
 *   require_once __DIR__ . '/includes/suite/loader.php';
 *
 * @package SkunkSuiteCore
 * @version 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Version of this suite-core package
if ( ! defined( 'SKUNK_SUITE_CORE_VERSION' ) ) {
	define( 'SKUNK_SUITE_CORE_VERSION', '1.0.0' );
}

// Resolve the directory this loader lives in
$skunk_suite_core_dir = dirname( __FILE__ ) . '/src/';

// Load classes with class_exists guards (first plugin to load wins)
if ( ! class_exists( 'Skunk_Icons' ) ) {
	require_once $skunk_suite_core_dir . 'class-skunk-icons.php';
}

if ( ! class_exists( 'Skunk_Product_Detect' ) ) {
	require_once $skunk_suite_core_dir . 'class-skunk-product-detect.php';
}

if ( ! class_exists( 'Skunk_Dashboard' ) ) {
	require_once $skunk_suite_core_dir . 'class-skunk-dashboard.php';
}

if ( ! class_exists( 'Skunk_Masterbar' ) ) {
	require_once $skunk_suite_core_dir . 'class-skunk-masterbar.php';
}

if ( ! class_exists( 'Skunk_Menu' ) ) {
	require_once $skunk_suite_core_dir . 'class-skunk-menu.php';
}

if ( ! class_exists( 'Skunk_License_Manager' ) ) {
	require_once $skunk_suite_core_dir . 'class-skunk-license-manager.php';
}

// Always boot license AJAX handlers (class file may skip init() on re-include)
if ( class_exists( 'Skunk_License_Manager' ) ) {
	Skunk_License_Manager::init();
}

if ( ! class_exists( 'Skunk_Update_Checker' ) ) {
	require_once $skunk_suite_core_dir . 'class-skunk-update-checker.php';
}

// Register the AJAX handler for NUX completion (only once)
if ( ! has_action( 'wp_ajax_skunk_complete_nux', array( 'Skunk_Dashboard', 'ajax_complete_nux' ) ) ) {
	add_action( 'wp_ajax_skunk_complete_nux', array( 'Skunk_Dashboard', 'ajax_complete_nux' ) );
}

// Handle suite-level activation redirect (for NUX)
if ( ! has_action( 'admin_init', array( 'Skunk_Dashboard', 'maybe_redirect_to_nux' ) ) ) {
	add_action( 'admin_init', array( 'Skunk_Dashboard', 'maybe_redirect_to_nux' ), 1 );
}

unset( $skunk_suite_core_dir );
