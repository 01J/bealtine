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

jimport('joomla.application.component.model');
require_once JPATH_COMPONENT_SITE.'/sef.cache.php';

class SEFModelSEFUrl extends SEFModel
{
    /**
     * Constructor that retrieves the ID from the request
     *
     * @access    public
     * @return    void
     */
    function __construct()
    {
        parent::__construct();

        $array = JRequest::getVar('cid',  0, '', 'array');
        $this->setId((int)$array[0]);
    }

    function setId($id)
    {
        // Set id and wipe data
        $this->_id      = $id;
        $this->_data    = null;
    }

    function &getData()
    {
        // Load the data
        if (empty($this->_data)) {
        	if(JRequest::getInt('viewmode')==6) {
        		$cid=JRequest::getVar('cid',array(),'request','array');
        		
        		$cache=SEFCache::getInstance();
        		$this->_data=$cache->getNonSEFURL($cid[0]);
        	} else {
	            if ($this->_id != 0) {
	                $query = "SELECT * FROM `#__sefurls` WHERE `id` = '{$this->_id}'";
	                $this->_db->setQuery($query);
	                $this->_data = $this->_db->loadObject();
                    if (isset($this->_data->metacustom)) {
                        $this->_data->metacustom = @unserialize($this->_data->metacustom);
                    }
	            }
        	}
        }
        if (!$this->_data) {
            $sefConfig =& SEFConfig::getConfig();

            $this->_data = new stdClass();
            $this->_data->id = 0;
            $this->_data->cpt = null;
            $this->_data->sefurl = null;
            $this->_data->origurl = null;
            $this->_data->Itemid = null;
            $this->_data->metadesc = null;
            $this->_data->metakey = null;
            $this->_data->metatitle = null;
            $this->_data->metalang = null;
            $this->_data->metarobots = null;
            $this->_data->metagoogle = null;
            $this->_data->metacustom = null;
            $this->_data->canonicallink = null;
            $this->_data->dateadd = null;
            $this->_data->enabled = 1;
            $this->_data->locked = 0;
            $this->_data->sef = 1;
            $this->_data->sm_indexed = ($sefConfig->sitemap_indexed ? 1 : 0);
            $this->_data->sm_date = date('Y-m-d');
            $this->_data->sm_frequency = $sefConfig->sitemap_frequency;
            $this->_data->sm_priority = $sefConfig->sitemap_priority;
            $this->_data->aliases = null;
            $this->_data->host = null;
            $this->_data->showsitename = _COM_SEF_SITENAME_GLOBAL;
        }
        else {
            // Get the aliases
            $query = "SELECT * FROM `#__sefaliases` WHERE `url` = '{$this->_id}'";
            $this->_db->setQuery($query);
            $objs = $this->_db->loadObjectList();
            
            $aliases = array();
            if ($objs) {
                foreach ($objs as $obj) {
                    $aliases[] = $this->_aliasToString($obj);
                }
            }
            $this->_data->aliases = implode("\n", $aliases);
        }

        return $this->_data;
    }
    
    function _aliasToString($row)
    {
        $alias = $row->alias;
        
        if (!empty($row->vars)) {
            $vars = trim(str_replace("\n", '&amp;', $row->vars));
            $alias .= '?'.$vars;
        }
        
        return $alias;
    }

    function getLists()
    {
        $lists = array();

        if (empty($this->_data)) {
            $this->getData();
        }
        
        $lists['customurl'] = $this->booleanRadio('customurl', 'class="inputbox"', 1);
        $lists['enabled']   = $this->booleanRadio('enabled', 'class="inputbox"', $this->_data->enabled);
        $lists['sef']       = $this->booleanRadio('sef', 'class="inputbox"', $this->_data->sef);
        $lists['locked']    = $this->booleanRadio('locked', 'class="inputbox"', $this->_data->locked);

        $useSitenameOpts[] = JHTML::_('select.option', _COM_SEF_SITENAME_GLOBAL,    JText::_('COM_SEF_USE_GLOBAL_CONFIG'));
        $useSitenameOpts[] = JHTML::_('select.option', _COM_SEF_SITENAME_BEFORE,    JText::_('COM_SEF_BEFORE_PAGE_TITLE'));
        $useSitenameOpts[] = JHTML::_('select.option', _COM_SEF_SITENAME_AFTER,     JText::_('COM_SEF_AFTER_PAGE_TITLE'));
        $useSitenameOpts[] = JHTML::_('select.option', _COM_SEF_SITENAME_NO,        JText::_('COM_SEF_NO'));
        $lists['showsitename']  = JHTML::_('select.genericlist', $useSitenameOpts, 'showsitename', 'class="inputbox" size="1"', 'value', 'text', $this->_data->showsitename);
        

        return $lists;
    }

