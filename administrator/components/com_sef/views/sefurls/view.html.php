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

class SefViewSefurls extends SefView
{
	function display($tpl = null)
	{
	    $mainframe =& JFactory::getApplication();
	    $viewmode = $mainframe->getUserStateFromRequest('sef.sefurls.viewmode', 'viewmode', 0);
	    if ($viewmode == 2) {
	        $icon = 'url-user.png';
	    }
	    else if( $viewmode == 1 ) {
	        $icon = '404-logs.png';
	    }
	    else {
	        $icon = 'url-edit.png';
	    }
		JToolBarHelper::title(JText::_('COM_SEF_JOOMSEF_URL_MANAGER'), $icon);
		
        $this->assign($this->getModel());
        $lists =& $this->get('Lists');
        
		$bar =& JToolBar::getInstance();
		
		// Actions
		$bar->appendButton('Custom', $lists['selection']);
		$bar->appendButton('Custom', $lists['actions']);
		$bar->appendButton('Custom', '<input type="button" class="btn" value="'.JText::_('COM_SEF_PROCEED').'" onclick="doAction();" />');
		JToolBarHelper::divider();
		
		if($viewmode!=6) {
			JToolBarHelper::addNew();
		}
		if ($this->viewmode == 1) {
		    // 404 log
		    JToolBarHelper::addNew('create301', 'COM_SEF_CREATE_301');
		}
		if($viewmode!=6) {
			JToolBarHelper::editList();
			JToolBarHelper::spacer();
			JToolBarHelper::custom('showimport', 'import', '', 'COM_SEF_IMPORT', false);
			JToolBarHelper::spacer();
		}
		JToolBarHelper::back('COM_SEF_BACK', 'index.php?option=com_sef');
		
		// Get data from the model
        $this->assignRef('items', $this->get('Data'));
        $this->assignRef('total', $this->get('Total'));
        $this->assignRef('lists', $lists);
        if($viewmode!=6) {
        	$this->assignRef('pagination', $this->get('Pagination'));
        }
        
        JHtml::_('behavior.tooltip');
        
		parent::display($tpl);
	}

    function showUpdate($controller = '')
    {
        JToolBarHelper::title( JText::_('COM_SEF_JOOMSEF_URLS_UPDATE'), 'url-update.png' );
        
        $this->setLayout('update');
        $this->assign('totalUrls', $this->get('UrlsToUpdate'));
        $this->assign('controllerVar', $controller);
        
        JHTML::_('behavior.framework');
        
        parent::display();
    }

    function showUpdateMeta($controller = '')
    {
        JToolBarHelper::title( JText::_('COM_SEF_JOOMSEF_META_TAGS_UPDATE'), 'url-update.png' );
        
        $this->setLayout('updatemeta');
        $this->assign('totalUrls', $this->get('UrlsToUpdate'));
        $this->assign('controllerVar', $controller);
        
        JHTML::_('behavior.framework');
        
        parent::display();
    }
    
    
    function showChangeMeta() {
 		JToolbarHelper::title(JText::_('COM_SEF_JOOMSEF_META_TAGS_CHANGE'));
 		JToolbarHelper::save('save_changed_metas');
 		JToolbarHelper::cancel('cancel_changed_metas');
 		
 		$this->setLayout('changemeta');
 		$this->cid=$this->get('ids');
 		
 		JHTML::_('behavior.framework');
 		
 		parent::display();   	
    }
}
