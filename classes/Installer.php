<?php

/**
 * Class: Installer
 * @author Brad
 * @purpose Handles the setup and updating of database tables and base system settings
 */

class Installer {

	function __construct() {
		global $wpdb;
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

		//echo "here";

		// Plugin DB Version
		$db_version = get_option( "CLUBHOUSE_DB_VERSION" );

		// Install / Update
		$installed = $this->confirmExists();
		if ($installed) {
			$cur_version = $this->checkVersion();
			if ($cur_version !== $db_version) {
				$this->updateDB();
			}
		}
		// ToDo: make update method
		// else {
		///	$this->setupClubhouse();
		//}


		// Initialize in Wordpress
		add_action('init',array(&$this,'init')); // called on page load

	}

	/**
	 * On Activate
	 * @purpose run items on plugin activation.
	 *          use self::otherMethod() instead of $this->otherMethod();
	 */
	function activate() {
	}

	/**
	 * Initialize Class
	 * @purpose run items on/before page load/display
	 */
	function init() {
	}

	// Check if Installed
	function confirmExists() {
		global $wpdb;
		$check_table = $wpdb->get_results("SHOW TABLES LIKE '" . CLUBHOUSE_TABLE_SETTINGS . "'", 'ARRAY_A');
		$check_table = $wpdb->num_rows;
		$return = $check_table > 0 ? true : false;
		return $return;
	}

	// Check installed version
	function checkVersion() {
		global $wpdb;
		$installed_db_version = null;
		if ($check_table > 0) {
			$installed_db_version = $wpdb->get_row("SELECT `db_ver` FROM `" . CLUBHOUSE_TABLE_SETTINGS . "` WHERE `id` = 1;");
		}
		return $installed_db_version;
	}

	// Install Tables
	function setupClubhouse() {
		global $wpdb;

		// Settings Table
		$sql = "
			CREATE TABLE IF NOT EXISTS " . CLUBHOUSE_TABLE_SETTINGS . " (
				`id` mediumint(9) NOT NULL AUTO_INCREMENT,
				`time` datetime  NOT NULL default '0000-00-00 00:00:00',
				`db_ver` float(3,1) NOT NULL,
				`app_ver` varchar(9) NOT NULL,
				PRIMARY KEY `id` (`id`)
			) ENGINE=MyISAM DEFAULT CHARSET=latin1;
		";
		dbDelta($sql);

		// check for the settings row and insert if non-existent
		$check = $wpdb->get_row("SELECT * FROM `" . CLUBHOUSE_TABLE_SETTINGS . "` WHERE `id` = 1");
		if (empty($check)) {
			$rows_affected = $wpdb->insert( CLUBHOUSE_TABLE_SETTINGS,
					array(
							'id' => 1,
							'time' => current_time('mysql'),
							'num_tags' => 25,
							'db_ver' => CLUBHOUSE_DB_VERSION,
							'app_ver' => CLUBHOUSE_VERSION
					)
			);
		}

		// Players Table
		$sql = "
			CREATE TABLE " . CLUBHOUSE_TABLE_PLAYERS . " (
				`id` mediumint(9) NOT NULL AUTO_INCREMENT,
				`first_name` varchar(100) NOT NULL,
				`last_name` varchar(100) NOT NULL,
				`email` varchar(255) NOT NULL,
				`pdga_number` int(10) NOT NULL,
				`updated` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
				PRIMARY KEY (`id`)
			)ENGINE=MyISAM  DEFAULT CHARSET=latin1;
		";
		dbDelta($sql);

		// Divisions Table
		$sql = "
			CREATE TABLE " . CLUBHOUSE_TABLE_DIVISIONS . " (
				`id` mediumint(9) NOT NULL AUTO_INCREMENT,
				`name` varchar(100) NOT NULL,
				`order` int(11) NOT NULL,
				PRIMARY KEY (`id`)
			) ENGINE=MyISAM  DEFAULT CHARSET=latin1;
		";
		dbDelta($sql);

		// Events Table
		$sql = "
			CREATE TABLE " . CLUBHOUSE_TABLE_EVENTS . " (
				`id` mediumint(9) NOT NULL AUTO_INCREMENT,
				`name` varchar(100) NOT NULL,
				`duration` int(10) NOT NULL,
				`itterator` enum('round','weekly') NOT NULL,
				PRIMARY KEY (`id`)
			) ENGINE=MyISAM  DEFAULT CHARSET=latin1;
		";
		dbDelta($sql);

		// Courses Table
		$sql = "
			CREATE TABLE " . CLUBHOUSE_TABLE_COURSES . " (
				`id` mediumint(9) NOT NULL AUTO_INCREMENT,
				`course` varchar(100) NOT NULL,
				`holes` int(2) NOT NULL,
				`tees` int(2) NOT NULL,
				PRIMARY KEY (`id`)
			) ENGINE=InnoDB DEFAULT CHARSET=latin1;
		";
		dbDelta($sql);

		// Scores Table
		$sql = "
			CREATE TABLE " . CLUBHOUSE_TABLE_SCORES . " (
				`year` int(4) NOT NULL,
				`event_id` enum('spring/summer','fall/winter') NULL,
				`itteration` int(2) NOT NULL,
				`course_id` varchar(100) NOT NULL,
				`player_id` int(10) NOT NULL,
				`hole` int(2) NOT NULL,
				`tee` int(2) NOT NULL,
				`score` int(2) NOT NULL,
				KEY `player_id` (`player_id`),
				KEY `hole` (`hole`),
				KEY `itteration` (`itteration`)
			) ENGINE=InnoDB DEFAULT CHARSET=latin1;
		";
		dbDelta($sql);

		// Regsys Table
		$sql = "
			CREATE TABLE wp_clubhouse_regsys (
				`id` mediumint(9) NOT NULL AUTO_INCREMENT,
				`player_id` mediumint(9) NOT NULL,
				`event_id` varchar(100) NOT NULL,
				`division_id` varchar(100) NOT NULL,
				`confirmed` enum('t','f') DEFAULT 'f' NOT NULL,
				`registered` timestamp NOT NULL default '0000-00-00 00:00:00',
				PRIMARY KEY (`id`)
			) ENGINE=MyISAM  DEFAULT CHARSET=latin1;
		";
		dbDelta($sql);

	}

	function updateDB() {

	}

}

?>