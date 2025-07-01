<?php

namespace Miraheze\MatomoAnalytics\Maintenance;

use MediaWiki\MainConfigNames;
use MediaWiki\Maintenance\Maintenance;
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
		if ( $this->hasOption( 'remove' ) ) {
			MatomoAnalytics::deleteSite( $dbname );
			return;
		}

		MatomoAnalytics::addSite( $dbname );
	}
}

// @codeCoverageIgnoreStart
return ModifyMatomo::class;
// @codeCoverageIgnoreEnd
