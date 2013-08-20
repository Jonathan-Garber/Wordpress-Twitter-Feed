<?php
$wtf_post_type = 'wtf_twitter';

// Return raw twitter data
function wtf_raw_tweets(){
	global $wtf_post_type;

		$args = array(
			'numberposts' => '-1',
			'post_type' => $wtf_post_type,
			'orderby' => 'title',
			'order' => 'desc',
			'post_status' => 'publish',
			'meta_query' => array(
				array(
					'key' => 'wtf_data',
					'value' => '',
					'compare' => '!=',
				)
			)
		 );
		$posts = get_posts( $args );
		
		if (!empty($posts)){		
			foreach ($posts as $p){
				$post_id = $p->ID;
				$twitter_data = get_post_meta($post_id, 'wtf_data');
				print_r($twitter_data);
			}
		}else{
			echo 'No Tweets Found';
		}
		
}


function wtf_flush(){
	global $wtf_post_type;
		$args = array(
			'numberposts' => '-1',
			'post_type' => $wtf_post_type,
			'orderby' => 'title',
			'order' => 'desc',
			'post_status' => 'publish',
			'meta_query' => array(
				array(
					'key' => 'wtf_data',
					'value' => '',
					'compare' => '!=',
				)
			)
		 );
		$posts = get_posts( $args );
		
		foreach ($posts as $post){
			$post_id = $post->ID;
			wp_delete_post($post_id, true);
		}
		$date = date('l F jS Y');
		$time = date('g:i:s a');
		update_option('wtf_flushrefresh_status', 'Flushed on '.$date.' at '.$time);
		
		//removes last ID imported so we can get a entire new list when we refresh them
		delete_option('wtf_last_id');
}

function wtf_show_tweets(){
	global $wtf_post_type;

		$args = array(
			'numberposts' => '-1',
			'post_type' => $wtf_post_type,
			'orderby' => 'title',
			'order' => 'desc',
			'post_status' => 'publish',
			'meta_query' => array(
				array(
					'key' => 'wtf_data',
					'value' => '',
					'compare' => '!=',
				)
			)
		 );
		$posts = get_posts( $args );
		
		foreach ($posts as $post){
			$post_id = $post->ID;			
			$twitter_array = get_post_meta($post_id, 'wtf_data', true);
			$tweet = $twitter_array->text;
			$twitter_image = $twitter_array->user->profile_image_url;
			$is_retweet = $twitter_array->retweeted_status->user->rofile_image_url;			
				if ($is_retweet){
					$twitter_image = $is_retweet;
				}
			echo '<br/>';
			echo '<img src="'.$twitter_image.'"/>';
			echo $tweet.'<br/>';
		}


}

