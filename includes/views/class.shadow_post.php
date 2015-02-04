<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
if ( ! class_exists( 'shadow_post' ) ) {
	class shadow_post {
	
		/**
		 * constructor
		 */
		function __construct() {
			add_action( 'init', array( $this, 'register_shadow_post_type' ), 11 );
			add_action( 'add_meta_boxes', array( $this, 'add_shadow_post_meta_boxes' ), 11, 1 );
			add_action( 'save_post', array( $this, 'save_shadow_post_object' ), 15, 2 );
			add_action( 'transition_post_status', array( $this, 'force_shadow_post_status' ), 15, 3 );
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
			if ( $post->post_type == SHADOW_POST_TYPE_POST && $new_status == 'publish' && $old_status  != $new_status ) {
				$post->post_status = 'private';
				wp_update_post( $post );
			}
		}
		/**
		 * Register the shadow content type.
		 *
		 * The point of this content is that a post can be linked back to a url so that it can be 
		 * updated from that url. 
		 */
		public function register_shadow_post_type() {
			$labels = array(
				'menu_name'          => __( 'Scrape N\' Post', SHADOW_KEY ),
				'name'               => __( 'Shadow Copy', SHADOW_KEY ),
				'singular_name'      => __( 'Shadow Copy', SHADOW_KEY ),
				'all_items'          => __( 'All Shadow Copies', SHADOW_KEY ),
				'add_new_item'       => __( 'Add Shadow Copy', SHADOW_KEY ),
				'edit_item'          => __( 'Edit Shadow Copy', SHADOW_KEY ),
				'new_item'           => __( 'New Shadow Copy', SHADOW_KEY ),
				'view_item'          => __( 'View Shadow Copy', SHADOW_KEY ),
				'search_items'       => __( 'Search Shadow Copies', SHADOW_KEY ),
				'not_found'          => __( 'No Shadow Copies found', SHADOW_KEY ),
				'not_found_in_trash' => __( 'No Shadow Copies found in trash', SHADOW_KEY ),
			);
			$description = __( 'Shadow Copies belonging to a post.', SHADOW_KEY );
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
			register_post_type( SHADOW_POST_TYPE_POST, $args );
		}

		/**
		 * Add meta boxes used to capture pieces of information.
		 *
		 * @param string $post_type
		 */
		public function add_shadow_post_meta_boxes( $post_type ) {
			//main content area
			add_meta_box( SHADOW_KEY.'_url', 'Url', array( $this, 'display_object_url_meta_box' ) , SHADOW_POST_TYPE_POST, 'normal', 'default' );
			add_meta_box( SHADOW_KEY.'_html', 'Content', array( $this, 'display_cached_html' ) , SHADOW_POST_TYPE_POST, 'normal', 'default' );
			//side bars
			add_meta_box( SHADOW_KEY.'_post_shadowing', 'Shadowing Post', array( $this, 'display_option_post_tie' ) , SHADOW_POST_TYPE_POST, 'side', 'default' );
			add_meta_box( SHADOW_KEY.'_ignored', 'Skip Link', array( $this, 'display_option_ignore' ) , SHADOW_POST_TYPE_POST, 'side', 'default' );
		}
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
						'name'                  => SHADOW_KEY.'_tied_post_id',
						'show_option_none'      => 'un-tied', // string
						'show_option_no_change' => null, // string
						'option_none_value'     => "", // string
					) ); ?> 
				</div>
				<input type="hidden" name="wsuwp_spn_porfile_used" value="<?=get_post_meta( $post->ID, '_wsuwp_spn_porfile_used', true )?>"/>
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
			global $scrape_core;
			?>
			<div id="wsuwp-snp-display-ignore">
				<?php
				$input_name = SHADOW_KEY."_ignored";
				$meta_data = get_post_meta( $post->ID, '_'.$input_name, true );
				$scrape_core->make_radio_html(array(
					'types'=>array('Yes'=>'1','No'=>'0'),
					'input_name'=>$input_name,
					'meta_data'=>$meta_data!=""?$meta_data:"0",
					'description'=>'during updates should this link be included in the crawl?',
					'title'=>'Ignore this url'
				))?>
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
					<input type="text" class="widefat" id="wsuwp-spn-url" name="<?=SHADOW_KEY?>_url" value="<?=$object_url?>" />
					<p class="description">Note, altering the url will cause the html to get reloaded.</p>
				</div>
				<div class="<?=SHADOW_KEY?>_last_http_status">
					<input type="hidden" name="<?=SHADOW_KEY?>_last_http_status" value="<?=($http_status!="not checked"?$http_status:"")?>"/>
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
			$shadow_post_object_names = array('url','tied_post_id','ignored','last_http_status','type','porfile_used');
			foreach($shadow_post_object_names as $name){
				if ( isset( $_POST[SHADOW_KEY.'_'.$name] ) ) {
					if ( empty( trim( $_POST[SHADOW_KEY.'_'.$name] ) ) ) {
						delete_post_meta( $post_id, '_'.SHADOW_KEY.'_'.$name );
					} else {
						update_post_meta( $post_id, '_'.SHADOW_KEY.'_'.$name, esc_url_raw( $_POST[SHADOW_KEY.'_'.$name] ) );
					}
				}
			}
			return;
		}
		
	}
	global $shadow_post;
	$shadow_post = new shadow_post();
}