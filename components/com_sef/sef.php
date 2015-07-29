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

require_once(JPATH_ROOT.'/components/com_sef/sef.router.php');
require_once(JPATH_ADMINISTRATOR.'/components/com_sef/controller.php');

class JoomSEFController extends SEFController
{
    function display()
    {
        $this->setRedirect(JURI::root());
    }
    
    function updateNext()
    {
        $db = JFactory::getDBO();
        
        // Load URLs to update
        $query = "SELECT `id`, `sefurl`, `origurl`, `Itemid` FROM `#__sefurls` WHERE `dateadd` = '0000-00-00' AND `locked` = '0' AND `flag` = '1' LIMIT 25";
        $db->setQuery($query);
        $rows = $db->loadObjectList();
        
        // Check that there's anything to update
        if( is_null($rows) || count($rows) == 0 ) {
            // Done
            echo json_encode(array('type'=>'completed','updated'=>0));
            jexit();
        }

        // OK, we've got some data, let's update them
        // First, we need to delete the URLs to be updated
        $ids = array();
        $count = count($rows);
        for ($i = 0; $i < $count; $i++) {
            $ids[] = $rows[$i]->id;
        }
        $ids = implode(',', $ids);
        $query = "DELETE FROM `#__sefurls` WHERE `id` IN ({$ids})";
        $db->setQuery($query);
        if (!$db->query()) {
            echo json_encode(array('type'=>'error','msg'=>$db->stderr(true)));
            jexit();
        }
        
        // Suppress all the normal output
        ob_start();
        
        // Loop through URLs and update them one by one
        $mainframe = JFactory::getApplication();
        $router = $mainframe->getRouter();
        $sefRouter = new JRouterJoomsef();
        for( $i = 0; $i < $count; $i++ ) {
            $row =& $rows[$i];
            $url = $row->origurl;
            $oldSef = $row->sefurl;
            if( !empty($row->Itemid) ) {
                if( strpos($url, '?') !== false ) {
                    $url .= '&';
                } else {
                    $url .= '?';
                }
                $url .= 'Itemid='.$row->Itemid;
            }
            
            $oldUri = new JURI($url);
            $newSefUri = $sefRouter->build($router, $oldUri);
            
            // JURI::toString() returns bad results when used with some UTF characters!
            $newSefUrl = JoomSefUri::getUri($newSefUri);
            $newSef = ltrim(str_replace(JURI::root(), '', $newSefUrl), '/');
            
            // If the SEF URL changed, we need to add it to 301 redirection table
            if( $oldSef != $newSef ) {
                // Check that the redirect does not already exist
                $query = "SELECT `id` FROM `#__sefmoved` WHERE `old` = ".$db->quote($oldSef)." AND `new` = ".$db->quote($newSef)." LIMIT 1";
                $db->setQuery($query);
                $id = $db->loadResult();
                
                if( !$id ) {
                    $query = "INSERT INTO `#__sefmoved` (`old`, `new`) VALUES (".$db->quote($oldSef).", ".$db->quote($newSef).")";
                    $db->setQuery($query);
                    if(!$db->query()) {
                    	echo json_encode(array('type'=>'error','msg'=>$db->stderr(true)));
            			jexit();
                    }
                }
            }
        }
        
        ob_end_clean();
        
        echo json_encode(array('type'=>'updatestep','updated'=>$count));
        jexit();
    }

    function updateMetaNext()
    {
        $db =& JFactory::getDBO();
        $sefConfig =& SEFConfig::getConfig();
        
        // Load all the URLs
        $query = "SELECT `id`, `sefurl`, `origurl`, `Itemid` FROM `#__sefurls` WHERE `locked` = '0' AND `flag` = '1' LIMIT 25";
        $db->setQuery($query);
        $rows = $db->loadObjectList();
        
        // Check that there's anything to update
        if( is_null($rows) || count($rows) == 0 ) {
            // Done
            echo json_encode(array('type'=>'completed','updated'=>0));
            jexit();
        }

        // OK, we've got some data, let's update them
        $count = count($rows);
        
        // Suppress all the normal output
        ob_start();
        
        // Loop through URLs and update them one by one
        for( $i = 0; $i < $count; $i++ ) {
            $row =& $rows[$i];
            $url = $row->origurl;
            if( !empty($row->Itemid) ) {
                if( strpos($url, '?') !== false ) {
                    $url .= '&';
                } else {
                    $url .= '?';
                }
                $url .= 'Itemid='.$row->Itemid;
            }
            
            $uri = new JURI($url);
            
            // Check if we have an extension for this URL
            $updated = false;
            $option = $uri->getVar('option');
            if (!empty($option)) {
                $file = JPATH_ROOT.'/components/com_sef/sef_ext/'.$option.'.php';
                $class = 'SefExt_'.$option;
                
                if (!class_exists($class) && file_exists($file)) {
                    require($file);
                }
                
                if (class_exists($class)) {
                    $ext = new $class();
                    $metadata = $ext->generateMeta($uri);
                    
                    if (is_array($metadata) && count($metadata) > 0) {
                        $metas = '';
                        foreach($metadata as $metakey => $metaval) {
                            $metas .= ", `$metakey` = ".$db->Quote($metaval,true);
                        }
                        
                        $query = "UPDATE `#__sefurls` SET `flag` = '0'".$metas." WHERE `id` = '{$row->id}'";
                        $db->setQuery($query);
                        if(!$db->query()) {
                        	echo json_encode(array('type'=>'error','msg'=>$db->stderr(true)));
                        	jexit();
                        }
                        $updated = true;
                    }
                }
            }
            
            if ($updated==false) {
                // Remove flag
                $query = "UPDATE `#__sefurls` SET `flag` = '0' WHERE `id` = '{$row->id}'";
                $db->setQuery($query);
                if(!$db->query()) {
                	echo json_encode(array('type'=>'error','msg'=>$db->stderr(true)));
                	jexit();
                }
            }
        }
        
        ob_end_clean();
        
        echo json_encode(array('type'=>'updatestep','updated'=>$count));
        jexit();
    }

}

$cmd = JRequest::getCmd('controller');
$classname = 'JoomSEFController'.$cmd;

if (!class_exists($classname)) {
    $file = JPATH_COMPONENT.'/controllers/'.$cmd.'.php';
    if (file_exists($file)) {
        require_once($file);
    }
    else {
        $classname = 'JoomSEFController';
    }
    
    if (!class_exists($classname)) {
        JError::raiseError(403, JText::_('Access Forbidden'));
    }
}

$controller = new $classname();
$controller->execute(JRequest::getCmd('task'));
$controller->redirect();
