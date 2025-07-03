<?php

namespace Miraheze\MatomoAnalytics\Maintenance;

use MediaWiki\MainConfigNames;
use MediaWiki\Maintenance\Maintenance;
use Miraheze\MatomoAnalytics\MatomoAnalytics;

class CleanupMatomos extends Maintenance {

	public function __construct() {
		parent::__construct();

		$this->addDescription( 'Cleanup Matomo IDs that don\'t have a corresponding wiki.' );
		$this->addOption( 'dry-run', 'Perform a dry run and do not actually remove any Matomo IDs.' );

		$this->requireExtension( 'MatomoAnalytics' );
	}

	public function execute(): void {
		$connectionProvider = $this->getServiceContainer()->getConnectionProvider();
		$dbr = $connectionProvider->getReplicaDatabase( 'virtual-matomoanalytics' );
		$databases = $this->getConfig()->get( MainConfigNames::LocalDatabases );
		$wikis = $dbr->newSelectQueryBuilder()
			->select( 'matomo_wiki' )
			->from( 'matomo' )
			->caller( __METHOD__ )
			->fetchFieldValues();

		foreach ( $wikis as $wiki ) {
			if ( in_array( $wiki, $databases, true ) ) {
				continue;
			}

			if ( $this->hasOption( 'dry-run' ) ) {
				$this->output( "[DRY RUN] Would remove Matomo ID from $wiki\n" );
				continue;
			}

			MatomoAnalytics::deleteSite( $wiki );
			$this->output( "Removed Matomo ID from $wiki\n" );
		}
	}
}

// @codeCoverageIgnoreStart
return CleanupMatomos::class;
// @codeCoverageIgnoreEnd
