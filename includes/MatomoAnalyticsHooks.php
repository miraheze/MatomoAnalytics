<?php

class MatomoAnalyticsHooks {
	public static function matomoAnalyticsSchemaUpdates( DatabaseUpdater $updater ) {
		global $wgMatomoAnalyticsUseDB, $wgMatomoAnalyticsDatabase, $wgDBname;

		if ( $wgMatomoAnalyticsUseDB && $wgMatomoAnalyticsDatabase === $wgDBname ) {
			$updater->addExtensionTable( 'matomo',
				__DIR__ . '/../sql/matomo.sql' );
		}

		return true;
	}

	public function wikiCreation( $dbname ) {
		MatomoAnalytics::addSite( $dbname );
	}

	public function wikiDeletion( $dbname ) {
		MatomoAnalytics::deleteSite( $dbname );
	}

	/**
	* Function to add Matomo JS to all MediaWiki pages
	*
	* Adds exclusion for users with 'noanalytics' userright
	*
	* @param Skin $skin Skin object
	* @param string &$text Output text.
	* @return bool
	*/
	public function matomoScript( $skin, &$text = '' ) {
		global $wgMatomoAnalyticsServerURL, $wgMatomoAnalyticsSiteID, $wgUser, $wgDBname;
		if ( !$wgMatomoAnalyticsSiteID ) {
			$wgMatomoAnalyticsSiteID = 1;
		}

		if ( $wgUser->isAllowed( 'noanalytics' ) ) {
			$text .= '<!-- MatomoAnalytics: User right noanalytics is assigned. -->';
		} else {
			$id = strval( $wgMatomoAnalyticsSiteID );
			$serverurl = Xml::encodeJsVar( $wgMatomoAnalyticsServerURL );
			$title = $skin->getRelevantTitle();
			$jstitle = Xml::encodeJsVar( $title->getPrefixedText() );
			$dbname = Xml::encodeJsVar( $wgDBname );
			$urltitle = $title->getPrefixedURL();
			$userType = $wgUser->isLoggedIn() ? "User" : "Anonymous";
			$text .= <<<SCRIPT
				<!-- Piwik -->
				<script type="text/javascript">
				var _paq = _paq || [];
				_paq.push(["trackPageView"]);
				_paq.push(["enableLinkTracking"]);
				(function() {
					var u = "{$serverurl}/";
					_paq.push(["setTrackerUrl", u + "piwik.php"]);
					_paq.push(['setDocumentTitle', {$dbname} + " - " + {$jstitle}]);
					_paq.push(["setSiteId", "{$id}"]);
					_paq.push(["setCustomVariable", 1, "userType", "{$userType}", "visit"]);
					var d=document, g=d.createElement("script"), s=d.getElementsByTagName("script")[0]; g.type="text/javascript";
					g.defer=true; g.async=true; g.src=u+"piwik.js"; s.parentNode.insertBefore(g,s);
				})();
				</script>
				<!-- End Piwik Code -->
				<!-- Piwik Image Tracker -->
				<noscript>
					<img src="{$serverurl}/piwik.php?idsite={$id}&amp;rec=1&amp;action_name={$urltitle}" style="border:0" alt="" />
				</noscript>
				<!-- End Piwik -->
SCRIPT;
		}

		return true;
	}
}

