<?php
/**
 * General helper to support ARTIO On-line Updates
 * 
 * @version		$Id$
 * @package		ARTIO General
 * @subpackage  helpers 
 * @copyright	Copyright (C) 2013 ARTIO s.r.o.. 
 * @author 		ARTIO, http://www.artio.net
 * @license     GNU/GPLv3 http://www.artio.net/license/gnu-general-public-license
 * @link        http://www.artio.net Official website
 */

defined('_JEXEC') or die('Restricted access');

class AUpdateHelper {
    static $url = 'http://www.artio.net/license-check';
    
    /**
     * Update URL for ARTIO Updater by adding or updating the Download ID if set
     * 
     * @param string $componentName
     * @param string $downloadID
     * @return boolean
     */
    static function setUpdateLink($componentName, $downloadID)
    {
        $db =& JFactory::getDBO();
        
        // look for update record in DB        
        $query = $db->getQuery(true);
        $query->select('location')->from('#__update_sites')->where('name = '.$db->quote($componentName));
        $db->setQuery($query);
        $origLocation = $location = $db->loadResult();
        
        $location_match = array();
        // if some ID is already set, update or remove it
        if (preg_match("/(-([A-Za-z0-9]*)).xml/", $location, $location_match)) {
            // update existing download ID
            if (strlen($downloadID)) {
                $location = str_replace($location_match[0], '-' . $downloadID.'.xml', $location);
            // or remove it, if not set
            } else {
                $location = str_replace($location_match[0], '.xml', $location);
            }
        // if not set yet but just entered, attach it
        } else if (strlen($downloadID)) {
            $location = str_replace('.xml', '-'.$downloadID.'.xml', $location);        
        }
        
        // if location string has changed, update it in DB
        if ($location != $origLocation) {
            $query = "UPDATE #__update_sites SET location = " . $db->quote($location)." WHERE name = " . $db->quote($componentName);
            $db->setQuery($query);
            // write to DB
            if (!$db->query()) {
                $this->setError($db->stderr(true));
                return false;
            }
        }
        return true;
    }
    
    /**
     * @param string $componentName
     * @param string $downloadID
     * @param string $errMsg
     * @return boolean
     */
    static function isCompatible($componentName, $downloadId, &$errMsg)
    {
        if (empty($downloadId) || trim($downloadId) == '') {
            $errMsg = 'ERR_NO_DOWNLOAD_ID';
            return false;
        }
        
        $data = array('download_id' => trim($downloadId), 'cat' => $componentName);
        $response = self::PostRequest(self::$url, null, $data);
        
        if (($response === false) || ($response->code != 200)) {
            $errMsg = 'ERR_CONNECT';
            return false;
        }
        else {
            // Parse the response - get individual lines
            $lines = explode("\n", $response->content);

            // Get the code
            $pos = strpos($lines[0], ' ');
            if ($pos === false) {
                $errMsg = 'ERR_CONNECT';
                return false;
            }
            $regInfo->code = intval(substr($lines[0], 0, $pos));

            if (($regInfo->code == 10)) {
                // OK
                return true;
            } 
            else if ($regInfo->code == 20) {
                $errMsg = 'ERR_EXPIRED';
                return false;
            }
            else if ($regInfo->code == 30) {
                $errMsg = 'ERR_NOT_ACTIVATED';
                return false;
            }
            else if ($regInfo->code == 40) {
                $errMsg = 'ERR_DOMAIN_NOT_MATCH';
                return false;
            }
            else if ($regInfo->code == 50) {
                $errMsg = 'ERR_INVALID_DOWNLOAD_ID';
                return false;
            }
            else if ($regInfo->code == 90) {
                $errMsg = 'ERR_DOWNLOAD_ID_NOT_FOUND';
                return false;
            }
            else {
                $errMsg = 'ERR_CONNECT';
                return false;
            }
        }

        return false;
    }

