<?php
if ( !defined('ABSPATH') )
{
	exit;
}

require_once plugin_dir_path( __FILE__ ) . 'cfg.php';
require_once plugin_dir_path( __FILE__ ) . 'flx.php';

class PWAMPTranscoding
{
	public function __construct()
	{
	}

	public function __destruct()
	{
	}


	public function transcode(&$page, $home_url, $theme, $data)
	{
		$flx = new Flx();

		$flx->base64_encode($page);

		$request = array(
			'page' => &$page,
			'home_url' => $home_url,
			'theme' => $theme,
			'data' => $data
		);

		$response = $flx->query(FLX_WORDPRESS, $request);
		if ( empty($response) )
		{
			$page = '';

			return;
		}

		if ( !isset($response['page']) || !is_string($response['page']) )
		{
			$page = '';

			return;
		}
		$page = $response['page'];

		$flx->base64_decode($page);
	}
}
