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

class SefExt
{
    var $params;
    protected $config=null;
    var $metatitle;
    var $metadesc;
    var $metakey;
    var $metakeySource;
    var $nonSefVars;
    var $ignoreVars;
    var $origmetakey;
    var $origmetadesc;
    var $currentUri = '';
    protected $_db=null;
    
    function SefExt()
    {
        // get extension params
        $className = get_class($this);
        if (substr($className, 0, 7) == 'SefExt_') $className = substr($className, 7);
        $this->config=SEFConfig::getConfig();    
        $this->params = SEFTools::getExtParams($className);
        $this->_db=JFactory::getDBO();
    }
    
    function setCurrentUri(&$uri)
    {
        $this->currentUri = $uri->toString(array('path', 'query'));
    }

    function beforeCreate(&$uri)
    {
        return;
    }

    function afterCreate(&$uri)
    {
        return;
    }
    
    /**
     * Returns the nonSef vars and ignore vars
     *
     * @param JURI $uri
     * @return array
     */
    function getNonSefVars(&$uri)
    {
        return array(array(), array());
    }

    function getSefUrlFromDatabase(&$uri)
    {
        $db =& JFactory::getDBO();
        $sefConfig =& SEFConfig::getConfig();

        // David (284): ignore Itemid if set to
        $where = '';

        // Get the extension's ignoreSource parameter
        $option = $uri->getVar('option');
        if( !is_null($option) ) {
            $params = SEFTools::getExtParams($option);
            $extIgnore = $params->get('ignoreSource', 2);
        } else {
            $extIgnore = 2;
        }
        $ignoreSource = ($extIgnore == 2 ? $sefConfig->ignoreSource : $extIgnore);
        $Itemid = $uri->getVar('Itemid');
        
        // If Itemid is set as ignored for the component, set ignoreSource to 1
        if (!is_null($Itemid) && !is_null($option)) {
            if (SEFTools::isItemidIgnored($option, $Itemid)) {
                $ignoreSource = 1;
            }
        }
        
        if (!$ignoreSource && !is_null($Itemid)) {
            $where = " AND (`Itemid` = '".$Itemid."' OR `Itemid` IS NULL)";
        }

        $origurl = addslashes(html_entity_decode(urldecode(JoomSEF::_uriToUrl($uri, 'Itemid'))));
        $query = "SELECT * FROM `#__sefurls` WHERE `origurl` = '" . $origurl . "'" . $where . ' LIMIT 2';
        $this->_db->setQuery($query);
        //echo "<b>".str_replace('#__','jos_',$query)."</b><br><br>";
        $sefurls = $this->_db->loadObjectList('Itemid');
        
        if (!is_array($sefurls)) {
            return false;
        }
        
        if (!$ignoreSource && !is_null($Itemid)) {
            if (isset($sefurls[$Itemid])) {
                $result = $sefurls[$Itemid];
            }
            else if (isset($sefurls[''])) {
                // We've found one of the ignored Itemids, update it with the current and return
                $result = $sefurls[''];
                $result->Itemid = $Itemid;
                $query = "UPDATE `#__sefurls` SET `Itemid` = '{$Itemid}' WHERE `id` = '{$result->id}' LIMIT 1";
                $this->_db->setQuery($query);
                $this->_db->query();
            }
            else {
                $result = reset($sefurls);
            }
        }
        else {
            $result = reset($sefurls);
        }
            
        return is_object($result) ? $result : false;
    }

    function create(&$uri)
    {
        $vars = $uri->getQuery(true);
        extract($vars);
        
        $title = array();
        $title[] = JoomSEF::_getMenuTitleLang(@$option, $lang, @$Itemid);

        $newUri = $uri;
        if (count($title) > 0) {
            $newUri = JoomSEF::_sefGetLocation($uri, $title, null, null, null, @$lang);
        }
        
        return $newUri;
    }
    
    /**
     * Tries to find a non-SEF URL with given language in the list of URLs
     * 
     * @param object[] $rows List of URLs
     * @param string $lang Language to find
     * @return object Found URL object or NULL
     */
    function findUrlForLanguage($rows, $lang)
    {
        if (empty($lang) || !is_array($rows) || count($rows) == 0)
            return null;
        
        $pattern = "#[?&]lang={$lang}(&|$)#i";
        foreach ($rows as $item) {
            if (preg_match($pattern, $item->origurl)) {
                return $item;
            }
        }
        
        return null;
    }
    
