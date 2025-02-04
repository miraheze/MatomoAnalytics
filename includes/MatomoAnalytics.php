<?php

namespace Miraheze\MatomoAnalytics;

use Exception;
use FormatJson;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use RuntimeException;

class MatomoAnalytics {
	private static function getConfig() {
		return MediaWikiServices::getInstance()
			->getConfigFactory()
			->makeConfig( 'MatomoAnalytics' );
	}

	private static function getLogger() {
		return LoggerFactory::getInstance( 'MatomoAnalytics' );
	}

	public static function addSite( $dbname ) {
		$config = self::getConfig();
		$logger = self::getLogger();

		$siteReply = MediaWikiServices::getInstance()->getHttpRequestFactory()->get(
			wfAppendQuery(
				$config->get( ConfigNames::ServerURL ),
				[
					'module' => 'API',
					'format' => 'json',
					'method' => 'SitesManager.addSite',
					'siteName' => $dbname,
					'token_auth' => $config->get( ConfigNames::TokenAuth )
				]
			),
			[],
			__METHOD__
		);

		$siteJson = FormatJson::decode( $siteReply, true );

		if ( !$siteJson ) {
			$logger->error( "Could not create id for {$dbname}." );
			return;
		}

		$siteId = $siteJson['value'];
		if ( $config->get( ConfigNames::UseDB ) ) {
			$dbw = MediaWikiServices::getInstance()->getConnectionProvider()
				->getPrimaryDatabase( 'virtual-matomoanalytics' );

			try {
				$dbw->insert(
					'matomo',
					[
						'matomo_id' => $siteId,
						'matomo_wiki' => $dbname,
					],
					__METHOD__
				);
			} catch ( Exception $e ) {
				return null;
			}
		}

		$logger->debug( "Successfully created {$dbname} with id {$siteId}." );
	}

	public static function deleteSite( $dbname ) {
		$config = self::getConfig();
		$logger = self::getLogger();

		$siteId = self::getSiteID( $dbname, true );

		if ( $config->get( ConfigNames::UseDB ) &&
			(string)$siteId === (string)$config->get( ConfigNames::SiteID )
		) {
			return;
		}

		MediaWikiServices::getInstance()->getHttpRequestFactory()->get(
			wfAppendQuery(
				$config->get( ConfigNames::ServerURL ),
				[
					'module' => 'API',
					'format' => 'json',
					'method' => 'SitesManager.deleteSite',
					'idSite' => $siteId,
					'token_auth' => $config->get( ConfigNames::TokenAuth )
				]
			),
			[],
			__METHOD__
		);

		if ( $config->get( ConfigNames::UseDB ) ) {
			$dbw = MediaWikiServices::getInstance()->getConnectionProvider()
				->getPrimaryDatabase( 'virtual-matomoanalytics' );

			$dbw->delete(
				'matomo',
				[ 'matomo_id' => $siteId ],
				__METHOD__
			);

			$cache = MediaWikiServices::getInstance()->getObjectCacheFactory()->getLocalClusterInstance();
			$key = $cache->makeKey( 'matomo', 'id' );
			$cache->delete( $key );
		}

		$logger->debug( "Successfully deleted {$dbname} with id {$siteId}." );

		return true;
	}

	public static function renameSite( $oldDb, $newDb ) {
		$config = self::getConfig();
		$logger = self::getLogger();

		$siteId = self::getSiteID( $oldDb, true );

		if ( $config->get( ConfigNames::UseDB ) &&
			(string)$siteId === (string)$config->get( ConfigNames::SiteID )
		) {
			return;
		}

		MediaWikiServices::getInstance()->getHttpRequestFactory()->get(
			wfAppendQuery(
				$config->get( ConfigNames::ServerURL ),
				[
					'module' => 'API',
					'format' => 'json',
					'method' => 'SitesManager.updateSite',
					'idSite' => $siteId,
					'siteName' => $newDb,
					'token_auth' => $config->get( ConfigNames::TokenAuth )
				]
			),
			[],
			__METHOD__
		);

		if ( $config->get( ConfigNames::UseDB ) ) {
			$dbw = MediaWikiServices::getInstance()->getConnectionProvider()
				->getPrimaryDatabase( 'virtual-matomoanalytics' );

			$dbw->update(
				'matomo',
				[ 'matomo_wiki' => $newDb ],
				[ 'matomo_id' => $siteId ],
				__METHOD__
			);

			$cache = MediaWikiServices::getInstance()->getObjectCacheFactory()->getLocalClusterInstance();
			$key = $cache->makeKey( 'matomo', 'id' );
			$cache->delete( $key );
		}

		if ( (string)$siteId === (string)self::getSiteID( $newDb ) ) {
			$logger->debug( "Successfully renamed {$oldDb} to {$newDb} with id {$siteId}." );

			return true;
		} else {
			$logger->error( "Failed to rename {$oldDb} to {$newDb} with id {$siteId}." );

			throw new RuntimeException( 'Error in renaming Matomo references' );
		}
	}

	public static function getSiteID( string $dbname, bool $disableCache = false ) {
		$config = self::getConfig();
		$logger = self::getLogger();

		if ( $config->get( ConfigNames::UseDB ) ) {
			$cache = MediaWikiServices::getInstance()->getObjectCacheFactory()->getLocalClusterInstance();
			$key = $cache->makeKey( 'matomo', 'id' );
			$cacheId = $cache->get( $key );
			if ( $cacheId && !$disableCache ) {
				return $cacheId;
			}

			$dbr = MediaWikiServices::getInstance()->getConnectionProvider()
				->getReplicaDatabase( 'virtual-matomoanalytics' );

			$id = $dbr->selectField(
				'matomo',
				'matomo_id',
				[ 'matomo_wiki' => $dbname ],
				__METHOD__
			);

			if ( !isset( $id ) || !$id ) {
				$logger->warning( "Could not find {$dbname} in matomo table." );

				// Because the site is not found in the matomo table,
				// we default to a value set in 'MatomoAnalyticsSiteID' which is 1.
				return $config->get( ConfigNames::SiteID );
			} else {
				$cache->set( $key, $id );

				return $id;
			}
		} else {
			return $config->get( ConfigNames::SiteID );
		}
	}
}
