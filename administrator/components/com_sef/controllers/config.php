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

class SEFControllerConfig extends SEFController
{
    function __construct()
    {
        parent::__construct();
        
        $this->registerTask('apply', 'save');
    }

    function edit()
    {

        JRequest::setVar( 'view', 'config' );

        parent::display();
    }

    function save()
    {
        $model = $this->getModel('config');

        if ($model->store()) {
            $msg = JText::_('COM_SEF_CONFIGURATION_UPDATED').' - '.JText::_('COM_SEF_INFO_CONFIG_UPDATE');
        } else {
        	$err = $model->getError();
            $msg = JText::_('COM_SEF_ERROR_WRITING_CONFIG').": ".$model->getError();
        }
        
        $task = JRequest::getCmd('task');
        if( $task == 'save' ) {
            $link = 'index.php?option=com_sef';
        }
        elseif( $task == 'apply' ) {
            $link = 'index.php?option=com_sef&controller=config&task=edit';
        }
                    
        $this->setRedirect($link, $msg);
    }

    function cancel()
    {
        $this->setRedirect( 'index.php?option=com_sef' );
    }
    
    function setinfotext()
    {
        // Get new state
        $state = JRequest::getVar('state');
        if (is_null($state)) {
            jexit();
        }
        
        $sefConfig =& SEFConfig::getConfig();
        $sefConfig->showInfoTexts = ($state ? true : false);
        $sefConfig->saveConfig(0);
        
        jexit();
    }
    
    function disable_plugin()
    {
        $obj = new stdClass();
        $obj->success = true;
        
        // Disable the Language Filter plugin
        $db = JFactory::getDbo();
        $db->setQuery("UPDATE `#__extensions` SET `enabled` = '0' WHERE `type` = 'plugin' AND `element` = 'languagefilter' AND `folder` = 'system'");
        if (!$db->query()) {
            $obj->success = false;
        }
        
        echo json_encode($obj);
        jexit();
    }
}
?>