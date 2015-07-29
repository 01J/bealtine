<?php
/**
 * Element: Field
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

require_once JPATH_PLUGINS . '/system/nnframework/helpers/functions.php';
require_once JPATH_PLUGINS . '/system/nnframework/helpers/parameters.php';
require_once JPATH_PLUGINS . '/system/nnframework/helpers/text.php';

class nnFormField extends JFormField
{
	public $type = 'Field';
	public $db = null;
	public $max_list_count = 0;
	public $params = null;

	public function __construct($form = null)
	{
		$this->db = JFactory::getDBO();

		$parameters = nnParameters::getInstance();
		$params = $parameters->getPluginParams('nnframework');
		$this->max_list_count = $params->max_list_count;
	}

	protected function getInput()
	{
		return false;
	}

	public function get($val, $default = '')
	{
		return (isset($this->params[$val]) && (string) $this->params[$val] != '') ? (string) $this->params[$val] : $default;
	}

	function getOptionsByList($list, $extras = array(), $levelOffset = 0)
	{
		$options = array();
		foreach ($list as $item)
		{
			$options[] = $this->getOptionByListItem($item, $extras, $levelOffset);
		}

		return $options;
	}

	function getOptionByListItem($item, $extras = array(), $levelOffset = 0)
	{
		$name = trim($item->name);

		foreach ($extras as $key => $extra)
		{
			if (empty($item->{$extra}))
			{
				continue;
			}

			if ($extra == 'language' && $item->{$extra} == '*')
			{
				continue;
			}

			if (in_array($extra, array('id', 'alias')) && $item->{$extra} == $item->name)
			{
				continue;
			}

			$name .= ' [' . $item->{$extra} . ']';
		}

		$name = nnText::prepareSelectItem($name, isset($item->published) ? $item->published : 1);

		$option = JHtml::_('select.option', $item->id, $name, 'value', 'text', 0);

		if (isset($item->level))
		{
			$option->level = $item->level + $levelOffset;
		}

		return $option;
	}

	function getOptionsTreeByList($items = array(), $root = 0)
	{
		// establish the hierarchy of the menu
		// TODO: use node model
		$children = array();

		if (!empty($items))
		{
			// first pass - collect children
			foreach ($items as $v)
			{
				$pt = $v->parent_id;
				$list = @$children[$pt] ? $children[$pt] : array();
				array_push($list, $v);
				$children[$pt] = $list;
			}
		}

		// second pass - get an indent list of the items
		$list = JHtml::_('menu.treerecurse', $root, '', array(), $children, 9999, 0, 0);

		// assemble items to the array
		$options = array();
		if ($this->get('show_ignore'))
		{
			if (in_array('-1', $this->value))
			{
				$this->value = array('-1');
			}
			$options[] = JHtml::_('select.option', '-1', '- ' . JText::_('NN_IGNORE') . ' -', 'value', 'text', 0);
			$options[] = JHtml::_('select.option', '-', '&nbsp;', 'value', 'text', 1);
		}

		foreach ($list as $item)
		{
			$item->treename = nnText::prepareSelectItem($item->treename, $item->published, '', 1);

			$options[] = JHtml::_('select.option', $item->id, $item->treename, 'value', 'text', 0);
		}

		return $options;
	}

	public function prepareText($string = '')
	{
		$string = trim($string);

		if ($string == '')
		{
			return '';
		}

		// variables
		$var1 = JText::_($this->get('var1'));
		$var2 = JText::_($this->get('var2'));
		$var3 = JText::_($this->get('var3'));
		$var4 = JText::_($this->get('var4'));
		$var5 = JText::_($this->get('var5'));

		$string = JText::sprintf(JText::_($string), $var1, $var2, $var3, $var4, $var5);
		$string = trim(nnText::html_entity_decoder($string));
		$string = str_replace('&quot;', '"', $string);
		$string = str_replace('span style="font-family:monospace;"', 'span class="nn_code"', $string);

		return $string;
	}
}
