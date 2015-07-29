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

// Check to ensure this file is within the rest of the framework
defined('_JEXEC') or die('Direct access to this location is not allowed.');

// IIS Patch
if (isset($_SERVER['HTTP_X_REWRITE_URL'])) {
    $_SERVER['REQUEST_URI'] = $_SERVER['HTTP_X_REWRITE_URL'];
}

require_once( JPATH_ROOT.'/components/com_sef/joomsef.php' );
require_once( JPATH_ROOT.'/components/com_sef/sef.cache.php' );
require_once( JPATH_ROOT.'/administrator/components/com_sef/classes/seftools.php' );
require_once( JPATH_ROOT.'/components/com_sef/sef.ext.php' );

jimport('joomla.application.router');

// Helper class to get access to the protected _uri property of JURI object
class JoomSefUri extends JUri
{
    public static function getUri($uri) {
        // Joomla 2.5 vs 3
        $url = property_exists($uri, '_uri') ? $uri->_uri : $uri->uri;
        
        // Joomla 3.4 contains special handling for &limitstart URLs, so we
        // need to check for it
        if ($url == 'index.php' && $uri->getQuery(true)) {
            // There is some query, but it's not present in the "uri" property
            return $uri->toString();
        }
        
        return $url;
    }
    
    public static function clearUri($uri) {
        $uri->setScheme(null);
        $uri->setUser(null);
        $uri->setPass(null);
        $uri->setHost(null);
        $uri->setPort(null);
        $uri->setPath(null);
        $uri->setFragment(null);
        $uri->setQuery(array());
    }
    
    public static function copyUri($from, $to) {
        if (property_exists($to, '_uri')) {
            // Joomla 2.5
            $to->_uri = $from->_uri;
        }
        else {
            // Joomla 3
            $to->uri = $from->uri;
        }
        
        $to->setScheme($from->getScheme());
        $to->setUser($from->getUser());
        $to->setPass($from->getPass());
        $to->setHost($from->getHost());
        $to->setPort($from->getPort());
        $to->setPath($from->getPath());
        $to->setFragment($from->getFragment());
        $to->setQuery($from->getQuery(true));
    }
    
    public static function updateUri($uri, $url) {
        self::clearUri($uri);
        $uri->parse($url);
    }
}

class JRouterJoomsef extends JRouter
{
    protected $parsing = false;
    protected $joomlaRouter = null;

    /**
     * Class constructor
     *
     * @access public
     */
    function __construct($options = array())
    {
        $app = JFactory::getApplication();
        $this->joomlaRouter = $app->getRouter();
    }

    function _prepareUrl($url)
    {
        // Create full URL if we are only appending variables to it
        if(substr($url, 0, 1) == '&') {
            $vars = array();
            parse_str($url, $vars);

            $vars = array_merge($this->joomlaRouter->getVars(), $vars);

            foreach($vars as $key => $var) {
                if($var == "") unset($vars[$key]);
            }

            $url = 'index.php?'.JURI::buildQuery($vars);
        }

        // Security - only allow one question mark in URL
        $pos = strpos($url, '?');
        if( $pos !== false ) {
            $url = substr($url, 0, $pos+1) . str_replace('?', '%3F', substr($url, $pos+1));
        }

        // Decompose link into url component parts
        return $url;
    }

