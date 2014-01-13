<?php

/**
 * Tournament Directors (TD)
 * @author Brad
 *
 * Methods:
 * - getTD
 * - getTDs
 * - confirmDuplicate
 * - manageTD
 * - getTDList
 *
 */

class Directors {

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
				'director_select_failed'	=> array('type' => 'error', 	'message' => __('Could not locate selected director. <a href="?page=clubhouse-config">View Director List</a>')),
				'director_update_failed'	=> array('type' => 'error', 	'message' => __('Could not update director information. Please try again.')),
				'director_updated'	    	=> array('type' => 'updated', 	'message' => __('Director information updated.')),
				'director_insert_failed'	=> array('type' => 'error', 	'message' => __('Director could not be created.')),
				'director_inserted'		   	=> array('type' => 'success', 	'message' => __('Director created.')),
				'director_delete_failed'	=> array('type' => 'error', 	'message' => __('Director could not be deleted.')),
				'director_deleted'		   	=> array('type' => 'updated', 	'message' => __('Director deleted.')),
				'director_failed_confirm'	=> array('type' => 'error', 	'message' => __('Could not confirm if director already exists.')),
				'director_failed_duplicate'	=> array('type' => 'error', 	'message' => __('Director already exists.')),
				'director_no_first_name'	=> array('type' => 'error', 	'message' => __('First Name is required.')),
				'director_no_last_name'		=> array('type' => 'error', 	'message' => __('Last Name is required.')),
				'director_no_email'			=> array('type' => 'error', 	'message' => __('Email is required.')),
				'director_no_phone_number'	=> array('type' => 'error', 	'message' => __('Phone Number is required.')),
		));

	}

	/**
	 * Get Director
	 * @param int $id
	 */
	function getDirector($id) {
		global $wpdb;
		$director = $wpdb->get_row(
				"SELECT * FROM `" . CLUBHOUSE_TABLE_DIRECTORS . "` WHERE `id` = '" . $wpdb->escape($id) . "';", 'ARRAY_A'
		);
		if (!empty($wpdb->error)) {
			$GLOBALS['CH_SysMessages']->collectResponse('director_select_failed');
		}
		return $director;
	}

	/**
	 * Get Directors
	 * @param optional string $query
	 */
	function getDirectors($query = '') {
		global $wpdb;
		$query = !empty($query) ? $query : "SELECT * FROM `" . CLUBHOUSE_TABLE_DIRECTORS . "`;";
		$directors = $wpdb->get_results($query, 'ARRAY_A');
		if (!empty($wpdb->error)) {
			$GLOBALS['CH_SysMessages']->collectResponse('director_select_failed');
		}
		return $directors;
	}

	/**
	 * Confirm Director Exists
	 * @param unknown_type $config
	 */
	function confirmDuplicate($config = array()) {
		global $wpdb;
		$director = $wpdb->get_row(
				"SELECT `id` FROM `" . CLUBHOUSE_TABLE_DIRECTORS . "` WHERE
				`first_name` = '" . $wpdb->escape($config['first_name']) . "' AND
				`last_name`  = '" . $wpdb->escape($config['last_name']) . "' AND
				`email`  	 = '" . $wpdb->escape($config['email'])  . "';"
		);
		if (!empty($wpdb->error)) {
			$GLOBALS['CH_SysMessages']->collectResponse('director_failed_confirm');
		}
		$confirmed = (!empty($director)) ? true : false;
		return $confirmed;
	}

	/**
	 * Manage Directors
	 * @param array $config
	 * @return html $output
	 */
	function manageDirector($config = array()) {
		if (empty($config)) return;
		global $wpdb;

		// Get Director
		$director = array(
			'id'=>'',
			'first_name'=>'',
			'last_name'=>'',
			'email'=>'',
			'phone_number'=>'',
		);
		if ($config['action'] == 'edit' && !empty($config['id'])) {
			$director = $this->getDirector($config['id']);
		}

		// Confirm Submission
		if ( !empty($_POST['submit']) ) {

			// Validate Referrer
			global $clubhouse_nonce;
			check_admin_referer( $clubhouse_nonce );
			if ( !empty($_POST['form_check']) && $_POST['form_check'] == 'director') {

				// Set Form Vars
				$fields = array('first_name', 'last_name', 'email', 'phone_number');
				foreach ($fields as $field) {
					$director[$field] = (isset($_POST[$field])) ? $_POST[$field] : '';
				}

				// Check Required
				if (empty($director['first_name'])) 	$GLOBALS['CH_SysMessages']->collectResponse('director_no_first_name');
				if (empty($director['last_name'])) 		$GLOBALS['CH_SysMessages']->collectResponse('director_no_last_name');
				if (empty($director['email'])) 			$GLOBALS['CH_SysMessages']->collectResponse('director_no_email');
				if (empty($director['phone_number'])) 	$GLOBALS['CH_SysMessages']->collectResponse('director_no_phone_number');

				// Proceed if No Errors
				if (empty($GLOBALS['CH_SysMessages']->responses)) {

					// Add Director
					if ( $_POST['action'] == 'add' ) {

						// Check for Duplicate
						$check_director = $this->confirmDuplicate(array(
							'first_name'	=> $wpdb->escape($director['first_name']),
							'last_name'		=> $wpdb->escape($director['last_name']),
							'email'			=> $wpdb->escape($director['email']),
							'phone_number'	=> $wpdb->escape($director['phone_number']),
						));

						// Insert Director
						if (empty($check_director)) {

							if (!$wpdb->query(
								"INSERT INTO `" . CLUBHOUSE_TABLE_DIRECTORS . "` SET
								`first_name`	= '" . $wpdb->escape($director['first_name']) . "',
								`last_name`			= '" . $wpdb->escape($director['last_name'])   	 . "',
								`email`       	= '" . $wpdb->escape($director['email'])       . "',
								`phone_number`       = '" . $wpdb->escape($director['phone_number'])     . "';"
							)
							) {

								// Error
								$GLOBALS['CH_SysMessages']->collectResponse('director_insert_failed');

							} else {

								// Success
								$GLOBALS['CH_SysMessages']->collectResponse('director_inserted');

								// Get Director and Set Action
								$lastid = $wpdb->insert_id;
								$director = $this->getDirector($lastid);
								$config['action'] = 'edit';

							}

						// Director Already Registered
						} else {
							$GLOBALS['CH_SysMessages']->collectResponse('director_failed_duplicate');
						}

					// Update Director
					} elseif ( $_POST['action'] == 'edit' ) {

						if ($wpdb->update(

							CLUBHOUSE_TABLE_DIRECTORS,
							array(
								'first_name'  	=> $wpdb->escape($director['first_name']),		// string
								'last_name'   	=> $wpdb->escape($director['last_name']),		// string
								'email'       	=> $wpdb->escape($director['email']),	  		// string
								'phone_number' 	=> $wpdb->escape($director['phone_number']),	// string
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
							$GLOBALS['CH_SysMessages']->collectResponse('director_update_failed');

						} else {

							// Success
							$GLOBALS['CH_SysMessages']->collectResponse('director_updated');

							// Get Director
							$director = $this->getDirector($config['id']);

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
				<h2><?php echo __($modifier.' Director'); ?></h2>

				<form id="clubhouse-director-form" method="post" action="?page=<?php echo $_GET['page']; ?>&control=directors">

					<?php
					global $clubhouse_nonce;
					clubhouse_nonce_field($clubhouse_nonce); // form validation ?>
					<input type="hidden" name="id" value="<?php echo $director['id']; ?>">
					<input type="hidden" name="action" value="<?php echo $config['action']; ?>">
					<input type="hidden" name="form_check" value="director">

					<div class="x3">
						<label>First Name *</label>
						<input type="text" name="first_name" value="<?php echo $director['first_name']; ?>">
					</div>

					<div class="x3">
						<label>Last Name *</label>
						<input type="text" name="last_name" value="<?php echo $director['last_name']; ?>">
					</div>

					<div class="x3">
						<label>Email *</label>
						<input type="text" name="email" value="<?php echo $director['email']; ?>">
					</div>

					<div class="x3">
						<label>Phone Number</label>
						<input type="text" name="phone_number" value="<?php echo $director['phone_number']; ?>">
					</div>

					<div class="clearer">
						<input type="submit" name="submit" value="<?php echo __($modifier.' Director'); ?>">
					</div>

				</form>

			</div>
		<?php

		$output = ob_get_clean();
		return $output;

	}


	/**
	 * Get List of Directors
	 * @param array config
	 * @purpose flexible selection of directors
	 */
	function getDirectorList($config = array()) {
		global $wpdb;

		// Delete Director
		if ( $config['action'] == 'delete' && !empty($config['id']) && is_numeric($config['id']) ) {
			if (!$wpdb->query( "DELETE FROM `" . CLUBHOUSE_TABLE_DIRECTORS . "` WHERE `id` = '" . $config['id'] . "';")) {
				$GLOBALS['CH_SysMessages']->collectResponse('director_delete_failed');
			} else {
				$GLOBALS['CH_SysMessages']->collectResponse('director_deleted');
			}
		}

		// Get Directors Query
		$query = ""; // use this for custom pull of info
		if (!empty($config['query'])) $query = $config['query'];
		$directors = $this->getDirectors($query);

		// Organize Directors
		$director_list = array();
		if (!empty($directors)) {
			foreach ($directors as $director) {
				$director_list[] = $director;
			}
		}

		// Display Director List
		ob_start();
		?>
		<div id="clubhouse-director-list">

			<h2><?php echo __('All Directors'); ?> [<a href="?page=<?php echo $_GET['page']; ?>&control=directors&action=add">Add one</a>]</h2>

			<?php if (!empty($director_list)) { ?>
			<table id="clubhouse-directors" class="clubhouse-table" cellspacing="0">

				<thead>
					<tr class="clubhouse-list-columns">
						<td style="width:100px;">First Name</td>
						<td style="width:100px;">Last Name</td>
						<td style="width:250px;">Email</td>
						<td>Phone Number</td>
						<td style="width:65px">&nbsp;</td>
					</tr>
				</thead>
				<tbody>
					<?php foreach($director_list as $director) { ?>
					<tr>
						<td><?php echo $director['first_name']; ?></td>
						<td><?php echo $director['last_name']; ?></td>
						<td><?php echo $director['email']; ?></td>
						<td><?php echo $director['phone_number']; ?></td>
						<td class="clubhouse-list-controls">
							<a href="?page=<?php echo $_GET['page']; ?>&control=directors&action=edit&id=<?php echo $director['id']; ?>">edit</a> |
							<a href="?page=<?php echo $_GET['page']; ?>&control=directors&action=delete&id=<?php echo $director['id']; ?>" onclick="return confirm('Are you sure you want to delete this director?');">delete</a>
						</td>
					</tr>
					<?php } ?>
				</tbody>

			</table>
			<?php } ?>

		</div>
	<?php
		$output = ob_get_clean();
		return $output;

	}

}
