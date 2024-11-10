<?php

namespace Miraheze\MatomoAnalytics;

use Exception;
use FormatJson;
use MediaWiki\MediaWikiServices;
use ObjectCache;
use RuntimeException;

class MatomoAnalytics {
	private static function getConfig() {
		return MediaWikiServices::getInstance()
			->getConfigFactory()
			->makeConfig( 'matomoanalytics' );
	}

	private static function getLogger() {
		return \MediaWiki\Logger\LoggerFactory::getInstance( 'MatomoAnalytics' );
	}

	public static function addSite( $dbname ) {
		$config = static::getConfig();

		$logger = static::getLogger();

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
			$dbw = MediaWikiServices::getInstance()->getDBLoadBalancerFactory()
				->getMainLB( $config->get( ConfigNames::Database ) )
				->getMaintenanceConnectionRef( DB_PRIMARY, [], $config->get( ConfigNames::Database ) );

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
		$config = static::getConfig();

		$siteId = static::getSiteID( $dbname, true );

		$logger = static::getLogger();

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
			$dbw = MediaWikiServices::getInstance()->getDBLoadBalancerFactory()
				->getMainLB( $config->get( ConfigNames::Database ) )
				->getMaintenanceConnectionRef( DB_PRIMARY, [], $config->get( ConfigNames::Database ) );

			$dbw->delete(
				'matomo',
				[ 'matomo_id' => $siteId ],
				__METHOD__
			);

			$cache = ObjectCache::getLocalClusterInstance();
			$key = $cache->makeKey( 'matomo', 'id' );
			$cache->delete( $key );
		}

		$logger->debug( "Successfully deleted {$dbname} with id {$siteId}." );

		return true;
	}

	public static function renameSite( $oldDb, $newDb ) {
		$config = static::getConfig();

		$siteId = static::getSiteID( $oldDb, true );

		$logger = static::getLogger();

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
			$dbw = MediaWikiServices::getInstance()->getDBLoadBalancerFactory()
				->getMainLB( $config->get( ConfigNames::Database ) )
				->getMaintenanceConnectionRef( DB_PRIMARY, [], $config->get( ConfigNames::Database ) );

			$dbw->update(
				'matomo',
				[ 'matomo_wiki' => $newDb ],
				[ 'matomo_id' => $siteId ],
				__METHOD__
			);

			$cache = ObjectCache::getLocalClusterInstance();
			$key = $cache->makeKey( 'matomo', 'id' );
			$cache->delete( $key );
		}

		if ( (string)$siteId === (string)static::getSiteID( $newDb ) ) {
			$logger->debug( "Successfully renamed {$oldDb} to {$newDb} with id {$siteId}." );

			return true;
		} else {
			$logger->error( "Failed to rename {$oldDb} to {$newDb} with id {$siteId}." );

			throw new RuntimeException( 'Error in renaming Matomo references' );
		}
	}

	public static function getSiteID( string $dbname, bool $disableCache = false ) {
		$config = static::getConfig();

		$logger = static::getLogger();

		if ( $config->get( ConfigNames::UseDB ) ) {
			$cache = ObjectCache::getLocalClusterInstance();
			$key = $cache->makeKey( 'matomo', 'id' );
			$cacheId = $cache->get( $key );
			if ( $cacheId && !$disableCache ) {
				return $cacheId;
			}

			$dbr = MediaWikiServices::getInstance()->getDBLoadBalancerFactory()
				->getMainLB( $config->get( ConfigNames::Database ) )
				->getMaintenanceConnectionRef( DB_REPLICA, [], $config->get( ConfigNames::Database ) );

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
