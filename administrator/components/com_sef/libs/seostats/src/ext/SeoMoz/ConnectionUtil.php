<?php
	/**
	 *  PHP class SEOstats
	 *
	 *  @class      SEOstats_Alexa
	 *  @package	class.seostats
	 *  @updated	2011/06/11
	 *  @author		Stephan Schmitz <eyecatchup@gmail.com>
	 *  @copyright	2010-present, Stephan Schmitz
	 *  @license	GNU General Public License (GPL)
	 */

defined('_JEXEC') or die('Restricted access');

class ConnectionUtil 
{
	const CURL_CONNECTION_TIMEOUT = 120;
	
	/**
	 * 
	 * Method to make a GET HTTP connecton to 
	 * the given url and return the output
	 * 
	 * @param urlToFetch url to be connected
	 * @return the http get response
	 */
	public static function makeRequest($urlToFetch)
	{
		$curl_handle = curl_init();

		curl_setopt($curl_handle, CURLOPT_URL, "$urlToFetch");
		curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, ConnectionUtil::CURL_CONNECTION_TIMEOUT);
		curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
				
		$buffer = curl_exec($curl_handle);
		//var_dump($buffer);
		curl_close($curl_handle);
		
		$arr = json_decode($buffer);
		
		return $arr;
	}
	
}

?>