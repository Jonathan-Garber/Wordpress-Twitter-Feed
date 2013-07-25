<?php
//twitter_update_auto();
if (isset($_POST['refresh'])){
tweet_tweet(100);
$resp = 'Tweets have been Imported';
}

if (isset($_POST['flush'])){
tweet_tweet_flush();
$resp = 'Tweets have been Flushed';
}

$token_array = get_option('tweety_token_array', true);
$token = $token_array[token];
$token_secret = $token_array[token_secret];
$consumer_key = get_option('tweety_consumer_key');
$consumer_secret = get_option('tweety_consumer_secret');

	if (empty($consumer_key)){
		$tweetybutton = 'Authorize Twitter';
	}else{
		$tweetybutton = 'Re-Authorize Twitter';
	}
	
//cron settings

//are we enabling or disabling cron
if (isset($_POST['do_cron'])){

//the interval for the cron job
$interval = 'minutes_5';

//enable or disable flag
$enabler = $_POST['enabler'];

//if enabling we go here
	if ($enabler == 'yes'){

		//does cron already exist? if not create it
		if( !wp_next_scheduled( 'twitter_auto' ) ) {
			wp_schedule_event(current_time( 'timestamp' ), $interval, 'twitter_auto' );
		}else{
		//cron exists already so we remove it and refresh it to the current timestamp also allows us to edit the interval if needed
		
			//clear current cron
			wp_clear_scheduled_hook('twitter_auto');
			
			//re-add current cron
			wp_schedule_event(current_time( 'timestamp' ), $interval, 'twitter_auto' );

		}

		//get cron jobs interval display name to properly show a resp message
		$interval_name = wp_get_schedules(twitter_auto);
		$interval_name = $interval_name[$interval][display];
		//set the name of the interval into option for easy management and retrieval
		update_option('twitter_interval', $interval_name);
		
		//send response message to page
		$resp = 'Automatic Twitter Updates are now Active & will run every '.$interval_name;
		
	}else{
//if disabling cron we go here

		//does cron already exist? if not we skip the disable
		if(wp_next_scheduled( 'twitter_auto' )) {
			//disables and removes the cron task
			wp_clear_scheduled_hook('twitter_auto');
			
			//clears the option storing the interval - clean up ur messes!!
			delete_option('twitter_interval');
		}
		
		//send response to screen
		$resp = 'Automatic Twitter Updates have been Cancelled.';
	}
}


//check to see if cron is scheduled now and build proper form
if( !wp_next_scheduled( 'twitter_auto' ) ) {
	$crontext = 'Enable 5 Minute Auto Update';
	$schedule_status = 'Automatic update is currently <b>Disabled</b>';
	$enabler_value = 'yes';
}else{
	$interval_name = get_option('twitter_interval');
	$crontext = 'Disable 5 Minute Auto Update';
	$schedule_status = 'Automatic update is currently <b>Enabled</b><br/>Automatic Updates will run every '.$interval_name;
	$enabler_value = 'no';
}


$twitter_log = get_option('twitter_flushrefresh_status');
$screenname = get_option('tweety_screenname');
?>

<div class="wrap">

	<div class="title">
		<div id="icon-options-general" class="icon32"></div>
		<h2>Wordpress Twitter Feed</h2>
	</div>
	
	<p>This plugin will allow you to easily link to your own Twitter Application & authorize it for access to any twitter account.</p>
	<h2>Authorize with your Twitter Application</h2>
	<form method="post" action="<?php echo site_url().'/?tweety_auth=1'; ?>">
		<p>Consumer Key: <input type="text" name="consumer_key" value="<?php echo $consumer_key ?>"></p>
		<p>Consumer Secret: <input type="text" name="consumer_secret" value="<?php echo $consumer_secret ?>"></p>
		<p><input type="submit" name="tweety_do_auth" value="<?php echo $tweetybutton ?>" class="button-secondary"></p>
	</form>
	<?php 
	if (isset($_GET['auth']) || $screenname != '' ){ 
	?>
	<strong>Your Application is Authorized and has access to the following Twitter Account <?php echo $screenname ?>.</strong>
	<?php } ?>
	<h3>Log</h3>
	Twitter Updates: <?php echo $twitter_log ?>
	<h3>Tools</h3>
		<form method="POST" action="options-general.php?page=twitter">
		<input type="submit" name="flush" value="Flush Twitter Feed" class="button-secondary">
		<input type="submit" name="refresh" value="Refresh Twitter Feed" class="button-secondary">
		</form>
		<h4>Automatic Updates</h4>
		<p><?php echo $schedule_status ?></p>
		<form method="POST" action="options-general.php?page=twitter">
		<input type="hidden" name="enabler" value="<?php echo $enabler_value ?>">
		<input type="submit" name="do_cron" value="<?php echo $crontext ?>" class="button-secondary">
		</form>
		<br/>
		<strong><?php echo $resp ?></strong>
		<br/>
	<h3>Raw Stored Twitter Posts</h3>
	<pre>
	<?php tweet_show_tweets(); ?>
	</pre>
</div>
