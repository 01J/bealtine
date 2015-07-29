<?php
/**
 * Element: Custom Field Key
 * Displays a custom key field (use in combination with customfieldvalue)
 *
 * @package         NoNumber Framework
 * @version         15.4.3
 *
 * @author          Peter van Westen <peter@nonumber.nl>
 * @link            http://www.nonumber.nl
 * @copyright       Copyright © 2015 NoNumber All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

require_once JPATH_PLUGINS . '/system/nnframework/helpers/field.php';

class JFormFieldNN_CustomFieldKey extends nnFormField
{
	public $type = 'CustomFieldKey';

	protected function getLabel()
	{
		$this->params = $this->element->attributes();

		$label = ($this->get('label') ? $this->get('label') : '');
		$size = ($this->get('size') ? 'style="width:' . $this->get('size') . 'px"' : '');
		$class = ($this->get('class') ? 'class="' . $this->get('class') . '"' : 'class="text_area"');
		$this->value = htmlspecialchars(html_entity_decode($this->value, ENT_QUOTES), ENT_QUOTES);

		$html = '<input type="text" name="' . $this->name . '" id="' . $this->id . '" value="' . $this->value
			. '" placeholder="' . JText::_($label) . '" title="' . JText::_($label) . '" ' . $class . ' ' . $size . ' />';
		$html = '<label for="' . $this->id . '" style="margin-top: -5px;">' . $html . '</label>';

		return $html;
	}

	protected function getInput()
	{
		return '<div style="display:none;"><div><div>';
	}
}
