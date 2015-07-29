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
defined('_JEXEC') or die;

require_once(JPATH_COMPONENT_ADMINISTRATOR.'/classes/config.php');
require_once(JPATH_COMPONENT_ADMINISTRATOR.'/classes/seftools.php');
require_once(JPATH_COMPONENT_ADMINISTRATOR.'/controller.php');
require_once(JPATH_COMPONENT_ADMINISTRATOR.'/controllers/crawler.php');

class JoomSEFControllerCron extends SEFController
{
    function display()
    {
        $this->setRedirect(JURI::root());
    }
    
}