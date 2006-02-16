<?php
$tables = array(
	'meta_attributes' => "
		meta_attribute_id I4 NOTNULL PRIMARY,
		name C(50) NOTNULL
	",
	'meta_values' => "
		meta_value_id I4 NOTNULL PRIMARY,
		value C(100) NOTNULL
	",
	'meta_associations' => "
		content_id I4 NOTNULL CONSTRAINTS 'FOREIGN KEY REFERENCES tiki_content',
		meta_attribute_id I4 NOTNULL CONSTRAINTS 'FOREIGN KEY REFERENCES meta_attributes',
		meta_value_id I4 NOTNULL CONSTRAINTS 'FOREIGN KEY REFERENCES meta_values',
		user_id I4 NOTNULL CONSTRAINTS 'FOREIGN KEY REFERENCES users_users',
		start I4  UNSIGNED NOTNULL,
		end I4 UNSIGNED NULL
	"
);

global $gBitInstaller;

$gBitInstaller->makePackageHomeable( META_PKG_NAME );

foreach( array_keys( $tables ) AS $tableName ) {
	$gBitInstaller->registerSchemaTable( META_PKG_NAME, $tableName, $tables[$tableName] );
}

$gBitInstaller->registerPackageInfo( META_PKG_NAME, array(
	'description' => "Allows to assign meta-data to various content to enable better classification.",
	'license' => '<a href="http://www.gnu.org/licenses/licenses.html#LGPL">LGPL</a>',
	'version' => '0.1',
	'state' => 'alpha',
	'dependencies' => '',
) );

// ### Indexes
$indices = array(
	'meta_asso_content_idx' => array( 'table' => 'meta_associations', 'cols' => 'content_id', 'opts' => 'KEY' ),
	'meta_asso_attribute_idx' => array( 'table' => 'meta_associations', 'cols' => 'meta_attribute_id', 'opts' => 'KEY' ),
	'meta_asso_value_idx' => array( 'table' => 'meta_associations', 'cols' => 'meta_value_id', 'opts' => 'KEY' ),
	'meta_asso_user_idx' => array( 'table' => 'meta_associations', 'cols' => 'user_id', 'opts' => 'KEY' ),
	'meta_attribute_name_idx' => array( 'table' => 'meta_attributes', 'cols' => 'name', 'opts' => 'UNIQUE' ),
	'meta_value_idx' => array( 'table' => 'meta_values', 'cols' => 'value', 'opts' => 'UNIQUE' )
);
$gBitInstaller->registerSchemaIndexes( META_PKG_NAME, $indices );

// ### Sequences
$sequences = array (
	'meta_attribute_id_seq' => array( 'start' => 100 ),
	'meta_value_id_seq' => array( 'start' => 1000 )
);
$gBitInstaller->registerSchemaSequences( META_PKG_NAME, $sequences );


$gBitInstaller->registerSchemaDefault( META_PKG_NAME, array(
	//      "INSERT INTO `".BIT_DB_PREFIX."bit_meta_types` (`type`) VALUES ('Sample')",
) );

// ### Default UserPermissions
$gBitInstaller->registerUserPermissions( META_PKG_NAME, array(
	array( 'bit_p_edit_attribute_meta', 'Can modify the list of valid attributes', 'editors', META_PKG_NAME ),
	array( 'bit_p_edit_value_meta', 'Can create new value', 'registered', META_PKG_NAME ),
	array( 'bit_p_assign_meta', 'Can assign meta values to content', 'registered', META_PKG_NAME ),
	array( 'bit_p_view_meta', 'Can view meta attributes and values', 'basic', META_PKG_NAME )
) );

// ### Default Preferences
$gBitInstaller->registerPreferences( META_PKG_NAME, array(
/*
	array( META_PKG_NAME, 'meta_default_ordering', 'meta_id_desc' ),
	array( META_PKG_NAME, 'meta_list_meta_id', 'y' ),
	array( META_PKG_NAME, 'meta_list_title', 'y' ),
	array( META_PKG_NAME, 'meta_list_description', 'y' ),
	array( META_PKG_NAME, 'feature_listSamples', 'y' ),
*/
) );
?>
