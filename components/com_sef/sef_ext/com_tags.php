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

class SefExt_com_tags extends SefExt
{
    public function getNonSefVars(&$uri)
    {
        $this->_createNonSefVars($uri);

        return array($this->nonSefVars, $this->ignoreVars);
    }

    protected function _createNonSefVars(&$uri)
    {
        $this->nonSefVars = array();
        $this->ignoreVars = array();

        if (!is_null($uri->getVar('limit'))) {
            $this->nonSefVars['limit'] = $uri->getVar('limit');
        }
        if (!is_null($uri->getVar('limitstart'))) {
            $this->nonSefVars['limitstart'] = $uri->getVar('limitstart');
        }
    }

    protected function AddNamePart(&$name, $object, $part) {
        if (isset($object->$part)) {
            $name[] = $object->$part;
        }
    }

    protected function BuildName($object, $fieldname, $defaultText) {
        $name = array();
        $object->text = $this->params->get($fieldname.'text', $defaultText);
        $this->AddNamePart($name, $object, $this->params->get($fieldname.'1', 'none'));
        $this->AddNamePart($name, $object, $this->params->get($fieldname.'2', 'title'));
        $this->AddNamePart($name, $object, $this->params->get($fieldname.'3', 'none'));

        return implode('-', $name);
    }

    public function beforeCreate(&$uri)
    {
        // Fix IDs
        SEFTools::fixVariable($uri, 'id');
    }
    
    protected function getTag($id)
    {
        $db = JFactory::getDbo();
        $db->setQuery("SELECT id, title, alias FROM #__tags WHERE id = ".(int)$id);
        $row = $db->loadObject();
        
        if (is_null($row)) {
            return null;
        }
        
        $name = $this->BuildName($row, 'tagname', 'Tag');
        
        return $name;
    }
    
    public function create(&$uri)
    {
        $title = array();

        $vars = $uri->getQuery(true);
        $this->_createNonSefVars($uri);

        // Set menu title
        $title[] = JoomSEF::_getMenuTitle($uri->getVar('option'), $uri->getVar('task'), $uri->getVar('Itemid'));
        
        if (isset($vars['view'])) {
            switch ($vars['view']) {
                case 'tag':
                    if (isset($vars['id'])) {
                        $tag = $this->getTag($vars['id']);
                        if (is_null($tag)) {
                            JoomSefLogger::Log('Tag with ID '.$vars['id'].' not set.', $this, 'com_tags');
                            return $uri;
                        }
                        
                        $title[] = $tag;
                    }
                    break;
                
                default:
                    $title[] = $vars['view'];
                    break;
            }
        }

        $newUri = $uri;
        if (count($title) > 0) {
            $newUri = JoomSEF::_sefGetLocation($uri, $title, null, null, null, $uri->getVar('lang'), $this->nonSefVars, null, null, null, true);
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
            case 'article':
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

    public function getPriority(&$uri)
    {
        $itemid = $uri->getVar('Itemid');
        $view = $uri->getVar('view');
        $layout = $uri->getVar('layout');

        switch($view)
        {
            case 'article':
                if( is_null($itemid) ) {
                    return _COM_SEF_PRIORITY_CONTENT_ARTICLE;
                } else {
                    return _COM_SEF_PRIORITY_CONTENT_ARTICLE_ITEMID;
                }
                break;

            case 'category':
                if( $layout == 'blog' ) {
                    if( is_null($itemid) ) {
                        return _COM_SEF_PRIORITY_CONTENT_CATEGORYBLOG;
                    } else {
                        return _COM_SEF_PRIORITY_CONTENT_CATEGORYBLOG_ITEMID;
                    }
                } else {
                    if( is_null($itemid) ) {
                        return _COM_SEF_PRIORITY_CONTENT_CATEGORYLIST;
                    } else {
                        return _COM_SEF_PRIORITY_CONTENT_CATEGORYLIST_ITEMID;
                    }
                }
                break;

            default:
                return null;
                break;
        }
    }
    
    function getURLPatterns($item) {
        $urls=array();
        if($item->getTableName()=='#__categories') {
            // Category view
            $urls[]='index\.php\?option=com_content(&format=feed)?&id='.$item->id.'&';
            // Content View
            $urls[]='index\.php\?option=com_content&catid='.$item->id.'&id=';
            $tree=$item->getTree($item->id);
            foreach($tree as $catitem) {
                $urls[]='index\.php\?option=com_content(&format=feed)?&id='.$catitem->id.'&';
                $urls[]='index\.php\?option=com_content&catid='.$catitem->id.'&id=';
            }
        } else {
            $urls[]='index\.php\?option=com_content(&catid=([0-9])*)*&id='.$item->id.'(&lang=[a-z]+)?(&limitstart=[0-9]+)?(&type=(atom|rss))?&view=article';
        }
        return $urls;
    }
}
?>