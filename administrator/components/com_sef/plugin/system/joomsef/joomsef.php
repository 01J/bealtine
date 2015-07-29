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

// Security check to ensure this file is being included by a parent file.
defined('_JEXEC') or die('Restricted access');

jimport('joomla.language.helper');
JLoader::register('SEFConfig', JPATH_ADMINISTRATOR.'/components/com_sef/classes/config.php');
JLoader::register('SEFTools', JPATH_ADMINISTRATOR.'/components/com_sef/classes/seftools.php');

class plgSystemJoomsef extends JPlugin
{
    var $linksDivs = array();

    function plgSystemJoomsef( &$subject )
    {
        parent::__construct($subject);

        // load plugin parameters
        $this->_plugin = JPluginHelper::getPlugin('system', 'joomsef');
        $this->_params = new JRegistry($this->_plugin->params);
        
        if($this->_isEnabled()) {
        	require_once( JPATH_ROOT.'/components/com_sef/joomsef.php' );
        	JoomSEF::getLanguage(JFactory::getURI());
        }
		
    }
    
    function onAfterInitialise()
    {
        $sefConfig = SEFConfig::getConfig();
        
        $mainframe = JFactory::getApplication();
        
        // Enable menu associations if set to
        $joomlaVersion = new JVersion();
        if ($joomlaVersion->isCompatible('3.0')) {
            $mainframe->item_associations = $sefConfig->langMenuAssociations ? 1 : 0;
        }
        else {
            $mainframe->set('menu_associations', $sefConfig->langMenuAssociations ? 1 : 0);
        }
        
        // Register installer and updater adapters in admin area
        $this->registerAdapters();
        
        // Check if JoomSEF should be run
        if (!self::_isEnabled()) {
            return true;
        }
        
        // Store the router for later use
        $router = $mainframe->getRouter();
        JoomSEF::set('sef.global.jrouter', $router);
        
        // Load JoomSEF language file
        $jLang = JFactory::getLanguage();
        $jLang->load('com_sef', JPATH_ADMINISTRATOR);

        require_once(JPATH_ROOT.'/components/com_sef/sef.router.php');
        $jsRouter = new JRouterJoomsef();
        $router->attachParseRule(array($jsRouter, 'parse'));
        $router->attachBuildRule(array($jsRouter, 'build'));
        
        // Disable global "Add suffix to URLs" before parsing and store current config
        $config = JFactory::getConfig();
        $oldSuffix = $config->get('sef_suffix', 0);
        $config->set('sef_suffix', 0);
        JoomSEF::set('sef.global.orig_sef_suffix', $oldSuffix);
        
        // Get all configured subdomains
        $subdomains = SEFTools::getAllSubdomains();
        
        // Redirect only when there's no POST variables
        if (($sefConfig->wwwHandling != _COM_SEF_WWW_NONE) && empty($_POST)) {
            // Handle www and non-www domain
            $uri  = JURI::getInstance();
            $host = $uri->getHost();
            $redirect = false;
            
            // Check if host is only IP
            $isIP = preg_match('/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}\z/', $host);

            if ($sefConfig->wwwHandling == _COM_SEF_WWW_USE_WWW && !$isIP && strpos($host, 'www.') !== 0) {
                // Check if host starts with one of our subdomains
                if (isset($subdomains['*']) && (count($subdomains['*']) > 0)) {
                    $parts = explode('.', $host);
                    $domain = $parts[0];
                    $found = false;
                    foreach ($subdomains['*'] as $sub) {
                        if ($domain == $sub->subdomain) {
                            $found = true;
                            break;
                        }
                    }
                    if (!$found) {
                        // Redirect to www form
                        $redirect = true;
                        $uri->setHost('www.'.$host);
                    }
                }
                else {
                    // Redirect to www form
                    $redirect = true;
                    $uri->setHost('www.'.$host);
                }
            }
            else if ($sefConfig->wwwHandling == _COM_SEF_WWW_USE_NONWWW && strpos($host, 'www.') === 0) {
                // host must not begin with www.
                $redirect = true;
                $uri->setHost(substr($host, 4));
            }

            // Redirect if needed
            if ($redirect) {
                $url = $uri->toString();
                header('Location: ' . $url, true, 301);
                jexit();
            }
        }
        
        // Load custom files only if needed for language or subdomains
        if (($sefConfig->langPlacementJoomla == _COM_SEF_LANG_DOMAIN) || (count($subdomains) > 0)) {
            JLoader::register("JRoute",JPATH_SITE.'/components/com_sef/helpers/methods.php',true);
            JLoader::register("JText",JPATH_SITE.'/components/com_sef/helpers/methods.php',true);
        }

        return true;
    }
    
