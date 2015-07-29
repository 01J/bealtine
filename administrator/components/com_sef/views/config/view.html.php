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

class SefViewConfig extends SefView
{

	function display($tpl = null)
	{
		JToolBarHelper::title( JText::_('COM_SEF_JOOMSEF_CONFIGURATION'), 'config.png' );
		
		JToolBarHelper::save();
		JToolBarHelper::apply();
		JToolBarHelper::spacer();
		JToolBarHelper::cancel();
		
		// Get data from the model
		$lists = & $this->get('Lists');

		$this->assignRef('lists', $lists);
		$this->langs=$this->get('langs');
		$this->subdomains=$this->get('subdomains');
		
        // Which tabs to show?
        $sefConfig =& SEFConfig::getConfig();
        $tabs = array('basic');
        if ($sefConfig->professionalMode) {
            $tabs[] = 'advanced';
        }
        $tabs[] = 'cache';
        $tabs[] = 'metatags';
        $tabs[] = 'seo';
        $tabs[] = 'sitemap';
        $tabs[] = 'language';
        $tabs[] = 'analytics';
        $tabs[] = 'subdomains';
        $tabs[] = '404';
        $tabs[] = 'registration';
        
		$tab = JRequest::getVar('tab', 'basic');
		$tabIdx = array_search($tab, $tabs);
        if ($tabIdx === false) {
            $tabIdx = 0;
        }
        $this->assignRef('tabs', $tabs);
		$this->assign('tab', $tabIdx);
        
        // Root domain for subdomains configuration
        $rootDomain = JFactory::getURI()->getHost();
        if (substr($rootDomain, 0, 4) == 'www.') {
            $rootDomain = substr($rootDomain, 4);
        }
        $this->assign('rootDomain', $rootDomain);
        
		JHTML::_('behavior.tooltip');
		JHTML::_('behavior.framework');

		$doc =& JFactory::getDocument();
		$doc->addStyleDeclaration('form#adminForm div.current { width: auto; }');
        
        $sefConfig =& SEFConfig::getConfig();
        if (!$sefConfig->professionalMode) {
            $mainframe =& JFactory::getApplication();
            $mainframe->enqueueMessage(JText::_('COM_SEF_BEGINNER_MODE_INFO'));
        }

		parent::display($tpl);
	}

}
?>