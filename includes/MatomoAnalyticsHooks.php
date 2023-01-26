<?php

use MediaWiki\MediaWikiServices;

class MatomoAnalyticsHooks {
	public static function matomoAnalyticsSchemaUpdates( DatabaseUpdater $updater ) {
		$updater->addExtensionTable( 'matomo',
			__DIR__ . '/../sql/matomo.sql' );

		$updater->addExtensionIndex( 'matomo', 'matomo_wiki',
			__DIR__ . '/../sql/patches/patch-matomo-add-indexes.sql' );
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

		static $alreadyDone = false;

		// Check if JS tracking is disabled or if the script has already been added and bow out early
		if ( $alreadyDone || $config->get( 'MatomoAnalyticsDisableJS' ) === true ) {
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
			$globalIdInt = (int)$globalId;
			$serverurl = $config->get( 'MatomoAnalyticsServerURL' );
			$title = $skin->getRelevantTitle();

			$jstitle = Xml::encodeJsVar( $title->getPrefixedText() );
			$dbname = Xml::encodeJsVar( $config->get( 'DBname' ) );
			$urltitle = $title->getPrefixedURL();
			$userType = $user->isRegistered() ? "User" : "Anonymous";
			$cookieDisable = (int)$config->get( 'MatomoAnalyticsDisableCookie' );
			$forceGetRequest = (int)$config->get( 'MatomoAnalyticsForceGetRequest' );
			$text .= <<<SCRIPT
				<script>
				var _paq = window._paq = window._paq || [];
				if ( {$cookieDisable} ) {
					_paq.push(['disableCookies']);
				}
				if ( {$forceGetRequest} ) {
					_paq.push(['setRequestMethod', 'GET']);
				}
				_paq.push(['trackPageView']);
				_paq.push(['enableLinkTracking']);
				(function() {
					var u = "{$serverurl}";
					_paq.push(['setTrackerUrl', u+'matomo.php']);
					_paq.push(['setDocumentTitle', {$dbname} + " - " + {$jstitle}]);
					_paq.push(['setSiteId', {$id}]);
					_paq.push(['setCustomVariable', 1, 'userType', "{$userType}", "visit"]);
					if ( {$globalIdInt} ) {
					    _paq.push(['addTracker', u + 'matomo.php', {$globalId}]);
					}
					var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
					g.async=true; g.src=u+'matomo.js'; s.parentNode.insertBefore(g,s);
				})();
				</script>
				<noscript><p><img src="{$serverurl}matomo.php?idsite={$id}&amp;rec=1&amp;action_name={$urltitle}" style="border:0;" alt="" /></p></noscript>
			SCRIPT;
		}

		$alreadyDone = true;

		return true;
	}
}