    function registerAdapters()
    {
        $app = JFactory::getApplication();
        
        // Register adapters only in admin area
        if (!$app->isAdmin()) {
            return;
        }
        
        // Include adapter files
        require_once(JPATH_ADMINISTRATOR.'/components/com_sef/adapters/sef_ext.php');
        require_once(JPATH_ADMINISTRATOR.'/components/com_sef/adapters/sef_update.php');
        
        // Set installer adapter
        $installer = JInstaller::getInstance();
        $adapterSefExt = new JInstallerAdapterSef_ext($installer);
        $installer->setAdapter('sef_ext', $adapterSefExt);
        
        // Set updater adapter
        $updater = JUpdater::getInstance();
        $adapterSefUpdate = new JUpdaterSEF_Update($updater, $updater->getDBO());
        $updater->setAdapter('sef_update', $adapterSefUpdate);
    }


    function onAfterDispatch()
    {
        $mainframe = JFactory::getApplication();
        if ($mainframe->isAdmin()) {
            // Add code to prevent separators in admin menu from
            // creating new URLs and generating 404's
            $doc = JFactory::getDocument();
            if ($doc->getType() == 'html') {
                $doc->addStyleDeclaration('.icon-16-separator { background: none !important; background-image: none !important; }');
            }
        }
        
        // Check if JoomSEF should be run
        if (!self::_isEnabled() || !class_exists('JoomSEF') || !JoomSEF::enabled($this)) {
            return true;
        }
        
        // Check page base href value
        $this->_checkBaseHref();

        // Remove Joomla generated canonical links if set to
        $sefConfig = SEFConfig::getConfig();
        if ($sefConfig->canonicalsRemove) {
            $this->_removeCanonicals();
        }
        
        // Do not run plugin if metadata generation is disabled
        if ($sefConfig->enable_metadata > 0) {
            // generate page title
            $this->_checkSEFTitle();

            // generate page metadata
            $this->_generateMeta();
        }
        
        // Fix canonical links if set to
        if ($sefConfig->canonicalsFix) {
            $this->_fixCanonicals();
        }
        
        
        return true;
    }
    
    function onAfterRender()
    {
        // Check if JoomSEF should be run
        if (!self::_isEnabled() || !class_exists('JoomSEF') || !JoomSEF::enabled($this)) {
            return;
        }
        
        // Change the index.php links to /
        $sefConfig = SEFConfig::getConfig();
        if ($sefConfig->fixIndexPhp) {
            $this->_fixIndexLinks();
        }
        //$this->_fixSubDomains();
    }
    
    function _isEnabled()
    {
        // Do not run plugin in administration area
        $mainframe = JFactory::getApplication();
        if ($mainframe->isAdmin()) {
           return false;
        }
        
        // Do not run plugin if SEF is disabled
        $config = JFactory::getConfig();
        if (!$config->get('sef')) {
            return false;
        }
        
        // Check if JoomSEF is enabled
        $sefConfig = SEFConfig::getConfig();
        if (!$sefConfig->enabled) {
            return false;
        }
        
        // Check if JoomSEF plugin is enabled
        if (!JPluginHelper::isEnabled('system', 'joomsef')) {
            return false;
        }
        
        // Check format
        //
        // 22.3.2012, dajo:
        // Removed, JoomSEF should be run, but such URLs shouldn't be
        // SEFed in the JoomSEF::build() function
        /*
        $format = JRequest::getVar('format');
        $tmpl = JRequest::getVar('tmpl');
        if ($format == 'raw' || $format == 'json' || $format == 'xml' || $tmpl == 'raw') {
            return false;
        }
        */
        
        return true;
    }

