<?php

/**
 * Events
 * @author Brad
 *
 * Methods:
 * - getEvent
 * - getEvents
 * - confirmDuplicate
 * - manageEvent
 * - getEventList
 *
 * General Functionality
 * - Manage Event Information
 * - Communicate with players

 *
Event
----------------

- tournament director, email address, phone number
- emails (welcome, updates)
- fees

Rounds (tournaments)
- day 1, # rounds, course
- day 2, # rounds, course


Weekly (league)
- day of week
- time
- fees


Monthly (tours)
- dates, locations, times















 */

class Events {

	/**
	 * Setup Class
	 * @param array $config
	 * @puprose items required for the class to function correctly
	 */
	function __construct() {

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

		// Messaging
		$GLOBALS['CH_SysMessages']->storeMessages(array(
				'event_select_failed'		=> array('type' => 'error', 	'message' => __('Could not locate selected event. <a href="?page=' . $_GET['page'] . '&control=events">View Event List</a>')),
				'event_update_failed'	   	=> array('type' => 'error', 	'message' => __('Could not update event information. Please try again.')),
				'event_updated'	   			=> array('type' => 'updated', 	'message' => __('Event information updated.')),
				'event_insert_failed'	   	=> array('type' => 'error', 	'message' => __('Event could not be created.')),
				'event_inserted'		   	=> array('type' => 'success', 	'message' => __('Event created.')),
				'event_delete_failed'	   	=> array('type' => 'error', 	'message' => __('Event could not be deleted.')),
				'event_deleted'		   		=> array('type' => 'updated', 	'message' => __('Event deleted.')),
				'event_failed_confirm'		=> array('type' => 'error', 	'message' => __('Could not confir m if event already exists.')),
				'event_failed_duplicate'	=> array('type' => 'error', 	'message' => __('Event already exists.')),
				'event_no_name'				=> array('type' => 'error', 	'message' => __('You must provide a name.')),
				'event_no_date'				=> array('type' => 'error', 	'message' => __('You must provide a valid date.')),
				'event_no_divisions'		=> array('type' => 'error', 	'message' => __('You must select at least one division.')),
				'event_no_itterator'		=> array('type' => 'error', 	'message' => __('You must select an itterator.')),
				'event_no_duration'			=> array('type' => 'error', 	'message' => __('You must provide a duration.')),
		));

		// Get Divisions
		$this->divisions = $GLOBALS['CH_Divisions']->getDivisions();

	}

	/**
	 * Get Event
	 * @param int $id
	 */
	function getEvent($id) {
		global $wpdb;
		$event = $wpdb->get_row(
				"SELECT * FROM `" . CLUBHOUSE_TABLE_EVENTS . "` WHERE `id` = '" . $wpdb->escape($id) . "';", 'ARRAY_A'
		);
		if (empty($event)) {
			$GLOBALS['CH_SysMessages']->collectResponse('event_select_failed');
		}
		return $event;
	}

	/**
	 * Get Events
	 * @param optional string $query
	 */
	function getEvents() {
		global $wpdb;
		$query = !empty($query) ? $query : "SELECT * FROM `" . CLUBHOUSE_TABLE_EVENTS . "`;";
		$events = $wpdb->get_results($query, 'ARRAY_A');
		if (empty($events)) {
			$GLOBALS['CH_SysMessages']->collectResponse('event_select_failed');
		}
		return $events;
	}

	/**
	 * Confirm Event Exists
	 * @param unknown_type $config
	 */
	function confirmDuplicate($config = array()) {
		global $wpdb;
		$event = $wpdb->get_row(
				"SELECT `id` FROM `" . CLUBHOUSE_TABLE_EVENTS . "` WHERE
				`name` = '" . $wpdb->escape($config['name']) . "';"
		);
		if (!empty($wpdb->error)) {
			$GLOBALS['CH_SysMessages']->collectResponse('event_failed_confirm');
		}
		$confirmed = (!empty($event)) ? true : false;
		return $confirmed;
	}

