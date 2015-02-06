<?php
/**
 * @todo Still needs a good refactor
 * - actions should be moved and ?page should be detected?
 */
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
if ( ! class_exists( 'scrape_pages' ) ) {
	class scrape_pages {
		public $dompdf = NULL;
		public $message = array();
		public $title = '';
	
		/**
		 * constructor
		 *
		 * @global class $_params
		 * @global class $scrape_actions
		 */
		function __construct() {
			global $_params,$scrape_actions;
			if (is_admin()) {
				if (isset($_params)) {
					if (isset($_params['scrape_save_option'])) {// Check if option save is performed
						add_action('init', array( $this, 'update_options' ));// Add update option action hook
					}
					if (isset($_params['scrape_findlinks'])) {// Check if pdf export is performed
						add_action('init', array($scrape_actions, 'findlinks' ));// Add export hook
					}
					if (isset($_params['scrape_test_crawler'])) {// Check if pdf export is performed
						add_action('init', array($scrape_actions, 'test_crawler' ));// Add export hook
					}
				}
				add_action('admin_init', array( $this, 'admin_init' ));
				add_action('admin_menu', array( $this, 'admin_menu' ), 11);
			}
			if (isset($_GET['scrape_dl'])) {// Check if post download is performed
				add_action('init', array( $this, 'download_post' ));// Add download action hook
			}
			if (isset($_GET['scrape_post_dl'])) {// Check if single post download is performed
				add_action('init', array( $this, 'download_posts' ));// Add download action hook
			}
		}
		/**
		 * Initailize plugin admin part
		 *
		 * @global class $wp_scripts
		 */
		public function admin_init() {
			global $wp_scripts;
			// Enque style and script		
			wp_enqueue_script('jquery-ui-core');
			wp_enqueue_script('jquery-ui-datepicker');
			wp_enqueue_script('jquery-ui-button');	
			wp_enqueue_script('jquery-ui-tabs');
			wp_enqueue_script('jquery-ui-dialog');

			// get registered script object for jquery-ui
			$ui = $wp_scripts->query('jquery-ui-core');
		 
			// tell WordPress to load the Smoothness theme from Google CDN
			$url = "//ajax.googleapis.com/ajax/libs/jqueryui/{$ui->ver}/themes/smoothness/jquery-ui.min.css";
			wp_enqueue_style('jquery-ui-smoothness', $url, false, null);
	
			wp_enqueue_script('scrape-js', SCRAPE_URL . 'js/scrape.custom.js', array('jquery'), '', 'all');
			wp_enqueue_style('scrape-style', SCRAPE_URL . 'css/style.css', false, '1.9.0', 'all');
		}
		
		/**
		 * Add plugin menu
		 */
		public function admin_menu() {
			global $scrape_core;
			// Register sub-menu
			$parent_slug = "edit.php?post_type=".SHADOW_POST_TYPE_POST;

			add_submenu_page($parent_slug, _('Crawl'), _('Crawl'), 'manage_options', 'scrape-crawler', array( $this, 'crawler_page' ));

			add_submenu_page($parent_slug, _('Settings'), _('Settings'), 'manage_options', SCRAPE_BASE_NAME, array( $this, 'option_page' ));
		}
	

	
		/**
		 * Display "Crawler" pages
		 *
		 * @global class $scrape_data
		 */
		public function crawler_page() {
			global $scrape_data;
			include(SCRAPE_PATH . '/includes/views/lists/class.crawl_list.php');
			$wp_list_table = new crawl_list();
			$wp_list_table->prepare_items();
			ob_start();
			$wp_list_table->display();
			$data['table']   = ob_get_clean();
			$data['message'] = $this->get_message();
			$data['option_url']    = "";//$tool_url;
			
			$this->view(SCRAPE_PATH . '/includes/views/crawl_list.php', $data);
		}
	

		/**
		 * get the list of shadows
		 *
		 * @global class $scrape_data
		 */
		public function url_to_post() {
			global $scrape_data;
			include(SCRAPE_PATH . '/includes/views/lists/class.crawl_list.php');
			$wp_list_table = new crawl_list();
			$wp_list_table->prepare_items();
			ob_start();
			$wp_list_table->display();
			$data['table']   = ob_get_clean();
			$data['message'] = $this->get_message();
			$data['option_url']    = "";//$tool_url;
			
			$this->view(SCRAPE_PATH . '/includes/views/crawl_list.php', $data);
		}


			
		/*-------------------------------------------------------------------------*/
		/* -Option- 															   */
		/*-------------------------------------------------------------------------*/
		/**
		 * Update plugin option
		 *
		 * @global class $_params
		 */
		public function update_options() {
			global $_params;
			$options = $_params;
			update_option('scrape_options', $options);
		}
		/**
		 * Display "Option" page
		 *
		 * @global class $scrape_data
		 */
		public function option_page() {
			global $scrape_data;
			// Set options
			$data['options']   = $scrape_data->get_options();
			$data['scrape_options']   = $data['options'];
			
			$data['post_type'] = get_post_types( array(), 'names', 'and' );
			// Get templates
			$data['templates'] = "";
			// Display option form
			$this->view(SCRAPE_PATH . '/includes/views/options.php', $data);
		}
	
	
	
	
		/*-------------------------------------------------------------------------*/
		/* -General- 															   */
		/*-------------------------------------------------------------------------*/
		/**
		 * Return falsh message
		 *
		 * @global class $scrape_core
		 */
		public function get_message() {
			global $scrape_core;
			if (!empty($scrape_core->message)) {
				$arr = $scrape_core->message;
				$message = "<div id='message' class='{$arr['type']}'><p>{$arr['message']}</p></div>";
				$scrape_core->message=NULL;
				return $message;
			}
		}

		/**
		 * Return query filter
		 *
		 * @param string $file
		 * @param array $data
		 * @param boolean return
		 */
		public function view($file = '', $data = array(), $return = false) {
			if (count($data) > 0) {
				extract($data);
			}
			if ($return) {
				ob_start();
				include($file);
				return ob_get_clean();
			} else {
				include($file);
			}
		}
		/**
		 * redirect as needed
		 *
		 * @param string $page
		 * @param string $scheme
		 */
		public function foward($page,$scheme='http') {  //fix the header issue
			if ( function_exists('admin_url') ) {
				wp_redirect( admin_url('admin.php?page='.$page, $scheme) );
			} else {
				wp_redirect( get_option('siteurl') . '/wp-admin/' . 'admin.php?page='.$page );
			}
		}

	}
	global $scrape_pages;
	$scrape_pages = new scrape_pages();
}