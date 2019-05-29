<?php

class SpecialAnalytics extends SpecialPage {
        public function __construct() {
		parent::__construct( 'Analytics' );
	}

	public function execute( $par ) {
		$this->setHeaders();
		$this->outputHeader();

		$out = $this->getOutput();
		$out->addWikiMsg( 'matomoanalytics-header' );
		$out->addModules( 'ext.matomoanalytics.oouiform' );

		$analyticsViewer = new MatomoAnalyticsViewer();
		$htmlForm = $analyticsViewer->getForm( $this->getContext() );
		$sectionTitles = $htmlForm->getFormSections();

		$sectTabs = [];
		foreach ( $sectionTitles as $key ) {
			$sectTabs[] = [
				'name' => $key,
				'label' => $htmlForm->getLegend( $key )
			];
		}

		$out->addJsConfigVars( 'wgMatomoAnalyticsOOUIFormTabs', $sectTabs );

		$htmlForm->show();
	}
}
