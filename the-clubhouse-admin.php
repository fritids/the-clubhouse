<?php

/**
 * The Clubhouse v0.01
 * -------------------
 *
 * Tools
 * - Player Manager
 * - Event Manager (weekly leagues, tournaments)
 *   - Score Entry System
 *   - Points Calculator
 *   - Handicap Calculator
 * - Bag Tags
 *   - Tracks opening tag per player, and closing tag after end of year tourney.
 *
 * To Do:
 * - Transfer in tools from existing plugins, and well, everything... HAVE FUN!!
 *
 */

// Make sure we don't expose any info if called directly
if ( !function_exists( 'add_action' ) ) {
	echo "Hi there!  I'm just a plugin, not much I can do when called directly.";
	exit;
}
// Menu Link to Config Page
function clubhouse_admin_config_page() {
	add_menu_page( __('The Clubhouse'), __('The Clubhouse'), 'manage_options', 'clubhouse-config', 'clubhouse_admin_conf');
}
add_action( 'admin_menu', 'clubhouse_admin_config_page' );

// Settings Link with Description
function clubhouse_plugin_action_links( $links, $file ) {
	if ( $file == plugin_basename( dirname(__FILE__).'/the-clubhouse.php' ) ) {
		$links[] = '<a href="plugins.php?page=clubhouse-config">'.__('Manage').'</a>';
	}
	return $links;
}
add_filter( 'plugin_action_links', 'clubhouse_plugin_action_links', 10, 2 );

// Initialize the Admin Page (captain!)
function clubhouse_admin_init() {
	global $wp_version;

	// all admin functions are disabled in old versions
	// (Optional ?) Additional Check:!function_exists('is_multisite')
	if ( version_compare( $wp_version, MIN_WP_VERSION, '<' ) ) {

		// notify user version upgrade required
		function clubhouse_version_warning() {
			echo "
			<div id='clubhouse-warning' class='updated fade'><p><strong>".sprintf(__('League Scores %s requires WordPress ' . MIN_WP_VERSION . ' or higher.'), LEAGUESCORES_VERSION) ."</strong> ".sprintf(__('Please <a href="%s">upgrade WordPress</a> to a current version.'), 'http://codex.wordpress.org/Upgrading_WordPress'). "</p></div>
			";
		}
		add_action('admin_notices', 'clubhouse_version_warning');

		return;
	}

	// add styles and scripts
	wp_register_style('the-clubhouse.css.php', CLUBHOUSE_PLUGIN_URL . 'the-clubhouse.css.php');
	wp_enqueue_style('the-clubhouse.css.php');
	wp_register_style('jqueryUIstyles', 'http://code.jquery.com/ui/1.10.3/themes/smoothness/jquery-ui.css');
	wp_enqueue_style('jqueryUIstyles');

	wp_enqueue_script('jquery');
	wp_register_script("jqueryUI","http://code.jquery.com/ui/1.10.3/jquery-ui.js");
	wp_enqueue_script('jqueryUI');
	wp_register_script('the-clubhouse.js', CLUBHOUSE_PLUGIN_URL . 'the-clubhouse.js', array('jquery'));
	wp_enqueue_script('the-clubhouse.js');

}
add_action('admin_init', 'clubhouse_admin_init');

