<?php

namespace Miraheze\MatomoAnalytics\HookHandlers;

use MediaWiki\Context\IContextSource;
use MediaWiki\Hook\InfoActionHook;
use MediaWiki\Hook\SkinAfterBottomScriptsHook;
use MediaWiki\Html\Html;
use MediaWiki\MainConfigNames;
use MediaWiki\MediaWikiServices;
use Miraheze\MatomoAnalytics\ConfigNames;
use Miraheze\MatomoAnalytics\MatomoAnalytics;
use Miraheze\MatomoAnalytics\MatomoAnalyticsWiki;
use Skin;

class Main implements
	InfoActionHook,
	SkinAfterBottomScriptsHook
{
	/**
	 * Function to add Matomo JS to all MediaWiki pages
	 *
	 * Adds exclusion for users with 'noanalytics' userright
	 *
	 * @param Skin $skin Skin object
	 * @param string &$text Output text.
	 * @return bool
	 */
	public function onSkinAfterBottomScripts( $skin, &$text ) {
		$config = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'matomoanalytics' );

		// Check if JS tracking is disabled and bow out early
		if ( $config->get( ConfigNames::DisableJS ) === true ) {
			return true;
		}

		$user = $skin->getUser();
		$mAId = MatomoAnalytics::getSiteID( $config->get( 'DBname' ) );

		$permissionManager = MediaWikiServices::getInstance()->getPermissionManager();
		if ( $permissionManager->userHasRight( $user, 'noanalytics' ) ) {
			$text = '<!-- MatomoAnalytics: User right noanalytics is assigned. -->';
			return true;
		}

		$id = strval( $mAId );
		$globalId = (string)$config->get( ConfigNames::GlobalID );
		$globalIdInt = (int)$globalId;
		$serverurl = $config->get( ConfigNames::ServerURL );
		$title = $skin->getRelevantTitle();

		$jstitle = Html::encodeJsVar( $title->getPrefixedText() );
		$dbname = Html::encodeJsVar( $config->get( 'DBname' ) );
		$urltitle = $title->getPrefixedURL();
		$userType = $user->isRegistered() ? 'User' : 'Anonymous';
		$cookieDisable = (int)$config->get( ConfigNames::DisableCookie );
		$forceGetRequest = (int)$config->get( ConfigNames::ForceGetRequest );
		$text = <<<SCRIPT
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

		return true;
	}

		/**
		 * Display total pageviews in the last 30 days and show a graph with details when clicked.
		 * @param IContextSource $context
		 * @param array &$pageInfo
		 */
	public function onInfoAction( $context, &$pageInfo ) {
		$mA = new MatomoAnalyticsWiki( $context->getConfig()->get( MainConfigNames::DBname ) );

		$title = $context->getTitle();
		$url = $title->getFullURL();
		$data = $mA->getPageViews( $url );
		$total = array_sum( $data );

		$pageInfo['header-basic'][] = [
			$context->msg( 'matomoanalytics-labels-pastmonth' ),
			$context->msg( 'matomoanalytics-count' )->numParams( $total )->parse()
		];
	}
}
