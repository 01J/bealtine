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

jimport('joomla.application.component.model');

class SEFModelSEF extends SEFModel
{
    function getFeed()
    {
        $sefConfig =& SEFConfig::getConfig();
        
        if (!$sefConfig->artioFeedDisplay) {
            return '';
        }
        
        $rssDoc = JFactory::getFeedParser($sefConfig->artioFeedUrl);
        
        if ($rssDoc === false) {
            return JText::_('COM_SEF_ERROR_CONNECTING_TO_RSS_FEED');
        }
        
        $items = $rssDoc->get_items();
        
        if (count($items) == 0) {
            return JText::_('COM_SEF_NO_ITEMS_TO_DISPLAY');
        }
        
        $txt = '';
        for ($i = 0, $n = count($items); $i < $n; $i++)
        {
            $item =& $items[$i];
            
            $title = $item->get_title();
            $link = $item->get_link();
            $desc = $item->get_description();
            $date = $item->get_date('j. F Y');
            $author = $item->get_author();
            
            $txt .= '<div class="feed-item">';
            $txt .= '<div class="feed-title"><a href="'.$link.'" target="_blank">'.$title.'</a></div>';
            $txt .= '<div class="feed-text">'.$desc.'</div>';
            $txt .= '</div>';
        }
        
        return $txt;
    }
    
    public function checkLanguagePlugins()
    {
        // Check that the language filter plugin is disabled
        if (JPluginHelper::isEnabled('system', 'languagefilter')) {
            JError::raiseWarning(0, JText::sprintf('COM_SEF_WARNING_LANGUAGEFILTER_ENABLED', '<a href="index.php?option=com_plugins&amp;filter_folder=system">'.JText::_('COM_SEF_PLUG_IN_MANAGER').'</a>'));
        }
    }
    
    public function clearCache($option = null)
    {
        $this->cleanCache($option, 0);
        $this->cleanCache($option, 1);
    }
}

?>