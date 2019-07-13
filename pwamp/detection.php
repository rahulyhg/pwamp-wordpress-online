<?php
if ( !defined('ABSPATH') )
{
	exit;
}

require_once plugin_dir_path( __FILE__ ) . 'cfg.php';
require_once plugin_dir_path( __FILE__ ) . 'flx.php';

class MDetection
{
	public function __construct()
	{
	}

	public function __destruct()
	{
	}


	public function get_device($user_agent, $accept, $profile)
	{
		$flx = new Flx();

		$request = array(
			'user_agent' => $user_agent,
			'accept' => $accept,
			'profile' => $profile
		);

		$response = $flx->query(FLX_DETECTION, $request);
		if ( empty($response) )
		{
			return;
		}

		if ( !isset($response['device']) || !is_string($response['device']) )
		{
			return;
		}
		$device = $response['device'];

		return $device;
	}
}
