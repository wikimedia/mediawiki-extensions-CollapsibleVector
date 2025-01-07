<?php

namespace Tests\CollapsibleVector\Structure;

/**
 * @group MobileFrontend
 */
class CollapsibleVectorBundleSizeTest extends \MediaWiki\Tests\Structure\BundleSizeTestBase {

	/** @inheritDoc */
	public function getBundleSizeConfig(): string {
		return dirname( __DIR__, 3 ) . '/bundlesize.config.json';
	}
}
