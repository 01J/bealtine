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
echo JHTML::_('tabs.panel', JText::_('COM_SEF_LANGUAGE'), 'language');
$x = 0;
$configStyle = $msgStyle = '';
if (JPluginHelper::isEnabled('system', 'languagefilter')) {
    $configStyle = 'style="display: none;"';
}
else {
    $msgStyle = 'style="display: none;"';
}
?>
<fieldset class="adminform">
<legend><?php echo JText::_('COM_SEF_LANGUAGE_SETTINGS'); ?></legend>
<div id="sefConfigLanguageMsg" <?php echo $msgStyle; ?>>
    <table class="adminform table table-striped">
        <tr><td><?php echo JText::_('COM_SEF_LANGUAGE_DISABLED'); ?></td></tr>
        <tr><td>
            <input type="button" value="<?php echo JText::_('COM_SEF_DISABLE_PLUGIN'); ?>" onclick="disableLanguagePlugin(this);" />
            <img id="sefAjaxProgressImg" src="components/com_sef/assets/images/ajax-loader-small.gif" style="margin-left: 15px; display: none;" />
        </td></tr>
    </table>
</div>
<div id="sefConfigLanguageConfig" <?php echo $configStyle ?>>
<table class="adminform table table-striped">
	<tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
		<td width="20"><?php echo $this->tooltip(JText::_('COM_SEF_TT_LANG_ENABLE'),JText::_('COM_SEF_LANG_ENABLE')); ?></td>
		<td width="200"><?php echo JText::_('COM_SEF_LANG_ENABLE'); ?></td>
		<td><?php echo $this->lists['langEnable']; ?></td>
	</tr>
	<tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
        <td width="20"><?php echo $this->tooltip(JText::_('COM_SEF_TT_JF_LANG_PLACEMENT'), JText::_('COM_SEF_LANGUAGE_INTEGRATION'));?></td>
        <td width="200"><?php echo JText::_('COM_SEF_LANGUAGE_INTEGRATION');?></td>
        <td><?php echo $this->lists['langPlacementJoomla'];?></td>
    </tr>
    <tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
        <td><?php echo $this->tooltip(JText::_('COM_SEF_TT_JF_ALWAYS_USE_LANG'), JText::_('COM_SEF_ALWAYS_USE_LANGUAGE'));?></td>
        <td><?php echo JText::_('COM_SEF_ALWAYS_USE_LANGUAGE');?></td>
        <td><?php echo $this->lists['alwaysUseLangJoomla'];?></td>
    </tr>
    <tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
        <td><?php echo $this->tooltip(JText::_('COM_SEF_TT_JF_ALWAYS_USE_LANG_HOME'), JText::_('COM_SEF_ALWAYS_USE_LANGUAGE_HOME'));?></td>
        <td><?php echo JText::_('COM_SEF_ALWAYS_USE_LANGUAGE_HOME');?></td>
        <td><?php echo $this->lists['alwaysUseLangHomeJoomla'];?></td>
    </tr>
    <tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
    	<td><?php echo $this->tooltip(JText::_('COM_SEF_TT_ADDLANG_TO_MULTILANGUAGE'),JText::_('COM_SEF_ADDLANG_TO_MULTILANGUAGE')); ?></td>
    	<td><?php echo JText::_('COM_SEF_ADDLANG_TO_MULTILANGUAGE'); ?></td>
    	<td><?php echo $this->lists['addLangMulti']; ?></td>
    </tr>
    <?php
    if(JPluginHelper::isEnabled('system','falangdriver')) {
    	?>
    	<tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
    		<td><?php echo $this->tooltip(JText::_('COM_SEF_TT_TRANSLATE_ITEMS'),JText::_('COM_SEF_TRANSLATE_ITEMS')); ?></td>
    		<td><?php echo JText::_('COM_SEF_TRANSLATE_ITEMS'); ?></td>
    		<td><?php echo $this->lists['translateItems']; ?></td>
    	</tr>
    	<?php
    }
    ?>
    <tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
        <td><?php echo $this->tooltip(JText::_('COM_SEF_TT_JF_BROWSER_LANG'), JText::_('COM_SEF_GET_LANGUAGE_FROM_BROWSER_SETTING'));?></td>
        <td><?php echo JText::_('COM_SEF_GET_LANGUAGE_FROM_BROWSER_SETTING');?></td>
        <td><?php echo $this->lists['browserLangJoomla'];?></td>
    </tr>
    <tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
        <td><?php echo $this->tooltip(JText::_('COM_SEF_TT_JF_LANG_COOKIE'), JText::_('COM_SEF_SAVE_LANGUAGE_TO_COOKIE'));?></td>
        <td><?php echo JText::_('COM_SEF_SAVE_LANGUAGE_TO_COOKIE');?></td>
        <td><?php echo $this->lists['langCookieJoomla'];?></td>
    </tr>
    <tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
        <td><?php echo $this->tooltip(JText::_('COM_SEF_TT_ROOT_303'), JText::_('COM_SEF_ROOT_303'));?></td>
        <td><?php echo JText::_('COM_SEF_ROOT_303');?>:</td>
        <td><?php echo $this->lists['rootLangRedirect303'];?></td>
    </tr>
    <tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
        <td><?php echo $this->tooltip(JText::_('COM_SEF_TT_MENU_ASSOCIATIONS'), JText::_('COM_SEF_MENU_ASSOCIATIONS'));?></td>
        <td><?php echo JText::_('COM_SEF_MENU_ASSOCIATIONS');?>:</td>
        <td><?php echo $this->lists['langMenuAssociations'];?></td>
    </tr>
    <tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
        <td><?php echo $this->tooltip(JText::_('COM_SEF_TT_MISMATCHED_LANG'), JText::_('COM_SEF_MISMATCHED_LANG'));?></td>
        <td><?php echo JText::_('COM_SEF_MISMATCHED_LANG');?>:</td>
        <td><?php echo $this->lists['mismatchedLangHandling'];?></td>
    </tr>
    <tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
        <td><?php echo $this->tooltip(JText::_('COM_SEF_TT_JF_MAIN_LANG'), JText::_('COM_SEF_MAIN_LANGUAGE'));?></td>
        <td><?php echo JText::_('COM_SEF_MAIN_LANGUAGE');?>:</td>
        <td><?php echo $this->lists['mainLanguageJoomla'];?></td>
    </tr>
