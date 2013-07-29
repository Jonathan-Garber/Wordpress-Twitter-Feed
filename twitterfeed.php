<?php

/*

	Plugin Name: Wordpress Twitter Feed	
	Description: Custom plugin to pull in your Twitter Feed
	Author: Jonathan-Garber
	Author URI: http://github.com/Jonathan-Garber
	Version: 1.0.1

*/

/*
Requires
*/
require_once 'functions/functions.php';

add_filter('query_vars','tweet_add_trigger');
function tweet_add_trigger($vars) {
    $vars[] = 'tweety_auth';
    return $vars;
}

add_action('template_redirect', 'tweet_trigger_check');

function tweet_trigger_check() {
	if (get_query_var('tweety_auth') == 1) {
		include 'functions/oauth/twitteroauth.php';
		$consumer_key = $_POST['consumer_key'];
		$consumer_secret = $_POST['consumer_secret'];
		
		//store consumer key and secret
		update_option('tweety_consumer_key', $consumer_key);
		update_option('tweety_consumer_secret', $consumer_secret);
		
		//set call back url to make it come back to this site for final auth steps
		$oauth_callback = get_home_url().'/?tweety_auth=2';
		
		//connect to twitter to get our auth request token
		$connection = new TwitterOAuth($consumer_key, $consumer_secret);
		$request_token = $connection->getRequestToken($oauth_callback);
	
		//get request token data and store in array for future use
		$twitter_request_token = $request_token['oauth_token'];
		$twitter_request_token_secret = $request_token['oauth_token_secret'];
		
		$token_request_array = array(
		'request_token'=>$twitter_request_token, 
		'request_token_secret'=>$twitter_request_token_secret
		);
	
		update_option('tweety_request_array', $token_request_array);
		
		//get auth url and send user to it 
		$authenticateUrl = $connection->getAuthorizeURL($twitter_request_token);
		header("Location: $authenticateUrl");		
		exit;
	}
	// Twitter returns user here from auth url to finish authorization
	if (get_query_var('tweety_auth') == 2) {
		include 'functions/authorize.php';
		exit;
	}
}

function tweet_plugin_menu() {
	add_submenu_page( 'options-general.php', 'Twitter', 'Twitter', 'manage_options', 'twitter', 'tweet_menu_main' );
}
add_action('admin_menu', 'tweet_plugin_menu');

function tweet_menu_main(){
	include 'pages/main_menu.php';
}


//CRON
add_action( 'twitter_auto', 'twitter_update_auto' );

//create custom schedule for every 5 mins
add_filter( 'cron_schedules', 'twitter_5mins');
function twitter_5mins($schedules) { 
    $schedules['minutes_5'] = array(
	'interval'=>300, 
	'display'=>'5 minutes'
	); 
    return $schedules;
}
//end CRON

function twitter_register_post_type() {
  $labels = array(
    'name' => 'Tweets',
    'singular_name' => 'Tweet',
    'add_new' => 'Add New',
    'add_new_item' => 'Add New Tweet',
    'edit_item' => 'Edit Tweet',
    'new_item' => 'New Tweet',
    'all_items' => 'All Tweets',
    'view_item' => 'View Tweet',
    'search_items' => 'Search Tweets',
    'not_found' =>  'No books found',
    'not_found_in_trash' => 'No Tweets found in Trash', 
    'parent_item_colon' => '',
    'menu_name' => 'Tweets'
  );

  $args = array(
    'labels' => $labels,
    'public' => false,
    'publicly_queryable' => true,
    'show_ui' => false, 
    'show_in_menu' => false, 
    'query_var' => true,
    'rewrite' => array( 'slug' => 'tweets' ),
    'capability_type' => 'post',
    'has_archive' => false, 
    'hierarchical' => false,
    'menu_position' => null,
    'supports' => array( 'title', 'editor', 'author', 'thumbnail', 'excerpt', 'comments' )
  ); 

  register_post_type( 'twitter', $args );
  flush_rewrite_rules();
}
add_action( 'init', 'twitter_register_post_type' );

if (is_admin()) { // note the use of is_admin() to double check that this is happening in the admin
	
	include_once 'functions/updater.php';
    $config = array(
        'slug' => plugin_basename(__FILE__),
        'proper_folder_name' => 'Wordpress-Twitter-Feed',
        'api_url' => 'https://api.github.com/repos/Jonathan-Garber/Wordpress-Twitter-Feed',
        'raw_url' => 'https://raw.github.com/Jonathan-Garber/Wordpress-Twitter-Feed/master',
        'github_url' => 'https://github.com/Jonathan-Garber/Wordpress-Twitter-Feed',
        'zip_url' => 'https://github.com/Jonathan-Garber/Wordpress-Twitter-Feed/zipball/master',
        'sslverify' => true,
        'requires' => '3.5.0',
        'tested' => '3.5.2',
        'readme' => 'readme.txt',
        'access_token' => '',
    );
    new WP_GitHub_Updater($config);
}
