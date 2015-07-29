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
<script type="text/javascript">
/* <![CDATA[ */
window.addEvent('domready', function() { sefStartUpdate(); });

var sefAjaxUpdate = null;
var sefTimer = null;

var urlsTotal = <?php echo $this->totalUrls; ?>;
var urlsLeft = <?php echo $this->totalUrls; ?>;
var urlsUpdated = 0;

function sefStartUpdate()
{   
    sefUpdate();
}

function sefUpdate() {
    new Request.JSON({
        'url': '<?php echo JURI::root(); ?>index.php?option=com_sef&task=updateMetaNext&format=json',
        'method': 'post',
        'onSuccess': function(json,text) {
            if (json.type == 'completed') {
                sefUpdateProgress(json);
                sefUpdateCompleted();
            } else if (json.type == 'updatestep') { 
                sefUpdateProgress(json);
                sefUpdate();
            } else if (json.type == 'error'){
                showError(json.msg);
            }
        }
    }).send();
}

function showError(msg)
{
    document.getElementById('urls_errors').innerHTML = msg;
    document.getElementById('urls_errors_table').style.display = 'block';
    document.getElementById('urls_table').style.display = 'none';
}

function sefUpdateProgress(response)
{
    var updated = response.updated;

    urlsUpdated += updated;
    urlsLeft -= updated;
    
    document.getElementById('urls_updated').innerHTML = urlsUpdated;
    document.getElementById('urls_left').innerHTML = urlsLeft;
}

function sefUpdateCompleted()
{
    document.getElementById('urls_message').innerHTML = '<?php echo JText::_('COM_SEF_METATAGS_UPDATE_COMPLETED'); ?>';
    document.getElementById('update_finished').disabled = false;
}
/* ]]> */
</script>

<form action="index.php" method="post" name="adminForm">
<fieldset class="adminform">
<legend><?php echo JText::_('COM_SEF_METATAGS_UPDATE_TITLE'); ?></legend>
<div id="urls_table">
<table class="adminform table">
<tr>
    <th colspan="2" id="urls_message">
        <?php echo JText::_('COM_SEF_METATAGS_UPDATE_PROGRESS'); ?>
    </th>
</tr>
<tr>
    <td width="100"><?php echo JText::_('COM_SEF_METATAGS_UPDATE_UPDATED'); ?>:</td>
    <td id="urls_updated">0</td>
</tr>
<tr>
    <td width="100"><?php echo JText::_('COM_SEF_METATAGS_UPDATE_LEFT'); ?>:</td>
    <td id="urls_left"><?php echo $this->totalUrls; ?></td>
</tr>
<tr>
    <td width="100"><?php echo JText::_('COM_SEF_METATAGS_UPDATE_TOTAL'); ?>:</td>
    <td id="urls_total"><?php echo $this->totalUrls; ?></td>
</tr>
<tr>
    <td colspan="2"><input type="submit" class="btn btn-primary" value="Finish" disabled="disabled" id="update_finished" /></td>
</tr>
</table>
</div>

<div id="urls_errors_table" style="display: none">
<table class="adminform table">
<tr>
    <th><?php echo JText::_('COM_SEF_METATAGS_UPDATE_ERROR'); ?></th>
</tr>
<tr>
    <td id="urls_errors">&nbsp;</td>
</tr>
</table>
</div>

</fieldset>

<input type="hidden" name="option" value="com_sef" />
<input type="hidden" name="controller" value="<?php echo $this->controllerVar; ?>" />
<input type="hidden" name="task" value="" />
</form>
