<?php

namespace Miraheze\MatomoAnalytics;

use DateTime;
use DateTimeZone;
use MediaWiki\MediaWikiServices;

class MatomoAnalyticsWiki {
	/** @var int */
	private int $siteId;

	/** @var int */
	private int $periodSelected;

	public function __construct( string $wiki, int $periodSelected = 7 ) {
		$this->siteId = MatomoAnalytics::getSiteID( $wiki );
		$this->periodSelected = $periodSelected;
	}

	public function getPeriodSelected(): int {
		return $this->periodSelected;
	}

	private function getData(
		string $module,
		string $period = 'range',
		string $jsonLabel = 'label',
		string $jsonData = 'nb_visits',
		bool $flat = false,
		?int $date = null,
		?string $pageUrl = null
	) {
		$config = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'matomoanalytics' );
		$date ??= $this->getPeriodSelected();

		$cacheKey = $this->getCacheKey( $module, $period, $date, $pageUrl );
		$cache = MediaWikiServices::getInstance()->getMainWANObjectCache();
		$cachedData = $cache->get( $cacheKey );

		if ( $cachedData !== false ) {
			return $cachedData;
		}

		$query = [
			'module' => 'API',
			'format' => 'json',
			'date' => 'previous' . $date,
			'method' => $module,
			'period' => $period,
			'idSite' => $this->siteId,
			'token_auth' => $config->get( ConfigNames::TokenAuth )
		];

		if ( $pageUrl !== null ) {
			$query['pageUrl'] = $pageUrl;
		}

		$siteReply = MediaWikiServices::getInstance()->getHttpRequestFactory()->get(
			wfAppendQuery(
				$config->get( ConfigNames::ServerURL ),
				$query
			)
		);

		$siteJson = json_decode( $siteReply, true );

		$arrayOut = [];

		foreach ( $siteJson as $key => $val ) {
			if ( $flat ) {
				$arrayOut[$key] = $val[$jsonLabel] ?: '-';
			} else {
				$arrayOut[$val[$jsonLabel]] = $val[$jsonData] ?: '-';
			}
		}

		// Calculate time to 1 AM next day in configured timezone
		$now = new DateTime( 'now', new DateTimeZone( 'UTC' ) );
		$next1AM = ( clone $now )->modify( 'tomorrow 01:00' );
		$expiration = $next1AM->getTimestamp() - $now->getTimestamp();

		// Store the result in cache until 1 AM
		$cache->set( $cacheKey, $arrayOut, $expiration );

		return $arrayOut;
	}

	private function getCacheKey(string $module, string $period, int $date, ?string $pageUrl): string {
		$keyParts = [$module, $period, $date];
		if ($pageUrl !== null) {
			$keyParts[] = md5($pageUrl);
		}
		return implode(':', $keyParts);
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

	// Visits by amount of views
	public function getTopPages() {
		return $this->getData( 'Actions.getPageTitles' );
	}

	// Get visits for specific pages
	public function getPageViews( string $pageUrl ) {
		return $this->getData( 'Actions.getPageUrl', 'range', 'label', 'nb_visits', false, 30, $pageUrl );
	}

	// Get number of visits to the site
	public function getSiteVisits() {
		return $this->getData( 'VisitsSummary.get', 'day', 'nb_visits', 'nb_visits', true );
	}

	// Get all keywords submitted to wiki search
	public function getSiteSearchKeywords() {
		return $this->getData( 'Actions.getSiteSearchKeywords' );
	}

	// Get all campaigns
	public function getCampaigns() {
		return $this->getData( 'Referrers.getCampaigns' );
	}
}
