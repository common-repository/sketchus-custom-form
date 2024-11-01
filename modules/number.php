<?php
/**
** A base module for the following types of tags:
** 	[number] and [number*]		# Number
** 	[range] and [range*]		# Range
**/

/* Shortcode handler */

add_action( 'skfom_init', 'skfom_add_shortcode_number' );

function skfom_add_shortcode_number() {
	skfom_add_shortcode( array( 'number', 'number*', 'range', 'range*' ),
		'skfom_number_shortcode_handler', true );
}

function skfom_number_shortcode_handler( $tag ) {
	$tag = new SKFOM_Shortcode( $tag );

	if ( empty( $tag->name ) )
		return '';

	$validation_error = skfom_get_validation_error( $tag->name );

	$class = skfom_form_controls_class( $tag->type );

	$class .= ' skfom-validates-as-number';

	if ( $validation_error )
		$class .= ' skfom-not-valid';

	$atts = array();

	$atts['class'] = $tag->get_class_option( $class );
	$atts['id'] = $tag->get_id_option();
	$atts['tabindex'] = $tag->get_option( 'tabindex', 'int', true );
	$atts['min'] = $tag->get_option( 'min', 'signed_int', true );
	$atts['max'] = $tag->get_option( 'max', 'signed_int', true );
	$atts['step'] = $tag->get_option( 'step', 'int', true );

	if ( $tag->has_option( 'readonly' ) )
		$atts['readonly'] = 'readonly';

	if ( $tag->is_required() )
		$atts['aria-required'] = 'true';

	$atts['aria-invalid'] = $validation_error ? 'true' : 'false';

	$value = (string) reset( $tag->values );

	if ( $tag->has_option( 'placeholder' ) || $tag->has_option( 'watermark' ) ) {
		$atts['placeholder'] = $value;
		$value = '';
	}

	$value = $tag->get_default_option( $value );

	$value = skfom_get_hangover( $tag->name, $value );

	$atts['value'] = $value;

	if ( skfom_support_html5() ) {
		$atts['type'] = $tag->basetype;
	} else {
		$atts['type'] = 'text';
	}

	$atts['name'] = $tag->name;

	$atts = skfom_format_atts( $atts );

	$html = sprintf(
		'<span class="skfom-form-control-wrap %1$s"><input %2$s />%3$s</span>',
		sanitize_html_class( $tag->name ), $atts, $validation_error );

	return $html;
}


/* Validation filter */

add_filter( 'skfom_validate_number', 'skfom_number_validation_filter', 10, 2 );
add_filter( 'skfom_validate_number*', 'skfom_number_validation_filter', 10, 2 );
add_filter( 'skfom_validate_range', 'skfom_number_validation_filter', 10, 2 );
add_filter( 'skfom_validate_range*', 'skfom_number_validation_filter', 10, 2 );

function skfom_number_validation_filter( $result, $tag ) {
	$tag = new SKFOM_Shortcode( $tag );

	$name = $tag->name;

	$value = isset( $_POST[$name] )
		? trim( strtr( (string) $_POST[$name], "\n", " " ) )
		: '';

	$min = $tag->get_option( 'min', 'signed_int', true );
	$max = $tag->get_option( 'max', 'signed_int', true );

	if ( $tag->is_required() && '' == $value ) {
		$result->invalidate( $tag, skfom_get_message( 'invalid_required' ) );
	} elseif ( '' != $value && ! skfom_is_number( $value ) ) {
		$result->invalidate( $tag, skfom_get_message( 'invalid_number' ) );
	} elseif ( '' != $value && '' != $min && (float) $value < (float) $min ) {
		$result->invalidate( $tag, skfom_get_message( 'number_too_small' ) );
	} elseif ( '' != $value && '' != $max && (float) $max < (float) $value ) {
		$result->invalidate( $tag, skfom_get_message( 'number_too_large' ) );
	}

	return $result;
}


/* Messages */

add_filter( 'skfom_messages', 'skfom_number_messages' );

function skfom_number_messages( $messages ) {
	return array_merge( $messages, array(
		'invalid_number' => array(
			'description' => __( "Number format that the sender entered is invalid", 'sketchus-custom-form' ),
			'default' => __( "The number format is invalid.", 'sketchus-custom-form' )
		),

		'number_too_small' => array(
			'description' => __( "Number is smaller than minimum limit", 'sketchus-custom-form' ),
			'default' => __( "The number is smaller than the minimum allowed.", 'sketchus-custom-form' )
		),

		'number_too_large' => array(
			'description' => __( "Number is larger than maximum limit", 'sketchus-custom-form' ),
			'default' => __( "The number is larger than the maximum allowed.", 'sketchus-custom-form' )
		) ) );
}


/* Tag generator */

add_action( 'skfom_admin_init', 'skfom_add_tag_generator_number', 18 );

function skfom_add_tag_generator_number() {
	$tag_generator = SKFOM_TagGenerator::get_instance();
	$tag_generator->add( 'number', __( 'number', 'sketchus-custom-form' ),
		'skfom_tag_generator_number' );
}

function skfom_tag_generator_number( $contact_form, $args = '' ) {
	$args = wp_parse_args( $args, array() );
	$type = 'number';

	$description = __( "Generate a form-tag for a field for numeric value input. For more details, see %s.", 'sketchus-custom-form' );

	$desc_link = skfom_link( __( 'http://dev.sketchus.com/custom-form/number-fields/', 'sketchus-custom-form' ), __( 'Number Fields', 'sketchus-custom-form' ) );

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
		<select name="tagtype">
			<option value="number" selected="selected"><?php echo esc_html( __( 'Spinbox', 'sketchus-custom-form' ) ); ?></option>
			<option value="range"><?php echo esc_html( __( 'Slider', 'sketchus-custom-form' ) ); ?></option>
		</select>
		<br />
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
	<th scope="row"><?php echo esc_html( __( 'Range', 'sketchus-custom-form' ) ); ?></th>
	<td>
		<fieldset>
		<legend class="screen-reader-text"><?php echo esc_html( __( 'Range', 'sketchus-custom-form' ) ); ?></legend>
		<label>
		<?php echo esc_html( __( 'Min', 'sketchus-custom-form' ) ); ?>
		<input type="number" name="min" class="numeric option" />
		</label>
		&ndash;
		<label>
		<?php echo esc_html( __( 'Max', 'sketchus-custom-form' ) ); ?>
		<input type="number" name="max" class="numeric option" />
		</label>
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

	<br class="clear" />

	<p class="description mail-tag"><label for="<?php echo esc_attr( $args['content'] . '-mailtag' ); ?>"><?php echo sprintf( esc_html( __( "To use the value input through this field in a mail field, you need to insert the corresponding mail-tag (%s) into the field on the Mail tab.", 'sketchus-custom-form' ) ), '<strong><span class="mail-tag"></span></strong>' ); ?><input type="text" class="mail-tag code hidden" readonly="readonly" id="<?php echo esc_attr( $args['content'] . '-mailtag' ); ?>" /></label></p>
</div>
<?php
}
