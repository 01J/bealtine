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
jimport('joomla.installer.installer');
jimport('joomla.installer.helper');

require_once(JPATH_COMPONENT.'/helpers/artio-update.php');

class SEFModelExtension extends SEFModel
{
    /**
     * Constructor that retrieves the ID from the request
     *
     * @access    public
     * @return    void
     */
    function __construct()
    {
        parent::__construct();

        $array = JRequest::getVar('cid',  array(0), '', 'array');
        $this->setId($array[0]);
    }

    function setId($id)
    {
        // Set id and wipe data
        $this->_id          = $id;
        $this->_extension   = null;
    }

    function getExtension()
    {
        // Load the data
        if (empty($this->_extension)) {
        	$query = $this->_db->getQuery(true);
        	$query->select('*')->from('#__extensions')->where('state>=0')->where('enabled=1')->where('type='.$this->_db->quote('sef_ext'))->where('element='.$this->_db->quote($this->_id));
        	$this->_db->setQuery($query);
        	$row = $this->_db->loadObject();
            if (is_null($row)) {
                $row = new stdClass();
            }

			$option = str_replace('ext_joomsef4_', 'com_', $this->_id);
            
            // Try to load language file for this extension
            $langFile = str_replace('com_', 'sef_ext_', $option);
            $lang = JFactory::getLanguage();
            $lang->load($langFile, JPATH_ADMINISTRATOR);

			$row->id = $this->_id;
            $row->description = '';
            $row->name = '';
            $row->version = '';
            $row->params =& SEFTools::getExtParams($option);
            $row->form =& SEFTools::getExtParamsForm($option);
            $row->option = $option;

            $xml = SEFTools::getExtXML($option);
            if ($xml) {
                $version = (string)$xml['version'];
                if (($xml->getName() == 'extension') && version_compare($version, '1.6', '>=') && ((string)$xml['type'] == 'sef_ext')) {
                    $element = $xml->description;
                    $row->description = $element ? trim( (string)$element ) : '';

                    $element = $xml->name;
                    $row->name = $element ? trim( (string)$element ) : '';

                    $element = $xml->version;
                    $row->version = $element ? trim( (string)$element ) : '';
                }
            }

            // Get the component for this extension
            $model = SEFModel::getInstance('Extensions', 'SEFModel');
            $row->component = $model->_getComponent($option);

            $this->_extension = $row;
        }

        return $this->_extension;
    }

    function getLanguages() {
    	return $this->_langs = JLanguageHelper::getLanguages();
    }

    function getStrings() {
    	$query="SELECT DISTINCT name \n";
    	$query.="FROM #__sefexttexts \n";
		$query.="WHERE extension=".$this->_db->quote($this->_extension->option);
		$this->_db->setQuery($query);
		return $this->_strings=$this->_db->loadObjectList();
    }

	function getTranslation() {
		$query="SELECT name, value, lang_id \n";
		$query.="FROM #__sefexttexts \n";
		$query.="WHERE extension=".$this->_db->quote($this->_extension->option);
		$this->_db->setQuery($query);
		$data=$this->_db->loadObjectList();

		$ndata=array();
		for($i=0;$i<count($data);$i++) {
			$ndata[$data[$i]->lang_id][$data[$i]->name]=$data[$i]->value;
		}
		return $ndata;
	}
	
	private function _getMenuItems($lang) {
    	$db = JFactory::getDbo();
		$db->setQuery(
			'SELECT menutype AS value, title AS text' .
			' FROM #__menu_types' .
			' ORDER BY title'
		);
		$menus = $db->loadObjectList();

		$query	= $db->getQuery(true);
		$query->select('a.id AS value, a.title AS text, a.level, a.menutype');
		$query->from('#__menu AS a');
		$query->where('a.parent_id > 0');
		$query->where('a.type <> '.$db->quote('url'));
		$query->where('a.client_id = 0');
		$query->where('a.language IN('.$db->quote($lang).','.$db->quote('*').')');

		$query->order('a.lft');

		$db->setQuery($query);
		$items = $db->loadObjectList();

		// Collate menu items based on menutype
		$lookup = array();
		foreach ($items as &$item) {
			if (!isset($lookup[$item->menutype])) {
				$lookup[$item->menutype] = array();
			}
			$lookup[$item->menutype][] = &$item;

			$item->text = str_repeat('- ', $item->level).$item->text;
		}
		$items = array();

		foreach ($menus as &$menu) {
			// Start group:
			$items[] = JHtml::_('select.optgroup',	$menu->text);

			// Menu items:
			if (isset($lookup[$menu->value])) {
				foreach ($lookup[$menu->value] as &$item) {
					$items[] = JHtml::_('select.option', $item->value, $item->text);
				}
			}

			// Finish group:
			$items[] = JHtml::_('select.optgroup',	$menu->text);
		}
		return $items;
	}
	
