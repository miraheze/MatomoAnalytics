<?php

namespace Miraheze\MatomoAnalytics\Hooks\Handlers;

use MediaWiki\Installer\Hook\LoadExtensionSchemaUpdatesHook;

class Installer implements LoadExtensionSchemaUpdatesHook {

	public function onLoadExtensionSchemaUpdates( $updater ) {
		$updater->addExtensionTable( 'matomo',
			__DIR__ . '/../../../sql/matomo.sql' );

		$updater->addExtensionIndex( 'matomo', 'matomo_wiki',
			__DIR__ . '/../../../sql/patches/patch-matomo-add-indexes.sql' );
	}
}