<?php

class SKFOM_ContactFormTemplate {

	public static function get_default( $prop = 'form' ) {
		if ( 'form' == $prop ) {
			$template = self::form();
		} elseif ( 'mail' == $prop ) {
			$template = self::mail();
		} elseif ( 'mail_2' == $prop ) {
			$template = self::mail_2();
		} elseif ( 'messages' == $prop ) {
			$template = self::messages();
		} else {
			$template = null;
		}

		return apply_filters( 'skfom_default_template', $template, $prop );
	}

	public static function form() {
		$template =
			'<p>' . __( 'Your Name', 'sketchus-custom-form' )
			. ' ' . __( '(required)', 'sketchus-custom-form' ) . '<br />' . "\n"
			. '    [text* your-name] </p>' . "\n\n"
			. '<p>' . __( 'Your Email', 'sketchus-custom-form' )
			. ' ' . __( '(required)', 'sketchus-custom-form' ) . '<br />' . "\n"
			. '    [email* your-email] </p>' . "\n\n"
			. '<p>' . __( 'Subject', 'sketchus-custom-form' ) . '<br />' . "\n"
			. '    [text your-subject] </p>' . "\n\n"
			. '<p>' . __( 'Your Message', 'sketchus-custom-form' ) . '<br />' . "\n"
			. '    [textarea your-message] </p>' . "\n\n"
			. '<p>[submit "' . __( 'Send', 'sketchus-custom-form' ) . '"]</p>';

		return $template;
	}

	public static function mail() {
		$template = array(
			'subject' => sprintf(
				_x( '%1$s "%2$s"', 'mail subject', 'sketchus-custom-form' ),
				get_bloginfo( 'name' ), '[your-subject]' ),
			'sender' => sprintf( '[your-name] <%s>', self::from_email() ),
			'body' =>
				sprintf( __( 'From: %s', 'sketchus-custom-form' ),
					'[your-name] <[your-email]>' ) . "\n"
				. sprintf( __( 'Subject: %s', 'sketchus-custom-form' ),
					'[your-subject]' ) . "\n\n"
				. __( 'Message Body:', 'sketchus-custom-form' )
					. "\n" . '[your-message]' . "\n\n"
				. '--' . "\n"
				. sprintf( __( 'This e-mail was sent from a contact form on %1$s (%2$s)',
					'sketchus-custom-form' ), get_bloginfo( 'name' ), get_bloginfo( 'url' ) ),
			'recipient' => get_option( 'admin_email' ),
			'additional_headers' => 'Reply-To: [your-email]',
			'attachments' => '',
			'use_html' => 0,
			'exclude_blank' => 0 );

		return $template;
	}

	public static function mail_2() {
		$template = array(
			'active' => false,
			'subject' => sprintf(
				_x( '%1$s "%2$s"', 'mail subject', 'sketchus-custom-form' ),
				get_bloginfo( 'name' ), '[your-subject]' ),
			'sender' => sprintf( '%s <%s>',
				get_bloginfo( 'name' ), self::from_email() ),
			'body' =>
				__( 'Message Body:', 'sketchus-custom-form' )
					. "\n" . '[your-message]' . "\n\n"
				. '--' . "\n"
				. sprintf( __( 'This e-mail was sent from a contact form on %1$s (%2$s)',
					'sketchus-custom-form' ), get_bloginfo( 'name' ), get_bloginfo( 'url' ) ),
			'recipient' => '[your-email]',
			'additional_headers' => sprintf( 'Reply-To: %s',
				get_option( 'admin_email' ) ),
			'attachments' => '',
			'use_html' => 0,
			'exclude_blank' => 0 );

		return $template;
	}

	public static function from_email() {
		$admin_email = get_option( 'admin_email' );
		$sitename = strtolower( $_SERVER['SERVER_NAME'] );

		if ( skfom_is_localhost() ) {
			return $admin_email;
		}

		if ( substr( $sitename, 0, 4 ) == 'www.' ) {
			$sitename = substr( $sitename, 4 );
		}

		if ( strpbrk( $admin_email, '@' ) == '@' . $sitename ) {
			return $admin_email;
		}

		return 'wordpress@' . $sitename;
	}

	public static function messages() {
		$messages = array();

		foreach ( skfom_messages() as $key => $arr ) {
			$messages[$key] = $arr['default'];
		}

		return $messages;
	}
}

function skfom_messages() {
	$messages = array(
		'mail_sent_ok' => array(
			'description'
				=> __( "Sender's message was sent successfully", 'sketchus-custom-form' ),
			'default'
				=> __( "Thank you for your message. It has been sent.", 'sketchus-custom-form' )
		),

		'mail_sent_ng' => array(
			'description'
				=> __( "Sender's message failed to send", 'sketchus-custom-form' ),
			'default'
				=> __( "There was an error trying to send your message. Please try again later.", 'sketchus-custom-form' )
		),

		'validation_error' => array(
			'description'
				=> __( "Validation errors occurred", 'sketchus-custom-form' ),
			'default'
				=> __( "One or more fields have an error. Please check and try again.", 'sketchus-custom-form' )
		),

		'spam' => array(
			'description'
				=> __( "Submission was referred to as spam", 'sketchus-custom-form' ),
			'default'
				=> __( "There was an error trying to send your message. Please try again later.", 'sketchus-custom-form' )
		),

		'accept_terms' => array(
			'description'
				=> __( "There are terms that the sender must accept", 'sketchus-custom-form' ),
			'default'
				=> __( "You must accept the terms and conditions before sending your message.", 'sketchus-custom-form' )
		),

		'invalid_required' => array(
			'description'
				=> __( "There is a field that the sender must fill in", 'sketchus-custom-form' ),
			'default'
				=> __( "The field is required.", 'sketchus-custom-form' )
		),

		'invalid_too_long' => array(
			'description'
				=> __( "There is a field with input that is longer than the maximum allowed length", 'sketchus-custom-form' ),
			'default'
				=> __( "The field is too long.", 'sketchus-custom-form' )
		),

		'invalid_too_short' => array(
			'description'
				=> __( "There is a field with input that is shorter than the minimum allowed length", 'sketchus-custom-form' ),
			'default'
				=> __( "The field is too short.", 'sketchus-custom-form' )
		)
	);

	return apply_filters( 'skfom_messages', $messages );
}
