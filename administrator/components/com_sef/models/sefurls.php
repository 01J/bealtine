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
defined('_JEXEC') or die();

jimport('joomla.language.helper');
jimport('joomla.application.component.model');
require_once JPATH_COMPONENT_SITE.'/sef.cache.php';

class SEFModelSEFUrls extends SEFModel
{
    /**
     * Constructor that retrieves variables from the request
     */
    function __construct()
    {
        parent::__construct();
        $this->_getVars();
    }

    function _getVars()
    {
        $mainframe =& JFactory::getApplication();

        $this->viewmode = $mainframe->getUserStateFromRequest('sef.sefurls.viewmode', 'viewmode', 0);
        //$this->sortby = $mainframe->getUserStateFromRequest('sef.sefurls.sortby', 'sortby', 0);
        $this->filterComponent = $mainframe->getUserStateFromRequest("sef.sefurls.comFilter", 'comFilter', '');
        $this->filterSEF = $mainframe->getUserStateFromRequest("sef.sefurls.filterSEF", 'filterSEF', '');
        $this->filterReal = $mainframe->getUserStateFromRequest("sef.sefurls.filterReal", 'filterReal', '');
        $this->filterHitsCmp = $mainframe->getUserStateFromRequest("sef.sefurls.filterHitsCmp", 'filterHitsCmp', 0);
        $this->filterHitsVal = $mainframe->getUserStateFromRequest("sef.sefurls.filterHitsVal", 'filterHitsVal', '');
        $this->filterItemid = $mainframe->getUserStateFromRequest("sef.sefurls.filterItemId", 'filterItemid', '');
        $this->filterLang = $mainframe->getUserStateFromRequest('sef.sefurls.filterLang', 'filterLang', '');
        $this->filterOrder = $mainframe->getUserStateFromRequest('sef.sefurls.filter_order', 'filter_order', 'sefurl');
        $this->filterOrderDir = $mainframe->getUserStateFromRequest('sef.sefurls.filter_order_Dir', 'filter_order_Dir', 'asc');

        $this->limit        = $mainframe->getUserStateFromRequest('global.list.limit', 'limit', $mainframe->getCfg('list_limit'), 'int');
        $this->limitstart    = $mainframe->getUserStateFromRequest('sef.sefurls.limitstart', 'limitstart', 0, 'int');

        // in case limit has been changed, adjust limitstart accordingly
        $this->limitstart = ($this->limit != 0 ? (floor($this->limitstart / $this->limit) * $this->limit) : 0);

        $total = $this->getTotal();

        /*
        if (($this->limitstart + $this->limit - 1) > $total) {
        $this->limitstart = max(($total - $this->limit), 0);
        }
        */
        
        // tracking on?
        $config =& SEFConfig::getConfig();
        $this->trace = $config->trace;        
    }

    /**
     * Returns the query
     * @return string The query to be used to retrieve the rows from the database
     */
    function _buildQuery()
    {
        $limit = '';
        if (($this->limit != 0) || ($this->limitstart != 0)) {
            $limit = " LIMIT {$this->limitstart},{$this->limit}";
        }

        $where = $this->_getWhere();
        $query = '';
        if ($where != '') {
            $query = "SELECT * FROM #__sefurls WHERE ".$where." ORDER BY ".$this->_getSort().$limit;
        }

        return $query;
    }

    function _getSort()
    {
        if( !isset($this->_sort) ) {
            $this->_sort = '`' . $this->filterOrder . '` ' . $this->filterOrderDir;
        }

        return $this->_sort;
    }

