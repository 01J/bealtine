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
defined('_JEXEC') or die('Restricted access.');
//if (!defined('_JEXEC') && !JoomSEF::CheckAccess()) die();

jimport('joomla.plugin.helper');
jimport('joomla.language.helper');
require_once JPATH_ADMINISTRATOR.'/components/com_sef/classes/config.php';
require_once JPATH_ADMINISTRATOR.'/components/com_sef/classes/seftools.php';
require_once JPATH_ADMINISTRATOR.'/components/com_sef/models/logger.php';
require_once JPATH_SITE.'/components/com_sef/sef.cache.php';
require_once JPATH_SITE.'/components/com_sef/sef.router.php';

define ('JROUTER_MODE_DONT_PARSE', 2);

class JoomSEF
{
    private $_data=null;
    static $props = array();
    
    function getInstance() {
        static $instance;
        if(!isset($instance)) {
            $instance=new JoomSEF();
        }
        return $instance;
    }
    
    function setData($data) {
        $this->_data=$data;
    }
    
    function set($name, $value)
    {
        if (!is_array(self::$props)) {
            self::$props = array();
        }
        
        $previous = self::get($name);
        self::$props[$name] = $value;
        
        return $previous;
    }
    
    function get($name, $default = null)
    {
        if (!is_array(self::$props) || !isset(self::$props[$name])) {
            return $default;
        }
        
        return self::$props[$name];
    }
    
    /**
     * Calls require_once in an isolated function to prevent overwriting any
     * local variables
     * 
     * @param string $file
     */
    protected static function _isolatedRequireOnce($file)
    {
        require_once($file);
    }
    
    /**
     * Tries to create a new router object for given component, returns NULL
     * if object not supported
     * 
     * @staticvar JComponentRouterInterface[] $cache
     * @param string $component Component name in format "com_xxx"
     * @return \JComponentRouterInterface
     */
    protected static function getComponentRouter($component)
    {
        static $cache = array();
        
        if (!array_key_exists($component, $cache)) {
            $router = null;
            $compName = ucfirst(substr($component, 4));
            $class = $compName.'Router';
            
            if (class_exists($class)) {
                // Check if it supports the Joomla router interface, because
                // some components use classes with the same name causing
                // fatal error then (eg. EasyBlog)
                $reflection = new ReflectionClass($class);
                if (in_array('JComponentRouterInterface', $reflection->getInterfaceNames())) {
                    // Create the router object
                    $app = JFactory::getApplication();
                    $menu = $app->getMenu('site');
                    $router = new $class($app, $menu);
                }
            }
            
            // If router class not supported, create legacy router object (Joomla 3)
            if (!$router && class_exists('JComponentRouterLegacy')) {
                $router = new JComponentRouterLegacy($compName);
            }
            
            // Cache the router object
            $cache[$component] = $router;
        }
        
        return $cache[$component];
    }
    
    function build(&$uri,$check=false)
    {
        static $extsCache;

        if (!isset($extsCache)) {
            $extsCache = array();
        }

        $mainframe = JFactory::getApplication();
        $jRouter = $mainframe->getRouter();
        $jRouter->SetMode(JROUTER_MODE_SEF);

        $config = JFactory::getConfig();
        $sefConfig = SEFConfig::getConfig();
        $cache = SEFCache::getInstance();

        // Restore global "Add suffix to URLs"
        $sefSuffix = JoomSEF::get('sef.global.orig_sef_suffix');
        $config->set('sef_suffix', $sefSuffix);
         
        // trigger onSefStart patches
        $mainframe->triggerEvent('onSefStart');

        // Do not SEF URLs with specific format or template according to configuration
        if (in_array($uri->getVar('format'), array('raw', 'json', 'xml')) || ($uri->getVar('tmpl') == 'raw') ||
            (!$sefConfig->sefComponentUrls && ($uri->getVar('tmpl') == 'component')))
        {
            $uri = JoomSEF::_createUri($uri);
            $mainframe->triggerEvent('onSefEnd');
            $jRouter->SetMode(JROUTER_MODE_RAW);
            return;
        }
        
        // check URL for junk if set to
        $vars = $uri->getQuery(true);
        if ($sefConfig->checkJunkUrls) {
            $junkWords =& $sefConfig->getJunkWords();
            $seferr = false;

            if (substr($uri->getVar('option', ''), 0, 4) != 'com_') {
                $seferr = true;
            }
            elseif (count($junkWords)) {
                $exclude =& $sefConfig->getJunkExclude();

                foreach ($vars as $key => $val) {
                    if (in_array($key, $exclude)) continue;

                    // Check junk words
                    foreach ($junkWords as $word) {
                        if (is_string($val)) {
                            if (strpos($val, $word) !== false) {
                                $seferr = true;
                                break;
                            }
                        }
                    }
                    if ($seferr) break;
                }
            }

            if ($seferr) {
                // trigger onSefEnd patches
                $mainframe->triggerEvent('onSefEnd');
                $jRouter->SetMode(JROUTER_MODE_RAW);

                // fix the path
                $path = $uri->getPath();

                return;
            }
        }
        
        // Handle lang variable
        if ($sefConfig->langEnable && $check == false) {
            $langs = JLanguageHelper::getLanguages('sef');
            $langsCode = JLanguageHelper::getLanguages('lang_code');
            
            $langVar = $uri->getVar('lang');
            if (empty($langVar)) {
                $langVar = JRequest::getVar('lang');
                $uri->setVar('lang', $langVar);
            }
            
            // Check for non-existent language
            if (!isset($langs[$langVar])) {
                // Not a SEF code, check for long code
                if (isset($langsCode[$langVar])) {
                    // Fix the code to short version
                    $uri->setVar('lang', $langsCode[$langVar]->sef);
                }
                else {
                    // Non-existent language, use current
                    $curLang = JFactory::getLanguage();
                    $uri->setVar('lang', $langsCode[$curLang->getTag()]->sef);
                }
            }
            
            // Check for mismatched language and Itemid?
            if ($sefConfig->mismatchedLangHandling != _COM_SEF_MISMATCHED_LANG_DONT_HANDLE) {
                $langVar = $uri->getVar('lang');
                $itemidVar = $uri->getVar('Itemid');
                if (!empty($langVar) && !empty($itemidVar)) {
                    // Get menu item language
                    $menu = $mainframe->getMenu('site');
                    $item = $menu->getItem($itemidVar);
                    if (is_object($item) && !empty($item->language) && ($item->language != '*')) {
                        if ($langsCode[$item->language]->sef != $langVar) {
                            if ($sefConfig->mismatchedLangHandling == _COM_SEF_MISMATCHED_LANG_DONT_SEF) {
                                // Don't SEF
                                $mainframe->triggerEvent('onSefEnd');
                                $jRouter->SetMode(JROUTER_MODE_RAW);
                                return;
                            }
                            else {
                                // Fix lang variable
                                $uri->setVar('lang', $langsCode[$item->language]->sef);
                            }
                        }
                    }
                }
            }
        }

        // Correct FaLang support for translations
        $prevLang = '';
        if ($sefConfig->langEnable && $check == false) {
            $langVar = $uri->getVar('lang');
            if (!empty($langVar)) {
                $langCode = JoomSEF::getLangCode($langVar);
                if (!is_null($langCode)) {
                    $curCode = JoomSEF::getLangCode();
                    if ($langCode != $curCode) {
                        // URL language is different from current language,
                        // change current language for correct translations
                        $language = JFactory::getLanguage();
                        $prevLang = $language->setLanguage($langCode);
                        
                        // 6.12.2012 dajo: Make sure that loaded language overwrites current strings!
                        $language->load('joomla', JPATH_BASE, null, true);
                    }
                }
            }
        }

        // if there are no variables and only single language is used
        $vars = $uri->getQuery(true);
        if (empty($vars) && !isset($lang)) {
            JoomSEF::_endSef($prevLang);
            return;
        }

        
        $option = $uri->getVar('option');
        if (!is_null($option)) {
            $params =& SEFTools::getExtParams($option);

            // Check the stop rule
            $stopRule = trim($params->get('stopRule', ''));
            if( $stopRule != '' ) {
                if( preg_match('/'.$stopRule.'/', $uri->toString()) > 0 ) {
                    // Don't SEF this URL
                    $uri = JoomSEF::_createUri($uri);
                    JoomSEF::_endSef($prevLang);
                    $jRouter->SetMode(JROUTER_MODE_RAW);
                    return;
                }
            }

            if(strlen($uri->getVar('Itemid'))==0) {
                $uri->delVar('Itemid');
            }            

            

            $handling = $params->get('handling', '0');
            switch($handling) {
                // skipped extensions
                case '2': {
                    // Check homepage
                    if (JoomSEF::_isHomePage($uri)) {
                        $lang = $uri->getVar('lang');
                        if (empty($lang)) {
                            JoomSefUri::updateUri($uri, 'index.php');
                        }
                        else {
                            JoomSefUri::updateUri($uri, 'index.php?lang='.$lang);
                        }
                    }

                    // Build URL
                    $uri = JoomSEF::_createUri($uri);
                    JoomSEF::_endSef($prevLang);
                    $jRouter->SetMode(JROUTER_MODE_RAW);
                    return;
                }
                // non-cached extensions
                case '1': {
                    // Check homepage
                    if (JoomSEF::_isHomePage($uri)) {
                        $lang = $uri->getVar('lang');
                        if (empty($lang)) {
                            JoomSefUri::updateUri($uri, 'index.php');
                        }
                        else {
                            JoomSefUri::updateUri($uri, 'index.php?lang='.$lang);
                        }
                    }
                    JoomSEF::_endSef($prevLang);
                    return;
                }
                // default handler or basic rewriting
                default: {
                    // if component has its own sef_ext plug-in included.
                    // however, prefer own plugin if exists (added by Michal, 28.11.2006)
                    $compExt = JPATH_ROOT.'/components/'.$option.'/router.php';
                    $ownExt = JPATH_ROOT.'/components/com_sef/sef_ext/'.$option.'.php';

                    // compatible extension build block
                    if (file_exists($compExt) && !file_exists($ownExt) && ($handling == '0')) {
                        // Check homepage
                        if (JoomSEF::_isHomePage($uri)) {
                            $lang = $uri->getVar('lang');
                            if (empty($lang)) {
                                JoomSefUri::updateUri($uri, 'index.php');
                            }
                            else {
                                JoomSefUri::updateUri($uri, 'index.php?lang='.$lang);
                            }
                            
                            // Create homepage SEF URL
                            $title = array();
                            $data = JoomSEF::_sefGetLocation($uri, $title, null, null, null, $uri->getVar('lang'));
                            $uri = JoomSEF::_storeLocation($data);
                            // remove path as Joomla will add it back
                            $uri->setPath(preg_replace("@^".$uri->base(true)."@","",$uri->getPath()));
                            
                            // Disable global "Add suffix to URLs" again
                            $config->set('sef_suffix', 0);
                            JoomSEF::_endSef($prevLang);
                            return;
                        }

                        // load the plug-in file
                        self::_isolatedRequireOnce($compExt);

                        $app        = JFactory::getApplication();
                        $menu       = $app->getMenu('site');
                        $route      = $uri->getPath();
                        $query      = $uri->getQuery(true);
                        $component  = preg_replace('/[^A-Z0-9_\.-]/i', '', $query['option']);
                        $tmp        = '';
                        
                        // Replace limitstart -> start, which is default Joomla's behaviour
                        if (isset($query['limitstart'])) {
                            $query['start'] = (int) $query['limitstart'];
                            unset($query['limitstart']);
                        }

                        // Get component router object if supported
                        $componentRouter = self::getComponentRouter($component);
                        if ($componentRouter) {
                            // Use the new Joomla 3 interface
                            $parts = $componentRouter->build($query);
                        }
                        else {
                            // Use old procedural approach
                            $function = substr($component, 4) . 'BuildRoute';
                            $parts    = $function($query);
                        }
                        
                        if (!is_array($parts)) {
                            if (is_string($parts)) {
                                $parts = array($parts);
                            }
                            else {
                                // Don't SEF
                                JoomSEF::_endSef($prevLang);
                                // Disable global "Add suffix to URLs" again
                                $config->set('sef_suffix', 0);
                                return;
                            }
                        }
                        
                        $total = count($parts);
                        for ($i = 0; $i < $total; $i++) {
                            $parts[$i] = str_replace(':', '-', $parts[$i]);
                        }

                        $result = implode('/', $parts);
                        $tmp    = ($result != "") ? '/'.$result : '';

                        // build the application route
                        $built = false;
                        if (isset($query['Itemid']) && !empty($query['Itemid'])) {
                            $item = $menu->getItem($query['Itemid']);

                            if (is_object($item) && $query['option'] == $item->component) {
                                $tmp = !empty($tmp) ? $item->route.$tmp : $item->route;
                                $built = true;
                            }
                        }

                        if(!$built) {
                            $tmp = 'component/'.substr($query['option'], 4).$tmp;
                        }

                        $route .= '/'.$tmp;
                        if($app->getCfg('sef_suffix') && !(substr($route, -9) == 'index.php' || substr($route, -1) == '/')) {
                            if (($format = $uri->getVar('format', 'html'))) {
                                $route .= '.' . $format;
                                $uri->delVar('format');
                            }
                        }

                        if($app->getCfg('sef_rewrite')) {
                            // transform the route
                            $route = str_replace('index.php/', '', $route);
                        }

                        // Unset unneeded query information
                        unset($query['Itemid']);
                        unset($query['option']);

                        //Set query again in the URI
                        $uri->setQuery($query);
                        $uri->setPath($route);

                        $uri = JoomSEF::_createUri($uri);

                        JoomSEF::_endSef($prevLang);
                        
                        // Disable global "Add suffix to URLs" again
                        $config->set('sef_suffix', 0);
                        
                        return;
                    }
                    // own extension block
                    else {
                        // Disable global "Add suffix to URLs"
                        $config->set('sef_suffix', 0);
                        
                        if ($handling == '3') {
                            // Basic rewriting
                            $class = 'SefExt_Basic';
                        }
                        else {
                            if (file_exists($ownExt)) {
                                $class = 'SefExt_'.$option;
                            } else {
                                $class = 'SefExt';
                            }
                        }
                        
                        // Extensions cache
                        if (!class_exists($class)) {
                            require($ownExt);
                        }
                        $sef_ext = new $class();
                        $extsCache[$class] = $sef_ext;

                        // Set currently handled URI
                        $sef_ext->setCurrentUri($uri);

                        // 17.2.2012, dajo: isHomePage should be tested before the beforeCreate() is called
                        // Grr Joomla SEF router adds home Itemid to Items without menu Item assigned
                        $homes=array_keys(SEFTools::getHomeQueries());
                        if(in_array($uri->getVar('Itemid'),$homes) && !JoomSEF::_isHomePage($uri)) {
                            $uri->setVar('Itemid',JRequest::getInt('Itemid'));
                        }
                        
                        // Let the extension change the url and options
                        $sef_ext->beforeCreate($uri);
                        list($sid, $mosmsg) = self::_prepareUriForCreate($params, $uri);
                        
                        // Get nonsef and ignore vars from extension
                        list($nonSefVars, $ignoreVars) = $sef_ext->getNonSefVars($uri);

                        // Create array of all the non sef vars
                        $nonSefVars = SEFTools::getNonSefVars($uri, $nonSefVars, $ignoreVars);

                        // Create a copy of JURI object
                        $uri2 = clone($uri);

                        // Remove nonsef variables from our JURI copy
                        $nonSefUrl = SEFTools::RemoveVariables($uri2, array_keys($nonSefVars));
                        
                        // Check homepage
                        if (JoomSEF::_isHomePage($uri2, true)) {
                            $title = array();
                            $lng = $uri2->getVar('lang');
                            if ($sefConfig->langEnable && ($sefConfig->langPlacementJoomla != _COM_SEF_LANG_DOMAIN) && ($sefConfig->alwaysUseLangHomeJoomla || ($lng != $sefConfig->mainLanguageJoomla))) {
                                $title[] = $lng;
                            }
                            $pagination=false;
                            if(method_exists($sef_ext,"_processPagination")) {
                                $title=array_merge($title,$sef_ext->_processPagination($uri2));
                                $pagination=true;
                            }
                            if($uri2->getVar('format')=='feed') {
                                $title[]=$uri2->getVar('type');
                            }
                            $data = JoomSEF::_sefGetLocation($uri2, $title, null, null, null, $uri->getVar('lang'),null,null,null,null,$pagination);
                            unset($data["lang"]);
                            
                            // We need to copy data, otherwise we would return $uri2 object - not working in Joomla 3
                            JoomSefUri::copyUri(JoomSEF::_storeLocation($data), $uri);
                            
                            // remove path as Joomla will add it back
                            $uri->setPath(preg_replace("@^".$uri->base(true)."@","",$uri->getPath()));
                            // Set non-SEF variables
                            $uri->setQuery($nonSefUrl);
                            
                            // Set domain
                            if ($sefConfig->langEnable && ($sefConfig->langPlacementJoomla == _COM_SEF_LANG_DOMAIN)) {
                                if (!empty($lng) && isset($sefConfig->subDomainsJoomla[$lng])) {
                                    $uri->setHost($sefConfig->subDomainsJoomla[$lng]);
                                }
                            }
                            
                            JoomSEF::_endSef($prevLang);
                            return;
                        }
                        
                        // clean Itemid if desired
                        // David: only if overriding is disabled
                        $override = $params->get('itemid', '0');
                        if (isset($sefConfig->excludeSource) && $sefConfig->excludeSource && ($override == '0')) {
                            $Itemid = $uri->getVar('Itemid');
                            $uri2->delVar('Itemid');
                        }

                        
                        $url = JoomSEF::_uriToUrl($uri2);

                        // try to get url from cache
                        $sefUrl = false;
                        if ($sefConfig->useCache) {
                            if(!$check) {
                                $sefUrl = $cache->GetSefUrl($url);
                            }
                        }
                        if (!$sefConfig->useCache || !$sefUrl) {
                            // check if the url is already saved in the database
                            $sefUrl = $sef_ext->getSefUrlFromDatabase($uri2);

                            if (is_string($sefUrl)) {
                                // Backward compatibility
                                $sefstring = $sefUrl;
                                $sefUrl = new stdClass();
                                $sefUrl->sefurl = $sefstring;
                                $sefUrl->sef = 1;
                                $sefUrl->host = '';
                            }
                        }

                        // unknown URL yet
                        if (!$sefUrl || $check) {
                            // load JoomSEF Language File
                            JFactory::getLanguage()->load('com_sef',JPATH_ADMINISTRATOR);
                            // rewrite the URL, creating new JURI object
                            $data = $sef_ext->create($uri);
                            if (is_object($data) && is_a($data, 'JURI')) {
                                // Backwards compatibility
                                JoomSefUri::copyUri($data, $uri);
                            }
                            else {
                                if($sefConfig->langPlacementJoomla==_COM_SEF_LANG_PATH) {
                                    // if data is not array, then we don't have in lang language from SEF extension, because it's original URL 
                                    if(is_array($data)) {
                                        if($data['lang']=='*') {
                                            // If we don't want to have language in multilanguage content strip down the language from path to eleminate duplicit pages with same content
                                            if($sefConfig->addLangMulti) {
                                                $data["lang"]=$data["uri"]->getVar('lang');
                                            } else {
                                                unset($data["lang"]);
                                                $data["uri"]->delVar('lang');
                                            }
                                        } else {
                                            $langs=JLanguageHelper::getLanguages('lang_code');
                                            if(array_key_exists($data["lang"],$langs)) {
                                                $data["lang"]=$langs[$data["lang"]]->sef;
                                            }
                                            if(!strlen($data["lang"])) {
                                                $data["lang"]=$data["uri"]->getVar('lang');
                                            }
                                        }
                                    }
                                    if($sefConfig->alwaysUseLangJoomla==false) {
                                        if(isset($data["lang"]) && $data["lang"]==$sefConfig->mainLanguageJoomla) {
                                            unset($data["lang"]);
                                            $data["uri"]->delVar('lang');
                                        }
                                    }
                                }
                                
                                $titlepage=false;
                                
                                $subdomain=SEFTools::getSubdomain($uri->getVar('Itemid'),$uri,$titlepage);
                                if(strlen($subdomain)) {
                                    $curHost = JFactory::getURI()->getHost();
                                    if (substr($curHost, 0, 4) == 'www.') {
                                        $curHost = substr($curHost, 4);
                                    }
                                    $uri->setHost($subdomain.'.'.$curHost);
                                }
                                
                                if($titlepage) {
                                    $data["title"]=array();
                                }
                                   
                                if(!isset($data["host"])) {
                                    $data["host"]=$uri->getHost();
                                }
                                
                                if($check) {
                                    $this->_data=$data;
                                }
                                
                                // 12.11.2012 dajo: Itemid must be removed in _storeLocation after the menu title is removed too
                                /*if (isset($sefConfig->excludeSource) && $sefConfig->excludeSource && ($override == '0')) {
                                    if (isset($data['uri'])) {
                                        $data['uri']->delVar('Itemid');
                                    }
                                }*/
                                
                                $removeItemid = (isset($sefConfig->excludeSource) && $sefConfig->excludeSource && ($override == '0'));
                                JoomSefUri::copyUri(JoomSEF::_storeLocation($data, $check, $removeItemid), $uri);
                            }
                        } else {
                            // if SEF is disabled, don't SEF
                            if (isset($sefUrl->sef) && !$sefUrl->sef) {
                                $uri = JoomSEF::_createUri($uri);
                                JoomSEF::_endSef($prevLang);
                                $jRouter->SetMode(JROUTER_MODE_RAW);
                                return;
                            }

                            // Create new JURI object from $sefstring
                            if (!isset($sefUrl->host) || !strlen($sefUrl->host)) {
                                $root = JFactory::getUri()->toString(array('host', 'port'));
                            } else {
                                $root = $sefUrl->host;
                            }
                            $url = JFactory::getURI()->getScheme()."://".$root.JURI::root(true);

                            if (substr($url, -1) != '/') {
                                $url .= '/';
                            }
                            if (isset($sefUrl->sefurl)) {
                                $url .= $sefUrl->sefurl;
                            }

                            // Add nonSef part if set
                            if( !empty($nonSefUrl) ) {
                                $url .= '?'.$nonSefUrl;
                            }

                            // Add fragment if set
                            $fragment = $uri->getFragment();
                            if (!empty($fragment)) {
                                $url .= '#'.$fragment;
                            }
                            JoomSefUri::updateUri($uri, $url);
                        }

                        // Set domain
                        if ($sefConfig->langEnable && ($sefConfig->langPlacementJoomla == _COM_SEF_LANG_DOMAIN)) {
                            $lng = $uri2->getVar('lang');
                            if (!empty($lng) && isset($sefConfig->subDomainsJoomla[$lng])) {
                                $uri->setHost($sefConfig->subDomainsJoomla[$lng]);
                            }
                        }
                            
                        // reconnect the sid to the url
                        if (!empty($sid) && COM_SEF_CONFIG_REMOVE_SID) $uri->setVar('sid', $sid);
                        // reconnect mosmsg to the url
                        if (!empty($mosmsg)) $uri->setVar('mosmsg', $mosmsg);

                        // reconnect ItemID to the url
                        // David: only if extension doesn't set its own Itemid through overrideId parameter
                        if (isset($sefConfig->excludeSource) && $sefConfig->excludeSource && $sefConfig->reappendSource && ($override == '0') && !empty($Itemid)) {
                            $uri->setVar('Itemid', $Itemid);
                        }

                        // let the extension change the resulting SEF url
                        $sef_ext->afterCreate($uri);
                    }
                }
            }
        }
        else if (!is_null($uri->getVar('Itemid'))) {            
            // there is only Itemid present - we must override the Ignore multiple sources option
            $oldIgnore = $sefConfig->ignoreSource;
            $sefConfig->ignoreSource = 0;

            $lang="";
            $title = array();
            $title[] = JoomSEF::_getMenuTitleLang(null, $lang, $uri->getVar('Itemid'));

            $data = JoomSEF::_sefGetLocation($uri, $title, null, null, null, strlen($lang)?$lang:$uri->getVar('lang'));
            $uri = JoomSEF::_storeLocation($data);

            $sefConfig->ignoreSource = $oldIgnore;
        }
        $uri->setPath(preg_replace("@^".$uri->base(true)."@","",$uri->getPath()));
        
        JoomSEF::_endSef($prevLang);
        
        // Set Joomla's router so it doesn't process URL further
        $jRouter->SetMode(JROUTER_MODE_RAW);
    }
    
