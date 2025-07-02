<?php

namespace Miraheze\MatomoAnalytics\Specials;

use MediaWiki\Html\Html;
use MediaWiki\HTMLForm\HTMLForm;
use MediaWiki\SpecialPage\SpecialPage;
use Miraheze\MatomoAnalytics\MatomoAnalyticsViewer;

class SpecialAnalytics extends SpecialPage {

	public function __construct() {
		parent::__construct( 'Analytics', 'viewanalytics' );
	}

	/**
	 * @param ?string $par @phan-unused-param
	 */
	public function execute( $par ) {
		$this->setHeaders();
		$this->checkPermissions();
		$this->outputHeader();

		$out = $this->getOutput();

		$out->addModules( [
			'ext.matomoanalytics.charts',
			'ext.matomoanalytics.graphs',
		] );

		$out->addModuleStyles( [
			'oojs-ui-widgets.styles',
			'ext.matomoanalytics.special',
		] );

		$period = $this->getContext()->getRequest()->getInt( 'period', 7 );

		if ( $period < 1 || $period > 31 ) {
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

		$selectForm = HTMLForm::factory( 'ooui', $selectionForm, $this->getContext() );
		$selectForm->setMethod( 'post' )
			->setSubmitCallback( [ $this, 'onSubmitRedirectToSelection' ] )
			->setWrapperLegendMsg( 'rcfilters-limit-title' )
			->setId( 'matomoanalytics-submit' )
			->setSubmitTextMsg( 'view' )
			->prepareForm()
			->show();

		$analyticsViewer = new MatomoAnalyticsViewer();
		$htmlForm = $analyticsViewer->getForm( $this->getContext(), $period );

		$createForm = HTMLForm::factory( 'ooui', $htmlForm, $this->getContext() );
		$createForm->setId( 'matomoanalytics-form' )
			->suppressDefaultSubmit()
			->prepareForm()
			->displayForm( false );
	}

	public function onSubmitRedirectToSelection( array $formData ): void {
		$this->getOutput()->redirect(
			$this->getPageTitle()->getFullURL(
				[ 'period' => $formData['time'] ]
			)
		);
	}
}
