<?php

require_once SKFOM_PLUGIN_DIR . '/admin/includes/admin-functions.php';
require_once SKFOM_PLUGIN_DIR . '/admin/includes/help-tabs.php';
require_once SKFOM_PLUGIN_DIR . '/admin/includes/tag-generator.php';

add_action( 'admin_init', 'skfom_admin_init' );

function skfom_admin_init() {
	do_action( 'skfom_admin_init' );
}

add_action( 'admin_menu', 'skfom_admin_menu', 9 );

function skfom_admin_menu() {
	global $_wp_last_object_menu;

	$_wp_last_object_menu++;

	add_menu_page( __( 'Sketchus Custom Form', 'sketchus-custom-form' ),
		__( 'Custom Forms', 'sketchus-custom-form' ),
		'skfom_read_contact_forms', 'skfom',
		'skfom_admin_management_page', 'dashicons-index-card',
		$_wp_last_object_menu );

	$edit = add_submenu_page( 'skfom',
		__( 'Edit Contact Form', 'sketchus-custom-form' ),
		__( 'View Custom Forms', 'sketchus-custom-form' ),
		'skfom_read_contact_forms', 'skfom',
		'skfom_admin_management_page' );

	add_action( 'load-' . $edit, 'skfom_load_contact_form_admin' );

	$addnew = add_submenu_page( 'skfom',
		__( 'Add New Contact Form', 'sketchus-custom-form' ),
		__( 'Create New Form', 'sketchus-custom-form' ),
		'skfom_edit_contact_forms', 'skfom-new',
		'skfom_admin_add_new_page' );

	add_action( 'load-' . $addnew, 'skfom_load_contact_form_admin' );

	$integration = SKFOM_Integration::get_instance();

	if ( $integration->service_exists() ) {
		$integration = add_submenu_page( 'skfom',
			__( 'Setup with Other Services', 'sketchus-custom-form' ),
			__( 'Extension', 'sketchus-custom-form' ),
			'skfom_manage_integration', 'skfom-integration',
			'skfom_admin_integration_page' );

		add_action( 'load-' . $integration, 'skfom_load_integration_page' );
	}
}

add_filter( 'set-screen-option', 'skfom_set_screen_options', 10, 3 );

function skfom_set_screen_options( $result, $option, $value ) {
	$skfom_screens = array(
		'skcf_contact_forms_per_page' );

	if ( in_array( $option, $skfom_screens ) )
		$result = $value;

	return $result;
}

