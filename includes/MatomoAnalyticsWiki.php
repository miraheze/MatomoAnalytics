<?php

namespace Miraheze\MatomoAnalytics;

use DateTime;
use DateTimeZone;
use MediaWiki\MediaWikiServices;

class MatomoAnalyticsWiki {

	public function __construct(
		private readonly int $period,
		private readonly int $siteId
	) {
	}

	private function getData( string $module, string $period, string $pageUrl ): array {
		$config = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'MatomoAnalytics' );
		if ( !$config->get( ConfigNames::ServerURL ) ) {
			// Early exit if we don't have the ServerURL set.
			return [];
		}

		$cacheKey = $this->getCacheKey( $module, $period, $pageUrl );
		$cache = MediaWikiServices::getInstance()->getMainWANObjectCache();
		$cachedData = $cache->get( $cacheKey );

		if ( is_array( $cachedData ) ) {
			return $cachedData;
		}

		$query = [
			'module' => 'API',
			'format' => 'json',
			'date' => "previous{$this->period}",
			'method' => $module,
			// Will be either day or range
			'period' => $period,
			'idSite' => $this->siteId,
			'token_auth' => $config->get( ConfigNames::TokenAuth ),
		];

		if ( $pageUrl !== '' ) {
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
			if ( $pageUrl !== null && $period === 'day' ) {
				// Support Actions.getPageUrl being such a special little snowflake
				$arrayOut[$key] = $val[0]['nb_visits'] ?: 0;
				continue;
			}

			if ( $period === 'day' ) {
				// Flat
				$arrayOut[$key] = $val['nb_visits'] ?: '-';
				continue;
			}

			$arrayOut[ $val['label'] ] = $val['nb_visits'] ?: '-';
		}

		// Calculate time to 1 AM next day in configured timezone
		$now = new DateTime( 'now', new DateTimeZone( 'UTC' ) );
		$next1AM = ( clone $now )->modify( 'tomorrow 01:00' );
		$expiration = $next1AM->getTimestamp() - $now->getTimestamp();

		// Store the result in cache until 1 AM
		$cache->set( $cacheKey, $arrayOut, $expiration );
		return $arrayOut;
	}

	private function getRangeData( string $module ): array {
		return $this->getData( $module, 'range', '' );
	}

	private function getPageRangeData( string $module, string $pageUrl ): array {
		return $this->getData( $module, 'range', $pageUrl );
	}

	private function getPerDayData( string $module ): array {
		return $this->getData( $module, 'day', '' );
	}

	private function getPagePerDayData( string $module, string $pageUrl ): array {
		return $this->getData( $module, 'day', $pageUrl );
	}

	private function getCacheKey( string $module, string $period, string $pageUrl ): string {
		$keyParts = [ $this->period, $this->siteId, $module, $period ];
		if ( $pageUrl !== '' ) {
			$keyParts[] = md5( $pageUrl );
		}

		return implode( ':', $keyParts );
	}

	/** Visits per browser type */
	public function getBrowserTypes(): array {
		return $this->getRangeData( 'DevicesDetection.getBrowsers' );
	}

	/** Visits by devices */
	public function getDeviceTypes(): array {
		return $this->getRangeData( 'DevicesDetection.getType' );
	}

	/** Visits by OS */
	public function getOSVersion(): array {
		return $this->getRangeData( 'DevicesDetection.getOsVersions' );
	}

	/** Visits by screen resolution */
	public function getResolution(): array {
		return $this->getRangeData( 'Resolution.getResolution' );
	}

	/** Visits by referrer */
	public function getReferrerType(): array {
		return $this->getRangeData( 'Referrers.getReferrerType' );
	}

	/** List of search numbers */
	public function getSearchKeywords(): array {
		return $this->getRangeData( 'Referrers.getKeywords' );
	}

	/** Visits by social network */
	public function getSocialReferrals(): array {
		return $this->getRangeData( 'Referrers.getSocials' );
	}

	/** Visits from another website */
	public function getWebsiteReferrals(): array {
		return $this->getRangeData( 'Referrers.getWebsites' );
	}

	/** Visits per continent */
	public function getUsersContinent(): array {
		return $this->getRangeData( 'UserCountry.getContinent' );
	}

	/** Visits per country */
	public function getUsersCountry(): array {
		return $this->getRangeData( 'UserCountry.getCountry' );
	}

	/** Visits per day */
	public function getVisitsByDay(): array {
		return $this->getRangeData( 'VisitTime.getByDayOfWeek' );
	}

	/** Visits per server hour */
	public function getVisitsPerServerHour(): array {
		$matomoData = $this->getRangeData( 'VisitTime.getVisitInformationPerServerTime' );

		$returnData = [];
		foreach ( $matomoData as $hour => $count ) {
			$labelHour = "$hour:00:00 - $hour:59:59";
			$returnData[$labelHour] = $count;
		}

		return $returnData;
	}

	/** Page groups per visit */
	public function getVisitPages(): array {
		return $this->getRangeData( 'VisitorInterest.getNumberOfVisitsPerPage' );
	}

	/** Time groups per visit */
	public function getVisitDurations(): array {
		return $this->getRangeData( 'VisitorInterest.getNumberOfVisitsPerVisitDuration' );
	}

	/** Days between visits */
	public function getVisitDaysPassed(): array {
		return $this->getRangeData( 'VisitorInterest.getNumberOfVisitsByDaysSinceLast' );
	}

	/** Visits by amount of views */
	public function getTopPages(): array {
		return $this->getRangeData( 'Actions.getPageTitles' );
	}

	/** Get visits for specific pages */
	public function getPageViews( string $pageUrl, string $periodType ): array {
		if ( $periodType === 'range' ) {
			return $this->getPageRangeData( 'Actions.getPageUrl', $pageUrl );
		}

		return $this->getPagePerDayData( 'Actions.getPageUrl', $pageUrl );
	}

	/** Get number of visits to the site */
	public function getSiteVisits(): array {
		return $this->getPerDayData( 'VisitsSummary.get' );
	}

	/** Get all keywords submitted to wiki search */
	public function getSiteSearchKeywords(): array {
		return $this->getRangeData( 'Actions.getSiteSearchKeywords' );
	}

	/** Get all campaigns */
	public function getCampaigns(): array {
		return $this->getRangeData( 'Referrers.getCampaigns' );
	}
}
