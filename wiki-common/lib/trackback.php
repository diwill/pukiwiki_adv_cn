<?php
// $Id: trackback.php,v 1.21.11 2012/06/14 21:44:00 Logue Exp $
// Copyright (C)
//   2010-2012 PukiWiki Advance Developer Team
//   2005-2007 PukiWiki Plus! Team
//   2003-2005 PukiWiki Developers Team
//   2003      Originally written by Katsumi Saito <katsumi@jo1upk.ymt.prug.or.jp>
// License: GPL v2 or (at your option) any later version
//
// PukiWiki/TrackBack

/*
 * NOTE:
 *     To get TrackBack ID correctly, specify URI clearly like:
 *     http://localhost/pukiwiki/pukiwiki.php?FrontPage
 *
 * tb_get_id($page)        Get TrackBack ID from page name
 * tb_id2page($tb_id)      Get page name from TrackBack ID
 * tb_get_filename($page)  Get file name of TrackBack ping data
 * tb_count($page)         Count the number of TrackBack pings included for the page
 *                         // pukiwiki.skin.php
 * tb_send($page, $links)  Send TrackBack ping(s) automatically // file.php
 * tb_delete($page)        Remove TrackBack ping data // edit.inc.php
 * tb_get($file, $key = 1) Import TrackBack ping data from file
 * tb_get_rdf($page)       Get a RDF comment to bury TrackBack-ping-URI under HTML(XHTML) output
 *                         // lib/pukiwiki.php
 * tb_get_url($url)        HTTP-GET from $uri, and reveal the TrackBack Ping URL
 * class TrackBack_XML     Parse and reveal the TrackBack Ping URL from RDF data
 */

use Zend\Http\Client;
define('PLUGIN_TRACKBACK_VERSION', 'PukiWiki Adv./TrackBack 0.5');

// Get TrackBack ID from page name
function tb_get_id($page)
{
	return md5($page);
}

// Get page name from TrackBack ID
function tb_id2page($tb_id)
{
	static $pages, $cache = array();

	if (isset($cache[$tb_id])) return $cache[$tb_id];

	if (! isset($pages)) $pages = get_existpages();
	foreach ($pages as $page) {
		$_tb_id = tb_get_id($page);
		$cache[$_tb_id] = $page;
		unset($pages[$page]);
		if ($tb_id == $_tb_id) return $cache[$tb_id]; // Found
	}

	$cache[$tb_id] = FALSE;
	return $cache[$tb_id]; // Not found
}

// Get file name of TrackBack ping data
function tb_get_filename($page, $ext = '.txt')
{
	return TRACKBACK_DIR . encode($page) . $ext;
}

// Count the number of TrackBack pings included for the page
function tb_count($page, $ext = '.txt')
{
	$filename = tb_get_filename($page, $ext);
	if (!file_exists($filename) || !is_readable($filename) || !($fp = fopen($filename,"r")) ) return 0;

	$i = 0;
	while ($data = @fgets($fp, 4096)) $i++;
	fclose($fp);
	unset($data);
	return $i;
}

// Send TrackBack ping(s) automatically
// $plus  = Newly added lines may include URLs
// $minus = Removed lines may include URLs
function tb_send($page, $links)
{
	global $trackback, $page_title, $log;
	$script = get_script_uri();

	//if (! $trackback) return;

	// No link, END
	if (! is_array($links) || empty($links)) return;

	// PROHIBITION OF INVALID TRANSMISSION
	$url = parse_url($script);
	$host = (empty($url['host'])) ? $script : $url['host'];
	if (is_ipaddr($host)) {
		if (is_localIP($host)) return;
	} else {
		if (is_ReservedTLD($host)) return;
	}
	if (is_ignore_page($page)) return;

	// Disable 'max execution time' (php.ini: max_execution_time)
	if (ini_get('safe_mode') == '0') set_time_limit(0);

	$excerpt = strip_htmltag(convert_html(get_source($page)));

	// Sender's information
	$putdata = array(
		'title'     => $page, // Title = It's page name
		'url'       => get_page_absuri($page),
		'excerpt'   => mb_strimwidth(preg_replace("/[\r\n]/", ' ', $excerpt), 0, 255, '...'),
		'blog_name' => $page_title . ' (' . PLUGIN_TRACKBACK_VERSION . ')',
		'charset'   => SOURCE_ENCODING // Ping text encoding (Not defined)
	);


	foreach ($links as $link) {
		if (path_check($script, $link)) continue; // Same Site
		$tb_url = tb_get_url($link);  // Get Trackback ID from the URL
		if (empty($tb_url)) continue; // Trackback is not supported

		$client = new Client($tb_url);
		$client->setParameterPost($putdata);

		//$result = pkwk_http_request($tb_id, 'POST', '', $putdata, 2, CONTENT_CHARSET);
		// FIXME: Create warning notification space at pukiwiki.skin!

		$log[] = $client->request("POST");
	}
}

