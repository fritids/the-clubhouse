<?php
class Divisions {

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
				'division_select_failed'		=> array('type' => 'error', 	'message' => __('Could not locate selected division. <a href="?page=' . $_GET['page'] . '&control=divisions">View Division List</a>')),
				'division_update_failed'	   	=> array('type' => 'error', 	'message' => __('Could not update division information. Please try again.')),
				'division_updated'	   			=> array('type' => 'updated', 	'message' => __('Division information updated.')),
				'division_insert_failed'	   	=> array('type' => 'error', 	'message' => __('Division could not be created.')),
				'division_inserted'		   		=> array('type' => 'success', 	'message' => __('Division created.')),
				'division_delete_failed'	   	=> array('type' => 'error', 	'message' => __('Division could not be deleted.')),
				'division_deleted'		   		=> array('type' => 'updated', 	'message' => __('Division deleted.')),
				'division_failed_confirm'		=> array('type' => 'error', 	'message' => __('Could not confirm if division already exists.')),
				'division_failed_duplicate'		=> array('type' => 'error', 	'message' => __('Division already exists.')),
				'division_no_name'				=> array('type' => 'error', 	'message' => __('You must provide a name.')),
		));

	}

	/**
	 * Get Division
	 * @param int $id
	 */
	function getDivision($id) {
		global $wpdb;
		$division = $wpdb->get_row(
				"SELECT * FROM `" . CLUBHOUSE_TABLE_DIVISIONS . "` WHERE `id` = '" . $wpdb->escape($id) . "';", 'ARRAY_A'
		);
		if (empty($division)) {
			$GLOBALS['CH_SysMessages']->collectResponse('division_select_failed');
		}
		return $division;
	}

	/**
	 * Get Divisions
	 * @param optional string $query
	 */
	function getDivisions() {
		global $wpdb;
		$query = !empty($query) ? $query : "SELECT * FROM `" . CLUBHOUSE_TABLE_DIVISIONS . "`  ORDER BY `order` ASC;";
		$divisions = $wpdb->get_results($query, 'ARRAY_A');
		if (empty($divisions)) {
			$GLOBALS['CH_SysMessages']->collectResponse('division_select_failed');
		}
		return $divisions;
	}

	/**
	 * Confirm Division Exists
	 * @param unknown_type $config
	 */
	function confirmDuplicate($config = array()) {
		global $wpdb;
		$division = $wpdb->get_row(
				"SELECT `id` FROM `" . CLUBHOUSE_TABLE_DIVISIONS . "` WHERE
				`name` = '" . $wpdb->escape($config['name']) . "';"
		);
		if (!empty($wpdb->error)) {
			$GLOBALS['CH_SysMessages']->collectResponse('failed_confirm');
		}
		$confirmed = (!empty($division)) ? true : false;
		return $confirmed;
	}

	/**
	 * Add/Edit Division
	 * @param array $config
	 * @return html $output
	 */
	function manageDivision($config = array()) {
		if (empty($config)) return;
		global $wpdb;

		// Get Division
		$division = '';
		if ($config['action'] == 'edit' && !empty($config['id'])) {
			$division = $this->getDivision($config['id']);
		}

		// Confirm Submission
		if ( isset($_POST['submit']) ) {

			// Validate Referrer
			check_admin_referer( $clubhouse_nonce );
			if ( isset($_POST['form_check']) && $_POST['form_check'] == 'division' ) {

				// Set Form Vars
				$fields = array('name');
				foreach ($fields as $field) {
					$division[$field] = (isset($_POST[$field])) ? $_POST[$field] : '';
				}

				// Check Required
				if (empty($division['name'])) $GLOBALS['CH_SysMessages']->collectResponse('no_value');

				// Proceed if No Errors
				if (empty($GLOBALS['CH_SysMessages']->responses)) {

					// Add Division
					if ( $_POST['action'] == 'add' ) {

						// Check for Duplicate
						$check_division = $this->confirmDuplicate(array(
							'name'  => $wpdb->escape($_POST['name'])
						));

						// Get Highest Order
						$order = $wpdb->get_row("SELECT MAX(`order`) as `max` FROM `" . CLUBHOUSE_TABLE_DIVISIONS . "`;", 'ARRAY_A');

						// Insert Division
						if (empty($check_division)) {

							if (!$wpdb->query(
									"INSERT INTO `" . CLUBHOUSE_TABLE_DIVISIONS . "` SET
									`name`  = '" . $wpdb->escape($_POST['name'])  . "',
									`order`  = '" . $wpdb->escape($order['max']+1)  . "';"
							)
							) {

								// Error
								$GLOBALS['CH_SysMessages']->collectResponse('insert_failed');

							} else {

								// Success
								$GLOBALS['CH_SysMessages']->collectResponse('inserted');

								// Get Division and Redirect
								$lastid = $wpdb->insert_id;
								$division = $this->getDivision($lastid);
								$config['action'] = 'edit';


							}

						// Division Already Registered
						} else {
							$GLOBALS['CH_SysMessages']->collectResponse('failed_duplicate');
						}

					// Update Division
					} elseif ( $_POST['action'] == 'edit' ) {

						if ($wpdb->update(
								CLUBHOUSE_TABLE_DIVISIONS,
								array(
										'name'  => $wpdb->escape($division['name']),	   // string
								),
								array( 'id' => $config['id'] ),
								array(
										'%s', // string
								),
								array( '%d' )

						) === false ) {

							// Error
							$GLOBALS['CH_SysMessages']->collectResponse('update_failed');

						} else {

							// Success
							$GLOBALS['CH_SysMessages']->collectResponse('updated');

							// Get Division
							$division = $this->getDivision($config['id']);

						}

					}

				}

			}

		}

		// Get Manager
		ob_start();
		?>
		<div class="clubhouse-form clubhouse-admin">

			<?php $modifier = ($config['action'] == 'edit') ? 'Edit' : 'Add'; ?>
			<h2><?php echo __($modifier.' Division'); ?></h2>

			<form id="clubhouse-division-form" method="post" action="?page=<?php echo $_GET['page']; ?>&control=divisions">

				<?php clubhouse_nonce_field($clubhouse_nonce); // form validation ?>
				<input type="hidden" name="id" value="<?php echo $division['id']; ?>">
				<input type="hidden" name="action" value="<?php echo $config['action']; ?>">
				<input type="hidden" name="form_check" value="division">

				<div class="x3">
					<label>Division Name</label>
					<input type="text" name="name" value="<?php echo $division['name']; ?>">
				</div>

				<div class="clearer">
					<input type="submit" name="submit" value="<?php echo __($modifier.' Division'); ?>">
				</div>

			</form>

		</div>

<?php

		$output = ob_get_clean();
		return $output;

	}

	/**
	 * Get List of Divisions
	 * @param array config
	 * @purpose flexible selection of divisions
	 */
	function getDivisionList($config = array()) {
		global $wpdb;

		// Delete Division
		if ( $config['action'] == 'delete' && !empty($config['id']) && is_numeric($config['id']) ) {
			if (!$wpdb->query( "DELETE FROM `" . CLUBHOUSE_TABLE_DIVISIONS . "` WHERE `id` = '" . $config['id'] . "';")) {
				$GLOBALS['CH_SysMessages']->collectResponse('delete_failed');
			} else {
				$GLOBALS['CH_SysMessages']->collectResponse('deleted');
			}
		}

		// Get Divisions
		$divisions = $this->getDivisions();

		// Display Division List
		ob_start();
?>
		<div id="clubhouse-division-list">

			<h2><?php _e('Divisions'); ?> [<a href="?page=<?php echo $_GET['page']; ?>&control=divisions&action=add"><?php _e('Add one'); ?></a>]</h2>

			<?php if (!empty($divisions)) { ?>
				<table class="clubhouse-table clubhouse-admin" cellspacing="0">

					<thead>
						<tr class="clubhouse-list-columns">
							<td style="width:10px;">&nbsp;</td>
							<td><?php _e('Name'); ?></td>
							<td style="width:65px;">&nbsp;</td>
						</tr>
					</thead>

					<tbody id="sortable" class="clubhouse-divisions">

						<?php foreach($divisions as $division) { ?>
						<tr id="division-<?php echo $division['id']; ?>">
							<td>.</td>
							<td><?php echo $division['name']; ?></td>
							<td>
								<a href="?page=<?php echo $_GET['page']; ?>&control=divisions&action=edit&id=<?php echo $division['id']; ?>">edit</a> |
								<a href="?page=<?php echo $_GET['page']; ?>&control=divisions&action=delete&id=<?php echo $division['id']; ?>" onclick="return confirm('Are you sure you want to delete this division?');">delete</a>
							</td>
						</tr>
						<?php } ?>

					</tbody>

				</table>
			<?php } else { ?>
				<p>No divisions found.</td>
			<?php } ?>

		</div>
<?php
		$output = ob_get_clean();
		return $output;

	}

}

