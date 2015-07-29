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

define( '_COM_SEF_PRIORITY_CONTENT_ARTICLE_ITEMID',         15 );
define( '_COM_SEF_PRIORITY_CONTENT_ARTICLE',                20 );
define( '_COM_SEF_PRIORITY_CONTENT_CATEGORYLIST_ITEMID',    35 );
define( '_COM_SEF_PRIORITY_CONTENT_CATEGORYLIST',           40 );
define( '_COM_SEF_PRIORITY_CONTENT_CATEGORYBLOG_ITEMID',    55 );
define( '_COM_SEF_PRIORITY_CONTENT_CATEGORYBLOG',           60 );

require_once(JPATH_ROOT.'/components/com_content/helpers/route.php');

class SefExtContentRouteHelper extends ContentHelperRoute
{
    public static function getItemid($id) {
        $needles = array('article'  => array((int) $id));
        $Itemid = self::_findItem($needles);
        return $Itemid;
    }
}

class SefExt_com_content extends SefExt
{
    public function getNonSefVars(&$uri)
    {
        $this->_createNonSefVars($uri);

        return array($this->nonSefVars, $this->ignoreVars);
    }

    protected function _createNonSefVars(&$uri)
    {
        if (!isset($this->nonSefVars) && !isset($this->ignoreVars)) {
            $this->nonSefVars = array();
            $this->ignoreVars = array();
        }

        $this->params =& SEFTools::GetExtParams('com_content');
        $sefConfig =& SEFConfig::getConfig();

        if ($sefConfig->appendNonSef && ($this->params->get('pagination', '0') != '0')) {

            if (!is_null($uri->getVar('limit'))) {
                $this->nonSefVars['limit'] = $uri->getVar('limit');
            }
            if (!is_null($uri->getVar('limitstart'))) {
                $this->nonSefVars['limitstart'] = $uri->getVar('limitstart');
            }
        }
        if (!is_null($uri->getVar('filter'))) {
            $this->nonSefVars['filter'] = $uri->getVar('filter');
        }
        if (!is_null($uri->getVar('return')))
            $this->nonSefVars['return'] = $uri->getVar('return');
    }

    protected function _getArticle($id)
    {
        $sefConfig =& SEFConfig::getConfig();
        $title=array();

        $field = 'title';
        if (SEFTools::UseAlias($this->params, 'title_alias')) {
            $field = 'alias';
        }

        $id = intval($id);
        $query = "SELECT `id`, `title`, `alias`, `introtext`, `fulltext`, `language`, `metakey`, `metadesc`, `metadata`, `catid` FROM `#__content` WHERE `id` = '{$id}'";
        $this->_db->setQuery($query);
        $row = $this->_db->loadObject('stdClass', $this->config->translateItems);
        // Article dont exists
        if (!is_object($row)) {
            JoomSefLogger::Log("Article with ID {$id} could not be found.", $this, 'com_content');
            return array();
        }
        
        $catInfo = $this->getCategoryInfo($row->catid);
        if ($catInfo === false) {
            JoomSefLogger::Log("Category with ID {$row->catid} could not be found.", $this, 'com_content');
            return array();
        }
       
        if($this->params->get('show_category', '2') != 0) {
            if (is_array($catInfo->path) && (count($catInfo->path) > 0)) {
                $catFilter = trim($this->params->get('exclude_categories', ''));
                if ($catFilter != '') {
                    $catFilter = explode("\n", $catFilter);
                    foreach ($catFilter as $filter) {
                        $filter = JString::strtolower(trim($filter));
                        $haystack = array_map(array('JString', 'strtolower'), $catInfo->titles);
                        
                        // Case insensitive search
                        $i = array_search($filter, $haystack);
                        if ($i !== false) {
                            unset($catInfo->path[$i]);
                            unset($catInfo->titles[$i]);
                        }
                    }
                }
                
                $title = array_merge($title, $catInfo->path);
            }
        }

        //$this->item_desc = $row->introtext;
        if ($this->params->get('googlenewsnum', 0) == 0) {
            $title[] = (($this->params->get('articleid', '0') == 1) ? $id.'-' : '').$row->$field;
        } else {
            $title = array_merge($title, $this->GoogleNews($row->$field, $id));
        }
        $this->getMetaData($row);
        
        if ($this->params->get('meta_titlecat',0) == 1) {
            $this->pageTitle = $row->title;
            $metatitle = array_merge(array($row->title), $catInfo->titles);
            $this->metatags["metatitle"] = implode(" - ", $metatitle);
        }
        
        $this->metadesc = $row->introtext;
        $this->origmetadesc = $row->metadesc;
        $this->metakeySource = $row->fulltext;
        $this->origmetakey = $row->metakey;
        
        $this->articleText = $row->introtext . chr(13) . chr(13) . $row->fulltext;
     
        return $title;
    }

