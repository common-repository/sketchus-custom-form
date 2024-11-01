<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) )
	exit();

function skfom_delete_plugin() {
	global $wpdb;

	delete_option( 'skfom' );

	$posts = get_posts( array(
		'numberposts' => -1,
		'post_type' => 'skfom_contact_form',
		'post_status' => 'any' ) );

	foreach ( $posts as $post )
		wp_delete_post( $post->ID, true );

	$table_name = $wpdb->prefix . "sketchus-custom-form";

	$wpdb->query( "DROP TABLE IF EXISTS $table_name" );
}

skfom_delete_plugin();

?>