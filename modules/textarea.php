<?php
/**
** A base module for [textarea] and [textarea*]
**/

/* Shortcode handler */

add_action( 'skfom_init', 'skfom_add_shortcode_textarea' );

function skfom_add_shortcode_textarea() {
	skfom_add_shortcode( array( 'textarea', 'textarea*' ),
		'skfom_textarea_shortcode_handler', true );
}

function skfom_textarea_shortcode_handler( $tag ) {
	$tag = new SKFOM_Shortcode( $tag );

	if ( empty( $tag->name ) )
		return '';

	$validation_error = skfom_get_validation_error( $tag->name );

	$class = skfom_form_controls_class( $tag->type );

	if ( $validation_error )
		$class .= ' skfom-not-valid';

	$atts = array();

	$atts['cols'] = $tag->get_cols_option( '40' );
	$atts['rows'] = $tag->get_rows_option( '10' );
	$atts['maxlength'] = $tag->get_maxlength_option();
	$atts['minlength'] = $tag->get_minlength_option();

	if ( $atts['maxlength'] && $atts['minlength'] && $atts['maxlength'] < $atts['minlength'] ) {
		unset( $atts['maxlength'], $atts['minlength'] );
	}

	$atts['class'] = $tag->get_class_option( $class );
	$atts['id'] = $tag->get_id_option();
	$atts['tabindex'] = $tag->get_option( 'tabindex', 'int', true );

	if ( $tag->has_option( 'readonly' ) ) {
		$atts['readonly'] = 'readonly';
	}

	if ( $tag->is_required() ) {
		$atts['aria-required'] = 'true';
	}

	$atts['aria-invalid'] = $validation_error ? 'true' : 'false';

	$value = empty( $tag->content )
		? (string) reset( $tag->values )
		: $tag->content;

	if ( $tag->has_option( 'placeholder' ) || $tag->has_option( 'watermark' ) ) {
		$atts['placeholder'] = $value;
		$value = '';
	}

	$value = $tag->get_default_option( $value );

	$value = skfom_get_hangover( $tag->name, $value );

	$atts['name'] = $tag->name;

	$atts = skfom_format_atts( $atts );

	$html = sprintf(
		'<span class="skfom-form-control-wrap %1$s"><textarea %2$s>%3$s</textarea>%4$s</span>',
		sanitize_html_class( $tag->name ), $atts,
		esc_textarea( $value ), $validation_error );

	return $html;
}


/* Validation filter */

add_filter( 'skfom_validate_textarea', 'skfom_textarea_validation_filter', 10, 2 );
add_filter( 'skfom_validate_textarea*', 'skfom_textarea_validation_filter', 10, 2 );

function skfom_textarea_validation_filter( $result, $tag ) {
	$tag = new SKFOM_Shortcode( $tag );

	$type = $tag->type;
	$name = $tag->name;

	$value = isset( $_POST[$name] ) ? (string) $_POST[$name] : '';

	if ( $tag->is_required() && '' == $value ) {
		$result->invalidate( $tag, skfom_get_message( 'invalid_required' ) );
	}

	if ( ! empty( $value ) ) {
		$maxlength = $tag->get_maxlength_option();
		$minlength = $tag->get_minlength_option();

		if ( $maxlength && $minlength && $maxlength < $minlength ) {
			$maxlength = $minlength = null;
		}

		$code_units = skfom_count_code_units( stripslashes( $value ) );

		if ( false !== $code_units ) {
			if ( $maxlength && $maxlength < $code_units ) {
				$result->invalidate( $tag, skfom_get_message( 'invalid_too_long' ) );
			} elseif ( $minlength && $code_units < $minlength ) {
				$result->invalidate( $tag, skfom_get_message( 'invalid_too_short' ) );
			}
		}
	}

	return $result;
}


/* Tag generator */

add_action( 'skfom_admin_init', 'skfom_add_tag_generator_textarea', 20 );

function skfom_add_tag_generator_textarea() {
	$tag_generator = SKFOM_TagGenerator::get_instance();
	$tag_generator->add( 'textarea', __( 'textarea', 'sketchus-custom-form' ),
		'skfom_tag_generator_textarea' );
}

function skfom_tag_generator_textarea( $contact_form, $args = '' ) {
	$args = wp_parse_args( $args, array() );
	$type = 'textarea';

	$description = __( "Generate a form-tag for a multi-line text input field. For more details, see %s.", 'sketchus-custom-form' );

	$desc_link = skfom_link( __( 'http://plugins.sketchus.com/custom-form/text-fields/', 'sketchus-custom-form' ), __( 'Text Fields', 'sketchus-custom-form' ) );

?>
<div class="control-box">
<fieldset>
<legend><?php echo sprintf( esc_html( $description ), $desc_link ); ?></legend>

<table class="form-table">
<tbody>
	<tr>
	<th scope="row"><?php echo esc_html( __( 'Field type', 'sketchus-custom-form' ) ); ?></th>
	<td>
		<fieldset>
		<legend class="screen-reader-text"><?php echo esc_html( __( 'Field type', 'sketchus-custom-form' ) ); ?></legend>
		<label><input type="checkbox" name="required" /> <?php echo esc_html( __( 'Required field', 'sketchus-custom-form' ) ); ?></label>
		</fieldset>
	</td>
	</tr>

	<tr>
	<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-name' ); ?>"><?php echo esc_html( __( 'Name', 'sketchus-custom-form' ) ); ?></label></th>
	<td><input type="text" name="name" class="tg-name oneline" id="<?php echo esc_attr( $args['content'] . '-name' ); ?>" /></td>
	</tr>

	<tr>
	<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-values' ); ?>"><?php echo esc_html( __( 'Default value', 'sketchus-custom-form' ) ); ?></label></th>
	<td><input type="text" name="values" class="oneline" id="<?php echo esc_attr( $args['content'] . '-values' ); ?>" /><br />
	<label><input type="checkbox" name="placeholder" class="option" /> <?php echo esc_html( __( 'Use this text as the placeholder of the field', 'sketchus-custom-form' ) ); ?></label></td>
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

	<br class="clear" />

	<p class="description mail-tag"><label for="<?php echo esc_attr( $args['content'] . '-mailtag' ); ?>"><?php echo sprintf( esc_html( __( "To use the value input through this field in a mail field, you need to insert the corresponding mail-tag (%s) into the field on the Mail tab.", 'sketchus-custom-form' ) ), '<strong><span class="mail-tag"></span></strong>' ); ?><input type="text" class="mail-tag code hidden" readonly="readonly" id="<?php echo esc_attr( $args['content'] . '-mailtag' ); ?>" /></label></p>
</div>
<?php
}
