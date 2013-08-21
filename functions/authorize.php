<?php
include 'oauth/twitteroauth.php';
//get stored consumer key and secret plus request token array
$consumer_key = get_option('wtf_consumer_key');
$consumer_secret = get_option('wtf_consumer_secret');
$wtf_request_array = get_option('wtf_request_array');
$request_token = $wtf_request_array[request_token];
$request_token_secret = $wtf_request_array[request_token_secret];


//submit request token to twitter in exchange for proper permanent auth token
$to = new TwitterOAuth($consumer_key, $consumer_secret,$request_token, $request_token_secret);
$tok = $to->getAccessToken($_REQUEST['oauth_verifier']);

//get permanent token into proper array token and token secret
$final_token = $tok['oauth_token'];
$final_token_secret = $tok['oauth_token_secret'];
$wtf_token_array = array( 'token' => $final_token, 'token_secret' => $final_token_secret );

//remove old token request array
delete_option('wtf_request_array');

$screenname = $tok['screen_name'];

//store permanent token and token secret for plugin use
update_option('wtf_token_array', $wtf_token_array);
update_option('wtf_screenname', $screenname);

//send user to main Twitter Plugin Page
$return_url = site_url().'/wp-admin/options-general.php?page=wtf-twitter&auth=1';
header("Location: $return_url");
?>