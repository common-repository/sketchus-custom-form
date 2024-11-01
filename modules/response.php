<?php
/**
** A base module for [response]
**/

/* Shortcode handler */

skfom_add_shortcode( 'response', 'skfom_response_shortcode_handler' );

function skfom_response_shortcode_handler( $tag ) {
	if ( $contact_form = skfom_get_current_contact_form() ) {
		return $contact_form->form_response_output();
	}
}

?>