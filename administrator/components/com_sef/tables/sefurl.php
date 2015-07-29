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


class TableSEFUrl extends JTable
{
    /** @var int */
    var $id = null;
    /** @var int */
    var $cpt = null;
    /** @var string */
    var $sefurl = null;
    /** @var string */
    var $origurl = null;
    /** @var int */
    var $Itemid = null;
    /** @var string */
    var $metadesc = null;
    /** @var string */
    var $metakey = null;
    /** @var string */
    var $metatitle = null;
    /** @var string */
    var $metalang = null;
    /** @var string */
    var $metarobots = null;
    /** @var string */
    var $metagoogle = null;
    /** @var string */
    var $metacustom = null;
    /** @var string */
    var $canonicallink = null;
    /** @var date */
    var $dateadd = null;
    /** @var priority */
    var $priority = null;
    /** @var enabled */
    var $enabled = null;
    /** @var locked */
    var $locked = null;
    /** @var sef */
    var $sef = null;
    /** @var sm_indexed */
    var $sm_indexed = null;
    /** @var sm_date */
    var $sm_date = null;
    /** @var sm_frequency */
    var $sm_frequency = null;
    /** @var sm_priority */
    var $sm_priority = null;
    /** @var int */
    var $showsitename = null;

    /**
	 * Constructor
	 *
	 * @param object Database connector object
	 */
    function TableSEFUrl(& $db)
    {
        parent::__construct('#__sefurls', 'id', $db);
    }

    function check()
    {
        //initialize
        $this->_error = null;
        $this->sefurl = trim($this->sefurl);
        $this->origurl = trim($this->origurl);
        $this->metadesc = trim($this->metadesc);
        $this->metakey = trim($this->metakey);
        // check for valid URLs
        if ($this->origurl == '') {
            $this->_error .= JText::_('COM_SEF_ERROR_EMPTY_URL');
            return false;
        }
        //if (eregi("^\\/", $this->sefurl)) {
        if (preg_match("|^\\/|i", $this->sefurl)) {
            $this->_error .= JText::_('COM_SEF_ERROR_URL_LEADING_SLASH');
        }
        //if ((eregi("^index.php", $this->origurl)) === false) {
        if (!preg_match("/^index.php/i", $this->origurl)) {
            $this->_error .= JText::_('COM_SEF_ERROR_URL_MISSING_INDEX');
        }
        if (is_null($this->_error)) {
            // 15.5.2012 dajo: REMOVED, as duplicate SEF URLs can also be stored in database
            // check for existing URLS
            /*$this->_db->setQuery("SELECT id FROM #__sefurls WHERE `sefurl` LIKE " . $this->_db->Quote($this->sefurl) . " AND `origurl` LIKE " . $this->_db->Quote($this->origurl));
            $xid = intval($this->_db->loadResult());
            if ($xid && $xid != intval($this->id)) {
                $this->_error = JText::_('COM_SEF_ERROR_URL_EXISTS');
                return false;
            }*/
            
            return true;
        } else {
            return false;
        }
    }
}
?>