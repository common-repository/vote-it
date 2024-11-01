<?php
/*
Plugin Name: Vote It!
Plugin URI: http://www.zauberpage.de/vote-it-wordpress-plugin-english.html
Description:  Vote It! Buttons for Blogpostings. A collection of diverse Button-Services find you <a href="http://www.zauberpage.de/vote-it-liste.html">here</a>
Version: 0.3.2
Author: Maik Schindler
Author URI: http://www.zauberpage.de
*/
$installpath = get_option('siteurl').'/wp-content/plugins/vote-it';
add_action('plugins_loaded', 'msVoteItInstall');
add_action('admin_menu', 'msVoteItConfiguration');
add_filter('the_content', 'msVoteIt');

function msVoteItInstall()
{
	global $wpdb;
	$voteIt_service = $wpdb->get_var("SELECT meta_id FROM $wpdb->postmeta WHERE meta_key='_voteIt_service' LIMIT 1");
	if(!$voteIt_service){
$ms_voteIt_service = 'Digg:@ms@:<script type="text/javascript"><!--
	digg_url = \'%url%\';
	digg_skin = \'normal\';
	digg_window = \'self\';
//-->
</script>
<script src="http://digg.com/tools/diggthis.js" type="text/javascript"></script>';
		$wpdb->query("INSERT INTO $wpdb->postmeta SET meta_key = '_voteIt_service', meta_value = '".mysql_real_escape_string(stripslashes($ms_voteIt_service))."'");
	}
	
	$voteIt_float = $wpdb->get_var("SELECT meta_value FROM $wpdb->postmeta WHERE meta_key='_voteIt_float' LIMIT 1");
	if(!$voteIt_float)
		$wpdb->get_results("INSERT INTO $wpdb->postmeta SET meta_key = '_voteIt_float', meta_value = 2");
		
	$voteIt_padding = $wpdb->get_var("SELECT meta_value FROM $wpdb->postmeta WHERE meta_key='_voteIt_padding' LIMIT 1");
	if(!$voteIt_padding)
		$wpdb->get_results("INSERT INTO $wpdb->postmeta SET meta_key = '_voteIt_padding', meta_value = '3px 3px 3px 3px'");
		
	$voteIt_rows = $wpdb->get_var("SELECT meta_value FROM $wpdb->postmeta WHERE meta_key='_voteIt_rows' LIMIT 1");
	if(!$voteIt_rows)
		$wpdb->get_results("INSERT INTO $wpdb->postmeta SET meta_key = '_voteIt_backlink', meta_value = 2");
		
	$voteIt_back = $wpdb->get_var("SELECT meta_value FROM $wpdb->postmeta WHERE meta_key='_voteIt_backlink' LIMIT 1");
	if($voteIt_back == "")
		$wpdb->get_results("INSERT INTO $wpdb->postmeta SET meta_key = '_voteIt_backlink', meta_value = 1");
		
}

function msVoteItConfiguration()
{
	global $installpath;
	if (function_exists('current_user_can')) {
		if (!current_user_can('manage_options')) return;
	} else {
		global $user_level;
		get_currentuserinfo();
		if ($user_level < 8) return;
	}
	if (function_exists('add_options_page')) {
		$installpath = get_option('siteurl').'/wp-content/plugins/vote-it';
		add_action('admin_head', 'msVoteItHeader');
		add_options_page(__('Vote It!'), __('Vote It!'), 1, __FILE__, 'msVoteItManagePage');	
		add_action('edit_form_advanced', 'msVoteItEditFormAdvanced');
		add_action('save_post', 'msVoteItToDB');
		
	}
	
}

function msVoteItHeader()
{
	global $installpath;
	echo '<script type="text/javascript" src="'.$installpath.'/js.js"></script>';
}

