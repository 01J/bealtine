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

class SefViewUpgrade extends SefView
{
	function __construct($config = null)
	{
		parent::__construct($config);
		//$this->_addPath('template', $this->_basePath.'/views/templates');
	}

	function display($tpl = null)
	{
		JToolBarHelper::title( JText::_( 'COM_SEF_JOOMSEF' ).' - '.JText::_('COM_SEF_UPGRADE_MANAGER'), 'update.png' );
		
		JToolBarHelper::back('COM_SEF_BACK', 'index.php?option=com_sef');

		$exts = $this->get('UpgradeExts');
		$this->assignRef('extensions', $exts);
		
		$oldVer = SEFTools::getSEFVersion();
		$this->assignRef('oldVer', $oldVer);
		
		$newVer = $this->get('newSEFVersion');
		$this->assignRef('newVer', $newVer);
		
		$regInfo = $this->get('RegisteredInfo');
		$this->assignRef('regInfo', $regInfo);
		
		$isPaidVersion = $this->get('IsPaidVersion');
		$this->assignRef('isPaidVersion', $isPaidVersion);
		
        JHTML::_('behavior.tooltip');
        JHTML::_('behavior.modal');
        
		parent::display($tpl);
	}

	function showMessage()
	{
	    JToolBarHelper::title( JText::_( 'JoomSEF' ).' '.JText::_('COM_SEF_UPGRADE_MANAGER'), 'update.png' );
	    
        $url = 'index.php?option=com_sef&task=showupgrade';
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
