<? header("Pragma: no-cache");
header("Expires: -1"); 

include_once($_SERVER['DOCUMENT_ROOT'] . '/202-config/connect.php'); 

$landing_page_id_public = $_GET['lpip'];
$mysql['landing_page_id_public'] = mysql_real_escape_string($landing_page_id_public);
$tracker_sql = "SELECT  202_landing_pages.user_id,
						  202_landing_pages.landing_page_id
				FROM     202_landing_pages
				WHERE  202_landing_pages.landing_page_id_public='".$mysql['landing_page_id_public']."'";   
$tracker_row = memcache_mysql_fetch_assoc($tracker_sql);    

//set the timezone to the users timezone
$mysql['user_id'] = mysql_real_escape_string($tracker_row['user_id']);
$user_sql = "SELECT 		user_timezone, 
							user_keyword_searched_or_bidded 
			   FROM 		202_users 
			   LEFT JOIN	202_users_pref USING (user_id)
			   WHERE 		202_users.user_id='".$mysql['user_id']."'";
$user_row = memcache_mysql_fetch_assoc($user_sql);

//now this sets it
AUTH::set_timezone($user_row['user_timezone']);

if (!$tracker_row) { die(); }

if ($_GET['t202id']) { 
	//grab tracker data if avaliable
	$mysql['tracker_id_public'] = mysql_real_escape_string($_GET['t202id']);

	$tracker_sql2 = "
		SELECT
			text_ad_id,
			ppc_account_id,
			click_cpc,
			click_cloaking,
			aff_campaign_id
		FROM
			202_trackers
		WHERE
			tracker_id_public='".$mysql['tracker_id_public']."'";   
	$tracker_row2 = memcache_mysql_fetch_assoc($tracker_sql2);
	if ($tracker_row2) {
		$tracker_row = array_merge($tracker_row,$tracker_row2);
	}
}

 
//INSERT THIS CLICK BELOW, if this click doesn't already exisit

//get mysql variables 
$mysql['user_id'] = mysql_real_escape_string($tracker_row['user_id']);
$mysql['aff_campaign_id'] = mysql_real_escape_string($tracker_row['aff_campaign_id']);
$mysql['ppc_account_id'] = mysql_real_escape_string($tracker_row['ppc_account_id']);
$mysql['click_cpc'] = mysql_real_escape_string($tracker_row['click_cpc']);
$mysql['click_payout'] = mysql_real_escape_string($tracker_row['aff_campaign_payout']);
$mysql['click_time'] = time();
$mysql['landing_page_id'] = mysql_real_escape_string($tracker_row['landing_page_id']);
$mysql['text_ad_id'] = mysql_real_escape_string($tracker_row['text_ad_id']);

/* ok, if $_GET['OVRAW'] that is a yahoo keyword, if on the REFER, there is a $_GET['q], that is a GOOGLE keyword... */
//so this is going to check the REFERER URL, for a ?q=, which is the ACUTAL KEYWORD searched.
$referer_url_parsed = @parse_url($_GET['referer']);
$referer_url_query = $referer_url_parsed['query'];
 
@parse_str($referer_url_query, $referer_query);

switch ($user_row['user_keyword_searched_or_bidded']) { 
	
	case "bidded":
	      #try to get the bidded keyword first
		if ($_GET['OVKEY']) { //if this is a Y! keyword
			$keyword = mysql_real_escape_string($_GET['OVKEY']);   
		} elseif ($_GET['t202kw']) { 
			$keyword = mysql_real_escape_string($_GET['t202kw']);  
		} elseif ($referer_query['p']) { 
			$keyword = mysql_real_escape_string($referer_query['p']);
		} elseif ($_GET['target_passthrough']) { //if this is a mediatraffic! keyword
			$keyword = mysql_real_escape_string($_GET['target_passthrough']);   
		} else { //if this is a zango, or more keyword
			$keyword = mysql_real_escape_string($_GET['keyword']);   
		} 
		break;
	case "searched":
		#try to get the searched keyword
		if ($referer_query['q']) { 
			$keyword = mysql_real_escape_string($referer_query['q']);
		} elseif ($referer_query['p']) { 
			$keyword = mysql_real_escape_string($referer_query['p']);
		} elseif ($_GET['OVRAW']) { //if this is a Y! keyword
			$keyword = mysql_real_escape_string($_GET['OVRAW']);   
		} elseif ($_GET['target_passthrough']) { //if this is a mediatraffic! keyword
			$keyword = mysql_real_escape_string($_GET['target_passthrough']);   
		} elseif ($_GET['keyword']) { //if this is a zango, or more keyword
			$keyword = mysql_real_escape_string($_GET['keyword']);   
		} else { 
			$keyword = mysql_real_escape_string($_GET['t202kw']);  
		}
		break;
}
$keyword = str_replace('%20',' ',$keyword);  
$keyword_id = INDEXES::get_keyword_id($keyword); 
$mysql['keyword_id'] = mysql_real_escape_string($keyword_id); 

