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

class ModifyMatomo extends Maintenance {
	public function __construct() {
		parent::__construct();

		$this->addDescription( 'Add or remove a wiki from matomo.' );
		$this->addOption( 'remove', 'Remove wiki from matomo' );

		$this->requireExtension( 'MatomoAnalytics' );
	}

	public function execute() {
		$dbname = $this->getConfig()->get( MainConfigNames::DBname );

		if ( $this->getOption( 'remove', false ) ) {
			MatomoAnalytics::deleteSite( $dbname );
		} else {
			MatomoAnalytics::addSite( $dbname );
		}
	}
}

$maintClass = ModifyMatomo::class;
require_once RUN_MAINTENANCE_IF_MAIN;
