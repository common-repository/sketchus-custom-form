<?php

// don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die( '-1' );
}

function skfom_admin_save_button( $post_id ) {
	static $button = '';

	if ( ! empty( $button ) ) {
		echo $button;
		return;
	}

	$nonce = wp_create_nonce( 'skfom-save-contact-form_' . $post_id );

	$onclick = sprintf(
		"this.form._wpnonce.value = '%s';"
		. " this.form.action.value = 'save';"
		. " return true;",
		$nonce );

	$button = sprintf(
		'<input type="submit" class="button-primary" name="skfom-save" value="%1$s" onclick="%2$s" />',
		esc_attr( __( 'Save', 'sketchus-custom-form' ) ),
		$onclick );

	echo $button;
}

?><div class="wrap">

<h1><?php
	if ( $post->initial() ) {
		echo esc_html( __( 'Add New Contact Form', 'sketchus-custom-form' ) );
	} else {
		echo esc_html( __( 'Edit Contact Form', 'sketchus-custom-form' ) );

		if ( current_user_can( 'skfom_edit_contact_forms' ) ) {
			echo ' <a href="' . esc_url( menu_page_url( 'skfom-new', false ) ) . '" class="add-new-h2">' . esc_html( __( 'Add New', 'sketchus-custom-form' ) ) . '</a>';
		}
	}
?></h1>

<?php do_action( 'skfom_admin_notices' ); ?>

<?php
if ( $post ) :

	if ( current_user_can( 'skfom_edit_contact_form', $post_id ) ) {
		$disabled = '';
	} else {
		$disabled = ' disabled="disabled"';
	}
?>

<form method="post" action="<?php echo esc_url( add_query_arg( array( 'post' => $post_id ), menu_page_url( 'skfom', false ) ) ); ?>" id="skfom-admin-form-element"<?php do_action( 'skfom_post_edit_form_tag' ); ?>>
<?php
	if ( current_user_can( 'skfom_edit_contact_form', $post_id ) ) {
		wp_nonce_field( 'skfom-save-contact-form_' . $post_id );
	}
?>
<input type="hidden" id="post_ID" name="post_ID" value="<?php echo (int) $post_id; ?>" />
<input type="hidden" id="skfom-locale" name="skfom-locale" value="<?php echo esc_attr( $post->locale ); ?>" />
<input type="hidden" id="hiddenaction" name="action" value="save" />
<input type="hidden" id="active-tab" name="active-tab" value="<?php echo isset( $_GET['active-tab'] ) ? (int) $_GET['active-tab'] : '0'; ?>" />

<div id="poststuff">
<div id="post-body" class="metabox-holder columns-2">
<div id="post-body-content">
<div id="titlediv">
<div id="titlewrap">
	<label class="screen-reader-text" id="title-prompt-text" for="title"><?php echo esc_html( __( 'Enter title here', 'sketchus-custom-form' ) ); ?></label>
<?php
	$posttitle_atts = array(
		'type' => 'text',
		'name' => 'post_title',
		'size' => 30,
		'value' => $post->initial() ? '' : $post->title(),
		'id' => 'title',
		'spellcheck' => 'true',
		'autocomplete' => 'off',
		'disabled' => current_user_can( 'skfom_edit_contact_form', $post_id )
			? '' : 'disabled' );

	echo sprintf( '<input %s />', skfom_format_atts( $posttitle_atts ) );
?>
</div><!-- #titlewrap -->

<div class="inside">
<?php
	if ( ! $post->initial() ) :
?>
	<p class="description">
	<label for="skfom-shortcode"><?php echo esc_html( __( "Copy this shortcode and paste it into your post, page, or text widget content:", 'sketchus-custom-form' ) ); ?></label>
	<span class="shortcode wp-ui-highlight"><input type="text" id="skfom-shortcode" onfocus="this.select();" readonly="readonly" class="large-text code" value="<?php echo esc_attr( $post->shortcode() ); ?>" /></span>
	</p>
<?php
		if ( $old_shortcode = $post->shortcode( array( 'use_old_format' => true ) ) ) :
?>
	<p class="description">
	<label for="skfom-shortcode-old"><?php echo esc_html( __( "You can also use this old-style shortcode:", 'sketchus-custom-form' ) ); ?></label>
	<span class="shortcode old"><input type="text" id="skfom-shortcode-old" onfocus="this.select();" readonly="readonly" class="large-text code" value="<?php echo esc_attr( $old_shortcode ); ?>" /></span>
	</p>
<?php
		endif;
	endif;
?>
</div>
</div><!-- #titlediv -->
</div><!-- #post-body-content -->

<div id="postbox-container-1" class="postbox-container">
<?php if ( current_user_can( 'skfom_edit_contact_form', $post_id ) ) : ?>
<div id="submitdiv" class="postbox">
<h3><?php echo esc_html( __( 'Status', 'sketchus-custom-form' ) ); ?></h3>
<div class="inside">
<div class="submitbox" id="submitpost">

<div id="minor-publishing-actions">

<div class="hidden">
	<input type="submit" class="button-primary" name="skfom-save" value="<?php echo esc_attr( __( 'Save', 'sketchus-custom-form' ) ); ?>" />