    function _getWhere()
    {
        if( empty($this->_where) ) {
            $db =& JFactory::getDBO();
            // filter ViewMode
            if ($this->viewmode == 1) {
                $where = "`dateadd` > '0000-00-00' AND `origurl` = '' ";
            } elseif ( $this->viewmode == 2 ) {
                $where = "`dateadd` > '0000-00-00' AND `origurl` != '' ";
            } elseif ( $this->viewmode == 0 ) {
                $where = "`dateadd` = '0000-00-00' ";
            } elseif ( $this->viewmode == 4 ) {
                $homes = SEFTools::getHomeQueries();
                
                if (is_array($homes) && (count($homes) > 0)) {
                    $home = reset($homes);
                    $qNL = $home->link;
                    
                    // Add lang= and sort variables
                    $q = $qNL.'&lang=';
                    $q = JoomSEF::_uriToUrl(new JURI($q));
                    
                    // Convert to regular expression
                    $q = str_replace('index.php?', 'index\\.php\\?', $q);
                    $q = str_replace('&lang=', '&lang=[^&]*', $q);
                    
                    $q = $db->quote($q);
                    $qNL = $db->quote($qNL);

                    $where = "(`origurl` != '') AND (`origurl` = {$qNL} OR `origurl` REGEXP {$q}) ";
                }
                else {
                    $where = "`origurl` != '' ";
                }
            } else {
                $where = "`origurl` != '' ";
            }

            // filter URLs
            if ($this->filterComponent != '' && $this->viewmode != 1) {
                $where .= "AND (`origurl` LIKE '%option={$this->filterComponent}&%' OR `origurl` LIKE '%option={$this->filterComponent}') ";
            }
            if ($this->filterLang != '' ) {
                $where .= "AND (`origurl` LIKE '%lang={$this->filterLang}%') ";
            }
            if ($this->filterSEF != '') {
                if( substr($this->filterSEF, 0, 4) == 'reg:' ) {
                    $val = substr($this->filterSEF, 4);
                    $neg = '';
                    if ($val[0] == '!') {
                        $val = substr($val, 1);
                        $neg = 'NOT';
                    }
                    if( $val != '' ) {
                        // Regular expression search
                        $val = $db->Quote($val);
                        $where .= "AND `sefurl` {$neg} REGEXP $val ";
                    }
                }
                else {
                    $val = $db->Quote('%'.$this->filterSEF.'%');
                    $where .= "AND `sefurl` LIKE $val ";
                }
            }
            if ($this->filterReal != '' && $this->viewmode != 1) {
                if( substr($this->filterReal, 0, 4) == 'reg:' ) {
                    $val = substr($this->filterReal, 4);
                    $neg = '';
                    if ($val[0] == '!') {
                        $val = substr($val, 1);
                        $neg = 'NOT';
                    }
                    if( $val != '' ) {
                        // Regular expression search
                        $val = $db->Quote($val);
                        $where .= "AND `origurl` {$neg} REGEXP $val ";
                    }
                }
                else {
                    $val = $db->Quote('%'.$this->filterReal.'%');
                    $where .= "AND `origurl` LIKE $val ";
                }
            }

            // filter hits
            if ($this->filterHitsVal != '') {
                $cmp = ($this->filterHitsCmp == 0) ? '=' : (($this->filterHitsCmp == 1) ? '>' : '<');
                $val = $db->Quote($this->filterHitsVal);
                $where .= "AND `cpt` $cmp $val ";
            }

            // Filter Itemid
            if ($this->filterItemid != '' && $this->viewmode != 1) {
                $val = $db->Quote($this->filterItemid);
                $where .= "AND `Itemid` = $val ";
            }

            // Filter duplicities
            if( $this->viewmode == 5 ) {
                // Get the list of duplicate ids
                $sql = "SELECT `id` FROM `#__sefurls` AS `t1` INNER JOIN (SELECT `sefurl` FROM `#__sefurls` GROUP BY `sefurl` HAVING COUNT(`sefurl`) > 1) AS `t2` ON `t1`.`sefurl` = `t2`.`sefurl` WHERE {$where}";
                $db->setQuery($sql);
                $ids = $db->loadColumn();
                
                // Create the IDs list
                $where = '';
                if (is_array($ids) && count($ids) > 0) {
                    $where = ' `id` IN (' . implode(',', $ids) . ')';
                }
            }
            
            $this->_where = $where;
        }

        return $this->_where;
    }

