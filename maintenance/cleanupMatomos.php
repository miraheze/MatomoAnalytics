<?php

namespace Miraheze\MatomoAnalytics\Maintenance;

use Maintenance;
use Miraheze\MatomoAnalytics\MatomoAnalytics;
use UnexpectedValueException;

$IP = getenv( 'MW_INSTALL_PATH' );
if ( $IP === false ) {
	$IP = __DIR__ . '/../../..';
}

require_once "$IP/maintenance/Maintenance.php";

class CleanupMatomos extends Maintenance {
	public function __construct() {
		parent::__construct();

		$this->addDescription( 'Cleanup matomo ids that don\'t have corresponding cw_wikis entries.' );
		$this->addOption( 'dry-run', 'Perform a dry run and do not actually remove any matomo ids.' );

		$this->requireExtension( 'CreateWiki' );
		$this->requireExtension( 'MatomoAnalytics' );
	}

	public function execute() {
		$dbw = $this->getDB( DB_PRIMARY, [], $this->getConfig()->get( 'CreateWikiDatabase' ) );

		$res = $dbw->select(
			'matomo',
			'*',
			[],
			__METHOD__
		);

		if ( !$res || !is_object( $res ) ) {
			throw new UnexpectedValueException( '$res was not set to a valid array.' );
		}

		foreach ( $res as $row ) {
			$DBname = $row->matomo_wiki;

			$wiki = $dbw->selectField(
				'cw_wikis',
				'wiki_dbname',
				[ 'wiki_dbname' => $DBname ],
				__METHOD__
			);

			if ( !isset( $wiki ) || !$wiki ) {
				if ( $this->getOption( 'dry-run', false ) ) {
					$this->output( "[DRY RUN] Would remove matomo id from {$DBname}\n" );
					continue;
				}

				$this->output( "Remove matomo id from {$DBname}\n" );
				$mA = new MatomoAnalytics;
				$mA->deleteSite( $DBname );
			}
		}
	}
}

$maintClass = CleanupMatomos::class;
require_once RUN_MAINTENANCE_IF_MAIN;
