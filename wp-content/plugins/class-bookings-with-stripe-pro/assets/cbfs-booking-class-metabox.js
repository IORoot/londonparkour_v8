/**
 * Class edit: place the listing-image metabox above Publish.
 */
( function ( $ ) {
	'use strict';

	$( function () {
		var $img = $( '#acf-group_clasbpro_class_sidebar_image' );
		var $pub = $( '#submitdiv' );
		if ( $img.length && $pub.length ) {
			$img.insertBefore( $pub );
		}
	} );
} )( jQuery );
