<?php

namespace Miraheze\MatomoAnalytics\Maintenance;

use Maintenance;
use UnexpectedValueException;

$IP = getenv( 'MW_INSTALL_PATH' );
if ( $IP === false ) {
	$IP = __DIR__ . '/../../..';
}

require_once "$IP/maintenance/Maintenance.php";

class AddMissingMatomos extends Maintenance {

	public function __construct() {
		parent::__construct();

		$this->addDescription( 'Add missing matomo ids.' );

		$this->requireExtension( 'CreateWiki' );
		$this->requireExtension( 'MatomoAnalytics' );
	}

	public function execute() {
		$dbw = $this->getDB( DB_PRIMARY, [], $this->getConfig()->get( 'CreateWikiDatabase' ) );

		$res = $dbw->select(
			'cw_wikis',
			'*',
			[],
			__METHOD__
		);

		if ( !$res || !is_object( $res ) ) {
			throw new UnexpectedValueException( '$res was not set to a valid array.' );
		}

		foreach ( $res as $row ) {
			$DBname = $row->wiki_dbname;

			$id = $dbw->selectField(
				'matomo',
				'matomo_id',
				[ 'matomo_wiki' => $DBname ],
				__METHOD__
			);

			if ( !isset( $id ) || !$id ) {
				$this->output( "Adding matomo id to {$DBname}\n" );
				\Miraheze\MatomoAnalytics\MatomoAnalytics::addSite( $DBname );
				$this->output( "Done!\n" );
			}
		}
	}
}

$maintClass = AddMissingMatomos::class;
require_once RUN_MAINTENANCE_IF_MAIN;
