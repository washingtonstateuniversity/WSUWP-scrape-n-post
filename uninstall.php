<?php
//if uninstall not called from WordPress exit
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit();
}
$option_name = 'scrape_options';

delete_option( $option_name );
delete_site_option( $option_name );  // For site options in multisite

//drop a custom db table
//@todo note that we'll removing the tables later and replacing that with a removal of the posts as they would be broken stuff of a post
global $wpdb;
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}scrape_n_post_queue" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}scrape_n_post_crawler_templates" );
//note in multisite looping through blogs to delete options on each blog does not scale. You'll just have to leave them.  This is per WP docs