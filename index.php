<?php
// $Header: /cvsroot/bitweaver/_bit_meta/index.php,v 1.13 2010/02/08 21:27:24 wjames5 Exp $
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

	$conditions = array();

	foreach( $_REQUEST['metatt'] as $key => $value ) {
		if( !empty( $value ) ) {
			if( $value == 'none' ) {
				$conditions['sql'][] = "COUNT( IF( `attribute`.`meta_attribute_id` = $key, 'GOOD', NULL ) ) = 0";
				$conditions['params'][] = $value;
				continue;
			} else {
				if( is_numeric( $key ) ) {
					// unimplemented search for $searchCol = '`meta_attribute_id`';
				} else {
					$listHash['name'][$key] = !empty( $value ) && ($value != '*any*' ) ? $value : NULL;
				}
			}
		}
	}
	if( count( $conditions ) > 0 ) {
		$listHash['conditions'] = $conditions;
	}
	$gBitSmarty->assign_by_ref( 'searchData', meta_search( $listHash ) );
	$gBitSmarty->assign( 'tab', 2 );
} else {
	$gBitSmarty->assign( 'tab', 0 );
}

// Display the template
$gBitSystem->display( 'bitpackage:meta/display_search.tpl', tra( 'Meta Search' ) , array( 'display_mode' => 'display' ));
?>
