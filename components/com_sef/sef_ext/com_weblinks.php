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

define('_COM_SEF_PRIORITY_WEBLINKS_LINK_ITEMID', 15);
define('_COM_SEF_PRIORITY_WEBLINKS_LINK', 20);
define('_COM_SEF_PRIORITY_WEBLINKS_CATEGORY_ITEMID', 25);
define('_COM_SEF_PRIORITY_WEBLINKS_CATEGORY', 30);

class SefExt_com_weblinks extends SefExt
{

    function getWeblinkTitle($id)
    {
    	$title=array();
    	$title_fld=SEFTools::UseAlias($this->params, 'weblink_alias') ? '`alias` AS `title`' : '`title`';
    	$query='SELECT `id`,`catid`, `metakey`, `metadata`, `metadesc`, `language`,' . $title_fld . ' FROM `#__weblinks` WHERE `id` = ' . (int) $id;
        $this->_db->setQuery($query);
        $row=$this->_db->loadObject('stdClass',$this->config->translateItems);
        if (is_null($row)) {
            JoomSefLogger::Log("Weblink with ID {$id} could not be found.", $this, 'com_weblinks');
            return array();
        }
        
        if($this->params->get('show_category',2)!=0) {
            $catInfo = $this->getCategoryInfo($row->catid);
            if ($catInfo === false) {
                JoomSefLogger::Log("Category with ID {$row->catid} could not be found.", $this, 'com_weblinks');
            }
            if (is_array($catInfo->path)) {
        	   $title = array_merge($title, $catInfo->path);
            }
        }
		$title[] = ($this->params->get('weblink_id') == 1 ? $row->id . '-' : '') . $row->title;
		$this->getMetaData($row);
        return $title;
        
    }

    function beforeCreate(&$uri)
    {
        // Remove the part after ':' from variables
        if (! is_null($uri->getVar('id')))
            SEFTools::fixVariable($uri, 'id');
        if (! is_null($uri->getVar('catid')))
            SEFTools::fixVariable($uri, 'catid');
        
        if ($uri->getVar('id') == 0)
            $uri->delVar('id');
        
        if ($uri->getVar('w_id') == 0)
            $uri->delVar('w_id');
        
        return;
    }

