<?php
// PukiPlus.
// $Id: funcplus.php,v 0.1.65 2011/09/11 23:01:00 Logue Exp $
// Copyright (C)
//   2010-2011 PukiWiki Advance Developers Team <http://pukiwiki.logue.be/>
//   2005-2009 PukiWiki Plus! Team
// License: GPL v2 or (at your option) any later version
//
// Plus! extension function(s)

defined('FUNC_POSTLOG')		or define('FUNC_POSTLOG', FALSE);
defined('FUNC_SPAMLOG')		or define('FUNC_SPAMLOG', TRUE);
defined('FUNC_BLACKLIST')	or define('FUNC_BLACKLIST', TRUE);
defined('FUNC_SPAMREGEX')	or define('FUNC_SPAMREGEX', '#(?:cialis|hydrocodone|viagra|levitra|tramadol|xanax|\[/link\]|\[/url\])#i');
defined('FUNC_SPAMCOUNT')	or define('FUNC_SPAMCOUNT', 2);

function showtaketime(){
	// http://pukiwiki.sourceforge.jp/dev/?BugTrack2%2F251
	$longtaketime = getmicrotime() - MUTIME;
	return sprintf('%01.03f', $longtaketime);
}

// same as 'basename' for page
function basepagename($str)
{
	return preg_replace('#^.*/#', '', $str);
}

// SPAM check
function is_spampost($array, $count=0)
{
	global $vars;

	if ($count <= 0) {
		$count = intval(FUNC_SPAMCOUNT);
	}
	$matches = array();
	foreach($array as $idx) {
		if (preg_match_all(FUNC_SPAMREGEX, $vars[$idx], $matches) >= $count)
			return TRUE;
	}
	return FALSE;
}
// POST logging
function postdata_write()
{
	global $get, $post, $vars, $cookie;

	// Logging for POST Report
	if (FUNC_POSTLOG === TRUE && version_compare(PHP_VERSION, '4.2.0', '>=')) {
		error_log("\n\n----" . date('Y-m-d H:i:s', time()) . "\n", 3, CACHE_DIR . 'postdata.log');
		error_log("[ADDR]" . get_remoteip() . "\t" . $_SERVER['HTTP_USER_AGENT'] . "\n", 3, CACHE_DIR . 'postdata.log');
		error_log("[SESS]\n" . var_export($cookie, TRUE) . "\n", 3, CACHE_DIR . 'postdata.log');
		error_log("[GET]\n"  . var_export($get,	TRUE) . "\n", 3, CACHE_DIR . 'postdata.log');
		error_log("[POST]\n" . var_export($post,   TRUE) . "\n", 3, CACHE_DIR . 'postdata.log');
		error_log("[VARS]\n" . var_export($vars,   TRUE) . "\n", 3, CACHE_DIR . 'postdata.log');
	}
}

// SPAM logging
function honeypot_write()
{
	global $get, $post, $vars, $cookie;

	// Logging for SPAM Address
	// NOTE: Not recommended use Rental Server
	if ((FUNC_SPAMLOG === TRUE || FUNC_BLACKLIST === TRUE) && version_compare(PHP_VERSION, '4.2.0', '>=')) {
		error_log(get_remoteip() . "\t" . UTIME . "\t" . $_SERVER['HTTP_USER_AGENT'] . "\n", 3, CACHE_DIR . 'blacklist.log');
	}

	// Logging for SPAM Report
	// NOTE: Not recommended use Rental Server
	if (FUNC_SPAMLOG === TRUE && version_compare(PHP_VERSION, '4.2.0', '>=')) {
		error_log("----" . date('Y-m-d H:i:s', time()) . "\n", 3, CACHE_DIR . 'honeypot.log');
		error_log("[ADDR]" . get_remoteip() . "\t" . $_SERVER['HTTP_USER_AGENT'] . "\n", 3, CACHE_DIR . 'honeypot.log');
		error_log("[SESS]\n" . var_export($cookie, TRUE) . "\n", 3, CACHE_DIR . 'honeypot.log');
		error_log("[GET]\n"  . var_export($get,	TRUE) . "\n", 3, CACHE_DIR . 'honeypot.log');
		error_log("[POST]\n" . var_export($post,   TRUE) . "\n", 3, CACHE_DIR . 'honeypot.log');
		error_log("[VARS]\n" . var_export($vars,   TRUE) . "\n", 3, CACHE_DIR . 'honeypot.log');
	}
}

