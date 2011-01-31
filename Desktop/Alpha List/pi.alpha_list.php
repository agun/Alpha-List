<?php

/*
=====================================================
 File: pi.sites.php
-----------------------------------------------------
 Purpose: Permits you to get a site url based on the 
 site's short_name or site_id, plus you can add in a path
=====================================================
*/


$plugin_info = array(
						'pi_name'			=> 'Alpha_list',
						'pi_version'		=> '1.0',
						'pi_author'			=> 'Andrew Gunstone',
						'pi_author_url'		=> 'http://www.thirststudios.com/',
						'pi_description'	=> 'Lists out an alphabetical list based on a particular weblog',
						'pi_usage'			=> Alpha_list::usage()
					);


class Alpha_list {

//
// Alphabetic links function
// creates a set of alphabetic links for the weblog entries being called
//
function alpha_links() {
	global $DB, $EXT, $FNS, $IN, $LOC, $PREFS, $SESS, $TMPL;
	
	$no_results = "";
	
	$site_id = $PREFS->ini('site_id');
	$db_prefix = $PREFS->ini('db_prefix');
	$current_page = $IN->GBL('page', 'GET') ? $IN->GBL('page', 'GET') : 1;
	
	$path = ($TMPL->fetch_param('path')) ? $TMPL->fetch_param('path') : "#";
	$css_id = ($TMPL->fetch_param('css_id')) ? " id=\"".$TMPL->fetch_param('css_id')."\"" : "id=\"alpha-list\"";
	$css_class = ($TMPL->fetch_param('css_class')) ? "class=\"" . $TMPL->fetch_param('css_class') . "\"" : "";
	$wrapper = ($TMPL->fetch_param('wrapper')) ? $TMPL->fetch_param('wrapper') : "div";


	// Where to place alpha list code
	$location = $TMPL->fetch_param('location');
	
	$tag_content = $tagdata = $TMPL->tagdata;

	$params = array();
	foreach($TMPL->var_pair as $pkey => $pval) {
		// Find weblog:entries tag
		if( ereg("^exp:weblog:entries", $pkey) ) {
			$params = $pval;
		}
		if( ereg("^no_results", $pkey) ) 
		{
			$temp = preg_match("/".LD."$pkey".RD."(.*?)".LD.SLASH.'no_results'.RD."/s", $TMPL->tagdata, $matches);
			$no_results = $matches[1];
			$tagdata = str_replace($matches[0], "", $tagdata);
		}
		else if( ereg("^section", $pkey) ) 
		{
			$temp = preg_match("/".LD."$pkey".RD."(.*?)".LD.SLASH.'section'.RD."/s", $TMPL->tagdata, $matches);
			$tagdata = str_replace($matches[0], "{exp:alpha_list:letter wrapper='$wrapper' title='{title}' count='{count}'}".$matches[1]."{&#47;exp:alpha_list:letter}", $tagdata);
		}

	}
	
	$where_clause = '';
	
	// Limit by weblog(s)
	if(isset($params['weblog'])) {
		$weblog_ids = explode(" ", $params['weblog']);
		$weblog_not = '';
		if($weblog_ids[0] == "not") {
			$weblog_ids = $weblog_ids[1];
			$weblog_not = 'NOT';
		} else {
			$weblog_ids = $weblog_ids[0];
		}
		
		$weblog_ids = explode("|", $weblog_ids);
		$weblog_temp = array();
		foreach($weblog_ids as $item) {
			$weblog_temp[] = "'" . $item . "'";
		}
		$weblog_ids = $weblog_temp;
		$weblog_ids = implode(",", $weblog_ids);
		$where_clause .= " AND blogs.blog_name $weblog_not IN ($weblog_ids)";
	}
	
	// Limit by author_id(s)
	if(isset($params['author_id'])) {
		if ($params['author_id'] == 'CURRENT_USER') {
    	$where_clause .=  "AND members.member_id = '" . $SESS->userdata('member_id')."' ";
    } elseif ($params['author_id'] == 'NOT_CURRENT_USER') {
    	$where_clause .=  "AND members.member_id != '" . $SESS->userdata('member_id')."' ";
    } else {                
      $where_clause .= $FNS->sql_andor_string($params['author_id'], 'members.member_id');
    }
	}
	
	// Limit by username
	if(isset($params['username'])) {
		if ($params['username'] == 'CURRENT_USER') {
    	$where_clause .=  "AND members.member_id = '".$SESS->userdata('member_id')."' ";
    } elseif ($params['username'] == 'NOT_CURRENT_USER') {
    	$where_clause .=  "AND members.member_id != '".$SESS->userdata('member_id')."' ";
    } else {                
    	$where_clause .= $FNS->sql_andor_string($params['username'], 'members.username');
    }
	}
	
	// Limit by entry_id(s)
	if(isset($params['entry_id'])) {
		$entry_ids = explode(" ", $params['entry_id']);
		if($entry_ids[0] == "not") {
			$entry_ids = $entry_ids[1];
			$entry_not = 'NOT';
		} else {
			$entry_ids = $entry_ids[0];
		}
		
		$entry_ids = explode("|", $entry_ids);
		$entry_ids = implode(",", $entry_ids);
		$where_clause .= " AND entries.entry_id $entry_not IN ($entry_ids)";
	}
	
	// Limit by entry_id_from
	if(isset($params['entry_id_from'])) {
		$where_clause .= " AND entries.entry_id >= " . $params['entry_id_from'];
	}
	
	// Limit by entry_id_to
	if(isset($params['entry_id_to'])) {
		$where_clause .= " AND entries.entry_id <= " . $params['entry_id_to'];
	}
	
	// Limit by group_id(s)
	if(isset($params['group_id'])) {
		$group_ids = explode(" ", $params['group_id']);
		if($group_ids[0] == "not") {
			$group_ids = $group_ids[1];
			$group_not = 'NOT';
		} else {
			$group_ids = $group_ids[0];
		}
		
		$group_ids = explode("|", $group_ids);
		$group_ids = implode(",", $group_ids);
		$where_clause .= " AND members.group_id $group_not IN ($group_ids)";
	}
	
	// Limit by show_expired
	if(isset($params['show_expired']) AND (!$params['show_expired'] || $params['show_expired'] == 'no')) {
		$where_clause .= " AND FROM_UNIXTIME(entries.expiration_date) < UTC_TIMESTAMP()";
	}
	
	// Limit by show_future_entries
	if(isset($params['show_future_entries']) AND (!$params['show_future_entries'] || $params['show_future_entries'] == 'no')) {
		$where_clause .= " AND FROM_UNIXTIME(entries.entry_date) <= UTC_TIMESTAMP()";
	}
	
	// Limit by start_on
	if(isset($params['start_on'])) {
     $where_clause .= "AND entries.entry_date >= '" . $LOC->convert_human_date_to_gmt($params['start_on']) . "' ";
	}

	// Limit by stop_before
  if(isset($params['stop_before'])) {
     $where_clause .= "AND entries.entry_date < '" . $LOC->convert_human_date_to_gmt($params['stop_before']) . "' ";
	}
	
	// Limit by year/month/day
	if(isset($params['year'])) {
		$year	= (! $params['year']) ? date('Y') : $params['year'];
    $smonth	= (! $params['month']) ? '01' : $params['month'];
    $emonth	= (! $params['month']) ? '12': $params['month'];
    $day	= (! $params['day']) ? '' : $params['day'];
    
    if ($day != '' AND ! $params['month']) {
			$smonth = date('m');
			$emonth = date('m');
    }
    
    if (strlen($smonth) == 1) $smonth = '0' . $smonth;
    if (strlen($emonth) == 1) $emonth = '0' . $emonth;

		if ($day == '')	{
			$sday = 1;
			$eday = $LOC->fetch_days_in_month($emonth, $year);
		}	else {
			$sday = $day;
			$eday = $day;
		}

		$stime = $LOC->set_gmt(mktime(0, 0, 0, $smonth, $sday, $year));
		$etime = $LOC->set_gmt(mktime(23, 59, 59, $emonth, $eday, $year));  

		$where_clause .= " AND entries.entry_date >= ".$stime." AND entries.entry_date <= ".$etime." ";
	}
	
	// Limit by status
	if(isset($params['status'])) {
		$status_ids = explode(" ", $params['status']);
		if($status_ids[0] == "not") {
			$status_ids = $status_ids[1];
			$status_not = 'NOT';
		} else {
			$status_ids = $status_ids[0];
		}
		
		$status_ids = explode("|", $status_ids);
		$status_temp = array();
		foreach($status_ids as $item) {
			if($item == 'IS_EMPTY') {
				$item = '';
			}
 			$status_temp[] = "'" . $item . "'";
		}
		$status_ids = $status_temp;
		$status_ids = implode(",", $status_ids);
		$where_clause .= " AND entries.status $status_not IN ($status_ids)";
	}
	
	// Limit by url_title(s)
	if(isset($params['url_title'])) {
		$url_title_ids = explode(" ", $params['url_title']);
		if($url_title_ids[0] == "not") {
			$url_title_ids = $url_title_ids[1];
			$url_title_not = 'NOT';
		} else {
			$url_title_ids = $url_title_ids[0];
		}
		
		$url_title_ids = explode("|", $url_title_ids);
		$url_title_temp = array();
		foreach($url_title_ids as $item) {
 			$url_title_temp[] = "'" . $item . "'";
		}
		$url_title_ids = $url_title_temp;
		
		$url_title_ids = implode(",", $url_title_ids);
		$where_clause .= " AND entries.url_title $url_title_not IN ($url_title_ids)";
	}
	
	// Limit by search:
	foreach($params as $param_k => $param_v) {
		if( ereg("^search:", $param_k) ) {
			$search_temp = explode(":", $param_k);
			$search_ids = '';
			$search_link = '';
			$search_type = '';
			$search_not = '';
			
			// determine if we have an OR search or an AND search
			if( strpos($param_v, "|") ) {
				$split_on = "|";
				$search_link = " OR ";
			} else {
				$split_on = "&&";
				$search_link = " AND ";
			}
			
			// determine if we have an EXACT or FUZZY search
			if( strpos($param_v, "=") === 0 ) {
				$search_type = 'exact';
			}
			
			// Remove = symbol for parsing
			$param_v = trim($param_v, "=");
			
			// Determine if we have a NOT search
			$param_temp = explode(" ", $param_v);
			if($param_temp[0] == "not") {
				$param_v = $param_temp[1];
				$search_not = 'NOT';
			}
			
			$search_ids = explode($split_on, $param_v);
			$params['search'][$search_temp[1]] = $search_ids;
			$params['search_link'][$search_temp[1]] = $search_link;
			$params['search_type'][$search_temp[1]] = $search_type;
			$params['search_not'][$search_temp[1]] = $search_not . " ";
		}
	}
	
	if(isset($params['search'])) {
		foreach($params['search'] as $skey => $sval) {
			$query = "SELECT field_id FROM " . $db_prefix . "_weblog_fields WHERE field_name = '$skey' AND site_id = $site_id";
			$result = $DB->query($query);
			
			$where_clause .= " AND (";
			foreach($sval as $term) {
				if( ereg("(.*)\W$", $term) ) {
					$comparison = "REGEXP";
				} else {
					$comparison = "LIKE";
				}
				
				$where_clause .= "wlog.field_id_" . $result->row['field_id'] . " " . $params['search_not'][$skey] . $comparison . " '";
				
				if($comparison == "REGEXP") {
					$where_clause .= "[[:<:]]" . substr($term, 0, -2) . "[[:>:]]";
				} else {
					// exact search or fuzzy search
					if($params['search_type'][$skey] == 'exact') {
						$where_clause .= "$term";
					} else {
						$where_clause .= "%$term%";
					}
				}
				
				$where_clause .= "'";
				
				if($term != end($sval)) {
					$where_clause .= $params['search_link'][$skey];
				}
			}
			$where_clause .= ")";
			
		}
	}
	
	// Build base for SQL query
	$sql = "SELECT entries.title" .
				 " FROM " . $db_prefix . "_weblog_titles AS entries" .
				 " LEFT JOIN " . $db_prefix . "_weblogs AS blogs ON entries.weblog_id = blogs.weblog_id" .
				 " LEFT JOIN " . $db_prefix . "_weblog_data AS wlog ON entries.entry_id = wlog.entry_id" .
				 " LEFT JOIN " . $db_prefix . "_members AS members ON members.member_id = entries.author_id";
	
	// Limit on category or category_group
	if(isset($params['category']) && ($params['category'] || $params['category_group'])) {
   	if((substr($params['category_group'], 0, 3) == 'not' OR substr($params['category'], 0, 3) == 'not') && $params['uncategorized_entries'] !== 'n')	{
   		$sql .= " LEFT JOIN " . $db_prefix . "_category_posts ON entries.entry_id = " . $db_prefix . "_category_posts.entry_id
		 					 LEFT JOIN " . $db_prefix . "_categories ON " . $db_prefix . "_category_posts.cat_id = " . $db_prefix . "_categories.cat_id";
   	}	else {
   		$sql .= " INNER JOIN " . $db_prefix . "_category_posts ON entries.entry_id = " . $db_prefix . "_category_posts.entry_id
		 					 INNER JOIN " . $db_prefix . "_categories ON " . $db_prefix . "_category_posts.cat_id = " . $db_prefix . "_categories.cat_id";
		}
	}
	
	$sql .= " WHERE entries.site_id = " . $site_id;
	
	if($where_clause) {
		$sql = $sql . $where_clause;
	}
	
	$sql = $sql . " ORDER BY entries.title ASC";

	$result = $DB->query($sql);

	// Make sure array indices are incremental (0..X)
	$result->result = array_values($result->result);
	
	$html = '';
	
	$alpha_list = array('#','A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z');

	$alpha_array = array();
	foreach($result->result as $key => $entry_data) {
		$alpha_array[$key] = substr(strtoupper($entry_data['title']),0,1);
		if (preg_match("/[0-9]/",$alpha_array[$key])) $alpha_array[$key] = "#";
	}
		
	foreach($alpha_list AS $key)
	{
		$letter = ($key == "#") ? "hash" : $key;

		if (in_array($key, $alpha_array))
			$html .= '<li><a href="'. $path . $letter.'">'.$key.'</a></li>';
		else
			$html .= '<li><a href="'. $path . $letter.'" class="no-link">'.$key.'</a></li>';
	}
		
	$html = "<ul {$css_id} {$css_class}>" . $html . "</ul>\n";
	
	if ($result->num_rows == 0){
		$html = $no_results;
	}else{
		// Decide where to show pagination links
		switch($location) {
			case 'top': 
			default:
				$html = $html . $tagdata . "</$wrapper>";
				break;
				
			case 'bottom':
				$html = $tagdata . "</$wrapper>" . $html;
				break;
				
			case 'both':
				$html = $html . $tagdata . "</$wrapper>" . $html;
				break;
		}
	}
	return $html;
}





