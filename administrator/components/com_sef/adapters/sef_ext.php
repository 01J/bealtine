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

// Check to ensure this file is within the rest of the framework
defined('JPATH_BASE') or die();

/**
 * Component installer
 *
 * @package		Joomla.Framework
 * @subpackage	Installer
 * @since		1.5
 */
jimport( 'joomla.utilities.simplexml' );
jimport('joomla.filesystem.file');
require_once JPATH_ADMINISTRATOR.'/components/com_sef/classes/seftools.php';

class JInstallerAdapterSef_ext extends JObject
{
    /**
	 * Constructor
	 *
	 * @access	protected
	 * @param	object	$parent	Parent object [JInstaller instance]
	 * @return	void
	 * @since	1.5
	 */
    function __construct(&$parent)
    {
        parent::__construct();
        
        $this->parent =& $parent;
        JFactory::getLanguage()->load('com_sef');
    }

    function install()
    {
        $extDir = JPATH_ROOT.'/components/com_sef/sef_ext';
        $db = $this->parent->getDBO();
        $this->manifest =& $this->parent->getManifest();
        $xml = $this->manifest;

        $name=(string)$xml->name;
        $name = JFilterInput::getInstance()->clean($name, 'string');
        $this->set('name', $name);

        $description=(string)$xml->description;

        if (is_a($description, 'JSimpleXMLElement')) {
            $this->parent->set('message',JText::_($description));
        } else {
            $this->parent->set('message', '' );
        }

		if (count($xml->files->children()))	{
			foreach ($xml->files->children() as $file)	{
				if ((string)$file->attributes()->sef_ext) {
					$element = (string)$file->attributes()->sef_ext;
					if(substr($element,0,13)!='ext_joomsef4_') {
						$element='ext_joomsef4_'.$element;
					}
					$this->set('element',$element);
					break;
				}
			}
		}

		if(!empty($element)) {
			$this->parent->setPath('extension_root', $extDir);
		} else {
			$this->parent->abort(JText::sprintf('COM_SEF_INSTALLER_ABORT_SEFEXT_INSTALL_NO_FILE', JText::_('JLIB_INSTALLER_'.$this->route)));
			return false;
		}

		$this->route='install';

        $query="SELECT extension_id, state, params \n";
        $query.="FROM #__extensions \n";
        $query.="WHERE type=".$db->quote('sef_ext')." \n";
        $query.="AND element=".$db->quote($element);
        $db->setQuery($query);
        $ext_o=$db->loadObject();
        if (!is_null($ext_o)) {
            $id=$ext_o->extension_id;
        }
        else {
            $id = null;
        }

        if(file_exists($this->parent->getPath('extension_root')) && (!$this->parent->isOverwrite()||$this->parent->isUpgrade())) {
        	if($this->parent->isUpgrade()||($this->parent->manifestClass && method_exists($this->parent->manifestClass,'update')||is_a($xml->update,'SimpleXMLElement'))) {
        		$this->parent->setOverwrite(true);
        		$this->parent->setUpgrade(true);
        		if($id && $ext_o->state!=-2) {
        			$this->route='update';
        		}
        	} else if(!$this->parent->isOverwrite()) {
        		$this->parent->abort(JText::sprintf('COM_SEF_INSTALLER_ABORT_SEFEXT_INSTALL_DIRECTORY',JText::_('JLIB_INSTALLER_'.$this->route),$this->parent->getPath('extension_root')));
        	}
        }

        if((string)$xml->scriptfile) {
        	$script=(string)$xml->scriptfile;
        	$script_file=$this->parent->getPath('source').'/'.$script;
        	if(is_file($script_file)) {
        		include_once $script_file;

				$class=$element.'InstallerScript';
				if(class_exists($class)) {
					$this->parent->manifestClass=new $class($this);
					$this->set('manifest_script',$script);
				}
        	}
        }

        ob_start();
        ob_implicit_flush(false);
        if($this->parent->manifestClass && method_exists($this->parent->manifestClass,'preflight')) {
        	if($this->parent->manifestClass->preflight($this->route,$this)===false) {
        		$this->parent->abort(JText::_('COM_SEF_INSTALLER_ABORT_SEF_INSTALL_CUSTOM_INSTALL_FAILURE'));
        		return false;
        	}
        }
       	$msg=ob_get_contents();
       	ob_end_clean();

		if(!file_exists($this->parent->getPath('extension_root'))) {
			if(JFolder::create($this->parent->getPath('extension_root'))) {
				$this->parent->abort(JText::sprintf('COM_SEF_INSTALLER_ABORT_SEF_INSTALL_CREATE_DIRECTORY',$this->parent->getPath('extension_root')));
				return false;
			}
		}

		$this->old_files = null;
        if($this->route=='update') {
			$old=null;
			$tmp=new JInstaller();
			$option=str_replace('ext_joomsef4_','com_',$this->get('element'));
			$tmp_manifest=$tmp->isManifest($this->parent->getPath('extension_root').'/'.$option.'.xml');
			if($tmp_manifest) {
				$this->old_files=$tmp_manifest->files;
			}
		}

		if(!$this->parent->parseFiles($xml->files,-1,$this->old_files)) {
			$this->parent->abort();
			return false;
		}

		if($this->get('manifest_script')) {
			$path['src'] = $this->parent->getPath('source').'/'. $this->get('manifest_script');
			$path['dest'] = $this->parent->getPath('extension_root').'/'.$this->get('manifest_script');
			if(!file_exists($path['desc'])) {
				if(!$this->parent->copyFiles(array($path))) {
					$this->parent->abort(JText::sprintf('COM_SEF_INSTALLER_ABORT_SEF_INSTALL_MANIFEST',JText::_('JLIB_INSTALLER_'.$this->route)));
					return false;
				}
			}
		}
		JTable::addIncludePath(JPATH_LIBRARIES.'/joomla/database/table');
		$row=JTable::getInstance('extension');
		if($id && $ext_o->state!=-2) {
			$row->load($id);
			$row->name=$this->get('name');
			$row->manifest_cache=$this->parent->generateManifestCache();
			$row->store();
		} else {
			if(is_object($ext_o) && ($ext_o->state==-2)) {
				$row->extension_id=$id;
			}
			$row->name=$this->get('name');
			$row->type='sef_ext';
			$row->element=$element;
			$row->enabled=1;
			$row->protected=0;
			$row->access=1;
			$row->client_id=0;
			$row->state=0;
			if(!is_object($ext_o) || ($ext_o->state!=-2)) {
				if(isset($this->manifest->install->defaultParams)) {
					$row->params = SEFTools::getDefaultParams($this->manifest->install->defaultParams);
				}
				if(isset($this->manifest->install->defaultFilters)) {
					$row->custom_data=SEFTools::getDefaultFilters($this->manifest->install->defaultFilters);
				}
			}
			$row->system_data='';
			$row->manifest_cache=$this->parent->generateManifestCache();
			if(!$row->store()) {
				$this->parent->abort(Jtext::sprintf('COM_SEF_INSTALLER_ABORT_SEF_INSTALL_ROLLBACK',JText::_('JLIB_INSTALLER_'.$this->route),$db->stderr(true)));
				return false;
			}
			$this->parent->pushStep(array ('type' => 'extension', 'id' => $row->extension_id));
			$id = $row->extension_id;
		}

		if($this->route=='install') {
			$utfresult = $this->parent->parseSQLFiles($this->manifest->install->sql);
			if ($utfresult === false)
			{
				// Install failed, rollback changes
				$this->parent->abort(JText::sprintf('COM_SEF_INSTALLER_ABORT_SEF_INSTALL_SQL_ERROR', JText::_('JLIB_INSTALLER_'.$this->route), $db->stderr(true)));
				return false;
			}

			if($this->manifest->update) {
				$this->parent->setSchemaVersion($this->manifest->update->schemas, $row->extension_id);
			} else {
				$query="SELECT COUNT(*) FROM #__schemas \n";
				$query.="WHERE extension_id=".$row->extension_id;
				$db->setQuery($query);
				$cnt=$db->loadResult();

				if($cnt==0) {
					$query="INSERT INTO #__schemas \n";
					$query.="SET extension_id=".$row->extension_id.", version_id=".$db->quote((string)$xml->version);
				} else {
					$query="UPDATE #__schemas \n";
					$query.="SET version_id=".$db->quote((string)$xml->version)." \n";
					$query.="WHERE extension_id=".$row->extension_id." \n";
				}
				$db->setQuery($query);
				$db->query();
			}
		} else {
			if($this->manifest->update)	{
				if(isset($this->manifest->update->schemas)) {
					$result = $this->parent->parseSchemaUpdates($this->manifest->update->schemas, $row->extension_id);
					if ($result === false)
					{
						$this->parent->abort(JText::sprintf('COM_SEF_INSTALLER_ABORT_SEF_UPDATE_SQL_ERROR', JText::_('JLIB_INSTALLER_'.$this->route), $db->stderr(true)));
						return false;
					}
				}
			} else {
				$query="SELECT COUNT(*) FROM #__schemas \n";
				$query.="WHERE extension_id=".$row->extension_id;
				$db->setQuery($query);
				$cnt=$db->loadResult();

				if($cnt==0) {
					$query="INSERT INTO #__schemas \n";
					$query.="SET extension_id=".$row->extension_id.", version_id=".$db->quote((string)$xml->version);
				} else {
					$query="UPDATE #__schemas \n";
					$query.="SET version_id=".$db->quote((string)$xml->version)." \n";
					$query.="WHERE extension_id=".$row->extension_id." \n";
				}
				$db->setQuery($query);
				$db->query();
			}
		}

        // Remove any pending updates in Joomla update cache
		$update = JTable::getInstance('update');
		$uid = $update->find(array('element' => $element, 'type' => 'sef_ext', 'client_id' => '', 'folder' => ''));
		if ($uid)
		{
			$update->delete($uid);
		}

       	ob_start();
		ob_implicit_flush(false);
		if ($this->parent->manifestClass && method_exists($this->parent->manifestClass,$this->route))
		{
			if($this->parent->manifestClass->{$this->route}($this) === false)
			{
				$this->parent->abort(JText::_('COM_SEF_INSTALLER_ABORT_SEF_INSTALL_CUSTOM_INSTALL_FAILURE'));
				return false;
			}
		}
		$msg .= ob_get_contents();
		ob_end_clean();

		if (!$this->parent->copyManifest(-1))
		{
			$this->parent->abort(JText::sprintf('COM_SEF_INSTALLER_ABORT_SEF_INSTALL_COPY_SETUP', JText::_('JLIB_INSTALLER_'.$this->route)));
			return false;
		}

		ob_start();
		ob_implicit_flush(false);
		if ($this->parent->manifestClass && method_exists($this->parent->manifestClass,'postflight'))
		{
			$this->parent->manifestClass->postflight($this->route, $this);
		}
		$msg .= ob_get_contents();
		ob_end_clean();
		if ($msg != '') {
			$this->parent->set('extension_message', $msg);
		}

        // Remove already created URLs for this extension from database
        // 25.4.2012: Only remove automatic URLs that are not locked!
        $component = str_replace('ext_joomsef4_', 'com_', $element);
        $query = "DELETE FROM `#__sefurls` WHERE (`origurl` LIKE '%option={$component}&%' OR `origurl` LIKE '%option={$component}') AND `dateadd` = '0000-00-00' AND `locked` = 0";
        $db->setQuery($query);
        if (!$db->query()) {
            $this->parent->abort( JText::_('COM_SEF_SEF_EXTENSION').' '.JText::_('COM_SEF_INSTALL').': '.JText::_('COM_SEF_ERROR_SQL')." ".$db->stderr(true) );
            return false;
        }
		return $id;
    }



