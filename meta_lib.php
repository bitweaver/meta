<?php
define( 'PLUGIN_GUID_DATAMETASEARCH', 'datametasearch' );

// vim: set fdm=marker:

function meta_get_possible_values( $db, $content_id = null ) { // {{{

	global $gBitUser;

	$attributes = array();
	$selected = array();

	if( $content_id != null ) {
		$result = $db->query( "SELECT `meta_attribute_id`, `meta_value_id` FROM `meta_associations` WHERE `end` IS NULL AND `content_id` = ?", array( $content_id ) );

		foreach( $result->getRows() as $row )
			$selected[ $row['meta_attribute_id'] ] = $row['meta_value_id'];
	}

	$result = $db->query( "
	SELECT DISTINCT
		`att`.`meta_attribute_id`, 
		`name`,
		`val`.`meta_value_id`,
		`value`
	FROM 
		`meta_attributes` as `att`
		LEFT JOIN `meta_associations` as `asso` ON `asso`.`meta_attribute_id` = `att`.`meta_attribute_id`
		LEFT JOIN `meta_values` as `val` ON `asso`.`meta_value_id` = `val`.`meta_value_id`
	ORDER BY `name`, `value`" );

	foreach( $result->getRows() as $row ) {
		$att = $row['meta_attribute_id'];
		$val = $row['meta_value_id'];

		if( !isset( $attributes[$att] ) ) {
			$attributes[$att] = array( 'name' => $row['name'], 'values' => array(
				array( 'id' => 'none', 'value' => tra( 'None' ), 'selected' => false ),
			) );

			if( $gBitUser->hasPermission( 'bit_p_edit_value_meta' ) )
				$attributes[$att]['values'][] = array( 'id' => 'other', 'value' => tra( 'Other' ), 'selected' => false );
		}

		if( !empty( $val ) )
			$attributes[$att]['values'][] = array( 
				'id' => $val, 
				'value' => $row['value'], 
				'selected' => isset( $selected[$att] ) && $val == $selected[$att]
			);
	}

	return $attributes;
} // }}}

function meta_content_display( &$pContent, &$pParamHash ) { // {{{
	global $gBitSystem;
	global $gBitSmarty;
	$result = $gBitSystem->mDb->query( "
	SELECT 
		`meta_attributes`.`name`, 
		`meta_values`.`value` 
	FROM 
		`meta_associations` 
		INNER JOIN `meta_attributes` ON 
			`meta_associations`.`meta_attribute_id` = `meta_attributes`.`meta_attribute_id` 
		INNER JOIN `meta_values` ON
			`meta_associations`.`meta_value_id` = `meta_values`.`meta_value_id` 
	WHERE 
		`meta_associations`.`content_id` = ? 
		AND `meta_associations`.`end` IS NULL"
	, array( $pContent->mContentId ) );

	$gBitSmarty->assign( 'metaInfo', $result->getRows() );
} // }}}

function meta_content_edit( &$pContent, &$pParamHash ) { // {{{
	global $gBitSmarty;
	global $gBitSystem;

	$gBitSmarty->assign( 'metaAttributes', meta_get_possible_values( $gBitSystem->mDb, $pContent->mContentId ) );
} // }}}

function meta_get_value_id( $db, $value ) { // {{{

	global $gBitUser;

	$result = $db->query( "SELECT `meta_value_id` FROM `meta_values` WHERE `value` LIKE ?", array( $value ) );

	$result = $result->getRows();

	if( count( $result ) > 0 )
		return $result[0]['meta_value_id'];
	else
	{
		if( $gBitUser->hasPermission( 'bit_p_edit_value_meta' ) ) {
			$id = $db->genID( 'meta_value_id_seq' );

			$db->query( "INSERT INTO `meta_values` (`meta_value_id`, `value`) VALUES( ?, ? )", array( $id, $value ) );

			return $id;
		}
		else
			return 0;
	}
} // }}}

function meta_content_preview( &$pContent, &$pParamHash ) { // {{{
	global $gBitSystem;
	global $gBitUser;
	global $gBitSmarty;
	$db = $gBitSystem->mDb;

	if( !$gBitUser->hasPermission( 'bit_p_assign_meta' ) )
		return;

	$attributes = meta_get_possible_values( $db );

	foreach( $_REQUEST['metatt'] as $att_id => $value ) {

		foreach( $attributes[$att_id]['values'] as $index => $row )
			if( $row['id'] == $value )
				$attributes[$att_id]['values'][$index]['selected'] = true;
	}

	$gBitSmarty->assign( 'metaAttributes', $attributes );
	$gBitSmarty->assign( 'metaAttributesOther', $_POST['metatt_other'] );

} // }}}

function meta_content_store( &$pContent, &$pParamHash ) { // {{{
	global $gBitSystem;
	global $gBitUser;
	$db = $gBitSystem->mDb;

	if( !$gBitUser->hasPermission( 'bit_p_assign_meta' ) )
		return;
	
	if( !isset( $_REQUEST['metatt'] ) )
		return;

	$now = time();

	$result = $db->query( "SELECT `meta_attribute_id`, `meta_value_id` FROM `meta_associations` WHERE `end` IS NULL AND `content_id` = ?", array( $pContent->mContentId ) );

	$selected = array();
	foreach( $result->getRows() as $row )
		$selected[ $row['meta_attribute_id'] ] = $row['meta_value_id'];
	
	foreach( $_REQUEST['metatt'] as $att_id => $value ) {

		if( $value == 'other' )
			$value = meta_get_value_id( $db, $_REQUEST['metatt_other'][$att_id] );

		if( $value == 'none' && isset( $selected[$att_id] ) ) {
			$db->query( "UPDATE `meta_associations` SET `end` = ? WHERE `content_id` = ? AND `meta_attribute_id` = ?", array( $now, $pContent->mContentId, $att_id ) );
		}
		elseif( !isset( $selected[$att_id] ) && $value != 'none' ) {
			$db->query( "INSERT INTO `meta_associations` ( `content_id`, `meta_attribute_id`, `meta_value_id`, `user_id`, `start` ) VALUES( ?, ?, ?, ?, ? )", array( $pContent->mContentId, $att_id, $value, $gBitUser->mUserId, $now ) );
		}
		elseif( isset( $selected[$att_id]) && $value != $selected[$att_id] ) {
			$db->query( "UPDATE `meta_associations` SET `end` = ? WHERE `content_id` = ? AND `meta_attribute_id` = ?", array( $now, $pContent->mContentId, $att_id ) );
			$db->query( "INSERT INTO `meta_associations` ( `content_id`, `meta_attribute_id`, `meta_value_id`, `user_id`, `start` ) VALUES( ?, ?, ?, ?, ? )", array( $pContent->mContentId, $att_id, $value, $gBitUser->mUserId, $now ) );
		}
	}

} // }}}

function meta_content_expunge( &$pContent, &$pParamHash ) { // {{{
	global $gBitSystem;
	global $gBitUser;
	$db = $gBitSystem->mDb;

	$db->query( "DELETE FROM `meta_associations` WHERE `content_id` = ?", array( $pContent->mDb ) );

} // }}}

function data_metasearch_help() { // {{{
	return 'N/A';
} // }}}

function data_metasearch($data, $params) { // {{{
	global $gBitSystem;
	if( !isset( $params['param'] ) )
		return 'Missing parameter "param".';
		
	$p = explode( ',', $params['param'] );
	$p = array_map( 'trim', $p );

	$conditions = array();
	
	foreach( $p as $value ) {
		list( $key, $value ) = explode( ':', $value );

		if( $value == 'none' ) {
			$conditions[] = "COUNT( IF( `attribute`.`name` = '$key', 'GOOD', NULL ) ) = 0";
			continue;
		}

		$conditions[] = "COUNT( IF( `attribute`.`name` = '$key' AND `value`.`value` = '$value', 'GOOD', NULL ) ) > 0";
	}

	if( count( $conditions ) > 0 ) {

		$query = "
			SELECT
				`content`.`content_id` as `id`,
				`content`.`title`,
				`content`.`last_modified`,
				`user`.`real_name`
			FROM
				`meta_associations` as `meta`
				INNER JOIN `meta_attributes` as `attribute` ON `meta`.`meta_attribute_id` = `attribute`.`meta_attribute_id`
				INNER JOIN `meta_values` as `value` ON `meta`.`meta_value_id` = `value`.`meta_value_id`
				INNER JOIN `liberty_content` as `content` ON `meta`.`content_id` = `content`.`content_id`
				INNER JOIN `users_users` as `user` ON `user`.`user_id` = `content`.`user_id`
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

		$data = array();
		foreach( $rows as $row ) {
			$data[] = "[" . BIT_ROOT_URL 
				. "/index.php?content_id={$row['id']}|{$row['title']}]|" 
				. strftime( $gBitSystem->get_long_date_format(), $row['last_modified'] )
				. "|" . $row['real_name'];
		}

		if( count( $data ) > 0 )
			return '||' . implode( "\r\n", $data ) . '||';
	}

	return 'No results found.';
} // }}}

?>