    /** ----------------------------------------
    /**  Letter
    /** ----------------------------------------*/

    function letter()
    {
        global $TMPL, $FNS, $DB, $PREFS, $IN, $SESS;
		
		// get site ID
		$site_id = $PREFS->ini('site_id');
		// get current uri path
		$uri = $IN->URI;
		
		$tagdata = $TMPL->tagdata;

		$first_letter = ($TMPL->fetch_param('title')) ? substr(strtoupper($TMPL->fetch_param('title')),0,1) : "";
		if (preg_match("/[0-9]/",$first_letter)) $first_letter = "#";

		$wrapper = ($TMPL->fetch_param('wrapper')) ? $TMPL->fetch_param('wrapper') : "div";
		$count = ($TMPL->fetch_param('count')) ? $TMPL->fetch_param('count') : "0";
				
		if ($first_letter == '') return '';
		$css_id = "id=\"" . $first_letter . "\"";

		if (!isset($_SESSION['ts_alpha_letter'])) $_SESSION['ts_alpha_letter'] = '';
		
		$html = '';
		if ($_SESSION['ts_alpha_letter'] != $first_letter)
		{
			$letter = ($first_letter == "#") ? "hash" : $first_letter;
			if ($count > 1) $html = "</$wrapper>";
			$html .= "<$wrapper id=\"$letter\">";
			$html .= str_replace("{letter}", $first_letter, $tagdata);
			$_SESSION['ts_alpha_letter'] = $first_letter;
		}
 		return $html;
    }
    /* END */




    /** ----------------------------------------
    /**  Letter
    /** ----------------------------------------*/

    function replace_chars()
    {
        global $TMPL;
		
		$tagdata = $TMPL->tagdata;
		
		$tagdata = str_replace("&#40;", "(", $tagdata);
		$tagdata = str_replace("&#41;", ")", $tagdata);

 		return $tagdata;
    }
    /* END */


    
// ----------------------------------------
//  Plugin Usage
// ----------------------------------------

// This function describes how the plugin is used.
//  Make sure and use output buffering

function usage()
{
ob_start(); 
?>

To do.

<?php
$buffer = ob_get_contents();
	
ob_end_clean(); 

return $buffer;
}
/* END */


}
// END CLASS
?>