// Admin Page Handler
function clubhouse_admin_conf() {

	$start_memory = memory_get_usage() / 1024 / 1024;

	// System Title
	$clubhouse_title = '<h1>' . __('The Clubhouse') . '</h1>';

	// Navigation
	$clubhouse_nav_links = array(
		array('title' => 'Players', 	 'slug' => 'players'),
		array('title' => 'Events', 		 'slug' => 'events'),
		array('title' => 'Divisions', 	 'slug' => 'divisions'),
		array('title' => 'Registration', 'slug' => 'registration'),
	);
	$clubhouse_nav = '<div id="clubhouse-nav">';
	$clubhouse_nav .= '<ul>';
	foreach ($clubhouse_nav_links as $nav) {
		$clubhouse_nav_current = ($_GET['control'] == $nav['slug']) ? ' class="current"' : '';
		$clubhouse_nav .= '<li' . $clubhouse_nav_current . '><a href="?page='.$_GET['page'].'&control=' . $nav['slug'] . '">' . $nav['title'] . '</a></li>';
	} unset($nav);
	$clubhouse_nav .= '</ul>';
	$clubhouse_nav .= '</div>';


	// Control Sets
	$clubhouse_controlset = '<div id="the-clubhouse">';
	if (!empty($_GET['control'])) {

		// What Action?
		$action = !empty($_REQUEST['action']) ? $_REQUEST['action'] : '';

		// Record?
		$id = !empty($_REQUEST['id']) && is_numeric($_REQUEST['id']) ? $_REQUEST['id'] : '';

		// Call Controlset
		// TODO: Simply this call system, perhaps with a wrapper class
		switch($_GET['control']) {

			case "divisions":

				if ($action == 'add') {
					$clubhouse_controlset .= $GLOBALS['CH_Divisions']->manageDivision(array('action' => 'add'));

				} elseif ($action == 'edit' && !empty($id)) {
					$clubhouse_controlset .= $GLOBALS['CH_Divisions']->manageDivision(array('action' => 'edit', 'id' => $id));

				} else {
					$clubhouse_controlset .= $GLOBALS['CH_Divisions']->getDivisionList(array('action' => ($_GET['action'] == 'delete' ? 'delete' : ''), 'id' => $id));

				}

				break;

			case "players":

				if ($action == 'add') {
					$clubhouse_controlset .= $GLOBALS['CH_Players']->managePlayer(array('action' => 'add'));

				} elseif ($action == 'edit' && !empty($id)) {
					$clubhouse_controlset .= $GLOBALS['CH_Players']->managePlayer(array('action' => 'edit', 'id' => $id));

				} else {
					$clubhouse_controlset .= $GLOBALS['CH_Players']->getPlayerList(array('action' => ($_GET['action'] == 'delete' ? 'delete' : ''), 'id' => $id));

				}
				break;

			case "events":

				if ($action == 'add') {
					$clubhouse_controlset .= $GLOBALS['CH_Events']->manageEvent(array('action' => 'add'));

				} elseif ($action == 'edit' && !empty($id)) {
					$clubhouse_controlset .= $GLOBALS['CH_Events']->manageEvent(array('action' => 'edit', 'id' => $id));

				} else {
					$clubhouse_controlset .= $GLOBALS['CH_Events']->getEventList(array('action' => ($_GET['action'] == 'delete' ? 'delete' : ''), 'id' => $id));

				}
				break;


		}

	}
	$clubhouse_controlset .= '</div>';

	// Message Output
	$clubhouse_messages = '';
	$clubhouse_msgs = $GLOBALS['CH_SysMessages']->returnMessages();
	if ( !empty($clubhouse_msgs) ) {

		$clubhouse_messages .= '<div id="messages">';
		foreach ($clubhouse_msgs as $type => $info) {
			$clubhouse_messages .= '<div class="' . $type . '">';
			$clubhouse_messages .= '<p><strong>' . implode('</strong></p><p><strong>',$info) . '</strong></p>';
			$clubhouse_messages .= '</div>';
		}
		$clubhouse_messages .= '</div>';
	}

	// Output
	echo $clubhouse_title . $clubhouse_messages . $clubhouse_nav . $clubhouse_controlset;

	// Memory Check

	$end_memory = memory_get_usage() / 1024 / 1024;
	$peak_memory = memory_get_peak_usage() / 1024 / 1024;
	echo "<p>Start: " . $start_memory . "<br />End: " . $end_memory . "<br />Used: " . ($end_memory - $start_memory) . " MB<br />Peak: " . $peak_memory . "</p>";


} // end clubhouse_admin_conf()
?>