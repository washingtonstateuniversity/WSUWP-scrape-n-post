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
			add_meta_box( SHADOW_KEY.'_shadow_feild_map', 'Field Map', array( $this, 'display_shadow_feild_map_meta_box' ) , SHADOW_POST_TYPE_PROFILE, 'normal', 'default' );
			add_meta_box( SHADOW_KEY.'_post_defaults', 'Defaults', array( $this, 'display_post_defaults_meta_box' ) , SHADOW_POST_TYPE_PROFILE, 'normal', 'default' );
		}

		/**
		 * Display a meta box of the captured html.  This is just displaying the post content, so it's 
		 * not really the meta of the post, but it'll work for our needs
		 *
		 * @global class $scrape_core
		 *
		 * @param WP_Post $post The full post object being edited.
		 */
		public function display_shadow_feild_map_meta_box( $post ) {
			global $scrape_core;
			?>
			<div class="field_block">
				<h3>post_name</h3>
				<label>root_selector</label><input type="text" value="" placeholder=".css_selector"/><br/>
				<label>selector</label><input type="text" value="" placeholder=".css_selector"/>
				<hr/>
				<?php
					$input_name = SHADOW_KEY."_pull_from";
					$meta_data = get_post_meta( $post->ID, '_'.$input_name, true );
					$array = array("text"=>"test()","html"=>"html()","innerHTML"=>"innerHTML()");
				?>
				<label> <?=_e( "Type of returned data" )?> </label>
				<select name="<?=$input_name?>">
				<?php foreach($array as $key=>$val): ?>
					<option <?=selected($key, ($meta_data!=""?$meta_data:"post"))?> value="<?=$key?>"> <?=$val?> </option>
				<?php endforeach; ?>
				</select>
				<p>The data that is brought back follows the same as the jQuery equivalents.</p>
				<hr/>
				<a href="#" class="prefilter-add" style="float:right;"><b>Add pre-filter<span class="dashicons dashicons-plus-alt"></span></b></a>
				<b>pre_filter</b>
				<p>Pre-filters are used on content <strong>before</strong> a match is seeked.  This could be to normalize content, or to add content or...</p>
				<ul>
					<li class="pre-filter-template" style="display:none;">
						<div class="filter_block">
							<?php
								$array = array("explode"=>"explode","remove"=>"remove","str_replace"=>"str_replace","preg_replace"=>"preg_replace");
								$input_name = SHADOW_KEY."_pull_from";
								$meta_data = get_post_meta( $post->ID, '_'.$input_name, true );
							?>
							<label> <?=_e( "Type of filter" )?> </label>
							<select name="<?=$input_name?>" class="filterTypeSelector">
							<?php foreach($array as $key=>$val): ?>
								<option <?=selected($key, ($meta_data!=""?$meta_data:"post"))?> value="<?=$key?>"> <?=$val?> </option>
							<?php endforeach; ?>
							</select><br/>
		
							<span class="filteroptions type_remove"><label>root</label><input type="text" value="" data-req='required' placeholder=".css_selector"/><br/></span>
							<span class="filteroptions type_remove"><label>selector</label><input type="text" value="" data-req='required' placeholder=".css_selector"/></span>
							
							<span class="filteroptions type_explode"><label>on</label><input type="text" value="" data-req='required'/><br/></span>
							<span class="filteroptions type_explode"><label>select</label><input type="number" value=""/></span>
							
							<span class="filteroptions type_str_replace"><label>search</label><input type="text" value="" data-req='required'/><br/></span>
							<span class="filteroptions type_preg_replace"><label>pattern</label><input type="text" value="" data-req='required' placeholder="/regex|pattern/si"/><br/></span>
							<span class="filteroptions type_str_replace type_preg_replace"><label>replace</label><input type="text" value="" data-req='required'/></span>
						</div>
					</li>
				</ul>
				
				<hr/>
				<a href="#" class="filter-add" style="float:right;"><b>Add content filter<span class="dashicons dashicons-plus-alt"></span></b></a>
				<b>Content filter</b>
				<p>Content filters are used on content <strong>after</strong> a match is found.  This could be to normalize content, or to add content or...</p>
				<ul>
					<li class="filter-template" style="display:none;">
						<div class="filter_block">
							<?php
								$array = array("explode"=>"explode","remove"=>"remove","str_replace"=>"str_replace","preg_replace"=>"preg_replace");
								$input_name = SHADOW_KEY."_pull_from";
								$meta_data = get_post_meta( $post->ID, '_'.$input_name, true );
							?>
							<label> <?=_e( "Type of filter" )?> </label>
							<select name="<?=$input_name?>" class="filterTypeSelector">
							<?php foreach($array as $key=>$val): ?>
								<option <?=selected($key, ($meta_data!=""?$meta_data:"post"))?> value="<?=$key?>"> <?=$val?> </option>
							<?php endforeach; ?>
							</select><br/>
		
							<span class="filteroptions type_remove"><label>root</label><input type="text" value="" data-req='required' placeholder=".css_selector"/><br/></span>
							<span class="filteroptions type_remove"><label>selector</label><input type="text" value="" data-req='required' placeholder=".css_selector"/></span>
							
							<span class="filteroptions type_explode"><label>on</label><input type="text" value="" data-req='required'/><br/></span>
							<span class="filteroptions type_explode"><label>select</label><input type="number" value=""/></span>
							
							<span class="filteroptions type_str_replace"><label>search</label><input type="text" value="" data-req='required'/><br/></span>
							<span class="filteroptions type_preg_replace"><label>pattern</label><input type="text" value="" data-req='required' placeholder="/regex|pattern/si"/><br/></span>
							<span class="filteroptions type_str_replace type_preg_replace"><label>replace</label><input type="text" value="" data-req='required'/></span>
						</div>
					</li>
				</ul>
				
				
				
				
				
				<a href="#" class="fallback"><b>Add a fallback <span class="dashicons dashicons-plus-alt"></span></b></a>
			</div>

			<?php
		}




