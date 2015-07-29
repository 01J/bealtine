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

class SEFControllerInfo extends SEFController
{
    function __construct()
    {
        parent::__construct();
    }

    function help()
    {
        JRequest::setVar('view', 'info');
        JRequest::setVar('layout' , 'help');

        parent::display();
    }
    
    function doc()
    {
        JRequest::setVar('view', 'info');
        JRequest::setVar('layout', 'doc');
        
        parent::display();
    }
    
    function changelog()
    {
        JRequest::setVar('view', 'info');
        JRequest::setVar('layout', 'changelog');
        
        parent::display();
    }

}
?>