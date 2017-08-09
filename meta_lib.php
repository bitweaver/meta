<?php
/**
 * $Header$
 *
 * Copyright (c) 2004 bitweaver.org
 * Copyright (c) 2003 tikwiki.org
 * Copyright (c) 2002-2003, Luis Argerich, Garland Foster, Eduardo Polidor, et. al.
 * All Rights Reserved. See below for details and a complete list of authors.
 * Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See http://www.gnu.org/copyleft/lesser.html for details
 *
 * @package meta
 * @subpackage functions
 */

/**
 * Initialize
 */
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
	global $gBitDb;
	global $gBitSmarty;
	global $metaTables;
	$result = $gBitDb->query( "
	SELECT matt.`name`, mval.`value` 
	FROM `".BIT_DB_PREFIX."meta_associations` mass
		INNER JOIN `".BIT_DB_PREFIX."meta_attributes` matt ON mass.`meta_attribute_id` = matt.`meta_attribute_id` 
		INNER JOIN `".BIT_DB_PREFIX."meta_values` mval ON mass.`meta_value_id` = mval.`meta_value_id` 
	WHERE mass.`content_id` = ?  AND mass.`end` IS NULL
	ORDER BY matt.`name`"
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
	global $gBitDb, $gBitUser, $gBitSmarty;

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
	global $gBitUser, $gBitDb;

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
			$gBitDb->query( "UPDATE `".BIT_DB_PREFIX."meta_associations` SET `end` = ? WHERE `content_id` = ? AND `meta_attribute_id` = ? AND `end` IS NULL", array( $now, $pContent->mContentId, $att_id ) );
		} elseif( !isset( $selected[$att_id] ) && $value != 'none' ) {
			$gBitDb->query( "INSERT INTO `".BIT_DB_PREFIX."meta_associations` ( `content_id`, `meta_attribute_id`, `meta_value_id`, `user_id`, `start` ) VALUES( ?, ?, ?, ?, ? )", array( $pContent->mContentId, $att_id, $value, $gBitUser->mUserId, $now ) );
		} elseif( isset( $selected[$att_id]) && $value != $selected[$att_id] ) {
			$gBitDb->query( "UPDATE `".BIT_DB_PREFIX."meta_associations` SET `end` = ? WHERE `content_id` = ? AND `meta_attribute_id` = ? AND `end` IS NULL", array( $now, $pContent->mContentId, $att_id ) );
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
	$selectSql = '';
	$joinSql = '';
	$havingSql = '';
	$whereSql = '';

	$i = 1;
	if( !empty( $pParamHash['search'] ) ) {
		foreach( $pParamHash['search'] as $name=>$value ) {
			$selectSql .= ", `attribute$i`.`name` AS `name$i`, `value$i`.`value` AS `value$i` ";
			$joinSql .= "
				INNER JOIN `".BIT_DB_PREFIX."meta_associations` as `meta$i` ON (`meta$i`.`content_id` = lc.`content_id` AND `meta$i`.`end` IS NULL)
				INNER JOIN `".BIT_DB_PREFIX."meta_attributes` as `attribute$i` ON `meta$i`.`meta_attribute_id` = `attribute$i`.`meta_attribute_id`
				INNER JOIN `".BIT_DB_PREFIX."meta_values` as `value$i` ON `meta$i`.`meta_value_id` = `value$i`.`meta_value_id`
			 ";
			$whereSql .= " AND `attribute$i`.`name`=? ";
			$bindVars[] = $name;

			if( !empty( $value ) ) {
				if( is_array( $value ) ) {
					$valueSql = '';
					while( $v = array_pop( $value ) ) {
						$valueSql .= " `value$i`.`value`=? ";
						if( count( $value ) ) {
							// we have more conditions to go
							$valueSql .= " OR ";
						}
						$bindVars[] = $v;
					}
					if( $valueSql ) {
						$whereSql .= ' AND ('.$valueSql.')';
					}
				} else {
					if( $value == 'none' ) {
						$whereSql .= " AND `value$i` IS NULL ";
					} elseif( $value == '*any*' ) {
						// Do Nothing
					} else {
						$whereSql .= " AND `value$i`.`value`=? ";
						$bindVars[] = $value;
					}
				}
			}
			$i++;
		}
	}

	$whereSql = preg_replace( '/^ AND /', ' WHERE ', $whereSql, 1 );

	if( empty( $pParamHash['sort_mode'] ) ) {
		$pParamHash['sort_mode'] = 'title_asc';
	}

	$query = "
		SELECT lc.`content_id`, lc.`title`, lc.`last_modified`, `user`.`real_name` $selectSql
		FROM `".BIT_DB_PREFIX."liberty_content` as lc
			INNER JOIN `".BIT_DB_PREFIX."users_users` as `user` ON `user`.`user_id` = lc.`user_id`
			$joinSql
		$whereSql
		$havingSql 
		ORDER BY ". $gBitDb->convertSortmode( $pParamHash['sort_mode'] );
	if( $result = $gBitDb->query( $query, $bindVars ) ) {
		while( $row = $result->fetchRow() ) {
			if( empty( $ret[$row['content_id']] ) ) {
				$ret[$row['content_id']] = $row;
			}
			foreach( $row as $key=>$value ) {
				if( preg_match( '/^value/', $key ) ) {
					$name = str_replace( 'value', 'name', $key );
					$ret[$row['content_id']]['meta'][$row[$name]] = $value;
				}
			}
		}
	}

	return $ret;
} // }}} 

