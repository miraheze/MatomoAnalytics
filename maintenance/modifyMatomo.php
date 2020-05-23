<?php

/**
* This program is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or
* (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License along
* with this program; if not, write to the Free Software Foundation, Inc.,
* 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
* http://www.gnu.org/copyleft/gpl.html
*
* @file
* @ingroup Maintenance
* @author Miraheze Site Reliability Engineering Team
*/

$IP = getenv( 'MW_INSTALL_PATH' );
if ( $IP === false ) {
	$IP = __DIR__ . '/../../..';
}
require_once "$IP/maintenance/Maintenance.php";

class ModifyMatomo extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->mDescription = "Adds or remove a wiki from matomo.";
		$this->addOption( 'remove', 'Removes wiki from matomo', false, false );
	}

	public function execute() {
		if ( $this->getOption( 'remove' ) ) {
			MatomoAnalyticsHooks::wikiDeletion( $wgDBname );
		} else {
			MatomoAnalyticsHooks::wikiCreation( $wgDBname );
		}
	}
}

$maintClass = 'ModifyMatomo';
require_once RUN_MAINTENANCE_IF_MAIN;
