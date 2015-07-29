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
	function upgradeExt(extension) {
		var form = document.adminForm;

		form.fromserver.value = '1';
		form.task.value = 'doUpgrade';
		form.ext.value = extension;
		form.submit();
	}

	function extParams(option) {
	    var form = document.adminFormCmp;

	    form.task.value = 'editExt';
	    $('hiddenCid').value = option;
	    form.submit();
	}

	function getExt(option) {
	    var form = document.adminFormCmp;

	    form.task.value = 'doInstall';
	    form.installtype.value = 'server';
	    form.extension.value = option;
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

<form action="index.php" method="post" name="adminForm" id="adminForm">

<?php echo $this->loadTemplate('extslist'); ?>

<input type="hidden" name="option" value="com_sef" />
<input type="hidden" name="task" value="" />
<input type="hidden" name="boxchecked" value="0" />
<input type="hidden" name="controller" value="" />
<input type="hidden" name="redirto" value="controller=extension" />
<input type="hidden" name="fromserver" value="0" />
<input type="hidden" name="ext" value="" />
<?php echo JHTML::_('form.token'); ?>
</form>

<form action="index.php" method="post" name="adminFormCmp" id="adminFormCmp">
<fieldset>
<legend><?php echo JText::_('COM_SEF_COMPONENTS_WITHOUT_SEF_EXTENSION_INSTALLED'); ?></legend>

<table class="adminlist table table-striped">
<thead>
    <tr>
        <th width="50%" class="title">
            <?php echo JText::_('COM_SEF_COMPONENT'); ?>
        </th>
        <!-- <th width="20%" class="title">
            <?php echo JText::_('COM_SEF_OPTION'); ?>
        </th> -->
        <th class="title">
            <?php echo JText::_('COM_SEF_EXTENSION_AVAILABILITY'); ?>
        </th>
        <th class="title">
            <?php echo JText::_('COM_SEF_INSTALLATION'); ?>
        </th>
        <th class="title">
            <?php echo JText::_('COM_SEF_ACTIVE_HANDLER'); ?>
        </th>
        <th>
            <?php echo JText::_('COM_SEF_PARAMETERS'); ?>
        </th>
    </tr>
</thead>
<tbody>
    <?php
    $k = 0;
    $i = count($this->extensions);
    foreach (array_keys($this->components) as $key) {
        $row =& $this->components[$key];
        ?>
        <tr class="<?php echo 'row'. $k; ?>">
            <td>
                <span class="editlinktip hasTip" title="<?php echo JText::_('COM_SEF_OPEN_PARAMETERS'); ?>">
                <a href="javascript:void(0);" onclick="return extParams('<?php echo $row->id;?>');">
                <?php echo $row->name; ?>
                </a>
                </span>
            </td>
            <!-- <td>
                <?php echo $row->option; ?>
            </td> -->
            <td>
                <?php
                if( is_null($row->extType) ) {
                    echo '-';
                }
                else {
                    if ($row->extType == 'Paid') {
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

                    if ($row->extType == 'Free') {
                        echo JText::_('COM_SEF_FREE');
                    } else {
                        echo JText::_('COM_SEF_PAID');
                    }
                }
                ?>
            </td>
            <td>
                <?php
                if( is_null($row->extType) ) {
                    echo '-';
                }
                else {
                    if( ($row->extType == 'Free') || ($row->params->get('downloadId', '') != '') ) {
                        $fn = 'getExt(\'' . $row->option . '\');';
                    }
                    else {
                        $fn = 'window.open(\'' . $row->extLink . '\');';
                    }
                    ?>
                    <input type="button" class="button hasTip" value="<?php echo JText::_('COM_SEF_GET_EXTENSION'); ?>" onclick="<?php echo $fn; ?>" title="<?php echo JText::_('COM_SEF_FROM_ARTIO_SERVER'); ?>" />
                    <?php
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
            <td>
                <span class="editlinktip hasTip" title="<?php echo JText::_('COM_SEF_OPEN_PARAMETERS'); ?>">
                    <a href="javascript:void(0);" onclick="return extParams('<?php echo $row->id;?>');">
                        <img src="<?php echo JURI::root(); ?>administrator/components/com_sef/assets/images/icon-16-config.png" border="0" alt="Open parameters" width="16" height="16" />
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

<input type="hidden" name="option" value="com_sef" />
<input type="hidden" name="task" value="" />
<input type="hidden" name="controller" value="" />
<input type="hidden" name="redirto" value="controller=extension" />
<input type="hidden" name="fromserver" value="0" />
<input type="hidden" name="cid[]" id="hiddenCid" value="" />
<input type="hidden" name="installtype" value="" />
<input type="hidden" name="extension" value="" />
<?php echo JHTML::_('form.token'); ?>
</form>
