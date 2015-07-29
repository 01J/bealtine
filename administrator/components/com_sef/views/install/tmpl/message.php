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
defined('_JEXEC') or die('Restricted access');

$state      = &$this->get('State');
$result     = $state->get('result');
$name       = $state->get('name');
$message    = $state->get('message');
?>

<table class="adminheading">
	<tr>
		<th class="install">
		<?php echo $name; ?>
		</th>
	</tr>
	</table>

	<table class="adminform">
	<tr>
		<td align="left">
		<strong><?php echo $message; ?></strong>
		</td>
	</tr>
	<tr>
		<td colspan="2" align="center">
		[&nbsp;<a href="<?php echo $this->url; ?>" style="font-size: 16px; font-weight: bold">Continue ...</a>&nbsp;]
		</td>
	</tr>
</table>
