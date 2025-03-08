<?php

namespace Miraheze\MatomoAnalytics\Maintenance;

use MediaWiki\MainConfigNames;
use MediaWiki\Maintenance\Maintenance;
use Miraheze\MatomoAnalytics\MatomoAnalytics;

class CleanupMatomos extends Maintenance {

	public function __construct() {
		parent::__construct();

		$this->addDescription( 'Cleanup matomo ids that don\'t have corresponding cw_wikis entries.' );
		$this->addOption( 'dry-run', 'Perform a dry run and do not actually remove any matomo ids.' );

		$this->requireExtension( 'MatomoAnalytics' );
	}

	public function execute(): void {
		$connectionProvider = $this->getServiceContainer()->getConnectionProvider();
		$dbr = $connectionProvider->getReplicaDatabase( 'virtual-matomoanalytics' );

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

// @codeCoverageIgnoreStart
return CleanupMatomos::class;
// @codeCoverageIgnoreEnd