    function _prepareUriForCreate(&$params, &$uri) {
        $sefConfig = SEFConfig::getConfig();
        
        // Ensure that the session IDs are removed
        // If set to
        $sid = $uri->getVar('sid');
        if (COM_SEF_CONFIG_REMOVE_SID) $uri->delVar('sid');
        // Ensure that the mosmsg are removed.
        $mosmsg = $uri->getVar('mosmsg');
        $uri->delVar('mosmsg');

        // override Itemid if set to
        $override = $params->get('itemid', '0');
        $overrideId = $params->get('overrideId', '');
        if (($override != '0') && ($overrideId != '')) {
            $uri->setVar('Itemid', $overrideId);
        }
        
        return array($sid, $mosmsg);
    }
    
    /**
     * Converts given language sef code to tag (eg. en => en-GB)
     * If given language is empty, returns current language tag
     */
    function getLangCode($sef = '') {
        if (empty($sef)) {
            $lang = JFactory::getLanguage();
            return $lang->getTag();
        }
        
        $langs = JLanguageHelper::getLanguages('sef');
        if (isset($langs[$sef])) {
            return $langs[$sef]->lang_code;
        }
        
        return null;
    }
    
    function getLanguage($uri) {
        $sefConfig =& SEFConfig::getConfig();
        
        if (!$sefConfig->langEnable) {
            // Use default language from Joomla or whichever plugin
            return;
        }
        
        $suffix = $sefConfig->suffix;
        $lang = '';
        $langs = JLanguageHelper::getLanguages('sef');
        
        JFactory::getApplication()->setLanguageFilter(true);
        switch($sefConfig->langPlacementJoomla) {
            case _COM_SEF_LANG_PATH:
                $lang = $uri->getVar('lang');
                if (strlen($lang) == 0) {
                    // Get language from path
                    $path = $uri->getPath();
                    $suffixLen = strlen($suffix);
                    if ($suffixLen > 0) {
                        if (substr($path, -$suffixLen) == $suffix) {
                            $path = substr($path, 0, -$suffixLen);
                        }
                    }
                    $path = str_replace($uri->base(true), '', $path);
                    $path = ltrim($path, '/');
                    $path = explode('/', $path);
                    if (array_key_exists($path[0], $langs)) {
                        $lang = $path[0];
                    }
                }
                break;
            case _COM_SEF_LANG_DOMAIN:
                // 22.2.2012, dajo: simplified and fixed the function
                $host = trim($uri->toString(array('host')), '/');
                foreach ($sefConfig->subDomainsJoomla as $lng => $domain) {
                    if ($host == $domain) {
                        $lang = $lng;
                        
                        // Save the language code obtained from domain for later use
                        self::set('domain_lang', $lang);
                        
                        break;
                    }
                }
                break;
        }
        
        if (strlen($lang) == 0) {
            $pth = rtrim($uri->getPath(), '/');
            if (substr($pth, -9) == 'index.php') {
                $pth = substr($pth, 0, -9);
                $pth = rtrim($pth, '/');
            }
            if ($pth == rtrim(JURI::base(true), '/')) {
                // This is homepage
                if ($sefConfig->alwaysUseLangHomeJoomla || (count($uri->getQuery(true)) > 0) || (count($_POST) > 0)) {
                    // 17.4.2013 dajo: we should also use cookie if there are some variables set,
                    // eg. for VirtueMart's AJAX cart to display correct language
                    
                    // Language code must always be present, so we can use
                    // cookie and browser setting if it's not there
                    if ($sefConfig->langCookieJoomla) {
                        $lang = JRequest::getString('joomsef_lang', '', 'cookie');
                    }
                    
                    if (strlen($lang) == 0 || !isset($langs[$lang])) {
                        if ($sefConfig->browserLangJoomla) {
                            $lang = JLanguageHelper::detectLanguage();
                            $langsCode = JLanguageHelper::getLanguages('lang_code');
                            if (isset($langsCode[$lang])) {
                              $lang = $langsCode[$lang]->sef;
                            }
                        }
                    }
                }
                
                // If no other language set, use the default one
                if (strlen($lang) == 0 || !isset($langs[$lang])) {
                    $lang = $sefConfig->mainLanguageJoomla;
                }
            } else {
                // This is not homepage, so if language is not present in URL, use the default one
                $lang = $sefConfig->mainLanguageJoomla;
            }
        }
        
        if (strlen($lang)) {
            if (!isset($langs[$lang])) {
                $lang = reset(array_keys($langs));
            }
            $lang_code = $langs[$lang]->lang_code;
            $cfg = JFactory::getConfig();
            $cfg->set('language', $lang_code);
            JRequest::setVar('lang', $lang);
            JRequest::setVar('language', $lang_code);
            JFactory::getLanguage()->setLanguage($lang_code);
            if ($sefConfig->langCookieJoomla && !headers_sent()) {
                setcookie('joomsef_lang', $lang, time()+24*60*60*1000, "/");
            }
            JFactory::getLanguage()->getMetadata($lang_code);
            
            // Set correct sitename
            if (!empty($langs[$lang]->sitename)) {
                $cfg->set('sitename', $langs[$lang]->sitename);
            }
            
            // Set VM currency if enabled
            if ($sefConfig->vmCurrencyEnable) {
                if (isset($sefConfig->vmCurrency[$lang]) && !is_null($sefConfig->vmCurrency[$lang])) {
                    $app = JFactory::getApplication();
                    $app->setUserState('virtuemart_currency_id', $sefConfig->vmCurrency[$lang]);
                }
            }
        }
    }

