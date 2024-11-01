<?php
/**
** A base module for [file] and [file*]
**/

/* Shortcode handler */

add_action( 'skfom_init', 'skfom_add_shortcode_file' );

function skfom_add_shortcode_file() {
	skfom_add_shortcode( array( 'file', 'file*' ),
		'skfom_file_shortcode_handler', true );
}

function skfom_file_shortcode_handler( $tag ) {
	$tag = new SKFOM_Shortcode( $tag );

	if ( empty( $tag->name ) ) {
		return '';
	}

	$validation_error = skfom_get_validation_error( $tag->name );

	$class = skfom_form_controls_class( $tag->type );

	if ( $validation_error ) {
		$class .= ' skfom-not-valid';
	}

	$atts = array();

	$atts['size'] = $tag->get_size_option( '40' );
	$atts['class'] = $tag->get_class_option( $class );
	$atts['id'] = $tag->get_id_option();
	$atts['tabindex'] = $tag->get_option( 'tabindex', 'int', true );

	if ( $tag->is_required() ) {
		$atts['aria-required'] = 'true';
	}

	$atts['aria-invalid'] = $validation_error ? 'true' : 'false';

	$atts['type'] = 'file';
	$atts['name'] = $tag->name;

	$atts = skfom_format_atts( $atts );

	$html = sprintf(
		'<span class="skfom-form-control-wrap %1$s"><input %2$s />%3$s</span>',
		sanitize_html_class( $tag->name ), $atts, $validation_error );

	return $html;
}


/* Encode type filter */

add_filter( 'skfom_form_enctype', 'skfom_file_form_enctype_filter' );

function skfom_file_form_enctype_filter( $enctype ) {
	$multipart = (bool) skfom_scan_shortcode( array( 'type' => array( 'file', 'file*' ) ) );

	if ( $multipart ) {
		$enctype = 'multipart/form-data';
	}

	return $enctype;
}


/* Validation + upload handling filter */

add_filter( 'skfom_validate_file', 'skfom_file_validation_filter', 10, 2 );
add_filter( 'skfom_validate_file*', 'skfom_file_validation_filter', 10, 2 );

function skfom_file_validation_filter( $result, $tag ) {
	$tag = new SKFOM_Shortcode( $tag );

	$name = $tag->name;
	$id = $tag->get_id_option();

	$file = isset( $_FILES[$name] ) ? $_FILES[$name] : null;

	if ( $file['error'] && UPLOAD_ERR_NO_FILE != $file['error'] ) {
		$result->invalidate( $tag, skfom_get_message( 'upload_failed_php_error' ) );
		return $result;
	}

	if ( empty( $file['tmp_name'] ) && $tag->is_required() ) {
		$result->invalidate( $tag, skfom_get_message( 'invalid_required' ) );
		return $result;
	}

	if ( ! is_uploaded_file( $file['tmp_name'] ) )
		return $result;

	$allowed_file_types = array();

	if ( $file_types_a = $tag->get_option( 'filetypes' ) ) {
		foreach ( $file_types_a as $file_types ) {
			$file_types = explode( '|', $file_types );

			foreach ( $file_types as $file_type ) {
				$file_type = trim( $file_type, '.' );
				$file_type = str_replace( array( '.', '+', '*', '?' ),
					array( '\.', '\+', '\*', '\?' ), $file_type );
				$allowed_file_types[] = $file_type;
			}
		}
	}

	$allowed_file_types = array_unique( $allowed_file_types );
	$file_type_pattern = implode( '|', $allowed_file_types );

	$allowed_size = 1048576; // default size 1 MB

	if ( $file_size_a = $tag->get_option( 'limit' ) ) {
		$limit_pattern = '/^([1-9][0-9]*)([kKmM]?[bB])?$/';

		foreach ( $file_size_a as $file_size ) {
			if ( preg_match( $limit_pattern, $file_size, $matches ) ) {
				$allowed_size = (int) $matches[1];

				if ( ! empty( $matches[2] ) ) {
					$kbmb = strtolower( $matches[2] );

					if ( 'kb' == $kbmb )
						$allowed_size *= 1024;
					elseif ( 'mb' == $kbmb )
						$allowed_size *= 1024 * 1024;
				}

				break;
			}
		}
	}

	/* File type validation */

	// Default file-type restriction
	if ( '' == $file_type_pattern )
		$file_type_pattern = 'jpg|jpeg|png|gif|pdf|doc|docx|ppt|pptx|odt|avi|ogg|m4a|mov|mp3|mp4|mpg|wav|wmv';

	$file_type_pattern = trim( $file_type_pattern, '|' );
	$file_type_pattern = '(' . $file_type_pattern . ')';
	$file_type_pattern = '/\.' . $file_type_pattern . '$/i';

	if ( ! preg_match( $file_type_pattern, $file['name'] ) ) {
		$result->invalidate( $tag, skfom_get_message( 'upload_file_type_invalid' ) );
		return $result;
	}

	/* File size validation */

	if ( $file['size'] > $allowed_size ) {
		$result->invalidate( $tag, skfom_get_message( 'upload_file_too_large' ) );
		return $result;
	}

	skfom_init_uploads(); // Confirm upload dir
	$uploads_dir = skfom_upload_tmp_dir();
	$uploads_dir = skfom_maybe_add_random_dir( $uploads_dir );

	$filename = $file['name'];
	$filename = skfom_canonicalize( $filename );
	$filename = sanitize_file_name( $filename );
	$filename = skfom_antiscript_file_name( $filename );
	$filename = wp_unique_filename( $uploads_dir, $filename );

	$new_file = trailingslashit( $uploads_dir ) . $filename;

	if ( false === @move_uploaded_file( $file['tmp_name'], $new_file ) ) {
		$result->invalidate( $tag, skfom_get_message( 'upload_failed' ) );
		return $result;
	}

	// Make sure the uploaded file is only readable for the owner process
	@chmod( $new_file, 0400 );

	if ( $submission = SKFOM_Submission::get_instance() ) {
		$submission->add_uploaded_file( $name, $new_file );
	}

	return $result;
}