    /**
     * Generate metadata
     */
    function _generateMeta()
    {
        $mainframe = JFactory::getApplication();

        $document = JFactory::getDocument();
        $sefConfig = SEFConfig::getConfig();

        $rewriteKey    = $sefConfig->rewrite_keywords;
        $rewriteDesc   = $sefConfig->rewrite_description;

        $metadesc       = str_replace('"', '&quot;', JoomSEF::get('sef.meta.desc'));
        $metakey        = str_replace('"', '&quot;', JoomSEF::get('sef.meta.key'));
        $metalang       = str_replace('"', '&quot;', JoomSEF::get('sef.meta.lang'));
        $metarobots     = str_replace('"', '&quot;', JoomSEF::get('sef.meta.robots'));
        $metagoogle     = str_replace('"', '&quot;', JoomSEF::get('sef.meta.google'));
        $canonicallink  = str_replace('"', '&quot;', JoomSEF::get('sef.link.canonical'));
        $metacustom     = JoomSEF::get('sef.meta.custom');
        $generator      = str_replace('"', '&quot;', $sefConfig->tag_generator);
        $googlekey      = str_replace('"', '&quot;', $sefConfig->tag_googlekey);
        $livekey        = str_replace('"', '&quot;', $sefConfig->tag_livekey);
        $yahookey       = str_replace('"', '&quot;', $sefConfig->tag_yahookey);
        
        // Global custom meta tags
        if (is_array($sefConfig->customMetaTags)) {
            foreach($sefConfig->customMetaTags as $name => $content) {
                $content = str_replace('"', '&quot;', $content);
                $document->setMetaData($name, $content);
            }
        }

        // description metatag
        if (!empty($metadesc)) {
            // get original description
            $oldDesc = $document->getDescription();

            // override by JoomSEF desc
            if ($rewriteDesc == _COM_SEF_META_PR_JOOMSEF || $oldDesc == '') {
                $document->setDescription($metadesc);
            // or join both
            } elseif ($rewriteDesc == _COM_SEF_META_PR_JOIN && $oldDesc != '') {
                $document->setDescription($metadesc . ', ' . $oldDesc);
            }
            // otherwise leave intact
        }

        // keywords metatag
        if (!empty($metakey)) {
            // get original keywords
            $oldKey = $document->getMetaData('keywords');

            // override by JoomSEF keys
            if ($rewriteKey == _COM_SEF_META_PR_JOOMSEF || $oldKey == '') {
                $document->setMetaData('keywords', $metakey);
            // or join both
            } elseif ($rewriteKey == _COM_SEF_META_PR_JOIN && $oldKey != '') {
                $document->setMetaData('keywords', $metakey . ', ' . $oldKey);
            }
            // otherwise leave intact
        }

        if (!empty($metalang))   $document->setMetaData('lang', $metalang);
        if (!empty($metarobots)) $document->setMetaData('robots', $metarobots);
        if (!empty($metagoogle)) $document->setMetaData('google', $metagoogle);
        if (!empty($generator))  $document->setGenerator($generator);
        if (!empty($googlekey))  $document->setMetaData('google-site-verification', $googlekey);
        if (!empty($livekey))    $document->setMetaData('msvalidate.01', $livekey);
        if (!empty($yahookey))   $document->setMetaData('y_key', $yahookey);
        
        // URL custom meta tags
        if (is_array($metacustom)) {
            foreach ($metacustom as $name => $content) {
                $content = str_replace('"', '&quot;', $content);
                $document->setMetaData($name, $content);
            }
        }

        if (method_exists($document, 'addHeadLink')) {
            if (!empty($canonicallink)) {
                // Always remove previous canonical links if adding our own
                $this->_removeCanonicals();
                
                // Add our own canonical link
                $document->addHeadLink($canonicallink, 'canonical');
            }
        }
    }

