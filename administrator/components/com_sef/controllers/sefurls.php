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

class SEFControllerSEFUrls extends SEFController
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
        JRequest::setVar( 'view', 'sefurls' );
        
        parent::display();
    }
    
    function edit()
    {
        JRequest::setVar( 'view', 'sefurl' );
        
        parent::display();
    }
    
    function save()
    {
        $model = $this->getModel('sefurl');

        if ($model->store()) {
            $msg = JText::_( 'URL Saved' );
        } else {
            $msg = JText::_( 'Error Saving URL' ) . ': ' . $model->getError();
        }

        $this->setRedirect('index.php?option=com_sef&controller=sefurls', $msg);
    }
    
    function save_cache()
    {
        $model = $this->getModel('sefurl');

        if ($model->storeCache()) {
            $msg = JText::_( 'URL Saved' );
        } else {
            $msg = JText::_( 'Error Saving URL' ) . ': ' . $model->getError();
        }

        $this->setRedirect('index.php?option=com_sef&controller=sefurls', $msg);
    }
    
    function setActive()
    {
        $model =& $this->getModel('sefurl');
        
        $ret = $model->setActive();
        if ($ret === false) {
            $msg = JText::_( 'Error: URL could not be set active' );
        } else {
            $msg = JText::_( 'URL Activated' );
        }
        
        if (intval(JRequest::getVar('ajax', 0)) == 1) {
            $this->showAjaxResponse($ret);
        }
        else {
            $this->setRedirect('index.php?option=com_sef&controller=sefurls', $msg);
        }
    }
    
    function _getWhere()
    {
        $selection = JRequest::getVar('selection', 'selected', 'post');
        $model =& $this->getModel('sefurls');
        
        $where = '';
        if ($selection == 'selected') {
            $where = $model->_getWhereIds();
        }
        else {
            $where = $model->_getWhere();
        }
        
        return $where;
    }
    
    function enable()
    {
        $this->_setEnabled(1);
    }
    
    function disable()
    {
        $this->_setEnabled(0);
    }
    
    function lock()
    {
        $this->_setLocked(1);
    }
    
    function unlock()
    {
        $this->_setLocked(0);
    }
    
    function sefEnable()
    {
        $this->_setSEF(1);
    }
    
    function sefDisable()
    {
        $this->_setSEF(0);
    }
    
    function delete()
    {
        $selection = JRequest::getVar('selection', 'selected', 'post');
        
        if ($selection == 'selected') {
            $this->delete_selected();
        }
        else {
            $this->delete_filtered();
        }
    }
    
    function delete_selected()
    {
		$model = $this->getModel('sefurl');
		
		if(!$model->delete()) {
			$msg = JText::_( 'Error: One or More URLs Could not be Deleted' );
		} else {
			$msg = JText::_( 'URL(s) Deleted' );
		}

		$this->setRedirect( 'index.php?option=com_sef&controller=sefurls', $msg );
    }
    
    function delete_filtered()
    {
        $model = $this->getModel('sefurls');
        
		if(!$model->deleteFiltered()) {
			$msg = JText::_( 'Error: One or More URLs Could not be Deleted' );
		} else {
			$msg = JText::_( 'URL(s) Deleted' );
		}

		$this->setRedirect( 'index.php?option=com_sef&controller=sefurls', $msg );
    }
    
    function cancel()
    {
        $this->setRedirect( 'index.php?option=com_sef&controller=sefurls' );
    }
    
    function showimport()
    {
        $model =& $this->getModel('import');
        $view =& $this->getView('importexport', 'html');
        $view->setModel($model, true);
        
        $view->display();
    }
    
    function import()
    {
        $model =& $this->getModel('import');
        $view =& $this->getView('importexport', 'html');
        $view->setModel($model);
        $view->setLayout('importstats');
        
		if(!$model->import()) {
		    $view->assign('success', false);
		} else {
		    $view->assign('success', true);
		}
		
		$view->assign('filetype', $model->type);
		$view->assign('total', $model->total);
		$view->assign('imported', $model->imported);
		$view->assign('notImported', $model->notImported);
		
		$view->display();
    }
    
    function importdbace()
    {
        $model =& $this->getModel('import');
        $view =& $this->getView('importexport', 'html');
        $view->setModel($model);
        $view->setLayout('importstats');
        
		if(!$model->importDBAce()) {
		    $view->assign('success', false);
		} else {
		    $view->assign('success', true);
		}
		
		$view->assign('filetype', $model->type);
		$view->assign('total', $model->total);
		$view->assign('imported', $model->imported);
		$view->assign('notImported', $model->notImported);
		
		$view->display();
    }
    
    function importdbsh()
    {
        $model =& $this->getModel('import');
        $view =& $this->getView('importexport', 'html');
        $view->setModel($model);
        $view->setLayout('importstats');
        
		if(!$model->importDBSh()) {
		    $view->assign('success', false);
		} else {
		    $view->assign('success', true);
		}
		
		$view->assign('filetype', $model->type);
		$view->assign('total', $model->total);
		$view->assign('imported', $model->imported);
		$view->assign('notImported', $model->notImported);
		
		$view->display();
    }
    
    function export()
    {
        $model =& $this->getModel('sefurls');
        $where = $this->_getWhere();
        
		if(!$model->export($where)) {
			$msg = JText::_( 'Error: URLs could not be exported.' );
		} else {
			$msg = JText::_( 'URL(s) Exported' );
		}

		$this->setRedirect( 'index.php?option=com_sef&controller=sefurls', $msg );
    }
    
    function create301()
    {
        $model =& $this->getModel('sefurl');
        $url301 =& $model->getData();
        
        $sefurl = '';
        if( !empty($url301->sefurl) ) {
            $sefurl = '&sefurl='.urlencode($url301->sefurl);
        }
        
        $this->setRedirect('index.php?option=com_sef&controller=movedurls&task=add'.$sefurl);
    }
    
    function showAjaxResponse($states)
    {
        $ret = array();
        foreach ($states as $id => $state) {
            $obj = new stdClass();
            $obj->id = $id;
            $obj->newValue = $state;
            $ret[] = $obj;
        }
        
        echo json_encode($ret);
        
        $app = JFactory::getApplication();
        $app->close();
    }
    
    function _setEnabled($state)
    {
        $model =& $this->getModel('sefurls');
        $where = $this->_getWhere();
        
        $msg = '';
        $newState = $state;
        if (!$model->setEnabled($state, $where)) {
            $msg = JText::_('Error Saving URL');
            $newState = ($state == '1' ? '0' : '1');
        }
        
        if (intval(JRequest::getVar('ajax', 0)) == 1) {
            $ids = JRequest::getVar('cid', array(), 'post', 'array');
            $this->showAjaxResponse(array($ids[0] => $newState));
        }
        else {
            $this->setRedirect( 'index.php?option=com_sef&controller=sefurls', $msg );
        }
    }
    
    function _setLocked($state)
    {
        $model =& $this->getModel('sefurls');
        $where = $this->_getWhere();
        
        $msg = '';
        $newState = $state;
        if( !$model->setLocked($state, $where) ) {
            $msg = JText::_( 'Error Saving URL' );
            $newState = ($state == '1' ? '0' : '1');
        }
        
        if (intval(JRequest::getVar('ajax', 0)) == 1) {
            $ids = JRequest::getVar('cid', array(), 'post', 'array');
            $this->showAjaxResponse(array($ids[0] => $newState));
        }
        else {
            $this->setRedirect( 'index.php?option=com_sef&controller=sefurls', $msg );
        }
    }
    
    function _setSEF($state)
    {
        $model =& $this->getModel('sefurls');
        $where = $this->_getWhere();
        
        $msg = '';
        $newState = $state;
        if( !$model->setSEF($state, $where) ) {
            $msg = JText::_( 'Error Saving URL' );
            $newState = ($state == '1' ? '0' : '1');
        }
        
        if (intval(JRequest::getVar('ajax', 0)) == 1) {
            $ids = JRequest::getVar('cid', array(), 'post', 'array');
            $this->showAjaxResponse(array($ids[0] => $newState));
        }
        else {
            $this->setRedirect( 'index.php?option=com_sef&controller=sefurls', $msg );
        }
    }
    
    function setOrigurl()
    {
        $ret = new stdClass();
        $ret->success = true;
        
        $model =& $this->getModel('sefurl');
        $origurl = $model->setOrigurl();
        if ($origurl === false) {
            $ret->success = false;
            $ret->msg = JText::_('COM_SEF_ERR_SET_ORIGURL');
        }
        else {
            $ret->origurl = $origurl;
        }
        
        echo json_encode($ret);
        
        $app = JFactory::getApplication();
        $app->close();
    }
    
    function setSefurl()
    {
        $ret = new stdClass();
        $ret->success = true;
        
        $model =& $this->getModel('sefurl');
        $sefurl = $model->setSefurl();
        if ($sefurl === false) {
            $ret->success = false;
            $ret->msg = JText::_('COM_SEF_ERR_SET_SEFURL');
        }
        else {
            $ret->sefurl = $sefurl;
        }
        
        echo json_encode($ret);
        
        $app = JFactory::getApplication();
        $app->close();
    }
    
    function createLinks()
    {
        $model =& $this->getModel('sefurls');
        
        $model->CreateHomeLinks();
        
        $this->setRedirect( 'index.php?option=com_sef&controller=sefurls' );
    }
    
    function update_urls() {
    	$model =& $this->getModel('sefurls');
        $view =& $this->getView('sefurls', 'html');
        $view->setModel($model, true);
        
        if (!$model->prepareUpdateSelected()) {
            $msg = JText::_('COM_SEF_ERROR_UPDATE_PREPARE');
            $this->setRedirect('index.php?option=com_sef&controller=sefurls', $msg);
            return;
        }
        
        $view->showUpdate('sefurls');
    }
    
    function update_metas() {
    	$model =& $this->getModel('sefurls');
        $view =& $this->getView('sefurls', 'html');
        $view->setModel($model, true);
        
        if (!$model->prepareUpdateSelected()) {
            $msg = JText::_('COM_SEF_ERROR_META_UPDATE_PREPARE');
            $this->setRedirect('index.php?option=com_sef&controller=sefurls', $msg);
            return;
        }
        
        $view->showUpdateMeta('sefurls');
    }
    
    function update_sitemap() {
    	$model =& $this->getModel('sefurls');
        $view =& $this->getView('sefurls', 'html');
        $view->setModel($model, true);
        
        if (!$model->prepareUpdateSelected()) {
            $msg = JText::_('COM_SEF_UPDATE_SITEMAP_PREPARE_FAILED');
            $this->setRedirect('index.php?option=com_sef&controller=sefurls', $msg);
            return;
        }
        
        $view->showUpdateSitemap('sefurls');
    }
    
    function updateUrls()
    {
        $model =& $this->getModel('sefurls');
        $view =& $this->getView('sefurls', 'html');
        $view->setModel($model, true);
        
        if (!$model->prepareUpdate()) {
            $msg = JText::_('COM_SEF_ERROR_UPDATE_PREPARE');
            $this->setRedirect('index.php?option=com_sef', $msg);
            return;
        }
        
        $view->showUpdate();
    }

    function updateMeta()
    {
        $model =& $this->getModel('sefurls');
        $view =& $this->getView('sefurls', 'html');
        $view->setModel($model, true);
        
        if (!$model->prepareUpdate()) {
            $msg = JText::_('COM_SEF_ERROR_META_UPDATE_PREPARE');
            $this->setRedirect('index.php?option=com_sef', $msg);
            return;
        }
        
        $view->showUpdateMeta();
    }
    
    
    function copy_to_cache() {
    	$model=$this->getModel('sefurl');
    	$model->copyToCache();
    	$this->setRedirect( 'index.php?option=com_sef&controller=sefurls');
    }
    
    function change_metas() {
    	$model =& $this->getModel('sefurls');
        $view =& $this->getView('sefurls', 'html');
        $view->setModel($model, true);
        
        $view->showChangeMeta();
    }
    
    function save_changed_metas() {
    	$model=$this->getModel('sefurls');
    	$msg="";    	
    	$err='';
    	if(!$model->saveChangedMetas()) {
    		$msg=$model->getError();
    		$err='error';
    	}
    	$this->setRedirect( 'index.php?option=com_sef&controller=sefurls',$msg,$err);
    }
    
    function cancel_changed_metas() {
    	$this->setRedirect( 'index.php?option=com_sef&controller=sefurls' );
    }
}
?>
