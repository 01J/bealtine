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
jimport( 'joomla.html.pane' );

class SefViewExtension extends SefView
{
	function display($tpl = null)
	{
		// Get data from the model
		$extension =& $this->get('extension');
		$this->assignRef('extension', $extension);
		$this->langs=$this->get('languages');
		$this->strings=$this->get('strings');
		$this->translation=$this->get('translation');
		$this->subdomains=$this->get('subdomains');
		$this->menus=$this->get('menus');

		$filters =& SEFTools::getExtFilters($extension->option, false);
		$this->assignRef('filters', $filters);

		$acceptVars =& SEFTools::getExtAcceptVars($extension->option, false);
		sort($acceptVars, SORT_STRING);
		$this->assignRef('acceptVars', $acceptVars);

        // Root domain for subdomains configuration
        $rootDomain = JFactory::getURI()->getHost();
        if (substr($rootDomain, 0, 4) == 'www.') {
            $rootDomain = substr($rootDomain, 4);
        }
        $this->assign('rootDomain', $rootDomain);
        
		JToolBarHelper::title( JText::_( 'SEF Extension' ).' <small>'.JText::_( 'Edit' ).' [ ' . (strlen($extension->name)?$extension->name:$extension->component->name) . ' ]</small>', 'plugin.png' );

		JToolBarHelper::save();
		JToolBarHelper::apply();
		JToolBarHelper::spacer();
		JToolBarHelper::cancel();

		JHTML::_('behavior.tooltip');

		$redir = JRequest::getVar('redirto', '');
		$this->assignRef('redirto', $redir);

		parent::display($tpl);
	}

	function showEditId()
	{
	    $ext = $this->get('extension');
	    /*print_r($ext);
	    exit;*/

	    $this->assignRef('ext', $ext);

	    $this->setLayout('editid');
	    parent::display(null);
	}
}