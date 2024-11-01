<?php

add_action( 'wp_loaded', 'skfom_control_init' );

function skfom_control_init() {
	if ( ! isset( $_SERVER['REQUEST_METHOD'] ) ) {
		return;
	}

	if ( 'GET' == $_SERVER['REQUEST_METHOD'] ) {
		if ( isset( $_GET['_skfom_is_ajax_call'] ) ) {
			skfom_ajax_onload();
		}
	}

	if ( 'POST' == $_SERVER['REQUEST_METHOD'] ) {
		if ( isset( $_POST['_skfom_is_ajax_call'] ) ) {
			skfom_ajax_json_echo();
		}

		skfom_submit_nonajax();
	}
}

function skfom_ajax_onload() {
	$echo = '';
	$items = array();

	if ( isset( $_GET['_skfom'] )
	&& $contact_form = skfom_contact_form( (int) $_GET['_skfom'] ) ) {
		$items = apply_filters( 'skfom_ajax_onload', $items );
	}

	$echo = wp_json_encode( $items );

	if ( skfom_is_xhr() ) {
		@header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
		echo $echo;
	}

	exit();
}

function skfom_ajax_json_echo() {
	$echo = '';

	if ( isset( $_POST['_skfom'] ) ) {
		$id = (int) $_POST['_skfom'];
		$unit_tag = skfom_sanitize_unit_tag( $_POST['_skfom_unit_tag'] );

		if ( $contact_form = skfom_contact_form( $id ) ) {
			$items = array(
				'mailSent' => false,
				'into' => '#' . $unit_tag,
				'captcha' => null );

			$result = $contact_form->submit( true );

			if ( ! empty( $result['message'] ) ) {
				$items['message'] = $result['message'];
			}

			if ( 'mail_sent' == $result['status'] ) {
				$items['mailSent'] = true;
			}

			if ( 'validation_failed' == $result['status'] ) {
				$invalids = array();

				foreach ( $result['invalid_fields'] as $name => $field ) {
					$invalids[] = array(
						'into' => 'span.skfom-form-control-wrap.'
							. sanitize_html_class( $name ),
						'message' => $field['reason'],
						'idref' => $field['idref'] );
				}

				$items['invalids'] = $invalids;
			}

			if ( 'spam' == $result['status'] ) {
				$items['spam'] = true;
			}

			if ( ! empty( $result['scripts_on_sent_ok'] ) ) {
				$items['onSentOk'] = $result['scripts_on_sent_ok'];
			}

			if ( ! empty( $result['scripts_on_submit'] ) ) {
				$items['onSubmit'] = $result['scripts_on_submit'];
			}

			$items = apply_filters( 'skfom_ajax_json_echo', $items, $result );
		}
	}

	$echo = wp_json_encode( $items );

	if ( skfom_is_xhr() ) {
		@header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ) );
		echo $echo;
	} else {
		@header( 'Content-Type: text/html; charset=' . get_option( 'blog_charset' ) );
		echo '<textarea>' . $echo . '</textarea>';
	}

	exit();
}

function skfom_is_xhr() {
	if ( ! isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) )
		return false;

	return $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest';
}

function skfom_submit_nonajax() {
	if ( ! isset( $_POST['_skfom'] ) )
		return;

	if ( $contact_form = skfom_contact_form( (int) $_POST['_skfom'] ) ) {
		$contact_form->submit();
	}
}

add_filter( 'widget_text', 'skfom_widget_text_filter', 9 );

function skfom_widget_text_filter( $content ) {
	if ( ! preg_match( '/\[[\r\n\t ]*sketchus-custom(-form)?[\r\n\t ].*?\]/', $content ) )
		return $content;

	$content = do_shortcode( $content );

	return $content;
}

add_action( 'wp_enqueue_scripts', 'skfom_do_enqueue_scripts' );

function skfom_do_enqueue_scripts() {
	if ( skfom_load_js() ) {
		skfom_enqueue_scripts();
	}

	if ( skfom_load_css() ) {
		skfom_enqueue_styles();
	}
}

function skfom_enqueue_scripts() {
	// jquery.form.js originally bundled with WordPress is out of date and deprecated
	// so we need to deregister it and re-register the latest one
	wp_deregister_script( 'jquery-form' );
	wp_register_script( 'jquery-form',
		skfom_plugin_url( 'includes/js/jquery.form.min.js' ),
		array( 'jquery' ), '3.51.0-2014.06.20', true );

	$in_footer = true;

	if ( 'header' === skfom_load_js() ) {
		$in_footer = false;
	}

	wp_enqueue_script( 'sketchus-custom-form',
		skfom_plugin_url( 'includes/js/scripts.js' ),
		array( 'jquery', 'jquery-form' ), SKFOM_VERSION, $in_footer );

	$_skfom = array(
		'loaderUrl' => skfom_ajax_loader(),
		'recaptchaEmpty' =>
			__( 'Please verify that you are not a robot.', 'sketchus-custom-form' ),
		'sending' => __( 'Sending ...', 'sketchus-custom-form' ) );

	if ( defined( 'WP_CACHE' ) && WP_CACHE ) {
		$_skfom['cached'] = 1;
	}

	if ( skfom_support_html5_fallback() ) {
		$_skfom['jqueryUi'] = 1;
	}

	wp_localize_script( 'sketchus-custom-form', '_skfom', $_skfom );

	do_action( 'skfom_enqueue_scripts' );
}

function skfom_script_is() {
	return wp_script_is( 'sketchus-custom-form' );
}

function skfom_enqueue_styles() {
	wp_enqueue_style( 'sketchus-custom-form',
		skfom_plugin_url( 'includes/css/styles.css' ),
		array(), SKFOM_VERSION, 'all' );

	if ( skfom_is_rtl() ) {
		wp_enqueue_style( 'sketchus-custom-form-rtl',
			skfom_plugin_url( 'includes/css/styles-rtl.css' ),
			array(), SKFOM_VERSION, 'all' );
	}

	do_action( 'skfom_enqueue_styles' );
}

function skfom_style_is() {
	return wp_style_is( 'sketchus-custom-form' );
}

/* HTML5 Fallback */

add_action( 'wp_enqueue_scripts', 'skfom_html5_fallback', 20 );

function skfom_html5_fallback() {
	if ( ! skfom_support_html5_fallback() ) {
		return;
	}

	if ( skfom_script_is() ) {
		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_enqueue_script( 'jquery-ui-spinner' );
	}

	if ( skfom_style_is() ) {
		wp_enqueue_style( 'jquery-ui-smoothness',
			skfom_plugin_url( 'includes/js/jquery-ui/themes/smoothness/jquery-ui.min.css' ), array(), '1.10.3', 'screen' );
	}
}
