<?php
/**
** A base module for [checkbox], [checkbox*], and [radio]
**/

/* Shortcode handler */

add_action( 'skfom_init', 'skfom_add_shortcode_checkbox' );

function skfom_add_shortcode_checkbox() {
	skfom_add_shortcode( array( 'checkbox', 'checkbox*', 'radio' ),
		'skfom_checkbox_shortcode_handler', true );
}

function skfom_checkbox_shortcode_handler( $tag ) {
	$tag = new SKFOM_Shortcode( $tag );

	if ( empty( $tag->name ) )
		return '';

	$validation_error = skfom_get_validation_error( $tag->name );

	$class = skfom_form_controls_class( $tag->type );

	if ( $validation_error )
		$class .= ' skfom-not-valid';

	$label_first = $tag->has_option( 'label_first' );
	$use_label_element = $tag->has_option( 'use_label_element' );
	$exclusive = $tag->has_option( 'exclusive' );
	$free_text = $tag->has_option( 'free_text' );
	$multiple = false;

	if ( 'checkbox' == $tag->basetype )
		$multiple = ! $exclusive;
	else // radio
		$exclusive = false;

	if ( $exclusive )
		$class .= ' skfom-exclusive-checkbox';

	$atts = array();

	$atts['class'] = $tag->get_class_option( $class );
	$atts['id'] = $tag->get_id_option();

	$tabindex = $tag->get_option( 'tabindex', 'int', true );

	if ( false !== $tabindex )
		$tabindex = absint( $tabindex );

	$html = '';
	$count = 0;

	$values = (array) $tag->values;
	$labels = (array) $tag->labels;

	if ( $data = (array) $tag->get_data_option() ) {
		if ( $free_text ) {
			$values = array_merge(
				array_slice( $values, 0, -1 ),
				array_values( $data ),
				array_slice( $values, -1 ) );
			$labels = array_merge(
				array_slice( $labels, 0, -1 ),
				array_values( $data ),
				array_slice( $labels, -1 ) );
		} else {
			$values = array_merge( $values, array_values( $data ) );
			$labels = array_merge( $labels, array_values( $data ) );
		}
	}

	$defaults = array();

	$default_choice = $tag->get_default_option( null, 'multiple=1' );

	foreach ( $default_choice as $value ) {
		$key = array_search( $value, $values, true );

		if ( false !== $key ) {
			$defaults[] = (int) $key + 1;
		}
	}

	if ( $matches = $tag->get_first_match_option( '/^default:([0-9_]+)$/' ) ) {
		$defaults = array_merge( $defaults, explode( '_', $matches[1] ) );
	}

	$defaults = array_unique( $defaults );

	$hangover = skfom_get_hangover( $tag->name, $multiple ? array() : '' );

	foreach ( $values as $key => $value ) {
		$class = 'skfom-list-item';

		$checked = false;

		if ( $hangover ) {
			if ( $multiple ) {
				$checked = in_array( esc_sql( $value ), (array) $hangover );
			} else {
				$checked = ( $hangover == esc_sql( $value ) );
			}
		} else {
			$checked = in_array( $key + 1, (array) $defaults );
		}

		if ( isset( $labels[$key] ) )
			$label = $labels[$key];
		else
			$label = $value;

		$item_atts = array(
			'type' => $tag->basetype,
			'name' => $tag->name . ( $multiple ? '[]' : '' ),
			'value' => $value,
			'checked' => $checked ? 'checked' : '',
			'tabindex' => $tabindex ? $tabindex : '' );

		$item_atts = skfom_format_atts( $item_atts );

		if ( $label_first ) { // put label first, input last
			$item = sprintf(
				'<span class="skfom-list-item-label">%1$s</span>&nbsp;<input %2$s />',
				esc_html( $label ), $item_atts );
		} else {
			$item = sprintf(
				'<input %2$s />&nbsp;<span class="skfom-list-item-label">%1$s</span>',
				esc_html( $label ), $item_atts );
		}

		if ( $use_label_element )
			$item = '<label>' . $item . '</label>';

		if ( false !== $tabindex )
			$tabindex += 1;

		$count += 1;

		if ( 1 == $count ) {
			$class .= ' first';
		}

		if ( count( $values ) == $count ) { // last round
			$class .= ' last';

			if ( $free_text ) {
				$free_text_name = sprintf(
					'_skfom_%1$s_free_text_%2$s', $tag->basetype, $tag->name );

				$free_text_atts = array(
					'name' => $free_text_name,
					'class' => 'skfom-free-text',
					'tabindex' => $tabindex ? $tabindex : '' );

				if ( skfom_is_posted() && isset( $_POST[$free_text_name] ) ) {
					$free_text_atts['value'] = wp_unslash(
						$_POST[$free_text_name] );
				}

				$free_text_atts = skfom_format_atts( $free_text_atts );

				$item .= sprintf( ' <input type="text" %s />', $free_text_atts );

				$class .= ' has-free-text';
			}
		}

		$item = '<span class="' . esc_attr( $class ) . '">' . $item . '</span>';
		$html .= $item;
	}

	$atts = skfom_format_atts( $atts );

	$html = sprintf(
		'<span class="skfom-form-control-wrap %1$s"><span %2$s>%3$s</span>%4$s</span>',
		sanitize_html_class( $tag->name ), $atts, $html, $validation_error );

	return $html;
}


/* Validation filter */

add_filter( 'skfom_validate_checkbox', 'skfom_checkbox_validation_filter', 10, 2 );
add_filter( 'skfom_validate_checkbox*', 'skfom_checkbox_validation_filter', 10, 2 );
add_filter( 'skfom_validate_radio', 'skfom_checkbox_validation_filter', 10, 2 );

