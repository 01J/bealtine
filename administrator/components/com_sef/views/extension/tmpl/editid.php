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

defined('_JEXEC') or die('Restricted access');

?>
<script type="text/javascript">
function submitbutton(task) {
	document.adminForm.task.value=task;
	document.adminForm.submit();
}

function close_win(refresh) {
    if (refresh) {
        window.parent.location.href = 'index.php?option=com_sef&controller=extension';
    }
	window.parent.SqueezeBox.close();
}
</script>
<form action="index.php" method="post" name="adminForm" autocomplete="off">

<fieldset>
	<div style="float: right">
		<button type="button" class="btn btn-primary" onclick="submitbutton('saveid');window.setTimeout('close_win(true)',500);">
		<!--<button type="button" onclick="submitbutton('saveid');">-->
			<?php echo JText::_( 'Save' );?></button>
		<button type="button" class="btn" onclick="close_win(false)">
			<?php echo JText::_( 'Cancel' );?></button>
	</div>
	<div class="configuration" >
	   <?php $txt = isset($this->ext->name) ? $this->ext->name : $this->ext->component->name; ?>
	   <?php echo $txt; ?>
	</div>
</fieldset>

<fieldset>
	<legend>
		<?php echo JText::_( 'Configuration' );?>
	</legend>
	<table class="admintable">
	   <tr>
	       <td class="key"><?php echo JText::_('COM_SEF_DOWNLOAD_ID'); ?></td>
	       <td>
	           <input type="text" name="downloadid" id="downloadid" size="60" value="<?php echo $this->ext->params->get('downloadId', ''); ?>" />
	       </td>
	   </tr>
	</table>
</fieldset>

<input type="hidden" name="controller" value="extension" />
<input type="hidden" name="option" value="com_sef" />
<input type="hidden" name="ext" value="<?php echo $this->ext->id; ?>" />
<input type="hidden" name="tmpl" value="component" />
<input type="hidden" name="task" value="" />
<?php echo JHTML::_( 'form.token' ); ?>
</form>