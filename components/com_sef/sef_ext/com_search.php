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

class SefExt_com_search extends SefExt
{

    function beforeCreate(&$uri)
    {
        $ord = $uri->getVar('ordering', null);
        if ($ord == '') {
            $uri->delVar('ordering');
        }
        
        $ph = $uri->getVar('searchphrase', null);
        if ($ph == 'all') {
            $uri->delVar('searchphrase');
        }
        
        if (is_null($uri->getVar('view'))) {
            $uri->setVar('view', 'search');
        }
    }
    
    function getNonSefVars(&$uri)
    {
        $this->_createNonSefVars($uri);
        
        return array($this->nonSefVars, $this->ignoreVars);
    }
    
    function _createNonSefVars(&$uri)
    {
        $this->nonSefVars = array();
        $this->ignoreVars = array();
        if (!is_null($uri->getVar('ordering')))
            $this->nonSefVars['ordering'] = $uri->getVar('ordering');
        if (!is_null($uri->getVar('searchphrase')))
            $this->nonSefVars['searchphrase'] = $uri->getVar('searchphrase');
        if (!is_null($uri->getVar('submit')))
            $this->nonSefVars['submit'] = $uri->getVar('submit');
        if (!is_null($uri->getVar('limit')))
            $this->nonSefVars['limit'] = $uri->getVar('limit');
        if (!is_null($uri->getVar('limitstart')))
            $this->nonSefVars['limitstart'] = $uri->getVar('limitstart');
        if (!is_null($uri->getVar('areas')))
            $this->nonSefVars['areas'] = $uri->getVar('areas');
        
        if (!is_null($uri->getVar('searchword')) && ($this->params->get('nonsefphrase', '1') == '1'))
            $this->nonSefVars['searchword'] = $uri->getVar('searchword');
    }
    
    function create(&$uri)
    {
        $vars = $uri->getQuery(true);
        extract($vars);
        
        // Don't SEF opensearch links
        if (isset($format) && ($format == 'opensearch')) {
            return $uri;
        }
        
        $this->params =& SEFTools::getExtParams('com_search');
        
        $newUri = $uri;
        if (!(isset($task) ? @$task : null)) {
            $title[] = JoomSEF::_getMenuTitleLang($option, $lang, @$Itemid);
            
            if( isset($searchword) && ($this->params->get('nonsefphrase', '1') != '1') ) {
                $title[] = $searchword;
            }
            
            if (isset($view) && ($view != 'search' || $this->params->get('add_search', '0') == '1')) {
                $title[] = $view;
            }
            if (isset($format)) {
                $title[] = $format;
            }
            
            $this->_createNonSefVars($uri);
            if (!isset($searchword) || ($this->params->get('nonsefphrase', '1') != '1') ) {
                // Generate meta tags
                $desc = array();
                if( isset($searchword) ) {
                    $desc[] = $searchword;
                }
                if( isset($searchphrase) ) {
                    $desc[] = $searchphrase;
                }
                $this->metadesc = implode(', ',$desc);
                unset($desc);
            }
            $metatags = $this->getMetaTags();

            $newUri = JoomSEF::_sefGetLocation($uri, $title, null, null, null, @$lang, $this->nonSefVars, null, $metatags, null, true);
        }
        
        return $newUri;
    }
}
?>