    function _getWhereIds()
    {
        $ids = JRequest::getVar('cid', array(), 'post', 'array');

        $where = '';
        if( count($ids) > 0 ) {
            $where = '`id` IN (' . implode(', ', $ids) . ')';
        }

        return $where;
    }

    function getTotal()
    {
        if (!isset($this->_total)) {
            $where = $this->_getWhere();
            if ($where != '') {
                $this->_db->setQuery("SELECT COUNT(*) FROM `#__sefurls` WHERE ".$where);
                $this->_total = $this->_db->loadResult();
            }
            else {
                $this->_total = 0;
            }
        }

        return $this->_total;
    }

    /**
     * Retrieves the data
     */
    function getData()
    {
        if ($this->viewmode != 6) {
            // Lets load the data if it doesn't already exist
            if (empty($this->_data))
            {
                $query = $this->_buildQuery();
                if ($query != '') {
                    $this->_data = $this->_getList($query);
                }
                else {
                    $this->_data = array();
                }
            }
            return $this->_data;
        } else {
            $cache=SEFCache::getInstance();
            $urls=$cache->getCacheURLS();
            if(strlen($this->filterHitsVal)) {
                $nurls=array();
                for($i=0;$i<count($urls);$i++) {
                    switch($this->filterHitsCmp) {
                        case 0:
                            if($urls[$i]->cpt==$this->filterHitsVal) {
                                $nurls[]=$urls[$i];
                            }
                            break;
                        case 1:
                            if($urls[$i]->cpt>$this->filterHitsVal) {
                                $nurls[]=$urls[$i];
                            }
                            break;
                        case 2:
                            if($urls[$i]->cpt<$this->filterHitsVal) {    
                                $nurls[]=$urls[$i];
                            }
                            break;
                    }
                }
                $urls=$nurls;
            }
            if(strlen($this->filterItemid)) {
                $nurls=array();
                for($i=0;$i<count($urls);$i++) {
                    if($urls[$i]->Itemid==$this->filterItemid) {
                        $nurls[]=$urls[$i];
                    }
                }
                $urls=$nurls;
            }
            if(strlen($this->filterSEF)) {
                $nurls=array();
                if(substr($this->filterSEF, 0, 4) == 'reg:') {
                    $filter=substr($this->filterSEF,4);
                    for($i=0;$i<count($urls);$i++) {                    
                        if(preg_match("/".$filter."/",$urls[$i]->sefurl)) {
                            $nurls[]=$urls[$i];
                        }
                    }
                } else {
                    for($i=0;$i<count($urls);$i++) {                    
                        if(substr_count($urls[$i]->sefurl,$this->filterSEF)) {
                            $nurls[]=$urls[$i];
                        }
                    }
                }
                $urls=$nurls;
            }
            
            if(strlen($this->filterReal)) {
                $nurls=array();
                if(substr($this->filterReal, 0, 4) == 'reg:') {
                    $filter=substr($this->filterReal,4);
                    for($i=0;$i<count($urls);$i++) {
                        if(preg_match("/".$filter."/",$urls[$i]->origurl)) {
                            $nurls[]=$urls[$i];
                        }
                    }
                } else {
                    for($i=0;$i<count($urls);$i++) {
                        if(substr_count($urls[$i]->origurl,$this->filterReal)) {
                            $nurls[]=$urls[$i];
                        }
                    }
                }
                $urls=$nurls;
            }
            if(strlen($this->filterComponent)) {
                $nurls=array();
                for($i=0;$i<count($urls);$i++) {
                    if(substr_count($urls[$i]->origurl,"option=".$this->filterComponent)) {
                        $nurls[]=$urls[$i];
                    }
                }
                $urls=$nurls;
            }
            
            if (strlen($this->filterLang)) {
                $nurls = array();
                for ($i = 0; $i < count($urls); $i++) {
                    if (strpos($urls[$i]->origurl, 'lang='.$this->filterLang) !== false) {
                        $nurls[] = $urls[$i];
                    }
                }
                $urls = $nurls;
            }
            
            return $urls;
        }
    }

