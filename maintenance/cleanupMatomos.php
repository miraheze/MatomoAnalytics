<?php

$IP = getenv( 'MW_INSTALL_PATH' );
if ( $IP === false ) {
  $IP = __DIR__ . '/../../..';
}
require_once "$IP/maintenance/Maintenance.php";

use MediaWiki\MediaWikiServices;

class CleanupMatomos extends Maintenance {
	public function __construct() {
		parent::__construct();

		$this->addDescription( 'Cleanup matomo ids that don\'t have corresponding cw_wikis entries.' );
		$this->addOption( 'dry-run', 'Perform a dry run and do not actually remove any matomo ids.' );
	}

	public function execute() {
		$config = MediaWikiServices::getInstance()
			->getConfigFactory()
			->makeConfig( 'matomoanalytics' );

		$dbw = $this->getDB( DB_PRIMARY, [], $config->get( 'CreateWikiDatabase' ) );

		$res = $dbw->select(
			'matomo',
			'*',
			[],
			__METHOD__
		);

		if ( !$res || !is_object( $res ) ) {
			throw new MWException( '$res was not set to a valid array.' );
		}

		foreach ( $res as $row ) {
			$DBname = $row->matomo_wiki;

			if ( $DBname === 'default' ) {
				continue;
			}

			$wiki = $dbw->selectField(
				'cw_wikis',
				'wiki_dbname',
				[ 'wiki_dbname' => $DBname ],
				__METHOD__
			);

			if ( !isset( $wiki ) || !$wiki ) {
				if ( !$this->getOption( 'dry-run', false ) ) {
					$this->output( "Remove matomo id from {$DBname}\n" );
					MatomoAnalytics::deleteSite( $DBname );

					continue;
				}

				$this->output( "[DRY RUN] Would remove matomo id from {$DBname}\n" );
			}
		}
	}
}

$maintClass = CleanupMatomos::class;
require_once RUN_MAINTENANCE_IF_MAIN;