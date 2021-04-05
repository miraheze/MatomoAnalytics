<?php

$IP = getenv( 'MW_INSTALL_PATH' );
if ( $IP === false ) {
  $IP = __DIR__ . '/../../..';
}
require_once "$IP/maintenance/Maintenance.php";

use MediaWiki\MediaWikiServices;

class addMissingMatomos extends Maintenance {
  public function __construct() {
    parent::__construct();
    $this->addOption( 'default', 'Add missing matomo ids' );
  }

  public function execute() {
    $config = MediaWikiServices::getInstance()
      ->getConfigFactory()
      ->makeConfig( 'matomoanalytics' );

    $dbw = wfGetDB( DB_MASTER, [], $config->get( 'CreateWikiDatabase' ) );

    $res = $dbw->select(
      'cw_wikis',
      '*',
      [],
      __METHOD__
    );

    if ( !$res || !is_object( $res ) ) {
      throw new MWException( '$res was not set to a valid array.' );
    }

    foreach ( $res as $row ) {
      $DBname = $row->wiki_dbname;

      if ( $DBname === 'default' ) {
            continue;
      }

      $id = $dbw->selectField(
        'matomo',
        'matomo_id',
        [ 'matomo_wiki' => $DBname ],
        __METHOD__
      );

      if ( !isset( $id ) || !$id ) {
        $this->output( "Add matomo id to {$DBname}\n");

        MatomoAnalytics::addSite( $DBname );
      } else {
        continue;
      }
    }
  }
}

$maintClass = 'addMissingMatomos';
require_once RUN_MAINTENANCE_IF_MAIN;