    function create(&$uri)
    { 
        $sefConfig = &SEFConfig::getConfig();
        
        $vars = $uri->getQuery(true);
        extract($vars);
        
        $title[] = JoomSEF::_getMenuTitleLang($option, $this->lang, @$Itemid);
        
        switch (@$view) {
        	case 'categories':
       			break;
            case 'category':
                $catInfo = $this->getCategoryInfo($id);
                if ($catInfo === false) {
                    JoomSefLogger::Log("Category with ID {$id} could not be found.", $this, 'com_weblinks');
                }
                if (is_array($catInfo->path)) {
            	   $title = array_merge($title, $catInfo->path);
                }
                break;
            case 'form':
            	if(isset($w_id)) {
                	$title = array_merge($title, $this->getWeblinkTitle(@$w_id));
            	}
                break;
            case 'weblink':
                if (!empty($id)) {
                    $title = array_merge($title, $this->getWeblinkTitle($id));
                }
                else {
                    if ($this->params->get('always_en', '0') == '1') {
                        $title[] = 'Submit';
                    }
                    else {
                        $title[] = JText::_('COM_SEF_SUBMIT');
                    }
                }
                break;
        }
        
        switch (@$task) {
            case 'weblink.go':
                if (!empty($id)) {
                    $title = array_merge($title, $this->getWeblinkTitle($id));
                }
                else {
                    if ($this->params->get('always_en', '0') == '1') {
                        $title[] = 'Submit';
                    }
                    else {
                        $title[] = JText::_('COM_SEF_SUBMIT');
                    }
                }
                break;
            case 'new':
                $title[] = 'new' . $sefConfig->suffix;
                break;
            case 'weblink.edit':
                $title = array_merge($title, $this->getWeblinkTitle(@$w_id));
                if ($this->params->get('always_en', '0') == '1') {
                    $title[] = 'Edit';
                }
                else {
                    $title[] = JText::_('COM_SEF_EDIT');
                }
                if (@$return)
                    $nonSefVars['return'] = $return;
                break;
        }
        
        if (@$format) {
            if ($format == 'feed' && @$type)
                $title[] = ucfirst($type);
            else
                $title[] = ucfirst($format);
        }
        
        $newUri = $uri;
        if (count($title)) {
            // Generate meta tags
            $this->metatags = $this->getMetaTags();
            
            $priority = $this->getPriority($uri);
            $sitemap = $this->getSitemapParams($uri);
            if(isset($this->lang)) {
            	$lang=$this->lang;
            }
            $newUri = JoomSEF::_sefGetLocation($uri, $title, null, null, null, @$lang, @$nonSefVars, null, $this->metatags, $priority, false, null, $sitemap);
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
        $task = $uri->getVar('task');
        
        $sm = array();
        if ($view == 'category' || $view == 'categories' || $task == 'weblink.go')
        {
            if ($view == 'categories') $view = 'category';
            if ($task == 'weblink.go') $view = 'weblink';
            
            $indexed = $this->params->get('sm_'.$view.'_indexed', '1');
            $freq = $this->params->get('sm_'.$view.'_freq', '');
            $priority = $this->params->get('sm_'.$view.'_priority', '');
            
            if (!empty($indexed)) $sm['indexed'] = $indexed;
            if (!empty($freq)) $sm['frequency'] = $freq;
            if (!empty($priority)) $sm['priority'] = $priority;
        }
        
        return $sm;
    }

    function getPriority(&$uri)
    {
        $itemid = $uri->getVar('Itemid');
        $view = $uri->getVar('view');
        
        switch ($view) {
            case 'weblink':
                if (is_null($itemid)) {
                    return _COM_SEF_PRIORITY_WEBLINKS_LINK;
                } else {
                    return _COM_SEF_PRIORITY_WEBLINKS_LINK_ITEMID;
                }
                break;
            
            case 'category':
                if (is_null($itemid)) {
                    return _COM_SEF_PRIORITY_WEBLINKS_CATEGORY;
                } else {
                    return _COM_SEF_PRIORITY_WEBLINKS_CATEGORY_ITEMID;
                }
                break;
            
            default:
                return null;
        }
    }
    
    function getURLPatterns($item) {
    	$db=JFactory::getDBO();
    	$urls=array();
    	if($item->getTableName()=='#__categories') {
    		// Category view
    		$urls[]='index\.php\?option=com_weblinks(&format=feed)?&id='.$item->id.'&';
    		
    		$query=$db->getQuery(true);
    		$query->select('id')->from('#__weblinks')->where('catid='.$item->id);
    		$db->setQUery($query);
    		$ids=$db->loadColumn();
    		foreach($ids as $id) {
    			// Content View
    			$urls[]='index\.php\?option=com_weblinks&id='.$id.'(&lang=[a-z]+)?&task=weblink.go';	
    		}
    		
    		$tree=$item->getTree($item->id);
    		foreach($tree as $catitem) {
    			$urls[]='index\.php\?option=com_weblinks(&format=feed)?&id='.$catitem->id.'&';
    			
    			$query=$db->getQuery(true);
	    		$query->select('id')->from('#__weblinks')->where('catid='.$catitem->id);
	    		$db->setQUery($query);
	    		$ids=$db->loadColumn();
	    		foreach($ids as $id) {
	    			// Content View
	    			$urls[]='index\.php\?option=com_weblinks&id='.$id.'(&lang=[a-z]+)?&task=weblink.go';	
	    		}
    		}
    	} else {
    		$urls[]='index\.php\?option=com_weblinks&id='.$item->id.'(&lang=[a-z]+)?&task=weblink.go';
    	}
    	return $urls;
    }
}

?>