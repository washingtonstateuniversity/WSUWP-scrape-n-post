<?php

/* lists helpers.. rethink this */
class crawl_list extends WP_List_Table {
    function __construct() {
        global $status, $page;
        parent::__construct(array(
            'singular' => 'wp_list_text_link',
            'plural' => 'wp_list_test_links',
            'ajax' => false
        ));
    }
    /*
    * Return no result copy
    */
    function no_items() {
        _e('No template found.');
    }
    /*
    * Return column default
    */
    function column_default($item, $column_name) {
        switch ($column_name) {
            case 'post_id':
					if($item->post_id==NULL)$item->post_id="--NA--";
					return stripslashes($item->post_id);
                break;
            case 'http_status':
					$imgurl = SCRAPE_URL."images/no-webshot.png"; // as if ==NULL
					if($item->http_status==200){
						//webshot would be the output url of te cache image created.
						$webshot = false;//as in it failed.. thou it's not here yet
						$imgurl = $webshot ? $webshot : SCRAPE_URL."images/no-webshot.png";
					}
					if($item->http_status==500){
						$imgurl = SCRAPE_URL."images/no-webshot-500.png";
					}
					if($item->http_status==503){
						$imgurl = SCRAPE_URL."images/no-webshot-503.png";
					}								
					if($item->http_status==404){
						$imgurl = SCRAPE_URL."images/no-webshot-404.png";
					}
					$item->http_status="<img src='{$imgurl}' />";
					return $item->http_status;
                break;
            case 'url':
                	return stripslashes($item->url);
                break;
            case 'added_date':
                	return date('d-m-Y', strtotime($item->added_date));
                break;
            default:
               		return print_r($item, true);
        }
    }
    /*
    * Set table column
    */
    function get_columns() {
        $columns = array(
            'cb' => '<input type="checkbox" />',
			'http_status' => __('Url Status', 'mylisttable'),
			'url' => __('URL', 'mylisttable'),
            'post_id' => __('Post ID', 'mylisttable'),
            'added_date' => __('Added Date', 'mylisttable')
        );
        return $columns;
    }
    /*
    * Set sortable columns
    */
    public function get_sortable_columns() {
        return $sortable = array(
            'target_id' => array( 'target_id', true ),
            'post_id' => array( 'post_id', true ),
            'added_date' => array( 'added_date', true )
        );
    }
    /*
    * Set template name column structure
	* note url is the id on perpose
    * @item - object
    */
    function column_url($item) {
		if($item->post_id==NULL || $item->post_id==0){
			$arr_params = array( 'url' => $item->url, 'scrape_action' => 'topost' );
			$topostlink   = add_query_arg($arr_params);
		}
        $arr_params = array( 'url' => $item->target_id, 'scrape_action' => 'ignore' );
        $ignorelink = add_query_arg($arr_params);

        $arr_params = array( 'url' => $item->url, 'scrape_action' => 'crawlhere' );
        $crawlherelink = add_query_arg($arr_params);
		
		if($item->post_id==NULL || $item->post_id==0){
			$actions['topost']='<a href="' . $topostlink . '">Make Post</a>';
			$actions['ignore']='<a href="' . $ignorelink . '">Ignore</a>';
		}
		$actions['crawlhere']='<a href="' . $crawlherelink . '">Crawl</a>';
		$actions['view']='<a href="' . $item->url . '" target="_blank">View</a>';

        return sprintf('<strong>%1$s</strong> %2$s', $item->url, $this->row_actions($actions));
    }

