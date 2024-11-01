<?php

if (!isset($_POST['rndval'])) die();

include_once("../../../wp-load.php");

$timezone=get_option("gmt_offset");
if ($timezone>=0) $timezone=-$timezone;
else $timezone="+".-$timezone;
$oldTZ=getenv("TZ");
putenv('TZ=Etc/GMT'.$timezone);
$IP=$_SERVER['REMOTE_ADDR'];
$PageViewed=$_POST['url'];
$SessionID=session_id();
$IP=$_SERVER['REMOTE_ADDR'];
$Referer=$_SERVER['HTTP_REFERER'];
$UA=$_SERVER['HTTP_USER_AGENT'];
$DT=strftime("%Y-%m-%d %H:%M:%S");
$HostLen=strlen($_SERVER['HTTP_HOST']);
$ProtocolLen=(isset($_SERVER['HTTPS'])) ? 8 : 7;
$Length=$HostLen+$ProtocolLen;
$Ref=substr($Referer,$Length);
$Date=strftime("%Y-%m-%d %H:%M:%S",strtotime("-30 min"));
putenv("TZ=$oldTZ");

// Check if this IP has viewed referring page within the last 30 minutes using the same Session ID. Will only record outgoing links if this is true
$check="SELECT count(*) as c FROM ".$wpdb->prefix."zd_stats_entry WHERE IP='$IP' and `DateTime`>'$Date' and SessionID='$SessionID' and SearchEngine in (0,999) and URL='$Ref'";
$res=$wpdb->get_row($check);

if ($res->c) {
	$query="INSERT INTO ".$wpdb->prefix."zd_stats_entry (SessionID, IP, Referer, UserAgent, DateTime, URL, SearchEngine, SqlQueries, LoadTime) VALUES('$SessionID', '$IP', '$Referer', '$UA', '$DT', '$PageViewed', 3, 0, 0)";
	$wpdb->query($query);
}
echo '';
?>