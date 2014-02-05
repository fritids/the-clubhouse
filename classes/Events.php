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
				'event_select_failed'		=> array('type' => 'error', 	'message' => __('Could not locate selected event. <a href="?page=clubhouse-config&control=events">View Event List</a>')),
				'event_update_failed'	   	=> array('type' => 'error', 	'message' => __('Could not update event information. Please try again.')),
				'event_updated'	   			=> array('type' => 'updated', 	'message' => __('Event information updated.')),
				'event_insert_failed'	   	=> array('type' => 'error', 	'message' => __('Event could not be created.')),
				'event_inserted'		   	=> array('type' => 'success', 	'message' => __('Event created.')),
				'event_delete_failed'	   	=> array('type' => 'error', 	'message' => __('Event could not be deleted.')),
				'event_deleted'		   		=> array('type' => 'updated', 	'message' => __('Event deleted.')),
				'event_failed_confirm'		=> array('type' => 'error', 	'message' => __('Could not confir m if event already exists.')),
				'event_failed_duplicate'	=> array('type' => 'error', 	'message' => __('Event already exists.')),
				'event_no_name'				=> array('type' => 'error', 	'message' => __('You must provide a name.')),
				'event_no_director'			=> array('type' => 'error', 	'message' => __('You must provide a director.')),
				'event_no_divisions'		=> array('type' => 'error', 	'message' => __('You must select at least one division.')),
				'event_no_type'				=> array('type' => 'error', 	'message' => __('You must select a type.')),
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
			$wpdb->prepare(
				"
					SELECT *
					FROM `" . CLUBHOUSE_TABLE_EVENTS . "`
					WHERE `id` = %d;
				",
				$id
			),
			'ARRAY_A'
		);
		if (!empty($wpdb->error)) {
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
		if (!empty($wpdb->error)) {
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
			$wpdb->prepare(
				"
					SELECT `id`
					FROM `" . CLUBHOUSE_TABLE_EVENTS . "`
					WHERE `event_name` = %s;
				",
				$config['event_name']
			)
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
		$event = array(
			'id'=>'',
			'event_name'=>'',
			'director_id'=>'',
			'divisions'=>'',
			'email'=>'',
			'division_id'=>'',
		);
		if ($config['action'] == 'edit' && !empty($config['id'])) {
			$event = $this->getEvent($config['id']);
		}

		// Confirm Submission
		if ( isset($_POST['submit']) ) {

			// Validate Referrer
			global $clubhouse_nonce;
			check_admin_referer( $clubhouse_nonce );
			if ( isset($_POST['form_check']) && $_POST['form_check'] == 'event' ) {

				// Set Form Vars
				$fields = array('event_name','divisions','type');
				foreach ($fields as $field) {
					$event[$field] = (isset($_POST[$field])) ? $_POST[$field] : '';
				}

				// Check Required
				if (empty($event['event_name'])) $GLOBALS['CH_SysMessages']->collectResponse('event_no_name');
				if (empty($event['director_id']))   $GLOBALS['CH_SysMessages']->collectResponse('event_no_director');
				if (empty($event['divisions']))  $GLOBALS['CH_SysMessages']->collectResponse('event_no_divisions');
				if (empty($event['type'])) 		 $GLOBALS['CH_SysMessages']->collectResponse('event_no_type');

				// Proceed if No Errors
				if (empty($GLOBALS['CH_SysMessages']->responses)) {

					// Add Event
					if ( $_POST['action'] == 'add' ) {

						// Check for Event
						$check_event = $this->confirmDuplicate(array(
							'event_name' => $wpdb->escape($event['event_name'])
						));

						// Insert Event
						if (empty($check_event)) {

							if(!$wpdb->insert(
									CLUBHOUSE_TABLE_EVENTS,
									array(
										'event_name'  => $_POST['event_name'],
										'director_id' => $_POST['director_id'],
										'divisions'   => serialize($event['divisions']),
										'type'		  => $event['type']
									),
									array(
										'%s',
										'%d',
										'%s',
										'%s',
									)
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
										'event_name'	=> $wpdb->escape($event['event_name']),	   			// string
										'director_id'	=> $event['director_id'],	   		// string
										'divisions' 	=> $wpdb->escape(serialize($event['divisions'])),	// string
										'type' 			=> $wpdb->escape($event['type']),	  				// string
								),
								array( 'id' => $config['id'] ),
								array(
										'%s', // string
										'%s', // string
										'%s', // string
										'%s', // string
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
		$event['divisions'] = is_array($event['divisions']) ? $event['divisions'] : unserialize(stripslashes($event['divisions']));

		// Get Directors
		$event['directors'] = $GLOBALS['CH_Directors']->getDirectors("SELECT `id`, CONCAT(`first_name`, ' ', `last_name`) AS `name`FROM `" . CLUBHOUSE_TABLE_DIRECTORS . "`;");

		// Get Manager
		ob_start();
		?>
		<div class="clubhouse-form clubhouse-admin">

			<?php $modifier = ($config['action'] == 'edit') ? 'Edit' : 'Add'; ?>
			<h2><?php echo __($modifier.' Event'); ?></h2>

			<form id="clubhouse-event-form" method="post" action="?page=<?php echo $_GET['page']; ?>&control=events">

				<?php
				global $clubhouse_nonce;
				clubhouse_nonce_field($clubhouse_nonce); // form validation ?>
				<input type="hidden" name="id" value="<?php echo $event['id']; ?>">
				<input type="hidden" name="action" value="<?php echo $config['action']; ?>">
				<input type="hidden" name="form_check" value="event">

				<div class="x3">

					<fieldset>

						<h3>Details</h3>

						<label>Event Name</label>
						<input type="text" name="event_name" value="<?php echo $event['event_name']; ?>">

						<label>Type</label>
						<select name="type" id="type_selector">
							<?php $types = array('weekly','tournament','tour'); ?>
							<option value="">Choose One</option>
							<?php foreach ($types as $type) { ?>
								<?php $selected = $type == $event['type'] ? ' selected="selected"' : '' ; ?>
								<option value="<?php echo $type; ?>"<?php echo $selected; ?>><?php echo ucwords($type); ?></option>
							<?php } ?>
						</select>

						<fieldset id="event-details"></fieldset>

					</fieldset>


				</div>

				<div class="x3">
					<label>Divisions</label>
					<select name="divisions[]" multiple="multiple" size="8">
					<?php foreach ($this->divisions as $division) { ?>
						<?php $selected = is_array($event['divisions']) && in_array($division['id'], $event['divisions']) ? ' selected="selected"' : '' ; ?>
						<option value="<?php echo $division['id']; ?>"<?php echo $selected; ?>><?php echo $division['division_name']; ?></option>
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
							<td>Type</td>
							<td style="width:65px;">&nbsp;</td>
						</tr>
					</thead>

					<tbody>

						<?php foreach($events as $event) { ?>
						<tr>
							<td><?php echo $event['event_name']; ?></td>
							<td><?php echo $event['type']; ?></td>
							<td class="clubhouse-list-controls">
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

	$('#type_selector').bind('change', function() {

		// Count Existing Controls
		var numControls = $('.event-controls').length,
			type = $(this).val();

		var data = {
			action: 'get_controls',
			controlset_id: numControls+1,
			type: type,
		};

		// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
		$.post(ajaxurl, data, function(response) {
			console.log('Got this from the server: ' + response);
			//if (type != 'tour') {
			//	$('#event-details').html(response);
			//} else {
			//	$('#event-details').append(response);
			//}
		});
	});

});
</script>
<?php
}

add_action('wp_ajax_event_actions', 'events_ajax_callback');
function events_ajax_callback() {
	global $wpdb;

	echo "here";

	// Get Event Controls
	//if ($_REQUEST['action'] == 'get_controls' && is_numeric($_REQUEST['controlset_id'])) {
	//	$controls = eventControls(array('id' => $_REQUEST['get_controls'], 'type' => $_REQUEST['type']));
	//	echo $controls;
	//}

	die(); // this is required to return a proper result
}

/****** SUPPORT FUNCTIONS ******/

/**
 * Event Controls
 * Purpose: modular output of controls specific to an event, or stop on an event
 */
function eventControls($config = array()) {

	$controlset_id = $config['id'];

	// Collect Event Controls
	ob_start();
	?>
<div id="event-controls-<?php echo $controlset_id; ?>" class="event-controls">

	<label>Tournament Director</label>
	<input type="text" class="autocomplete" name="td_<?php echo $controlset_id; ?>" value="" />

	<label>Course</label>
	<input type="text" class="autocomplete"  name="course_<?php echo $controlset_id; ?>" value="" />

	<?php if ($config['type'] == 'weekly') { ?>

		<label>Day of Week</label>
		<select name="weekday_<?php echo $controlset_id; ?>">
			<option value="">Choose One</option>
			<option value="Mon">Monday</option>
			<option value="Tue">Tuesday</option>
			<option value="Wed">Wednesday</option>
			<option value="Thu">Thursday</option>
			<option value="Fri">Friday</option>
			<option value="Sat">Saturday</option>
			<option value="Sun">Sunday</option>
		</select>

	<?php } else { ?>

		<label>Start Date</label>
		<input type="text" class="datepicker" name="start_date_<?php echo $controlset_id; ?>" value="" />

		<label>End Date</label>
		<input type="text" class="datepicker" name="end_date_<?php echo $controlset_id; ?>" value="" />

	<?php } ?>

	<label>Players Meeting</label>
	<input type="text" class="timepicker" name="players_meeting_<?php echo $controlset_id; ?>" value="" />

</div>
<?php
		$output = ob_get_clean();
		return $output;

	}

?>