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

class SefViewCrawler extends SefView
{
	function display($tpl = null)
	{
		JToolBarHelper::title(JText::_('COM_SEF_WEBSITE_CRAWLER'), 'web-crawl.png');
		JToolBarHelper::back('COM_SEF_BACK', 'index.php?option=com_sef');
        
        // Prepare vars for JS
        $document = & JFactory::getDocument();
        $js = "var jsCrawlerTextRunning = '".JText::_('COM_SEF_CRAWLER_RUNNING', true)."';\n";
        $js .= "var jsCrawlerTextCancel = '".JText::_('COM_SEF_CRAWLER_CANCEL', true)."';\n";
        $js .= "var jsCrawlerTextFinish = '".JText::_('COM_SEF_CRAWLER_FINISH', true)."';\n";
        $js .= "var jsCrawlerTextSuccess = '".JText::_('COM_SEF_CRAWLER_SUCCESS', true)."';\n";
        $js .= "var jsCrawlerTextCancelled = '".JText::_('COM_SEF_CRAWLER_CANCELLED', true)."';\n";
        $js .= "var jsCrawlerTextError = '".JText::_('COM_SEF_CRAWLER_ERROR', true)."';\n";
        $js .= "var jsCrawlerTextErrorMsg = '".JText::_('COM_SEF_CRAWLER_ERROR_MSG', true)."';\n";
        $js .= "var jsCrawlerTextResponseTime = '".JText::_('COM_SEF_CRAWLER_RESPONSE_TIME', true)."';\n";
        $js .= "var jsCrawlerScriptUrl = '".JURI::root()."administrator/index.php?option=com_sef&controller=crawler&task=crawl';\n";
        $js .= "var jsCrawlerRootUrl = '".JURI::root()."';\n";
        $document->addScriptDeclaration($js);
        
        // Load JS
        JHTML::script('administrator/components/com_sef/assets/js/crawler.js', true);
        JHTML::_('behavior.tooltip');
        
		parent::display($tpl);
	}
}
