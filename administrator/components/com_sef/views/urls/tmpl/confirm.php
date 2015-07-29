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
?>
<form action="index.php" method="post" name="adminForm">

		<table class="adminheading">
		<tr>
			<td><p class="message"><?php echo $this->msg; ?><br />
			<?php if( $this->count == 0 ) { ?>
				<input type="submit" name="continue" value=" <?php echo JText::_('COM_SEF_OK'); ?> " />
			<?php } else { ?>
			    <input type="hidden" name="controller" value="urls" />
			    <input type="hidden" name="task" value="purge" />
			    <input type="hidden" name="type" value="<?php echo JRequest::getVar('type', -1); ?>" />
				<input type="submit" name="confirmed" value=" <?php echo JText::_('COM_SEF_PROCEED'); ?> " />
			<?php } ?>
				</p>
			</td>
		</tr>
	  </table>

<input type="hidden" name="option" value="com_sef" />
</form>
