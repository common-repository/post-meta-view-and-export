<?php

/*
Plugin Name: Post Meta View and Export
Description: Inspect post meta data and export it to share with third parties (available only to superadmins)
Version: 1.0
Author: Matej Kravjar
Author URI: https://profiles.wordpress.org/kravco/
Text Domain: post-meta-view-and-export
Domain Path: /languages/
Requires at least: 5.3
Requires PHP: 7.0
*/

namespace MatejKravjar\Wordpress\Plugins\PostMetaViewAndExport;

function sort_meta_keys( &$meta_data ) {
	uksort( $meta_data, function( $a, $b ) {
		return strcasecmp( ltrim( $a, '_' ), ltrim( $b, '_' ) );
	} );
}

function flat_single_array_value( $value ) {
	if ( is_array( $value ) && 1 === count( $value ) && isset( $value[0] ) ) {
		return $value[0];
	}
	return $value;
}

function custom_get_post_meta( $post ) {
	$meta = array_map( __NAMESPACE__ . '\flat_single_array_value', get_post_meta( $post->ID ) );
	sort_meta_keys( $meta );
	return $meta;
}

function custom_get_post_data( $post ) {
	$data = [ 'meta' => custom_get_post_meta( $post ) ];
	/**
	 * Allows extensions to add custom post data.
	 *
	 * Allows other plugins to provide additional data for the post to view or export,
	 * preferably not altering metadata, but adding new sections with new types of data.
	 * Good example could be adding data about WooCommerce order items. These data would
	 * appear in export right away, but to appear in view mode, one need to add a display
	 * action to handle the data---see action post-meta-view-and-export-additional-data.
	 *
	 * @param array    $data The post data. Key "meta" is reserved for metadata and extensions
	 *                       should use unique keys that would not conflict with other plugins.
	 * @param \WP_Post $post The post object the data is tied to.
	 */
	return apply_filters( 'post-meta-view-and-export-post-data', $data, $post );
}

add_action( 'admin_init', function() {
	// only superadmins can see or export post meta data
	if ( ! is_super_admin() ) {
		return;
	}

	// handle export request
	if ( isset( $_GET[ 'post-meta-view-and-export-id' ] ) ) {
		if ( ! wp_verify_nonce( $_GET[ '_wpnonce' ] ?? '', 'post-meta-view-and-export-nonce' ) ) {
			wp_die( __( 'Post meta export link has expired. Go back, refresh the page and hit Export again.', 'post-meta-view-and-export' ) );
		}
		$post_ID = intval( $_GET[ 'post-meta-view-and-export-id' ] );
		$post = get_post( $post_ID );
		if ( ! ( $post instanceof \WP_Post ) ) {
			/* translators: %s: the post ID */
			wp_die( sprintf( __( 'The post ID %s to be exported was not found in this installation.', 'post-meta-view-and-export' ), $post_ID ) );
		}
		$data = custom_get_post_data( $post );
		if ( headers_sent( $file, $line ) ) {
			/* translators: %1$s: filename, %2$s: line number */
			wp_die( sprintf( __( 'Headers already sent. Output has started in file %1$s on line %2$s', 'post-meta-view-and-export' ), $file, $line ) );
		}
		header( 'Content-Type: application/json; charset=UTF-8' );
		$filedate = str_replace( [ '+', '-' ], [ 'e', 'w' ], wp_date( 'Ymd\tHisO' ) );
		$filename = $post->post_type . '-' . $post->ID . '-meta-export-' . $filedate . '.json';
		$filename = str_replace( '"', '', sanitize_file_name( $filename ) );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		// this output is part of raw file download (*.json file), hence the headers above,
		// and therefore it should not be escaped in any way---in fact, the function
		// json_encode() itself is the correct way to escape output to such file
		echo json_encode( $data, JSON_PRETTY_PRINT );
		exit;
	}

	// add meta box content
	add_action( 'add_meta_boxes', function() {
		add_meta_box( 
			'post-meta-view-and-export',
			__( 'Post Meta View and Export (available only to superadmins)', 'post-meta-view-and-export' ),
			function( $post ) {
				$data = custom_get_post_data( $post );
				require __DIR__ . '/templates/template-meta-box.php';
			},
			null,
			'normal',
			'low'
		);
	}, 1000 );
} );
