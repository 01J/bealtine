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

$sefConfig=SEFConfig::getConfig();
echo JHtml::_('tabs.panel', JText::_('COM_SEF_BASIC'), 'basic');
        $x = 0;
		?>
		
  <fieldset class="adminform">
      <legend><?php echo JText::_('COM_SEF_MAIN_JOOMSEF_CONFIGURATION'); ?></legend>
      <table class="adminform table table-striped">
        <tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
            <td width="20"><?php echo $this->tooltip(JText::_('COM_SEF_TT_JOOMSEF_ENABLED'),JText::_('COM_SEF_ENABLED'));?></td>
            <td width="200"><?php echo JText::_('COM_SEF_JOOMSEF_ENABLED');?>?</td>
            <td><?php echo $this->lists['enabled'];?></td>
        </tr>
        <tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
            <td><?php echo $this->tooltip(JText::_('COM_SEF_TT_DISABLE_NEW_SEF'),JText::_('COM_SEF_DISABLE_CREATION_OF_NEW_SEF_URLS'));?></td>
            <td><?php echo JText::_('COM_SEF_DISABLE_CREATION_OF_NEW_SEF_URLS');?></td>
            <td><?php echo $this->lists['disableNewSEF']; ?></td>
        </tr>
        <tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
            <td><?php echo $this->tooltip(JText::_('COM_SEF_TT_ENABLE_PROFESSIONAL_MODE'),JText::_('COM_SEF_ENABLE_PROFESSIONAL_MODE'));?></td>
            <td><?php echo JText::_('COM_SEF_ENABLE_PROFESSIONAL_MODE');?></td>
            <td><?php echo $this->lists['professionalMode']; ?></td>
        </tr>
      </table>
  </fieldset>
  
  <?php $x = 0; ?>
  <fieldset class="adminform">
      <legend><?php echo JText::_('COM_SEF_BASIC_CONFIGURATION'); ?></legend>
      <table class="adminform table table-striped">
        <tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
            <td width="20"><?php echo $this->tooltip(JText::_('COM_SEF_TT_SUFFIX'),JText::_('COM_SEF_SUFFIX'));?></td>
            <td width="200"><?php echo JText::_('COM_SEF_SUFFIX');?></td>
            <td><input type="text" name="suffix" value="<?php echo $sefConfig->suffix; ?>" size="10" maxlength="6"></td>
        </tr>
        <tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
            <td><?php echo $this->tooltip(JText::_('COM_SEF_TT_USE_ALIAS'),JText::_('COM_SEF_USE_TITLE_ALIAS'));?></td>
            <td><?php echo JText::_('COM_SEF_USE_ALIAS');?></td>
            <td><?php echo $this->lists['useAlias'];?></td>
        </tr>
        <tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
            <td><?php echo $this->tooltip(JText::_('COM_SEF_TT_LOWERCASE'),JText::_('COM_SEF_ALL_LOWERCASE'));?></td>
            <td><?php echo JText::_('COM_SEF_ALL_LOWERCASE');?>?</td>
            <td><?php echo $this->lists['lowerCase'];?></td>
        </tr>
        <tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
            <td><?php echo $this->tooltip(JText::_('COM_SEF_TT_WWW_HANDLING'),JText::_('COM_SEF_WWW_HANDLING'));?></td>
            <td><?php echo JText::_('COM_SEF_WWW_HANDLING');?></td>
            <td><?php echo $this->lists['wwwHandling']; ?></td>
        </tr>
        
        <?php if ($sefConfig->professionalMode) { ?>
        <tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
            <td><?php echo $this->tooltip(JText::_('COM_SEF_TT_NUMBER_DUPLICATES'),JText::_('COM_SEF_NUMBER_DUPLICATE_URLS'));?></td>
            <td><?php echo JText::_('COM_SEF_NUMBER_DUPLICATE_URLS');?></td>
            <td><?php echo $this->lists['numberDuplicates']; ?></td>
        </tr>
        <tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
            <td><?php echo $this->tooltip(JText::_('COM_SEF_TT_REPLACE_CHAR'),JText::_('COM_SEF_REPLACE_CHAR'));?></td>
            <td><?php echo JText::_('COM_SEF_REPLACE_CHAR');?></td>
            <td><input type="text" name="replacement" value="<?php echo $sefConfig->replacement;?>" size="1" maxlength="1"></td>
        </tr>
        <tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
            <td><?php echo $this->tooltip(JText::_('COM_SEF_TT_PAGE_SEP_CHAR'),JText::_('COM_SEF_PAGE_SEP_CHAR'));?></td>
            <td><?php echo JText::_('COM_SEF_PAGE_SEP_CHAR');?></td>
            <td><input type="text" name="pagerep" value="<?php echo $sefConfig->pagerep;?>" size="1" maxlength="1"></td>
        </tr>
        <tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
            <td><?php echo $this->tooltip(JText::_('COM_SEF_TT_STRIP_CHAR'),JText::_('COM_SEF_STRIP_CHAR'));?></td>
            <td><?php echo JText::_('COM_SEF_STRIP_CHAR');?></td>
            <td><input type="text" name="stripthese" value="<?php echo $sefConfig->stripthese;?>" size="60" maxlength="255"></td>
        </tr>
        <tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
            <td><?php echo $this->tooltip(JText::_('COM_SEF_TT_FRIEND_TRIM_CHAR'),JText::_('COM_SEF_TRIM_FRIEND_TRIM_CHAR'));?></td>
            <td><?php echo JText::_('COM_SEF_TRIM_FRIEND_TRIM_CHAR');?></td>
            <td><input type="text" name="friendlytrim" value="<?php echo $sefConfig->friendlytrim;?>" size="60" maxlength="255"></td>
        </tr>
        <tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
            <td><?php echo $this->tooltip(JText::_('COM_SEF_TT_PAGE_TEXT'),JText::_('COM_SEF_PAGE_TEXT'));?></td>
            <td><?php echo JText::_('COM_SEF_PAGE_TEXT');?></td>
            <td><input type="text" name="pagetext" value="<?php echo $sefConfig->pagetext; ?>" size="30" maxlength="30"></td>
        </tr>
        <?php } // $sefConfig->professionalMode ?>
      </table>
  </fieldset>