    function update() {
    	$this->parent->setOverwrite(true);
    	$this->parent->setUpgrade(true);
    	$this->route='update';
    	return $this->install();
    }

    function uninstall($id)
    {
        $this->route='uninstall';
        $db=JFactory::getDBO();

        $row=JTable::getInstance('extension');
        if(!$row->load((int)$id)) {
        	JError::raiseWarning(100, JText::_('COM_SEF_INSTALLER_ERROR_SEF_UNINSTALL_ERRORUNKOWNEXTENSION'));
			return false;
        }

        if ($row->protected) {
			JError::raiseWarning(100, JText::sprintf('COM_SEF_INSTALLER_ERROR_SEF_UNINSTALL_WARNCOREPLUGIN', $row->name));
			return false;
		}

		$this->parent->setPath('extension_root',JPATH_ROOT.'/components/com_sef/sef_ext');
		$manifest_file=$this->parent->getPath('extension_root').'/'.str_replace('ext_joomsef4_','com_',$row->element).'.xml';
		if(!file_exists($manifest_file)) {
			JError::raiseWarning(100, JText::_('COM_SEF_INSTALLER_ERROR_SEF_UNINSTALL_INVALID_NOTFOUND_MANIFEST'));
			$row->delete($row->extension_id);
			unset($row);
			return false;
		}

		$xml = simplexml_load_file($manifest_file);
		$this->manifest = $xml;

		if (!$xml)
		{
			JError::raiseWarning(100, JText::_('COM_SEF_INSTALLER_ERROR_SEF_UNINSTALL_LOAD_MANIFEST'));
			$row->delete($row->extension_id);
			unset($row);
			return false;
		}

		if ($xml->getName() != 'install' && $xml->getName() != 'extension')
		{
			JError::raiseWarning(100, JText::_('COM_SEF_INSTALLER_ERROR_SEF_UNINSTALL_INVALID_MANIFEST'));
			return false;
		}

		$element=$row->element;
		$script=(string)$xml->scriptfile;
		if($script) {
			$script_file=$this->parent->getPath('source').'/'.$script;
			if(is_file($script_file)) {
				include_once($script_file);
				$class=$element.'InstallerScript';
				if(class_exists($class)) {
					$this->parent->manifestClass=new $class($this);
					$this->set('manifest_script',$script);
				}
			}
		}

		ob_start();
		ob_implicit_flush(false);
		if ($this->parent->manifestClass && method_exists($this->parent->manifestClass,'preflight'))
		{
			if($this->parent->manifestClass->preflight($this->route, $this) === false)
			{
				$this->parent->abort(JText::_('COM_SEF_INSTALLER_ABORT_SEF_INSTALL_CUSTOM_INSTALL_FAILURE'));
				return false;
			}
		}
		$msg = ob_get_contents();
		ob_end_clean();

		if($this->parent->parseSQLFiles($xml->uninstall->sql)===FALSE) {
			$this->parent->abort(JText::sprintf('COM_SEF_INSTALLER_ABORT_PLG_UNINSTALL_SQL_ERROR', $db->stderr(true)));
			return false;
		}

		ob_start();
		ob_implicit_flush(false);
		if ($this->parent->manifestClass && method_exists($this->parent->manifestClass,'uninstall')) {
			$this->parent->manifestClass->uninstall($this);
		}
		$msg = ob_get_contents();
		ob_end_clean();

		$this->parent->removeFiles($xml->files, -1);
		JFile::delete($manifest_file);

		$query=$db->getQuery(true);
		$query->delete()->from('#__schemas')->where('extension_id='.$row->extension_id);
		$db->setQUery($query);
		$db->query();

		$row->delete($row->extension_id);
		unset($row);

		if ($msg) {
			$this->parent->set('extension_message',$msg);
		}

        return true;
    }

