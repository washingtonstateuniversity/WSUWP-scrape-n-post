<?php

/* lists helpers.. rethink this */
class template_list extends WP_List_Table {
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
        $user_info = get_userdata($item->create_by);
        $user_name = $user_info->data->user_login;
        switch ($column_name) {
            case 'template_name':
                return stripslashes($item->template_name);
                break;
            case 'template_description':
                return stripslashes($item->template_description);
                break;
            case 'create_by':
                return $user_name;
                break;
            case 'create_date':
                return date('d-m-Y', strtotime($item->create_date));
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
            'template_name' => __('Name', 'mylisttable'),
            'template_description' => __('Description', 'mylisttable'),
            'create_by' => __('Created By', 'mylisttable'),
            'create_date' => __('Create Date', 'mylisttable')
        );
        return $columns;
    }
    /*
    * Set sortable columns
    */
    public function get_sortable_columns() {
        return $sortable = array(
            'template_id' => array( 'template_id', true ),
            'template_name' => array( 'template_name', true ),
            'create_date' => array( 'create_date', true )
        );
    }
    /*
    * Set template name column structure
    @item - object
    */
    function column_template_name($item) {
        $arr_params = array( 'template' => $item->template_id, 'scrape_action' => 'edit' );
        $editlink   = add_query_arg($arr_params);
		
        $arr_params = array( 'template' => $item->template_id, 'scrape_action' => 'delete' );
        $deletelink = add_query_arg($arr_params);
		
        $actions    = array(
            'edit' => '<a href="' . $editlink . '">Edit</a>',
            'delete' => '<a href="' . $deletelink . '">Delete</a>'
        );
        return sprintf('<strong><a href="' . $editlink . '" title="Edit">%1$s</a></strong> %2$s', $item->template_name, $this->row_actions($actions));
    }
    /*
    * Set table bulk action
    */
    function get_bulk_actions() {
        $actions = array(
            'delete' => 'Delete'
        );
        return $actions;
    }
    /*
    * Set culumn checkbox
    * @item - object
    */
    function column_cb($item) {
        return sprintf('<input type="checkbox" name="template[]" value="%s" />', $item->template_id);
    }
    /*
    * Process action performed
	* @todo post for _params
    */
    function process_bulk_action() {
        global $scrape_templates;
        if ('delete' === $this->current_action()) {
            if (count($_POST['template']) > 0) {
                foreach ($_POST['template'] as $template) {
                    $scrape_templates->delete_template($template);
                }
            }
        }
    }
    /*
    * Process action performed
    */
    function process_link_action() {
        global $scrape_templates;
        if (isset($_GET['scrape_action']) && $_GET['scrape_action'] == 'delete') {
            $scrape_templates->delete_template($_GET['template']);
        }
    }
    /*
    * Prepage table items
	* @todo reduce sql
    */
    function prepare_items() {
        global $wpdb, $_wp_column_headers;
        $screen = get_current_screen();
        $this->process_bulk_action();
        $this->process_link_action();
        $query   = "SELECT * FROM " . $wpdb->prefix . "scrape_n_post_crawler_templates";
        $orderby = !empty($_GET["orderby"]) ? mysql_real_escape_string($_GET["orderby"]) : 'ASC';
        $order   = !empty($_GET["order"]) ? mysql_real_escape_string($_GET["order"]) : '';
        if (!empty($orderby) & !empty($order)) {
            $query .= ' ORDER BY ' . $orderby . ' ' . $order;
        }
        $totalitems = $wpdb->query($query);
        $perpage    = 10;
        $paged      = !empty($_GET["paged"]) ? mysql_real_escape_string($_GET["paged"]) : '';
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