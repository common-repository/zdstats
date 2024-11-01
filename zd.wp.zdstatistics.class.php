<?php
/*
Plugin Name: ZdStatistics
Plugin URI: http://www.zen-dreams.com/en/zdstats
Description: ZdStatistics is a statistics plugin with spam filter included
Version: 2.0.1
Author: Anthony PETITBOIS
Author URI: http://www.zen-dreams.com/

Copyright 2008  Anthony PETITBOIS  (email : anthony@zen-dreams.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

class ZdStatisticsV2 {
	var $PLUGINDIR;
	var $Entries;
	var $UserLevelExcluded, $SeparateFeeds, $AutomaticUpdate, $SpamDNS, $UserAgentList;
	var $SaveBots, $BlackList, $BrowserList, $OsList, $OptionTabs,$GoogleMaps_API;
	var $CurrentPage;
	var $ContentDirectory;

	function ZdStatisticsV2() {
		global $wpdb;
		load_plugin_textdomain('ZdStatsV2',PLUGINDIR . '/' . dirname(plugin_basename (__FILE__)) . '/lang');
		
		// Define actions (admin menu, shutdown of WP, Initialisation of WP
		add_action('admin_menu',array(& $this, 'add_menu'));
		add_action('admin_head',array (& $this, 'DisplayStyle'));
		add_action('shutdown',array(& $this, 'RecordVisit'));
		add_action('init',array(& $this, 'StartSession'));
		add_action('wp_head', array (& $this, "HTMLHead"));
		
		$this->Entries=$wpdb->prefix."zd_stats_entry";
		if ( !defined('WP_CONTENT_URL') ) define( 'WP_CONTENT_URL', get_option('siteurl') . '/wp-content');
		$lngt=strlen(get_option('siteurl'));
		$this->PLUGINDIR=WP_CONTENT_URL;
		$this->PLUGINDIR=substr($this->PLUGINDIR,$lngt);
		$this->CurrentPage=$_SERVER['PHP_SELF']."?page=".$_GET['page'];
		$this->UserLevelExcluded=get_option('zd_stats_excluded_level');
		$this->SeparateFeeds=get_option('zd_stats_showfeeds');
		$this->AutomaticUpdate=get_option('zd_stats_autoupdate');
		$this->UserAgentList=get_option('zd_stats_useragents');
		$this->BlackList=get_option('zd_stats_blacklist');
		$this->SaveBots=get_option('zd_stats_recordbots');
		$this->SpamDNS=get_option('zd_stats_sbl');
		$this->OptionTabs=get_option('zd_stats_option_tabs');
		$this->GoogleMaps_API=get_option('zd_stats_gmaps_api');
		$this->GeoFile=get_option('zd_stats_geofile');
		
		$this->BrowserList=array (
			'msie'		=>	array ("Internet Explorer","/MSIE ([0-9,.]+);/i"),
			'IEMobile'	=>	array ("Internet Explorer Mobile","/IEMobile ([0-9\.]+)/i"),
			'firefox'	=>	array ("Firefox","/Firefox\/([0-9,.]+)/i"),
			'lackBerry'	=>	array ("BlackBerry","/BlackBerry([0-9,.]+)\//i"),
			"Safari"	=>	array ("Safari","/Safari\/([0-9,.]+)/i"),
			"Opera"	=>	array ("Opera","/Opera\/([0-9,.]+)/i"),
			"Netscape"	=>	array ("Netscape","/Netscape\/([0-9,.]+)/i"),
			"Konqueror"	=>	array	("Konqueror","/Konqueror\/([0-9,.]+)/i"),
			"Links"	=>	array ("Links Browser",""),
			"Lynx"	=>	array ("Lynx Browser",""),
			"Paperblog" =>	array ("Via Paperblog.fr","/Paperblog\/([0-9\.])/i"),
			"Chrome"	=>	array ("Google Chrome","/Chrome\/([0-9,.]+)/i"),
			"http://www.netvibes.com/"  =>	array("Via Netvibes","")
		);
		$this->OsList=array (
			'Windows'		=>	array("Windows","/Windows ([0-9a-zA-Z .]*)/i"),
			'linux'			=>	array("Linux","/Linux ([0-9a-zA-Z .]*)/i"),
			'Macintosh'		=>	array("Macintosh",""),
			'Mac_PowerPC'	=>	array("Macintosh",""),
			"Mac OS X"		=>	array("Mac OS X",""),
			"FreeBSD"		=>	array("FreeBSD",""),
			"SunOS"		=>	array("Solaris","/SunOS ([0-9a-zA-Z .]*)/i"),
			"OpenBSD"		=>	array("OpenBSD",""),
			"iPhone"		=>	array ("iPhone","/iPhone OS ([0-9\_]*)/i")
		);
		register_activation_hook(__FILE__,array(& $this, 'initialize'));
		
		$t=explode('/',__FILE__);
		if (count($t)==1) $t=explode("\\",__FILE__);		
		$this->ContentDirectory=get_bloginfo('url').$this->PLUGINDIR.'/plugins/'.$t[count($t)-2];
	}

	function initialize() {
		global $wpdb;
		$sql="CREATE TABLE ".$wpdb->prefix."zd_stats_entry (
		 EntryID int NOT NULL AUTO_INCREMENT,
		 SessionID varchar(32) NOT NULL,
		 IP varchar(15) NOT NULL,
		 Referer varchar(255),
		 UserAgent varchar(255),
		 DateTime DATETIME,
		 URL varchar(255),
		 SearchEngine tinyint,
		 SqlQueries INT,
		 LoadTime FLOAT,
		 primary key (EntryID));";
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
		// Creates database structure if not present	
		
		// Set default options if they do not exist
		if ($this->UserLevelExcluded=="") update_option('zd_stats_excluded_level',-1);
		if ($this->AutomaticUpdate=="") update_option('zd_stats_autoupdate',"on");		
		if ($this->SeparateFeeds=="") update_option('zd_stats_showfeeds',"on");
		if ($this->UserAgentList=="") update_option('zd_stats_useragents',
"Googlebot => Google Bot
msnbot => Msn Bot
Yahoo! Slurp => Yahoo!
YahooFeedSeeker => Yahoo Feeds
Ask Jeeves => Ask Jeeves
SnapPreviewBot => Snap.com
http://www.snap.com => Snap.com
http://www.twingly.com/ => Twingly
Technoratibot =>  Technorati
BlogPulse => BlogPulse
yetibot@naver.com => YetiBot
Mediapartners-Google => Google Adsense
W3C_Validator => W3C Validator
Balihoo => Balihoo
larbin_ => Larbin bot
BlogsNowBot => BlogsNow
Sphere Scout => Sphere.com
www.radian6.com/crawler => Radian6
ia_archiver => Internet Archive
LinkWalker => SEVENtwentyfour
psbot => Picsearch
Netvibes => Netvibes
Moreoverbot => MoreOver
Bloglines => Bloglines
Wget => wget
Technoratibot => Technorati
Filtrbox => Filtrbox
Exabot => Exalead
panscient.com => panscient.com
ScoutJet => Scoutjet
libwww-perl => LibPerl
http://www.proximic.com => Proximic.com
http://www.feedhub.com => Feedhub
Feedfetcher-Google => Google Reader
www.gigablast.com => Gigablast
discobot => discoveryengine.com
FeedBurner => Feedburner"
);
		if ($this->BlackList=="") update_option('zd_stats_blacklist',"127.*.*.*, 72.44.3[2-9].* , 72.44.[4-5][5-9].* , 72.44.6[0-3].* , 67.202.[0-5]*.* , 67.202.6[0-3].* , 68.180.13[8-9].* , 64.40.117.226 , 64.191.203.* , 64.13.251.89 , 64.41.145.* , 77.91.224.* , 209.200.22[4-9].* , 142.166.3.122 , 208.66.6[4-7].* , 193.189.143.170 , 38.*.*.* , 74.86.171.82 , 65.160.238.180 , 219.163.40.107 , 200.61.185.200 , 65.5[2-5].*");
		if ($this->SaveBots=="") update_option('zd_stats_recordbots',"off");
		if ($this->SpamDNS=="") update_option('zd_stats_sbl','sbl-xbl.spamhaus.org');
		if ($this->GeoFile=="") update_option('zd_stats_geofile',dirname(__FILE__).'/geoip');
	}

	function add_menu() {
		$optiontabs=explode(',',$this->OptionTabs);
		// Add main pages
		add_menu_page(__('Statistics','ZdStatsV2'),__('Statistics','ZdStatsV2'),7,'ZdStatsV2',array(& $this, "Overview"));
		add_submenu_page('ZdStatsV2',__('Overview','ZdStatsV2'),__('Overview','ZdStatsV2'),8,'ZdStatsV2',array(& $this, "Overview"));
		add_submenu_page('ZdStatsV2',__('Daily Stats','ZdStatsV2'),__('Daily Stats','ZdStatsV2'),8,'ZdStatsV2_Daily',array(& $this, "DailyStats"));
	
		// Add optional pages
		if (array_search("2",$optiontabs)!==false) add_submenu_page('ZdStatsV2',__('Referers','ZdStatsV2'),__('Referers','ZdStatsV2'),8,'ZdStatsV2_Referers',array(& $this, "Referers"));
		if (array_search("3",$optiontabs)!==false) add_submenu_page('ZdStatsV2',__('Keywords','ZdStatsV2'),__('Keywords','ZdStatsV2'),8,'ZdStatsV2_Keywords',array(& $this, "Keywords"));
		if (array_search("4",$optiontabs)!==false) add_submenu_page('ZdStatsV2',__('Geolocalisation','ZdStatsV2'),__('Geolocalisation','ZdStatsV2'),8,'ZdStatsV2_Geolocalisation',array(& $this, "Geolocalisation"));
		if (array_search("5",$optiontabs)!==false) add_submenu_page('ZdStatsV2',__('Technologies','ZdStatsV2'),__('Technologies','ZdStatsV2'),8,'ZdStatsV2_Technologies',array(& $this, "Technologies"));
		if (array_search("6",$optiontabs)!==false) add_submenu_page('ZdStatsV2',__('Top Ten','ZdStatsV2'),__('Top Ten','ZdStatsV2'),8,'ZdStatsV2_TopTen',array(& $this, "TopTen"));
		if (array_search("7",$optiontabs)!==false) add_submenu_page('ZdStatsV2',__('Performances','ZdStatsV2'),__('Performances','ZdStatsV2'),8,'ZdStatsV2_Performances',array(& $this, "Performances"));
		if (array_search("8",$optiontabs)!==false) add_submenu_page('ZdStatsV2',__('Search','ZdStatsV2'),__('Search','ZdStatsV2'),8,'ZdStatsV2_Search',array(& $this, "Search"));
		if (array_search("9",$optiontabs)!==false) add_submenu_page('ZdStatsV2',__('Outgoing Links','ZdStatsV2'),__('Outgoing Links','ZdStatsV2'),8,'ZdStatsV2_OutgoingLinks',array(& $this, "OutgoingLinks"));

		// Add Options
		add_submenu_page('ZdStatsV2',__('Options','ZdStatsV2'),__('Options','ZdStatsV2'),8,'ZdStatsV2_Options',array(& $this, "Options"));
	}
	
	function Options() {
		echo '<div class="wrap" id="zdstats"><h2>'.__('Options','ZdStatsV2').'</h2>';
		if (isset($_POST['reprocess'])) {
			echo '<br /><div id="message" class="updated fade"><p>';
			$this->ProcessStats();
			echo '</p></div>';
		}
		
		if (isset($_POST['excluded_level'])) {
			$this->OptionTabs=@implode(',',$_POST['option_tabs']);
			$this->AutomaticUpdate = ($_POST['autoupdate']=="on") ? "on" : "off";
			$this->SeparateFeeds = ($_POST['showfeeds']=="on") ? "on" : "off";
			$this->SaveBots = ($_POST['savebots']=="on") ? "on" : "off";
			$this->BlackList = $_POST['botips'];
			$this->UserAgentList = $_POST['useragent'];
			$this->UserLevelExcluded = $_POST['excluded_level'];
			$this->SpamDNS = $_POST['sbl'];
			$this->GoogleMaps_API=$_POST['gmaps_api'];
			$this->GeoFile=stripslashes($_POST['geofile']);

			update_option('zd_stats_excluded_level',$this->UserLevelExcluded);
			update_option('zd_stats_autoupdate',$this->AutomaticUpdate);
			update_option('zd_stats_showfeeds',$this->SeparateFeeds);
			update_option('zd_stats_useragents',$this->UserAgentList);
			update_option('zd_stats_blacklist',$this->BlackList);
			update_option('zd_stats_recordbots',$this->SaveBots);
			update_option('zd_stats_sbl',$this->SpamDNS);
			update_option('zd_stats_option_tabs',$this->OptionTabs);
			update_option('zd_stats_gmaps_api',$this->GoogleMaps_API);
			update_option('zd_stats_geofile',$this->GeoFile);
		
			echo '<br /><div id="message" class="updated fade"><p>'.__('Options updated','ZdStatsV2').'</p></div>';
		}
			echo "\n\t".'<form action="admin.php?page=ZdStatsV2_Options" method="post">';
			echo "\n\t".'<input type="hidden" name="'.$hidden_field.'" value="update" />';
			echo "\n\t".'<input type="hidden" name="fct" value="options" />';
			echo "\n\t".'<table class="form-table">';
			$UserLevel=$this->UserLevelExcluded;
			echo "\n\t\t".'<tr>';
				echo "\n\t\t\t".'<td><label for="excluded_level">'.__('Exclude users with level greater than','ZdStatsV2').'</label></td>';
				echo "\n\t\t\t".'<td><select id="excluded_level" name="excluded_level" />';
				echo '<option value="-1"'; if ($UserLevel=="-1") echo ' selected="selected"'; echo '>'.__('Do not filter on user level','ZdStatsV2').'</option>';
				echo '<option value="0"'; if ($UserLevel=="0") echo ' selected="selected"';echo '>'.__('Subscriber','ZdStatsV2').'</option>';
				echo '<option value="1"'; if ($UserLevel=="1") echo ' selected="selected"';echo '>'.__('Contributor','ZdStatsV2').'</option>';
				echo '<option value="2"'; if ($UserLevel=="2") echo ' selected="selected"';echo '>'.__('Author','ZdStatsV2').'</option>';
				echo '<option value="7"'; if ($UserLevel=="7") echo ' selected="selected"';echo '>'.__('Editor','ZdStatsV2').'</option>';
				echo '<option value="10"'; if ($UserLevel=="10") echo ' selected="selected"';echo '>'.__('Administrator','ZdStatsV2').'</option>';
			echo '</select></td>';
			echo "\n\t\t</tr>";

			$optiontabs=explode(',',$this->OptionTabs);
			echo "\n\t\t".'<tr>';
				echo "\n\t\t\t".'<td><label for="option_tabs">'.__('Optionnal Tabs to display','ZdStatsV2').'</label></td>';
				echo "\n\t\t\t".'<td><select id="option_tabs" name="option_tabs[]" multiple="true" style="height: 72px !important; width: 200px;"/>';
				echo "\n\t\t\t".'<option value="2"';if (array_search("2",$optiontabs)!==false) echo ' SELECTED'; echo '>'.__('Referers','ZdStatsV2').'</option>';
				echo "\n\t\t\t".'<option value="3"';if (array_search("3",$optiontabs)!==false) echo ' SELECTED'; echo '>'.__('Keywords','ZdStatsV2').'</option>';
				echo "\n\t\t\t".'<option value="4"';if (array_search("4",$optiontabs)!==false) echo ' SELECTED'; echo '>'.__('Geolocalisation','ZdStatsV2').'</option>';
				echo "\n\t\t\t".'<option value="5"';if (array_search("5",$optiontabs)!==false) echo ' SELECTED'; echo '>'.__('Technologies','ZdStatsV2').'</option>';
				echo "\n\t\t\t".'<option value="6"';if (array_search("6",$optiontabs)!==false) echo ' SELECTED'; echo '>'.__('Top ten','ZdStatsV2').'</option>';
				echo "\n\t\t\t".'<option value="7"';if (array_search("7",$optiontabs)!==false) echo ' SELECTED'; echo '>'.__('Performances','ZdStatsV2').'</option>';
				echo "\n\t\t\t".'<option value="8"';if (array_search("8",$optiontabs)!==false) echo ' SELECTED'; echo '>'.__('Search','ZdStatsV2').'</option>';
				echo "\n\t\t\t".'<option value="9"';if (array_search("9",$optiontabs)!==false) echo ' SELECTED'; echo '>'.__('Outgoing Links','ZdStatsV2').'</option>';
			echo '</select></td>';
			echo "\n\t\t</tr>";
			
			echo "\n\t\t".'<tr>';
				echo "\n\t\t\t".'<td><label for="autoupdate">'.__('Automatic Update and Analysis','ZdStatsV2').'</label></td>';
				$checked=($this->AutomaticUpdate=="on") ? 'checked="on"' : '';
				echo "\n\t\t\t".'<td><input type="checkbox" id="autoupdate" name="autoupdate" '.$checked.'/> ';
			echo __('Checking this box will enable auto analysis of incoming statistics, it may degrade performances but will make stats realtime','ZdStatsV2').'</td>';
			echo "\n\t\t</tr>";
			echo "\n\t\t".'<tr>';
				echo "\n\t\t\t".'<td><label for="showfeeds">'.__('Separate Feeds','ZdStatsV2').'</label></td>';
				$feedschecked=($this->SeparateFeeds=="on") ? 'checked="on"' : '';
				echo "\n\t\t\t".'<td><input type="checkbox" id="showfeeds" name="showfeeds" '.$feedschecked.'/> ';
			echo __('Checking this box will enable feed tracking separatly, therefore not showing feeds in standard pageviews','ZdStatsV2').'</td>';
			echo "\n\t\t</tr>";
			echo "\n\t\t".'<tr>';
				echo "\n\t\t\t".'<td><label for="savebots">'.__('Record bots','ZdStatsV2').'</label></td>';
				$savebots=($this->SaveBots=="on") ? 'checked="on"' : '';
				echo "\n\t\t\t".'<td><input type="checkbox" id="showfeeds" name="savebots" '.$savebots.'/> ';
			echo __('Checking this box will enable bot tracking, it will increase your database size a lot, be carefull using this option.','ZdStatsV2').'</td>';
			echo "\n\t\t</tr>";
			echo "\n\t\t".'<tr>';
				echo "\n\t\t\t".'<td><label for="botips">'.__('Your IP BlackList','ZdStatsV2').'</label></td>';
				echo "\n\t\t\t".'<td><textarea id="botips" name="botips" rows="4" cols="80"/>';
				echo stripslashes($this->BlackList);
			echo '</textarea>'."<br />".__('These are IP of known bots/spammers, IP can be described using regular expressions, such as 127.0.*.?<br />Syntax : IPs separated by commas.','ZdStatsV2').'</td>';
			echo "\n\t\t</tr>";
			echo "\n\t\t".'<tr>';
				echo "\n\t\t\t".'<td><label for="useragent">'.__('Bots UserAgents','ZdStatsV2').'</label></td>';
				echo "\n\t\t\t".'<td><textarea id="useragent" name="useragent" rows="4" cols="80"/>';
				echo stripslashes($this->UserAgentList);
			echo '</textarea>'."<br />".__('This describe useragent strings that are known from bots<br />Syntax : part_of_the_useragent_string => Bot_Description','ZdStatsV2').'</td>';
			echo "\n\t\t</tr>";
			echo "\n\t\t".'<tr>';
				echo "\n\t\t\t".'<td><label for="gmaps_api">'.__('Google Maps API Key','ZdStatsV2').'</label></td>';
				echo "\n\t\t\t".'<td><input type="text" id="gmaps_api" name="gmaps_api" value="'.$this->GoogleMaps_API.'" style="width: 594px;"/>';
			echo "<br />".__('This is used for Geolocalisation, you can get one here <a href="http://code.google.com/apis/maps/signup.html" target="_blank">Google Maps API Key</a>','ZdStatsV2').'</td>';
			echo "\n\t\t</tr>";
			echo "\n\t\t".'<tr>';
				echo "\n\t\t\t".'<td><label for="sbl">'.__('Spam Blocklist Server','ZdStatsV2').'</label></td>';
				echo "\n\t\t\t".'<td><input type="text" id="sbl" name="sbl" value="'.$this->SpamDNS.'" style="width: 594px;"/>';
			echo "<br />".__('This is used for spam detection','ZdStatsV2').'</td>';
			echo "\n\t\t</tr>";
			echo "\n\t\t".'<tr>';
				echo "\n\t\t\t".'<td><label for="geofile">'.__('GeoLiteCity.dat localisation on server','ZdStatsV2').'</label></td>';
				echo "\n\t\t\t".'<td><input type="text" id="geofile" name="geofile" value="'.$this->GeoFile.'" style="width: 594px;"/></td>';
			echo "\n\t\t</tr>";			
			echo "\n\t".'</table>';
			echo "\n\t".'<p class="submit"><input class="button" type="submit" value="'.__('Update options','ZdStatsV2').'" name="submit"/></p>';
			echo "\n".'</form>';

			echo "\n\t".'<form action="admin.php?page=ZdStatsV2_Options" method="post">';
			echo "\n\t".'<input type="hidden" name="reprocess" value="update" />';
			echo "\n\t".'<input type="hidden" name="fct" value="options" />';
			echo "\n\t".'<p class="submit"><input class="button" type="submit" value="'.__('Re-process statistics','ZdStatsV2').'" name="submit"/></p>';
			echo "\n</form>";
		echo '</div>';
	}
	
	function Overview() {
		global $wpdb, $user_level;
		
		if (isset($_GET['detail'])) {
			switch ($_GET['detail']) {
				case 'IP':
					$this->ShowIPDetail();
					break;
				case 'Session':
					$this->ShowSessionDetail();
					break;
				case 'Referer':
					$this->ShowRefererDetail();
					break;
				case 'Keyword':
					$this->ShowKeywordDetail();
					break;
				case 'Hour':
					$this->ShowTimeDetail();
					break;
				case 'Visit':
					$this->ShowVisitDetail();
					break;
				case 'Page':
					$this->ShowPageViews();
					break;
				case 'export':
					$this->ExportCSV();
					break;
			}
			return;
		}
		
		echo '<div class="wrap" id="zdstats">';
		echo '<h2>'.__('Statistics','ZdStatsV2').'</h2>';
		if ($_GET['debug']) {
			echo '<small><a href="admin.php?page=ZdStatsV2">Debug off</a></small>';
			echo "<br /><small style=\"color: #FF3355; font-weight: bold;\">Copy/paste this and send me an email at anthony[@]zen-dreams[dot]com for error reporting</small><br />";
			echo '<pre style="width: 100%; overflow: auto; background: #000; color: #FFF;">';
			print_r($this);
			echo "\nGlobal Variables {";
			echo "\n\tUserLevel : $user_level";
			echo "\n\tKnown Spammer : 207.46.120.166 => ".(($this->IsSpammer("207.46.120.166")==true) ? "Spammer" : "Not spammer");
			echo "\n\tNot Spammer : IP of zen-dreams.com => ".(($this->IsSpammer("212.227.30.42")==true) ? "Spammer" : "Not spammer");
			echo "\n}</pre>";
		} else {
			echo '<small><a href="admin.php?page=ZdStatsV2&amp;debug=on">Debug on</a></small>';
			// Default date span for Overview is Current Week.
			
			$Dates=$this->DisplayPeriodSelector(2);
			$FirstDay=$Dates->FirstDay;
			$LastDay=$Dates->LastDay;
			$query="SELECT SessionID, DateTime FROM ".$this->Entries." WHERE `DateTime`>'$FirstDay' and `DateTime`<'$LastDay' and SearchEngine=0 order by DateTime, SessionID";
			$stats=$wpdb->get_results($query);
			if ($stats) {
				$CurrentSession="";
				foreach ($stats as $key => $row) {
					if ($CurrentSession!=$row->SessionID) {
						$CurrentSession=$row->SessionID;
						$DailyStat[substr($row->DateTime,0,10)]->Visitors++;
					}
					$DailyStat[substr($row->DateTime,0,10)]->Count++;
				}
				
				$GraphDatas->Legends=array (
					__('Day','ZdStatsV2') => 'string',
					__('Visitors','ZdStatsV2') => 'number',
					__('Pages viewed','ZdStatsV2') => 'number'
				);
				$GraphDatas->Title=sprintf(__('General Overview from %s to %s','ZdStatsV2'),substr($FirstDay,0,10),substr($LastDay,0,10));
				foreach ($DailyStat as $Day => $row) {
					$GraphDatas->Dataset[$Day]=array ($row->Visitors, $row->Count);
				}
				
				$this->DrawAreaChart($GraphDatas);

				echo '<table class="widefat">';
				echo '<tr><th>'.__('Date &amp; Time','ZdStatsV2').'</th><th>'.__('Visitors','ZdStatsV2').'</th><th>'.__('Pages viewed','ZdStatsV2').'</th></tr>';
				$Count['Lines']=0;
				$Count['Total']=0;
				krsort($DailyStat);
				foreach ($DailyStat as $Day => $row) {
					echo '<tr>';
					echo '<td><a href="?page=ZdStatsV2_Daily&amp;P='.$Day.'">'.$Day.'</a></td>';
					echo '<td>'.$row->Visitors.'</td>';
					echo '<td>'.$row->Count.'</td>';
					echo '</tr>';
					$Count['Lines']+=$row->Visitors;
					$Count['Total']+=$row->Count;
				}
				echo '<tr class="footer"><td>'.__('Total','ZdStatsV2').'</td><td>'.$Count['Lines'].'</td><td>'.$Count['Total'].'</td></tr>';
				echo '</table>';
				echo '<small><a href="?page=ZdStatsV2&amp;detail=export&amp;q='.base64_encode($query).'">'.__('Export as CSV','ZdStatsV2').'</a></small>';
			} else {
				echo '<h3>'.__('Nothing found for this period','ZdStatsV2').'</h3>';
			}
		}
		echo '</div>';
	}
	
	function DailyStats() {
		global $wpdb;
		echo '<div class="wrap" id="zdstats">';
		echo '<h2>'.__('Daily Stats','ZdStatsV2').'</h2>';
		
		if (isset($_GET['P'])) $_POST['P']=$_GET['P'];
		
		if (!isset($_POST['P'])) $_POST['P']=strftime("%Y-%m-%d",time());
		$_POST['S']=strftime("%Y-%m-%d",strtotime($_POST['P']." -1 day"));
		$Dates=$this->DisplayPeriodSelector(1);
		
		$FirstDay=substr($Dates->LastDay,0,10);

		$query="SELECT SessionID, DateTime FROM ".$this->Entries." WHERE `DateTime` like '$FirstDay%' and SearchEngine=0 order by DateTime, SessionID";
		$stats=$wpdb->get_results($query);
		if ($stats) {
			$CurrentSession="";
			foreach ($stats as $key => $row) {
				if ($CurrentSession!=$row->SessionID) {
					$CurrentSession=$row->SessionID;
					$DailyStat[substr($row->DateTime,11,2).'h']->Visitors++;
				}
				$DailyStat[substr($row->DateTime,11,2).'h']->Count++;
				$DailyStat[substr($row->DateTime,11,2).'h']->DateTime=substr($row->DateTime,0,13);
			}
			
			$GraphDatas->Legends=array (
				__('Hour','ZdStatsV2') => 'string',
				__('Visitors','ZdStatsV2') => 'number',
				__('Pages viewed','ZdStatsV2') => 'number'
			);
			$GraphDatas->Title=__('Daily Stats for ','ZdStatsV2').strftime("%A %d %B %Y",strtotime($FirstDay));
			foreach ($DailyStat as $Day => $row) {
				$GraphDatas->Dataset[$Day]=array ($row->Visitors, $row->Count);
			}
			
			$this->DrawAreaChart($GraphDatas);

			echo '<table class="widefat">';
			echo '<tr><th>'.__('Date &amp; Time','ZdStatsV2').'</th><th>'.__('Visitors','ZdStatsV2').'</th><th>'.__('Pages viewed','ZdStatsV2').'</th></tr>';
			$Count['Visitors']=0;
			$Count['Pageviews']=0;
			foreach ($DailyStat as $Day => $row) {
				echo '<tr>';
				echo '<td><a href="?page=ZdStatsV2&amp;detail=Hour&amp;time='.$row->DateTime.'">'.$Day.'</a></td>';
				echo '<td>'.$row->Visitors.'</td>';
				echo '<td>'.$row->Count.'</td>';
				echo '</tr>';
				$Count['Visitors']+=$row->Visitors;
				$Count['Pageviews']+=$row->Count;
			}
			echo '<tr class="footer"><td>'.__('Total','ZdStatsV2').'</td><td>'.$Count['Visitors'].'</td><td>'.$Count['Pageviews'].'</td></tr>';
			echo '</table>';
			
			echo '<h3>'.__('Pages viewed','ZdStatsV2').'</h3>';
			$query="SELECT DateTime, URL, Count(URL) as curl FROM ".$this->Entries." WHERE `DateTime` like '$FirstDay%' and SearchEngine=0 group by URL order by curl desc";
			$stats=$wpdb->get_results($query);
			echo '<table class="widefat">';
			echo '<tr><th>'.__('Pages viewed','ZdStatsV2').'</th><th>'.__('Visitors','ZdStatsV2').'</th></tr>';
			$Count['Visitors']=0;
			$Count['Pageviews']=0;
			foreach ($stats as $Day => $row) {
				echo '<tr>';
				echo '<td><a href="?page=ZdStatsV2&amp;detail=Page&amp;URL='.base64_encode($row->URL).'&amp;S='.$FirstDay.'&amp;P='.$FirstDay.'">'.$row->URL.'</a></td>';
				echo '<td>'.$row->curl.'</td>';
				echo '</tr>';
				$Count['Pageviews']+=$row->curl;
			}
			echo '<tr class="footer"><td>'.__('Total','ZdStatsV2').'</td><td>'.$Count['Pageviews'].'</td></tr>';
			echo '</table>';
			
			echo '<small><a href="?page=ZdStatsV2&amp;detail=export&amp;q='.base64_encode($query).'">'.__('Export as CSV','ZdStatsV2').'</a></small>';
		} else {
			echo '<h3>'.__('Nothing found for this period','ZdStatsV2').'</h3>';
		}
		echo '</div>';
	}
	
	function ShowTimeDetail() {
		$Keyword=$_GET['time'];
		global $wpdb;
		echo '<div class="wrap" id="zdstats">';
		echo '<h2>';
		printf(__('Details for (%s)','ZdStatsV2'), $Keyword);
		echo '</h2>';
		
		$query="SELECT EntryID, Referer, IP, URL, DateTime FROM ".$this->Entries." WHERE DateTime like '$Keyword%' and SearchEngine=0 order by DateTime";
		$res=$wpdb->get_results($query);
		if ($res) {
			echo '<table class="widefat">';
			echo '<tr><th>'.__('Date &amp; Time','ZdStatsV2').'</th><th>'.__('Referer','ZdStatsV2').'</th><th>'.__('Page viewed','ZdStatsV2').'</th><th>'.__('IP','ZdStatsV2').'</th></tr>';
			$Count=0;
			foreach ($res as $index => $Value) {
				echo '<tr>';
				echo '<td><a href="?page=ZdStatsV2&amp;detail=Visit&id='.$Value->EntryID.'">'.strftime("%Y-%m-%d &mdash; %H:%M",strtotime($Value->DateTime)).'</a></td>';
				echo '<td><a href="'.$Value->Referer.'" target="_blank">'.$this->GetSiteName($Value->Referer).'</a></td>';
				echo '<td>'.$Value->URL.'</td>';
				echo '<td><a href="?page=ZdStatsV2&amp;detail=IP&amp;IP='.base64_encode($Value->IP).'">'.$Value->IP.'</a></td>';
				echo '</tr>';
				$Count++;
			}
			echo '<tr class="footer"><td colspan="3">'.__('Total','ZdStatsV2').'</td><td style="text-align: right;">'.$Count.'</td></tr>';
			echo '</table>';
			echo '<small><a href="?page=ZdStatsV2&amp;detail=export&amp;q='.base64_encode($query).'">'.__('Export as CSV','ZdStatsV2').'</a></small>';
		}
		echo '</div>';
	}
	
	function ShowSessionDetail() {
		global $wpdb;
		$Session=$_GET['sid'];
		echo '<div class="wrap" id="zdstats">';
		echo '<h2>';
		echo __('Navigation summary','ZdStatsV2');
		echo '</h2>';
		
		$query="SELECT DateTime, UserAgent, EntryID, Referer, URL, IP
		FROM ".$this->Entries."
		WHERE SessionID='".$Session."' AND SearchEngine in (0,3)
		ORDER BY DateTime";
		$res=$wpdb->get_results($query);
		if ($res) {
		if (file_exists($this->GeoFile.'/GeoLiteCity.dat')) $GeoIP="ok";
			else $GeoIP="ko";
			if ($GeoIP=="ok") {
				include_once ('../'.PLUGINDIR . '/' . dirname(plugin_basename (__FILE__)) .'/geoip/geoipcity.inc');
				include_once ('../'.PLUGINDIR . '/' . dirname(plugin_basename (__FILE__)) .'/geoip/geoipregionvars.php');
				$gi = geoip_open($this->GeoFile.'/GeoLiteCity.dat',GEOIP_STANDARD);
				$record = geoip_record_by_addr($gi,$res[0]->IP);
				$Loc=($record->country_name!="") ? utf8_encode($record->country_name) : "Unknown";
				$Loc.=", ";
				$Loc.=$CityName=($record->city!="") ? utf8_encode($record->city) : "Unknown";
			} else $Loc="Unknown";
		
			echo '<table class="details">';
			echo '<tr><th colspan="3">'.__('General information','ZdStatsV2').'</th></tr>';
			echo '<tr><td class="first">'.__('Date &amp; Time','ZdStatsV2').'</td><td>'.$res[0]->DateTime.'</td><td><a href="'.$this->CurrentPage.'&amp;detail=IP&amp;IP='.base64_encode($res[0]->IP).'&amp;S='.$FirstDay.'&amp;P='.$LastDay.'&amp;option=delete">'.__('Delete all pageviews for this IP/period','ZdStatsV2').'</a></td></tr>';
			echo '<tr><td class="first">'.__('IP','ZdStatsV2').'</td><td colspan="2"><a href="'.$this->CurrentPage.'&amp;detail=IP&amp;IP='.base64_encode($res[0]->IP).'&amp;S='.$FirstDay.'&amp;P='.$LastDay.'">'.$res[0]->IP.'</a></td></tr>';
			echo '<tr><td class="first">'.__('Hostname','ZdStatsV2').'</td><td colspan="2">'.gethostbyaddr($res[0]->IP).'</td></tr>';
			echo '<tr><td class="first">'.__('Geolocalisation','ZdStatsV2').'</td><td colspan="2"><a href="http://maps.google.com/maps?f=q&hl=en&geocode=&z=6&q='.$Loc.'" target="_blank">'.$Loc.'</a></td></tr>';
			echo '<tr><td class="first">'.__('Browser','ZdStatsV2').'</td><td colspan="2">'.$this->FetchBrowser($res[0]->UserAgent).'</td></tr>';
			echo '<tr><td class="first">'.__('Operating System','ZdStatsV2').'</td><td colspan="2">'.$this->FetchOS($res[0]->UserAgent).'</td></tr>';
			echo '<tr><td class="first">'.__('User agent','ZdStatsV2').'</td><td colspan="2">'.$res[0]->UserAgent.'</td></tr>';
			echo '<tr><td colspan="3" class="first"><center><a href="edit-comments.php?s='.$res[0]->IP.'&mode=detail&comment_status=">'.__('View all comments written from this IP','ZdStatsV2').'</a></center></td></tr>';			
			echo '</table>';
			echo '<p>&nbsp;</p>';
			echo '<table class="widefat">';
			echo '<tr><th>'.__('Date &amp; Time','ZdStatsV2').'</th><th>'.__('Referer','ZdStatsV2').'</th><th>'.__('Page viewed','ZdStatsV2').'</th></tr>';
			$Count=0;
			foreach ($res as $index => $Value) {
				echo '<tr>';
				echo '<td><a href="?page=ZdStatsV2&amp;detail=Visit&id='.$Value->EntryID.'">'.strftime("%Y-%m-%d &mdash; %H:%M",strtotime($Value->DateTime)).'</a></td>';
				echo '<td><a href="'.$Value->Referer.'">'.$this->GetSiteName($Value->Referer).'</a></td>';
				echo '<td>'.$Value->URL.'</td>';
				echo '</tr>';
				$Count++;
			}
			echo '<tr class="footer"><td colspan="2">'.__('Total','ZdStatsV2').'</td><td style="text-align: right;">'.$Count.'</td></tr>';
			echo '</table>';
			echo '<small><a href="?page=ZdStatsV2&amp;detail=export&amp;q='.base64_encode($query).'">'.__('Export as CSV','ZdStatsV2').'</a></small>';
		} else {
			echo '<h3>'.__('Nothing found for this period','ZdStatsV2').'</h3>';
		}
		echo '</div>';	
	}
	
	function ShowVisitDetail() {
		$Key=$_GET['id'];
		global $wpdb;
		echo '<div class="wrap" id="zdstats">';
		echo '<h2>'.__('Details for visit','ZdStatsV2').'</h2>';
		$query="SELECT * from ".$this->Entries." WHERE EntryID=$Key";
		$result=$wpdb->get_row($query);
		
		
		if (file_exists($this->GeoFile.'/GeoLiteCity.dat')) $GeoIP="ok";
		else $GeoIP="ko";
		if ($GeoIP=="ok") {
			include_once ('../'.PLUGINDIR . '/' . dirname(plugin_basename (__FILE__)) .'/geoip/geoipcity.inc');
			include_once ('../'.PLUGINDIR . '/' . dirname(plugin_basename (__FILE__)) .'/geoip/geoipregionvars.php');

			$gi = geoip_open($this->GeoFile.'/GeoLiteCity.dat',GEOIP_STANDARD);
			$record = geoip_record_by_addr($gi,$result->IP);
			$Loc=($record->country_name!="") ? utf8_encode($record->country_name) : "Unknown";
			$Loc.=", ";
			$Loc.=$CityName=($record->city!="") ? utf8_encode($record->city) : "Unknown";
		} else $Loc="Unknown";
		echo '<p><a href="?page=ZdStatsV2&amp;detail=Session&amp;sid='.$result->SessionID.'">'.__('Show navigation summary','ZdStatsV2').'</a></p>';
		echo '<table class="details">';
		echo '<tr><td class="first">'.__('Date &amp; Time','ZdStatsV2').'</td><td>'.$result->DateTime.'</td></tr>';
		echo '<tr><td class="first">'.__('IP Adress','ZdStatsV2').'</td><td><a href="?page=ZdStatsV2&amp;detail=IP&amp;S='.$FirstDay.'&amp;P='.$LastDay.'&amp;IP='.base64_encode($result->IP).'">'.$result->IP.'</a></td></tr>';
		echo '<tr><td class="first">'.__('Hostname','ZdStatsV2').'</td><td>'.gethostbyaddr($result->IP).'</td></tr>';
		echo '<tr><td class="first">'.__('Geolocalisation','ZdStatsV2').'</td><td><a href="http://maps.google.com/maps?f=q&hl=en&geocode=&z=6&q='.$Loc.'" target="_blank">'.$Loc.'</a></td></tr>';
		echo '<tr><td class="first">'.__('Page viewed','ZdStatsV2').'</td><td><a href="'.get_bloginfo('url').$result->URL.'" target="_blank">'.$result->URL.'</a></td></tr>';
		echo '<tr><td class="first">'.__('Referer','ZdStatsV2').'</td><td><a href="'.$result->Referer.'" target="_blank">'.$result->Referer.'</a></td></tr>';
		echo '<tr><td class="first">'.__('Browser','ZdStatsV2').'</td><td>'.$this->FetchBrowser($result->UserAgent).'</td></tr>';
		echo '<tr><td class="first">'.__('Operating System','ZdStatsV2').'</td><td>'.$this->FetchOS($result->UserAgent).'</td></tr>';
		echo '<tr><td class="first">'.__('User agent','ZdStatsV2').'</td><td>'.$result->UserAgent.'</td></tr>';
		echo '<tr><td class="first">'.__('Performances','ZdStatsV2').'</td><td>'.$result->SqlQueries.' '.__('SQL Queries','ZdStatsV2').' / '.$result->LoadTime.' '.__('Seconds to load','ZdStatsV2').'</td></tr>';
		echo '<tr><td colspan="3" class="first"><center><a href="edit-comments.php?s='.$result->IP.'&mode=detail&comment_status=">'.__('View all comments written from this IP','ZdStatsV2').'</a></center></td></tr>';
		echo '</table>';
		echo '<small><a href="?page=ZdStatsV2&amp;detail=export&amp;q='.base64_encode($query).'">'.__('Export as CSV','ZdStatsV2').'</a></small>';
		echo '</div>';
	}
	
	function OutgoingLinks() {
		global $wpdb;
		echo '<div class="wrap" id="zdstats">';
		echo '<h2>'.__('Outgoing Links','ZdStatsV2').'</h2>';

		if (isset($_GET['P'])) $_POST['P']=$_GET['P'];
		if (isset($_GET['S'])) $_POST['S']=$_GET['S'];		
		if (!isset($_POST['P'])) $_POST['P']=strftime("%Y-%m-%d",time());
		if (!isset($_POST['S'])) $_POST['S']=strftime("%Y-%m-%d",strtotime($_POST['P']." -1 day"));
		
		$Dates=$this->DisplayPeriodSelector(2);
		$FirstDay=$Dates->FirstDay;
		$LastDay=$Dates->LastDay;
		$query="SELECT URL, EntryID, IP, Referer, DateTime FROM ".$this->Entries." WHERE `DateTime`>'$FirstDay' and `DateTime`<'$LastDay' and SearchEngine=3 order by DateTime, SessionID";
		$stats=$wpdb->get_results($query);
		if ($stats) {
			echo '<p></p><table class="widefat">';
			echo '<tr><th>'.__('Date &amp; Time','ZdStatsV2').'</th><th>'.__('Page viewed','ZdStatsV2').'</th><th>'.__('Came from','ZdStatsV2').'</th></tr>';
			foreach ($stats as $idx => $row) {
				echo '<tr>';
				echo '<td><a href="?page=ZdStatsV2&amp;detail=Visit&id='.$row->EntryID.'&amp;S='.$FirstDay.'&amp;P='.$LastDay.'">'.$row->DateTime.'</a></td>';
				echo '<td>'.$row->URL.'</td>';
				echo '<td>'.$row->Referer.'</td>';
				echo '</tr>';
			}
			echo '</table>';
		} else {
			echo '<h3>'.__('Nothing found for this period','ZdStatsV2').'</h3>';
		}
		echo '</div>';
	}
	
	function ShowIPDetail() {
		$IP=base64_decode($_GET['IP']);
		global $wpdb;
		echo '<div class="wrap" id="zdstats">';
		echo '<h2>';
		printf(__('Details for IP (%s)','ZdStatsV2'), $IP);
		echo '</h2>';
		
		$old=$this->CurrentPage;
		$this->CurrentPage.="&detail=IP&amp;IP=".$_GET['IP'];
		$Dates=$this->DisplayPeriodSelector(2);
		$FirstDay=$Dates->FirstDay;
		$LastDay=$Dates->LastDay;
		$this->CurrentPage=$old;
		
		if ($_GET['option']=="delete") $wpdb->query("DELETE FROM ".$this->Entries." WHERE `DateTime`>'$FirstDay' and `DateTime`<'$LastDay' and IP='".$IP."'");
		
		$query="SELECT DateTime, UserAgent, EntryID, Referer, URL
		FROM ".$this->Entries."
		WHERE `DateTime`>'$FirstDay' and `DateTime`<'$LastDay' and IP='".$IP."' AND SearchEngine=0
		ORDER BY DateTime";
		$res=$wpdb->get_results($query);
		if ($res) {
		
		if (file_exists($this->GeoFile.'/GeoLiteCity.dat')) $GeoIP="ok";
			else $GeoIP="ko";
			if ($GeoIP=="ok") {
				include_once ('../'.PLUGINDIR . '/' . dirname(plugin_basename (__FILE__)) .'/geoip/geoipcity.inc');
				include_once ('../'.PLUGINDIR . '/' . dirname(plugin_basename (__FILE__)) .'/geoip/geoipregionvars.php');
				$gi = geoip_open($this->GeoFile.'/GeoLiteCity.dat',GEOIP_STANDARD);
				$record = geoip_record_by_addr($gi,$IP);
				$Loc=($record->country_name!="") ? utf8_encode($record->country_name) : "Unknown";
				$Loc.=", ";
				$Loc.=$CityName=($record->city!="") ? utf8_encode($record->city) : "Unknown";
			} else $Loc="Unknown";
		
			echo '<table class="details">';
			echo '<tr><th colspan="3">'.__('General information','ZdStatsV2').'</th></tr>';
			echo '<tr><td class="first">'.__('Date &amp; Time','ZdStatsV2').'</td><td>'.$res[0]->DateTime.'</td><td><a href="'.$this->CurrentPage.'&amp;detail=IP&amp;IP='.$_GET['IP'].'&amp;S='.$FirstDay.'&amp;P='.$LastDay.'&amp;option=delete">'.__('Delete all pageviews for this IP/period','ZdStatsV2').'</a></td></tr>';
			echo '<tr><td class="first">'.__('Hostname','ZdStatsV2').'</td><td colspan="2">'.gethostbyaddr($IP).'</td></tr>';
			echo '<tr><td class="first">'.__('Geolocalisation','ZdStatsV2').'</td><td colspan="2"><a href="http://maps.google.com/maps?f=q&hl=en&geocode=&z=6&q='.$Loc.'" target="_blank">'.$Loc.'</a></td></tr>';
			echo '<tr><td class="first">'.__('Browser','ZdStatsV2').'</td><td colspan="2">'.$this->FetchBrowser($res[0]->UserAgent).'</td></tr>';
			echo '<tr><td class="first">'.__('Operating System','ZdStatsV2').'</td><td colspan="2">'.$this->FetchOS($res[0]->UserAgent).'</td></tr>';
			echo '<tr><td class="first">'.__('User agent','ZdStatsV2').'</td><td colspan="2">'.$res[0]->UserAgent.'</td></tr>';
			echo '<tr><td colspan="3" class="first"><center><a href="edit-comments.php?s='.$IP.'&mode=detail&comment_status=">'.__('View all comments written from this IP','ZdStatsV2').'</a></center></td></tr>';			
			echo '</table>';
			echo '<p>&nbsp;</p>';
			echo '<table class="widefat">';
			echo '<tr><th>'.__('Date &amp; Time','ZdStatsV2').'</th><th>'.__('Referer','ZdStatsV2').'</th><th>'.__('Page viewed','ZdStatsV2').'</th></tr>';
			$Count=0;
			foreach ($res as $index => $Value) {
				echo '<tr>';
				echo '<td><a href="?page=ZdStatsV2&amp;detail=Visit&id='.$Value->EntryID.'">'.strftime("%Y-%m-%d &mdash; %H:%M",strtotime($Value->DateTime)).'</a></td>';
				echo '<td><a href="'.$Value->Referer.'">'.$this->GetSiteName($Value->Referer).'</a></td>';
				echo '<td>'.$Value->URL.'</td>';
				echo '</tr>';
				$Count++;
			}
			echo '<tr class="footer"><td colspan="2">'.__('Total','ZdStatsV2').'</td><td style="text-align: right;">'.$Count.'</td></tr>';
			echo '</table>';
			echo '<small><a href="?page=ZdStatsV2&amp;detail=export&amp;q='.base64_encode($query).'">'.__('Export as CSV','ZdStatsV2').'</a></small>';
		} else {
			echo '<h3>'.__('Nothing found for this period','ZdStatsV2').'</h3>';
		}
		echo '</div>';
	}
	
	function Referers() {
		global $wpdb;
		echo '<div class="wrap" id="zdstats">';
		echo '<h2>'.__('Incoming Referers','ZdStatsV2').'</h2>';

		if (isset($_GET['P'])) $_POST['P']=$_GET['P'];
		if (isset($_GET['S'])) $_POST['S']=$_GET['S'];		
		if (!isset($_POST['P'])) $_POST['P']=strftime("%Y-%m-%d",time());
		if (!isset($_POST['S'])) $_POST['S']=strftime("%Y-%m-%d",strtotime($_POST['P']." -1 day"));
		
		$Dates=$this->DisplayPeriodSelector(2);
		echo '<a href="'.$this->CurrentPage.'&amp;S='.$_GET['S'].'&amp;P='.$_GET['P'].'&amp;view=domains">'.__('View Referring Domains','ZdStatsV2').'</a>
		&nbsp;&nbsp;&nbsp;<a href="'.$this->CurrentPage.'&amp;S='.$_GET['S'].'&amp;P='.$_GET['P'].'&amp;view=full">'.__('View Full Referers','ZdStatsV2')."</a><br /><br />";
		$FirstDay=$Dates->FirstDay;
		$LastDay=$Dates->LastDay;
		$query="SELECT Referer FROM ".$this->Entries." WHERE `DateTime`>'$FirstDay' and `DateTime`<'$LastDay' and SearchEngine=0 order by DateTime, SessionID";
		$stats=$wpdb->get_results($query);
		if ($stats) {
			$CurrentSession="";
			if (isset($_GET['view'])) $view=$_GET['view'];
			else $view="domains";
			foreach ($stats as $key => $row) {
				$Ref=($view=="domains") ? "http://".$this->GetSiteName($row->Referer) : $row->Referer;
				if ((!strstr($row->Referer,get_bloginfo('url')))&&($row->Referer!="")) $DailyStat[$Ref]->Count++;
			}
			if ($DailyStat) {
				echo '<table class="widefat">';
				echo '<tr><th>'.__('Referring Site','ZdStatsV2').'</th><th>'.__('Number of pageviews','ZdStatsV2').'</th></tr>';
				arsort($DailyStat);
				$Count['Total']=0;
				$Count['Pageviews']=0;
				foreach ($DailyStat as $Referer => $row) {
					echo '<tr>';
					echo '<td><a href="'.$Referer.'">'.$Referer.'</a></td>';
					echo '<td><a href="?page=ZdStatsV2&amp;detail=Referer&amp;S='.$FirstDay.'&amp;P='.$LastDay.'&amp;URL='.base64_encode($Referer).'">'.$row->Count.'</a></td>';
					echo '</tr>';
					$Count['Total']++;
					$Count['Pageviews']+=$row->Count;
				}
				echo '<tr class="footer"><td>'.__('Total','ZdStatsV2').'<span style="padding-left: 90%;">'.$Count['Total'].'</span></td><td>'.$Count['Pageviews'].'</td></tr>';
				echo '</table>';
				echo '<small><a href="?page=ZdStatsV2&amp;detail=export&amp;q='.base64_encode($query).'">'.__('Export as CSV','ZdStatsV2').'</a></small>';
			} else echo '<h3>'.__('Nothing found for this period','ZdStatsV2').'</h3>';
		} else {
			echo '<h3>'.__('Nothing found for this period','ZdStatsV2').'</h3>';
		}
		echo '</div>';	
	}
	
	function ShowRefererDetail() {
		global $wpdb;
		$Referer=base64_decode($_GET['URL']);
		echo '<div class="wrap" id="zdstats">';
		echo '<h2>'.__('Details for Referers','ZdStatsV2')." &laquo; $Referer &raquo;".'</h2>';

		if (isset($_GET['P'])) $_POST['P']=$_GET['P'];
		if (isset($_GET['S'])) $_POST['S']=$_GET['S'];		
		if (!isset($_POST['P'])) $_POST['P']=strftime("%Y-%m-%d",time());
		if (!isset($_POST['S'])) $_POST['S']=strftime("%Y-%m-%d",strtotime($_POST['P']." -1 day"));
		
		$old=$this->CurrentPage;
		$this->CurrentPage.="&amp;detail=Referer&amp;URL=".$_GET['URL'];
		$Dates=$this->DisplayPeriodSelector(2);
		$this->CurrentPage=$old;
		$FirstDay=$Dates->FirstDay;
		$LastDay=$Dates->LastDay;
		$query="SELECT EntryID, Referer, DateTime, URL FROM ".$this->Entries." WHERE `DateTime`>'$FirstDay' and `DateTime`<'$LastDay' and SearchEngine=0 and Referer like '$Referer%'order by DateTime, SessionID";
		$stats=$wpdb->get_results($query);
		if ($stats) {
			echo '<p></p><table class="widefat">';
			echo '<tr><th>'.__('Date &amp; Time','ZdStatsV2').'</th><th>'.__('Referring Site','ZdStatsV2').'</th><th>'.__('Page viewed','ZdStatsV2').'</th></tr>';
			$Count=0;
			foreach ($stats as $key => $row) {
				echo '<tr>';
				echo '<td><a href="?page=ZdStatsV2&amp;detail=Visit&id='.$row->EntryID.'">'.strftime("%Y-%m-%d &mdash; %H:%M",strtotime($row->DateTime)).'</a></td>';
				echo '<td><a href="'.$row->Referer.'">'.$row->Referer.'</a></td>';
				echo '<td>'.$row->URL.'</td>';
				echo '</tr>';
				$Count++;
			}
			echo '<tr class="footer"><td colspan="2">'.__('Total','ZdStatsV2').'</td><td>'.$Count.'</td></tr>';
			echo '</table>';
			echo '<small><a href="?page=ZdStatsV2&amp;detail=export&amp;q='.base64_encode($query).'">'.__('Export as CSV','ZdStatsV2').'</a></small>';			
		} else {
			echo '<h3>'.__('Nothing found for this period','ZdStatsV2').'</h3>';
		}
		echo '</div>';
	}

	function UserKeySort($a, $b) {
		if ($a['Count'] < $b['Count']) return 1;
		else return -1;
	}

	function Keywords() {
		global $wpdb;
		echo '<div class="wrap" id="zdstats">';
		echo '<h2>'.__('Keywords','ZdStatsV2').'</h2>';
		$Dates=$this->DisplayPeriodSelector(2);
		$FirstDay=$Dates->FirstDay;
		$LastDay=$Dates->LastDay;
		$query="SELECT Referer FROM ".$this->Entries." WHERE `DateTime`>'$FirstDay' and `DateTime`<'$LastDay' and SearchEngine=0 order by DateTime, SessionID";
		$stats=$wpdb->get_results($query);
		if ($stats) {
			foreach ($stats as $key => $row) {
				$keyword=$this->ExtractKeywords($row->Referer);
				if ($keyword['Word']) {
					$Words[$keyword['Word']]['Count']++;
					$Words[$keyword['Word']]['URL']=$row->Referer;
				}
			}
			
			if ($Words) {
				uasort($Words,array (& $this, "UserKeySort"));
				echo '<table class="widefat">';
				echo '<tr><th>'.__('Engine Name','ZdStatsV2').'</th><th>'.__('Keyword','ZdStatsV2').'</th><th>'.__('Count','ZdStatsV2').'</th></tr>';
				$Count['Total']=0;
				$Count['Lines']=0;
				foreach ($Words as $Name => $V) {
					echo '<tr>';
					echo '<td>'.$this->GetSiteName($V['URL']).'</td>';
					echo '<td><a href="'.$V['URL'].'">'.$Name.'</a></td>';
					echo '<td><a href="?page=ZdStatsV2&amp;detail=Keyword&amp;word='.base64_encode($Name).'&amp;S='.$FirstDay.'&amp;P='.$LastDay.'">'.$V['Count'].'</a></td>';
					echo '</tr>';
					$Count['Total']+=$V['Count'];
					$Count['Lines']++;
				}
				echo '<tr class="footer"><td>'.__('Total','ZdStatsV2').'</td><td>'.$Count['Lines'].'</td><td>'.$Count['Total'].'</td></tr>';
				echo '</table>';
			}
			echo '<small><a href="?page=ZdStatsV2&amp;detail=export&amp;q='.base64_encode($query).'">'.__('Export as CSV','ZdStatsV2').'</a></small>';
		} else {
			echo '<h3>'.__('Nothing found for this period','ZdStatsV2').'</h3>';
		}
		echo '</div>';
	}
	
	function ShowKeywordDetail() {
		$Keyword=base64_decode($_GET['word']);
		global $wpdb;
		echo '<div class="wrap" id="zdstats">';
		echo '<h2>';
		printf(__('Details for Keyword (%s)','ZdStatsV2'), $Keyword);
		echo '</h2>';
		
		$old=$this->CurrentPage;
		$this->CurrentPage.="&detail=Keyword&amp;word=".$_GET['word'];
		$Dates=$this->DisplayPeriodSelector(2);
		$FirstDay=$Dates->FirstDay;
		$LastDay=$Dates->LastDay;
		$this->CurrentPage=$old;
		
		$query="SELECT EntryID, DateTime, Referer, URL, IP
		FROM ".$this->Entries."
		WHERE `DateTime`>'$FirstDay' and `DateTime`<'$LastDay' and (Referer like '%=".urlencode($Keyword)."&%' or  Referer like '%=".urlencode($Keyword)."')
		ORDER BY DateTime desc";
		$res=$wpdb->get_results($query);
		if ($res) {
			echo '<table class="widefat">';
			echo '<tr><th>'.__('Date &amp; Time','ZdStatsV2').'</th><th>'.__('Search Engine','ZdStatsV2').'</th><th>'.__('Page viewed','ZdStatsV2').'</th><th>'.__('IP','ZdStatsV2').'</th></tr>';
			$Count=0;
			foreach ($res as $index => $Value) {
				echo '<tr>';
				echo '<td><a href="?page=ZdStatsV2&amp;detail=Visit&id='.$Value->EntryID.'">'.strftime("%Y-%m-%d &mdash; %H:%M",strtotime($Value->DateTime)).'</a></td>';
				echo '<td><a href="'.$Value->Referer.'">'.$this->GetSiteName($Value->Referer).'</a></td>';
				echo '<td>'.$Value->URL.'</td>';
				echo '<td><a href="?page=ZdStatsV2&detail=IP&S='.$FirstDay.'&P='.$LastDay.'&IP='.base64_encode($Value->IP).'">'.$Value->IP.'</a></td>';
				echo '</tr>';
				$Count++;
			}
			echo '<tr class="footer"><td colspan="3">'.__('Total','ZdStatsV2').'</td><td style="text-align: right;">'.$Count.'</td></tr>';
			echo '</table>';
			echo '<small><a href="?page=ZdStatsV2&amp;detail=export&amp;q='.base64_encode($query).'">'.__('Export as CSV','ZdStatsV2').'</a></small>';
		} else {
			echo '<h3>'.__('Nothing found for this period','ZdStatsV2').'</h3>';
		}
		echo '</div>';
	}
	
	function ShowPageViews() {
		$Keyword=base64_decode($_GET['URL']);
		global $wpdb;

		echo '<div class="wrap" id="zdstats">';
		echo '<h2>';
		printf(__('Visits for %s','ZdStatsV2'), $Keyword);
		echo '</h2>';
		
		$old=$this->CurrentPage;
		$this->CurrentPage.="&detail=Page&amp;URL=".$_GET['URL'];
		$Dates=$this->DisplayPeriodSelector(2);
		$FirstDay=$Dates->FirstDay;
		$LastDay=$Dates->LastDay;
		$this->CurrentPage=$old;
		
		$query="SELECT EntryID, DateTime, Referer, IP
		FROM ".$this->Entries."
		WHERE `DateTime`>'$FirstDay' and `DateTime`<'$LastDay' and URL='".$Keyword."'
		ORDER BY DateTime desc";
		$res=$wpdb->get_results($query);
		if ($res) {
			echo '<table class="widefat">';
			echo '<tr><th>'.__('Date &amp; Time','ZdStatsV2').'</th><th>'.__('Referer','ZdStatsV2').'</th><th>'.__('IP','ZdStatsV2').'</th></tr>';
			$Count=0;
			foreach ($res as $index => $Value) {
				echo '<tr>';
				echo '<td><a href="?page=ZdStatsV2&amp;detail=Visit&id='.$Value->EntryID.'">'.strftime("%Y-%m-%d &mdash; %H:%M",strtotime($Value->DateTime)).'</a></td>';
				echo '<td><a href="'.$Value->Referer.'">'.$Value->Referer.'</a></td>';
				echo '<td><a href="?page=ZdStatsV2&amp;detail=IP&amp;S='.$FirstDay.'&amp;P='.$LastDay.'&amp;IP='.base64_encode($Value->IP).'">'.$Value->IP.'</a></td>';
				echo '</tr>';
				$Count++;
			}
			echo '<tr class="footer"><td colspan="2">'.__('Total','ZdStatsV2').'</td><td style="text-align: right;">'.$Count.'</td></tr>';
			echo '</table>';
			echo '<small><a href="?page=ZdStatsV2&amp;detail=export&amp;q='.base64_encode($query).'">'.__('Export as CSV','ZdStatsV2').'</a></small>';
		} else {
			echo '<h3>'.__('Nothing found for this period','ZdStatsV2').'</h3>';
		}
		echo '</div>';
	}
	
	function Geolocalisation() {
		global $wpdb;
		echo '<div class="wrap" id="zdstats">';
		echo '<h2>'.__('Geolocalisation','ZdStatsV2').'</h2>';
		if (file_exists($this->GeoFile.'/GeoLiteCity.dat')) $GeoIP="ok";
		else $GeoIP="ko";
		if ($GeoIP=="ko") {
			echo '<h3>'.__('GeoLiteCity.dat not found, feature is disabled','ZdStatsV2').'</h3>';
			echo '</div>';
			return;
		}
		
		include_once ('../'.PLUGINDIR . '/' . dirname(plugin_basename (__FILE__)) .'/geoip/geoipcity.inc');
		include_once ('../'.PLUGINDIR . '/' . dirname(plugin_basename (__FILE__)) .'/geoip/geoipregionvars.php');
		$gi = geoip_open($this->GeoFile.'/GeoLiteCity.dat',GEOIP_STANDARD);

		
		$Dates=$this->DisplayPeriodSelector(2);
		$FirstDay=$Dates->FirstDay;
		$LastDay=$Dates->LastDay;
		$query="SELECT distinct(IP) as IP, count(IP) as Compte FROM ".$this->Entries." WHERE `DateTime`>'$FirstDay' and `DateTime`<'$LastDay' and SearchEngine=0 GROUP BY IP";
		$stats=$wpdb->get_results($query);
		if ($stats) {
			foreach ($stats as $key => $row) {
				$CurrentIP=$row->IP;
				$record = geoip_record_by_addr($gi,$CurrentIP);
				$CodePays=($record->country_code!="") ? $record->country_code : "??";
				$Pays=($record->country_name!="") ? utf8_encode($record->country_name) : "Unknown";
				$CityName=($record->city!="") ? utf8_encode($record->city) : "Unknown";
				$CodePays=($record->country_code!="") ? $record->country_code : "??";
				$GeoTags=$record->latitude.";".$record->longitude;
				
				$City[$CityName]['Count']=$row->Compte;
				$City[$CityName]['Country']=$Pays;
				$City[$CityName]['Lat']=$record->latitude;
				$City[$CityName]['Lon']=$record->longitude;
			}
			if ($City) {
				$this->DrawMap($City);			
				arsort($City);
				echo '<table class="widefat">';
				echo '<tr><th>'.__('Country','ZdStatsV2').'</th><th>'.__('City','ZdStatsV2').'</th><th>'.__('Count','ZdStatsV2').'</th></tr>';
				foreach ($City as $Name => $V) {
					echo '<tr>';
					echo '<td><a href="http://maps.google.com/maps?f=q&hl=en&geocode=&z=6&q='.$V['Country'].'" target="_blank">'.$V['Country'].'</a></td>';
					echo '<td><a href="http://maps.google.com/maps?f=q&hl=en&geocode=&z=6&q='.$V['Country'].',+'.$Name.'" target="_blank">'.$Name.'</a></td>';
					echo '<td>'.$V['Count'].'</td>';
					echo '</tr>';
				}
				echo '</table>';
				echo '<small><a href="?page=ZdStatsV2&amp;detail=export&amp;q='.base64_encode($query).'">'.__('Export as CSV','ZdStatsV2').'</a></small>';
			}
		} else {
			echo '<h3>'.__('Nothing found for this period','ZdStatsV2').'</h3>';
		}
		echo '</div>';
	}
	
	function TopTen() {
		global $wpdb;
		echo '<div class="wrap" id="zdstats">';
		echo '<h2>'.__('Top Ten','ZdStatsV2').'</h2>';

		if (isset($_GET['max'])) $_POST['max']=$_GET['max'];
		$max=(isset($_POST['max'])) ? $_POST['max'] : 10;

		$OldPage=$this->CurrentPage;
		if (@$_GET['opt']=="visitors") $this->CurrentPage.="&amp;opt=visitors&amp;max=".$max;
		else if (@$_GET['opt']=="outgoing") $this->CurrentPage.="&amp;opt=outgoing&amp;max=".$max;
		$Dates=$this->DisplayPeriodSelector(2);
		$this->CurrentPage=$OldPage;
		$FirstDay=$Dates->FirstDay;
		$LastDay=$Dates->LastDay;
		$LD=strftime("%Y-%m-%d",strtotime($LastDay." -1 day"));
		
		if (@$_GET['opt']=="visitors") {
			$Name="Visitors";
			$query="SELECT IP as A, count(IP) as B FROM ".$this->Entries." WHERE `DateTime`>'$FirstDay' and `DateTime`<'$LastDay' and SearchEngine=0 group by IP order by B desc";
			$URL='<a href="?page=ZdStatsV2&amp;detail=IP&amp;IP=%s&amp;S='.$FirstDay.'&amp;P='.$LastDay.'">%s</a>';
		} else if (@$_GET['opt']=="outgoing") {
			$Name="Outgoing Links";
			$query="SELECT URL as A, count(URL) as B FROM ".$this->Entries." WHERE `DateTime`>'$FirstDay' and `DateTime`<'$LastDay' and SearchEngine=3 group by URL order by B desc";
			$URL='<a href="?page=ZdStatsV2&amp;detail=Page&amp;URL=%s&amp;S='.$FirstDay.'&amp;P='.$LastDay.'">%s</a>';
		} else {
			$Name="Pages";
			$query="SELECT URL as A, count(URL) as B FROM ".$this->Entries." WHERE `DateTime`>'$FirstDay' and `DateTime`<'$LastDay' and SearchEngine=0 group by URL order by B desc";
			$URL='<a href="?page=ZdStatsV2&amp;detail=Page&amp;URL=%s&amp;S='.$FirstDay.'&amp;P='.$LastDay.'">%s</a>';
		}
		
		$query.=" LIMIT 0, $max";
		$stats=$wpdb->get_results($query);
		echo '<p><a href="'.$this->CurrentPage.'&amp;P='.$LD.'&amp;S='.$FirstDay.'&amp;max='.$max.'">'.__('Pages','ZdStatsV2').'</a>&nbsp;&nbsp;&nbsp;&mdash;&nbsp;&nbsp;&nbsp;';
		echo '<a href="'.$this->CurrentPage.'&amp;opt=visitors&amp;P='.$LD.'&amp;S='.$FirstDay.'&amp;max='.$max.'">'.__('Visitors','ZdStatsV2').'</a>&nbsp;&nbsp;&nbsp;&mdash;&nbsp;&nbsp;&nbsp;';
		echo '<a href="'.$this->CurrentPage.'&amp;opt=outgoing&amp;P='.$LD.'&amp;S='.$FirstDay.'&amp;max='.$max.'">'.__('Outgoing Links','ZdStatsV2').'</a></p>';
		if ($stats) {
			echo '<table class="widefat">';
			echo '<tr><th>'.__('#Rank','ZdStatsV2').'</th><th>'.__($Name,'ZdStatsV2').'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
			<a href="'.$this->CurrentPage.'&amp;opt='.$_GET['opt'].'&amp;P='.$LD.'&amp;S='.$FirstDay.'&amp;max=5">5</a>
			&mdash; <a href="'.$this->CurrentPage.'&amp;opt='.$_GET['opt'].'&amp;P='.$LD.'&amp;S='.$FirstDay.'&amp;max=10">10</a>
			&mdash; <a href="'.$this->CurrentPage.'&amp;opt='.$_GET['opt'].'&amp;P='.$LD.'&amp;S='.$FirstDay.'&amp;max=20">20</a>
			&mdash; <a href="'.$this->CurrentPage.'&amp;opt='.$_GET['opt'].'&amp;P='.$LD.'&amp;S='.$FirstDay.'&amp;max=50">50</a>
			</th><th>'.__('Count','ZdStatsV2').'</th></tr>';
			$Count=0;
			$index=1;
			foreach ($stats as $key => $row) {
				echo '<tr>';
				echo '<td>'.$index.'</td>';
				echo '<td>';
				printf($URL,base64_encode($row->A),$row->A);
				echo '</td>';
				echo '<td>'.$row->B.'</td>';
				echo '</tr>';
				$Count+=$row->B;
				$index++;
			}
			echo '<tr class="footer"><td colspan="2">'.__('Total','ZdStatsV2').'</td><td>'.$Count.'</td></tr>';
			echo '</table>';
			echo '<small><a href="?page=ZdStatsV2&amp;detail=export&amp;q='.base64_encode($query).'">'.__('Export as CSV','ZdStatsV2').'</a></small>';
		} else {
			echo '<h3>'.__('Nothing found for this period','ZdStatsV2').'</h3>';
		}
		echo '</div>';	
	}
	
	function Technologies() {
		global $wpdb;
		echo '<div class="wrap" id="zdstats">';
		echo '<h2>'.__('Technologies','ZdStatsV2').'</h2>';
		
		$Dates=$this->DisplayPeriodSelector(2);
		$FirstDay=$Dates->FirstDay;
		$LastDay=$Dates->LastDay;
		
		$query="SELECT UserAgent FROM ".$this->Entries." WHERE `DateTime`>'$FirstDay' and `DateTime`<'$LastDay' and SearchEngine=0 group by IP";
		$res=$wpdb->get_results($query);
		$Total=0;
		foreach ($res as $line) {
			$Browsers[$this->FetchBrowser($line->UserAgent,3)]++;
			$Os[$this->FetchOS($line->UserAgent)]++;
			$Total++;
		}
		$GraphDatas->Legends=array (
			__('Browser','ZdStatsV2') => 'string',
			__('Visits','ZdStatsV2') => 'number',
		);
		$GraphDatas->Title=sprintf(__('Browser stats From %s To %s ','ZdStatsV2'),strftime("%A %d %B %Y",strtotime($FirstDay)),strftime("%A %d %B %Y",strtotime($LastDay)));
		arsort($Browsers);
		foreach ($Browsers as $Name => $Count) {
			$GraphDatas->Dataset[$Name]=$Count;
		}
		$this->DrawPieChart($GraphDatas);
		
		$GraphDatas->Legends=array (
			__('OS','ZdStatsV2') => 'string',
			__('Visits','ZdStatsV2') => 'number',
		);
		$GraphDatas->Title=sprintf(__('Operating Systems From %s To %s ','ZdStatsV2'),strftime("%A %d %B %Y",strtotime($FirstDay)),strftime("%A %d %B %Y",strtotime($LastDay)));
		arsort($Os);
		$GraphDatas->Dataset="";
		foreach ($Os as $Name => $Count) {
			$GraphDatas->Dataset[$Name]=$Count;
		}
		$this->DrawPieChart($GraphDatas);
		
		echo '<div style="float:left; width: 484px; margin: 3px;">';
		echo '<table class="widefat">';
		echo '<tr><th>'.__('Browser name','ZdStatsV2').'</th><th>'.__('Percentage','ZdStatsV2').'</th><th>'.__('Count','ZdStatsV2').'</th></tr>';
		foreach ($Browsers as $Name => $Count) {
			echo '<tr>';
			echo "<td>$Name</td>";
			echo "<td>".sprintf("%2.2f%%",(($Count/$Total)*100))."</td>";
			echo "<td>".$Count."</td>";
			echo '</tr>';
		}
		echo '<tr class="footer"><td>'.__('Total "Unique" Visitors','ZdStatsV2').'</td><td colspan="2">'.$Total.'</td></tr>';
		echo '</table>';
		echo '</div>';
		echo '<div style="float:right; width: 484px; margin: 3px;">';
		echo '<table class="widefat">';
		echo '<tr><th>'.__('Operating System','ZdStatsV2').'</th><th>'.__('Percentage','ZdStatsV2').'</th><th>'.__('Count','ZdStatsV2').'</th></tr>';
		foreach ($Os as $Name => $Count) {
			echo '<tr>';
			echo "<td>$Name</td>";
			echo "<td>".sprintf("%2.2f%%",(($Count/$Total)*100))."</td>";
			echo "<td>".$Count."</td>";
			echo '</tr>';
		}
		echo '<tr class="footer"><td>'.__('Total "Unique" Visitors','ZdStatsV2').'</td><td colspan="2">'.$Total.'</td></tr>';
		echo '</table>';		
		echo '</div>';
		echo '</div>';
	}
	
	function Performances() {
		global $wpdb;
		echo '<div class="wrap" id="zdstats">';
		echo '<h2>'.__('Performances','ZdStatsV2').'</h2>';
		$Dates=$this->DisplayPeriodSelector(2);
		$FirstDay=$Dates->FirstDay;
		$LastDay=$Dates->LastDay;
		$query="SELECT SqlQueries, LoadTime, DateTime FROM ".$this->Entries." WHERE `DateTime`>'$FirstDay' and `DateTime`<'$LastDay' and SearchEngine=0 order by DateTime, SessionID";
		$stats=$wpdb->get_results($query);
		if ($stats) {
			$CurrentSession="";
			foreach ($stats as $key => $row) {
				$DailyStat[substr($row->DateTime,0,10)]->SQL+=$row->SqlQueries;
				$DailyStat[substr($row->DateTime,0,10)]->Time+=$row->LoadTime;
				$DailyStat[substr($row->DateTime,0,10)]->Count++;
			}

			
			$GraphDatas->Legends=array (
				__('Day','ZdStatsV2') => 'string',
				__('SQL Queries','ZdStatsV2') => 'number',
				__('Time to load page','ZdStatsV2') => 'number'
			);
			$GraphDatas->Title=sprintf(__('Performances from %s to %s','ZdStatsV2'),substr($FirstDay,0,10),substr($LastDay,0,10));
			foreach ($DailyStat as $Day => $row) {
				$GraphDatas->Dataset[$Day]=array ($row->SQL/$row->Count, $row->Time/$row->Count);
			}
			
			$this->DrawAreaChart($GraphDatas);

			echo '<table class="widefat">';
			echo '<tr><th>'.__('Date &amp; Time','ZdStatsV2').'</th><th>'.__('Average SQL Queries','ZdStatsV2').'</th><th>'.__('Average time to load page','ZdStatsV2').'</th></tr>';
			$Count['Lines']=0;
			$Count['Total']=0;
			krsort($DailyStat);
			foreach ($DailyStat as $Day => $row) {
				echo '<tr>';
				echo '<td><a href="?page=ZdStatsV2_Daily&amp;P='.$Day.'">'.$Day.'</a></td>';
				echo '<td>'.sprintf("%2.2f",$row->SQL/$row->Count).'</td>';
				echo '<td>'.sprintf("%2.2f",$row->Time/$row->Count).'</td>';
				echo '</tr>';
				$Count['Lines']+=$row->Visitors;
				$Count['Total']+=$row->Count;
			}
			echo '<tr class="footer"><td>'.__('Total','ZdStatsV2').'</td><td>'.$Count['Lines'].'</td><td>'.$Count['Total'].'</td></tr>';
			echo '</table>';
			echo '<small><a href="?page=ZdStatsV2&amp;detail=export&amp;q='.base64_encode($query).'">'.__('Export as CSV','ZdStatsV2').'</a></small>';
		} else {
			echo '<h3>'.__('Nothing found for this period','ZdStatsV2').'</h3>';
		}
		echo '</div>';
	}
	
	function ExtractKeywords($URL) {
		$Queries=array (
			"www\.google\."	=> array("[\?&]q=([^&]*)", "Google"),
			"blogsearch\.google\."	=> array("[\?&]q=([^&]*)", "Blogsearch Google"),
			"search\.msn\."	=> array("[\?&]q=([^&]*)","Search Msn"),
			"search\.live\."	=> array("[\?&]q=([^&]*)","Search Live"),
			"search\.yahoo\.com"	=> array("[\?&]p=([^&]*)","Yahoo"),
			".altavista.com"	=> array("[\?&]p=([^&]*)","Altavista"),
			"\.voila\.fr"	=> array("[\?&]rdata=([^&]*)","Voila"),
			"\.lycos\."	=> array("[\?&]query=([^&]*)","Lycos")
		);
		foreach ($Queries as $Search => $Keywords) {
			if (preg_match_all("|$Search|i",$URL, $res)) {
				preg_match_all("|".$Keywords[0]."|i",$URL, $res);
				$Resultat['Word']=urldecode($res[1][0]);
				$Resultat['Engine']=$Keywords[1];
				break;
			}
		}
		return $Resultat;
	}

	function GetSiteName($str) {
		preg_match('@^(?:http://)?([^/]+)@i', $str, $matches);
		$host = $matches[1];
		return $host;
	}

	function DisplayStyle() {
		echo '<link media="all" type="text/css" href="'.$this->ContentDirectory.'/style.css" rel="stylesheet" />';
		echo '<link rel="stylesheet" type="text/css" media="all" href="'.$this->ContentDirectory.'/cal/calendar-blue.css" title="win2k-cold-1" />';
	}
	
	function StartSession() {
		// This session_start is used to allow user tracking
		@session_start();
	}
	
	function DisplayPeriodSelector($type=2, $showform=true) {
		if (isset($_POST['P'])) $_GET['P']=$_POST['P'];
		if (isset($_POST['S'])) $_GET['S']=$_POST['S'];
	
		$P=(isset($_GET['P'])) ? $_GET['P'] : strftime("%Y-%m-%d",time());
		$S=(isset($_GET['S'])) ? $_GET['S'] : strftime("%Y-%m-%d",strtotime("-6 days"));
		if ($S) $Selected='Selected';
		else $Selected='Current';
		$_GET['S']=$S;
		$_GET['P']=$P;
		
		$DiffTime=(strtotime($P)-strtotime($S));
		$PreS=strftime("%Y-%m-%d",strtotime($S)-$DiffTime);
		$PostP=strftime("%Y-%m-%d",strtotime($P)+$DiffTime);
		
		echo '<script type="text/javascript" src="'.$this->ContentDirectory.'/cal/calendar.js"></script>
		<script type="text/javascript" src="'.$this->ContentDirectory.'/cal/lang/calendar-en.js"></script>
		<script type="text/javascript" src="'.$this->ContentDirectory.'/cal/calendar-setup.js"></script>';
		
		if ($showform) {
			echo '<form action="'.$this->CurrentPage.'" method="post" style="text-align: center; width: 100%;">';
			echo '<a href="'.$this->CurrentPage.'&amp;S='.$PreS.'&amp;P='.$S.'">&laquo; '.__('Previous period','ZdStatsV2').'</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
		}
			if ($type=="2") echo '<label for="f_date_from">'.__('From :','ZdStatsV2').'</label><input type="text" id="f_date_from" name="S" size="10" value="'.$S.'"/>&nbsp;&nbsp;';
			if ($type=="2") echo '<label for="f_date_to">'.__('To :','ZdStatsV2').'</label>';
			echo '<input type="text" id="f_date_to" name="P" size="10" value="'.$P.'"/>&nbsp;&nbsp;';
			if ($showform) {
				echo '<input type="submit" name="submit" value="'.__('Go','ZdStatsV2').'" />';
				if ((strtotime($P)+$DiffTime)<time()) echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href="'.$this->CurrentPage.'&amp;S='.$P.'&amp;P='.$PostP.'">'.__('Next period','ZdStatsV2').' &raquo;</a>&nbsp;&nbsp;&nbsp;';
			}
		echo '<script type="text/javascript">';
		if ($type=="2") echo 'Calendar.setup({
				inputField     :    "f_date_from",   // id of the input field
				ifFormat       :    "%Y-%m-%d",       // format of the input field
				showsTime      :    false,
				timeFormat     :    "24"
			});';
		echo 'Calendar.setup({
				inputField     :    "f_date_to",   // id of the input field
				ifFormat       :    "%Y-%m-%d",       // format of the input field
				showsTime      :    false,
				timeFormat     :    "24"
			});';
		echo '</script>';	
		
		if ($showform) echo '</form>';
		$Dates->FirstDay=strftime("%Y-%m-%d 00:00",strtotime($S));
		if ($type==2) $Dates->LastDay=strftime("%Y-%m-%d 00:00",strtotime($P." +1 day"));
		else $Dates->LastDay=strftime("%Y-%m-%d 01:01",strtotime($P));
		return $Dates;
	}
	

	function DrawAreaChart($Datas) {
		$Legends=$Datas->Legends;
		echo '<script type="text/javascript" src="http://www.google.com/jsapi"></script>
<script type="text/javascript">
  google.load("visualization", "1", {packages:["areachart"]});
  google.setOnLoadCallback(draw_ZdstatsChart);';

		echo "      function draw_ZdstatsChart() {
        var data = new google.visualization.DataTable();";
        if ($Datas->Legends) foreach ($Datas->Legends as $Name => $type) {
		echo "data.addColumn('$type','$Name');\n";
	}
	        echo "data.addRows(".count($Datas->Dataset).");\n";
		$i=0;
		foreach ($Datas->Dataset as $Time => $row) {
			echo "data.setValue($i, 0, '$Time');";
			if ($row[0]=="") $row[0]="0";
			if ($row[1]=="") $row[1]="0";
			echo "data.setValue($i, 1, ".$row[0].");";
			echo "data.setValue($i, 2, ".$row[1].");";
			$i++;
		}
	echo "        var chart = new google.visualization.AreaChart(document.getElementById('chart_div'));
        chart.draw(data, { width: 980, height: 350, legend: 'top', title: '".$Datas->Title."', colors: ['#8d0f0f', '#53a1b4'] });
      }

</script>";
	echo '<div id="chart_div" style="text-align: center;"></div><p>&nbsp;</p>';
	}
	
	function DrawPieChart($Datas) {
		echo '<script type="text/javascript" src="http://www.google.com/jsapi"></script>
    <script type="text/javascript">
      google.load("visualization", "1", {packages:["piechart"]});
      google.setOnLoadCallback(drawChart);
      function drawChart() {
        var data = new google.visualization.DataTable(); ';
		$divname="";
		if ($Datas->Legends) foreach ($Datas->Legends as $Name => $type) {
			if (!$divname) $divname=$Name;
			echo "data.addColumn('$type','$Name');\n";
		}
		echo "data.addRows(".count($Datas->Dataset).");\n";
		$i=0;
			foreach ($Datas->Dataset as $Time => $row) {
				if ($row=="") $row=0;
				echo "data.setValue($i, 0, '$Time');";
				echo "data.setValue($i, 1, ".$row.");";
				$i++;
			}

		echo "var chart = new google.visualization.PieChart(document.getElementById('".$divname."_div'));
		chart.draw(data, {width: 490, height: 350, legend: 'right', is3D: true, title: '".$Datas->Title."'});";
		echo '}</script>';
		echo '<div id="'.$divname.'_div" style="text-align: left; float: left;"></div>';
	}
	
	function DrawMap($Data) {
    ?><script src="http://maps.google.com/maps?file=api&v=2&key=<?php echo $this->GoogleMaps_API ?>" type="text/javascript"></script>
    <script type="text/javascript" src="http://www.google.com/jsapi"></script>
    <script type="text/javascript">
      google.load("visualization", "1", {packages:["map"]});
      google.setOnLoadCallback(drawMap);
      function drawMap() {
        var data = new google.visualization.DataTable();
        data.addColumn('number', 'Lat');
        data.addColumn('number', 'Lon');
        data.addColumn('string', 'Name');
<?php
        echo 'data.addRows('.count($Data).');';
	$i=0;
	foreach ($Data as $Name => $Values) {
		if ($Values['Count']>1) {
			if ($Values['Lat']&&$Values['Lon']) {
				echo "data.setCell($i, 0, ".$Values['Lat'].");\n";
				echo "data.setCell($i, 1, ".$Values['Lon'].");\n";
				echo "data.setCell($i, 2, '".addslashes($Name.', '.$Values['Country'])."<br />".$Values['Count']." ".__('Pages viewed','ZdStatsV2')."');\n";
				$i++;
			}
		}
	}
?>
        var map = new google.visualization.Map(document.getElementById('map_div'));
        map.draw(data, {showTip: true, mapType: 'hybrid', enableScrollWheel: true});
      }
    </script>
    <?php
	echo '<div id="map_div" style="border: 1px solid;"></div><small>'.__('Locations with only one page viewed are not displayed','ZdStatsV2');
	echo '<br />'.__('<strong>Warning</strong> : Map  loading time may be long due to the amount of points to draw','ZdStatsV2');
	echo '</small>';
	}
	
	function IsSpammer($ip) {
		//	Check if IP is marked as Spammer from SBL provider (default is spamhaus.org)
		if ($this->SpamDNS) {
			$checkedip=implode('.',array_reverse(explode('.', $ip))).'.'.$this->SpamDNS;
			if ( $checkedip != gethostbyname($checkedip) ) {
				return true;
			}
		}
		return false;
	}
	
	
	function FetchOS($UserAgent) {
		$OS="Unknown";
		$Version="";
		foreach ($this->OsList as $SearchedText => $FoundUA) {
			if (stripos($UserAgent,$SearchedText)===false);
			else {
				$OS=$FoundUA[0];
				$preg=$FoundUA[1];
			}
		}
		if ($preg!="") preg_match($preg,$UserAgent, $Version);
		$Version[1]=trim($Version[1]);
		if ($OS=="Windows") {
			if ($Version[1]=="NT 6.1") $Version[1]="Storage Server";
			if ($Version[1]=="NT 6.0") $Version[1]="Vista";
			if ($Version[1]=="NT 5.2") $Version[1]="2003";
			if ($Version[1]=="NT 5.1") $Version[1]="XP";
			if ($Version[1]=="NT 5.0") $Version[1]="2000";
		}
		return $OS.' '.$Version[1];
	}
	
	function FetchBrowser($UserAgent, $version=999) {
		$Browser="Unknown";
		foreach ($this->BrowserList as $SearchedText => $BrowserFetched) {
			if (stripos($UserAgent,$SearchedText)===false);
			else {
				$Browser=$BrowserFetched[0];
				$preg=$BrowserFetched[1];
			}
		}
		if ($preg!="") preg_match($preg,$UserAgent, $Version);
		if ($Browser=="Unknown") return $Browser;
		return $Browser.' '.substr($Version[1],0,$version);
	}
	
	function IPtoRegExp($IP) {
		// Build Regexp from IP
		$from=array("*",".","?");
		$to=array("[0-9]*","\.","[0-9]?");
		return str_replace($from, $to,"/".$IP."/U");
	}
	
	
	function ProcessStats() {
		global $wp_query, $wpdb, $wp;
		$query='SELECT * FROM '.$this->Entries.' WHERE SearchEngine=999';
		$res=$wpdb->get_results($query);
		if ($res) {
			foreach ($res as $idx => $row) {
				$fetched=false;
				if ($this->IsSpammer($row->IP)) {
					$ToDelete[]=$row->EntryID;
					$fetched=true;
				}
				if ((!$fetched)&&($this->BlackList)) {
					foreach (explode(",",$this->BlackList) as $BL_IP) {
						$BL_IP=trim($BL_IP);
						$BL_IP=$this->IpToRegExp($BL_IP);
						if (preg_match($BL_IP, $row->IP, $res)==1) {
							$ToDelete[]=$row->EntryID;
							$fetched=true;
						}
					}
				}
				if ((!$fetched)&&($row->UserAgent=="")) {
					if ($this->SaveBots!="on") { $ToDelete[]=$row->EntryID; $fetched=true; }
					else { $SearchEngine[]=$row->EntryID; $fetched=true; }
				}
				$UserAgents=explode("\n",stripslashes($this->UserAgentList));
				
				if ((!$fetched)&&($UserAgents)) foreach ($UserAgents as $BotDef) {
					$Search=explode('=>',$BotDef);
					$SearchedText=trim($Search[0]);
					if (stripos($row->UserAgent,$SearchedText)===false);
					else {
						if ($SaveBots=="on") {
							$SearchEngine[]=$row->EntryID;
							$fetched=true;
						} else {
							$ToDelete[]=$row->EntryID;
							$fetched=true;
						}
					}
				}
				if ((!$fetched)&&($this->FetchBrowser($row->UserAgent)=="Unknown")) {
					$SearchEngine[]=$row->EntryID;
					$fetched=true;
				}
				if (!$fetched) $RealHit[]=$row->EntryID;
			}
		}
	
		if ($RealHit) $wpdb->query("UPDATE ".$this->Entries." set SearchEngine=0 WHERE EntryID in (".implode(',',$RealHit).")");
		if ($SearchEngine) $wpdb->query("UPDATE ".$this->Entries." set SearchEngine=1 WHERE EntryID in (".implode(',',$SearchEngine).")");
		if ($ToDelete) $wpdb->query("DELETE FROM ".$this->Entries." WHERE EntryID in (".implode(',',$ToDelete).")");
		printf(__('Updated %s Entries','ZdStatsV2'),count($ToDelete)+count($SearchEngine)+count($RealHit));
		echo "<br />".__('Done processing statistics','ZdStatsV2');
	}
	
	function RecordVisit() {
		global $wp_query, $wpdb, $user_level, $wp;

		$timezone=get_option("gmt_offset");
		if ($timezone>=0) $timezone=-$timezone;
		else $timezone="+".-$timezone;
		
		$oldTZ=getenv("TZ");
		putenv('TZ=Etc/GMT'.$timezone);
		
		if (function_exists('headers_list')) {
			foreach (headers_list() as $idx => $header) {
				if (preg_match("|Location: .*|i",$header)) return;
			}
		}
		$UL=(isset($user_level)) ? $user_level : -1;
		if (($UL>=$this->UserLevelExcluded)&&($this->UserLevelExcluded!=-1)) {
			return;
		}
		$IP=$_SERVER['REMOTE_ADDR'];
		if (is_404()) return;		// If page is 404 error, don't record
		if (is_admin()) return;		// If administration page, don't record
		
		$PageViewed=$_SERVER['REQUEST_URI'];
		if (strstr($PageViewed,$this->PLUGINDIR)) return;	// If page is from wp-content folder, don't record
		if (strstr($PageViewed,'/wp-includes/')) return;		// If include script
		if (strstr($PageViewed,"wp-login.php")) return;		// If page is login page, don't record

		$SessionID=session_id();
		$IP=$_SERVER['REMOTE_ADDR'];
		$Referer=$_SERVER['HTTP_REFERER'];
		$UA=$_SERVER['HTTP_USER_AGENT'];
		$DT=strftime("%Y-%m-%d %H:%M:%S");
		
		putenv("TZ=$oldTZ");
		
		// Check using an SBL provider if IP is known as Spammer IP
		if ($this->IsSpammer($IP)) {return;}
		
		// Check if IP is BlackListed by Yourself
		if ($this->BlackList) {
			foreach (explode(",",$this->BlackList) as $BL_IP) {
				$BL_IP=trim($BL_IP);
				$BL_IP=$this->IpToRegExp($BL_IP);
				if (preg_match($BL_IP, $IP, $res)==1) { return;}
			}
		}
		if ($this->AutomaticUpdate=="on") {
			$SearchEngine=0;
			if (is_feed()&&($this->SeparateFeeds)) $SearchEngine=2;
			else {
				// If UserAgent empty, consider visitor as a BOT
				if ($UA=="") {
					$SearchEngine=1;
					$SessionID="Unknown";
					if ($this->SaveBots!="on") return;
				}
				$UserAgents=explode("\n",stripslashes($this->UserAgentList));
				
				// if UserAgent is in the BotList definition, treat it as such
				if ($SearchEngine=="0") foreach ($UserAgents as $BotDef) {
					$Search=explode('=>',$BotDef);
					$SearchedText=trim($Search[0]);
					if (stripos($UA,$SearchedText)===false);
					else {
						$SearchEngine=1;
						$SessionID=trim($Search[1]);
						if ($SaveBots=="on") break;
						else return;
					}
				}
				
				// If UserAgent gives an Unknown Browser, consider as BOT
				if (($SearchEngine=="0")&&($this->FetchBrowser($UA)=="Unknown")) {
					$SearchEngine=1;
					$SessionID="Unknown";
				}
			}
		} else $SearchEngine=999; // If automatic update not active, mark it for further processing
		$LoadTime=timer_stop(0,6);
		$LoadTime=str_replace(',','.',$LoadTime);		// Fetch page load time
		$SqlQueries=get_num_queries() + 1;			// Retrieve number of SQL queries executed until here (including insert below)
		$query="INSERT INTO ".$this->Entries." (SessionID, IP, Referer, UserAgent, DateTime, URL, SearchEngine, SqlQueries, LoadTime) VALUES('$SessionID', '$IP', '$Referer', '$UA', '$DT', '$PageViewed', $SearchEngine, $SqlQueries, '$LoadTime')";
		$wpdb->query($query);
	}
	
	function Search() {
		global $wpdb;
		echo '<div class="wrap" id="zdstats">';
		echo '<h2>'.__('Search','ZdStatsV2').'</h2>';

		echo '<form action="'.$this->CurrentPage.'" method="post">';
		$Dates=$this->DisplayPeriodSelector(2, false);
		$FirstDay=$Dates->FirstDay;
		$LastDay=$Dates->LastDay;
		$URL=($_POST['URL']!="") ? $_POST['URL'] : "*";
		$Referer=($_POST['Referer']!="") ? $_POST['Referer'] : "*";
		$UA=($_POST['UA']!="") ? $_POST['UA'] : "*";
		$IP=($_POST['IP']!="") ? $_POST['IP'] : "*";
		if (!isset($_POST['type'])) $_POST['type']=array("0");
		
		echo '<p><label for="URL">'.__('URL Like','ZdStatsV2').' : </label><input type="text" id="URL" name="URL" value="'.$URL.'" /></p>';
		echo '<p><label for="Referer">'.__('Referer Like','ZdStatsV2').' : </label><input type="text" id="Referer" name="Referer" value="'.$Referer.'" /></p>';
		echo '<p><label for="UA">'.__('User Agent Like','ZdStatsV2').' : </label><input type="text" id="UA" name="UA" value="'.$UA.'" /></p>';
		echo '<p><label for="IP">'.__('IP Like','ZdStatsV2').' : </label><input type="text" id="IP" name="IP" value="'.$IP.'" /></p>';
		echo '<p><label for="Type">'.__('Hit type','ZdStatsV2').' : </label><select MULTIPLE name="type[]" style="width: 300px; height: 72px;">
		<option value="0" ';if (array_search("0",$_POST['type'])!==false) echo 'SELECTED';echo '>'.__('Real users','ZdStatsV2').'</option>
		<option value="1" ';if (array_search("1",$_POST['type'])!==false) echo 'SELECTED';echo '>'.__('Search Engine','ZdStatsV2').'</option>
		<option value="2" ';if (array_search("2",$_POST['type'])!==false) echo 'SELECTED';echo '>'.__('Feed views','ZdStatsV2').'</option>
		<option value="3" ';if (array_search("3",$_POST['type'])!==false) echo 'SELECTED';echo '>'.__('Outgoing Links','ZdStatsV2').'</option>
		<option value="999" ';if (array_search("999",$_POST['type'])!==false) echo 'SELECTED';echo '>'.__('Not processed yet','ZdStatsV2').'</option>
		</select></p>';
		echo '<p style="font-size: x-small;">'.__('Note : All parameters are cumulated','ZdStatsV2').'</p>';
		echo '<p><input type="submit" name="submit"/></p>';
		echo '</form>';
		if (isset($_POST['submit'])) {
			$URL=str_replace("*","%",$_POST['URL']);
			$Referer=str_replace("*","%",$_POST['Referer']);
			$UserAgent=str_replace("*","%",$_POST['UA']);
			$IP=str_replace("*","%",$_POST['IP']);
			$Type=implode(',',$_POST['type']);
			
			$query="SELECT * FROM ".$this->Entries." WHERE `DateTime`>'$FirstDay' and `DateTime`<'$LastDay'";
			$query.=" AND URL like '".$URL."'";
			$query.=" AND Referer like '".$Referer."'";
			$query.=" AND UserAgent like '".$UserAgent."'";
			$query.=" AND IP like '$IP'";
			$query.=" AND SearchEngine in ($Type)";
			$res=$wpdb->get_results($query);
			if ($res) {
				echo '<table class="widefat">';
				echo '<tr><th>'.__('Date &amp; Time','ZdStatsV2').'</th><th>'.__('URL','ZdStatsV2').'</th><th>'.__('Referer','ZdStatsV2').'</th><th>'.__('IP','ZdStatsV2').'</th></tr>';
				$Count=0;
				foreach ($res as $index => $Value) {
					echo '<tr>';
					echo '<td><a href="?page=ZdStatsV2&amp;detail=Visit&id='.$Value->EntryID.'">'.strftime("%Y-%m-%d &mdash; %H:%M",strtotime($Value->DateTime)).'</a></td>';
					echo '<td><a href="?page=ZdStatsV2&amp;detail=Page&amp;S='.$FirstDay.'&amp;P='.$LastDay.'&amp;URL='.base64_encode($Value->URL).'">'.$Value->URL.'</a></td>';
					echo '<td><a href="'.$Value->Referer.'" title="'.$Value->Referer.'" alt="'.$Value->Referer.'">'.$this->GetSiteName($Value->Referer).'</a></td>';
					echo '<td><a href="?page=ZdStatsV2&amp;detail=IP&amp;S='.$FirstDay.'&amp;P='.$LastDay.'&amp;IP='.base64_encode($Value->IP).'">'.$Value->IP.'</a></td>';
					echo '</tr>';
					$Count++;
				}
				echo '<tr class="footer"><td colspan="3">'.__('Total','ZdStatsV2').'</td><td style="text-align: right;">'.$Count.'</td></tr>';
				echo '</table>';
				echo '<small><a href="?page=ZdStatsV2&amp;detail=export&amp;q='.base64_encode($query).'">'.__('Export as CSV','ZdStatsV2').'</a></small>';
			} else {
				echo '<h3>'.__('Nothing found for these criterias','ZdStatsV2').'</h3>';
			}
		}
		echo '</div>';
	}
	
	function ExportCSV() {
		global $wpdb;
		echo '<div class="wrap" id="zdstats">';
		echo '<h2>'.__('Export as CSV','ZdStatsV2').'</h2>';		
		$query=base64_decode($_GET['q']);
		
		$query=preg_replace("|(SELECT)(.*)(FROM.*)|msi","$1 * $3",$query);
		$res=$wpdb->get_results($query);
		$export=fopen(dirname(__FILE__)."/export.csv","w+");
		$RowCount=0;
		foreach ($res[0] as $key => $element) $Header[]='"'.$key.'"';
		if ($export) {
			 fwrite($export,implode(';',$Header)."\n");
			foreach ($res as $idx => $Row) {
				$Value=array();
				foreach ($Row as $idx => $Element) $Value[]='"'.$Element.'"';
				fwrite($export,implode(';',$Value)."\n");
				$RowCount++;
			}
			fclose($export);
			echo '<p style="text-align: center;">'.sprintf(__('File exported successfully (%s lines returned)','ZdStatsV2'), $RowCount).'</a></p>';
			echo '<p style="text-align: center;"><a href="'.$this->ContentDirectory.'/export.csv">'.__('Download this export','ZdStatsV2').'</a></p>';
		}
		echo '</div>';
	}
	
	function HTMLHead() {
		echo "<!-- ZdStatistics V2 for outgoing links -->\n";
		wp_print_scripts('jquery');
		wp_print_scripts( array( 'sack' ));
		wp_register_script('zd_statsv2_js',$this->ContentDirectory.'/zd.javascript.js.php', array ("jquery","sack"), "2.0");
		wp_print_scripts('zd_statsv2_js');
		echo "<!-- ZdStatistics V2 for outgoing links -->\n";
	}
}

$ZdStatsV2= new ZdStatisticsV2();
?>