    function _getWords()
    {
        $words = array();
        if ($this->_id != 0) {
            $query = "SELECT `w`.`id`, `w`.`word` FROM `#__sefwords` AS `w` INNER JOIN `#__sefurlword_xref` AS `x` ON `w`.`id` = `x`.`word` WHERE `x`.`url` = '{$this->_id}'";
            $this->_db->setQuery($query);
            $words = $this->_db->loadObjectList();
        }

        return $words;
    }

    function fixOrigurlPost(&$data)
    {
        if (isset($data['origurl'])) {
            $origurl = $data['origurl'];
            if (substr($origurl, 0, 10) == 'index.php?') {
                $origurl = substr($origurl, 10);
                $origvars = array();
                parse_str($origurl, $origvars);
                if (!empty($origvars)) {
                    // Handle Itemid
                    if (isset($origvars['Itemid'])) {
                        $Itemid = $origvars['Itemid'];
                        unset($origvars['Itemid']);
                        
                        if (!isset($data['Itemid']) || ($data['Itemid'] == '')) {
                            $data['Itemid'] = $Itemid;
                            JRequest::setVar('Itemid', $Itemid, 'post');
                        }
                    }
                    
                    // Sort vars and move option to beginning
                    if (isset($origvars['option'])) {
                        $opt = $origvars['option'];
                        unset($origvars['option']);
                    }
                    ksort($origvars);
                    if (isset($opt)) {
                        $origvars = array_merge(array('option' => $opt), $origvars);
                    }
                    
                    // Build the origurl again
                    $origurl = 'index.php?';
                    $parts = array();
                    foreach ($origvars as $var => $val) {
                        $parts[] = $var.'='.$val;
                    }
                    $origurl .= implode('&', $parts);
                    
                    // Update value
                    $data['origurl'] = $origurl;
                    JRequest::setVar('origurl', $origurl, 'post');
                }
            }
        }
    }
    
    function store()
    {
        $row =& $this->getTable();

        $data = JRequest::get('post');

        // Make sure origurl follows the rules
        $this->fixOrigurlPost($data);
        
        // Handle the enabled, sef and locked checkboxes
        if (!isset($data['enabled'])) {
            $data['enabled'] = '0';
        }
        if (!isset($data['sef'])) {
            $data['sef'] = '0';
        }
        if (!isset($data['locked'])) {
            $data['locked'] = '0';
        }

        
        // Create the array of custom meta tags
        if (isset($data['metanames']) && is_array($data['metanames'])) {
            for ($i = 0, $n = count($data['metanames']); $i < $n; $i++) {
                if (empty($data['metanames'][$i])) {
                    unset($data['metanames'][$i]);
                    if (isset($data['metacontents'][$i])) {
                        unset($data['metacontents'][$i]);
                    }
                }
            }
            
            // Create the associative array of custom meta tags
            $data['metacustom'] = array_combine($data['metanames'], $data['metacontents']);
        }
        else {
            // No meta tags
            $data['metacustom'] = array();
        }
        $data['metacustom'] = serialize($data['metacustom']);
        
        // Bind the form fields to the table
        if (!$row->bind($data)) {
            $this->setError($this->_db->getErrorMsg());
            return false;
        }

        // Make sure the record is valid
        if (!$row->check()) {
            $this->setError($row->_error);
            return false;
        }

        // Set the priority according to Itemid
        if ($row->Itemid != '') {
            $row->priority = _COM_SEF_PRIORITY_DEFAULT_ITEMID;
        }
        else {
            $row->priority = _COM_SEF_PRIORITY_DEFAULT;
        }

        // Store the table to the database
        if (!$row->store()) {
            $this->setError( $row->getError() );
            return false;
        }

        
        // Handle the aliases references
        // remove the current bindings
        $this->_db->setQuery("DELETE FROM `#__sefaliases` WHERE `url` = '{$row->id}'");
        if (!$this->_db->query()) {
            $this->setError($this->_db->getErrorMsg());
            return false;
        }
        
        // add all the aliases for current URL
        $data['aliases'] = trim($data['aliases']);
        if (!empty($data['aliases'])) {
            $aliases = str_replace("\r", '', $data['aliases']);
            $aliases = explode("\n", $aliases);
            
            $vals = array();
            foreach ($aliases as $arow) {
                if (strpos($arow, '?') !== false) {
                    list($alias, $vars) = explode('?', $arow);
                    
                    $vars = str_replace('&', "\n", $vars);
                }
                else {
                    $alias = $arow;
                    $vars = '';
                }
                $alias = ltrim($alias, '/');
                
                $vals[] = '(' . $this->_db->Quote($alias) . ', ' . $this->_db->Quote($vars) . ", '{$row->id}')";
            }
            
            $query = "INSERT INTO `#__sefaliases` (`alias`, `vars`, `url`) VALUES " . implode(', ', $vals);
            $this->_db->setQuery($query);
            if (!$this->_db->query()) {
                $this->setError($this->_db->getErrorMsg());
                return false;
            }
        }

        // check if there's old url to save to Moved Permanently table
        $unchanged = JRequest::getVar('unchanged');
        if ($data["addtosefmoved"]) {
            $row =& $this->getTable('MovedUrl');
            $row->old = $unchanged;
            $row->new = JRequest::getVar('sefurl');

            // pre-save checks
            if (!$row->check()) {
                $this->setError($row->getError());
                return false;
            }

            // save the changes
            if (!$row->store()) {
                $this->setError($row->getError());
                return false;
            }
        }
        
        $config=SEFConfig::getConfig();
        if($config->useCache) {
        	$data=(object)$data;
        	$cache=SEFCache::getInstance();
        	$nonsefcache=$cache->getNonSEFURL($data->unchanged);
        	if($nonsefcache) { 
        		$cache->changeUrl($row->id, $data->origurl,$data->sefurl,0,$data->Itemid,$data->metatitle,$data->metadesc,$data->metakey,$data->metalang,$data->metarobots,$data->metagoogle,$data->canonicallink,$data->metacustom,$data->enabled,$data->sef,true,$row->host,$row->showsitename);
        	}
        }

        return true;
    }
    
