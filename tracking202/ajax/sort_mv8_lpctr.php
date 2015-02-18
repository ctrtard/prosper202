<? include_once($_SERVER['DOCUMENT_ROOT'] . '/202-config/connect.php');

AUTH::require_user();


//set the timezone for the user, for entering their dates.
	AUTH::set_timezone($_SESSION['user_timezone']);

//show breakdown
	runBreakdown(true);

//grab user time range preference
	$time = grab_timeframe();
	$mysql['to'] = mysql_real_escape_string($time['to']);
	$mysql['from'] = mysql_real_escape_string($time['from']);


//show real or filtered clicks
	$mysql['user_id'] = mysql_real_escape_string($_SESSION['user_id']);
	$user_sql = "SELECT user_pref_breakdown, user_pref_show, user_cpc_or_cpv FROM 202_users_pref WHERE user_id=".$mysql['user_id'];
	$user_result = _mysql_query($user_sql, $dbGlobalLink); //($user_sql);
	$user_row = mysql_fetch_assoc($user_result);
	$breakdown = $user_row['user_pref_breakdown'];

	if ($user_row['user_pref_show'] == 'all') { $click_flitered = ''; }
	if ($user_row['user_pref_show'] == 'real') { $click_filtered = " AND click_filtered='0' "; }
	if ($user_row['user_pref_show'] == 'filtered') { $click_filtered = " AND click_filtered='1' "; }
	if ($user_row['user_pref_show'] == 'leads') { $click_filtered = " AND click_lead='1' "; }

	if ($user_row['user_cpc_or_cpv'] == 'cpv')  $cpv = true;
	else 										$cpv = false;


