<?php

function wgsAdminXOrderStatData()
{
	$orderStat = [];
	$orderData = WHMCS\Database\Capsule::table('tblorders')->select('status', WHMCS\Database\Capsule::raw('count(*) as orderCount'))->groupBy('status')->get();

	if (0 < count($orderData)) {
		foreach ($orderData as $orderDataObj) {
			$orderStat[strtolower($orderDataObj->status)] = $orderDataObj->orderCount;
		}
	}

	return $orderStat;
}

function wgsAdminXInvoiceStatData()
{
	$invoiceStat = [];
	$invoiceStat['unpaid'] = WHMCS\Billing\Invoice::unpaid()->count('id');
	$invoiceStat['overdue'] = WHMCS\Billing\Invoice::overdue()->count('id');
	$invoiceStat['cancelled'] = WHMCS\Billing\Invoice::cancelled()->count('id');
	$invoiceStat['paymentpending'] = WHMCS\Billing\Invoice::paymentpending()->count('id');
	$invoiceStat['collections'] = WHMCS\Billing\Invoice::collections()->count('id');
	$invoiceStat['refunded'] = WHMCS\Billing\Invoice::refunded()->count('id');
	$invoiceStat['paid'] = WHMCS\Billing\Invoice::paid()->count('id');
	return $invoiceStat;
}

function wgsAdminxTicketStatData()
{
	$supprtDeptArray = [];
	$ticketCountsAdminx = [];
	$ticketAllStatus = [];
	$activeTicket = $awaitingTicket = 0;
	$getLoggedInAdminDetail = WHMCS\Database\Capsule::table('tbladmins')->select('supportdepts')->where('id', $_SESSION['adminid'])->first();
	$getSupportDepartmentArray = explode(',', $getLoggedInAdminDetail->supportdepts);

	foreach ($getSupportDepartmentArray as $getSupportDepartmentArrayObj) {
		if (trim($getSupportDepartmentArrayObj)) {
			$supprtDeptArray[] = $getSupportDepartmentArrayObj;
		}
	}

	if (0 < count($supprtDeptArray)) {
		$ticketDataGetFromTable = WHMCS\Database\Capsule::table('tblticketstatuses')->select('tblticketstatuses.title', 'tblticketstatuses.showactive', 'tblticketstatuses.showactive', 'tblticketstatuses.showawaiting', WHMCS\Database\Capsule::raw('(SELECT COUNT(tbltickets.id) as ticketCount FROM tbltickets WHERE did IN(' . db_build_in_array($supprtDeptArray) . ') AND tbltickets.status=tblticketstatuses.title AND tbltickets.merged_ticket_id = \'0\') as countTicket'))->orderBy('sortorder', 'ASC')->get();

		foreach ($ticketDataGetFromTable as $ticketDataGetFromTableObj) {
			$titleKeyCreate = str_replace(' ', '_', $ticketDataGetFromTableObj->title);
			$ticketCountsAdminx[$titleKeyCreate] = $ticketDataGetFromTableObj->countTicket;

			if ($ticketDataGetFromTableObj->showactive) {
				$activeTicket += $ticketDataGetFromTableObj->countTicket;
			}

			if ($ticketDataGetFromTableObj->showawaiting) {
				$awaitingTicket += $ticketDataGetFromTableObj->countTicket;
			}
		}

		$flaggedTicketData = WHMCS\Database\Capsule::table('tbltickets')->where('status', '!=', 'Closed')->where('merged_ticket_id', '0')->where('flag', (int) $_SESSION['adminid'])->whereIn('did', $supprtDeptArray)->count();
		$ticketAllStatus['adminxTicketCountStatus'] = $ticketCountsAdminx;
		$ticketAllStatus['adminxTicketActive'] = $activeTicket;
		$ticketAllStatus['adminxTicketAwaiting'] = $awaitingTicket;
		$ticketAllStatus['adminxTicketFlagged'] = $flaggedTicketData;
	}

	return $ticketAllStatus;
}

if (!defined('WHMCS')) {
	exit('This file cannot be accessed directly');
}

