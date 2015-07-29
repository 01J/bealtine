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

echo JHTML :: _('tabs.panel', JText :: _('COM_SEF_SUBDOMAINS'), 'subdomains');
?>
<fieldset class="adminform">
    <legend><?php echo JText::_('COM_SEF_SUBDOMAINS'); ?></legend>
    <?php
    echo JHTML :: _('tabs.start', 'subdomains_lang');
    foreach ($this->langs as $lang)
    {
        $sef = $lang->sef;
        echo JHTML::_('tabs.panel', JHTML::_('image', 'media/mod_languages/images/' . $lang->image . '.gif', $lang->sef, 'title="'.$lang->sef.'"'), 'subdomains_lang_' . $lang->sef);
        ?>
        <table id="subdomains_tbl_<?php echo $sef; ?>" class="table">
            <tr>
                <th width="140" align="left">
                <?php echo Jtext::_('COM_SEF_SUBDOMAIN'); ?>
                </th>
                <th width="140" align="left">
                <?php echo Jtext::_('COM_SEF_MENU'); ?>
                </th>
                <th width="140" align="left">
                <?php echo Jtext::_('COM_SEF_TITLEPAGE'); ?>
                </th>
                <th width="140">
                <?php echo JText::_('COM_SEF_REMOVE_SUBDOMAIN'); ?>
                </th>
            </tr>
            <?php
    
            if (isset ($this->subdomains[$sef]))
            {
                foreach ($this->subdomains[$sef] as $i => $subdomain)
                {
                    ?>
                    <tr class="row<?php echo $i%2; ?>">
                        <td valign="top"><input class="inputbox" type="text" style="text-align:right" name="subdomain[title][<?php echo $sef; ?>][<?php echo $i; ?>]" size="10" value="<?php echo $subdomain->subdomain; ?>"/>.<?php echo $this->rootDomain; ?>
                        <td valign="top"><?php echo $subdomain->Itemid; ?></td>
                        <td valign="top"><?php echo $subdomain->Itemid_titlepage; ?></td>
                        <td valign="top"><input class="button" type="button" onclick="remove_subdomain(this);" value="<?php echo Jtext::_('COM_SEF_REMOVE_SUBDOMAIN'); ?>" /></td>
                    </tr>
                    <?php
                }
            }
            ?>
            <tr>
                <td><input class="button" type="button" onclick="add_subdomain('<?php echo $sef; ?>');" value="<?php echo Jtext::_('COM_SEF_ADD_SUBDOMAIN'); ?>" /></td>
                <td colspan="3"></td>
            </tr>
        </table>
        <?php
    }
    echo JHTML :: _('tabs.end');
    ?>
</fieldset>
