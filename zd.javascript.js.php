<?php
include_once("../../../wp-load.php");

if ( !defined('WP_CONTENT_URL') ) define( 'WP_CONTENT_URL', get_option('siteurl') . '/wp-content');
$lngt=strlen(get_option('siteurl'));
$PLUGINDIR=WP_CONTENT_URL.'/plugins/';
$t=explode('/',__FILE__);
if (count($t)==1) $t=explode("\\",__FILE__);
$PLUGINDIR.=$t[count($t)-2];

?>
function visit_url(url) {
	var mysack = new sack("<?php echo $PLUGINDIR;?>/zd.wp.ajax.php" );
	 mysack.execute = 1;
	mysack.method = 'POST';
	mysack.setVar( "url", url );
	mysack.onError = function() { alert('Ajax error in outlink collection' )};
	mysack.runAJAX();
}
 jQuery(document).ready(function() {
	jQuery("a[href^=http]").not("[@href*=<?php echo get_bloginfo('url'); ?>]").mousedown(function(event) { 
		if (jQuery.browser.msie==true) {
			if (((event.button==4)&&(jQuery.browser.version>=7))||(event.button==1)) {
				visit_url(this.href);
			}
		} else {
			if ((event.button==0)||(event.button==1)) {
				visit_url(this.href);
			}
		}
		return true;
	})
});