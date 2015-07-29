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

class plgSystemJoomSEFLang extends JPlugin {
	function __construct($plugin) {
		parent::__construct($plugin);
	}

	function onAfterInitialise() {
		if(JFactory::getApplication()->isAdmin()==false) {
			return true;
		}

		JFactory::getLanguage()->load('com_sef',JPATH_ADMINISTRATOR);
	}
}
?>