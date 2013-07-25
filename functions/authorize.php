<?php
include 'oauth/twitteroauth.php';
//get stored consumer key and secret plus request token array
$consumer_key = get_option('tweety_consumer_key');
$consumer_secret = get_option('tweety_consumer_secret');
$tweety_request_array = get_option('tweety_request_array');
$request_token = $tweety_request_array[request_token];
$request_token_secret = $tweety_request_array[request_token_secret];


//submit request token to twitter in exchange for proper permanent auth token
$to = new TwitterOAuth($consumer_key, $consumer_secret,$request_token, $request_token_secret);
$tok = $to->getAccessToken($_REQUEST['oauth_verifier']);

//get permanent token into proper array token and token secret
$final_token = $tok['oauth_token'];
$final_token_secret = $tok['oauth_token_secret'];
$tweety_token_array = array( 'token' => $final_token, 'token_secret' => $final_token_secret );

//remove old token request array
delete_option('tweety_request_array');

$screenname = $tok['screen_name'];

//store permanent token and token secret for plugin use
update_option('tweety_token_array', $tweety_token_array);
update_option('tweety_screenname', $screenname);

//send user to main Twitter Plugin Page
$return_url = get_home_url().'/wp-admin/options-general.php?page=twitter&auth=1';
header("Location: $return_url");
?>