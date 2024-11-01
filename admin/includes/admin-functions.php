<?php

function skfom_current_action() {
	if ( isset( $_REQUEST['action'] ) && -1 != $_REQUEST['action'] ) {
		return $_REQUEST['action'];
	}

	if ( isset( $_REQUEST['action2'] ) && -1 != $_REQUEST['action2'] ) {
		return $_REQUEST['action2'];
	}

	return false;
}

function skfom_admin_has_edit_cap() {
	return current_user_can( 'skfom_edit_contact_forms' );
}

function skfom_add_tag_generator( $name, $title, $elm_id, $callback, $options = array() ) {
	$tag_generator = SKFOM_TagGenerator::get_instance();
	return $tag_generator->add( $name, $title, $callback, $options );
}

function skfom_save_contact_form( $post_id = -1 ) {
	if ( -1 != $post_id ) {
		$contact_form = skfom_contact_form( $post_id );
	}

	if ( empty( $contact_form ) ) {
		$contact_form = SKFOM_ContactForm::get_template();
	}

	if ( isset( $_POST['post_title'] ) ) {
		$contact_form->set_title( $_POST['post_title'] );
	}

	if ( isset( $_POST['skfom-locale'] ) ) {
		$locale = trim( $_POST['skfom-locale'] );

		if ( skfom_is_valid_locale( $locale ) ) {
			$contact_form->locale = $locale;
		}
	}

	$properties = $contact_form->get_properties();

	if ( isset( $_POST['skfom-form'] ) ) {
		$properties['form'] = trim( $_POST['skfom-form'] );
	}

	$mail = $properties['mail'];

	if ( isset( $_POST['skfom-mail-subject'] ) ) {
		$mail['subject'] = trim( $_POST['skfom-mail-subject'] );
	}

	if ( isset( $_POST['skfom-mail-sender'] ) ) {
		$mail['sender'] = trim( $_POST['skfom-mail-sender'] );
	}

	if ( isset( $_POST['skfom-mail-body'] ) ) {
		$mail['body'] = trim( $_POST['skfom-mail-body'] );
	}

	if ( isset( $_POST['skfom-mail-recipient'] ) ) {
		$mail['recipient'] = trim( $_POST['skfom-mail-recipient'] );
	}

	if ( isset( $_POST['skfom-mail-additional-headers'] ) ) {
		$headers = '';
		$tempheaders = str_replace(
			"\r\n", "\n", $_POST['skfom-mail-additional-headers'] );
		$tempheaders = explode( "\n", $tempheaders );

		foreach ( $tempheaders as $header ) {
			$header = trim( $header );

			if ( '' !== $header ) {
				$headers .= $header . "\n";
			}
		}

		$mail['additional_headers'] = trim( $headers );
	}

	if ( isset( $_POST['skfom-mail-attachments'] ) ) {
		$mail['attachments'] = trim( $_POST['skfom-mail-attachments'] );
	}

	$mail['use_html'] = ! empty( $_POST['skfom-mail-use-html'] );
	$mail['exclude_blank'] = ! empty( $_POST['skfom-mail-exclude-blank'] );

	$properties['mail'] = $mail;

	$mail_2 = $properties['mail_2'];

	$mail_2['active'] = ! empty( $_POST['skfom-mail-2-active'] );

	if ( isset( $_POST['skfom-mail-2-subject'] ) ) {
		$mail_2['subject'] = trim( $_POST['skfom-mail-2-subject'] );
	}

	if ( isset( $_POST['skfom-mail-2-sender'] ) ) {
		$mail_2['sender'] = trim( $_POST['skfom-mail-2-sender'] );
	}

	if ( isset( $_POST['skfom-mail-2-body'] ) ) {
		$mail_2['body'] = trim( $_POST['skfom-mail-2-body'] );
	}

	if ( isset( $_POST['skfom-mail-2-recipient'] ) ) {
		$mail_2['recipient'] = trim( $_POST['skfom-mail-2-recipient'] );
	}

	if ( isset( $_POST['skfom-mail-2-additional-headers'] ) ) {
		$headers = '';
		$tempheaders = str_replace(
			"\r\n", "\n", $_POST['skfom-mail-2-additional-headers'] );
		$tempheaders = explode( "\n", $tempheaders );

		foreach ( $tempheaders as $header ) {
			$header = trim( $header );

			if ( '' !== $header ) {
				$headers .= $header . "\n";
			}
		}

		$mail_2['additional_headers'] = trim( $headers );
	}

	if ( isset( $_POST['skfom-mail-2-attachments'] ) ) {
		$mail_2['attachments'] = trim( $_POST['skfom-mail-2-attachments'] );
	}

	$mail_2['use_html'] = ! empty( $_POST['skfom-mail-2-use-html'] );
	$mail_2['exclude_blank'] = ! empty( $_POST['skfom-mail-2-exclude-blank'] );

	$properties['mail_2'] = $mail_2;

	foreach ( skfom_messages() as $key => $arr ) {
		$field_name = 'skfom-message-' . strtr( $key, '_', '-' );

		if ( isset( $_POST[$field_name] ) ) {
			$properties['messages'][$key] = trim( $_POST[$field_name] );
		}
	}

	if ( isset( $_POST['skfom-additional-settings'] ) ) {
		$properties['additional_settings'] = trim(
			$_POST['skfom-additional-settings'] );
	}

	$contact_form->set_properties( $properties );

	do_action( 'skfom_save_contact_form', $contact_form );

	$post_id = $contact_form->save();

	if ( skfom_validate_configuration() ) {
		$contact_form->validate_configuration();
	}

	return $post_id;
}
