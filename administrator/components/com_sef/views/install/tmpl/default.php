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

<script language="javascript" type="text/javascript">
<!--
	function submitbutton3(pressbutton) {
		var form = document.adminForm_dir;

		// do field validation
		if (form.install_directory.value == ""){
			alert( "<?php echo JText::_('COM_SEF_SELECT_DIRECTORY', true); ?>" );
		} else {
			form.submit();
		}
	}

	function upgradeExt(extension) {
		var form = document.adminForm;

		form.fromserver.value = '1';
		form.task.value = 'doUpgrade';
		form.ext.value = extension;
		form.submit();
	}

    function changeHandler(option) {
        var form = document.adminForm;

        form.task.value = 'changeHandler';
        form.ext.value = option;
        form.controller.value = 'extension';
        form.submit();
    }
//-->
</script>

<div style="margin: 10px 0px; padding: 5px 15px 5px 35px; min-height: 25px; border: 1px solid #cc0000; background: #ffffcc; text-align: left; color: red; font-weight: bold; background-image: url(../includes/js/ThemeOffice/warning.png); background-repeat: no-repeat; background-position: 10px 50%;">
Warning: Installing 3rd party extensions may compromise your server's security. Upgrading your Joomla! installation will not update your 3rd party extensions.<br />For more information on keeping your site secure, please see the <a href="http://forum.joomla.org/index.php/board,267.0.html" target="_blank" style="color: blue; text-decoration: underline;">Joomla! Security Forum</a>.
</div>

<form enctype="multipart/form-data" action="index.php" method="post" name="adminForm_pck">
<table class="adminform">
<tr>
	<th colspan="2"><?php echo JText::_('COM_SEF_UPLOAD_PACKAGE'); ?></th>
</tr>
<tr>
	<td width="120">
		<label for="install_package"><?php echo JText::_('COM_SEF_PACKAGE_FILE'); ?>:</label>
	</td>
	<td>
		<input class="input_box" id="install_package" name="install_package" type="file" size="57" />
		<input class="button" type="submit" value="<?php echo JText::_('COM_SEF_UPLOAD_FILE'); ?> &amp; <?php echo JText::_( 'COM_SEF_INSTALL' ); ?>" />
	</td>
</tr>
</table>
<input type="hidden" name="option" value="com_sef" />
<input type="hidden" name="task" value="doInstall" />
<input type="hidden" name="controller" value="" />
<input type="hidden" name="installtype" value="upload" />
<?php echo JHTML::_('form.token'); ?>
</form>

<form enctype="multipart/form-data" action="index.php" method="post" name="adminForm_dir">
<table class="adminform">
<tr>
	<th colspan="2"><?php echo JText::_('COM_SEF_INSTALL_FROM_DIRECTORY'); ?></th>
</tr>
<tr>
	<td width="120">
		<label for="install_directory"><?php echo JText::_('COM_SEF_INSTALL_DIRECTORY'); ?>:</label>
	</td>
	<td>
		<input type="text" id="install_directory" name="install_directory" class="input_box" size="70" value="" />
		<input type="button" class="button" value="<?php echo JText::_('COM_SEF_INSTALL'); ?>" onclick="submitbutton3()" />
	</td>
</tr>
</table>
<input type="hidden" name="option" value="com_sef" />
<input type="hidden" name="task" value="doInstall" />
<input type="hidden" name="controller" value="" />
<input type="hidden" name="installtype" value="folder" />
<?php echo JHTML::_('form.token'); ?>
</form>

<form action="index.php" method="post" name="adminForm" id="adminForm">
<?php echo $this->loadTemplate('extslist'); ?>
<input type="hidden" name="option" value="com_sef" />
<input type="hidden" name="boxchecked" value="0" />
<input type="hidden" name="task" value="" />
<input type="hidden" name="controller" value="" />
<input type="hidden" name="redirto" value="task=installext" />
<input type="hidden" name="fromserver" value="0" />
<input type="hidden" name="ext" value="" />
<?php echo JHTML::_('form.token'); ?>
</form>
