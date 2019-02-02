<?php
if ( !defined('ABSPATH') )
{
	exit;
}

require_once plugin_dir_path( __FILE__ ) . '../var/cfg.php';
require_once plugin_dir_path( __FILE__ ) . '../lib/flx.php';

class PWAMPO_Transcoding
{
	private $home_url;
	private $data;


	public function __construct($home_url, $data)
	{
		$this->home_url = $home_url;
		$this->data = $data;
	}

	public function __destruct()
	{
	}


	public function transcode(&$page)
	{
		$flx = new Flx();

		$flx->base64_encode($page);

		$request = array(
			'page' => &$page,
			'home_url' => $this->home_url,
			'data' => $this->data
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
