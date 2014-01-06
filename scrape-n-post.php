<?php
/*
Plugin Name: Scrape-N-Post
Version: 0.1.1
Plugin URI: #
Description: Import content form your old site with easy
Author: Jeremy Bass
Author URI: #
*/

define('SCRAPE_NAME', 'Scrape-N-Post');
define('SCRAPE_BASE_NAME', 'scrape-n-post');
define('SCRAPE_VERSION', '0.1.1');
define('SCRAPE_URL', plugin_dir_url(__FILE__));
define('SCRAPE_PATH', plugin_dir_path(__FILE__));
define('SCRAPE_CACHE_PATH', SCRAPE_PATH . 'cache/');
define('SCRAPE_CACHE_URL', SCRAPE_URL . 'cache/');

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
	$scrape_core = NULL;
	class scrapeNpostLoad {
		public function __construct() {
			
			/*
			 * Initiate the plug-in.
			 */
			include(SCRAPE_PATH . '/includes/class.core.php');// Include core
			register_activation_hook(__FILE__,  'scrape_N_post_initializer');// Install
			register_deactivation_hook(__FILE__,  'scrape_N_post_remove');// Uninstall
		}
	}

	// Set option values
	function scrape_N_post_initializer() {
		global $scrape_core;
		$scrape_core->install_init();		// Call plugin initializer
	}
	// Unset option values
	function scrape_N_post_remove() {
		//delete_option('scrape_options');	// Delete plugin options
	}
	global $scrapeNpostLoad;
	$scrapeNpostLoad = new scrapeNpostLoad();
}

?>