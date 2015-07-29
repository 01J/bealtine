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

class plgExtensionJoomSEFInstall extends JPlugin {
	function __construct($plugin) {
		parent::__construct($plugin);
	}

	function onExtensionAfterInstall($installer,$eid) {
		$this->_processUpdateSites($installer->manifest);
	}

	function onExtensionAfterUpdate($installer,$eid) {
		$this->_processUpdateSites($installer->manifest);
	}

	private function _processUpdateSites($xml) {
        if (!is_object($xml)) {
            return;
        }
        
		$db=JFactory::getDBO();
		$free=false;
		$freecnt=array();
		$pay=false;

		if((string)$xml->name=='com_sef' || (string)$xml['type']=='sef_ext') {
			if((string)$xml->name=='com_sef') {
				$name='com_joomsef';
			} else {
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
				$name=$element;
			}

			$query=$db->getQuery(true);
			$query->select('update_site_id, location')->from('#__update_sites')->where('name='.$db->quote($name));
			$db->setQuery($query);
			$updates=$db->loadObjectList();


			if(count($updates)>0) {
				for($i=0;$i<count($updates);$i++) {
					if($updates[$i]->location==(string)$xml->updateservers->server) {
						$free=true;
						$freecnt[]=$updates[$i]->update_site_id;
					}
					if($updates[$i]->location!=(string)$xml->updateservers->server) {
						$pay=true;
					}
				}
			}

			if($free==true && $pay==true) {
				$query=$db->getQuery(true);
				$query->delete('#__update_sites')->where('location='.$db->quote((string)$xml->updateservers->server));
				$db->setQUery($query);
				$db->query();
			} else if(count($freecnt)>1) {
				array_shift($freecnt);

				$query=$db->getQuery($query);
				$query->delete('#__update_sites')->where('update_site_id IN('.implode(",",$freecnt).')');
				$db->setQuery($query);
				$db->query();
			}
		}
	}
}
?>