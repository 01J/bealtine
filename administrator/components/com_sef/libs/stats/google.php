<?php
/**
 * SEF component for Joomla!
 * 
 * @package   JoomSEF
 * @version   4.6.2
 * @author    ARTIO s.r.o., http://www.artio.net
 * @copyright Copyright (C) 2015 ARTIO s.r.o. 
 * @license   GNU/GPLv3 http://www.artio.net/license/gnu-general-public-license
 */
 
defined('_JEXEC') or die('Restricted access');

require_once JPATH_COMPONENT_ADMINISTRATOR.'/classes/seftools.php';

class StatsGoogle extends JObject {
	private function genhash ($url)
    {
        $hash = 'Mining PageRank is AGAINST GOOGLE\'S TERMS OF SERVICE. Yes, I\'m talking to you, scammer.';
        $c = 16909125;
        $length = strlen($url);
        $hashpieces = str_split($hash);
        $urlpieces = str_split($url);
        for ($d = 0; $d < $length; $d++)
        {
            $c = $c ^ (ord($hashpieces[$d]) ^ ord($urlpieces[$d]));
            $c = $this->zerofill($c, 23) | $c << 9;
        }
        return '8' . $this->hexencode($c);
    }
    
    private function hexencode($str)
    {
        $out  = $this->hex8($this->zerofill($str, 24));
        $out .= $this->hex8($this->zerofill($str, 16) & 255);
        $out .= $this->hex8($this->zerofill($str, 8 ) & 255);
        $out .= $this->hex8($str & 255);

        return $out;
    }
    
    private function zerofill($a,$b)
    {
        $z = hexdec(80000000);
        if ($z & $a)
        {
            $a  = ($a>>1);
            $a &= (~$z);
            $a |= 0x40000000;
            $a  = ($a>>($b-1));
        }
        else
        {
            $a = ($a>>$b);
        }
        return $a;
    }
    
    private function hex8 ($str)
    {
        $str = dechex($str);
        (strlen($str) == 1 ? $str = '0' . $str: null);

        return $str;
    }
    
	function getPageRank($url) 
	{
		// Page rank can't be obtained currently
        return 0;
        
		$checksum=$this->genhash($url);
		$googleurl  = 'http://toolbarqueries.google.com/tbr?features=Rank&sourceid=navclient-ff&client=navclient-auto-ff';
        $googleurl .= '&googleip=O;66.249.81.104;104&ch='.$checksum.'&q=info:'.urlencode($url);
        $data=SEFTools::PostRequest($googleurl,null,null,'get');
        if(!empty($matches[2])) {
			return $matches[2][0];
		} else {
			return 0;
		}
	}
	
	function getTotalIndexed($url) 
	{
		$url = rtrim(JURI::root(), '/').'/'.ltrim($url, '/');
        $url = str_replace(array('http://', 'https://'), '', $url);
        if (substr($url, 0, 4) == 'www.') {
            $url = substr($url, 4);
        }
		$google_url = 'http://www.google.com/search?q=site:'.urlencode($url);
		$data=SEFTools::PostRequestFollowRedirects($google_url,null,null,'get');
		$matches=array();
		if (preg_match('#<div[^>]*id="?resultStats"?>[^0-9]*([0-9,\s]*)<#', html_entity_decode($data->content), $matches)) {
            return preg_replace('/[^0-9]/', '', $matches[1]);
        }
        
        return 0;
	}
	
	function getPopularity($url) 
	{
		$url = rtrim(JURI::root(), '/').'/'.ltrim($url, '/');
        $url = str_replace(array('http://', 'https://'), '', $url);
        if (substr($url, 0, 4) == 'www.') {
            $url = substr($url, 4);
        }
		$google_url = 'http://www.google.com/search?q="'.urlencode($url).'"+-site:'.urlencode($url);
		$data=SEFTools::PostRequestFollowRedirects($google_url,null,null,'get');
		$matches=array();
        if (preg_match('#<div[^>]*id="?resultStats"?>[^0-9]*([0-9,\s]*)<#', html_entity_decode($data->content), $matches)) {
            return preg_replace('/[^0-9]/', '', $matches[1]);
        }
        
        return 0;
	}
	