/* Messages */

add_filter( 'skfom_messages', 'skfom_file_messages' );

function skfom_file_messages( $messages ) {
	return array_merge( $messages, array(
		'upload_failed' => array(
			'description' => __( "Uploading a file fails for any reason", 'sketchus-custom-form' ),
			'default' => __( "There was an unknown error uploading the file.", 'sketchus-custom-form' )
		),

		'upload_file_type_invalid' => array(
			'description' => __( "Uploaded file is not allowed for file type", 'sketchus-custom-form' ),
			'default' => __( "You are not allowed to upload files of this type.", 'sketchus-custom-form' )
		),

		'upload_file_too_large' => array(
			'description' => __( "Uploaded file is too large", 'sketchus-custom-form' ),
			'default' => __( "The file is too big.", 'sketchus-custom-form' )
		),

		'upload_failed_php_error' => array(
			'description' => __( "Uploading a file fails for PHP error", 'sketchus-custom-form' ),
			'default' => __( "There was an error uploading the file.", 'sketchus-custom-form' )
		)
	) );
}


/* Tag generator */

add_action( 'skfom_admin_init', 'skfom_add_tag_generator_file', 50 );

function skfom_add_tag_generator_file() {
	$tag_generator = SKFOM_TagGenerator::get_instance();
	$tag_generator->add( 'file', __( 'file', 'sketchus-custom-form' ),
		'skfom_tag_generator_file' );
}

