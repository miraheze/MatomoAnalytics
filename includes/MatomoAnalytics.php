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
		$queryapi .= '?modules=API&format=json&method=SitesManager.deleteSite';
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

	public static function getSiteID( $dbname ) {
		global $wgMatomoAnalyticsUseDB, $wgMatomoAnalyticsDatabase, $wgMatomoAnalyticsSiteID;

		if ( $wgMatomoAnalyticsUseDB ) {
			$row = wfGetDB( DB_SLAVE, array(), $wgMatomoAnalyticsDatabase )->selectRow(
				'matomo',
				array ( 'matomo_id' ),
				array ( 'matomo_wiki' => $dbname ),
				__METHOD__
			);

			return $row->matomo_id;
		} else {
			return $wgMatomoAnalyticsSiteID;
		}
	}
}
