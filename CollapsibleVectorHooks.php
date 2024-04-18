<?php
/**
 * Hooks for CollapsibleVector extension
 *
 * @file
 * @ingroup Extensions
 */

use MediaWiki\Hook\BeforePageDisplayHook;
use MediaWiki\Hook\MakeGlobalVariablesScriptHook;
use MediaWiki\MediaWikiServices;
use MediaWiki\Preferences\Hook\GetPreferencesHook;
use MediaWiki\ResourceLoader\Hook\ResourceLoaderGetConfigVarsHook;
use MediaWiki\Skins\Vector\SkinVector22;
use MediaWiki\Skins\Vector\SkinVectorLegacy;

class CollapsibleVectorHooks implements
	BeforePageDisplayHook,
	GetPreferencesHook,
	MakeGlobalVariablesScriptHook,
	ResourceLoaderGetConfigVarsHook
{

	/** @var array */
	protected static $features = [
		'collapsiblenav' => [
			'preferences' => [
				'collapsiblevector-collapsiblenav' => [
					'type' => 'toggle',
					'label-message' => 'collapsiblevector-collapsiblenav-preference',
					'section' => 'rendering/advancedrendering',
				],
			],
			'requirements' => [
				'collapsiblevector-collapsiblenav' => true,
			],
			'modules' => [ 'ext.collapsiblevector.collapsibleNav' ],
		],
		'experiments' => [
			'preferences' => [
				'collapsiblevector-noexperiments' => [
					'type' => 'toggle',
					'label-message' => 'collapsiblevector-noexperiments-preference',
					'section' => 'rendering/advancedrendering',
				],
			],
		],
	];

	/**
	 * Checks if a certain option is enabled
	 *
	 * @param string $name Name of the feature, should be a key of $features
	 * @return bool
	 */
	private function isEnabled( $name ) {
		global $wgCollapsibleVectorFeatures;

		// Features with global set to true are always enabled
		if (
			!isset( $wgCollapsibleVectorFeatures[$name] ) || $wgCollapsibleVectorFeatures[$name]['global']
		) {
			return true;
		}
		// Features with user preference control can have any number of preferences
		// to be specific values to be enabled
		if ( $wgCollapsibleVectorFeatures[$name]['user'] ) {
			if ( isset( self::$features[$name]['requirements'] ) ) {
				$userOptionsManager = MediaWikiServices::getInstance()->getUserOptionsManager();
				$user = RequestContext::getMain()->getUser();
				foreach ( self::$features[$name]['requirements'] as $requirement => $value ) {
					// Important! We really do want fuzzy evaluation here
					if ( $userOptionsManager->getOption( $user, $requirement ) != $value ) {
						return false;
					}
				}
			}
			return true;
		}
		// Features controlled by $wgCollapsibleVectorFeatures with both global and user set to false
		// are always disabled
		return false;
	}

	/**
	 * BeforePageDisplay hook
	 *
	 * Adds the modules to the page
	 *
	 * @param OutputPage $out output page
	 * @param Skin $skin current skin
	 * @return void
	 */
	public function onBeforePageDisplay( $out, $skin ): void {
		if ( $skin instanceof SkinVectorLegacy || $skin instanceof SkinVector22 ) {
			// Add modules for enabled features
			foreach ( self::$features as $name => $feature ) {
				if ( isset( $feature['modules'] ) && $this->isEnabled( $name ) ) {
					$out->addModules( $feature['modules'] );
				}
			}
		}
	}

	/**
	 * GetPreferences hook
	 *
	 * Adds Vector-releated items to the preferences
	 *
	 * @param User $user current user
	 * @param array &$defaultPreferences list of default user preference controls
	 * @return true
	 */
	public function onGetPreferences( $user, &$defaultPreferences ) {
		global $wgCollapsibleVectorFeatures;

		foreach ( self::$features as $name => $feature ) {
			if (
				isset( $feature['preferences'] ) &&
				( !isset( $wgCollapsibleVectorFeatures[$name] ) || $wgCollapsibleVectorFeatures[$name]['user'] )
			) {
				foreach ( $feature['preferences'] as $key => $options ) {
					$defaultPreferences[$key] = $options;
				}
			}
		}
		return true;
	}

	/**
	 * ResourceLoaderGetConfigVars hook
	 *
	 * Adds enabled/disabled switches for Vector modules
	 * @param array &$vars
	 * @param string $skin
	 * @param Config $config
	 * @return void
	 */
	public function onResourceLoaderGetConfigVars( array &$vars, $skin, Config $config ): void {
		global $wgCollapsibleVectorFeatures;

		$configurations = [];
		foreach ( self::$features as $name => $feature ) {
			if (
				isset( $feature['configurations'] ) &&
				( !isset( $wgCollapsibleVectorFeatures[$name] ) || $this->isEnabled( $name ) )
			) {
				foreach ( $feature['configurations'] as $configuration ) {
					global $$wgConfiguration;
					$configurations[$configuration] = $$wgConfiguration;
				}
			}
		}
		if ( count( $configurations ) ) {
			$vars = array_merge( $vars, $configurations );
		}
	}

	/**
	 * @param array &$vars
	 * @param OutputPage $out
	 * @return void
	 */
	public function onMakeGlobalVariablesScript( &$vars, $out ): void {
		// Build and export old-style wgVectorEnabledModules object for back compat
		$enabledModules = [];
		foreach ( self::$features as $name => $feature ) {
			$enabledModules[$name] = $this->isEnabled( $name );
		}

		$vars['wgCollapsibleVectorEnabledModules'] = $enabledModules;
	}
}
