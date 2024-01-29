<?php

function adminx_config()
{
	$configarray = [
		'name'        => 'Wgs Adminx',
		'description' => 'This addon provide you the access to control the Adminx whmcs Admin theme feature.',
		'version'     => '1.0.3',
		'author'      => '<a href="http://whmcsglobalservices.com/" target="_blank">WHMCS GLOBAL SERVICES</a>',
		'language'    => 'english',
		'fields'      => [
		'license_key' => ['FriendlyName' => 'License key', 'Type' => 'text', 'Size' => '50'],
		'delete_db'   => ['FriendlyName' => 'Delete Database Table', 'Type' => 'yesno', 'Default' => 'yes', 'Description' => 'Tick this box to delete the addon module database table when deactivating the module.']
	]
	];
	return $configarray;
}

function adminx_activate()
{
	$intialIzeClassWgs = new WGS\ADMINXTHEME\WGSADMINXTHEME\wgsAdminxAdminThemeClasses();
	$response = $intialIzeClassWgs->wgsAdminxTableCreate();
	return ['status' => 'success', 'description' => 'Activated successfully.'];
	return ['status' => 'error', 'description' => $response];
	return ['status' => 'info', 'description' => 'This addon provide you the access to control the Adminx whmcs admin theme feature.'];
}

function adminx_deactivate()
{
	$intialIzeClassWgs = new WGS\ADMINXTHEME\WGSADMINXTHEME\wgsAdminxAdminThemeClasses();
	$response = $intialIzeClassWgs->wgsAdminxTableDrop();
	return ['status' => 'success', 'description' => 'Deactivated successfully.'];
	return ['status' => 'error', 'description' => $response];
	return ['status' => 'info', 'description' => 'This addon provide you the access to control the Adminx whmcs admin theme feature.'];
}

function adminx_output($vars)
{
	global $whmcs;
	$imgPath = '../modules/addons/adminx/assets/images/';
	$cssPath = '../modules/addons/adminx/assets/css/';
	$jsPath = '../modules/addons/adminx/assets/js/';
	$assetsPath = '../modules/addons/adminx/assets/';
	$modulelink = $vars['modulelink'];
	$LANG = $vars['_lang'];
	if (isset($_POST['ajaxCallAdminxTheme']) && ($_POST['ajaxCallAdminxTheme'] == 'proceed')) {
		require_once __DIR__ . '/pages/' . 'ajax.php';
		exit();
	}

	if (file_exists(__DIR__ . '/pages/include_pages.php')) {
		require_once __DIR__ . '/pages/include_pages.php';
	}
}

if (!defined('WHMCS')) {
	exit('This file cannot be accessed directly');
}

global $whmcs;

if (file_exists(__DIR__ . '/lib/class.php')) {
	require_once __DIR__ . '/lib/class.php';
}

?>