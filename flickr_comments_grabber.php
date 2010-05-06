<?php

/******************************************************************/
/* Name: Flickr Comments Grabber
/* Version: v0.2.3
/* Description: Retrieve your image comments from Flickr
/* Author: Aymeric Brisse <aymeric.brisse@gmail.com>
/* License: GNU GPL 2.0
/******************************************************************/

$addon_name = "Flickr Comments Grabber";
$addon_version = "0.2.3";
$api_key = "fc7815d7cf9297ba57023aa76787f3b3";
$service_url = 'http://www.dev-it.com/clients/pixelpost/options.php';

if( isset( $_GET['view']) && $_GET['view']=='addons') {
	
	global $cfgrow;
	if(!isset($_SESSION["pixelpost_admin"]) || $cfgrow['password'] != $_SESSION["pixelpost_admin"] || $_GET["_SESSION"]["pixelpost_admin"] == $_SESSION["pixelpost_admin"] || $_POST["_SESSION"]["pixelpost_admin"] == $_SESSION["pixelpost_admin"])  die ("Try another day!!");
	
	/* Admin Area */
	/* Note : this plugin is not an 'admin' plugin since it requires 'normal' features to be executed from a distant website (cron jobs) */
	
	$site_url = $cfgrow['siteurl'];
	$flickr_content = "";
	
	/* Update Auto-Update Status */
	
	if( isset($_POST['flickr_settings_update']) && isset($_POST['state']) ) {
		$flickr_content .= "&nbsp;&nbsp;<b><font color='red'>" . file_get_contents($service_url . '?action=manage_subscription&state=' . $_POST['state'] . '&url=' . urlencode($site_url)) . "</font></b><br/>";
		if ($_POST['state'])
			$flickr_comments_update = "all";
	}
	
	/* Reset */
	
	if( isset($_POST['flickr_comments_remove'])) {
		require_once("phpFlickr/phpFlickr.php");
		$f = new phpFlickr($api_key);
		$flickr_content .= $f->resetComments();
	}
	
	/* Retrieve comments */
	if ( isset($_POST['flickr_comments_update']) ) {	
	
		$flickr_id = trim($_POST['f_id']);
		
		if (!empty($flickr_id))
			$flickr_comments_update = "unique";
		else
			$flickr_comments_update = "all";
	
		require_once("phpFlickr/phpFlickr.php");
		$f = new phpFlickr($api_key);		
		$flickr_content .= $f->grabComments( $flickr_comments_update, $_POST['f_id'] );		
	}
	
	
	/* Check for plugin updates */
	$last_version = file_get_contents($service_url . '?action=check_update');
	
	if ($last_version!=$addon_version)
		$please_update = true;
	else
		$please_update = false;
	
	/* Display Feedback Messages */
	
	if (isset($flickr_content))			
		echo $flickr_content . '<p>&nbsp;</p>';		
	
	$flickr_infos = file_get_contents($service_url . '?action=subscription_status&url=' . urlencode($site_url));
	
	if (substr($flickr_infos, 0, 1)=='@') {
		$flickr_state = (bool)substr($flickr_infos, 1,1);
		$flickr_last_update = substr($flickr_infos, 3);
	}
	else {
		$flickr_state = false;
	}
	
	/* Unique Photo */
	
	$description = "";
	
	if ($please_update) {
		$description .= "<h3><font color='red'>New Update available : " . $addon_name . " " . $last_version . ". Please update!</font> [ <a href='http://www.pixelpost.org/extend/addons/flickr-comments-grabber/'><font color='blue'>Download Link</font></a> ]</h3>";
		
	}

	$description .= "<h3>Retrieve comments</h3>";

	$description .= "<form action='".$site_url."admin/index.php?view=addons' method='post' accept-charset='UTF-8'>
	Retrieve the comments of all your photos. If you only want to retrieve the comments of a specified photo, enter the Flickr ID of the photo in the input text<br>
	Duration of the operation : 2 images per second.<br>
	<br>
	Flickr ID (<i>optional</i>) : <input type='text' size='10' value='' name='f_id'>
	<input type='submit' value='grab !' name='flickr_comments_update'>
	</form>";
	
	/* Auto-Update */
	
	$description .= "<h3>Auto-Update</h3>";
	
	$description .= "<form action='".$site_url."admin/index.php?view=addons' method='post' accept-charset='UTF-8'>
	If you want to retrieve automatically the comments of all your photos (once a day at 00:00:00 UTC).<br />
	Enable this option will first retrieve the comments of all your photos | Execution Time : 2 images per second.
	<br /><br />
	<input type='radio' name='state' id='state1' value='1' " . ($flickr_state ? 'checked="checked" ' : '') . "/>
	<label for='state1'>Enabled</label>	
	<input type='radio' name='state' id='state2' value='0' " . (!$flickr_state ? 'checked="checked" ' : '') . "/>
	<label for='state2'>Disabled</label>
	<input type='submit' value='save !' name='flickr_settings_update' >" . ($flickr_state ? '&nbsp;&nbsp;&nbsp;<strong>Last update: ' . $flickr_last_update . '</strong>' : '') . "
	</form>";		
		
	/* Reset */
	
	$description .= "<h3>Reset</h3>";
	
	$description .= "<form action='".$site_url."admin/index.php?view=addons' method='post' accept-charset='UTF-8'>
	If you want to remove all the comments that have been retrieved from Flickr (your other comments will not be removed).
	<br /><br />
	<input type='submit' value='reset !' name='flickr_comments_remove'>
	</form>";	
	
	/* About */
	
	$addon_description = "Retrieve your image comments from Flickr. In order for it to work, please insert the Flickr ID into the photo's tags (ex : 4518790540).<p>".$description."<p>Created by Aymeric Brisse (<a href='http://www.agregats.net/' target='_blank'>agregats.net</a>) - (<a href='http://www.pixelpost.org/extend/addons/flickr-comments-grabber/' target='_blank'>Plugin Page</a>) (<a href='http://www.pixelpost.org/forum/showthread.php?t=11883' target='_blank'>Forum</a>) </p>";
}
elseif( isset( $_GET['action']) && $_GET['action']=='flickr_comments_update') {
	
	/* Retrieve comments - via AutoUpdate */
	
	require_once("phpFlickr/phpFlickr.php");
	$f = new phpFlickr($api_key);
	$flickr_content .= $f->grabComments( "all" );		
	
	/* Log */
	
	die($flickr_content);
	
}
 
?>