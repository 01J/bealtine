<?php
/**
 * NoNumber Framework Helper File: Assignments: Zoo
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

require_once JPATH_PLUGINS . '/system/nnframework/helpers/assignment.php';

class nnFrameworkAssignmentsZoo extends nnFrameworkAssignment
{
	function init()
	{
		if (!$this->request->view)
		{
			$this->request->view = $this->request->task;
		}

		switch ($this->request->view)
		{
			case 'item':
				$this->request->idname = 'item_id';
				break;
			case 'category':
				$this->request->idname = 'category_id';
				break;
		}

		$this->request->id = JFactory::getApplication()->input->getInt($this->request->idname, 0);
	}

	public function initAssignment($assignment, $article = 0)
	{
		parent::initAssignment($assignment, $article);

		if ($this->request->option != 'com_zoo' && !isset($this->request->idname))
		{
			return;
		}

		switch ($this->request->idname)
		{
			case 'item_id':
				$this->request->view = 'item';
				break;
			case 'category_id':
				$this->request->view = 'category';
				break;
		}
	}

	function passPageTypes()
	{
		return $this->passByPageTypes('com_zoo', $this->selection, $this->assignment);
	}

	function passCategories()
	{
		if ($this->request->option != 'com_zoo')
		{
			return $this->pass(false);
		}

		$pass = (
			($this->params->inc_apps && $this->request->view == 'frontpage')
			|| ($this->params->inc_categories && $this->request->view == 'category')
			|| ($this->params->inc_items && $this->request->view == 'item')
		);

		if (!$pass)
		{
			return $this->pass(false);
		}

		$cats = array();
		if ($this->article && isset($this->article->catid))
		{
			$cats = $this->article->catid;
		}
		else
		{
			switch ($this->request->view)
			{
				case 'frontpage':
					if ($this->request->id)
					{
						$cats[] = $this->request->id;
					}
					else
					{
						$menuparams = $this->getMenuItemParams($this->request->Itemid);
						if (isset($menuparams->application))
						{
							$cats[] = 'app' . $menuparams->application;
						}
					}
					break;

				case 'category':
					if ($this->request->id)
					{
						$cats[] = $this->request->id;
					}
					else
					{
						$menuparams = $this->getMenuItemParams($this->request->Itemid);
						if (isset($menuparams->category))
						{
							$cats[] = $menuparams->category;
						}
					}
					if ($cats['0'])
					{
						$query = $this->db->getQuery(true)
							->select('c.application_id')
							->from('#__zoo_category AS c')
							->where('c.id = ' . (int) $cats['0']);
						$this->db->setQuery($query);
						if ($this->db->loadResult())
						{
							$cats[] = 'app' . $this->db->loadResult();
						}
					}
					break;

				case 'item':
					$id = $this->request->id;
					if (!$id)
					{
						$menuparams = $this->getMenuItemParams($this->request->Itemid);
						$id = isset($menuparams->item_id) ? $menuparams->item_id : '';
					}
					if ($id)
					{
						$query = $this->db->getQuery(true)
							->select('c.category_id')
							->from('#__zoo_category_item AS c')
							->where('c.item_id = ' . (int) $id)
							->where('c.category_id != 0');
						$this->db->setQuery($query);
						$cats = $this->db->loadColumn();

						$query = $this->db->getQuery(true)
							->select('i.application_id')
							->from('#__zoo_item AS i')
							->where('i.id = ' . (int) $id);
						$this->db->setQuery($query);
						$cats[] = 'app' . $this->db->loadResult();
					}
					break;

				default:
					return $this->pass(false);
			}
		}

		$cats = $this->makeArray($cats);

		$pass = $this->passSimple($cats, 'include');

		if ($pass && $this->params->inc_children == 2)
		{
			return $this->pass(false);
		}
		else if (!$pass && $this->params->inc_children)
		{
			foreach ($cats as $cat)
			{
				$cats = array_merge($cats, $this->getCatParentIds($cat));
			}
		}

		return $this->passSimple($cats);
	}

	function passItems()
	{
		if (!$this->request->id || $this->request->option != 'com_zoo')
		{
			return $this->pass(false);
		}

		if ($this->request->view != 'item')
		{
			return $this->pass(false);
		}

		$pass = false;

		// Pass Article Id
		if (!$this->passItemByType($pass, 'ContentIds'))
		{
			return $this->pass(false);
		}

		// Pass Authors
		if (!$this->passItemByType($pass, 'Authors'))
		{
			return $this->pass(false);
		}

		return $this->pass($pass);
	}

	public function getItem($fields = array())
	{
		$query = $this->db->getQuery(true)
			->select($fields)
			->from('#__zoo_item')
			->where('id = ' . (int) $this->request->id);
		$this->db->setQuery($query);

		return $this->db->loadObject();
	}

	function getCatParentIds($id = 0)
	{
		$parent_ids = array();

		if (!$id)
		{
			return $parent_ids;
		}

		while ($id)
		{
			if (substr($id, 0, 3) == 'app')
			{
				$parent_ids[] = $id;
				break;
			}

			$query = $this->db->getQuery(true)
				->select('c.parent')
				->from('#__zoo_category AS c')
				->where('c.id = ' . (int) $id);
			$this->db->setQuery($query);
			$pid = $this->db->loadResult();

			if (!$pid)
			{
				$query = $this->db->getQuery(true)
					->select('c.application_id')
					->from('#__zoo_category AS c')
					->where('c.id = ' . (int) $id);
				$this->db->setQuery($query);
				$app = $this->db->loadResult();

				if ($app)
				{
					$parent_ids[] = 'app' . $app;
				}

				break;
			}

			$parent_ids[] = $pid;

			$id = $pid;
		}

		return $parent_ids;
	}
}