//only create a new list if there is no post
	if (($_POST['order']== '') and ($_POST['offset'] == '')) {

		//delete old keyword list
			$mysql['user_id'] = mysql_real_escape_string($_SESSION['user_id']);
			$keyword_sql = "DELETE FROM `202_sort_mv8_lpctr` WHERE `user_id`='".$mysql['user_id']."'";
			$keyword_result = mysql_query($keyword_sql) or record_mysql_error($keyword_sql);

		//lets build the new keyword list
			$db_table = '2c';
			$query = query('
				SELECT *
				FROM
					202_clicks AS 2c
					LEFT OUTER JOIN 202_clicks_site AS 2cs ON (2cs.click_id = 2c.click_id)
                    LEFT OUTER JOIN 202_clicks_tracking AS 2tr ON (2tr.click_id = 2c.click_id) 
					LEFT OUTER JOIN 202_tracking_mv8 AS 2v ON (2v.mv8_id = 2tr.mv8_id)
			', $db_table, true, true, false, " $click_filtered GROUP BY 2v.mv8_id", false, false, true);
			$info_sql = $query['click_sql'];
			$info_result = mysql_query($info_sql) or record_mysql_error($info_sql);
//            var_dump($info_sql);
//            die;

		//run query
			while ($info_row = mysql_fetch_assoc($info_result)) {

				//mysql escape the vars
					$mysql['mv8_id'] = mysql_real_escape_string($info_row['mv8_id']);

				//grab the variables

                    $click_sql = "
                        SELECT
                            COUNT(*) AS clicks,
                            AVG(2c.click_cpc) AS avg_cpc,
                            SUM(2cr.click_out) AS click_throughs,
                            SUM(2c.click_lead) AS leads,
                            SUM(2c.click_payout*2c.click_lead) AS income
                        FROM
                            202_clicks AS 2c
                            LEFT OUTER JOIN 202_clicks_site AS 2cs ON (2cs.click_id = 2c.click_id)
                            LEFT OUTER JOIN 202_clicks_record AS 2cr ON (2cr.click_id = 2c.click_id)
                            LEFT OUTER JOIN 202_clicks_tracking AS 2tr ON (2tr.click_id = 2c.click_id)    
                            LEFT OUTER JOIN 202_tracking_mv8 AS 2v ON (2v.mv8_id = 2tr.mv8_id)  
                    ";
                    $click_sql_add = "
                        $click_filtered
                        AND 2v.mv8_id='".$mysql['mv8_id']."'
                        GROUP BY 2v.mv8_id
                    ";
                    
//                    var_dump($click_sql);
//                    var_dump($click_sql_add);

                    
                    $query = query($click_sql, $db_table, true, true, false, $click_sql_add, false, false, true);
                    $click_sql = $query['click_sql'];
                    $click_result = mysql_query($click_sql) or record_mysql_error($click_sql);
                    $click_row = mysql_fetch_assoc($click_result);
                    
//                    var_dump($click_row);

                    

                //get the stats
                    $clicks = 0;
                    $clicks = $click_row['clicks'];

                    $total_clicks = $total_clicks + $clicks;

                    $click_throughs = 0;
                    $click_throughs = $click_row['click_throughs'];

                    $total_click_throughs = $click_throughs;

                //ctr rate
                    $ctr_ratio = 0;
                    $ctr_ratio = @round($click_throughs/$clicks*100,2);

                    $total_ctr_ratio = @round($total_click_throughs/$clicks*100,2);

                //avg cpc and cost
                    $avg_cpc = 0;
                    $avg_cpc = $click_row['avg_cpc'];

                    $cost = 0;
                    $cost = $clicks * $avg_cpc;

                    $total_cost = $total_cost + $cost;
                    $total_avg_cpc = @round($total_cost/$total_clicks, 5);

                //leads
                    $leads = 0;
                    $leads = $click_row['leads'];

                    $total_leads = $total_leads + $leads;

                //signup ratio
                    $su_ratio - 0;
                    $su_ratio = @round($leads/$clicks*100,2);

                    $total_su_ratio = @round($total_leads/$total_clicks*100,2);

                //current payout
                    $payout = 0;
                    $payout = $info_row['click_payout'];

                //income
                    $income = 0;
                    $income = $click_row['income'];

                    $total_income = $total_income + $income;

                //grab the EPC
                    $epc = 0;
                    $epc = @round($income/$clicks,2);

                    $total_epc = @@round($total_income/$total_clicks,2);

                //net income
                    $net = 0;
                    $net = $income - $cost;

                    $total_net = $total_income - $total_cost;

                //roi
                    $roi = 0;
                    $roi = @round($net/$cost*100);

                    $total_roi = @round($total_net/$total_cost);

                //mysql escape vars
                    $mysql['sort_mv8_clicks'] = mysql_real_escape_string($clicks);
                    
// Clean escape characters from strings
					$mysql['sort_mv8_click_throughs'] = mysql_real_escape_string($total_click_throughs);
					$mysql['sort_mv8_ctr'] = mysql_real_escape_string($total_ctr_ratio);
					$mysql['sort_mv8_leads'] = mysql_real_escape_string($leads);
					$mysql['sort_mv8_su_ratio'] = mysql_real_escape_string($su_ratio);
					$mysql['sort_mv8_payout'] = mysql_real_escape_string($payout);
					$mysql['sort_mv8_avg_cpc'] = mysql_real_escape_string($avg_cpc);
					$mysql['sort_mv8_epc'] = mysql_real_escape_string($epc);
					$mysql['sort_mv8_income'] = mysql_real_escape_string($income);
					$mysql['sort_mv8_cost'] = mysql_real_escape_string($cost);
					$mysql['sort_mv8_net'] = mysql_real_escape_string($net);
					$mysql['sort_mv8_roi'] = mysql_real_escape_string($roi);


                //insert the data
                    $keyword_sort_sql = "INSERT INTO 202_sort_mv8_lpctr
                                         SET         user_id='".$mysql['user_id']."',
                                                     mv8_id='".$mysql['mv8_id']."',
                                                     sort_mv8_clicks='".$mysql['sort_mv8_clicks']."',

                                                     sort_mv8_click_throughs='".$mysql['sort_mv8_click_throughs']."',
                                                     sort_mv8_ctr='".$mysql['sort_mv8_ctr']."',

                                                     sort_mv8_leads='".$mysql['sort_mv8_leads']."',
                                                     sort_mv8_su_ratio='".$mysql['sort_mv8_su_ratio']."',
                                                     sort_mv8_payout='".$mysql['sort_mv8_payout']."',
                                                     sort_mv8_epc='".$mysql['sort_mv8_epc']."',
                                                     sort_mv8_avg_cpc='".$mysql['sort_mv8_avg_cpc']."',
                                                     sort_mv8_income='".$mysql['sort_mv8_income']."',
                                                     sort_mv8_cost='".$mysql['sort_mv8_cost']."',
                                                     sort_mv8_net='".$mysql['sort_mv8_net']."',
                                                     sort_mv8_roi='".$mysql['sort_mv8_roi']."'";
                                                     
//var_dump($keyword_sort_sql);
//echo "<hr>";                                                     
                    $keyword_sort_result = mysql_query($keyword_sort_sql) or record_mysql_error($keyword_sort_sql);

			}

	}


$html['order'] = htmlentities($_POST['order'], ENT_QUOTES, 'UTF-8');

$html['sort_mv8_mv8_order'] = 'mv8 asc';
if ($_POST['order'] == 'mv8 asc') {
	$html['sort_mv8_mv8_order'] = 'mv8 desc';
	$mysql['order'] = 'ORDER BY `mv8` DESC';
} elseif ($_POST['order'] == 'mv8 desc') {
	$html['sort_mv8_mv8_order'] = 'mv8 ASC';
	$mysql['order'] = 'ORDER BY `mv8` ASC';
}

$html['sort_mv8_clicks_order'] = 'sort_mv8_clicks asc';
if ($_POST['order'] == 'sort_mv8_clicks asc') {
	$html['sort_mv8_clicks_order'] = 'sort_mv8_clicks desc';
	$mysql['order'] = 'ORDER BY `sort_mv8_clicks` DESC';
} elseif ($_POST['order'] == 'sort_mv8_clicks desc') {
	$html['sort_mv8_clicks_order'] = 'sort_mv8_clicks asc';
	$mysql['order'] = 'ORDER BY `sort_mv8_clicks` ASC';
}

$html['sort_mv8_click_throughs_order'] = 'sort_mv8_click_throughs asc';
if ($_POST['order'] == 'sort_mv8_click_throughs asc') {
	$html['sort_mv8_click_throughs_order'] = 'sort_mv8_click_throughs desc';
	$mysql['order'] = 'ORDER BY `sort_mv8_click_throughs` DESC';
} elseif ($_POST['order'] == 'sort_mv8_click_throughs desc') {
	$html['sort_mv8_click_throughs_order'] = 'sort_mv8_click_throughs asc';
	$mysql['order'] = 'ORDER BY `sort_mv8_click_throughs` ASC';
}

$html['sort_mv8_ctr_order'] = 'sort_mv8_ctr asc';
if ($_POST['order'] == 'sort_mv8_ctr asc') {
	$html['sort_mv8_ctr_order'] = 'sort_mv8_ctr desc';
	$mysql['order'] = 'ORDER BY `sort_mv8_ctr` DESC';
} elseif ($_POST['order'] == 'sort_mv8_ctr desc') {
	$html['sort_mv8_ctr_order'] = 'sort_mv8_ctr asc';
	$mysql['order'] = 'ORDER BY `sort_mv8_ctr` ASC';
}

$html['sort_mv8_leads_order'] = 'sort_mv8_leads asc';
if ($_POST['order'] == 'sort_mv8_leads asc') {
	$html['sort_mv8_leads_order'] = 'sort_mv8_leads desc';
	$mysql['order'] = 'ORDER BY `sort_mv8_leads` DESC';
} elseif ($_POST['order'] == 'sort_mv8_leads desc') {
	$html['sort_mv8_leads_order'] = 'sort_mv8_leads asc';
	$mysql['order'] = 'ORDER BY `sort_mv8_leads` ASC';
}

$html['sort_mv8_su_ratio_order'] = 'sort_mv8_su_ratio asc';
if ($_POST['order'] == 'sort_mv8_su_ratio asc') {
	$html['sort_mv8_su_ratio_order'] = 'sort_mv8_su_ratio desc';
	$mysql['order'] = 'ORDER BY `sort_mv8_su_ratio` DESC';
} elseif ($_POST['order'] == 'sort_mv8_su_ratio desc') {
	$html['sort_mv8_su_ratio_order'] = 'sort_mv8_su_ratio asc';
	$mysql['order'] = 'ORDER BY `sort_mv8_su_ratio` ASC';
}

$html['sort_mv8_payout_order'] = 'sort_mv8_payout asc';
if ($_POST['order'] == 'sort_mv8_payout asc') {
	$html['sort_mv8_payout_order'] = 'sort_mv8_payout desc';
	$mysql['order'] = 'ORDER BY `sort_mv8_payout` DESC';
} elseif ($_POST['order'] == 'sort_mv8_payout desc') {
	$html['sort_mv8_payout_order'] = 'sort_mv8_payout asc';
	$mysql['order'] = 'ORDER BY `sort_mv8_payout` ASC';
}

$html['sort_mv8_epc_order'] = 'sort_mv8_epc asc';
if ($_POST['order'] == 'sort_mv8_epc asc') {
	$html['sort_mv8_epc_order'] = 'sort_mv8_epc desc';
	$mysql['order'] = 'ORDER BY `sort_mv8_epc` DESC';
} elseif ($_POST['order'] == 'sort_mv8_epc desc') {
	$html['sort_mv8_epc_order'] = 'sort_mv8_epc asc';
	$mysql['order'] = 'ORDER BY `sort_mv8_epc` ASC';
}

$html['sort_mv8_avg_cpc_order'] = 'sort_mv8_avg_cpc asc';
if ($_POST['order'] == 'sort_mv8_avg_cpc asc') {
	$html['sort_mv8_avg_cpc_order'] = 'sort_mv8_avg_cpc desc';
	$mysql['order'] = 'ORDER BY `sort_mv8_avg_cpc` DESC';
} elseif ($_POST['order'] == 'sort_mv8_avg_cpc desc') {
	$html['sort_mv8_avg_cpc_order'] = 'sort_mv8_avg_cpc asc';
	$mysql['order'] = 'ORDER BY `sort_mv8_avg_cpc` ASC';
}

$html['sort_mv8_income_order'] = 'sort_mv8_income asc';
if ($_POST['order'] == 'sort_mv8_income asc') {
	$html['sort_mv8_income_order'] = 'sort_mv8_income desc';
	$mysql['order'] = 'ORDER BY `sort_mv8_income` DESC';
} elseif ($_POST['order'] == 'sort_mv8_income desc') {
	$html['sort_mv8_income_order'] = 'sort_mv8_income asc';
	$mysql['order'] = 'ORDER BY `sort_mv8_income` ASC';
}

$html['sort_mv8_cost_order'] = 'sort_mv8_cost asc';
if ($_POST['order'] == 'sort_mv8_cost asc') {
	$html['sort_mv8_cost_order'] = 'sort_mv8_cost desc';
	$mysql['order'] = 'ORDER BY `sort_mv8_cost` DESC';
} elseif ($_POST['order'] == 'sort_mv8_cost desc') {
	$html['sort_mv8_cost_order'] = 'sort_mv8_cost asc';
	$mysql['order'] = 'ORDER BY `sort_mv8_cost` ASC';
}

$html['sort_mv8_net_order'] = 'sort_mv8_net asc';
if ($_POST['order'] == 'sort_mv8_net asc') {
	$html['sort_mv8_net_order'] = 'sort_mv8_net desc';
	$mysql['order'] = 'ORDER BY `sort_mv8_net` DESC';
} elseif ($_POST['order'] == 'sort_mv8_net desc') {
	$html['sort_mv8_net_order'] = 'sort_mv8_net asc';
	$mysql['order'] = 'ORDER BY `sort_mv8_net` ASC';
}

$html['sort_mv8_roi_order'] = 'sort_mv8_roi asc';
if ($_POST['order'] == 'sort_mv8_roi asc') {
	$html['sort_mv8_roi_order'] = 'sort_mv8_roi desc';
	$mysql['order'] = 'ORDER BY `sort_mv8_roi` DESC';
} elseif ($_POST['order'] == 'sort_mv8_roi desc') {
	$html['sort_mv8_roi_order'] = 'sort_mv8_roi asc';
	$mysql['order'] = 'ORDER BY `sort_mv8_roi` ASC';
}




if (empty($mysql['order'])) {
	$mysql['order'] = ' ORDER BY 202_sort_mv8_lpctr.sort_mv8_clicks DESC';
}
$db_table = '202_sort_mv8_lpctr';

$query = query('SELECT * FROM 202_sort_mv8_lpctr LEFT JOIN 202_tracking_mv8 ON (202_sort_mv8_lpctr.mv8_id = 202_tracking_mv8.mv8_id)', $db_table, false, false, false,  $mysql['order'], $_POST['offset'], true, true);
$keyword_sql = $query['click_sql'];
$keyword_result = mysql_query($keyword_sql) or record_mysql_error($keyword_sql);

$html['from'] = htmlentities($query['from'], ENT_QUOTES, 'UTF-8');
$html['to'] = htmlentities($query['to'], ENT_QUOTES, 'UTF-8');
$html['rows'] = htmlentities($query['rows'], ENT_QUOTES, 'UTF-8');


?>
<table cellspacing="0" cellpadding="0" style="width: 100%; font-size: 12px;">
	<tr>
		<td width="100%;">
			<a target="_new" href="/tracking202/analyze/mv8_lpctr_download.php">
				<strong>Download to excel</strong>
				<img src="/202-img/icons/16x16/page_white_excel.png" style="margin: 0px 0px -3px 3px;"/>

			</a>
		</td>
		<td>
			<? printf('<div class="results">Results <b>%s - %s</b> of <b>%s</b></div>',$html['from'],$html['to'],$html['rows']); ?>
		</td>
	</tr>
</table>

<table cellpadding="0" cellspacing="1" class="m-stats">
	<tr>
		<th><a class="onclick_color" onclick="loadContent('/tracking202/ajax/sort_mv8_lpctr.php','','<? echo $html['sort_mv8_mv8_order']; ?>');">Snippet H</a></th>
		<th><a class="onclick_color" onclick="loadContent('/tracking202/ajax/sort_mv8_lpctr.php','','<? echo $html['sort_mv8_clicks_order']; ?>');">Clicks</a></th>
		<th><a class="onclick_color" onclick="loadContent('/tracking202/ajax/sort_mv8_lpctr.php','','<? echo $html['sort_mv8_click_throughs_order']; ?>');">Click Throughs</a></th>
		<th><a class="onclick_color" onclick="loadContent('/tracking202/ajax/sort_mv8_lpctr.php','','<? echo $html['sort_mv8_ctr_order']; ?>');">LP CTR</a></th>
		<th><a class="onclick_color" onclick="loadContent('/tracking202/ajax/sort_mv8_lpctr.php','','<? echo $html['sort_mv8_leads_order']; ?>');">Leads</a></th>
		<th><a class="onclick_color" onclick="loadContent('/tracking202/ajax/sort_mv8_lpctr.php','','<? echo $html['sort_mv8_su_ratio_order']; ?>');">S/U</a></th>
		<th><a class="onclick_color" onclick="loadContent('/tracking202/ajax/sort_mv8_lpctr.php','','<? echo $html['sort_mv8_payout_order']; ?>');">Payout</a></th>
		<th><a class="onclick_color" onclick="loadContent('/tracking202/ajax/sort_mv8_lpctr.php','','<? echo $html['sort_mv8_epc_order']; ?>');">EPC</a></th>
		<th><a class="onclick_color" onclick="loadContent('/tracking202/ajax/sort_mv8_lpctr.php','','<? echo $html['sort_mv8_avg_cpc_order']; ?>');">Avg CPC</a></th>
		<th><a class="onclick_color" onclick="loadContent('/tracking202/ajax/sort_mv8_lpctr.php','','<? echo $html['sort_mv8_income_order']; ?>');">Income</a></th>
		<th><a class="onclick_color" onclick="loadContent('/tracking202/ajax/sort_mv8_lpctr.php','','<? echo $html['sort_mv8_cost_order']; ?>');">Cost</a></th>
		<th><a class="onclick_color" onclick="loadContent('/tracking202/ajax/sort_mv8_lpctr.php','','<? echo $html['sort_mv8_net_order']; ?>');">Net</a></th>
		<th><a class="onclick_color" onclick="loadContent('/tracking202/ajax/sort_mv8_lpctr.php','','<? echo $html['sort_mv8_roi_order']; ?>');">ROI</a></th>
	</tr>

	<?

while ($keyword_row = mysql_fetch_array($keyword_result, MYSQL_ASSOC)) {

	if (!$keyword_row['mv8']) {
		$html['keyword'] = '[Snippet H unused]';
	} else {
		$html['keyword'] = htmlentities($keyword_row['mv8'], ENT_QUOTES, 'UTF-8');
	}

	error_reporting(0);

	$html['sort_mv8_clicks'] = htmlentities($keyword_row['sort_mv8_clicks'], ENT_QUOTES, 'UTF-8');

	$html['sort_mv8_click_throughs'] = htmlentities($keyword_row['sort_mv8_click_throughs'], ENT_QUOTES, 'UTF-8');
	$html['sort_mv8_ctr'] = htmlentities($keyword_row['sort_mv8_ctr'], ENT_QUOTES, 'UTF-8');
	$html['sort_mv8_leads'] = htmlentities($keyword_row['sort_mv8_leads'], ENT_QUOTES, 'UTF-8');
	$html['sort_mv8_su_ratio'] = htmlentities($keyword_row['sort_mv8_su_ratio'].'%', ENT_QUOTES, 'UTF-8');
	$html['sort_mv8_payout'] = htmlentities(dollar_format($keyword_row['sort_mv8_payout']), ENT_QUOTES, 'UTF-8');
	$html['sort_mv8_epc'] = htmlentities(dollar_format($keyword_row['sort_mv8_epc']), ENT_QUOTES, 'UTF-8');
	$html['sort_mv8_avg_cpc'] = htmlentities(dollar_format($keyword_row['sort_mv8_avg_cpc'], $cpv), ENT_QUOTES, 'UTF-8');
	$html['sort_mv8_income'] = htmlentities(dollar_format($keyword_row['sort_mv8_income']), ENT_QUOTES, 'UTF-8');
	$html['sort_mv8_cost'] = htmlentities(dollar_format($keyword_row['sort_mv8_cost'], $cpv), ENT_QUOTES, 'UTF-8');
	$html['sort_mv8_net'] = htmlentities(dollar_format($keyword_row['sort_mv8_net'], $cpv), ENT_QUOTES, 'UTF-8');
	$html['sort_mv8_roi'] = htmlentities($keyword_row['sort_mv8_roi'].'%', ENT_QUOTES, 'UTF-8');

	error_reporting(6135); ?>

	<tr>
		<td class="m-row2  m-row2-fade" ><? echo $html['keyword']; ?></td>
		<td class="m-row1"><? echo $html['sort_mv8_clicks']; ?></td>
		<td class="m-row1"><? echo $html['sort_mv8_click_throughs']; ?></td>
		<td class="m-row1"><? echo $html['sort_mv8_ctr']; ?></td>
		<td class="m-row1"><? echo $html['sort_mv8_leads']; ?></td>
		<td class="m-row1"><? echo $html['sort_mv8_su_ratio']; ?></td>
		<td class="m-row1"><? echo $html['sort_mv8_payout']; ?></td>
		<td class="m-row3"><? echo $html['sort_mv8_epc']; ?></td>
		<td class="m-row3"><? echo $html['sort_mv8_avg_cpc']; ?></td>
		<td class="m-row4 "><? echo $html['sort_mv8_income']; ?></td>
		<td class="m-row4 ">(<? echo $html['sort_mv8_cost']; ?>)</td>
		<td class="<? if ($keyword_row['sort_mv8_net'] > 0) { echo 'm-row_pos'; } elseif ($keyword_row['sort_mv8_net'] < 0) { echo 'm-row_neg'; } else { echo 'm-row_zero'; } ?>"><? echo $html['sort_mv8_net'] ; ?></td>
		<td class="<? if ($keyword_row['sort_mv8_net'] > 0) { echo 'm-row_pos'; } elseif ($keyword_row['sort_mv8_net'] < 0) { echo 'm-row_neg'; } else { echo 'm-row_zero'; } ?>"><? echo $html['sort_mv8_roi'] ; ?></td>
	</tr>
<? } ?>
</table>

<? if ($query['pages'] > 2) { ?>
	<div class="offset">   <?
		if ($query['offset'] > 0) {
			printf(' <a class="onclick_color" onclick="loadContent(\'/tracking202/ajax/sort_mv8_lpctr.php\',\'%s\',\'%s\');">First</a> ', $i, $html['order']);
			printf(' <a class="onclick_color" onclick="loadContent(\'/tracking202/ajax/sort_mv8_lpctr.php\',\'%s\',\'%s\');">Prev</a> ', $query['offset'] - 1, $html['order']);
		}

		if ($query['pages'] > 1) {
			for ($i=0; $i < $query['pages']-1; $i++) {
				if (($i >= $query['offset'] - 10) and ($i < $query['offset'] + 11)) {
					if ($query['offset'] == $i) { $class = 'class="link_selected"'; } else { $class='class="onclick_color"'; }
					printf(' <a %s onclick="loadContent(\'/tracking202/ajax/sort_mv8_lpctr.php\',\'%s\',\'%s\');">%s</a> ', $class, $i, $html['order'], $i+1);
					unset($class);
				}
			}
		}

		if ($query['offset'] < $query['pages'] - 2) {
			printf(' <a class="onclick_color" onclick="loadContent(\'/tracking202/ajax/sort_mv8_lpctr.php\',\'%s\',\'%s\');"">Next</a> ', $query['offset'] + 1, $html['order']);
			printf(' <a class="onclick_color" onclick="loadContent(\'/tracking202/ajax/sort_mv8_lpctr.php\',\'%s\',\'%s\');">Last</a> ', $query['pages'] - 2, $html['order']);
		} ?>
	</div>
<? } ?>
