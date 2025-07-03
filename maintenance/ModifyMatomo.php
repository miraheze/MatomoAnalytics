<?php

namespace Miraheze\MatomoAnalytics\Maintenance;

use MediaWiki\Maintenance\Maintenance;
use MediaWiki\WikiMap\WikiMap;
use Miraheze\MatomoAnalytics\MatomoAnalytics;

class ModifyMatomo extends Maintenance {

	public function __construct() {
		parent::__construct();

		$this->addDescription( 'Add or remove a wiki from Matomo.' );
		$this->addOption( 'remove', 'Remove the wiki from Matomo' );

		$this->requireExtension( 'MatomoAnalytics' );
	}

	public function execute(): void {
		$wikiId = WikiMap::getCurrentWikiId();
		if ( $this->hasOption( 'remove' ) ) {
			MatomoAnalytics::deleteSite( $wikiId );
			return;
		}

		MatomoAnalytics::addSite( $wikiId );
	}
}

// @codeCoverageIgnoreStart
return ModifyMatomo::class;
// @codeCoverageIgnoreEnd
