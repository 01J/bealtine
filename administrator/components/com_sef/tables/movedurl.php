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


class TableMovedUrl extends JTable
{
    var $id;
    var $old;
    var $new;

    /**
     * Constructor
     *
     * @param object Database connector object
     */
    function TableMovedUrl(& $db) {
        parent::__construct('#__sefmoved', 'id', $db);
    }

}
?>