function msVoteItManagePage() {
	global $wpdb;
	
	
	if($_REQUEST['submit'] && $_REQUEST['page'] == 'vote-it/vote-it.php'){
		//print_r($_REQUEST);
		/* _voteIt_service */
		$wpdb->get_results("DELETE FROM $wpdb->postmeta WHERE meta_key='_voteIt_service'");
		for($msi = 0; $msi < sizeof($_REQUEST['voteit-name']); $msi++){
			if($_REQUEST['voteit-name'][$msi] != '' && $_REQUEST['voteit-code'][$msi] != ''){
				$insert_system = $_REQUEST['voteit-name'][$msi].':@ms@:'.$_REQUEST['voteit-code'][$msi];
				$wpdb->query("INSERT INTO $wpdb->postmeta SET meta_key = '_voteIt_service', meta_value = '".mysql_real_escape_string(stripslashes($insert_system))."'");
			}
		}
		
		/* _voteIt_float */
		$wpdb->get_results("DELETE FROM $wpdb->postmeta WHERE meta_key='_voteIt_float'");
		$wpdb->get_results("INSERT INTO $wpdb->postmeta SET meta_key = '_voteIt_float', meta_value = '".$_REQUEST['voteit-align']."'");
		
		/* _voteIt_padding */
		$wpdb->get_results("DELETE FROM $wpdb->postmeta WHERE meta_key='_voteIt_padding'");
		$insert_padding = $_REQUEST['voteit-paddding'][0].' '.$_REQUEST['voteit-paddding'][1].' '.$_REQUEST['voteit-paddding'][2].' '.$_REQUEST['voteit-paddding'][3];
		$wpdb->get_results("INSERT INTO $wpdb->postmeta SET meta_key = '_voteIt_padding', meta_value = '".$insert_padding."'");
		
		/* _voteIt_rows */
		$wpdb->get_results("DELETE FROM $wpdb->postmeta WHERE meta_key='_voteIt_rows'");
		$wpdb->get_results("INSERT INTO $wpdb->postmeta SET meta_key = '_voteIt_rows', meta_value = '".$_REQUEST['voteit-rows']."'");
		
		/* _voteIt_backlink */
		$wpdb->get_results("DELETE FROM $wpdb->postmeta WHERE meta_key='_voteIt_backlink'");
		$wpdb->get_results("INSERT INTO $wpdb->postmeta SET meta_key = '_voteIt_backlink', meta_value = ".($_REQUEST['voteit-backlink'] == 1 ? '1' : '0' ));
		
	}
	$string = '
	<div class="wrap">
		<h2>Vote It!</h2>
		<b>Here you find a Help and install guide <a href="http://www.zauberpage.de/vote-it-wordpress-plugin-english.html">[english]</a> | <a href="http://www.zauberpage.de/vote-it-de.html">[german]</a></b><br />
		The current supported variables are:<br />
		<span style="color:#999999">URL = %url%, Title = %title%</span><br />
		Let me know, when you need more variables
	</div>
	<form method="post" action="">	
		<div class="wrap">
			<h2>Service</h2>
			<table summary="Vote It" id="voteIt-services">';
	
			$vote_systems = $wpdb->get_results("SELECT meta_value FROM $wpdb->postmeta WHERE meta_key='_voteIt_service' ORDER BY meta_id");
			foreach ( $vote_systems as $vote_system ) {
				$ms_ex = explode(':@ms@:', $vote_system->meta_value, 2);
				$string .='
				<tr>
					<td style="vertical-align:top;">
						<b>Service-Name:</b><br />
						<input name="voteit-name[]" type="text" size="20" maxlength="20" value="'.$ms_ex[0].'" />
						<div class="submit">
							<input id="rem-'.$msi.'" type="submit" name="submit" value="Remove service" />
						</div>				
					</td>
					<td>
						<b>Service Code:</b><br />
						<textarea name="voteit-code[]" rows="5" cols="100">'.$ms_ex[1].'</textarea>
					</td>
				</tr>';
			}
			
			$string .='
			</table>
			<div class="submit" style="border:0; margin-bottom:25px;">
			<input id="addService" type="button" name="button" value="Add service" />
			</div>
		</div>
		<div class="wrap">
			<h2>Design</h2>
			<table summary="Vote It">';
	
			$voteIt_float = $wpdb->get_var("SELECT meta_value FROM $wpdb->postmeta WHERE meta_key='_voteIt_float' LIMIT 1");
			$string .='
				<tr>
					<td style="vertical-align:top;">
						<b>Alignment:</b>					
					</td>
					<td>
						<input type="radio" name="voteit-align" value="1" '.($voteIt_float == 1 ? 'checked="checked"' : '').' /> Top left<br />
						<input type="radio" name="voteit-align" value="2" '.($voteIt_float == 2 ? 'checked="checked"' : '').' /> Top right<br />
						<input type="radio" name="voteit-align" value="3" '.($voteIt_float == 3 ? 'checked="checked"' : '').' /> Bottom left<br />
						<input type="radio" name="voteit-align" value="4" '.($voteIt_float == 4 ? 'checked="checked"' : '').' /> Bottom right<br />
						<input type="radio" name="voteit-align" value="5" '.($voteIt_float == 5 ? 'checked="checked"' : '').' /> set manual (set in your single template<b> &lt;? echo msVoteIt(); ?&gt;</b>)<br />
					</td>
				</tr>
				<tr>
					<td colspan="2">&nbsp;</td>
				</tr>';
			
			$voteIt_padding = $wpdb->get_var("SELECT meta_value FROM $wpdb->postmeta WHERE meta_key='_voteIt_padding' LIMIT 1");
			$voteIt_padding = explode(' ', $voteIt_padding);
			$string .='
				<tr>
					<td style="vertical-align:top;">
						<b>Padding:</b>					
					</td>
					<td>
						<input name="voteit-paddding[]" type="text" size="3" maxlength="4" value="'.$voteIt_padding[0].'" /> Top<br />
						<input name="voteit-paddding[]" type="text" size="3" maxlength="4" value="'.$voteIt_padding[1].'" /> Right<br />
						<input name="voteit-paddding[]" type="text" size="3" maxlength="4" value="'.$voteIt_padding[2].'" /> Bottom<br />
						<input name="voteit-paddding[]" type="text" size="3" maxlength="4" value="'.$voteIt_padding[3].'" /> Left<br />
					</td>
				</tr>
				<tr>
					<td colspan="2">&nbsp;</td>
				</tr>';
			
			$voteIt_rows = $wpdb->get_var("SELECT meta_value FROM $wpdb->postmeta WHERE meta_key='_voteIt_rows' LIMIT 1");
			$string .='
				<tr>
					<td style="vertical-align:top;">
						<b>Vote It!:</b>	
					</td>
					<td>
						<input name="voteit-rows" type="text" size="1" maxlength="2" value="'.$voteIt_rows.'" /> The number of Vote iT! Buttonons displayed in one row (Default: 2)
					</td>
				</tr>
				<tr>
					<td colspan="2">&nbsp;</td>
				</tr>';
			
			$voteIt_back = $wpdb->get_var("SELECT meta_value FROM $wpdb->postmeta WHERE meta_key='_voteIt_backlink' LIMIT 1");
			$string .='
				<tr>
					<td>Backlink:</td>
					<td><input type="checkbox" name="voteit-backlink" value="1" '.($voteIt_back == 1 ? 'checked="checked"' : '' ).' /> set a backlink to plugin page</td>
				<tr>
					<td colspan="2">&nbsp;</td>
				</tr>

				<tr>
			</table>
		</div>
		<div class="wrap submit">
			<input type="submit" name="submit" value="Update Vote It!" />
			<input type="submit" name="reset" value="Reset" />
		</div>
	</form>
';
	echo $string;
}

