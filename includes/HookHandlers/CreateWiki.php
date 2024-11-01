<?php

namespace Miraheze\MatomoAnalytics\HookHandlers;

use Miraheze\CreateWiki\Hooks\CreateWikiCreationHook;
use Miraheze\CreateWiki\Hooks\CreateWikiDeletionHook;
use Miraheze\CreateWiki\Hooks\CreateWikiRenameHook;
use Wikimedia\Rdbms\DBConnRef;

class CreateWiki implements
	CreateWikiCreationHook,
	CreateWikiDeletionHook,
	CreateWikiRenameHook
{
	public function onCreateWikiCreation( string $dbname, bool $private ): void {
		\Miraheze\MatomoAnalytics\MatomoAnalytics::addSite( $dbname );
	}

	public function onCreateWikiDeletion( DBConnRef $cwdb, string $dbname ): void {
		\Miraheze\MatomoAnalytics\MatomoAnalytics::deleteSite( $dbname );
	}

	public function onCreateWikiRename( DBConnRef $cwdb, string $old, string $new ): void {
		\Miraheze\MatomoAnalytics\MatomoAnalytics::renameSite( $old, $new );
	}
}