    function storeCache() {
    	$data=(object)JRequest::get('post');
    	
    	$cache=SEFCache::getInstance();
    	$cache->changeUrl($data->id, $data->origurl,$data->sefurl,0,$data->Itemid,$data->metatitle,$data->metadesc,$data->metakey,$data->metalang,$data->metarobots,$data->metagoogle,$data->canonicallink,$data->metacustom,$data->enabled,$data->sef,true,$data->host,$data->showsitename);
    	
    	if($data->addtosefmoved) {
    		$query="INSERT INTO #__sefmoved \n";
    		$query.="SET old=".$this->_db->quote($data->unchanged).", ";
    		$query.="new=".$this->_db->quote($data->sefurl)." \n";
    		$this->_db->setQuery($query);
    		if(!$this->_db->query()) {
    			$this->setError($this->_db->stderr(true));
    			return false;
    		}
    	}
    	if($data->urlchanged) {
    		$query="SELECT COUNT(*) \n";
    		$query.="FROM #__sefurls \n";
    		$query.="WHERE origurl=".$this->_db->quote($data->origurl)." \n";
    		$this->_db->setQuery($query);
    		$cnt=$this->_db->loadResult();
    		
    		if($cnt) {
	    		$query="UPDATE #__sefurls \n";
	    		$query.="SET sefurl=".$this->_db->quote($data->sefurl)." \n";
	    		$query.="WHERE origurl=".$this->_db->quote($data->origurl)." \n";
	    		$this->_db->setQUery($query);
	    		if(!$this->_db->query()) {
	    			$this->setError($this->_db->stderr(true));
	    			return false;
	    		}
    		}
    	}
    	return true;
    }

    function delete()
    {
        $cids = JRequest::getVar('cid', array(0), 'post', 'array');
        
        if(JRequest::getInt('viewmode')!=6) {
	        if (count($cids)) {
	            // Remove URL
	            $ids = implode(',', $cids);
	            $query = "DELETE FROM `#__sefurls` WHERE `id` IN ($ids) AND `locked` = '0'";
	            $this->_db->setQuery($query);
	            if (!$this->_db->query()) {
	                $this->setError($this->_db->getErrorMsg());
	                return false;
	            }
	            
	            // Remove aliases
	            $query = "DELETE FROM `#__sefaliases` WHERE `url` IN ($ids)";
	            $this->_db->setQuery($query);
	            if (!$this->_db->query()) {
	                $this->setError($this->_db->getErrorMsg());
	                return false;
	            }
	            
	            // Remove words references
	            $query = "DELETE FROM `#__sefurlword_xref` WHERE `url` IN ($ids)";
	            $this->_db->setQuery($query);
	            if (!$this->_db->query()) {
	                $this->setError($this->_db->getErrorMsg());
	                return false;
	            }
	        }
        } else {
        	$cache=SEFCache::getInstance();
        	foreach($cids as $url) {
        		$cache->removeSEF($url);
        	}
        }
        
        return true;
    }