    function revert($route, &$disabled)
    {
        $sefConfig =& SEFConfig::getConfig();
        $cache =& SEFCache::getInstance();
        $vars = array();

        $route = html_entity_decode(urldecode($route));
        $routeNoSlash = rtrim($route, '/');
        
        // try to use cache
        $row = null;
        if ($sefConfig->useCache) {
            // There can be multiple non-SEF URLs for the same SEF URL
            $rows = $cache->getNonSefUrl($route);
            if ($rows) {
                // If there is a language from domain, try to find the correct URL
                $lang = JoomSEF::get('domain_lang');
                if (!empty($lang)) {
                    $row = $this->findUrlForLanguage($rows, $lang);
                }
                else {
                    $row = reset($rows);
                }
            }
        }
        
        // cache worked
        if ($row) {
            $fromCache = true;
        }
        else {
            // URL isn't in cache or cache disabled
            $fromCache = false;
            
            if ($sefConfig->transitSlash) {
                $where = "(`sefurl` = ".$this->_db->Quote($routeNoSlash).") OR (`sefurl` = ".$this->_db->Quote($routeNoSlash.'/').")";
            } else {
                $where = "`sefurl` = ".$this->_db->Quote($route);
            }
            $sql = "SELECT * FROM #__sefurls WHERE ($where) AND (`origurl` != '') ORDER BY `priority`";
            
            // Try to find URL with correct language if using domains
            $lang = JoomSEF::get('domain_lang');
            if (!empty($lang)) {
                // Get all SEF URLs
                $row = null;
                $this->_db->setQuery($sql);
                $rows = $this->_db->loadObjectList();
                
                // Try to find the URL with correct language
                if (is_array($rows) && (count($rows) > 0)) {
                    $pattern = "#[?&]lang={$lang}(&|$)#i";
                    foreach ($rows as $item) {
                        if (preg_match($pattern, $item->origurl)) {
                            $row = $item;
                            break;
                        }
                    }
                    
                    // No URL with correct language found, use the first one
                    if (is_null($row)) {
                        $row = reset($rows);
                    }
                }
            }
            else {
                // Find the first matching URL
                $sql .= ' LIMIT 1';
                $this->_db->setQuery($sql);
                $row = $this->_db->loadObject();
            }
        }

        if ($row) {
            // Search in database is not case-sensitive, but URLs are case-sensitive so we should check
            // if the found route really matches the searched one and redirect if necessary to avoid duplicate content
            if (($sefConfig->transitSlash && ($row->sefurl != $routeNoSlash) && ($row->sefurl != $routeNoSlash.'/'))
                || (!$sefConfig->transitSlash && ($row->sefurl != $route))) {
                // Redirect if possible
                if (empty($_POST)) {
                    $redir = JURI::getInstance();
                    $redir->setPath('/'.ltrim($row->sefurl, '/'));
                    SEFTools::redirect($redir->toString(), true);
                }
            }
            
            // Set the disabled flag (old cache records don't need to have enabled set)
            if (!isset($row->enabled)) {
                $row->enabled = 1;
            }
            if ($row->enabled) {
                $disabled = false;
            } else {
                $disabled = true;
            }
            
            // Use the already created URL
            $string = $row->origurl;
            if (isset($row->Itemid) && ($row->Itemid != '')) {
                $string .= (strpos($string, '?') ? '&' : '?') . 'Itemid=' . $row->Itemid;
            }

            // update the hits count if needed
            if (!$fromCache || $sefConfig->cacheRecordHits) {
                $where = '';
                if (!empty($row->id)) {
                    $where = " WHERE `id` = '{$row->id}'";
                } else {
                    $where = " WHERE `sefurl` = '{$row->sefurl}' AND `origurl` != ''";
                }
            
                $this->_db->setQuery("UPDATE #__sefurls SET cpt=(cpt+1)".$where);
                $this->_db->query();
            }
            
            $string = str_replace( '&amp;', '&', $string );
            $QUERY_STRING = str_replace('index.php?', '', $string);
            parse_str($QUERY_STRING, $vars);
            
            // Moved to JoomSEF::_parseSefUrl()
            /*
            if ($sefConfig->setQueryString) {
                $_SERVER['QUERY_STRING'] = $QUERY_STRING;
            }
            */

            // prepare the meta tags array for MetaBot
            // only if URL is not disabled
            if (!$disabled) {
                $mainframe =& JFactory::getApplication();
                if (!empty($row->metatitle))  JoomSEF::set('sef.meta.title',  $row->metatitle);
                if (!empty($row->metadesc))   JoomSEF::set('sef.meta.desc',   $row->metadesc);
                if (!empty($row->metakey))    JoomSEF::set('sef.meta.key',    $row->metakey);
                if (!empty($row->metalang))   JoomSEF::set('sef.meta.lang',   $row->metalang);
                if (!empty($row->metarobots)) JoomSEF::set('sef.meta.robots', $row->metarobots);
                if (!empty($row->metagoogle)) JoomSEF::set('sef.meta.google', $row->metagoogle);
                if (!empty($row->canonicallink)) JoomSEF::set('sef.link.canonical', $row->canonicallink);
                if(!empty($row->metaauthor)) {
                    JoomSEF::set('sef.meta.author',$row->metaauthor);
                }
                if (isset($row->showsitename))   JoomSEF::set('sef.meta.showsitename', $row->showsitename);
                if (!empty($row->metacustom)) {
                    $metacustom = @unserialize($row->metacustom);
                    if (!empty($metacustom)) {
                        JoomSEF::set('sef.meta.custom', $metacustom);
                    }
                }
            }

            // If cache is enabled but URL isn't in cache yet, add it
            if ($sefConfig->useCache && !$fromCache) {
                $cache->addUrl($row->id, $row->origurl, $row->sefurl, $row->cpt + 1, $row->Itemid, $row->metatitle, $row->metadesc, $row->metakey, $row->metalang, $row->metarobots, $row->metagoogle, $row->metaauthor, $row->canonicallink, $row->metacustom, $row->enabled, $row->sef, false, $row->host, $row->showsitename);
            }
        } elseif ($sefConfig->useMoved) {
            // URL not found, let's try the Moved Permanently table
            $where = '';
            if( $sefConfig->transitSlash ) {
                $where = "(`old` = ".$this->_db->quote($routeNoSlash).") OR (`old` = ".$this->_db->quote($routeNoSlash.'/').")";
            }
            else {
                $where = "`old` = ".$this->_db->quote($route);
            }
            $this->_db->setQuery("SELECT * FROM `#__sefmoved` WHERE {$where}");
            $row = $this->_db->loadObject();

            if($row) {
                // URL found, let's update the lastHit in table and redirect
                $this->_db->setQuery("UPDATE `#__sefmoved` SET `lastHit` = NOW() WHERE `id` = '$row->id'");
                $this->_db->query();

                // Let's build absolute URL from our link
                $root = JURI::root();
                if( strstr($row->new, $root) === false ) {
                    $url = $root;
                    if (substr($url, -1) != '/') $url .= '/';
                    if (substr($row->new, 0, 1) == '/') $row->new = substr($row->new, 1);
                    $url .= $row->new;
                } else {
                    $url = $row->new;
                }

                // Use the link to redirect
                SEFTools::redirect($url, true);
            }
        }

        return $vars;
    }

