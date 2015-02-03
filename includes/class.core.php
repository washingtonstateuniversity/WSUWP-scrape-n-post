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
		 * @access public
		 */
		public $scrape_pages = NULL;
		
		/**
		 * scrape_output class.
		 *
		 * @since 0.1.0
		 * @var class $scrape_output.
		 * @access public
		 */
		public $scrape_output = NULL;
		
		/**
		 * scrape_data class.
		 *
		 * @since 0.1.0
		 * @var class $scrape_data.
		 * @access public
		 */
		public $scrape_data = NULL;
		
		/**
		 * scrape_actions class.
		 *
		 * @since 0.1.0
		 * @var class $scrape_actions.
		 * @access public
		 */
		public $scrape_actions = NULL;
		
		
		/**
		 * message array.
		 *
		 * @since 0.1.0
		 * @var array $message.
		 * @access public
		 */
		public $message = array();
		
		/**
		 * _params from post/get array.
		 *
		 * @since 0.1.0
		 * @var array $_params.
		 * @access public
		 */
		public $_params;

		/**
		 * The slug used to register the shadow content type.
		 *
		 * @var string
		 */
		var $shadow_content_type = 'wsuwp_snp_postshadow';

		/**
		 * The slug used to register the shadow profile type.
		 *
		 * @var string
		 */
		var $shadow_profile_type = 'wsuwp_snp_profile';
		
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
				//include(SCRAPE_PATH . '/includes/phpQuery.php');
				include(SCRAPE_PATH . '/includes/QueryPath/qp.php');
				include(SCRAPE_PATH . '/includes/class.templates.php');// Include scrape_data::	
				include(SCRAPE_PATH . '/includes/class.actions.php');// Include scrape_actions::	
				include(SCRAPE_PATH . '/includes/class.output.php');// Include scrape_output::
				include(SCRAPE_PATH . '/includes/class.data.php');// Include scrape_data::	
				include(SCRAPE_PATH . '/includes/class.pages.php');// Include scrape_pages::
				
				add_action( 'init', array( $this, 'set_default_model' ), 10 );
				
				add_action( 'init', array( $this, 'register_shadow_post_type' ), 11 );
				add_action( 'add_meta_boxes', array( $this, 'add_shadow_post_meta_boxes' ), 11, 1 );

				add_action( 'init', array( $this, 'register_shadow_profile_type' ), 11 );
				add_action( 'add_meta_boxes', array( $this, 'add_shadow_profile_meta_boxes' ), 11, 1 );



				add_action( 'save_post', array( $this, 'save_shadow_post_object' ), 15, 2 );
				add_action( 'transition_post_status', array( $this, 'force_shadow_post_status' ), 15, 3 );
				
				add_action( 'save_post', array( $this, 'save_shadow_profile_object' ), 15, 2 );
				
				add_action( 'init', array( $this, 'process_upgrade_routine' ), 18 );
				
			}
		}

		/**
		 * force post status to be private for the shadow posts.
		 * 
		 * @param string $new_status string of new value
		 * @param string $old_status string of old value
		 * @param object $post Post object
		 * 
		 * @access public
		 */
		public function force_shadow_post_status( $new_status, $old_status,  $post ) {
			if ( $post->post_type == $this->shadow_content_type && $new_status == 'publish' && $old_status  != $new_status ) {
				$post->post_status = 'private';
				wp_update_post( $post );
			}
		}
	
		/**
		 * Initialize install.
		 *
		 * @access public
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
			$db_version = get_option( 'wsuwp_snp_version', '0.0.0' );
	
			// Flush rewrite rules if on an early or non existing DB version.
			if ( version_compare( $db_version, '0.2.0', '<' ) ) {
				flush_rewrite_rules();
			}
	
			update_option( 'wsuwp_snp_version', SCRAPE_VERSION );
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
		 * @access public
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
		 * @access public
		 * 
		 * @todo will be a post typed item later on
		 */
		public function _is_exist($column = '', $value = '') {
			global $wpdb;
			$table_name = $wpdb->prefix . "scrape_n_post_queue";
			$result     = $wpdb->get_results("SELECT * FROM " . $table_name . " WHERE " . $column . " = '" . $value . "'");
			return (count($result) > 0);
		}
		
		/**
		 * Register the shadow content type.
		 *
		 * The point of this content is that a post can be linked back to a url so that it can be 
		 * updated from that url. 
		 */
		public function register_shadow_post_type() {
			$labels = array(
				'menu_name'          => __( 'Scrape N\' Post', 'wsuwp_snp' ),
				'name'               => __( 'Shadow Copy', 'wsuwp_snp' ),
				'singular_name'      => __( 'Shadow Copy', 'wsuwp_snp' ),
				'all_items'          => __( 'All Shadow Copies', 'wsuwp_snp' ),
				'add_new_item'       => __( 'Add Shadow Copy', 'wsuwp_snp' ),
				'edit_item'          => __( 'Edit Shadow Copy', 'wsuwp_snp' ),
				'new_item'           => __( 'New Shadow Copy', 'wsuwp_snp' ),
				'view_item'          => __( 'View Shadow Copy', 'wsuwp_snp' ),
				'search_items'       => __( 'Search Shadow Copies', 'wsuwp_snp' ),
				'not_found'          => __( 'No Shadow Copies found', 'wsuwp_snp' ),
				'not_found_in_trash' => __( 'No Shadow Copies found in trash', 'wsuwp_snp' ),
			);
			$description = __( 'Shadow Copies belonging to a post.', 'wsuwp_snp' );
			$default_slug = 'post_shadow';

			$args = array(
				'labels' => $labels,
				'description' => $description,
				'public' => false,
				'hierarchical' => false,
				'supports' => array (
					'title',
					'revisions'
				),
				'has_archive' => true,
				'rewrite' => array(
					'slug' => $default_slug,
					'with_front' => false
				),
				'exclude_from_search' => true,
				'publicly_queryable' => false,
				'show_in_nav_menus' => false,
				'show_ui' => true,
				'menu_position' => 80,
				'menu_icon' => 'dashicons-share-alt2',
				'show_in_menu'=>true,
			);
			register_post_type( $this->shadow_content_type, $args );
		}

		/**
		 * Add meta boxes used to capture pieces of information.
		 *
		 * @param string $post_type
		 */
		public function add_shadow_post_meta_boxes( $post_type ) {
			if ($post_type == $this->shadow_content_type){   
				//main content area
				add_meta_box( 'wsuwp_snp_url', 'Url', array( $this, 'display_object_url_meta_box' ) , null, 'normal', 'default' );
				add_meta_box( 'wsuwp_snp_html', 'Content', array( $this, 'display_cached_html' ) , null, 'normal', 'default' );
				//side bars
				add_meta_box( 'display_option_post_tie', 'Shadowing Post', array( $this, 'display_option_post_tie' ) , null, 'side', 'default' );
				add_meta_box( 'wsuwp_snp_ignored', 'Skip Link', array( $this, 'display_option_ignore' ) , null, 'side', 'default' );
			}
		}






		/**
		 * Register the shadow profile type.
		 *
		 * This defined how urls are crawled and consumed. 
		 */
		public function register_shadow_profile_type() {
			
			$labels = array(
				'singular_name'      => __( 'Shadow profile', 'wsuwp_snp' ),
				'all_items'          => __( 'All Shadow profiles', 'wsuwp_snp' ),
				'add_new_item'       => __( 'Add Shadow profile', 'wsuwp_snp' ),
				'edit_item'          => __( 'Edit Shadow profile', 'wsuwp_snp' ),
				'new_item'           => __( 'New Shadow profile', 'wsuwp_snp' ),
				'view_item'          => __( 'View Shadow profile', 'wsuwp_snp' ),
				'search_items'       => __( 'Search Shadow profiles', 'wsuwp_snp' ),
				'not_found'          => __( 'No Shadow profiles found', 'wsuwp_snp' ),
				'not_found_in_trash' => __( 'No Shadow profiles found in trash', 'wsuwp_snp' ),
			);
			$description = __( 'Shadow profiles used when crawling sites.', 'wsuwp_snp' );
			$default_slug = 'shadow_profile';

			$args = array(
				'labels' => $labels,
				'description' => $description,
				'public' => false,
				'hierarchical' => true,
				'supports' => array ( 'title', 'page-attributes' ),
				'has_archive' => false,
				'exclude_from_search' => true,
				'publicly_queryable' => false,
				'show_in_nav_menus' => false,
				'show_ui' => true,
				'show_in_menu'=>"edit.php?post_type=".$this->shadow_content_type
			);
			
			register_post_type( $this->shadow_profile_type, $args );
		}


		/**
		 * Add meta boxes used to capture pieces of information for the profile.
		 *
		 * @param string $post_type
		 */
		public function add_shadow_profile_meta_boxes( $post_type ) {
			if ($post_type == $this->shadow_profile_type){   
				add_meta_box( 'wsuwp_snp_post_defaults', 'Defaults', array( $this, 'display_post_defaults_meta_box' ) , null, 'normal', 'default' );
			}
		}


		/**
		 * Display a meta box of the captured html.  This is just displaying the post content, so it's 
		 * not really the meta of the post, but it'll work for our needs
		 *
		 * @param WP_Post $post The full post object being edited.
		 */
		public function display_post_defaults_meta_box( $post ) {
			?>
			<div>
				<p class="description">Ignore this url</p>
				<div class="html radio_buttons">
				<?php 
					$input_name = "wsuwp_spn_post_status";
					$meta_data = get_post_meta( $post->ID, '_'.$input_name, true );
					if($meta_data==""){
						$meta_data="draft";	
					}
					$types = array('draft','publish','pending','future','private');
				?>
				<?php foreach($types as $type):?>
					<input type="radio" name="<?=$input_name?>" value="<?=$type?>" id="<?=$input_name?><?=$type?>" <?=checked($meta_data,$type)?>/>
					<label for="<?=$input_name?><?=$type?>"><?=$type?></label>
				<?php endforeach;?>
				</div>
				<div class="clear"></div>
			</div>
			<?php
		}


