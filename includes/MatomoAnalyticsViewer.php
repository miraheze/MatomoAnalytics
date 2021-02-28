<?php

class MatomoAnalyticsViewer {
	public function getFormDescriptor(
		IContextSource $context
	) {
		OutputPage::setupOOUI(
			strtolower( $context->getSkin()->getSkinName() ),
			$context->getLanguage()->getDir()
		);

		$id = MatomoAnalytics::getSiteID( $context->getConfig()->get( 'DBname' ) );
		
		// If the id does not exist in the db,
		// lets just return here as we will likely
		// return inconsitent data or index
		// errors will happen.
		if ( $id == false ) {
			return [];
		}

		$mA = new MatomoAnalyticsWiki( $id );

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
			'visitpass' => $mA->getVisitDaysPassed()
		];

		$formDescriptor = [];
		foreach ( $descriptorData as $type => $data ) {
			foreach ( $data as $label => $value ) {
				$formDescriptor["{$type}-{$label}"] = [
					'type' => 'info',
					'label' => $label,
					'default' => (string)$value,
					'section' => $type
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

		$htmlForm->setId( 'mw-baseform-analytics' );
		$htmlForm->suppressDefaultSubmit();
		$htmlForm->setSubmitCallback( [ $this, 'dummyProcess' ] );

		return $htmlForm;
	}

	public function dummyProcess() {
		return true;
	}
}
