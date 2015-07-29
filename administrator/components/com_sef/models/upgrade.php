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
jimport('joomla.filesystem.file');
jimport('joomla.filesystem.folder');
jimport('joomla.installer.helper');
jimport('joomla.installer.installer');
jimport('joomla.application.helper');
require_once JPATH_ADMINISTRATOR.'/components/com_sef/classes/seftools.php';

class SEFModelUpgrade extends SEFModel
{
    function getUpgradeExts()
    {
        if (!isset($this->_upgradeExts)) {
            $this->_loadVersions();

            $basedir = JPATH_ROOT.'/components/com_sef/sef_ext';

            $extensions = array();
            if( count($this->_extVersions) > 0 ) {
                foreach( $this->_extVersions as $key => $ext ) {
                	$option=str_replace('ext_joomsef4_','com_',$ext->name);
                    $xmlfile = $basedir.'/'.$option.'.xml';
                    if (!JFile::exists($xmlfile)) {
                        continue;
                    }

                    $xml=simplexml_load_file($xmlfile);
                    if (!$xml) {
                        unset($xml);
                        continue;
                    }

                    if (!is_object($xml) ||
                        ($xml->getName() != 'extension') ||
                        version_compare((string)$xml['version'], '1.6', '<') ||
                        ((string)$xml['type'] != 'sef_ext' ))
                    {
                        unset($xml);
                        continue;
                    }

                    $extension = new stdClass();
                    $extension->new = $ext->version;

                    $extension->type = $ext->type;
                    $extension->option = $key;
                    $extension->params = SEFTools::getExtParams($key);

                    $element         = $xml->name;
                    $extension->name = $element ? (string)$element : '';

                    $element        = $xml->version;
                    $extension->old = $element ? (string)$element : '';

                    $extensions[$ext->name] = $extension;
                }
            }

            $this->_upgradeExts = $extensions;
        }

        return $this->_upgradeExts;
    }

    function getNewSEFVersion()
    {
        if( !isset($this->_newSEFVersion) ) {
            $this->_loadVersions();
            $this->_newSEFVersion=$this->_extVersions['com_joomsef4']->version;
        }

        return $this->_newSEFVersion;
    }

    function getRegisteredInfo()
    {
        if (!isset($this->_regInfo) ) {
            $regInfo = new stdClass();

            $sefConfig =& SEFConfig::getConfig();
            if( trim($sefConfig->artioDownloadId) != '' ) {
                // Send the request to ARTIO server to check registration
                $data = array('download_id' => trim($sefConfig->artioDownloadId),'cat'=>'com_joomsef4');
                $response = SEFTools::PostRequest($sefConfig->serverLicenser, null, $data);

                if( ($response === false) || ($response->code != 200) ) {
                    JError::raiseNotice(100, JText::_('COM_SEF_ERROR_REG_CHECK_FAIL'));
                    return null;
                }
                else {
                    // Parse the response - get individual lines
                    $lines = explode("\n", $response->content);

                    // Get the code
                    $pos = strpos($lines[0], ' ');
                    if( $pos === false ) {
                        JError::raiseNotice(100, JText::_('COM_SEF_ERROR_REG_CHECK_FAIL'));
                        return null;
                    }
                    $regInfo->code = intval(substr($lines[0], 0, $pos));

                    if( ($regInfo->code == 10) || ($regInfo->code == 20) ) {
                    	// Download id found
	                    if (count($lines) < 3) {
	                        // Wrong response
	                        JError::raiseNotice(100, JText::_('COM_SEF_ERROR_REG_CHECK_FAIL'));
	                        return null;
	                    }
	
	                    // Parse the date
	                    $date =& JFactory::getDate(str_replace('.', '/', trim($lines[1])));
	                    $regInfo->date = $date->format(JText::_('DATE_FORMAT_LC3'));
	
	                    // Parse the name
	                    $regInfo->name = trim($lines[2]);
	
	                    // Parse the company
	                    $regInfo->company = isset($lines[3]) ? trim($lines[3]) : '';
	
	                    // Is upgrade expired?
	                    if ($regInfo->code == 20) {
	                        JError::raiseNotice(100, JText::sprintf('COM_SEF_EXPIRED', 'Artio JoomSEF 4') . ' ' . JText::_('COM_SEF_INFO_YOU_GET_FREE_VERSION'));
	                    }
	                    
                    } 
	                // Is upgrade inactive
					else if ($regInfo->code == 30) {
                        JError::raiseNotice(100, JText::sprintf('COM_SEF_NOT_ACTIVATED', 'Artio JoomSEF 4') . ' ' . JText::_('COM_SEF_INFO_YOU_GET_FREE_VERSION'));
                        $regInfo->date = JText::_('COM_SEF_NOT_ACTIVATED_YET');
                    }
                    else if($regInfo->code==40) {
                    	JError::raiseNotice(100,JText::sprintf('COM_SEF_ERR_DOMAIN_NOT_MATCH','Artio JoomSEF 4').' '.JText::_('COM_SEF_INFO_YOU_GET_FREE_VERSION'));
                    	return null;
                    }
                    else if($regInfo->code==50) {
                    	JError::raiseNotice(100,JText::sprintf('COM_SEF_DOWLOAD_ID_INVALID','Artio JoomSEF 4').' '.JText::_('COM_SEF_INFO_YOU_GET_FREE_VERSION'));
                    	return null;	
                    }
                    else if( $regInfo->code == 90 ) {
                        // Download id not found, do nothing
                        JError::raiseNotice(100, JText::sprintf('COM_SEF_ERROR_DOWNLOAD_ID_NOT_FOUND',trim($sefConfig->artioDownloadId)). ' ' . JText::_('COM_SEF_INFO_YOU_GET_FREE_VERSION'));
                    }
                    else {
                        // Wrong response
                        JError::raiseNotice(100, JText::_('COM_SEF_ERROR_REG_CHECK_FAIL'));
                        return null;
                    }
                }
            }
            else {
                // Download ID not set
                $link1 = '<a href="index.php?option=com_sef&controller=config&task=edit&tab=registration">';
                $link2 = '</a>';
                $enterIdText = sprintf(JText::_('COM_SEF_INFO_ENTER_DOWNLOAD_ID'), $link1, $link2);
                JError::raiseNotice(100, JText::_('COM_SEF_DOWNLOAD_ID_NOT_SET') . ' ' . JText::_('COM_SEF_INFO_YOU_GET_FREE_VERSION') . ' ' . $enterIdText);
                return null;
            }

            $this->_regInfo = $regInfo;
        }

        return $this->_regInfo;
    }

