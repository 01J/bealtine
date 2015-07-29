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

class SefViewInfo extends SefView
{

	function display($tpl = null)
	{
		$task = JRequest::getVar('task');
	
		if ($task == 'help') {
		    $title = JText::_('COM_SEF_JOOMSEF_SUPPORT');
		    $icon = 'help.png';
		}
		elseif ($task == 'doc') {
		    $title = JText::_('COM_SEF_JOOMSEF_DOCUMENTATION');
		    $icon = 'docs.png';
		}
		elseif ($task == 'changelog') {
		    $title = JText::_('COM_SEF_JOOMSEF_CHANGELOG');
		    $icon = 'info.png';
		}
		else {
		    $title = JText::_('COM_SEF_JOOMSEF');
		    $icon = 'artio.png';
		}
		
		JToolBarHelper::title($title, $icon);		
		JToolBarHelper::back('COM_SEF_BACK', 'index.php?option=com_sef');

		parent::display($tpl);
	}

}
