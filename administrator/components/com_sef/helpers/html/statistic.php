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
 
defined('_JEXEC') or die('Restricted access');

class JHTMLStatistic
{
	static function link($url,$text,$attribs=null) {
		if (is_array($attribs)) {
			$attribs = JArrayHelper::toString($attribs);
        }

		return "<a href='".$url."' ".$attribs.">".$text."</a>";
	}
}
?>
