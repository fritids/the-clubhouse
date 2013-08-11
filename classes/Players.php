<?php

/**
 * Players
 * @author Brad
 *
 * Methods:
 * - getPlayer
 * - getPlyers
 * - confirmDuplicate
 * - managePlayer
 * - getPlayerList
 *
 */

class Players {

	/**
	 * Setup Class
	 * @param array $config
	 * @puprose items required for the class to function correctly
	 */
	function __construct($config = array()) {
		global $wpdb;

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
				'player_select_failed'			=> array('type' => 'error', 	'message' => __('Could not locate selected player. <a href="?page=clubhouse-config">View Player List</a>')),
				'player_update_failed'	   		=> array('type' => 'error', 	'message' => __('Could not update player information. Please try again.')),
				'player_updated'	    		=> array('type' => 'updated', 	'message' => __('Player information updated.')),
				'player_insert_failed'	   		=> array('type' => 'error', 	'message' => __('Player could not be created.')),
				'player_inserted'		   		=> array('type' => 'success', 	'message' => __('Player created.')),
				'player_delete_failed'	   		=> array('type' => 'error', 	'message' => __('Player could not be deleted.')),
				'player_deleted'		   		=> array('type' => 'updated', 	'message' => __('Player deleted.')),
				'player_failed_confirm'			=> array('type' => 'error', 	'message' => __('Could not confirm if player already exists.')),
				'player_failed_duplicate'		=> array('type' => 'error', 	'message' => __('Player already exists.')),
				'player_no_first_name'			=> array('type' => 'error', 	'message' => __('First Name is required.')),
				'player_no_last_name'			=> array('type' => 'error', 	'message' => __('Last Name is required.')),
				'player_no_email'				=> array('type' => 'error', 	'message' => __('Email is required.')),
				'player_invalid_email'			=> array('type' => 'error', 	'message' => __('Email is malformed.')),
				'player_no_division'			=> array('type' => 'error', 	'message' => __('Division is required.')),
				'player_invalid_pdga_number'	=> array('type' => 'error', 	'message' => __('Please enter a valid pdga number.')),
		));

		// Get Divisions
		$this->divisions = $GLOBALS['CH_Divisions']->getDivisions();

	}

	/**
	 * Get Player
	 * @param int $id
	 */
	function getPlayer($id) {
		global $wpdb;
		$player = $wpdb->get_row(
				"SELECT * FROM `" . CLUBHOUSE_TABLE_PLAYERS . "` WHERE `id` = '" . $wpdb->escape($id) . "';", 'ARRAY_A'
		);
		if (!empty($wpdb->error)) {
			$GLOBALS['CH_SysMessages']->collectResponse('player_select_failed');
		}
		return $player;
	}

	/**
	 * Get Players
	 * @param optional string $query
	 */
	function getPlayers($query = '') {
		global $wpdb;
		$query = !empty($query) ? $query : "SELECT * FROM `" . CLUBHOUSE_TABLE_PLAYERS . "`;";
		$players = $wpdb->get_results($query, 'ARRAY_A');
		if (!empty($wpdb->error)) {
			$GLOBALS['CH_SysMessages']->collectResponse('player_select_failed');
		}
		return $players;
	}

	/**
	 * Confirm Player Exists
	 * @param unknown_type $config
	 */
	function confirmDuplicate($config = array()) {
		global $wpdb;
		$player = $wpdb->get_row(
				"SELECT `id` FROM `" . CLUBHOUSE_TABLE_PLAYERS . "` WHERE
				`first_name` = '" . $wpdb->escape($config['first_name']) . "' AND
				`last_name`  = '" . $wpdb->escape($config['last_name'])  . "' AND
				`email`      = '" . $wpdb->escape($config['email'])      . "';"
		);
		if (!empty($wpdb->error)) {
			$GLOBALS['CH_SysMessages']->collectResponse('failed_confirm');
		}
		$confirmed = (!empty($player)) ? true : false;
		return $confirmed;
	}

	//function getHandicap($config = array()) {
	//	// fetaches player handicap
	//}
    //
	//function getScores($config = array()) {
	//	// get player score
	//	// get round score
	//}

	/**
	 * Manage Players
	 * @param array $config
	 * @return html $output
	 */
	function managePlayer($config = array()) {
		if (empty($config)) return;
		global $wpdb;

		// Get Player
		$player = array(
			'id'=>'',
			'first_name'=>'',
			'last_name'=>'',
			'email'=>'',
			'division_id'=>'',
			'pdga_number'=>'',
		);
		if ($config['action'] == 'edit' && !empty($config['id'])) {
			$player = $this->getPlayer($config['id']);
		}

		// Confirm Submission
		if ( !empty($_POST['submit']) ) {

			// Validate Referrer
			global $clubhouse_nonce;
			check_admin_referer( $clubhouse_nonce );
			if ( !empty($_POST['form_check']) && $_POST['form_check'] == 'player') {

				// Set Form Vars
				$fields = array('first_name', 'last_name', 'email', 'division_id', 'pdga_number');
				foreach ($fields as $field) {
					$player[$field] = (isset($_POST[$field])) ? $_POST[$field] : '';
				}

				// Check Required
				if (empty($player['first_name'])) 			$GLOBALS['CH_SysMessages']->collectResponse('player_no_first_name');
				if (empty($player['last_name'])) 			$GLOBALS['CH_SysMessages']->collectResponse('player_no_last_name');
				if (empty($player['email'])) 				$GLOBALS['CH_SysMessages']->collectResponse('player_no_email');
				if (!empty($player['email']) && !is_email($player['email']))
															$GLOBALS['CH_SysMessages']->collectResponse('player_invalid_email');
				if (empty($player['division_id'])) 			$GLOBALS['CH_SysMessages']->collectResponse('player_no_division');
				if (!is_numeric($player['pdga_number'])) 	$GLOBALS['CH_SysMessages']->collectResponse('player_invalid_pdga_number');

				// Proceed if No Errors
				if (empty($GLOBALS['CH_SysMessages']->responses)) {

					// Add Player
					if ( $_POST['action'] == 'add' ) {

						// Check for Duplicate
						$check_player = $this->confirmDuplicate(array(
							'first_name'  => $wpdb->escape($player['first_name']),
							'last_name'   => $wpdb->escape($player['last_name']),
							'email'       => $wpdb->escape($player['email']),
						));

						// Insert Player
						if (empty($check_player)) {

							if (!$wpdb->query(
									"INSERT INTO `" . CLUBHOUSE_TABLE_PLAYERS . "` SET
									`first_name`  		 = '" . $wpdb->escape($player['first_name'])  . "',
									`last_name`   		 = '" . $wpdb->escape($player['last_name'])   . "',
									`email`       		 = '" . $wpdb->escape($player['email'])       . "',
									`division_id`        = '" . $wpdb->escape($player['division_id'])    . "',
									`pdga_number` 		 = '" . $wpdb->escape($player['pdga_number']) . "',
									`timestamp_created`  = '" . $wpdb->escape(date('Y-m-d H:i:s'))    . "';"
							)
							) {

								// Error
								$GLOBALS['CH_SysMessages']->collectResponse('player_insert_failed');

							} else {

								// Success
								$GLOBALS['CH_SysMessages']->collectResponse('player_inserted');

								// Get Player and Set Action
								$lastid = $wpdb->insert_id;
								$player = $this->getPlayer($lastid);
								$config['action'] = 'edit';

							}

						// Player Already Registered
						} else {
							$GLOBALS['CH_SysMessages']->collectResponse('player_failed_duplicate');
						}

					// Update Player
					} elseif ( $_POST['action'] == 'edit' ) {

						if ($wpdb->update(

								CLUBHOUSE_TABLE_PLAYERS,
								array(
										'first_name'  => $wpdb->escape($player['first_name']),	// string
										'last_name'   => $wpdb->escape($player['last_name']),	// string
										'email'       => $wpdb->escape($player['email']),	  	// string
										'division_id' => $wpdb->escape($player['division_id']),	// int
										'pdga_number' => $wpdb->escape($player['pdga_number']), // int
								),
								array( 'id' => $config['id'] ),
								array(
										'%s', // string
										'%s', // string
										'%s', // string
										'%d', // int
										'%d', // int
								),
								array( '%d' )

						) === false ) {

							// Error
							$GLOBALS['CH_SysMessages']->collectResponse('player_update_failed');

						} else {

							// Success
							$GLOBALS['CH_SysMessages']->collectResponse('player_updated');

							// Get Player
							$player = $this->getPlayer($config['id']);

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
			<h2><?php echo __($modifier.' Player'); ?></h2>

			<form id="clubhouse-player-form" method="post" action="?page=<?php echo $_GET['page']; ?>&control=players">

				<?php
				global $clubhouse_nonce;
				clubhouse_nonce_field($clubhouse_nonce); // form validation ?>
				<input type="hidden" name="id" value="<?php echo $player['id']; ?>">
				<input type="hidden" name="action" value="<?php echo $config['action']; ?>">
				<input type="hidden" name="form_check" value="player">

				<div class="x3">
					<label>First Name *</label>
					<input type="text" name="first_name" value="<?php echo $player['first_name']; ?>">
				</div>

				<div class="x3">
					<label>Last Name *</label>
					<input type="text" name="last_name" value="<?php echo $player['last_name']; ?>">
				</div>

				<div class="x3">
					<label>Email *</label>
					<input type="text" name="email" value="<?php echo $player['email']; ?>">
				</div>

				<div class="x3">
					<label>Default Division *</label>
					<select name="division_id">
						<option value="">Choose One</option>
						<?php foreach ($this->divisions as $division) { ?>
							<?php $selected = ($player['division_id'] == $division['id']) ? ' selected' : '' ; ?>
							<option value="<?php echo $division['id']; ?>"<?php echo $selected; ?>><?php echo $division['division_name']; ?></option>
						<?php } ?>
					</select>
				</div>

				<div class="x3">
					<label>PDGA #</label>
					<input type="text" name="pdga_number" value="<?php echo $player['pdga_number']; ?>">
				</div>

				<div class="clearer">
					<input type="submit" name="submit" value="<?php echo __($modifier.' Player'); ?>">
				</div>

			</form>

		</div>

<?php

		$output = ob_get_clean();
		return $output;

	}


	/**
	 * Get List of Players
	 * @param array config
	 * @purpose flexible selection of players
	 */
	function getPlayerList($config = array()) {
		global $wpdb;

		// Delete Player
		if ( $config['action'] == 'delete' && !empty($config['id']) && is_numeric($config['id']) ) {
			if (!$wpdb->query( "DELETE FROM `" . CLUBHOUSE_TABLE_PLAYERS . "` WHERE `id` = '" . $config['id'] . "';")) {
				$GLOBALS['CH_SysMessages']->collectResponse('player_delete_failed');
			} else {
				$GLOBALS['CH_SysMessages']->collectResponse('player_deleted');
			}
		}

		// Get Players Query
		$query = "
			SELECT `p`.*, `d`.`division_name`
			FROM `" . CLUBHOUSE_TABLE_PLAYERS . "` AS `p`
			JOIN `" . CLUBHOUSE_TABLE_DIVISIONS . "` AS `d`
			ON `p`.`division_id` = `d`.`id`
			ORDER BY `d`.`order` ASC, `p`.`last_name` ASC
		";
		if (!empty($config['query'])) $query = $config['query'];
		$players = $this->getPlayers($query);

		// Organize Players
		$player_list = array();
		if (!empty($players)) {
			foreach ($players as $player) {
				$division_name = !empty($player['division_name']) ? $player['division_name'] : 'No Division';
				$player_list[$division_name][] = $player;
			}
		}

		// Display Player List
		ob_start();
?>
		<div id="clubhouse-player-list">

			<h2><?php echo __('All Players'); ?> [<a href="?page=<?php echo $_GET['page']; ?>&control=players&action=add">Add one</a>]</h2>

			<?php if (!empty($player_list)) { ?>
			<?php foreach($player_list as $division => $players) { ?>
			<table id="clubhouse-<?php echo strtolower(str_replace(' ', '-', $division)); ?>" class="clubhouse-table" cellspacing="0">

				<thead>
					<tr class="clubhouse-list-heading">
						<th colspan="4"><?php echo $division; ?></th>
					</tr>
					<tr class="clubhouse-list-columns">
						<td style="width:250px;">Name</td>
						<td>Email</td>
						<td style="width:65px;">PDGA #</td>
						<td style="width:65px">&nbsp;</td>
					</tr>
				</thead>
				<tbody>

					<?php foreach ($players as $player) { ?>
					<tr>
						<td><?php echo $player['first_name']; ?> <?php echo $player['last_name']; ?></td>
						<td><?php echo $player['email']; ?></td>
						<td><?php echo (!empty($player['pdga_number']) ? $player['pdga_number'] : '&nbsp;'); ?></td>
						<td>
							<a href="?page=<?php echo $_GET['page']; ?>&control=players&action=edit&id=<?php echo $player['id']; ?>">edit</a> |
							<a href="?page=<?php echo $_GET['page']; ?>&control=players&action=delete&id=<?php echo $player['id']; ?>" onclick="return confirm('Are you sure you want to delete this player?');">delete</a>
						</td>
					</tr>
					<?php } ?>

				</tbody>

			</table>
			<?php }} ?>

		</div>
<?php
		$output = ob_get_clean();
		return $output;

	}

}


/****** AJAX ******/

add_action( 'admin_footer', 'players_ajax_javascript' );
function players_ajax_javascript() {
	?>
<script type="text/javascript" >
jQuery(document).ready(function($) {

	// Player Score Character Limiting
	$('input[name="pdga_number"]').bind('keyup', function() {
		$(this).val($(this).val().replace(/[^0-9]/g, ""));
	});

});
</script>
<?php
}
?>