    function parse(&$uri)
    {
        $sefConfig =& SEFConfig::getConfig();
        $langs=JLanguageHelper::getLanguages('sef');
        // add base path, as Joomla does not send it but we count on it 
        $uri->setPath(JURI::base(true) . '/' . $uri->getPath());
        
        $url_query=$uri->getQuery();
        $host=explode(".",$uri->getHost());
        $subdomain=array_shift($host);
        $db=JFactory::getDBO();
        // Subdomain titlepage
        if(($uri->getPath()==JURI::base(true).'/') && empty($url_query) && empty($_POST)) {
            $query=$db->getQuery(true);
            $query->select('Itemid_titlepage')->from('#__sef_subdomains');
            $query->where('subdomain='.$db->quote($subdomain));
            if($sefConfig->langEnable) {
                $lang=JRequest::getVar('lang');
                $query->where('lang='.$db->quote($lang));
            }
            $db->setQuery($query, 0, 1);
            $Itemid=$db->loadResult();
            if($sefConfig->langEnable==1 && $sefConfig->alwaysUseLangHomeJoomla && $Itemid>0) {
                SEFTools::redirect($uri->base(true).'/'.$lang, true);
            } else { 
                if($Itemid>0) {
                    $uri->setVar('Itemid',$Itemid);
                    JoomSEF::set('real_domain', JFactory::getUri()->getHost());
                    JFactory::getUri()->setHost(implode(".",$host));
                }
            }
        } else if(empty($url_query) && empty($_POST) && $sefConfig->langEnable) {
            $query=$db->getQuery(true);
            $query->select('Itemid_titlepage')->from('#__sef_subdomains');
            $query->where('subdomain='.$db->quote($subdomain));
            $lang=JRequest::getVar('lang');
            $query->where('lang='.$db->quote($lang));            
            $db->setQuery($query, 0, 1);
            $Itemid=$db->loadResult();
            if($Itemid>0) {
                $uri->setVar('Itemid',$Itemid);
                JoomSEF::set('real_domain', JFactory::getUri()->getHost());
                JFactory::getUri()->setHost(implode(".",$host));
            }
        } else {
            $query=$db->getQuery(true);
            $query->select('COUNT(*)')->from('#__sef_subdomains')->where('subdomain='.$db->quote($subdomain));
            $db->setQuery($query);
            $cnt=$db->loadResult();
            if($cnt) {
                JoomSEF::set('real_domain', JFactory::getUri()->getHost());
                JFactory::getUri()->setHost(implode(".",$host));
            }
        }
                
        // Set Joomla's router so it doesn't process URL further
        $mainframe =& JFactory::getApplication();
        $jRouter = $mainframe->getRouter();
        $jRouter->SetMode(JROUTER_MODE_DONT_PARSE);

        // store the old URI before we change it in case we will need it
        // for default Joomla SEF
        $oldUri = clone $uri;

        

        // load patches
        JPluginHelper::importPlugin('sefpatch');

        // trigger onSefLoad patches
        $mainframe->triggerEvent('onSefLoad');

        // get path
        $path = $uri->getPath();

        // remove basepath
        $path = substr_replace($path, '', 0, strlen(JURI::base(true)));

        // remove slashes
        $path = ltrim($path, '/');
        
        // Redirect URL with / on the end to URL without / on the end
        if($sefConfig->redirectSlash) {
            $request=$_SERVER["REQUEST_URI"];
            if($request!=$uri->base(true)."/" && substr($request,-1)=='/') {
                SEFTools::redirect(rtrim($request, '/'), true);
            }
        }
        
        $request=$_SERVER["REQUEST_URI"];
        $route=str_replace($uri->getPath(),'',$request);
        $route=str_replace("?".$uri->getQuery(),'',$route);
        
        // Redirect the index.php (need to check this before index.php removal)
        // 29.11.2012 dajo: Redirect only pure index.php
        if ($sefConfig->fixIndexPhp && ($route == 'index.php') && (count($uri->getQuery(true)) == 0) && (count($_POST) == 0)) {
            $newUrl = JURI::root();
            if (substr($newUrl, -1) != '/') {
                $newUrl .= '/';
            }
            $q = $uri->getQuery();
            if (!empty($q)) {
                $newUrl .= '?'.$q;
            }
            SEFTools::redirect($newUrl, true);
        }
        
        // fix Joomla URLs with index.php/
        $path = preg_replace('#^index\\.php\\/#i', '', $path);
        
        // Redirect root URL to URL with language code
        if ($sefConfig->langEnable) {
            if (($sefConfig->langPlacementJoomla == _COM_SEF_LANG_PATH) && $sefConfig->alwaysUseLangHomeJoomla) {
                $query=$uri->getQuery(true);
                if (str_replace($uri->base(true), "", $uri->getPath()) == '/' && empty($query) && empty($_POST)) {
                    $home_items=SEFTools::getHomeQueries();
                    $lang=$langs[JRequest::getVar('lang')]->lang_code;
                    $uself=JPluginHelper::isEnabled('system','languagefilter');
                    $usefalang=JPluginHelper::isEnabled('system','falangdriver');
                    foreach($home_items as $id=>$item) {
                        if ($usefalang) {
                            if ($item->language == '*') {
                                $Itemid = $item->id;
                                $link = $item->link;
                                break;
                            }
                        }
                        else {
                            if ($item->language == $lang || ($langs[JRequest::getVar('lang')]->sef == $sefConfig->mainLanguageJoomla && $item->language == '*')) {
                                $Itemid = $item->id;
                                $link = $item->link;
                                
                                // Don't break, we may find other item that fits better
                            }
                        }
                    }
                    
                    // Add Itemid to link
                    $link .= (strpos($link, '?') === false ? '?' : '&') . 'Itemid=' . $Itemid;
                    
                    SEFTools::redirect(JRoute::_($link), ($sefConfig->rootLangRedirect303 ? false : true));
                }
            }
        }
        
        // Remove language code and redirect for main language if set to
        if ($sefConfig->langEnable && ($sefConfig->langPlacementJoomla == _COM_SEF_LANG_PATH)
            && (!$sefConfig->alwaysUseLangJoomla || !$sefConfig->alwaysUseLangHomeJoomla) && empty($_POST))
        {
            $langSefLen = strlen($sefConfig->mainLanguageJoomla);
            if (substr($path, 0, $langSefLen) == $sefConfig->mainLanguageJoomla) {
                // Main language is in the path
                if (strlen($path) == $langSefLen) {
                    // Root URL
                    $query = $uri->getQuery(true);
                    if (!$sefConfig->alwaysUseLangHomeJoomla && empty($query)) {
                        // Redirect
                        $link = JURI::root(true);
                        SEFTools::redirect($link, true);
                    }
                }
                else {
                    // Not root URL
                    if ($path[$langSefLen] == '/' && !$sefConfig->alwaysUseLangJoomla) {
                        // Redirect
                        $link = rtrim(JURI::root(true), '/').substr($path, $langSefLen);
                        $query = $uri->getQuery();
                        if (!empty($query)) {
                            $link .= '?'.$query;
                        }
                        SEFTools::redirect($link, true);
                    }
                }
            }
        }
        
        // Try the 301 Alias redirect
        if (count($_POST) == 0) {
            Joomsef::_parseAlias($path, $uri->getQuery(true));
        }

        // remove prefix (both index.php and index2.php)
        //$path = eregi_replace('^index2?.php', '', $path);
        $path = preg_replace('/^index2?.php/i', '', $path);

        // remove slashes again to be sure there aren't any left
        $path = ltrim($path, '/');

        // replace spaces with our replacement character
        // (mainly for '+' handling, but may be useful in some other situations too)
        $path = str_replace(' ', $sefConfig->replacement, $path);

        // set the route
        $uri->setPath($path);        

        // parse the url
        $vars = JoomSEF::_parseSefUrl($uri, $oldUri);

        // handle custom site name for extensions
        if (isset($vars['option'])) {
            $params =& SEFTools::getExtParams($vars['option']);

            $useSitename = $params->get('useSitename', '1');
            $customSitename = trim($params->get('customSitename', ''));

            $config =& JFactory::getConfig();

            if ($useSitename == '0') {
                // don't use site name
                $config->set('sitename', '');
            }
            elseif (!empty($customSitename)) {
                // use custom site name
                $config->set('sitename', $customSitename);
            }
        }

        // trigger onSefUnload patches
        $mainframe->triggerEvent('onSefUnload');

        return $vars;
    }

