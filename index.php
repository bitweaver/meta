<?php
// $Header: /cvsroot/bitweaver/_bit_meta/index.php,v 1.6 2007/11/20 17:06:40 spiderr Exp $
// Copyright (c) 2004 bitweaver Sample
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

// Initialization
require_once( '../bit_setup_inc.php' );
require_once 'meta_lib.php';

// Is package installed and enabled
$gBitSystem->verifyPackage( 'meta' );

// Now check permissions to access this page
$gBitSystem->verifyPermission( 'p_view_meta' );

$gBitSmarty->assign( 'metaAttributes', meta_get_possible_values( null, false ) );

if( isset( $_REQUEST['search'] ) ) {

	$conditions = array();
	
	foreach( $_REQUEST['metatt'] as $key => $value ) {
		if( $value == 'none' ) {
			$conditions[] = "COUNT( IF( `meta`.`meta_attribute_id` = $key, 'GOOD', NULL ) ) = 0";
			continue;
		}

		if( !is_numeric( $key ) || !is_numeric( $value ) )
			continue;

		$conditions[] = "COUNT( IF( `meta`.`meta_attribute_id` = $key AND `meta`.`meta_value_id` = $value, 'GOOD', NULL ) ) > 0";
	}

	if( count( $conditions ) > 0 ) {
		
		$query = "
			SELECT
				`content`.`content_id` as `id`,
				`content`.`title`,
				`content`.`last_modified`,
				`user`.`real_name`
			FROM
				`".BIT_DB_PREFIX."meta_associations` as `meta`
				INNER JOIN `".BIT_DB_PREFIX."liberty_content` as `content` ON `meta`.`content_id` = `content`.`content_id`
				INNER JOIN `".BIT_DB_PREFIX."users_users` as `user` ON `user`.`user_id` = `content`.`user_id`
			WHERE
				`meta`.`end` IS NULL
			GROUP BY
				`meta`.`content_id`
			HAVING
				" . implode( ' AND ', $conditions ) . "
			ORDER BY
				`content`.`last_modified` DESC";

		$result = $gBitSystem->mDb->query( $query );

		$rows = $result->getRows();
		$gBitSmarty->assign_by_ref( 'searchData', $rows );
		$gBitSmarty->assign( 'tab', 2 );
	}
}
else
	$gBitSmarty->assign( 'tab', 0 );

// Display the template
$gBitSystem->display( 'bitpackage:meta/display_search.tpl', tra( 'Meta Search' ) );
?>
