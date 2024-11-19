<?php

namespace Miraheze\MatomoAnalytics\Maintenance;

$IP ??= getenv( 'MW_INSTALL_PATH' ) ?: dirname( __DIR__, 3 );
require_once "$IP/maintenance/Maintenance.php";

use Maintenance;
use MediaWiki\MainConfigNames;
use Miraheze\MatomoAnalytics\MatomoAnalytics;

class ModifyMatomo extends Maintenance {

	public function __construct() {
		parent::__construct();

		$this->addDescription( 'Add or remove a wiki from matomo.' );
		$this->addOption( 'remove', 'Remove wiki from matomo' );

		$this->requireExtension( 'MatomoAnalytics' );
	}

	public function execute(): void {
		$dbname = $this->getConfig()->get( MainConfigNames::DBname );

		if ( $this->getOption( 'remove', false ) ) {
			MatomoAnalytics::deleteSite( $dbname );
			return;
		}

		MatomoAnalytics::addSite( $dbname );
	}
}

$maintClass = ModifyMatomo::class;
require_once RUN_MAINTENANCE_IF_MAIN;
