<?php
	define("DATA_PREFIX","af_");
	define ("BASE_URL", "http://".$_SERVER["HTTP_HOST"]."/");
	define ("BASE_THEME", "base");
	define ("SITE_ROOT", $_SERVER["DOCUMENT_ROOT"]);
	
	/**
	 *	Working locally set up details
	 */
	if (preg_match("/.dev.drivebusiness.co.uk/",$_SERVER["HTTP_HOST"])) {
		ini_set( 'display_errors', '1' );
		error_reporting( E_ALL );
		date_default_timezone_set('Europe/London');
		$db_connection=@mysql_connect("localhost", "root", "");
		@mysql_select_db("affiliate");
		define ("THEME", "designer-clothing-shop");
	}
	else {
		$db_connection=@mysql_connect("db2935.oneandone.co.uk", "dbo348250583", "Beastie10");
		@mysql_select_db("db348250583");
		$temp=explode(".",$_SERVER["HTTP_HOST"]);
		if ($temp[0]=="www") $temp[0]=$temp[1];
		define ("THEME", strtolower($temp[0]));
	}
	
	/**
	 *	All the reusable functions to go here
	 */
	include("fn.functions.inc.php");
	
	/**
	 *	Find the site they are on based on the theme
	 */
	$site["site_id"]=10;
	$site["title"]="";
	$site["url"]="";
	$site["google"]="";
	$site["description"]="";
	$site["google-site-verification"]="";
	$result=@mysql_query("SELECT site_id, title, url, google, description, `google-site-verification` FROM ".DATA_PREFIX."site WHERE theme='".THEME."'");
	if ($details=@mysql_fetch_array($result)) {
		$site["site_id"]=$details["site_id"];
		$site["title"]=stripslashes($details["title"]);
		$site["url"]=stripslashes($details["url"]);
		$site["google"]=$details["google"];
		$site["description"]=stripslashes($details["description"]);
		$site["google-site-verification"]=stripslashes($details["google-site-verification"]);
	}
?>