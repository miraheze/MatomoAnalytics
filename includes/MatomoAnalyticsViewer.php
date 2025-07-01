<?php

namespace Miraheze\MatomoAnalytics;

use MediaWiki\Context\IContextSource;
use MediaWiki\Html\Html;
use MediaWiki\MainConfigNames;
use MediaWiki\Output\OutputPage;

class MatomoAnalyticsViewer {

	private const CHART_TYPES = [
		'sitevisits' => 'line',
		'toppages' => 'doughnut',
		'topsearches' => 'bar',
		'browser' => 'doughnut',
		'devices' => 'doughnut',
		'os' => 'polarArea',
		'resolution' => 'pie',
		'referrer' => 'pie',
		'search' => 'bar',
		'social' => 'pie',
		'website' => 'bar',
		'continent' => 'polarArea',
		'country' => 'bar',
		'visitday' => 'bar',
		'visithour' => 'bar',
		'visitpages' => 'radar',
		'visitduration' => 'bar',
		'visitpass' => 'bar',
		'campaigns' => 'doughnut',
		'default' => 'pie',
	];

	public function getFormDescriptor(
		IContextSource $context,
		int $periodSelected
	): array {
		OutputPage::setupOOUI(
			strtolower( $context->getSkin()->getSkinName() ),
			$context->getLanguage()->getDir()
		);

		$mA = new MatomoAnalyticsWiki(
			$context->getConfig()->get( MainConfigNames::DBname ),
			$periodSelected
		);

		$descriptorData = [
			'sitevisits' => $mA->getSiteVisits(),
			'toppages' => $mA->getTopPages(),
			'topsearches' => $mA->getSiteSearchKeywords(),
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
			'campaigns' => $mA->getCampaigns(),
		];

		$formDescriptor = [];
		foreach ( $descriptorData as $type => $data ) {
			$chartType = self::CHART_TYPES[$type] ?? self::CHART_TYPES['default'];

			$formDescriptor["$type-info"] = [
				'type' => 'info',
				'cssclass' => 'matomoanalytics-chart-noselect',
				'label-message' => "matomoanalytics-labels-$type-info",
				'section' => "matomoanalytics-labels-$type",
			];

			$formDescriptor["$type-chart"] = [
				'type' => 'info',
				'raw' => true,
				'default' => $this->getAnalyticsCanvasHtml( $type, $chartType ),
				'section' => "matomoanalytics-labels-$type",
			];

			$formDescriptor["$type-showdata"] = [
				'type' => 'check',
				'label-message' => 'matomoanalytics-labels-showdata',
				'section' => "matomoanalytics-labels-$type",
			];

			foreach ( $data as $label => $value ) {
				$formDescriptor["$type-$label"] = [
					'type' => 'info',
					'label' => $label,
					'hide-if' => [ '!==', "$type-showdata", '1' ],
					'default' => (string)$value,
					'section' => "matomoanalytics-labels-$type",
				];
			}
		}

		return $formDescriptor;
	}

	public function getAnalyticsCanvasHtml( string $type, string $chartType ): string {
		return Html::element( 'canvas', [
			'id' => "matomoanalytics-chart-$type",
			'class' => [
				'matomoanalytics-chart',
				"matomoanalytics-chart-$chartType",
			],
			'data-chart-type' => $chartType,
			'style' => 'width: 100%; max-width: 500px;',
		] );
	}

	public function getForm(
		IContextSource $context,
		int $periodSelected
	): array {
		$formDescriptor = $this->getFormDescriptor( $context, $periodSelected );
		return $formDescriptor;
	}
}
