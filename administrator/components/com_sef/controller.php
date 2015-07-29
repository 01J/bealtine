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

// no direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.controller');

if (!class_exists('JControllerLegacy')) {
    class JControllerLegacy extends JController { }
}

class SEFController extends JControllerLegacy
{
    function __construct()
    {
        parent::__construct();
    }

    protected function addSubmenu()
    {
        $view = JRequest::getVar('view');

        JSubMenuHelper::addEntry(JText::_('COM_SEF_CPANEL'), 'index.php?option=com_sef', is_null($view));
        JSubMenuHelper::addEntry(JText::_('COM_SEF_CONFIG'), 'index.php?option=com_sef&controller=config&task=edit', $view == 'config');
        JSubMenuHelper::addEntry(JText::_('COM_SEF_EXTENSIONS'), 'index.php?option=com_sef&controller=extension', $view == 'extensions');
        JSubMenuHelper::addEntry(JText::_('COM_SEF_HTACCESS'), 'index.php?option=com_sef&controller=htaccess', $view == 'htaccess');
        JSubMenuHelper::addEntry(JText::_('COM_SEF_SEFURLS'), 'index.php?option=com_sef&controller=sefurls&viewmode=3', $view == 'sefurls');
        JSubMenuHelper::addEntry(JText::_('COM_SEF_METATAGS'), 'index.php?option=com_sef&controller=metatags', $view == 'metatags');
        JSubMenuHelper::addEntry(JText::_('COM_SEF_SITEMAP'), 'index.php?option=com_sef&controller=sitemap', $view == 'sitemap');
        JSubMenuHelper::addEntry(JText::_('COM_SEF_REDIRECTS'), 'index.php?option=com_sef&controller=movedurls', $view == 'movedurls');
        JSubMenuHelper::addEntry(JText::_('COM_SEF_STATISTICS'), 'index.php?option=com_sef&view=statistics', $view == 'statistics');
        JSubMenuHelper::addEntry(JText::_('COM_SEF_UPGRADE'), 'index.php?option=com_sef&task=showUpgrade', $view == 'upgrade');
        JSubMenuHelper::addEntry(JText::_('COM_SEF_HELP_AND_SUPPORT'), 'index.php?option=com_sef&controller=info&task=help', $view == 'info');

    }

    function display()
    {
        $viewVar = JRequest::getVar('view');
        if (is_null($viewVar) || $viewVar == 'sef') {
            $model =& $this->getModel('extensions');
            $view =& $this->getView('sef', 'html', 'sefview');
            $view->setModel($model);
        }

        parent::display();

        $this->addSubmenu();
    }

    function editExt()
    {
        JRequest::setVar('view', 'extension');

        parent::display();
    }

    function doInstall() {
    	$err='';
    	$model=$this->getModel('extension');

    	if(!$model->install()) {
    		$msg=$model->getError();
    		$err='error';
    	}
		$this->setRedirect('index.php?option=com_sef&controller=extension',$msg,$err);
    }

    function installExt()
    {
       $this->setRedirect('index.php?option=com_installer');
    }

    function uninstallExt()
    {
        JRequest::checkToken() or jexit( 'Invalid Token' );

		//JFactory::getApplication()->setState('filter.type','sef-ext');
        $this->setRedirect('index.php?option=com_installer&view=manage',JText::_('COM_SEF_USE_STANDARD_UNINSTALL'));

		/*$cid=JRequest::getVar('cid',array(),'','array');
		//I know that it is controller but temporarilly add this query
        $db=JFactory::getDBO();
        $query=$db->getQuery(true);
        $query->select('extension_id')->from('#__extensions')->where('state>=0')->where('enabled=1')->where('type='.$db->quote('sef_ext'))->where('element='.$db->quote($cid[0]));
        $db->setQuery($query);
        $eid=$db->loadResult();

		JTable::addIncludePath(JPATH_LIBRARIES.'/joomla/database/table');
		echo JPATH_LIBRARIES.'/joomla/database/table';
        JModel::addIncludePath(JPATH_ADMINISTRATOR.'/components/com_installer/models','InstallerModel');
        $model=JModel::getInstance('manage','InstallerModel');
        $model->remove(array($eid));

        $this->setRedirect('index.php?option=com_sef');*/
    }

    function showUpgrade()
    {
        JRequest::setVar('view', 'upgrade');

        parent::display();

        $this->addSubmenu();
    }

    function doUpgrade()
    {
        JRequest::checkToken() or jexit( 'Invalid Token' );

        $model =& $this->getModel('upgrade');
        $err='';
        if(!$model->upgrade()) {
        	$err='error';
        }
        $msg=$model->getState('message');
        
        $this->setRedirect(JRoute::_('index.php?option=com_sef&task=showUpgrade',false),$msg,$err);
    }

    function cleanCache()
    {
        require_once(JPATH_COMPONENT.'/controllers/urls.php');
        $controller = new SEFControllerURLs();
        $controller->execute( 'cleancache' );
        $this->setRedirect('index.php?option=com_sef', JText::_('COM_SEF_CACHE_CLEANED'));
    }

    function UpdateURLs()
    {
        $model =& $this->getModel('sefurls');

        $result = $model->UpdateURLs();

        $this->setRedirect('index.php?option=com_sef&task=urlsupdated&result='.$result);
    }

    function URLsUpdated()
    {
        $view =& $this->getView('sefurls', 'html');
        $view->showUpdated();
    }

    function enableStatus()
    {
        $this->setStatus(1);
    }

    function disableStatus()
    {
        $this->setStatus(0);
    }

