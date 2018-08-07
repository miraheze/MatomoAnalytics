<?php

class SpecialAnalytics extends SpecialPage {
	function __construct() {
		parent::__construct( 'Analytics' );
	}

	function execute( $par ) {
		$this->setHeaders();
		$this->outputHeader();

		$out = $this->getOutput();

		$out->addWikiMsg( 'matomoanalytics-header' );

		$options = [
			'browser' => 'Browser Usage',
			'devices' => 'Device Usage',
			'referrer' => 'Site Referrers',
			'search' => 'Search Engine Keywords',
			'social' => 'Social Network Referrals',
			'website' => 'Website Referrals',
			'continent' => 'Visits By Continent',
			'country' => 'Visits By Country',
			'visitday' => 'Visits By Day',
			'visithour' => 'Visits By Hour',
			'visitpages' => 'Number of Pages Accessed',
			'visitduration' => 'Length of Visits',
			'visitpass' => 'Time Between Visits',
			'visitcount' => 'Visits By User',
		];

		$optionDescriptor = [
			'statistic1' => [
				'type' => 'select',
				'name' => 'stat1',
				'label-message' => 'matomoanalytics-form-stat1',
				'options' => array_flip( $options ),
			],
			'statistic2' => [
				'type' => 'select',
				'name' => 'stat2',
				'label-message' => 'matomoanalytics-form-stat2',
				'options' => array_flip( $options ),
			],
		];

		$optionForm = HTMLForm::factory( 'ooui', $optionDescriptor, $this->getContext() );
		$optionForm->setSubmitCallback( [ $this, 'dummyHandler' ] )->setMethod( 'get' )->setFormIdentifier( 'optionForm' )->prepareForm()->show();

		$stat1 = $this->getRequest()->getText( 'stat1' );
		$stat2 = $this->getRequest()->getText( 'stat2' );

		if ( !in_array( '', array( $stat1, $stat2 ) ) ) {
			$statDescriptor = [];

			$stat1out = self::statisticReturn( $stat1 );
			foreach ( $stat1out as $label => $value ) {
				$statDescriptor[$label] = [
					'type' => 'info',
					'label' => $label,
					'default' => (string)$value,
					'section' => 'matomoanalytics-form-stat1',
				];
			}


			$stat2out = self::statisticReturn( $stat2 );
			foreach ( $stat2out as $label => $value ) {
				$statDescriptor[$label] = [
					'type' => 'info',
					'label' => $label,
					'default' => (string)$value,
					'section' => 'matomoanalytics-form-stat2',
				];
			}

			$statForm = HTMLForm::factory( 'ooui', $statDescriptor, $this->getContext() );
			$statForm->setSubmitCallback( [ $this, 'dummyHandler' ] )->setMethod( 'get' )->setFormIdentifier( 'statForm' )->suppressDefaultSubmit()->prepareForm()->show();
		}

	}

	function dummyHandler( $formData ) {
		return false;
	}

	private function statisticReturn( $stat ) {
		global $wgDBname;

		switch ( $stat ) {
			case 'browser':
				$statarray = MatomoAnalytics::getBrowserTypes( $wgDBname );
				break;
			case 'device':
				$statarray = MatomoAnalytics::getDeviceTypes( $wgDBname );
				break;
			case 'referrer':
				$statarray = MatomoAnalytics::getReferrerType( $wgDBname );
				break;
			case 'search':
				$statarray = MatomoAnalytics::getSearchKeywords( $wgDBname );
				break;
			case 'social':
				$statarray = MatomoAnalytics::getSocialReferrals( $wgDBname );
				break;
			case 'website':
				$statarray = MatomoAnalytics::getWebsiteReferrals( $wgDBname );
				break;
			case 'continent':
				$statarray = MatomoAnalytics::getUsersContinent( $wgDBname );
				break;
			case 'country':
				$statarray = MatomoAnalytics::getUsersCountry( $wgDBname );
				break;
			case 'visitday':
				$statarray = MatomoAnalytics::getVisitsByDay( $wgDBname );
				break;
			case 'visithour':
				$statarray = MatomoAnalytics::getVisitsPerServerHour( $wgDBname );
				break;
			case 'visitpages':
				$statarray = MatomoAnalytics::getVisitPages( $wgDBname );
				break;
			case 'visitduration':
				$statarray = MatomoAnalytics::getVisitDurations( $wgDBname );
				break;
			case 'visitpass':
				$statarray = MatomoAnalytics::getVisitDaysPassed( $wgDBname );
				break;
			case 'visitcount':
				$statarray = MatomoAnalytics::getVisitsCount( $wgDBname );
				break;
		}

		return $statarray;
	}
}
