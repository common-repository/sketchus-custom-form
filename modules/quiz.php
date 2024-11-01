<?php
/**
** A base module for [quiz]
**/

/* Shortcode handler */

add_action( 'skfom_init', 'skfom_add_shortcode_quiz' );

function skfom_add_shortcode_quiz() {
	skfom_add_shortcode( 'quiz', 'skfom_quiz_shortcode_handler', true );
}

function skfom_quiz_shortcode_handler( $tag ) {
	$tag = new SKFOM_Shortcode( $tag );

	if ( empty( $tag->name ) )
		return '';

	$validation_error = skfom_get_validation_error( $tag->name );

	$class = skfom_form_controls_class( $tag->type );

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
	$atts['aria-required'] = 'true';
	$atts['aria-invalid'] = $validation_error ? 'true' : 'false';

	$pipes = $tag->pipes;

	if ( $pipes instanceof SKFOM_Pipes && ! $pipes->zero() ) {
		$pipe = $pipes->random_pipe();
		$question = $pipe->before;
		$answer = $pipe->after;
	} else {
		// default quiz
		$question = '1+1=?';
		$answer = '2';
	}

	$answer = skfom_canonicalize( $answer );

	$atts['type'] = 'text';
	$atts['name'] = $tag->name;

	$atts = skfom_format_atts( $atts );

	$html = sprintf(
		'<span class="skfom-form-control-wrap %1$s"><label><span class="skfom-quiz-label">%2$s</span> <input %3$s /></label><input type="hidden" name="_skfom_quiz_answer_%4$s" value="%5$s" />%6$s</span>',
		sanitize_html_class( $tag->name ),
		esc_html( $question ), $atts, $tag->name,
		wp_hash( $answer, 'skfom_quiz' ), $validation_error );

	return $html;
}


/* Validation filter */

add_filter( 'skfom_validate_quiz', 'skfom_quiz_validation_filter', 10, 2 );

function skfom_quiz_validation_filter( $result, $tag ) {
	$tag = new SKFOM_Shortcode( $tag );

	$name = $tag->name;

	$answer = isset( $_POST[$name] ) ? skfom_canonicalize( $_POST[$name] ) : '';
	$answer = wp_unslash( $answer );

	$answer_hash = wp_hash( $answer, 'skfom_quiz' );

	$expected_hash = isset( $_POST['_skfom_quiz_answer_' . $name] )
		? (string) $_POST['_skfom_quiz_answer_' . $name]
		: '';

	if ( $answer_hash != $expected_hash ) {
		$result->invalidate( $tag, skfom_get_message( 'quiz_answer_not_correct' ) );
	}

	return $result;
}


/* Ajax echo filter */

add_filter( 'skfom_ajax_onload', 'skfom_quiz_ajax_refill' );
add_filter( 'skfom_ajax_json_echo', 'skfom_quiz_ajax_refill' );

function skfom_quiz_ajax_refill( $items ) {
	if ( ! is_array( $items ) )
		return $items;

	$fes = skfom_scan_shortcode( array( 'type' => 'quiz' ) );

	if ( empty( $fes ) )
		return $items;

	$refill = array();

	foreach ( $fes as $fe ) {
		$name = $fe['name'];
		$pipes = $fe['pipes'];

		if ( empty( $name ) )
			continue;

		if ( $pipes instanceof SKFOM_Pipes && ! $pipes->zero() ) {
			$pipe = $pipes->random_pipe();
			$question = $pipe->before;
			$answer = $pipe->after;
		} else {
			// default quiz
			$question = '1+1=?';
			$answer = '2';
		}

		$answer = skfom_canonicalize( $answer );

		$refill[$name] = array( $question, wp_hash( $answer, 'skfom_quiz' ) );
	}

	if ( ! empty( $refill ) )
		$items['quiz'] = $refill;

	return $items;
}


/* Messages */

add_filter( 'skfom_messages', 'skfom_quiz_messages' );

function skfom_quiz_messages( $messages ) {
	return array_merge( $messages, array( 'quiz_answer_not_correct' => array(
		'description' => __( "Sender doesn't enter the correct answer to the quiz", 'sketchus-custom-form' ),
		'default' => __( "The answer to the quiz is incorrect.", 'sketchus-custom-form' )
	) ) );
}


/* Tag generator */

add_action( 'skfom_admin_init', 'skfom_add_tag_generator_quiz', 40 );

function skfom_add_tag_generator_quiz() {
	$tag_generator = SKFOM_TagGenerator::get_instance();
	$tag_generator->add( 'quiz', __( 'quiz', 'sketchus-custom-form' ),
		'skfom_tag_generator_quiz' );
}

function skfom_tag_generator_quiz( $contact_form, $args = '' ) {
	$args = wp_parse_args( $args, array() );
	$type = 'quiz';

	$description = __( "Generate a form-tag for a question-answer pair. For more details, see %s.", 'sketchus-custom-form' );

	$desc_link = skfom_link( __( 'http://dev.sketchus.com/custom-form/quiz/', 'sketchus-custom-form' ), __( 'Quiz', 'sketchus-custom-form' ) );

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
	<th scope="row"><?php echo esc_html( __( 'Questions and answers', 'sketchus-custom-form' ) ); ?></th>
	<td>
		<fieldset>
		<legend class="screen-reader-text"><?php echo esc_html( __( 'Questions and answers', 'sketchus-custom-form' ) ); ?></legend>
		<textarea name="values" class="values" id="<?php echo esc_attr( $args['content'] . '-values' ); ?>"></textarea><br />
		<label for="<?php echo esc_attr( $args['content'] . '-values' ); ?>"><span class="description"><?php echo esc_html( __( "One pipe-separated question-answer pair (e.g. The capital of Brazil?|Rio) per line.", 'sketchus-custom-form' ) ); ?></span></label>
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
