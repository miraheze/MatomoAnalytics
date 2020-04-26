<?php

use MediaWiki\MediaWikiServices;

class MatomoAnalyticsHooks {
	public static function matomoAnalyticsSchemaUpdates( DatabaseUpdater $updater ) {
		$config = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'matomoanalytics' );

		if ( $config->get( 'MatomoAnalyticsUseDB' ) && $config->get( 'MatomoAnalyticsDatabase' ) === $config->get( 'DBname' ) ) {
			$updater->addExtensionTable( 'matomo',
				__DIR__ . '/../sql/matomo.sql' );

			$updater->modifyTable(
 				'matomo',
  				__DIR__ . '/../sql/patches/patch-matomo-add-indexes.sql',
 				true
 			);
		}

		return true;
	}

	public static function wikiCreation( $dbname ) {
		MatomoAnalytics::addSite( $dbname );
	}

	public static function wikiDeletion( $dbw, $dbname ) {
		MatomoAnalytics::deleteSite( $dbname );
	}

	public static function wikiRename( $dbw, $old, $new ) {
		MatomoAnalytics::renameSite( $old, $new );
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
	public static function matomoScript( $skin, &$text = '' ) {
		$config = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'matomoanalytics' );

		// Check if JS tracking is disabled and bow out early
		if ( $config->get( 'MatomoAnalyticsDisableJS' ) === true ) {
			return true;
		}

		$user = RequestContext::getMain()->getUser();
		$mAId = MatomoAnalytics::getSiteID( $config->get( 'DBname' ) );

		$permissionManager = MediaWikiServices::getInstance()->getPermissionManager();
		if ( $permissionManager->userHasRight( $user, 'noanalytics' ) ) {
			$text .= '<!-- MatomoAnalytics: User right noanalytics is assigned. -->';
		} else {
			$id = strval( $mAId );
			$globalId = (string)$config->get( 'MatomoAnalyticsGlobalID' );
			$globalIdInt = (int)$config->get( 'MatomoAnalyticsGlobalID' );
			$serverurl = $config->get( 'MatomoAnalyticsServerURL' );
			$title = $skin->getRelevantTitle();
			$jstitle = Xml::encodeJsVar( $title->getPrefixedText() );
			$dbname = Xml::encodeJsVar( $config->get( 'DBname' ) );
			$urltitle = $title->getPrefixedURL();
			$userType = $user->isLoggedIn() ? "User" : "Anonymous";
			$cookieDisable = (int)$config->get( 'MatomoAnalyticsDisableCookie' );
			$text .= <<<SCRIPT
				<!-- Matomo -->
				<script type="text/javascript">
				var _paq = _paq || [];
				if ( {$cookieDisable} ) {
					_paq.push(['disableCookies']);
				}
				_paq.push(["trackPageView"]);
				_paq.push(["enableLinkTracking"]);
				(function() {
					var u = "{$serverurl}";
					_paq.push(["setTrackerUrl", u + "piwik.php"]);
					_paq.push(['setDocumentTitle', {$dbname} + " - " + {$jstitle}]);
					_paq.push(["setSiteId", "{$id}"]);
					_paq.push(["setCustomVariable", 1, "userType", "{$userType}", "visit"]);
					if ( {$globalIdInt} ) {
					    _paq.push(['addTracker', u + "piwik.php", {$globalId}]);
					}
					var d=document, g=d.createElement("script"), s=d.getElementsByTagName("script")[0]; g.type="text/javascript";
					g.defer=true; g.async=true; g.src=u+"piwik.js"; s.parentNode.insertBefore(g,s);
				})();
				</script>
				<!-- End Matomo Code -->
				<!-- Matomo Image Tracker -->
				<noscript><p><img src="{$serverurl}piwik.php?idsite={$id}&amp;rec=1&amp;action_name={$urltitle}" style="border:0;" alt="" /></p></noscript>
				<!-- End Matomo -->
SCRIPT;
		}

		return true;
	}
}