    function getLists()
    {
        // Make the input boxes for hits filter
        $hitsCmp[] = JHTML::_('select.option', '0', '=');
        $hitsCmp[] = JHTML::_('select.option', '1', '>');
        $hitsCmp[] = JHTML::_('select.option', '2', '<');
        $lists['hitsCmp'] = JHTML::_('select.genericlist', $hitsCmp, 'filterHitsCmp', "class=\"inputbox\" style=\"float:none; width: 50px;\" onkeydown=\"return handleKeyDown(event);\" size=\"1\"" , 'value', 'text', $this->filterHitsCmp);
        $lists['hitsVal'] = "<input type=\"text\" name=\"filterHitsVal\" value=\"{$this->filterHitsVal}\" style=\"float:none; width: 50px;\" size=\"5\" maxlength=\"10\" onkeydown=\"return handleKeyDown(event);\" />";

        // Make the input box for Itemid filter
        $lists['itemid'] = "<input type=\"text\" name=\"filterItemid\" value=\"{$this->filterItemid}\" style=\"width: 50px;\" size=\"5\" maxlength=\"10\" onkeydown=\"return handleKeyDown(event);\" />";

        // make the select list for the filter
        $viewmode[] = JHTML::_('select.option', '3', JText::_('COM_SEF_SHOW_ALL_REDIRECTS'));
        $viewmode[] = JHTML::_('select.option', '2', JText::_('COM_SEF_SHOW_CUSTOM_REDIRECTS'));
        $viewmode[] = JHTML::_('select.option', '0', JText::_('COM_SEF_SHOW_SEF_URLS'));
        $viewmode[]=JHTML::_('select.option','6',JText::_('COM_SEF_SHOW_CACHED_ITEMS'));
        $viewmode[] = JHTML::_('select.option', '4', JText::_('COM_SEF_SHOW_LINKS_TO_HOMEPAGE'));
        $viewmode[] = JHTML::_('select.option', '1', JText::_('COM_SEF_SHOW_404_LOG'));
        $viewmode[] = JHTML::_('select.option', '5', JText::_('COM_SEF_SHOW_DUPLICITIES'));
        $lists['viewmode'] = JHTML::_('select.genericlist', $viewmode, 'viewmode', "class=\"inputbox\" onchange=\"document.adminForm.submit();\" size=\"1\"" ,  'value', 'text', $this->viewmode);

        // make the select list for the component filter
        $comList[] = JHTML::_('select.option', '', JText::_('COM_SEF_ALL'));
        $rows = SEFTools::getInstalledComponents();
        foreach(array_keys($rows) as $i) {
            $row = &$rows[$i];
            $comList[] = JHTML::_('select.option', $row->option, $row->name );
        }
        $lists['comList'] = JHTML::_( 'select.genericlist', $comList, 'comFilter', "class=\"inputbox\" onchange=\"document.adminForm.submit();\" size=\"1\"", 'value', 'text', $this->filterComponent);

        // make the filter text boxes
        $lists['filterSEF']  = "<input class=\"hasTip\" type=\"text\" name=\"filterSEF\" value=\"{$this->filterSEF}\" size=\"40\" maxlength=\"255\" onkeydown=\"return handleKeyDown(event);\" title=\"".JText::_('COM_SEF_TT_FILTER_SEF')."\" />";
        $lists['filterReal'] = "<input class=\"hasTip\" type=\"text\" name=\"filterReal\" value=\"{$this->filterReal}\" size=\"40\" maxlength=\"255\" onkeydown=\"return handleKeyDown(event);\" title=\"".JText::_('COM_SEF_TT_FILTER_REAL')."\" />";
        
        $lists['filterSEFRE'] = JText::_('COM_SEF_USE_RE').'&nbsp;<input type="checkbox" style="float:none" ' . ((substr($this->filterSEF, 0, 4) == 'reg:') ? 'checked="checked"' : '') . ' onclick="useRE(this, document.adminForm.filterSEF);" />';
        $lists['filterRealRE'] = JText::_('COM_SEF_USE_RE').'&nbsp;<input type="checkbox" style="float:none" ' . ((substr($this->filterReal, 0, 4) == 'reg:') ? 'checked="checked"' : '') . ' onclick="useRE(this, document.adminForm.filterReal);" />';
        
        // Language filter
        $sefConfig = SEFConfig::getConfig();
        if ($sefConfig->langEnable) {
            $langs = JLanguageHelper::getLanguages();
            
            $langList = array();
            $langList[] = JHTML::_('select.option', '', JText::_('COM_SEF_ALL'));
            foreach ($langs as $lng) {
                $langList[] = JHTML::_('select.option', $lng->sef, $lng->title);
            }
            $lists['filterLang'] = JHTML::_('select.genericlist', $langList, 'filterLang', 'class="inputbox" onchange="document.adminForm.submit();" size="1"', 'value', 'text', $this->filterLang);
        }
        
        $lists['filterReset'] = '<input type="button" class="btn" value="'.JText::_('COM_SEF_RESET').'" onclick="resetFilters();" />';
        
        // Ordering
        $lists['filter_order'] = $this->filterOrder;
        $lists['filter_order_Dir'] = $this->filterOrderDir;
        
        // Selection
        $sel[] = JHTML::_('select.option', 'selected', JText::_('COM_SEF_ONLY_SELECTED'));
        if($this->viewmode!=6) {
            $sel[] = JHTML::_('select.option', 'filtered', JText::_('COM_SEF_ALL_FILTERED'));
        }
        $lists['selection'] = JHTML::_('select.genericlist', $sel, 'sef_selection', 'class="inputbox" size="1"');
        
        // Actions
        $acts[] = JHTML::_('select.option', 'enable', JText::_('COM_SEF_ENABLE'));
        $acts[] = JHTML::_('select.option', 'disable', JText::_('COM_SEF_DISABLE'));
        $acts[] = JHTML::_('select.option', 'sefenable', JText::_('COM_SEF_SEF'));
        $acts[] = JHTML::_('select.option', 'sefdisable', JText::_('COM_SEF_DONT_SEF'));
        if($this->viewmode!=6) {
            $acts[] = JHTML::_('select.option', 'lock', JText::_('COM_SEF_LOCK'));
            $acts[] = JHTML::_('select.option', 'unlock', JText::_('COM_SEF_UNLOCK'));
            $acts[] = JHTML::_('select.option', 'sep', '---');
            $acts[]=JHTML::_('select.option','copy_to_cache',JText::_('COM_SEF_COPY_TO_CACHE'));
            $acts[]=JHTML::_('select.option','update_urls',JText::_('COM_SEF_UPDATE_URLS'));
            $acts[]=JHTML::_('select.option','update_metas',JText::_('COM_SEF_UPDATE_META_TAGS'));
            $acts[]=JHTML::_('select.option','change_metas',JText::_('COM_SEF_CHANGE_META_TAGS'));
        }
        $acts[] = JHTML::_('select.option', 'sep', '---');
        $acts[] = JHTML::_('select.option', 'delete', JText::_('COM_SEF_DELETE'));
        if($this->viewmode!=6) {
            $acts[] = JHTML::_('select.option', 'export', JText::_('COM_SEF_EXPORT'));
        }
        $lists['actions'] = JHTML::_('select.genericlist', $acts, 'sef_actions', 'class="inputbox" size="1"');
        
        return $lists;
    }

