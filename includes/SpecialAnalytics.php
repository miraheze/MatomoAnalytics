<?php

namespace Miraheze\MatomoAnalytics;

use MediaWiki\Html\Html;
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

		$out->addModules( [ 'ext.matomoanalytics.charts', 'ext.matomoanalytics.graphs' ] );
		$out->addModuleStyles( [ 'oojs-ui-widgets.styles', 'ext.matomoanalytics.special' ] );

		$period = $this->getContext()->getRequest()->getInt( 'period', 7 );

		if ( $period <= 1 || $period > 31 ) {
			$period = 7;

			$out->addHTML(
				Html::errorBox( $this->msg( 'htmlform-select-badoption' )->escaped() )
			);
		}

		$selectionForm = [];

		$selectionForm['info'] = [
			'label-message' => 'matomoanalytics-header',
			'type' => 'info',
		];

		$selectionForm['time'] = [
			'label-message' => 'rcfilters-date-popup-title',
			'default' => $period,
			'type' => 'select',
			'options' => [
				$this->msg( 'days' )->numParams( 1 )->text() => 1,
				$this->msg( 'days' )->numParams( 7 )->text() => 7,
				$this->msg( 'days' )->numParams( 14 )->text() => 14,
				$this->msg( 'days' )->numParams( 21 )->text() => 21,
				$this->msg( 'days' )->numParams( 31 )->text() => 31,
			],
		];

		$selectForm = HTMLForm::factory( 'ooui', $selectionForm, $this->getContext(), 'selectionForm' );
		$selectForm->setId( 'matomoanalytics-submit' )
			->setWrapperLegendMsg( 'rcfilters-limit-title' )
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
		$out->redirect( SpecialPage::getTitleFor( 'Analytics' . '?' $params['time'] )->getFullURL() );

		return true;
	}
}

