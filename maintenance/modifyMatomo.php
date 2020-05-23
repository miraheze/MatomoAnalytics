<?php

$IP = getenv( 'MW_INSTALL_PATH' );
if ( $IP === false ) {
	$IP = __DIR__ . '/../../..';
}
require_once "$IP/maintenance/Maintenance.php";

class ModifyMatomo extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->mDescription = "Adds or remove a wiki from matomo.";
		$this->addOption( 'remove', 'Removes wiki from matomo', false, false );
	}

	public function execute() {
		if ( $this->getOption( 'remove' ) ) {
			MatomoAnalyticsHooks::wikiDeletion( null, $wgDBname );
		} else {
			MatomoAnalyticsHooks::wikiCreation( $wgDBname );
		}
	}
}

$maintClass = 'ModifyMatomo';
require_once RUN_MAINTENANCE_IF_MAIN;