    function _parseSefUrl(&$uri, &$oldUri)
    {
        $mainframe =& JFactory::getApplication();

        $db =& JFactory::getDBO();
        $sefConfig =& SEFConfig::getConfig();

        $route = $uri->getPath();        

        //Get the variables from the uri
        $vars = $uri->getQuery(true);

        // Should we generate canonical link automatically?
        $generateCanonical = (count($vars) > 0);
        

        // handle an empty URL (special case)
        if (empty($route) || $route==JRequest::getVar('lang')) {
            if (count($vars) > 0) {
                $redir = false;
            }
            else {
                $redir = true;
            }    

            $menu  = $mainframe->getMenu('site');
            
            // TODO: handle metatags for subdomains correctly

            // if route is empty AND option is set in the query, assume it's non-sef url, and parse apropriately
            if (isset($vars['option']) || isset($vars['Itemid'])) {               
                return JoomSEF::_parseRawRoute($uri);
            }
            
            //$item = $menu->getDefault();
            // Workaround until Joomla menu bug will be fixed
            $langs=JLanguageHelper::getLanguages('sef');
            $items = null;
            if (isset($langs[JRequest::getVar('lang')])) {
                $items=$menu->getItems(array('home','language'),array('1',$langs[JRequest::getVar('lang')]->lang_code));
            }
            if (!is_array($items) || (count($items) == 0)) {
                $items = $menu->getItems(array('home'), array('1'));
            }
            $item=$items[0];

            //Set the information in the request
            $vars = $item->query;

            //Get the itemid
            $vars['Itemid'] = $item->id;

            // Set the active menu item
            $menu->setActive($vars['Itemid']);

            // Create automatic canonical link if set to
            if ($generateCanonical) {
                $extAuto = 2;
                if (isset($vars['option'])) {
                    $params =& SEFTools::getExtParams($vars['option']);
                    $extAuto = $params->get('autoCanonical', 2);
                }
                $autoCanonical = ($extAuto == 2) ? $sefConfig->autoCanonical : $extAuto;

                if ($autoCanonical) {
                    JoomSEF::set('sef.link.canonical', JURI::root());
                }
            }

            // MetaTags for frontpage
            if (JPluginHelper::isEnabled('system', 'joomsef')) {
                // ... and frontpage has meta tags
                // Get all the URLs for frontpage and try to find the correct one
                $lang = JRequest::getVar('lang');
                $query = "SELECT * FROM #__sefurls WHERE sefurl = ".$db->quote($route)." ORDER BY `priority`";
                $db->setQuery($query);
                $sefRows = $db->loadObjectList();
                
                if (is_array($sefRows)) {
                    $count = count($sefRows);
                    if ($count == 1) {
                        // Use the only one
                        $sefRow = $sefRows[0];
                    }
                    else if ($count > 1) {
                        // Loop through URLs and find the one corresponding to menu item and possibly language
                        foreach ($sefRows as $row) {
                            // Check if variables match
                            $varsOk = true;
                            parse_str(str_replace('index.php?', '', $row->origurl), $rowVars);
                            foreach ($vars as $varKey => $varVal) {
                                if ($varKey == 'Itemid') {
                                    if ($row->Itemid != $varVal) {
                                        $varsOk = false;
                                        break;
                                    }
                                }
                                else {
                                    if (!isset($rowVars[$varKey]) || ($rowVars[$varKey] != $varVal)) {
                                        $varsOk = false;
                                        break;
                                    }
                                }
                            }
                            if (!$varsOk) {
                                continue;
                            }
                            
                            // Variables match, this seems to be home page URL, try checking language
                            if (is_null($lang)) {
                                // No language, use this URL
                                $sefRow = $row;
                                break;
                            }
                            else {
                                // Check language
                                if (isset($rowVars['lang'])) {
                                    if ($rowVars['lang'] == $lang) {
                                        // Found exact URL
                                        $sefRow = $row;
                                        break;
                                    }
                                }
                                else if (empty($noLang)) {
                                    // This URL doesn't contain lang variable, store it for later use
                                    $noLang = $row;
                                }
                            }
                        }
                        
                        // If we didn't find correct URL, try to use the one without lang variable
                        if (empty($sefRow) && !empty($noLang)) {
                            $sefRow = $noLang;
                        }
                    }
                }
                
                // Set meta tags
                if( !empty($sefRow) ) {
                    $mainframe =& JFactory::getApplication();
                    if (!empty($sefRow->metatitle))  JoomSEF::set('sef.meta.title', $sefRow->metatitle);
                    if (!empty($sefRow->metadesc))   JoomSEF::set('sef.meta.desc', $sefRow->metadesc);
                    if (!empty($sefRow->metakey))    JoomSEF::set('sef.meta.key', $sefRow->metakey);
                    if (!empty($sefRow->metalang))   JoomSEF::set('sef.meta.lang', $sefRow->metalang);
                    if (!empty($sefRow->metarobots)) JoomSEF::set('sef.meta.robots', $sefRow->metarobots);
                    if (!empty($sefRow->metagoogle)) JoomSEF::set('sef.meta.google', $sefRow->metagoogle);
                    if (!empty($sefRow->canonicallink)) JoomSEF::set('sef.link.canonical', $sefRow->canonicallink);
                    if (isset($sefRow->showsitename))   JoomSEF::set('sef.meta.showsitename', $sefRow->showsitename);
                    if (!empty($sefRow->metacustom)) {
                        $metacustom = @unserialize($sefRow->metacustom);
                        if (!empty($metacustom)) {
                            JoomSEF::set('sef.meta.custom', $metacustom);
                        }
                    }
                    
                    // Update hits count if set to
                    if ($sefConfig->homePageHits) {
                        $db->setQuery("UPDATE #__sefurls SET cpt=(cpt+1) WHERE id = '{$sefRow->id}'");
                        $db->query();
                    }
                }
            }

            return $vars;
        }

        $disabled = false;
        $sef_ext = new SefExt();
        $newVars = $sef_ext->revert($route, $disabled);

        // We need to determine language BEFORE Joomla SEO
        // so the menu is translated correctly
        $lang = self::get('domain_lang');
        if (empty($lang)) {
            $lang = (isset($newVars['lang']) ? $newVars['lang'] : (isset($vars['lang']) ? $vars['lang'] : null));
        }
        else if ($sefConfig->wrongDomainHandling != _COM_SEF_WRONG_DOMAIN_DO_NOTHING) {
            // We have a language from domain, check if it corresponds to language in SEF URL
            if (isset($newVars['lang']) && ($newVars['lang'] != $lang)) {
                // Domain and SEF URL languages don't match
                if ($sefConfig->wrongDomainHandling == _COM_SEF_WRONG_DOMAIN_REDIRECT) {
                    // Redirect to correct domain
                    if (isset($sefConfig->subDomainsJoomla[$newVars['lang']])) {
                        $domain = $sefConfig->subDomainsJoomla[$newVars['lang']];
                        $redir = JURI::getInstance();
                        $redir->setHost($domain);
                        
                        // Redirect
                        SEFTools::redirect($redir->toString(), true);
                    }
                    
                    // No domain found, show 404
                    $disabled = true;
                }
                else {
                    // Show 404 page
                    $disabled = true;
                }
            }
        }

        if (!empty($newVars) && !empty($vars) && $sefConfig->nonSefQueryVariables) {
            // If this was SEF url, consider the vars in query as nonsef
            $nonsef = array_diff_key($vars, $newVars);
            if (!empty($nonsef)) {
                JoomSEF::set('sef.global.nonsefvars', $nonsef);
            }
        }

        // try to parse joomla native seo
        if ($sefConfig->parseJoomlaSEO && empty($newVars)) {
            $oldUrl = $oldUri->toString(array('path', 'query', 'fragment'));           
            $router = $mainframe->getRouter();
            $router->setMode(JROUTER_MODE_SEF);
            $jvars = $router->parse($oldUri);
            $router->setMode(JROUTER_MODE_DONT_PARSE);
            
            // Check 404
            if (isset($jvars['option']) && ($jvars['option'] == 'com_content') &&
                isset($jvars['view']) &&
                isset($jvars['id']) && ($jvars['id'] == 0))
            {
                // 404
                $jvars = array();
            }

            if (!empty($jvars['option']) || !empty($jvars['Itemid'])) {
                // Fix Itemid
                if (array_key_exists('Itemid', $jvars) && is_null($jvars['Itemid'])) {
                    unset($jvars['Itemid']);
                }

                // Try to get option from URL or menu item
                if (!isset($jvars['option'])) {
                    // Get the option from menu item
                    $menu = JFactory::getApplication()->getMenu('site');
                    $item =& $menu->getItem($jvars['Itemid']);

                    if (!is_null($item) && isset($item->query['option']))
                    {
                        $jopt = $item->query['option'];
                    }
                }
                else {
                    $jopt = $jvars['option'];
                }
                
                // Was it possible to retrieve component?
                if (isset($jopt)) {
                    // If the component is not handled by default Joomla router
                    // try to find corresponding SEF URL in JoomSEF's database
                    require_once(JPATH_ADMINISTRATOR.'/components/com_sef/models/extensions.php');
                    $handler = SEFModelExtensions::_getActiveHandler($jopt);
                    if (!in_array($handler->code, array(_COM_SEF_HANDLER_ROUTER, _COM_SEF_HANDLER_JOOMLA, _COM_SEF_HANDLER_NONE))) {
                        // Try to get the SEF URL
                        $oldDisable = $sefConfig->disableNewSEF;
                        $sefConfig->disableNewSEF = true;
        
                        $jUri = new JURI('index.php');
                        $jUri->setQuery($jvars);
                        if ($jUri->getVar('format') == 'html') {
                            $jUri->delVar('format');
                        }
                        $jUrl = $jUri->toString(array('path', 'query', 'fragment'));
                        $jSefUri = new JURI(JRoute::_($jUrl));
                        $jSef = $jSefUri->toString(array('path', 'query', 'fragment'));
                        //$jSef = urldecode(str_replace('&amp;', '&', $jSef));
                        
                        // Remove base
                        $base = JURI::base(true);
                        $baseLen = strlen($base);
                        if (substr($oldUrl, 0, $baseLen) == $base) {
                            $oldUrl = substr($oldUrl, $baseLen);
                        }
                        if (substr($jSef, 0, $baseLen) == $base) {
                            $jSef = substr($jSef, $baseLen);
                        }
                        
                        // Fix slashes - left
                        $oldUrl = ltrim($oldUrl, '/');
                        $jSef = ltrim($jSef, '/');
        
                        // Fix slashes - right
                        //$oldUrl = preg_replace('/^([^?]*)\/(\??)/', '$1$2', $oldUrl);
                        //$jSef = preg_replace('/^([^?]*)\/(\??)/', '$1$2', $jSef);
                        $oldUrl = JoomSEF::_removeRightSlash($oldUrl);
                        $jSef = JoomSEF::_removeRightSlash($jSef);
                        
                        // Restore the configuration
                        $sefConfig->disableNewSEF = $oldDisable;
        
                        if (count($_POST) == 0) {
                            // Non-SEF redirect
                            if ((strpos($jSef, 'index.php?') === false) && ($oldUrl != $jSef)) {
                                // Seems the URL is SEF, let's redirect
                                SEFTools::redirect(JURI::root() . $jSef, true);
                            }
                        }
                    }

                    // OK, we can show the page for this component
                    $newVars = $jvars;
                }
            }
        }

        if (!empty($vars)) {
            // append the original query string because some components
            // (like SMF Bridge and SOBI2) use it
            $vars = array_merge($vars, $newVars);
        } else {
            $vars = $newVars;
        }
        
        if (empty($newVars)==false && $disabled==false) {
            // Parsed correctly and enabled
            JoomSEF::_sendHeader('HTTP/1.0 200 OK');

            // Create automatic canonical link if set to and it is not already set
            $canonical = JoomSEF::get('sef.link.canonical');
            if ($generateCanonical && empty($canonical)) {
                $extAuto = 2;
                if (isset($vars['option'])) {
                    $params =& SEFTools::getExtParams($vars['option']);
                    $extAuto = $params->get('autoCanonical', 2);
                }
                $autoCanonical = ($extAuto == 2) ? $sefConfig->autoCanonical : $extAuto;

                if ($autoCanonical) {
                    JoomSEF::set('sef.link.canonical', JURI::root().$route);
                }
            }
        }
        else
        {
            // 2.8.2013 dajo: don't set nonsef vars, this causes duplicate SEF URLs to be created, for example
            //                when a search engine visits http://domain.com/non-existent-page?id=10
            //                (all menu links etc. have duplicates without the id variable created)
            // set nonsef vars
            //if (!$disabled) {
            //    JoomSEF::set('sef.global.nonsefvars', $vars);
            //}

            // bad URL, so check to see if we've seen it before
            // 404 recording (only if enabled)
            if ($sefConfig->record404) {
                $query = "SELECT * FROM `#__sefurls` WHERE `sefurl` = '".$route."'";
                $db->setQuery($query);
                $results = $db->loadObjectList();

                if ($results) {
                    // we have it, so update counter
                    $db->setQuery("UPDATE `#__sefurls` SET `cpt`=(`cpt`+1) WHERE `sefurl` = '".$route."'");
                    $db->query();
                }
                else {
                    // get trace info
                    if (@$sefConfig->trace) {
                        $traceinfo = $db->quote(JoomSEF::_getDebugInfo($sefConfig->traceLevel, true));
                    }
                    else $traceinfo = "NULL";

                    // record the bad URL
                    $query = "INSERT INTO `#__sefurls` (`cpt`, `sefurl`, `origurl`, `trace`, `dateadd`) "
                    . " VALUES ( '1', '$route', '', {$traceinfo}, CURDATE() )";
                    $db->setQuery($query);
                    $db->query();
                }
            }

            // redirect to the error page
            $vars = JoomSEF::_get404vars($route);
        }

        // Set QUERY_STRING if set to
        if ($sefConfig->setQueryString) {
            $qs = array();
            self::_buildQueryStringValues($qs, '', $vars);
            $qs = implode('&', $qs);
            if (!empty($qs)) {
                $_SERVER['QUERY_STRING'] = $qs;
            }
        }

        return $vars;
    }
    
    function _buildQueryStringValues(&$qs, $name, $val) {
        if (is_array($val)) {
            foreach ($val as $k => $v) {
                $newName = empty($name) ? $k : $name.'['.$k.']';
                self::_buildQueryStringValues($qs, $newName, $v);
            }
        }
        else {
            $qs[] = $name . '=' . urlencode($val);
        }
    }

    function _removeRightSlash($url)
    {
        if (strpos($url, '?') === false) {
            // No question mark
            return rtrim($url, '/');
        }
        
        // Check slash before question mark
        $qpos = strpos($url, '/?');
        if ($qpos !== false) {
            /*$spos = strpos($url, '/');
            if ($qpos <= $spos) {
                $url = substr($url, 0, $qpos).substr($url, $qpos+1);
            }*/
            $url = substr($url, 0, $qpos).substr($url, $qpos+1);
        }

        return $url;
    }

    function _get404vars($route = '')
    {
        $mainframe =& JFactory::getApplication();

        $db =& JFactory::getDBO();
        $sefConfig =& SEFConfig::getConfig();

        // you MUST create a static content page with the title 404 for this to work properly
        if ($sefConfig->showMessageOn404) {
            $mosmsg = 'FILE NOT FOUND: '.$route;
            $mainframe->enqueueMessage($mosmsg);
        }
        else $mosmsg = '';

        if ($sefConfig->page404 == _COM_SEF_404_DEFAULT) {
            $sql = 'SELECT `id`  FROM `#__content` WHERE `title`= "404"';
            $db->setQuery($sql);

            if (($id = $db->loadResult())) {
                $vars['option'] = 'com_content';
                $vars['view'] = 'article';
                $vars['id'] = $id;
            }
            else {
                die(JText::_('COM_SEF_ERROR_DEFAULT_404').$mosmsg."<br />URI:".htmlspecialchars($_SERVER['REQUEST_URI']));
            }
        }
        elseif ($sefConfig->page404 == _COM_SEF_404_FRONTPAGE) {
            $menu  = $mainframe->getMenu('site');
            //$item = $menu->getDefault();
            // Workaround until Joomla menu bug will be fixed
            $items=$menu->getItems(array('home','language'),array('1','*'));
            $item=$items[0];


            //Set the information in the frontpage request
            $vars = $item->query;

            //Get the itemid
            $vars['Itemid'] = $item->id;
            $menu->setActive($vars['Itemid']);
        }
        else {
            $id = $sefConfig->page404;
            $vars['option'] = 'com_content';
            $vars['view'] = 'article';
            $vars['id'] = $id;
        }

        // If custom Itemid set, use it
        if ($sefConfig->use404itemid) {
            $vars['Itemid'] = $sefConfig->itemid404;
        }

        JoomSEF::_sendHeader('HTTP/1.0 404 NOT FOUND');

        return $vars;
    }

    /**
     * Recursively sorts nested array by keys
     */
    function ksort_deep(&$arr) {
        ksort($arr);
        foreach ($arr as &$a) {
            if (is_array($a) && !empty($a)) {
                self::ksort_deep($a);
            }
        }
    }

    function _parseAlias($route, &$vars)
    {
        $db =& JFactory::getDBO();
        $sefConfig =& SEFConfig::getConfig();

        $route = html_entity_decode(urldecode($route));

        // Get all the corresponding aliases
        $query = "SELECT `a`.`vars`, `u`.`sefurl` FROM `#__sefaliases` AS `a` INNER JOIN `#__sefurls` AS `u` ON `u`.`id` = `a`.`url` WHERE `a`.`alias` = " . $db->Quote($route);
        if ($route == '') {
            $query .= " OR `a`.`alias` = 'index.php'";
        }
        $db->setQuery($query);
        $aliases = $db->loadObjectList();

        // Are there any aliases?
        if (!is_array($aliases) || (count($aliases) == 0)) {
            return;
        }
        
        // Sort variables by keys
        self::ksort_deep($vars);

        // Try to find alias with corresponding variables
        foreach ($aliases as $alias) {
            // Create the array of alias variables
            $avars = array();
            $alias->vars = trim($alias->vars);
            if (!empty($alias->vars)) {
                $tmpvars = str_replace("\n", '&', $alias->vars);
                parse_str($tmpvars, $avars);
            }

            // Sort alias variables by keys
            self::ksort_deep($avars);
            
            // Compare variables arrays
            if ($vars !== $avars) {
                continue;
            }

            // Correct alias found, redirect
            $url = JURI::root();
            if (substr($url, -1) != '/') {
                $url .= '/';
            }
            $url .= ltrim($alias->sefurl, '/');
            SEFTools::redirect($url, true);
        }
    }

    function _sendHeader($header)
    {
        if (!headers_sent()) {
            //file_put_contents(JPATH_SITE.'/tmp/header',$header."\n",FILE_APPEND);
            header($header);
        }
        // 25.4.2012 dajo: Don't die when headers already sent
        // else {
        //    JoomSEF::_headers_sent_error($f, $l, __FILE__, __LINE__);
        //}
    }

