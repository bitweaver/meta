<?php
// $Header$
// Copyright (c) 2004 bitweaver Sample
// All Rights Reserved. See below for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See http://www.gnu.org/copyleft/lesser.html for details.

// Initialization
require_once( '../kernel/includes/setup_inc.php' );
require_once META_PKG_INCLUDE_PATH.'meta_lib.php';

// Is package installed and enabled
$gBitSystem->verifyPackage( 'meta' );

// Now check permissions to access this page
$gBitSystem->verifyPermission( 'p_browse_meta' );

$gBitSmarty->assign( 'metaAttributes', meta_get_possible_values( null, false ) );

if( isset( $_REQUEST['metatt'] ) ) {

	foreach( $_REQUEST['metatt'] as $key => $value ) {
		if( !empty( $value ) ) {
			$listHash['search'][$key] = $value;
		}
	}

	$searchResults = meta_search( $listHash );
	$groupedResults = array();
	foreach( array_keys( $searchResults ) as $key ) {
		$groupKey = implode( ',', $searchResults[$key]['meta'] );
		$groupedResults[$groupKey][$key] = $searchResults[$key];
	}

	ksort( $groupedResults );
	$gBitSmarty->assignByRef( 'searchData', $groupedResults );
	$gBitSmarty->assign( 'tab', 2 );
} else {
	$gBitSmarty->assign( 'tab', 0 );
}

// Display the template
$gBitSystem->display( 'bitpackage:meta/display_search.tpl', tra( 'Meta Search' ) , array( 'display_mode' => 'display' ));
?>
