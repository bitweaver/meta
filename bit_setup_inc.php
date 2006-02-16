<?php
global $gBitSystem;
$gBitSystem->registerPackage( 'meta', dirname(__FILE__).'/', true, LIBERTY_SERVICE_METADATA );

if( $gBitSystem->isPackageActive( 'meta' ) ) {
	require_once "meta_lib.php";

	$gLibertySystem->registerService( LIBERTY_SERVICE_METADATA, META_PKG_NAME, array(
		'content_display_function' => 'meta_content_display',
		'content_edit_function' => 'meta_content_edit',
		'content_store_function' => 'meta_content_store',
		'content_expunge_function' => 'meta_content_expunge',
		'content_preview_function' => 'meta_content_preview',
		'content_body_tpl' => 'bitpackage:meta/display_meta.tpl',
		'content_edit_tab_tpl' => 'bitpackage:meta/assign_attribute_form.tpl'
	) );
}
?>
