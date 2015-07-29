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
<!--
function handleKeyDown(e)
{
    var code;
    code = e.keyCode;
    
    if (code == 13) {
        // Enter pressed
        document.adminForm.submit();
        return false;
    }
    
    return true;
}

function resetFilters()
{
    document.adminForm.filterMessage.value = '';
    document.adminForm.filterUrl.value = '';
    document.adminForm.filterPage.value = '';
    document.adminForm.comFilter.value = '';
    
    document.adminForm.submit();
}
-->
</script>

<form action="index.php" method="post" name="adminForm" id="adminForm">

<fieldset>
    <legend><?php echo JText::_('COM_SEF_FILTERS'); ?></legend>
<table>
    <tr>
        <td width="100%" valign="bottom">
        </td>
        <td nowrap="nowrap" align="right">
            <?php
            echo JText::_('COM_SEF_LOGS_MESSAGE') . ':';
            ?>
        </td>
        <td nowrap="nowrap" align="right">
            <?php
            echo JText::_('COM_SEF_LOGS_URL') . ':';
            ?>
        </td>
        <td nowrap="nowrap" align="right">
            <?php
            echo JText::_('COM_SEF_LOGS_PAGE') . ':';
            ?>
        </td>
        <td nowrap="nowrap" align="right">
            <?php
            echo JText::_('COM_SEF_COMPONENT') . ':';
            ?>
        </td>
        <td></td>
    </tr>
    <tr>
        <td></td>
        <td>
            <?php echo $this->lists['filterMessage']; ?>
        </td>
        <td>
            <?php echo $this->lists['filterUrl']; ?>
        </td>
        <td>
            <?php echo $this->lists['filterPage']; ?>
        </td>
        <td>
            <?php echo $this->lists['comList']; ?>
        </td>
        <td>
            <?php echo $this->lists['filterReset']; ?>
        </td>
    </tr>
</table>
</fieldset>

<table class="adminlist table table-striped">
<thead>
    <tr>
        <th width="5">
            <?php echo JText::_('COM_SEF_NUM'); ?>
        </th>
        <th class="title" width="150px">
            <?php
            	echo JHTML::_('grid.sort', 'COM_SEF_LOGS_TIME', 'time', $this->lists['filter_order_Dir'], $this->lists['filter_order'], null, 'desc');
            ?>
        </th>
        <th class="title">
            <?php
                echo JHTML::_('grid.sort', 'COM_SEF_LOGS_MESSAGE', 'message', $this->lists['filter_order_Dir'], $this->lists['filter_order']);
            ?>
        </th>
        <th class="title">
            <?php
                echo JHTML::_('grid.sort', 'COM_SEF_LOGS_URL', 'url', $this->lists['filter_order_Dir'], $this->lists['filter_order']);
            ?>
        </th>
        <th class="title">
            <?php
                echo JHTML::_('grid.sort', 'COM_SEF_LOGS_PAGE', 'page', $this->lists['filter_order_Dir'], $this->lists['filter_order']);
            ?>
        </th>
        <th class="title" width="150px">
            <?php
                echo JHTML::_('grid.sort', 'COM_SEF_COMPONENT', 'component', $this->lists['filter_order_Dir'], $this->lists['filter_order']);
            ?>
        </th>
    </tr>
</thead>
<tfoot>
    <tr>
        <td colspan="6">
            <?php echo $this->pagination->getListFooter(); ?>
        </td>
    </tr>
</tfoot>
<tbody>
    <?php
    $k = 0;
    //for ($i=0, $n=count( $rows ); $i < $n; $i++) {
    foreach (array_keys($this->items) as $i) {
        $row = &$this->items[$i];
        ?>
        <tr class="<?php echo 'row'. $k; ?>">
            <td align="center">
                <?php echo $this->pagination->getRowOffset($i); ?>
            </td>
            <td>
                <?php echo $row->time; ?>
            </td>
            <td>
                <?php echo $row->message; ?>
            </td>
            <td>
                <?php echo htmlspecialchars($row->url); ?>
            </td>
            <td>
                <?php echo htmlspecialchars($row->page); ?>
            </td>
            <td>
                <?php echo $row->component; ?>
            </td>
        </tr>
        <?php
        $k = 1 - $k;
    }
    ?>
</tbody>
</table>

<input type="hidden" name="option" value="com_sef" />
<input type="hidden" name="task" value="" />
<input type="hidden" name="controller" value="logger" />
<input type="hidden" name="filter_order" value="<?php echo $this->lists['filter_order']; ?>" />
<input type="hidden" name="filter_order_Dir" value="<?php echo $this->lists['filter_order_Dir']; ?>" />
</form>