    public function beforeCreate(&$uri)
    {
        // remove the limitstart and limit variables if they point to the first page
        if (!is_null($uri->getVar('limitstart')) && ($uri->getVar('limitstart') == '0')) {
            $uri->delVar('limitstart');
            $uri->delVar('limit');
        }
        
        // Remove empty variables
        if ($uri->getVar('limitstart') == '') {
            $uri->delVar('limitstart');
        }
        if ($uri->getVar('showall') == '') {
            $uri->delVar('showall');
        }

        // Try to guess the correct Itemid if set to
        if ($this->params->get('guessId', '0') != '0') {
            if (!is_null($uri->getVar('Itemid')) && !is_null($uri->getVar('id'))) {
                $itemid = SefExtContentRouteHelper::getItemid($uri->getVar('id'));
                if (!is_null($itemid)) {
                    $uri->setVar('Itemid', $itemid);
                }
            }
        }

        // Remove the part after ':' from variables
        if (!is_null($uri->getVar('id')))    SEFTools::fixVariable($uri, 'id');
        if (!is_null($uri->getVar('catid'))) SEFTools::fixVariable($uri, 'catid');

        // TODO: We should remove this, as it generates 1 unnecessary SQL query for each article link,
        // instead the catid should just be always removed from article URL (but when updating JoomSEF,
        // we'll need to update URLs already in database to reflect such change = remove catid from them!)
        // If catid not given, try to find it
        $catid = $uri->getVar('catid');
        if (!is_null($uri->getVar('view')) && ($uri->getVar('view') == 'article') && !is_null($uri->getVar('id')) && empty($catid)) {
            $id = $uri->getVar('id');
            $query = "SELECT `catid` FROM `#__content` WHERE `id` = '{$id}'";
            $this->_db->setQuery($query);
            $catid = $this->_db->loadResult();
            
            if (is_null($catid)) {
                JoomSefLogger::Log("Article with ID {$id} could not be found.", $this, 'com_content');
            }

            if (!empty($catid)) {
                $uri->setVar('catid', $catid);
            }
        }

        // remove empty id in categories list
        if ($uri->getVar('view') == 'categories' && ! (int) $uri->getVar('id'))
            $uri->delVar('id');

        return;
    }

    protected function GoogleNews($title, $id)
    {
        $db =& JFactory::getDBO();

        $num = '';
        $add = $this->params->get('googlenewsnum', '0');

        if ($add == '1' || $add == '3') {
            // Article ID
            $digits = trim($this->params->get('digits', '3'));
            if (!is_numeric($digits)) {
                $digits = '3';
            }

            $num1 = sprintf('%0'.$digits.'d', $id);
        }
        if ($add == '2' || $add == '3') {
            // Publish date
            $query = "SELECT `publish_up` FROM `#__content` WHERE `id` = '$id'";
            $db->setQuery($query);
            $time = $db->loadResult();

            $time = strtotime($time);

            $date = $this->params->get('dateformat', 'ddmm');

            $search = array('dd', 'd', 'mm', 'm', 'yyyy', 'yy');
            $replace = array(date('d', $time),
            date('j', $time),
            date('m', $time),
            date('n', $time),
            date('Y', $time),
            date('y', $time) );
            $num2 = str_replace($search, $replace, $date);
        }

        if ($add == '1') {
            $num = $num1;
        }
        else if ($add == '2') {
            $num = $num2;
        }
        else if ($add == '3') {
            $sep = $this->params->get('iddatesep', '');
            if ($this->params->get('iddateorder', '0') == '0') {
                $num = $num2.$sep.$num1;
            }
            else {
                $num = $num1.$sep.$num2;
            }
        }

        if (!empty($num)) {
            $onlyNum = ($this->params->get('title_alias', 'global') == 'googlenews');

            if ($onlyNum) {
                $title = $num;
            }
            else {
                $sep = $this->params->get('iddatesep', '');
                if (empty($sep)) {
                    $sefConfig =& SEFConfig::getConfig();
                    $sep = $sefConfig->replacement;
                }
    
                $where = $this->params->get('numberpos', '1');
    
                if( $where == '1' ) {
                    $title = $title.$sep.$num;
                } else {
                    $title = $num.$sep.$title;
                }
            }
        }
        
        // Support for slashes
        $title = explode('/', $title);

        return $title;
    }

