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

// Check ACL
if (!JFactory::getUser()->authorise('core.manage', 'com_sef'))
{
	return JError::raiseWarning(404, JText::_('JERROR_ALERTNOAUTHOR'));
}

// Load language
$lang = JFactory::getLanguage();
$source = JPATH_ADMINISTRATOR . '/components/com_sef';
    $lang->load("com_sef.sys", JPATH_ADMINISTRATOR, null, false, false)
||  $lang->load("com_sef.sys", $source, null, false, false)
||  $lang->load("com_sef.sys", JPATH_ADMINISTRATOR, $lang->getDefault(), false, false)
||  $lang->load("com_sef.sys", $source, $lang->getDefault(), false, false);

// Load the CSS
$document = & JFactory::getDocument();
$document->addStyleSheet('components/com_sef/assets/css/default.css');
if (version_compare(JVERSION, '3.0', '>=')) {
    $document->addStyleSheet('components/com_sef/assets/css/joomla3.css');
}

// Require the base classes
require_once (JPATH_COMPONENT.'/controller.php');
require_once (JPATH_COMPONENT.'/model.php');
require_once (JPATH_COMPONENT.'/view.php');
require_once (JPATH_COMPONENT.'/classes/config.php');
require_once (JPATH_COMPONENT.'/classes/seftools.php');

// Require specific controller if requested
if($controller = JRequest::getVar('controller')) {
	$path = JPATH_COMPONENT.'/controllers/'.$controller.'.php';
	if( file_exists($path) ) {
	    require_once($path);
	} else {
	    $controller = '';
	}
}

// Create the controller
$classname	= 'SEFController'.$controller;
$controller = new $classname( );

// Perform the Request task
$controller->execute( JRequest::getVar('task') );

// Redirect if set by the controller
$controller->redirect();

?>