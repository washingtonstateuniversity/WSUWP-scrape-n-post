<?php


if ( ! defined( 'ABSPATH' ) ) exit;// Exit if accessed directly
if ( ! class_exists( 'scrape_data' ) ) {
	class scrape_data extends scrape_core {
		public $seen = array();
		public $wanted = array();
		function __construct() { }
		
		/**
		 * Get the options for the plugin as set for each run.
		 * 
		 * @reutrun array
		 *
		 * @TODO should pull from inline options right before run then merge the options
		 */
		public function get_options(){
			$plugin_option = get_option('scrape_options', array(
				'crawl_depth' => 5,
				'on_error' => 'error_hide',
				'custom_error' => 'Unable to fetch data',
				'useragent' => "Scrape-N-Post bot -- NOT A DDoS",
				'timeout' => 2,
				'limit_scraps'=>'-1',
				'interval'=>1,
				'retry_interval'=>2,
				'retry_limit'=>3,
				'timeout_limit'=>300,
				'memory_limit'=>'-1',
				'xdebug_fix'=>1,
				'add_post_on_crawl'=>0,
				'post_type'=>'post',
			));	
			return $plugin_option;
		}

		/**
		 * Get the profile jig which will have all the data patterns to match the page being scraped.
		 * 
		 * @param int $id
		 * 
		 * @return array
		 *
		 * @TODO this is just laying out the ground work still
		 */
		public function get_scraping_profile($id=0){
			$default_profile = array();
			$found_profile = array();
			if($id>0){
				$found_profile = array();
			}
			$profile = array_merge($default_profile,$found_profile);
			return $profile;
		}





		/**
		 * Get all the urls of a page.
		 * 
		 * @return array All the urls that are on a page
		 */
		public function get_all_urls($url, $depth = 5) {
			$this->traverse_all_urls($url,$depth);
			return $this->wanted;
		}
		
		
		/**
		 * Look through a page and grab and store based on the options set.
		 * 
		 * @global class $scrape_actions
		 * @global class $scrape_core
		 * @global array $_params
		 * 
		 * @param string $url Starting url
		 * @param int $depth Starting depth pool, -- with interation
		 * 
		 * @TODO should store the content if option allows for it, create option to not allow for storeage, and when to not allow
		 */
		public function traverse_all_urls( $url,  $depth = 5, $idx = 0 ) {
			global $_params,$scrape_core,$scrape_actions;
			
			if ( isset($this->seen["{$url}"]) 
				 || $depth === 0 
				 || strpos($url,'javascript:newWindow') !== false
				 ) {
				return;
			}
			$this->seen["{$url}"] = true;
			$scrape_options = get_option('scrape_options');
			
			if($scrape_options['limit_scraps']<=$idx){
				return;
			}
			
			
			$urls = $this->get_urls($url);
			foreach($urls as $href=>$obj ) {
				if($obj['type']=='page'){
					if (0 !== strpos($href, 'http')) {
						$relative=false;
						if(substr($href,0,1)!='/'){
							$relative=true;
						}
						$path = '/' . ltrim($href, '/');
	
						$parts = parse_url($url);
						$href = $parts['scheme'] . '://';
						if (isset($parts['user']) && isset($parts['pass'])) {
							$href .= $parts['user'] . ':' . $parts['pass'] . '@';
						}
						$href .= $parts['host'];
						if (isset($parts['port'])) {
							$href .= ':' . $parts['port'];
						}
						if($relative){
							$pathparts=explode('/',$parts['path']);
							$last=end($pathparts);
							if(strpos($last,'.')!==false){
								array_pop($pathparts);
							}
							$urlpath=implode('/',$pathparts);
							$href .= '/'.trim($urlpath, '/').'/'.trim($path, '/');
						}else{
							$href .= '/'.trim($path, '/');	
						}
					}
				}
				if(strpos($href,'.htm/')!==false){
					die($href);
				}
				if (isset($this->seen["{$href}"]) && $this->seen["{$href}"]) {
					continue;
				}
				if( $obj['type'] == "page" ){
					$exist=$scrape_core->_is_exist('url',$href);

					if(!$exist){
						
						$raw_html = wp_remote_get($href);//$scrape_data->scrape_get_content($id, 'html');
						//var_dump($raw_html);die();
						if(is_a($raw_html, 'WP_Error') || $raw_html=="ERROR::404"){
							$scrape_core->message = array(
								'type' => 'error',
								'message' => __('Failed '.print_r($raw_html))
							);
						}
						
						$scrape_actions->add_queue(array(
							'url'=>$href,
							'type'=>$obj['type'],
							'http_status'=>$raw_html['response']['code'],
							'html'=>($raw_html['response']['code']!=200)?"":$raw_html['body']
						));
						
						if($scrape_options['add_post_on_crawl']){
							$scrape_actions->make_post($href);
						}
						$idx++;
					}
					if($scrape_options['limit_scraps']<=$idx){
						return;
					}
					$this->wanted[$href]=$obj;
					$this->traverse_all_urls($href,$depth - 1,$idx);
				}
			}
			sleep( $scrape_options['interval'] );
			echo $url;
		}
	
		/**
		 * Get all urls from links in a page.
		 * 
		 * @global class $scrape_data
		 * @global class $scrape_core
		 * @global array $_params
		 * 
		 * @param string $url starting url of target content
		 *
		 * @return array $url all url found in the page
		 *
		 * @TODO should pull from store the content if option allows for it, create option to not allow for storage, and when to not allow
		 */
		public function get_urls($url){
			global $scrape_core,$scrape_data,$_params;
			$res = wp_remote_get($url);
			$page=wp_remote_retrieve_body( $res );
			if(empty($page)){
				$page=$scrape_data->scrape_get_content($url, 'body');
			}
			if($page=="ERROR::404"){
				var_dump($url);
				die();
			}
			
		
			$content_obj = htmlqp($page, 'body', array('ignore_parser_warnings' => TRUE));
			$as = $content_obj->find('a');
			
			//$doc = phpQuery::newDocument($page);
			//$as = pq('a');
			$urls=array();
			foreach($as as $a) {
				$link_url=$a->attr('href');
				if(!empty($link_url)){
					$type='page';
					if(!$this->is_localurl($link_url)){
						$type='external';
					}elseif($this->is_fileurl($link_url)){
						$type='file';
					}elseif($this->is_email($link_url)){
						$type='email';
					}elseif($this->is_anchor($link_url)){
						$type='anchor';
					}
					$urls["{$link_url}"]=array('type'=>$type);
				}
			}
			return $urls;
			//if(!empty($page)){}die('had nothing');
		}

		/**
		 * build link object.
		 * 
		 * @TODO remove
		 */
		public function build_link_object(){
	
			return array();
		}
		
		/**
		 * Wrapper function to fetch content, select / query it and parse it.
		 * 
		 * @param string $url
		 * @param string $selector (optional) Selector
		 * @param string $xpath (optional) XPath
		 * @param array $scrapeopt Options
		 *
		 * @return string
		 *
		 * @TODO Refactor this alot!!
		 */
		function scrape_get_content($url, $selector = '', $xpath = '', $scrapeopt = '') {
			$scrape_options = get_option('scrape_options');
			//$scrape_options = $scrape_options['scrape_options'];
			$default_scrapeopt = array(
					'postargs' => '',
					'user_agent' => $scrape_options['useragent'],
					'timeout' => $scrape_options['timeout'],
					'output' => 'html',
					'clear_regex' => '',
					'clear_selector' => '',
					'replace_regex' => '',
					'replace_selector' => '',
					'replace_with' => '',
					'replace_selector_with' => '',
					'basehref' => '',
					'striptags' => '',
					'removetags' => '',
					'callback' => '',
					'debug' => '1',
					'htmldecode' => ''
			);
			$scrapeopt = wp_parse_args( $scrapeopt, $default_scrapeopt );
			unset($scrapeopt['url']);
			unset($scrapeopt['selector']);
			unset($scrapeopt['xpath']);
			//print('getting content for <h5>'.$url.'</h5>');
			if(!isset($scrapeopt['request_mt']))$scrapeopt['request_mt']=microtime(true);
	
			if(empty($url)) {
				//on error
			}
		
			if( strstr($url, '___QUERY_STRING___') ) {
				$url = str_replace('___QUERY_STRING___', $_SERVER['QUERY_STRING'], $url);
			} else {
				$url = preg_replace_callback('/___(.*?)___/', create_function('$matches','return $_REQUEST[$matches[1]];'), $url);
			}
		
			if( strstr($scrapeopt['postargs'], '___QUERY_STRING___') ) {
				$scrapeopt['postargs'] = str_replace('___QUERY_STRING___', $_SERVER['QUERY_STRING'], $scrapeopt['postargs']);
			} else {
				$scrapeopt['postargs'] = preg_replace_callback('/___(.*?)___/', create_function('$matches','return $_REQUEST[$matches[1]];'), $scrapeopt['postargs']);
			}
		
			//$cache_args['cache'] = $scrapeopt['cache'];
			$cache_args=array();
			if ( !empty($scrapeopt['postargs']) ) {
				$http_args['headers'] = $scrapeopt['postargs'];
				$cache_args['headers'] = $scrapeopt['postargs'];
			}
			$http_args['user-agent'] = $scrapeopt['user_agent'];
			$http_args['timeout'] = $scrapeopt['timeout'];
			//print('making request content for <h5>'.$url.'</h5>');
			$response = $this->scrape_remote_request($url, $cache_args, $http_args);
			//var_dump($response);
			if( !is_wp_error( $response ) ) {
				$raw_html = $response['body'];
				if( !empty($selector) ) {
					$raw_html = $this->scrape_get_html_by_selector($raw_html, $selector, $scrapeopt['output']);
					 if( !is_wp_error( $raw_html ) ) {
						 $filtered_html = $raw_html;
					 } else {
						 $err_str = $raw_html->get_error_message();
					 }
				}
				if( !empty($err_str) ) {
					//log error
				}
				return $raw_html;
			} else {
				return "ERROR::".$response['response']['code'];
			}
		
		}
		
		/**
		 * Retrieve the raw response from the HTTP request (or its cached version).
		 * 
		 * Wrapper function to wp_remote_request().
		 * 
		 * @param string $url Site URL to retrieve.
		 * @param array $cache_args Optional. Override the defaults.
		 * @param array $http_args Optional. Override the defaults.
		 * 
		 * @return WP_Error|array The response or WP_Error on failure.
		 */
		function scrape_remote_request($url, $cache_args = array(), $http_args = array(),$retry_limit=false) {
			//print('starting request <h5>'.$url.'</h5>');
			//var_dump($http_args);
			$scrape_options = get_option('scrape_options');
			if(!$retry_limit){
				$retry_limit = $scrape_options['retry_limit'];
			}
			
			$default_cache_args = array(
				'cache' => 60,
				'on-error' => 'cache'
			);
			$default_http_args = array(
				'user-agent' => 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.1)'
			);
			$cache_args = wp_parse_args( $cache_args, $default_cache_args );
			$http_args = wp_parse_args( $http_args, $default_http_args );
			if(isset($cache_args['headers']) && $cache_args['headers']) {
				$transient = md5($url.serialize($cache_args['headers']));
			} else {
				$transient = md5($url);
			}
		
			
			//print('doing request <h5>'.$url.'</h5>');
			//var_dump($http_args);
			$response = wp_remote_request($url, $http_args);
			if( !is_wp_error( $response ) ) {
				if($cache_args['cache'] != 0)
					set_transient($transient, $response, $cache_args['cache'] * 60 ); // set back up
				@$response['headers']['source'] = 'WP_Http';
				return $response;
			} else {
				
				var_dump($response);
				var_dump($url);
				var_dump($cache_args);
				var_dump($http_args);
				var_dump($retry_limit);
				if($retry_limit>0){
					sleep($scrape_options['retry_interval']);
					print('retrying');
					return $this->scrape_remote_request($url,$cache_args,$http_args,$retry_limit-1);	
				}
				die("failed on {$url}");
				return $response['response']['code'];
			}
		}
	
		/**
		 * Get HTML from a web page using selector.
		 *
		 * @param string $raw_html Raw HTML
		 * @param string $selector Selector
		 * @param string $output html or text
		 *
		 * @return string
		 */
		function scrape_get_html_by_selector($raw_html, $selector, $output = 'html'){
			// Parsing request using phpQuery
			//$currcharset = get_bloginfo('charset');
			//require_once 'phpQuery.php';
			//$phpquery = phpQuery::newDocumentHTML($raw_html, $currcharset);
			//phpQuery::selectDocument($phpquery);
			
			//$html5 = new HTML5();
			//$dom = $html5->loadHTML($raw_html);
			
			
			if($output == 'text'){
				return html5qp($raw_html,$selector)->text();
			}
			if($output == 'html'){
				return html5qp($raw_html,$selector)->html();
			}
			if( empty($output) ){
				return new WP_Error('scrape_get_html_by_selector_failed', "Error parsing selector: $selector");
			}
		}
	
		
	
		
		/**
		 * take @param $url and insure it's a fully qualified URL.
		 * 
		 * NOTE: this only does local domains
		 * @param string $url possibly drity url
		 * 
		 * @return string Clean url
		 */	
		public function normalize_url($url){
			if(!$this->is_localurl($url)){
				return $url;
			}
			if($this->is_email($url)){
				return $url;
			}
			if($this->is_anchor($url)){
				return $url;
			}
			$baseurl=str_replace('http://'.$this->rootUrl,'',$url);
			if(substr($baseurl,0,1)!='/'){
				if(strpos($baseurl,'/')!==false ){
					 $baseparts=explode('/',$baseurl); 
					 $file = end ($baseparts);
				}else{
					$file =$baseurl;
				}
				$newbaseurl='http://'.$this->rootUrl;
				if($file){
					//$fileparts=explode( $file, $this->currentUrl[$depth]);
					//$newbaseurl = $fileparts[0];
				}
				$url=trim($newbaseurl,'/').'/'.trim($file,'/');
			}else{
				$url='http://'.$this->rootUrl.$baseurl;
			}
			$url=$this->normalize_url_format($url);
			return $url;	
		}
		

		/*
		 * corrects any oddities of @param $url.
		 * 
		 * EX: 'HtTp://UsEr:PaSs@wWW.ExAmPle.cOm:80/BlaH' becomes
		 * 'http://UsEr:PaSs@www.example.com:80/BlaH'
		 * 
		 * @param string $url
		 *
		 * @return string
		 *
		 * @todo this should be an optional part, an pull from a type list
		 */
		public function normalize_url_format($url){
			$url=preg_replace( '#(^[a-z]+://)(.+@)?([^/]+)(.*)$#ei', "strtolower('\\1').'\\2'.strtolower('\\3').'\\4'", $url);	
			return $url;
		}

		/*
		 * test if @param $url is not a file or emai.
		 * 
		 * @param string $url
		 *
		 * @return boolean
		 *
		 * @todo this should be an optional part, an pull from a type list
		 */
		public function is_page($url=false){
			if(	$url === false || is_email($url)!==false || is_fileurl($url)!==false ){
				return false;
			}
			return true;
		}
		
		/*
		 * test if @param $url is a comman media file.
		 * 
		 * @param string $url
		 *
		 * @return boolean
		 * 
		 * @todo this should be an optional part, an pull from a type list
		 */
		public function is_fileurl($url=false){
			if(	$url === false || (strpos($url,'.pdf')===false && strpos($url,'.jpg')===false && strpos($url,'.gif')===false && strpos($url,'.png')===false && strpos($url,'.css')===false && strpos($url,'.js')===false) ){
				return false;
			}
			return true;
		}

		/*
		 * test if @param $url is an email.
		 * 
		 * @param string $url
		 *
		 * @return boolean
		 */
		public function is_email($url=false){
			if( $url === false || strpos($url,'mailto:')===false ){
				return false;
			}
			return true;
		}
		
		/*
		 * test if @param $url is an anchor.
		 * 
		 * @param string $url
		 *
		 * @return boolean
		 */
		public function is_anchor($url=false){
			if(substr($url,0,1) == '#'){
				return true;
			}
			return false;
		}
		
		/*
		 * test if @param $url is an internal url to the site.
		 * 
		 * @param string $url
		 *
		 * @return boolean
		 */
		public function is_localurl($url){
			if( substr($url,0,4) == 'http' && strpos($url,$this->rootUrl)===false ){
				return false;
			}
			return true;
		}

		/*
		 * Get post data.
		 * 
		 * It's worth noting that any out put here will print into the pdf.  If the PDF can't be 
		 * read then look at it in a text editor like Notepad, where you will see the php errors.
		 *
		 * @global array $_params
		 * 
		 * @param int $id
		 *
		 * @return object
		 */
		public function query_posts($id = NULL) {
			global $_params;
			$type = isset($_params['type'])?$_params['type']:"post";
			$args = array(
				'post_type' => $type,
				'posts_per_page' => -1,
				'order' => 'DESC'
			);
			if ($id !== NULL) {
				$args['p'] = $id;
			}
			if (isset($_params['user']) && count($_params['user']) > 0) {
				$args['author'] = implode(',',$_params['user']);
			}
			if (isset($_params['status']) && count($_params['status']) > 0) {
				$args['post_status'] = implode(',',$_params['status']);
			}
			if (isset($_params['cat']) && count($_params['cat']) > 0) {
				$args['cat'] = implode(',',$_params['cat']);
			}
			add_filter('posts_where', array( $this, 'filter_where' ));
			$result = new WP_Query($args);
			
			return $result->posts;
		}
		
		/*
		 * Return query filter.
		 *
		 * @global array $_params
		 * 
		 * @param string $where
		 * 
		 * @return string
		 */
		public function filter_where($where = '') {
			global $_params;
			if (isset($_params['from']) && $_params['from'] != '') {
				$from = date('Y-m-d', strtotime($_params['from']));
				$where .= ' AND DATE_FORMAT( post_date , "%Y-%m-%d" ) >= "' . $from . '"';
			}
			if (isset($_params['to']) && $_params['to'] != '') {
				$to = date('Y-m-d', strtotime($_params['to']));
				$where .= ' AND DATE_FORMAT( post_date , "%Y-%m-%d" ) <= "' . $to . '"';
			}
			return $where;
		}
		
		/*
		 * Return detected meta keys.
		 *
		 * @global class $wpdb
		 * 
		 * @param string $post_type
		 * 
		 * @return array
		 */
		public function get_meta_keys( $post_type='post' ){
			global $wpdb;
			$query = "
				SELECT DISTINCT($wpdb->postmeta.meta_key) 
				FROM $wpdb->posts 
				LEFT JOIN $wpdb->postmeta 
				ON $wpdb->posts.ID = $wpdb->postmeta.post_id 
				WHERE $wpdb->posts.post_type = '%s' 
				AND $wpdb->postmeta.meta_key != '' 
				AND $wpdb->postmeta.meta_key NOT RegExp '(^[_0-9].+$)' 
				AND $wpdb->postmeta.meta_key NOT RegExp '(^[0-9]+$)'
			";
			$meta_keys = $wpdb->get_col($wpdb->prepare($query, $post_type));
			set_transient($post_type.'_meta_keys', $meta_keys, 60*60*24); # 1 Day Expiration
			return $meta_keys;
		}
		/*
		 * Return detected meta keys.
		 *
		 * @global class $wpdb
		 * 
		 * @return array
		 */
		public function get_all_meta_keys($unique=true){
			global $wpdb;
			$post_types      = get_post_types(array(
				'public'   => true,
			),'names' , 'and' );
			
			$meta_keys = array();
			foreach( $post_types as $post_type){
				$meta_keys = array_merge($meta_keys,$this->get_meta_keys( $post_type ));
			}
			return $unique ? array_unique ($meta_keys) : $meta_keys;
		}	
		
		
		
	}
	global $scrape_data;
	$scrape_data = new scrape_data();
}