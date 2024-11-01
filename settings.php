<?php

require_once SKFOM_PLUGIN_DIR . '/includes/functions.php';
require_once SKFOM_PLUGIN_DIR . '/includes/l10n.php';
require_once SKFOM_PLUGIN_DIR . '/includes/formatting.php';
require_once SKFOM_PLUGIN_DIR . '/includes/pipe.php';
require_once SKFOM_PLUGIN_DIR . '/includes/shortcodes.php';
require_once SKFOM_PLUGIN_DIR . '/includes/capabilities.php';
require_once SKFOM_PLUGIN_DIR . '/includes/contact-form-template.php';
require_once SKFOM_PLUGIN_DIR . '/includes/contact-form.php';
require_once SKFOM_PLUGIN_DIR . '/includes/mail.php';
require_once SKFOM_PLUGIN_DIR . '/includes/submission.php';
require_once SKFOM_PLUGIN_DIR . '/includes/upgrade.php';
require_once SKFOM_PLUGIN_DIR . '/includes/integration.php';
require_once SKFOM_PLUGIN_DIR . '/includes/config-validator.php';

if ( is_admin() ) {
	require_once SKFOM_PLUGIN_DIR . '/admin/admin.php';
} else {
	require_once SKFOM_PLUGIN_DIR . '/includes/controller.php';
}

class SKFOM {

	public static function load_modules() {
		self::load_module( 'acceptance' );
		self::load_module( 'akismet' );
		self::load_module( 'checkbox' );
		self::load_module( 'count' );
		self::load_module( 'date' );
		self::load_module( 'file' );
		self::load_module( 'flamingo' );
		self::load_module( 'listo' );
		self::load_module( 'number' );
		self::load_module( 'quiz' );
		self::load_module( 'really-simple-captcha' );
		self::load_module( 'recaptcha' );
		self::load_module( 'response' );
		self::load_module( 'select' );
		self::load_module( 'submit' );
		self::load_module( 'text' );
		self::load_module( 'textarea' );
	}

	protected static function load_module( $mod ) {
		$dir = SKFOM_PLUGIN_MODULES_DIR;

		if ( empty( $dir ) || ! is_dir( $dir ) ) {
			return false;
		}

		$file = path_join( $dir, $mod . '.php' );

		if ( file_exists( $file ) ) {
			include_once $file;
		}
	}

	public static function get_option( $name, $default = false ) {
		$option = get_option( 'skfom' );

		if ( false === $option ) {
			return $default;
		}

		if ( isset( $option[$name] ) ) {
			return $option[$name];
		} else {
			return $default;
		}
	}

	public static function update_option( $name, $value ) {
		$option = get_option( 'skfom' );
		$option = ( false === $option ) ? array() : (array) $option;
		$option = array_merge( $option, array( $name => $value ) );
		update_option( 'skfom', $option );
	}
}

add_action( 'plugins_loaded', 'skfom' );

function skfom() {
	skfom_load_textdomain();
	SKFOM::load_modules();

	/* Shortcodes */
	add_shortcode( 'sketchus-custom-form', 'skfom_contact_form_tag_func' );
	add_shortcode( 'contact-form', 'skfom_contact_form_tag_func' );
}

add_action( 'init', 'skfom_init' );

function skfom_init() {
	skfom_get_request_uri();
	skfom_register_post_types();

	do_action( 'skfom_init' );
}

add_action( 'admin_init', 'skfom_upgrade' );

function skfom_upgrade() {
	$old_ver = SKFOM::get_option( 'version', '0' );
	$new_ver = SKFOM_VERSION;

	if ( $old_ver == $new_ver ) {
		return;
	}

	do_action( 'skfom_upgrade', $new_ver, $old_ver );

	SKFOM::update_option( 'version', $new_ver );
}

/* Install and default settings */

add_action( 'activate_' . SKFOM_PLUGIN_BASENAME, 'skfom_install' );

function skfom_install() {
	if ( $opt = get_option( 'skfom' ) ) {
		return;
	}

	skfom_load_textdomain();
	skfom_register_post_types();
	skfom_upgrade();

	if ( get_posts( array( 'post_type' => 'skfom_contact_form' ) ) ) {
		return;
	}

	$contact_form = SKFOM_ContactForm::get_template( array(
		'title' => sprintf( __( 'Contact form %d', 'sketchus-custom-form' ), 1 ) ) );

	$contact_form->save();

	SKFOM::update_option( 'bulk_validate', array(
		'timestamp' => current_time( 'timestamp' ),
		'version' => SKFOM_VERSION,
		'count_valid' => 1,
		'count_invalid' => 0 ) );
}