/*
  'post_status'    => [ 'draft' | 'publish' | 'pending'| 'future' | 'private' | custom registered status ] // Default 'draft'.
  'post_type'      => [ 'post' | 'page' | 'link' | 'nav_menu_item' | custom post type ] // Default 'post'.
  'post_author'    => [ <user ID> ] // The user ID number of the author. Default is the current user ID.
  'ping_status'    => [ 'closed' | 'open' ] // Pingbacks or trackbacks allowed. Default is the option 'default_ping_status'.
  'post_parent'    => [ <post ID> ] // Sets the parent of the new post, if any. Default 0.
  'menu_order'     => [ <order> ] // If new post is a page, sets the order in which it should appear in supported menus. Default 0.
  'to_ping'        => [ <string> ] // Space or carriage return-separated list of URLs to ping. Default empty string.
  'pinged'         => [ <string> ] // Space or carriage return-separated list of URLs that have been pinged. Default empty string.
  'post_password'  => [ <string> ] // Password for post, if any. Default empty string.
  'post_excerpt'   => [ <string> ] // For all your post excerpt needs.
  'post_date'      => [ Y-m-d H:i:s ] // The time post was made.
  'comment_status' => [ 'closed' | 'open' ] // Default is the option 'default_comment_status', or 'closed'.
  'post_category'  => [ array(<category id>, ...) ] // Default empty.
  'tags_input'     => [ '<tag>, <tag>, ...' | array ] // Default empty.
  'tax_input'      => [ array( <taxonomy> => <array | string> ) ] // For custom taxonomies. Default empty.
  'page_template'  => [ <string> ] // Requires name of template file, eg template.php. Default empty.
*/









		/**
		 * Display a meta box of the captured html.  This is just displaying the post content, so it's 
		 * not really the meta of the post, but it'll work for our needs
		 *
		 * @param WP_Post $post The full post object being edited.
		 */
		public function display_cached_html( $post ) {
			?>
			<div id="wsuwp-snp-display-content">
				<p class="description">Html from url</p>
				<p class="description"><strong>note:</strong> edits to this will not be saved. This is purely informational only.</p>
				<div class="html">
					<label for="wsuwp-snp-html">Last captured html:</label><br/>
					<textarea id="wsuwp-snp-html" style="width:100%; min-height:500px;"><?=$post->content?></textarea>
					<input type="hidden" name="content" value="<?=$post->content?>" />
				</div>
				<div class="clear"></div>
			</div>
			<?php
		}

		/**
		 * Which post should this be feeding?
		 *
		 * @param WP_Post $post The full post object being edited.
		 */
		public function display_option_post_tie( $post ) {
			$tiedTo = get_post_meta( $post->ID, '_wsuwp_spn_tied_post_id', true );
			?>
			<div id="wsuwp-snp-display-ignore">
				<p class="description">This is the post that the shadow post feeds to when requested</p>
				<div class="html">
					<?php wp_dropdown_pages( array(
						'depth'                 => 0,
						'child_of'              => 0,
						'selected'              => $tiedTo,
						'echo'                  => 1,
						'name'                  => 'wsuwp_spn_tied_post_id',
						'show_option_none'      => 'un-tied', // string
						'show_option_no_change' => null, // string
						'option_none_value'     => "", // string
					) ); ?> 
				</div>
				<div class="clear"></div>
			</div>
			<?php
		}

		/**
		 * Should this shadow be used for an ignore list?
		 *
		 * @param WP_Post $post The full post object being edited.
		 */
		public function display_option_ignore( $post ) {
			$input_name = "wsuwp_spn_ignored";
			$ignore = get_post_meta( $post->ID, '_'.$input_name, true );
			?>
			<div id="wsuwp-snp-display-ignore">
				<p class="description">Ignore this url</p>
				<div class="html radio_buttons">
					<input type="radio" name="<?=$input_name?>" value="1" id="<?=$input_name?>1" <?=checked($ignore,1)?>/>
					<label for="<?=$input_name?>1">Yes</label>
					
					<input type="radio" name="<?=$input_name?>" value="0" id="<?=$input_name?>2" <?=checked($ignore,0)?> />
					<label for="<?=$input_name?>2">No</label>
				</div>
				<div class="clear"></div>
			</div>
			<?php
		}


		/**
		 * Display a meta box to capture the URL for an object.
		 *
		 * @param WP_Post $post
		 */
		public function display_object_url_meta_box( $post ) {
			$object_url = get_post_meta( $post->ID, '_wsuwp_spn_url', true );
			$object_url = ! empty( $object_url ) ? esc_url( $object_url ) : '';
			$http_status = get_post_meta( $post->ID, '_wsuwp_spn_last_http_status', true );
			$http_status = ! empty( $http_status ) ? $http_status : 'not checked';
			?>
			<div id="wsuwp-snp-display">
				<div class="html">
					<label for="wsuwp-spn-url">Tracked URL:</label>
					<input type="text" class="widefat" id="wsuwp-spn-url" name="wsuwp_spn_url" value="<?=$object_url?>" />
					<p class="description">Note, altering the url will cause the html to get reloaded.</p>
				</div>
				<div class="wsuwp_spn_last_http_status">
					<input type="hidden" name="wsuwp_spn_last_http_status" value="<?=($http_status!="not checked"?$http_status:"")?>"/>
					<b>Last checked header status:</b> <i style="color:<?=($http_status=="200"?"green":"red")?>" > <?=$http_status?> </i>
				</div>
				<div class="clear"></div>
			</div>
			<?php
		}
		/**
		 * Assign a URL to an object when saved through the object's meta box.
		 *
		 * @param int     $post_id The ID of the post being saved.
		 * @param object  $post The post being saved.
		 */
		public function save_shadow_post_object( $post_id, $post ) {
			/*
			`url` MEDIUMINT(9),
			`tied_post_id` MEDIUMINT(9),
			`ignored` BIT(1) NOT NULL DEFAULT 0,
			`last_http_status` MEDIUMINT(9),
			`type` VARCHAR(255) DEFAULT NULL,
			*/
			
			if ( isset( $_POST['wsuwp_spn_url'] ) ) {
				if ( empty( trim( $_POST['wsuwp_spn_url'] ) ) ) {
					delete_post_meta( $post_id, '_wsuwp_spn_url' );
				} else {
					update_post_meta( $post_id, '_wsuwp_spn_url', esc_url_raw( $_POST['wsuwp_spn_url'] ) );
				}
			}
			
			if ( isset( $_POST['wsuwp_spn_tied_post_id'] ) ) {
				if ( empty( trim( $_POST['wsuwp_spn_tied_post_id'] ) ) ) {
					delete_post_meta( $post_id, '_wsuwp_spn_tied_post_id' );
				} else {
					update_post_meta( $post_id, '_wsuwp_spn_tied_post_id', $_POST['wsuwp_spn_tied_post_id'] );
				}
			}
			
			if ( isset( $_POST['wsuwp_spn_ignored'] ) ) {
				if ( empty( trim( $_POST['wsuwp_spn_ignored'] ) ) ) {
					delete_post_meta( $post_id, '_wsuwp_spn_ignored' );
				} else {
					update_post_meta( $post_id, '_wsuwp_spn_ignored', $_POST['wsuwp_spn_ignored']);
				}
			}			

			if ( isset( $_POST['wsuwp_spn_last_http_status'] ) ) {
				if ( empty( trim( $_POST['wsuwp_spn_last_http_status'] ) ) ) {
					delete_post_meta( $post_id, '_wsuwp_spn_last_http_status' );
				} else {
					update_post_meta( $post_id, '_wsuwp_spn_last_http_status', $_POST['wsuwp_spn_last_http_status'] );
				}
			}					
			
			if ( isset( $_POST['wsuwp_spn_type'] ) ) {
				if ( empty( trim( $_POST['wsuwp_spn_type'] ) ) ) {
					delete_post_meta( $post_id, '_wsuwp_spn_type' );
				} else {
					update_post_meta( $post_id, '_wsuwp_spn_type', $_POST['wsuwp_spn_type'] );
				}
			}		
			return;
		}
		
		/**
		 * Save a profiles meta saved through the object's meta box.
		 *
		 * @param int     $post_id The ID of the post being saved.
		 * @param object  $post The post being saved.
		 */
		public function save_shadow_profile_object( $post_id, $post ) {
			if ( isset( $_POST['wsuwp_spn_type'] ) ) {
				if ( empty( trim( $_POST['wsuwp_spn_type'] ) ) ) {
					delete_post_meta( $post_id, '_wsuwp_spn_type' );
				} else {
					update_post_meta( $post_id, '_wsuwp_spn_type', $_POST['wsuwp_spn_type'] );
				}
			}		
			return;
		}
		
		
	}
	global $scrape_core;
	$scrape_core = new scrape_core();
}
?>