function skfom_checkbox_validation_filter( $result, $tag ) {
	$tag = new SKFOM_Shortcode( $tag );

	$type = $tag->type;
	$name = $tag->name;

	$value = isset( $_POST[$name] ) ? (array) $_POST[$name] : array();

	if ( $tag->is_required() && empty( $value ) ) {
		$result->invalidate( $tag, skfom_get_message( 'invalid_required' ) );
	}

	return $result;
}


/* Adding free text field */

add_filter( 'skfom_posted_data', 'skfom_checkbox_posted_data' );

function skfom_checkbox_posted_data( $posted_data ) {
	$tags = skfom_scan_shortcode(
		array( 'type' => array( 'checkbox', 'checkbox*', 'radio' ) ) );

	if ( empty( $tags ) ) {
		return $posted_data;
	}

	foreach ( $tags as $tag ) {
		$tag = new SKFOM_Shortcode( $tag );

		if ( ! isset( $posted_data[$tag->name] ) ) {
			continue;
		}

		$posted_items = (array) $posted_data[$tag->name];

		if ( $tag->has_option( 'free_text' ) ) {
			if ( SKFOM_USE_PIPE ) {
				$values = $tag->pipes->collect_afters();
			} else {
				$values = $tag->values;
			}

			$last = array_pop( $values );
			$last = html_entity_decode( $last, ENT_QUOTES, 'UTF-8' );

			if ( in_array( $last, $posted_items ) ) {
				$posted_items = array_diff( $posted_items, array( $last ) );

				$free_text_name = sprintf(
					'_skfom_%1$s_free_text_%2$s', $tag->basetype, $tag->name );

				$free_text = $posted_data[$free_text_name];

				if ( ! empty( $free_text ) ) {
					$posted_items[] = trim( $last . ' ' . $free_text );
				} else {
					$posted_items[] = $last;
				}
			}
		}

		$posted_data[$tag->name] = $posted_items;
	}

	return $posted_data;
}


/* Tag generator */

add_action( 'skfom_admin_init',
	'skfom_add_tag_generator_checkbox_and_radio', 30 );

function skfom_add_tag_generator_checkbox_and_radio() {
	$tag_generator = SKFOM_TagGenerator::get_instance();
	$tag_generator->add( 'checkbox', __( 'checkboxes', 'sketchus-custom-form' ),
		'skfom_tag_generator_checkbox' );
	$tag_generator->add( 'radio', __( 'radio buttons', 'sketchus-custom-form' ),
		'skfom_tag_generator_checkbox' );
}

function skfom_tag_generator_checkbox( $contact_form, $args = '' ) {
	$args = wp_parse_args( $args, array() );
	$type = $args['id'];

	if ( 'radio' != $type ) {
		$type = 'checkbox';
	}

	if ( 'checkbox' == $type ) {
		$description = __( "Generate a form-tag for a group of checkboxes. For more details, see %s.", 'sketchus-custom-form' );
	} elseif ( 'radio' == $type ) {
		$description = __( "Generate a form-tag for a group of radio buttons. For more details, see %s.", 'sketchus-custom-form' );
	}

	$desc_link = skfom_link( __( 'http://dev.sketchus.com/custom-form/checkboxes-radio-buttons-and-menus/', 'sketchus-custom-form' ), __( 'Checkboxes, Radio Buttons and Menus', 'sketchus-custom-form' ) );

?>
<div class="control-box">
<fieldset>
<legend><?php echo sprintf( esc_html( $description ), $desc_link ); ?></legend>

<table class="form-table">
<tbody>
<?php if ( 'checkbox' == $type ) : ?>
	<tr>
	<th scope="row"><?php echo esc_html( __( 'Field type', 'sketchus-custom-form' ) ); ?></th>
	<td>
		<fieldset>
		<legend class="screen-reader-text"><?php echo esc_html( __( 'Field type', 'sketchus-custom-form' ) ); ?></legend>
		<label><input type="checkbox" name="required" /> <?php echo esc_html( __( 'Required field', 'sketchus-custom-form' ) ); ?></label>
		</fieldset>
	</td>
	</tr>
<?php endif; ?>

	<tr>
	<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-name' ); ?>"><?php echo esc_html( __( 'Name', 'sketchus-custom-form' ) ); ?></label></th>
	<td><input type="text" name="name" class="tg-name oneline" id="<?php echo esc_attr( $args['content'] . '-name' ); ?>" /></td>
	</tr>

	<tr>
	<th scope="row"><?php echo esc_html( __( 'Options', 'sketchus-custom-form' ) ); ?></th>
	<td>
		<fieldset>
		<legend class="screen-reader-text"><?php echo esc_html( __( 'Options', 'sketchus-custom-form' ) ); ?></legend>
		<textarea name="values" class="values" id="<?php echo esc_attr( $args['content'] . '-values' ); ?>"></textarea>
		<label for="<?php echo esc_attr( $args['content'] . '-values' ); ?>"><span class="description"><?php echo esc_html( __( "One option per line.", 'sketchus-custom-form' ) ); ?></span></label><br />
		<label><input type="checkbox" name="label_first" class="option" /> <?php echo esc_html( __( 'Put a label first, a checkbox last', 'sketchus-custom-form' ) ); ?></label><br />
		<label><input type="checkbox" name="use_label_element" class="option" /> <?php echo esc_html( __( 'Wrap each item with label element', 'sketchus-custom-form' ) ); ?></label>
<?php if ( 'checkbox' == $type ) : ?>
		<br /><label><input type="checkbox" name="exclusive" class="option" /> <?php echo esc_html( __( 'Make checkboxes exclusive', 'sketchus-custom-form' ) ); ?></label>
<?php endif; ?>
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
