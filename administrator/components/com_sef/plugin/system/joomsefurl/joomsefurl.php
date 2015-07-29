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

class plgSystemJoomSEFUrl extends JPlugin {
	function __construct(&$subject,$config) {
		parent::__construct($subject,$config);
	}
	
	function onAfterRender() {
		$db=JFactory::getDBO();
		if(JFactory::getApplication()->isAdmin()) {
			return;
		}
		if(JFactory::getApplication()->getCfg('sef')==0) {
			return;
		}
		if (JRequest::getVar('tmpl') == 'component') {
			return;
		}
        if (JRequest::getVar('format', 'html') != 'html') {
            return;
        }
		
		
		$body=JResponse::getBody();
		$doc=new DomDocument("1.0");
		$doc->loadHTML($body);
		$xpath=new DomXPath($doc);
		
		$hrefs=$xpath->query("//a");
		foreach($hrefs as $href) {
			$link=$href->getAttribute('href');
			if(JFactory::getURI()->isInternal($link)==false) {
				continue;
			}
			
			$link=substr($link,1);
			
			if($this->params->get('raw')) {
				$origurl=JoomSEF::_createUri(new JURI($link));
			} else {
				$origurl=JoomSEF::getNonSEFURL($link);
			}
			
			
			if(strlen($origurl)) {
				$href->setAttribute('class','link_tip');
				$href->setAttribute('title',$origurl);
			}		
		}
		
		$body=$doc->saveHTML();
		JResponse::setBody($body);
	}
}
?>