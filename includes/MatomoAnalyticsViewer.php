<?php

namespace Miraheze\MatomoAnalytics;

use MediaWiki\Context\IContextSource;
use MediaWiki\Html\Html;
use MediaWiki\Output\OutputPage;

class MatomoAnalyticsViewer {
	public function getFormDescriptor(
		IContextSource $context
	) {
		OutputPage::setupOOUI(
			strtolower( $context->getSkin()->getSkinName() ),
			$context->getLanguage()->getDir()
		);

		$mA = new MatomoAnalyticsWiki( $context->getConfig()->get( 'DBname' ) );

		$descriptorData = [
			'sitevisits' => $mA->getSiteVisits(),
			'toppages' => $mA->getTopPages(),
			'browser' => $mA->getBrowserTypes(),
			'devices' => $mA->getDeviceTypes(),
			'os' => $mA->getOSVersion(),
			'resolution' => $mA->getResolution(),
			'referrer' => $mA->getReferrerType(),
			'search' => $mA->getSearchKeywords(),
			'social' => $mA->getSocialReferrals(),
			'website' => $mA->getWebsiteReferrals(),
			'continent' => $mA->getUsersContinent(),
			'country' => $mA->getUsersCountry(),
			'visitday' => $mA->getVisitsByDay(),
			'visithour' => $mA->getVisitsPerServerHour(),
			'visitpages' => $mA->getVisitPages(),
			'visitduration' => $mA->getVisitDurations(),
			'visitpass' => $mA->getVisitDaysPassed(),
		];

		$formDescriptor = [];
		foreach ( $descriptorData as $type => $data ) {
			$formDescriptor["{$type}-chart"] = [
				'type' => 'info',
				'raw' => true,
				'default' => $this->getAnalyticsCanvasHtml( $type, 'bar' ),
				'section' => 'matomoanalytics-labels-' . $type,
			];
			$formDescriptor["{$type}-showdata"] = [
				'type' => 'check',
				'label-message' => 'matomoanalytics-labels-showdata',
				'section' => 'matomoanalytics-labels-' . $type,
			];
			foreach ( $data as $label => $value ) {
				$formDescriptor["{$type}-{$label}"] = [
					'type' => 'info',
					'label' => $label,
					'hide-if' => [ '!==', "{$type}-showdata", '1' ],
					'default' => (string)$value,
					'section' => 'matomoanalytics-labels-' . $type,
				];
			}
		}

		return $formDescriptor;
	}

	public function getAnalyticsCanvasHtml( string $type, string $chartType ) {
		$html = '';

		$html .= Html::element( 'canvas', [
			'id' => 'matomoanalytics-chart',
			'class' => 'matomoanalytics-chart matomoanalytics-chart-bar',
			'height' => 200,
			'width' => 500,
		] );

		return $html;
	}

	public function getForm(
		IContextSource $context
	) {
		$formDescriptor = $this->getFormDescriptor( $context );

		return $formDescriptor;
	}
}
