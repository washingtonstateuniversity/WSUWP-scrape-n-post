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
		 * @global class $current_user
		 * @global class $scrape_core
		 * @global class $scrape_data
		 * @global array $_params
		 * 
		 * @param int $post_id
		 * @param int $target_id
		 *
		 * @access public
		 */	
		public function make_post($target_id=NULL, $arr = array()){
			global $wpdb, $current_user,$scrape_core,$scrape_data,$_params;
			
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

			$doc = phpQuery::newDocumentHTML($raw_html['body'], $currcharset);
			phpQuery::selectDocument($doc);
				
			//NOTE WHAT IS GOIGN TO BE DONE IS A EVAL FOR A PATTERN
			//remove placeholder

			$title = pq('html')->find('title');
			$title = $title->text();
			if($title==""){ $title = pq('#siteID')->find('h1:first')->text(); }
			if($title==""){ $title = pq('h2:first')->text(); }
			//var_dump($title);

			//should applie paterens by option
			$catName = pq('p:first')->html();
			$catarea = explode('<br>',$catName);
			$catName = trim($catarea[0]);
			//var_dump($catName);
			

			$content = pq('html')->find('div#main:eq(0)')->html();
			if($content==""){
				pq('body')->find('h3:first')->remove();
				pq('body')->find('p:first')->remove();
				pq('body')->find('h2:first')->remove();
				pq('body')->find('p:first')->remove();
				$doc->document->saveXML();
				$content = trim(pq('body')->html());
			}//var_dump($content);

			//die();
			

			
	
			//EOF PATTERN AREA
			
			
			// Get user info
			$current_user = get_userdata( get_current_user_id());
			$user               = $current_user;
	
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
			$complied = array(
				'post_type' => 'wsu_policy', // yes don't hard code in final   
				'post_title' => $title,
				'post_content' => $content,
				'post_status' => 'draft',
				'comment_status'	=>	'closed',
				'ping_status'		=>	'closed',
				'post_category' => array($cat_ID),
				'post_author' => $author_id,
			);	
			
			$arrs = array_merge($complied,$arr);
			//good so far let make the post
			if(isset($arrs['ID'])){
				$post_id = wp_update_post( $arrs );
				if( !is_wp_error($post_id) ) {
					$scrape_core->message = array(
						'type' => 'updated',
						'message' => __('Updated post')
					);
				}else{
					$scrape_core->message = array(
						'type' => 'error',
						'message' => __('Post error '.$post_id->get_error_message())
					);	
				}
					
			}else{
				$post_id = wp_insert_post($arrs);	
				if( !is_wp_error($post_id) ) {
					$scrape_core->message = array(
						'type' => 'updated',
						'message' => __('Adding Post')
					);
				} else {
					$scrape_core->message = array(
						'type' => 'error',
						'message' => __('Post error '.$post_id->get_error_message())
					);
				}
			}
			//all good let tie the post to the url
			$this->url_to_post($post_id,$url);
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
				$options = get_option( 'scrape_options', array('crawl_depth'=>5) );
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