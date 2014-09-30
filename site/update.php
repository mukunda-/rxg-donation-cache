<?php

// update single
// update all
// setup database

require_once 'config.php';
require_once 'sql.php';

/** ---------------------------------------------------------------------------
 * Create database tables.
 *
 * Deletes existing table.
 */
function SetupDatabase() {
	$sql = GetSQL();
	$sql->RunQuery( "DROP TABLE IF EXISTS SteamDonationCache" );
	$sql->RunQuery( "DROP TABLE IF EXISTS UserDonationCache" );
	
	$sql->RunQuery( 
		"CREATE TABLE SteamDonationCache (
		steamid INT NOT NULL PRIMARY KEY COMMENT 'Steam ID index.',
		expires INT NOT NULL COMMENT 'Unixtime of $5/mo expiry.',
		expires2 INT NOT NULL COMMENT 'Unixtime of $1/mo expiry.'
	) ENGINE = InnoDB COMMENT = 'Donation cache for Steam IDs'" );
	
	$sql->RunQuery( 
		"CREATE TABLE UserDonationCache (
		userid INT NOT NULL PRIMARY KEY COMMENT 'Forum account ID.',
		expires INT NOT NULL COMMENT 'Unixtime of $5/mo expiry.',
		expires2 INT NOT NULL COMMENT 'Unixtime of $1/mo expiry.'
	) ENGINE = InnoDB COMMENT = 'Donation cache for forum accounts.'" );
	
}

/** ---------------------------------------------------------------------------
 * Convert a userid into a steam id via the steamuser table.
 *
 * @param int $userid Forum account id.
 * @return int 64-bit SteamID for user, or 0 if none attached.
 */
function GetSteamFromUser( $userid ) {
	$result = $db->RunQuery( 
		"SELECT steamid 
		FROM steamuser 
		WHERE userid=$userid" );
	
	$row = $result->fetch_row();
	if( $row === FALSE ) return;
	return $row[0];
}

/** ---------------------------------------------------------------------------
 * Update a SteamID entry in the donation cache.
 *
 * @param int $steamid SteamID, any format.
 */
function UpdateSteamID( $steamid ) {
	$steamid = SteamID::Parse( $steamid, SteamID::S32 );
	if( $steamid === FALSE ) return;
	
	$db = GetSQL();
	
	if( $userid != 0 && $steamid == 0 ) {
		$steamid = GetSteamFromUser( $userid );
	}
	
	if( $steamid == 0 ) {
		$sql->RunQuery( 
	}
}

/** ---------------------------------------------------------------------------
 * Update a userid entry in the donation cache.
 *
 * @param int $userid Forum account ID.
 */
function UpdateUser( $userid ) {
	
}

if( !isset($_POST['data']) ) exit();


$request = $_POST['data'];

?>