    /**
     * Get metatags.
     * If they do not exist, generate new.
     * 
     * @return array
     */
    function getMetaTags()
    {
        $sefConfig = SEFConfig::getConfig();
        
        // clean source of meta description
        if (!empty($this->metadesc)) $cleanDesc = SEFTools::cleanDesc($this->metadesc);
        else $cleanDesc = '';
        // clean source of meta keywords
        if (!empty($this->metakeySource)) $cleanKeySource = SEFTools::cleanDesc($this->metakeySource);
        else $cleanKeySource = $cleanDesc;
        
        // generate own meta description if set to by extension and global configuration
        if ($this->params->get('meta_desc', '1') && ($sefConfig->metadata_auto == _COM_SEF_META_GEN_ALWAYS || $sefConfig->metadata_auto == _COM_SEF_META_GEN_EMPTY && strlen($this->origmetadesc) == 0)) {            
            // get generation params 
            $maxLen = $this->params->get('desc_len', '250');
            // generate description
            $this->metatags['metadesc'] = SEFTools::clipDesc($cleanDesc, $maxLen);
        } else /*if($sefConfig->metadata_auto == _COM_SEF_META_GEN_EMPTY && strlen($this->origmetadesc))*/ {
            $this->metatags['metadesc']=$this->origmetadesc;
        }
        
        // generate own meta keywords if set to by extension and global configuration
        if ($this->params->get('meta_keys', '1') 
            && ($sefConfig->metadata_auto == _COM_SEF_META_GEN_ALWAYS 
                || $sefConfig->metadata_auto == _COM_SEF_META_GEN_EMPTY && strlen($this->origmetakey) == 0)) {
            // get generation params
            $minLen = $this->params->get('keys_minlen', '3');
            $count = $this->params->get('keys_count', '8');
            $blacklist = $this->params->get('blacklist', null);
            // generate keywords
            $this->metatags['metakey'] = SEFTools::generateKeywords($cleanKeySource, $blacklist, $count, $minLen);
        } else /*if($sefConfig->metadata_auto == _COM_SEF_META_GEN_EMPTY && strlen($this->origmetakey)) */ {
            $this->metatags['metakey']=$this->origmetakey;
        }
        return $this->metatags;
    }

    /**
     * Returns sitemap parameters for given URI
     */
    function getSitemapParams(&$uri)
    {
        return array();
    }

