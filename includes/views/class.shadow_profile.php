<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
if ( ! class_exists( 'shadow_profile' ) ) {
	class shadow_profile {
	
		/**
		 * constructor
		 */
		function __construct() {
			add_action( 'init', array( $this, 'register_shadow_profile_type' ), 11 );
			add_action( 'add_meta_boxes', array( $this, 'add_shadow_profile_meta_boxes' ), 11, 1 );
			add_action( 'save_post', array( $this, 'save_shadow_profile_object' ), 15, 2 );
			
		}
		/**
		 * Register the shadow profile type.
		 *
		 * This defined how urls are crawled and consumed.
		 */
		public function register_shadow_profile_type() {
			$labels = array(
				'singular_name'      => __( 'Shadow profile', SHADOW_KEY ),
				'all_items'          => __( 'All Shadow profiles', SHADOW_KEY ),
				'add_new_item'       => __( 'Add Shadow profile', SHADOW_KEY ),
				'edit_item'          => __( 'Edit Shadow profile', SHADOW_KEY ),
				'new_item'           => __( 'New Shadow profile', SHADOW_KEY ),
				'view_item'          => __( 'View Shadow profile', SHADOW_KEY ),
				'search_items'       => __( 'Search Shadow profiles', SHADOW_KEY ),
				'not_found'          => __( 'No Shadow profiles found', SHADOW_KEY ),
				'not_found_in_trash' => __( 'No Shadow profiles found in trash', SHADOW_KEY ),
			);
			$description = __( 'Shadow profiles used when crawling sites.', SHADOW_KEY );
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
				'show_in_menu'=>"edit.php?post_type=".SHADOW_POST_TYPE_POST
			);
			
			register_post_type( SHADOW_POST_TYPE_PROFILE , $args );
		}


		/**
		 * Add meta boxes used to capture pieces of information for the profile.
		 *
		 * @param string $post_type
		 */
		public function add_shadow_profile_meta_boxes( $post_type ) {
			if ($post_type == SHADOW_POST_TYPE_PROFILE){   
				add_meta_box( SHADOW_KEY.'_post_defaults', 'Defaults', array( $this, 'display_post_defaults_meta_box' ) , null, 'normal', 'default' );
			}
		}


		/**
		 * Display a meta box of the captured html.  This is just displaying the post content, so it's 
		 * not really the meta of the post, but it'll work for our needs
		 *
		 * @global class $scrape_core
		 *
		 * @param WP_Post $post The full post object being edited.
		 */
		public function display_post_defaults_meta_box( $post ) {
			global $scrape_core;
			?>
			<div>
				<?php
				$input_name = SHADOW_KEY."_post_status";
				$meta_data = get_post_meta( $post->ID, '_'.$input_name, true );
				$scrape_core->make_radio_html(array(
					'types'=>get_post_statuses(),
					'input_name'=>$input_name,
					'meta_data'=>$meta_data!=""?$meta_data:"draft",
					'description'=>'set this to what you want a post to be when it is created.',
					'title'=>'Post status'
				))?>
				<hr/>
				
				
				<?php
					$input_name = SHADOW_KEY."_post_type";
					$meta_data = get_post_meta( $post->ID, '_'.$input_name, true );
				?>
				<label> <?=_e( "Use Post Type" )?> </label>
				<select name="<?=$input_name?>">
				<?php foreach(get_post_types( array(), 'names', 'and' ) as $key=>$val): ?>
					<option <?=selected($key, ($meta_data!=""?$meta_data:"post"))?> value="<?=$key?>"> <?=$val?> </option>
				<?php endforeach; ?>
				</select>
				<hr/>
				
				<?php
					$input_name = SHADOW_KEY."_post_author";
					$meta_data = get_post_meta( $post->ID, '_'.$input_name, true );
				?>
				<label> <?=_e( "Use Author for Posts" )?> </label>
				<select name="<?=$input_name?>">
				<?php foreach( get_users( array( 'fields'=>array( 'ID','display_name' ) ) ) as $user ): ?>
					<option <?=selected($user->ID, $meta_data)?> value="<?=$user->ID?>"> <?=$user->display_name?> </option>
				<?php endforeach; ?>
				</select>				
				
				<hr/>
				<?php
				$input_name = SHADOW_KEY."_ping_status";
				$meta_data = get_post_meta( $post->ID, '_'.$input_name, true );
				$scrape_core->make_radio_html(array(
					'types'=>array('closed'=>'closed','open'=>'open'),
					'input_name'=>$input_name,
					'meta_data'=>$meta_data!=""?$meta_data:"closed",
					'description'=>'',
					'title'=>'Pingbacks or trackbacks are allowed?'
				))?>
				
				<hr/>
				<?php
				$input_name = SHADOW_KEY."_comment_status";
				$meta_data = get_post_meta( $post->ID, '_'.$input_name, true );
				$scrape_core->make_radio_html(array(
					'types'=>array('closed'=>'closed','open'=>'open'),
					'input_name'=>$input_name,
					'meta_data'=>$meta_data!=""?$meta_data:"closed",
					'description'=>'',
					'title'=>'Comments are allowed?'
				))?>
								
				<div class="clear"></div>	
			</div>
			<?php
		}


/*


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
		 * Save a profiles meta saved through the object's meta box.
		 *
		 * @param int     $post_id The ID of the post being saved.
		 * @param object  $post The post being saved.
		 */
		public function save_shadow_profile_object( $post_id, $post ) {
			if ( isset( $_POST[SHADOW_KEY.'_type'] ) ) {
				if ( empty( trim( $_POST[SHADOW_KEY.'_type'] ) ) ) {
					delete_post_meta( $post_id, '_wsuwp_spn_type' );
				} else {
					update_post_meta( $post_id, '_wsuwp_spn_type', $_POST[SHADOW_KEY.'_type'] );
				}
			}		
			return;
		}

	}
	global $shadow_profile;
	$shadow_profile = new shadow_profile();
}