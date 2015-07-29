<?php
/**
 * Plugin Helper File
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

require_once JPATH_PLUGINS . '/system/nnframework/helpers/functions.php';
require_once JPATH_PLUGINS . '/system/nnframework/helpers/protect.php';
require_once JPATH_PLUGINS . '/system/nnframework/helpers/text.php';

nnFrameworkFunctions::loadLanguage('plg_system_articlesanywhere');

/**
 * Plugin that places articles
 */
class plgSystemArticlesAnywhereHelper
{
	var $helpers = array();

	public function __construct(&$params)
	{
		$this->params = $params;

		$this->params->comment_start = '<!-- START: Articles Anywhere -->';
		$this->params->comment_end = '<!-- END: Articles Anywhere -->';
		$this->params->message_start = '<!--  Articles Anywhere Message: ';
		$this->params->message_end = ' -->';

		$this->params->article_tag = trim($this->params->article_tag);

		$this->params->message = '';

		$this->params->option = JFactory::getApplication()->input->get('option');

		$this->params->disabled_components = array('com_acymailing');

		require_once __DIR__ . '/helpers/helpers.php';
		$this->helpers = plgSystemArticlesAnywhereHelpers::getInstance($params);
	}

	public function onContentPrepare(&$article, &$context)
	{
		$area = isset($article->created_by) ? 'articles' : 'other';


		nnFrameworkHelper::processArticle($article, $context, $this, 'processArticles', array($area, &$article));
	}

	public function onAfterDispatch()
	{
		// only in html
		if (JFactory::getDocument()->getType() !== 'html' && JFactory::getDocument()->getType() !== 'feed')
		{
			return;
		}

		$buffer = JFactory::getDocument()->getBuffer('component');

		if (empty($buffer) || is_array($buffer))
		{
			return;
		}

		$this->helpers->get('replace')->replaceTags($buffer, 'component');

		JFactory::getDocument()->setBuffer($buffer, 'component');
	}

	public function onAfterRender()
	{
		// only in html and feeds
		if (JFactory::getDocument()->getType() !== 'html' && JFactory::getDocument()->getType() !== 'feed')
		{
			return;
		}

		$html = JResponse::getBody();
		if ($html == '')
		{
			return;
		}

		if (JFactory::getDocument()->getType() != 'html')
		{
			$this->helpers->get('replace')->replaceTags($html, 'body');
			$this->helpers->get('clean')->cleanLeftoverJunk($html);

			JResponse::setBody($html);

			return;
		}

		// only do stuff in body
		list($pre, $body, $post) = nnText::getBody($html);
		$this->helpers->get('replace')->replaceTags($body, 'body');
		$html = $pre . $body . $post;

		$this->helpers->get('clean')->cleanLeftoverJunk($html);

		// replace head with newly generated head
		// this is necessary because the plugins might have added scripts/styles to the head
		$this->helpers->get('head')->updateHead($html);

		JResponse::setBody($html);
	}

	public function processArticles(&$string, $area = 'articles', &$article)
	{
		$this->helpers->get('process')->processArticles($string, $area, $article);
	}
}
