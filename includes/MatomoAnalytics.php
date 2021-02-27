<?php

use MediaWiki\MediaWikiServices;

class MatomoAnalytics {
	protected static $mACache;

	public static function addSite( $dbname ) {
		$config = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'matomoanalytics' );

		$siteReply = MediaWikiServices::getInstance()->getHttpRequestFactory()->get(
			wfAppendQuery(
				$config->get( 'MatomoAnalyticsServerURL' ),
				[
					'module' => 'API',
					'format' => 'json',
					'method' => 'SitesManager.addSite',
					'siteName' => $dbname,
					'token_auth' => $config->get( 'MatomoAnalyticsTokenAuth' )
				]
			),
			[],
			__METHOD__
		);

		$siteJson = FormatJson::decode( $siteReply, true );

		if ( $config->get( 'MatomoAnalyticsUseDB' ) ) {
			$dbw = wfGetDB( DB_MASTER, [], $config->get( 'MatomoAnalyticsDatabase' ) );
			try {
				$dbw->insert(
					'matomo',
					[
						'matomo_id' => $siteJson['value'],
						'matomo_wiki' => $dbname,
					],
					__METHOD__
				);
			} catch ( Exception $e ) {
				return null;
			}
		}

		return $siteJson['value'];
	}

	public static function deleteSite( $dbname ) {
		$config = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'matomoanalytics' );

		$siteId = static::getSiteID( $dbname );

		MediaWikiServices::getInstance()->getHttpRequestFactory()->get(
			wfAppendQuery(
				$config->get( 'MatomoAnalyticsServerURL' ),
				[
					'module' => 'API',
					'format' => 'json',
					'method' => 'SitesManager.deleteSite',
					'idSite' => $siteId,
					'token_auth' => $config->get( 'MatomoAnalyticsTokenAuth' )
				]
			),
			[],
			__METHOD__
		);

		if ( $config->get( 'MatomoAnalyticsUseDB' ) ) {
			$dbw = wfGetDB( DB_MASTER, [], $config->get( 'MatomoAnalyticsDatabase' ) );

			$dbw->delete(
				'matomo',
				[ 'matomo_id' => $siteId ],
				__METHOD__
			);

			$cache = ObjectCache::getLocalClusterInstance();
			$key = $cache->makeKey( 'matomo', 'id' );
			$cache->delete( $key );
		}

		return true;
	}

	public static function renameSite( $old, $new ) {
		$config = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'matomoanalytics' );

		$siteId = static::getSiteID( $old );

		MediaWikiServices::getInstance()->getHttpRequestFactory()->get(
			wfAppendQuery(
				$config->get( 'MatomoAnalyticsServerURL' ),
				[
					'module' => 'API',
					'format' => 'json',
					'method' => 'SitesManager.updateSite',
					'idSite' => $siteId,
					'siteName' => $new,
					'token_auth' => $config->get( 'MatomoAnalyticsTokenAuth' )
				]
			),
			[],
			__METHOD__
		);


		if ( $config->get( 'MatomoAnalyticsUseDB' ) ) {
			$dbw = wfGetDB( DB_MASTER, [], $config->get( 'MatomoAnalyticsDatabase' ) );

			$dbw->update(
				'matomo',
				[ 'matomo_wiki' => $new ],
				[ 'matomo_id' => $siteId ],
				__METHOD__
			);

			$cache = ObjectCache::getLocalClusterInstance();
			$key = $cache->makeKey( 'matomo', 'id' );
			$cache->delete( $key );
		}

		if ( $siteId === static::getSiteID( $new ) ) {
			return true;
		} else {
			throw new MWException( 'Error in renaming Matomo references' );
		}
	}

	public static function getSiteID( $dbname ) {
		$config = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'matomoanalytics' );

		if ( $config->get( 'MatomoAnalyticsUseDB' ) ) {
			$cache = ObjectCache::getLocalClusterInstance();
			$key = $cache->makeKey( 'matomo', 'id' );
			$cacheId = static::$mACache;
			if ( $cacheId === null ) {
				$cacheId = (int)$cache->get( $key );
			}

			if ( $cacheId ) {
				return $cacheId;
			}

			$dbr = wfGetDB( DB_REPLICA, [], $config->get( 'MatomoAnalyticsDatabase' ) );
			$id = $dbr->selectField(
				'matomo',
				'matomo_id',
				[ 'matomo_wiki' => $dbname ],
				__METHOD__
			);

			if ( !isset( $id ) ) {
				wfDebugLog( 'MatomoAnalytics', "could not find {$dbname} in matomo table" );

				// Because site has not been found in the matomo table
				// lets put a 0 to prevent it throwing errors.
				return (int)0;
			} else {
				$cache->set( $key, $id );

				return $id;
			}
		} else {
			return $config->get( 'MatomoAnalyticsSiteID' );
		}
	}
}