    function build(&$siteRouter, &$uri)
    {
        // Get correct URL for JoomSEF (menu items containing only Itemid, not option) and store the original path
        $origPath = $uri->getPath();
        $url = JoomSefUri::getUri($uri);
        $option=$uri->getVar('option');

        // Security - only allow colon in protocol part
        if( strpos($url, ':') !== false ) {
            $offset = 0;
            if( substr($url, 0, 5) == 'http:' ) {
                $offset = 5;
            }
            elseif( substr($url, 0, 6) == 'https:' ) {
                $offset = 6;
            }

            $url = substr($url, 0, $offset) . str_replace(':', '%3A', substr($url, $offset));
        }
        
        // Fix the amp; as they shouldn't be present there - VirtueMart has problem with those
        $url = str_replace(array('?amp;', '&amp;'), array('?', '&'), $url);

        // Update URI object
        JoomSefUri::updateUri($uri, $this->_prepareUrl($url));

        // Check the path part for URLs without mod_rewrite support
        $route = $uri->getPath();
        if (substr($route, 0, 10) == 'index.php/') {
            $route = substr($route, 10);
            $uri->setPath($route);
            return $uri;
        }
        
        // Last resort check for URLs that shouldn't be SEFed
        if (substr($route, 0, 9) != 'index.php') {
            return $uri;
        }

        // Set URI defaults
        $menu = JFactory::getApplication()->getMenu('site');

        // We don't want to add any variables if the URL is pure index.php
        if ($url != 'index.php') {
            // Get the itemid from the URI
            $Itemid = $uri->getVar('Itemid');

            if (is_null($Itemid)) {
                if ($option = $uri->getVar('option')) {
                    $item = $menu->getItem($this->joomlaRouter->getVar('Itemid'));
                    if (isset($item) && $item->component == $option) {
                        $uri->setVar('Itemid', $item->id);
                    }
                }
                else {
                    if (($Itemid = $this->joomlaRouter->getVar('Itemid'))) {
                        $uri->setVar('Itemid', $Itemid);
                    }
                }
            }
            
            // If there is no option specified, try to get the query from menu item
            if (is_null($uri->getVar('option'))) {

                // Joomla pagination can generate only URL like ?limitstart=5 and Joomla router add into URL actual query automatically.
                if (count($vars = $uri->getQuery(true)) == 2 && isset($vars['Itemid']) && isset($vars['limitstart'])) {
                    foreach ($this->joomlaRouter->getVars() as $name => $value)
                        if ($name != 'limitstart' && $name != 'start')
                            $uri->setVar($name, $value);
                    if ($uri->getVar('limitstart') == 0)
                        $uri->delVar('limitstart');
                }
                else if (!is_null($uri->getVar('Itemid'))) {
                    $item = $menu->getItem($uri->getVar('Itemid'));

                    $origId = $uri->getVar('Itemid');
                    while (is_object($item) && ($item->type == 'alias')) {
                        // Get aliased menu item
                        if (is_object($item->params)) {
                            $aliasId = $item->params->get('aliasoptions', null);
                            if (!is_null($aliasId) && ($aliasId != $origId)) {
                                $item = $menu->getItem($aliasId);
                                
                                // Fix Itemid
                                if (is_object($item)) {
                                    $uri->setVar('Itemid', $item->id);
                                }
                            }
                            else {
                                break;
                            }
                        }
                        else {
                            break;
                        }
                    }
                        
                    if (is_object($item)) {
                        //$uri->setVar('option',$item->component);
                        foreach($item->query as $k => $v) {
                            $test=$uri->getVar($k);
                            if(strlen($test)==0) {
                                $uri->setVar($k, $v);
                            }
                        }
                    }
                }
                else {
                    // There is no option or Itemid specified, try to use current option
                    if ($option = $this->joomlaRouter->getVar('option')) {
                        $uri->setVar('option', $option);
                    }
                    
                    // 10.6.2012 dajo: Removed, was behaving differently than Joomla router
                    
                    //$item = $menu->getDefault();

                    // Workaround until Joomla menu bug will be fixed
                    //$items=$menu->getItems(array('home','language'),array('1','*'));
                    //$item=$items[0];

                    //if (is_object($item)) {
                    //    foreach($item->query as $k => $v) {
                    //        $uri->setVar($k, $v);
                    //    }

                    //    // Set Itemid
                    //    $uri->setVar('Itemid', $item->id);
                    //}
                }
            }
        } // if ($url != 'index.php')
        else {
            // Set the current menu item's query if set to
            // (default Joomla's behaviour)
            $sefConfig = SEFConfig::getConfig();
            if ($sefConfig->indexPhpCurrentMenu) {
                $itemid = $this->getVar('Itemid');
                if (!is_null($itemid)) {
                    $item = $menu->getItem($itemid);
                    if (is_object($item)) {
                        $uri->setQuery($item->query);
                        $uri->setVar('Itemid', $itemid);
                    }
                }
                else {
                    // Set at least option
                    $option = $this->getVar('option');
                    if (!is_null($option)) {
                        $uri->setVar('option', $option);
                    }
                }
            }
        }

        JoomSEF::build($uri);
        //$uri->setHost('joomla7.ar');

        // Combine original path with new path
        // It's not necesarry in new versions of Joomla and cause some problems
        /*$path = $uri->getPath();
        if ($path != "") {
            if (substr($origPath, 0, 10) == 'index.php/')
            {
                $origPath = substr($origPath, 10);
            }
            $path = rtrim($origPath, '/').$path;
        }
        $uri->setPath($path);*/

        return $uri;
    }

    function getMode()
    {
        return JROUTER_MODE_SEF;
    }