</div>

<?php
	if ( ! $post->initial() ) :
		$copy_nonce = wp_create_nonce( 'skfom-copy-contact-form_' . $post_id );
?>
	<input type="submit" name="skfom-copy" class="copy button" value="<?php echo esc_attr( __( 'Duplicate', 'sketchus-custom-form' ) ); ?>" <?php echo "onclick=\"this.form._wpnonce.value = '$copy_nonce'; this.form.action.value = 'copy'; return true;\""; ?> />
<?php endif; ?>
</div><!-- #minor-publishing-actions -->

<div id="major-publishing-actions">

<?php
	if ( ! $post->initial() ) :
		$delete_nonce = wp_create_nonce( 'skfom-delete-contact-form_' . $post_id );
?>
<div id="delete-action">
	<input type="submit" name="skfom-delete" class="delete submitdelete" value="<?php echo esc_attr( __( 'Delete', 'sketchus-custom-form' ) ); ?>" <?php echo "onclick=\"if (confirm('" . esc_js( __( "You are about to delete this contact form.\n  'Cancel' to stop, 'OK' to delete.", 'sketchus-custom-form' ) ) . "')) {this.form._wpnonce.value = '$delete_nonce'; this.form.action.value = 'delete'; return true;} return false;\""; ?> />
</div><!-- #delete-action -->
<?php endif; ?>

<div id="publishing-action">
	<span class="spinner"></span>
	<?php skfom_admin_save_button( $post_id ); ?>
</div>
<div class="clear"></div>
</div><!-- #major-publishing-actions -->
</div><!-- #submitpost -->
</div>
</div><!-- #submitdiv -->
<?php endif; ?>

<div>
<p><a href="http://dev.sketchus.com/?wpref" title="Donate Now"><img src="<?php echo skfom_plugin_url("/images/donate.png"); ?>"></a></p>
</div>
<div id="informationdiv" class="postbox">
<h3><?php echo esc_html( __( 'Information', 'sketchus-custom-form' ) ); ?></h3>
<div class="inside">
<ul>
<li><?php echo skfom_link( __( 'http://dev.sketchus.com/custom-form/support/', 'sketchus-custom-form' ), __( 'Support', 'sketchus-custom-form' ) ); ?></li>
<li><?php echo skfom_link( __( 'http://dev.sketchus.com/custom-form/docs/', 'sketchus-custom-form' ), __( 'Documentations', 'sketchus-custom-form' ) ); ?></li>
<li><?php echo skfom_link( __( 'http://dev.sketchus.com/custom-form/faq/', 'sketchus-custom-form' ), __( 'FAQs', 'sketchus-custom-form' ) ); ?></li>
</ul>
</div>
</div><!-- #informationdiv -->

<div>
<p><a href="https://www.hostrider.us/seo/?wpref" title="SEO Services"><img src="<?php echo skfom_plugin_url("/images/seo.png"); ?>"></a></p>
</div><!-- #informationdiv -->

</div><!-- #postbox-container-1 -->

<div id="postbox-container-2" class="postbox-container">
<div id="contact-form-editor">


<?php

	$editor = new SKFOM_Editor( $post );
	$panels = array();

	if ( current_user_can( 'skfom_edit_contact_form', $post_id ) ) {
		$panels = array(
			'form-panel' => array(
				'title' => __( 'HTML Form', 'sketchus-custom-form' ),
				'callback' => 'skfom_editor_panel_form' ),
			'mail-panel' => array(
				'title' => __( 'Mailbox Settings', 'sketchus-custom-form' ),
				'callback' => 'skfom_editor_panel_mail' ),
			'messages-panel' => array(
				'title' => __( 'Custom Messages', 'sketchus-custom-form' ),
				'callback' => 'skfom_editor_panel_messages' ) );

		$additional_settings = trim( $post->prop( 'additional_settings' ) );
		$additional_settings = explode( "\n", $additional_settings );
		$additional_settings = array_filter( $additional_settings );
		$additional_settings = count( $additional_settings );

		$panels['additional-settings-panel'] = array(
			'title' => $additional_settings
				? sprintf(
					__( 'More Settings (%d)', 'sketchus-custom-form' ),
					$additional_settings )
				: __( 'More Settings', 'sketchus-custom-form' ),
			'callback' => 'skfom_editor_panel_additional_settings' );
	}

	$panels = apply_filters( 'skfom_editor_panels', $panels );

	foreach ( $panels as $id => $panel ) {
		$editor->add_panel( $id, $panel['title'], $panel['callback'] );
	}

	$editor->display();
?>
</div><!-- #contact-form-editor -->

<?php if ( current_user_can( 'skfom_edit_contact_form', $post_id ) ) : ?>
<p class="submit"><?php skfom_admin_save_button( $post_id ); ?></p>
<?php endif; ?>

</div><!-- #postbox-container-2 -->

</div><!-- #post-body -->
<br class="clear" />
</div><!-- #poststuff -->
</form>

<?php endif; ?>

</div><!-- .wrap -->

<?php

	$tag_generator = SKFOM_TagGenerator::get_instance();
	$tag_generator->print_panels( $post );

	do_action( 'skfom_admin_footer', $post );
