<?php
// $Header: /cvsroot/bitweaver/_bit_meta/index.php,v 1.7 2008/01/08 16:51:42 spiderr Exp $
// Copyright (c) 2004 bitweaver Sample
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

// Initialization
require_once( '../bit_setup_inc.php' );
require_once 'meta_lib.php';

// Is package installed and enabled
$gBitSystem->verifyPackage( 'meta' );

// Now check permissions to access this page
$gBitSystem->verifyPermission( 'p_browse_meta' );

$gBitSmarty->assign( 'metaAttributes', meta_get_possible_values( null, false ) );

if( isset( $_REQUEST['metatt'] ) ) {

	$conditions = array();
	
	foreach( $_REQUEST['metatt'] as $key => $value ) {
		if( $value == 'none' ) {
			$conditions[] = "COUNT( IF( `meta`.`meta_attribute_id` = $key, 'GOOD', NULL ) ) = 0";
			continue;
		}

		if( is_numeric( $key ) && is_numeric( $value ) ) {
			$conditions[] = "COUNT( IF( `meta`.`meta_attribute_id` = $key AND `meta`.`meta_value_id` = $value, 'GOOD', NULL ) ) > 0";
		}
	}

	if( count( $conditions ) > 0 ) {
		$listHash['conditions'] = $conditions;
		$gBitSmarty->assign_by_ref( 'searchData', meta_search( $listHash ) );
		$gBitSmarty->assign( 'tab', 2 );
	}
} else {
	$gBitSmarty->assign( 'tab', 0 );
}

// Display the template
$gBitSystem->display( 'bitpackage:meta/display_search.tpl', tra( 'Meta Search' ) );
?>
