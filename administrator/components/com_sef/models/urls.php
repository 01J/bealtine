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

jimport('joomla.application.component.model');
JLoader::register('SEFCache', JPATH_ROOT.'/components/com_sef/sef.cache.php');

class SEFModelURLs extends SEFModel
{
    function __construct()
    {
        parent::__construct();
    }
    
    function purge()
    {
        if( $this->_getTableWhere($table, $where) === false ) {
            return false;
        }
        
        $db =& JFactory::getDBO();
        $sql = "DELETE FROM $table" . (!empty($where) ? " WHERE $where" : '');
        $db->setQuery($sql);
        
        return $db->query();
    }
    
    /**
     * 0 - SEF
     * 1 - 404
     * 2 - Custom
     * 3 - Moved
     * 4 - Disabled
     * 5 - Not SEFed
     * 6 - Locked
     * 7 - Cached
     *
     * @param int $type
     * @return int
     */
    function getCount($type = null)
    {
        if( $this->_getTableWhere($table, $where, $type) === false ) {
            return 0;
        }
        
        $db =& JFactory::getDBO();
        $sql = "SELECT COUNT(*) FROM $table" . (!empty($where) ? " WHERE $where" : '');
        $db->setQuery($sql);
        $this->_count = $db->loadResult();
        
        return $this->_count;
    }
    
    function getStatistics()
    {
        $sefConfig =& SEFConfig::getConfig();
        
        $stats = array();
        
        $stat = new stdClass();
        $stat->text = JText::_('COM_SEF_AUTOMATIC_SEF_URLS');
        $stat->value = $this->getCount(0);
        $stat->link = 'index.php?option=com_sef&controller=sefurls&viewmode=0';
        $stats[] = $stat;
        
        $stat = new stdClass();
        $stat->text = JText::_('COM_SEF_CUSTOM_SEF_URLS');
        $stat->value = $this->getCount(2);
        $stat->link = 'index.php?option=com_sef&controller=sefurls&viewmode=2';
        $stats[] = $stat;
        
        $stat = new stdClass();
        $stat->text = JText::_('COM_SEF_404_URLS');
        $stat->value = $this->getCount(1);
        $stat->link = 'index.php?option=com_sef&controller=sefurls&viewmode=1';
        $stats[] = $stat;
        
        $stat = new stdClass();
        $stat->text = JText::_('COM_SEF_MOVED_URLS');
        $stat->value = $this->getCount(3);
        $stat->link = 'index.php?option=com_sef&controller=movedurls';
        $stats[] = $stat;
        
        $stat = new stdClass();
        $stat->text = JText::_('COM_SEF_TOTAL_URLS');
        $stat->value = $stats[0]->value + $stats[1]->value + $stats[2]->value + $stats[3]->value;
        $stats[] = $stat;
        
        $stat = new stdClass();
        $stat->text = '';
        $stats[] = $stat;
        
        $stat = new stdClass();
        $stat->text = JText::_('COM_SEF_DISABLED_URLS');
        $stat->value = $this->getCount(4);
        $stats[] = $stat;
        
        $stat = new stdClass();
        $stat->text = JText::_('COM_SEF_NOT_SEFED_URLS');
        $stat->value = $this->getCount(5);
        $stats[] = $stat;
        
        $stat = new stdClass();
        $stat->text = JText::_('COM_SEF_LOCKED_URLS');
        $stat->value = $this->getCount(6);
        $stats[] = $stat;
        
        $stat = new stdClass();
        $stat->text = JText::_('COM_SEF_CACHE_ENTRIES');
        
        if ($sefConfig->useCache) {
            $cache =& sefCache::getInstance();
            $stat->value = $cache->getCount();
        } else {
            $stat->value = JText::_('COM_SEF_CACHE_DISABLED');
        }
        $stats[] = $stat;
        
        $stat = new stdClass();
        $stat->text = '';
        $stats[] = $stat;
        
        $stat = new stdClass();
        $stat->text = JText::_('COM_SEF_ERRORS_LOGGED');
        $stat->value = $this->getCount(7);
        $stat->link = 'index.php?option=com_sef&controller=logger';
        $stats[] = $stat;
        
        return $stats;
    }
    
    function _getTableWhere(&$table, &$where, $type = null)
    {
        if (is_null($type)) {
            $type = JRequest::getInt('type', null);
            if (!is_null($type) && (($type < 0) || ($type > 3))) {
                // Can purge only types 0 - 3
                $type = null;
            }
        }
        if( is_null($type) ) {
            return false;
        }
        
        if( ($type >= 0) && ($type <= 2) ) {
            $table = '`#__sefurls`';
            if( $type == 0 ) {
                // Automatic SEF
                $where = "`dateadd` = '0000-00-00' AND `locked` = '0'";
            }
            elseif( $type == 1 ) {
                // 404
                $where = "`dateadd` > '0000-00-00' and `origurl` = '' AND `locked` = '0'";
            }
            elseif( $type == 2 ) {
                // Custom
                $where = "`dateadd` > '0000-00-00' and `origurl` != '' AND `locked` = '0'";
            }
        } elseif ( $type == 3 ) {
            // Moved
            $table = '`#__sefmoved`';
            $where = '';
        } elseif (($type >= 4) && ($type <= 6)) {
            $table = '`#__sefurls`';
            if ($type == 4) {
                // Disabled
                $where = "`enabled` = '0'";
            }
            elseif ($type == 5) {
                // Not SEFed
                $where = "`sef` = '0'";
            }
            elseif ($type == 6) {
                // Locked
                $where = "`locked` = '1'";
            }
        } elseif ($type == 7) {
            // Errors logged
            $table = '`#__seflog`';
            $where = '';
        } else {
            return false;
        }
        
        return true;
    }

}
?>