// インクルードで余計なものはソースから削除する
function convert_filter($str)
{
	global $filter_rules;
	static $patternf, $replacef;

	if (!isset($patternf)) {
		$patternf = array_map(create_function('$a','return "/$a/";'), array_keys($filter_rules));
		$replacef = array_values($filter_rules);
		unset($filter_rules);
	}
	return preg_replace($patternf, $replacef, $str);
}

function get_fancy_uri()
{
	$script  = (SERVER_PORT == 443 ? 'https://' : 'http://'); // scheme
	$script .= SERVER_NAME; // host
	$script .= (SERVER_PORT == 80 || SERVER_PORT == 443) ? '' : ':' . SERVER_PORT;  // port

	// SCRIPT_NAME が'/'で始まっていない場合(cgiなど) REQUEST_URIを使ってみる
	$path	= SCRIPT_NAME;
	$script .= $path; // path

	return $script;
}

function get_remoteip()
{
	static $array_var = array('HTTP_CF_CONNECTING_IP', 'HTTP_X_REMOTE_ADDR','REMOTE_ADDR'); // HTTP_X_FORWARDED_FOR
	foreach($array_var as $x){
		if (isset($_SERVER[$x])) return $_SERVER[$x];
	}
	return '';
}

function mb_ereg_quote($str)
{
	return mb_ereg_replace('([.\\+*?\[^\]\$(){}=!<>|:])', '\\\1', $str);
}

// タグの追加
function open_uri_in_new_window($anchor, $which = '')
{
	global $use_open_uri_in_new_window,		// この関数を使うか否か
		   $open_uri_in_new_window_opis,		// 同一サーバー(Farm?)
		   $open_uri_in_new_window_opisi,		// 同一サーバー(Farm?)のInterWiki
		   $open_uri_in_new_window_opos,		// 外部サーバー
		   $open_uri_in_new_window_oposi;		// 外部サーバーのInterWiki
	global $_symbol_extanchor, $_symbol_innanchor;	// 新規ウィンドウを開くアイコン

	// この関数を使わない OR 呼び出し元が不正な場合はスルーする
	if (!$use_open_uri_in_new_window || empty($which) || !$_symbol_extanchor || !$_symbol_innanchor) {
		return $anchor;
	}


	// 外部形式のリンクをどうするか
	// 質問箱/115 対応
	/*
	if ($which == 'link_interwikiname') {
		$frame = (is_inside_uri($anchor) ? $open_uri_in_new_window_opisi:$open_uri_in_new_window_oposi);
		$symbol = (is_inside_uri($anchor) ? $_symbol_innanchor:$_symbol_extanchor);
		$aclass = (is_inside_uri($anchor) ? 'inn':'ext');
	} elseif ($which == 'link_url_interwiki') {
		$frame = (is_inside_uri($anchor) ? $open_uri_in_new_window_opisi:$open_uri_in_new_window_oposi);
		$symbol = (is_inside_uri($anchor) ? $_symbol_innanchor:$_symbol_extanchor);
		$aclass = (is_inside_uri($anchor) ? 'inn':'ext');
	} elseif ($which == 'link_url') {
		$frame = (is_inside_uri($anchor) ? $open_uri_in_new_window_opis:$open_uri_in_new_window_opos);
		$symbol = (is_inside_uri($anchor) ? $_symbol_innanchor:$_symbol_extanchor);
		$aclass = (is_inside_uri($anchor) ? 'inn':'ext');
	}
	*/

	switch (strtolower($which)) {
	case 'link_interwikiname':
	case 'link_url_interwiki':
		$frame  = (is_inside_uri($anchor) ? $open_uri_in_new_window_opisi : $open_uri_in_new_window_oposi);
		$symbol = (is_inside_uri($anchor) ? $_symbol_innanchor : $_symbol_extanchor);
		break;
	case 'link_url':
		$frame  = (is_inside_uri($anchor) ? $open_uri_in_new_window_opis : $open_uri_in_new_window_opos);
		$symbol = (is_inside_uri($anchor) ? $_symbol_innanchor : $_symbol_extanchor);
		break;
	default:
		$symbol = $frame = '';
		break;
	}

	if ($frame == '')
		return $anchor;

	// 引数 $anchor は a タグの中にクラスはない
	$aclasspos = mb_strpos($anchor, '<a ', 0, mb_detect_encoding($anchor)) + 3; // 3 is strlen('<a ')
	$insertpos = mb_strpos($anchor, '</a>', $aclasspos, mb_detect_encoding($anchor));
	preg_match('#href="([^"]+)"#', $anchor, $href);

	return (mb_substr($anchor, 0, $aclasspos) .
		mb_substr($anchor, $aclasspos , $insertpos-$aclasspos)
			. str_replace('$1', $href[1], str_replace('$2', $frame, $symbol)) . mb_substr($anchor, $insertpos));
}

