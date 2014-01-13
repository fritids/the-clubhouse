<?php
/**
 * @package The Clubhouse
 */
/*
Plugin Name: The Clubhouse
Plugin URI:
Description: This is a utility specifically designed for Disc Golf clubs. It provides a means to track players across events, and view statistics.
Version: 0.0.1
Author: Brad Groat
Author URI: http://bradgroat.com
License: GPLv2 or later
*/

/*  Copyright 2013  Brad Groat  (email : contact@bradgroat.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
	echo "Hi there!  I'm just a plugin, not much I can do when called directly.";
	exit;
}

// Assets
global $wpdb;

// Plugin Constants
define('MIN_WP_VERSION', 			'3.5.1');
define('CLUBHOUSE_VERSION', 		'0.0.1');
define('CLUBHOUSE_DB_VERSION', 		'0.1');
define('CLUBHOUSE_PLUGIN_URL', 		plugin_dir_url( __FILE__ ));
define('CLUBHOUSE_PLUGIN_DIR',		plugin_dir_path(__FILE__));
define('CLUBHOUSE_TABLE_SETTINGS', 	$wpdb->prefix . 'clubhouse_settings');
define('CLUBHOUSE_TABLE_PLAYERS', 	$wpdb->prefix . 'clubhouse_players');
define('CLUBHOUSE_TABLE_DIVISIONS',	$wpdb->prefix . 'clubhouse_divisions');
define('CLUBHOUSE_TABLE_EVENTS', 	$wpdb->prefix . 'clubhouse_events');
define('CLUBHOUSE_TABLE_COURSES', 	$wpdb->prefix . 'clubhouse_courses');
define('CLUBHOUSE_TABLE_SCORES', 	$wpdb->prefix . 'clubhouse_scores');
define('CLUBHOUSE_TABLE_REGSYS', 	$wpdb->prefix . 'clubhouse_regsys');
define('CLUBHOUSE_TABLE_DIRECTORS', $wpdb->prefix . 'clubhouse_directors');

// Load System Message Class
global $CH_SysMessages;
include_once(CLUBHOUSE_PLUGIN_DIR . 'classes/SysMessages.php');
$CH_SysMessages = new SysMessages;

// Load Control Classes
$fh = @opendir(CLUBHOUSE_PLUGIN_DIR . 'classes/');
while (false !== ($entry = readdir($fh))) {
	if (preg_match('/.php$/', $entry) && !preg_match('/^Installer/', $entry) && !preg_match('/^SysMessages/', $entry)) {

		// Include Class
		include_once(CLUBHOUSE_PLUGIN_DIR . 'classes/' . $entry);

		// Create Base Name
		$clubhouse_class_basename = str_replace('.php','',$entry);
		$clubhouse_class_handle = 'CH_' . $clubhouse_class_basename;

		// Create Instance
		global $$clubhouse_class_handle;
		$$clubhouse_class_handle = new $clubhouse_class_basename;

		// Activate
		// ToDo: confirm if this is even required, currently throwing an error on activation of the plugin
		//register_activation_hook(__FILE__,array($clubhouse_class_basename,'activate'));

	}
}
closedir($fh);

// Setup Plugin
register_activation_hook(__FILE__, 'clubhouse_install');

// Setup Clubhouse
function clubhouse_install () {
	global $wpdb;

	// register db version
	add_option("CLUBHOUSE_DB_VERSION", CLUBHOUSE_DB_VERSION);

	// register plugin location
	add_option("CLUBHOUSE_PLUGIN_DIR", CLUBHOUSE_PLUGIN_DIR);

	// Run Installer
	include(CLUBHOUSE_PLUGIN_DIR . 'classes/Installer.php');
	$Installer = new Installer();
	$Installer->setupClubhouse();

	// Activate
	register_activation_hook(__FILE__,array('Installer','activate'));

}

// Initialize
function clubhouse_init() {
	// add styles and scripts
	wp_register_style('the-clubhouse.css', CLUBHOUSE_PLUGIN_URL . 'the-clubhouse.css');
	wp_enqueue_style('the-clubhouse.css');
	wp_register_script('the-clubhouse.js.php', CLUBHOUSE_PLUGIN_URL . 'the-clubhouse.js.php', array('jquery'));
	wp_enqueue_script('the-clubhouse.js.php');
}
add_action('init', 'clubhouse_init');

// Setup Nonce
function clubhouse_nonce_field($action = -1) { return wp_nonce_field($action); }
global $clubhouse_nonce;
$clubhouse_nonce = 'clubhouse-update-key';

// Display Form
//function clubhouse_output ($content) {
//
//	// This is front-end output
//
//	return $content;
//}
//add_filter( 'the_content', 'clubhouse_output');



/*

// Display Form
function regsys_output ($content) {

	// Locate All Forms
	$regsys_forms_regex = '/(__regsys_form([\[\]a-zA-Z0-9\s]+)?__)/i';
	preg_match_all($regsys_forms_regex, $content, $regsys_form_matches);



	// ************************************************************* *
	// TO DO: Create loop to allow multiple forms on a page
	// ************************************************************* *


	// Set Current Form
	$regsys_form_call = $regsys_form_matches[1][0];

	// Confirm Form Call Made
	if (!empty($regsys_form_call)) {
		global $wpdb;

		// Set Collectors / Params
		$regsys_output = '';
		$regsys_errors = array();
		$show_form = true;
		$regsys_player_cap = 90;


		// ************************************************************* *
	 	// TO DO: Pull form settings from database once a manager
	 	//        has been created.
	 	// ************************************************************* *


		// Locate Form
		$regsys_form_regex = '/\[([a-zA-Z0-9\s]+)\]/i';
		preg_match($regsys_form_regex, $regsys_form_call, $regsys_form_match);
		$regsys_form = $regsys_form_match[1];

		// Prevent Non-specific Forms
		if (!empty($regsys_form)) {

			// Set Form Vars
			$fields = array('first_name', 'last_name', 'email', 'division', 'pdga_number');
			foreach ($fields as $field) {
				$regsys_fieldname = 'regsys_'.$field;
				$$regsys_fieldname = (isset($_POST[$field])) ? $_POST[$field] : '';
			}

			// Handle Form Submit
			if (!empty($_POST['submit'])) {

				// validate referrer
				check_admin_referer( $regsys_nonce );

				// Required
				if (empty($regsys_first_name)) 	$regsys_errors[] = 'First Name is required';
				if (empty($regsys_last_name)) 	$regsys_errors[] = 'Last Name is required';
				if (empty($regsys_email)) 		$regsys_errors[] = 'Email is required';
				if (!empty($regsys_email) && !is_email($regsys_email))
												$regsys_errors[] = 'Email is malformed';
				if (empty($regsys_division)) 	$regsys_errors[] = 'Division is required';

				// No Errors, Process
				if (empty($regsys_errors)) {

					// Collect User Info
					$regsys_user_info = array(
						'first_name'  => $regsys_first_name,
						'last_name'   => $regsys_last_name,
						'email'       => $regsys_email,
						'event'       => $regsys_form,
						'division'    => $regsys_division,
						'pdga_number' => $regsys_pdga_number,
					);

					// Store User
					$regsys_store_user_result = regsys_store_user($regsys_user_info);
					if ($regsys_store_user_result === true) {

						// Get Division Name
						$regsys_players_division = $wpdb->get_row("SELECT `name` FROM `" . REGSYS_TABLE_DIVISIONS . "` WHERE `id` = " . $regsys_division, 'ARRAY_A');
						$regsys_user_info['division'] = $regsys_players_division['name'];


						// Send Admin Notification
						regsys_notify_admin($regsys_user_info, $regsys_form);

						// Hide Form
						$show_form = false;

					// Throw Error
					} else {
						$regsys_errors[] = $regsys_store_user_result;
					}

				}

			}

			// Output Form
			if ($show_form) {

				// Generate Nonce
				$regsys_nonce_field = regsys_nonce_field($regsys_nonce);

				// Get Player Count (used to show notice that event pre-registration has passed the max player count... but that's not to say they shouldn't pre-register)
				$regsys_player_count_notice = '';
				$regsys_player_count = $wpdb->get_row("SELECT COUNT(`id`) AS `total` FROM `" . REGSYS_TABLE_USERS . "` WHERE `confirmed` = 't'", 'ARRAY_A');
				if ($regsys_player_count['total'] >= $regsys_player_cap) {
					$regsys_player_count_notice = '<strong>Pre-registration has now passed the 90 player mark. Additional registrants will be placed on the waitlist, as indicated.</strong><br><br>';
				}

				// Get Divisions and Build Select Menu
				$regsys_division_options = '';
				$regsys_divisions = $wpdb->get_results("SELECT * FROM `" . REGSYS_TABLE_DIVISIONS . "` ORDER BY `order` ASC;", 'ARRAY_A');
				foreach ($regsys_divisions as $regsys_division) {
					$regsys_division_selected = ($regsys_option == $regsys_division) ? ' selected="selected"' : '' ;
					$regsys_division_options .= '<option value="' . $regsys_division['id'] . '"' . $regsys_division_selected . '>' . $regsys_division['name'] . '</option>';
				}

				// Collect Errors for Output
				$regsys_error_messages = '';
				if (!empty($regsys_errors)) {
					$regsys_error_messages .= '<ul class="regsys_errors">';
					foreach ($regsys_errors as $regsys_error) {
						$regsys_error_messages .= '<li>' . $regsys_error . '</li>';
					}
					$regsys_error_messages .= '</ul>';
				}

				// Generate Form
				$regsys_output  .= <<<REGFORM
<a name="submitted"></a>
<div class="regsys">

	<h2>Pre-Register</h2>

	$regsys_error_messages

	<div class="regsys-form">

		<form id="regsys-form" action="#submitted" method="POST">

			<input type="hidden" name="regsys_form_confirm" value="$regsys_form" />
			$regsys_nonce_field

			<label>First Name *</label>
			<input type="text" name="first_name" value="$regsys_first_name" />

			<label>Last Name *</label>
			<input type="text" name="last_name" value="$regsys_last_name" />

			<label>Email *</label>
			<input type="text" name="email" value="$regsys_email" />

			<label>Division *</label>
			<select name="division">
				<option value="">Choose One</option>
				$regsys_division_options
			</select>

			<label>PDGA #</label>
			<input type="text" name="pdga_number" value="$regsys_pdga_number" />

			<input type="submit" name="submit" value="Submit" />

		</form>

	</div>
	<div class="regsys-form-message">
		$regsys_player_count_notice
		Pre-Registration requests are reviewed before being published. If you do not receive a response from an NDGC member regarding your registration, or your name is not listed within 24 hours, please get in touch with us through the <a href="/contact/">contact form</a>.
		<br /><br />
		* Required Fields
	</div>

	<div class="clearfix" clear="all" />

</div>
REGFORM;

			// Return Message
			} else {

				$regsys_output .= <<<REGSYS_SUCCESS
<a name="submitted"></a>
<div class="regys-success">
	<strong>Thank you for pre-registering to the Hub City Huck, $regsys_first_name $regsys_last_name.</strong><br /><br />

	If we have any questions we'll be in touch. Otherwise, please check back in 24 hours to confirm you're name is listed as pre-registered.
</div>
REGSYS_SUCCESS;

			}

			// Display Player List
			$regsys_players = regsys_player_list($regsys_form, $regsys_player_cap);
			if (!empty($regsys_players)) {
				$regsys_output .= $regsys_players;
			}

		}
		$content = str_replace($regsys_form_call, $regsys_output, $content);
	}

	// Close registration
	//if (date('Y-m-d H:m:s') == '2013-07-21 10:00:00') {
	//	$content = "Registration for the Hub City Huck 2013 event is now closed.";
	//}

	return $content;
}
add_filter( 'the_content', 'regsys_output');

// Send Notification Email
function regsys_notify_admin($regsys_user_info, $regsys_event) {


	// ************************************************************* *
	// TO DO: Make capable of sending SMTP email
	// ************************************************************* *

	if (!class_exists('PHPMailer'))
		require_once(ABSPATH . "wp-includes/class-phpmailer.php");
	$mail = new PHPMailer();
	$regsys_body  = "Player: " . $regsys_user_info['first_name'] . ' ' . $regsys_user_info['last_name'] . "<br />\n";
	$regsys_body .= "Email: " . $regsys_user_info['email'] . "<br />\n";
	$regsys_body .= "Division: " . $regsys_user_info['division'] . "<br />\n";
	$regsys_body .= "PDGA #: " . $regsys_user_info['pdga_number'] . "<br />\n";
	$regsys_body .= "Message: Please register me for the " . $regsys_event . " event.<br />\n";
	$regsys_body .= "Message Type: Pre-registration<br />\n";
	//$regsys_body .= 'Sender IP: ' . $_SERVER['REMOTE_ADDR'] . "<br />\n";
	$mail->From = $regsys_user_info['email'];
	$mail->FromName = 'NDGC Pre-registration Forms';
	$mail->AddAddress('webmaster@nanaimodgc.com');
	$mail->AddAddress('hubcityhuck-td@nanaimodgc.com');
	$mail->Subject = "A player has registered!";
	$mail->CharSet = "utf-8";
	$mail->AltBody = "To view the message, please use an HTML compatible email viewer!"; // optional, comment out and test
	$mail->MsgHTML($regsys_body);
	$mail->Send();
	return true;
}

// Store Registerd Player
function regsys_store_user($regsys_options) {
	global $wpdb;

	// Player Check
	$regsys_check_player = $wpdb->get_row(
		"SELECT `id` FROM `" . REGSYS_TABLE_USERS . "` WHERE
		`first_name` = '" . $wpdb->escape($regsys_options['first_name']) . "' AND
		`last_name`  = '" . $wpdb->escape($regsys_options['last_name'])  . "' AND
		`email`      = '" . $wpdb->escape($regsys_options['email'])      . "';"
	);

	// Insert Player
	if (empty($regsys_check_player)) {

		if (!$wpdb->query(
				"INSERT INTO `" . REGSYS_TABLE_USERS . "` SET
				`first_name`  = '" . $wpdb->escape($regsys_options['first_name']) . "',
				`last_name`   = '" . $wpdb->escape($regsys_options['last_name'])  . "',
				`email`       = '" . $wpdb->escape($regsys_options['email'])      . "',
				`event`       = '" . $wpdb->escape($regsys_options['event'])      . "',
				`division`    = '" . $wpdb->escape($regsys_options['division'])   . "',
				`pdga_number` = '" . $wpdb->escape($regsys_options['pdga_number'])   . "',
				`registered`  = '" . $wpdb->escape(date('Y-m-d H:i:s'))   . "';"
			)
		) {

			// Error
			return 'Failed to pre-register';

		} else {

			return true;
		}

	// Player Already Registered
	} else {
		return 'Player already registered';
	}

}

// Get Player List
function regsys_player_list($event, $player_cap) {
	global $wpdb;

	// Ensure Event Passed
	if (!empty($event)) {

		// Total Players
		$regsys_total_players = 0;

		// First 90
		$regsys_first_players = array();
		$regsys_event_players = $wpdb->get_results("
				SELECT `id`
				FROM `" . REGSYS_TABLE_USERS . "` `u`
				WHERE `u`.`event` = '" . $wpdb->escape($event)   . "' AND `u`.`confirmed` = 't'
				ORDER BY `u`.`registered` ASC;"
				, 'ARRAY_A');

		if (!empty($regsys_event_players)) {
			foreach ($regsys_event_players as $player) {
				if ($regsys_total_players <= $player_cap) {
					$regsys_first_players[] = $player;
				}
				$regsys_total_players++;
			}
		}

		// Get Event Players
		$regsys_event_players = $wpdb->get_results("
				SELECT `u`.*, `d`.`name` AS `division_name`
				FROM `" . REGSYS_TABLE_USERS . "` `u`
				JOIN `" . REGSYS_TABLE_DIVISIONS . "` `d`
				ON `u`.`division` = `d`.`id`
				WHERE `u`.`event` = '" . $wpdb->escape($event)   . "' AND `u`.`confirmed` = 't'
				ORDER BY `u`.`registered` ASC, `u`.`division`, `u`.`first_name`;"
				, 'ARRAY_A');

		$regsys_player_list = array();
		if (!empty($regsys_event_players)) {
			foreach ($regsys_event_players as $regsys_player) {
				$regsys_player['waitlist'] = (in_array($regsys_player['id'], $regsys_first_players)) ? ' - <strong>on waitlist</strong>' : '';
				$regsys_player_list[$regsys_player['division_name']][] = $regsys_player;

			}
		}

		// Get Division List
		$regsys_division_list = array();
		$regsys_divisions = $wpdb->get_results("SELECT `name` FROM `" . REGSYS_TABLE_DIVISIONS . "`;", 'ARRAY_A');
		if (!empty($regsys_divisions)) {
			foreach ($regsys_divisions as $regsys_division) {
				$regsys_division_list[] = $regsys_division['name'];
			}
		}

		// Create Player List
		if (!empty($regsys_division_list)) {
			$regsys_player_table = '<h2>Pre-Registered Players (' . $regsys_total_players . ')</h2>';
			foreach ($regsys_division_list as $regsys_division) {
				$regsys_player_table .= '<table class="regsys_event_players">';
				// Division Title
				$regsys_player_table .= '<tr>';
				$regsys_player_table .= '<th>' . $regsys_division . ' (' . count($regsys_player_list[$regsys_division]) . ')</th>';
				$regsys_player_table .= '</tr>';
				// Division Players
				if (!empty($regsys_player_list[$regsys_division])) {
					$regsys_flip_count = 1;
					foreach ($regsys_player_list[$regsys_division] as $regsys_player) {
						$regsys_player_table .= '<tr ' . (!empty($regsys_flip_count) ? 'class="odd"' : '') . '>';
						$regsys_player_table .= '<td>' . $regsys_player['first_name'] . ' ' . $regsys_player['last_name'] . (!empty($regsys_player['waitlist']) ? $regsys_player['waitlist'] : '') . '</td>';
						$regsys_player_table .= '</tr>';
						$regsys_flip_count = ($regsys_flip_count == 1) ? 0 : 1;
					}
				} else {
					$regsys_player_table .= '<tr>';
					$regsys_player_table .= '<td>No players pre-registered yet</td>';
					$regsys_player_table .= '</tr>';
				}
				$regsys_player_table .= '</table>';
			}
		}

		// Return
		if (!empty($regsys_player_table)) {
			return $regsys_player_table;
		} else {
			return false;
		}

	// No Event Found
	} else {
		return false;
	}

}
*/

// Load Admin Controls
if ( is_admin() ) {
	require_once dirname( __FILE__ ) . '/the-clubhouse-admin.php';
}

?>