    function _parseRawRoute(&$uri)
    {
        $sefConfig =& SEFConfig::getConfig();

        // Make sure that Itemid is numeric
        $Itemid = $uri->getVar('Itemid');
        if (!empty($Itemid)) {
            $uri->setVar('Itemid', intval($Itemid));
        }

        // Redirect to correct language if set to
        if ($sefConfig->langEnable && ($sefConfig->mismatchedLangHandling == _COM_SEF_MISMATCHED_LANG_FIX) && (count($_POST) == 0)) {
            $langVar = $uri->getVar('lang');
            $itemidVar = $uri->getVar('Itemid');
            if (!empty($langVar) && !empty($itemidVar)) {
                // Get menu item language
                $menu = JFactory::getApplication()->getMenu('site');
                $item = $menu->getItem($itemidVar);
                if (is_object($item) && !empty($item->language) && ($item->language != '*')) {
                    $langsCode = JLanguageHelper::getLanguages('lang_code');
                    if ($langsCode[$item->language]->sef != $langVar) {
                        // Redirect to correct language
                        $curUri = JURI::getInstance();
                        $curUri->setVar('lang', $langsCode[$item->language]->sef);
                        SEFTools::redirect($curUri->toString(), true);
                    }
                }
            }
        }
        
        if( is_null($uri->getVar('option')) ) {
            // Set the URI from Itemid
            $menu = JFactory::getApplication()->getMenu('site');
            $item = $menu->getItem($uri->getVar('Itemid'));
            if( !is_null($item) ) {
                $uri->setQuery($item->query);
                $uri->setVar('Itemid', $item->id);
            }
        }


        $extAuto = 2;
        if (isset($params)) {
            $extAuto = $params->get('autoCanonical', 2);
        }
        $autoCanonical = ($extAuto == 2) ? $sefConfig->autoCanonical : $extAuto;

        if (($sefConfig->nonSefRedirect && (count($_POST) == 0)) || $autoCanonical)
        {
            // Try to find the non-SEF URL in the database - don't create new!
            $oldDisable = $sefConfig->disableNewSEF;
            $sefConfig->disableNewSEF = true;
            
            $uri->setPath('index.php');
            $url = $uri->toString(array('path', 'query', 'fragment'));
            $sef = JRoute::_($url);
            
            // Revert, otherwise Joomla in its router thinks this is SEF URL,
            // because its path is not empty!
            $uri->setPath('');

            // Restore the configuration
            $sefConfig->disableNewSEF = $oldDisable;

            if ($sefConfig->nonSefRedirect && (count($_POST) == 0)) {
                // Non-SEF redirect
                if( strpos($sef, 'index.php?') === false ) {
                    // Check if it's different from current URL
                    $curUri = JURI::getInstance();
                    if ($sef[0] == '/') {
                        $curUrl = $curUri->toString(array('path', 'query', 'fragment'));
                    }
                    else {
                        $curUrl = JoomSefUri::getUri($curUri);
                    }
                    
                    // Fix the &amp; characters
                    $sef = str_replace('&amp;', '&', $sef);
                    
                    if ($sef != $curUrl) {
                        // Seems the URL is SEF, let's redirect
                        SEFTools::redirect($sef, true);
                    }
                }
            }
            else if ($autoCanonical) {
                // Only set canonical URL
                $mainframe =& JFactory::getApplication();

                // Remove the query part from SEF URL
                $pos = strpos($sef, '?');
                if ($pos !== false) {
                    $sef = substr($sef, 0, $pos);
                }

                JoomSEF::set('sef.link.canonical', $sef);
            }
        }

        return $uri->getQuery(true);
    }

    // 25.4.2012 dajo: removed
    //function _headers_sent_error($sentFile, $sentLine, $file, $line)
    //{
    //    die("<br />Error: headers already sent in ".basename($sentFile)." on line $sentLine.<br />Stopped at line ".$line." in ".basename($file));
    //}

    function _createUri($uri)
    {
        $url = JURI::root();
        $path=JURI::root(true);
        if( substr($url, -1) != '/' ) {
            $url .= '/';
        }
        $url .= $uri->toString(array('path', 'query', 'fragment'));
        JoomSefUri::updateUri($uri, $url);
        $path=str_replace($path,"",$uri->getPath());
        $uri->setPath($path);

        return $uri;
    }

    function _endSef($lang = '')
    {
        $mainframe = JFactory::getApplication();

        $mainframe->triggerEvent('onSefEnd');
        JoomSEF::_restoreLang($lang);
    }

    function _restoreLang($lang = '')
    {
        if ($lang != '') {
            if ($lang != JoomSEF::getLangCode()) {
                $language = JFactory::getLanguage();
                $language->setLanguage($lang);
                
                // 6.12.2012 dajo: Make sure that the language gets loaded and overwrites current strings!
                $language->load('joomla', JPATH_BASE, null, true);
            }
        }
    }

    function _isHomePage(&$uri, $altered = false)
    {
        $home_items=SEFTools::getHomeQueries();
        $langs=JLanguageHelper::getLanguages('lang_code');
        $config=SEFConfig::getConfig();
        
        $Itemid = $uri->getVar('Itemid');
        if(array_key_exists($Itemid,$home_items) && $uri->getPath()=='index.php') {
            if($config->langEnable) {
                if (strlen($uri->getVar('lang', '')) == 0) {
                    $langTag = $home_items[$Itemid]->language;
                    if (isset($langs[$langTag])) {
                        $uri->setVar('lang', $langs[$langTag]->sef);
                    }
                    else {
                        // Use current language
                        $langTag = JFactory::getLanguage()->getTag();
                        if (isset($langs[$langTag])) {
                            $uri->setVar('lang', $langs[$langTag]->sef);
                        }
                    }
                }
            }
            
            // Set the link queries if not already there
            if (!isset($home_items[$Itemid]->linkQuery)) {
                $link = new JURI($home_items[$Itemid]->link);
                $home_items[$Itemid]->linkQuery = $link->getQuery(true);
                $home_items[$Itemid]->normalizedQuery = null;
                
                // Normalize query if an extension is available
                $option = $link->getVar('option');
                if (!is_null($option)) {
                    $extFile = JPATH_ROOT.'/components/com_sef/sef_ext/'.$option.'.php';
                    if (file_exists($extFile)) {
                        $class = 'SefExt_'.$option;
                        
                        if (!class_exists($class)) {
                            require($extFile);
                        }
                        $sef_ext = new $class();
                        $link->setVar('Itemid', $Itemid);
                        $sef_ext->beforeCreate($link);
                        $link->delVar('Itemid');
                        $link->delVar('lang');
                        $home_items[$Itemid]->normalizedQuery = $link->getQuery(true);
                        $sef_ext = null;
                    }
                }
            }
            
            // The queries need to match 1:1 (except Itemid and lang(?)), not just the variables present in home item!
            $uriQuery = $uri->getQuery(true);
            if (array_key_exists('Itemid', $uriQuery)) unset($uriQuery['Itemid']);
            if (array_key_exists('lang', $uriQuery)) unset($uriQuery['lang']);
            
            // Check base link
            $same = ($uriQuery == $home_items[$Itemid]->linkQuery);
            if (!$same && is_array($home_items[$Itemid]->normalizedQuery)) {
                // Check normalized link
                $same = ($uriQuery == $home_items[$Itemid]->normalizedQuery);
            }
            
            return $same;
        }
        
        return false;
    }

    function _getMenuTitle($option, $lang, $id = null, $string = null)
    {
        return self::_getMenuTitleLang($option, $lang, $id, $string);
    }
    
    function _getMenuTitleLang($option, &$lang, $id = null, $string = null)
    {
        static $stringCache = array();
        static $idCache = array();
        static $optionCache = array();
        
        $db = JFactory::getDBO();
        $sefConfig = SEFConfig::getConfig();
        
        if ($title = JoomSEF::_getCustomMenuTitle($option)) {
            return $title;
        }
        
        // Which column to use?
        $column = 'title';
        if ($sefConfig->useAlias) {
            $column = 'alias';
        }
        
        // Translate URLs?
        if ($sefConfig->translateItems) {
            $jfTranslate = '`id`, ';
        }
        else {
            $jfTranslate = '';
        }
        
        // Try to load cached data
        $row = false;
        if (isset($string)) {
            // Search by specific link
            if (!isset($stringCache[$string])) {
                $query = "SELECT {$jfTranslate}`$column` AS `name`, `language` FROM `#__menu` WHERE `link` = '$string' AND `published` > 0";
                $db->setQuery($query);
                $stringCache[$string] = $db->loadObject();
                if (!$stringCache[$string])
                    $stringCache[$string] = false;
            }
            
            $row = $stringCache[$string];
        }
        elseif (isset($id) && $id != 0) {
            // Search by Itemid (most used)
            if (!isset($idCache[$id])) {
                $query = "SELECT {$jfTranslate}`$column` AS `name`, `language` FROM `#__menu` WHERE `id` = '$id' AND `published` > 0";
                $db->setQuery($query);
                $idCache[$id] = $db->loadObject();
                if (!$idCache[$id])
                    $idCache[$id] = false;
            }
            
            $row = $idCache[$id];
        }
        else {
            // Search by option
            if (!isset($optionCache[$option])) {
                $query = "SELECT {$jfTranslate}`$column` AS `name`, `language` FROM `#__menu` WHERE `link` = 'index.php?option=$option' AND `published` > 0";
                $db->setQuery($query);
                $optionCache[$option] = $db->loadObject();
                if (!$optionCache[$option]) {
                    // Try to extend the search for any link to component
                    $query = "SELECT {$jfTranslate}`$column` AS `name`, `language` FROM `#__menu` WHERE `link` LIKE 'index.php?option=$option%' AND `published` > 0";
                    $db->setQuery($query);
                    $optionCache[$option] = $db->loadObject();
                    if (!$optionCache[$option])
                        $optionCache[$option] = false;
                }
            }
            
            $row = $optionCache[$option];
        }
        
        if ($row && !empty($row->name)) {
            $title = $row->name;
            $lang = $row->language;
        }
        else {
            $title = str_replace('com_', '', $option);
        }
        
        return $title;
    }

    function _getMenuItemInfo($option, $task, $id = null, $string = null)
    {
        static $stringCache = array();
        static $idCache = array();
        static $optionCache = array();
        
        $db = JFactory::getDBO();
        $sefConfig = SEFConfig::getConfig();

        // JF translate extension.
        $jfTranslate = $sefConfig->translateNames ? ', `id`' : '';

        $item = new stdClass();
        $item->title = JoomSEF::_getCustomMenuTitle($option);

        // Which column to use?
        $column = 'title';
        if ($sefConfig->useAlias) $column = 'alias';

        // Try to load data from cache
        $row = false;
        if (isset($id) && $id != 0) {
            if (!isset($idCache[$id])) {
                $query = "SELECT `$column` AS `name`, `params`$jfTranslate FROM `#__menu` WHERE `id` = $id AND `published` > 0";
                $db->setQuery($query);
                $idCache[$id] = $db->loadObject();
                if (!$idCache[$id])
                    $idCache[$id] = false;
            }
            
            $row = $idCache[$id];
        }
        elseif (isset($string)) {
            if (!isset($stringCache[$string])) {
                $query = "SELECT `$column`AS `name`, `params` $jfTranslate FROM `#__menu` WHERE `link` = '$string' AND `published` > 0";
                $db->setQuery($query);
                $stringCache[$string] = $db->loadObject();
                if (!$stringCache[$string])
                    $stringCache[$string] = false;
            }
            
            $row = $stringCache[$string];
        }
        else {
            if (!isset($optionCache[$option])) {
                // Search for direct link to component only
                $query = "SELECT `$column` AS `name`, `params` $jfTranslate FROM `#__menu` WHERE `link` = 'index.php?option=$option' AND `published` > 0";
                $db->setQuery($query);
                $optionCache[$option] = $db->loadObject();
                if (!$optionCache[$option]) {
                    // Try to extend the search for any link to component
                    $query = "SELECT `$column` AS `name`, `params` $jfTranslate FROM `#__menu` WHERE `link` LIKE 'index.php?option=$option%' AND `published` > 0";
                    $db->setQuery($query);
                    $optionCache[$option] = $db->loadObject();
                    if (!$optionCache[$option])
                        $optionCache[$option] = false;
                }
            }
            
            $row = $optionCache[$option];
        }
        
        if (!empty($row)) {
            if (!empty($row->name) && !$item->title) $item->title = $row->name;
            $item->params = new JRegistry($row->params);
        }
        else {
            $item->title = str_replace('com_', '', $option);
        }
        
        return $item;
    }

    function _getCustomMenuTitle($option)
    {
        $db =& JFactory::getDBO();
        $sefConfig =& SEFConfig::getConfig();
        $lang=JFactory::getConfig()->get('language');
        $element=str_replace('com_','ext_joomsef4_',$option);

        static $titles;

        $jfTranslate = $sefConfig->translateNames ? ', `id`' : '';

        if( !isset($titles) ) {
            $titles = array();
        }

        if( !isset($titles[$lang]) ) {
            $titles[$lang] = array();
            
            $query=$db->getQuery(true);
            $query->select('params, element')->from('#__extensions')->where('state>=0')->where('enabled=1')->where('type='.$db->quote('sef_ext'));
            $db->setQuery($query);
            $data = $db->loadObjectList();
            
            foreach ($data as $val) {
                $params = new JRegistry($val->params);
                $titles[$lang][$val->element] = $params->get('customMenuTitle');
            }
        }
        
        if (isset($titles[$lang][$element])) {
            return $titles[$lang][$element];
        }
        
        return null;
    }

    /**
     * Convert title to URL name.
     *
     * @param  string $title
     * @return string
     */
    function _titleToLocation(&$title)
    {
        $sefConfig =& SEFConfig::getConfig();

        // remove accented characters
        // $title = strtr($title,
        // replace non-ASCII characters.
        $title = strtr($title, $sefConfig->getReplacements());

        // remove quotes, spaces, and other illegal characters
        if( $sefConfig->allowUTF ) {
            $title = preg_replace(array('/\'/', '/[\s"\?\:\/\\\\]/', '/(^_|_$)/'), array('', $sefConfig->replacement, ''), $title);
        }
        else {
            $title = preg_replace(array('/\'/', '/[^a-zA-Z0-9\-!.,+]+/', '/(^_|_$)/'), array('', $sefConfig->replacement, ''), $title);
        }

        // Handling lower case
        if( $sefConfig->lowerCase ) {
            $title = JoomSEF::_toLowerCase($title);
        }

        return $title;
    }

    /**
     * Tries to correctly handle conversion to lowercase even for UTF-8 string
     *
     * @param unknown_type $str
     */
    function _toLowerCase($str)
    {
        $sefConfig =& SEFConfig::getConfig();

        if( $sefConfig->allowUTF ) {
            if( function_exists('mb_convert_case') ) {
                $str = mb_convert_case($str, MB_CASE_LOWER, 'UTF-8');
            }
        }
        else {
            $str = strtolower($str);
        }

        return $str;
    }

    function _utf8LowerCase($str)
    {
        if( function_exists('mb_convert_case') ) {
            $str = mb_convert_case($str, MB_CASE_LOWER, 'UTF-8');
        }
        else {
            $str = strtolower($str);
        }

        return $str;
    }