    function _loadVersions()
    {
        if (!isset($this->_extVersions)) {
            $sefConfig =& SEFConfig::getConfig();

    		$this->_extVersions = array();
            
            // Get the response from server
    		$response = SEFTools::PostRequest($sefConfig->serverNewVersionURL);

    		// Check the response
    		if( ($response === false) || ($response->code != 200) ) {
    		    JError::raiseNotice(100, JText::_('COM_SEF_ERROR_NO_VERSION_INFO'));
                
                // Set dummy data
                $ext = new stdClass();
                $ext->version = '?.?.?';
                $ext->name = 'JoomSEF';
                $this->_extVersions['com_joomsef4'] = $ext;
    		}
    		else {
    		    $versions = $response->content;
                
        		$xml = simplexml_load_string($versions);
        		if (is_object($xml)) {
    	    		foreach ($xml->children() as $package) {
    	    			$ext = new stdClass();
    	    			$ext->name = (string)$package->element;
    	    			$ext->version = (string)$package->version;
    	    			if (isset($package->buyURL)) {
    	    				$ext->type = 'Paid';
    	    				$ext->link = (string)$package->buyURL;
    	    			} else {
    	    				$ext->type = 'Free';
    	    				$ext->link = '';
    	    			}
    
    	    			$this->_extVersions[$ext->name]=$ext;
    	    		}
        		}
    		}
        }
    }

    function &getVersions()
    {
        $this->_loadVersions();

        return $this->_extVersions;
    }

    function getIsPaidVersion()
    {
        if( !isset($this->_isPaidVersion) ) {
            $check = SEFTools::GetSEFGlobalMeta();
            $ctrl = md5(implode(file(JPATH_ROOT.'/administrator/components/com_sef/sef.xml')));

            $this->_isPaidVersion = ($check == $ctrl);
        }

        return $this->_isPaidVersion;
    }

    function upgrade()
    {
        $extDir = JPATH_ROOT.'/components/com_sef/sef_ext';
        JFActory::getLanguage()->load('com_installer',JPATH_ADMINISTRATOR);

        $fromServer = JRequest::getVar('fromserver');
        $extension = JRequest::getVar('ext');

        if( is_null($fromServer) ) {
            $this->setState('message', JText::_('COM_SEF_ERROR_UPGRADE_SOURCE'));
            return false;
        }

        if( $fromServer == 1 ) {
            $package = $this->_getPackageFromServer($extension);
        } else {
            $package = $this->_getPackageFromUpload();
        }

        // was the package unpacked?
        if (!$package) {
            $this->setState('message', 'Unable to find install package.');
            return false;
        }

    	$xmls=JFolder::files($package['extractdir'],'.xml');
    	$xmlfile=$xmls[0];

		$xml=simplexml_load_file($package['extractdir'].'/'.$xmlfile);

        $installer=JInstaller::getInstance();
        JTable::addIncludePath(JPATH_LIBRARIES.'/joomla/database/table');
        if(!$installer->update($package['dir'])) {
        	$msg=JText::sprintf('COM_INSTALLER_MSG_UPDATE_ERROR', JText::_('COM_INSTALLER_TYPE_TYPE_'.strtoupper($package['type'])));
            $result=false;
        } else {
        	$msg=JText::sprintf('COM_INSTALLER_MSG_UPDATE_SUCCESS', JText::_('COM_INSTALLER_TYPE_TYPE_'.strtoupper($package['type'])));
            $result=true;
        }
        $this->setState('message',$msg);

        if (!is_file($package['packagefile'])) {
			$config=JFactory::getConfig();
			$package['packagefile']=$config->get('tmp_path').'/'.$package['packagefile'];
       	}

		JInstallerHelper::cleanupInstall($package['packagefile'],$package['extractdir']);

		return $result;
    }