function is_inside_uri($anchor)
{
	global $open_uri_in_new_window_servername;
	static $set_baseuri = true;

		if ($set_baseuri) {
		$set_baseuri = false;
		$open_uri_in_new_window_servername[] = get_baseuri();
		}

	foreach ($open_uri_in_new_window_servername as $servername) {
		if (stristr($anchor, $servername)) {
			return true;
		}
	}
	return false;
}

function load_init_value($name,$must=0)
{
	$init_dir = array(INIT_DIR, SITE_INIT_DIR);
	$read_dir = array();
	$init_data = $name . '.ini.php';

	// Exclusion of repetition definition
	foreach($init_dir as $val) { $read_dir[$val] = ''; }

	foreach($read_dir as $key=>$val) {
		if (file_exists($key.$init_data)) {
			if ($must)
				require_once($key.$init_data);
			else
				include_once($key.$init_data);
			return TRUE;
		}
	}

	return FALSE;
}

function add_homedir($file)
{
	foreach(array(DATA_HOME,SITE_HOME) as $dir) {
		if (file_exists($dir.$file) && is_readable($dir.$file)) return $dir.$file;
	}
	return $file;
}

function add_skindir($skin_name)
{
	$cond = array(
		SKIN_DIR.THEME_PLUS_NAME.$skin_name.'/',
		EXT_SKIN_DIR.THEME_PLUS_NAME.$skin_name.'/'
	);

	$file = basepagename($skin_name).'.skin.php';
	$conf = basepagename($skin_name).'.ini.php';

	foreach($cond as $dir){
		if (file_exists($dir.$file) && is_readable($dir.$file)){
			// スキンが見つかった場合
			if ( file_exists($dir.$conf) && is_readable($dir.$conf)){
				// スキンのオーバーライド設定ファイルが存在する場合、それを読み取る。
				require_once $dir.$conf;
			}
			return $dir.$file;
		}
	}

	die_message('Skin File:<var>'.$skin_name.'</var> is not found or not readable. Please check <var>SKIN_DIR</var> value. (NOT <var>SKIN_URI</var>. )');
}

function is_ignore_page($page)
{
	global $navigation,$whatsnew,$whatsdeleted,$interwiki,$menubar,$sidebar,$headarea,$footarea;

	$ignore_regrex = '/['.$navigation.'|'.$whatsnew.'|'.$whatsdeleted.'|'.
		$interwiki.'|'.$menubar.'|'.$sidebar.'|'.$headarea.'|'.$footarea.']$/';
	return (preg_match($ignore_regrex, $page)) ? TRUE : FALSE;
}

function is_localIP($ip)
{
	static $localIP = array('127.0.0.0/8','10.0.0.0/8','172.16.0.0/12','192.168.0.0/16');
	if (is_ipaddr($ip) === FALSE) return FALSE;
	return ip_scope_check($ip,$localIP);
}

function is_ipaddr($ip)
{
	$valid = ip2long($ip);
	return ($valid == -1 || $valid == FALSE) ? FALSE : $valid;
}