	function getSubDomains() {
		$query=$this->_db->getQuery(true);
		$query->select('subdomain, lang, Itemid_titlepage');
		$query->from('#__sef_subdomains');
		$query->where('`option`='.$this->_db->quote(str_replace('ext_joomsef4_','com_',$this->_id)));
		$this->_db->setQuery($query);
		return $this->_db->loadObjectList('lang');
	}
	
	function getMenus() {
		$data=array();
		foreach($this->_langs as $lang) {
			$data[$lang->sef]=$this->_getMenuItems($lang->lang_code);
		}
		return $data;
	}

    function store()
    {
        $query=$this->_db->getQuery(true);
        $query->select('extension_id')->from('#__extensions')->where('(state>=0 OR state=-2)')->where('type='.$this->_db->quote('sef_ext'))->where('element='.$this->_db->quote(JRequest::getCmd('element')));
        $this->_db->setQUery($query);
        $id=$this->_db->loadResult();

        $post = JRequest::get('post');
        JTable::addIncludePath(JPATH_LIBRARIES.'/joomla/database/table');
		$row=JTable::getInstance('extension');
		$row->load($id);

        // Bind the form fields to the table
        if (!$row->bind($post,array('params'))) {
            JError::raiseError(500, $row->getError() );
        }
        $row->type='sef_ext';
        if(!$id) {
        	$row->state=-2;
        }


        // Save params
        $params = JRequest::getVar( 'params', array(), 'post', 'array' );
        if (is_array( $params )) {
            $p = new JRegistry($row->params);
            $p->loadArray($params);
            $row->params = $p->toString();
        }

		if(isset($row->state) && $row->state>=0) {
	        if (!AUpdateHelper::setUpdateLink($row->element, $params['downloadId'])) {
	           return false;
	        }
		}

        $row->custom_data=$post["filters"];
        
        $subdomains=JRequest::getVar('subdomain',array(),'post','array');
        $query="DELETE FROM #__sef_subdomains \n";
		$query.="WHERE `option`=".$this->_db->quote(str_replace("ext_joomsef4_","com_",JRequest::getString('element')))." \n";		
		$this->_db->setQuery($query);
		if(!$this->_db->query()) {
			JError::raiseError(500, $this->_db->stderr(true) );
			return false;
		}
		
        foreach($subdomains as $lang=>$item) {
        	if(strlen($item["title"])) {
        		$query="INSERT INTO #__sef_subdomains \n";
        		$query.="SET subdomain=".$this->_db->quote($item["title"]).", `option`=".$this->_db->quote(str_replace("ext_joomsef4_","com_",JRequest::getString('element'))).", \n";
        		$query.="Itemid_titlepage=".$this->_db->quote($item["titlepage"]).", lang=".$this->_db->quote($lang)." \n";
        		/*$query.="ON DUPLICATE KEY UPDATE `option`=".$this->_db->quote(str_replace("ext_joomsef4_","com_",JRequest::getString('element'))).", \n";
        		$query.="Itemid_titlepage=".$this->_db->quote($item["titlepage"]).", lang=".$this->_db->quote($lang)." \n";*/
        		$this->_db->setQuery($query);
        		if(!$this->_db->query()) {
        			JError::raiseError(500, $this->_db->stderr(true) );
        			return false;
        		}
        	}
        }

        // Store the table to the database
        if (!$row->store()) {
            JError::raiseError(500, $row->getError() );
        }

        //$ext=str_replace('.xml','',$row->file);
        if(!$this->_storeTranslation(JRequest::getCmd('element'))) {
        	return false;
        }

        return true;
    }

