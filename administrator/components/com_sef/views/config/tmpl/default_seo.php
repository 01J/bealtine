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

echo JHtml::_('tabs.panel', JText::_('COM_SEF_SEO'), 'seo');
$x = 0;
?>
<fieldset class="adminform">
    <legend><?php echo JText::_('COM_SEF_SEO_CONFIGURATION'); ?></legend>
<?php
JoomSEF::OnlyPaidVersion();
?>
</fieldset>
<fieldset class="adminform">
    <legend><?php echo JText::_('COM_SEF_CANONICAL_CONFIGURATION'); ?></legend>
    <table class="adminform table table-striped">
        <tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
            <td width="20"><?php echo $this->tooltip(JText::_('COM_SEF_TT_CANONICALS_REMOVE'), JText::_('COM_SEF_CANONICALS_REMOVE'));?></td>
            <td width="200"><?php echo JText::_('COM_SEF_CANONICALS_REMOVE');?>:</td>
            <td><?php echo $this->lists['canonicalsRemove'];?></td>
        </tr>
        <tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
            <td width="20"><?php echo $this->tooltip(JText::_('COM_SEF_TT_CANONICALS_FIX'), JText::_('COM_SEF_CANONICALS_FIX'));?></td>
            <td width="200"><?php echo JText::_('COM_SEF_CANONICALS_FIX');?>:</td>
            <td><?php echo $this->lists['canonicalsFix'];?></td>
        </tr>
    </table>
</fieldset>