    function discover() {
    	$results=array();
		$list=JFolder::files(JPATH_ROOT.'/components/com_sef/sef_ext');

		foreach($list as $sef) {
			if(substr($sef,-4)!='.xml') {
				continue;
			}

			$xml=simplexml_load_file(JPATH_ROOT.'/components/com_sef/sef_ext/'.$sef);
            
			if (count($xml->files->children()))	{
				foreach ($xml->files->children() as $file)	{
					if ((string)$file->attributes()->sef_ext) {
						$element = (string)$file->attributes()->sef_ext;
						if(substr($element,0,13)!='ext_joomsef4_') {
							$element='ext_joomsef4_'.$element;
						}
						break;
					}
				}
			}
			$extension=JTable::getInstance('extension');
			$extension->set('type','sef_ext');
			$extension->set('client_id',0);
			$extension->set('element',$element);
			$extension->set('name',(string)$xml->name);
			$extension->set('state',-1);
			$extension->set('manifest_cache',json_encode(JApplicationHelper::parseXMLInstallFile(JPATH_ROOT.'/components/com_sef/sef_ext/'.$sef)));
			$results[]=clone $extension;
		}
    	return $results;
    }

    function discover_install() {
        $option = str_replace('ext_joomsef4_', 'com_', $this->parent->extension->element);
    	$manifest_path=JPATH_ROOT.'/components/com_sef/sef_ext/'.$option.'.xml';
    	$this->parent->manifest=$this->parent->isManifest($manifest_path);
        
        if (!is_object($this->parent->manifest)) {
            JError::raiseWarning(101, JText::_('COM_SEF_INSTALLER_ERROR_SEF_DISCOVER_STORE_DETAILS'));
			return false;
        }
        
    	$description=(string)$this->parent->manifest->description;

		if($description) {
			$this->parent->set('message',$description);
		} else {
			$this->parent->set('message');
		}

		$this->parent->setPath('manifest',$manifest_path);
		$manifest_details = JApplicationHelper::parseXMLInstallFile($this->parent->getPath('manifest'));
		$this->parent->extension->manifest_cache = json_encode($manifest_details);
		$this->parent->extension->state = 0;
		$this->parent->extension->name = $manifest_details['name'];
		$this->parent->extension->enabled = 1;
		if(isset($this->manifest->install->defaultparams)) {
			$this->parent->extension->params = SEFTools::getDefaultParams((string)$this->manifest->install->defaultparams);
		}
		if(isset($this->manigest->install->defaultfilters)) {
			$this->parent->extension->custom_data=SEFTools::getDefaultFilters((string)$this->manigest->install->defaultfilters);
		}
		if ($this->parent->extension->store()) {
			return $this->parent->extension->get('extension_id');
		} else {
			JError::raiseWarning(101, JText::_('COM_SEF_INSTALLER_ERROR_SEF_DISCOVER_STORE_DETAILS'));
			return false;
		}

		$utfresult = $this->parent->parseSQLFiles($this->manifest->install->sql);
		if ($utfresult === false)
		{
			JError::raiseWarning(JText::sprintf('COM_SEF_INSTALLER_ABORT_SEF_INSTALL_SQL_ERROR', JText::_('JLIB_INSTALLER_'.$this->route), $db->stderr(true)));
			return false;
		}

		if($this->manifest->update) {
			$this->parent->setSchemaVersion($this->manifest->update->schemas, $row->extension_id);
		}
    }

    function refreshManifestCache() {
        $file = str_replace('ext_joomsef4_', 'com_', $this->parent->extension->element);
    	$manifest_path=JPATH_ROOT.'/components/com_sef/sef_ext/'.$file.'.xml';
    	$this->parent->manifest=$this->parent->isManifest($manifest_path);
    	$this->parent->setPath('manifest',$manifest_path);
    	$manifest_details = JApplicationHelper::parseXMLInstallFile($this->parent->getPath('manifest'));
    	$this->parent->extension->manifest_cache = json_encode($manifest_details);
		$this->parent->extension->name = $manifest_details['name'];
		if (!$this->parent->extension->store()) {
			JError::raiseWarning(101, JText::_('COM_SEF_INSTALLER_ERROR_SEF_REFRESH_MANIFEST_CACHE'));
			return false;
		}
		return true;
    }
}