/****** AJAX ******/
add_action( 'admin_footer', 'divisions_ajax_javascript' );

function divisions_ajax_javascript() {
	?>
<script type="text/javascript" >
jQuery(document).ready(function($) {

	// Sortable
	$( "#sortable .clubhouse-divisions" ).sortable({
		stop: function (event, ui) {

			var data = {
				action: 'division_actions',
				divisions: $(this).sortable("serialize"),
			};

			// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
			$.post(ajaxurl, data, function(response) {
				//console.log('Got this from the server: ' + response);
			});

		}
	});

});
</script>
<?php
}

add_action('wp_ajax_division_actions', 'division_actions_callback');

function division_actions_callback() {
	global $wpdb; // this is how you get access to the database

	//var_dump($_REQUEST);
	$division_list = explode('&',$_REQUEST['divisions']);

	// Clean List
	$divisions = array();
	foreach ($division_list as $division_info) {
		list($junk, $id) = explode('=', $division_info);
		$divisions[] = $id;
	}
	asort($divisions);

	if (empty($divisions) || !is_array($divisions)) die();
	foreach ($divisions as $new_order => $id) {

		// TODO: increase the efficiency of this, we don't need to update everything

		if ($wpdb->update(
				CLUBHOUSE_TABLE_DIVISIONS,
				array(
						'order'  => $wpdb->escape($new_order),	   // int
				),
				array( 'id' => $id ),
				array(
						'%d', // int
				),
				array( '%d' )

		) === false) {

			//echo "fail";

		} else {

			///echo "success";

			// Get Divisions
			$divisions = $GLOBALS['CH_Divisions']->getDivisions();

		}

	}

	//echo $divisions;
	die(); // this is required to return a proper result
}

?>