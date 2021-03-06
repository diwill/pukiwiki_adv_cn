<?php
// PukiWiki - Yet another WikiWikiWeb clone
// $Id: links.inc.php,v 1.24.4 2011/06/08 20:05:00 Logue Exp $
//
// Update link cache plugin

// Message setting
function plugin_links_init()
{
	$messages = array(
		'_links_messages'=>array(
			'title_update'  => T_('Cache update'),
			'msg_adminpass' => T_('Administrator password'),
			'btn_submit'    => T_('Exec'),
			'btn_force'		=> T_('Force'),
			'msg_done'      => T_('The update of cashe was completed.'),
			'msg_error'		=> T_('The update of cashe was failure. Please check password.'),
			'msg_usage1'	=> 
				T_('* Content of processing') . "\n" .
				T_(':Cache update|') . "\n" .
				T_('All pages are scanned, whether on which page certain pages have been linked is investigated, and it records in the cache.') ."\n\n" .
				T_('* CAUTION') . "\n" .
				T_('It is likely to drive it for a few minutes in execution.') .
				T_('Please wait for a while after pushing the execution button.') . "\n\n",
			'msg_usage2'	=> 
				T_('* EXEC') ."\n" .
				T_('Please input the Administrator password, and click the [Exec] button.') . "\n"
		),
	);
	set_plugin_messages($messages);
}

function plugin_links_action()
{
	global $post, $vars, $foot_explain;
	global $_links_messages, $_string;

	// if (PKWK_READONLY) die_message('PKWK_READONLY prohibits this');
	if (auth::check_role('readonly')) die_message( $_string['error_prohibit'] );

	$admin_pass = (empty($post['adminpass'])) ? '' : $post['adminpass'];
	if ( isset($vars['menu']) && (! auth::check_role('role_adm_contents') || pkwk_login($admin_pass) )) {
		$force = (isset($post['force']) && $post['force'] === 'on') ? true : false;
		$msg  = & $_links_messages['title_update'];

		if (update_cache(null, $force) !== false){
			$foot_explain = array(); // Exhaust footnotes
			$body = & $_links_messages['msg_done'];
			return array('msg'=>$msg, 'body'=>$body);
		}else{
			return array('msg'=>$msg, 'body'=>$_links_messages['msg_failure']);
		}

		return array('msg'=>$msg, 'body'=>$body);
	}

	$msg   = & $_links_messages['title_update'];
	$body  = convert_html( sprintf($_links_messages['msg_usage1']) );
	$script = get_script_uri();
	$body .= <<<EOD
<form method="post" action="$script" class="links_form">
	<input type="hidden" name="cmd" value="links" />
	<input type="hidden" name="menu" value="1" />
EOD;
	if (auth::check_role('role_adm_contents')) {
		$body .= convert_html( sprintf($_links_messages['msg_usage2']) );
		$body .= <<<EOD
	<label for="_p_links_adminpass">{$_links_messages['msg_adminpass']}</label>
	<input type="password" name="adminpass" id="_p_links_adminpass" size="20" value="" />
EOD;
	}
	$body .= <<<EOD
	<input type="checkbox" name="force" id="_c_force" />
	<label for="_c_force">{$_links_messages['btn_force']}</label>
	<input type="submit" value="{$_links_messages['btn_submit']}" />
</form>
EOD;

	return array('msg'=>$msg, 'body'=>$body);
}
/* End of file links.inc.php */
/* Location: ./wiki-common/plugin/links.inc.php */
