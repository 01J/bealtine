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
<form action="index.php" method="post" name="adminForm" id="adminForm">
	<fieldset class="adminform">
	   <legend><?php echo JText::_('COM_SEF_META_TAGS'); ?></legend>
	   <table class="admintable">
		<tr><td colspan="2"><?php echo  $this->tooltip(JText::_('COM_SEF_INFO_JOOMSEF_PLUGIN'), JText::_('COM_SEF_JOOMSEF_PLUGIN_NOTICE')); ?></td></tr>
		<tr>
		  <td class="key"><?php echo JText::_('COM_SEF_TITLE'); ?>:</td>
		  <td align="left"><input class="inputbox" type="text" size="100" maxlength="255" name="meta[metatitle]" value="<?php echo htmlspecialchars($this->sef->metatitle); ?>">
		  </td>
		</tr>
		<tr>
		  <td class="key"><?php echo JText::_('COM_SEF_META_DESCRITION'); ?>:</td>
		  <td align="left"><input class="inputbox" type="text" size="100" maxlength="255" name="meta[metadesc]" value="<?php echo htmlspecialchars($this->sef->metadesc); ?>">
		  </td>
		</tr>
		<tr>
		  <td class="key"><?php echo JText::_('COM_SEF_META_KEYWORDS'); ?>:</td>
		  <td align="left"><input class="inputbox" type="text" size="100" maxlength="255" name="meta[metakey]" value="<?php echo htmlspecialchars($this->sef->metakey); ?>">
		  </td>
		</tr>
		<tr>
		  <td class="key"><?php echo JText::_('COM_SEF_META_CONTENT_LANGUAGE'); ?>:</td>
		  <td align="left"><input class="inputbox" type="text" size="100" maxlength="30" name="meta[metalang]" value="<?php echo htmlspecialchars($this->sef->metalang); ?>">
		  </td>
		</tr>
		<tr>
		  <td class="key"><?php echo JText::_('COM_SEF_META_ROBOTS'); ?>:</td>
		  <td align="left"><input class="inputbox" type="text" size="100" maxlength="30" name="meta[metarobots]" value="<?php echo htmlspecialchars($this->sef->metarobots); ?>">
		  </td>
		</tr>
		<tr>
		  <td class="key"><?php echo JText::_('COM_SEF_META_GOOGLEBOT'); ?>:</td>
		  <td align="left"><input class="inputbox" type="text" size="100" maxlength="30" name="meta[metagoogle]" value="<?php echo htmlspecialchars($this->sef->metagoogle); ?>">
		  </td>
		</tr>
	</table>
	</fieldset>
	<input type="hidden" name="option" value="com_sef" />
	<input type="hidden" name="controller" value="sefurls" />
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="ids" value="<?php echo implode(",",$this->cid); ?>" />
</form>