<?php
// $Id: stationary.inc.php,v 1.9.3 2011/02/05 12:43:00 Logue Exp $
//
// Stationary plugin
// License: The same as PukiWiki

// Define someting like this
define('PLUGIN_STATIONARY_MAX', 10);

// Init someting
function plugin_stationary_init()
{
	// if (PKWK_SAFE_MODE || PKWK_READONLY) return; // Do nothing
	if (auth::check_role('safemode') || auth::check_role('readonly')) return; // Do nothing

	$messages = array(
		'_plugin_stationary_A' => 'a',
		'_plugin_stationary_B' => array('C' => 'c', 'D'=>'d'),
		);
	set_plugin_messages($messages);
}

// Convert-type plugin: #stationary or #stationary(foo)
function plugin_stationary_convert()
{
	// If you don't want this work at secure/productive site,
	// if (PKWK_SAFE_MODE) return ''; // Show nothing
	if (auth::check_role('safemode')) return ''; // Show nothing

	// If this plugin will write someting,
	// if (PKWK_READONLY) return ''; // Show nothing
	if (auth::check_role('readonly')) return ''; // Show nothing

	// Init
	$args = array();
	$result = '';

	// Get arguments
	if (func_num_args()) {
		$args = func_get_args();
		foreach	(array_keys($args) as $key)
			$args[$key] = trim($args[$key]);
		$result = join(',', $args);
	}

	return '#stationary(' . htmlsc($result) . ')<br />';
}

// In-line type plugin: &stationary; or &stationary(foo); , or &stationary(foo){bar};
function plugin_stationary_inline()
{
	// if (PKWK_SAFE_MODE || PKWK_READONLY) return ''; // See above
	if (auth::check_role('safemode') || auth::check_role('readonly')) return ''; // See above

	// {bar} is always exists, and already sanitized
	$args = func_get_args();
	$body = strip_autolink(array_pop($args)); // {bar}

	foreach	(array_keys($args) as $key)
		$args[$key] = trim($args[$key]);
	$result = join(',', $args);

	return '&amp;stationary(' . htmlsc($result) . '){' . $body . '};';
}

// Action-type plugin: ?plugin=stationary&foo=bar
function plugin_stationary_action()
{
	// See above
	// if (PKWK_SAFE_MODE || PKWK_READONLY)
	if (auth::check_role('safemode') || auth::check_role('readonly'))
		die_message('PKWK_SAFE_MODE or PKWK_READONLY prohibits this');

	$msg  = 'Message';
	$body = 'Message body';

	return array('msg'=>htmlsc($msg), 'body'=>htmlsc($body));
}
?>
