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

defined('_JEXEC') or die('Restricted access');

jimport('joomla.updater.updateadapter');
require_once JPATH_ADMINISTRATOR.'/components/com_sef/classes/seftools.php';

class JUpdaterSEF_Update extends JUpdateAdapter {
	protected $base=null;
	protected $update_sites=null;
	protected $updates=null;

	function findUpdate($data) {
		$this->_update_site_id=$data["update_site_id"];
		$this->base=array();
		$this->update_sites=array();
		$this->updates=array();

		$ext_data=SEFTools::postRequest($data['location']);
		$details=$ext_data->content;

		$db=JFactory::getDBO();

		if(!strlen($details)) {
			$query=$db->getQuery(true);
			$query->update('#__update_sites')->set('enabled=0')->where('update_site='.$data["update_site_id"]);
			$db->query();
			JError::raiseWarning('101', JText::sprintf('JLIB_UPDATER_ERROR_SEFEXT_OPEN_URL', $data["url"]));
			return false;
		}

		if(preg_match("/^[0-9]{2}/",$details,$status)) {
			$details=$status[0];

			$url=explode("/",$data['location']);
			$ext=array_pop($url);
			$ext=str_replace('.xml','',$ext);
			if(substr_count($ext,"-")) {
				$ext_name_arr=explode("-",$ext);
				$ext_name=$ext_name_arr[0];
			} else {
				$ext_name=$ext;
			}

			if(substr_count($ext,'com_joomsef4')) {
				$data["name"]="Artio JoomSEF 4";	
			} else {
				$query=$db->getQuery(true);
				$query->select('name')->from('#__extensions')->where('element='.$db->quote($ext_name))->where('type='.$db->quote('sef_ext'));
				$db->setQuery($query);
				$data["name"]=$db->loadResult();
                if (is_null($data['name'])) {
                    // Extension not found
                    return true;
                }
                $data['name'] .= ' (JoomSEF extension)';
			}
		}

		switch($details) {
			case '20':
				JError::raiseWarning('101', $data["name"].': '.JText::sprintf('COM_SEF_EXPIRED', $data["name"]));
				break;
			case '30':
				JError::raiseWarning('101', $data["name"].': '.JText::sprintf('COM_SEF_NOT_ACTIVATED',$data["name"]));
				break;
			case '40':
				JError::raiseWarning('101', $data["name"].': '.JText::sprintf('COM_SEF_ERR_DOMAIN_NOT_MATCH',$data["name"]));
				break;
			case '50':
				JError::raiseWarning('101', $data["name"].': '.JText::sprintf('COM_SEF_DOWLOAD_ID_INVALID',$data["name"]));
				break;
			case '90':
				// Commercial extension
				$location_match=array();
				if(preg_match("/(-([A-Za-z0-9]*)).xml/",$data["location"],$location_match)) {
					JError::raiseWarning('101', $data["name"].': '.JText::sprintf('COM_SEF_ERROR_DOWNLOAD_ID_NOT_FOUND',$location_match[2]));
				// Free extension
				} else {
					JError::raiseWarning('101', $data["name"].': '.JText::sprintf('COM_SEF_NOT_FOUND',$data["name"]));
				}
				break;
			default:
				$xml=simplexml_load_string($details);
				if(is_object($xml)) {
					foreach($xml->children() as $update) {
						$table=JTable::getInstance('update');
						$table->update_site_id=$this->_update_site_id;
						$table->version=(string)$update->version;
						if ((string)$update->element == 'com_joomsef4') {
							$sef_version=SEFTools::getSEFVersion();
							$table->name=(string)$update->name;
							$table->element='com_sef';
							$table->client_id=1;
							$table->type='component';
						} else {
							$sefext_version=SEFTools::getExtVersion((string)$update->name);
							$table->name=(string)$update->name;
							$table->element=(string)$update->element;
							$table->client_id=0;
							$table->type='sef_ext';
						}

						$table->detailsurl=$data["location"];
						$this->updates[]=$table;

					}
				}
		}

		return array('update_sites'=>$this->update_sites,'updates'=>$this->updates);
	}
}
?>