<?php
/**
** A base module for [submit]
**/

/* Shortcode handler */

add_action( 'skfom_init', 'skfom_add_shortcode_submit' );

function skfom_add_shortcode_submit() {
	skfom_add_shortcode( 'submit', 'skfom_submit_shortcode_handler' );
}

function skfom_submit_shortcode_handler( $tag ) {
	$tag = new SKFOM_Shortcode( $tag );

	$class = skfom_form_controls_class( $tag->type );

	$atts = array();

	$atts['class'] = $tag->get_class_option( $class );
	$atts['id'] = $tag->get_id_option();
	$atts['tabindex'] = $tag->get_option( 'tabindex', 'int', true );

	$value = isset( $tag->values[0] ) ? $tag->values[0] : '';

	if ( empty( $value ) )
		$value = __( 'Send', 'sketchus-custom-form' );

	$atts['type'] = 'submit';
	$atts['value'] = $value;

	$atts = skfom_format_atts( $atts );

	$html = sprintf( '<input %1$s />', $atts );

	return $html;
}


/* Tag generator */

add_action( 'skfom_admin_init', 'skfom_add_tag_generator_submit', 55 );

function skfom_add_tag_generator_submit() {
	$tag_generator = SKFOM_TagGenerator::get_instance();
	$tag_generator->add( 'submit', __( 'submit', 'sketchus-custom-form' ),
		'skfom_tag_generator_submit', array( 'nameless' => 1 ) );
}

function skfom_tag_generator_submit( $contact_form, $args = '' ) {
	$args = wp_parse_args( $args, array() );

	$description = __( "Generate a form-tag for a submit button. For more details, see %s.", 'sketchus-custom-form' );

	$desc_link = skfom_link( __( 'http://dev.sketchus.com/custom-form/submit-button/', 'sketchus-custom-form' ), __( 'Submit Button', 'sketchus-custom-form' ) );

?>
<div class="control-box">
<fieldset>
<legend><?php echo sprintf( esc_html( $description ), $desc_link ); ?></legend>

<table class="form-table">
<tbody>
	<tr>
	<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-values' ); ?>"><?php echo esc_html( __( 'Label', 'sketchus-custom-form' ) ); ?></label></th>
	<td><input type="text" name="values" class="oneline" id="<?php echo esc_attr( $args['content'] . '-values' ); ?>" /></td>
	</tr>

	<tr>
	<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-id' ); ?>"><?php echo esc_html( __( 'Id attribute', 'sketchus-custom-form' ) ); ?></label></th>
	<td><input type="text" name="id" class="idvalue oneline option" id="<?php echo esc_attr( $args['content'] . '-id' ); ?>" /></td>
	</tr>

	<tr>
	<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-class' ); ?>"><?php echo esc_html( __( 'Class attribute', 'sketchus-custom-form' ) ); ?></label></th>
	<td><input type="text" name="class" class="classvalue oneline option" id="<?php echo esc_attr( $args['content'] . '-class' ); ?>" /></td>
	</tr>

</tbody>
</table>
</fieldset>
</div>

<div class="insert-box">
	<input type="text" name="submit" class="tag code" readonly="readonly" onfocus="this.select()" />

	<div class="submitbox">
	<input type="button" class="button button-primary insert-tag" value="<?php echo esc_attr( __( 'Insert Tag', 'sketchus-custom-form' ) ); ?>" />
	</div>
</div>
<?php
}
