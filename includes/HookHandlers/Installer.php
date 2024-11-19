<?php

namespace Miraheze\MatomoAnalytics\HookHandlers;

use MediaWiki\Installer\Hook\LoadExtensionSchemaUpdatesHook;

class Installer implements LoadExtensionSchemaUpdatesHook {

	public function onLoadExtensionSchemaUpdates( $updater ) {
		$updater->addExtensionUpdateOnVirtualDomain( [
			'virtual-matomoanalytics',
			'addTable',
			'matomo',
			__DIR__ . '/../../sql/matomo.sql',
			true,
		] );

		$updater->addExtensionUpdateOnVirtualDomain( [
			'virtual-matomoanalytics',
			'addIndex',
			'matomo',
			'matomo_wiki',
			__DIR__ . '/../../sql/patches/patch-matomo-add-indexes.sql',
			true,
		] );
	}
}
