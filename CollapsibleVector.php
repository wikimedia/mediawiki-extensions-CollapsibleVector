<?php
/**
 * CollapsibleVector extension
 * 
 * @file
 * @ingroup Extensions
 * 
 * @license GPL v2 or later
 * @version 0.1.2
*
* Requires MediaWiki 1.24+
 */


if ( version_compare( $GLOBALS['wgVersion'], '1.24c', '<' ) ) {
    die( '<b>Error:</b> CollapsibleVector requires MediaWiki 1.24 or above.' );
} 


/* Configuration */

// Each module may be configured individually to be globally on/off or user preference based
$GLOBALS['wgVectorFeatures'] = array(
	'collapsiblenav' => array( 'global' => true, 'user' => true ),
);

// Enable bucket testing for new version of collapsible nav
$GLOBALS['wgCollapsibleNavBucketTest'] = false;
// Force the new version
$GLOBALS['wgCollapsibleNavForceNewVersion'] = false;

/* Setup */

$GLOBALS['wgExtensionCredits']['other'][] = array(
	'path' => __FILE__,
	'name' => 'CollapsibleVector',
	'author' => array( 'paladox' ),
	'version' => '0.1.2',
	'url' => 'https://www.mediawiki.org/wiki/Extension:CollapsibleVector',
	'descriptionmsg' => 'collapsiblevector-desc',
);
$GLOBALS['wgAutoloadClasses']['VectorHooks'] = __DIR__ . '/CollapsibleVector.hooks.php';
$GLOBALS['wgExtensionMessagesFiles']['CollapsibleVector'] = __DIR__ . '/CollapsibleVector.i18n.php';
$GLOBALS['wgMessagesDirs']['CollapsibleVector'] = __DIR__ . '/i18n';
$GLOBALS['wgHooks']['BeforePageDisplay'][] = 'VectorHooks::beforePageDisplay';
$GLOBALS['wgHooks']['GetPreferences'][] = 'VectorHooks::getPreferences';
$GLOBALS['wgHooks']['ResourceLoaderGetConfigVars'][] = 'VectorHooks::resourceLoaderGetConfigVars';
$GLOBALS['wgHooks']['MakeGlobalVariablesScript'][] = 'VectorHooks::makeGlobalVariablesScript';

$GLOBALS['vectorResourceTemplate'] = array(
	'localBasePath' => __DIR__ . '/modules',
	'remoteExtPath' => 'CollapsibleVector/modules',
	'group' => 'ext.vector',
);
$GLOBALS['wgResourceModules'] += array(
	// TODO this module should be merged with ext.vector.collapsibleTabs
	'ext.vector.collapsibleNav' => $vectorResourceTemplate + array(
		'scripts' => 'ext.vector.collapsibleNav.js',
		'styles' => 'ext.vector.collapsibleNav.less',
		'messages' => array(
			'collapsiblevector-collapsiblenav-more',
		),
		'dependencies' => array(
			'jquery.client',
		    'jquery.cookie',
		    'jquery.tabIndex',
		),
	),
);