function meta_parse_plugin_params( $paramString ) {
	$p = explode( ',', $paramString );
	$p = array_map( 'trim', $p );

	$conditions = array();
	foreach( $p as $value ) {
		list( $key, $values ) = explode( '=', $value );

		$values = explode( '|', $values );
		foreach( $values as $value ) {
			$conditions[$key][] = $value;
		}
	}
	return $conditions;
}

function data_metasearch($data, $params) { // {{{ 
	global $gBitSystem;
	if( !isset( $params['param'] ) ) {
		return tra( 'Missing parameter "param".' );
	}
		
	$listHash['search'] = meta_parse_plugin_params( $params['param'] );
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

function data_metatable( $data, $params, $pFormat='html' ) { // {{{ 
	global $gBitDb;
	$whereSql = '';
	$listHash['search'] = array();
	if( isset( $params['param'] ) ) {
		$listHash['search'] = meta_parse_plugin_params( $params['param'] );
	} elseif( isset( $params['query'] ) ) {
$pattern = '~(.+)(&&|\|\|)(.+)~';
		if( $parameters = preg_split($pattern, $params['query'], -1, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE) ) {
			foreach( $parameters as $param ) {
				if( $param == '&&' ) {
				} else {
					$keyValue = meta_parse_plugin_params( trim( $param ) );
					$listHash['search'][key($keyValue)] = current( $keyValue );
				}
			}
		}
	}
	if( empty( $listHash['search'] ) ) {
		return tra( 'Missing parameter "param" or "query".' );
	}
	$data = array();
	if( $rows = meta_search( $listHash ) ) {
		switch( $pFormat ) {
			case 'csv':
				$columns = array( '-1'=> tra( 'Name' ) );
				break;
			default:
				$columns = array( '-1'=>'<a href="'.META_PKG_URL.'export.php?'.http_build_query( $params ).'"><i class="icon-table"></i></a> '.tra( 'Name' ) );
				break;
		}

		$groupSql = '';
		if( !empty( $params['columns'] ) ) {
			$colVars = array();
			$p = explode( ',', $params['columns'] );
			$p = array_map( 'trim', $p );

			foreach( $p as $value ) {
				if( $valueId = meta_get_attribute_id( $value ) ) {
					$columns[$valueId] = ucwords( $value );
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

		// Populate each row with specified columns
		foreach( $rows as &$row ) {
			$bindVars = array( $row['content_id'] );
			$whereSql = '';

			switch( $pFormat ) {
				case 'csv':
					$row['meta'][-1] = $row['title'];
					break;
				default:
					$row['meta'][-1] = '<a href="'.BIT_ROOT_URL.'index.php?content_id='.$row['content_id'].'">'.$row['title'].'</a>';
					break;
			}


			if( $groupSql ) {
				$bindVars = array_merge( $bindVars, $colVars );
				$whereSql .= " AND ( $groupSql ) ";
			}
			$sql = "SELECT * 
					FROM `".BIT_DB_PREFIX."meta_associations` metaa
						INNER JOIN `".BIT_DB_PREFIX."meta_values` metav ON( metaa.`meta_value_id`=metav.`meta_value_id`)
					WHERE metaa.`content_id`=? AND `metaa`.`end` IS NULL $whereSql ";
			if( $vals = $gBitDb->getAll( $sql, $bindVars ) ) {
				foreach( $vals as $v ) {
					$row['meta'][$v['meta_attribute_id']] = $v['value'];
				}
			}
			$sql = "SELECT lc.*, lcds.`data` AS `summary` 
					FROM `".BIT_DB_PREFIX."liberty_content` lc 
						LEFT OUTER JOIN `".BIT_DB_PREFIX."liberty_content_data` lcds ON( lc.`content_id` = lcds.`content_id` AND lcds.`data_type` = ? )
					WHERE lc.`content_id`=?";
			$row['content'] = current( $gBitDb->getAssoc( $sql, array( 'summary', $row['content_id'] ) ) );

		}

		// sort if necessary
		if( !empty( $params['sort'] ) && $sortKey = array_search( $params['sort'], $columns ) ) {
			uasort ( $rows, function ($a, $b) use ($sortKey) {
				return strnatcmp( $a['meta'][$sortKey], $b['meta'][$sortKey] ); // or other function/code
			} );
		}

		// generate output
		foreach( $rows as $row ) {
			$dataString = '';
			$rowClass = ($rowCount++ % 2) ? 'odd' : 'even';
			foreach( $columns AS $valueId=>$value ) {
				switch( $pFormat ) {
					case 'csv':
						$dataString[] .= (!empty( $row['meta'][$valueId] ) ? $row['meta'][$valueId] : (!empty( $row['content'][$valueId] ) ? $row['content'][$valueId] : ''));
						break;
					default:
						$dataString .= '<td class="'.$rowClass.'">'.(!empty( $row['meta'][$valueId] ) ? $row['meta'][$valueId] : (!empty( $row['content'][$valueId] ) ? $row['content'][$valueId] : '&nbsp;')).'</td>';
						break;
				}
			}
			$data[] = $dataString;
		}
	}

	if( count( $data ) > 0 ) {
		switch( $pFormat ) {
			case 'csv':
				$ret = array_merge( array( $columns ), $data );
				break;
			default:
				$ret = '<table class="table"><tr><th>'.implode( '</th><th class="bitbar">', $columns ).'</th></tr><tr>' . implode( "</tr><tr>", $data ) . '</tr></table>';
				break;
		}
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
