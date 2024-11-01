=== ZdStatistics ===
Contributors: ZenDreams
Donate link: http://www.zen-dreams.com/en/zdstats
Tags: Statistics
Requires at least: 2.5.0
Tested up to: 2.6.2
Stable tag: 2.0.1

ZdStatistics is a flexible and powerfull statistics plugin for wordpress

== Description ==


ZdStatistics is a wordpress plugin allowing you to trace your visitors. This is not a simple statistics plugin, it is very flexible and dynamic. This means you can update your filters by yourself and therefore by as realistic as possible.

Here is a lsit of functions :

* Display a summary of visits (week, month, trimester, semester and year)
* Display Pageviews and visitors with daily precision
* Referring pages
* Used keywords
* Localization of your visitors
* Browser / OS
* Outgoing links

The statistics are collected and instantly processed, or not, as you can decide this using an option. You can also separate feeds from real pageviews, exclude some IPs from being collected (@home, @work, @school, etc...) and choose to collect robots pageviews or not.

== Installation ==

1. Setup is very simple, just unzip the archive in your wp-content/plugins folder
2. Download the GeoLiteCity.dat from Maxmind.com and place it in the geoip folder (in fact, I am not sure if it is redistruable, so you will have to download it yourself)
3. You can now activate the plugin and change the options.

== Frequently Asked Questions ==

= I lost geolocalization feature after doing an automatic upgrade. =
Yes, this is due to the fact that wordpress automatic deletes the directory in order to place the new version on your wordpress.
To re-activate it, you will have to upload GeoLiteCity.dat to your wp-content/plugins/zdstats/geoip folder

== Screenshots ==

1. General statistics view

== Changelog ==

= v2.0.1 =
* Fixed css style bug
* GeoLiteCity.dat can now be located outside the plugin, you'll never loose it again after plugin upgrade
* Daily Stats will now display summary of page viewed during the day
* Some typos in French translation

= v2.0 =
* Completely re-written code.
* Spam check using spam dns lookup
* Better blacklist format
* Charts change from OpenFlashChart to Google Vizualisation
* Geolocalization now includes Google Maps display
* Top ten is now top X (5, 10, 20 or 50)

= v1.1.4 =
* Added Top ten page (pages, referers, keywords)
* Added function to view all comments by IP
* Added load time and sql queries statistics

= v1.1.3 =
* Added a function you can call from within your theme to display pageviews : zd_stats_Display_Pageviews($URL);
* Added a widget to display some stats on your sidebars
* Added the possibility to filter pageviews according to user level
* Bug : Wrong auto insertion of IP Filter, might have cause the filter to not work properly
* Bug : Accents in geolocalisation corrected

= v1.1.2 =
* Added error checking for geolocalization file after an update of the plugin
* Added link for any IP displayed to see history for it
* Added link for any Time displayed to see corresponding pageview
* Added links to google maps from within the Geolocalization main page.
* Corrected Time calculation bug, **PHP4 is now supported** !

