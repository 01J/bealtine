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

if (!class_exists('JModelLegacy')) {
    class JModelLegacy extends JModel { }
}

class SEFModel extends JModelLegacy
{
    protected function booleanRadio($name, $attrs, $selected)
    {
		$arr = array(JHtml::_('select.option', '1', JText::_('JYES')), JHtml::_('select.option', '0', JText::_('JNO')));
        
        $html  = '<fieldset class="radio btn-group">';
        $html .= str_replace(array('<div class="controls">', '</div>'), '', JHtml::_('select.radiolist', $arr, $name, $attrs, 'value', 'text', (int) $selected, false));
        $html .= '</fieldset>';
        
        return $html;
    }
}
?>