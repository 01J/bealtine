<?php
/**
 * NoNumber Framework Helper File: Assignments: CookieConfirm
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

class nnFrameworkAssignmentsCookieConfirm extends nnFrameworkAssignment
{
	function passCookieConfirm()
	{
		require_once JPATH_PLUGINS . '/system/cookieconfirm/core.php';
		$pass = plgSystemCookieconfirmCore::getInstance()->isCookiesAllowed();

		return $this->pass($pass);
	}
}