</table>
<table class="adminform table table-striped">
	<tr>
		<th width="20"><?php echo $this->tooltip(JText::_('COM_SEF_TT_JF_DOMAIN'), JText::_('COM_SEF_DOMAIN_CONFIGURATION'));?></th>
      	<th width="200"><?php echo JText::_('COM_SEF_DOMAIN_CONFIGURATION'); ?></th>
      	<th>&nbsp;</th>
	</tr>
	<?php
	for($i=0;$i<count($this->lists["subdomainsJoomla"]);$i++) {
		$l=$this->lists["subdomainsJoomla"][$i];
		?>
		<tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
			<td colspan="2"><?php echo $l->title; ?></td>
			<td><input type="text" name="subDomainsJoomla[<?php echo $l->sef; ?>]" class="inputbox" size="45" value="<?php echo $l->value; ?>" /></td>
		</tr>
		<?php
	}
	?>
    <tr>
        <td width="20"><?php echo JHTML::_('tooltip', JText::_('COM_SEF_TT_WRONG_DOMAIN_HANDLING'), JText::_('COM_SEF_WRONG_DOMAIN_HANDLING'));?></td>
        <td width="200"><?php echo JText::_('COM_SEF_WRONG_DOMAIN_HANDLING'); ?></td>
        <td><?php echo $this->lists['wrongDomainHandling']; ?></td>
    </tr>
</table>
</div>
</fieldset>
<fieldset class="adminform">
<legend><?php echo JText::_('COM_SEF_LANGUAGE_VM_CURRENCY'); ?></legend>
<?php
if (!$this->lists['vm_installed']) {
    echo JText::_('COM_SEF_VM_NOT_INSTALLED');
}
else {
    $x = 0;
    ?>
    <table class="adminform table table-striped">
        <tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
            <td width="20"><?php echo $this->tooltip(JText::_('COM_SEF_TT_VM_CURRENCY_ENABLE'), JText::_('COM_SEF_VM_CURRENCY_ENABLE'));?></td>
            <td width="200"><?php echo JText::_('COM_SEF_VM_CURRENCY_ENABLE');?>:</td>
            <td><?php echo $this->lists['vmCurrencyEnable'];?></td>
        </tr>
        <?php
        foreach ($this->lists['vmCurrency'] as $currency) {
            ?>
    		<tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
    			<td colspan="2"><?php echo $currency->lang; ?></td>
    			<td><?php echo $currency->list; ?></td>
    		</tr>
            <?php
        }
        ?>
    </table>
    <?php
}
?>
</fieldset>