    /**
     * Sends the POST request
     *
     * @param string $url
     * @param string $referer
     * @param array $_data
     * @return object
     */
    static function PostRequest($url, $referer = null, $_data = null, $method = 'post', $userAgent = null, $headers = null) {
        // convert variables array to string:
        $data = '';
        if (is_array($_data) && count($_data) > 0) {
            // format --> test1=a&test2=b etc.
            $data = array();
            while( list($n, $v) = each($_data) ) {
                $data[] = "$n=$v";
            }
            $data = implode('&', $data);
            $contentType = "Content-type: application/x-www-form-urlencoded\r\n";
        }
        else {
            $data = $_data;
            $contentType = "Content-type: text/xml\r\n";
        }

        if (is_null($referer)) {
            $referer = JURI::root();
        }

        // parse the given URL
        $url = parse_url($url);
        if (!isset($url['scheme'])) {
            return false;
        }

        // extract host and path:
        $host = $url['host'];
        $path = isset($url['path']) ? $url['path'] : '/';

        // Prepare host and port to connect to
        $connhost = $host;
        $port = 80;
        
        // Workaround for some PHP versions, where fsockopen can't connect to
        // 'localhost' string on Windows servers
        if ($connhost == 'localhost') {
            $connhost = gethostbyname('localhost');
        }
        
        // Handle scheme
        if ($url['scheme'] == 'https') {
            $connhost = 'ssl://'.$connhost;
            $port = 443;
        }
        else if ($url['scheme'] != 'http') {
            return false;
        }
        
        // open a socket connection
        $errno = null;
        $errstr = null;
        $fp = @fsockopen($connhost, $port, $errno, $errstr, 5);
        if (!is_resource($fp) || ($fp === false)) {
            return false;
        }

        if (!is_null($userAgent)) {
            $userAgent = "User-Agent: ".$userAgent."\r\n";
        }

        // send the request
        if ($method == 'post') {
            // Check the first fputs, sometimes fsockopen doesn't fail, but fputs doesn't work
            if (!@fputs($fp, "POST $path HTTP/1.1\r\n")) {
                @fclose($fp);
                return false;
            }
            
            if (!is_null($userAgent)) {
                fputs($fp, $userAgent);
            }
            fputs($fp, "Host: $host\r\n");
            fputs($fp, "Referer: $referer\r\n");
            fputs($fp, $contentType);
            fputs($fp, "Content-length: ". strlen($data) ."\r\n");
            
            // Send additional headers if set
            if (is_array($headers)) {
                foreach ($headers as $h) {
                    $h = rtrim($h);
                    $h .= "\r\n";
                    fputs($fp, $h);
                }
            }
            
            fputs($fp, "Connection: close\r\n\r\n");
            fputs($fp, $data);
        }
        elseif ($method == 'get') {
            $query = '';
            if (isset($url['query'])) {
                $query = '?'.$url['query'];
            }
            // Check the first fputs, sometimes fsockopen doesn't fail, but fputs doesn't work
            if (!@fputs($fp, "GET {$path}{$query} HTTP/1.1\r\n")) {
                @fclose($fp);
                return false;
            }
            if (!is_null($userAgent)) {
                fputs($fp, $userAgent);
            }
            fputs($fp, "Host: $host\r\n");
            
            // Send additional headers if set
            if (is_array($headers)) {
                foreach ($headers as $h) {
                    $h = rtrim($h);
                    $h .= "\r\n";
                    fputs($fp, $h);
                }
            }
            
            fputs($fp, "Connection: close\r\n\r\n");
        }

        $result = '';
        while (!feof($fp)) {
            // receive the results of the request
            $result .= fgets($fp, 128);
        }

        // close the socket connection:
        fclose($fp);

        // split the result header from the content
        $result = explode("\r\n\r\n", $result, 2);

        $header = isset($result[0]) ? $result[0] : '';
        $content = isset($result[1]) ? $result[1] : '';

        $response = new stdClass();
        $response->header = $header;
        $response->content = $content;

        // Handle chunked transfer if needed
        if (strpos(strtolower($response->header), 'transfer-encoding: chunked') !== false) {
            $parsed = '';
            $left = $response->content;

            while (true) {
                $pos = strpos($left, "\r\n");
                if ($pos === false) {
                    return $response;
                }

                $chunksize = substr($left, 0, $pos);
                $pos += strlen("\r\n");
                $left = substr($left, $pos);

                $pos = strpos($chunksize, ';');
                if ($pos !== false) {
                    $chunksize = substr($chunksize, 0, $pos);
                }
                $chunksize = hexdec($chunksize);

                if ($chunksize == 0) {
                    break;
                }

                $parsed .= substr($left, 0, $chunksize);
                $left = substr($left, $chunksize + strlen("\r\n"));
            }

            $response->content = $parsed;
        }

        // Get the response code from header
        $headerLines = explode("\n", $response->header);
        $header1 = explode(' ', trim($headerLines[0]));
        $code = intval($header1[1]);
        $response->code = $code;

        return $response;
    }
}
?>