    function _processPagination(&$uri) {
        $title=array();
        $sefConfig =& SEFConfig::getConfig();
        $handle=$this->params->get('pagination',0);
        if($sefConfig->appendNonSef==true && $handle==1 ) {
            $this->nonSefVars['limitstart'] = $uri->getVar('limitstart');
            return array();
        }
        //$appParams=JFactory::getApplication('site')->getParams();
        $appParams=JApplication::getInstance('site')->getParams('com_content');
        $menu = JFactory::getApplication()->getMenu('site');
        if( !isset($Itemid) ) {
            // We need to find Itemid first
            $active =& $menu->getActive();
            if (is_null($active)) {
                $active =& $menu->getDefault();
            }
            $Itemid = $active->id;
        }
        $menuParams =& $menu->getParams($Itemid);
        $menuParams->merge($appParams);
        // View: Article
        if($uri->getVar("view")=="article") {
            if(($limitstart=$uri->getVar('limitstart'))>0) {
                $pagetext = null;
                if ($this->params->get('multipagetitles', '1') == '1') {
                    $pagetext = $this->_getPageTitle($limitstart);
                }
                
                if (!is_null($pagetext)) {
                    $title[] = $pagetext;
                }
                else {
                    $pagetext = strval($limitstart+1);
                    if (($cnfPageText = $sefConfig->getPageText())) {
                        $this->pageNumberText = str_replace('%s', $limitstart+1, $cnfPageText);
                        $title[] = $this->pageNumberText;
                    }
                }
            }
            if($uri->getVar('showall')==1) {
                if ($this->params->get('always_en', '0') == '1') {
                    $title[] = 'All pages';
                }
                else {
                    $title[] = JText::_('COM_SEF_ALL_PAGES');
                }
            }
        // Layouts: category default list; View: Archive
        } else if($uri->getVar("layout")!="blog" && $uri->getVar("view")!="featured") {
            // If pagination filter is disabled we can make sef URL's with pagination
            if($menuParams->get('show_pagination_limit',1)==0 || $uri->getVar("view")!="featured") {
                $limit=$menuParams->get('display_num');
                $limitstart=$uri->getVar('limitstart');
                if (intval($limit) == 0) {
                    $limit = 1;
                }
                @$page=intval($limitstart/$limit)+1;
                if($page!=1) {
                    $pagetext = strval($page);
                    if (($cnfPageText = $sefConfig->getPageText())) {
                        $this->pageNumberText = str_replace('%s', $page, $cnfPageText);
                        $title[] = $this->pageNumberText;
                    }
                }
            } else {
                if(!is_null($uri->getVar('limitstart'))) {
                    $this->nonSefVars['limitstart'] = $uri->getVar('limitstart');
                }
            }
        // Layout: category blog; View: featured
        } else {
            $leading = $menuParams->get('num_leading_articles', 1);
            $intro   = $menuParams->get('num_intro_articles', 4);
            $limit = $leading + $intro;
            if (intval($limit) == 0) {
                $limit = 1;
            }    
            $limitstart=$uri->getVar('limitstart');
            $page = intval($limitstart / $limit)  + 1;
            if($page!=1) {
                $pagetext = strval($page);
                if (($cnfPageText = $sefConfig->getPageText())) {
                    $this->pageNumberText = str_replace('%s', $page, $cnfPageText);
                    $title[] = $this->pageNumberText;
                }
            }
        }
        
        return $title;    
    }
    
    private function _getPageTitle($page)
    {
        jimport('joomla.utilities.utility');
        
        if (empty($this->articleText)) {
            return null;
        }
        
        // simple performance check
        if (JString::strpos($this->articleText, 'class="system-pagebreak') === false) {
            return null;
        }
        
        // regex
        $regex = '#<hr(.*)class="system-pagebreak"(.*)\/>#iU';
        
        // Find all occurences
        $matches = array();
        preg_match_all($regex, $this->articleText, $matches, PREG_SET_ORDER);
        
        if (!isset($matches[$page-1]) || !isset($matches[$page-1][2])) {
            return null;
        }
        
        $attrs = JUtility::parseAttributes($matches[$page-1][0]);
        
        if (isset($attrs['alt'])) {
            return stripslashes($attrs['alt']);
        }
        else if (isset($attrs['title'])) {
            return stripslashes($attrs['title']);
        }
        else {
            return null;
        }
    }
    
