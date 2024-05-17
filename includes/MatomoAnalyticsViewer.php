<?php

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
			'mostvisited' => $mA->getMostVisistedPages(),
		];

		$formDescriptor = [];
		foreach ( $descriptorData as $type => $data ) {
			foreach ( $data as $label => $value ) {
				$formDescriptor["{$type}-{$label}"] = [
					'type' => 'info',
					'label' => $label,
					'default' => (string)$value,
					'section' => $type,
				];
			}
		}

		return $formDescriptor;
	}

	public function getForm(
		IContextSource $context
	) {
		$formDescriptor = $this->getFormDescriptor( $context );

		$htmlForm = new MatomoAnalyticsOOUIForm( $formDescriptor, $context, 'matomoanalytics-labels' );

		$htmlForm->setId( 'matomoanalytics-form' );
		$htmlForm->suppressDefaultSubmit();

		return $htmlForm;
	}
}
