<?php
// $Header$
// Copyright (c) 2004 bitweaver Sample
// All Rights Reserved. See below for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See http://www.gnu.org/copyleft/lesser.html for details.

// Initialization
require_once( '../kernel/setup_inc.php' );
require_once 'meta_lib.php';

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
	$gBitSmarty->assign_by_ref( 'searchData', meta_search( $listHash ) );
	$gBitSmarty->assign( 'tab', 2 );
} else {
	$gBitSmarty->assign( 'tab', 0 );
}

// Display the template
$gBitSystem->display( 'bitpackage:meta/display_search.tpl', tra( 'Meta Search' ) , array( 'display_mode' => 'display' ));
?>
