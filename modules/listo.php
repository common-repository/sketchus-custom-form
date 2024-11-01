<?php
/**
** Retrieve list data from the Listo plugin.
** Listo http://wordpress.org/plugins/listo/
**/

add_filter( 'skfom_form_tag_data_option', 'skfom_listo', 10, 3 );

function skfom_listo( $data, $options, $args ) {
	if ( ! function_exists( 'listo' ) ) {
		return $data;
	}

	$args = wp_parse_args( $args, array() );

	$contact_form = skfom_get_current_contact_form();
	$args['locale'] = $contact_form->locale;

	foreach ( (array) $options as $option ) {
		$option = explode( '.', $option );
		$type = $option[0];
		$args['group'] = isset( $option[1] ) ? $option[1] : null;

		if ( $list = listo( $type, $args ) ) {
			$data = array_merge( (array) $data, $list );
		}
	}

	return $data;
}

?>