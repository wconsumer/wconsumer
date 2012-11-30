<?php
/**
 * Drupal API Requestor
 *
 * Abstract class that will be extended by another class to impliment specific
 * items such as OAuth or API Key Requests
 *
 * @package	wc_core
**/
abstract class ApiRequestor {
	/**
	 * The Base URL to the API
	 *
	 * @global	string
	**/
	public $baseUrl = '';
	
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param		array
	**/
	public function __construct($data = array())
	{
		
	}
	
	/**
	 * Make a request to the API
	 *
	 * @access	public
	**/
	abstract public function makeRequest();
	
	abstract public function parseResponse();
	abstract public function encodeParameters();
	
	/**
	 * Build an API URL
	 *
	 * @access	public
	 * @param		string Add some endpoints to the API URL
	**/
	public function function apiUrl($url = '')
	{
		return $this->baseUrl.$url;
	}
}

/**
 * OAuth Implementation of the API Requestor
 *
 * @package	wc_core
**/
class OAuthRequestor extends ApiRequestor {
	private $applicationCreds = array();
	private $userCreds = array();
	
	/**
	 * Constructor
	 *
	 * Passed the OAuth Credentials to setup the request
	 *
	 * @param		array
	 * @return  void
	**/
	public function __construct($data = array())
	{
		if (! is_array($data) OR ! isset($data['consumer_key']) OR ! isset($data['consumer_secret']) )
			throw new ApiRequestorException('Invalid arguments passed to OAuthRequestor');
		
		// Save the consumer key and secret
		$this->applicationCreds['consumer_key'] = $data['consumer_key'];
		$this->applicationCreds['consumer_secret'] = $data['consumer_secret'];
		
		// Did they pass the access token/secret?
		if (isset($data['access_token']) AND isset($data['access_token_secret']))
			$this->setAccessTokens($data['access_token'], $data['access_token_secret']);
	}
	
	public function setAccessTokens($token, $secret)
	{
		// Invalid Data
		if (empty($token) OR empty($secret))
			return;
		
		$this->userCreds = array(
			'access_token'				=> $token,
			'access_token_secret'	=> $secret,
		);
	}
}

class ApiRequestorException extends Exception { }