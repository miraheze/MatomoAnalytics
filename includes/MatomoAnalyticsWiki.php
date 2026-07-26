<?php

namespace Miraheze\MatomoAnalytics;

use DateTime;
use DateTimeZone;
use MediaWiki\MainConfigNames;
use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;

class MatomoAnalyticsWiki {

	public function __construct(
		private readonly int $period,
		private readonly int $siteId
	) {
	}

	private function fetchReport( string $module, string $period, string $pageUrl, array $extraParams = [] ): array {
		$config = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'MatomoAnalytics' );
		if ( !$config->get( ConfigNames::ServerURL ) ) {
			// Early exit if we don't have the ServerURL set.
			return [];
		}

		$cacheKey = $this->getCacheKey( $module, $period, $pageUrl, $extraParams );
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
		] + $extraParams;

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

		$rows = json_decode( $siteReply, true ) ?: [];

		// Calculate time to 1 AM next day in configured timezone
		$now = new DateTime( 'now', new DateTimeZone( 'UTC' ) );
		$next1AM = ( clone $now )->modify( 'tomorrow 01:00' );
		$expiration = $next1AM->getTimestamp() - $now->getTimestamp();

		// Store the result in cache until 1 AM
		$cache->set( $cacheKey, $rows, $expiration );
		return $rows;
	}

	private function getData( string $module, string $period, string $pageUrl ): array {
		$rows = $this->fetchReport( $module, $period, $pageUrl );

		$arrayOut = [];
		foreach ( $rows as $key => $val ) {
			$label = $key;
			if ( $period !== 'day' ) {
				$label = $val['label'];
			}

			$arrayOut[$label] = ( $val['nb_visits'] ?? null ) ?: '-';
		}

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

	private function getCacheKey( string $module, string $period, string $pageUrl, array $extraParams = [] ): string {
		$keyParts = [ $this->period, $this->siteId, $module, $period ];
		if ( $pageUrl !== '' ) {
			$keyParts[] = md5( $pageUrl );
		}

		if ( $extraParams ) {
			$keyParts[] = md5( serialize( $extraParams ) );
		}

		return implode( ':', $keyParts );
	}

	/** Resolve the tracked url back to the wiki's own page title, rather than the title Matomo recorded */
	private function resolveTitle( string $url ): ?string {
		if ( $url === '' ) {
			return null;
		}

		$mainConfig = MediaWikiServices::getInstance()->getMainConfig();
		$serverHost = parse_url( (string)$mainConfig->get( MainConfigNames::CanonicalServer ), PHP_URL_HOST );
		$urlHost = parse_url( $url, PHP_URL_HOST );

		if ( $serverHost !== null && $urlHost !== null && strcasecmp( $serverHost, $urlHost ) !== 0 ) {
			// Not a hit on this wiki's own domain, so there is no local title to resolve it to.
			return null;
		}

		$path = (string)parse_url( $url, PHP_URL_PATH );
		$prefix = str_replace( '$1', '', (string)$mainConfig->get( MainConfigNames::ArticlePath ) );

		if ( $prefix !== '' && str_starts_with( $path, $prefix ) ) {
			$titleText = substr( $path, strlen( $prefix ) );
		} else {
			parse_str( (string)parse_url( $url, PHP_URL_QUERY ), $query );
			$titleText = $query['title'] ?? null;
		}

		if ( !$titleText ) {
			return null;
		}

		$title = Title::newFromText( rawurldecode( $titleText ) );
		return $title ? $title->getPrefixedText() : null;
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

	/** Visits by amount of views, with the wiki's own page title and url for each row */
	public function getTopPages(): array {
		$rows = $this->fetchReport( 'Actions.getPageUrls', 'range', '', [ 'flat' => 1 ] );

		$pages = [];
		foreach ( $rows as $row ) {
			$url = (string)( $row['url'] ?? '' );
			if ( $url === '' ) {
				// Matomo rolls up the low-traffic tail of a folder that exceeded its archiving
				// row limit into a synthetic "<folder> - Others" row with no real url attached.
				// That is not an actual page, so it does not belong in a list of top pages.
				continue;
			}

			$title = $this->resolveTitle( $url ) ?? trim( (string)( $row['label'] ?? '' ) );
			$visits = ( $row['nb_visits'] ?? null ) ?: 0;

			if ( isset( $pages[$title] ) ) {
				$pages[$title]['visits'] += $visits;
				// Same page hit through different query strings, e.g. plain view and ?action=edit.
				// Keep the plain url as the one shown, since that is the page itself.
				if ( parse_url( $url, PHP_URL_QUERY ) === null ) {
					$pages[$title]['url'] = $url;
				}

				continue;
			}

			$pages[$title] = [
				'title' => $title,
				'url' => $url,
				'visits' => $visits,
			];
		}

		return array_values( $pages );
	}

	/** Get visits for specific pages */
	public function getPageViews( string $pageUrl ): array {
		return $this->getPageRangeData( 'Actions.getPageUrl', $pageUrl );
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
