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

require_once (JPATH_ADMINISTRATOR.'/components/com_sef/model.php');

/**
 * Used to easily access the log function
 */
if (!class_exists('JoomSefLogger')) {
    class JoomSefLogger
    {
        function Log($msg, $sefExt, $component = null, $page = null)
        {
            static $model;
            
            if (is_null($model))
            {
                $model = SEFModel::getInstance('logger', 'SEFModel');
            }
            
            $url = '';
            if (!is_null($sefExt)) {
                if (isset($sefExt->currentUri)) {
                    $url = $sefExt->currentUri;
                }
            }
            
            $model->addLog($msg, $url, $component, $page);
        }
    }
}

if (!class_exists('SEFModelLogger')) {
    class SEFModelLogger extends SEFModel
    {
        function __construct()
        {
            parent::__construct();
            $this->_getVars();
        }
        
        function _getVars()
        {
            $app =& JFactory::getApplication();
            
            $this->filterMessage = $app->getUserStateFromRequest("sef.logger.filterMessage", 'filterMessage', '');
            $this->filterComponent = $app->getUserStateFromRequest("sef.logger.filterComponent", 'comFilter', '');
            $this->filterPage = $app->getUserStateFromRequest("sef.logger.filterPage", 'filterPage', '');
            $this->filterUrl = $app->getUserStateFromRequest("sef.logger.filterUrl", 'filterUrl', '');
            $this->filterOrder = $app->getUserStateFromRequest('sef.logger.filter_order', 'filter_order', 'time');
            $this->filterOrderDir = $app->getUserStateFromRequest('sef.logger.filter_order_Dir', 'filter_order_Dir', 'desc');
            
            $this->limit		= $app->getUserStateFromRequest('global.list.limit', 'limit', $app->getCfg('list_limit'), 'int');
            $this->limitstart	= $app->getUserStateFromRequest('sef.logger.limitstart', 'limitstart', 0, 'int');
    
            // in case limit has been changed, adjust limitstart accordingly
            $this->limitstart = ($this->limit != 0 ? (floor($this->limitstart / $this->limit) * $this->limit) : 0);
    
            // enabled?
            $config =& SEFConfig::getConfig();
            $this->enabled = $config->logErrors;
        }
        
        function _buildQuery()
        {
            $limit = '';
            if (($this->limit != 0) || ($this->limitstart != 0)) {
                $limit = " LIMIT {$this->limitstart},{$this->limit}";
            }
    
            $query = "SELECT * FROM `#__seflog`".$this->_getWhere()." ORDER BY ".$this->_getSort().$limit;
    
            return $query;
        }
    
        function _getSort()
        {
            if (!isset($this->_sort)) {
                $this->_sort = '`' . $this->filterOrder . '` ' . $this->filterOrderDir;
            }
    
            return $this->_sort;
        }
        
        function _getWhere()
        {
            if (empty($this->_where)) {
                $db =& JFactory::getDBO();
                $where = array();
                
                // Filter message
                if ($this->filterMessage != '') {
                    $val = $db->quote('%'.$this->filterMessage.'%');
                    $where[] = "(`message` LIKE {$val})";
                }
                
                // Filter component
                if ($this->filterComponent != '') {
                    if ($this->filterComponent == '-') {
                        // Empty component
                        $where[] = "(`component` IS NULL)";
                    }
                    else {
                        $val = $db->quote($this->filterComponent);
                        $where[] = "(`component` = {$val})";
                    }
                }
                
                // Filter URL
                if ($this->filterUrl != '') {
                    $val = $db->quote('%'.$this->filterUrl.'%');
                    $where[] = "(`url` LIKE {$val})";
                }
                
                // Filter page
                if ($this->filterPage != '') {
                    $val = $db->quote('%'.$this->filterPage.'%');
                    $where[] = "(`page` LIKE {$val})";
                }
                
                if (count($where) > 0) {
                    $this->_where = ' WHERE '.implode(' AND ', $where);
                }
                else {
                    $this->_where = '';
                }
            }
            
            return $this->_where;
        }
    
        function getTotal()
        {
            if (!isset($this->_total)) {
                $this->_db->setQuery("SELECT COUNT(*) FROM `#__seflog`".$this->_getWhere());
                $this->_total = $this->_db->loadResult();
            }
    
            return $this->_total;
        }
    
        function getData()
        {
            if (empty($this->_data))
            {
                $query = $this->_buildQuery();
                $this->_data = $this->_getList($query);
            }
            
            return $this->_data;
        }
        
        function getEnabled()
        {
            return $this->enabled;
        }
        
        function getLists()
        {
            // make the select list for the component filter
            $comList[] = JHTML::_('select.option', '', JText::_('COM_SEF_ALL'));
            $comList[] = JHTML::_('select.option', '-', JText::_('COM_SEF_LOGS_EMPTY'));
            $rows = SEFTools::getInstalledComponents();
            foreach(array_keys($rows) as $i) {
                $row = &$rows[$i];
                $comList[] = JHTML::_('select.option', $row->option, $row->name );
            }
            $lists['comList'] = JHTML::_( 'select.genericlist', $comList, 'comFilter', "class=\"inputbox\" onchange=\"document.adminForm.submit();\" size=\"1\"", 'value', 'text', $this->filterComponent);
            
            // make the filter text boxes
            $lists['filterMessage']  = "<input type=\"text\" name=\"filterMessage\" value=\"{$this->filterMessage}\" size=\"40\" maxlength=\"255\" onkeydown=\"return handleKeyDown(event);\" />";
            $lists['filterPage'] = "<input type=\"text\" name=\"filterPage\" value=\"{$this->filterPage}\" size=\"40\" maxlength=\"255\" onkeydown=\"return handleKeyDown(event);\" />";
            $lists['filterUrl'] = "<input type=\"text\" name=\"filterUrl\" value=\"{$this->filterUrl}\" size=\"40\" maxlength=\"255\" onkeydown=\"return handleKeyDown(event);\" />";
            
            // Reset button
            $lists['filterReset'] = '<input type="button" value="'.JText::_('COM_SEF_RESET').'" onclick="resetFilters();" />';
            
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
    
        function addLog($msg, $url = '', $component = null, $page = null)
        {
            if (!$this->enabled) {
                // Log disabled
                return;
            }
            
            $db =& JFactory::getDBO();
            
            if (is_null($component)) {
                $component = 'NULL';
            }
            else {
                $component = $db->quote($component);
            }
            
            if (is_null($page)) {
                $uri = JURI::getInstance();
                $page = $uri->toString();
            }
            
            $msg = $db->quote($msg);
            $page = $db->quote($page);
            $url = $db->quote($url);
            $query = "INSERT INTO `#__seflog` (`time`, `message`, `url`, `component`, `page`) VALUES (NOW(), {$msg}, {$url}, {$component}, {$page})";
            $db->setQuery($query);
            $db->query();
        }
        
        function clearLogs()
        {
            $db =& JFactory::getDBO();
            
            $db->setQuery("DELETE FROM `#__seflog`");
            if (!$db->query()) {
                return false;
            }
            else {
                return true;
            }
        }
    }
}