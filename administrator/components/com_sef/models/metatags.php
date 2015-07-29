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

class SEFModelMetaTags extends SEFModel
{
    function __construct()
    {
        parent::__construct();
        $this->_getVars();
    }

    function _getVars()
    {
        $mainframe =& JFactory::getApplication();

        $this->filterComponent = $mainframe->getUserStateFromRequest("sef.metatags.comFilter", 'comFilter', '');
        $this->filterSEF = $mainframe->getUserStateFromRequest("sef.metatags.filterSEF", 'filterSEF', '');
        $this->filterReal = $mainframe->getUserStateFromRequest("sef.metatags.filterReal", 'filterReal', '');
        $this->filterLang = $mainframe->getUserStateFromRequest('sef.metatags.filterLang', 'filterLang', '');
        $this->filterTitle = $mainframe->getUserStateFromRequest("sef.metatags.filterTitle", 'filterTitle', 0);
        $this->filterDesc = $mainframe->getUserStateFromRequest("sef.metatags.filterDesc", 'filterDesc', 0);
        $this->filterKeys = $mainframe->getUserStateFromRequest("sef.metatags.filterKeys", 'filterKeys', 0);
        $this->filterOrder = $mainframe->getUserStateFromRequest('sef.metatags.filter_order', 'filter_order', 'sefurl');
        $this->filterOrderDir = $mainframe->getUserStateFromRequest('sef.metatags.filter_order_Dir', 'filter_order_Dir', 'asc');

        $this->limit		= $mainframe->getUserStateFromRequest('global.list.limit', 'limit', $mainframe->getCfg('list_limit'), 'int');
        $this->limitstart	= $mainframe->getUserStateFromRequest('sef.metatags.limitstart', 'limitstart', 0, 'int');

        // In case limit has been changed, adjust limitstart accordingly
        $this->limitstart = ( $this->limit != 0 ? (floor($this->limitstart / $this->limit) * $this->limit) : 0 );
    }

    /**
     * Returns the query
     * @return string The query to be used to retrieve the rows from the database
     */
    function _buildQuery()
    {
        $limit = '';
        if( ($this->limit != 0) || ($this->limitstart != 0) ) {
            $limit = " LIMIT {$this->limitstart},{$this->limit}";
        }

        $query = "SELECT * FROM `#__sefurls` ".$this->_getWhere()." ORDER BY ".$this->_getSort().$limit;

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
        	$config=SEFConfig::getConfig();
            $where = "`origurl` != '' ";
            $db =& JFactory::getDBO();
			
            // filter URLs
            if ($this->filterComponent != '') {
                $where .= "AND (`origurl` LIKE '%option={$this->filterComponent}&%' OR `origurl` LIKE '%option={$this->filterComponent}') ";
            }
            if ($this->filterLang != '' && $this->filterLang!=$config->mainLanguageJoomla) {
                $where .= "AND (`origurl` LIKE '%lang={$this->filterLang}%') ";
            } else if($this->filterLang==$config->mainLanguageJoomla)  {
                 $where .= "AND (`origurl` NOT LIKE '%lang=%' OR `origurl` LIKE '%lang={$this->filterLang}%')"; 
            }
            
            if ($this->filterSEF != '') {
                if( substr($this->filterSEF, 0, 4) == 'reg:' ) {
                    $val = substr($this->filterSEF, 4);
                    if( $val != '' ) {
                        // Regular expression search
                        $val = $db->Quote($val);
                        $where .= "AND `sefurl` REGEXP $val ";
                    }
                }
                else {
                    $val = $db->Quote('%'.$this->filterSEF.'%');
                    $where .= "AND `sefurl` LIKE $val ";
                }
            }
            if ($this->filterReal != '') {
                if( substr($this->filterReal, 0, 4) == 'reg:' ) {
                    $val = substr($this->filterReal, 4);
                    if( $val != '' ) {
                        // Regular expression search
                        $val = $db->Quote($val);
                        $where .= "AND `origurl` REGEXP $val ";
                    }
                }
                else {
                    $val = $db->Quote('%'.$this->filterReal.'%');
                    $where .= "AND `origurl` LIKE $val ";
                }
            }
            
            // filter meta tags
            if ($this->filterTitle != 0) {
                if ($this->filterTitle == 1) {
                    $where .= "AND `metatitle` = '' ";
                }
                elseif ($this->filterTitle == 2) {
                    $where .= "AND `metatitle` != ''";
                }
            }
            if ($this->filterDesc != 0) {
                if ($this->filterDesc == 1) {
                    $where .= "AND `metadesc` = '' ";
                }
                elseif ($this->filterDesc == 2) {
                    $where .= "AND `metadesc` != ''";
                }
            }
            if ($this->filterKeys != 0) {
                if ($this->filterKeys == 1) {
                    $where .= "AND `metakey` = '' ";
                }
                elseif ($this->filterKeys == 2) {
                    $where .= "AND `metakey` != ''";
                }
            }

            if( !empty($where) ) {
                $where = "WHERE " . $where;
            }
            
            $this->_where = $where;
        }

