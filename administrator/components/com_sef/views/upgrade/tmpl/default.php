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

$sefConfig =& SEFConfig::getConfig();

if( (trim($sefConfig->artioDownloadId) != '') && (is_null($this->regInfo) || ($this->regInfo->code != 10)) ) {
    $needConfirm = true;
}
else {
    $needConfirm = false;
}

if( (trim($sefConfig->artioDownloadId) == '') || is_null($this->regInfo) || ($this->regInfo->code != 10) ) {
    $downloadPaid = false;
}
else {
    $downloadPaid = true;
}
?>

<script language="javascript" type="text/javascript">
<!--
	function submitbutton3(pressbutton) {
		var form = document.adminForm;

		var sendOk = true;
		
		<?php
		if( $needConfirm ) {
		    ?>
		    sendOk = confirm('<?php echo JText::_('COM_SEF_UPGRADE_NON_PAID'); ?>');
		    <?php
		}
		?>
		if( sendOk ) {
    		form.fromserver.value = '1';
    		form.submit();
		}
	}
	
	function submitbuttonext(extension) {
		var form = document.adminForm;

		form.fromserver.value = '1';
		form.ext.value = extension;
		form.submit();	    
	}

//-->
</script>

<fieldset class="adminform">
<legend><?php echo JText::_('COM_SEF_JOOMSEF'); ?></legend>
<table class="adminform table table-striped">
<tr>
    <th colspan="2"><?php echo JText::_('COM_SEF_VERSION_INFO'); ?></th>
</tr>
<tr>
    <td width="20%"><?php echo JText::_('COM_SEF_INSTALLED_VERSION').':'; ?></td>
    <td><?php echo $this->oldVer; ?></td>
</tr>
<tr>
    <td><?php echo JText::_('COM_SEF_NEWEST_VERSION').':'; ?></td>
    <td><?php echo $this->newVer; ?></td>
</tr>
</table>

<?php
if( trim($sefConfig->artioDownloadId) != '' ) {
    ?>
    <table class="adminform">
    <tr>
        <th colspan="2"><?php echo JText::_('COM_SEF_REGISTRATION_INFO'); ?></th>
    </tr>
    <?php
    if( is_null($this->regInfo) ) {
        ?>
        <tr>
            <td colspan="2"><?php echo JText::_('COM_SEF_ERROR_REGISTRATION_INFO'); ?></td>
        </tr>
        <?php
    }
    else if( $this->regInfo->code == 90 ) {
        ?>
        <tr>
            <td colspan="2"><?php echo JText::sprintf('COM_SEF_ERROR_DOWNLOAD_ID_NOT_FOUND',trim($sefConfig->artioDownloadId)); ?></td>
        </tr>
        <?php
    }
    else {
        $regTo = $this->regInfo->name;
        if( !empty($this->regInfo->company) ) {
            $regTo .= ', ' . $this->regInfo->company;
        }
        ?>
        <tr>
            <td width="20%""><?php echo JText::_('COM_SEF_REGISTERED_TO'); ?>:</td>
            <td><?php echo $regTo; ?></td>
        </tr>
        <?php
        if ($this->regInfo->code == 10 || $this->regInfo->code == 30) {
            $dateText = JText::_('COM_SEF_FREE_UPGRADES_AVAILABLE_UNTIL');
        }
        elseif ($this->regInfo->code == 20) {
            $dateText = JText::_('COM_SEF_FREE_UPGRADES_EXPIRED');
        }
        ?>
        <tr>
            <td><?php echo $dateText; ?>:</td>
            <td><?php echo $this->regInfo->date; ?></td>
        </tr>
        <?php
    }
    ?>
    </table>
    <?php
} // Download ID set
?>

<form enctype="multipart/form-data" action="index.php" method="post" name="adminForm">
<?php
$available = false;
if ((strnatcasecmp($this->newVer, $this->oldVer) > 0) ||
(strnatcasecmp($this->newVer, substr($this->oldVer, 0, strpos($this->oldVer, '-'))) == 0) ||
($this->newVer == "?.?.?") )
{
    $available = true;

    if (!$this->isPaidVersion && $downloadPaid) {
        $btnText = JText::_('COM_SEF_ONLINE_UPGRADE_TO_PAID_VERSION');
    } else {
        $btnText = JText::_('COM_SEF_UPGRADE_FROM_ARTIO_SERVER');
    }
}
elseif (($this->newVer == $this->oldVer)) {
//else {	
    $available = true;
    if (!$this->isPaidVersion && $downloadPaid) {
    	$btnText = JText::_('COM_SEF_ONLINE_MIGRATE_TO_PAID_VERSION');
    } else {
    	$btnText = JText::_('COM_SEF_REINSTALL_FROM_ARTIO_SERVER');
    }
}

