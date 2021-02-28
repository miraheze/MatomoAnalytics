<?php

use MediaWiki\MediaWikiServices;

class MatomoAnalytics {
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

	public static function deleteSite( string $dbname ) {
		$config = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'matomoanalytics' );

		$siteId = static::getSiteID( $dbname );

		if ( $siteId == false ) {
			return true;
		}

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

	public static function renameSite( string $old, string $new ) {
		$config = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'matomoanalytics' );

		$siteId = static::getSiteID( $old );

		if ( $siteId == false ) {
			return true;
		}

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

	public static function getSiteID( string $dbname ){
		$config = MediaWikiServices::getInstance()->getConfigFactory()->makeConfig( 'matomoanalytics' );

		if ( $config->get( 'MatomoAnalyticsUseDB' ) ) {
			$cache = ObjectCache::getLocalClusterInstance();
			$key = $cache->makeKey( 'matomo', 'id' );
			$cacheId = $cache->get( $key );
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

			if ( !isset( $id ) || !$id ) {
				wfDebugLog( 'MatomoAnalytics', "could not find {$dbname} in matomo table" );

				// If the wiki does not exist, we do not return a default value,
				// instead callers are expected to default to a value.
				return false;
			} else {
				$cache->set( $key, $id );

				return $id;
			}
		} else {
			return $config->get( 'MatomoAnalyticsSiteID' );
		}
	}
}
