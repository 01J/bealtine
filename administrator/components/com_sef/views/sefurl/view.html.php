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

jimport('joomla.application.component.view');

class SefViewSefurl extends SefView
{
    function display($tpl = null)
    {
        //get the data
        $sef      =& $this->get('Data');
        $lists    = $this->get('Lists');
        if(JRequest::getInt('viewmode')!=6) {
        	$isNew    = ($sef->id < 1);
        } else {
        	$isNew = strlen($sef->sefurl)?false:true;
        }

        $text = $isNew ? JText::_( 'COM_SEF_NEW' ) : JText::_( 'COM_SEF_EDIT' );
        JToolBarHelper::title('JoomSEF - ' .  JText::_( 'COM_SEF_SEF_URL' ).' [ ' . $text.' ]', 'url-edit.png' );
        if(JRequest::getInt('viewmode')==6) {
        	JToolBarHelper::save('save_cache');
        } else {
        	JToolBarHelper::save();
        }
        if ($isNew)  {
            JToolBarHelper::cancel();
        } else {
            // for existing items the button is renamed `close`
            JToolBarHelper::cancel('cancel', 'Close');
        }

        $this->assignRef('sef', $sef);
        $this->assignRef('lists', $lists);
        
        JHTML::_('behavior.tooltip');
        
        // Load JS
        $document = & JFactory::getDocument();
        $document->addScript('components/com_sef/assets/js/words.js');
        $document->addStyleSheet('components/com_sef/assets/css/words.css');

        parent::display($tpl);
    }
    
}
?>