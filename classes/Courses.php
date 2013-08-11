<?php

/**
 * Courses
 * @author Brad
 *
 * Methods:
 * - getCourse
 * - getCourses
 * - confirmDuplicate
 * - manageCourse
 * - getCourseList
 *
 */

class Courses {

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
				'course_select_failed'		=> array('type' => 'error', 	'message' => __('Could not locate selected course. <a href="?page=clubhouse-config">View Course List</a>')),
				'course_update_failed'	   	=> array('type' => 'error', 	'message' => __('Could not update course information. Please try again.')),
				'course_updated'	    	=> array('type' => 'updated', 	'message' => __('Course information updated.')),
				'course_insert_failed'	   	=> array('type' => 'error', 	'message' => __('Course could not be created.')),
				'course_inserted'		   	=> array('type' => 'success', 	'message' => __('Course created.')),
				'course_delete_failed'	   	=> array('type' => 'error', 	'message' => __('Course could not be deleted.')),
				'course_deleted'		   	=> array('type' => 'updated', 	'message' => __('Course deleted.')),
				'course_failed_confirm'		=> array('type' => 'error', 	'message' => __('Could not confirm if course already exists.')),
				'course_failed_duplicate'	=> array('type' => 'error', 	'message' => __('Course already exists.')),
				'course_no_course_name'		=> array('type' => 'error', 	'message' => __('Course Name is required.')),
				'course_no_city'			=> array('type' => 'error', 	'message' => __('City is required.')),
				'course_no_state'			=> array('type' => 'error', 	'message' => __('State is required.')),
				'course_no_country'			=> array('type' => 'error', 	'message' => __('Country is required.')),
		));

	}

	/**
	 * Get Course
	 * @param int $id
	 */
	function getCourse($id) {
		global $wpdb;
		$course = $wpdb->get_row(
				"SELECT * FROM `" . CLUBHOUSE_TABLE_COURSES . "` WHERE `id` = '" . $wpdb->escape($id) . "';", 'ARRAY_A'
		);
		if (!empty($wpdb->error)) {
			$GLOBALS['CH_SysMessages']->collectResponse('course_select_failed');
		}
		return $course;
	}

	/**
	 * Get Courses
	 * @param optional string $query
	 */
	function getCourses($query = '') {
		global $wpdb;
		$query = !empty($query) ? $query : "SELECT * FROM `" . CLUBHOUSE_TABLE_COURSES . "`;";
		$courses = $wpdb->get_results($query, 'ARRAY_A');
		if (!empty($wpdb->error)) {
			$GLOBALS['CH_SysMessages']->collectResponse('course_select_failed');
		}
		return $courses;
	}

	/**
	 * Confirm Course Exists
	 * @param unknown_type $config
	 */
	function confirmDuplicate($config = array()) {
		global $wpdb;
		$course = $wpdb->get_row(
				"SELECT `id` FROM `" . CLUBHOUSE_TABLE_COURSES . "` WHERE
				`course_name` = '" . $wpdb->escape($config['course_name']) . "' AND
				`city`  	  = '" . $wpdb->escape($config['city'])  . "';"
		);
		if (!empty($wpdb->error)) {
			$GLOBALS['CH_SysMessages']->collectResponse('course_failed_confirm');
		}
		$confirmed = (!empty($course)) ? true : false;
		return $confirmed;
	}

	/**
	* Manage Courses
	* @param array $config
	* @return html $output
	*/
	function manageCourse($config = array()) {
		if (empty($config)) return;
		global $wpdb;

		// Get Course
		$course = array(
			'id'=>'',
			'course_name'=>'',
			'city'=>'',
			'state'=>'',
			'country'=>'',
		);
		if ($config['action'] == 'edit' && !empty($config['id'])) {
			$course = $this->getCourse($config['id']);
		}

		// Confirm Submission
		if ( !empty($_POST['submit']) ) {

			// Validate Referrer
			global $clubhouse_nonce;
			check_admin_referer( $clubhouse_nonce );
			if ( !empty($_POST['form_check']) && $_POST['form_check'] == 'course') {

			// Set Form Vars
			$fields = array('course_name', 'city', 'state', 'country');
			foreach ($fields as $field) {
				$course[$field] = (isset($_POST[$field])) ? $_POST[$field] : '';
			}

			// Check Required
			if (empty($course['course_name'])) 	$GLOBALS['CH_SysMessages']->collectResponse('course_no_course_name');
			if (empty($course['city'])) 		$GLOBALS['CH_SysMessages']->collectResponse('course_no_city');
			if (empty($course['state'])) 		$GLOBALS['CH_SysMessages']->collectResponse('course_no_state');
			if (empty($course['country'])) 		$GLOBALS['CH_SysMessages']->collectResponse('course_no_country');

			// Proceed if No Errors
			if (empty($GLOBALS['CH_SysMessages']->responses)) {

				// Add Course
				if ( $_POST['action'] == 'add' ) {

					// Check for Duplicate
					$check_course = $this->confirmDuplicate(array(
						'course_name'	=> $wpdb->escape($course['course_name']),
						'city'  		=> $wpdb->escape($course['city']),
					));

					// Insert Course
					if (empty($check_course)) {

						if (!$wpdb->query(
								"INSERT INTO `" . CLUBHOUSE_TABLE_COURSES . "` SET
								`course_name`  	= '" . $wpdb->escape($course['course_name']) . "',
								`city`   		= '" . $wpdb->escape($course['city'])   	 . "',
								`state`       	= '" . $wpdb->escape($course['state'])       . "',
								`country`       = '" . $wpdb->escape($course['country'])     . "';"
							)
							) {

								// Error
								$GLOBALS['CH_SysMessages']->collectResponse('course_insert_failed');

							} else {

								// Success
								$GLOBALS['CH_SysMessages']->collectResponse('course_inserted');

								// Get Course and Set Action
								$lastid = $wpdb->insert_id;
								$course = $this->getCourse($lastid);
								$config['action'] = 'edit';

							}

						// Course Already Registered
						} else {
							$GLOBALS['CH_SysMessages']->collectResponse('course_failed_duplicate');
						}

					// Update Course
					} elseif ( $_POST['action'] == 'edit' ) {

						if ($wpdb->update(

							CLUBHOUSE_TABLE_COURSES,
							array(
								'course_name'  	=> $wpdb->escape($course['course_name']),	// string
								'city'   		=> $wpdb->escape($course['city']),			// string
								'state'       	=> $wpdb->escape($course['state']),	  		// string
								'country' 		=> $wpdb->escape($course['country']),		// string
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
							$GLOBALS['CH_SysMessages']->collectResponse('course_update_failed');

						} else {

							// Success
							$GLOBALS['CH_SysMessages']->collectResponse('course_updated');

							// Get Course
							$course = $this->getCourse($config['id']);

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
				<h2><?php echo __($modifier.' Course'); ?></h2>

				<form id="clubhouse-course-form" method="post" action="?page=<?php echo $_GET['page']; ?>&control=courses">

					<?php
					global $clubhouse_nonce;
					clubhouse_nonce_field($clubhouse_nonce); // form validation ?>
					<input type="hidden" name="id" value="<?php echo $course['id']; ?>">
					<input type="hidden" name="action" value="<?php echo $config['action']; ?>">
					<input type="hidden" name="form_check" value="course">

					<div class="x3">
						<label>Course Name *</label>
						<input type="text" name="course_name" value="<?php echo $course['course_name']; ?>">
					</div>

					<div class="x3">
						<label>City *</label>
						<input type="text" name="city" value="<?php echo $course['city']; ?>">
					</div>

					<div class="x3">
						<label>State *</label>
						<input type="text" name="state" value="<?php echo $course['state']; ?>">
					</div>

					<div class="x3">
						<label>Country</label>
						<input type="text" name="country" value="<?php echo $course['country']; ?>">
					</div>

					<div class="clearer">
						<input type="submit" name="submit" value="<?php echo __($modifier.' Course'); ?>">
					</div>

				</form>

			</div>
		<?php

		$output = ob_get_clean();
		return $output;

	}


	/**
	 * Get List of Courses
	 * @param array config
	 * @purpose flexible selection of courses
	 */
	function getCourseList($config = array()) {
		global $wpdb;

		// Delete Course
		if ( $config['action'] == 'delete' && !empty($config['id']) && is_numeric($config['id']) ) {
			if (!$wpdb->query( "DELETE FROM `" . CLUBHOUSE_TABLE_COURSES . "` WHERE `id` = '" . $config['id'] . "';")) {
				$GLOBALS['CH_SysMessages']->collectResponse('course_delete_failed');
			} else {
				$GLOBALS['CH_SysMessages']->collectResponse('course_deleted');
			}
		}

		// Get Courses Query
		$query = ""; // use this for custom pull of info
		if (!empty($config['query'])) $query = $config['query'];
		$courses = $this->getCourses($query);

		// Organize Courses
		$course_list = array();
		if (!empty($courses)) {
			foreach ($courses as $course) {
				$course_list[] = $course;
			}
		}

		// Display Course List
		ob_start();
		?>
		<div id="clubhouse-course-list">

			<h2><?php echo __('All Courses'); ?> [<a href="?page=<?php echo $_GET['page']; ?>&control=courses&action=add">Add one</a>]</h2>

			<?php if (!empty($course_list)) { ?>

			<table id="clubhouse-courses" class="clubhouse-table" cellspacing="0">

				<thead>
					<tr class="clubhouse-list-columns">
						<td style="width:250px;">Course Name</td>
						<td style="width:65px;">City</td>
						<td style="width:100px;">State/Prov</td>
						<td style="width:100px;">Country</td>
						<td style="width:65px">&nbsp;</td>
					</tr>
				</thead>
				<tbody>
					<?php foreach($course_list as $course) { ?>
					<tr>
						<td><?php echo $course['course_name']; ?></td>
						<td><?php echo $course['city']; ?></td>
						<td><?php echo $course['state']; ?></td>
						<td><?php echo $course['country']; ?></td>
						<td>
							<a href="?page=<?php echo $_GET['page']; ?>&control=courses&action=edit&id=<?php echo $course['id']; ?>">edit</a> |
							<a href="?page=<?php echo $_GET['page']; ?>&control=courses&action=delete&id=<?php echo $course['id']; ?>" onclick="return confirm('Are you sure you want to delete this course?');">delete</a>
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