// IP の判定
function ip_scope_check($ip,$networks)
{
	// $l_ip = ip2long( ip2arrangement($ip) );
	$l_ip = ip2long($ip);
	foreach($networks as $network) {
		$range = explode('/', $network);
		$l_network = ip2long( ip2arrangement($range[0]) );
		// $l_network = ip2long( $range[0] );
		if (empty($range[1])) $range[1] = 32;
		$subnetmask = pow(2,32) - pow(2,32 - $range[1]);
		if (($l_ip & $subnetmask) == $l_network) return TRUE;
	}
	return FALSE;
}

// ex. ip=192.168.101.1 from=192.168.0.0 to=192.168.211.12
function ip_range_check($ip,$from,$to)
{
	if (empty($to)) return ip_scope_check($ip,array($from));
		$l_ip = ip2long($ip);
		$l_from = ip2long( ip2arrangement($from) );
		$l_to = ip2long( ip2arrangement($to) );
		return ($l_from <= $l_ip && $l_ip <= $l_to);
}

// ex. 10 -> 10.0.0.0, 192.168 -> 192.168.0.0
function ip2arrangement($ip)
{
	$x = explode('.', $ip);
	if (count($x) == 4) return $ip;
	for($i=0;$i<4;$i++) { if (empty($x[$i])) $x[$i] =0; }
	return sprintf('%d.%d.%d.%d',$x[0],$x[1],$x[2],$x[3]);
}

// 予約されたドメイン
function is_ReservedTLD($host)
{
	// RFC2606
	static $ReservedTLD = array('example' =>'','invalid' =>'','localhost'=>'','test'=>'',);
	$x = array_reverse(explode('.', strtolower($host) ));
	return (isset($ReservedTLD[$x[0]])) ? TRUE : FALSE;
}

function path_check($url1,$url2)
{
	$u1 = parse_url(strtolower($url1));
	$u2 = parse_url(strtolower($url2));

	// http = https とする
	if (!empty($u1['scheme']) && $u1['scheme'] == 'https') $u1['scheme'] = 'http';
	if (!empty($u2['scheme']) && $u2['scheme'] == 'https') $u2['scheme'] = 'http';

	// path の手当て
	if (!empty($u1['path'])) {
		$u1['path'] = substr($u1['path'],0,strrpos($u1['path'],'/'));
	}
	if (!empty($u2['path'])) {
		$u2['path'] = substr($u2['path'],0,strrpos($u2['path'],'/'));
	}

	foreach(array('scheme','host','path') as $x) {
		$u1[$x] = (empty($u1[$x])) ? '' : $u1[$x];
		$u2[$x] = (empty($u2[$x])) ? '' : $u2[$x];
		if ($u1[$x] == $u2[$x]) continue;
		return FALSE;
	}
	return TRUE;
}

// Check CGI/CLI(true) or MOD_PHP(false)
function is_sapi_clicgi()
{
	$sapiname = php_sapi_name();
	if ($sapiname == 'cgi' || $sapiname == 'cli')
		return TRUE;
	return FALSE;
}

// get "GD" extension version
function get_gdversion()
{
	if (!extension_loaded('gd')) { return 0; }
	if (!function_exists('gd_info')) { return 0; }
	$gd_info = gd_info();
	$matches = array();
	preg_match('/\d/', $gd_info['GD Version'], $matches);
	return $matches[0];
}

