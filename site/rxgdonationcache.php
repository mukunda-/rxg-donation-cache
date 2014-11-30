<?php

require_once 'config.php';
require_once 'sql.php';
require_once 'lib/steamid.php';

class RXGDonationCache {

	/** ---------------------------------------------------------------------------
	 * Create database tables.
	 *
	 * @param bool $drop Erase existing tables.
	 */
	public static function SetupDatabase( $drop ) {
	
		$db = GetSQL();
		if( $drop ) {
			$db->RunQuery( "DROP TABLE IF EXISTS SteamDonationCache" );
			$db->RunQuery( "DROP TABLE IF EXISTS UserDonationCache" );
		}
		
		$db->RunQuery( 
			"CREATE TABLE IF NOT EXISTS SteamDonationCache (
			steamid BIGINT NOT NULL PRIMARY KEY COMMENT 'Steam ID index.',
			expires1 INT NOT NULL COMMENT 'Unixtime of $1/mo expiry.',
			expires5 INT NOT NULL COMMENT 'Unixtime of $5/mo expiry.'
		) ENGINE = InnoDB COMMENT = 'Donation cache for Steam IDs'" );
		
		$db->RunQuery( 
			"CREATE TABLE IF NOT EXISTS UserDonationCache (
			userid INT NOT NULL PRIMARY KEY COMMENT 'Forum account ID.',
			expires1 INT NOT NULL COMMENT 'Unixtime of $1/mo expiry.',
			expires5 INT NOT NULL COMMENT 'Unixtime of $5/mo expiry.'
		) ENGINE = InnoDB COMMENT = 'Donation cache for forum accounts.'" );
		
		$db->RunQuery( "DROP FUNCTION IF EXISTS DCache_GetSteam" ); 
		$db->RunQuery( "DROP FUNCTION IF EXISTS DCache_GetForum" ); 
		$db->RunQuery( file_get_contents( "sql/DCache_GetSteam.sql" ) );
		$db->RunQuery( file_get_contents( "sql/DCache_GetForum.sql" ) );
		
	}

	/** ---------------------------------------------------------------------------
	 * Erase all data.
	 *
	 */
	public static function ResetDatabase() {
		$db = GetSQL();
		$db->RunQuery( "TRUNCATE SteamDonationCache" );
		$db->RunQuery( "TRUNCATE UserDonationCache" );
	}

	/** ---------------------------------------------------------------------------
	 * Convert a userid into a steam id via the steamuser table.
	 *
	 * @param int $userid Forum account id.
	 * @return SteamID|false SteamID or FALSE if none found.
	 */
	public static function GetSteamFromUser( $userid ) {
		$db = GetSQL();
		$result = $db->RunQuery( 
			"SELECT steamid 
			FROM steamuser 
			WHERE userid=$userid" );
		
		$row = $result->fetch_row();
		if( $row === NULL ) return FALSE;
		return SteamID::Parse( $row[0] );
	}

	/** ---------------------------------------------------------------------------
	 * Convert a steamid into a userid via the steamuser table.
	 *
	 * @param SteamID $steamid Steam ID to query.
	 * @return int|false userid or FALSE if none found.
	 */
	public static function GetUserFromSteam( $steamid ) {
		$db = GetSQL();
		$stem64 = $steamid->Format( SteamID::FORMAT_STEAMID64 );
		$result = $db->RunQuery( 
			"SELECT userid 
			FROM steamuser 
			WHERE steamid=$stem64" );
		
		$row = $result->fetch_row();
		if( $row === NULL ) return FALSE;
		return is_null($row[0]) ? 0 : $row[0];
	}

	/** ---------------------------------------------------------------------------
	 * Find the expiration time for a user's donations.
	 *
	 * @param int $userid User ID to query, or FALSE for a steamid only search.
	 * @param SteamID $steamid Steam ID to search for, or FALSE for a userid only
	 *                         search.
	 * @param float $rate How many seconds $1.00 is worth.
	 * @return int Expiration time as a unix timestamp.
	 */
	private static function GetExpiryTime( $userid, $steamid, $rate ) {
		$rate = (float)$rate;
		
		if( $steamid !== FALSE ) {
			$steamid = $steamid->Format( SteamID::FORMAT_STEAMID32 );
			$steamid[6] = '_';
			// STEAM__:x:zzzzzz
			// _ is a single-character wildcard for LIKE.
		}
		
		if( $userid === FALSE || $userid == 0 ) {
			if( $steamid === FALSE ) {
				throw new BadFunctionCallException( 
					'$userid or $steamid must be set.' );
			}
			
			$condition = " option_name2 LIKE '$steamid' ";
		} else {
			$userid = (int)$userid;
			if( $steamid === FALSE ) {
				$condition = " user_id=$userid ";
			} else {
				$condition = " (user_id=$userid OR option_name2 LIKE '$steamid') ";
			}
		}
		
		$db = GetSQL();
		$db->RunQuery( 'SET @a := 0' );
		
		// @a is the running expiry time, if the donation time is past the expiry time, then @a gets
		// reset to that donation's expiry, otherwise, the donation duration time is added to @a.
		$result = $db->RunQuery( 
			"SELECT MAX(IF(time >= @a, @a := time + amt_time, @a := @a + amt_time )) AS donation_expiry_date
				FROM (
					SELECT
						payment_date AS time, (mc_gross*exchange_rate)*$rate AS amt_time
						FROM dopro_donations
						WHERE $condition

						AND (payment_status = 'Completed' OR payment_status = 'Refunded')
						ORDER BY payment_date ASC
					) AS q1" );
		
		$row = $result->fetch_row();
		if( $row === NULL ) return 0;
		
		//echo $condition . '<br>';
		//print_r( $row );
		//echo '<br>';
		return $row[0];
	}

	/** ---------------------------------------------------------------------------
	 * Update a SteamID entry in the donation cache.
	 *
	 * @param SteamID $steamid SteamID to update.
	 */
	public static function UpdateStem( $steamid ) { 
		$userid = self::GetUserFromSteam( $steamid );
		
		$expires1 = self::GetExpiryTime( $userid, $steamid, 2678400.0 );
		$expires5 = self::GetExpiryTime( $userid, $steamid, 535680.0 );
		
		$db = GetSQL();
		
		$s32 = $steamid->Format( SteamID::FORMAT_S32 );
		
		$db->RunQuery( 
			"INSERT INTO SteamDonationCache (steamid,expires1,expires5)
			VALUES ($s32,$expires1,$expires5)
			ON DUPLICATE KEY UPDATE
			expires1=$expires1, expires5=$expires5" );
	}

	/** ---------------------------------------------------------------------------
	 * Update a userid entry in the donation cache.
	 *
	 * @param int $userid Forum account ID.
	 */
	public static function UpdateUser( $userid ) {
	 
		$steamid = self::GetSteamFromUser( $userid ); 
		$db = GetSQL();
		
		$expires1 = self::GetExpiryTime( $userid, $steamid, 2678400.0 );
		$expires5 = self::GetExpiryTime( $userid, $steamid, 535680.0 );
		
		$db = GetSQL();
		 
		$db->RunQuery( 
			"INSERT INTO UserDonationCache (userid,expires1,expires5)
			VALUES ($userid,$expires1,$expires5)
			ON DUPLICATE KEY UPDATE
			expires1=$expires1, expires5=$expires5" );
		
	}

	/** ---------------------------------------------------------------------------
	 * Rebuild ALL data.
	 */
	public static function UpdateAll() { 
		$db = GetSQL();
		$db->MQueryUpdate( file_get_contents( "sql/DCache_Update.sql" ), true );
	}
}

?>