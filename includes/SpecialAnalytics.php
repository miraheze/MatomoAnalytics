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

		$period = $this->getContext()->getRequest()->getRawVal( 'period' ) ?? 7;

		if ( !is_numeric( $period ) || (int)$period <= 1 || (int)$period > 31 ) {
			$period = 7;

			$out->addHTML(
				Html::errorBox( $this->msg( 'htmlform-select-badoption' )->escaped() )
			);
		} else {
			$period = (int)$period;
		}

		$selectionForm = [];

		$selectionForm['info'] = [
			'label-message' => 'matomoanalytics-header',
			'type' => 'info',
		];

		$selectionForm['time'] = [
			'label-message' => 'rcfilters-date-popup-title',
			'default' => (int)$period ?? 7,
			'type' => 'select',
			'options' => [
				$this->msg( 'days' )->params( '1' )->text() => 1,
				$this->msg( 'days' )->params( '7' )->text() => 7,
				$this->msg( 'days' )->params( '14' )->text() => 14,
				$this->msg( 'days' )->params( '21' )->text() => 21,
				$this->msg( 'days' )->params( '31' )->text() => 31,
			],
		];

		$selectForm = HTMLForm::factory( 'ooui', $selectionForm, $this->getContext(), 'selectionForm' );
		$selectForm->setId( 'matomoanalytics-submit' )
			->setWrapperLegendMsg( 'managewiki-permissions-select-header' )
			->setMethod( 'post' )
			->setFormIdentifier( 'selectForm' )
			->setSubmitCallback( [ $this, 'onSubmitRedirectToSelection' ] )
			->setSubmitTextMsg( 'view' )
			->prepareForm()
			->show();

		$analyticsViewer = new MatomoAnalyticsViewer();
		$htmlForm = $analyticsViewer->getForm( $this->getContext(), $period );

		$createForm = HTMLForm::factory( 'ooui', $htmlForm, $this->getContext() );
		$createForm->setId( 'matomoanalytics-form' )
			->suppressDefaultSubmit()
			->setSubmitCallback( [ $this, 'onSubmitRedirectToSelection' ] )
			->show();
	}

	public function onSubmitRedirectToSelection( array $params ) {
		header( 'Location: ' . SpecialPage::getTitleFor( 'Analytics' )->getFullURL() . '?' . $params['time'] );

		return true;
	}
}