// Remove TrackBack ping data
function tb_delete($page)
{
	$filename = tb_get_filename($page);
	if (file_exists($filename)) @unlink($filename);
}

// Import TrackBack ping data from file
function tb_get($file, $key = 1)
{
	if (! file_exists($file)) return array();

	$result = array();
	$fp = @fopen($file, 'r');
	set_file_buffer($fp, 0);
	@flock($fp, LOCK_EX);
	rewind($fp);
	while ($data = @fgets($fp, 8192)) {
		// $data[$key] = URL
		$result[rawurldecode($data[$key])] = explode(',', $data);
	}
	@flock($fp, LOCK_UN);
	fclose ($fp);

	return $result;
}

// Get a RDF comment to bury TrackBack-ping-URI under HTML(XHTML) output
function tb_get_rdf($page)
{
	global $modifier;
	$url = get_page_absuri($page);
	$tb_ping = get_cmd_absuri('tb','','tb_id='.tb_get_id($page));
	// $dcdate = substr_replace(get_date('Y-m-d\TH:i:sO', $time), ':', -2, 0);
	// dc:date="$dcdate"

	return <<<EOD
<!--
<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#"
	xmlns:dc="http://purl.org/dc/elements/1.1/"
	xmlns:trackback="http://madskills.com/public/xml/rss/module/trackback/">
 	<rdf:Description
		rdf:about="$url"
		dc:identifier="$url"
		dc:title="$page"
		dc:creator="$modifier"
		trackback:ping="$tb_ping" />
</rdf:RDF>
-->
EOD;
}

use Zend\Http\ClientStatic;
// HTTP-GET from $uri, and reveal the TrackBack Ping URL
function tb_get_url($url)
{
	global $use_proxy, $no_proxy;

	// Don't go across HTTP-proxy server
	$parse_url = parse_url($url);
	if (empty($parse_url['host']) ||
	   ($use_proxy && ! in_the_net($no_proxy, $parse_url['host'])))
		return '';

	//$data = pkwk_http_request($url);	// Get trackback xml.
	//if ($data['rc'] !== 200) return '';

	$response = ClientStatic::get($url);
	if ($response->isSuccess()){
		$matches = array();
		if (! preg_match_all('#<rdf:RDF[^>]*xmlns:trackback=[^>]*>(.*?)</rdf:RDF>#si',  $response->getBody(),
			$matches, PREG_PATTERN_ORDER))
			return '';

		$obj = new TrackBack_XML();
		foreach ($matches[1] as $body) {
			$tb_url = $obj->parse($body, $url);
			if ($tb_url !== FALSE) return $tb_url;
		}
	}

	return '';
}

// Parse and reveal the TrackBack Ping URL from RDF(XML) data
class TrackBack_XML
{
	var $url;
	var $tb_url;

	function parse($buf, $url)
	{
		// Init
		$this->url    = $url;
		$this->tb_url = FALSE;

		$xml_parser = xml_parser_create();
		if ($xml_parser === FALSE) return FALSE;

		xml_set_element_handler($xml_parser, array(& $this, 'start_element'),
			array(& $this, 'end_element'));

		if (! xml_parse($xml_parser, $buf, TRUE)) {
/*			die(sprintf('XML error: %s at line %d in %s',
				xml_error_string(xml_get_error_code($xml_parser)),
				xml_get_current_line_number($xml_parser),
				$buf));
*/
			return FALSE;
		}

		return $this->tb_url;
	}

	function start_element($parser, $name, $attrs)
	{
		if ($name !== 'RDF:DESCRIPTION') return;

		$about = $url = $tb_url = '';
		foreach ($attrs as $key=>$value) {
			switch ($key) {
			case 'RDF:ABOUT'     : $about  = $value; break;
			case 'DC:IDENTIFER'  : /*FALLTHROUGH*/
			case 'DC:IDENTIFIER' : $url    = $value; break;
			case 'TRACKBACK:PING': $tb_url = $value; break;
			}
		}
		if ($about == $this->url || $url == $this->url)
			$this->tb_url = $tb_url;
	}

	function end_element($parser, $name) {}
}

/* End of file trackback.php */
/* Location: ./wiki-common/lib/trackback.php */