    function _getPackageFromUpload()
    {
        // Get the uploaded file information
        $userfile = JRequest::getVar('install_package', null, 'files', 'array' );

        // Make sure that file uploads are enabled in php
        if (!(bool) ini_get('file_uploads')) {
            JError::raiseWarning(100, JText::_('COM_SEF_WARNINSTALLFILE'));
            return false;
        }

        // Make sure that zlib is loaded so that the package can be unpacked
        if (!extension_loaded('zlib')) {
            JError::raiseWarning(100, JText::_('COM_SEF_WARNINSTALLZLIB'));
            return false;
        }

        // If there is no uploaded file, we have a problem...
        if (!is_array($userfile) ) {
            JError::raiseWarning(100, JText::_('COM_SEF_NO_FILE_SELECTED'));
            return false;
        }

        // Check if there was a problem uploading the file.
        if ( $userfile['error'] || $userfile['size'] < 1 )
        {
            JError::raiseWarning(100, JText::_('COM_SEF_WARNINSTALLUPLOADERROR'));
            return false;
        }

        // Build the appropriate paths
        $config =& JFactory::getConfig();
        $tmp_dest = $config->get('tmp_path').'/'.$userfile['name'];
        $tmp_src  = $userfile['tmp_name'];

        // Move uploaded file
        jimport('joomla.filesystem.file');
        $uploaded = JFile::upload($tmp_src, $tmp_dest);

        // Unpack the downloaded package file
        $package = JInstallerHelper::unpack($tmp_dest);

        // Delete the package file
        JFile::delete($tmp_dest);

        return $package;
    }

    function _getPackageFromServer($extension)
    {
        // Make sure that zlib is loaded so that the package can be unpacked
        if (!extension_loaded('zlib')) {
            JError::raiseWarning(100, JText::_('COM_SEF_WARNINSTALLZLIB'));
            return false;
        }
        // build the appropriate paths
        $sefConfig =& SEFConfig::getConfig();
        $config =& JFactory::getConfig();
        if( strlen($extension)==0 ) {
            $tmp_dest = $config->get('tmp_path').'/joomsef.zip';
        }
        else {
            $tmp_dest = $config->get('tmp_path').'/'.$extension.'.zip';
        }

        // Validate the upgrade on server
        $data = array();
        $data['username'] = $sefConfig->artioUserName;
        $data['password'] = $sefConfig->artioPassword;
        if( strlen($extension)==0 ) {
            $data['download_id'] = $sefConfig->artioDownloadId;
            $data['file'] = 'com_joomsef4';
        }
        else {
            $params =& SEFTools::getExtParams($extension);
            $data['download_id'] = $params->get('downloadId', '');
            $data['file'] = $extension;
        }
        $uri = parse_url(JURI::root());
        $url = $uri['host'].$uri['path'];
        $url = trim($url, '/');
        $data['site'] = $url;
        $data['ip'] = $_SERVER['SERVER_ADDR'];
        $lang =& JFactory::getLanguage();
        $data['lang'] = $lang->getTag();
        $data['cat'] = 'joomsef4';

        // Get the server response
        $response = SEFTools::PostRequest($sefConfig->serverAutoUpgrade, JURI::root(), $data);

        // Check the response
        if( ($response === false) || ($response->code != 200) ) {
            JError::raiseWarning(100, JText::_('COM_SEF_ERROR_SERVER_CONNECTION'));
            return false;
        }

        // Response OK, check what we got
        if( strpos($response->header, 'Content-Type: application/zip') === false ) {
            JError::raiseWarning(100, $response->content);
            return false;
        }

        // Seems we got the ZIP installation package, let's save it to disk
        if (!JFile::write($tmp_dest, $response->content)) {
            JError::raiseWarning(100, JText::_('COM_SEF_ERROR_TEMP_DIRECTORY'));
            return false;
        }

        // Unpack the downloaded package file
        $package = JInstallerHelper::unpack($tmp_dest);

        // Delete the package file
        JFile::delete($tmp_dest);

        return $package;
    }

    function _getXmlText($file, $variable)
    {
        // try to find variable
        $value = null;
        if (JFile::exists($file)) {
            $xml = simplexml_load_file($file);
            if ($xml !== false) {
                if (isset($xml->$variable)) {
                    $value = (string)$xml->$variable;
                }
            }
        }

        return $value;
    }

}
?>