	/**
	 * Add/Edit Event
	 * @param array $config
	 * @return html $output
	 */
	function manageEvent($config = array()) {
		if (empty($config)) return;
		global $wpdb;

		// Get Event
		$event = '';
		if ($config['action'] == 'edit' && !empty($config['id'])) {
			$event = $this->getEvent($config['id']);
		}

		// Confirm Submission
		if ( isset($_POST['submit']) ) {

			// Validate Referrer
			check_admin_referer( $clubhouse_nonce );
			if ( isset($_POST['form_check']) && $_POST['form_check'] == 'event' ) {

				// Set Form Vars
				$fields = array('name','date','divisions','itterator','duration');
				foreach ($fields as $field) {
					$event[$field] = (isset($_POST[$field])) ? $_POST[$field] : '';
				}

				// Check Required
				if (empty($event['name'])) 		$GLOBALS['CH_SysMessages']->collectResponse('event_no_name');
				if (empty($event['date'])) 		$GLOBALS['CH_SysMessages']->collectResponse('event_no_date');
				if (empty($event['divisions'])) $GLOBALS['CH_SysMessages']->collectResponse('event_no_divisions');
				if (empty($event['itterator'])) $GLOBALS['CH_SysMessages']->collectResponse('event_no_itterator');
				if (empty($event['duration'])) 	$GLOBALS['CH_SysMessages']->collectResponse('event_no_duration');

				// Proceed if No Errors
				if (empty($GLOBALS['CH_SysMessages']->responses)) {

					// Add Event
					if ( $_POST['action'] == 'add' ) {

						// Check for Event
						$check_event = $this->confirmDuplicate(array(
							'name'  => $wpdb->escape($_POST['name'])
						));

						// Insert Event
						if (empty($check_event)) {

							if (!$wpdb->query(
									"INSERT INTO `" . CLUBHOUSE_TABLE_EVENTS . "` SET
									`name`  	= '" . $wpdb->escape($_POST['name']) . "',
									'date'      = '" . $wpdb->escape($event['date']) . "',
									'divisions' = '" . $wpdb->escape(serialize($event['divisions'])) . "',
									'itterator' = '" . $wpdb->escape($event['itterator']) . "',
									'duration'  = '" . $wpdb->escape($event['duration']) . "';"
							)) {

								// Error
								$GLOBALS['CH_SysMessages']->collectResponse('event_insert_failed');

							} else {

								// Success
								$GLOBALS['CH_SysMessages']->collectResponse('event_inserted');

								// Get Event and Redirect
								$lastid = $wpdb->insert_id;
								$event = $this->getEvent($lastid);
								$config['action'] = 'edit';


							}

						// Event Already Registered
						} else {
							$GLOBALS['CH_SysMessages']->collectResponse('event_failed_duplicate');
						}

					// Update Event
					} elseif ( $_POST['action'] == 'edit' ) {

						if ($wpdb->update(
								CLUBHOUSE_TABLE_EVENTS,
								array(
										'name' 		=> $wpdb->escape($event['name']),	   				// string
										'date'      => $wpdb->escape($event['date']),					// string
										'divisions' => $wpdb->escape(serialize($event['divisions'])),	// string
										'itterator' => $wpdb->escape($event['itterator']),	  			// string
										'duration'  => $wpdb->escape($event['duration']),	   			// int
								),
								array( 'id' => $config['id'] ),
								array(
										'%s', // string
										'%s', // string
										'%s', // string
										'%s', // string
										'%d', // int
								),
								array( '%d' )

						) === false ) {

							// Error
							$GLOBALS['CH_SysMessages']->collectResponse('event_update_failed');

						} else {

							// Success
							$GLOBALS['CH_SysMessages']->collectResponse('event_updated');

							// Get Event
							$event = $this->getEvent($config['id']);

						}

					}

				}

			}

		}

		// Prep Data
		$event['divisions'] = unserialize(stripslashes($event['divisions']));

		// Get Manager
		ob_start();
		$events['divisions'] = json_decode($events['divisions']);
		?>
		<div class="clubhouse-form clubhouse-admin">

			<?php $modifier = ($config['action'] == 'edit') ? 'Edit' : 'Add'; ?>
			<h2><?php echo __($modifier.' Event'); ?></h2>

			<form id="clubhouse-event-form" method="post" action="?page=<?php echo $_GET['page']; ?>&control=events">

				<?php clubhouse_nonce_field($clubhouse_nonce); // form validation ?>
				<input type="hidden" name="id" value="<?php echo $event['id']; ?>">
				<input type="hidden" name="action" value="<?php echo $config['action']; ?>">
				<input type="hidden" name="form_check" value="event">

				<div class="x3">

					<label>Event Name</label>
					<input type="text" name="name" value="<?php echo $event['name']; ?>">

					<label>Event Date(s)</label>
					<input type="text" name="dates" value="<?php echo $event['dates']; ?>" class="datepicker">

					<label>Itterator</label>
					<select name="itterator">
						<?php $itterators = array('rounds','weekly','monthly'); ?>
						<option value="">Choose One</option>
						<?php foreach ($itterators as $itterator) { ?>
							<?php $selected = $itterator == $event['itterator'] ? ' selected="selected"' : '' ; ?>
							<option value="<?php echo $itterator; ?>"<?php echo $selected; ?>><?php echo ucwords($itterator); ?></option>
						<?php } ?>
					</select>

					<label>Duration</label>
					<input type="text" name="duration" value="<?php echo $event['duration']; ?>" maxlength="2">

				</div>

				<div class="x3">
					<label>Divisions</label>
					<select name="divisions[]" multiple="multiple" size="8">
					<?php foreach ($this->divisions as $division) { ?>
						<?php $selected = is_array($event['divisions']) && in_array($division['id'], $event['divisions']) ? ' selected="selected"' : '' ; ?>
						<option value="<?php echo $division['id']; ?>"<?php echo $selected; ?>><?php echo $division['name']; ?></option>
					<?php } ?>
					</select>
				</div>

				<div class="clearer">
					<input type="submit" name="submit" value="<?php echo __($modifier.' Event'); ?>">
				</div>

			</form>

		</div>

<?php

		$output = ob_get_clean();
		return $output;

	}

