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

function meta_get_possible_values( $content_id = null, $other = true ) { // {{{ 

	global $gBitUser, $gBitDb;

	$attributes = array( '' => array() );
	$selected = array();

	if( $content_id != null ) {
		$result = $gBitDb->query( "SELECT `meta_attribute_id`, `meta_value_id` FROM `".BIT_DB_PREFIX."meta_associations` WHERE `end` IS NULL AND `content_id` = ?", array( $content_id ) );

		foreach( $result->getRows() as $row )
			$selected[ $row['meta_attribute_id'] ] = $row['meta_value_id'];
	}

	$result = $gBitDb->query( "
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

	$gBitSmarty->assign( 'metaAttributes', meta_get_possible_values( $pContent->mContentId ) );
} // }}} 

function meta_get_attribute_id( $pAttributeName ) { // {{{ 
	global $gBitDb;
	return $gBitDb->getOne( "SELECT `meta_attribute_id` FROM `".BIT_DB_PREFIX."meta_attributes` WHERE `name` = ?", array( $pAttributeName ) );
} // }}} 

function meta_get_value_id( $value ) { // {{{ 
	global $gBitUser, $gBitDb;

	$ret = 0;

	$result = $gBitDb->query( "SELECT `meta_value_id` FROM `".BIT_DB_PREFIX."meta_values` WHERE `value` LIKE ?", array( $value ) );

	$result = $result->getRows();

	if( count( $result ) > 0 ) {
		$ret = $result[0]['meta_value_id'];
	} else {
		if( $gBitUser->hasPermission( 'p_edit_value_meta' ) && !empty( $value ) ) {
			$id = $gBitDb->genID( 'meta_value_id_seq' );
			$gBitDb->query( "INSERT INTO `".BIT_DB_PREFIX."meta_values` (`meta_value_id`, `value`) VALUES( ?, ? )", array( $id, $value ) );
			$ret = $id;
		}
	}
	return $ret;
} // }}} 

function meta_content_preview( &$pContent, &$pParamHash ) { // {{{ 
	global $gBitDb;
	global $gBitUser;
	global $gBitSmarty;

	if( !$gBitUser->hasPermission( 'p_assign_meta' ) )
		return;

	$attributes = meta_get_possible_values();
	
	if( !empty( $pParamHash['metatt'] ) ) {
		foreach( $pParamHash['metatt'] as $att_id => $value ) {
			foreach( $attributes as $group => $values ) {
				if( isset( $values[$att_id] ) )
					foreach( $values[$att_id]['values'] as $index => $row ) {
						if( $row['id'] == $value )
							$attributes[$group][$att_id]['values'][$index]['selected'] = true;
					}
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

	if( !isset( $pParamHash['metatt'] ) )
		return;

	$now = time();

	$selected = $gBitDb->getAssoc( "SELECT `meta_attribute_id`, `meta_value_id` FROM `".BIT_DB_PREFIX."meta_associations` WHERE `end` IS NULL AND `content_id` = ?", array( $pContent->mContentId ) );

	foreach( $pParamHash['metatt'] as $att_id => $value ) {
		if( !empty( $pParamHash['metatt_other'][$att_id] ) ) {
			$value = meta_get_value_id( $pParamHash['metatt_other'][$att_id] );
		}

		if( $value == 'none' && isset( $selected[$att_id] ) ) {
			$gBitDb->query( "UPDATE `".BIT_DB_PREFIX."meta_associations` SET `end` = ? WHERE `content_id` = ? AND `meta_attribute_id` = ?", array( $now, $pContent->mContentId, $att_id ) );
		} elseif( !isset( $selected[$att_id] ) && $value != 'none' ) {
			$gBitDb->query( "INSERT INTO `".BIT_DB_PREFIX."meta_associations` ( `content_id`, `meta_attribute_id`, `meta_value_id`, `user_id`, `start` ) VALUES( ?, ?, ?, ?, ? )", array( $pContent->mContentId, $att_id, $value, $gBitUser->mUserId, $now ) );
		} elseif( isset( $selected[$att_id]) && $value != $selected[$att_id] ) {
			$gBitDb->query( "UPDATE `".BIT_DB_PREFIX."meta_associations` SET `end` = ? WHERE `content_id` = ? AND `meta_attribute_id` = ?", array( $now, $pContent->mContentId, $att_id ) );
			$gBitDb->query( "INSERT INTO `".BIT_DB_PREFIX."meta_associations` ( `content_id`, `meta_attribute_id`, `meta_value_id`, `user_id`, `start` ) VALUES( ?, ?, ?, ?, ? )", array( $pContent->mContentId, $att_id, $value, $gBitUser->mUserId, $now ) );
		}
	}

} // }}} 

function meta_content_expunge( &$pContent, &$pParamHash ) { // {{{ 
	global $gBitDb;

	$gBitDb->query( "DELETE FROM `".BIT_DB_PREFIX."meta_associations` WHERE `content_id` = ?", array( $pParamHash->mContentId ) );

} // }}} 

function data_metasearch_help() { // {{{ 
	return 'N/A';
} // }}} 

function meta_search( $pParamHash ) { // {{{ 
	global $gBitDb;
	$ret = array();
	$bindVars = array();
	$havingSql = '';

	if( !empty( $pParamHash['conditions'] ) && count( $pParamHash['conditions'] ) > 0 ) {
		$havingSql = " HAVING " . implode( ' AND ', $pParamHash['conditions']['sql'] );
		$bindVars = array_merge( $bindVars, $pParamHash['conditions']['params']  );
	}

		$query = "
			SELECT lc.`content_id`, lc.`title`, lc.`last_modified`, `user`.`real_name`
			FROM
				`".BIT_DB_PREFIX."meta_associations` as `meta`
				INNER JOIN `".BIT_DB_PREFIX."meta_attributes` as `attribute` ON `meta`.`meta_attribute_id` = `attribute`.`meta_attribute_id`
				INNER JOIN `".BIT_DB_PREFIX."meta_values` as `value` ON `meta`.`meta_value_id` = `value`.`meta_value_id`
				INNER JOIN `".BIT_DB_PREFIX."liberty_content` as lc ON `meta`.`content_id` = lc.`content_id`
				INNER JOIN `".BIT_DB_PREFIX."users_users` as `user` ON `user`.`user_id` = lc.`user_id`
			WHERE `meta`.`end` IS NULL
			GROUP BY `meta`.`content_id`
			$havingSql
			ORDER BY lc.`last_modified` DESC";

		$result = $gBitDb->query( $query, $bindVars );
		$ret = $result->getRows();
	
	return $ret;
} // }}} 

function meta_parse_plugin_params( $paramString ) {
	$p = explode( ',', $paramString );
	$p = array_map( 'trim', $p );

	$conditions = array();
	foreach( $p as $value ) {
		list( $key, $values ) = explode( '=', $value );
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
			$conditions['params'][] = $key;
			$conditions['params'][] = $value;
		}

		if( count( $temp ) > 0 ) {
			$conditions['sql'][] = "( " . implode( ' OR ', $temp ) . " )";
		}
	}
	return $conditions;
}

function data_metasearch($data, $params) { // {{{ 
	global $gBitSystem;
	if( !isset( $params['param'] ) ) {
		return tra( 'Missing parameter "param".' );
	}
		
	$listHash['conditions'] = meta_parse_plugin_params( $params['param'] );
	$data = array();
	if( $rows = meta_search( $listHash ) ) {
		foreach( $rows as $row ) {
			$data[] = "[" . BIT_ROOT_URL 
				. "/index.php?content_id={$row['id']}|{$row['title']}]|" 
				. strftime( $gBitSystem->get_long_date_format(), $row['last_modified'] )
				. "|" . $row['real_name'];
		}
	}
	if( count( $data ) > 0 ) {
		$ret = '||' . implode( "\r\n", $data ) . '||';
	} else {
		$ret = tra( 'No results found.' );
	}
	return $ret;
} // }}} 

function data_metatable($data, $params) { // {{{ 
	global $gBitSystem;
	$whereSql = '';
	if( !isset( $params['param'] ) ) {
		return tra( 'Missing parameter "param".' );
	}
		
	$listHash['conditions'] = meta_parse_plugin_params( $params['param'] );
	$data = array();
	if( $rows = meta_search( $listHash ) ) {
		$columns = array( '-1'=>'Name' );
		if( !empty( $params['columns'] ) ) {
			$colVars = array();
			$groupSql = '';
			$p = explode( ',', $params['columns'] );
			$p = array_map( 'trim', $p );

			foreach( $p as $value ) {
				if( $valueId = meta_get_attribute_id( $value ) ) {
					$columns[$valueId] = $value;
					if( !empty( $groupSql ) ) {
						$groupSql .= ' OR ';
					}
					$groupSql .= ' meta_attribute_id=? ';
					$colVars[] = $valueId;
				} else {
					$columns[strtolower( $value )] = $value;
				}
			}
		}

		$rowCount = 1;
		foreach( $rows as $row ) {
			$dataString = '';
			$whereSql = '';
			$rowClass = ($rowCount++ % 2) ? 'odd' : 'even';
			$bindVars = array_merge( array( $row['content_id'] ), $colVars );
			$rowData[-1] = '<a href="'.BIT_ROOT_URL.'index.php?content_id='.$row['content_id'].'">'.$row['title'].'</a>';
			if( $groupSql ) {
				$whereSql .= " AND ( $groupSql ) ";
			}
			$sql = "SELECT * 
					FROM `".BIT_DB_PREFIX."meta_associations` metaa
						INNER JOIN `".BIT_DB_PREFIX."meta_values` metav ON( metaa.`meta_value_id`=metav.`meta_value_id`)
					WHERE metaa.`content_id`=?  $whereSql ";
			if( $vals = $gBitSystem->mDb->getAll( $sql, $bindVars ) ) {
				foreach( $vals as $v ) {
					$rowData[$v['meta_attribute_id']] = $v['value'];
				}
			}
			$sql = "SELECT lc.*, lcds.`data` AS `summary` 
					FROM `".BIT_DB_PREFIX."liberty_content` lc 
						LEFT OUTER JOIN `".BIT_DB_PREFIX."liberty_content_data` lcds ON( lc.`content_id` = lcds.`content_id` AND lcds.`data_type` = ? )
					WHERE lc.`content_id`=?";
			$contentData = current( $gBitSystem->mDb->getAssoc( $sql, array( 'summary', $row['content_id'] ) ) );
			foreach( $columns AS $valueId=>$value ) {
				$dataString .= '<td class=""'.$rowClass.'">'.(!empty( $rowData[$valueId] ) ? $rowData[$valueId] : (!empty( $contentData[$valueId] ) ? $contentData[$valueId] : '&nbsp;')).'</td>';
			}
			$data[] = $dataString;
		}
	}
	if( count( $data ) > 0 ) {
		$ret = '<table class="bittable"><tr><th class="bitbar">'.implode( '</th><th class="bitbar">', $columns ).'</th></tr><tr>' . implode( "</tr><tr>", $data ) . '</tr></table>';
	} else {
		$ret = tra( 'No results found.' );
	}

	return $ret;
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
		$row = explode( '=', $row, 2 );
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
