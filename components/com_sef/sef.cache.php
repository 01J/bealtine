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

/**
 * Class representing one cached record
 *
 */
class SEFCacheItem
{
    var $id = null;
    var $sefurl = '';
    var $origurl = '';
    var $cpt = 0;
    var $Itemid = '';
    var $metatitle = '';
    var $metadesc = '';
    var $metakey = '';
    var $metalang = '';
    var $metarobots = '';
    var $metagoogle = '';
    var $metaauthor = '';
    var $canonicallink = '';
    var $metacustom = '';
    var $enabled = 1;
    var $sef = 1;
    var $host = null;
    var $showsitename = 3;
    
    function SEFCacheItem($id, $nonsef, $sefurl, $hits, $Itemid = '', $metatitle = '', $metadesc = '', $metakey = '', $metalang = '', $metarobots = '', $metagoogle = '', $metaauthor='', $canonicallink = '', $metacustom = '', $enabled = '1', $sef = '1', $host = null, $showsitename = 3)
    {
        $this->id = $id;
        $this->sefurl = $sefurl;
        $this->origurl = $nonsef;
        $this->cpt = $hits;
        $this->Itemid = $Itemid;
        $this->metatitle = $metatitle;
        $this->metadesc = $metadesc;
        $this->metakey = $metakey;
        $this->metalang = $metalang;
        $this->metarobots = $metarobots;
        $this->metagoogle = $metagoogle;
        $this->metaauthor=$metaauthor;
        $this->canonicallink = $canonicallink;
        $this->metacustom = $metacustom;
        $this->enabled = $enabled;
        $this->sef = $sef;
        $this->host = $host;
        $this->showsitename = $showsitename;
    }
}

/**
 * Main class handling JoomSEF's cache
 *
 */
class SEFCache
{
    var $cacheLoaded = false;
    var $loadCacheCalled = false;
    var $cacheObject = null;
    var $cache = array();
    var $maxSize;
    var $minHits;

    /**
     * Sets the main variables and loads the cache from disk
     *
     * @param int $maxSize
     * @param int $minHits
     * @return sefCache
     */
    function sefCache($maxSize, $minHits)
    {
        $this->maxSize = $maxSize;
        $this->minHits = $minHits;
        $this->cacheFile = JPATH_SITE.'/cache/joomsef.cache';

        $this->loadCache();
    }

    function getInstance()
    {
        static $instance;
        if( !isset($instance) ) {
            $sefConfig =& SEFConfig::getConfig();
            $instance = new sefCache($sefConfig->cacheSize, $sefConfig->cacheMinHits);
        }
        return $instance;
    }
    
    /**
     * Creates the joomla cache object
     *
     */
    function createCacheObject()
    {
        if (!is_null($this->cacheObject)) {
            return;
        }
        
        $conf =& JFactory::getConfig();
		$storage = $conf->get('cache_handler', 'file');

		$options = array(
			'defaultgroup' 	=> 'joomsef',
			'cachebase'		=> JPATH_SITE.'/cache',
			'lifetime' 		=> 315360000,                               // since Joomla doesn't support no-expire cache,
			'checkTime'		=> false,                                   // we'll set expire to approx 10 years - should be enough :)
			'language' 		=> 'en-GB',                                 // we want our cache mutual for all languages
			'storage'		=> $storage
		);

		jimport('joomla.cache.cache');

		$this->cacheObject =& JCache::getInstance( 'output', $options );
		
		if ($this->cacheObject && ($storage == 'memcache')) {
		    // Set the lifetime to 0 for memcache storage
		    $handler =& $this->cacheObject->_getStorage();
		    $handler->_lifetime = 0;
		}
    }