	/*function getTitlePopularity($url) 
	{
		$url=str_replace("http://","",$url);
		$url=str_replace("www.","",$url);
		$google_url = 'http://www.google.com/search?q="'.urlencode($url).'"+-site:'.urlencode($url);
		$data=SEFTools::PostRequest($google_url,null,null,'get');
		$mathes=array();
		preg_match_all('#<div id=resultStats>([A-Za-z]*) ([0-9,]*)#',$data->content,$matches);
		return $matches[2][0];
	}*/
	
	function getFacebookIndexed($url) {
		$url = rtrim(JURI::root(), '/').'/'.ltrim($url, '/');
        $url = str_replace(array('http://', 'https://'), '', $url);
        if (substr($url, 0, 4) == 'www.') {
            $url = substr($url, 4);
        }
		$google_url = 'http://www.google.com/search?q="'.urlencode($url).'"+site:'.urlencode('facebook.com');
		$data=SEFTools::PostRequestFollowRedirects($google_url,null,null,'get');
		$matches=array();
        if (preg_match('#<div[^>]*id="?resultStats"?>[^0-9]*([0-9,\s]*)<#', html_entity_decode($data->content), $matches)) {
            return preg_replace('/[^0-9]/', '', $matches[1]);
        }
        
        return 0;
	}
	
	function getTwitterIndexed($url) {
		$url = rtrim(JURI::root(), '/').'/'.ltrim($url, '/');
        $url = str_replace(array('http://', 'https://'), '', $url);
        if (substr($url, 0, 4) == 'www.') {
            $url = substr($url, 4);
        }
		$google_url = 'http://www.google.com/search?q="'.urlencode($url).'"+site:'.urlencode('twitter.com');
		$data=SEFTools::PostRequestFollowRedirects($google_url,null,null,'get');
		$matches=array();
        if (preg_match('#<div[^>]*id="?resultStats"?>[^0-9]*([0-9,\s]*)<#', html_entity_decode($data->content), $matches)) {
            return preg_replace('/[^0-9]/', '', $matches[1]);
        }
        
        return 0;
	}
	
	function getPageSpeed($url) {
		require_once JPATH_COMPONENT_ADMINISTRATOR.'/classes/config.php';
		$url = rtrim(JURI::root(), '/').'/'.ltrim($url, '/');
		
		$config = SEFConfig::getConfig();
        
		$ndata=new stdClass();
		if(strlen($config->google_apikey)==0) {
			return false;
		}
		
		$google_url='https://www.googleapis.com/pagespeedonline/v1/runPagespeed?url='.urlencode($url).'&key='.$config->google_apikey;
        
        // Use CURL if available
        if (function_exists('curl_init')) {
    		$certFile = realpath(dirname(__FILE__).'/../cacert.pem');
            $c=curl_init($google_url);
    		curl_setopt($c,CURLOPT_RETURNTRANSFER,1);
    		curl_setopt($c, CURLOPT_ENCODING, "utf-8" );
            curl_setopt($c, CURLOPT_CAINFO, $certFile);
    		$data=curl_exec($c);
    		curl_close($c);
        }
        else {
            // Try to use our own method
            $data = SEFTools::PostRequest($google_url, null, null, 'get');
            if ($data !== false) {
                $data = $data->content;
            }
        }
        if ($data === false) {
            // Could not connect
            return false;
        }
		$data=json_decode($data);
        
		$ndata=new stdClass();
		
		if(isset($data->error)) {
			$ndata->message=$data->error->errors[0]->message;
			return $ndata;
		}
		
		@$ndata->pageStats=$data->pageStats;
		@$ndata->score=$data->score;
		
		$reg=new JRegistry();
		$reg->loadObject($ndata);
		return $reg->toString("ini");
		
	}
}
?>