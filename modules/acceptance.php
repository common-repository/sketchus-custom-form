<?php
/**
** A base module for [acceptance]
**/

/* Shortcode handler */

add_action( 'skfom_init', 'skfom_add_shortcode_acceptance' );

function skfom_add_shortcode_acceptance() {
	skfom_add_shortcode( 'acceptance',
		'skfom_acceptance_shortcode_handler', true );
}

function skfom_acceptance_shortcode_handler( $tag ) {
	$tag = new SKFOM_Shortcode( $tag );

	if ( empty( $tag->name ) )
		return '';

	$validation_error = skfom_get_validation_error( $tag->name );

	$class = skfom_form_controls_class( $tag->type );

	if ( $validation_error )
		$class .= ' skfom-not-valid';

	if ( $tag->has_option( 'invert' ) )
		$class .= ' skfom-invert';

	$atts = array();

	$atts['class'] = $tag->get_class_option( $class );
	$atts['id'] = $tag->get_id_option();
	$atts['tabindex'] = $tag->get_option( 'tabindex', 'int', true );

	if ( $tag->has_option( 'default:on' ) )
		$atts['checked'] = 'checked';

	$atts['aria-invalid'] = $validation_error ? 'true' : 'false';

	$atts['type'] = 'checkbox';
	$atts['name'] = $tag->name;
	$atts['value'] = '1';

	$atts = skfom_format_atts( $atts );

	$html = sprintf(
		'<span class="skfom-form-control-wrap %1$s"><input %2$s />%3$s</span>',
		sanitize_html_class( $tag->name ), $atts, $validation_error );

	return $html;
}


/* Validation filter */

add_filter( 'skfom_validate_acceptance', 'skfom_acceptance_validation_filter', 10, 2 );

function skfom_acceptance_validation_filter( $result, $tag ) {
	if ( ! skfom_acceptance_as_validation() )
		return $result;

	$tag = new SKFOM_Shortcode( $tag );

	$name = $tag->name;
	$value = ( ! empty( $_POST[$name] ) ? 1 : 0 );

	$invert = $tag->has_option( 'invert' );

	if ( $invert && $value || ! $invert && ! $value ) {
		$result->invalidate( $tag, skfom_get_message( 'accept_terms' ) );
	}

	return $result;
}


/* Acceptance filter */

add_filter( 'skfom_acceptance', 'skfom_acceptance_filter' );

function skfom_acceptance_filter( $accepted ) {
	if ( ! $accepted )
		return $accepted;

	$fes = skfom_scan_shortcode( array( 'type' => 'acceptance' ) );

	foreach ( $fes as $fe ) {
		$name = $fe['name'];
		$options = (array) $fe['options'];

		if ( empty( $name ) )
			continue;

		$value = ( ! empty( $_POST[$name] ) ? 1 : 0 );

		$invert = (bool) preg_grep( '%^invert$%', $options );

		if ( $invert && $value || ! $invert && ! $value )
			$accepted = false;
	}

	return $accepted;
}

add_filter( 'skfom_form_class_attr', 'skfom_acceptance_form_class_attr' );

function skfom_acceptance_form_class_attr( $class ) {
	if ( skfom_acceptance_as_validation() )
		return $class . ' skfom-acceptance-as-validation';

	return $class;
}

function skfom_acceptance_as_validation() {
	if ( ! $contact_form = skfom_get_current_contact_form() )
		return false;

	return $contact_form->is_true( 'acceptance_as_validation' );
}


/* Tag generator */

add_action( 'skfom_admin_init', 'skfom_add_tag_generator_acceptance', 35 );

function skfom_add_tag_generator_acceptance() {
	$tag_generator = SKFOM_TagGenerator::get_instance();
	$tag_generator->add( 'acceptance', __( 'acceptance', 'sketchus-custom-form' ),
		'skfom_tag_generator_acceptance' );
}

function skfom_tag_generator_acceptance( $contact_form, $args = '' ) {
	$args = wp_parse_args( $args, array() );
	$type = 'acceptance';

	$description = __( "Generate a form-tag for an acceptance checkbox. For more details, see %s.", 'sketchus-custom-form' );

	$desc_link = skfom_link( __( 'http://dev.sketchus.com/custom-form/acceptance-checkbox/', 'sketchus-custom-form' ), __( 'Acceptance Checkbox', 'sketchus-custom-form' ) );

?>
<div class="control-box">
<fieldset>
<legend><?php echo sprintf( esc_html( $description ), $desc_link ); ?></legend>

<table class="form-table">
<tbody>
	<tr>
	<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-name' ); ?>"><?php echo esc_html( __( 'Name', 'sketchus-custom-form' ) ); ?></label></th>
	<td><input type="text" name="name" class="tg-name oneline" id="<?php echo esc_attr( $args['content'] . '-name' ); ?>" /></td>
	</tr>

	<tr>
	<th scope="row"><?php echo esc_html( __( 'Options', 'sketchus-custom-form' ) ); ?></th>
	<td>
		<fieldset>
		<legend class="screen-reader-text"><?php echo esc_html( __( 'Options', 'sketchus-custom-form' ) ); ?></legend>
		<label><input type="checkbox" name="default:on" class="option" /> <?php echo esc_html( __( 'Make this checkbox checked by default', 'sketchus-custom-form' ) ); ?></label><br />
		<label><input type="checkbox" name="invert" class="option" /> <?php echo esc_html( __( 'Make this work inversely', 'sketchus-custom-form' ) ); ?></label>
		</fieldset>
	</td>
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
	<input type="text" name="<?php echo $type; ?>" class="tag code" readonly="readonly" onfocus="this.select()" />

	<div class="submitbox">
	<input type="button" class="button button-primary insert-tag" value="<?php echo esc_attr( __( 'Insert Tag', 'sketchus-custom-form' ) ); ?>" />
	</div>
</div>
<?php
}