    function getPagination()
    {
        jimport('joomla.html.pagination');
        $pagination = new JPagination($this->getTotal(), $this->limitstart, $this->limit);

        return $pagination;
    }

    function deleteFiltered()
    {
        if( $this->viewmode == 5 ) {
            // We need to get the list of duplicates IDs first
            // (MySQL can't use the same table in DELETE and SELECT subquery). Can't do this:
            // $query = "DELETE FROM `#__sefurls` WHERE ".$this->_getWhere();

            $query = "SELECT `id` FROM `#__sefurls` WHERE ".$this->_getWhere();
            $this->_db->setQuery($query);
            $ids = $this->_db->loadColumn();
            
            if( !is_array($ids) || count($ids) == 0 ) {
                return true;
            }
            
            // Now we need to use the IDs in the WHERE clause
            $query = "DELETE FROM `#__sefurls` WHERE `id` IN (" . implode(',', $ids) . ") AND `locked` = '0'";
        } else {
            $query = "DELETE FROM `#__sefurls` WHERE ".$this->_getWhere()." AND `locked` = '0'";
        }
        
        $this->_db->setQuery($query);
        if (!$this->_db->query()) {
            $this->setError( $this->_db->getErrorMsg() );
            return false;
        }

        return true;
    }
    
    function setEnabled($state, $where = '')
    {
        if($this->viewmode!=6) {
            if (empty($where)) {
                return true;
            }
            
            $cache=SEFCache::getInstance();
            
            $state = intval($state);
            $query = "UPDATE `#__sefurls` SET `enabled` = '$state' WHERE $where";
            $this->_db->setQuery( $query );
            if( !$this->_db->query() ) {
                $this->setError($this->_db->getErrorMsg());
                return false;
            }
            
            $query="SELECT sefurl FROM #__sefurls \n";
            $query.="WHERE ".$where." \n";
            $this->_db->setQuery($query);
            $urls=$this->_db->loadColumn();
            foreach($urls as $url) {
                $cache->setSEFEnabled($url,$state);
            }
        } else {
            $cid=JRequest::getVar('cid',array(),'post','array');
            $cache=SEFCache::getInstance();
            
            foreach($cid as $url) {
                $cache->setSEFEnabled($url,$state);
                
                $state = intval($state);
                $query = "UPDATE `#__sefurls` SET `enabled` = '$state' WHERE sefurl=".$this->_db->quote($url);
                $this->_db->setQuery( $query );
                $this->_db->query();
            }
        }

        return true;
    }

