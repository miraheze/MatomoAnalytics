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

}
