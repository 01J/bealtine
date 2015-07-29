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
 
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.view');

class SefViewStatistics extends SefView {
	function display($tpl=null) {
        $this->setLayout(JRequest::getCmd('layout','default'));
        
		$icon = 'statistics.png';
            JToolbarHelper::title(JText::_('COM_SEF_STATISTICS'), $icon);
            JToolBarHelper::back('COM_SEF_BACK', 'index.php?option=com_sef');
		
		parent::display($tpl);

	}
	
}
?>