if( $available )
{
?>
    <table class="adminform table table-striped">
        <tr>
            <th><?php echo $btnText; ?></th>
        </tr>
        <tr>
            <td>
                   <?php
                   if( $this->newVer == '?.?.?' ) {
                       echo JText::_('COM_SEF_SERVER_NOT_AVAILABLE');
                   }
                   else
                   {
                       ?>
                       <input class="button btn" type="button" value="<?php echo $btnText; ?>" onclick="submitbutton3()" />
                       <?php
                   }
                   ?>
            </td>
        </tr>
    </table>
<?php
} else {
?>
    <table class="adminform table table-striped">
        <tr>
            <th><?php echo JText::_('COM_SEF_YOUR_JOOMSEF_IS_UP_TO_DATE'); ?></th>
        </tr>
    </table>
<?php } ?>

<table class="adminform table table-striped">
<tr>
    <th colspan="2"><?php echo JText::_( 'COM_SEF_UPLOAD_PACKAGE' ); ?></th>
</tr>
<tr>
    <td width="120">
        <label for="install_package"><?php echo JText::_( 'COM_SEF_PACKAGE_FILE' ); ?>:</label>
    </td>
    <td>
        <input class="input_box" id="install_package" name="install_package" type="file" size="57" />
        <input class="button btn" type="submit" value="<?php echo JText::_( 'COM_SEF_UPLOAD_FILE' ); ?> &amp; <?php echo JText::_( 'COM_SEF_INSTALL' ); ?>" />
    </td>
</tr>
</table>
</fieldset>

<fieldset class="adminform">
<legend><?php echo JText::_('COM_SEF_SEF_EXTENSIONS'); ?></legend>
<table class="adminform table table-striped">
    <tr>
        <th><?php echo JText::_('COM_SEF_SEF_EXTENSION'); ?></th>
        <th><?php echo JText::_('COM_SEF_INSTALLED_VERSION'); ?></th>
        <th><?php echo JText::_('COM_SEF_NEWEST_VERSION'); ?></th>
        <th><?php echo JText::_('COM_SEF_TYPE'); ?></th>
        <th><?php echo JText::_('COM_SEF_UPGRADE'); ?></th>
    </tr>
    <?php
    $k = 0;
    if( (count($this->extensions) > 0) ) {
        foreach(array_keys($this->extensions) as $i) {
            $row = &$this->extensions[$i];
        ?>
        <tr class="<?php echo 'row'.$k; ?>">
            <td><?php echo $row->name; ?></td>
            <td>
                <?php
                    $color = version_compare($row->old, $row->new, '>=') ? 'green' : 'red';
                    echo '<span style="color: '.$color.'">'.$row->old.'</span>';
                ?>
            </td>
            <td><?php echo $row->new; ?></td>
            <td>
                <?php
                    if ($row->type == 'Paid') {
                        $img = 'icon-16-key';
                        $ttl = JText::_('COM_SEF_DOWNLOAD_ID_SET');
                        $txt = JText::_('COM_SEF_CLICK_TO_CHANGE');
                        if ($row->params->get('downloadId', '') == '') {
                            $img .= '_bw';
                            $ttl = JText::_('COM_SEF_DOWNLOAD_ID_NOT_SET');
                            $txt = JText::_('COM_SEF_CLICK_TO_SET');
                        }
                        
                        $href = 'index.php?option=com_sef&amp;controller=extension&amp;cid[]='.$row->option.'&amp;task=editId&amp;tmpl=component';
                        echo '<span class="editlinktip hasTip" title="'.$ttl.'::'.$txt.'">';
                        echo '<a class="modal" href="'.$href.'" rel="{handler: \'iframe\', size: {x: 570, y: 150}}"><img src="components/com_sef/assets/images/'.$img.'.png" /></a>';
                        echo '</span>&nbsp;';
                    }
                
                    echo JText::_($row->type);
                ?>
            </td>
            <td>
            <?php
            if( (strnatcasecmp($row->new, $row->old) > 0) ||
                (strnatcasecmp($row->new, substr($row->old, 0, strpos($row->old, '-'))) == 0) )
            {
                ?>
                <input class="button btn btn-small" type="button" value="<?php echo JText::_('COM_SEF_UPGRADE'); ?>" onclick="submitbuttonext('<?php echo $i; ?>')" />
                <?php
            } else {
                echo JText::_('COM_SEF_NOT_AVAILABLE');
            }
            ?>
            </td>
        </tr>
        <?php
        $k = 1 - $k;
        }
    }
    ?>
</table>
</fieldset>

<input type="hidden" name="option" value="com_sef" />
<input type="hidden" name="task" value="doUpgrade" />
<input type="hidden" name="controller" value="" />
<input type="hidden" name="fromserver" value="0" />
<input type="hidden" name="ext" value="" />
<?php echo JHTML::_('form.token'); ?>
</form>