    /**
     * Loads the cache from disk to memory
     *
     */
    function loadCache()
    {
        // Was this function already called?
        if ($this->loadCacheCalled) {
            return;
        }
        $this->loadCacheCalled = true;
        
        // Is cache already loaded?
        if ($this->cacheLoaded) {
            return;
        }

        // If cache is disabled, don't load anything
        $sefConfig =& SEFConfig::getConfig();
        if (!$sefConfig->useCache) {
            $this->cacheLoaded = true;
            return;
        }
        
        // Create the cache object if needed
        $this->createCacheObject();
        if (is_null($this->cacheObject)) {
            return;
        }        
        
        // Load the cache string
        $cacheString = $this->cacheObject->get('cache');
        
        if ($cacheString === false) {
            // Cache is not created yet
            $this->cacheLoaded = true;
            return;
        }
        
        // Unserialize it to the object
        $this->cache = @unserialize($cacheString);
        
        if ($this->cache === false || !is_array($this->cache)) {
            // Error loading cache
            if ($sefConfig->cacheShowErr) {
                // Show error message only when set to
                JError::raiseWarning(100, JText::_('COM_SEF_JOOMSEF').': '.JText::_('COM_SEF_CACHE_FILE_IS_CORRUPTED'));
            }
            return;
        }
        
        $this->cacheLoaded = true;
    }

    /**
     * Saves the cache arrays to disk
     */
    function saveCache()
    {
        // Create the cache object if needed
        $this->createCacheObject();
        if (is_null($this->cacheObject)) {
            return;
        }
        
        // Create the cache string
        $cacheString = serialize($this->cache);
        
        // Store the cache string
        // use 5 retries (in case of file locking problems), otherwise clear the cache
        for ($i = 0; $i < 5; $i++) {
            if ($this->cacheObject->store($cacheString, 'cache')) {
                return;
            }
        }
        
        // Cache could not be stored
        $this->cleanCache();
    }
    
    /**
     * Clears the cache
     *
     */
    function cleanCache()
    {
        // Create the cache object if needed
        $this->createCacheObject();
        if (is_null($this->cacheObject)) {
            return;
        }
        
        $this->cacheObject->remove('cache');
    }

    /**
     * Tries to find nonSEF URLs corresponding to given SEF URL
     * updateHits is deprecated and is not used anymore
     *
     * @param string $sef
     * @param boolean $updateHits
     * @return object[]
     */
    function getNonSefUrl($sef, $updateHits = true)
    {
        // Load the cache if needed
        if (!$this->cacheLoaded) {
            $this->loadCache();
        }
        
        // Check if the cache was loaded successfully
        if (!$this->cacheLoaded) {
            return false;
        }

        $sefConfig =& SEFConfig::getConfig();

        // If we are tolerant for trailing slash
        if ($sefConfig->transitSlash) {
            // Remove trailing slash
            $sef = rtrim($sef, '/');
            if( !isset($this->cache[$sef]) ) {
                // If there isn't URL without trailing slash, add the slash
                $sef .= '/';
            }
        }
        
        // Does the item exist in cache?
        if (isset($this->cache[$sef])) {
            // Check for old cache version - item must be array of URLs!
            if (!is_array($this->cache[$sef])) {
                // Clear the cache
                $this->cleanCache();
                $this->cache = array();
                return false;
            }
            
            // Return the array
            return $this->cache[$sef];
        } else {
            // Cache record not found
            return false;
        }
    }