function wtf_get_retweets($id){
$count = 100;
	$connectionSession = curl_init(); //open connection
	$url = 'https://api.twitter.com/1.1/statuses/retweets/'.$id.'.json?count='.$number;
	curl_setopt($connectionSession, CURLOPT_URL, $url);
	curl_setopt($connectionSession, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
	curl_setopt($connectionSession, CURLOPT_HTTPGET, 1);
	curl_setopt($connectionSession, CURLOPT_HEADER, false);
	curl_setopt($connectionSession, CURLOPT_HTTPHEADER, array('Accept: application/json'));
	curl_setopt($connectionSession, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($connectionSession, CURLOPT_FAILONERROR, true);
	curl_setopt($connectionSession, CURLOPT_TIMEOUT, 2);	
	$response = curl_exec($connectionSession);
	curl_close($connectionSession); //close connection
 
	//load JSON
	$retweets = json_decode($response, true);
	
	echo '<pre>';
	print_r($retweets);
	echo '</pre>';


}

function wtf_update_auto(){
	$limit = '4';
	include 'oauth/twitteroauth.php';
	$consumer_key = get_option('wtf_consumer_key');
	$consumer_secret = get_option('wtf_consumer_secret');
	$wtf_token_array = get_option('wtf_token_array');
	$token = $wtf_token_array[token];
	$token_secret = $wtf_token_array[token_secret];
	
	$last_id = get_option('wtf_last_id');	
	
$to = new TwitterOAuth($consumer_key, $consumer_secret, $token, $token_secret);
	//if last id not empty we know this username has been called for feed before
	if (empty($last_id) || $last_id == ''){
		$tweets = $to->get('statuses/user_timeline', array (
		'count' => 200
		));
	}else{
		$tweets = $to->get('statuses/user_timeline', array (
		'since_id' => $last_id,
		'count' => $limit,
		));
	}

	$tweetCount = count($tweets);
	$counter = 0;
	
	$first_id = $tweets[$counter]->id_str;
	//if first id is empty there are no new tweets if its not empty we take that id
	if ($first_id != ''){
	update_option('wtf_last_id', $first_id);
	}
	
	//loop through tweets
	while ( $counter < $tweetCount ) {
		global $wtf_post_type;

		$wtf_id = $tweets[$counter]->id_str;	
		$profile_image = $tweets[$counter]->user->profile_image_url;
		$convo_check = $tweets[$counter]->in_reply_to_status_id;		
		$date = date("F j, Y G:i",strtotime($tweets[$counter]->created_at));
		$description = $tweets[$counter]->text;
		$description = preg_replace("#(^|[\n ])@([^ \"\t\n\r<]*)#ise", "'\\1<a href=\"http://www.twitter.com/\\2\" >@\\2</a>'", $description);  
		$description = preg_replace("#(^|[\n ])([\w]+?://[\w]+[^ \"\n\r\t<]*)#ise", "'\\1<a href=\"\\2\" >\\2</a>'", $description);
		$description = preg_replace("#(^|[\n ])((www|ftp)\.[^ \"\t\n\r<]*)#ise", "'\\1<a href=\"http://\\2\" >\\2</a>'", $description);
		
		if ($wtf_id > $last_id){
		
			$my_post = array(
				 'post_type' => $wtf_post_type,
				 'post_title' => $wtf_id,
				 'post_status' => 'publish',
				 'post_author' => 0,
				 'post_category' => array(0)
			  );

			$post_id = wp_insert_post( $my_post );
			update_post_meta($post_id, 'wtf_data', $tweets[$counter]);
		
			if ($convo_check != ''){
				update_post_meta($post_id, 'is_convo', true);
			}
		}

	    $counter++;
	}
		$date = date('l F jS Y');
		$time = date('g:i:s a');
		update_option('wtf_flushrefresh_status', 'Last automatic update ran on '.$date.' at '.$time);
}


function wtf_tweet($limit) {
	include 'oauth/twitteroauth.php';
	$consumer_key = get_option('wtf_consumer_key');
	$consumer_secret = get_option('wtf_consumer_secret');
	$wtf_token_array = get_option('wtf_token_array');
	$token = $wtf_token_array[token];
	$token_secret = $wtf_token_array[token_secret];
	
	$last_id = get_option('wtf_last_id');
	echo $last_id.'<br/><br/>';

$to = new TwitterOAuth($consumer_key, $consumer_secret, $token, $token_secret);
	//if last id not empty we know this username has been called for feed before
	if (empty($last_id) || $last_id == ''){
		$tweets = $to->get('statuses/user_timeline', array (
		'count' => 200
		));
	}else{
		$tweets = $to->get('statuses/user_timeline', array (
		'since_id' => $last_id,
		'count' => $limit
		));
	}

	$tweetCount = count($tweets);
	$counter = 0;

	$first_id = $tweets[$counter]->id_str;
	if ($first_id != ''){
		update_option('wtf_last_id', $first_id);
	}	
	
	//loop through tweets
	while ( $counter < $tweetCount ) {
		global $wtf_post_type;

		$wtf_id = $tweets[$counter]->id_str;	
		$profile_image = $tweets[$counter]->user->profile_image_url;
		$convo_check = $tweets[$counter]->in_reply_to_status_id;		
		$date = date("F j, Y G:i",strtotime($tweets[$counter]->created_at));
		$description = $tweets[$counter]->text;
		$description = preg_replace("#(^|[\n ])@([^ \"\t\n\r<]*)#ise", "'\\1<a href=\"http://www.twitter.com/\\2\" >@\\2</a>'", $description);  
		$description = preg_replace("#(^|[\n ])([\w]+?://[\w]+[^ \"\n\r\t<]*)#ise", "'\\1<a href=\"\\2\" >\\2</a>'", $description);
		$description = preg_replace("#(^|[\n ])((www|ftp)\.[^ \"\t\n\r<]*)#ise", "'\\1<a href=\"http://\\2\" >\\2</a>'", $description);
		
		if ($wtf_id > $last_id){		
			$my_post = array(
				 'post_type' => $wtf_post_type,
				 'post_title' => $wtf_id,
				 'post_status' => 'publish',
				 'post_author' => 0,
				 'post_category' => array(0)
			  );

			$post_id = wp_insert_post( $my_post );
			update_post_meta($post_id, 'wtf_data', $tweets[$counter]);
		
			if ($convo_check != ''){
				update_post_meta($post_id, 'is_convo', true);
			}
		}

	    $counter++;

	}
		$date = date('l F jS Y');
		$time = date('g:i:s a');
		update_option('wtf_flushrefresh_status', 'Last manual update ran on '.$date.' at '.$time);
}
?>