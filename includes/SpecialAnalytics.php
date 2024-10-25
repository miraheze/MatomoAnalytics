<?php

namespace Miraheze\MatomoAnalytics;

use MediaWiki\HTMLForm\HTMLForm;
use MediaWiki\SpecialPage\SpecialPage;

class SpecialAnalytics extends SpecialPage {
	public function __construct() {
		parent::__construct( 'Analytics' );
	}

	/**
	 * @param string $par
	 */
	public function execute( $par ) {
		$this->setHeaders();
		$this->outputHeader();

		$out = $this->getOutput();
		$out->addWikiMsg( 'matomoanalytics-header' );

		//$out->addModules( [ 'ext.matomoanalytics.oouiform' ] );
		$out->addModules( [ 'ext.matomoanalytics.charts' ] );
		$out->addModules( [ 'ext.matomoanalytics.graphs' ] );
		//$out->addModuleStyles( [ 'ext.matomoanalytics.oouiform.styles' ] );
		$out->addModuleStyles( [ 'oojs-ui-widgets.styles' ] );

		$analyticsViewer = new MatomoAnalyticsViewer();
		$htmlForm = $analyticsViewer->getForm( $this->getContext() );

		$createForm = HTMLForm::factory( 'ooui', $htmlForm, $this->getContext() );
		$createForm->setId( 'matomoanalytics-form' ),
			->suppressDefaultSubmit(),
			->show();
	}
}
