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
	/** @var string */
	private $matomoAnalytics;

	public function __construct( MatomoAnalytics $matomoAnalytics ) {
		$this->matomoAnalytics = $matomoAnalytics;
	}

	public function onCreateWikiCreation( string $dbname, bool $private ): void {
		$this->matomoAnalytics->addSite( $dbname );
	}

	public function onCreateWikiDeletion( DBConnRef $cwdb, string $dbname ): void {
		$this->matomoAnalytics->deleteSite( $dbname );
	}

	public function onCreateWikiRename( DBConnRef $cwdb, string $old, string $new ): void {
		$this->matomoAnalytics->renameSite( $old, $new );
	}
}
