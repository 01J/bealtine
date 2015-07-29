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

echo JHtml::_('tabs.panel', JText::_('COM_SEF_CACHE'), 'cache');
$sefConfig =& SEFConfig::getConfig();
$x = 0;
?>

<fieldset class="adminform">
  <legend><?php echo JText::_('COM_SEF_CACHE_CONFIGURATION');?></legend>
  <table class="adminform table table-stripped">
    <tr>
        <td><?php echo $this->lists['cache_info']; ?></td>
    </tr>
  </table>
  <table class="adminform table table-striped">
    <tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
        <td width="20"><?php echo $this->tooltip(JText::_('COM_SEF_TT_USE_CACHE'), JText::_('COM_SEF_USE_CACHE'));?></td>
        <td width="200"><?php echo JText::_('COM_SEF_USE_CACHE');?></td>
        <td><?php echo $this->lists['useCache'];?></td>
    </tr>
    
    <?php if ($sefConfig->professionalMode) { ?>
    <tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
        <td><?php echo $this->tooltip(JText::_('COM_SEF_TT_CACHE_SIZE'), JText::_('COM_SEF_MAXIMUM_CACHE_SIZE'));?></td>
        <td><?php echo JText::_('COM_SEF_MAXIMUM_CACHE_SIZE');?>:</td>
        <td><?php echo $this->lists['cacheSize'];?></td>
    </tr>
    <tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
        <td><?php echo $this->tooltip(JText::_('COM_SEF_TT_CACHE_HITS'), JText::_('COM_SEF_MINIMUM_CACHE_HITS_COUNT'));?></td>
        <td><?php echo JText::_('COM_SEF_MINIMUM_CACHE_HITS_COUNT');?>:</td>
        <td><?php echo $this->lists['cacheMinHits'];?></td>
    </tr>
    <tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
        <td><?php echo $this->tooltip(JText::_('COM_SEF_TT_CACHE_RECORDHITS'), JText::_('COM_SEF_RECORD_HITS_FOR_CACHED_URLS'));?></td>
        <td><?php echo JText::_('COM_SEF_RECORD_HITS_FOR_CACHED_URLS');?>:</td>
        <td><?php echo $this->lists['cacheRecordHits'];?></td>
    </tr>
    <tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
        <td><?php echo $this->tooltip(JText::_('COM_SEF_TT_CACHE_SHOWERR'), JText::_('COM_SEF_DISPLAY_CACHE_CORRUPTED_ERROR'));?></td>
        <td><?php echo JText::_('COM_SEF_DISPLAY_CACHE_CORRUPTED_ERROR');?>:</td>
        <td><?php echo $this->lists['cacheShowErr'];?></td>
    </tr>
    <?php } // $sefConfig->professionalMode ?>
  </table>
</fieldset>