    /**
     * Stores the given parameters in an array and returns it
     *
     * @param JURI $uri
     * @param array $title
     * @param string $task
     * @param int $limit
     * @param int $limitstart
     * @param string $lang
     * @param array $nonSefVars
     * @param array $ignoreSefVars
     * @param array $metadata List of metadata to be stored. (metakeywords, metadesc, ..., canonicallink)
     * @param boolean $priority
     * @param boolean $pageHandled Set to true if the extension handles its pagination on its own
     * @return string
     */
    function _sefGetLocation(&$uri, &$title, $task = null, $limit = null, $limitstart = null, $lang = null, $nonSefVars = null, $ignoreSefVars = null, $metadata = null, $priority = null, $pageHandled = false,$host=null, $sitemapParams = null)
    {
        $data = compact('uri', 'title', 'task', 'limit', 'limitstart', 'lang', 'nonSefVars', 'ignoreSefVars', 'metadata', 'priority', 'pageHandled', 'host', 'sitemapParams');
        return $data;
    }

    /**
     * Find existing or create new SEO URL.
     *
     * @param array $data
     * @return string
     */
    function _storeLocation(&$data, $check = false, $removeItemid = false)
    {
        $mainframe =& JFactory::getApplication();

        $db =& JFactory::getDBO();
        $sefConfig =& SEFConfig::getConfig();
        $cache =& SEFCache::getInstance();
        
        // Extract variables
        $defaults = array('uri' => null, 'title' => null, 'task' => null, 'limit' => null, 'limitstart' => null, 'lang' => null, 'nonSefVars' => null, 'ignoreSefVars' => null, 'metadata' => null, 'priority' => null, 'pageHandled' => false,'host'=>false, 'sitemapParams' => null);
        foreach ($defaults as $varName => $value) {
            if (is_array($data) && isset($data[$varName])) {
                $$varName = $data[$varName];
            }
            else {
                $$varName = $value;
            }
        }
        
        // Original object is stored in origUri
        $origUri = $uri;
        $uri = clone($origUri);
        
        // Get the default priority if not set
        if( is_null($priority) ) {
            $priority = JoomSEF::_getPriorityDefault($uri);
        }

        // Get the parameters for this component
        if( !is_null($uri->getVar('option')) ) {
            $params =& SEFTools::getExtParams($uri->getVar('option'));
        }

        // remove the menu title if set to for this component
        if( isset($params) && ($params->get('showMenuTitle', '1') == '0') ) {
            if ((count($title) > 1) &&
            ((count($title) != 2) || ($title[1] != '/')) &&
            ($title[0] == JoomSEF::_getMenuTitle(@$uri->getVar('option'), null, @$uri->getVar('Itemid')))) {
                array_shift($title);
            }
        }
        
        // remove the Itemid if set to
        if ($removeItemid) {
            $uri->delVar('Itemid');
        }

        // add the page number if the extension does not handle it
        if( !$pageHandled && !is_null($uri->getVar('limitstart')) ) {
            $limit = $uri->getVar('limit');
            if( is_null($limit) ) {
                if( !is_null($uri->getVar('option')) ) {
                    $limit = intval($params->get('pageLimit', ''));
                    if( $limit == 0 ) {
                        $limit = 5;
                    }
                }
                else {
                    $limit = 5;
                }
            }
            $pageNum = intval($uri->getVar('limitstart') / $limit) + 1;
            $pagetext = strval($pageNum);
            if (($cnfPageText = $sefConfig->getPageText())) {
                $pagetext = str_replace('%s', $pageNum, $cnfPageText);
            }
            $title[] = $pagetext;
        }

        // get all the titles ready for urls.
        $location = array();
        foreach ($title as $titlePart) {
            if (strlen($titlePart) == 0) continue;
            $location[] = JoomSEF::_titleToLocation($titlePart);
        }

        // remove unwanted characters.
        $finalstrip = explode('|', $sefConfig->stripthese);
        $takethese = str_replace('|', '', $sefConfig->friendlytrim);
        if (strstr($takethese, $sefConfig->replacement) === FALSE) {
            $takethese .= $sefConfig->replacement;
        }

        $imptrim = implode('/', $location);

        if (!is_null($task)) {
            $task = str_replace($sefConfig->replacement.'-'.$sefConfig->replacement, $sefConfig->replacement, $task);
            $task = str_replace($finalstrip, '', $task);
            $task = trim($task,$takethese);
        }

        $imptrim = str_replace($sefConfig->replacement.'-'.$sefConfig->replacement, $sefConfig->replacement, $imptrim);
        $suffixthere = 0;
        $regexSuffix = str_replace('.', '\.', $sefConfig->suffix);
        $pregSuffix = addcslashes($regexSuffix, '/');
        //if (eregi($regexSuffix.'$', $imptrim)) {
        if (preg_match('/'.$pregSuffix.'$/i', $imptrim)) {
            $suffixthere = strlen($sefConfig->suffix);
        }

        $imptrim = str_replace($finalstrip, $sefConfig->replacement, substr($imptrim, 0, strlen($imptrim) - $suffixthere));
        $imptrim = str_replace($sefConfig->replacement.$sefConfig->replacement, $sefConfig->replacement, $imptrim);

        $suffixthere = 0;
        //if (eregi($regexSuffix.'$', $imptrim)) {
        if (preg_match('/'.$pregSuffix.'$/i', $imptrim)) {
            $suffixthere = strlen($sefConfig->suffix);
        }

        $imptrim = trim(substr($imptrim, 0, strlen($imptrim) - $suffixthere), $takethese);

        // add the task if set
        $imptrim .= (!is_null($task) ? '/'.$task.$sefConfig->suffix : '');

        // remove all the -/
        $imptrim = SEFTools::ReplaceAll($sefConfig->replacement.'/', '/', $imptrim);

        // remove all the /-
        $imptrim = SEFTools::ReplaceAll('/'.$sefConfig->replacement, '/', $imptrim);

        // Remove all the //
        $location = SEFTools::ReplaceAll('//', '/', $imptrim);

        // check if the location isn't too long for database storage and truncate it in that case
        $suffixthere = 0;
        //if (eregi($regexSuffix.'$', $location)) {
        if (preg_match('/'.$pregSuffix.'$/i', $location)) {
            $suffixthere = strlen($sefConfig->suffix);
        }
        $suffixLen = strlen($sefConfig->suffix);
        $maxlen = 240 + $suffixthere - $suffixLen;  // Leave some space for language and numbers
        if (strlen($location) > $maxlen) {
            // Temporarily remove the suffix
            //$location = ereg_replace($regexSuffix.'$', '', $location);
            $location = preg_replace('/'.$pregSuffix.'$/', '', $location);

            // Explode the location to parts
            $parts = explode('/', $location);
            do {
                // Find the key of the longest part
                $key = 0;
                $len = strlen($parts[0]);
                for( $i = 1, $n = count($parts); $i < $n; $i++ ) {
                    $tmpLen = strlen($parts[$i]);
                    if( $tmpLen > $len ) {
                        $key = $i;
                        $len = $tmpLen;
                    }
                }

                // Truncate the longest part
                $truncBy = strlen($location) - $maxlen;
                if( $truncBy > 10 ) {
                    $truncBy = 10;
                }
                $parts[$key] = substr($parts[$key], 0, -$truncBy);

                // Implode to location again
                $location = implode('/', $parts);

                // Add suffix if was there
                if( $suffixthere > 0 ) {
                    $location .= $sefConfig->suffix;
                }
            } while(strlen($location) > $maxlen);
        }

        // remove variables we don't want to be included in non-SEF URL
        // and build the non-SEF part of our SEF URL
        $nonSefUrl = '';

        // load the nonSEF vars from option parameters
        $paramNonSef = array();
        if( isset($params) ) {
            $nsef = $params->get('customNonSef', '');

            if( !empty($nsef) ) {
                // Some variables are set, let's explode them
                $paramNonSef = explode(';', $nsef);
            }
        }

        // get globally configured nonSEF vars
        $configNonSef = array();
        if( !empty($sefConfig->customNonSef) ) {
            $configNonSef = explode(';', $sefConfig->customNonSef);
        }


        // combine all the nonSEF vars arrays
        $nsefvars = array_merge($paramNonSef, $configNonSef);
        if (!empty($nsefvars)) {
            foreach($nsefvars as $nsefvar) {
                // add each variable, that isn't already set, and that is present in our URL
                if( !isset($nonSefVars[$nsefvar]) && !is_null($uri->getVar($nsefvar)) ) {
                    $nonSefVars[$nsefvar] = $uri->getVar($nsefvar);
                }
            }
        }

        // nonSefVars - variables to exclude only if set to in configuration
        if ($sefConfig->appendNonSef && isset($nonSefVars)) {
            $vars = array_keys($nonSefVars);
            $q = SEFTools::RemoveVariables($uri, $vars);
            if ($q != '') {
                if ($nonSefUrl == '') {
                    $nonSefUrl = '?'.$q;
                }
                else {
                    $nonSefUrl .= '&amp;'.$q;
                }
            }
            // if $nonSefVars mixes with $GLOBALS['JOOMSEF_NONSEFVARS'], exclude the mixed vars
            // this is important to prevent duplicating params by adding JOOMSEF_NONSEFVARS to
            // $ignoreSefVars
            $gNonSef = JoomSEF::get('sef.global.nonsefvars');
            if (!empty($gNonSef)) {
                foreach (array_keys($gNonSef) as $key) {
                    if (in_array($key, array_keys($nonSefVars))) unset($gNonSef[$key]);
                }
                JoomSEF::set('sef.global.nonsefvars', $gNonSef);
            }
        }

        // if there are global variables to exclude, add them to ignoreSefVars array
        $gNonSef = JoomSEF::get('sef.global.nonsefvars');
        if (!empty($gNonSef)) {
            if (!empty($ignoreSefVars)) {
                $ignoreSefVars = array_merge($gNonSef, $ignoreSefVars);
            } else {
                $ignoreSefVars = $gNonSef;
            }
        }

        // ignoreSefVars - variables to exclude allways
        if (isset($ignoreSefVars)) {
            $vars = array_keys($ignoreSefVars);
            $q = SEFTools::RemoveVariables($uri, $vars);
            if ($q != '') {
                if ($nonSefUrl == '') {
                    $nonSefUrl = '?'.$q;
                }
                else {
                    $nonSefUrl .= '&amp;'.$q;
                }
            }
        }

        // If the component requests strict accept variables filtering, remove the ones that don't match
        if( isset($params) && ($params->get('acceptStrict', '0') == '1') ) {
            $acceptVars =& SEFTools::getExtAcceptVars($uri->getVar('option'));
            $uriVars = $uri->getQuery(true);
            if( (count($acceptVars) > 0) && (count($uriVars) > 0) ) {
                foreach($uriVars as $name => $value) {
                    // Standard Joomla variables
                    if (in_array($name, $sefConfig->globalAcceptVars)) {
                        continue;
                    }
                    // Accepted variables
                    if( in_array($name, $acceptVars) ) {
                        continue;
                    }

                    // Variable not accepted, add it to non-SEF part of the URL
                    $value = urlencode($value);
                    if (strlen($nonSefUrl) > 0) {
                        $nonSefUrl .= '&amp;'.$name.'='.$value;
                    } else {
                        $nonSefUrl = '?'.$name.'='.$value;
                    }
                    $uri->delVar($name);
                }
            }
        }

        // always remove Itemid and store it in a separate column
        if (!is_null($uri->getVar('Itemid'))) {
            $Itemid = $uri->getVar('Itemid');
            $uri->delVar('Itemid');
        }

        // check for non-sef url first and avoid repeative lookups
        // we only want to look for title variations when adding new
        // this should also help eliminate duplicates.

        // David (284): ignore Itemid if set to
        if( isset($params) ) {
            $extIgnore = $params->get('ignoreSource', 2);
        } else {
            $extIgnore = 2;
        }
        $ignoreSource = ($extIgnore == 2 ? $sefConfig->ignoreSource : $extIgnore);

        // If Itemid is set as ignored for the component, set ignoreSource to 1
        $itemidIgnored = false;
        if (isset($Itemid) && !is_null($uri->getVar('option'))) {
            $itemidIgnored = SEFTools::isItemidIgnored($uri->getVar('option'), $Itemid);
            if ($itemidIgnored) {
                $ignoreSource = 1;
            }
        }

        $where = '';
        if (!$ignoreSource && isset($Itemid)) {
            $where .= " AND (`Itemid` = '".$Itemid."' OR `Itemid` IS NULL)";
        }
        $url = JoomSEF::_uriToUrl($uri);

        // if cache is activated, search in cache first
        $realloc = false;
        if ($sefConfig->useCache) {
            if(!$check) {                
                $realloc = $cache->GetSefUrl($url, @$Itemid);
            }
        }
        // search if URL exists, if we do not use cache or URL was not cached
        if (!$sefConfig->useCache || !$realloc) {
            $query = "SELECT * FROM `#__sefurls` WHERE `origurl` = '" . addslashes(html_entity_decode(urldecode($url))) . "'" . $where . ' LIMIT 2';
            $db->setQuery($query);
            $sefurls = $db->loadObjectList('Itemid');

            if (!$ignoreSource && isset($Itemid)) {
                if (isset($sefurls[$Itemid])) {
                    $realloc = $sefurls[$Itemid];
                }
                else if (isset($sefurls[''])) {
                    
                    // We've found one of the ignored Itemids, update it with the current and return
                    $realloc = $sefurls[''];
                    $realloc->Itemid = $Itemid;
                    $query = "UPDATE `#__sefurls` SET `Itemid` = '{$Itemid}' WHERE `id` = '{$realloc->id}' LIMIT 1";
                    $db->setQuery($query);
                    $db->query();
                }
                else {
                    $realloc = reset($sefurls);
                }
            }
            else {
                $realloc = reset($sefurls);
            }
            /*
            // removed - causing problems, ignore multiple sources not working correctly
            // test if current Itemid record exists, if YES, use it, if NO, use first found
            $curId = isset($Itemid) ? $Itemid : '';
            $active = isset($sefurls[$curId]) ? $sefurls[$curId] : reset($sefurls);
            $realloc = $active;
            */
        }
        // if not found, try to find the url without lang variable     
        if (!$realloc && ($sefConfig->langPlacement == _COM_SEF_LANG_DOMAIN)) {
            $url = JoomSEF::_uriToUrl($uri, 'lang');

            if ($sefConfig->useCache) {
                $realloc = $cache->GetSefUrl($url, @$Itemid);
            }
            if (!$sefConfig->useCache || !$realloc) {
                $query = "SELECT * FROM `#__sefurls` WHERE `origurl` = '".addslashes(html_entity_decode(urldecode($url)))."'" . $where . ' LIMIT 2';
                $db->setQuery($query);
                $sefurls = $db->loadObjectList('Itemid');

                if (!$ignoreSource && isset($Itemid)) {
                    if (isset($sefurls[$Itemid])) {
                        $realloc = $sefurls[$Itemid];
                    }
                    else if (isset($sefurls[''])) {
                        // We've found one of the ignored Itemids, update it with the current and return
                        $realloc = $sefurls[''];
                        $realloc->Itemid = $Itemid;
                        $query = "UPDATE `#__sefurls` SET `Itemid` = '{$Itemid}' WHERE `id` = '{$realloc->id}' LIMIT 1";
                        $db->setQuery($query);
                        $db->query();
                    }
                    else {
                        $realloc = reset($sefurls);
                    }
                }
                else {
                    $realloc = reset($sefurls);
                }
                /*
                // removed - causing problems, ignore multiple sources not working correctly
                   // test if current Itemid record exists, if YES, use it, if NO, use first found
                   $curId = isset($Itemid) ? $Itemid : '';
                $active = isset($sefurls[$curId]) ? $sefurls[$curId] : reset($sefurls);
                $realloc = $active;
                */
            }
        }

        // found a match, so we are done
        if (is_object($realloc) && !$check) {
            // return the original URL if SEF is disabled
            if (!$realloc->sef) {
                return $origUri;
            }

            // return found URL with non-SEF part appended
            if (($nonSefUrl != '') && (strstr($realloc->sefurl, '?'))) {
                $nonSefUrl = str_replace('?', '&amp;', $nonSefUrl);
            }

            if(!strlen($host)) {
                $root=JFactory::getURI()->getHost();
            } else {
                $root=$host;
            }
            $url=JFactory::getURI()->getScheme()."://".$root;

            if (substr($url, -1) != '/') $url .= '/';
            $url .= $realloc->sefurl.$nonSefUrl;
            $fragment = $uri->getFragment();
            if (!empty($fragment)) $url .= '#'.$fragment;

            JoomSefUri::updateUri($origUri, $url);
            return $origUri;
        }
        // URL not found, so lets create it
        else if(!is_object($realloc)||$check) {
            // return the original URL if we don't want to save new URLs
            if ($sefConfig->disableNewSEF) return $origUri;

            $realloc = null;

            $suffixMust = false;
            if (!isset($suffix)) {
                $suffix = $sefConfig->suffix;
            }

            $addFile = $sefConfig->addFile;
            if (($pos = strrpos($addFile, '.')) !== false) {
                $addFile = substr($addFile, 0, $pos);
            }

            // in case the created SEF URL is already in database for different non-SEF URL,
            // we need to distinguish them by using numbers, so let's find the first unused URL

            $leftPart = '';   // string to be searched before page number
            $rightPart = '';  // string to be searched after page number
            if (substr($location, -1) == '/' || strlen($location) == 0) {
                if (($pagetext = $sefConfig->getPageText())) {
                    // use global limit if NULL and set in globals
                    if (is_null($limit) && isset($_REQUEST['limit']) && $_REQUEST['limit'] > 0) $limit = $_REQUEST['limit'];
                    // if we are using pagination, try to calculate page number
                    if (!is_null($limitstart) && $limitstart > 0) {
                        // make sure limit is not 0
                        if ($limit == 0) {
                            $config =& JFactory::getConfig();
                            $listLimit = $config->get('list_limit');
                            $limit = ($listLimit > 0) ? $listLimit : 20;
                        }
                        $pagenum = $limitstart / $limit;
                        $pagenum++;
                    }
                    else $pagenum = 1;

                    if (strpos($pagetext, '%s') !== false) {
                        $page = str_replace('%s', $pagenum == 1 ? $addFile : $pagenum, $pagetext) . $suffix;

                        $pages = explode('%s', $pagetext);
                        $leftPart = $location . $pages[0];
                        $rightPart = $pages[1] . $suffix;
                    }
                    else {
                        $page = $pagetext.($pagenum == 1 ? $addFile : $sefConfig->pagerep . $pagenum) . $suffix;

                        $leftPart = $location . $pagetext . $sefConfig->pagerep;
                        $rightPart = $suffix;
                    }

                    $temploc = $location . ($pagenum == 1 && !$suffixMust ? '' : $page);
                }
                else {
                    $temploc = $location . ($suffixMust ? $sefConfig->pagerep.$suffix : '');

                    $leftPart = $location . $sefConfig->pagerep;
                    $rightPart = $suffix;
                }
            }
            elseif ($suffix) {
                if ($sefConfig->suffix != '/') {
                    //if (eregi($regexSuffix, $location)) {
                    if (preg_match('/'.$pregSuffix.'/i', $location)) {
                        $temploc = preg_replace('/' . $pregSuffix . '/', '', $location) . $suffix;

                        $leftPart = preg_replace('/' . $pregSuffix . '/', '', $location) . $sefConfig->pagerep;
                        $rightPart = $suffix;
                    }
                    else {
                        $temploc = $location . $suffix;

                        $leftPart = $location . $sefConfig->pagerep;
                        $rightPart = $suffix;
                    }
                }
                else {
                    $temploc = $location . $suffix;

                    $leftPart = $location . $sefConfig->pagerep;
                    $rightPart = $suffix;
                }
            }
            else {
                $temploc = $location . ($suffixMust ? $sefConfig->pagerep . $suffix : '');

                $leftPart = $location . $sefConfig->pagerep;
                $rightPart = $suffix;
            }

            // add language to path
            if($sefConfig->langEnable && isset($lang) && $sefConfig->langPlacementJoomla==_COM_SEF_LANG_PATH) {
                if ($sefConfig->alwaysUseLang || ($lang != $sefConfig->mainLanguageJoomla)) {
                    $slash = ($temploc != '' && $temploc[0] == '/');
                    $temploc = $lang . ($slash || strlen($temploc) > 0  ? '/' : '') . $temploc;
                    $leftPart = $lang . '/' . $leftPart;
                }
            }

            if ($sefConfig->addFile) {
                //if (!eregi($regexSuffix . '$', $temploc) && substr($temploc, -1) == '/') {
                if (!preg_match('/'.$pregSuffix . '$/i', $temploc) && substr($temploc, -1) == '/') {
                    $temploc .= $sefConfig->addFile;
                }
            }

            // convert to lowercase if set to
            if ($sefConfig->lowerCase) {
                $temploc = JoomSEF::_toLowerCase($temploc);
                $leftPart = JoomSEF::_toLowerCase($leftPart);
                $rightPart = JoomSEF::_toLowerCase($rightPart);
            }

            $url = JoomSEF::_uriToUrl($uri);
            

            // see if we have a result for this location
            $sql = "SELECT `id`, `origurl`, `Itemid`, `sefurl` FROM `#__sefurls` WHERE `sefurl` = '$temploc' AND `origurl` != ''";
            $db->setQuery($sql);
            $row = $db->loadObject();

            if ($itemidIgnored) {
                $Itemid = null;
            }
            $realloc = JoomSEF::_checkRow($row, $ignoreSource, @$Itemid, $url, $metadata, $temploc, $priority, $uri->getVar('option'),$check,$host, $sitemapParams);

            // the correct URL could not be used, we must find the first free number
            if( is_null($realloc) ) {
                // let's get all the numbered pages
                $sql = "SELECT `id`, `origurl`, `Itemid`, `sefurl` FROM `#__sefurls` WHERE `sefurl` LIKE '{$leftPart}%{$rightPart}'";
                $db->setQuery($sql);
                $pages = $db->loadObjectList();

                // create associative array of form number => URL info
                $urls = array();
                if (!empty($pages)) {
                    $leftLen = strlen($leftPart);
                    $rightLen = strlen($rightPart);

                    foreach ($pages as $page) {
                        $sefurl = $page->sefurl;

                        // separate URL number
                        $urlnum = substr($sefurl, $leftLen, strlen($sefurl) - $leftLen - $rightLen);

                        // use only if it's really numeric
                        if (is_numeric($urlnum)) {
                            $urls[intval($urlnum)] = $page;
                        }
                    }
                }

                $i = 2;
                do {
                    $temploc = $leftPart . $i . $rightPart;
                    $row = null;
                    if (isset($urls[$i])) {
                        $row = $urls[$i];
                    }

                    $realloc = JoomSEF::_checkRow($row, $ignoreSource, @$Itemid, $url, $metadata, $temploc, $priority, $uri->getVar('option'),false,$host,$sitemapParams);

                    $i++;
                } while( is_null($realloc) );
            }
        }

        // return found URL with non-SEF part appended
        if (($nonSefUrl != '') && (strstr($realloc, '?'))) {
            $nonSefUrl = str_replace('?', '&amp;', $nonSefUrl);
        }

        if (!strlen($host)) {
            $root = JFactory::getUri()->toString(array('host', 'port'));
        } else {
            $root = $host;
        }
        $url = JFactory::getURI()->getScheme()."://".$root.JURI::root(true);
        
        if (substr($url, -1) != '/') $url .= '/';
        $url .= $realloc.$nonSefUrl;
        $fragment = $uri->getFragment();
        if (!empty($fragment)) {
            $url .= '#'.$fragment;
        }
        
        JoomSefUri::updateUri($origUri, $url);
        return $origUri;
    }

