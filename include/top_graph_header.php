<?php
/*
 +-------------------------------------------------------------------------+
 | Copyright (C) 2004-2015 The Cacti Group                                 |
 |                                                                         |
 | This program is free software; you can redistribute it and/or           |
 | modify it under the terms of the GNU General Public License             |
 | as published by the Free Software Foundation; either version 2          |
 | of the License, or (at your option) any later version.                  |
 |                                                                         |
 | This program is distributed in the hope that it will be useful,         |
 | but WITHOUT ANY WARRANTY; without even the implied warranty of          |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the           |
 | GNU General Public License for more details.                            |
 +-------------------------------------------------------------------------+
 | Cacti: The Complete RRDTool-based Graphing Solution                     |
 +-------------------------------------------------------------------------+
 | This code is designed, written, and maintained by the Cacti Group. See  |
 | about.php and/or the AUTHORS file for specific developer information.   |
 +-------------------------------------------------------------------------+
 | http://www.cacti.net/                                                   |
 +-------------------------------------------------------------------------+
*/

global $menu, $config;
$using_guest_account = false;
$show_console_tab = true;

$oper_mode = api_plugin_hook_function('top_graph_header', OPER_MODE_NATIVE);
if ($oper_mode == OPER_MODE_RESKIN) {
	return;
}

/* ================= input validation ================= */
input_validate_input_number(get_request_var_request('local_graph_id'));
input_validate_input_number(get_request_var_request('graph_start'));
input_validate_input_number(get_request_var_request('graph_end'));
/* ==================================================== */

if (read_config_option('auth_method') != 0) {
	/* at this point this user is good to go... so get some setting about this
	user and put them into variables to save excess SQL in the future */
	$current_user = db_fetch_row_prepared('SELECT * FROM user_auth WHERE id = ?', array($_SESSION['sess_user_id']));

	/* find out if we are logged in as a 'guest user' or not */
	if (db_fetch_cell_prepared('SELECT id FROM user_auth WHERE username = ?', array(read_config_option('guest_user'))) == $_SESSION['sess_user_id']) {
		$using_guest_account = true;
	}

	/* find out if we should show the "console" tab or not, based on this user's permissions */
	if (sizeof(db_fetch_assoc_prepared('SELECT realm_id FROM user_auth_realm WHERE realm_id = 8 AND user_id = ?', array($_SESSION['sess_user_id']))) == 0) {
		$show_console_tab = false;
	}
}

/* need to correct $_SESSION["sess_nav_level_cache"] in zoom view */
if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'zoom') {
	$_SESSION['sess_nav_level_cache'][2]['url'] = 'graph.php?local_graph_id=' . $_REQUEST['local_graph_id'] . '&rra_id=0';
}

$page_title = api_plugin_hook_function('page_title', draw_navigation_text('title'));

