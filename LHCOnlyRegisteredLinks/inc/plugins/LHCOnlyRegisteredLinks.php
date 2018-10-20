<?php
/**
 * This file is part of "LHC Only Registered Links" plugin for MyBB.
 * Copyright (C) Life Heart Club <info@lifeheart.club>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

 /**
 * Disallow direct access to this file for security reasons
 */
if(!defined("IN_MYBB"))
    die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
	
/**
 * Standard MyBB info function
 */
function LHCOnlyRegisteredLinks_info()
{
    global $lang;

    $lang->load("LHCOnlyRegisteredLinks");
	
    $lang->LHCOnlyRegisteredLinksDesc = '<br /><form action="https://www.paypal.com/cgi-bin/webscr" method="post">
		<!-- Identify your business so that you can collect the payments. -->
		<input type="hidden" name="business"
			value="yaeldd@outlook.com">

		<!-- Specify a Donate button. -->
		<input type="hidden" name="cmd" value="_donations">

		<!-- Specify details about the contribution -->
		<input type="hidden" name="item_name" value="MyBB Forums Developer Donation">
		<input type="hidden" name="item_number" value="LHC Only Registered Links">
		<input type="hidden" name="currency_code" value="USD">

		<!-- Display the payment button. -->
		<input type="image" name="submit"
		src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_LG.gif"
		alt="Donate">
		<img alt="" width="1" height="1"
		src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" >
	</form><br />' . $lang->LHCOnlyRegisteredLinksDesc;

    return Array(
        'name' => $lang->LHCOnlyRegisteredLinksName,
        'description' => $lang->LHCOnlyRegisteredLinksDesc,
        'website' => 'http://www.lifeheart.club',
        'author' => 'YaelDD',
        'authorsite' => 'http://yaeldd.lifeheart.club',
        'version' => '1.0.2',
        'guid' => '',
        'compatibility' => '18*'
    );
}

$plugins->add_hook("parse_message", "LHCOnlyRegisteredLinks");

function LHCOnlyRegisteredLinks_is_installed()
{
	global $db;

	// Check if settings gorup exists
	$isplugininstalled = false;
	$query = $db->simple_select('settinggroups', 'name', 'name="lhcorl"');
	while($db->fetch_field($query, 'name'))
	{
		$isplugininstalled = true;
	}

	return $isplugininstalled;
}

function LHCOnlyRegisteredLinks_install() {
	global $db, $lang;
	
	// Create settings group
	$insertarray = array(
		'name' => 'lhcorl', 
		'title' => 'LHC Only Registered Links', 
		'description' => "Settings related to the LHC \"Only Registered Links\" plugin.", 
		'disporder' => 100, 
		'isdefault' => 0
	);
	$gid = $db->insert_query("settinggroups", $insertarray);
	
	// Add settings
	$settingEnabled = array(
		"sid"			=> NULL,
		"name"			=> "lhcorl_enabled",
		"title"			=> "Enable Plugin?",
		"description"	=> "Choose if the plugin is enabled or disabled",
		"optionscode"	=> "yesno",
		"value"			=> 0,
		"disporder"		=> 0,
		"gid"			=> $gid
	);
	$db->insert_query("settings", $settingEnabled);

	$settingContent = array(
		"sid"			=> NULL,
		"name"			=> "lhcorl_content",
		"title"			=> "Replace links by the following text",
		"description"	=> "Enter the text to show instead links for those users who are \"Guests\" or \"Awaiting Activation\"",
		"optionscode"	=> "text",
		"value"			=> "Only registered and activated users can see links",
		"disporder"		=> 1,
		"gid"			=> $gid
	);
	$db->insert_query("settings", $settingContent);
	
	$settingButtonText = array(
		"sid"			=> NULL,
		"name"			=> "lhcorl_linktext",
		"title"			=> "Button Text",
		"description"	=> "Enter the text to show in the register button",
		"optionscode"	=> "text",
		"value"			=> "Click here to register",
		"disporder"		=> 2,
		"gid"			=> $gid
	);
	$db->insert_query("settings", $settingButtonText);

	$settingExcludedForums = array(
		"sid"			=> NULL,
		"name"			=> "lhcorl_exludedforums",
		"title"			=> "Excluded Forums",
		"description"	=> "Enter a comma separated list of Forums IDs which will show the links even if the plugin is enabled.",
		"optionscode"	=> "text",
		"value"			=> "",
		"disporder"		=> 3,
		"gid"			=> $gid
	);
	$db->insert_query("settings", $settingExcludedForums);
	
	rebuild_settings();
}

function LHCOnlyRegisteredLinks_uninstall() {
	global $db, $mybb;
	// Delete settings group
	$db->delete_query("settinggroups", "name = 'lhcorl'");

	// Remove settings
	$db->delete_query('settings', 'name IN ( \'lhcorl_enabled\',\'lhcorl_content\',\'lhcorl_linktext\',\'lhcorl_exludedforums\')');

	rebuild_settings();
}

function LHCOnlyRegisteredLinks_activate() {
	global $db;

	$db->update_query("settings", ["value" => 1], "name='lhcorl_enabled'");
	rebuild_settings();
}

function LHCOnlyRegisteredLinks_deactivate() {
	global $db;

	$db->update_query("settings", ["value" => 0], "name='lhcorl_enabled'");
	rebuild_settings();
}

function LHCOnlyRegisteredLinks($content) {
	global $mybb, $db;
	
	$threadID = $db->escape_string($mybb->input['tid']);
	//echo "THREAD ID: ".$threadID."<br />";
	$forumID = $db->fetch_field($db->simple_select('threads', 'fid', 'tid='.$threadID), 'fid');
	//echo "FORUM ID: ".$forumID."<br />";
	//echo "EXCLUDED FORUMS: ".$mybb->settings['lhcorl_exludedforums']."<br />";
	$excludedForumsArray = explode(",", $mybb->settings['lhcorl_exludedforums']);
	//echo "[EF]ARRAY: ";
	//print_r($excludedForumsArray);

	if($mybb->user['usergroup'] < 2 && !in_array($forumID, $excludedForumsArray))  {
		$content = preg_replace("/<a href=\"(.*?)\" target=\"(.*?)\">(.*?)<\/a>/is","<span style=\"display:block;font-weight:bold;border:2px solid firebrick;padding:10px;margin:10px;\">[".$mybb->settings['lhcorl_content']." <a href=\"".$mybb->settings['bburl']."/member.php?action=register\">".$mybb->settings['lhcorl_linktext']."</a>]</span>",$content);
	}
    return $content;
}
