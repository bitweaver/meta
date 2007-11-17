<?php
define( 'PLUGIN_GUID_DATAMETASEARCH', 'datametasearch' );
define( 'PLUGIN_GUID_METADATA', 'datametadata' );

// vim: set fdm=marker:
function meta_placeholder_present() { // {{{
	global $gBitThemes;
	$gBitThemes->loadLayout();

	foreach( $gBitThemes->mLayout as $col )
		if( is_array( $col ) )
			foreach( $col as $elem )
				if( $elem['module_rsrc'] == 'bitpackage:meta/mod_meta_placeholder.tpl' )
					return true;
					
	return false;
} // }}}

function meta_get_possible_values( $db, $content_id = null, $other = true ) { // {{{

	global $gBitUser;

	$attributes = array( '' => array() );
	$selected = array();

	if( $content_id != null ) {
		$result = $db->query( "SELECT `meta_attribute_id`, `meta_value_id` FROM `".BIT_DB_PREFIX."meta_associations` WHERE `end` IS NULL AND `content_id` = ?", array( $content_id ) );

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
		`".BIT_DB_PREFIX."meta_attributes` as `att`
		LEFT JOIN `".BIT_DB_PREFIX."meta_associations` as `asso` ON `asso`.`meta_attribute_id` = `att`.`meta_attribute_id`
		LEFT JOIN `".BIT_DB_PREFIX."meta_values` as `val` ON `asso`.`meta_value_id` = `val`.`meta_value_id`
	ORDER BY `name`, `value`" );

	foreach( $result->getRows() as $row ) {
		$att = $row['meta_attribute_id'];
		$val = $row['meta_value_id'];

		$parts = explode( ".", $row['name'], 2 );
		if( count( $parts ) == 1 )
			array_unshift( $parts, '' );

		list( $group, $name ) = $parts;

		if( !isset( $attributes[$group] ) )
			$attributes[$group] = array();

		if( !isset( $attributes[$group][$att] ) ) {
			$attributes[$group][$att] = array( 'name' => $name, 'values' => array(
				array( 'id' => 'none', 'value' => tra( 'None' ), 'selected' => false ),
			) );

			if( $other && $gBitUser->hasPermission( 'p_edit_value_meta' ) )
				$attributes[$group][$att]['values'][] = array( 'id' => 'other', 'value' => tra( 'Other' ), 'selected' => false );
		}

		if( !empty( $val ) )
			$attributes[$group][$att]['values'][] = array( 
				'id' => $val, 
				'value' => $row['value'], 
				'selected' => isset( $selected[$att] ) && $val == $selected[$att]
			);
	}

	if( count( $attributes[''] ) == 0 )
		array_shift( $attributes );

	return $attributes;
} // }}}

function meta_content_display( &$pContent, &$pParamHash ) { // {{{
	global $gBitSystem;
	global $gBitSmarty;
	global $metaTables;
	$result = $gBitSystem->mDb->query( "
	SELECT 
		`".BIT_DB_PREFIX."meta_attributes`.`name`, 
		`".BIT_DB_PREFIX."meta_values`.`value` 
	FROM 
		`".BIT_DB_PREFIX."meta_associations` 
		INNER JOIN `".BIT_DB_PREFIX."meta_attributes` ON 
			`".BIT_DB_PREFIX."meta_associations`.`meta_attribute_id` = `".BIT_DB_PREFIX."meta_attributes`.`meta_attribute_id` 
		INNER JOIN `".BIT_DB_PREFIX."meta_values` ON
			`".BIT_DB_PREFIX."meta_associations`.`meta_value_id` = `".BIT_DB_PREFIX."meta_values`.`meta_value_id` 
	WHERE 
		`".BIT_DB_PREFIX."meta_associations`.`content_id` = ? 
		AND `".BIT_DB_PREFIX."meta_associations`.`end` IS NULL"
	, array( $pContent->mContentId ) );


	$metaInfo = array( '' => array() );

	foreach( $result->getRows() as $row ) {
		$parts = explode( ".", $row['name'] );
		if( count( $parts ) == 1 )
			array_unshift( $parts, '' );

		list( $group, $name ) = $parts;

		if( !isset( $metaInfo[$group] ) )
			$metaInfo[$group] = array();

		$metaInfo[$group][] = array( 'name' => $name, 'value' => $row['value'] );
	}

	if( count( $metaInfo[''] ) == 0 )
		array_shift( $metaInfo );

	if( meta_placeholder_present() && count( $metaInfo ) )
		$metaTables[tra( "Attributes" )] = $metaInfo;
	else
		$gBitSmarty->assign( 'metaInfo', $metaInfo );
} // }}}

function meta_content_edit( &$pContent, &$pParamHash ) { // {{{
	global $gBitSmarty;
	global $gBitSystem;

	$gBitSmarty->assign( 'metaAttributes', meta_get_possible_values( $gBitSystem->mDb, $pContent->mContentId ) );
} // }}}

function meta_get_value_id( $db, $value ) { // {{{

	global $gBitUser;

	$result = $db->query( "SELECT `meta_value_id` FROM `".BIT_DB_PREFIX."meta_values` WHERE `value` LIKE ?", array( $value ) );

	$result = $result->getRows();

	if( count( $result ) > 0 )
		return $result[0]['meta_value_id'];
	else
	{
		if( $gBitUser->hasPermission( 'p_edit_value_meta' ) ) {
			$id = $db->genID( 'meta_value_id_seq' );

			$db->query( "INSERT INTO `".BIT_DB_PREFIX."meta_values` (`meta_value_id`, `value`) VALUES( ?, ? )", array( $id, $value ) );

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

	if( !$gBitUser->hasPermission( 'p_assign_meta' ) )
		return;

	$attributes = meta_get_possible_values( $db );

	foreach( $_REQUEST['metatt'] as $att_id => $value ) {
		foreach( $attributes as $group => $values ) {
			if( isset( $values[$att_id] ) )
				foreach( $values[$att_id]['values'] as $index => $row ) {
					if( $row['id'] == $value )
						$attributes[$group][$att_id]['values'][$index]['selected'] = true;
				}
		}
	}

	$gBitSmarty->assign( 'metaAttributes', $attributes );
	$gBitSmarty->assign( 'metaAttributesOther', $_POST['metatt_other'] );

} // }}}

function meta_content_store( &$pContent, &$pParamHash ) { // {{{
	global $gBitSystem, $gBitUser, $gBitDb;

	if( !$pContent->hasUserPermission( 'p_assign_meta' ) )
		return;
	
	if( !isset( $_REQUEST['metatt'] ) )
		return;

	$now = time();

	$selected = $gBitDb->getAssoc( "SELECT `meta_attribute_id`, `meta_value_id` FROM `".BIT_DB_PREFIX."meta_associations` WHERE `end` IS NULL AND `content_id` = ?", array( $pContent->mContentId ) );

	foreach( $_REQUEST['metatt'] as $att_id => $value ) {

		if( !empty( $_REQUEST['metatt_other'][$att_id] ) ) {
			$value = meta_get_value_id( $gBitDb, $_REQUEST['metatt_other'][$att_id] );
		}

		if( $value == 'none' && isset( $selected[$att_id] ) ) {
			$gBitDb->query( "UPDATE `".BIT_DB_PREFIX."meta_associations` SET `end` = ? WHERE `content_id` = ? AND `meta_attribute_id` = ?", array( $now, $pContent->mContentId, $att_id ) );
		}
		elseif( !isset( $selected[$att_id] ) && $value != 'none' ) {
			$gBitDb->query( "INSERT INTO `".BIT_DB_PREFIX."meta_associations` ( `content_id`, `meta_attribute_id`, `meta_value_id`, `user_id`, `start` ) VALUES( ?, ?, ?, ?, ? )", array( $pContent->mContentId, $att_id, $value, $gBitUser->mUserId, $now ) );
		}
		elseif( isset( $selected[$att_id]) && $value != $selected[$att_id] ) {
			$gBitDb->query( "UPDATE `".BIT_DB_PREFIX."meta_associations` SET `end` = ? WHERE `content_id` = ? AND `meta_attribute_id` = ?", array( $now, $pContent->mContentId, $att_id ) );
			$gBitDb->query( "INSERT INTO `".BIT_DB_PREFIX."meta_associations` ( `content_id`, `meta_attribute_id`, `meta_value_id`, `user_id`, `start` ) VALUES( ?, ?, ?, ?, ? )", array( $pContent->mContentId, $att_id, $value, $gBitUser->mUserId, $now ) );
		}
	}

} // }}}

function meta_content_expunge( &$pContent, &$pParamHash ) { // {{{
	global $gBitSystem;
	global $gBitUser;
	$db = $gBitSystem->mDb;

	$db->query( "DELETE FROM `".BIT_DB_PREFIX."meta_associations` WHERE `content_id` = ?", array( $pParamHash->mContentId ) );

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
	$parameters = array();
	
	foreach( $p as $value ) {
		list( $key, $values ) = explode( ':', $value );
		$values = explode( '|', $values );

		$temp = array();

		foreach( $values as $value ) {
			if( $value == 'none' ) {
				$temp[] = "COUNT( IF( `attribute`.`name` = ?, 'GOOD', NULL ) ) = 0";
				$parameters[] = $key;
				continue;
			}

			if( $value == 'any' ) {
				$temp[] = "COUNT( IF( `attribute`.`name` LIKE ?, 'GOOD', NULL ) ) > 0";
				$parameters[] = $key;
				continue;
			}

			$temp[] = "COUNT( IF( `attribute`.`name` = ? AND `value`.`value` = ?, 'GOOD', NULL ) ) > 0";
			$parameters[] = $key;
			$parameters[] = $value;
		}

		if( count( $temp ) > 0 )
			$conditions[] = "( " . implode( ' OR ', $temp ) . " )";
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
				INNER JOIN `".BIT_DB_PREFIX."meta_attributes` as `attribute` ON `meta`.`meta_attribute_id` = `attribute`.`meta_attribute_id`
				INNER JOIN `".BIT_DB_PREFIX."meta_values` as `value` ON `meta`.`meta_value_id` = `value`.`meta_value_id`
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

		$result = $gBitSystem->mDb->query( $query, $parameters );

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

function data_metadata_help() { // {{{
	return 'N/A';
} // }}}

function data_metadata( $data, $params ) { // {{{
	extract ($params, EXTR_SKIP);

	
	global $gContent;

	if( !meta_placeholder_present() )
		return tra( "Could not display meta data: mod_meta_placeholder not found." );

	if( !isset( $title ) )
		return tra( "No title set." );
	
	$data = explode( "\n", $data );
	$categ = '';

	foreach( $data as $row ) {
		$row = explode( ':', $row, 2 );
		$row = array_map( 'trim', $row );

		switch( count( $row ) ) {
		case 1:
			if( !empty( $row[0] ) )
				$categ = $row[0];
			break;
		case 2:
			meta_add_pair( $title, $row[0], $gContent->parseData( $row[1] ), $categ );
			break;
		}
	}
	
    return ' ';
} // }}}

function meta_add_pair( $table, $name, $value, $section = '' ) { // {{{
	global $metaTables;

	if( !isset( $metaTables[$table] ) )
		$metaTables[$table] = array( '' => array() );
	if( !isset( $metaTables[$table][$section] ) )
		$metaTables[$table][$section] = array();

	$metaTables[$table][$section][] = array( 'name' => $name, 'value' => $value );
} // }}}

?>
