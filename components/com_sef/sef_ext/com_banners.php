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

class SefExt_com_banners extends SefExt
{
    var $params;
    
    function GetBannerName($id) {
        $database =& JFactory::getDBO();
        $sefConfig =& SEFConfig::getConfig();
        
        $field = 'name';
        if( SEFTools::UseAlias($this->params, 'banner_alias') ) {
            $field = 'alias';
        }
        
        $id = intval($id);
        $query = "SELECT id, `$field` AS `name`, `language` FROM `#__banners` WHERE `id` = '{$id}'";
        $database->setQuery($query);
        $row = $database->loadObject('stdClass',$this->config->translateItems);
        if (is_null($row)) {
            JoomSefLogger::Log("Banner with ID {$id} could not be found.", $this, 'com_banners');
            return '';
        }
        
        $this->lang = $row->language;
        
        $name = isset($row->name) ? $row->name : '';
        if( $this->params->get('banner_id', '0') ) {
            $name = $id . '-' . $name;
        }
        
        return $name;
    }
    
    function create(&$uri) {
        $sefConfig =& SEFConfig::getConfig();
        $this->params =& SEFTools::getExtParams('com_banners');
        
        $vars = $uri->getQuery(true);
        extract($vars);
        
        $title[] = JoomSEF::_getMenuTitleLang(@$option, @$task, @$Itemid);
        
        switch(@$task) {
            case 'click':
                $title[] = $this->GetBannerName($id);
                unset($task);
                break;
        }

        $newUri = $uri;
        if(isset($this->lang)) {
        	$lang=$this->lang;
        }
        if (count($title) > 0) $newUri = JoomSEF::_sefGetLocation($uri, $title, @$task, null, null, @$lang);
        
        return $newUri;
    }
    
    function getURLPatterns($item) {
    	$urls=array();
    	$urls[]='index\.php\?option=com_banners&id='.$item->id.'&task=click';
    	return $urls;
    }
}
?>