    public function create(&$uri)
    {
        $this->metadesc = null;
        $this->cat_desc = null;
        $this->item_desc = null;

        $sefConfig =& SEFConfig::getConfig();
        $title=array();

        $vars = $uri->getQuery(true);
        extract($vars);
        $this->_createNonSefVars($uri);

        // Set title.
        $title[] = JoomSEF::_getMenuTitleLang(@$option, $this->lang, @$Itemid);
        switch (@$view) {
            case 'form':
                // 13.2.2012, dajo: Don't SEF
                return $uri;
                
                //$this->nonSefVars = array();
                //$this->nonSefVars["return"]=@$return;
                //if(isset($a_id)) {
                //    $title=array_merge($title,$this->_getArticle($a_id));                    
                //}
                //$title[] = JText::_('COM_SEF_FORM');
                 //break;
            case 'featured':
                if(isset($format) && $format=="feed") {
                    @$title[]=$type;
                }
                $title=array_merge($title,$this->_processPagination($uri));
                break;
            case 'categories':
                break;
            case 'category':
                if (isset($id)) {
                    $catInfo = $this->getCategoryInfo($id);
                    if ($catInfo === false) {
                        JoomSefLogger::Log("Category with ID {$id} could not be found.", $this, 'com_content');
                        return $uri;
                    }
                    
                    if (is_array($catInfo->path) && (count($catInfo->path) > 0)) {
                        $title = array_merge($title, $catInfo->path);
                    }
                }
                if($this->params->get('add_layout')==2 || ($this->params->get('add_layout')==1 && $this->params->get('def_layout')!=@$layout)) {
                    @$title[]=$layout;
                }
                if(isset($format) && $format=="feed") {
                    @$title[]=$type;
                }
                $title=array_merge($title,$this->_processPagination($uri));
                break;
            case 'article':
                $articleInfo = $this->_getArticle($id);
                if (!$articleInfo)
                    return $uri;
                
                $title = array_merge($title, $articleInfo);
                $title = array_merge($title, $this->_processPagination($uri));
                break;
            case 'archive':
                if( !empty($year) ) {
                    $title[] = $year;
                }
                if( !empty($month) ) {
                    $title[] = $month;
                }
                $title=array_merge($title,$this->_processPagination($uri));
                break;
            default:
                switch(@$task) {
                    case 'article.add':
                        if (isset($catid)) {
                            $catInfo = $this->getCategoryInfo($catid);
                            if ($catInfo === false) {
                                JoomSefLogger::Log("Category with ID {$catid} could not be found.", $this, 'com_content');
                                return $uri;
                            }
                            
                            if (is_array($catInfo->path) && (count($catInfo->path) > 0)) {
                                $title = array_merge($title, $catInfo->path);
                            }
                        }
                
                        if ($this->params->get('always_en', '0') == '1') {
                            $title[] = 'New';
                        }
                        else {
                            $title[]=JText::_('COM_SEF_NEW');
                        }
                        break;
                    case 'article.edit':
                        if(isset($a_id)) {
                            $title=array_merge($title,$this->_getArticle($a_id));                    
                        }
                        if ($this->params->get('always_en', '0') == '1') {
                            $title[] = 'Edit';
                        }
                        else {
                            $title[]=JText::_('COM_SEF_EDIT');
                        }
                        break;
                    default:
                        // Don't SEF
                        return $uri;
                }
                break;
        }
        
        // Handle printing
        if (isset($print) && (intval($print) == 1)) {
            if ($this->params->get('always_en', '0') == '1') {
                $title[] = 'Print';
            }
            else {
                $title[] = JText::_('JGLOBAL_PRINT');
            }
        }

        $newUri = $uri;
        if (count($title) > 0) {
            // Generate meta tags
            $this->metatags=$this->getMetaTags();
            if (($this->params->get('meta_titlepage', '0') == '1') && !empty($this->pageNumberText)) {
                // Add page number to page title
                if (!empty($this->metatags["metatitle"])) {
                    $this->metatags["metatitle"] .= ' - '.$this->pageNumberText;
                }
                else {
                    $this->metatags["metatitle"] = (!empty($this->pageTitle) ? $this->pageTitle.' - ' : '') . $this->pageNumberText;
                }
            }

            $priority = $this->getPriority($uri);
            $sitemap = $this->getSitemapParams($uri);
            if(isset($this->lang)) {
                $lang=$this->lang;
            }
            
            $newUri = JoomSEF::_sefGetLocation($uri, $title, null, null, null, @$lang, $this->nonSefVars, null, $this->metatags, $priority, true,null, $sitemap);

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