/*

'post_title'=>(object) [
	'id' => 1,
	'root_selector' => '#siteID',
	'selector' => 'h1:eq(0)',
	'pull_from' => 'text',
	'pre_filter' => [],
	'filter' => [],
	'fall_back' =>(object) [
		'id' => 2,
		'root_selector' => 'h2:eq(0)',
		'selector' => '',
		'pull_from' => 'text',
		'pre_filter' => [
				(object) [
					'type'=>'str_replace',
					'search'=>'<H2>',
					'replace'=>'<h2>'
				],
				(object) [
					'type'=>'str_replace',
					'search'=>'</H2>',
					'replace'=>'</h2>'
				]
			],
		'filter' => [],
		'fall_back' =>(object) [
			'id' => 3,
			'root_selector' => 'html',
			'selector' => 'title',
			'pull_from' => 'text',
			'pre_filter' => [],
			'filter' => [
				(object) [
					'type'=>'str_replace',
					'search'=>'.htm',
					'replace'=>''
				],
				(object) [
					'type'=>'str_replace',
					'search'=>'_',
					'replace'=>' '
				],
				(object) [
					'type'=>'preg_replace',
					'pattern'=>'/\d+\.\d+/',
					'replace'=>''
				]
			]
		]
	]
]
*/
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
					'types'=> array_flip(get_post_statuses()),
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
					'types'=>array('Closed'=>'closed','Open'=>'open'),
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
					'types'=>array('Closed'=>'closed','Open'=>'open'),
					'input_name'=>$input_name,
					'meta_data'=>$meta_data!=""?$meta_data:"closed",
					'description'=>'',
					'title'=>'Comments are allowed?'
				))?>
					
					
					
				<hr/>
				
				<?php
					$input_name = SHADOW_KEY."_post_password";
					$meta_data = get_post_meta( $post->ID, '_'.$input_name, true );
				?>
				<label> <?=_e( "Use a master Password for posts that are private" )?> </label>
				<input type="password" name="<?=$input_name?>" value="<?=$meta_data?>" class="show_hide_pass" />


				<hr/>
				<?php
				$input_name = SHADOW_KEY."_post_excerpt";
				$meta_data = get_post_meta( $post->ID, '_'.$input_name, true );
				$scrape_core->make_radio_html(array(
					'types'=>array('Yes'=>'yes','No'=>'no'),
					'input_name'=>$input_name,
					'meta_data'=>$meta_data!=""?$meta_data:"closed",
					'description'=>'',
					'title'=>'Use an excerpt?'
				))?>
				
				<hr/>
				<?php
					$input_name = SHADOW_KEY."_post_category";
					$meta_data = get_post_meta( $post->ID, '_'.$input_name, true );
				?>
				<label> <?=_e( "Use Base Categories Posts" )?> </label>
				<select name="<?=$input_name?>">
				<?php foreach( get_categories() as $cat ): ?>
					<option <?=selected($cat->term_id, $meta_data)?> value="<?=$cat->term_id?>"> <?=$cat->name?> </option>
				<?php endforeach; ?>
				</select>	

				<?php
				$input_name = SHADOW_KEY."_post_category_method";
				$meta_data = get_post_meta( $post->ID, '_'.$input_name, true );
				$scrape_core->make_radio_html(array(
					'types'=>array('Override'=>'override','Append'=>'append'),
					'input_name'=>$input_name,
					'meta_data'=>$meta_data!=""?$meta_data:"append",
					'description'=>'By setting it to <code>Override</code>, if there is any matches retruned from the content, the default will be not used.  When set to <code>Append</code> the default list will be used and any new categories will be added to the list for the shadow post.',
					'title'=>'How should the default categories be used?'
				))?>
				<hr/>

				<?php
					$input_name = SHADOW_KEY."_page_template";
					$meta_data = get_post_meta( $post->ID, '_'.$input_name, true );
				?>
				<label><?=_e( "Use template for the posts" )?></label>
				<select name="<?=$input_name?>">
					<option <?=selected("default", $meta_data)?> value="default"><?=_e( "Default Template" )?></option>
				<?php foreach( get_page_templates() as $name=>$file ): ?>
					<option <?=selected($file, $meta_data)?> value="<?=$file?>"><?=$name?></option>
				<?php endforeach; ?>
				</select>

				<div class="clear"></div>	
			</div>
			<?php
		}


/*
  'post_date'      => [ Y-m-d H:i:s ] // The time post was made.
  'tags_input'     => [ '<tag>, <tag>, ...' | array ] // Default empty.
  'tax_input'      => [ array( <taxonomy> => <array | string> ) ] // For custom taxonomies. Default empty.
*/
		
		/**
		 * Save a profiles meta saved through the object's meta box.
		 *
		 * @param int     $post_id The ID of the post being saved.
		 * @param object  $post The post being saved.
		 */
		public function save_shadow_profile_object( $post_id, $post ) {
			$shadow_profile_object_names = array('post_status','post_type','post_author','ping_status','comment_status','post_password','post_excerpt','post_category','post_category_method','page_template');
			foreach($shadow_profile_object_names as $name){
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
	global $shadow_profile;
	$shadow_profile = new shadow_profile();
}