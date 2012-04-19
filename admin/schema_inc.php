<?php
$tables = array(
	// name and value are reserved database names and should be changed for something else
	'meta_attributes' => "
		meta_attribute_id I4 NOTNULL PRIMARY,
		name C(50) NOTNULL
	",
	'meta_values' => "
		meta_value_id I4 NOTNULL PRIMARY,
		value C(100) NOTNULL
	",
	// we use DATADICT constraints
	'meta_associations' => "
		content_id I4 NOTNULL,
		meta_attribute_id I4 NOTNULL,
		meta_value_id I4 NOTNULL,
		user_id I4 NOTNULL,
		start_epoch I4  UNSIGNED NOTNULL,
		end_epoch I4 UNSIGNED NULL
		CONSTRAINT ' , CONSTRAINT `meta_associations_content_ref` FOREIGN KEY (`content_id`) REFERENCES `".BIT_DB_PREFIX."liberty_content`( `content_id` )
			, CONSTRAINT `meta_associations_attribute_ref` FOREIGN KEY (`meta_attribute_id`) REFERENCES `".BIT_DB_PREFIX."meta_attributes`( `meta_attribute_id` )
			, CONSTRAINT `meta_associations_value_ref` FOREIGN KEY (`meta_value_id`) REFERENCES `".BIT_DB_PREFIX."meta_values`( `meta_value_id` )
			, CONSTRAINT `meta_associations_user_ref` FOREIGN KEY (`user_id`) REFERENCES `".BIT_DB_PREFIX."users_users`( `user_id` )'
	"
);

global $gBitInstaller;

foreach( array_keys( $tables ) AS $tableName ) {
	$gBitInstaller->registerSchemaTable( META_PKG_NAME, $tableName, $tables[$tableName] );
}

$gBitInstaller->registerPackageInfo( META_PKG_NAME, array(
	'description' => "Allows to assign meta-data to various content to enable better classification.",
	'license' => '<a href="http://www.gnu.org/licenses/licenses.html#LGPL">LGPL</a>',
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
	array( 'p_edit_attribute_meta', 'Can modify the list of valid attributes', 'editors', META_PKG_NAME ),
	array( 'p_edit_value_meta', 'Can create new value', 'registered', META_PKG_NAME ),
	array( 'p_assign_meta', 'Can assign meta values to content', 'registered', META_PKG_NAME ),
	array( 'p_view_meta', 'Can view meta attributes and values', 'basic', META_PKG_NAME ),
	array( 'p_browse_meta', 'Can browse content with similar meta attributes and values', 'basic', META_PKG_NAME ),
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

// Requirements
$gBitInstaller->registerRequirements( META_PKG_NAME, array(
    'liberty' => array( 'min' => '2.1.4' ),
));