//<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
?>
<!DOCTYPE html>
<html>
<head>
	<meta http-equiv='X-UA-Compatible' content='IE=edge'>
	<meta content='width=720, initial-scale=1.2, maximum-scale=1.2, minimum-scale=1.2' name='viewport'>
	<title><?php echo $page_title; ?></title>
	<meta http-equiv='Content-Type' content='text/html;charset=utf-8'>
	<link href='<?php echo $config['url_path']; ?>include/themes/<?php print read_config_option('selected_theme');?>/main.css' type='text/css' rel='stylesheet'>
	<link href='<?php echo $config['url_path']; ?>include/themes/<?php print read_config_option('selected_theme');?>/jquery.zoom.css' type='text/css' rel='stylesheet'>
	<link href='<?php echo $config['url_path']; ?>include/themes/<?php print read_config_option('selected_theme');?>/jquery-ui.css' type='text/css' rel='stylesheet'>
	<link href='<?php echo $config['url_path']; ?>include/themes/<?php print read_config_option('selected_theme');?>/default/style.css' type='text/css' rel='stylesheet'>
	<link href='<?php echo $config['url_path']; ?>include/themes/<?php print read_config_option('selected_theme');?>/jquery.multiselect.css' type='text/css' rel='stylesheet'>
	<link href='<?php echo $config['url_path']; ?>include/themes/<?php print read_config_option('selected_theme');?>/jquery.timepicker.css' type='text/css' rel='stylesheet'>
	<link href='<?php echo $config['url_path']; ?>include/fa/css/font-awesome.css' type='text/css' rel='stylesheet'>
	<link href='<?php echo $config['url_path']; ?>images/favicon.ico' rel='shortcut icon'>
	<?php api_plugin_hook('page_head'); ?>
	<script type='text/javascript' src='<?php echo $config['url_path']; ?>include/js/jquery.js'></script>
	<script type='text/javascript' src='<?php echo $config['url_path']; ?>include/js/jquery-ui.js'></script>
	<script type='text/javascript' src='<?php echo $config['url_path']; ?>include/js/jquery.cookie.js'></script>
	<script type='text/javascript' src='<?php echo $config['url_path']; ?>include/js/jquery.storageapi.js'></script>
	<script type='text/javascript' src='<?php echo $config['url_path']; ?>include/js/jstree.js'></script>
	<script type='text/javascript' src='<?php echo $config['url_path']; ?>include/js/jquery.hotkeys.js'></script>
	<script type='text/javascript' src='<?php echo $config['url_path']; ?>include/js/jquery.zoom.js'></script>
	<script type='text/javascript' src='<?php echo $config['url_path']; ?>include/js/jquery.multiselect.js'></script>
	<script type='text/javascript' src='<?php echo $config['url_path']; ?>include/js/jquery.timepicker.js'></script>
	<script type='text/javascript' src='<?php echo $config['url_path']; ?>include/jscalendar/calendar.js'></script>
	<script type='text/javascript' src='<?php echo $config['url_path']; ?>include/jscalendar/lang/calendar-en.js'></script>
	<script type='text/javascript' src='<?php echo $config['url_path']; ?>include/jscalendar/calendar-setup.js'></script>
	<script type='text/javascript' src='<?php echo $config['url_path']; ?>include/layout.js'></script>
	<script type='text/javascript' src='<?php echo $config['url_path']; ?>include/themes/<?php print read_config_option('selected_theme');?>/main.js'></script>
	<script type='text/javascript' src='<?php echo $config['url_path'] . 'include/realtime.js';?>'></script>
	<?php include($config['base_path'] . '/include/global_session.php'); api_plugin_hook('page_head'); ?>
</head>

<?php if ($oper_mode == OPER_MODE_NATIVE) {?>
<body <?php print api_plugin_hook_function('body_style', '');?>>
<?php }else{?>
<body <?php print api_plugin_hook_function('body_style', '');?>>
<?php }?>

<table style='width:100%;'>
<?php if ($oper_mode == OPER_MODE_NATIVE) { ;?>
	<tr class='cactiPageHead noprint'>
		<td class='cactiGraphPageHeadBackdrop'>
			<table style='width:100%;vertical-align:bottom;'>
				<tr>
					<td id='tabs'>
						<?php print html_show_tabs_left($show_console_tab); ?>
					</td>
					<td id='gtabs'>
						<?php print html_graph_tabs_right($current_user);?>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
<table style='width:100%;'>
<?php } elseif ($oper_mode == OPER_MODE_NOTABS) { api_plugin_hook_function('print_top_header'); } ?>
	<tr class='breadCrumbBar noprint'>
		<td>
			<table style='width:100%;'>
				<tr>
					<td>
						<div id='navBar' class='navBar'>
							<?php echo draw_navigation_text();?>
						</div>
						<div class='scrollBar'></div>
						<div class='infoBar'>
							<?php echo draw_login_status();?>
						</div>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
<table style='width:100%;'>
	<?php

	global $graph_views;
	load_current_session_value('action', 'sess_cacti_graph_action', $graph_views['2']);
	?>
	<tr>
		<?php if (basename($_SERVER['PHP_SELF']) == 'graph_view.php' && ($_REQUEST['action'] == 'tree' || (isset($_REQUEST['view_type']) && $_REQUEST['view_type'] == 'tree'))) { ?>
		<td id='navigation' class='cactiTreeNavigationArea noprint' style='display:none;vertical-align:top;width:200px;'>
			<?php grow_dhtml_trees();?>
		</td>
		<?php } ?>
		<td id='navigation_right' class='cactiGraphContentArea' style='display:none;vertical-align:top;'><div id='message_container'><?php print display_output_messages();?></div><div style='position:static;' id='main'>
