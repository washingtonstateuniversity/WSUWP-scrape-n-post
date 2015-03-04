<?php
/**
 * Core methods for the scraper.
 *
 * @link URL
 * @since 0.1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
if ( ! class_exists( 'scrape_core' ) ) {
	class scrape_core {
		
		/**
		 * scrape_pages class.
		 *
		 * @since 0.1.0
		 * @var class $scrape_pages.
		 */
		public $scrape_pages = NULL;

		/**
		 * scrape_data class.
		 *
		 * @since 0.1.0
		 * @var class $scrape_data.
		 */
		public $scrape_data = NULL;

		/**
		 * scrape_actions class.
		 *
		 * @since 0.1.0
		 * @var class $scrape_actions.
		 */
		public $scrape_actions = NULL;

		/**
		 * shadow_profile class.
		 *
		 * @since 0.1.0
		 * @var class $shadow_profile.
		 */
		public $shadow_profile = NULL;
		
		/**
		 * shadow_post class.
		 *
		 * @since 0.1.0
		 * @var class $shadow_post.
		 */
		public $shadow_post = NULL;

		/**
		 * message array.
		 *
		 * @since 0.1.0
		 * @var array $message.
		 */
		public $message = array();
		
		/**
		 * _params from post/get array.
		 *
		 * @since 0.1.0
		 * @var array $_params.
		 */
		public $_params;
	
		/**
		 * Add template table.
		 * 
		 * @global array $_params
		 * 
		 * @todo refactor with conversion to a post typed object will be needed
		 * @access private
		 */
		function __construct() {
			global $_params;
			
			$_params = $_REQUEST; // this needs to get validated and noonced and what not

			if (is_admin()) {
				include(SCRAPE_PATH . '/includes/QueryPath/qp.php');
				include(SCRAPE_PATH . '/includes/class.templates.php');// Include scrape_data::	
				include(SCRAPE_PATH . '/includes/class.actions.php');// Include scrape_actions::
				include(SCRAPE_PATH . '/includes/class.data.php');// Include scrape_data::	
				include(SCRAPE_PATH . '/includes/class.pages.php');// Include scrape_pages::
				
				add_action( 'init', array( $this, 'set_default_model' ), 10 );
				
				include(SCRAPE_PATH . '/includes/views/class.shadow_post.php');// Include shadow_post::
				include(SCRAPE_PATH . '/includes/views/class.shadow_profile.php');// Include shadow_profile::

				add_action( 'init', array( $this, 'process_upgrade_routine' ), 18 );
				
			}
		}

		/**
		 * Initialize install.
		 */
		public function install_init() {
			// Add database table
			$this->_add_table();
		}
		
		/**
		 * Make sure everything is good to go as the plugin is run.
		 * 
		 * @global class $scrape_data
		 * 
		 * @todo refactor with conversion to a post typed object will be needed
		 * @access private
		 */
		public function set_default_model() {
			global $scrape_data;
			
			$options = $scrape_data->get_options(); // after _param validation just in case
				
			//seems that if xdebug is in use then it'll kill something at 100 when it shouldn't have
			if(isset($options['xdebug_fix']) && $options['xdebug_fix']==1){
				ini_set('xdebug.max_nesting_level', 1000); // should quitely fail if no xdebug
			}
			
			if(isset($options['timeout_limit']) && (int)$options['timeout_limit']>-1){
				set_time_limit((int)$options['timeout_limit']);
			}
			if(isset($options['memory_limit']) && (int)$options['memory_limit']>-2){
				ini_set('memory_limit', (int)$options['memory_limit']);
			}
		}
		
		/**
		 * Process any upgrade routines between versions or on initial activation.
		 */
		public function process_upgrade_routine() {
			$db_version = get_option( SHADOW_KEY.'_version', '0.0.0' );
	
			// Flush rewrite rules if on an early or non existing DB version.
			if ( version_compare( $db_version, '0.2.0', '<' ) ) {
				flush_rewrite_rules();
			}
	
			update_option( SHADOW_KEY.'_version', SCRAPE_VERSION );
		}

		/**
		 * Flush the rewrite rules on the site.
		 *
		 * is an expensive operation so it should only be used when absolutely necessary.
		 */
		public function flush_rewrite_rules() {
			flush_rewrite_rules();
		}

		/**
		 * Add template table.
		 * 
		 * @global class $wpdb
		 * @global class $scrape_data
		 * 
		 * @todo will be a post typed item later on
		 */
		public function _add_table() {
			global $wpdb,$scrape_data;
			
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			
			// Construct queue		
			$table_name = $wpdb->prefix . "scrape_n_post_queue";
			$sql        = "
			#DROP TABLE IF EXISTS `{$table_name}`;
			CREATE TABLE `{$table_name}`  (
				`target_id` MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
				`post_id` MEDIUMINT(9),
				`ignore` BIT(1) NOT NULL DEFAULT 0,
				`url` TEXT NOT NULL,
				`referrer` TEXT,
				`match_level` TEXT,
				`http_status` MEDIUMINT(9),
				`type` VARCHAR(255) DEFAULT NULL,
				`last_imported` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
				`last_checked` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
				`added_date` DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
			UNIQUE KEY id (target_id)
			);";
			// Import wordpress database library
			dbDelta($sql);
			
			// Construct templates		
			$table_name = $wpdb->prefix . "scrape_n_post_crawler_templates";
			$sql        = "
			#DROP TABLE IF EXISTS `{$table_name}`;
			CREATE TABLE `{$table_name}`  (
				`template_id` MEDIUMINT(9) NOT NULL AUTO_INCREMENT,
				`pattern` MEDIUMINT(9),
				`template_name` varchar(50) NOT NULL,
				`template_description` text,
				`create_by` mediumint(9) NOT NULL,
				`create_date` datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
			UNIQUE KEY id (template_id)
			);";
			// Import wordpress database library
			dbDelta($sql);			
			
			// Save version
			add_option('scrape_db_version', SCRAPE_VERSION);
			// Add plugin option holder
			$options = $scrape_data->get_options();
			add_option('scrape_options', $options, '', 'yes');
			// Define and create required directories
			$required_dir = array(
				'modules' => SCRAPE_PATH . '/scrape-content/modules',
				'http-cache' => SCRAPE_PATH . '/scrape-content/http-cache'
			);
			foreach ($required_dir as $dir){
				if( !is_dir($dir) ){
					 @mkdir($dir, 0777);
				}
			}
			
			
		}

		/**
		 * Check if entry already exist.
		 * 
		 * @param string $column
		 * @param string $value
		 * 
		 * @todo will be a post typed item later on
		 */
		 //@todo note that the 'post_type'  => 'any' would fail.  the query was not returning anything. BUG?
		public function _is_exist( $column = '', $value = '', $post_type = SHADOW_POST_TYPE_POST ) {
			global $wpdb;
			$args = array(
				'meta_query' => array(
					array(
						'key' => '_'.SHADOW_KEY.'_'.$column,
						'value' => $value,
					)
				),
				'post_status'=> 'any',
				'post_type'  => $post_type,
				'nopaging'   => true
			);
			$posts = get_posts($args);
			return (count($posts) > 0);
		}

		/**
		 * Build a jQuery UI style radio group
		 *
		 * @param array $options
		 */
		public function make_radio_html($options=array()){
			if(empty($options)){
				return "";	
			}
			$types = $options['types'];
			$meta_data = $options['meta_data'];
			$input_name = $options['input_name'];
			$description = $options['description'];
			$title = $options['title'];
			?>
				<?php if($title!=""):?><p><?=$title?></p><?php endif;?>
				<div class="html radio_buttons">
					<?php foreach($types as $name=>$value):?>
						<input type="radio" name="<?=$input_name?>" value="<?=$value?>" id="<?=$input_name?>_<?=$value?>" <?=checked($meta_data,$value)?>/>
						<label for="<?=$input_name?>_<?=$value?>"><?=$name?></label>
					<?php endforeach;?>
				</div>
				<?php if($description!=""):?><p class="description"><?=$description?></p><?php endif;?>

			<?php
		}
	}
	global $scrape_core;
	$scrape_core = new scrape_core();
}
?>