// create thumbnail (required "GD" extension)
function make_thumbnail($ofile, $sfile, $maxw, $maxh, $refresh=FALSE, $zoom='10,90', $quality='75')
{
	static $gdversion = FALSE;
	if ($gdversion === FALSE) {
		$gdversion = get_gdversion();
	}

	if (!$refresh && file_exists($sfile)) return $sfile;
	if ($gdversion < 1 || !function_exists('imagecreate')) return $ofile; // Not Supported

	$imagecreate = ($gdversion >= 2)? 'imagecreatetruecolor' : 'imagecreate';
	$imageresize = ($gdversion >= 2)? 'imagecopyresampled' : 'imagecopyresized';

	$imagesiz = @getimagesize($ofile);
	if (!$imagesiz) return $ofile; // Not Picture

	$orgw = $imagesiz[0];
	$orgh = $imagesiz[1];
	if ($maxw >= $orgw && $maxh >= $orgh) return $ofile; // so big. why?

	list($minz, $maxz) = explode(",", $zoom);
	$zoom = min(($maxw/$orgw),($maxh/$orgh));
	if (!$zoom || $zoom < $minz/100 || $zoom > $maxz/100) return $ofile; // Invalid Zoom value
	$w = $orgw * $zoom;
	$h = $orgh * $zoom;

	// defined thumbnail file-type?(.jpg)
	$s_ext = '';
	$s_ext = preg_replace('/\.([^\.]+)$/', '$1', $sfile);

	// Create image.
	switch($imagesiz[2]) {
	case '1': // gif
		if (function_exists('imagecreatefromgif')) {
			$imsrc = imagecreatefromgif($ofile);
			$colortransparent = imagecolortransparent($imsrc);
			if ($s_ext != 'jpg' && $colortransparent > -1) {
				// Use transparent
				$imdst = $imagecreate($w, $h);
				imagepalettecopy($imdst, $imsrc);
				imagefill($imdst, 0, 0, $colortransparent);
				imagecolortransparent($imdst, $colortransparent);
				imagecopyresized($imdst, $imsrc, 0, 0, 0, 0, $w, $h, $orgw, $orgh);
			} else {
				// Unuse transparent
				$imdst = $imagecreate($w, $h);
				$imageresize($imdst, $imsrc, 0, 0, 0, 0, $w, $h, $orgw, $orgh);
				imagetruecolortopalette($dst_im, imagecolorstotal($imsrc));
			}
			touch($sfile);
			if ($s_ext == 'jpg') {
				imagejpeg($imdst, $sfile, $quality);
			} elseif (function_exists('imagegif')) {
				imagegif($imdst, $sfile);
			} else {
				imagepng($imdst, $sfile);
			}
			$ofile = $sfile;
		}
		break;
	case '2': // jpg
		$imsrc = imagecreatefromjpeg($ofile);
		$imdst = $imagecreate($w, $h);
		$imageresize($imdst, $imsrc, 0, 0, 0, 0, $w, $h, $orgw, $orgh);
		touch($sfile);
		imagejpeg($imdst, $sfile, $quality);
		$ofile = $sfile;
		break;
	case '3': // png
		$imsrc = imagecreatefrompng($ofile);
		if (imagecolorstotal($imsrc)) {
			// PaletteColor
			$colortransparent = imagecolortransparent($imsrc);
			if ($s_ext != 'jpg' && $colortransparent > -1) {
				// Use transparent
				$imdst = $imagecreate($w, $h);
				imagepalettecopy($imdst, $imsrc);
				imagefill($imdst, 0, 0, $colortransparent);
				imagecolortransparent($imdst, $colortransparent);
				imagecopyresized($imdst, $imsrc, 0, 0, 0, 0, $w, $h, $orgw, $orgh);
			} else {
				// Unuse transparent
				$imdst = $imagecreate($w, $h);
				$imageresize($imdst, $imsrc, 0, 0, 0, 0, $w, $h, $orgw, $orgh);
				imagetruecolortopalette($dst_im, imagecolorstotal($imsrc));
			}
		} else {
			// TrueColor
			$imdst = $imagecreate($w, $h);
			$imageresize($imdst, $imsrc, 0, 0, 0, 0, $w, $h, $orgw, $orgh);
		}
		touch($sfile);
		if ($s_ext == 'jpg') {
			imagejpeg($imdst, $sfile, $quality);
		} else {
			imagepng($imdst, $sfile);
		}
		$ofile = $sfile;
		break;
	default:
		break;
	}
	@imagedestroy($imdst);
	@imagedestroy($imsrc);
	return $ofile;
}

function is_mobile()
{
	return (UA_PROFILE == 'mobile' || UA_PROFILE == 'handheld');
}

