<?php

class SysMessages {

	/**
	 * Collect Responses
	 * @var array
	 */
	public $responses = array();

	/**
	 * System Messages
	 * @var array
	 */
	public $messages = array();

	/**
	 * Constructor
	 * @param array $config
	 */
	function __construct($config = array()) {

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
		return;
	}

	/**
	 * Return Messages
	 * @return void
	 */
	function returnMessages() {
		if (empty($this->messages) && empty($this->responses)) return;

		// Gather Messages
		$messages = array();
		foreach ( $this->responses as $response ) {

			// Get Type of Message (e.g. error, success)
			$type = $this->messages[$response]['type'];

			// Organize Messages
			$messages[$type] = array_key_exists($type, $messages) ? $messages[$type] : array();
			$messages[$type][] = $this->messages[$response]['message'];

		}

		// Return Messages
		$return = !empty($messages) ? $messages : '';
		return $return;
	}

	/**
	 * Store Messages
	 * @param string $message
	 */
	function storeMessages($messages) {
		$messages = is_array($messages) ? $messages : explode(',',$messages);
		foreach ($messages as $message_code => $message) $this->messages[$message_code] = $message;
		return;
	}

	/**
	 * Store Message Code
	 * @param string $message_code
	 */
	function collectResponse($response) {
		$this->responses[] = $response;
		return;
	}

}

?>