<?php
namespace PukiWiki\Lib;

class Router{
	// Show (critical) error message
	public static function die_message($msg, $error_title='', $http_code = 500){
		global $skin_file, $page_title, $_string, $_title, $_button, $vars;

		$title = !empty($error_title) ? $error_title : $_title['error'];
		$page = $_title['error'];

		if (PKWK_WARNING !== true){	// PKWK_WARNINGが有効でない場合は、詳細なエラーを隠す
			$msg = $_string['error_msg'];
		}
		$ret = array();
		$ret[] = '<p>[ ';
		if ( isset($vars['page']) && !empty($vars['page']) ){
			$ret[] = '<a href="' . get_page_location_uri($vars['page']) .'">'.$_button['back'].'</a> | ';
			$ret[] = '<a href="' . get_cmd_uri('edit',$vars['page']) . '">Try to edit this page</a> | ';
		}
		$ret[] = '<a href="' . get_cmd_uri() . '">Return to FrontPage</a> ]</p>';
		$ret[] = '<div class="message_box ui-state-error ui-corner-all">';
		$ret[] = '<p style="padding:0 .5em;"><span class="ui-icon ui-icon-alert"></span> <strong>' . $_title['error'] . '</strong> ' . $msg . '</p>';
		$ret[] = '</div>';
		$body = join("\n",$ret);

		global $trackback;
		$trackback = 0;

		if (!headers_sent()){
			pkwk_common_headers(0,0, $http_code);
		}

		if(defined('SKIN_FILE')){
			if (file_exists(SKIN_FILE) && is_readable(SKIN_FILE)) {
				catbody($page, $title, $body);
			} elseif ( !empty($skin_file) && file_exists($skin_file) && is_readable($skin_file)) {
				define('SKIN_FILE', $skin_file);
				catbody($page, $title, $body);
			}
		}else{
			$html = array();
			$html[] = '<!doctype html>';
			$html[] = '<html>';
			$html[] = '<head>';
			$html[] = '<meta charset="utf-8">';
			$html[] = '<meta name="robots" content="NOINDEX,NOFOLLOW" />';
			$html[] = '<link rel="stylesheet" href="http://code.jquery.com/ui/' . JQUERY_UI_VER . '/themes/base/jquery-ui.css" type="text/css" />';
			$html[] = '<title>' . $page . ' - ' . $page_title . '</title>';
			$html[] = '</head>';
			$html[] = '<body>' . $body . '</body>';
			$html[] = '</html>';
			echo join("\n",$html);
		}
		pkwk_common_suffixes();
		die();
	}

	public static function redirect($url = ''){
		global $vars;
		if (empty($url)){
			$url = isset($vars['page']) ? self::get_page_location_uri($vars['page']) : self::get_script_uri();
		}
		pkwk_headers_sent();
		header('Status: 301 Moved Permanently');
		header('Location: ' . $url);
		$html = array();
		$html[] = '<!doctype html>';
		$html[] = '<html>';
		$html[] = '<head>';
		$html[] = '<meta charset="utf-8">';
		$html[] = '<meta name="robots" content="NOINDEX,NOFOLLOW" />';
		$html[] = '<meta http-equiv="refresh" content="1; URL='.$url.'" />';
		$html[] = '<link rel="stylesheet" href="http://code.jquery.com/ui/' . JQUERY_UI_VER . '/themes/base/jquery-ui.css" type="text/css" />';
		$html[] = '<title>301 Moved Permanently</title>';
		$html[] = '</head>';
		$html[] = '<body>';
		$html[] = '<div class="message_box ui-state-info ui-corner-all">';
		$html[] = '<p style="padding:0 .5em;"><span class="ui-icon ui-icon-alert"></span>Please click <a href="'.$url.'">here</a> if you do not want to move even after a while.</p>';
		$html[] = '</div>';
		$html[] = '</body>';
		$html[] = '</html>';
		echo join("\n",$html);
		exit;
	}
	
	private function init($init_uri = '',$get_init_value=0){
		global $script_directory_index, $absolute_uri;
		static $script;

		if ( empty($init_uri) ) {
			// Get
			if (isset($script)) {
				if ($get_init_value) return $script;
				return $absolute_uri ? get_script_absuri() : $script;
			}
			$script = self::get_script_absuri();
			return $script;
		}

		// Set manually
		if (isset($script)) die_message('$script: Already init');
		if (! self::is_reluri($init_uri) && ! is_url($init_uri, TRUE)) die_message('$script: Invalid URI');
		$script = $init_uri;

		// Cut filename or not
		if (isset($script_directory_index)) {
			if (! file_exists($script_directory_index))
				die_message('Directory index file not found: ' .
					htmlsc($script_directory_index));
			$matches = array();
			if (preg_match('#^(.+/)' . preg_quote($script_directory_index, '#') . '$#',
				$script, $matches)) $script = $matches[1];
		}

		return $absolute_uri ? self::get_script_absuri() : $script;
	}

	// Get absolute-URI of this script
	public static function get_script_uri($path='')
	{
		global $absolute_uri, $script_directory_index;

		if ($absolute_uri === 1) return get_script_absuri();
		$uri = get_baseuri($path);
		if (! isset($script_directory_index)) $uri .= init();
		return $uri;
	}