add_hook('AdminAreaPage', 1, function($vars) {
	global $whmcs;
	global $customadminpath;
	$templateName = $vars['template'];
	if (isset($_POST['ajaxCallAdminxThemeStyle']) && ($_POST['ajaxCallAdminxThemeStyle'] == 'proceed')) {
		$colorStyle = $whmcs->get_req_var('colorStyle');
		$countDataSettings = WHMCS\Database\Capsule::table('mod_adminx_theme_setting')->where('setting', 'color_style_adminx')->count();

		if (0 < $countDataSettings) {
			WHMCS\Database\Capsule::table('mod_adminx_theme_setting')->where('setting', 'color_style_adminx')->update(['value' => $colorStyle]);
		}
		else {
			WHMCS\Database\Capsule::table('mod_adminx_theme_setting')->insert(['setting' => 'color_style_adminx', 'value' => $colorStyle]);
		}

		exit();
	}

	if ($vars['filename'] != '') {
		$fileName = $vars['filename'];
	}
	else {
		$fileName = 'common';
	}

	$classNamePages = $fileName . '-page-data';
	$actionCls = '';

	if (isset($_GET['action'])) {
		$actionCls = '-' . strtolower($_GET['action']);
	}

	if (isset($_GET['status'])) {
		$replaceSpace = str_replace(' ', '-', $_GET['status']);
		$actionCls = $actionCls . '-' . strtolower($replaceSpace);
	}

	if (isset($_GET['view'])) {
		$replaceSpace = str_replace(' ', '-', $_GET['view']);
		$actionCls = $actionCls . '-view-' . strtolower($replaceSpace);
	}

	$classNamePagesInner = $fileName . '-page-inner-data' . $actionCls;
	$adminThemeSettings = [];
	$countSettingData = WHMCS\Database\Capsule::table('mod_adminx_theme_setting')->count();

	if (0 < $countSettingData) {
		$adminSettingData = WHMCS\Database\Capsule::table('mod_adminx_theme_setting')->get();

		foreach ($adminSettingData as $adminSettingDataObj) {
			$adminThemeSettings[$adminSettingDataObj->setting] = $adminSettingDataObj->value;
		}
	}

	$defaultAdminFolder = 'admin';

	if ($customadminpath != '') {
		$defaultAdminFolder = $customadminpath;
	}

	$addonAdminxActivate = true;

	if (file_exists(__DIR__ . '/lib/class.php')) {
		require_once __DIR__ . '/lib/class.php';
		$getLicenseDetailAdminx = WHMCS\Database\Capsule::table('tbladdonmodules')->where('setting', 'license_key')->where('module', 'adminx')->first();
		$adminX = new WGS\ADMINXTHEME\WGSADMINXTHEME\wgsAdminxAdminThemeClasses();
		$license = $adminX->wgsAdminxThemeLicenseCheck($getLicenseDetailAdminx->value);
		$status = trim($license['status']);

		if ($status == 'Active') {
			$addonAdminxActivate = true;
		}
	}

	$extraVariables['adminXPagesClass'] = $classNamePages;
	$extraVariables['adminXPagesInnerClass'] = $classNamePagesInner;
	$extraVariables['adminXOnlyPagesNameClass'] = $fileName;
	$extraVariables['adminXLicenseState'] = 'inactive';
	$extraVariables['adminXOverrideCssState'] = 'inactive';
	if (!empty($addonAdminxActivate) && ($templateName == 'adminx')) {
		$extraVariables['adminXSettingsData'] = $adminThemeSettings;
		$extraVariables['adminXSideBarStatData']['order'] = wgsAdminXOrderStatData();
		$extraVariables['adminXSideBarStatData']['invoice'] = wgsAdminXInvoiceStatData();
		$extraVariables['adminXSideBarStatData']['tickets'] = wgsAdminxTicketStatData();
		$extraVariables['adminXLicenseState'] = 'active';

		if (file_exists(ROOTDIR . '/' . $defaultAdminFolder . '/templates/adminx/css/adminx_overrides.css')) {
			$extraVariables['adminXOverrideCssState'] = 'active';
		}
	}

	return $extraVariables;
});
add_hook('AdminHomepage', 1, function($vars) {
	$templateName = $vars['template'];
	$script = '<script>' . "\n" . '    jQuery(document).ready(function(){' . "\n\t\t" . 'if(jQuery("#panelMarketConnect").length > 0){' . "\n\t\t\t" . 'jQuery("#panelMarketConnect .selling-status").find(".service").each(function(){' . "\n\t\t\t\t" . 'jQuery("<div class=\'market-wrap-content\'></div>").insertBefore(jQuery(this).find("img"));' . "\n\t\t\t\t" . 'jQuery(jQuery(this).find("img")).appendTo(jQuery(this).find(".market-wrap-content"));' . "\n\t\t\t\t" . 'jQuery(jQuery(this).find(".title")).appendTo(jQuery(this).find(".market-wrap-content"));' . "\n\t\t\t" . '});' . "\t\t\t\n\t\t" . '}' . "\n\t\t" . 'if(jQuery("#panelActivity").length > 0){' . "\n\t\t\t" . 'jQuery(".feed-element div:first-child").addClass(\'adminx-activity-main-sec\');' . "\n\t\t\t" . 'jQuery(".adminx-activity-main-sec").find("div").addClass(\'adminx-activity-content-sec\');' . "\t\t\t\n\t\t" . '}' . "\n" . '   ' . "\t" . '});' . "\n" . '   </script>';
	$addonAdminxActivate = false;

	if (file_exists(__DIR__ . '/lib/class.php')) {
		require_once __DIR__ . '/lib/class.php';
		$getLicenseDetailAdminx = WHMCS\Database\Capsule::table('tbladdonmodules')->where('setting', 'license_key')->where('module', 'adminx')->first();
		$adminX = new WGS\ADMINXTHEME\WGSADMINXTHEME\wgsAdminxAdminThemeClasses();
		$license = $adminX->wgsAdminxThemeLicenseCheck($getLicenseDetailAdminx->value);
		$status = trim($license['status']);

		if ($status == 'Active') {
			$addonAdminxActivate = true;
		}
	}
	if (!empty($addonAdminxActivate) && ($templateName == 'adminx')) {
		return $script;
	}
});
add_hook('AdminAreaFooterOutput', 1, function($vars) {
	$templateName = $vars['template'];
	$script = '<script>' . "\n" . '    jQuery(document).ready(function(){' . "\n\t\t" . 'if(jQuery("form#orderfrm").length > 0){' . "\n\t\t\t" . 'jQuery("form#orderfrm").find(".col-md-8").addClass("adminx-col8");' . "\t\t\t\n\t\t\t" . 'jQuery("form#orderfrm").find(".col-md-4").addClass("adminx-col4");' . "\n\t\t\t" . 'jQuery("<div class=\'order-summary-wrap-main\'></div>").insertBefore(jQuery("form#orderfrm").find("#ordersumm"));' . "\n\t\t\t" . 'jQuery(jQuery("form#orderfrm").find("#ordersumm")).appendTo(jQuery(".order-summary-wrap-main"));' . "\n\t\t\t" . 'jQuery(jQuery("form#orderfrm").find(".adminx-col4 .ordersummarytitle")).appendTo(jQuery(".order-summary-wrap-main"));' . "\n\t\t\t" . 'jQuery("form#orderfrm").find("a.addproduct").find("img").attr(\'src\',adminxTemplateImageName+\'plus-circle.svg\');' . "\n\t\t\t" . 'jQuery("form#orderfrm").find("a.adddomain").find("img").attr(\'src\',adminxTemplateImageName+\'plus-circle.svg\');' . "\t\t\n\t\t" . '}' . "\n\t\t" . 'if(jQuery("form#clientinfo").length > 0){' . "\n\t\t\t" . 'if(jQuery("#newclientform").length > 0){' . "\n\t\t\t\t" . 'jQuery("<div class=\'table-wrap-line-iteam\'></div>").insertAfter(jQuery("#newclientform"));' . "\n\t\t\t\t" . 'jQuery(jQuery(".table-wrap-line-iteam").next("h2")).appendTo(jQuery(".table-wrap-line-iteam"));' . "\n\t\t\t\t" . 'jQuery(jQuery(".table-wrap-line-iteam").next(".tablebg")).appendTo(jQuery(".table-wrap-line-iteam"));' . "\n\t\t\t" . '}' . "\n\t\t" . '}' . "\n\t\t" . 'if(jQuery("#servicecontent").length > 0){' . "\n\t\t\t" . 'if(jQuery("#servicecontent").find("form").find("table").length > 0){' . "\n\t\t\t\t" . 'jQuery("#servicecontent").find("form").find("table tr").each(function(){' . "\n\t\t\t\t\t" . 'if(typeof jQuery(this).find("input").eq(0).attr("name") !== "undefined"){' . "\n\t\t\t\t\t\t" . 'var classNameTr = jQuery(this).find("input").eq(0).attr("name");' . "\n\t\t\t\t\t\t" . 'jQuery(this).addClass(classNameTr);' . "\n\t\t\t\t\t" . '}' . "\n\t\t\t\t" . '});' . "\n\t\t\t" . '}' . "\n\t\t" . '}' . "\n\t\t" . 'if(jQuery("#profileContent").length > 0){' . "\n\t\t\t" . 'jQuery("#profileContent").find("form").eq(0).addClass("clientDomainsForm");' . "\n\t\t\t" . 'jQuery("#profileContent").find("form").eq(1).addClass("clientAddDomainsContactForm");' . "\n\t\t" . '}' . "\n\t\t" . 'if(jQuery("#frmRecordsFound").length > 0){' . "\n\t\t\t" . 'jQuery("#frmRecordsFound").next("form").addClass("clients-list-form");' . "\n\t\t" . '}' . "\n\t\t" . 'if(jQuery("#togglenotesbtnholder").length > 0){' . "\n\t\t\t" . 'jQuery("#togglenotesbtnholder").next("br").remove();' . "\n\t\t\t" . 'jQuery("#togglenotesbtnholder").prev("table.form").addClass("table-order-form");' . "\n\t\t\t" . 'jQuery("<div class=\'orderPgaeHeadSecWrap\'></div>").insertBefore(jQuery("#togglenotesbtnholder"));' . "\n\t\t\t" . 'jQuery(jQuery(".orderPgaeHeadSecWrap").next("#togglenotesbtnholder")).appendTo(jQuery(".orderPgaeHeadSecWrap"));' . "\n\t\t\t" . 'jQuery(jQuery(".orderPgaeHeadSecWrap").next("h2")).appendTo(jQuery(".orderPgaeHeadSecWrap"));' . "\n\t\t" . '}' . "\n\t\t" . 'if(jQuery("form#frmWhois").length > 0){' . "\n\t\t\t" . 'jQuery("form#frmWhois").next("form").addClass("order-items-form");' . "\n\t\t" . '}' . "\n\t\t" . 'if(jQuery(".billableitems-page-data").length > 0){' . "\n\t\t\t" . 'jQuery(".billableitems-page-data").find("form").eq(0).addClass("billable-items-form-first");' . "\n\t\t" . '}' . "\n\t\t" . 'if(jQuery("table#tblDisputes").length > 0){' . "\n\t\t\t" . 'jQuery("table#tblDisputes").addClass("datatable");' . "\n\t\t" . '}' . "\n\t\t" . 'if(jQuery(".supportcenter-page-data").length > 0){' . "\n\t\t\t" . 'jQuery(".supportcenter-page-data").find("form").next("div").addClass("supportCenterSection");' . "\n\t\t\t" . 'jQuery(".supportCenterSection").removeAttr("style");' . "\n\t\t" . '}' . "\n\t\t" . 'if(jQuery(".reports-index").length > 0){' . "\n\t\t\t" . 'jQuery(".reports-index").find("div").each(function(){' . "\n\t\t\t\t" . 'jQuery(this).addClass("button-row");' . "\n\t\t\t\t" . 'var imgTagIcons = \'<img src="\'+adminxTemplateImageName+\'\'+jQuery(this).prev("h2").text().toLowerCase()+\'_report.svg" class="reports-page-images">\';' . "\n\t\t\t\t" . 'jQuery(\'<div class="reportHeadingWrap"></div>\').prependTo(jQuery(this));' . "\n\t\t\t\t" . 'jQuery(this).find(".reportHeadingWrap").attr("data-image-key",jQuery(this).prev("h2").text().toLowerCase());' . "\n\t\t\t\t" . 'jQuery(jQuery(this).prev("h2")).prependTo(jQuery(this).find(".reportHeadingWrap"));' . "\n\t\t\t\t" . 'jQuery(imgTagIcons).prependTo(jQuery(this).find(".reportHeadingWrap"));' . "\n\t\t\t" . '});' . "\n\t\t\t" . 'jQuery("<div class=\'reportMainSecWrap\'></div>").insertBefore(jQuery(".reports-index"));' . "\n\t\t\t" . 'jQuery(jQuery(".reports-index")).appendTo(jQuery(".reportMainSecWrap"));' . "\n\t\t\t" . 'jQuery(jQuery(".reportMainSecWrap").prev("p")).prependTo(jQuery(".reportMainSecWrap"));' . "\n\t\t\t" . 'jQuery(jQuery(".reportMainSecWrap").prev("h1")).prependTo(jQuery(".reportMainSecWrap"));' . "\n\t\t" . '}' . "\n\t\t" . 'if(jQuery(".reports-page-data").length > 0){' . "\n\t\t\t" . 'jQuery(".reports-page-data").find("table").addClass("datatable");' . "\n\t\t" . '}' . "\n\t\t" . 'if(jQuery(".orders-page-data").find(\'input[name="massaccept"]\').length > 0){' . "\n\t\t\t" . 'jQuery(".orders-page-data").find(\'input[name="massaccept"]\').addClass("adminxBtnSuccess");' . "\n\t\t" . '}' . "\n\t\t" . 'if(jQuery(".orders-page-data").find(\'input[name="massaccept"]\').length > 0){' . "\n\t\t\t" . 'jQuery(".orders-page-data").find(\'input[name="masscancel"]\').addClass("adminxBtnCancel");' . "\n\t\t" . '}' . "\n\t\t" . 'if(jQuery(".orders-page-data").find(\'input[name="massdelete"]\').length > 0){' . "\n\t\t\t" . 'jQuery(".orders-page-data").find(\'input[name="massdelete"]\').addClass("adminxBtnDelete");' . "\n\t\t" . '}' . "\n\t\t" . 'if(jQuery(".orders-page-data").find(\'input[name="massdelete"]\').length > 0){' . "\n\t\t\t" . 'jQuery(".orders-page-data").find(\'input[name="sendmessage"]\').addClass("adminxBtnSend");' . "\n\t\t" . '}' . "\n\t\t" . 'if(jQuery(".invoices-page-data").find(\'input[name="markpaid"]\').length > 0){' . "\n\t\t\t" . 'jQuery(".invoices-page-data").find(\'input[name="markpaid"]\').addClass("adminxBtnSuccess");' . "\n\t\t" . '}' . "\n\t\t" . 'if(jQuery(".invoices-page-data").find(\'input[name="markunpaid"]\').length > 0){' . "\n\t\t\t" . 'jQuery(".invoices-page-data").find(\'input[name="markunpaid"]\').addClass("adminxBtnUnpaid");' . "\n\t\t" . '}' . "\n\t\t" . 'if(jQuery(".invoices-page-data").find(\'input[name="markcancelled"]\').length > 0){' . "\n\t\t\t" . 'jQuery(".invoices-page-data").find(\'input[name="markcancelled"]\').addClass("adminxBtnCancel");' . "\n\t\t" . '}' . "\n\t\t" . 'if(jQuery(".invoices-page-data").find(\'input[name="duplicateinvoice"]\').length > 0){' . "\n\t\t\t" . 'jQuery(".invoices-page-data").find(\'input[name="duplicateinvoice"]\').addClass("adminxBtnDuplicate");' . "\n\t\t" . '}' . "\n\t\t" . 'if(jQuery(".invoices-page-data").find(\'input[name="paymentreminder"]\').length > 0){' . "\n\t\t\t" . 'jQuery(".invoices-page-data").find(\'input[name="paymentreminder"]\').addClass("adminxBtnPayment");' . "\n\t\t" . '}' . "\n\t\t" . 'if(jQuery(".invoices-page-data").find(\'input[name="massdelete"]\').length > 0){' . "\n\t\t\t" . 'jQuery(".invoices-page-data").find(\'input[name="massdelete"]\').addClass("adminxBtnDelete");' . "\n\t\t" . '}' . "\n\t\t" . 'if(jQuery(".invoices-page-inner-data-draft").find(".tab-content.admin-tabs").length > 0){' . "\n\t\t\t" . 'jQuery(".invoices-page-inner-data-draft").find(".tab-content.admin-tabs").next("br").remove();' . "\n\t\t" . '}' . "\n\t\t" . 'if(jQuery(".table-bg-overflow-visible table").length > 0){' . "\n\t\t\t" . 'jQuery(".table-bg-overflow-visible table").find("tbody>tr>th").each(function(){' . "\n\t\t\t\t" . 'if(jQuery(this).find("img").length > 0){' . "\n\t\t\t\t\t" . 'var imageDataDefaultO = jQuery(this).find("img").attr(\'src\');' . "\n\t\t\t\t\t" . 'var spliToArrayThO = imageDataDefaultO.split(\'/\');' . "\n\t\t\t\t\t" . 'var fileNameArrowThO = spliToArrayThO[spliToArrayThO.length - 1];' . "\n\t\t\t\t\t" . 'var newImageDataO = adminxTemplateImageName+fileNameArrowThO;' . "\n\t\t\t\t\t" . 'jQuery(this).find("img").attr(\'src\',newImageDataO);' . "\n\t\t\t\t" . '}' . "\n\t\t\t" . '});' . "\n\t\t" . '}' . "\n\t\t" . 'if(jQuery(".tablebg table").length > 0){' . "\n\t\t\t" . 'jQuery(".tablebg table").find("tbody>tr>th").each(function(){' . "\n\t\t\t\t" . 'if(jQuery(this).find("img").length > 0){' . "\n\t\t\t\t\t" . 'var imageDataDefault = jQuery(this).find("img").attr(\'src\');' . "\n\t\t\t\t\t" . 'var spliToArrayTh = imageDataDefault.split(\'/\');' . "\n\t\t\t\t\t" . 'var fileNameArrowTh = spliToArrayTh[spliToArrayTh.length - 1];' . "\n\t\t\t\t\t" . 'var newImageData = adminxTemplateImageName+fileNameArrowTh;' . "\n\t\t\t\t\t" . 'jQuery(this).find("img").attr(\'src\',newImageData);' . "\n\t\t\t\t" . '}' . "\n\t\t\t" . '});' . "\n\t\t\t" . 'jQuery(".tablebg table").each(function(){' . "\n\t\t\t\t" . 'var thLength = jQuery(this).find("th").length;' . "\n\t\t\t\t" . 'var trLength = jQuery(this).find("tr").length;' . "\n\t\t\t\t" . 'if(trLength == \'2\'){' . "\n\t\t\t\t\t" . 'var getAttributeTd = jQuery(this).find("tr").find("td").attr("colspan");' . "\n\t\t\t\t\t" . 'if(typeof getAttributeTd !== \'undefined\' && getAttributeTd !== false) {' . "\n\t\t\t\t\t\t" . 'if(thLength == getAttributeTd){' . "\n\t\t\t\t\t\t\t" . 'jQuery(this).find("tr").eq(1).addClass("adminxTableNoRecord");' . "\t\n\t\t\t\t\t\t" . '}' . "\n\t\t\t\t\t" . '}' . "\n\t\t\t\t" . '}' . "\n\t\t\t" . '});' . "\n\t\t\t" . 'jQuery(".tablebg table").find("tbody>tr>td").each(function(){' . "\n\t\t\t\t" . 'if(jQuery(this).find("img").length > 0){' . "\n\t\t\t\t\t" . 'var deleteImgSrc = jQuery(this).find("img").attr(\'src\');' . "\n\t\t\t\t\t" . 'var spliToArray = deleteImgSrc.split(\'/\');' . "\n\t\t\t\t\t" . 'var fileNameDelete = spliToArray[spliToArray.length - 1];' . "\n\t\t\t\t\t" . 'if(fileNameDelete == \'delete.gif\'){' . "\n\t\t\t\t\t\t" . 'jQuery(this).find("img").attr(\'src\',adminxTemplateImageName+\'delete.svg\');' . "\n\t\t\t\t\t" . '}else if(fileNameDelete == \'edit.gif\'){' . "\n\t\t\t\t\t\t" . 'jQuery(this).find("img").attr(\'src\',adminxTemplateImageName+\'edit.svg\');' . "\n\t\t\t\t\t" . '}' . "\n\t\t\t\t" . '}' . "\n\t\t\t" . '});' . "\n\t\t" . '}' . "\t\t\n\t\t" . 'if(jQuery("#createNewNetworkIssue").length > 0){' . "\n\t\t\t" . 'jQuery("#createNewNetworkIssue").find("img").attr(\'src\',adminxTemplateImageName+\'plus-circle.svg\');' . "\n\t\t" . '}' . "\n\t\t" . 'if(jQuery(".supportkb-page-inner-data").find(".browse-section-title").length > 0){' . "\n\t\t\t" . 'jQuery(".supportkb-page-inner-data").find(".browse-section-title").eq(0).next("div").find("div").addClass("categoryKbBox");' . "\n\t\t\t" . 'jQuery(".supportkb-page-inner-data").find(".categoryKbBox").each(function(){' . "\n\t\t\t\t" . 'var contentHtmlGet = jQuery(this).html();' . "\n\t\t\t\t" . 'jQuery(this).html(\'<div class="content-box-kb-cat">\'+contentHtmlGet+\'</div>\');' . "\n\t\t\t\t" . 'jQuery(this).find(".content-box-kb-cat br").remove();' . "\n\t\t\t\t" . 'jQuery(\'<div class="img-box-kb-cat"></div>\').insertBefore(jQuery(this).find(".content-box-kb-cat"));' . "\n\t\t\t\t" . 'jQuery(jQuery(this).find(".content-box-kb-cat").find("img").eq(0)).appendTo(jQuery(this).find(".img-box-kb-cat"));' . "\n\t\t\t\t" . 'jQuery(this).find("img").each(function(){' . "\n\t\t\t\t\t" . 'var ImgSrcDataName = jQuery(this).attr(\'src\');' . "\n\t\t\t\t\t" . 'var spliToArrayImg = ImgSrcDataName.split(\'/\');' . "\n\t\t\t\t\t" . 'var fileNameImgD = spliToArrayImg[spliToArrayImg.length - 1];' . "\n\t\t\t\t\t" . 'if(fileNameImgD == \'delete.gif\'){' . "\n\t\t\t\t\t\t" . 'jQuery(this).attr(\'src\',adminxTemplateImageName+\'delete.svg\');' . "\n\t\t\t\t\t" . '}else if(fileNameImgD == \'edit.gif\'){' . "\n\t\t\t\t\t\t" . 'jQuery(this).attr(\'src\',adminxTemplateImageName+\'edit.svg\');' . "\n\t\t\t\t\t" . '}else if(fileNameImgD == \'folder.gif\'){' . "\n\t\t\t\t\t\t" . 'jQuery(this).attr(\'src\',adminxTemplateImageName+\'folder.svg\');' . "\n\t\t\t\t\t" . '}' . "\n\t\t\t\t" . '});' . "\n\t\t\t" . '});' . "\n\t\t" . '}' . "\n\t\t" . 'if(jQuery(".systemdatabase-page-inner-data").length > 0){' . "\n\t\t\t" . 'jQuery(".systemdatabase-page-inner-data").find("table").addClass("datatable");' . "\n\t\t" . '}' . "\n\t\t" . 'if(jQuery(".systemphpinfo-page-inner-data").find(".whmcs-phpinfo").length > 0){' . "\n\t\t\t" . 'jQuery(".systemphpinfo-page-inner-data").find(".whmcs-phpinfo").find("table").addClass("datatable");' . "\n\t\t" . '}' . "\n\t\t" . 'if(jQuery(".common-page-inner-data").find("#setupTasksDrawer").length > 0){' . "\n\t\t\t" . 'jQuery(".common-page-inner-data").find("#setupTasksDrawer").prev("h1").remove();' . "\n\t\t" . '}' . "\n\t\t" . 'if(jQuery(".quotes-page-inner-data-manage").find("table").length > 0){' . "\n\t\t\t" . 'jQuery(".quotes-page-inner-data-manage").find("table:last").addClass(\'lastTableManageQuote\');' . "\n\t\t" . '}' . "\n\t\t" . 'if(jQuery("#frmAddUser").length > 0){' . "\n\t\t\t" . '/*  Will Check later if we can do anything on form' . "\n\t\t\t" . 'jQuery(\'<div class="clientAddFormAdminX"></div>\').insertBefore(jQuery("#frmAddUser").find("table"));' . "\n\t\t\t" . 'jQuery("#frmAddUser").find("table").addClass("hidden");' . "\n\t\t\t" . 'var clientAddForm = \'\';' . "\n\t\t\t" . 'jQuery("#frmAddUser").find("table tr").each(function(){' . "\n\t\t\t\t" . 'var trId = \'\';' . "\n\t\t\t\t" . 'if(jQuery(this).attr(\'id\') !== undefined){' . "\n\t\t\t\t\t" . 'trId = jQuery(this).attr(\'id\');' . "\n\t\t\t\t" . '}' . "\n\t\t\t\t" . 'clientAddForm += \'<div class="form-group" id="\'+trId+\'">\';' . "\n\t\t\t\t" . 'clientAddForm += \'<div class="col-md-6">\';' . "\n\t\t\t\t" . 'clientAddForm += \'<label class="form-label-adminx">\'+jQuery(this).find("td.fieldlabel").eq(0).html()+\'</label>\';' . "\n\t\t\t\t" . 'clientAddForm += \'<div class="form-input-are-adminx">\'+jQuery(this).find("td.fieldarea").eq(0).html()+\'</div>\';' . "\n\t\t\t\t" . 'clientAddForm += \'</div>\';' . "\n\t\t\t\t" . 'if(jQuery(this).find("td").length > 2){' . "\n\t\t\t\t\t" . 'clientAddForm += \'<div class="col-md-6">\';' . "\n\t\t\t\t\t" . 'clientAddForm += \'<label class="form-label-adminx">\'+jQuery(this).find("td.fieldlabel").eq(1).html()+\'</label>\';' . "\n\t\t\t\t\t" . 'clientAddForm += \'<div class="form-input-are-adminx">\'+jQuery(this).find("td.fieldarea").eq(1).html()+\'</div>\';' . "\n\t\t\t\t\t" . 'clientAddForm += \'</div>\';' . "\n\t\t\t\t" . '}' . "\t\t\t\t\n\t\t\t\t" . 'clientAddForm += \'</div>\';' . "\n\t\t\t" . '});' . "\n\t\t\t" . 'jQuery(clientAddForm).appendTo(jQuery(".clientAddFormAdminX"));' . "\n\t\t\t" . 'jQuery("#frmAddUser").find("table").remove();' . "\n\t\t\t" . '*/' . "\n\t\t" . '}' . "\n\t\t" . 'if(jQuery(".clientsbillableitems-page-inner-data-manage").length > 0){' . "\n\t\t\t" . 'jQuery(".clientsbillableitems-page-inner-data-manage").find("form.clientDomainsForm").find("table").find("tr#duedaterow").prev("tr").addClass("invoice-action-tr");' . "\n\t\t\t" . 'jQuery("tr.invoice-action-tr").find("td.fieldarea").addClass("invoice-action-td");' . "\n\t\t" . '}' . "\n\t\t" . 'if(jQuery(".billable-items-form-first").length > 0){' . "\n\t\t\t" . 'jQuery(".billable-items-form-first").find("table").find("tr#duedaterow").prev("tr").addClass("invoice-action-tr-1");' . "\n\t\t\t" . 'jQuery("tr.invoice-action-tr-1").find("td.fieldarea").addClass("invoice-action-td-1");' . "\n\t\t" . '}' . "\n\t\t" . 'if(jQuery(".reports-page-inner-data").length > 0){' . "\n\t\t\t" . 'jQuery(\'<div class="reportTableDataSec"></div>\').insertBefore(jQuery(".reports-page-inner-data").find("table"));' . "\n\t\t\t" . 'jQuery(jQuery(".reports-page-inner-data").find("table")).appendTo(jQuery(".reportTableDataSec"));' . "\n\t\t" . '}' . "\n\t\t" . 'if(jQuery(".systemsupportrequest-page-inner-data").length > 0){' . "\n\t\t\t" . 'jQuery(".systemsupportrequest-page-inner-data").find("form").parent().addClass("searchHelpResourcesForm");' . "\n\t\t\t" . 'jQuery(\'<div class="systemsupportrequestTableDataSec"></div>\').insertBefore(jQuery(".systemsupportrequest-page-inner-data").find("table").eq(0));' . "\n\t\t\t" . 'jQuery(jQuery(".systemsupportrequest-page-inner-data").find("table").eq(0)).appendTo(jQuery(".systemsupportrequestTableDataSec"));' . "\n\t\t" . '}' . "\n\t\t" . 'if(jQuery(".market-connect-apps-container").length > 0){' . "\n\t\t\t" . 'jQuery(\'<div class="marketConnectButtonList"></div>\').insertAfter(jQuery(".market-connect-apps-container"));' . "\n\t\t\t" . 'jQuery("a.btn-default").appendTo(jQuery(".marketConnectButtonList"));' . "\n\t\t" . '}' . "\n\t\t" . 'if(jQuery(".configbannedemails-page-inner-data").length > 0){' . "\n\t\t\t" . 'jQuery(".configbannedemails-page-inner-data").find("form").find(\'input[name="domain"]\').addClass("form-control");' . "\n\t\t" . '}' . "\n\t\t" . 'if(jQuery(".configbannedips-page-inner-data").length > 0){' . "\n\t\t\t" . 'jQuery(".configbannedips-page-inner-data").find("form").find(\'input[name="day"]\').parent().addClass("banned-ip-fieldarea");' . "\n\t\t\t" . 'jQuery(".configbannedips-page-inner-data").find("form").find(\'input[name="ip"]\').addClass("form-control");' . "\n\t\t\t" . 'jQuery(".configbannedips-page-inner-data").find("form").find(\'input[name="reason"]\').addClass("form-control reason-banned-field");' . "\n\t\t\t" . 'jQuery(".configbannedips-page-inner-data").find("form").find(\'input[name="day"]\').addClass("form-control");' . "\n\t\t\t" . 'jQuery(".configbannedips-page-inner-data").find("form").find(\'input[name="month"]\').addClass("form-control");' . "\n\t\t\t" . 'jQuery(".configbannedips-page-inner-data").find("form").find(\'input[name="year"]\').addClass("form-control");' . "\n\t\t\t" . 'jQuery(".configbannedips-page-inner-data").find("form").find(\'input[name="hour"]\').addClass("form-control");' . "\n\t\t\t" . 'jQuery(".configbannedips-page-inner-data").find("form").find(\'input[name="minutes"]\').addClass("form-control");' . "\n\t\t" . '}' . "\n\t\t" . 'if(jQuery(".sendmessage-page-inner-data").length > 0){' . "\n\t\t\t" . 'jQuery(\'<div class="sendMessageTableDataSec"></div>\').insertBefore(jQuery(".sendmessage-page-inner-data").find("form#sendmsgfrm").find("table").eq(0));jQuery(".sendmessage-page-inner-data").find("form#sendmsgfrm").find("table").eq(0).appendTo(jQuery(".sendMessageTableDataSec"));' . "\n\t\t\t" . 'jQuery(".sendMessageTableDataSec").find("select").each(function(){' . "\n\t\t\t\t" . 'var multiSelectsSend = jQuery(this).attr("size");' . "\n\t\t\t\t" . 'if(typeof multiSelectsSend !== \'undefined\' && multiSelectsSend !== false){' . "\n\t\t\t\t\t" . 'jQuery(this).addClass("adminx-multi-select-dropdown");' . "\t\n\t\t\t\t" . '}' . "\n\t\t\t" . '});' . "\n\t\t" . '}' . "\n\t\t" . 'if(jQuery(".clientsservices-page-inner-data").length > 0){' . "\n\t\t\t" . 'jQuery(\'<div class="clientProductServicesTab"></div>\').insertBefore(jQuery(".clientsservices-page-inner-data").find("form.clientAddDomainsContactForm").find("table").eq(0));' . "\n\t\t\t" . 'jQuery(".clientsservices-page-inner-data").find("form.clientAddDomainsContactForm").find("table").eq(0).appendTo(jQuery(".clientProductServicesTab"));' . "\t\t\t\n\t\t" . '}' . "\n\t\t" . 'if(jQuery(".systemsupportrequest-page-inner-data").length > 0){' . "\n\t\t\t" . 'jQuery(\'<div class="systemSupportRequestTable"></div>\').insertBefore(jQuery(".systemsupportrequest-page-inner-data").find("table").eq(1));' . "\n\t\t\t" . 'jQuery(".systemsupportrequest-page-inner-data").find("table").eq(1).appendTo(jQuery(".systemSupportRequestTable"));' . "\t\t\t\n\t\t" . '}' . "\n\t\t" . 'if(jQuery(".supporttickets-page-inner-data-open").length > 0){' . "\n\t\t\t" . 'jQuery(".supporttickets-page-inner-data-open").find("table:last").addClass("attachmentTableOpenticket");' . "\n\t\t" . '}' . "\n\t\t" . 'if(jQuery(".networkissues-page-inner-data-manage").length > 0){' . "\n\t\t\t" . 'jQuery(".networkissues-page-inner-data-manage").find("tr#affectingother").find(\'input[name="affecting"]\').addClass("form-control");' . "\n\t\t" . '}' . "\n\t\t" . 'if(jQuery(".configticketescalations-page-inner-data-manage").length > 0){' . "\n\t\t\t" . 'jQuery(".configticketescalations-page-inner-data-manage").find("form").find("select").each(function(){' . "\n\t\t\t\t" . 'var multiSelects = jQuery(this).attr("multiple");' . "\n\t\t\t\t" . 'if(typeof multiSelects !== \'undefined\' && multiSelects !== false){' . "\n\t\t\t\t\t" . 'jQuery(this).addClass("adminx-multi-select-dropdown");' . "\t\n\t\t\t\t" . '}' . "\n\t\t\t" . '});' . "\n\t\t" . '}' . "\n\t\t" . 'if(jQuery(".supportdownloads-page-inner-data").length > 0){' . "\n\t\t\t" . 'jQuery(".supportdownloads-page-inner-data").find(".browse-section-title").eq("0").next(".row").addClass("downloadSupportCategory");' . "\n\t\t" . '}' . "\n\t\t" . 'if(jQuery(".supportticketpredefinedreplies-page-inner-data").length > 0){' . "\n\t\t\t" . 'jQuery(".supportticketpredefinedreplies-page-inner-data").find(".browse-section-title").eq("0").next(".row").addClass("supportTicketPredefineCategory");' . "\n\t\t" . '}' . "\t\t\n" . '   ' . "\t" . '});' . "\n\t" . 'function adminXContentWithoutTag(){' . "\n\t\t" . 'const el = document.getElementsByClassName(\'content-box-kb-cat\')[0];' . "\n\t\t" . 'const elNodes = el.childNodes;' . "\n\t\t" . 'let plainText = "";' . "\n\t\t" . 'for(i=0;i<elNodes.length;i++){' . "\n\t\t" . '   if(elNodes[i].nodeName == \'#text\'){' . "\n\t\t\t" . ' plainText+=elNodes[i].textContent;' . "\n\t\t" . '   }' . "\n\t\t" . '}' . "\n\t" . '}' . "\n" . '   </script>';
	$addonAdminxActivate = false;

	if (file_exists(__DIR__ . '/lib/class.php')) {
		require_once __DIR__ . '/lib/class.php';
		$getLicenseDetailAdminx = WHMCS\Database\Capsule::table('tbladdonmodules')->where('setting', 'license_key')->where('module', 'adminx')->first();
		$adminX = new WGS\ADMINXTHEME\WGSADMINXTHEME\wgsAdminxAdminThemeClasses();
		$license = $adminX->wgsAdminxThemeLicenseCheck($getLicenseDetailAdminx->value);
		$status = trim($license['status']);

		if ($status == 'Active') {
			$addonAdminxActivate = true;
		}
	}
	if (!empty($addonAdminxActivate) && ($templateName == 'adminx')) {
		return $script;
	}
});

?>