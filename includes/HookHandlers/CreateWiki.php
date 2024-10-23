<?php

namespace Miraheze\MatomoAnalytics\HookHandlers;

use Miraheze\CreateWiki\Hooks\CreateWikiCreationHook;
use Miraheze\CreateWiki\Hooks\CreateWikiDeletionHook;
use Miraheze\CreateWiki\Hooks\CreateWikiRenameHook;
use Miraheze\MatomoAnalytics\MatomoAnalytics;
use Wikimedia\Rdbms\DBConnRef;

class CreateWiki implements
	CreateWikiCreationHook,
	CreateWikiDeletionHook,
	CreateWikiRenameHook
{
	public function onCreateWikiCreation( string $dbname, bool $private ): void {
		$mA = new MatomoAnalytics;
		$mA->addSite( $dbname );
	}

	public function onCreateWikiDeletion( DBConnRef $cwdb, string $dbname ): void {
		$mA = new MatomoAnalytics;
		$mA->deleteSite( $dbname );
	}

	public function onCreateWikiRename( DBConnRef $cwdb, string $old, string $new ): void {
		$mA = new MatomoAnalytics;
		$mA->renameSite( $old, $new );
	}
}