    /*
    * Set template name column structure
    * @item - object
    */
    function column_post_id($item) {
		if($item->post_id>0){
			$arr_params = array( 'post_id' => $item->post_id,'url' => $item->target_id, 'scrape_action' => 'reimport' );
			$reimportlink   = add_query_arg($arr_params);
			
			$arr_params = array( 'url' => $item->target_id, 'scrape_action' => 'detach' );
			$detachlink = add_query_arg($arr_params);
			
			$actions    = array(
				'reimport' => '<a href="' . $reimportlink . '">Reimport Post</a>',
				'detach' => '<a href="' . $detachlink . '">Detach URL Relation</a>'
			);
		}else{
			$actions=array();
		}
        return sprintf('<strong>%1$s</strong> %2$s', $item->post_id, $this->row_actions($actions));
    }	
	
	
	
	
    /*
    * Set table bulk action
    */
    function get_bulk_actions() {
        $actions = array(
            'ignore' => 'Ignore',
			'topost' => 'Make Post',
			'reimport' => 'Reimport Posts',
			'detach' => 'Detact URL Relation'
        );
        return $actions;
    }
    /*
    * Set culumn checkbox
    * @item - object
    */
    function column_cb($item) {
        return sprintf('<input type="checkbox" name="url[]" value="%s" />', $item->target_id);
    }
    /*
    * Process action performed
	* @todo post for _params but note url is the id on perpose
    */
    function process_bulk_action() {
        global $scrape_actions,$_param;
        if ('ignore' === $this->current_action()) {
            if (count($_param['url']) > 0) {
                foreach ($_param['url'] as $url) {
					//add ignore flag
                    //$scrape_actions->update_queue($url);
                }
            }
        }
        if ('topost' === $this->current_action()) {
            if (count($_param['url']) > 0) {
                foreach ($_param['url'] as $url) {
					$scrape_actions->make_post($url,array());
                }
            }
        }
        if ('reimport' === $this->current_action()) {
            if (count($_param['url']) > 0) {
                foreach ($_param['url'] as $url) {
					$scrape_actions->reimport_post($url);
                }
            }
        }	
        if ('detach' === $this->current_action()) {
            if (count($_param['url']) > 0) {
                foreach ($_param['url'] as $url) {
					$scrape_actions->detach_post($url);
                }
            }
        }		
		
		
		
			
    }
    /*
    * Process action performed
    */
    function process_link_action() {
        global $scrape_actions,$_params;
        if (isset($_params['scrape_action']) && $_params['scrape_action'] == 'ignore') {
			//add ignore flag
            $scrape_actions->ignore_url();
        }
        if (isset($_params['scrape_action']) && $_params['scrape_action'] == 'topost') {
			$scrape_actions->make_post();
        }
        if (isset($_params['scrape_action']) && $_params['scrape_action'] == 'crawlhere') {
			//$scrape_data->make_post($_GET['url']);
			
			//add change import data
			//$scrape_data->update_queue($_GET['url']);
        }
        if (isset($_params['scrape_action']) && $_params['scrape_action'] == 'reimport') {
			$scrape_actions->reimport_post();
        }
        if (isset($_params['scrape_action']) && $_params['scrape_action'] == 'detach') {
			//remove post id
			$scrape_actions->detach_post();
        }
    }
	
    /*
    * Prepage table items
	* @todo reduce sql
    */
    function prepare_items() {
        global $wpdb, $_wp_column_headers,$scrape_actions,$_params;
        $screen = get_current_screen();
        $this->process_bulk_action();
        $this->process_link_action();
        $query   = "SELECT * FROM " . $wpdb->prefix . "scrape_n_post_queue";
		if(isset($_GET['ignore'])){
			$query .= ' WHERE `ignore`='.mysql_real_escape_string($_params['ignore']).' ';
		}
        $orderby = !empty($_params["orderby"]) ? mysql_real_escape_string($_params["orderby"]) : 'ASC';
        $order   = !empty($_params["order"]) ? mysql_real_escape_string($_params["order"]) : '';
        if (!empty($orderby) & !empty($order)) {
            $query .= ' ORDER BY ' . $orderby . ' ' . $order;
        }

        $totalitems = $wpdb->query($query);
        $perpage    = 50;
        $paged      = !empty($_params["paged"]) ? mysql_real_escape_string($_params["paged"]) : '';
        if (empty($paged) || !is_numeric($paged) || $paged <= 0) {
            $paged = 1;
        }
        $totalpages = ceil($totalitems / $perpage);
        if (!empty($paged) && !empty($perpage)) {
            $offset = ($paged - 1) * $perpage;
            $query .= ' LIMIT ' . (int) $offset . ',' . (int) $perpage;
        }
        $this->set_pagination_args(array(
            "total_items" => $totalitems,
            "total_pages" => $totalpages,
            "per_page" => $perpage
        ));
        $columns               = $this->get_columns();
        $hidden                = array();
        $sortable              = $this->get_sortable_columns();
        $this->_column_headers = array(
            $columns,
            $hidden,
            $sortable
        );
        $this->items           = $wpdb->get_results($query);
    }
}
?>