function msVoteIt($posting = false)
{
	global $wpdb, $post, $installpath;
	
	/* checked of single page */
	if(!is_single()) return $posting;
	$vote_float = $wpdb->get_var("SELECT meta_value FROM $wpdb->postmeta WHERE meta_key='_voteIt_float' LIMIT 1");
	if($vote_float == 5 && $posting) return $posting;
	
	$check = $wpdb->get_var("SELECT meta_value FROM $wpdb->postmeta WHERE meta_key='_voteIt_showPost' AND post_id = ".$post->ID." LIMIT 1");
	if($check == "") return $posting; 
	$ms_ex = explode(':@ms@:', $check); 
	foreach($ms_ex AS $key){
		if($sql_string != '') $sql_string .= ' OR ';
		$sql_string .= ' meta_value LIKE "'.$key.'%"';
	}
	//echo "SELECT meta_value FROM $wpdb->postmeta WHERE meta_key='_voteIt_service' AND (".$sql_string.") ORDER BY meta_id";
	$vote_systems = $wpdb->get_results("SELECT meta_value FROM $wpdb->postmeta WHERE meta_key='_voteIt_service' AND (".$sql_string.") ORDER BY meta_id");
	foreach ( $vote_systems as $vote_system ) {
		$ms_ex = explode(':@ms@:', $vote_system->meta_value, 2);
		$ms_voteIt = str_replace('%url%', get_permalink(), $ms_ex[1]);
		$ms_voteIt = str_replace('%title%', $post->post_title, $ms_voteIt);
		$ms_system[] = $ms_voteIt;
	}
	
	$vote_back = $wpdb->get_var("SELECT meta_value FROM $wpdb->postmeta WHERE meta_key='_voteIt_backlink' LIMIT 1");
	$backlink = '<a href="http://www.zauberpage.de/vote-it-wordpress-plugin-english.html" title="Vote It!"><img src="'.$installpath.'/vote-it.gif" /></a>';
	
	$msk = 1;
	$vote_rows = $wpdb->get_var("SELECT meta_value FROM $wpdb->postmeta WHERE meta_key='_voteIt_rows' LIMIT 1");
	for($msi = 0; $msi < sizeof($ms_system); $msi++){
		
		$string .= '<td>'.$ms_system[$msi].'</td>';
		if (($msk % $vote_rows ==  0) && (($msi+1) < sizeof($ms_system))){
			if($vote_back == 1 && $backlink){
				$string .= '<td>'.$backlink.'</td>';
				$backlink = false;
			}elseif($vote_back == 1 && !$backlink){
				$string .= '<td>&nbsp;</td>';
			}
			
			$string .= '</tr>';
			$string .= '<tr>';
		}
		$msk++;
	}
	$rest = ($msk-1) % $vote_rows;
	for($msi = 1; $msi <= $rest; $msi++){
		if($vote_back == 1 && $backlink && $msi == $rest){
			$string .= '<td><img src="'.$backlink.'</td>';
			$backlink = false;
		}else{
			$string .= '<td>&nbsp;</td>';
		}
	}
	if($vote_back == 1 && $backlink){
		$string .= '<td>'.$backlink.'</td>';
	}
	
	$vote_float = $wpdb->get_var("SELECT meta_value FROM $wpdb->postmeta WHERE meta_key='_voteIt_float' LIMIT 1");
	$vote_padding = $wpdb->get_var("SELECT meta_value FROM $wpdb->postmeta WHERE meta_key='_voteIt_padding' LIMIT 1");
	if($vote_float == 1 || $vote_float == 2)
		$string = '<table id="voteIt" summary="Vote It!" style="float:'.($vote_float == 1 ? 'left' : 'right' ).'; padding:'.$vote_padding.';"><tr>'.$string.'</tr></table>'.$posting.'<div id="vote-It" style="clear:both;"></div>';
	elseif($vote_float == 3 || $vote_float == 4)
		$string = $posting.'<table id="voteIt" summary="Vote It!" style="float:'.($vote_float == 3 ? 'left' : 'right' ).'; padding:'.$vote_padding.';"><tr>'.$string.'</tr></table><div id="vote-It" style="clear:both;"></div>';
	elseif($vote_float == 5)
		$string = '<table id="voteIt" summary="Vote It!" style="padding:'.$vote_padding.';"><tr>'.$string.'</tr></table>';
	return $string;
}

