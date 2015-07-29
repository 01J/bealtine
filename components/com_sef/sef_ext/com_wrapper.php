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

class SefExt_com_wrapper extends SefExt
{
    function beforeCreate(&$uri) {
        // Set the default view if not set
        if( is_null($uri->getVar('view')) ) {
            $uri->setVar('view', 'wrapper');
        }
    }
    
    function create(&$uri) {
        $vars = $uri->getQuery(true);
        extract($vars);
        
        $title = array();
        $title[] = JoomSEF::_getMenuTitleLang(@$option, $this->lang, @$Itemid);

        $newUri = $uri;
        if (count($title) > 0) {
            $newUri = JoomSEF::_sefGetLocation($uri, $title, null, null, null, @$lang);
        }
        
        return $newUri;
    }
}
?>