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

jimport( 'joomla.application.component.view' );

class SefViewInstall extends SefView
{
	function __construct($config = null)
	{
		parent::__construct($config);
		$this->_addPath('template', $this->_basePath.'/views/templates');
	}

	function display($tpl = null)
	{
		JToolBarHelper::title( JText::_( 'COM_SEF_INSTALL' ).' '.JText::_('COM_SEF_SEF_EXTENSION'), 'plugin.png' );
		
		$bar = & JToolBar::getInstance();
		$bar->appendButton('Confirm', 'COM_SEF_CONFIRM_UNINSTALL_EXTENSION', 'uninstall', 'COM_SEF_UNINSTALL', 'uninstallext', true, false);
		JToolBarHelper::spacer();
		JToolBarHelper::back('COM_SEF_BACK', 'index.php?option=com_sef&controller=extension');
		
		$exts = $this->get('extensions', 'extensions');
		$this->assignRef('extensions', $exts);
		
		// Check that the sef_ext directory is writable
		if( !is_writable(JPATH_ROOT.'/components/com_sef/sef_ext') ) {
		    JError::raiseWarning(100, JText::_('COM_SEF_ERROR_EXTENSIONS_DIRECTORY'));
		}

        JHTML::_('behavior.tooltip');
        
		parent::display($tpl);
	}
	
	function showMessage()
	{
	    JToolBarHelper::title( JText::_( 'Install' ).' '.JText::_('COM_SEF_SEF_EXTENSION'), 'plugin.png' );
	    
        $url = 'index.php?option=com_sef&task=installext';
        $redir = JRequest::getVar('redirto', null, 'post');
        if( !is_null($redir) ) {
            $url = 'index.php?option=com_sef&'.$redir;
        }
	    JToolBarHelper::back('Continue', $url);
	    
	    $this->assign('url', $url);
	    
	    $this->setLayout('message');
	    parent::display();
	}
}
