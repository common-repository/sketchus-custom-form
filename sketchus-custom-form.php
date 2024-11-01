<?php
/*
Plugin Name: SketchUs Custom Form
Plugin URI: http://dev.sketchus.com/custom-form/
Description: Just another contact form plugin. Simple but flexible.
Author: Pritpal Singh
Author URI: http://dev.sketchus.com/custom-form/
Text Domain: sketchus-custom-form
Version: 1.0.0
*/

define( 'SKFOM_VERSION', '4.4.1' );

define( 'SKFOM_REQUIRED_WP_VERSION', '4.3' );

define( 'SKFOM_PLUGIN', __FILE__ );

define( 'SKFOM_PLUGIN_BASENAME', plugin_basename( SKFOM_PLUGIN ) );

define( 'SKFOM_PLUGIN_NAME', trim( dirname( SKFOM_PLUGIN_BASENAME ), '/' ) );

define( 'SKFOM_PLUGIN_DIR', untrailingslashit( dirname( SKFOM_PLUGIN ) ) );

define( 'SKFOM_PLUGIN_MODULES_DIR', SKFOM_PLUGIN_DIR . '/modules' );

if ( ! defined( 'SKFOM_LOAD_JS' ) ) {
	define( 'SKFOM_LOAD_JS', true );
}

if ( ! defined( 'SKFOM_LOAD_CSS' ) ) {
	define( 'SKFOM_LOAD_CSS', true );
}

if ( ! defined( 'SKFOM_AUTOP' ) ) {
	define( 'SKFOM_AUTOP', true );
}

if ( ! defined( 'SKFOM_USE_PIPE' ) ) {
	define( 'SKFOM_USE_PIPE', true );
}

if ( ! defined( 'SKFOM_ADMIN_READ_CAPABILITY' ) ) {
	define( 'SKFOM_ADMIN_READ_CAPABILITY', 'edit_posts' );
}

if ( ! defined( 'SKFOM_ADMIN_READ_WRITE_CAPABILITY' ) ) {
	define( 'SKFOM_ADMIN_READ_WRITE_CAPABILITY', 'publish_pages' );
}

if ( ! defined( 'SKFOM_VERIFY_NONCE' ) ) {
	define( 'SKFOM_VERIFY_NONCE', true );
}

if ( ! defined( 'SKFOM_USE_REALLY_SIMPLE_CAPTCHA' ) ) {
	define( 'SKFOM_USE_REALLY_SIMPLE_CAPTCHA', false );
}

if ( ! defined( 'SKFOM_VALIDATE_CONFIGURATION' ) ) {
	define( 'SKFOM_VALIDATE_CONFIGURATION', true );
}

define( 'SKFOM_PLUGIN_URL', untrailingslashit( plugins_url( '', SKFOM_PLUGIN ) ) );

require_once SKFOM_PLUGIN_DIR . '/settings.php';
