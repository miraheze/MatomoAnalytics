<?php

namespace Miraheze\MatomoAnalytics\Maintenance;

use Maintenance;
use Miraheze\MatomoAnalytics\MatomoAnalytics;

$IP = getenv( 'MW_INSTALL_PATH' );
if ( $IP === false ) {
	$IP = __DIR__ . '/../../..';
}

require_once "$IP/maintenance/Maintenance.php";

class ModifyMatomo extends Maintenance {

	/** @var string */
	private $matomoAnalytics;

	public function __construct( MatomoAnalytics $matomoAnalytics ) {
		parent::__construct();

		$this->addDescription( 'Add or remove a wiki from matomo.' );
		$this->addOption( 'remove', 'Remove wiki from matomo' );

		$this->requireExtension( 'MatomoAnalytics' );

		$this->matomoAnalytics = $matomoAnalytics;
	}

	public function execute() {
		$DBname = $this->getConfig()->get( 'DBname' );

		if ( $this->getOption( 'remove', false ) ) {
			$this->matomoAnalytics->deleteSite( $DBname );
		} else {
			$this->matomoAnalytics->addSite( $DBname );
		}
	}
}

$maintClass = ModifyMatomo::class;
require_once RUN_MAINTENANCE_IF_MAIN;
