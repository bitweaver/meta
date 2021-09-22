<?php
/**
 * $Header$
 *
 * Copyright (c) 2013 bitweaver.org
 * All Rights Reserved. See below for details and a complete list of authors.
 * Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See http://www.gnu.org/copyleft/lesser.html for details
 *
 * @package meta
 */

require_once( '../kernel/includes/setup_inc.php' );

// Is package installed and enabled
$gBitSystem->verifyPackage( 'meta' );

// Now check permissions to access this page
$gBitSystem->verifyPermission( 'p_view_meta' );

require_once( META_PKG_PATH.'meta_lib.php' );

$data = data_metatable( '', $_REQUEST, 'csv' );

$file = tempnam( sys_get_temp_dir(), 'users' );
$fp = fopen($file, 'w');
foreach( $data as &$row ) {
	fputcsv( $fp, $row );
}
fclose( $fp );
header( "Content-Type: text/csv" );
header('Content-disposition: attachment;filename='.$gBitSystem->getConfig('site_title', 'Site').'-'.META_PKG_DIR.'-export-'.date('Y-m-d_Hi').'.csv');
readfile( $file );
flush();
unlink( $file );
exit;