    function setLocked($state, $where = '')
    {
        if (empty($where)) {
            return true;
        }
        
        $state = intval($state);
        $query = "UPDATE `#__sefurls` SET `locked` = '$state' WHERE $where";
        $this->_db->setQuery( $query );
        if( !$this->_db->query() ) {
            $this->setError($this->_db->getErrorMsg());
            return false;
        }

        return true;
    }

    function setSEF($state, $where = '')
    {
        if($this->viewmode!=6) {
            if (empty($where)) {
                return true;
            }
            
            $cache=SEFCache::getInstance();
            
            $state = intval($state);
            $query = "UPDATE `#__sefurls` SET `sef` = '$state' WHERE $where";
            $this->_db->setQuery( $query );
            if( !$this->_db->query() ) {
                $this->setError($this->_db->getErrorMsg());
                return false;
            }
            
            $query="SELECT sefurl FROM #__sefurls \n";
            $query.="WHERE ".$where." \n";
            $this->_db->setQuery($query);
            $urls=$this->_db->loadColumn();
            foreach($urls as $url) {
                $cache->setSEFState($url,$state);
            }
        } else {
            $cid=JRequest::getVar('cid',array(),'post','array');
            $cache=SEFCache::getInstance();
            
            foreach($cid as $url) {
                $cache->setSEFState($url,$state);
                
                $state = intval($state);
                $query = "UPDATE `#__sefurls` SET `sef` = '$state' WHERE sefurl=".$this->_db->quote($url);
                $this->_db->setQuery( $query );
                if( !$this->_db->query() ) {
                    $this->setError($this->_db->getErrorMsg());
                    return false;
                }
            }
        }
        
        return true;
    }