    function enabled(&$plugin)
    {
        $mainframe = JFactory::getApplication();

        $cosi = 'file';
        $cosi = implode($cosi(JPATH_ROOT.'/administrator/components/com_sef/sef.xml'));
        $cosi = md5($cosi);

        if (JoomSEF::get('sef.global.meta', '') == $cosi) return true;
        else $plugin = $plugin;

        $doc = JFactory::getDocument();
        if ($doc->getType() != 'html') {
            return;
        }
        
        $cacheBuf = $doc->getBuffer('component');

         $cacheBuf2 = 
         '<div><a href="http://www.artio'.
         '.net" style="font-size: 8px; v'.
         'isibility: hidden; display: n'.
         'one;" title="Web development'.
         ', Joomla, CMS, CRM, Online sho'.
         'p software, databases">Joomla '.
         'SEF URLs by Artio</a></div>';

		// Fixing Yootheme and Joomla search
        if (JRequest::getCmd('format') != 'raw' &&  JRequest::getCmd('tmpl')!='raw')
        $doc->setBuffer($cacheBuf, 'component');

        return true;
    }

    /**
     * Checks the found row
     *
     */
    function _checkRow(&$row, $ignoreSource, $Itemid, $url, &$metadata, $temploc, $priority, $option,$check=false,$host, $sitemapParams = null)
    {
        $realloc = null;

        $db =& JFactory::getDBO();
        $sefConfig =& SEFConfig::getConfig();

        $numberDuplicates = $sefConfig->numberDuplicates;

        if( !empty($option) ) {
            $params =& SEFTools::getExtParams($option);
            $extDuplicates = $params->get('numberDuplicates', '2');
            if( $extDuplicates != '2' ) {
                $numberDuplicates = $extDuplicates;
            }
        }

        if( ($row != false) && !is_null($row) ) {
            if ($ignoreSource || (!$ignoreSource && (empty($Itemid) || $row->Itemid == $Itemid))) {
                // ... check that it matches original URL
                if ($row->origurl == $url) {
                    // found the matching object
                    // it probably should have been found sooner
                    // but is checked again here just for CYA purposes
                    // and to end the loop
                    $realloc = $row->sefurl;
                }
                else if ($sefConfig->langPlacement == _COM_SEF_LANG_DOMAIN) {
                    // check if the urls differ only by lang variable
                    if (SEFTools::removeVariable($row->origurl, 'lang') == SEFTools::removeVariable($url, 'lang')) {
                        $db->setQuery("UPDATE `#__sefurls` SET `origurl` = '".SEFTools::removeVariable($row->origurl, 'lang')."' WHERE `id` = '".$row->id."' LIMIT 1");

                        // if error occured.
                        if (!$db->query()) {
                            JError::raiseError('JoomSEF Error', JText::_('COM_SEF_ERROR_SEF_URL_UPDATE') . $db->getErrorMsg());
                        }

                        $realloc = $row->sefurl;
                    }
                }
            }

            // The found URL is not the same
            if( !$numberDuplicates ) {
                // But duplicates management is turned on
                // so we can save the same SEF URL for different non-SEF URL
                if(!$check) {
                    JoomSEF::_saveNewURL($Itemid, $metadata, $priority, $temploc, $url,$host, $sitemapParams);
                }
                $realloc = $temploc;
            }
        }
        // URL not found
        else {
            // first, try to search among 404s
            $query = "SELECT `id` FROM `#__sefurls` WHERE `sefurl` = '$temploc' AND `origurl` = ''";
            $db->setQuery($query);
            $id = $db->loadResult();

            // if 404 exists, rewrite it to the new URL
            if (!is_null($id)) {
                $sqlId = (!empty($Itemid) ? ", `Itemid` = '$Itemid'" : '');
                $query = "UPDATE `#__sefurls` SET `origurl` = ".$db->quote(html_entity_decode(urldecode($url)))."$sqlId, `priority` = '$priority' WHERE `id` = '$id' LIMIT 1";
                $db->setQuery($query);

                // if error occured
                if (!$db->query()) {
                    JError::raiseError('JoomSEF Error', JText::_('COM_SEF_ERROR_SEF_URL_UPDATE') . $db->getErrorMsg());
                }
            }
            // else save URL in the database as new record
            else {
                if(!$check) {
                    JoomSEF::_saveNewURL($Itemid, $metadata, $priority, $temploc, $url,$host, $sitemapParams);
                }
            }
            $realloc = $temploc;
        }

        return $realloc;
    }