    function parse(&$siteRouter, &$uri)
    {
        // Call this function only once in the stack, so
        // we can use Joomla default router to parse
        if ($this->parsing) {
            return array();
        }
        $this->parsing = true;

        $mainframe =& JFactory::getApplication();
        JoomSEF::set('sef.global.meta', SEFTools::GetSEFGlobalMeta());
        
        // Restore global "Add suffix to URLs"
        $sefSuffix = JoomSEF::get('sef.global.orig_sef_suffix');
        $config = JFactory::getConfig();
        $config->set('sef_suffix', $sefSuffix); 

        $vars   = array();
        $vars = JoomSEF::parse($uri);
        $menu = $mainframe->getMenu('site');

        // Parsing done
        $this->parsing = false;

        // Fix the start variable
        $start = $uri->getVar('start');
        if (!is_null($start) && is_null($uri->getVar('limitstart'))) {
            $uri->delVar('start');
            $vars['limitstart'] = $start;
        }

        //Handle an empty URL (special case)
        if(empty($vars['Itemid']) && empty($vars['option']))
        {
            //$item = $menu->getDefault();
            // Workaround until Joomla menu bug will be fixed
            $items=$menu->getItems(array('home','language'),array('1','*'));
            $item=$items[0];
            if(!is_object($item)) return $vars; // No default item set

            // set the information in the request
            $vars = $item->query;

            // get the itemid
            $vars['Itemid'] = $item->id;

            // set the active menu item
            $menu->setActive($vars['Itemid']);

            // set vars
            $this->setRequestVars($vars);

            $this->fixDocument($vars);

            return $vars;
        }

        // Get the item id, if it hasn't been set force it to null
        if( empty($vars['Itemid']) ) {
            $vars['Itemid'] = JRequest::getInt('Itemid', null);
        }

        // Set vars
        $this->setVars($vars);
        $siteRouter->setVars($vars);
        
        // Make sure the Joomla router doesn't process URL any further
        $siteRouter->setMode(JROUTER_MODE_DONT_PARSE);
        
        // No option? Get the full information from the itemid
        if( empty($vars['option']) )
        {
            $item = $menu->getItem($this->getVar('Itemid'));
            if(!is_object($item)) return $vars; // No default item set

            $vars = $vars + $item->query;
        }

        // Set the active menu item
        $menu->setActive($this->getVar('Itemid'));

        // Set base href
        //$this->setBaseHref($vars);

        // Set vars
        $this->setRequestVars($vars);

        $this->fixDocument($vars);

        return $vars;
    }

    function fixDocument(&$vars)
    {
        $sefConfig =& SEFConfig::getConfig();

        if ($sefConfig->fixDocumentFormat) {
            if (isset($vars['format']) || isset($vars['no_html'])) {
                // Set currently created document to null if its type is different
                // than requested, so it will be created again with correct format parsed from URL
                if (isset($vars['no_html'])) {
                    JFactory::$document = null;
                }
                else {
                    $doc = JFactory::getDocument();
                    if ($doc->getType() != $vars['format']) {
                        JFactory::$document = null;
                    }
                }
            }
        }
    }

    function setRequestVars(&$vars)
    {
        $sefConfig =& SEFConfig::getConfig();

        // 6.9.2013 dajo: override for Kunena home view, because Kunena since version 3 calls the router
        //                again from home controller, which overwrites its modified variables and causes endless loop
        if (is_array($vars) && isset($vars['option']) && $vars['option'] == 'com_kunena' &&
            isset($vars['view']) && $vars['view'] == 'home')
        {
            return;
        }
        
        if( $sefConfig->preventNonSefOverwrite ) {
            // Set the variables to JRequest, as mainframe does not overwrite
            // non-sef variables, so they hide the parsed ones

            if( is_array($vars) && count($vars) ) {
                foreach($vars as $name => $value) {
                    if (!is_array($value) && (strlen($value) == 0)) {
                        continue;
                    }
                    
                    // Clean the var
                    $GLOBALS['_JREQUEST'][$name] = array();

                    // Set the GET array
                    $_GET[$name] = $value;
                    $GLOBALS['_JREQUEST'][$name]['SET.GET'] = true;

                    // Set the REQUEST array if request method is GET
                    if( $_SERVER['REQUEST_METHOD'] == 'GET' ) {
                        $_REQUEST[$name] = $value;
                        $GLOBALS['_JREQUEST'][$name]['SET.REQUEST'] = true;
                    }
                }
            }
        }
    }

}
?>