<?php

use MediaWiki\MediaWikiServices;

class MatomoAnalytics {
	private $cache;
	private $config;
	private $httpRequestFactory;

	public function __construct() {
		$this->cache = ObjectCache::getLocalClusterInstance();
		$this->config = MediaWikiServices::getInstance()->getConfigFactory();
		$this->httpRequestFactory = MediaWikiServices::getInstance()->getHttpRequestFactory();
	}

	public static function addSite( $dbname ) {
		$config = $this->config->makeConfig( 'matomoanalytics' );

		$siteReply = $this->httpRequestFactory->get(
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
		$config = $this->config->makeConfig( 'matomoanalytics' );

		$siteId = static::getSiteID( $dbname );

		$this->httpRequestFactory->get(
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
			
			static::deleteCacheId();
		}

		return true;
	}

	public static function renameSite( $old, $new ) {
		$config = $this->config->makeConfig( 'matomoanalytics' );

		$siteId = static::getSiteID( $old );

		$this->httpRequestFactory->get(
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
			
			static::deleteCacheId();
		}

		if ( $siteId === static::getSiteID( $new ) ) {
			return true;
		} else {
			throw new MWException( 'Error in renaming Matomo references' );
		}
	}

	public static function getSiteID( string $dbname ) {
		$config = $this->config->makeConfig( 'matomoanalytics' );

		if ( $config->get( 'MatomoAnalyticsUseDB' ) ) {
			$cacheId = self::getCachedId();
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
				static::setCacheId( $id );
				return $id;
			}
		} else {
			return $config->get( 'MatomoAnalyticsSiteID' );
		}
	}

	public static function getCachedId() {
		$key = $this->cache->makeKey( 'matomo', 'id' );
		return $this->cache->get( $key );
	}

	public static function setCacheId( $id ) {
		$key = $this->cache->makeKey( 'matomo', 'id' );
		$this->cache->set( $key, $id );
	}

	public static function deleteCacheId() {
		$key = $this->cache->makeKey( 'matomo', 'id' );
		$this->cache->delete( $key );
	}
}