function skfom_load_contact_form_admin() {
	global $plugin_page;

	$action = skfom_current_action();

	if ( 'save' == $action ) {
		$id = $_POST['post_ID'];
		check_admin_referer( 'skfom-save-contact-form_' . $id );

		if ( ! current_user_can( 'skfom_edit_contact_form', $id ) )
			wp_die( __( 'You are not allowed to edit this item.', 'sketchus-custom-form' ) );

		$id = skfom_save_contact_form( $id );

		$query = array(
			'message' => ( -1 == $_POST['post_ID'] ) ? 'created' : 'saved',
			'post' => $id,
			'active-tab' => isset( $_POST['active-tab'] ) ? (int) $_POST['active-tab'] : 0 );

		$redirect_to = add_query_arg( $query, menu_page_url( 'skfom', false ) );
		wp_safe_redirect( $redirect_to );
		exit();
	}

	if ( 'copy' == $action ) {
		$id = empty( $_POST['post_ID'] )
			? absint( $_REQUEST['post'] )
			: absint( $_POST['post_ID'] );

		check_admin_referer( 'skfom-copy-contact-form_' . $id );

		if ( ! current_user_can( 'skfom_edit_contact_form', $id ) )
			wp_die( __( 'You are not allowed to edit this item.', 'sketchus-custom-form' ) );

		$query = array();

		if ( $contact_form = skfom_contact_form( $id ) ) {
			$new_contact_form = $contact_form->copy();
			$new_contact_form->save();

			$query['post'] = $new_contact_form->id();
			$query['message'] = 'created';
		}

		$redirect_to = add_query_arg( $query, menu_page_url( 'skfom', false ) );

		wp_safe_redirect( $redirect_to );
		exit();
	}

	if ( 'delete' == $action ) {
		if ( ! empty( $_POST['post_ID'] ) )
			check_admin_referer( 'skfom-delete-contact-form_' . $_POST['post_ID'] );
		elseif ( ! is_array( $_REQUEST['post'] ) )
			check_admin_referer( 'skfom-delete-contact-form_' . $_REQUEST['post'] );
		else
			check_admin_referer( 'bulk-posts' );

		$posts = empty( $_POST['post_ID'] )
			? (array) $_REQUEST['post']
			: (array) $_POST['post_ID'];

		$deleted = 0;

		foreach ( $posts as $post ) {
			$post = SKFOM_ContactForm::get_instance( $post );

			if ( empty( $post ) )
				continue;

			if ( ! current_user_can( 'skfom_delete_contact_form', $post->id() ) )
				wp_die( __( 'You are not allowed to delete this item.', 'sketchus-custom-form' ) );

			if ( ! $post->delete() )
				wp_die( __( 'Error in deleting.', 'sketchus-custom-form' ) );

			$deleted += 1;
		}

		$query = array();

		if ( ! empty( $deleted ) )
			$query['message'] = 'deleted';

		$redirect_to = add_query_arg( $query, menu_page_url( 'skfom', false ) );

		wp_safe_redirect( $redirect_to );
		exit();
	}

	if ( 'validate' == $action && skfom_validate_configuration() ) {
		if ( 'POST' == $_SERVER['REQUEST_METHOD'] ) {
			check_admin_referer( 'skfom-bulk-validate' );

			if ( ! current_user_can( 'skfom_edit_contact_forms' ) ) {
				wp_die( __( "You are not allowed to validate configuration.", 'sketchus-custom-form' ) );
			}

			$contact_forms = SKFOM_ContactForm::find();
			$result = array(
				'timestamp' => current_time( 'timestamp' ),
				'version' => SKFOM_VERSION,
				'count_valid' => 0,
				'count_invalid' => 0 );

			foreach ( $contact_forms as $contact_form ) {
				$contact_form->validate_configuration();

				if ( $contact_form->get_config_errors() ) {
					$result['count_invalid'] += 1;
				} else {
					$result['count_valid'] += 1;
				}
			}

			SKFOM::update_option( 'bulk_validate', $result );

			$query = array(
				'message' => 'validated' );

			$redirect_to = add_query_arg( $query, menu_page_url( 'skfom', false ) );
			wp_safe_redirect( $redirect_to );
			exit();
		}
	}

	$_GET['post'] = isset( $_GET['post'] ) ? $_GET['post'] : '';

	$post = null;

	if ( 'skfom-new' == $plugin_page ) {
		$post = SKFOM_ContactForm::get_template( array(
			'locale' => isset( $_GET['locale'] ) ? $_GET['locale'] : null ) );
	} elseif ( ! empty( $_GET['post'] ) ) {
		$post = SKFOM_ContactForm::get_instance( $_GET['post'] );
	}

	$current_screen = get_current_screen();

	$help_tabs = new SKFOM_Help_Tabs( $current_screen );

	if ( $post && current_user_can( 'skfom_edit_contact_form', $post->id() ) ) {
		$help_tabs->set_help_tabs( 'edit' );
	} else {
		$help_tabs->set_help_tabs( 'list' );

		if ( ! class_exists( 'SKFOM_Contact_Form_List_Table' ) ) {
			require_once SKFOM_PLUGIN_DIR . '/admin/includes/class-contact-forms-list-table.php';
		}

		add_filter( 'manage_' . $current_screen->id . '_columns',
			array( 'SKFOM_Contact_Form_List_Table', 'define_columns' ) );

		add_screen_option( 'per_page', array(
			'default' => 20,
			'option' => 'skcf_contact_forms_per_page' ) );
	}
}

add_action( 'admin_enqueue_scripts', 'skfom_admin_enqueue_scripts' );

