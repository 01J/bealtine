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

<fieldset>
<legend><?php echo JText::_('COM_SEF_INSTALLED_SEF_EXTENSIONS'); ?></legend>

<table class="adminlist table table-striped">
<thead>
    <tr>
        <th width="15%" class="title" colspan="2">
            <?php echo JText::_('COM_SEF_SEF_EXTENSION'); ?>
        </th>
        <th width="15%" class="title">
            <?php echo JText::_('COM_SEF_COMPONENT'); ?>
        </th>
        <!--  <th width="15%" class="title">
            <?php echo JText::_('COM_SEF_OPTION'); ?>
        </th>-->
        <th width="15%" class="title">
            <?php echo JText::_('COM_SEF_AUTHOR'); ?>
        </th>
        <th width="5%" class="title">
            <?php echo JText::_('COM_SEF_VERSION'); ?>
        </th>
        <th width="10%" class="title">
            <?php echo JText::_('COM_SEF_DATE'); ?>
        </th>
        <th width="5%" class="title">
            <?php echo JText::_('COM_SEF_NEWEST_VERSION'); ?>
        </th>
        <th width="5%" class="title">
            <?php echo JText::_('COM_SEF_TYPE'); ?>
        </th>
        <th width="5%" class="title">
            <?php echo JText::_('COM_SEF_UPGRADE'); ?>
        </th>
        <th width="10%" class="title">
            <?php echo JText::_('COM_SEF_ACTIVE_HANDLER'); ?>
        </th>
    </tr>
</thead>
<tbody>
    <?php
    $k = 0;
    $i = 0;
    foreach (array_keys($this->extensions) as $key) {
        $row = &$this->extensions[$key];
        ?>
        <tr class="<?php echo 'row'. $k; ?>">
            <td width="1%">
                <input type="radio" id="cb<?php echo $i;?>" name="cid[]" value="<?php echo $row->id; ?>" onclick="isChecked(this.checked);" />
            </td>
            <td>
                <span class="editlinktip hasTip" title="<?php echo JText::_('COM_SEF_EXT_PARAMETERS'); ?>">
                <a href="javascript:void(0);" onclick="return listItemTask('cb<?php echo $i;?>', 'editext')">
                <?php echo $row->name; ?>
                </a>
                </span>
            </td>
            <td>
                <?php
                if( !is_null(@$row->component) ) {
                    echo $row->component->name;
                }
                else {
                    echo '- ' . JText::_('COM_SEF_NOT_INSTALLED') . ' -';
                }
                ?>
            </td>
            <!-- <td>
                <?php echo @$row->option != '' ? $row->option : "&nbsp;"; ?>
            </td>-->
            <td>
                <?php
                if( @$row->authorUrl != '' ) {
                    echo '<span class="editlinktip hasTip" title="' . JText::_('COM_SEF_OPEN_AUTHORS_SITE') . '">';
                    echo '<a href="' . (substr( $row->authorUrl, 0, 7) == 'http://' ? $row->authorUrl : 'http://'.$row->authorUrl) . '" target="_blank">';
                    echo @$row->author != '' ? $row->author : "&nbsp;";
                    echo '</a>';
                    echo '</span>';
                }
                else {
                    echo @$row->author != '' ? $row->author : "&nbsp;";
                }

                ?>
            </td>
            <td align="center">
                <?php
                    if (isset($row->version) && !empty($row->version)) {
                        if (is_null($row->newestVersion) || version_compare($row->version, $row->newestVersion, '>=')) {
                            echo '<span style="color: green">'.$row->version.'</span>';
                        }
                        else {
                            echo '<span style="color: red">'.$row->version.'</span>';
                        }
                    }
                    else {
                        echo "&nbsp;";
                    }
                ?>
            </td>
            <td align="center">
                <?php echo @$row->creationdate != '' ? $row->creationdate : "&nbsp;"; ?>
            </td>
            <td align="center">
                <?php
                if( is_null($row->newestVersion) ) {
                    echo '-';
                }
                else {
                    echo $row->newestVersion;
                }
                ?>
            </td>
            <td align="center">
                <?php
                if( is_null($row->type) ) {
                    echo '-';
                }
                else {
                    if ($row->type == 'Paid') {
                        $img = 'icon-16-key';
                        $ttl = JText::_('COM_SEF_DOWNLOAD_ID_SET');
                        $txt = JText::_('COM_SEF_CLICK_TO_CHANGE');
                        if ($row->params->get('downloadId', '') == '') {
                            $img .= '_bw';
                            $ttl = JText::_('COM_SEF_DOWNLOAD_ID_NOT_SET');
                            $txt = JText::_('COM_SEF_CLICK_TO_SET');
                        }

                        $href = 'index.php?option=com_sef&amp;controller=extension&amp;cid[]='.$row->id.'&amp;task=editId&amp;tmpl=component';
                        echo '<span class="editlinktip hasTip" title="'.$ttl.'::'.$txt.'">';
                        echo '<a class="modal" href="'.$href.'" rel="{handler: \'iframe\', size: {x: 570, y: 150}}"><img src="components/com_sef/assets/images/'.$img.'.png" /></a>';
                        echo '</span>&nbsp;';
                    }

                    if ($row->type == 'Free') {
                        echo JText::_('COM_SEF_FREE');
                    } else {
                        echo JText::_('COM_SEF_PAID');
                    }
                }
                ?>
            </td>
            <td>
                <?php
                if( is_null($row->newestVersion) ) {
                    echo '-';
                }
                else if( ((strnatcasecmp($row->newestVersion, $row->version) > 0) ||
                     (strnatcasecmp($row->newestVersion, substr($row->version, 0, strpos($row->version, '-'))) == 0)) )
                {
                    ?>
                    <input class="button hasTip btn btn-small" type="button" value="<?php echo JText::_('COM_SEF_UPGRADE'); ?>" onclick="upgradeExt('<?php echo $row->id; ?>')" title="<?php echo JText::_('COM_SEF_AUTO_EXT_UPGRADE'); ?>" />
                    <?php
                }
                else {
                    echo JText::_('COM_SEF_UP_TO_DATE');
                }
                ?>
            </td>
            <td>
                <span class="editlinktip hasTip" title="<?php echo JText::_('COM_SEF_CHANGE_ACTIVE_HANDLER'); ?>">
                <a href="javascript:void(0);" onclick="return changeHandler('<?php echo $row->id;?>');" style="color: <?php echo $row->handler->color; ?>">
                <?php echo $row->handler->text; ?>
                </a>
                </span>
            </td>
        </tr>
        <?php
        $k = 1 - $k;
        $i++;
    }
    ?>
</tbody>
</table>
</fieldset>