    /**
     * Uses the extension's create() method to generate the meta tags for given URI.
     * Extensions should override this function with more efficient and sophisticated algorithm.
     * 
     * @param $uri URI to generate meta tags for
     * @return array Associative array of meta tags
     */
    function generateMeta(&$uri)
    {
        $data = $this->create($uri);
        
        if (is_object($data) && is_a($data, 'JURI')) {
            // Backwards compatibility
            return array();
        }
        else {
            if (isset($data['metadata']) && is_array($data['metadata'])) {
                return $data['metadata'];
            }
            else {
                return array();
            }
        }
    }
    
    protected function getMetaData($row) {
        $this->metatags=array();
        if(isset($row->metakey)) {
            $this->metatags["metakey"]=$row->metakey;
        }
        if(isset($row->metadesc)) {
            $this->metatags["metadesc"]=$row->metadesc;
        }
        if(isset($row->metadata)) {
            $metadata=new JRegistry($row->metadata);
            $this->metatags["metaauthor"]=$metadata->get('author');
            $this->metatags["metarobots"]=$metadata->get('robots');
        }
        $this->lang=$row->language;
    }
    
    function getParam($name) {
        return $this->params->get($name);
    }
    
    protected function getCategoryInfo($id)
    {
        $field = 'title';
        if (SEFTools::UseAlias($this->params, 'category_alias')) {
            $field = 'alias';
        }

        $addId = (bool) $this->params->get('categoryid', '0');
        
        $catInfo = new stdClass();
        $titles = array();
        $path = array();
        
        $id = intval($id);
        $this->_db->setQuery("SELECT `lft`, `rgt` FROM `#__categories` WHERE `id` = '{$id}'");
        $idx = $this->_db->loadObject();
        if (!$idx) {
            return false;
        }

        $query = "SELECT `id`, `title`, `alias`, `description`, language, `metakey`, `metadesc`, `metadata`, `parent_id` FROM `#__categories` WHERE `lft` <= '{$idx->lft}' AND `rgt` >= '{$idx->rgt}' AND id!=1 ORDER BY `lft` DESC";
        if ($this->params->get('show_category', 2) != 2) {
            $query .= " LIMIT 1";
        }
        $this->_db->setQuery($query);
        $cats = $this->_db->loadObjectList('', 'stdClass', $this->config->translateItems);
        $result = null;
        $this->metatags = array();

        foreach ($cats as $cat) {
            // Get only last category metas
            if (is_null($result)) {
                $result = new stdClass();
                $this->lang = $cat->language;
                $this->metadesc = $cat->description;
                $this->metatags["metakey"] = $cat->metakey;
                $this->metatags["metadesc"] = $cat->metadesc;
                
                $metadata = new JRegistry($cat->metadata);
                $this->metatags["metaauthor"] = $metadata->get('author');
                $this->metatags["metarobots"] = $metadata->get('robots');
                
                $this->metadesc = $cat->description;
                $this->origmetadesc = $cat->metadesc;
                $this->metakeysource = $cat->description;
                $this->origmetakey = $cat->metakey;
                
                $this->pageTitle = $cat->title;
            }

            $name = ($addId ? $id.'-'.$cat->$field : $cat->$field);
            array_unshift($path, $name);
            array_unshift($titles, $cat->title);
            if (empty($this->metatags["metadesc"])) {
                $this->metatags["metadesc"] = $cat->description;
            }
            $id = $cat->parent_id;

            if ($id <= 1) {
                break;
            }
        }
        
        $catInfo->titles = $titles;
        $catInfo->path = $path;
        
        return $catInfo;
    }
}

/**
 * JoomSEF basic rewriting class
 *
 */
class SefExt_Basic extends SefExt
{
    function _addValue(&$title, $value)
    {
        if (!is_array($value)) {
            $title[] = $value;
        }
        else {
            foreach ($value as $val) {
                $this->_addValue($title, $val);
            }
        }
    }
    
    function create(&$uri)
    {
        $vars = $uri->getQuery(true);
        extract($vars);
        
        $title = array();
        
        if ($this->params->get('showMenuTitle', '1') == '1') {
            $title[] = JoomSEF::_getMenuTitleLang($uri->getVar('option'), @$lang, $uri->getVar('Itemid'));
        }
        else {
            $title[] = substr($uri->getVar('option'), 4);
        }
        
        $noAdd = array('option', 'lang', 'Itemid');
        foreach($vars as $name => $value) {
            if (in_array($name, $noAdd)) {
                continue;
            }
            
            // Arrays support
            $this->_addValue($title, $value);
        }

        $newUri = $uri;
        if (count($title) > 0) {
            $newUri = JoomSEF::_sefGetLocation($uri, $title, null, null, null, @$lang);
        }
        
        return $newUri;
    }
}

?>