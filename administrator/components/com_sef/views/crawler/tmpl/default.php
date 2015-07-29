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

$opts = array();
for ($i = 1; $i <= 10; $i++) {
    $opts[] = JHTML::_('select.option', $i, $i);
}
$maxLevelList = JHTML::_('select.genericlist', $opts, 'crawlerMaxLevel', 'class="classic" size="1"', 'value', 'text', 5);
?>

<form action="index.php" method="post" name="adminForm" id="adminForm">
<input type="hidden" name="option" value="com_sef" />
<input type="hidden" name="task" value="" />
</form>

<div class="sef-width-100">
<?php $this->showInfoText('COM_SEF_INFOTEXT_CRAWL', true); ?>

<fieldset class="adminform">
    <legend><?php echo JText::_('COM_SEF_CRAWLER_CONFIG'); ?></legend>
    
    <table class="admintable">
        <tr>
            <td class="key"><?php echo JHTML::_('tooltip', JText::_('COM_SEF_CRAWLER_ROOTURL_TT'), JText::_('COM_SEF_CRAWLER_ROOTURL'), '', JText::_('COM_SEF_CRAWLER_ROOTURL')); ?>:</td>
            <td><?php echo JURI::root(); ?><input type="text" id="crawlerRootUrl" name="crawlerRootUrl" value="" class="classic" size="40" /></td>
        </tr>
        <tr>
            <td class="key"><?php echo JHTML::_('tooltip', JText::_('COM_SEF_CRAWLER_MAXLEVEL_TT'), JText::_('COM_SEF_CRAWLER_MAXLEVEL'), '', JText::_('COM_SEF_CRAWLER_MAXLEVEL')); ?>:</td>
            <td><?php echo $maxLevelList; ?></td>
        </tr>
    </table>
</fieldset>

<fieldset class="adminform">
    <legend><?php echo JText::_('COM_SEF_CRAWLER_STATUS_TITLE'); ?></legend>
    
    <table class="admintable">
        <tr>
            <td width="140" class="key"><?php echo JText::_('COM_SEF_CRAWLER_STATUS'); ?>:</td>
            <td>
                <span id="crawlerRunningValue"><?php echo JText::_('COM_SEF_CRAWLER_NOT_RUNNING'); ?></span>
                <img id="crawlerRunningImg" src="components/com_sef/assets/images/ajax-loader-small.gif" style="display: none; float: right; margin: 0px 0px 0px 5px;" />
            </td>
        </tr>
        <tr>
            <td>&nbsp;</td>
            <td><span id="crawlerResponseTime">&nbsp;</span></td>
        </tr>
        <tr>
            <td class="key"><?php echo JText::_('COM_SEF_CRAWLER_CURRENT_LEVEL'); ?>:</td>
            <td><span id="crawlerLevelValue">0</span></td>
        </tr>
        <tr>
            <td class="key"><?php echo JText::_('COM_SEF_CRAWLER_CRAWLED_URLS'); ?>:</td>
            <td><span id="crawlerCrawledValue">0</span> / <span id="crawlerUrlsValue">0</span></td>
        </tr>
        <tr>
            <td colspan="2">&nbsp;</td>
        </tr>
        <tr>
            <td colspan="2">
                <input type="button" id="crawlerContinueButton" name="crawlerContinueButton" class="classic btn btn-primary" onclick="jsCrawlerRecoverCrawl();" value="<?php echo JText::_('COM_SEF_CRAWLER_CONTINUE'); ?>" style="display: none;" />
                <input type="button" id="crawlerButton" name="crawlerButton" class="classic btn btn-primary" onclick="jsCrawlerButtonClicked();" value="<?php echo JText::_('COM_SEF_CRAWLER_START'); ?>" />
            </td>
        </tr>
    </table>
</fieldset>
</div>