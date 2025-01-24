<?php
/**
 * Hooks for CollapsibleVector extension
 *
 * @file
 * @ingroup Extensions
 */

namespace MediaWiki\Extension\CollapsibleVector;

use MediaWiki\Config\Config;
use MediaWiki\Context\RequestContext;
use MediaWiki\Hook\MakeGlobalVariablesScriptHook;
use MediaWiki\Output\Hook\BeforePageDisplayHook;
use MediaWiki\Output\OutputPage;
use MediaWiki\Preferences\Hook\GetPreferencesHook;
use MediaWiki\ResourceLoader\Hook\ResourceLoaderGetConfigVarsHook;
use MediaWiki\Skins\Vector\SkinVector22;
use MediaWiki\Skins\Vector\SkinVectorLegacy;
use MediaWiki\User\Options\UserOptionsManager;
use MediaWiki\User\User;
use Skin;

class Hooks implements
	BeforePageDisplayHook,
	GetPreferencesHook,
	MakeGlobalVariablesScriptHook,
	ResourceLoaderGetConfigVarsHook
{
	private Config $config;
	private UserOptionsManager $userOptionsManager;

	public function __construct(
		Config $config,
		UserOptionsManager $userOptionsManager
	) {
		$this->config = $config;
		$this->userOptionsManager = $userOptionsManager;
	}

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
		$features = $this->config->get( 'CollapsibleVectorFeatures' );

		// Features with global set to true are always enabled
		if (
			!isset( $features[$name] ) || $features[$name]['global']
		) {
			return true;
		}
		// Features with user preference control can have any number of preferences
		// to be specific values to be enabled
		if ( $features[$name]['user'] ) {
			if ( isset( self::$features[$name]['requirements'] ) ) {
				$user = RequestContext::getMain()->getUser();
				// @phan-suppress-next-line PhanTypePossiblyInvalidDimOffset False positive
				foreach ( self::$features[$name]['requirements'] as $requirement => $value ) {
					// Important! We really do want fuzzy evaluation here
					if ( $this->userOptionsManager->getOption( $user, $requirement ) != $value ) {
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
		$features = $this->config->get( 'CollapsibleVectorFeatures' );

		foreach ( self::$features as $name => $feature ) {
			if (
				isset( $feature['preferences'] ) &&
				( !isset( $features[$name] ) || $features[$name]['user'] )
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
		$features = $this->config->get( 'CollapsibleVectorFeatures' );

		$configurations = [];
		foreach ( self::$features as $name => $feature ) {
			if (
				isset( $feature['configurations'] ) &&
				( !isset( $features[$name] ) || $this->isEnabled( $name ) )
			) {
				foreach ( $feature['configurations'] as $configuration ) {
					// @phan-suppress-next-line PhanUndeclaredVariable
					global $$wgConfiguration;
					$configurations[$configuration] = $$wgConfiguration;
					// @phan-suppress-previous-line PhanUndeclaredVariable
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
