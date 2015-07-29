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

//require_once(JPATH_COMPONENT.'/classes/button.php');

jimport( 'joomla.html.pane' );

class SefViewSef extends SefView
{
	function display($tpl = null)
	{
		JToolBarHelper::title(JText::_('COM_SEF_JOOMSEF'), 'artio.png');
		
		$user = JFactory::getUser();
		if ($user->authorise('core.admin', 'com_sef')) {
		    JToolBarHelper::preferences('com_sef');
		}
		
		// Get number of URLs for purge warning
		$model = SEFModel::getInstance('URLs', 'SEFModel');
		$this->assign('purgeCount', $model->getCount(0));
		
		// Get newest version available
		$sefConfig = SEFConfig::getConfig();
		
		if ($sefConfig->versionChecker) {
    		$model2 = SEFModel::getInstance('Upgrade', 'SEFModel');
    		$newVer = $model2->getNewSEFVersion();
    		$sefinfo = SEFTools::getSEFInfo();
    		
    		if( ((strnatcasecmp($newVer, $sefinfo['version']) > 0) ||
            (strnatcasecmp($newVer, substr($sefinfo['version'], 0, strpos($sefinfo['version'], '-'))) == 0)) ) {
                $newVer = '<span style="font-weight: bold; color: red;">'.$newVer.'</span>&nbsp;&nbsp;<input type="button" class="btn btn-small" onclick="showUpgrade();" value="' . JText::_('COM_SEF_GO_TO_UPGRADE_PAGE') . '" />';
            }
            $newVer .= ' <input type="button" class="btn btn-danger btn-small" onclick="disableStatus(\'versioncheck\');" value="' . JText::_('COM_SEF_DISABLE_VERSION_CHECKER') . '" />';
            
    		$this->assign('newestVersion', $newVer);
		}
		else {
		    $newestVersion = JText::_('COM_SEF_VERSION_CHECKER_DISABLED') . '&nbsp;&nbsp;<input type="button" class="btn btn-success btn-small" onclick="enableStatus(\'versioncheck\');" value="' . JText::_('COM_SEF_ENABLE') . '" />';
		    $this->assign('newestVersion', $newestVersion);
		}
		
		// Get statistics
		$stats = $model->getStatistics();
		$this->assignRef('stats', $stats);
		
		// Get feed
		$feed = $this->get('Feed');
		$this->assignRef('feed', $feed);
		
		// Check language filter plugin
		$this->getModel('sef')->checkLanguagePlugins();

		parent::display($tpl);
	}
}
