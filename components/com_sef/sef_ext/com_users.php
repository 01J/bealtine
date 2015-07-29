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
defined('_JEXEC') or die('Restricted access.');

class SefExt_com_users extends SefExt
{
    protected static $textsEn = null;
    
    public function getNonSefVars(&$uri)
    {
        $this->_createNonSefVars($uri);

        return array($this->nonSefVars, $this->ignoreVars);
    }

    protected function _createNonSefVars(&$uri)
    {
        if (!isset($this->nonSefVars) && !isset($this->ignoreVars)) {
            $this->nonSefVars = array();
        	$this->ignoreVars = array();
        }

        if (!is_null($uri->getVar('token'))) {
            $this->nonSefVars['token'] = $uri->getVar('token');
        }
        if(!is_null($uri->getVar('return'))) {
        	$this->nonSefVars['return']=$uri->getVar('return');
        }
    }

    function GetUserName($id)
    {
        $id = intval($id);
        $user = JUser::getInstance($id);
        
        return $user->username;
    }
    
    protected function _prepareTexts() {
        if (is_null(self::$textsEn)) {
            self::$textsEn = array();
            self::$textsEn['COM_SEF_USERS_ACTIVATE'] = 'Activate';
            self::$textsEn['COM_SEF_USERS_COMPLETE'] = 'Complete';
            self::$textsEn['COM_SEF_USERS_CONFIRM'] = 'Confirm';
            self::$textsEn['COM_SEF_USERS_EDIT'] = 'Edit';
            self::$textsEn['COM_SEF_USERS_LOGIN'] = 'Login';
            self::$textsEn['COM_SEF_USERS_PROFILE'] = 'Profile';
            self::$textsEn['COM_SEF_USERS_REGISTER'] = 'Register';
            self::$textsEn['COM_SEF_USERS_REGISTRATION'] = 'Registration';
            self::$textsEn['COM_SEF_USERS_REMIND'] = 'Forgot username';
            self::$textsEn['COM_SEF_USERS_REQUEST'] = 'Request';
            self::$textsEn['COM_SEF_USERS_RESET'] = 'Forgot password';
            self::$textsEn['COM_SEF_USERS_SAVE'] = 'Save';
            self::$textsEn['COM_SEF_USERS_SUBMIT'] = 'Submit';
            self::$textsEn['COM_SEF_USERS_USER'] = 'User';
        }
    }
    
    function create(&$uri)
    {
        $vars = $uri->getQuery(true);
        extract($vars);
        $this->_createNonSefVars($uri);
        
        $this->_prepareTexts();
        
        $title = array();
        $title[] = JoomSEF::_getMenuTitleLang(@$option, $lang, @$Itemid);

        if (!empty($view)) {
            if ($this->params->get('always_en', '0') == '1') {
                $title[] = self::$textsEn[strtoupper('COM_SEF_USERS_'.$view)];
            }
            else {
                $title[] = JText::_('COM_SEF_USERS_'.$view);
            }
        }
        
        if (!empty($layout)) {
            if ($this->params->get('always_en', '0') == '1') {
                $title[] = self::$textsEn[strtoupper('COM_SEF_USERS_'.$layout)];
            }
            else {
                $title[] = JText::_('COM_SEF_USERS_'.$layout);
            }
        }
        
        if (!empty($task)) {
            $tasks = explode('.', $task);
            
            if ($tasks[0] == 'profile') {
                if (isset($user_id)) {
                    $title[] = $this->GetUserName($user_id);
                }
            }
            else {
                if ($this->params->get('always_en', '0') == '1') {
                    $title[] = self::$textsEn[strtoupper('COM_SEF_USERS_'.$tasks[0])];
                }
                else {
                    $title[] = JText::_('COM_SEF_USERS_'.$tasks[0]);
                }
            }
            
            if (isset($tasks[1])) {
                if ($tasks[1] == 'remind') {
                    $tasks[1] = 'submit';
                }
                
                if ($this->params->get('always_en', '0') == '1') {
                    $title[] = self::$textsEn[strtoupper('COM_SEF_USERS_'.$tasks[1])];
                }
                else {
                    $title[] = JText::_('COM_SEF_USERS_'.$tasks[1]);
                }
                
                if (in_array($tasks[1], array('confirm', 'complete'))) {
                    if ($this->params->get('always_en', '0') == '1') {
                        $title[] = self::$textsEn['COM_SEF_USERS_SUBMIT'];
                    }
                    else {
                        $title[] = JText::_('COM_SEF_USERS_SUBMIT');
                    }
                }
            }
        }
        
        $newUri = $uri;
        if (count($title) > 0) {
            $newUri = JoomSEF::_sefGetLocation($uri, $title, null, null, null, @$lang, $this->nonSefVars);
        }
        
        return $newUri;
    }

}
?>