function msVoteItEditFormAdvanced()
{
	global $wpdb, $post_ID;
	
	if($post_ID)
		$ms_checked = $wpdb->get_var("SELECT meta_value FROM $wpdb->postmeta WHERE meta_key='_voteIt_showPost' AND post_id = ".$post_ID." LIMIT 1");
	
	$vote_systems = $wpdb->get_results("SELECT meta_value FROM $wpdb->postmeta WHERE meta_key='_voteIt_service' ORDER BY meta_id");
	foreach ( $vote_systems as $vote_system ) {
		$ms_ex = explode(':@ms@:', $vote_system->meta_value, 2);
		$ms_system .= '<div style="padding:0 0 10px 0;"><input type="checkbox" name="ms_voteIt[]" value="'.$ms_ex[0].'" '.(strpos($ms_checked, $ms_ex[0]) !== false ? 'checked="checked"' : '' ).' /> '.$ms_ex[0].'</div>';
	}
	$string = '<div id="voteIt" class="postbox">
			<h3>Vote It!</h3>
			<div class="inside">'.$ms_system.'</div>
		</div>';
	
	echo $string;
}

function msVoteItToDB()
{
	global $wpdb, $post_ID;
	
	if($_REQUEST['action'] == 'autosave') return ;
	
	if($post_ID)
		$wpdb->query("DELETE FROM $wpdb->postmeta WHERE post_id = ".$post_ID." AND meta_key='_voteIt_showPost'");
		
	if($_REQUEST['ms_voteIt']){
		foreach($_REQUEST['ms_voteIt'] AS $key){
			if($vote_it_to_db != '') $vote_it_to_db .= ':@ms@:';
			$vote_it_to_db .= $key;
		}
		$wpdb->query("INSERT INTO $wpdb->postmeta SET post_id = ".$post_ID.", meta_key = '_voteIt_showPost', meta_value = '".$vote_it_to_db."'");
	}
}

?>