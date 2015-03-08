<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! class_exists( 'scrape_actions' ) ) {
	class scrape_actions extends scrape_core {

		function __construct() { }

		/**
		 * Insert to template table.
		 * 
		 * @global class $wpdb
		 * 
		 * @param array $arr
		 */
		public function add_queue($arr = array()) {
				$parent_id = isset($arr['post_id']) ? $arr('post_id') : 0;
				// Create post object
				$post_compiled = array(
					'post_type'      => SHADOW_POST_TYPE_POST,
					'post_name'      => $arr['url'],
					'post_title'     => $arr['url'],
					'post_author'    => 0,
					'post_parent'    => 0,//may want to use this to tie to the parent?
					'menu_order'     => 0,
					'post_excerpt'   => '',
					'post_date'      => current_time('mysql'),
					'post_category'  => array(),
					'tags_input'     => '',
					'tax_input'      => '',
					'post_status'      => 'private',
					'post_content'   => $arr['html']
				);
				$arrs = array_merge($post_compiled,$arr);

				
				//good so far let make the post
				if(isset($arr['shadow_id'])){
					$post_id = wp_update_post( $arrs );
				}else{
					$post_id = wp_insert_post( $arrs );	
				}
				if( !is_wp_error($post_id) ) {
					update_post_meta( $post_id, '_'.SHADOW_KEY.'_'.'url', $arr['url'] );
					update_post_meta( $post_id, '_'.SHADOW_KEY.'_'.'ignored', '0' );
					update_post_meta( $post_id, '_'.SHADOW_KEY.'_'.'tied_post_id', $parent_id);
					update_post_meta( $post_id, '_'.SHADOW_KEY.'_'.'last_http_status', $arr['http_status'] );
					$scrape_core->message = array(
						'type' => isset($arrs['ID']) ? 'updated' : 'added',
						'message' => isset($arrs['ID']) ?  __('Updated post') : __('Added new Post')
					);
				}else{
					$scrape_core->message = array(
						'type' => 'error',
						'message' => __('Post error '.$post_id->get_error_message())
					);	
				}
				//var_dump($arrs);
				//die();
			
			//needs message
		}
		
		/**
		 * Update entry in template table.
		 * 
		 * @global class $wpdb
		 * @global class $scrape_core
		 * @global array $_params
		 * 
		 * @param array $arr
		 */
		public function update_queue($arr = array()) {
			global $wpdb,$scrape_core,$_params;
			$where         = array(
				'target_id' => $_params['target_id']
			);
			$arr['last_checked'] = current_time('mysql');
			$table_name    = $wpdb->prefix . "scrape_n_post_queue";
			$rows_affected = $wpdb->update($table_name, $arr, $where);
			//needs message
		}
		
		/**
		 * Mark a target to be ignored.
		 * 
		 * @global class $wpdb
		 * @global class $scrape_core
		 * @global array $_params
		 * 
		 * @param int $target_id
		 */			
		public function ignore_url($target_id=NULL) {
			global $wpdb,$scrape_core,$_params;
			if( $target_id==NULL && !isset($_params['url']) ){
				 return; // do message
			}else{
				$id = $target_id==NULL ? $_params['url'] : $target_id;
			}
			$where         = array(
				'target_id' => $id
			);
			$arr['ignore'] = 1;
			$table_name    = $wpdb->prefix . "scrape_n_post_queue";
			$rows_affected = $wpdb->update($table_name, $arr, $where);
			//needs message
		}
		
		/**
		 * Unlink a post from the url it was created from.
		 * 
		 * @global class $wpdb
		 * @global class $scrape_core
		 * @global class $scrape_pages
		 * @global array $_params
		 * 
		 * @param int $target_id
		 */	
		public function detach_post($target_id=NULL) {
			global $wpdb,$scrape_core,$_params,$scrape_pages;
			if( $target_id==NULL && !isset($_params['url']) ){
				 return; // do message
			}else{
				$id = $target_id==NULL ? $_params['url'] : $target_id;
			}
			$where         = array(
				'target_id' => $id 
			);
			$arr['post_id'] = NULL;
			$table_name    = $wpdb->prefix . "scrape_n_post_queue";
			$rows_affected = $wpdb->update($table_name, $arr, $where);
			$scrape_pages->foward('scrape-crawler',$scheme='http');
			//needs message
		}
		
		/**
		 * Re import the post.
		 * 
		 * @global class $wpdb
		 * @global class $scrape_core
		 * @global class $scrape_pages
		 * @global array $_params
		 * 
		 * @param int $target_id
		 */	
		public function reimport_post($target_id=NULL) {
			global $wpdb,$scrape_core,$_params,$scrape_pages;
			if( $target_id==NULL && !isset($_params['url']) ){
				 return; // do message
			}else{
				$id = $target_id==NULL ? $_params['url'] : $target_id;
			}
			$table_name = $wpdb->prefix . "scrape_n_post_queue";
			$row = $wpdb->get_var( $wpdb->prepare(
					"SELECT url FROM {$table_name} WHERE target_id=%d",
					 $id
				 ) );
			 if( !is_wp_error( $row ) ) {
				$url = $row; 
			 }

			if( !isset($_params['post_id']) ){
				 return; // do message
			}else{
				$post_id = $_params['post_id'];
			}
			$this->make_post($url, $arr = array('ID'=>$post_id));
		}	
			
		/**
		 * Make a post from the url stored - this is use for repost too, not just new ones.
		 * 
		 * @global class $wpdb
		 * @global class $scrape_core
		 * @global array $_params
		 * 
		 * @param int $post_id
		 * @param int $target_id
		 */	
		public function url_to_post($post_id=NULL,$target_id=NULL) {
			global $wpdb,$scrape_core,$_params;
			if( $target_id==NULL && !isset($_params['url']) ){
				$scrape_core->message = array(
						'type' => 'error',
						'message' => __('Failed to recived a proper url to work with after reciving the remote content.')
					);
				return; // do message
			}else{
				$id = $target_id==NULL ? $_params['url'] : $target_id;
			}
			
			if( $post_id==NULL && !isset($_params['post_id']) ){
				$scrape_core->message = array(
						'type' => 'error',
						'message' => __('Failed to recived a proper post id to work with after reciving the remote content.')
					);
				return; // do message
			}else{
				$post_id = $post_id==NULL ? $_params['post_id'] : $post_id;
			}
			
			$where         = array( 'url' => $id );
			$arr['post_id'] = $post_id;
			$arr['last_checked'] = current_time('mysql');
			$table_name    = $wpdb->prefix . "scrape_n_post_queue";
			$rows_affected = $wpdb->update($table_name, $arr, $where);
			$scrape_core->message = array(
					'type' => 'updated',
					'message' => __('Added a new post for the url')
				);
		}
	
		/**
		 * Make a post from the url stored - this is use for repost too, not just new ones.
		 * 
		 * @global class $wpdb
		 * @global class $wp_query
		 * @global class $current_user
		 * @global class $scrape_core
		 * @global class $scrape_data
		 * @global array $_params
		 * 
		 * @param int $target_id
		 * @param array $arr
		 */	
		public function make_post( $_url=NULL, $arr = array() ){
			global $wpdb,$wp_query, $current_user,$scrape_core,$scrape_data,$_params;
			
			if( $_url==NULL ){
				wp_die( __('Failed to recived a proper URK to work with before getting the remote content.') );	 
			}
			$raw_html = wp_remote_get( $_url );//$scrape_data->scrape_get_content($id, 'html');
			//var_dump($raw_html);
			if( is_a($raw_html, 'WP_Error') || $raw_html=="ERROR::404" ){
				$scrape_core->message = array(
					'type' => 'error',
					'message' => __('Failed '.print_r($raw_html))
				);
				//var_dump($url); die(); //should be a message no? yes!
			}
			$currcharset = get_bloginfo('charset');
			//options to to the run on
			$options = $scrape_data->get_options();
			//profile to parse the data on
			$scrape_profile = $scrape_data->get_scraping_profile();

			$html=$raw_html['body'];

			$obj = get_post_type_object( $options['post_type'] );
			$post = get_post(42);//, $output, $filter 
			//var_dump($post);

			// Get user info
			$current_user = get_userdata( get_current_user_id() );
			$user         = $current_user; // current_user is default, w/should get it as a choice
	
			if($user){
				$author_id=$user->ID;
			}
			if($author_id<=0){
				wp_die( __( 'User not found to assign to an author' ).print_r($user.true) );
			}


			$shadow_profile_object_mapping_names = array( 'post_content', 'post_name', 'post_title', 'post_excerpt', 'post_date', 'post_category' );
			$porfile_obj = array();
			foreach($shadow_profile_object_mapping_names as $name){
				$input_name = SHADOW_KEY."_map[$name]";
				$value = get_post_meta( $post->ID, '_'.SHADOW_KEY.'_map_'.$name, true );
				$block = json_decode($value);
				//var_dump($block);
				$porfile_obj[$name]=$block;
			}
			
			
			$profile=(object)$porfile_obj;

			$catName = $this->get_content_part($html,$profile->post_category);


			$cat_ID = 0;
			if($catName!=""){
				$catSlug = sanitize_title_with_dashes($catName);
				if ($cat = get_term_by('slug', $catSlug,'category')){
					$cat_ID = $cat->term_id;
				}else{
					wp_insert_term($catName, 'category', array(
						'description' => '',
						'slug' => $catSlug
					));	
					if ($cat = get_term_by('slug', $catSlug,'category')){
						$cat_ID = $cat->term_id;
					}
				}
			}
			
			

			$post_preppened_content = get_post_meta( $post->ID, '_'.SHADOW_KEY.'_post_preppened_content', true );
			$post_preppened_shortcode = get_post_meta( $post->ID, '_'.SHADOW_KEY.'_post_preppened_shortcode', true );
			
			$post_appended_content = get_post_meta( $post->ID, '_'.SHADOW_KEY.'_post_appended_content', true );
			$post_appended_shortcode = get_post_meta( $post->ID, '_'.SHADOW_KEY.'_post_appended_shortcode', true );
			//var_dump($post_appened_content);
			


			if( isset($post_preppened_shortcode) && $post_preppened_shortcode == "yes" ){
				$post_preppened = isset($post_preppened_content) ? do_shortcode( $post_preppened_content ) : '';
			}else{
				$post_preppened = isset($post_preppened_content) ? $post_preppened_content : '';
			}
			
			if( isset($post_appended_shortcode) && $post_appended_shortcode == "yes" ){
				$post_appended = isset($post_appended_content) ? do_shortcode( $post_appended_content ) : '';
			}else{
				$post_appended = isset($post_appended_content) ? $post_appended_content : '';
			}
			//var_dump($post_appended);die();
			$content = $post_preppened . $this->get_content_part($html,$profile->post_content) . $post_appended;

			// Create post object
			$post_compiled = array(
				'post_type'      => $options['post_type'],
				'post_name'      => $this->get_content_part($html,$profile->post_name),
				'post_title'     => $this->get_content_part($html,$profile->post_title),
				'post_author'    => $author_id,
				'post_parent'    => 0,
				'menu_order'     => 0,
				'post_excerpt'   => (isset($profile->post_excerpt)?$this->get_content_part($html,$profile->post_excerpt):""),
				'post_date'      => (isset($profile->post_date)?$this->get_content_part($html,$profile->post_date):""),//[ Y-m-d H:i:s ] // The time post was made.
				'post_category'  => array($cat_ID),
				'tags_input'     => (isset($profile->tags_input)?$this->get_content_part($html,$profile->tags_input):""),//[ '<tag>, <tag>, ...' | array ] // Default empty.
				'tax_input'      => (isset($profile->tax_input)?$this->get_content_part($html,$profile->tax_input):""),//[ array( <taxonomy> => <array | string> ) ] // For custom taxonomies. Default empty.
				'post_content'   =>  $content
			);
						
			
			
			$arrs = array_merge($post_compiled,$arr);
			
			/*var_dump($arrs);
			var_dump((object)$porfile_obj);
			
			die();*/
			
			//good so far let make the post
			if(isset($arrs['ID'])){
				$post_id = wp_update_post( $arrs );
			}else{
				$post_id = wp_insert_post( $arrs );	
			}
			if( is_wp_error($post_id) ) {
				wp_die( __('Post error '.$post_id->get_error_message()) );
			}
			foreach( $profile as $key=>$meta_profile ){
				if( strpos($key,'meta__') !== false ){
					$meta = $this->get_content_part($html,$meta_profile);
					update_post_meta( $post_id, str_replace('meta__','',$key), $meta_profile );
				}
			}
			$scrape_core->message = array(
				'type' => isset($arrs['ID']) ? 'updated' : 'added',
				'message' => isset($arrs['ID']) ?  __('Updated post') : __('Added new Post')
			);
			return true;
			//all good let tie the post to the url
			//$this->url_to_post($post_id,$url);
		}

		/**
		 * Find content from basic options
		 * 
		 * @param object $profile_obj
		 *
		 * @return string
		 */
		public function get_content_part($html,$profile_obj=NULL){
			
			$check = (array)$profile_obj;
			if($profile_obj==NULL || empty($check)){
				return "";	
			}
			/*
			'root_selector' => 'html',
			'selector' => 'title',
			'pull_from' => 'text',
			'filter' => (object) [],
			*/
			$output = "";
			
			if( isset($profile_obj->pre_filters) ){
				$pre = (array)$profile_obj->pre_filters;
				if(!empty($pre)){
					$html = $this->filter_content($html, $pre);
				}
			}


			$content_obj = htmlqp($html, $profile_obj->root_selector, array('ignore_parser_warnings' => TRUE));
			//var_dump($content_obj);
			if(isset($profile_obj->selector) && !empty($profile_obj->selector)){
				$content_obj = $content_obj->find($profile_obj->selector);
			}
			
			$grab = $profile_obj->pull_from;
			if( $grab == "text" ){
				$output = $content_obj->text();
			}elseif( $grab == "innerHTML" ){
				$output = $content_obj->innerHTML();
			}else{
				$output = $content_obj->html();
			}
			
			
			//would filter here
			//var_dump($profile_obj->filters);
			
			if( isset($profile_obj->filters) ){
				$fill = (array)$profile_obj->filters;
				if(!empty($fill)){
					$output = $this->filter_content($output,$fill);
				}
			}

			if( empty($output) && isset($profile_obj->fallback) ){ 
				//var_dump('starting fallback');
				foreach($profile_obj->fallback as $fallback){
					//var_dump('doing fallback');
					//var_dump($fallback);
					$output = $this->get_content_part($html,$fallback);
					if(!empty($output)){
						break;	
					}
				}
			}
			return trim($output);
		}

		/**
		 * Filter content from basic options
		 * 
		 * @param string $content
		 * @param object $filter_obj
		 *
		 * @return string
		 */
		public function filter_content($content, $filter_obj){
			//var_dump($filter_obj);
			foreach($filter_obj as $key=>$filter){
				switch($filter->type){
					case 'explode':
						$content=explode($filter->on,$content);
						$content=$content[$filter->select];
						break;
					case 'remove':
						$content_obj = htmlqp($content, $filter->root, array('ignore_parser_warnings' => TRUE));
						$content_obj->find($filter->selector)->remove();
						$content = $content_obj->top()->html();
						break;
					case 'str_replace':
						$content = str_replace($filter->search,$filter->replace,$content);
						break;
					case 'preg_replace':
						$content = preg_replace($filter->pattern,$filter->replace,$content);
						break;

						
				}
			}
			return trim($content);
		}
	
		/**
		 * Start the crawl from this url.
		 * 
		 * @global class $scrape_core
		 * @global array $_params
		 *
		 * @param string $url
		 */	
		public function crawl_from($url=NULL) {
			global $_params,$scrape_core,$scrape_data;
			if(isset($_params['url'])){
				$options = get_option( 'scrape_options', array('crawl_depth'=>5) ); //@todo bring this option in line with the abstracted
				$depth = $options['depth']; 
				$scrape_data->traverse_all_urls($_params['url'],$depth);
			}
		}
			
		/**
		 * Start a test the crawl from this url, and display the title back.
		 * 
		 * @global class $scrape_core
		 * @global class $scrape_data
		 * @global array $_params
		 */		
		public function test_crawler(){
			global $scrape_core,$scrape_data,$_params;
			$url = $_params['scrape_url'];

			$res = wp_remote_get($url);
			$response_code = wp_remote_retrieve_response_code( $res );
			
			if($response_code=="200"){
				$page = wp_remote_retrieve_body( $res );
				if(empty($page)){
					$page = $scrape_data->scrape_get_content($url, 'head');
				}
				/*$doc = phpQuery::newDocument($page);
				$title = pq('title')->text();*/
				
				$content_obj = htmlqp($page, 'head', array('ignore_parser_warnings' => TRUE));
				$title = $content_obj->find('title')->text();
				$error = false;
				if(empty($title)){
					$error = true;
					$message=" error : no page title";
				}else{
					$message=' and return html <code>&lt;title&gt; '.$title.'&lt;/title&gt; </code>';	
				}
				if(empty($page)){
					$message.=" -- the page also didn't render.";
				}
			}else{
				$error = true;	
				$message=" and retruned <b>${response_code}</b>";	
			}
			
			$scrape_core->message = array(
					'type' =>  ( !$error ?'updated':'error'),
					'message' => ( !$error ? '<span class="dashicons dashicons-yes"></span>' : '<span class="dashicons dashicons-no-alt"></span>') . __( 'tested <code>`'.$url.'`</code> '.$message)
				);
		}
		

		
		
		/**
		 * Start the crawl from this url.
		 * 
		 * @global class $wpdb
		 * @global class $scrape_core
		 * @global class $scrape_pages
		 * @global class $scrape_data
		 * @global array $_params
		 */	
		public function findlinks() {
			global $wpdb,$scrape_core,$scrape_pages,$scrape_data, $_params;
			
			$options = $scrape_data->get_options();
	
			$url=$_params['scrape_url'];
			$scrape_data->rootUrl = parse_url($url, PHP_URL_HOST);
			//var_dump($url);die();
			$urls = $scrape_data->get_all_urls($url,$options['crawl_depth']);

			$scrape_pages->crawler_page();
		}
	
	}	
	global $scrape_actions;
	$scrape_actions = new scrape_actions();
}
?>