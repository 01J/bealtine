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

class SEFControllerURLs extends SEFController
{
    function __construct()
    {
        parent::__construct();
    }

    function purge()
    {
        $confirmed = JRequest::getVar('confirmed', '0');

        if( $confirmed == '0' ) {
            JRequest::setVar('view', 'urls');
            JRequest::setVar('layout', 'confirm');
        } else {
            $model =& $this->getModel('urls');
            if( $model->purge() ) {
                $this->cleanCache();
                $this->setRedirect('index.php?option=com_sef', JText::_('COM_SEF_SUCCESSFULLY_PURGED_RECORDS'));
            } else {
                $this->setRedirect('index.php?option=com_sef', JText::_('COM_SEF_COULD_NOT_PURGE_RECORDS'));
            }
        }

        parent::display();
    }

    function cleanCache()
    {
        require_once(JPATH_ROOT.'/components/com_sef/sef.cache.php');
        $cache =& sefCache::getInstance();
        $cache->cleanCache();
    }

}
?>