    /**
     * Removes all the canonical links currently set
     */
    function _removeCanonicals()
    {
        // Handle canonicals only in HTML document
        $doc = JFactory::getDocument();
        if ($doc->getType() != 'html') {
            return;
        }
        
        // Check if there are any links
        if (!isset($doc->_links) || !is_array($doc->_links)) {
            return;
        }
        
        // Remove canonical links
        foreach ($doc->_links as $url => $link) {
            if ($link['relation'] == 'canonical') {
                unset($doc->_links[$url]);
            }
        }
    }
    
    /**
     * Fixes duplicate domain in canonical links
     */
    function _fixCanonicals()
    {
        // Handle canonicals only in HTML document
        $doc = JFactory::getDocument();
        if ($doc->getType() != 'html') {
            return;
        }
        
        // Check if there are any links
        if (!isset($doc->_links) || !is_array($doc->_links)) {
            return;
        }
        
        // Fix canonical links
        foreach ($doc->_links as $url => $link) {
            if ($link['relation'] != 'canonical')
                continue;

            // Fix canonical link
            $fixed = preg_replace('|^https?://.*(https?://)|i', '$1', $url);

            // Always unset and set the link again, otherwise we'd change the ordering
            unset($doc->_links[$url]);
            $doc->_links[$fixed] = $link;
        }
    }
    
    /**
     * Check page title.
     */
    function _checkSEFTitle()
    {
        $mainframe = JFactory::getApplication();

        $document = JFactory::getDocument();
        $config = JFactory::getConfig();
        $sefConfig = SEFConfig::getConfig();

        $sitename     = $config->get('sitename');
        $preferTitle = $sefConfig->prefer_joomsef_title;
        $sitenameSep = ' '.trim($sefConfig->sitename_sep).' ';
        $preventDupl = $sefConfig->prevent_dupl;

        $useSitename = JoomSEF::get('sef.meta.showsitename', _COM_SEF_SITENAME_GLOBAL);
        if ($useSitename == _COM_SEF_SITENAME_GLOBAL) {
            $useSitename = $sefConfig->use_sitename;
        }
        
        if ($sitenameSep == '  ') $sitenameSep = ' ';

        // Page title
        $pageTitle = JoomSEF::get('sef.meta.title');

        if (empty($pageTitle)) {
            $pageTitle = $document->getTitle();

            // Dave: replaced regular expression as it was causing problems
            //       with site names like [ index-i.cz ] with str_replace
            // Dave: 3.2.9 fix - added check for !empty($sitename) - was causing
            //       problems with empty site names

            /*$pageSep = '( - |'.$sitenameSep.')';
            if (preg_match('/('.$GLOBALS['mosConfig_sitename'].$pageSep.')?(.*)?/', $pageTitle, $matches) > 0) {
            $pageTitle = strtr($pageTitle, array($matches[1] => ''));
            }*/
            if (!empty($sitename)) {
                $pageTitle = str_replace(array($sitename.' - ', ' - '.$sitename, $sitename.$sitenameSep, $sitenameSep.$sitename), '', $pageTitle);
            }
        }

        if ($preferTitle) {
            $pageTitle = trim($pageTitle);

            // Prevent name duplicity if set to
            if ($preventDupl && strcmp($pageTitle, trim($sitename)) == 0) {
                $pageTitle = '';
            }

            if (empty($pageTitle)) $sitenameSep = '';

            if ($useSitename == _COM_SEF_SITENAME_BEFORE && $sitename) {
                $pageTitle = $sitename . $sitenameSep . $pageTitle;
            }
            elseif ($useSitename == _COM_SEF_SITENAME_AFTER && $sitename) {
                $pageTitle .= $sitenameSep . $sitename;
            }

            // set page title and (optionally) meta title tag
            if ($pageTitle) {
                // Joomla escapes the title automatically
                $document->setTitle($pageTitle);
            }
        }
    }

