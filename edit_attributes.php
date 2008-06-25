<?php
/**
 * $Header: /cvsroot/bitweaver/_bit_meta/edit_attributes.php,v 1.6 2008/06/25 22:21:14 spiderr Exp $
 *
 * Copyright (c) 2004 bitweaver.org
 * Copyright (c) 2003 tikwiki.org
 * Copyright (c) 2002-2003, Luis Argerich, Garland Foster, Eduardo Polidor, et. al.
 * All Rights Reserved. See copyright.txt for details and a complete list of authors.
 * Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details
 *
 * @package meta
 * @subpackage functions
 */

/**
 * Initialization
 */
require_once( '../bit_setup_inc.php' );

// Is package installed and enabled
$gBitSystem->verifyPackage( 'meta' );

// Now check permissions to access this page
$gBitSystem->verifyPermission( 'p_edit_attribute_meta' );

if( isset( $_REQUEST['id'] ) ) {
	$result = $gBitSystem->mDb->query( "SELECT `name` FROM `".BIT_DB_PREFIX."meta_attributes` WHERE `meta_attribute_id` = ?", array( $_REQUEST['id'] ) );

	$result = $result->getRows();

	if( count( $result ) > 0 ) {
		$gBitSmarty->assign( 'metaId', $_REQUEST['id'] );
		$gBitSmarty->assign( 'metaName', $result[0]['name'] );
	}
}

if( isset( $_REQUEST['action'] ) ) {
	$gBitSmarty->assign( 'metaAction', $_REQUEST['action'] );
}

if( $_SERVER['REQUEST_METHOD'] == 'POST' ) {
	if( isset( $_POST['add_attribute'] ) ) {
		$id = $gBitSystem->mDb->genID( 'meta_attribute_id_seq' );

		$gBitSystem->mDb->query( "INSERT INTO `".BIT_DB_PREFIX."meta_attributes` ( `meta_attribute_id`, `name` ) VALUES( ?, ? )", array( $id, $_POST['name'] ) );
	}
	elseif( isset( $_POST['edit_attribute'] ) ) {
		$id = $_REQUEST['id'];
		$name = $_REQUEST['name'];

		$gBitSystem->mDb->query( "UPDATE `".BIT_DB_PREFIX."meta_attributes` SET `name` = ? WHERE `meta_attribute_id` = ?", array( $name, $id ) );

		$gBitSmarty->assign( 'metaAction', 'view' );
	}
	elseif( isset( $_POST['delete_attribute'] ) ) {
		$id = $_REQUEST['id'];

		$gBitSystem->mDb->query( "DELETE FROM `".BIT_DB_PREFIX."meta_associations` WHERE `meta_attribute_id` = ?", array( $id ) );
		$gBitSystem->mDb->query( "DELETE FROM `".BIT_DB_PREFIX."meta_attributes` WHERE `meta_attribute_id` = ?", array( $id ) );

		$gBitSmarty->assign( 'metaAction', 'view' );
	}
}

// Fetching list of attributes {{{1
$result = $gBitSystem->mDb->query( "
SELECT
	`a`.`meta_attribute_id` as `id`,
	`a`.`name`,
	COUNT( `b`.`meta_value_id` ) as `asso`,
	COUNT( DISTINCT `b`.`meta_value_id` ) as `val`
FROM
	`".BIT_DB_PREFIX."meta_attributes` as `a`
	LEFT JOIN `".BIT_DB_PREFIX."meta_associations` as `b` ON `a`.`meta_attribute_id` = `b`.`meta_attribute_id`
WHERE
	`b`.`end` IS NULL
GROUP BY `a`.`meta_attribute_id`
" );

$gBitSmarty->assign( 'attributes', $result->getRows() );
// }}}1

// Display the template
$gBitSystem->display( 'bitpackage:meta/edit_attributes.tpl', tra( 'Manage Meta Attributes' ) , array( 'display_mode' => 'edit' ));
?>