$c1 = mysql_real_escape_string($_GET['c1']);
$c1 = str_replace('%20',' ',$c1);  
$c1_id = INDEXES::get_c1_id($c1); 
$mysql['c1_id'] = mysql_real_escape_string($c1_id);

$c2 = mysql_real_escape_string($_GET['c2']);
$c2 = str_replace('%20',' ',$c2);
$c2_id = INDEXES::get_c2_id($c2);
$mysql['c2_id'] = mysql_real_escape_string($c2_id);

$c3 = mysql_real_escape_string($_GET['c3']);
$c3 = str_replace('%20',' ',$c3);  
$c3_id = INDEXES::get_c3_id($c3); 
$mysql['c3_id'] = mysql_real_escape_string($c3_id);

$c4 = mysql_real_escape_string($_GET['c4']);
$c4 = str_replace('%20',' ',$c4);
$c4_id = INDEXES::get_c4_id($c4);
$mysql['c4_id'] = mysql_real_escape_string($c4_id);

$c5 = mysql_real_escape_string($_GET['c5']);
$c5 = str_replace('%20',' ',$c5);  
$c5_id = INDEXES::get_c5_id($c5); 
$mysql['c5_id'] = mysql_real_escape_string($c5_id);

$c6 = mysql_real_escape_string($_GET['c6']);
$c6 = str_replace('%20',' ',$c6);
$c6_id = INDEXES::get_c6_id($c6);
$mysql['c6_id'] = mysql_real_escape_string($c6_id);

$c7 = mysql_real_escape_string($_GET['c7']);
$c7 = str_replace('%20',' ',$c7);  
$c7_id = INDEXES::get_c7_id($c7); 
$mysql['c7_id'] = mysql_real_escape_string($c7_id);

$c8 = mysql_real_escape_string($_GET['c8']);
$c8 = str_replace('%20',' ',$c8);
$c8_id = INDEXES::get_c8_id($c8);
$mysql['c8_id'] = mysql_real_escape_string($c8_id);

$c9 = mysql_real_escape_string($_GET['c9']);
$c9 = str_replace('%20',' ',$c9);  
$c9_id = INDEXES::get_c9_id($c9); 
$mysql['c9_id'] = mysql_real_escape_string($c9_id);

$c10 = mysql_real_escape_string($_GET['c10']);
$c10 = str_replace('%20',' ',$c10);
$c10_id = INDEXES::get_c10_id($c10);
$mysql['c10_id'] = mysql_real_escape_string($c10_id);

$c11 = mysql_real_escape_string($_GET['c11']);
$c11 = str_replace('%20',' ',$c11);  
$c11_id = INDEXES::get_c11_id($c11); 
$mysql['c11_id'] = mysql_real_escape_string($c11_id);

$c12 = mysql_real_escape_string($_GET['c12']);
$c12 = str_replace('%20',' ',$c12);
$c12_id = INDEXES::get_c12_id($c12);
$mysql['c12_id'] = mysql_real_escape_string($c12_id);

$c13 = mysql_real_escape_string($_GET['c13']);
$c13 = str_replace('%20',' ',$c13);  
$c13_id = INDEXES::get_c13_id($c13); 
$mysql['c13_id'] = mysql_real_escape_string($c13_id);

$c14 = mysql_real_escape_string($_GET['c14']);
$c14 = str_replace('%20',' ',$c14);
$c14_id = INDEXES::get_c14_id($c14);
$mysql['c14_id'] = mysql_real_escape_string($c14_id);

$c15 = mysql_real_escape_string($_GET['c15']);
$c15 = str_replace('%20',' ',$c15);  
$c15_id = INDEXES::get_c15_id($c15); 
$mysql['c15_id'] = mysql_real_escape_string($c15_id);