    /**
     * Tries to find a SEF URL corresponding with given nonSEF URL
     *
     * @param string $nonsef
     * @param string $Itemid
     * @return string
     */
    function getSefUrl($nonsef, $Itemid = null)
    {
        $sefConfig =& SEFConfig::getConfig();

        // Load the cache if needed
        if (!$this->cacheLoaded) {
            $this->LoadCache();
        }

        // Check if the cache was loaded successfully
        if (!$this->cacheLoaded) {
            return false;
        }
        /*foreach($this->cache as $item) {
        	echo "<pre>";
        	print_r($item);
        	echo "</pre>";
        	echo "<br><hr><br>";
        }
        exit;*/

        // Check if non-sef url doesn't contain Itemid
        $vars = array();
        parse_str(str_replace('index.php?', '', $nonsef), $vars);
        if (is_null($Itemid) && strpos($nonsef, 'Itemid=')) {
            if (isset($vars['Itemid'])) $Itemid = $vars['Itemid'];
            $nonsef = SEFTools::removeVariable($nonsef, 'Itemid');
        }

        // Get the ignoreSource parameter
        if (isset($vars['option'])) {
            $params = SEFTools::getExtParams($vars['option']);
            $extIgnore = $params->get('ignoreSource', 2);
        } else {
            $extIgnore = 2;
        }
        $ignoreSource = ($extIgnore == 2 ? $sefConfig->ignoreSource : $extIgnore);
        
        // If Itemid is set as ignored for the component, set ignoreSource to 1
        if (!is_null($Itemid) && isset($vars['option'])) {
            if (SEFTools::isItemidIgnored($vars['option'], $Itemid)) {
                $ignoreSource = 1;
            }
        }

        // Get all sef urls matching non-sef url
        if (isset($this->cache[$nonsef]) && is_array($this->cache[$nonsef]) && (count($this->cache[$nonsef]) > 0)) {
            // Search with Itemid if set to and Itemid set
            if( !$ignoreSource && !is_null($Itemid) ) {
                $nullId = null;
                
                for ($i = 0, $n = count($this->cache[$nonsef]); $i < $n; $i++ ) {
                    $row = $this->cache[$nonsef][$i];
                    if (isset($row->Itemid) && ($row->Itemid == $Itemid)) {
                        return $row;
                    }
                    
                    if (empty($row->Itemid)) {
                        $nullId = $i;
                    }
                }
                
                // Not found with correct itemid, try to find without itemid
                if (!is_null($nullId)) {
                    // Update Itemid in cache
                    $this->cache[$nonsef][$i]->Itemid = $Itemid;
                    $row = $this->cache[$nonsef][$i];
                    
                    // Save the cache
                    $this->saveCache();
                    
                    // Return found row
                    return $row;
                }
            }
            // otherwise, return first result found
            else {
                return $this->cache[$nonsef][0];
            }
        }
        
        // URL does not exist in the cache
        return false;
    }
    
    /**
     * Returns number of entries in cache
     *
     * @return int
     */
    function getCount()
    {
        // Load the cache if needed
        if (!$this->cacheLoaded) {
            $this->LoadCache();
        }

        // Check if the cache was loaded successfully
        if (!$this->cacheLoaded) {
            return false;
        }

        return count($this->cache);
    }
    
    function getSEFURLExists($nonsef) {
    	return isset($this->cache[$nonsef]);
    }

    /**
     * Adds the URL to cache
     *
     * @param string $nonsef
     * @param string $sef
     * @param int $hits
     * @param string $Itemid
     * @param string $metatitle
     * @param string $metadesc
     * @param string $metakey
     * @param string $metalang
     * @param string $metarobots
     * @param string $metagoogle
     * @param string $canonicalLink
     */
    function addUrl($id, $nonsef, $sefurl, $hits, $Itemid = '', $metatitle = '', $metadesc = '', $metakey = '', $metalang = '', $metarobots = '', $metagoogle = '', $metaauthor='', $canonicallink = '', $metacustom = '', $enabled = '1', $sef = '1', $force = false, $host = null, $showsitename = 3)
    {
    	
        // check if URL's hits count is enough to be stored
        if ($force==false && $hits < $this->minHits) {
            return;
        }

        // check the cache size
        if (count($this->cache) > $this->maxSize) {
            // Sorry, our cache is full
            return;
        }
        
        // OK, we can add the URL to the cache
        // let's create the cache record
        $cacheItem = new SEFCacheItem($id, $nonsef, $sefurl, $hits, $Itemid, $metatitle, $metadesc, $metakey, $metalang, $metarobots, $metagoogle, $metaauthor, $canonicallink, $metacustom, $enabled, $sef, $host, $showsitename);
        
        // Add it to our cache array indexing it both by SEF and nonSEF URLs
        if (!isset($this->cache[$sefurl])) {
            $this->cache[$sefurl] = array();
        }
        $this->cache[$sefurl][$id] = $cacheItem;
        
        // We can have the same nonSEF URLs with different Itemids
        if (!isset($this->cache[$nonsef])) {
            $this->cache[$nonsef] = array();
        }
        $this->cache[$nonsef][] =& $this->cache[$sefurl][$id];
        
        // Save the cache
        $this->saveCache();
    }
    
    function checkSEFURL($search_url) {
    	if (!$this->cacheLoaded) {
            $this->loadCache();
        }
    	$urls=array();
    	foreach($this->cache as $url=>$details) {
    		$matches=array();
    		if(preg_match("/".$search_url."/",$url,$matches)) {
    			foreach($this->cache[$url] as $item=>$details) {
    				$urls[$details->origurl]=$details->sefurl;
    			}
    		}
    	}
    	return $urls;
    }
    
