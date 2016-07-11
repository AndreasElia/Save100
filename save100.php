<?php

	require 'vendor/autoload.php';

	// Settings
	$app_token = '';
	$bot_token = '';

	// Information from request
	$token 	 = $_POST[ 'token' ];
	$channel = $_POST[ 'channel_id' ];

	// Check to see if the token is valid
	if ( $token != $bot_token )
	{
		die( 'Invalid token!' );
	}

	// Guzzle
	$client = new GuzzleHttp\Client();

	// Notification Message (nm)
	$nm_payload = [
		'token'   => $app_token,
		'channel' => $channel,
		'text' 	  => '*Processing request*'
	];

	$nm_request = $client->request(
		'POST', 
		'https://slack.com/api/chat.postMessage?' . http_build_query( $nm_payload )
	);

	// Channel History (ch)
	$ch_payload = [
		'token'   => $app_token,
		'channel' => $channel,
	];

	$ch_request = $client->request(
		'GET', 
		'https://slack.com/api/channels.history?' . http_build_query( $ch_payload )
	);

	$ch_response = json_decode( $ch_request->getBody(), true );

	// Team Info (ti)
	$ti_payload = [
		'token' => $app_token
	];

	$ti_request = $client->request(
		'GET', 
		'https://slack.com/api/team.info?' . http_build_query( $ti_payload )
	);

	$ti_response = json_decode($ti_request->getBody(), true);

	// Channel Info (ci)
	$ci_payload = [
		'token'   => $app_token,
		'channel' => $channel,
	];

	$ci_request = $client->request(
		'GET', 
		'https://slack.com/api/channels.info?' . http_build_query( $ch_payload )
	);

	$ci_response = json_decode( $ci_request->getBody(), true );

	// Data for the Gist
	$mh_team_name 	 = $ti_response[ 'team' ][ 'name' ];

	$mh_channel_name = ' #' . $ci_response[ 'channel' ][ 'name' ];

	$mh_channel_date = ' [' . date( 'd-m-Y h:i:s A' ) . ']';

	// Stores the data to be put in a Gist
	$mh_message_data = $mh_team_name . $mh_channel_name . $mh_channel_date . "\n\n";

	// Loop through each message
	foreach ( array_reverse( $ch_response[ 'messages' ] ) as $message )
	{
		// User Info (ui)
		$ui_payload = [
			'token' => $app_token,
			'user'	=> $message[ 'user' ]
		];

		$ui_request = $client->request(
			'GET', 
			'https://slack.com/api/users.info?' . http_build_query( $ui_payload )
		);

		$ui_response = json_decode( $ui_request->getBody(), true );

		// Add info to the rest of the data
		$mh_date = json_encode([date( 'd-m-Y h:i:s A', $message['ts'] )]);;
		$mh_username = $ui_response[ 'user' ][ 'name' ] ?: 'Bot';

		$mh_message_data = sprintf(
			'%s <%s> %s %s >\n'
			$mh_message_data,
			$mh_date,
			$mh_username,
			$message[ 'text' ]
		);
	}

	// Info for the Gist
	$gh_file_name 	= $ci_response[ 'channel' ][ 'name' ] . '_' . date( 'd-m-Y_h-i-s-a' );

	$gh_description = $mh_team_name . $mh_channel_name . $mh_channel_date;

	$gh_files = array(
		$mh_team_name . '_' . $gh_file_name  . '.txt' => array(
			'content' => $mh_message_data
		),
	);

	$gh_data = array(
		'description' => $gh_description,
		'public'	  => true,
		'files' 	  => $gh_files
	);

	// Request the Gist
	$gh_request = $client->request( 'POST', 'https://api.github.com/gists', [
		'headers' => [
			'User-Agent' => 'https://api.github.com/meta'
		],
		'body' => json_encode( $gh_data )
	] );

	$gh_response = json_decode( $gh_request->getBody(), true );

	// Notification Message (nm)
	$nm_payload = [
		'token'   => $app_token,
		'channel' => $channel,
		'text' 	  => '*Last 100 messages have been saved here:* <' . $gh_response[ 'html_url' ] . '>'
	];

	$nm_request = $client->request(
		'POST', 
		'https://slack.com/api/chat.postMessage?' . http_build_query( $nm_payload )
	);
