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

define('_COM_SEF_PRIORITY_CONTACT_CONTACT_ITEMID', 15);
define('_COM_SEF_PRIORITY_CONTACT_CONTACT', 20);
define('_COM_SEF_PRIORITY_CONTACT_CATEGORY_ITEMID', 25);
define('_COM_SEF_PRIORITY_CONTACT_CATEGORY', 30);

class SefExt_com_contact extends SefExt
{

    function getContactName($id)
    {
        $sefConfig = & SEFConfig::getConfig();
        $title=array();
        
        $field = 'name';
        if (SEFTools::UseAlias($this->params, 'contact_alias')) {
            $field = 'alias';
        }
        
        $id = intval($id);
        $query="SELECT `id`, `$field` AS `name`, `catid`, `metakey`, `metadesc`, `metadata`, `language`,`misc` FROM `#__contact_details` WHERE `id` = '{$id}'";
        $this->_db->setQuery($query);
        $row = $this->_db->loadObject('stdClass',$this->config->translateItems);
        if (is_null($row)) {
            JoomSefLogger::Log("Contact with ID {$id} could not be found.", $this, 'com_contact');
            return array();
        }
        
        $name = (($this->params->get('contactid', '0') != '0') ? $id . '-' : '') . $row->name;
        // use contact description as page meta tags if available
        if (($row->misc = JString::trim($row->misc))) {
            $this->metadesc = $row->misc;
        }
        
        if ($this->params->get('show_category', '2') != '0') {
            $catInfo = $this->getCategoryInfo($row->catid);
            if ($catInfo === false) {
                JoomSefLogger::Log("Category with ID {$row->catid} could not be found.", $this, 'com_contact');
            }
            if (is_array($catInfo->path)) {
        	   $title = array_merge($title, $catInfo->path);
            }
        }
        $title[]=$row->name;
        $this->getMetaData($row);
        
        return $title;
    }

    function beforeCreate(&$uri)
    {
        // Remove the part after ':' from variables
        if (!is_null($uri->getVar('id')))
            SEFTools::fixVariable($uri, 'id');
        if (!is_null($uri->getVar('catid')))
            SEFTools::fixVariable($uri, 'catid');
        
        $view = $uri->getVar('view');
        $id = (int) $uri->getVar('id');
        $catid = (int) $uri->getVar('catid');
        
        switch ($view) {
            case 'category':
            case 'categories':
                // Remove view and catid if they point to empty category/categories
                if (! $id) {
                    $uri->delVar('view');
                    $uri->delVar('id');
                }
            case 'contact':
                if ($id && $catid)
                    $uri->delVar('catid');
        }
        
        return;
    }

    function create(&$uri)
    {
        $this->metadesc = null;
        
        // Extract variables
        $vars = $uri->getQuery(true);
        extract($vars);
        
        $this->params = SEFTools::getExtParams('com_contact');
        
        $title[] = JoomSEF::_getMenuTitleLang(@$option, $this->lang, @$Itemid);
        
        if (isset($view)) {
            switch ($view) {
            	case 'categories':
            	case 'featured':
            		break;
            	case 'category':
                    $catInfo = $this->getCategoryInfo($id);
                    if ($catInfo === false) {
                        JoomSefLogger::Log("Category with ID {$id} could not be found.", $this, 'com_contact');
                    }
                    if (is_array($catInfo->path)) {
                	   $title = array_merge($title, $catInfo->path);
                    }
            		break;
                case 'contact':
                    $title = array_merge($title, $this->getContactName($id));
                    break;
            }
        }
        
        if (!empty($format)) {
            if ($format == 'feed' && !empty($type))
                $title[] = $type;
            elseif ($format == 'vcf')
                $title[] = 'vCard';
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
            
            $newUri = JoomSEF::_sefGetLocation($uri, $title, null, null, null, @$lang, null, null, $this->metatags, $priority, false, null,$sitemap);
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
            case 'contact':
            case 'category':
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
        
        switch ($view) {
            case 'contact':
                if (is_null($itemid)) {
                    return _COM_SEF_PRIORITY_CONTACT_CONTACT;
                } else {
                    return _COM_SEF_PRIORITY_CONTACT_CONTACT_ITEMID;
                }
                break;
            
            default:
                if (is_null($itemid)) {
                    return _COM_SEF_PRIORITY_CONTACT_CATEGORY;
                } else {
                    return _COM_SEF_PRIORITY_CONTACT_CATEGORY_ITEMID;
                }
                break;
        }
    }
    
    function getURLPatterns($item) {
    	$db=JFactory::getDBO();
    	$urls=array();
    	if($item->getTableName()=='#__categories') {
    		// Category view
    		$urls[]='index\.php\?option=com_contact(&format=feed)?&id='.$item->id.'(&lang=[a-z]+)?(&limitstart=[0-9]+)?(&type=(atom|rss))?&view=category';
    		
    		$query=$db->getQuery(true);
    		$query->select('id')->from('#__contact_details')->where('catid='.$item->id);
    		$db->setQuery($query);
    		$ids=$db->loadColumn();
    		foreach($ids as $id) {
				$urls[]='index\.php\?option=com_contact&id='.$id.'&';
    		}
    		
    		$tree=$item->getTree($item->id);
    		foreach($tree as $catitem) {
    			$urls[]='index\.php\?option=com_contact(&format=feed)?&id='.$catitem->id.'(&lang=[a-z]+)?(&limitstart=[0-9]+)?(&type=(atom|rss))?&view=category';
    			
    			$query=$db->getQuery(true);
	    		$query->select('id')->from('#__contact_details')->where('catid='.$catitem->id);
	    		$db->setQuery($query);
	    		$ids=$db->loadColumn();
	    		foreach($ids as $id) {
    				$urls[]='index\.php\?option=com_contact&id='.$id.'&';
	    		}
    		}
    	} else {
    		$urls[]='index\.php\?option=com_contact&id='.$item->id.'&';
    	}
    	return $urls;
    }
}
?>