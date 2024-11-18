<?php

namespace Miraheze\MatomoAnalytics\Maintenance;

use Maintenance;
use MediaWiki\MainConfigNames;
use Miraheze\MatomoAnalytics\MatomoAnalytics;

$IP = getenv( 'MW_INSTALL_PATH' );
if ( $IP === false ) {
	$IP = __DIR__ . '/../../..';
}

require_once "$IP/maintenance/Maintenance.php";

class AddMissingMatomos extends Maintenance {

	public function __construct() {
		parent::__construct();

		$this->addDescription( 'Add missing matomo ids.' );
		$this->requireExtension( 'MatomoAnalytics' );
	}

	public function execute() {
		$dbw = $this->getDB( DB_PRIMARY, [], 'virtual-matomoanalytics' );
		$databases = $this->getConfig()->get( MainConfigNames::LocalDatabases );
		foreach ( $databases as $dbname ) {
			$id = $dbw->selectField(
				'matomo',
				'matomo_id',
				[ 'matomo_wiki' => $dbname ],
				__METHOD__
			);

			if ( !isset( $id ) || !$id ) {
				$this->output( "Adding matomo id to {$dbname}\n" );
				MatomoAnalytics::addSite( $dbname );
				$this->output( "Done!\n" );
			}
		}
	}
}

$maintClass = AddMissingMatomos::class;
require_once RUN_MAINTENANCE_IF_MAIN;
