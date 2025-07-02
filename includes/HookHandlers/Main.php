<?php

namespace Miraheze\MatomoAnalytics\HookHandlers;

use MediaWiki\Hook\InfoActionHook;
use MediaWiki\Hook\SkinAfterBottomScriptsHook;
use MediaWiki\Html\Html;
use MediaWiki\WikiMap\WikiMap;
use Miraheze\MatomoAnalytics\ConfigNames;
use Miraheze\MatomoAnalytics\MatomoAnalytics;
use Miraheze\MatomoAnalytics\MatomoAnalyticsWiki;

class Main implements
	InfoActionHook,
	SkinAfterBottomScriptsHook
{

	/**
	 * Add Matomo JS to all MediaWiki pages
	 * Exclude users with the 'noanalytics' userright
	 *
	 * @inheritDoc
	 */
	public function onSkinAfterBottomScripts( $skin, &$text ) {
		$config = $skin->getConfig();
		// Check if JS tracking is disabled and bow out early
		if ( $config->get( ConfigNames::DisableJS ) ) {
			return;
		}

		if ( $skin->getAuthority()->isAllowed( 'noanalytics' ) ) {
			$text = '<!-- MatomoAnalytics: User right noanalytics is assigned. -->';
			return;
		}

		$siteId = (string)MatomoAnalytics::getSiteID( WikiMap::getCurrentWikiId() );

		$globalId = $config->get( ConfigNames::GlobalID );
		$globalIdString = (string)$globalId;

		$serverUrl = $config->get( ConfigNames::ServerURL );
		$title = $skin->getRelevantTitle();

		$jsTitle = Html::encodeJsVar( $title->getPrefixedText() );
		$wikiId = Html::encodeJsVar( WikiMap::getCurrentWikiId() );

		$urlTitle = $title->getPrefixedURL();
		$userType = $skin->getUser()->isRegistered() ? 'User' : 'Anonymous';

		$disableCookie = (int)$config->get( ConfigNames::DisableCookie );
		$forceGetRequest = (int)$config->get( ConfigNames::ForceGetRequest );
		$enableCustomDimensionsUserType = (int)$config->get( ConfigNames::EnableCustomDimensionsUserType );

		$text .= <<<SCRIPT
			<script>
			var _paq = window._paq = window._paq || [];
			if ( {$disableCookie} ) {
				_paq.push(['disableCookies']);
			}
			if ( {$forceGetRequest} ) {
				_paq.push(['setRequestMethod', 'GET']);
			}
			_paq.push(['trackPageView']);
			_paq.push(['enableLinkTracking']);
			(function() {
				var u = "{$serverUrl}";
				_paq.push(['setTrackerUrl', u+'matomo.php']);
				_paq.push(['setDocumentTitle', {$wikiId} + " - " + {$jsTitle}]);
				_paq.push(['setSiteId', {$siteId}]);
				if ( {$enableCustomDimensionsUserType} ) {
					_paq.push(['setCustomDimension', 1, "{$userType}"]);
				}
				if ( {$globalId} ) {
					_paq.push(['addTracker', u + 'matomo.php', {$globalIdString}]);
				}
				var d=document, g=d.createElement('script'), s=d.getElementsByTagName('script')[0];
				g.async=true; g.src=u+'matomo.js'; s.parentNode.insertBefore(g,s);
			})();
			</script>
			<noscript><p><img src="{$serverUrl}matomo.php?idsite={$siteId}&amp;rec=1&amp;action_name={$urlTitle}" style="border: 0;" alt="" /></p></noscript>
		SCRIPT;
	}

	/**
	 * Display total pageviews in the last 30 days and show a graph with details when clicked.
	 *
	 * @inheritDoc
	 */
	public function onInfoAction( $context, &$pageInfo ) {
		$mAId = MatomoAnalytics::getSiteID( WikiMap::getCurrentWikiId() );
		$mA = new MatomoAnalyticsWiki( period: 30, siteId: $mAId );

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
