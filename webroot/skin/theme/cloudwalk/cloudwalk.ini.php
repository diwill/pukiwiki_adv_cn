<?php
// PukiWiki - Yet another WikiWikiWeb clone.
// $Id: cloudwalk.ini.php,v1.2.1 2011/09/11 22:56:30 Logue Exp $
// Copyright (C) 2010-2011 PukiWiki Advance Developers Team
// PukiWiki Advance Clowdwalk skin
//
// License: GPL v2 or (at your option) any later version
// http://www.opensource.org/licenses/gpl-2.0.html
//
// ------------------------------------------------------------
// Settings (define before here, if you want)
global $_SKIN, $link_tags, $js_tags;


$_SKIN = array(
/*
UI Themes
jQuery(jQuery UI): 
	base, black-tie, blitzer, cupertino, dark-hive, dot-luv, eggplant, excite-bike, flick, hot-sneaks
	humanity, le-frog, mint-choc, overcast, pepper-grinder, redmond, smoothness, south-street,
	start, sunny, swanky-purse, trontastic, ui-darkness, ui-lightness, vader

see also
http://www.devcurry.com/2010/05/latest-jquery-and-jquery-ui-theme-links.html
http://jqueryui.com/themeroller/
*/
	'ui_theme'		=> 'cupertino',

	// Navibar系プラグインでもアイコンを表示する
	'showicon'		=> true,

	// アドレスの代わりにパスを表示
	'topicpath'		=> true,

);

// 読み込むスタイルシート
$link_tags[] = array('rel'=>'stylesheet','href'=>SKIN_URI.'scripts.css.php?base=' . urlencode(IMAGE_URI) );
$link_tags[] = array('rel'=>'stylesheet','href'=>SKIN_URI.THEME_PLUS_NAME.PLUS_THEME.'/'.PLUS_THEME.'.css.php');
// 読み込むスクリプト
$js_tags[] = array('type'=>'text/javascript', 'src'=>SKIN_URI.THEME_PLUS_NAME.PLUS_THEME.'/'.PLUS_THEME.'.js', 'defer'=>'defer');

/* End of file cloudwalk.ini.php */
/* Location: ./webroot/skin/theme/cloudwalk/cloudwalk.ini.php */