function skfom_tag_generator_file( $contact_form, $args = '' ) {
	$args = wp_parse_args( $args, array() );
	$type = 'file';

	$description = __( "Generate a form-tag for a file uploading field. For more details, see %s.", 'sketchus-custom-form' );

	$desc_link = skfom_link( __( 'http://dev.sketchus.com/custom-form/file-uploading-and-attachment/', 'sketchus-custom-form' ), __( 'File Uploading and Attachment', 'sketchus-custom-form' ) );

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
	<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-limit' ); ?>"><?php echo esc_html( __( "File size limit (bytes)", 'sketchus-custom-form' ) ); ?></label></th>
	<td><input type="text" name="limit" class="filesize oneline option" id="<?php echo esc_attr( $args['content'] . '-limit' ); ?>" /></td>
	</tr>

	<tr>
	<th scope="row"><label for="<?php echo esc_attr( $args['content'] . '-filetypes' ); ?>"><?php echo esc_html( __( 'Acceptable file types', 'sketchus-custom-form' ) ); ?></label></th>
	<td><input type="text" name="filetypes" class="filetype oneline option" id="<?php echo esc_attr( $args['content'] . '-filetypes' ); ?>" /></td>
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

	<p class="description mail-tag"><label for="<?php echo esc_attr( $args['content'] . '-mailtag' ); ?>"><?php echo sprintf( esc_html( __( "To attach the file uploaded through this field to mail, you need to insert the corresponding mail-tag (%s) into the File Attachments field on the Mail tab.", 'sketchus-custom-form' ) ), '<strong><span class="mail-tag"></span></strong>' ); ?><input type="text" class="mail-tag code hidden" readonly="readonly" id="<?php echo esc_attr( $args['content'] . '-mailtag' ); ?>" /></label></p>
</div>
<?php
}


/* Warning message */

add_action( 'skfom_admin_notices', 'skfom_file_display_warning_message' );

function skfom_file_display_warning_message() {
	if ( ! $contact_form = skfom_get_current_contact_form() ) {
		return;
	}

	$has_tags = (bool) $contact_form->form_scan_shortcode(
		array( 'type' => array( 'file', 'file*' ) ) );

	if ( ! $has_tags ) {
		return;
	}

	$uploads_dir = skfom_upload_tmp_dir();
	skfom_init_uploads();

	if ( ! is_dir( $uploads_dir ) || ! wp_is_writable( $uploads_dir ) ) {
		$message = sprintf( __( 'This contact form contains file uploading fields, but the temporary folder for the files (%s) does not exist or is not writable. You can create the folder or change its permission manually.', 'sketchus-custom-form' ), $uploads_dir );

		echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
	}
}


/* File uploading functions */

function skfom_init_uploads() {
	$dir = skfom_upload_tmp_dir();
	wp_mkdir_p( $dir );

	$htaccess_file = trailingslashit( $dir ) . '.htaccess';

	if ( file_exists( $htaccess_file ) ) {
		return;
	}

	if ( $handle = @fopen( $htaccess_file, 'w' ) ) {
		fwrite( $handle, "Deny from all\n" );
		fclose( $handle );
	}
}

function skfom_maybe_add_random_dir( $dir ) {
	do {
		$rand_max = mt_getrandmax();
		$rand = zeroise( mt_rand( 0, $rand_max ), strlen( $rand_max ) );
		$dir_new = path_join( $dir, $rand );
	} while ( file_exists( $dir_new ) );

	if ( wp_mkdir_p( $dir_new ) ) {
		return $dir_new;
	}

	return $dir;
}

function skfom_upload_tmp_dir() {
	if ( defined( 'SKFOM_UPLOADS_TMP_DIR' ) )
		return SKFOM_UPLOADS_TMP_DIR;
	else
		return skfom_upload_dir( 'dir' ) . '/skfom_uploads';
}

add_action( 'template_redirect', 'skfom_cleanup_upload_files', 20 );

function skfom_cleanup_upload_files() {
	if ( is_admin() || 'GET' != $_SERVER['REQUEST_METHOD']
	|| is_robots() || is_feed() || is_trackback() ) {
		return;
	}

	$dir = trailingslashit( skfom_upload_tmp_dir() );

	if ( ! is_dir( $dir ) || ! is_readable( $dir ) || ! wp_is_writable( $dir ) ) {
		return;
	}

	if ( $handle = @opendir( $dir ) ) {
		while ( false !== ( $file = readdir( $handle ) ) ) {
			if ( $file == "." || $file == ".." || $file == ".htaccess" ) {
				continue;
			}

			$mtime = @filemtime( $dir . $file );

			if ( $mtime && time() < $mtime + 60 ) { // less than 60 secs old
				continue;
			}

			skfom_rmdir_p( path_join( $dir, $file ) );
		}

		closedir( $handle );
	}
}
