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

<form action="index.php" method="post" name="adminForm" id="adminForm">
<fieldset class="adminform">
<legend><?php echo JText::_('COM_SEF_IMPORT_REPORT'); ?></legend>
<table class="adminform table">
<tr>
    <th colspan="2">
        <?php if( $this->success ) {
            echo '<span style="color: green">' . JText::_('COM_SEF_IMPORT_SUCCESSFUL') . '</span>';
        } else {
            echo '<span style="color: red">' . JText::_('COM_SEF_ERROR_IMPORT') . '</span>';
        }
        ?>
    </th>
</tr>
<tr>
    <td width="200"><?php echo JText::_('COM_SEF_FILE_FORMAT'); ?>:</td>
    <td><?php echo $this->filetype; ?></td>
</tr>
<tr>
    <td><?php echo JText::_('COM_SEF_PARSED_LINES'); ?>:</td>
    <td><?php echo $this->total; ?></td>
</tr>
<tr>
    <td><?php echo JText::_('COM_SEF_SUCCESSFULLY_IMPORTED'); ?>:</td>
    <td><?php echo $this->imported; ?></td>
</tr>
<tr>
    <td><?php echo JText::_('COM_SEF_NOT_IMPORTED'); ?>:</td>
    <td><?php echo $this->notImported; ?></td>
</tr>
<tr>
    <td colspan="2"><input type="button" value="<?php echo JText::_('COM_SEF_OK'); ?>" onclick="document.adminForm.submit();" /></td>
</tr>
</table>
</fieldset>

<input type="hidden" name="option" value="com_sef" />
<input type="hidden" name="task" value="" />
<input type="hidden" name="controller" value="sefurls" />
</form>