	/**
	 * Get List of Events
	 * @param array config
	 * @purpose flexible selection of events
	 */
	function getEventList($config = array()) {
		global $wpdb;

		// Delete Event
		if ( $config['action'] == 'delete' && !empty($config['id']) && is_numeric($config['id']) ) {
			if (!$wpdb->query( "DELETE FROM `" . CLUBHOUSE_TABLE_EVENTS . "` WHERE `id` = '" . $config['id'] . "';")) {
				$GLOBALS['CH_SysMessages']->collectResponse('delete_failed');
			} else {
				$GLOBALS['CH_SysMessages']->collectResponse('event_deleted');
			}
		}

		// Get Events
		$events = $this->getEvents();

		// Display Event List
		ob_start();
?>
		<div id="clubhouse-event-list">

			<h2><?php echo __('Events'); ?> [<a href="?page=<?php echo $_GET['page']; ?>&control=events&action=add">Add one</a>]</h2>

			<?php if (!empty($events)) { ?>
				<table class="clubhouse-table clubhouse-admin" cellspacing="0">

					<thead>
						<tr class="clubhouse-list-columns">
							<td>Name</td>
							<td>Itterator</td>
							<td>Duration</td>
							<td style="width:65px;">&nbsp;</td>
						</tr>
					</thead>

					<tbody>

						<?php foreach($events as $event) { ?>
						<tr>
							<td><?php echo $event['name']; ?></td>
							<td><?php echo $event['itterator']; ?></td>
							<td><?php echo $event['duration']; ?></td>
							<td>
								<a href="?page=<?php echo $_GET['page']; ?>&control=events&action=edit&id=<?php echo $event['id']; ?>">edit</a> |
								<a href="?page=<?php echo $_GET['page']; ?>&control=events&action=delete&id=<?php echo $event['id']; ?>" onclick="return confirm('Are you sure you want to delete this event?');">delete</a>
							</td>
						</tr>
						<?php } ?>

					</tbody>

				</table>
			<?php } else { ?>
				<p>No events found.</td>
			<?php } ?>

		</div>
<?php
		$output = ob_get_clean();
		return $output;

	}

}

/****** AJAX ******/
add_action( 'admin_footer', 'events_ajax_javascript' );

function events_ajax_javascript() {
	?>
<script type="text/javascript" >
jQuery(document).ready(function($) {

	// Date Picker
	$( ".datepicker" ).datepicker({
		altFormat: "yy-mm-dd",
		dateFormat: "MM d, yy"
	});

});
</script>
<?php
}

add_action('wp_ajax_event_actions', 'event_actions_callback');

function event_actions_callback() {
	global $wpdb; // this is how you get access to the database

}

?>