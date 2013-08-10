<?php

/**
 * Registation
 * @author Brad
 * - Build event picker
 * - Build registration form output
 * - Build registration list manager (pull from older plugin)
 */
class Registration {

	/**
	 * Events
	 * @var array
	 */
	private $events = array();

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
				'event_select_failed'	=> array('type' => 'error', 	'message' => __('Could not locate selected event. <a href="?page=' . $_GET['page'] . '&control=events">View Event List</a>')),
				'update_failed'	   		=> array('type' => 'error', 	'message' => __('Could not update event information. Please try again.')),
				'event_updated'	   		=> array('type' => 'updated', 	'message' => __('Event information updated.')),
				'insert_failed'	   		=> array('type' => 'error', 	'message' => __('Event could not be created.')),
				'inserted'		   		=> array('type' => 'success', 	'message' => __('Event created.')),
				'delete_failed'	   		=> array('type' => 'error', 	'message' => __('Event could not be deleted.')),
				'deleted'		   		=> array('type' => 'updated', 	'message' => __('Event deleted.')),
				'failed_confirm'		=> array('type' => 'error', 	'message' => __('Could not confir m if event already exists.')),
				'failed_duplicate'		=> array('type' => 'error', 	'message' => __('Event already exists.')),
				'no_name'				=> array('type' => 'error', 	'message' => __('You must provide a name.')),
				'no_divisions'			=> array('type' => 'error', 	'message' => __('You must select at least one division.')),
				'no_itterator'			=> array('type' => 'error', 	'message' => __('You must select an itterator.')),
				'no_duration'			=> array('type' => 'error', 	'message' => __('You must provide a duration.')),
		));

		// Get Divisions
		$this->divisions = $GLOBALS['CH_Divisions']->getDivisions();

		// Get Events
		$this->events = $GLOBALS['CH_Events']->getEvents();

	}

	/**
	 * Get Registered Players
	 */
	function getRegistered($config = array()) {

		// outputs list of players currenly registered to the event

	}

	/**
	 * Manage Registered Players
	 * @param array $config
	 */
	function manageRegistration($config = array()) {

		// confirm player is registered
		// flag player as cancelled

	}

	/**
	 * Get Event Registration Form
	 * @param array $config
	 */
	function getRegistrationForm($config = array()) {

	}

	function sendNotification() {

	}

}