	// Get absolute-URI of this script
	public static function get_script_absuri()
	{
		global $script_abs, $script_directory_index;
		global $script;
		static $uri;

		// Get
		if (isset($uri)) return $uri;

		if (isset($script_abs) && is_url($script_abs,true)) {
			$uri = $script_abs;
			return $uri;
		} else
		if (isset($script) && is_url($script,true)) {
			$uri = $script;
			return $uri;
		}

		// Set automatically
		$msg	 = 'get_script_absuri() failed: Please set [$script or $script_abs] at INI_FILE manually';

		$uri  = ( ( isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'];
		$uri .= ($_SERVER['SERVER_PORT'] == 80 || $_SERVER['SERVER_PORT'] == 443) ? '' : ':' . SERVER_PORT;  // port

		// SCRIPT_NAME が'/'で始まっていない場合(cgiなど) REQUEST_URIを使ってみる
		$path	= SCRIPT_NAME;
		if ($path{0} !== '/') {
			if (! isset($_SERVER['REQUEST_URI']) || $_SERVER['REQUEST_URI']{0} != '/') {
				die_message($msg);
			}

			// REQUEST_URIをパースし、path部分だけを取り出す
			$parse_url = parse_url($uri . $_SERVER['REQUEST_URI']);
			if (! isset($parse_url['path']) || $parse_url['path']{0} != '/') {
				die_message($msg);
			}

			$path = $parse_url['path'];
		}
		$uri .= $path;

		if (! is_url($uri, true) && php_sapi_name() == 'cgi') {
			die_message($msg);
		}
		unset($msg);

		// Cut filename or not
		if (isset($script_directory_index)) {
			if (! file_exists($script_directory_index))
				die_message('Directory index file not found: ' .
				htmlsc($script_directory_index));
			$matches = array();
			if (preg_match('#^(.+/)' . preg_quote($script_directory_index, '#') . '$#',
				$uri, $matches)) $uri = $matches[1];
		}

		return $uri;
	}

	public static function get_resolve_uri($cmd='', $page='', $path_reference='rel', $query=array(), $fragment='')
	{
		global $static_url, $url_suffix, $vars;
		$path = (empty($path_reference)) ? 'rel' : $path_reference;
		$ret = self::get_script_uri($path);

		if (! empty($cmd) && $cmd !== 'read') {
			$ret .= '?cmd='.$cmd;
			$flag = '&';
			if (! empty($page)) {
				$ret .= $flag. 'page='.rawurlencode($page);
			}
			// query
			if (! empty($query)) {
				$ret .= '&' . (is_string($query) ? $query : http_build_query($query));
			}
		}else{
			// Apacheは、:が含まれるアドレスを正確に処理できない
			// https://issues.apache.org/bugzilla/show_bug.cgi?id=41441
			if ($static_url === 1 && 
				!( stristr(getenv('SERVER_SOFTWARE'), 'apache') !== FALSE && (strstr($page, ':' ) !== FALSE || strstr($page,' ' ) !== FALSE) )){
				$ret .= str_replace('%2F', '/', rawurlencode($page));
			}else{
				$ret .= '?' . rawurlencode($page);
			}
		}

		// fragment
		if (! empty($fragment)) {
			$ret .= '#'.$fragment;
		}
		unset($flag);
		return $ret;
	}
	public static function get_baseuri($path='')
	{
		// RFC2396,RFC3986 : relativeURI = ( net_path | abs_path | rel_path ) [ "?" query ]
		//				   absoluteURI = scheme ":" ( hier_part | opaque_part )
		$ret = '';
		$script = self::get_script_uri();

		switch($path) {
		case 'net': // net_path	  = "//" authority [ abs_path ]
			$parsed_url = parse_url(self::get_script_absuri());
			$pref = '//';
			if (isset($parsed_url['user'])) {
				$ret .= $pref . $parsed_url['user'];
				$pref = '';
				$ret .= (isset($parsed_url['pass'])) ? ':'.$parsed_url['pass'] : '';
				$ret .= '@';
			}
			if (isset($parsed_url['host'])) {
				$ret .= $pref . $parsed_url['host'];
				$pref = '';
			}
			$ret .= (isset($parsed_url['port'])) ? ':'.$parsed_url['port'] : '';
		case 'abs': // abs_path	  = "/"  path_segments
			if ($path === 'abs') $parsed_url = parse_url(get_script_absuri());
			if (isset($parsed_url['path']) && ($pos = strrpos($parsed_url['path'], '/')) !== false) {
				$ret .= substr($parsed_url['path'], 0, $pos + 1);
			} else {
				$ret .= '/';
			}
			break;
		case 'rel': // rel_path	  = rel_segment [ abs_path ]
			if (is_url($script, true)) {
				$ret = './';
			} else {
				$parsed_url = parse_url($script);
				if (isset($parsed_url['path']) && ($pos = strrpos($parsed_url['path'], '/')) !== false) {
					$ret .= substr($parsed_url['path'], 0, $pos + 1);
				}
			}
			break;
		case 'full':
		default:
			$absoluteURI = get_script_absuri();
			$ret = substr($absoluteURI, 0, strrpos($absoluteURI, '/')+1);
			break;
		}

		return $ret;
	}
	private function is_reluri($str)
	{
		// global $script_directory_index;
		switch ($str) {
			case '':
			case './':
			case 'index.php';
			case './index.php';
				return true;
			}
		// if (! isset($script_directory_index) && $str == 'index.php') return true;
		return false;
	}

}