$mv1 = mysql_real_escape_string($_GET['mv1']);
$mv1 = str_replace('%20',' ',$mv1);  
$mv1_id = INDEXES::get_mv1_id($mv1); 
$mysql['mv1_id'] = mysql_real_escape_string($mv1_id);

$mv2 = mysql_real_escape_string($_GET['mv2']);
$mv2 = str_replace('%20',' ',$mv2);
$mv2_id = INDEXES::get_mv2_id($mv2);
$mysql['mv2_id'] = mysql_real_escape_string($mv2_id);

$mv3 = mysql_real_escape_string($_GET['mv3']);
$mv3 = str_replace('%20',' ',$mv3);  
$mv3_id = INDEXES::get_mv3_id($mv3); 
$mysql['mv3_id'] = mysql_real_escape_string($mv3_id);

$mv4 = mysql_real_escape_string($_GET['mv4']);
$mv4 = str_replace('%20',' ',$mv4);
$mv4_id = INDEXES::get_mv4_id($mv4);
$mysql['mv4_id'] = mysql_real_escape_string($mv4_id);

$mv5 = mysql_real_escape_string($_GET['mv5']);
$mv5 = str_replace('%20',' ',$mv5);  
$mv5_id = INDEXES::get_mv5_id($mv5); 
$mysql['mv5_id'] = mysql_real_escape_string($mv5_id);

$mv6 = mysql_real_escape_string($_GET['mv6']);
$mv6 = str_replace('%20',' ',$mv6);
$mv6_id = INDEXES::get_mv6_id($mv6);
$mysql['mv6_id'] = mysql_real_escape_string($mv6_id);

$mv7 = mysql_real_escape_string($_GET['mv7']);
$mv7 = str_replace('%20',' ',$mv7);  
$mv7_id = INDEXES::get_mv7_id($mv7); 
$mysql['mv7_id'] = mysql_real_escape_string($mv7_id);

$mv8 = mysql_real_escape_string($_GET['mv8']);
$mv8 = str_replace('%20',' ',$mv8);
$mv8_id = INDEXES::get_mv8_id($mv8);
$mysql['mv8_id'] = mysql_real_escape_string($mv8_id);

$mv9 = mysql_real_escape_string($_GET['mv9']);
$mv9 = str_replace('%20',' ',$mv9);  
$mv9_id = INDEXES::get_mv9_id($mv9); 
$mysql['mv9_id'] = mysql_real_escape_string($mv9_id);

$mv10 = mysql_real_escape_string($_GET['mv10']);
$mv10 = str_replace('%20',' ',$mv10);
$mv10_id = INDEXES::get_mv10_id($mv10);
$mysql['mv10_id'] = mysql_real_escape_string($mv10_id);

$mv11 = mysql_real_escape_string($_GET['mv11']);
$mv11 = str_replace('%20',' ',$mv11);  
$mv11_id = INDEXES::get_mv11_id($mv11); 
$mysql['mv11_id'] = mysql_real_escape_string($mv11_id);

$mv12 = mysql_real_escape_string($_GET['mv12']);
$mv12 = str_replace('%20',' ',$mv12);
$mv12_id = INDEXES::get_mv12_id($mv12);
$mysql['mv12_id'] = mysql_real_escape_string($mv12_id);

$mv13 = mysql_real_escape_string($_GET['mv13']);
$mv13 = str_replace('%20',' ',$mv13);  
$mv13_id = INDEXES::get_mv13_id($mv13); 
$mysql['mv13_id'] = mysql_real_escape_string($mv13_id);

$mv14 = mysql_real_escape_string($_GET['mv14']);
$mv14 = str_replace('%20',' ',$mv14);
$mv14_id = INDEXES::get_mv14_id($mv14);
$mysql['mv14_id'] = mysql_real_escape_string($mv14_id);

$mv15 = mysql_real_escape_string($_GET['mv15']);
$mv15 = str_replace('%20',' ',$mv15);  
$mv15_id = INDEXES::get_mv15_id($mv15); 
$mysql['mv15_id'] = mysql_real_escape_string($mv15_id);


$ip_id = INDEXES::get_ip_id($_SERVER['HTTP_X_FORWARDED_FOR']);
$mysql['ip_id'] = mysql_real_escape_string($ip_id);     

$id = INDEXES::get_platform_and_browser_id();
$mysql['platform_id'] = mysql_real_escape_string($id['platform']); 
$mysql['browser_id'] = mysql_real_escape_string($id['browser']); 

