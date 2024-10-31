<?php

namespace MatejKravjar\Wordpress\Plugins\PostMetaViewAndExport;

/** @var array    $data */
/** @var \WP_Post $post */

function dump( $value, $level = 0 ) {
	$type = gettype( $value );
	if ( is_scalar( $value ) ) {
		$dump = str_replace( ' ', '&nbsp;', esc_html( var_export( $value, true ) ) );
	}
	elseif ( is_array( $value ) ) {
		$type = 'array/' . count( $value );
		$dump = '[' . dump_multiple( $value, $level ) . ']';
	}
	elseif ( is_object( $value ) ) {
		$type = 'object/' . get_class( $value );
		$dump = '{' . dump_multiple( get_object_vars( $value ), $level ) . '}';
	}
	else {
		$dump = '<em>(value dump not implemented)</em>';
	}
	return "<span>$type</span> $dump";
}

function dump_multiple( $value, $level ) {
	return "\n" . implode( "\n", array_map( function( $value, $key ) use ( $level ) {
		return str_repeat( '&nbsp;', 4 * $level ) . dump( $key, $level + 1 ) . ' => ' . dump( $value, $level + 1 );
	}, array_values( $value ), array_keys( $value ) ) ) . "\n";
}

?>
<style>
.post-meta-view-and-export-actions {
	margin: 1em 0;
	text-align: left;
}
.post-meta-view-and-export-data {
	border-collapse: collapse;
	width: 100%;
}
.post-meta-view-and-export-data th {
	padding: 1em .333em .333em;
	text-align: left;
}
.post-meta-view-and-export-data td {
	font-family: monospace;
	font-size: 90%;
	padding: .333em;
	vertical-align: top;
}
.post-meta-view-and-export-data tr:hover td {
	background: #0001;
}
.post-meta-view-and-export-data td:nth-child(2) span {
	opacity: 0.333;
}
</style>
<?php

	$export_url = add_query_arg( 'post-meta-view-and-export-id', $post->ID, admin_url() );
	$export_url = wp_nonce_url( $export_url, 'post-meta-view-and-export-nonce' );

?>
<div class="post-meta-view-and-export-actions">
<a href="<?php echo esc_attr( $export_url ) ?>" class="button"><?php
	/* translators: %s: the post type */
	echo esc_html( sprintf( __( 'Export %s', 'post-meta-view-and-export' ), $post->post_type ) );
?>
</a>
<?php

	/**
	 * Allows extensions to add custom export actions.
	 *
	 * Allows other plugins to provide different kinds of export, for example export to XML,
	 * export with additional relevant data, etc.
	 *
	 * @param array    $data The post data.
	 * @param \WP_Post $post The post object the data is tied to.
	 */
	do_action( 'post-meta-view-and-export-additional-actions', $data, $post );

?>
</div>
<table class="post-meta-view-and-export-data">
<tr>
	<th><?php _e( 'meta key', 'post-meta-view-and-export' ); ?></th>
	<th><?php _e( 'meta value', 'post-meta-view-and-export' ); ?></th>
</tr>
<?php

	if ( ! isset( $data[ 'meta' ] ) ):

?>
	<tr>
		<td colspan="2"><em>
<?php
	_e( 'Missing post meta data.', 'post-meta-view-and-export' );
	echo ' ';
	/* translators: %s: the filter slug */
	printf( __( 'Most likely, it is caused by incorrectly used filter %s.', 'post-meta-view-and-export' ), 'post-meta-view-and-export-post-data' );
?>
		</em></td>
	</tr>
<?php

	elseif ( ! is_array( $data[ 'meta' ] ) ):

?>
	<tr>
		<td colspan="2"><em>
<?php
	_e( 'Post meta data is not an array.', 'post-meta-view-and-export' );
	echo ' ';
	/* translators: %s: the filter slug */
	printf( __( 'Most likely, it is caused by incorrectly used filter %s.', 'post-meta-view-and-export' ), 'post-meta-view-and-export-post-data' );
?>
		</em></td>
	</tr>
<?php

	elseif ( empty( $data[ 'meta' ] ) ):

?>
	<tr>
		<td colspan="2"><em><?php _e( 'No post meta data.', 'post-meta-view-and-export' ); ?></em></td>
	</tr>
<?php

	else: 
		
	foreach ( $data['meta'] as $key => $value ):

?>
	<tr>
		<td><?php echo esc_html( $key ) ?></td>
		<td><?php echo wp_kses( dump( $value ), [ 'em' => [], 'span' => [] ] ); ?></td>
	</tr>
<?php
	
	endforeach; 
	
	endif;

	/**
	 * Allows extensions to add custom view data.
	 *
	 * Allows other plugins to provide different kinds of view data related to post,
	 * for example, the WooCommerce could provide item data for it's order posts.
	 *
	 * @param array    $data The post data.
	 * @param \WP_Post $post The post object the data is tied to.
	 */
	do_action( 'post-meta-view-and-export-additional-data', $data, $post );

?>
</table>
