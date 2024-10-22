<?php

namespace Miraheze\MatomoAnalytics\Maintenance;

use Maintenance;

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
		$DBname = $this->getConfig()->get( 'DBname' );
		$mA = new MatomoAnalytics;

		if ( $this->getOption( 'remove', false ) ) {
			$mA->deleteSite( $DBname )
		} else {
			$mA->addSite( $DBname );
		}
	}
}
}

$maintClass = ModifyMatomo::class;
require_once RUN_MAINTENANCE_IF_MAIN;