	private function _storeTranslation($ext) {
    	$texts=JRequest::getVar('texts',array(),'post','array');
        $ext = str_replace('ext_joomsef4_', 'com_', $ext);

    	$query="DELETE FROM #__sefexttexts \n";
    	$query.="WHERE extension=".$this->_db->quote($ext);
    	$this->_db->setQuery($query);
    	if(!$this->_db->query()) {
    		$this->setError($this->_db->stderr(true));
    		return false;
    	}

    	$query="INSERT INTO #__sefexttexts (extension,name,value,lang_id) VALUES \n";
    	$query_arr=array();
    	foreach($texts as $lang_id=>$data) {
			foreach($data as $name=>$value) {
				if(strlen($value)==0) {
					continue;
				}
				$query_arr[]="(".$this->_db->quote($ext).",".$this->_db->quote($name).",".$this->_db->quote($value).",".$this->_db->quote($lang_id).")";
			}
    	}
    	if(count($query_arr)) {
    		$query.=implode(",",$query_arr);
    		$this->_db->setQuery($query);
    		if(!$this->_db->query()) {
    			$this->setError($this->_db->stderr(true));
    			return false;
    		}
    	}
    	return true;
    }

    function storeId()
    {
        $ext_name = JRequest::getVar('ext');
        $download_id=JRequest::getVar('downloadid','');
        $db=JFactory::getDBO();

        if (is_null($ext_name)) {
            return false;
        }

        $query=$db->getQuery(true);
		$query->select('state, params')->from('#__extensions')->where('(state>=0 OR state=-2)')->where('enabled=1')->where('type='.$db->quote('sef_ext'))->where('element='.$db->quote($ext_name));
		$db->setQuery($query);
		$ext=$db->loadObject();

		$params=new JRegistry(isset($ext->params)?$ext->params:null);
        $params->set('downloadId', JRequest::getVar('downloadid', ''));
        $nparams = $params->toString();

        $query=$db->getQuery(true);
        if(isset($ext->state)) {
        	$query->update('#__extensions')->set('params='.$db->quote($nparams))->where('(state>=0 OR state=-2)')->where('enabled=1')->where('type='.$db->quote('sef_ext'))->where('element='.$db->quote($ext_name));
        } else {
        	$query->insert('#__extensions')->set('params='.$db->quote($nparams))->set('state=-2')->set('type='.$db->quote('sef_ext'))->set('element='.$db->quote($ext_name));
        }
        $db->setQuery($query);
        if(!$db->query()) {
        	echo $db->stderr(true);
        	$this->setError($db->stderr(true));
        	return false;
        }

		if(isset($ext->state) && $ext->state>=0) {
	        if (!AUpdateHelper::setUpdateLink($ext_name, $download_id)) {
	           return false;
	        }
		}

        jexit();
    }

    function changeHandler()
    {
        $ext = JRequest::getVar('ext');
        $db=JFactory::getDBO();

        if (is_null($ext)) {
            return false;
        }

		$query=$db->getQuery(true);
		$query->select('state, params')->from('#__extensions')->where('(state>=0 OR state=-2)')->where('enabled=1')->where('type='.$db->quote('sef_ext'))->where('element='.$db->quote($ext));
		$db->setQuery($query);
		$ext_o=$db->loadObject();

		$params=new JRegistry(isset($ext_o->params)?$ext_o->params:null);
        $handlers = array(0 => 3, 3 => 1, 1 => 2, 2 => 0);
        $handler = intval($params->get('handling', 0));
        $handler = $handlers[$handler];
        $params->set('handling', $handler);
        $nparams = $params->toString();

        $query=$db->getQuery(true);
        if(isset($ext_o->state)) {
        	$query->update('#__extensions')->set('params='.$db->quote($nparams))->where('(state>=0 OR state=-2)')->where('enabled=1')->where('type='.$db->quote('sef_ext'))->where('element='.$db->quote($ext));
        } else {
        	$query->insert('#__extensions')->set('params='.$db->quote($nparams))->set('state=-2')->set('type='.$db->quote('sef_ext'))->set('element='.$db->quote($ext));
        }
        //$query->update('#__extensions')->set('params='.$db->quote($nparams))->where('state>=0')->where('enabled=1')->where('type='.$db->quote('sef_ext'))->where('element='.$db->quote($ext));
        $db->setQuery($query);
        if(!$db->query()) {
        	$this->setError($db->stderr(true));
        	return false;
        }

        return true;
    }

