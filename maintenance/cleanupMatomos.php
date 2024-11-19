<?php

namespace Miraheze\MatomoAnalytics\Maintenance;

$IP ??= getenv( 'MW_INSTALL_PATH' ) ?: dirname( __DIR__, 3 );
require_once "$IP/maintenance/Maintenance.php";

use Maintenance;
use MediaWiki\MainConfigNames;
use Miraheze\MatomoAnalytics\MatomoAnalytics;

class CleanupMatomos extends Maintenance {

	public function __construct() {
		parent::__construct();

		$this->addDescription( 'Cleanup matomo ids that don\'t have corresponding cw_wikis entries.' );
		$this->addOption( 'dry-run', 'Perform a dry run and do not actually remove any matomo ids.' );

		$this->requireExtension( 'MatomoAnalytics' );
	}

	public function execute() {
		$connectionProvider = $this->getServiceContainer()->getConnectionProvider();
		$dbr = $dbr->getReplicaDatabase( 'virtual-matomoanalytics' );

		$databases = $this->getConfig()->get( MainConfigNames::LocalDatabases );

		$res = $dbr->select(
			'matomo',
			'*',
			[],
			__METHOD__
		);

		if ( !$res || !is_object( $res ) ) {
			$this->fatalError( '$res was not set to a valid array.' );
		}

		foreach ( $res as $row ) {
			$dbname = $row->matomo_wiki;

			if ( !in_array( $dbname, $databases ) ) {
				if ( $this->getOption( 'dry-run', false ) ) {
					$this->output( "[DRY RUN] Would remove matomo id from {$dbname}\n" );
					continue;
				}

				$this->output( "Remove matomo id from {$dbname}\n" );
				MatomoAnalytics::deleteSite( $dbname );
			}
		}
	}
}

$maintClass = CleanupMatomos::class;
require_once RUN_MAINTENANCE_IF_MAIN;
