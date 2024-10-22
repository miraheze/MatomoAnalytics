<?php

use MediaWiki\MediaWikiServices;

class MatomoAnalyticsWiki {
	/** @var int */
	private $siteId;

	public function __construct( $wiki ) {
		$this->siteId = MatomoAnalytics::getSiteID( $wiki );
	}

	private function getData(
		string $module,
		string $period = 'range',
		string $jsonLabel = 'label',
		string $jsonData = 'nb_visits',
		bool $flat = false,
		string $title = null
	) {
		$config = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'matomoanalytics' );

		$siteReply = MediaWikiServices::getInstance()->getHttpRequestFactory()->get(
			wfAppendQuery(
				$config->get( 'MatomoAnalyticsServerURL' ),
				[
					'module' => 'API',
					'format' => 'json',
					'date' => 'previous30',
					'method' => $module,
					'period' => $period,
					'idSite' => $this->siteId,
					'token_auth' => $config->get( 'MatomoAnalyticsTokenAuth' )
				]
			)
		);

		if ( $title !== null ) {
			$query['pageTitle'] = $title;
		}

		$siteJson = json_decode( $siteReply, true );

		$arrayOut = [];

		foreach ( $siteJson as $key => $val ) {
			if ( $flat ) {
				$arrayOut[$key] = $val ?: '-';
			} else {
				$arrayOut[$val[$jsonLabel]] = $val[$jsonData] ?: '-';
			}
		}

		return $arrayOut;
	}

	// Visits per browser type
	public function getBrowserTypes() {
		return $this->getData( 'DevicesDetection.getBrowsers' );
	}

	// Visits by devices
	public function getDeviceTypes() {
		return $this->getData( 'DevicesDetection.getType' );
	}

	// Visits by OS
	public function getOSVersion() {
		return $this->getData( 'DevicesDetection.getOsVersions' );
	}

	// Visits by screen resolution
	public function getResolution() {
		return $this->getData( 'Resolution.getResolution' );
	}

	// Visits by referrer
	public function getReferrerType() {
		return $this->getData( 'Referrers.getReferrerType' );
	}

	// List of search numbers
	public function getSearchKeywords() {
		return $this->getData( 'Referrers.getKeywords' );
	}

	// Visits by social network
	public function getSocialReferrals() {
		return $this->getData( 'Referrers.getSocials' );
	}

	// Visits from another website
	public function getWebsiteReferrals() {
		return $this->getData( 'Referrers.getWebsites' );
	}

	// Visits per continent
	public function getUsersContinent() {
		return $this->getData( 'UserCountry.getContinent' );
	}

	// Visits per country
	public function getUsersCountry() {
		return $this->getData( 'UserCountry.getCountry' );
	}

	// Visits per day
	public function getVisitsByDay() {
		return $this->getData( 'VisitTime.getByDayOfWeek' );
	}

	// Visits per server hour
	public function getVisitsPerServerHour() {
		$matomoData = $this->getData( 'VisitTime.getVisitInformationPerServerTime' );

		$returnData = [];
		foreach ( $matomoData as $hour => $count ) {
			$labelHour = "{$hour}:00:00 - {$hour}:59:59";
			$returnData[$labelHour] = $count;
		}

		return $returnData;
	}

	// Page groups per visit
	public function getVisitPages() {
		return $this->getData( 'VisitorInterest.getNumberOfVisitsPerPage' );
	}

	// Time groups per visit
	public function getVisitDurations() {
		return $this->getData( 'VisitorInterest.getNumberOfVisitsPerVisitDuration' );
	}

	// Days between visits
	public function getVisitDaysPassed() {
		return $this->getData( 'VisitorInterest.getNumberOfVisitsByDaysSinceLast' );
	}

	// Visits by social network
	public function getTopPages() {
		return $this->getData( 'Actions.getPageTitles' );
	}

	// Visits by social network
	public function getPageViews( $title ) {
		return $this->getData( 'Actions.getPageTitle', 'range', 'label', 'nb_visits', false, $title );
	}

}
