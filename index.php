<?php
// $Header: /cvsroot/bitweaver/_bit_meta/index.php,v 1.2 2006/02/16 23:22:55 lphuberdeau Exp $
// Copyright (c) 2004 bitweaver Sample
// All Rights Reserved. See copyright.txt for details and a complete list of authors.
// Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details.

// Initialization
require_once( '../bit_setup_inc.php' );
require_once 'meta_lib.php';

// Is package installed and enabled
$gBitSystem->verifyPackage( 'meta' );

// Now check permissions to access this page
$gBitSystem->verifyPermission( 'bit_p_view_meta' );

$gBitSmarty->assign( 'attributes', meta_get_possible_values( $gBitSystem->mDb ) );

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

		if( $gBitSystem->isPackageActive( 'pigeonholes' ) && isset( $_POST['pigeonholes'] ) ) {
			$category = "LEFT JOIN `pigeonhole_members` as `pigeon` ON `pigeon`.`content_id` = `content`.`content_id`";

			$holes = array();
			$p = $_POST['pigeonholes']['pigeonhole'];
			
			if( is_array( $p ) ) {
				foreach( $p as $hole )
					if( is_numeric( $hole ) )
						$holes[] = $hole;
			}

			if( count( $holes ) )
				$holes = "AND `pigeon`.`parent_id` IN( " . implode( ', ', $holes ) . " )";
			else
				$holes = "";
			
		}
		else {
			$category = "";
			$holes = "";
		}
		
		$query = "
			SELECT
				`content`.`content_id` as `id`,
				`content`.`title`,
				`content`.`last_modified`,
				`user`.`real_name`
			FROM
				`meta_associations` as `meta`
				INNER JOIN `liberty_content` as `content` ON `meta`.`content_id` = `content`.`content_id`
				INNER JOIN `users_users` as `user` ON `user`.`user_id` = `content`.`user_id`
				$category
			WHERE
				`meta`.`end` IS NULL
				$holes
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

if( $gBitSystem->isPackageActive( 'pigeonholes' ) )
	pigeonholes_content_input();

// Display the template
$gBitSystem->display( 'bitpackage:meta/display_search.tpl', tra( 'Meta Search' ) );
?>
