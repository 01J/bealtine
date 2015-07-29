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

define('_COM_SEF_HANDLER_EXTENSION', 0);
define('_COM_SEF_HANDLER_ROUTER',    1);
define('_COM_SEF_HANDLER_DEFAULT',   2);
define('_COM_SEF_HANDLER_BASIC',     3);
define('_COM_SEF_HANDLER_JOOMLA',    4);
define('_COM_SEF_HANDLER_NONE',      5);

class SEFModelExtensions extends SEFModel
{
    var $_extensions;
    var $_components;
    var $_componentsNoExt;
    var $_newVersions;

    function __construct()
    {
        parent::__construct();
    }

    function getExtensions()
    {
        if( !isset($this->_extensions) ) {
            // Try to get the newest versions information from upgrade server
            $this->_loadNewVersions();

			$query=$this->_db->getQuery(true);
			$query->select('element')->from('#__extensions')->where('type='.$this->_db->quote('sef_ext'));
	        $query->where('state>=0');
	        $query->where('enabled=1');
	        $this->_db->setQuery($query);
	        $sefs=$this->_db->loadColumn();

            $path = JPATH_ROOT.'/components/com_sef/sef_ext';

            $exts = array();
            if( is_array($sefs) && (count($sefs) > 0) ) {
                foreach($sefs as $sef) {
                	$option=str_replace('ext_joomsef4_','com_',$sef);
                    $xml = $this->_isManifest($path.'/'.$option.'.xml');
                    if (!is_null($xml)) {
                        $ext = new stdClass();
                        $ext->id = $sef;

						$ext->option = $option;
                        $ext->component = $this->_getComponent($option);

                        $ext->name          = (string)$xml->name;
                        $ext->creationdate  = (string)$xml->creationDate;
                        $ext->author		= (string)$xml->author;
                        $ext->copyright	    = (string)$xml->copyright;
                        $ext->authorEmail	= (string)$xml->authorEmail;
                        $ext->authorUrl	    = (string)$xml->authorUrl;
                        $ext->version		= (string)$xml->version;

                        if (isset($this->_newVersions[$ext->id])) {
                            $ext->newestVersion = $this->_newVersions[$ext->id]->version;
                            $ext->type = $this->_newVersions[$ext->id]->type;
                        }
                        else {
                            $ext->newestVersion = null;
                            $ext->type = null;
                        }

                        // Load parameters
                        $ext->params =& SEFTools::getExtParams($ext->option);

                        // Active handler
                        $ext->handler = $this->_getActiveHandler($ext->option);

                        $exts[$ext->option] = $ext;
                    }
                }
            }

            $this->_extensions = $exts;
        }

        return $this->_extensions;
    }

    function getComponentsWithoutExtension()
    {
        if( !isset($this->_componentsNoExt) ) {
            $this->_loadComponents();
            $this->getExtensions();
            $this->_loadNewVersions();

            $this->_componentsNoExt = array();

            // Loop through the components and find those without installed extension
            if( is_array($this->_components) && (count($this->_components) > 0) ) {
                foreach($this->_components as $component) {
                    if( isset($this->_extensions[$component->option]) ) {
                        continue;
                    }

                    $cmp = new stdClass();
                    $cmp = $component;
                    $cmp->id=str_replace('com_','ext_joomsef4_',$cmp->option);

                    if( isset($this->_newVersions[$cmp->id]) ) {
                        $cmp->extType = $this->_newVersions[$cmp->id]->type;
                        $cmp->extVersion = $this->_newVersions[$cmp->id]->version;
                        $cmp->extLink = $this->_newVersions[$cmp->id]->link;
                    }
                    else {
                        $cmp->extType = null;
                        $cmp->extVersion = null;
                        $cmp->extLink = null;
                    }

                    // Load component parameters
                    $cmp->params =& SEFTools::getExtParams($cmp->option);

                    // Active handler
                    $cmp->handler = $this->_getActiveHandler($cmp->option);

                    $this->_componentsNoExt[$cmp->option] = $cmp;
                }
            }
        }

        return $this->_componentsNoExt;
    }

    function _isManifest($file)
    {
        // Initialize variables
        $xml = simplexml_load_file($file);

        // If we cannot load the xml file return null
        if ($xml === false) {
            return null;
        }

        /*
         * Check for a valid XML root tag.
         */
        if (($xml->getName() != 'extension') ||
            version_compare((string)$xml['version'], '1.6', '<') ||
            ((string)$xml['type'] != 'sef_ext'))
        {
            // Free up xml parser memory and return null
            unset ($xml);
            return null;
        }

        // Valid manifest file return the object
        return $xml;
    }

    function _loadComponents()
    {
        if( isset($this->_components) ) {
            return;
        }

        $this->_components = SEFTools::getInstalledComponents();
    }

    function _getComponent($option)
    {
        $this->_loadComponents();

        if( isset($this->_components[$option]) ) {
            return $this->_components[$option];
        }

        return null;
    }

    function _loadNewVersions()
    {
        if( isset($this->_newVersions) ) {
            return;
        }

        $upgradeModel = SEFModel::getInstance('Upgrade', 'SEFModel');
        $this->_newVersions =& $upgradeModel->getVersions();
        /*echo "<pre>";
        print_r($this->_newVersions);
        echo "</pre>";
        exit;*/
    }

    function _getActiveHandler($option)
    {
        $params =& SEFTools::getExtParams($option);

        $handler = $params->get('handling', '0');
        $ret = new stdClass();
        switch($handler)
        {
            case '0':
                $compExt = JFile::exists(JPATH_ROOT.'/components/'.$option.'/router.php');
                $ownExt = JFile::exists(JPATH_ROOT.'/components/com_sef/sef_ext/'.$option.'.php');

                if( $compExt && !$ownExt ) {
                    $ret->text = JText::_('COM_SEF_COMPONENTS_ROUTER');
                    $ret->code = _COM_SEF_HANDLER_ROUTER;
                    $ret->color = 'black';
                }
                else if( $ownExt ) {
                    $ret->text = JText::_('COM_SEF_JOOMSEF_EXTENSION');
                    $ret->code = _COM_SEF_HANDLER_EXTENSION;
                    $ret->color = 'green';
                }
                else {
                    $ret->text = JText::_('COM_SEF_JOOMSEF_DEFAULT_HANDLER');
                    $ret->code = _COM_SEF_HANDLER_DEFAULT;
                    $ret->color = 'green';
                }
                break;

            case '1':
                $ret->text = JText::_('COM_SEF_DEFAULT_JOOMLA_ROUTER');
                $ret->code = _COM_SEF_HANDLER_JOOMLA;
                $ret->color = 'orange';
                break;

            case '2':
                $ret->text = JText::_('COM_SEF_NOT_USING_SEF');
                $ret->code = _COM_SEF_HANDLER_NONE;
                $ret->color = 'red';
                break;

            case '3':
                $ret->text = JText::_('COM_SEF_JOOMSEF_BASIC_REWRITING');
                $ret->code = _COM_SEF_HANDLER_BASIC;
                $ret->color = 'green';
                break;
        }

        return $ret;
    }
}
?>