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

		// $out->addModules( [ 'ext.matomoanalytics.oouiform' ] );
		$out->addModules( [ 'ext.matomoanalytics.charts', 'ext.matomoanalytics.graphs' ] );
		// $out->addModuleStyles( [ 'ext.matomoanalytics.oouiform.styles' ] );
		$out->addModuleStyles( [ 'oojs-ui-widgets.styles', 'ext.matomoanalytics.special' ] );

		$period = $this->getContext()->getRequest()->getRawVal( 'period' ) ?? 31;

		$selectionForm = [];

		$selectionForm['info'] = [
			'default' => $this->msg( 'matomoanalytics-header' )->text(),
			'type' => 'info',
		];

		$selectionForm['time'] = [
			'label-message' => 'managewiki-permissions-select',
			'default' => (int)$period ?? 31,
			'type' => 'select',
			'options' => [
				'1' => $this->msg( 'days' )->params( '1' )->text(),
				'7' => $this->msg( 'days' )->params( '7' )->text(),
				'14' => $this->msg( 'days' )->params( '14' )->text(),
				'21' => $this->msg( 'days' )->params( '21' )->text(),
				'31' => $this->msg( 'days' )->params( '31' )->text(),
			],
		];

		$selectForm = HTMLForm::factory( 'ooui', $selectionForm, $this->getContext(), 'selectionForm' );
		$selectForm->setId( 'matomoanalytics-form' )
			->setWrapperLegendMsg( 'managewiki-permissions-select-header' )
			->show();

		$selectForm->setMethod( 'post' )->setSubmitCallback( [ $this, 'onSubmitRedirectToSelection' ] )->prepareForm()->show();

		$analyticsViewer = new MatomoAnalyticsViewer();
		$htmlForm = $analyticsViewer->getForm( $this->getContext(), $period );

		$createForm = HTMLForm::factory( 'ooui', $htmlForm, $this->getContext() );
		$createForm->setId( 'matomoanalytics-form' )
			->suppressDefaultSubmit()
			->show();
	}

	public function onSubmitRedirectToSelection( array $params ) {
		header( 'Location: ' . SpecialPage::getTitleFor( 'Analytics' )->getFullURL() . '?' . $params['time'] );

		return true;
	}
}
