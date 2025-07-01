<?php

namespace Miraheze\MatomoAnalytics;

use DateTime;
use DateTimeZone;
use MediaWiki\MediaWikiServices;

class MatomoAnalyticsWiki {

	private int $periodSelected;
	private int $siteId;

	public function __construct( string $dbname, int $periodSelected = 7 ) {
		$this->siteId = MatomoAnalytics::getSiteID( $dbname );
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
	): array {
		$config = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'MatomoAnalytics' );
		if ( !$config->get( ConfigNames::ServerURL ) ) {
			// Early exit if we don't have the ServerURL set.
			return [];
		}

		$date ??= $this->getPeriodSelected();

		$cacheKey = $this->getCacheKey( $this->siteId, $module, $period, $date, $pageUrl );
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
			'token_auth' => $config->get( ConfigNames::TokenAuth ),
		];

		if ( $pageUrl !== null ) {
			$query['pageUrl'] = $pageUrl;
		}

		$siteReply = MediaWikiServices::getInstance()->getHttpRequestFactory()->get(
			wfAppendQuery(
				$config->get( ConfigNames::ServerURL ),
				$query
			),
			[],
			__METHOD__
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

	private function getCacheKey(
		int $siteId,
		string $module,
		string $period,
		int $date,
		?string $pageUrl
	): string {
		$keyParts = [ $siteId, $module, $period, $date ];
		if ( $pageUrl !== null ) {
			$keyParts[] = md5( $pageUrl );
		}

		return implode( ':', $keyParts );
	}

	// Visits per browser type
	public function getBrowserTypes(): array {
		return $this->getData( 'DevicesDetection.getBrowsers' );
	}

	// Visits by devices
	public function getDeviceTypes(): array {
		return $this->getData( 'DevicesDetection.getType' );
	}

	// Visits by OS
	public function getOSVersion(): array {
		return $this->getData( 'DevicesDetection.getOsVersions' );
	}

	// Visits by screen resolution
	public function getResolution(): array {
		return $this->getData( 'Resolution.getResolution' );
	}

	// Visits by referrer
	public function getReferrerType(): array {
		return $this->getData( 'Referrers.getReferrerType' );
	}

	// List of search numbers
	public function getSearchKeywords(): array {
		return $this->getData( 'Referrers.getKeywords' );
	}

	// Visits by social network
	public function getSocialReferrals(): array {
		return $this->getData( 'Referrers.getSocials' );
	}

	// Visits from another website
	public function getWebsiteReferrals(): array {
		return $this->getData( 'Referrers.getWebsites' );
	}

	// Visits per continent
	public function getUsersContinent(): array {
		return $this->getData( 'UserCountry.getContinent' );
	}

	// Visits per country
	public function getUsersCountry(): array {
		return $this->getData( 'UserCountry.getCountry' );
	}

	// Visits per day
	public function getVisitsByDay(): array {
		return $this->getData( 'VisitTime.getByDayOfWeek' );
	}

	// Visits per server hour
	public function getVisitsPerServerHour(): array {
		$matomoData = $this->getData( 'VisitTime.getVisitInformationPerServerTime' );

		$returnData = [];
		foreach ( $matomoData as $hour => $count ) {
			$labelHour = "$hour:00:00 - $hour:59:59";
			$returnData[$labelHour] = $count;
		}

		return $returnData;
	}

	// Page groups per visit
	public function getVisitPages(): array {
		return $this->getData( 'VisitorInterest.getNumberOfVisitsPerPage' );
	}

	// Time groups per visit
	public function getVisitDurations(): array {
		return $this->getData( 'VisitorInterest.getNumberOfVisitsPerVisitDuration' );
	}

	// Days between visits
	public function getVisitDaysPassed(): array {
		return $this->getData( 'VisitorInterest.getNumberOfVisitsByDaysSinceLast' );
	}

	// Visits by amount of views
	public function getTopPages(): array {
		return $this->getData( 'Actions.getPageTitles' );
	}

	// Get visits for specific pages
	public function getPageViews( string $pageUrl, string $period = 'range' ): array {
		return $this->getData( 'Actions.getPageUrl', $period, 'label', 'nb_visits', false, 30, $pageUrl );
	}

	// Get number of visits to the site
	public function getSiteVisits(): array {
		return $this->getData( 'VisitsSummary.get', 'day', 'nb_visits', 'nb_visits', true );
	}

	// Get all keywords submitted to wiki search
	public function getSiteSearchKeywords(): array {
		return $this->getData( 'Actions.getSiteSearchKeywords' );
	}

	// Get all campaigns
	public function getCampaigns(): array {
		return $this->getData( 'Referrers.getCampaigns' );
	}
}
