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
		MatomoAnalytics::addSite( $dbname );
	}

	public function onCreateWikiDeletion( DBConnRef $cwdb, string $dbname ): void {
		MatomoAnalytics::deleteSite( $dbname );
	}

	public function onCreateWikiRename(
		DBConnRef $cwdb,
		string $oldDbName,
		string $newDbName
	): void {
		MatomoAnalytics::renameSite( $oldDbName, $newDbName );
	}
}
