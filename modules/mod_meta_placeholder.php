<?php
/**
 * $Header: /cvsroot/bitweaver/_bit_meta/modules/mod_meta_placeholder.php,v 1.1 2006/02/24 00:51:58 lphuberdeau Exp $
 *
 * Copyright (c) 2004 bitweaver.org
 * Copyright (c) 2003 tikwiki.org
 * Copyright (c) 2002-2003, Luis Argerich, Garland Foster, Eduardo Polidor, et. al.
 * All Rights Reserved. See copyright.txt for details and a complete list of authors.
 * Licensed under the GNU LESSER GENERAL PUBLIC LICENSE. See license.txt for details
 *
 * $Id: mod_meta_placeholder.php,v 1.1 2006/02/24 00:51:58 lphuberdeau Exp $
 * @package wiki
 * @subpackage modules
 */
global $metaTables, $gBitSmarty;
$gBitSmarty->assign_by_ref( 'metaTables', $metaTables );
?>