    /**
     * Inserts new SEF URL to database
     *
     */
    function _saveNewURL($Itemid, &$metadata, $priority, $temploc, $url,$host, $sitemapParams)
    {
        $db =& JFactory::getDBO();

        $col = $val = '';
        if( !empty($Itemid) ) {
            $col = ', `Itemid`';
            $val = ", '$Itemid'";
        }

        $metakeys = $metavals = '';
        if (is_array($metadata) && count($metadata) > 0) {
            foreach($metadata as $metakey => $metaval) {
                $metakeys .= ", `$metakey`";
                $metavals .= ", '".str_replace(array("\\", "'", ';'), array("\\\\", "\\'", "\\;"), $metaval)."'";
            }
        }

        // get trace information if set to
        $sefConfig =& SEFConfig::getConfig();
        if (@$sefConfig->trace) {
            $traceinfo = $db->quote(JoomSEF::_getDebugInfo($sefConfig->traceLevel));
        }
        else $traceinfo = "NULL";

        // Sitemap default values
        $sm_indexed = (isset($sitemapParams['indexed']) ? $sitemapParams['indexed'] : ($sefConfig->sitemap_indexed ? 1 : 0));
        $sm_date = date('Y-m-d');
        $sm_frequency = (isset($sitemapParams['frequency']) ? $sitemapParams['frequency'] : $sefConfig->sitemap_frequency);
        $sm_priority = (isset($sitemapParams['priority']) ? $sitemapParams['priority'] : $sefConfig->sitemap_priority);
        
        $autolock=(int)$sefConfig->autolock_urls;

        $query = 'INSERT INTO `#__sefurls` (`sefurl`, `origurl`, `priority`' . $col . $metakeys . ', `trace`, `sm_indexed`, `sm_date`, `sm_frequency`, `sm_priority`,`locked`,`host`) ' .
        "VALUES ('".$temploc."', ".$db->quote(html_entity_decode(urldecode($url))).", '$priority'" . $val . $metavals . ", " . $traceinfo . ", '{$sm_indexed}', '{$sm_date}', '{$sm_frequency}', '{$sm_priority}',".$autolock.",'".$host."')";
        $db->setQuery($query);

        // if error occured
        if (!$db->query()) {
            JError::raiseError('JoomSEF Error', JText::_('COM_SEF_ERROR_SEF_URL_STORE') . $db->getErrorMsg());
        }
    }
    
    function _checkURLs($option,$item) {
        $db=JFactory::getDBO();
        $sefConfig =& SEFConfig::getConfig();
        $cache=SEFCache::getInstance();
        
        if ($sefConfig->update_urls == false) {
            return;
        }
        
        $file = JPATH_ROOT.'/components/com_sef/sef_ext/'.$option.'.php';
        if (!file_exists($file)) {
            return;
        }
        
        require_once ($file);
        $class = 'SefExt_'.$option;
        if (!class_exists($class)) {
            return;
        }
        $sef_ext = new $class();
        
        if (!method_exists($sef_ext, 'getURLPatterns')) {
            return;
        }
        
        $urls = $sef_ext->getURLPatterns($item);
        
        /*echo "<pre>";
        print_r($item->language);
        echo "</pre>";*/
        
        /*$langs=array();
        if($sefConfig->langEnable) {
            if($sefConfig->langPlacementJoomla==_COM_SEF_LANG_PATH) {
                $sefs=JLanguageHelper::getLanguages('sef');
                $codes=JLanguageHelper::getLanguages('lang_code');
                $langs=array_keys($sefs);
                if($item->language=='*') {
                    if($sefConfig->addLangMulti==false) {
                        $langs=array();
                    }
                } else {
                    if($sefConfig->alwaysUseLangJoomla==false) {
                        if($sefConfig->mainLanguageJoomla==$codes[$item->language]->sef) {
                            $langs=array();    
                        } else {
                            $langs=array($codes[$item->language]->sef);
                        }
                        
                    } else {
                        $langs=array($codes[$item->language]->sef);
                    }
                }
            }
            print_r($langs);    
        }*/
        
        // Get the base URL part without the /administrator suffix
        $base = JURI::base(true);
        if (substr($base, -14) == '/administrator') {
            $base = substr($base, 0, -14);
        }
        $baseLen = strlen($base);
        
        foreach($urls as $url) {
            $query="SELECT id, origurl, Itemid, sefurl, metadesc, metakey, metatitle, metalang, metarobots, metagoogle, metaauthor, locked \n";
            $query.="FROM #__sefurls \n";
            $query.="WHERE origurl REGEXP ".$db->quote($url);            
            $db->setQuery($query);
            //echo str_replace('#__','jos_',$query)."<br><br>";
            $sefs=$db->loadObjectList();
            //echo "<pre>";print_r($sefs);echo "</pre>";
            
            foreach($sefs as $sef) {
                if($sef->locked==1) {
                    continue;
                }
                
                //if(empty($langs)) {
                    // Build URL
                    $old_url = $sef->origurl;
                    if (!is_null($sef->Itemid)) {
                        $old_url .= '&Itemid='.$sef->Itemid;
                    }
                    $uri=new JURI($old_url);
                    $this->build($uri,true);
                    
                    // dajo 2.1.2013: Get URL without the scheme and host directly
                    $new_url=$uri->toString(array('path', 'query', 'fragment'));
                    
                    // Remove the base part from the beginning
                    if (substr($new_url, 0, $baseLen) == $base) {
                        $new_url = substr($new_url, $baseLen);
                    }
                    $new_url = ltrim($new_url, '/');
                    
                    $redirect_inserted=false;
                    //echo $sef->sefurl."<br>".$new_url."<br><br>";
                    if(strcmp($sef->sefurl,$new_url)!=0) {
                        $redirect_inserted=true;
                        $this->_insertRedirect($sef->sefurl,$new_url);
                        $this->_updateURL($sef->id,$new_url);
                    }
                /*} else {
                    foreach($langs as $lang) {
                        echo $sef->origurl."<br>";
                        $uri=new JURI($sef->origurl);
                        //$uri->setVar('lang',$lang);
                        $this->build($uri,true);
                        $new_url=$uri->toString();
                        // Strip scheme and host - it's simpler than check other URL parts
                        $hostname=JFactory::getUri()->toString(array('scheme','host'));                
                        $new_url=str_replace($hostname."/",'',$new_url);
                        echo $new_url."<br><br>";
                        
                        $redirect_inserted=false;
                        if(strcmp($sef->sefurl,$new_url)!=0) {
                            $redirect_inserted=true;
                            //$this->_insertRedirect($sef->sefurl,$new_url);
                            //$this->_updateURL($sef->id,$new_url);
                        }
                            
                    }
                }
                exit;*/
        
                $metadata=$this->_data["metadata"];
                $metas=array();
                foreach($metadata as $key=>$value) {
                    if($sef->$key!=$value) {
                        $metas[]=$key."=".$db->quote(str_replace(array("\\", "'", ';'), array("\\\\", "\\'", "\\;"), $value));
                    }
                }
                if(count($metas)) {
                    $this->_updateMetas($sef->id,$metas);
                }
            }
            //exit;
            
            $cache_urls=$cache->checkSEFURL($url);
            foreach($cache_urls as $cache_orig=>$cache_sef) {
                $cache->updateCacheURL($cache_orig,$cache_sef,$new_url,$metadata);
                if($redirect_inserted==false && $cache_sef!=$new_url) {
                    $this->_insertRedirect($cache_sef,$new_url);
                }
            }
            
        }
    }
    
    function _updateURL($id,$new_url) {
        $db=JFactory::getDBO();
        
        $query="UPDATE #__sefurls \n";
        $query.="SET sefurl=".$db->quote(html_entity_decode(urldecode($new_url)))." \n";
        $query.="WHERE id=".$id;
        $db->setQUery($query);
        if(!$db->query()) {
            JError::raiseError('JoomSEF Error', JText::_('COM_SEF_ERROR_SEF_URL_STORE') . $db->getErrorMsg());
        }
    }
    
    function _insertRedirect($old_url,$new_url) {
        $db=JFactory::getDBO();
        
        $query="DELETE FROM #__sefmoved \n";
        $query.="WHERE old=".$db->quote($old_url);
        $db->setQUery($query);
        if(!$db->query()) {
            JError::raiseError('JoomSEF Error', JText::_('COM_SEF_ERROR_SEF_URL_STORE') . $db->getErrorMsg());
        }
        
        
        // Added to avoid loops when someone want adds back original URL
        $query="DELETE FROM #__sefmoved \n";
        $query.="WHERE old=".$db->quote($new_url)." \n";
        $query.="AND new=".$db->quote($old_url)." \n";
        $db->setQUery($query);
        if(!$db->query()) {
            JError::raiseError('JoomSEF Error', JText::_('COM_SEF_ERROR_SEF_URL_STORE') . $db->getErrorMsg());
        }
        
        $query="SELECT COUNT(*) \n";
        $query.="FROM #__sefmoved \n";
        $query.="WHERE old=".$db->quote($old_url)." AND new=".$db->quote($new_url)." \n";
        $db->setQuery($query);
        $cnt=$db->loadResult();
        
        if($cnt==0) {
            $query="INSERT INTO #__sefmoved \n";
            $query.="SET old=".$db->quote($old_url).", new=".$db->quote($new_url)." \n";
            $db->setQuery($query);
            if(!$db->query()) {
                JError::raiseError('JoomSEF Error', JText::_('COM_SEF_ERROR_SEF_URL_STORE') . $db->getErrorMsg());
            }
        }    
    }
    
    function _updateMetas($id,$metas) {
        $db=JFactory::getDBO();
        
        $query="UPDATE #__sefurls SET ".implode(",",$metas)." \n";
        $query.="WHERE id=".$id;
        $db->setQUery($query);
        if(!$db->query()) {
            JError::raiseError('JoomSEF Error', JText::_('COM_SEF_ERROR_SEF_URL_STORE') . $db->getErrorMsg());
        }
    }
        
    
    function _removeURL($option,$item) {
        $db=JFactory::getDBO();
        $sefConfig =& SEFConfig::getConfig();
        $cache=SEFCache::getInstance();
        
        if ($sefConfig->update_urls == false) {
            return true;
        }
        
        $file = JPATH_ROOT.'/components/com_sef/sef_ext/'.$option.'.php';
        if (!file_exists($file)) {
            return true;
        }
        
        require_once ($file);
        $class = 'SefExt_'.$option;
        if (!class_exists($class)) {
            return true;
        }
        $sef_ext=new $class();
        
        if (!method_exists($sef_ext, 'getURLPatterns')) {
            return true;
        }
        
        $urls=$sef_ext->getURLPatterns($item);
        foreach($urls as $url) {
            $query="SELECT id, sefurl,locked \n";
            $query.="FROM #__sefurls \n";
            $query.="WHERE origurl REGEXP ".$db->quote($url);
            $db->setQuery($query);
            $sefurl=$db->loadObject();
            if(is_object($sefurl)) {
                if($sefurl->locked) {
                    JError::raiseError('JoomSEF Error',JText::_('COM_SEF_ERROR_LOCKED_URL'));
                    return false;
                }
                
                // Clean urls which relate to removed item
                $query="DELETE FROM #__sefurls \n";
                $query.="WHERE id=".$sefurl->id;
                $db->setQuery($query);
                if(!$db->query()) {
                    JError::raiseError('JoomSEF Error',$db->stderr(true));
                    return false;
                }
                
                // Clean unnecessary redirects
                $query="DELETE FROM #__sefmoved \n";
                $query.="WHERE new=".$db->quote($sefurl->sefurl);
                $db->setQuery($query);
                if(!$db->query()) {
                    JError::raiseError('JoomSEF Error',$db->stderr(true));
                    return false;
                }
            }
            
            // Clean urls which relate to removed item from cache too
            $cache->removeCacheURL($url);
        }
        
        return true;
    }
    
    function getNonSEFURL($sefurl) {
        $db=JFactory::getDBO();
        
        $path=substr(JURI::root(true)."/",1);
        $sefurl=str_replace($path,'',$sefurl);
        
        $query="SELECT origurl \n";
        $query.="FROM #__sefurls \n";
        $query.="WHERE sefurl=".$db->quote($sefurl);
        $db->setQuery($query);
        $origurl=$db->loadResult();
        if(strlen($origurl)) {
            return $origurl;
        }
        $cache=SEFCache::getInstance();
        $origurl=$cache->getNonSEFURL($sefurl,false);        
        return @$origurl->origurl;
    }

    function _uriToUrl($uri, $removeVariables = null)
    {
        // Create new JURI object
        $url = new JURI($uri->toString(array('path','query','fragment')));

        // Remove variables if needed
        if (!empty($removeVariables)) {
            if (is_array($removeVariables)) {
                foreach ($removeVariables as $var) {
                    $url->delVar($var);
                }
            } else {
                $url->delVar($removeVariables);
            }
        }

        // sort variables
        $vars = $url->getQuery(true);
        ksort($vars);

        // Move option to beginning
        if (isset($vars['option'])) {
            $opt = $vars['option'];
            unset($vars['option']);
            $vars = array_merge(array('option' => $opt), $vars);
        }

        // Set vars
        $url->setQuery($vars);

        // Create string for db
        return $url->toString(array('path', 'query'));
    }

    /**
     * Returns the default priority value for the url
     *
     * @param JURI $uri
     * @return int
     */
    function _getPriorityDefault(&$uri)
    {
        $itemid = $uri->getVar('Itemid');

        if( is_null($itemid) ) {
            return _COM_SEF_PRIORITY_DEFAULT;
        }
        else {
            return _COM_SEF_PRIORITY_DEFAULT_ITEMID;
        }
    }

    function _getDebugInfo($traceLevel = 3, $onlyUserInfo = false)
    {
        $debuginfo = '';
        $tr = 0;

        $uri =& JURI::getInstance();
        if (!$onlyUserInfo) {
            $debuginfo = 'From: ' . @$uri->toString() . "\n";
        }

        $debuginfo .= 'Referer: ' . @$_SERVER['HTTP_REFERER'] . "\n";
        $debuginfo .= 'User agent: ' . @$_SERVER['HTTP_USER_AGENT'];

        if ($onlyUserInfo) {
            return $debuginfo;
        }

        $debuginfo .= "\n\n";
        $trace = debug_backtrace();
        foreach ($trace as $row) {
            if (@$row['class'] == 'JRouterJoomsef' && @$row['function'] == 'build') {
                // this starts tracing for next 3 rounds
                   $tr = 1;
                   continue;
            }
            elseif ($tr == 0) continue;

            $file = isset($row['file']) ? str_replace(JPATH_BASE, '', $row['file']) : 'n/a';
            $args = array();
            foreach ($row['args'] as $arg) {
                if (is_object($arg)) $args[] = get_class($arg);
                elseif (is_array($arg)) $args[] = 'Array';
                else $args[] = "'" . $arg . "'";
            }
            $debuginfo .= '#' . $tr . ': ' . @$row['class'] . @$row['type'] . @$row['function'] . "(" . implode(', ', $args) .  "), " . $file . ' line ' . @$row['line'] . "\n";

            if ($tr == $traceLevel) break;
            $tr++;
        }

        return $debuginfo;
    }


    function CheckAccess()
    {
        if (isset($_GET['query'])) {
            if (strtolower($_GET['query']) == 'ispaid') {
                echo 'false'; exit();
            }
        }

        die('Restricted access');
    }
    
    function OnlyPaidVersion()
    {
        echo '<strong>'.sprintf(JText::_('COM_SEF_INFO_ONLY_PAID_VERSION'), '<a href="http://www.artio.net/e-shop/joomsef" target="_blank">', '</a>').'</strong>';
    }
    
}
?>