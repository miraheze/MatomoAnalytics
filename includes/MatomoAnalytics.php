<?php
class MatomoAnalytics {
	// Cache site ID
	public $siteId = array();
	
	private function __construct( $dbname ) {
		$this->dbname = $dbname;
	}

	public static function addSite( $dbname ) {
		global $wgMatomoAnalyticsServerURL, $wgMatomoAnalyticsUseDB, $wgMatomoAnalyticsDatabase, $wgMatomoAnalyticsTokenAuth;

		$siteReply = Http::get(
			wfAppendQuery(
				$wgMatomoAnalyticsServerURL,
				[
					'module' => 'API',
					'format' => 'json',
					'method' => 'SitesManager.addSite',
					'siteName' => $dbname,
					'token_auth' => $wgMatomoAnalyticsTokenAuth
				]
			),
			[],
			__METHOD__ 
		);
		$siteJson = FormatJson::decode( $siteReply, true );

		if ( $wgMatomoAnalyticsUseDB ) {
			$dbw = wfGetDB( DB_MASTER, [], $wgMatomoAnalyticsDatabase );
			$dbw->insert(
				'matomo',
				[
					'matomo_id' => $siteJson->value,
					'matomo_wiki' => $dbname,
				],
				__METHOD__
			);
		}

		return $sitejson['value'];
	}

	public static function deleteSite( $dbname ) {
		global $wgMatomoAnalyticsServerURL, $wgMatomoAnalyticsUseDB, $wgMatomoAnalyticsDatabase, $wgMatomoAnalyticsTokenAuth;

		$siteReply = Http::get(
			wfAppendQuery(
				$wgMatomoAnalyticsServerURL,
				[
					'module' => 'API',
					'format' => 'json',
					'method' => 'SitesManager.deleteSite',
					'idSite' => MatomoAnalytics::getSiteID( $dbname ),
					'token_auth' => $wgMatomoAnalyticsTokenAuth
				]
			),
			[],
			__METHOD__ 
		);

		if ( $wgMatomoAnalyticsUseDB ) {
			$dbw = wfGetDB( DB_MASTER, [], $wgMatomoAnalyticsDatabase );

			$dbw->delete(
				'matomo',
				[ 'matomo_id' => $siteid ],
				__METHOD__
			);
		}

		return true;
	}

	public static function getSiteID( $dbname ) {
		global $wgMatomoAnalyticsUseDB, $wgMatomoAnalyticsDatabase, $wgMatomoAnalyticsSiteID;

		if ( $wgMatomoAnalyticsUseDB ) {
			if ( isset( $this->siteId[$dbname] ) ) {
				return $this->siteId[$dbname];
			} else {
				$dbr = wfGetDB( DB_REPLICA, [], $wgMatomoAnalyticsDatabase );
				$row = $dbr->selectRow(
					'matomo',
					[ 'matomo_id' ],
					[ 'matomo_wiki' => $dbname ],
					__METHOD__
				);
				$dbr->close();

				$this->siteId[$dbname] = $row->matomo_id;
				
				return $this->siteId[$dbname];
			}
		} else {
			return $wgMatomoAnalyticsSiteID;
		}
	}

	private static function getAPIData( $dbname, $module, $period = 'month', $jsonlabel = 'label', $jsondata = 'nb_visits' ) {
		global $wgMatomoAnalyticsServerURL, $wgMatomoAnalyticsTokenAuth;

		$siteReply = Http::get(
			wfAppendQuery(
				$wgMatomoAnalyticsServerURL,
				[
					'module' => 'API',
					'format' => 'json',
					'date' => 'yesterday',
					'method' => $module,
					'period' => $period,
					'idSite' => MatomoAnalytics::getSiteID( $dbname ),
					'token_auth' => $wgMatomoAnalyticsTokenAuth
				]
			),
			[],
			__METHOD__ 
		);
		$siteJson = FormatJson::decode( $siteReply, true );
		
		$arrayout = [];

		foreach ( $siteJson as $key => $val ) {
			$arrayout[$val[$jsonlabel]] = $val[$jsondata];
		}

		return $arrayout;
	}

	/*
	 * Below are a lot of functions that return arrays of data from Matomo APIs.
	 * Each function has a comment above it that states what it gets.
	 * This data can be manipulated by anyone, however they want.
	 */

	// Returns number of visits from a browser (Chrome, Firefox, Edge etc.)
	public static function getBrowserTypes( $dbname ) {
		return self::getAPIData( $dbname, 'DevicesDetection.getBrowsers' );
	}

	// Returns number of visits from a type of device (Desktop, Smartphone etc.)
	public static function getDeviceTypes( $dbname ) {
		return self::getAPIData( $dbname, 'DevicesDetection.getType' );
	}

	// Returns number of visits from a type of refereer (Search Engine, Websites etc.)
	public static function getReferrerType( $dbname ) {
		return self::getAPIData( $dbname, 'Referrers.getReferrerType' );
	}

	// Returns list of search terms and number of times they were used.
	public static function getSearchKeywords( $dbname ) {
		return self::getAPIData( $dbname, 'Referrers.getKeywords' );
	}

	// Returns number of visits from a social network.
	public static function getSocialReferrals( $dbname ) {
		return self::getAPIData( $dbname, 'Referrers.getSocials' );
	}

	// Returns number of visits that started from another website.
	public static function getWebsiteReferrals( $dbname ) {
		return self::getAPIData( $dbname, 'Referrers.getWebsites' );
	}

	// Returns number of visits per continent.
	public static function getUsersContinent( $dbname ) {
		return self::getAPIData( $dbname, 'UserCountry.getContinent' );
	}

	// Returns number of visits per country.
	public static function getUsersCountry( $dbname ) {
		return self::getAPIData( $dbname, 'UserCountry.getCountry' );
	}

	// Returns number of visits per day.
	public static function getVisitsByDay( $dbname ) {
		return self::getAPIData( $dbname, 'VisitTime.getByDayOfWeek' );
	}

	// Returns number of visits started per each server hour (24 hours, server timezone).
	public static function getVisitsPerServerHour( $dbname ) {
		return self::getAPIData( $dbname, 'VisitTime.getVisitInformationPerServerTime' );
	}

	// Returns page groupings for visit sessions (1 pages, 2-3 pages, 4-5 pages etc.).
	public static function getVisitPages( $dbname ) {
		return self::getAPIData( $dbname, 'VisitorInterest.getNumberOfVisitsPerPage' );
	}

	// Returns time groupings for visit sessions (0-10s, 11-60s, 1-5m etc.).
	public static function getVisitDurations( $dbname ) {
		return self::getAPIData( $dbname, 'VisitorInterest.getNumberOfVisitsPerVisitDuration' );
	}

	// Returns number of days passed between visit sessions (first visit, 0 days, 1 day, 2 days etc.).
	public static function getVisitDaysPassed( $dbname ) {
		return self::getAPIData( $dbname, 'VisitorInterest.getNumberOfVisitsByDaysSinceLast' );
	}

	// Returns number of visits grouped by total number of visits.
	public static function getVisitsCount( $dbname ) {
		return self::getAPIData( $dbname, 'VisitorInterest.getNumberOfVisitsByVisitCount' );
	}

	// Returns percentage of visit shares by total number of visits.
	public static function getVisitsCountPercentage( $dbname ) {
		return self::getAPIData( $dbname, 'VisitorInterest.getNumberOfVisitsByVisitCount', $jsondata = 'nb_visits_percentage' );
	}
}
