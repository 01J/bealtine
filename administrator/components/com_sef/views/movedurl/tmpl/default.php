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

$sefConfig =& SEFConfig::getConfig();
?>

	<script language="javascript">
	<!--
	Joomla.submitbutton = function(pressbutton)
	{
	    var form = document.adminForm;
	    if (pressbutton == 'cancel') {
	        Joomla.submitform( pressbutton );
	        return;
	    }
	    // do field validation
	    if (form.new.value == "" || form.old.value == "") {
	        alert( "<?php echo JText::_('COM_SEF_ERROR_EMPTY_URL'); ?>" );
	    } else {
	        Joomla.submitform( pressbutton );
	    }
	}
	//-->
	</script>
	<form action="index.php" method="post" name="adminForm" id="adminForm">
	<table class="adminform table table-striped">
	    <tr><th colspan="2"><?php echo JText::_('COM_SEF_URL'); ?></th></tr>
		<tr>
			<td width="150"><?php echo JText::_('COM_SEF_MOVED_FROM_URL'); ?></td>
			<td><input class="inputbox" type="text" size="100" name="old" style="width: 500px;" value="<?php echo $this->url->old; ?>">
			<?php echo $this->tooltip(JText::_('COM_SEF_THIS_IS_URL_TO_REDIRECT_FROM')); ?>
			</td>
		</tr>
		<tr>
			<td><?php echo JText::_('COM_SEF_MOVED_TO_URL');?></td>
			<td align="left"><input class="inputbox" type="text" size="100" style="width: 500px;" name="new" value="<?php echo $this->url->new; ?>">
			<?php echo $this->tooltip(JText::_('COM_SEF_THIS_IS_URL_TO_REDIRECT_TO'));?>
			</td>
		</tr>
	</table>

	<input type="hidden" name="option" value="com_sef" />
	<input type="hidden" name="id" value="<?php echo $this->url->id; ?>" />
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="controller" value="movedurls" />
	</form>
