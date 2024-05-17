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
		string $date = 'previous30',
		string $period = 'range',
		string $jsonLabel = 'label',
		string $jsonData = 'nb_visits',
		int $flat = 0,
		bool $flatArray = false
	) {
		$config = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'matomoanalytics' );

		$siteReply = MediaWikiServices::getInstance()->getHttpRequestFactory()->get(
			wfAppendQuery(
				$config->get( 'MatomoAnalyticsServerURL' ),
				[
					'module' => 'API',
					'format' => 'json',
					'date' => $date,
					'method' => $module,
					'period' => $period,
					'flat' => $flat,
					'idSite' => $this->siteId,
					'token_auth' => $config->get( 'MatomoAnalyticsTokenAuth' )
				]
			)
		);

		$siteJson = json_decode( $siteReply, true );

		$arrayOut = [];

		foreach ( $siteJson as $key => $val ) {
			if ( $flatArray ) {
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

	// Most visited pages
	public function getMostVisistedPages() {
		// We can also add support for linking to them through URLs with Actions.getPageUrls if we want
		return $this->getData( 'Actions.getPageTitles', 'today', 'month', 'label', 'nb_visits', 1 );
	}
}