$mysql['click_in'] = 1;
$mysql['click_out'] = 0;


//now lets get variables for clicks site
//so this is going to check the REFERER URL, for a ?url=, which is the ACUTAL URL, instead of the google content, pagead2.google.... 
if ($referer_query['url']) { 
	$click_referer_site_url_id = INDEXES::get_site_url_id($referer_query['url']);
} else {
	$click_referer_site_url_id = INDEXES::get_site_url_id($_GET['referer']);
}
$mysql['click_referer_site_url_id'] = mysql_real_escape_string($click_referer_site_url_id); 


 //see if this click should be filtered
$ip_address = $_SERVER['HTTP_X_FORWARDED_FOR'];
$user_id = $tracker_row['user_id'];

$click_filtered = FILTER::startFilter($click_id,$ip_id,$ip_address,$user_id);
$mysql['click_filtered'] = mysql_real_escape_string($click_filtered);



//ok we have the main data, now insert this row
$click_sql = "INSERT INTO   202_clicks_counter SET click_id=DEFAULT"; 
$click_result = mysql_query($click_sql) or record_mysql_error($click_sql);

//now gather the info for the advance click insert
$click_id = mysql_insert_id();
$mysql['click_id'] = mysql_real_escape_string($click_id);
$click_id_public = rand(1,9) . $click_id . rand(1,9);
$mysql['click_id_public'] = mysql_real_escape_string($click_id_public);

//because this is a simple landing page, set click_alp (which stands for click advanced landing page, equal to 0)
$mysql['click_alp'] = 1;

//ok we have the main data, now insert this row
$click_sql = "INSERT INTO   202_clicks
			  SET           	click_id='".$mysql['click_id']."',
							user_id = '".$mysql['user_id']."',   
							landing_page_id='".$mysql['landing_page_id']."',
							ppc_account_id = '".$mysql['ppc_account_id']."',   
							click_cpc = '".$mysql['click_cpc']."',   
							click_payout = '".$mysql['click_payout']."',   
							click_filtered = '".$mysql['click_filtered']."',   
							click_alp = '".$mysql['click_alp']."',   
							click_time = '".$mysql['click_time']."'"; 
$click_result = mysql_query($click_sql) or record_mysql_error($click_sql);

//ok we have the main data, now insert this row
$click_sql = "INSERT INTO  202_clicks_spy
			  SET          	 	click_id='".$mysql['click_id']."',
							user_id = '".$mysql['user_id']."',   
							landing_page_id='".$mysql['landing_page_id']."',
							ppc_account_id = '".$mysql['ppc_account_id']."',   
							click_cpc = '".$mysql['click_cpc']."',   
							click_payout = '".$mysql['click_payout']."',   
							click_filtered = '".$mysql['click_filtered']."',   
							click_alp = '".$mysql['click_alp']."',   
							click_time = '".$mysql['click_time']."'"; 
$click_result = mysql_query($click_sql) or record_mysql_error($click_sql);

//now we have the click's advance data, now insert this row
$click_sql = "INSERT INTO  202_clicks_advance
			  SET           	click_id='".$mysql['click_id']."',
							text_ad_id='".$mysql['text_ad_id']."',
							keyword_id='".$mysql['keyword_id']."',
							ip_id='".$mysql['ip_id']."',
							platform_id='".$mysql['platform_id']."',
							browser_id='".$mysql['browser_id']."'";
$click_result = mysql_query($click_sql) or record_mysql_error($click_sql);   

//insert the tracking data
$click_sql = "
	INSERT INTO
		202_clicks_tracking
	SET
		click_id='".$mysql['click_id']."',
		c1_id = '".$mysql['c1_id']."',
		c2_id = '".$mysql['c2_id']."',
		c3_id = '".$mysql['c3_id']."',
		c4_id = '".$mysql['c4_id']."',
		c5_id = '".$mysql['c5_id']."',
		c6_id = '".$mysql['c6_id']."',
		c7_id = '".$mysql['c7_id']."',
		c8_id = '".$mysql['c8_id']."',
		c9_id = '".$mysql['c9_id']."',
		c10_id = '".$mysql['c10_id']."',
		c11_id = '".$mysql['c11_id']."',
		c12_id = '".$mysql['c12_id']."', 		
		c13_id = '".$mysql['c13_id']."',
		c14_id = '".$mysql['c14_id']."',
		c15_id = '".$mysql['c15_id']."', 
		mv1_id = '".$mysql['mv1_id']."',
		mv2_id = '".$mysql['mv2_id']."',
		mv3_id = '".$mysql['mv3_id']."',
		mv4_id = '".$mysql['mv4_id']."',
		mv5_id = '".$mysql['mv5_id']."',
		mv6_id = '".$mysql['mv6_id']."',
		mv7_id = '".$mysql['mv7_id']."',
		mv8_id = '".$mysql['mv8_id']."',
		mv9_id = '".$mysql['mv9_id']."',
		mv10_id = '".$mysql['mv10_id']."',
		mv11_id = '".$mysql['mv11_id']."',
		mv12_id = '".$mysql['mv12_id']."', 		
		mv13_id = '".$mysql['mv13_id']."',
		mv14_id = '".$mysql['mv14_id']."',
		mv15_id = '".$mysql['mv15_id']."'";
