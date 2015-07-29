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

jimport('joomla.plugin.plugin');
require_once JPATH_SITE.'/components/com_sef/joomsef.php';

class plgContentJoomSEF extends JPlugin {
	function __construct(&$subject,$config) {
		parent::__construct($subject,$config);	
	}
	
	function onContentAfterSave($context,$item,$isNew) {
		$context=explode(".",$context);
		$option=$context[0];
			
		if($option=='com_categories' && strlen(($extension=JRequest::getString('extension',''))!='')) {
			$option=$extension;
		}
		
		$sef=JoomSEF::getInstance();
		if($isNew==false) {
			$sef->_checkURLs($option,$item);
		}
	}
	
	function onContentAfterDelete($context,$item) {
		$context=explode(".",$context);
		$option=$context[0];
		
		JoomSEF::_removeURL($option,$item);
	}
} 
?>