function get_mimeinfo($filename)
{
	$type = '';
	if (function_exists('finfo_open')) {
		$finfo = finfo_open(FILEINFO_MIME);
		if (!$finfo) return $type;
		$type = finfo_file($finfo, $filename);
		finfo_close($finfo);
		return $type;
	}

	if (function_exists('mime_content_type')) {
		$type = mime_content_type($filename);
		return $type;
	}

	// PHP >= 4.3.0
	$filesize = @getimagesize($filename);
	if (is_array($filesize) && preg_match('/^(image\/)/i', $filesize['mime'])) {
		$type = $filesize['mime'];
	}
	return $type;
}

function get_main_pluginname()
{
	$pos = strpos($_SERVER['REQUEST_URI'], '?');
	if ($pos === false) return 'read';

	$query_string = explode('&',rawurldecode( substr($_SERVER['REQUEST_URI'], $pos+1) ));

	$query = array();
	foreach($query_string as $q) {
		$cmd = explode('=',$q);
		$query[$cmd[0]] = $cmd[1];
	}

	// 優先順位 (cmd -> plugin)
	if (!empty($query['cmd'])) return $query['cmd'];
	if (!empty($query['plugin'])) return $query['plugin'];

	if (empty($query['page'])) return 'read';
	return (is_page($query['page'])) ? 'read' : 'edit';
}

function get_main_pagename()
{
	global $defaultpage;

	$pos = strpos($_SERVER['REQUEST_URI'], '?');
	if ($pos === false) return $defaultpage;

	if (! strpos($_SERVER['REQUEST_URI'], '=')) return rawurldecode( substr($_SERVER['REQUEST_URI'], $pos+1));

	$query_string = explode('&',rawurldecode( substr($_SERVER['REQUEST_URI'], $pos+1) ));

	foreach($query_string as $q) {
		$cmd = explode('=',$q);
		switch($cmd[0]) {
		case 'cmd':
		case 'plugin':
			continue;
		case 'page':
			return $cmd[1];
		}
	}

	return '';
}

// FIXME
function is_reluri($str)
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