    function _checkBaseHref()
    {
        $sefConfig = SEFConfig::getConfig();

        $checkBaseHref = $sefConfig->check_base_href;

        // now we can set base href
        $document = JFactory::getDocument();
        if ($checkBaseHref == _COM_SEF_BASE_HOMEPAGE) {
            $uri = JURI::getInstance();
            $curUri = clone($uri);
            $domain = JoomSEF::get('real_domain');
            if ($domain) {
                $curUri->setHost($domain);
            }
            
            // dajo 10.9.2012: Make sure base ends with a slash
            $base = $curUri->toString(array('scheme', 'host', 'port')).JURI::base(true);
            $base = rtrim($base, '/').'/';
            
            $document->setBase($base);
        }
        elseif ($checkBaseHref == _COM_SEF_BASE_CURRENT) {
            $uri = JURI::getInstance();
            $curUri = clone($uri);
            $domain = JoomSEF::get('real_domain');
            if ($domain) {
                $curUri->setHost($domain);
            }
            $document->setBase(htmlspecialchars($curUri->toString(array('scheme', 'host', 'port', 'path'))));
        }
        elseif ($checkBaseHref == _COM_SEF_BASE_NONE) {
            $document->setBase('');
        }
        else return;
    }

    
    function _fixIndexLinks()
    {
        // Check the document type
        $document = JFactory::getDocument();
        if ($document->getType() != 'html') {
            return;
        }
        
        // Get the response body
        $body = JResponse::getBody();
        
        // Get the root URL
        $url = JURI::root();
        if (substr($url, -1) != '/') {
            $url .= '/';
        }
        
        // Replace the index.php links in "<a href" and "<form action"
        $body = preg_replace('|<a(\\s[^>]*)href="/?index\\.php"|', '<a$1href="'.$url.'"', $body);
        $body = preg_replace('|<a(\\s[^>]*)href="'.$url.'index\\.php"|', '<a$1href="'.$url.'"', $body);
        $body = preg_replace('|<form(\\s[^>]*)action="/?index\\.php"|', '<form$1action="'.$url.'"', $body);
        $body = preg_replace('|<form(\\s[^>]*)action="'.$url.'index\\.php"|', '<form$1action="'.$url.'"', $body);
        
        // Set new response body
        JResponse::setBody($body);
    }
    
    private function _fixSubDomains() {
    	$document = JFactory::getDocument();
        if ($document->getType() != 'html') {
            return;
        }
        
        // Get the response body
        $body = JResponse::getBody();
        
        $url = JURI::root();
        if (substr($url, -1) != '/') {
            $url .= '/';
        }
        
        //echo JFactory::getUri()->getHost();
        
        $body = preg_replace_callback('|<(a)(\\s*[^>]*)href="([/\-\.a-z0-9]*)"(\\s*[^>]*)>|', array($this,"_replaceLink"), $body);        
        $body = preg_replace_callback('|<(form)(\\s*[^>]*)action="([/\-\.a-z0-9]*)"(\\s*[^>]*)>|', array($this,"_replaceLink"), $body);
        
        JResponse::setBody($body);
    }
    
    private function _replaceLink($matches) {
    	$host=JFactory::getUri()->getHost();
    	
    	$db=JFactory::getDBO();
    	$query=$db->getQuery(true);
    	$query->select('Itemid')->from('#__sefurls')->where('sefurl='.$db->quote(ltrim(str_replace(JFactory::getUri()->base(true),"",$matches[3]),"/")));
    	$db->setQuery($query);
    	$Itemid=$db->loadResult();
    	
    	//echo $matches[3]."\t".$Itemid."<br>";
    	
    	if(strlen($Itemid)) {
	    	$query=$db->getQuery(true);
	    	$query->select('subdomain')->from('#__sef_subdomains')->where('Itemid='.$Itemid);
	    	$db->setQuery($query);
	    	$subdomain=$db->loadResult();
	    	
	    	if(strlen($subdomain)) {
	    		$host=$subdomain.".".$host;
	    	}
    	}

    	switch($matches[1]) {
    		case 'a':
    			return '<a'.$matches[2].'href="http://'.$host.$matches[3].'"'.$matches[4].'>';
    			break;
    		case 'form':
    			return '<form'.$matches[2].'action="http://'.$host.$matches[3].'"'.$matches[4].'>';
    			break;
    	}
    		
    }
}
?>