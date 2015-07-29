<?php
/**
 * Plugin Helper File: Article
 *
 * @package         Articles Anywhere
 * @version         3.9.1
 *
 * @author          Peter van Westen <peter@nonumber.nl>
 * @link            http://www.nonumber.nl
 * @copyright       Copyright © 2015 NoNumber All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

class plgSystemArticlesAnywhereHelperArticle
{
	var $helpers = array();
	var $params = null;

	public function __construct()
	{
		require_once __DIR__ . '/helpers.php';
		$this->helpers = plgSystemArticlesAnywhereHelpers::getInstance();
		$this->params = $this->helpers->getParams();
	}

	public function replace(&$string, &$match, $art = null)
	{
		$groups = explode('|', $match['4']);
		$match['4'] = trim(array_shift($groups));
		$ignores = array();

		foreach ($groups as $group)
		{
			if (strpos($group, '=') === false)
			{
				continue;
			}

			$this->helpers->get('process')->getIgnoreSetting($ignores, $group);
		}

		$html = $this->helpers->get('process')->processMatch($string, $art, $match, $ignores);
		$string = nnText::strReplaceOnce($match['0'], $html, $string);
	}
}
