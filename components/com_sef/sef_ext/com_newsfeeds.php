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

define( '_COM_SEF_PRIORITY_NEWSFEEDS_FEED_ITEMID',      15 );
define( '_COM_SEF_PRIORITY_NEWSFEEDS_FEED',             20 );
define( '_COM_SEF_PRIORITY_NEWSFEEDS_CATEGORY_ITEMID',  25 );
define( '_COM_SEF_PRIORITY_NEWSFEEDS_CATEGORY',         30 );

class SefExt_com_newsfeeds extends SefExt
{
    function beforeCreate(&$uri) {
        // Remove the part after ':' from variables
        if (!is_null($uri->getVar('id'))) {
            SEFTools::fixVariable($uri, 'id');
        }
        if (!is_null($uri->getVar('catid'))) {
            SEFTools::fixVariable($uri, 'catid');
        }
        
        if($uri->getVar('view') == 'categories' && ! (int) $uri->getVar('id')) {
			$uri->delVar('id');        	
        }
        
        if (is_null($uri->getVar('view'))) {
            $uri->setVar('view', 'categories');
        }

        return;
    }
    
    function getFeedTitle($id) {
    	$title=array();
    	$field = 'name';
        if( SEFTools::UseAlias($this->params, 'feed_alias') ) {
            $field = 'alias';
        }
        
        $id = intval($id);
    	$query="SELECT `id`, `$field` AS `name`, `catid`, `language`, `metadesc`, `metakey`, `metadata` FROM `#__newsfeeds` WHERE `id` = '{$id}'";
        $this->_db->setQuery($query);
        $row = $this->_db->loadObject('stdClass',$this->config->translateItems);
        if (is_null($row)) {
            JoomSefLogger::Log("Feed with ID {$id} could not be found.", $this, 'com_newsfeeds');
            return array();
        }
        
        if($this->params->get('show_category',2)!=0) {                	
            $catInfo = $this->getCategoryInfo($row->catid);
            if ($catInfo === false) {
                JoomSefLogger::Log("Category with ID {$row->catid} could not be found.", $this, 'com_newsfeeds');
            }
            if (is_array($catInfo->path)) {
        	   $title = array_merge($title, $catInfo->path);
            }
        }
        
        $this->getMetaData($row);
                
        $title[] = (($this->params->get('feedid', '0')==0)?'':($id.'-')).$row->name;
        
        return $title;
    }
    
    function create(&$uri) {
        $sefConfig =& SEFConfig::getConfig();
        $database =& JFactory::getDBO();
        
        $vars = $uri->getQuery(true);
        extract($vars);

        $title = array();
        
        $title[] = JoomSEF::_getMenuTitleLang($option, $this->lang);
        
        switch (@$view) {
        	case 'categories':
        		break;
        	case 'category':
                $catInfo = $this->getCategoryInfo($id);
                if ($catInfo === false) {
                    JoomSefLogger::Log("Category with ID {$id} could not be found.", $this, 'com_newsfeeds');
                }
                if (is_array($catInfo->path)) {
            	   $title = array_merge($title, $catInfo->path);
                }
        		break;
        	case 'newsfeed':
                $title=array_merge($title,$this->getFeedTitle($id));
        		break;
        }
        
        if (isset($format)) {
            if ($format == 'feed' && isset($type)) {
                $title[] = $type;
            }
            else {
                $title[] = $format;
            }
        }

        $newUri = $uri;
        if (count($title) > 0) {
            // Generate meta tags
            $this->metatags = $this->getMetaTags();
        
            $priority = $this->getPriority($uri);
            $sitemap = $this->getSitemapParams($uri);
            if(isset($this->lang)) {
            	$lang=$this->lang;
            }
            $newUri = JoomSEF::_sefGetLocation($uri, $title, null, null, null, @$lang, null, null, $this->metatags, $priority, false, null, $sitemap);
        }
        
        return $newUri;
    }
    
    function getSitemapParams(&$uri)
    {
        if ($uri->getVar('format', 'html') != 'html') {
            // Handle only html links
            return array();
        }
        
        $view = $uri->getVar('view');
        
        $sm = array();
        switch ($view)
        {
            case 'newsfeed':
            case 'category':
            case 'categories':
                if ($view == 'categories') $view = 'category';
                
                $indexed = $this->params->get('sm_'.$view.'_indexed', '1');
                $freq = $this->params->get('sm_'.$view.'_freq', '');
                $priority = $this->params->get('sm_'.$view.'_priority', '');
                
                if (!empty($indexed)) $sm['indexed'] = $indexed;
                if (!empty($freq)) $sm['frequency'] = $freq;
                if (!empty($priority)) $sm['priority'] = $priority;
                
                break;
        }
        
        return $sm;
    }

    function getPriority(&$uri)
    {
        $itemid = $uri->getVar('Itemid');
        $view = $uri->getVar('view');
        
        switch($view)
        {
            case 'newsfeed':
                if (is_null($itemid)) {
                    return _COM_SEF_PRIORITY_NEWSFEEDS_FEED;
                } else {
                    return _COM_SEF_PRIORITY_NEWSFEEDS_FEED_ITEMID;
                }
                break;
                
            case 'category':
                if (is_null($itemid)) {
                    return _COM_SEF_PRIORITY_NEWSFEEDS_CATEGORY;
                } else {
                    return _COM_SEF_PRIORITY_NEWSFEEDS_CATEGORY_ITEMID;
                }
                break;
                
            default:
                return null;
        }
    }
    
    function getURLPatterns($item) {
    	$urls=array();
    	if($item->getTableName()=='#__categories') {
    		// Category view
    		$urls[]='index\.php\?option=com_newsfeeds&id='.$item->id.'&';
    		// Content View
    		$urls[]='index\.php\?option=com_newsfeeds&catid='.$item->id.'&id=';
    		$tree=$item->getTree($item->id);
    		foreach($tree as $catitem) {
    			$urls[]='index\.php\?option=com_newsfeeds&id='.$catitem->id.'&';
    			$urls[]='index\.php\?option=com_newsfeeds&catid='.$catitem->id.'&id=';
    		}
    	} else {
    		$urls[]='index\.php\?option=com_newsfeeds(&catid=([0-9])*)*&id='.$item->id.'(&lang=[a-z]+)?&view=';
    	}
    	return $urls;
    }
}
?>