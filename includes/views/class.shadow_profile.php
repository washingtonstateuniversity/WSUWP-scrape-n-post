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
			
			add_action('admin_footer-post.php', array( $this, 'add_block_tempates' ), 11);
			
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
			$mapable_parts=array('post_content','post_name','post_title','post_excerpt','post_date','post_category');//,'post_author','post_parent','menu_order','tags_input','tax_input');
			foreach($mapable_parts as $name){
				$value=array();
				$input_name = SHADOW_KEY."_map[$name]";
				$value = get_post_meta( $post->ID, '_'.SHADOW_KEY.'_map_'.$name, true );
				$this->mapping_block($post,$name,$input_name,json_decode($value));
			}
		}
		
		public function add_block_tempates(){
			global $post;    
			?>
			<div id="mapping_template">
				<?=$this->feild_block_stub($post,"{STUB_NAME}","{INPUT_NAME}",array())?>
			</div>
			<div id="filter_template">
				<?=$this->build_filter_ui_block($post,"{INPUT_NAME}",array(),array())?>
			</div>
			<?php		
		}
		
		public function mapping_block($post,$name,$input_name,$values=array()){
			$display="";
			if($name=="post_excerpt" && get_post_meta( $post->ID, '_'.SHADOW_KEY."_post_excerpt", true )!="yes"){
				$display="display:none;";
			}
			?>
			<fieldset class="field_block <?=$name?>" style=" <?=$display?> ">
				<legend><?=$name?></legend>
				<a href="#" class="mapping-add button" style="float:right;<?=(isset($values) && !empty($values)?"display:none;":"")?>" data-block_name="<?=$name?>" data-base_input_name="<?=$input_name?>"><b>Add mapping<span class="dashicons dashicons-plus-alt"></span></b></a>
				
				<div class="fields_area">
				<?php if(isset($values) && !empty($values)):?>
					<?=$this->feild_block_stub($post,$name,$input_name,$values)?>
				<?php endif;?>

				</div>
			</fieldset>
			<?php
		}
		public function feild_block_stub($post,$name,$input_name,$values=array()){
			
			$value=(array)$values;
			
			
			?>
			<div class="field_block_area"  >
				<a href="#" class="mapping-removal" style="float:right;"><b>Remove<span class="dashicons dashicons-dismiss"></span></b></a>
				<label>root_selector</label><input type="text" value="<?=isset($value["root_selector"])?$value["root_selector"]:""?>" name="<?=$input_name."[root_selector]"?>" placeholder=".css_selector"/><br/>
				<label>selector</label><input type="text" value="<?=isset($value["selector"])?$value["selector"]:""?>" name="<?=$input_name."[selector]"?>" placeholder=".css_selector"/>
				<hr/>
				<?php
					$selinput_name = $input_name."[pull_from]";
					$meta_data = isset($value["pull_from"])?$value["pull_from"]:"";
					$array = array("text"=>"text()","html"=>"html()","innerHTML"=>"innerHTML()");
				?>
				<label> <?=_e( "Type of returned data" )?> </label>
				<select name="<?=$selinput_name?>">
				<?php foreach($array as $key=>$val): ?>
					<option <?=selected($key, ($meta_data!=""?$meta_data:"post"))?> value="<?=$key?>"> <?=$val?> </option>
				<?php endforeach; ?>
				</select>
				<p>The data that is brought back follows the same as the jQuery equivalents.</p>
				<hr/>
				
				<div class="pre_fill map_filter_wrapper" data-count="0">
					<a href="#" class="filter-add button" style="float:right;" data-base_input_name="<?=$input_name."[pre_filters]"?>"><b>Add pre-filter<span class="dashicons dashicons-plus-alt"></span></b></a>
					<b>pre_filter</b>
					<p class="filter-discription">Pre-filters are used on content <strong>before</strong> a match is seeked.  This could be to normalize content, or to add content or...</p>
					<ul>
					<?php if(isset($value['pre_filters']) && !empty($value['pre_filters'])){
						$i=0;
						foreach($value['pre_filters'] as $filter){
ob_start();
$this->build_filter_ui_block($post,$input_name.'[pre_filters]',$value['pre_filters'],$filter);
$content = ob_get_clean();
							?><li class="filter-template">
							<?=preg_replace("/\{##\}/si","$i",$content)?>
							</li><?php
							$i++;
						}
					}?>
					</ul>
				</div>
				
				<hr/>
				<div class="post_fill map_filter_wrapper" data-count="0">
					<a href="#" class="filter-add button" style="float:right;" data-base_input_name="<?=$input_name."[filters]"?>"><b>Add content filter<span class="dashicons dashicons-plus-alt"></span></b></a>
					<b>Content filter</b>
					<p class="filter-discription">Content filters are used on content <strong>after</strong> a match is found.  This could be to normalize content, or to add content or...</p>
					<ul>
					<?php if(isset($value['filters']) && !empty($value['filters'])){
						$i=0;
						foreach($value['filters'] as $filter){
ob_start();
$this->build_filter_ui_block($post,$input_name.'[filters]',$value['filters'],$filter);
$content = ob_get_clean();
							?><li class="filter-template">
							<?=preg_replace("/\{##\}/si","$i",$content)?>
							</li><?php
							$i++;
						}
					}?>
					</ul>
				</div>
				<hr/>
				<a href="#" class="fallback-add button" data-block_name="<?=$name?>"  data-base_input_name="<?=$input_name."[fallback]"?>"><b>Add a fallback <span class="dashicons dashicons-plus-alt"></span></b></a>
				<ul class="fallbacks <?=(isset($value["fallback"]) && !empty($value["fallback"])?"active":"")?>">
				<?php
				if(isset($value["fallback"]) && !empty($value["fallback"])){
					$i=0;
					foreach($value["fallback"] as $object){
						?><li><?php
						$this->feild_block_stub($post,$name,$input_name."[fallback][$i]",(object)$object);
						?></li><?php
						$i++;
					}
				}
				?>
				</ul>
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


		public function build_filter_ui_block($post,$input_name,$block_obj=array(), $values=array()){
			$value=(array)$values;
			$input_name.="[{##}]";

			?>
			<div class="filter_block">
				<a href="#" class="filter-removal" style="float:right;"><b>Remove<span class="dashicons dashicons-dismiss"></span></b></a>
				<?php
					$array = array("explode"=>"explode","remove"=>"remove","str_replace"=>"str_replace","preg_replace"=>"preg_replace");
					$selinput_name = $input_name."[type]";
					$meta_data = isset($value["type"])?$value["type"]:"";
				?>
				<label> <?=_e( "Type of filter" )?> </label>
				<select name="<?=$selinput_name?>" class="filterTypeSelector">
				<?php foreach($array as $key=>$val): ?>
					<option <?=selected($key, ($meta_data!=""?$meta_data:"post"))?> value="<?=$key?>"> <?=$val?> </option>
				<?php endforeach; ?>
				</select><br/>

				<!-- remove type -->
				<span class="filteroptions type_remove">
					<label>root</label><input type="text" name="<?=$input_name."[root]"?>" value='<?=isset($value["root"])?str_replace("'","\'",$value["root"]):""?>' data-req='required' placeholder=".css_selector" class="half"/><br/>
				</span>
				<span class="filteroptions type_remove">
					<label>selector</label>
					<input type="text" name="<?=$input_name."[selector]"?>" value='<?=isset($value["selector"])?str_replace("'","\'",$value["selector"]):""?>' data-req='required' placeholder=".css_selector" class="half"/>
				</span>
				
				<!-- explode type -->
				<span class="filteroptions type_explode">
					<label>on</label>
					<input type="text" name="<?=$input_name."[on]"?>" value="<?=isset($value["on"])?$value["on"]:""?>" data-req='required'/><br/>
				</span>
				<span class="filteroptions type_explode">
					<label>select</label>
					<input type="number" name="<?=$input_name."[select]"?>" value="<?=isset($value["select"])?$value["select"]:""?>"/>
				</span>
				
				
				<!-- replace types -->
				<span class="filteroptions type_str_replace">
					<label>search</label>
					<input type="text" name="<?=$input_name."[search]"?>" value='<?=isset($value["search"])?str_replace("'","\'",$value["search"]):""?>' data-req='required' class="full"/><br/>
				</span>
				<span class="filteroptions type_preg_replace">
					<label>pattern</label>
					<input type="text" name="<?=$input_name."[pattern]"?>" value='<?=isset($value["pattern"])?str_replace("'","\'",$value["pattern"]):""?>' data-req='required' placeholder="/regex|pattern/si" class="full"/><br/>
				</span>
				<span class="filteroptions type_str_replace type_preg_replace">
					<label>replace</label>
					<input type="text" name="<?=$input_name."[replace]"?>" value='<?=isset($value["replace"])?$value["replace"]:""?>'  class="full"/>
				</span>
				
				
			</div>
			<?php

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
				$post_status_data = get_post_meta( $post->ID, '_'.$input_name, true );
				$scrape_core->make_radio_html(array(
					'types'=> array_flip(get_post_statuses()),
					'input_name'=>$input_name,
					'meta_data'=>$post_status_data!=""?$post_status_data:"draft",
					'description'=>'set this to what you want a post to be when it is created.',
					'title'=>'Post status'
				))?>
				
				<span id="post_password_area" style=" <?=($post_status_data!="private"?"display:none;":"")?> ">
					<?php
						$input_name = SHADOW_KEY."_post_password";
						$meta_data = get_post_meta( $post->ID, '_'.$input_name, true );
					?>
					<label> <?=_e( "Use a master Password for posts that are private" )?> </label>
					<input type="password" name="<?=$input_name?>" value="<?=$meta_data?>" class="show_hide_pass" />
				</span>
				
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
				$input_name = SHADOW_KEY."_post_excerpt";
				$meta_data = get_post_meta( $post->ID, '_'.$input_name, true );
				$scrape_core->make_radio_html(array(
					'types'=>array('Yes'=>'yes','No'=>'no'),
					'input_name'=>$input_name,
					'meta_data'=>$meta_data!=""?$meta_data:"yes",
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
						update_post_meta( $post_id, '_'.SHADOW_KEY.'_'.$name, $_POST[SHADOW_KEY.'_'.$name] );
					}
				}
			}
			
			$shadow_profile_object_mapping_names = array('post_content','post_name','post_title','post_excerpt','post_date','post_category');
			foreach($shadow_profile_object_mapping_names as $name){
				if ( isset( $_POST[SHADOW_KEY.'_map'][$name] ) ) {
					$value = json_encode($_POST[SHADOW_KEY.'_map'][$name]);
					if ( empty( trim( $value ) ) ) {
						delete_post_meta( $post_id, '_'.SHADOW_KEY.'_map_'.$name );
					} else {
						update_post_meta( $post_id, '_'.SHADOW_KEY.'_map_'.$name, json_encode($_POST[SHADOW_KEY.'_map'][$name]) );
					}
				}
			}
			return;
		}

	}
	global $shadow_profile;
	$shadow_profile = new shadow_profile();
}