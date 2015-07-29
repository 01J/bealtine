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

class SefViewMovedurl extends SefView
{
    function display($tpl = null)
    {
        //get the data
        $url      =& $this->get('Data');
        $isNew    = ($url->id < 1);

        $text = $isNew ? JText::_( 'COM_SEF_NEW' ) : JText::_( 'COM_SEF_EDIT' );
        JToolBarHelper::title(   JText::_( 'COM_SEF_301_REDIRECT' ).': <small>[ ' . $text.' ]</small>', '301-redirects.png' );
        JToolBarHelper::save();
        if ($isNew)  {
            JToolBarHelper::cancel();
        } else {
            // for existing items the button is renamed `close`
            JToolBarHelper::cancel( 'cancel', 'Close' );
        }

        $this->assignRef('url', $url);
        
        JHTML::_('behavior.tooltip');

        parent::display($tpl);
    }
    
}