    function setActive()
    {
        if ($this->_id == 0) {
            return false;
        }

        // Get the SEF URL for given id
        $row =& $this->getData();

        $states = array();
        $states[$this->_id] = 0;
        
        // Set priority to 0 for given id
        $query = "UPDATE `#__sefurls` SET `priority` = '0' WHERE `id` = '{$this->_id}' LIMIT 1";
        $this->_db->setQuery($query);
        if( !$this->_db->query() ) {
            $this->setError($this->_db->getErrorMsg());
            return false;
        }

        // Get all other IDs
        $query = "SELECT `id` FROM `#__sefurls` WHERE (`sefurl` = '{$row->sefurl}') AND (`id` != '{$this->_id}')";
        $this->_db->setQuery($query);
        $ids = $this->_db->loadColumn();
        if (count($ids) > 0) {
            foreach ($ids as $id) {
                $states[$id] = 100;
            }
            
            // Set priority to 100 for every other same SEF URL
            $where = implode(',', $ids);
            $query = "UPDATE `#__sefurls` SET `priority` = '100' WHERE `id` IN ({$where})";
            $this->_db->setQuery($query);
            if( !$this->_db->query() ) {
                $this->setError($this->_db->getErrorMsg());
                return false;
            }
        }
        
        return $states;
    }
    
    function setOrigurl()
    {
        if ($this->_id == 0) {
            return false;
        }

        $data = JRequest::get('post');

        // Make sure origurl follows the rules
        $this->fixOrigurlPost($data);
        
        $row =& $this->getTable();
        if (!$row->load($this->_id)) {
            return false;
        }
        
        $row->origurl = $data['origurl'];
        $row->Itemid = $data['Itemid'];
        
        if (!$row->check()) {
            return false;
        }
        
        if (!$row->store()) {
            return false;
        }
        
        $origurl = $data['origurl'];
        $Itemid = trim($data['Itemid']);
        if (strlen($Itemid) > 0) {
            if (strpos($origurl, '?') !== false) {
                $origurl .= '&Itemid='.intval($data['Itemid']);
            }
            else {
                $origurl .= '?Itemid='.intval($data['Itemid']);
            }
        }
        
        return $origurl;
    }
    
    function setSefurl()
    {
        if ($this->_id == 0) {
            return false;
        }
        
        $row =& $this->getTable();
        if (!$row->load($this->_id)) {
            return false;
        }
        
        $row->sefurl = JRequest::getVar('sefurl', '', 'post');
        
        if (!$row->check()) {
            return false;
        }
        
        if (!$row->store()) {
            return false;
        }
        
        return $row->sefurl;
    }
	
	function copyToCache() {
		$selection=JRequest::getString('selection');
		if($selection=='selected') {
			$ids=JRequest::getVar('cid',array(),'request','array');
		} else {
			require_once JPATH_COMPONENT_ADMINISTRATOR.'/models/sefurls.php';
			$model=new SEFModelSEFUrls();
			$where=$model->_getWhere();
			$query="SELECT id FROM #__sefurls \n";
			$query.="WHERE ".$where;
			$this->_db->setQuery($query);
			$ids=$this->_db->loadColumn();
		}
		
		$cache=SEFCache::getInstance();
		
		$query  = "SELECT * \n";
		$query .= "FROM #__sefurls \n";
		$query .= "WHERE id IN(".implode(",", $ids).") \n";
		$this->_db->setQuery($query);
		$urls = $this->_db->loadObjectList();
		
		foreach ($urls as $url) {
			if (!$cache->getSEFURLExists($url->origurl)) {
				$cache->addURL($url->id, $url->origurl,$url->sefurl,$url->cpt,$url->Itemid,$url->metatitle,$url->metadesc,$url->metakey,$url->metalang,$url->metarobots,$url->metagoogle,$url->metaauthor,$url->canonicallink,$url->metacustom,$url->enabled,$url->sef,true,$url->host,$url->showsitename);
			}
		}
		return true;
	}
}
?>