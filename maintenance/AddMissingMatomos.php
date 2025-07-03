<?php

namespace Miraheze\MatomoAnalytics\Maintenance;

use MediaWiki\MainConfigNames;
use MediaWiki\Maintenance\Maintenance;
use Miraheze\MatomoAnalytics\MatomoAnalytics;

class AddMissingMatomos extends Maintenance {

	public function __construct() {
		parent::__construct();

		$this->addDescription( 'Add missing Matomo IDs.' );
		$this->requireExtension( 'MatomoAnalytics' );
	}

	public function execute(): void {
		$connectionProvider = $this->getServiceContainer()->getConnectionProvider();
		$dbr = $connectionProvider->getReplicaDatabase( 'virtual-matomoanalytics' );
		$databases = $this->getConfig()->get( MainConfigNames::LocalDatabases );
		foreach ( $databases as $dbname ) {
			$id = $dbr->newSelectQueryBuilder()
				->select( 'matomo_id' )
				->from( 'matomo' )
				->where( [ 'matomo_wiki' => $dbname ] )
				->caller( __METHOD__ )
				->fetchField();

			if ( !$id ) {
				MatomoAnalytics::addSite( $dbname );
				$this->output( "Added Matomo ID to $dbname\n" );
			}
		}
	}
}

// @codeCoverageIgnoreStart
return AddMissingMatomos::class;
// @codeCoverageIgnoreEnd