function get_baseuri($path='')
{
	global $script;

	// RFC2396,RFC3986 : relativeURI = ( net_path | abs_path | rel_path ) [ "?" query ]
	//				   absoluteURI = scheme ":" ( hier_part | opaque_part )
	$ret = '';

	switch($path) {
	case 'net': // net_path	  = "//" authority [ abs_path ]
		$parsed_url = parse_url(get_script_absuri());
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

function change_uri($cmd='',$force=0)
{
	global $script, $script_abs, $absolute_uri, $script_directory_index;
	static $onece, $bkup, $bkup_script, $bkup_script_abs, $bkup_absolute_uri;
	static $target_fields = array('script'=>'bkup_script','script_abs'=>'bkup_script_abs','absolute_uri'=>'bkup_absolute_uri');

	if (! isset($bkup)) {
		$bkup = true;
		foreach($target_fields as $org=>$bkup) {
			if (! isset($$bkup) && isset($org)) $$bkup = $$org;
		}
	}

	if (isset($onece)) return;

	switch($cmd) {
	case 'reset':
		foreach($target_fields as $org=>$bkup) {
			if (isset($$bkup)) {
				$$org = $$bkup;
			} else {
				if (isset($$org)) unset($$org);
			}
		}
		return;
	case 'net':
	case 'abs':
	case 'rel':
		change_uri('reset');
		$absolute_uri = 0;
		break;
	default:
		$absolute_uri = 1;
	}

	$script = get_baseuri($cmd);
	if (! isset($script_directory_index)) $script .= init_script_filename();
	if ($force === 1) $onece = 1;
	return;
}

function init_script_filename()
{
	// $script にファイル名が設定されていれば、それを求める
	$script = init_script_uri('',1);
	$pos = strrpos($script, '/');
	if ($pos !== false) {
		return substr($script, $pos + 1);
	}
	return '';
}

function get_script_filename()
{
	$default_idx = 'index.php';
	$path	= SCRIPT_NAME; // ex. /path/index.php
	if ($path{0} != '/') {
		if (! isset($_SERVER['REQUEST_URI']) || $_SERVER['REQUEST_URI']{0} != '/') {
			return $default_idx;
		}

		$parse_url = parse_url($_SERVER['REQUEST_URI']);
		if (! isset($parse_url['path']) || $parse_url['path']{0} != '/') {
			return $default_idx;
		}

		$path = $parse_url['path'];
	}

	$pos = strrpos($path, '/');
	if ($pos !== false) {
		return substr($path, $pos + 1);
	}
	return $default_idx;
}

// PHP 5
if (! function_exists('http_build_query')) {
	// string http_build_query  ( array $formdata  [, string $numeric_prefix  [, string $arg_separator  ]] )
	function http_build_query($formdata, $numeric_prefix='', $arg_separator='') {
		$retval = $flag = '';
		// arg_separator.output -> PHP 4.0.5
		if (empty($arg_separator)) {
			$arg_separator = ini_get('arg_separator.output');
			if (empty($arg_separator)) $arg_separator = '&';
		}
		foreach($formdata as $key=>$val) {
			if (empty($val)) continue;
			$key1 = (is_numeric($key)) ? $numeric_prefix.$key : $key;
			$retval .= $flag . $key1 . '=' . rawurlencode($val);
			$flag = $arg_separator;
		}
		return $retval;
	}
}

// インラインパラメータのデータを１行毎に分割する
function line2array($x)
{
	$x = preg_replace(
		array("[\\r\\n]","[\\r]"),
		array("\n","\n"),
		$x
	); // 行末の統一
	return explode("\n", $x);
}

function dat2html($x)
{
	return preg_replace(
		array("'<p>'si","'</p>'si"),
		array('',''),
		trim(convert_html($x))
	);
}

function tbl2dat($data)
{
	$x = explode('|',$data);
	if (substr($data,0,1) == '|') array_shift($x);
	if (substr($data,-1)  == '|') array_pop($x);
	return $x;
}

function is_header($x) { return ( substr($x,-2) == '|h') ? true : false; }

function strip_a($x)
{
	$x = preg_replace('#<a href="(.*?)"[^>]*>(.*?)</a>#si', '$2', $x);
	$x = preg_replace('#<a class="ext" href="(.*?)" .*?>(.*?)<img src="' . IMAGE_URI . 'plus/ext.png".*?</a>#si','$2',$x);
	return $x;
}

function is_webdav()
{
	global $log_ua;
	static $status = false;
	if ($status) return true;

	static $ua_dav = array(
		'Microsoft-WebDAV-MiniRedir\/',
		'Microsoft Data Access Internet Publishing Provider',
		'MS FrontPage',
		'^WebDrive',
		'^WebDAVFS\/',
		'^gnome-vfs\/',
		'^XML Spy',
		'^Dreamweaver-WebDAV-SCM1',
		'^Rei.Fs.WebDAV',
	);

	switch($_SERVER['REQUEST_METHOD']) {
	case 'OPTIONS':
	case 'PROPFIND':
	case 'MOVE':
	case 'COPY':
	case 'DELETE':
	case 'PROPPATCH':
	case 'MKCOL':
	case 'LOCK':
	case 'UNLOCK':
		$status = true;
		return $status;
	default:
		continue;
	}

	$matches = array();
	foreach($ua_dav as $pattern) {
		if (preg_match('/'.$pattern.'/', $log_ua, $matches)) {
			$status = true;
			return true;
		}
	}

	return false;
}

/* PukiWiki Adv. Extend codes *********************************************************************/
// for debug use
function pr($value){
	if (DEBUG){
		Zend\Debug\Debug::dump($value);
	}
	return '';
}

//バックトレースを表示
// http://techblog.ecstudio.jp/tech-tips/php/debug-basics.html
function print_backtrace($backtrace){
	echo '<table class="style_table">';
	echo '<thead><tr><th class="style_th">#</th><th class="style_th">call</th><th class="style_th">path</th></tr></thead><tbody>';
	foreach ($backtrace as $key => $val){
		echo '<tr><td class="style_td">'.$key.'</td>';
		echo '<td class="style_td">'.$val['function']."(".print_r($val['args'],true).")</td>";
		echo '<td class="style_td">'.$val['file']." on line ".$val['line']."</td></tr>";
	}
	echo "</tbody></table>";
}

function error_msg($msg,$body){
	return <<<HTML
<div style="padding: 0pt 0.7em;" class="ui-state-error ui-corner-all">
	<p><span class="ui-icon ui-icon-alert" style="float: left; margin-right: 0.3em;"></span> <strong>$msg:</strong>$body</p>
</div>
HTML;
}


/**************************************************************************************************/
/////////////// PHP Extesnion ///////////////
if (! function_exists('slide_rename')) {
	function slide_rename($basename, $max, $extfmt = '.%d') {
		for ($i = $max - 1; $i >= 1; $i--) {
			if (file_exists($basename . sprintf($extfmt, $i))) {
				$max = $i;
				break;
			}
		}
		for ($i = $max; $i >= 1; $i--) {
			@move($basename . sprintf($extfmt, $i), $basename . sprintf($extfmt, $i+1));
		}
	}
}
if (! function_exists('move')) {
	/**
	 * Move a file (rename does not overwrite if $newname exists on Win)
	 *
	 * @param string $oldname
	 * @param string $newname
	 * @return boolean
	 */
	function move($oldname, $newname) {
		if (! rename($oldname, $newname)) {
			if (copy ($oldname, $newname)) {
				unlink($oldname);
				return TRUE;
			}
			return FALSE;
		}
		return TRUE;
	}
}
/**************************************************************************************************/
// 標準出力からMecabを実行
// https://github.com/odoku/MeCab-for-PHP5/blob/master/MeCab.php
function mecab_stdio($switch, $str){
	global $mecab_path;
	if (!file_exists($mecab_path)){
		die_message('Mecab is not found or not executable. Please check mecab path: '.$mecab_path);
	}
	$pipes = array();
	$result = $error = '';
	$descriptorspec = array (
		0 => array('pipe', "r"), // stdin
		1 => array('pipe', "w"), // stdout
		2 => array('pipe', 'w')
	);

	$cmd = $mecab_path. isset($switch) ? ' '.$switch : '';
	$process = proc_open($cmd, $descriptorspec, $pipes, null, null);
	if (!is_resource($process)) return false;

	fwrite($pipes[0], $str);
	fclose($pipes[0]);

	$lines = array();
	while ($line = fgets($pipes[1])) $lines[] = str_replace(array("\r\n", "\r", "\n"), '', $line);
	fclose($pipes[1]);

	fwrite($pipes[2], $error);
	fclose($pipes[2]);

	$status = proc_close($process);

	return join("\n",$lines);
}

function mecab_parse($input){
	if (!extension_loaded('mecab')) {
		$result = mecab_stdio('',$input);
	}else{
		$mecab = new MeCab_Tagger();
		$result = $mecab->parse($input);
	}

	// 出力フォーマット：表層形\t品詞, 品詞細分類1, 品詞細分類2, 品詞細分類3, 活用形, 活用型, 原形, 読み, 発音
	$lines = explode("\n", $result);
	foreach($lines as $line){
		if(in_array(trim($line), array('EOS', ''))){
			continue;
		}
		$s = explode("\t", $line);
		$surface = $s[0];
		$info = explode(',', $s[1]);

		$analisys[] = array(
			'surface'       => $surface,							// 表層形
			'class'         => $info[0],							// 品詞
			'detail1'       => $info[1] !== '*' ? $info[1] : null,	// 品詞細分類1
			'detail2'       => $info[2] !== '*' ? $info[2] : null,	// 品詞細分類2
			'detail3'       => $info[3] !== '*' ? $info[3] : null,	// 品詞細分類3
			'inflections'   => $info[4] !== '*' ? $info[4] : null,	// 活用形
			'conjugation'   => $info[5] !== '*' ? $info[5] : null,	// 活用型
			'origin'        => $info[6] !== '*' ? $info[6] : null,	// 原形
		);
	}
	return $analisys;
}

function mecab_wakati($input){
	if (!extension_loaded('mecab')) {
		$str = mecab_stdio('-O wakati', $input);
		return $str;
	}else{
		$mecab = new MeCab_Tagger();
		return $mecab->keyword($input);
	}
}

function mecab_reading($input){
	if (!extension_loaded('mecab')) {
		return ( mecab_stdio('-Oyomi', $input));
	}else{
		$mecab = new MeCab_Tagger();
		return $mecab->keyword($input);
	}
}