function skfom_admin_enqueue_scripts( $hook_suffix ) {
	if ( false === strpos( $hook_suffix, 'skfom' ) ) {
		return;
	}

	wp_enqueue_style( 'sketchus-custom-form-admin',
		skfom_plugin_url( 'admin/css/styles.css' ),
		array(), SKFOM_VERSION, 'all' );

	if ( skfom_is_rtl() ) {
		wp_enqueue_style( 'sketchus-custom-form-admin-rtl',
			skfom_plugin_url( 'admin/css/styles-rtl.css' ),
			array(), SKFOM_VERSION, 'all' );
	}

	wp_enqueue_script( 'skfom-admin',
		skfom_plugin_url( 'admin/js/scripts.js' ),
		array( 'jquery', 'jquery-ui-tabs' ),
		SKFOM_VERSION, true );

	wp_localize_script( 'skfom-admin', '_skfom', array(
		'pluginUrl' => skfom_plugin_url(),
		'saveAlert' => __( "The changes you made will be lost if you navigate away from this page.", 'sketchus-custom-form' ),
		'activeTab' => isset( $_GET['active-tab'] ) ? (int) $_GET['active-tab'] : 0 ) );

	add_thickbox();

	wp_enqueue_script( 'skfom-admin-taggenerator',
		skfom_plugin_url( 'admin/js/tag-generator.js' ),
		array( 'jquery', 'thickbox', 'skfom-admin' ), SKFOM_VERSION, true );
}

function skfom_admin_management_page() {
	if ( $post = skfom_get_current_contact_form() ) {
		$post_id = $post->initial() ? -1 : $post->id();

		require_once SKFOM_PLUGIN_DIR . '/admin/includes/editor.php';
		require_once SKFOM_PLUGIN_DIR . '/admin/edit-contact-form.php';
		return;
	}

	if ( 'validate' == skfom_current_action()
	&& skfom_validate_configuration()
	&& current_user_can( 'skfom_edit_contact_forms' ) ) {
		skfom_admin_bulk_validate_page();
		return;
	}

	$list_table = new skfom_Contact_Form_List_Table();
	$list_table->prepare_items();

?>
<div class="wrap">

<h1><?php
	echo esc_html( __( 'Sketchus Custom Forms', 'sketchus-custom-form' ) );

	if ( current_user_can( 'skfom_edit_contact_forms' ) ) {
		echo ' <a href="' . esc_url( menu_page_url( 'skfom-new', false ) ) . '" class="add-new-h2">' . esc_html( __( 'Add New', 'sketchus-custom-form' ) ) . '</a>';
	}

	if ( ! empty( $_REQUEST['s'] ) ) {
		echo sprintf( '<span class="subtitle">'
			. __( 'Search results for &#8220;%s&#8221;', 'sketchus-custom-form' )
			. '</span>', esc_html( $_REQUEST['s'] ) );
	}
?></h1>

<?php do_action( 'skfom_admin_notices' ); ?>

<form method="get" action="">
	<input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST['page'] ); ?>" />
	<?php $list_table->search_box( __( 'Search Contact Forms', 'sketchus-custom-form' ), 'skfom-contact' ); ?>
	<?php $list_table->display(); ?>
</form>

</div>
<?php
}

function skfom_admin_bulk_validate_page() {
	$contact_forms = SKFOM_ContactForm::find();
	$count = SKFOM_ContactForm::count();

	$submit_text = sprintf(
		_n(
			"Validate %s Contact Form Now",
			"Validate %s Contact Forms Now",
			$count, 'sketchus-custom-form' ),
		number_format_i18n( $count ) );

?>
<div class="wrap">

<h1><?php echo esc_html( __( 'Validate Configuration', 'sketchus-custom-form' ) ); ?></h1>

<form method="post" action="">
	<input type="hidden" name="action" value="validate" />
	<?php wp_nonce_field( 'skfom-bulk-validate' ); ?>
	<p><input type="submit" class="button" value="<?php echo esc_attr( $submit_text ); ?>" /></p>
</form>

<?php echo skfom_link( __( 'http://dev.sketchus.com/custom-form/configuration-validator-faq/', 'sketchus-custom-form' ), __( 'FAQ about Configuration Validator', 'sketchus-custom-form' ) ); ?>

</div>
<?php
}

