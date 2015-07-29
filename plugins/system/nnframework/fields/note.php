<?php
/**
 * Element: Note
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

class JFormFieldNN_Note extends nnFormField
{
	public $type = 'Note';

	public function setup(SimpleXMLElement $element, $value, $group = null)
	{
		$this->element = $element;

		$element['label'] = $this->prepareText($element['label']);
		$element['description'] = $this->prepareText($element['description']);
		$element['translateDescription'] = false;

		return parent::setup($element, $value, $group);
	}
}
