<?php

namespace Miraheze\MatomoAnalytics;

use MediaWiki\Config\Config;
use MediaWiki\Json\FormatJson;
use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Wikimedia\Rdbms\DBQueryError;

class MatomoAnalytics {

	private static function getConfig(): Config {
		return MediaWikiServices::getInstance()
			->getConfigFactory()
			->makeConfig( 'MatomoAnalytics' );
	}

	private static function getLogger(): LoggerInterface {
		return LoggerFactory::getInstance( 'MatomoAnalytics' );
	}

	public static function addSite( string $dbname ): void {
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
					'token_auth' => $config->get( ConfigNames::TokenAuth ),
				]
			),
			[],
			__METHOD__
		);

		$siteJson = FormatJson::decode( $siteReply, true );
		if ( !$siteJson ) {
			$logger->error( "Could not create id for $dbname." );
			return;
		}

		$siteId = $siteJson['value'];
		if ( $config->get( ConfigNames::UseDB ) ) {
			$dbw = MediaWikiServices::getInstance()->getConnectionProvider()
				->getPrimaryDatabase( 'virtual-matomoanalytics' );

			try {
				$dbw->newInsertQueryBuilder()
					->insertInto( 'matomo' )
					->row( [
						'matomo_id' => $siteId,
						'matomo_wiki' => $dbname,
					] )
					->caller( __METHOD__ )
					->execute();
			} catch ( DBQueryError ) {
				return;
			}
		}

		$logger->debug( "Successfully created $dbname with id $siteId." );
	}

	public static function deleteSite( string $dbname ): void {
		$config = self::getConfig();
		$logger = self::getLogger();

		$siteId = self::getSiteID( $dbname, disableCache: true );
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
					'token_auth' => $config->get( ConfigNames::TokenAuth ),
				]
			),
			[],
			__METHOD__
		);

		if ( $config->get( ConfigNames::UseDB ) ) {
			$dbw = MediaWikiServices::getInstance()->getConnectionProvider()
				->getPrimaryDatabase( 'virtual-matomoanalytics' );

			$dbw->newDeleteQueryBuilder()
				->deleteFrom( 'matomo' )
				->where( [ 'matomo_id' => $siteId ] )
				->caller( __METHOD__ )
				->execute();

			$cache = MediaWikiServices::getInstance()->getObjectCacheFactory()->getLocalClusterInstance();
			$key = $cache->makeKey( 'matomo', 'id' );
			$cache->delete( $key );
		}

		$logger->debug( "Successfully deleted $dbname with id $siteId." );
	}

	public static function renameSite(
		string $oldDbName,
		string $newDbName
	): void {
		$config = self::getConfig();
		$logger = self::getLogger();

		$siteId = self::getSiteID( $oldDbName, disableCache: true );
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
					'siteName' => $newDbName,
					'token_auth' => $config->get( ConfigNames::TokenAuth ),
				]
			),
			[],
			__METHOD__
		);

		if ( $config->get( ConfigNames::UseDB ) ) {
			$dbw = MediaWikiServices::getInstance()->getConnectionProvider()
				->getPrimaryDatabase( 'virtual-matomoanalytics' );

			$dbw->newUpdateQueryBuilder()
				->update( 'matomo' )
				->set( [ 'matomo_id' => $siteId ] )
				->where( [ 'matomo_wiki' => $newDbName ] )
				->caller( __METHOD__ )
				->execute();

			$cache = MediaWikiServices::getInstance()->getObjectCacheFactory()->getLocalClusterInstance();
			$key = $cache->makeKey( 'matomo', 'id' );
			$cache->delete( $key );
		}

		if ( (string)$siteId === (string)self::getSiteID( $newDbName, disableCache: false ) ) {
			$logger->debug( "Successfully renamed $oldDbName to $newDbName with id $siteId." );
			return;
		}

		$logger->error( "Failed to rename $oldDbName to $newDbName with id $siteId." );
		throw new RuntimeException( 'Error in renaming Matomo references' );
	}

	public static function getSiteID( string $dbname, bool $disableCache ): int {
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

			$id = $dbr->newSelectQueryBuilder()
				->select( 'matomo_id' )
				->from( 'matomo' )
				->where( [ 'matomo_wiki' => $dbname ] )
				->caller( __METHOD__ )
				->fetchField();

			if ( $id ) {
				$cache->set( $key, $id );
				return $id;
			}

			$logger->warning( "Could not find $dbname in matomo table." );

			// Because the site is not found in the matomo table,
			// we default to a value set in 'MatomoAnalyticsSiteID' which is 1.
			return $config->get( ConfigNames::SiteID );

		}

		return $config->get( ConfigNames::SiteID );
	}
}
