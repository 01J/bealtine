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

class SEFControllerMovedUrls extends SEFController
{
    /**
     * constructor (registers additional tasks to methods)
     * @return void
     */
    function __construct()
    {
        parent::__construct();
        
        $this->registerTask('add', 'edit');
    }

    function display()
    {
        JRequest::setVar( 'view', 'movedurls' );
        
        parent::display();
    }
    
    function edit()
    {
        JRequest::setVar( 'view', 'movedurl' );
        
        parent::display();
    }
    
    function save()
    {
        $model = $this->getModel('movedurl');

        if ($model->store()) {
            $msg = JText::_( 'URL Saved' );
        } else {
            $msg = JText::_( 'Error Saving URL' );
        }

        $this->setRedirect('index.php?option=com_sef&controller=movedurls', $msg);
    }
    
    function remove()
    {
		$model = $this->getModel('movedurl');
		
		if(!$model->delete()) {
			$msg = JText::_( 'Error: One or More URLs Could not be Deleted' );
		} else {
			$msg = JText::_( 'URL(s) Deleted' );
		}

		$this->setRedirect( 'index.php?option=com_sef&controller=movedurls', $msg );
    }
    
    function deleteFiltered()
    {
        $model = $this->getModel('movedurls');
        
		if(!$model->deleteFiltered()) {
			$msg = JText::_( 'Error: One or More URLs Could not be Deleted' );
		} else {
			$msg = JText::_( 'URL(s) Deleted' );
		}

		$this->setRedirect( 'index.php?option=com_sef&controller=movedurls', $msg );
    }
    
    function cancel()
    {
        $this->setRedirect( 'index.php?option=com_sef&controller=movedurls' );
    }
}
?>