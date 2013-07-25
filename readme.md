===
Wordpress Twitter Feed
===

This is a small wordpress plugin that we use to simply fetch a twitter users statuses. We can then take the raw status data and display it any way we like in a theme or other plugins. This plugin simply authorizes itself with any twitter application you designate to gain access to your twitter account.

===
Requirements
===
1. A Twitter Application to link this plugin to. This allows the plugin on your Wordpress site to fully communicate with the Twitter Account you choose to link to.

===
Features
===
1. Automatic tweet updating with a 5 minute interval using wp_cron

2. Display raw status data using function tweet_raw_tweets();

3. Display simple list of tweets with user icon and status using function tweet_show_tweets();
