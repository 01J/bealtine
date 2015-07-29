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

class SefViewExtensions extends SefView
{
	/*function __construct($config = null)
	{
		parent::__construct($config);
		$this->_addPath('template', $this->_basePath.'/views/templates');
	}*/

	function display($tpl = null)
	{
		JToolBarHelper::title('JoomSEF - '. JText::_('COM_SEF_EXTENSIONS_MANAGEMENT'), 'plugin.png' );

		$bar = & JToolBar::getInstance();
		JToolBarHelper::custom('installext', 'install', '', 'COM_SEF_INSTALL', false);
		//$bar->appendButton('Confirm', 'COM_SEF_CONFIRM_UNINSTALL_EXTENSION', 'uninstall', 'COM_SEF_UNINSTALL', 'uninstallext', true, false);
		JToolBarHelper::editList('editext');
		JToolBarHelper::spacer();
		JToolBarHelper::back('COM_SEF_BACK', 'index.php?option=com_sef');

		$exts = $this->get('extensions', 'extensions');
		$this->assignRef('extensions', $exts);

		$noExts = $this->get('ComponentsWithoutExtension', 'extensions');
		$this->assignRef('components', $noExts);

        JHTML::_('behavior.tooltip');
        JHTML::_('behavior.modal');
        JHTML::_('behavior.framework');

		parent::display($tpl);
	}

}