        return $this->_where;
    }

    function getTotal()
    {
        if( !isset($this->_total) )
        {
            $this->_db->setQuery("SELECT COUNT(*) FROM `#__sefurls` ".$this->_getWhere());
            $this->_total = $this->_db->loadResult();
        }

        return $this->_total;
    }

    /**
     * Retrieves the data
     */
    function getData()
    {
        // Lets load the data if it doesn't already exist
        if (empty( $this->_data ))
        {
            $query = $this->_buildQuery();
            $this->_data = $this->_getList( $query );
        }

        return $this->_data;
    }

    function getLists()
    {
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
        
        // Filter meta tags
        $metas[] = JHTML::_('select.option', 0, JText::_('COM_SEF_ALL'));
        $metas[] = JHTML::_('select.option', 1, JText::_('COM_SEF_EMPTY'));
        $metas[] = JHTML::_('select.option', 2, JText::_('COM_SEF_FILLED'));
        $lists['filterTitle'] = JHTML::_('select.genericlist', $metas, 'filterTitle', 'class="inputbox" onchange="document.adminForm.submit();" style="width: 120px;" size="1"', 'value', 'text', $this->filterTitle);
        $lists['filterDesc'] = JHTML::_('select.genericlist', $metas, 'filterDesc', 'class="inputbox" onchange="document.adminForm.submit();" style="width: 120px;" size="1"', 'value', 'text', $this->filterDesc);
        $lists['filterKeys'] = JHTML::_('select.genericlist', $metas, 'filterKeys', 'class="inputbox" onchange="document.adminForm.submit();" style="width: 120px;" size="1"', 'value', 'text', $this->filterKeys);
        
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

        return $lists;
    }

    function getPagination()
    {
        jimport('joomla.html.pagination');
        $pagination = new JPagination($this->getTotal(), $this->limitstart, $this->limit);

        return $pagination;
    }
    
    function store()
    {
        $ids = JRequest::getVar('id');
        $metatitle = JRequest::getVar('metatitle');
        $metadesc = JRequest::getVar('metadesc');
        $metakey = JRequest::getVar('metakey');
        
        if (is_array($ids)) {
            foreach ($ids as $id) {
                if (!is_numeric($id)) {
                    continue;
                }
                
                $title = isset($metatitle[$id]) ? $metatitle[$id] : '';
                $desc = isset($metadesc[$id]) ? $metadesc[$id] : '';
                $key = isset($metakey[$id]) ? $metakey[$id] : '';
                
                // cleanup
                $title = str_replace(array("\n", "\r"), '', $title);
                $desc = str_replace(array("\n", "\r"), '', $desc);
                $key = str_replace(array("\n", "\r"), '', $key);
                
                $date = date('Y-m-d');
                $query = "UPDATE `#__sefurls` SET `dateadd` = '{$date}', `metatitle` = ".$this->_db->Quote($title).", `metadesc` = ".$this->_db->Quote($desc).", `metakey` = ".$this->_db->Quote($key)." WHERE `id` = '{$id}' LIMIT 1";
                $this->_db->setQuery($query);
                
                if (!$this->_db->query()) {
                    $this->setError($this->_db->getErrorMsg());
                    return false;
                }
            }
        }
        
        return true;
    }
}
?>