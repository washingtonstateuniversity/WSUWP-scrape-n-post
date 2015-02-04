<?php
/*
Plugin Name: Scrape-N-Post
Version: 0.1.1
Plugin URI: http://web.wsu.edu/wordpress/plugins/scrape-n-post/
Description: Import content form your old site with ease
Author: washingtonstateuniversity, jeremyBass
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/



/* things still to do
[ ]-Needs to set a intermediary on url to post
[X]-add on crawl post creation
[•]-make page that build the mapping for the post creation-going to be templates
[ ]-Put in the AJAX stuff
[•]-Add webshot for previews of the urls
[ ]-POST/GET to $_param validation
[ ]-cronjob support for active crawl and snync
*/
if ( ! class_exists( 'scrapeNpostLoad' ) ) {
	
	define('SCRAPE_NAME', 'Scrape-N-Post');
	define('SCRAPE_BASE_NAME', 'scrape-n-post');
	define('SCRAPE_VERSION', '0.1.1');
	
	define('SCRAPE_URL', plugin_dir_url(__FILE__));
	define('SCRAPE_PATH', plugin_dir_path(__FILE__));
	define('SCRAPE_CACHE_PATH', SCRAPE_PATH . 'cache/');
	define('SCRAPE_CACHE_URL', SCRAPE_URL . 'cache/');

	/**
	 * The slug used to register the shadow key used for meta data and such.
	 *
	 * @cons string
	 */
	define('SHADOW_KEY', 'wsuwp_snp');
	

	/**
	 * The slug used to register the shadow post type.
	 *
	 * @cons string
	 */
	define('SHADOW_POST_TYPE_POST', SHADOW_KEY.'_post');
	
	
	/**
	 * The slug used to register the shadow profile type.
	 *
	 * @cons string
	 */
	define('SHADOW_POST_TYPE_PROFILE', SHADOW_KEY.'_profile');

	$scrape_core = NULL;
	class scrapeNpostLoad {
					
		/*
		 * Initiate the plug-in.
		 */
		public function __construct() {
			include(SCRAPE_PATH . '/includes/class.core.php');// Include core
			register_activation_hook(__FILE__,  '_activation');// Install
			register_deactivation_hook(__FILE__,  '_deactivation');// turn off cron jobs
		}
	}
	/**
	 * Get the plugin into an active state
	 * 
	 * @global class $scrape_core
	 *
	 * @access public
	 */
	function _activation() {
		global $scrape_core;
		$scrape_core->install_init();		// Call plugin initializer
	}
	/**
	 * Get the plugin into an inactive state
	 *
	 * @access public
	 */
	function _deactivation() {
		//trun off the cron jobs
	}
	global $scrapeNpostLoad;
	$scrapeNpostLoad = new scrapeNpostLoad();
}

?>