function skfom_admin_add_new_page() {
	$post = skfom_get_current_contact_form();

	if ( ! $post ) {
		$post = SKFOM_ContactForm::get_template();
	}

	$post_id = -1;

	require_once SKFOM_PLUGIN_DIR . '/admin/includes/editor.php';
	require_once SKFOM_PLUGIN_DIR . '/admin/edit-contact-form.php';
}

function skfom_load_integration_page() {
	$integration = SKFOM_Integration::get_instance();

	if ( isset( $_REQUEST['service'] )
	&& $integration->service_exists( $_REQUEST['service'] ) ) {
		$service = $integration->get_service( $_REQUEST['service'] );
		$service->load( skfom_current_action() );
	}

	$help_tabs = new SKFOM_Help_Tabs( get_current_screen() );
	$help_tabs->set_help_tabs( 'integration' );
}

function skfom_admin_integration_page() {
	$integration = SKFOM_Integration::get_instance();

?>
<div class="wrap">

<h1><?php echo esc_html( __( 'Integration with Other Services', 'sketchus-custom-form' ) ); ?></h1>

<?php do_action( 'skfom_admin_notices' ); ?>

<?php
	if ( isset( $_REQUEST['service'] )
	&& $service = $integration->get_service( $_REQUEST['service'] ) ) {
		$message = isset( $_REQUEST['message'] ) ? $_REQUEST['message'] : '';
		$service->admin_notice( $message );
		$integration->list_services( array( 'include' => $_REQUEST['service'] ) );
	} else {
		$integration->list_services();
	}
?>

</div>
<?php
}

/* Misc */

add_action( 'skfom_admin_notices', 'skfom_admin_updated_message' );

function skfom_admin_updated_message() {
	if ( empty( $_REQUEST['message'] ) ) {
		return;
	}

	if ( 'created' == $_REQUEST['message'] ) {
		$updated_message = __( "Contact form created.", 'sketchus-custom-form' );
	} elseif ( 'saved' == $_REQUEST['message'] ) {
		$updated_message = __( "Contact form saved.", 'sketchus-custom-form' );
	} elseif ( 'deleted' == $_REQUEST['message'] ) {
		$updated_message = __( "Contact form deleted.", 'sketchus-custom-form' );
	}

	if ( ! empty( $updated_message ) ) {
		echo sprintf( '<div id="message" class="updated notice notice-success is-dismissible"><p>%s</p></div>', esc_html( $updated_message ) );
		return;
	}

	if ( 'validated' == $_REQUEST['message'] ) {
		$bulk_validate = SKFOM::get_option( 'bulk_validate', array() );
		$count_invalid = isset( $bulk_validate['count_invalid'] )
			? absint( $bulk_validate['count_invalid'] ) : 0;

		if ( $count_invalid ) {
			$updated_message = sprintf(
				_n(
					"Configuration validation completed. An invalid contact form was found.",
					"Configuration validation completed. %s invalid contact forms were found.",
					$count_invalid, 'sketchus-custom-form' ),
				number_format_i18n( $count_invalid ) );

			echo sprintf( '<div id="message" class="notice notice-warning is-dismissible"><p>%s</p></div>', esc_html( $updated_message ) );
		} else {
			$updated_message = __( "Configuration validation completed. No invalid contact form was found.", 'sketchus-custom-form' );

			echo sprintf( '<div id="message" class="notice notice-success is-dismissible"><p>%s</p></div>', esc_html( $updated_message ) );
		}

		return;
	}
}

add_filter( 'plugin_action_links', 'skfom_plugin_action_links', 10, 2 );

function skfom_plugin_action_links( $links, $file ) {
	if ( $file != SKFOM_PLUGIN_BASENAME )
		return $links;

	$settings_link = '<a href="' . menu_page_url( 'skfom', false ) . '">'
		. esc_html( __( 'Settings', 'sketchus-custom-form' ) ) . '</a>';

	array_unshift( $links, $settings_link );

	return $links;
}

add_action( 'skfom_admin_notices', 'skfom_old_wp_version_error', 2 );

