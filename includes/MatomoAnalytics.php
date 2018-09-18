<?php
class MatomoAnalytics {
	private function __construct( $dbname ) {
		$this->dbname = $dbname;
	}

	public static function addSite( $dbname ) {
		global $wgMatomoAnalyticsServerURL, $wgMatomoAnalyticsUseDB, $wgMatomoAnalyticsDatabase, $wgMatomoAnalyticsTokenAuth;

		$queryapi = $wgMatomoAnalyticsServerURL;
		$queryapi .= '?module=API&format=json&method=SitesManager.addSite';
		$queryapi .= "&siteName=$dbname";
		$queryapi .= "&token_auth=$wgMatomoAnalyticsTokenAuth";

		$sitereply = file_get_contents($queryapi);
		$sitejson = json_decode( $sitereply );

		if ( $wgMatomoAnalyticsUseDB ) {
			$dbw = wfGetDB( DB_MASTER, array(), $wgMatomoAnalyticsDatabase );
			$dbw->insert(
				'matomo',
				array(
					'matomo_id' => $sitejson->value,
					'matomo_wiki' => $dbname,
				),
				__METHOD__
			);
		}

		return $sitejson->value;
	}

	public static function deleteSite( $dbname ) {
		global $wgMatomoAnalyticsServerURL, $wgMatomoAnalyticsUseDB, $wgMatomoAnalyticsDatabase, $wgMatomoAnalyticsTokenAuth;

		$siteid = MatomoAnalytics::getSiteID( $dbname );

		$queryapi = $wgMatomoAnalyticsServerURL;
		$queryapi .= '?module=API&format=json&method=SitesManager.deleteSite';
		$queryapi .= "&idSite=$siteid";
		$queryapi .= "&token_auth=$wgMatomoAnalyticsTokenAuth";

		$sitereply = file_get_contents( $queryapi );

		if ( $wgMatomoAnalyticsUseDB ) {
			$dbw = wfGetDB( DB_MASTER, array(), $wgMatomoAnalyticsDatabase );

			$dbw->delete(
				'matomo',
				array( 'matomo_id' => $siteid ),
				__METHOD__
			);
		}

		return true;
	}

	public static function renameSite( $old, $new ) {
		global $wgMatomoAnalyticsServerURL, $wgMatomoAnalyticsUseDB, $wgMatomoAnalyticsDatabase, $wgMatomoAnalyticsTokenAuth;

		$siteid = MatomoAnalytics::getSiteID( $old );

		$queryapi = $wgMatomoAnalyticsServerURL;
		$queryapi .= '?module=API&format=json&method=SitesManager.updateSite';
		$queryapi .= "&idSite=$siteid&siteName=$new";
		$queryapi .= "&token_auth=$wgMatomoAnalyticsTokenAuth";

		$sitereply = file_get_contents( $queryapi );

		if ( $wgMatomoAnalyticsUseDB ) {
			$dbw = wfGetDB( DB_MASTER, array(), $wgMatomoAnalyticsDatabase );

			$dbw->update(
				'matomo',
				array( 'matomo_wiki' => $new ),
				array( 'matomo_id' => $siteid ),
				__METHOD__
			);
		}

		if ( $siteid === MatomoAnalytics::getSiteID( $new ) ) {
			return true;
		} else {
			return 'Error in renaming Matomo references';
		}
	}

	public static function getSiteID( $dbname ) {
		global $wgMatomoAnalyticsUseDB, $wgMatomoAnalyticsDatabase, $wgMatomoAnalyticsSiteID;

		if ( $wgMatomoAnalyticsUseDB ) {
			$dbr = wfGetDB( DB_SLAVE, array(), $wgMatomoAnalyticsDatabase );
			$row = $dbr->selectRow(
				'matomo',
				array ( 'matomo_id' ),
				array ( 'matomo_wiki' => $dbname ),
				__METHOD__
			);

			if ( !isset( $row->matomo_id ) ) {
				wfDebugLog( 'MatomoAnalytics', "could not find {$dbname} in matomo table" );

				// Because site has not been found in the matomo table
				// lets put a 0 to prevent it throwing errors.
				return (int)0;
			} else {
				return $row->matomo_id;
			}
		} else {
			return $wgMatomoAnalyticsSiteID;
		}
	}

	private static function getAPIData( $dbname, $module, $period = 'month', $jsonlabel = 'label', $jsondata = 'nb_visits' ) {
		global $wgMatomoAnalyticsServerURL, $wgMatomoAnalyticsTokenAuth;

		$siteid = MatomoAnalytics::getSiteID( $dbname );

		$queryapi = $wgMatomoAnalyticsServerURL;
		$queryapi .= '?module=API&format=json&date=yesterday';
		$queryapi .= "&method=$module&period=$period&idSite=$siteid";
		$queryapi .= "&token_auth=$wgMatomoAnalyticsTokenAuth";

		$sitereply = file_get_contents( $queryapi );
		$json = json_decode( $sitereply, true );
		$arrayout = [];

		foreach ( $json as $key => $val ) {
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