$click_result = mysql_query($click_sql) or record_mysql_error($click_sql);

if (!$tracker_row['click_cloaking']) { 
	$mysql['click_cloaking'] = -1; 
} else {
	$mysql['click_cloaking'] = mysql_real_escape_string($tracker_row['click_cloaking']); 	
}


//ok we have our click recorded table, now lets insert theses
$click_sql = "INSERT INTO   202_clicks_record
			  SET           	click_id='".$mysql['click_id']."',
							click_id_public='".$mysql['click_id_public']."',
							click_cloaking='".$mysql['click_cloaking']."',
							click_in='".$mysql['click_in']."',
							click_out='".$mysql['click_out']."'"; 
$click_result = mysql_query($click_sql) or record_mysql_error($click_sql);
							


$landing_site_url = $_SERVER['HTTP_REFERER'];
$click_landing_site_url_id = INDEXES::get_site_url_id($landing_site_url); 
$mysql['click_landing_site_url_id'] = mysql_real_escape_string($click_landing_site_url_id);

$old_lp_site_url = 'http://'.$_SERVER['REDIRECT_SERVER_NAME'].'/lp/'.$landing_page_id_public;  

//insert this
$click_sql = "INSERT INTO   202_clicks_site
			  SET           	click_id='".$mysql['click_id']."',
							click_referer_site_url_id='".$mysql['click_referer_site_url_id']."',
							click_landing_site_url_id='".$mysql['click_landing_site_url_id']."',
							click_outbound_site_url_id='".$mysql['click_outbound_site_url_id']."',
							click_cloaking_site_url_id='".$mysql['click_cloaking_site_url_id']."',
							click_redirect_site_url_id='".$mysql['click_redirect_site_url_id']."'";
$click_result = mysql_query($click_sql) or record_mysql_error($click_sql); 

//update the click summary table if this is a 'real click'
#if ($click_filtered == 0) {
	
	$now = time();

	$today_day = date('j', time());
	$today_month = date('n', time());
	$today_year = date('Y', time());

	//the click_time is recorded in the middle of the day
	$click_time = mktime(12,0,0,$today_month,$today_day,$today_year);
	$mysql['click_time'] = mysql_real_escape_string($click_time);

	//check to make sure this click_summary doesn't already exist
	$check_sql = "SELECT  COUNT(*)
				  FROM    202_summary_overview
				  WHERE user_id='".$mysql['user_id']."'
				  AND     landing_page_id='".$mysql['landing_page_id']."'
				  AND     ppc_account_id='".$mysql['ppc_account_id']."'
				  AND     click_time='".$mysql['click_time']."'";
	$check_result = mysql_query($check_sql) or record_mysql_error($check_sql);
	$check_count = mysql_result($check_result,0,0);      

	//if this click summary hasn't been recorded do this now
	if ($check_count == 0 ) {

		$insert_sql = "INSERT INTO 202_summary_overview
					   	SET         user_id='".$mysql['user_id']."',
								   landing_page_id='".$mysql['landing_page_id']."',
								   ppc_account_id='".$mysql['ppc_account_id']."',
								   click_time='".$mysql['click_time']."'";
		$insert_result = mysql_query($insert_sql);
	}  
#}

//set the cookie
setClickIdCookie($mysql['click_id'],$mysql['aff_campaign_id']);

?> 


function t202initB() { 

	var subid ='<?php echo $click_id; ?>';
	createCookie('tracking202subid',subid,0);
	
	var pci = '<?php echo $click_id_public; ?>';
	createCookie('tracking202pci',pci,0);

}

t202initB(); 