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

include_once 'Authenticator.php';
include_once 'ConnectionUtil.php';

/**
 * 
 * Service class to call the various methods to
 * URL Metrics
 * 
 * URL Metrics is a paid API that returns the metrics about a URL or set of URLs.  
 * 
 * @author Radeep Solutions
 *
 */
class URLMetricsService 
{
	private $authenticator;
	
	public function __construct($authenticator)
	{
		$this->authenticator = $authenticator;		
	}
	
	/**
	 * 
	 * This method returns the metrics about a URL or set of URLs.  
	 * 
	 * @param objectURL
	 * @param col This field filters the data to get only specific columns
	 * 			  col = 0 fetches all the data
	 * @return
	 */
	public function getUrlMetrics($objectURL, $col = 0)
	{
		
		$urlToFetch = "http://lsapi.seomoz.com/linkscape/url-metrics/" . urlencode($objectURL) . "?" . Authenticator::getInstance()->getAuthenticationStr();
		
		if($col > 0)
		{
			$urlToFetch = $urlToFetch . "&Cols=" . $col;
		}
		$response = ConnectionUtil::makeRequest($urlToFetch);
		
		return $response;
	}
	
	/**
	 * @return the $authenticator
	 */
	public function getAuthenticator() {
		return $this->authenticator;
	}

	/**
	 * @param $authenticator the $authenticator to set
	 */
	public function setAuthenticator($authenticator) {
		$this->authenticator = $authenticator;
	}
	
}

?>