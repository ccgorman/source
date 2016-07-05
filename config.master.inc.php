<?php
	header("Content-Type: text/html; charset=ISO-8859-1");
	
	include("config.env.inc.php");
	
	/**
	 *	Link out of the site to a product, anytime a product_id is set link out to the affiliate site
	 */
	if (isset($_REQUEST["product_id"])) {
		$result=mysql_query("SELECT producturl FROM ".DATA_PREFIX."product WHERE product_id=".mysql_real_escape_string($_REQUEST["product_id"]));
		if ($details=mysql_fetch_array($result)) {
			if ($site["site_id"]!=10) {
				header("Location:http://www.designer-clothing-shop.co.uk/?product_id=".$_REQUEST["product_id"]."&site_id=".$site["site_id"]);
				exit();
			}
			else {
				if (isset($_GET["site_id"])) $site["site_id"]=$_GET["site_id"];
				$result=@mysql_query("INSERT INTO ".DATA_PREFIX."tracking (
					product_id, site_id, views
				) VALUES (
					".mysql_real_escape_string($_REQUEST["product_id"]).", ".mysql_real_escape_string($site["site_id"]).", 1
				) ON DUPLICATE KEY UPDATE views=views+1");
				header("Location:".$details["producturl"]);
				exit();
			}
		}
	}
	
	/**
	 *	See if they have requested to subscribe
	 */
	$site["email"]="Enter Your E-mail Address";
	if (isset($_REQUEST["email"])) {
		$email=stripslashes(trim($_REQUEST["email"]));
		if (preg_match("/^[_\'a-z0-9-]+(\.[_\'a-z0-9-]+)*@([0-9a-z][0-9a-z-]*[0-9a-z]\.)+[a-z]{2,4}[mtgvu]?$/i", $_REQUEST["email"])) {
			$email=mysql_real_escape_string($_REQUEST["email"]);
			$result=@mysql_query("INSERT INTO ".DATA_PREFIX."user (
				user_id, username, password, security_val, email,
				multiple_login, last_update, reg_date,
				subscribe, site_id, all_sites
			)
			VALUES (
				NULL, '".$email."', MD5('[-blank-]'), 8, '".$email."',
				0, now(), now(),
				1, ".$site["site_id"].", 0
			)");
			$site["email"]="Thank You";
		}
		else {
			$site["email"]="Invalid E-mail Address";
		}
	}
	
	/**
	 *	Default search text
	 */
	$site["searchtext"]="Enter Search Text";
	$site["search"]=FALSE;
	if (isset($_POST["searchtext"])) {
		$site["searchtext"]=stripslashes($_POST["searchtext"]);
		$site["search"]=TRUE;
		$result=@mysql_query("INSERT INTO ".DATA_PREFIX."search (
			search_id, site_id, title, date_added, frequency
		) VALUES (
			NULL, ".mysql_real_escape_string($site["site_id"]).", '".mysql_real_escape_string($site["searchtext"])."', now(), 1
		) ON DUPLICATE KEY UPDATE frequency=frequency+1");
	}
	elseif (isset($_GET["searchtext"])) {
		$site["searchtext"]=urldecode($_GET["searchtext"]);
		$site["search"]=TRUE;
	}
	$site["searchtext"]=af_text_form($site["searchtext"]);
	
	/**
	 *	Work out the category, sub category or product
	 */
	$site["link_id"]=0;
	$site["link_name"]="";
	$site["gender"]="";
	$site["gender_name"]="";
	$site["category_id"]=0;
	$site["category_name"]="";
	$site["ref"]=0;
	$site["ref_name"]="";
	$site["product_id"]=0;
	$site["product_name"]="";
	$site["product_description"]="";
	$site["page"]=1;
	if (!$site["search"]) {
		if (isset($_REQUEST["url"])) {
			$url=explode("/",$_REQUEST["url"]);
			if (isset($url[0])) {
				$shoptext="";
				for ($i=0;$i<count($url);$i++) {
					if (preg_match("/^SHOP/",$url[$i],$nada)) {
						$shopstructure=af_calculate_shop_url($shoptext,$url[$i]);
					}
					else {
						$shoptext.=$url[$i];
					}
				}
				/**
				 *	Old URL
				 */
				if (preg_match("/^shop/",$_REQUEST["url"],$nada)) {
					$url=explode("/",$_REQUEST["url"]);
					if (isset($url[1])) {
						$link=$url[1];
						if (isset($url[2]) && $url[2]) $category=$url[2]; else $category="";
						if (isset($url[3]) && $url[3]) $subcategory=$url[3]; else $subcategory="";
						if (isset($url[4]) && $url[4]) $product=$url[4]; else $product="";
						if (!$product && $subcategory) {
							$product=$subcategory;
							$subcategory="";
						}
						header ('HTTP/1.1 301 Moved Permanently');
						header("Location:".af_create_shop_url ($link,$category,$subcategory,$product));
						exit();
					}
				}
				/**
				 *	New URL
				 */
				elseif (!isset($shopstructure["link"])) {
					$len=0;
					$result=@mysql_query("SELECT modrewriteurl FROM ".DATA_PREFIX."link WHERE site_id=".$site["site_id"]);
					while ($details=@mysql_fetch_array($result)) {
						if (preg_match("/^".$details["modrewriteurl"]."/",$shoptext)) {
							if (strlen($details["modrewriteurl"])>$len) {
								$len=strlen($details["modrewriteurl"]);
								$shopstructure["link"]=$details["modrewriteurl"];
								$shopstructure["category"]=preg_replace("/^".$details["modrewriteurl"]."/","",$shoptext);
							}
						}
					}
				}
				
				$result=@mysql_query("SELECT link_id, gender, title FROM ".DATA_PREFIX."link WHERE site_id=".$site["site_id"]." AND modrewriteurl='".$shopstructure["link"]."'");
				if ($details=@mysql_fetch_array($result)) {
					$site["link_id"]=$details["link_id"];
					$site["gender"]=$details["gender"];
					$site["link_name"]=stripslashes($details["title"]);
					if ($site["gender"]=="M") {
						$site["gender_name"]="Mens";
					}
					elseif ($site["gender"]=="W") {
						$site["gender_name"]="Womens";
					}
					if (isset($shopstructure["category"])) {
						$result=@mysql_query("SELECT category_id, title FROM ".DATA_PREFIX."category WHERE link_id=".$site["link_id"]." AND modrewriteurl='".$shopstructure["category"]."'");
						if ($details=@mysql_fetch_array($result)) {
							$site["category_id"]=$details["category_id"];
							$site["category_name"]=stripslashes($details["title"]);
							$ref=0;
							if (isset($shopstructure["subcategory"])) {
								$result=@mysql_query("SELECT category_id, title FROM ".DATA_PREFIX."category WHERE ref=".$details["category_id"]." AND modrewriteurl='".$shopstructure["subcategory"]."'");
								if ($details=@mysql_fetch_array($result)) {
									$site["ref"]=$site["category_id"];
									$site["category_id"]=$details["category_id"];
									$site["ref_name"]=$site["category_name"];
									$site["category_name"]=stripslashes($details["title"]);
								}
							}
							if (isset($shopstructure["product"])) {
								$result=@mysql_query("SELECT product_id, title, description FROM ".DATA_PREFIX."product WHERE modrewriteurl='".$shopstructure["product"]."'");
								if ($details=@mysql_fetch_array($result)) {
									$site["product_id"]=$details["product_id"];
									$site["product_name"]=stripslashes($details["title"]);
									$site["product_description"]=stripslashes($details["description"]);
								}
							}
							if (isset($shopstructure["page"]) && $shopstructure["page"]) {
								$site["page"]=$shopstructure["page"];
							}
						}
					}
				}
			}
		}
	}
	
	if (isset($_POST["page"])) {
		$site["page"]=$_POST["page"];
	}
	
	$page_position=1;
	
	/*
		head title
		meta description
		meta keywords
		site name - top logo
		banner link and alt text
	*/
?>