    function setStatus($state)
    {
        $ajax = (intval(JRequest::getVar('ajax')) == 1);
        
        if (!$ajax) {
            JRequest::checkToken() or jexit( 'Invalid Token' );
        }
        
        $type = JRequest::getVar('cid', array(''), 'post', 'array');
        $type = JRequest::getVar('statusType', $type[0], 'post', 'string');
        $types = array('sef', 'mod_rewrite', 'sef_suffix', 'joomsef', 'plugin', 'newurls', 'versioncheck', 'jfrouter');
        $msg = '';

        if( in_array($type, $types) ) {
            // SEF and mod_rewrite settings
            if( $type == 'sef' || $type == 'mod_rewrite' || $type == 'sef_suffix' ) {
                jimport('joomla.client.helper');
                jimport('joomla.filesystem.path');
                jimport('joomla.filesystem.file');
                
                // We need to load the config file manually,
                // because on Joomla 3 the global JFactory::getConfig()
                // already contains some changed themeParams data,
                // which causes fatal error after saving
                $file = JPATH_CONFIGURATION.'/configuration.php';
                if (!is_file($file)) {
                    $msg = JText::_('COM_SEF_ERROR_LOADING_CONFIG');
                }
                else {
                    include_once($file);
                    if (!class_exists('JConfig')) {
                        $msg = JText::_('COM_SEF_ERROR_LOADING_CONFIG');
                    }
                    else {
                        $config = new JRegistry();
                        $data = new JConfig();
                        $config->loadObject($data);
                        
                        if( $type == 'sef' ) {
                            $config->set('sef', $state);
                        }
                        else if( $type == 'mod_rewrite' ) {
                            $config->set('sef_rewrite', $state);
                        }
                        else {
                            $config->set('sef_suffix', $state);
                        }
        
                        // Store the configuration
        
                		// Get the new FTP credentials.
                		$ftp = JClientHelper::getCredentials('ftp', true);
        
                		// Attempt to make the file writeable if using FTP.
                		if (!$ftp['enabled'] && JPath::isOwner($file) && !JPath::setPermissions($file, '0644')) {
                			$msg = JText::_('COM_CONFIG_ERROR_CONFIGURATION_PHP_NOTWRITABLE');
                		}
        
                		if( !JFile::write($file, $config->toString('PHP', array('class' => 'JConfig', 'closingtag' => false))) ) {
                			$msg = JText::_('COM_SEF_ERROR_WRITING_CONFIG');
                		}
                    }
                }
            }
            else if( $type == 'joomsef' || $type == 'newurls' || $type == 'versioncheck' ) {
                // JoomSEF and new URLs settings
                $sefConfig = SEFConfig::getConfig();

                if( $type == 'joomsef' ) {
                    $sefConfig->enabled = $state;
                }
                else if( $type == 'newurls' ) {
                    $sefConfig->disableNewSEF = 1 - $state;
                }
                else {
                    $sefConfig->versionChecker = $state;
                }

                // Store the configuration
                if( !$sefConfig->saveConfig() ) {
                    $msg = JText::_('COM_SEF_ERROR_WRITING_CONFIG');
                }
            }
            else if( $type == 'plugin' || $type == 'jfrouter' ) {
                // Plugins settings
                $db = JFactory::getDBO();

                if( $type == 'plugin' ) {
                    $plg = 'joomsef';
                }
                else if( $type == 'jfrouter' ) {
                    $plg = 'jfrouter';
                }

                $query = "UPDATE `#__extensions` SET `enabled` = '{$state}' WHERE (`type` = 'plugin') AND (`element` = '{$plg}') AND (`folder` = 'system') LIMIT 1";
                $db->setQuery($query);
                if( !$db->query() ) {
                    $msg = JText::_('COM_SEF_ERROR_WRITING_CONFIG');
                }
                
                // Clear cache for com_plugins!
                $model = $this->getModel();
                $model->clearCache('com_plugins');
            }
        }
        
        if ($ajax) {
            $obj = new stdClass();
            $obj->id = $type;
            $obj->newValue = $state;
            $ret = array($obj);
            echo json_encode($ret);
            $app = JFactory::getApplication();
            $app->close();
        }
        else {
            $return = JRequest::getVar('return', 'index.php?option=com_sef');
            $this->setRedirect($return, $msg);
        }
    }
    
    function finish_upgrade() {
    	$db=JFactory::getDBO();
    	$sefConfig = SEFConfig::getConfig();
    	
    	$download_id=$sefConfig->artioDownloadId;

        $query=$db->getQuery(true);
        $query->select('location')->from('#__update_sites')->where('name='.$db->quote('com_joomsef'));
        $db->setQuery($query);
        $location=$db->loadResult();
        
        $location_match=array();
        if(preg_match("/(-([A-Za-z0-9]*)).xml/",$location,$location_match)) {
        	if(strlen($download_id)) {
        		$location=str_replace($location_match[0],'-'.$download_id.'.xml',$location);
        	} else {
        		$location=str_replace($location_match[0],'.xml',$location);
        	}

        	$query="UPDATE #__update_sites \n";
        	$query.="SET location=".$db->quote($location)." \n";
        	$query.="WHERE name=".$db->quote('com_joomsef');
        	$db->setQUery($query);
        	if(!$db->query()) {
        		$this->setError($db->stderr(true));
        		return false;
        	}
        } else if(strlen($download_id)) {
        	$location=str_replace('.xml','-'.$download_id.'.xml',$location);

        	$query="UPDATE #__update_sites \n";
        	$query.="SET location=".$db->quote($location)." \n";
        	$query.="WHERE name=".$db->quote('com_joomsef');
        	$db->setQUery($query);
        	if(!$db->query()) {
        		$this->setError($db->stderr(true));
        		return false;
        	}
        }
        
        $this->setRedirect('index.php?option=com_sef',JText::_('COM_SEF_UPGRADE_SUCCESSFULL'));
    }
}
?>