    function install()
    {
        $mainframe =& JFactory::getApplication();

        switch( JRequest::getVar('installtype') )
        {
            case 'folder':
                $package = $this->_getPackageFromFolder();
                break;

            case 'upload':
                $package = $this->_getPackageFromUpload();
                break;

            case 'server':
                $package = $this->_getPackageFromServer();
                break;

            default:
                $this->setState('message', 'No Install Type Found');
                $this->setState('result', false);
                return false;
                break;
        }

        // Was the package unpacked?
        if (!$package) {
            $this->setState('message', 'Unable to find install package');
            $this->setState('result', false);
            return false;
        }

        // Get an installer object for the extension type
        jimport('joomla.installer.installer');
        $installer =& JInstaller::getInstance();

        require_once(JPATH_COMPONENT.'/adapters/sef_ext.php');
        $adapter = new JInstallerSef_Ext($installer);
        $adapter->parent =& $installer;
        $installer->setAdapter('sef_ext', $adapter);

		// Install the package
		if (!$installer->install($package['dir'])) {
			// There was an error installing the package
			$msg = JText::_('COM_SEF_SEF_EXTENSION').' '.JText::_('COM_SEF_INSTALL').': '.JText::_('COM_SEF_ERROR');
			$result = false;
		} else {
			// Package installed sucessfully
			$msg = JText::_('COM_SEF_SEF_EXTENSION').' '.JText::_('COM_SEF_INSTALL').': '.JText::_('COM_SEF_SUCCESS');
			$result = true;
		}

		// Set some model state values
		$mainframe->enqueueMessage($msg);
		$this->setState('name', $installer->get('name'));
		$this->setState('result', $result);
		$this->setState('message', $installer->message);
		$this->setState('extension.message', $installer->get('extension.message'));

		// Cleanup the install files
		if (!is_file($package['packagefile'])) {
			$config =& JFactory::getConfig();
			$package['packagefile'] = $config->get('tmp_path').'/'.$package['packagefile'];
		}

		JInstallerHelper::cleanupInstall($package['packagefile'], $package['extractdir']);

		return $result;
    }

    function _getPackageFromFolder()
    {
        // Get the path to the package to install
        $p_dir = JRequest::getString('install_directory');
        $p_dir = JPath::clean( $p_dir );

        // Did you give us a valid directory?
        if( !is_dir($p_dir) ) {
            JError::raiseWarning(100, JText::_('COM_SEF_ENTER_PACKAGE_DIRECTORY'));
            return false;
        }

        // Detect the package type
        $type = JInstallerHelper::detectType( $p_dir );

        // Did you give us a valid package?
        if( !$type || ($type != 'sef_ext') ) {
            JError::raiseWarning(100, JText::_('COM_SEF_ERROR_NO_VALID_PACKAGE'));
            return false;
        }

        $package['packagefile'] = null;
        $package['extractdir'] = null;
        $package['dir'] = $p_dir;
        $package['type'] = $type;

        return $package;
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
        $tmp_dest 	= $config->get('tmp_path').'/'.$userfile['name'];
        $tmp_src	= $userfile['tmp_name'];

        // Move uploaded file
        jimport('joomla.filesystem.file');
        $uploaded = JFile::upload($tmp_src, $tmp_dest);

        // Unpack the downloaded package file
        $package = JInstallerHelper::unpack($tmp_dest);

        return $package;
    }

    function _getPackageFromServer()
    {
        $extension = trim(JRequest::getString('extension'));

        // Make sure we have an extension selected
        if( empty($extension) ) {
            JError::raiseWarning(100, JText::_('COM_SEF_NO_EXTENSION_SELECTED'));
            return false;
        }

        // Make sure that zlib is loaded so that the package can be unpacked
        if (!extension_loaded('zlib')) {
            JError::raiseWarning(100, JText::_('COM_SEF_WARNINSTALLZLIB'));
            return false;
        }

        // build the appropriate paths
        $sefConfig =& SEFConfig::getConfig();
        $config =& JFactory::getConfig();
        $tmp_dest = $config->get('tmp_path').'/'.$extension.'.zip';

        // Validate the upgrade on server
        $data = array();
        $data['username'] = $sefConfig->artioUserName;
        $data['password'] = $sefConfig->artioPassword;
        $params =& SEFTools::getExtParams($extension);
        $data['download_id'] = $params->get('downloadId', '');
        $data['file'] = 'ext_joomsef4_' . substr($extension, 4);
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

    function delete()
    {
        // Get an installer object for the extension type
        jimport('joomla.installer.installer');
        $installer =& JInstaller::getInstance();

        require_once(JPATH_COMPONENT.'/adapters/sef_ext.php');
        $adapter = new JInstallerSef_Ext($installer);
        $installer->setAdapter('sef_ext', $adapter);

        $result = $installer->uninstall('sef_ext', $this->_id, 0);

        return $result;
    }

}
?>