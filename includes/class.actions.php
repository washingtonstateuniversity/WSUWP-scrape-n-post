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
		 *
		 * @access public
		 */
		public function add_queue($arr = array()) {
			global $wpdb;
			$arr['added_date'] = current_time('mysql');
			$table_name         = $wpdb->prefix . "scrape_n_post_queue";
			$rows_affected      = $wpdb->insert($table_name, $arr);
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
		 *
		 * @access public
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
		 *
		 * @access public
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
		 *
		 * @access public
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
		 *
		 * @access public
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
		 *
		 * @access public
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
		 *
		 * @access public
		 */	
		public function make_post($target_id=NULL, $arr = array()){
			global $wpdb,$wp_query, $current_user,$scrape_core,$scrape_data,$_params;
			
			if( $target_id==NULL && !isset($_params['url']) ){
				$scrape_core->message = array(
						'type' => 'error',
						'message' => __('Failed to recived a proper post id to work with before getting the remote content.')
					);
				 return; // do message
			}else{
				$url = $target_id==NULL ? $_params['url'] : $target_id;
			}
			$raw_html = wp_remote_get($url);//$scrape_data->scrape_get_content($id, 'html');
			//var_dump($raw_html);
			if(is_a($raw_html, 'WP_Error') || $raw_html=="ERROR::404"){
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


			//$doc = phpQuery::newDocumentHTML($raw_html['body'], $currcharset);
			//phpQuery::selectDocument($doc);

//$html5 = new HTML5();
//$dom = $html5->loadHTML($html);
$html=$raw_html['body'];

			$obj = get_post_type_object( $options['post_type'] );

/* this is the basic map for a post, just need the meta map
Not all of this will be set at the post level but set at the run 
level in refernce to the options that are pull

//compiled layer
$post_compiled = array(
  'post_content'   => [ <string> ] // The full text of the post.
  'post_name'      => [ <string> ] // The name (slug) for your post
  'post_title'     => [ <string> ] // The title of your post.
  'post_author'    => [ <user ID> ] // The user ID number of the author. Default is the current user ID.
  'post_parent'    => [ <post ID> ] // Sets the parent of the new post, if any. Default 0.
  'menu_order'     => [ <order> ] // If new post is a page, sets the order in which it should appear in supported menus. Default 0.
  'post_excerpt'   => [ <string> ] // For all your post excerpt needs.
  'post_date'      => [ Y-m-d H:i:s ] // The time post was made.
  'post_category'  => [ array(<category id>, ...) ] // Default empty.
  'tags_input'     => [ '<tag>, <tag>, ...' | array ] // Default empty.
  'tax_input'      => [ array( <taxonomy> => <array | string> ) ] // For custom taxonomies. Default empty.
);
$post_compiled_meta = array();
//from defaults
$post_base = array(
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
);
merge into each other droping emtpies first
$post_arrs = array_merge(array_filter( $post_compiled, 'strlen' ),$post_base);
// make post
// add $post_compiled_meta
// report
// repeat
 */

				
			//NOTE WHAT IS GOIGN TO BE DONE IS A EVAL FOR A PATTERN
			//remove placeholder
				
			//foreach profile query not fallback
				//$profile_obj = get_meta profile_id
				//$content = get_content($profile_obj);
				//if $content == "" && profile_fallback > 0
					// repeat for profile_fallback_id
				//assign string to post part
			//
			
			
			
			$profile = (object) [
				'post_name'=>(object) [
					'id' => 1,
					'root_selector' => 'html',
					'selector' => 'title',
					'pull_from' => 'text',
					'pre_filter' => [],
					'filter' => [],
					'fall_back' =>(object) [
						'id' => 2,
						'root_selector' => '#siteID',
						'selector' => 'h1:first',
						'pull_from' => 'text',
						'pre_filter' => [],
						'filter' => [],
						'fall_back' =>(object) [
							'id' => 3,
							'root_selector' => 'h2:first',
							'selector' => '',
							'pull_from' => 'text',
							'pre_filter' => [],
							'filter' => []
						]
					]
				],
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
				],
				'post_category'=>(object) [
					'id' => 1,
					'root_selector' => 'html',
					'selector' => 'p:eq(0)',
					'pull_from' => 'innerHTML',
					'pre_filter' => [
						(object) [
							'type'=>'str_replace',
							'search'=>'<P>',
							'replace'=>'<p>'
						],
						(object) [
							'type'=>'str_replace',
							'search'=>'</P>',
							'replace'=>'</p>'
						]
					],
					'filter' => [
						(object) [
							'type'=>'explode',
							'on'=>'<br/>',
							'select'=>'0'
						]
					],
				],
				'post_content'=>(object) [
					'id' => 1,
					'root_selector' => 'html',
					'selector' => 'div#main:eq(0)',
					'pull_from' => 'innerHTML',
					'pre_filter' => [],
					'filter' => [],
					'fall_back' =>(object) [
						'id' => 2,
						'root_selector' => 'body',
						'selector' => '',
						'pull_from' => 'innerHTML',
						'pre_filter' => [
							(object) [
								'type'=>'remove',
								'root'=>'body',
								'selector'=>'h3:eq(0)'
							],
							(object) [
								'type'=>'remove',
								'root'=>'body',
								'selector'=>'p:eq(0)'
							],
							(object) [
								'type'=>'remove',
								'root'=>'body',
								'selector'=>'h2:eq(0)'
							],
							(object) [
								'type'=>'remove',
								'root'=>'body',
								'selector'=>'p:eq(0)'
							]
						],
						'filter' => [],
						'fall_back' =>(object) []
					]
				]
			];
			
			
			
			//var_dump($profile);
			
			
			$post = get_post(42);//, $output, $filter 
			//var_dump($post);
			
			$shadow_profile_object_mapping_names = array('post_content','post_name','post_title','post_excerpt','post_date','post_category');
			$porfile_obj = array();
			foreach($shadow_profile_object_mapping_names as $name){
				$input_name = SHADOW_KEY."_map[$name]";
				$value = get_post_meta( $post->ID, '_'.SHADOW_KEY.'_map_'.$name, true );
				$block = json_decode($value);
				//var_dump($block);
				$porfile_obj[$name]=$block;
			}
			
			
			$profile=(object)$porfile_obj;
			/*$out = $this->get_content_part($html,$profile->post_content);
			/var_dump($out );
			var_dump((object)$porfile_obj);
			die();*/
			
			
			

			$catName = $this->get_content_part($html,$profile->post_category);
			//var_dump('$catName:'.$catName);

			//var_dump('$content:'.$content);die();
			//die();

			//EOF PATTERN AREA
			
			
			// Get user info
			$current_user = get_userdata( get_current_user_id() );
			$user         = $current_user;
	
			if($user) $author_id=$user->ID; // Outputs 1
			if($author_id<=0)die('user not found');
			
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
				'post_content'   => $this->get_content_part($html,$profile->post_content)
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
			if( !is_wp_error($post_id) ) {
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
			//all good let tie the post to the url
			$this->url_to_post($post_id,$url);
		}

		/**
		 * Find content from basic options
		 * 
		 * @param object $profile_obj
		 *
		 * @return string
		 * 
		 * @access public
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
			if( $grab == "text"){
				$output = $content_obj->text();
			}elseif($grab == "innerHTML"){
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
			
			
			// search for the match in the doc
			// - what it the $root element?
			// - what is the $selector to use?
			
			// if doc match and + strlen;
				// = if type is text push to ->text() else push to html;
				// if == "" && profile_fallback > 0
					// repeat for profile_fallback
					
			// foreach filter
				// $content = filter_content($content, $filter_id)
			// if $content != "" || $content == "" && profile_fallback <= 0
				// repeat for profile_fallback_id
		}

		/**
		 * Filter content from basic options
		 * 
		 * @param string $content
		 * @param object $filter_obj
		 *
		 * @return string
		 * 
		 * @access public
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
		 *
		 * @access public
		 */	
		public function crawl_from($url=NULL) {
			global $_params,$scrape_core;
			if(isset($_params['url'])){
				$options = get_option( 'scrape_options', array('crawl_depth'=>5) ); //@todo bring this option in line with the abstracted
				$depth = $options['depth']; 
				$this->traverse_all_urls($_params['url'],$depth);
			}
		}
			
		/**
		 * Start a test the crawl from this url, and display the title back.
		 * 
		 * @global class $scrape_core
		 * @global class $scrape_data
		 * @global array $_params
		 *
		 * @access public
		 */		
		public function test_crawler(){
			global $scrape_core,$scrape_data,$_params;
			$url = $_params['scrape_url'];
			$res = wp_remote_get($url);
			$page = wp_remote_retrieve_body( $res );
			if(empty($page)){
				$page = $scrape_data->scrape_get_content($url, 'body');
			}
			$doc = phpQuery::newDocument($page);
			$title = pq('title')->text();
			if(empty($title))$title=" error : no title- page didn't render";
			$scrape_core->message = array(
					'type' => 'updated',
					'message' => __('tested '.$url.' and return html &lt;title&gt; '.$title)
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
		 *
		 * @access public
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