<?php
/**
** A base module for the following types of tags:
** 	[text] and [text*]		# Single-line text
** 	[email] and [email*]	# Email address
** 	[url] and [url*]		# URL
** 	[tel] and [tel*]		# Telephone number
**/

/* Shortcode handler */

add_action( 'skfom_init', 'skfom_add_shortcode_text' );

function skfom_add_shortcode_text() {
	skfom_add_shortcode(
		array( 'text', 'text*', 'email', 'email*', 'url', 'url*', 'tel', 'tel*' ),
		'skfom_text_shortcode_handler', true );
}

function skfom_text_shortcode_handler( $tag ) {
	$tag = new SKFOM_Shortcode( $tag );

	if ( empty( $tag->name ) )
		return '';

	$validation_error = skfom_get_validation_error( $tag->name );

	$class = skfom_form_controls_class( $tag->type, 'skfom-text' );

	if ( in_array( $tag->basetype, array( 'email', 'url', 'tel' ) ) )
		$class .= ' skfom-validates-as-' . $tag->basetype;

	if ( $validation_error )
		$class .= ' skfom-not-valid';

	$atts = array();

	$atts['size'] = $tag->get_size_option( '40' );
	$atts['maxlength'] = $tag->get_maxlength_option();
	$atts['minlength'] = $tag->get_minlength_option();

	if ( $atts['maxlength'] && $atts['minlength'] && $atts['maxlength'] < $atts['minlength'] ) {
		unset( $atts['maxlength'], $atts['minlength'] );
	}

	$atts['class'] = $tag->get_class_option( $class );
	$atts['id'] = $tag->get_id_option();
	$atts['tabindex'] = $tag->get_option( 'tabindex', 'int', true );

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

add_filter( 'skfom_validate_text', 'skfom_text_validation_filter', 10, 2 );
add_filter( 'skfom_validate_text*', 'skfom_text_validation_filter', 10, 2 );
add_filter( 'skfom_validate_email', 'skfom_text_validation_filter', 10, 2 );
add_filter( 'skfom_validate_email*', 'skfom_text_validation_filter', 10, 2 );
add_filter( 'skfom_validate_url', 'skfom_text_validation_filter', 10, 2 );
add_filter( 'skfom_validate_url*', 'skfom_text_validation_filter', 10, 2 );
add_filter( 'skfom_validate_tel', 'skfom_text_validation_filter', 10, 2 );
add_filter( 'skfom_validate_tel*', 'skfom_text_validation_filter', 10, 2 );

function skfom_text_validation_filter( $result, $tag ) {
	$tag = new SKFOM_Shortcode( $tag );

	$name = $tag->name;

	$value = isset( $_POST[$name] )
		? trim( wp_unslash( strtr( (string) $_POST[$name], "\n", " " ) ) )
		: '';

	if ( 'text' == $tag->basetype ) {
		if ( $tag->is_required() && '' == $value ) {
			$result->invalidate( $tag, skfom_get_message( 'invalid_required' ) );
		}
	}

	if ( 'email' == $tag->basetype ) {
		if ( $tag->is_required() && '' == $value ) {
			$result->invalidate( $tag, skfom_get_message( 'invalid_required' ) );
		} elseif ( '' != $value && ! skfom_is_email( $value ) ) {
			$result->invalidate( $tag, skfom_get_message( 'invalid_email' ) );
		}
	}

	if ( 'url' == $tag->basetype ) {
		if ( $tag->is_required() && '' == $value ) {
			$result->invalidate( $tag, skfom_get_message( 'invalid_required' ) );
		} elseif ( '' != $value && ! skfom_is_url( $value ) ) {
			$result->invalidate( $tag, skfom_get_message( 'invalid_url' ) );
		}
	}

	if ( 'tel' == $tag->basetype ) {
		if ( $tag->is_required() && '' == $value ) {
			$result->invalidate( $tag, skfom_get_message( 'invalid_required' ) );
		} elseif ( '' != $value && ! skfom_is_tel( $value ) ) {
			$result->invalidate( $tag, skfom_get_message( 'invalid_tel' ) );
		}
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


/* Messages */

add_filter( 'skfom_messages', 'skfom_text_messages' );

function skfom_text_messages( $messages ) {
	return array_merge( $messages, array(
		'invalid_email' => array(
			'description' => __( "Email address that the sender entered is invalid", 'sketchus-custom-form' ),
			'default' => __( "The e-mail address entered is invalid.", 'sketchus-custom-form' )
		),

		'invalid_url' => array(
			'description' => __( "URL that the sender entered is invalid", 'sketchus-custom-form' ),
			'default' => __( "The URL is invalid.", 'sketchus-custom-form' )
		),

		'invalid_tel' => array(
			'description' => __( "Telephone number that the sender entered is invalid", 'sketchus-custom-form' ),
			'default' => __( "The telephone number is invalid.", 'sketchus-custom-form' )
		) ) );
}


/* Tag generator */

add_action( 'skfom_admin_init', 'skfom_add_tag_generator_text', 15 );

function skfom_add_tag_generator_text() {
	$tag_generator = SKFOM_TagGenerator::get_instance();
	$tag_generator->add( 'text', __( 'text box', 'sketchus-custom-form' ),
		'skfom_tag_generator_text' );
	$tag_generator->add( 'email', __( 'email box', 'sketchus-custom-form' ),
		'skfom_tag_generator_text' );
	$tag_generator->add( 'url', __( 'URL box', 'sketchus-custom-form' ),
		'skfom_tag_generator_text' );
	$tag_generator->add( 'tel', __( 'Phone box', 'sketchus-custom-form' ),
		'skfom_tag_generator_text' );
}

function skfom_tag_generator_text( $contact_form, $args = '' ) {
	$args = wp_parse_args( $args, array() );
	$type = $args['id'];

	if ( ! in_array( $type, array( 'email', 'url', 'tel' ) ) ) {
		$type = 'text';
	}

	if ( 'text' == $type ) {
		$description = __( "Generate a form-tag for a single-line plain text input field. For more details, see %s.", 'sketchus-custom-form' );
	} elseif ( 'email' == $type ) {
		$description = __( "Generate a form-tag for a single-line email address input field. For more details, see %s.", 'sketchus-custom-form' );
	} elseif ( 'url' == $type ) {
		$description = __( "Generate a form-tag for a single-line URL input field. For more details, see %s.", 'sketchus-custom-form' );
	} elseif ( 'tel' == $type ) {
		$description = __( "Generate a form-tag for a single-line telephone number input field. For more details, see %s.", 'sketchus-custom-form' );
	}

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

<?php if ( in_array( $type, array( 'text', 'email', 'url' ) ) ) : ?>
	<tr>
	<th scope="row"><?php echo esc_html( __( 'Akismet', 'sketchus-custom-form' ) ); ?></th>
	<td>
		<fieldset>
		<legend class="screen-reader-text"><?php echo esc_html( __( 'Akismet', 'sketchus-custom-form' ) ); ?></legend>

<?php if ( 'text' == $type ) : ?>
		<label>
			<input type="checkbox" name="akismet:author" class="option" />
			<?php echo esc_html( __( "This field requires author's name", 'sketchus-custom-form' ) ); ?>
		</label>
<?php elseif ( 'email' == $type ) : ?>
		<label>
			<input type="checkbox" name="akismet:author_email" class="option" />
			<?php echo esc_html( __( "This field requires author's email address", 'sketchus-custom-form' ) ); ?>
		</label>
<?php elseif ( 'url' == $type ) : ?>
		<label>
			<input type="checkbox" name="akismet:author_url" class="option" />
			<?php echo esc_html( __( "This field requires author's URL", 'sketchus-custom-form' ) ); ?>
		</label>
<?php endif; ?>

		</fieldset>
	</td>
	</tr>
<?php endif; ?>

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
