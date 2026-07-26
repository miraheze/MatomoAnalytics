<?php

namespace Miraheze\MatomoAnalytics\Rest;

use MediaWiki\Rest\LocalizedHttpException;
use MediaWiki\Rest\Response;
use MediaWiki\Rest\SimpleHandler;
use MediaWiki\WikiMap\WikiMap;
use Miraheze\MatomoAnalytics\MatomoAnalytics;
use Miraheze\MatomoAnalytics\MatomoAnalyticsWiki;
use Wikimedia\Message\MessageValue;
use Wikimedia\ParamValidator\ParamValidator;

class TopPagesHandler extends SimpleHandler {

	private const MIN_PERIOD = 1;
	private const MAX_PERIOD = 31;
	private const DEFAULT_PERIOD = 7;

	private const DEFAULT_LIMIT = 10;
	private const MAX_LIMIT = 50;

	public function run(): Response {
		if ( !$this->getAuthority()->isAllowed( 'viewanalytics' ) ) {
			throw new LocalizedHttpException(
				new MessageValue( 'matomoanalytics-rest-permission-denied' ), 403
			);
		}

		$params = $this->getValidatedParams();

		$period = $params['period'];
		if ( $period < self::MIN_PERIOD || $period > self::MAX_PERIOD ) {
			$period = self::DEFAULT_PERIOD;
		}

		$limit = min( $params['limit'], self::MAX_LIMIT );

		$siteId = MatomoAnalytics::getSiteID( WikiMap::getCurrentWikiId(), disableCache: false );
		$matomoWiki = new MatomoAnalyticsWiki( period: $period, siteId: $siteId );

		$response = [];
		foreach ( $matomoWiki->getTopPages() as $page ) {
			$response[] = [
				'title' => $page['title'],
				'url' => $page['url'],
				'views' => is_numeric( $page['visits'] ) ? (int)$page['visits'] : 0,
			];
		}

		usort( $response, static fn ( array $a, array $b ): int => $b['views'] <=> $a['views'] );
		$response = array_slice( $response, 0, $limit );

		return $this->getResponseFactory()->createJson( $response );
	}

	public function needsWriteAccess(): bool {
		return false;
	}

	public function getParamSettings(): array {
		return [
			'period' => [
				self::PARAM_SOURCE => 'query',
				ParamValidator::PARAM_TYPE => 'integer',
				ParamValidator::PARAM_REQUIRED => false,
				ParamValidator::PARAM_DEFAULT => self::DEFAULT_PERIOD,
			],
			'limit' => [
				self::PARAM_SOURCE => 'query',
				ParamValidator::PARAM_TYPE => 'integer',
				ParamValidator::PARAM_REQUIRED => false,
				ParamValidator::PARAM_DEFAULT => self::DEFAULT_LIMIT,
			],
		];
	}
}