    function removeSEF($sef,$nonsef=null) {
    	if(is_null($nonsef)) {
    		$nonsef=$this->cache[$sef]->origurl;
    	}
    	unset($this->cache[$sef]);
    	unset($this->cache[$nonsef]);
    	$this->saveCache();
    	
    }

    function updateCacheURL($nonsef,$oldsef,$sef,$metadata) {
    	$item=new stdClass();
    	$item->sefurl=$sef;
    	$item->origurl=$nonsef;
    	$item->cpt=$this->cache[$oldsef]->cpt;
    	$item->Itemid=$this->cache[$oldsef]->Itemid;
    	foreach($metadata as $key=>$value) {
    		$item->$key=$value;
    	}
    	$item->canonicallink=$this->cache[$oldsef]->canonicallink;
    	$item->enabled=$this->cache[$oldsef]->enabled;
    	$item->sef=$this->cache[$oldsef]->sef;
    	$this->cache[$sef]=$item;
    	
    	unset($this->cache[$oldsef]);
    	
    	foreach($this->cache[$nonsef] as $pos=>$details) {
    		if($details->sefurl==$oldsef) {
    			$this->cache[$nonsef][$pos]=$item;
    		}
    	}
    	
    	$this->saveCache();
    }
    
    function removeCacheURL($nonsef) {
    	$urls=array_keys($this->checkSEFURL($nonsef));
    	foreach($urls as $nonsef) {
	    	foreach($this->cache[$nonsef] as $pos=>$item) {
	    		unset($this->cache[$item->sefurl]);
	    	}
	    	unset($this->cache[$nonsef]);
    	}
    	
    	$this->saveCache();
    }
    
    function getCacheUrls() {
    	$urls = array();
    	foreach ($this->cache as $key => $item) {
    		// return only URLs indexed by SEF URL
            if (substr($key, 0, 9) == 'index.php')
                continue;
            
    		if (is_array($item)) {
                foreach ($item as $url) {
                    $urls[] = $url;
                }
            }
            else {
                $urls[] = $item;
            }
    	}
        
    	return $urls;
    }
    
    function setSEFState($sef, $state) {
        // Not working now
        //if (isset($this->cache[$sef])) {
        //	$this->cache[$sef]->sef = $state;
        //	foreach ($this->cache[$this->cache[$sef]->origurl] as $url) {
        //		$url->sef = $state;
        //	}
        //	$this->saveCache();
        //}
    }
    
    function setSEFEnabled($sef, $state) {
        // Not working now
        //if (isset($this->cache[$sef])) {
        //	$this->cache[$sef]->enabled = $state;
        //	foreach ($this->cache[$this->cache[$sef]->origurl] as $url) {
        //		$url->enabled = $state;
        //	}
        //	$this->saveCache();
        //}
    }
    
    function updateMetas($sef,$metas) {
    	foreach($metas as $key=>$value) {
    		if(isset($this->cache[$sef])) {
    			$this->cache[$sef]->$key=$value;
    		}
    	}
    	foreach($this->cache[$this->cache[$sef]->origurl] as $url) {
    		foreach($metas as $key=>$value) {
    			$url->$key=$value;
    		}
    	}
    	$this->saveCache();
    }
    
    function changeUrl($id,$nonsef,$sefurl,$hits,$Itemid='',$metatitle='',$metadesc='',$metakey='',$metalang='',$metarobots='',$metagoogle='',$metaauthor='',$canonicallink='',$metacustom='',$enabled='1',$sef='1',$host=null, $showsitename = 3) {
    	// If this URL dont exists in cache, we don't create new one
    	if(!array_key_exists($nonsef,$this->cache)) {
    		return;
    	}
    
    	// Remove assingned nonSEF URL's
    	unset($this->cache[$this->cache[$nonsef][0]->sefurl]);
    	unset($this->cache[$nonsef]);
    	$cacheItem = new SEFCacheItem($id, $nonsef, $sefurl, $hits, $Itemid, $metatitle, $metadesc, $metakey, $metalang, $metarobots, $metagoogle, $metaauthor, $canonicallink, $metacustom, $enabled, $sef, $host, $showsitename);
    	$this->cache[$sefurl]=$cacheItem;
    	$this->cache[$nonsef][]=$cacheItem;
    	$this->saveCache();
    }
}
?>