function skfom_old_wp_version_error() {
	$wp_version = get_bloginfo( 'version' );

	if ( ! version_compare( $wp_version, SKFOM_REQUIRED_WP_VERSION, '<' ) ) {
		return;
	}

?>
<div class="notice notice-error is-dismissible">
<p><?php echo sprintf( __( '<strong>Sketchus Custom Form %1$s requires WordPress %2$s or higher.</strong> Please <a href="%3$s">update WordPress</a> first.', 'sketchus-custom-form' ), SKFOM_VERSION, SKFOM_REQUIRED_WP_VERSION, admin_url( 'update-core.php' ) ); ?></p>
</div>
<?php
}

add_action( 'wp_ajax_skfom-update-welcome-panel', 'skfom_admin_ajax_welcome_panel' );

function skfom_admin_ajax_welcome_panel() {
	check_ajax_referer( 'skfom-welcome-panel-nonce', 'welcomepanelnonce' );

	$vers = get_user_meta( get_current_user_id(),
		'skfom_hide_welcome_panel_on', true );

	if ( empty( $vers ) || ! is_array( $vers ) ) {
		$vers = array();
	}

	if ( empty( $_POST['visible'] ) ) {
		$vers[] = SKFOM_VERSION;
	}

	$vers = array_unique( $vers );

	update_user_meta( get_current_user_id(), 'skfom_hide_welcome_panel_on', $vers );

	wp_die( 1 );
}

add_action( 'skfom_admin_notices', 'skfom_not_allowed_to_edit' );

function skfom_not_allowed_to_edit() {
	if ( ! $contact_form = skfom_get_current_contact_form() ) {
		return;
	}

	$post_id = $contact_form->id();

	if ( current_user_can( 'skfom_edit_contact_form', $post_id ) ) {
		return;
	}

	$message = __( "You are not allowed to edit this contact form.",
		'sketchus-custom-form' );

	echo sprintf(
		'<div class="notice notice-warning is-dismissible"><p>%s</p></div>',
		esc_html( $message ) );
}

add_action( 'skfom_admin_notices', 'skfom_notice_config_errors' );

function skfom_notice_config_errors() {
	if ( ! $contact_form = skfom_get_current_contact_form() ) {
		return;
	}

	if ( ! skfom_validate_configuration()
	|| ! current_user_can( 'skfom_edit_contact_form', $contact_form->id() ) ) {
		return;
	}

	if ( $config_errors = $contact_form->get_config_errors() ) {
		$message = sprintf(
			_n(
				"This contact form has a configuration error.",
				"This contact form has %s configuration errors.",
				count( $config_errors ), 'sketchus-custom-form' ),
			number_format_i18n( count( $config_errors ) ) );

		$link = skfom_link(
			__( 'http://dev.sketchus.com/custom-form/configuration-errors/', 'sketchus-custom-form' ),
			__( 'How to Resolve Configuration Errors', 'sketchus-custom-form' ) );

		echo sprintf( '<div class="notice notice-warning is-dismissible"><p>%s &raquo; %s</p></div>', esc_html( $message ), $link );
	}
}

add_action( 'admin_notices', 'skfom_notice_bulk_validate_config' );

function skfom_notice_bulk_validate_config() {
	if ( ! skfom_validate_configuration()
	|| ! current_user_can( 'skfom_edit_contact_forms' ) ) {
		return;
	}

	if ( isset( $_GET['page'] ) && 'skfom' == $_GET['page']
	&& isset( $_GET['action'] ) && 'validate' == $_GET['action'] ) {
		return;
	}

	if ( SKFOM::get_option( 'bulk_validate' ) ) { // already done.
		return;
	}

	$link = add_query_arg(
		array( 'action' => 'validate' ),
		menu_page_url( 'skfom', false ) );

	$link = sprintf( '<a href="%s">%s</a>', $link, esc_html( __( 'Validate Sketchus Custom Form Configuration', 'sketchus-custom-form' ) ) );

	$message = __( "Misconfiguration leads to mail delivery failure or other troubles. Validate your contact forms now.", 'sketchus-custom-form' );

	echo sprintf( '<div class="notice notice-warning is-dismissible"><p>%s &raquo; %s</p></div>', esc_html( $message ), $link );
}