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

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

class SEFControllerCrawler extends SEFController
{
    /**
     * constructor (registers additional tasks to methods)
     * @return void
     */
    function __construct()
    {
        parent::__construct();
    }
    
    function display()
    {
        JRequest::setVar('view', 'crawler');
        
        parent::display();
    }
    
    function crawl()
    {
        // Suppress all normal output
        ob_start();
        
        // Compute maximum time for script execution
        $timeLimit = ini_get('max_execution_time');
        $timeLimit = $timeLimit / 2;
        if ($timeLimit > 10) {
            $timeLimit = 10;
        }
        $maxTime = $_SERVER['REQUEST_TIME'] + $timeLimit;
        
        $urls = JRequest::getVar('url', array());
        
        $crawled = 0;
        $found = array();
        
        // Crawl URLs
        foreach ($urls as $url) {
            // Find URLs on URL
            $this->_parseLinks($url, $found);
            
            // Increase counter
            $crawled++;

            // Check time
            if (time() > $maxTime) {
                // We need to interrupt the script
                break;
            }
        }
        
        // Create response object
        $ret = new stdClass();
        $ret->crawled = $crawled;
        $ret->found = $found;
        
        ob_end_clean();
        
        // Write response
        echo json_encode($ret);
        jexit();
    }
    
    function _parseLinks($url, &$found)
    {
        // Get response, follow 5 redirections
        $redirs = 0;
        do {
            $redirect = false;
            $response = SEFTools::PostRequest($url, null, null, 'get', null);
            
            if ($response === false) {
                // Error
                return;
            }
            
            if ($redirs < 5 && $response->code >= 300 && $response->code < 400) {
                // Parse redirect URL
                $pos = stripos($response->header, 'location:');
                if ($pos !== false) {
                    $pos += 9;  // Skip header name
                    $pos2 = strpos($response->header, "\r", $pos);
                    $url = substr($response->header, $pos, $pos2 - $pos);
                    $url = trim($url);
                    $redirect = true;
                    $redirs++;
                }
            }
        } while ($redirect);
        
        // Check code
        if ($response->code == 200) {
            // Parse URLs
            $matches = array();
            if (preg_match_all('#<a\s[^>]*href=["\']([^"\']+)#i', $response->content, $matches) > 0) {
                // Loop through found URLs
                foreach ($matches[1] as $link) {
                    $link = str_replace(JURI::root(), '', $link);
                    
                    if (strpos($link, '://') !== false) {
                        // Absolute external link, skip
                        continue;
                    }
                    
                    // Internal link
                    $link = JURI::root().ltrim(html_entity_decode(urldecode($link)), '/');
                    
                    if (!in_array($link, $found)) {
                        $found[] = $link;
                    }
                }
            }
        }
    }
}