    function export($where = '')
    {
        $config =& JFactory::getConfig();
        $dbprefix = $config->get('dbprefix');
        $sql_data = '';
        $filename = 'joomsef_custom_urls.sql';
        $fields = array('cpt', 'sefurl', 'origurl', 'Itemid', 'metadesc', 'metakey', 'metatitle', 'metalang', 'metarobots', 'metagoogle', 'canonicallink', 'dateadd', 'priority', 'trace', 'enabled', 'locked', 'sef', 'sm_indexed', 'sm_date', 'sm_frequency', 'sm_priority', 'host', 'showsitename');

        // Get number of records
        $query = "SELECT COUNT(*) FROM `#__sefurls`";
        if( !empty($where) ) {
            $query .= " WHERE " . $where;
        }
        $this->_db->setQuery( $query );
        $count = $this->_db->loadResult();
        if (!$count) {
            return false;
        }
        
        if( !headers_sent() ) {
            // flush the output buffer
            while( ob_get_level() > 0 ) {
                ob_end_clean();
            }

            header ('Expires: 0');
            header ('Last-Modified: '.gmdate ('D, d M Y H:i:s', time()) . ' GMT');
            header ('Pragma: public');
            header ('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            header ('Accept-Ranges: bytes');
            //header ('Content-Length: ' . strlen($sql_data));
            header ('Transfer-Encoding: chunked');
            //header ('Content-Type: application/octet-stream');
            header ('Content-Type: application/x-unknown');
            header ('Content-Disposition: attachment; filename="' . $filename . '"');
            header ('Connection: close');
        } else {
            return false;
        }
        
        $step = 200;
        $curStep = 0;
        
        $query = "SELECT * FROM `#__sefurls`";
        if( !empty($where) ) {
            $query .= " WHERE " . $where;
        }
        while ($curStep < $count) {
            $this->_db->setQuery( $query, $curStep, $step );
            $rows = $this->_db->loadObjectList();
    
            if (!empty($rows)) {
                $sql_data = '';
                foreach ($rows as $row) {
                    $values = array();
                    foreach ($fields as $field) {
                        if (isset($row->$field)) {
                            $values[] = $this->_db->Quote($row->$field);
                        } else {
                            $values[] = '\'\'';
                        }
                    }
                    $sql_data .= "INSERT INTO `{$dbprefix}sefurls` (".implode(', ', $fields).") VALUES (".implode(', ', $values).");\n";
                }
                
                // Send data chunk
                echo dechex(strlen($sql_data)) . "\r\n";
                echo $sql_data . "\r\n";
                
                $curStep += $step;
            } else {
                return false;
            }
        }
        
        echo "0\r\n";
        
        jexit();
        return true;
    }
    
    function CreateHomeLinks()
    {
        $db =& JFactory::getDBO();
        $sefConfig =& SEFConfig::getConfig();
        
        $links = array();
        
        // Create array of links for each language
        $homes = SEFTools::getHomeQueries(false);
        if (!is_array($homes) || (count($homes) == 0)) {
            return;
        }
        
        // Three cases
        if (!$sefConfig->langEnable) {
            // No languages, find home link with All languages set
            foreach ($homes as $home) {
                if ($home->language == '*') {
                    $links[''] = array('orig' => $home->link, 'Itemid' => $home->id);
                    break;
                }
            }
        }
        else if (JPluginHelper::isEnabled('system', 'falangdriver')) {
            // FaLang, find home link with All languages set and prepare links with lang= for all languages
            foreach ($homes as $home) {
                if ($home->language == '*') {
                    // Prepare link with lang=
                    $link = JoomSEF::_uriToUrl(new JURI($home->link.'&lang='));
                    
                    // Loop through languages and prepare links
                    $langs = JLanguageHelper::getLanguages('sef');
                    foreach ($langs as $sef => $lang) {
                        $links[$sef] = array('orig' => str_replace('&lang=', '&lang='.$sef, $link), 'Itemid' => $home->id);
                    }
                    break;
                }
            }
        }
        else {
            // Joomla native multilanguage, for each link with language set prepare a home link
            $langs = JLanguageHelper::getLanguages('lang_code');
            foreach ($homes as $home) {
                if ($home->language != '*') {
                    // Get SEF code for language
                    if (isset($langs[$home->language])) {
                        $sef = $langs[$home->language]->sef;
                        
                        // Prepare link with lang=
                        $link = JoomSEF::_uriToUrl(new JURI($home->link.'&lang='.$sef));
                        $links[$sef] = array('orig' => $link, 'Itemid' => $home->id);
                    }
                }
            }
        }
        
        // Store the links in database if they don't already exist
        foreach($links as $sef => $link) {
            $orig = $db->quote($link['orig']);
            $query = "SELECT `id` FROM `#__sefurls` WHERE `origurl` = {$orig} AND (`Itemid` IS NULL OR `Itemid` = '{$link['Itemid']}')";
            $db->setQuery($query);
            $id = $db->loadResult();
            if ($id) {
                continue;
            }
            
            $query = "INSERT INTO `#__sefurls` (`sefurl`, `origurl`, `Itemid`) VALUES ('{$sef}', {$orig}, '{$link['Itemid']}')";
            $db->setQuery($query);
            $db->query();
        }
    }
    
    function prepareUpdateSelected() {
        $db = JFactory::getDBO();
        $selection = JRequest::getVar('selection', 'selected', 'post');
        
        if ($selection == 'selected') {
            $where = $this->_getWhereIds();
        } else {
            $where = $this->_getWhere();
        }
        
        $sql = "UPDATE `#__sefurls` SET `flag` = IF(`locked` = '0' AND {$where}, '1', '0')";
        $db->setQuery($sql);
        if (!$db->query()) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Prepares the database and configuration for SEF URLs update
     */
    function prepareUpdate()
    {
        $db = JFactory::getDBO();
        
        $sql = "UPDATE `#__sefurls` SET `flag` = IF(`dateadd` = '0000-00-00' AND `locked` = '0', '1', '0')";
        $db->setQuery($sql);
        if (!$db->query()) {
            return false;
        }
        
        return true;
    }
    
    function getUrlsToUpdate()
    {
        $db = JFactory::getDBO();
        $db->setQuery("SELECT COUNT(`id`) FROM `#__sefurls` WHERE `locked` = '0' AND `flag` = '1'");
        $count = $db->loadResult();
        
        return $count;
    }
    
    function getIds() {
        $db=JFactory::getDBO();
        $selection = JRequest::getVar('selection', 'selected', 'post');
        
        $query="SELECT id \n";
        $query.="FROM #__sefurls \n";
        $query.="WHERE locked=0 AND \n";
        if($selection=='selected') {
            $query.=$this->_getWhereIds();
        } else {
            $query.=$this->_getWhere();
        }
        
        $db->setQuery($query);
        return $db->loadColumn();
    }
    
    function saveChangedMetas() {
        $db=JFactory::getDBO();
        $cid=explode(",",JRequest::getString('ids'));
        $metadata=JRequest::getVar('meta',array(),'post','array');
        $cache=SEFCache::getInstance();
        
        if (is_array($metadata) && count($metadata) > 0) {
            $metas=array();
            foreach($metadata as $metakey => $metaval) {
                if(strlen($metaval)) {
                    $metas []= "`$metakey`="."'".str_replace(array("\\", "'", ';'), array("\\\\", "\\'", "\\;"), $metaval)."'";
                }
            }
        }
        
        if(count($metas)) {
            $query="UPDATE #__sefurls SET ".implode(",",$metas)." \n";
            $query.="WHERE id IN(".implode(",",$cid).")";
            $db->setQUery($query);
            if(!$db->query()) {
                $this->setError($db->stderr(true));
                return false;
            }
        }
        
        $metas=array();
        foreach($metadata as $metakey => $metaval) {
            if(strlen($metaval)) {
                $metas [$metakey]= $metaval;
            }
        }
        
        $query="SELECT sefurl \n";
        $query.="FROM #__sefurls \n";
        $query.="WHERE id IN(".implode(",",$cid).")";
        $db->setQuery($query);
        $urls=$db->loadColumn();
        
        foreach($urls as $url) {
            $cache->updateMetas($url,$metas);
        }
        return true;
    }
    
    function updatePageRank() {
        $db=JFactory::getDBO();
        
        $query="SELECT sefurl \n";
        $query.="FROM #__sefurls \n";
        $db->setQuery($query);
        $urls=$db->loadColumn();
        
        foreach($urls as $url) {
            
        }
    }
}
?>