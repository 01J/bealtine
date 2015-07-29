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

class SefExt_com_mailto extends SefExt
{
    function getNonSefVars(&$uri)
    {
        $this->_createNonSefVars($uri);
        
        return array($this->nonSefVars, $this->ignoreVars);
    }
    
    function _createNonSefVars(&$uri)
    {
        if (isset($this->nonSefVars) && isset($this->ignoreVars))
            return;
            
        $this->nonSefVars = array();
        $this->ignoreVars = array();
        
        if (!is_null($uri->getVar('link')))
            $this->ignoreVars['link'] = $uri->getVar('link');
    }
    
    function create(&$uri) {
        $sefConfig =& SEFConfig::getConfig();

        $params = SEFTools::GetExtParams('com_content');

        $vars = $uri->getQuery(true);
        extract($vars);

        // Set title.
        $title = array();
        
        $title[] = JoomSEF::_getMenuTitleLang(@$option, @$task, @$Itemid);
        
        if (!empty($tmpl)) {
            $title[] = $tmpl;
        }

        $newUri = $uri;
        if (count($title) > 0) {
            $this->_createNonSefVars($uri);

            $newUri = JoomSEF::_sefGetLocation($uri, $title, null, null, null, @$lang, null, $this->ignoreVars);
        }

        return $newUri;
    }
}
?>