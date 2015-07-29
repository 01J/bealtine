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

echo JHtml::_('tabs.panel', JText::_('COM_SEF_REGISTRATION'), 'registration');
		  $x = 0;
		  ?>
		  
		  <fieldset class="adminform">
		      <legend><?php echo JText::_('COM_SEF_ARTIO_JOOMSEF_REGISTRATION');?></legend>
		      <p><?php echo JText::_('COM_SEF_INFO_REGISTRATION'); ?></p>
		      <table class="adminform table table-striped">
    	        <tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
    	            <td width="20"><?php echo $this->tooltip(JText::_('COM_SEF_TT_ARTIO_DOWNLOAD_ID'), JText::_('COM_SEF_JOOMSEF_DOWNLOAD_ID'));?></td>
    	            <td width="200"><?php echo JText::_('COM_SEF_JOOMSEF_DOWNLOAD_ID');?>:</td>
    	            <td><?php echo $this->lists['artioDownloadId'];?></td>
    	        </tr>
		      </table>
		  </fieldset>

		  <?php $x = 0; ?>
		  <fieldset class="adminform">
		      <legend><?php echo JText::_('COM_SEF_ARTIO_USER_ACCOUNT'); ?></legend>
		      <p><?php echo JText::_('COM_SEF_INFO_ACCOUNT'); ?></p>
		      <table class="adminform table table-striped">
    	        <tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
    	            <td width="20"><?php echo $this->tooltip(JText::_('COM_SEF_TT_ARTIO_USERNAME'), JText::_('COM_SEF_ARTIO_SITE_USERNAME'));?></td>
    	            <td width="200"><?php echo JText::_('COM_SEF_ARTIO_SITE_USERNAME');?>:</td>
    	            <td><?php echo $this->lists['artioUserName'];?></td>
    	        </tr>
    	        <tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
    	            <td><?php echo $this->tooltip(JText::_('COM_SEF_TT_ARTIO_PASSWORD'), JText::_('COM_SEF_ARTIO_SITE_PASSWORD'));?></td>
    	            <td><?php echo JText::_('COM_SEF_ARTIO_SITE_PASSWORD');?>:</td>
    	            <td><?php echo $this->lists['artioPassword'];?></td>
    	        </tr>
		      </table>
		  </fieldset>
		  
          <?php $x = 0; ?>
		  <fieldset class="adminform">
		      <legend><?php echo JText::_('COM_SEF_ARTIO_NEWS'); ?></legend>
		      <table class="adminform table table-striped">
                <tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
                    <td width="20"><?php echo $this->tooltip(JText::_('COM_SEF_TT_ARTIO_FEED'), JText::_('COM_SEF_DISPLAY_ARTIO_NEWSFEED'));?></td>
                    <td width="200"><?php echo JText::_('COM_SEF_DISPLAY_ARTIO_NEWSFEED');?>:</td>
                    <td><?php echo $this->lists['artioFeedDisplay'];?></td>
    	        </tr>
    	        <tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
    	            <td width="20"><?php echo $this->tooltip(JText::_('COM_SEF_TT_VERSION_CHECKER'),JText::_('COM_SEF_CHECK_FOR_NEWER_VERSIONS'));?></td>
    	            <td width="200"><?php echo JText::_('COM_SEF_CHECK_FOR_NEWER_VERSIONS');?>:</td>
    	            <td><?php echo $this->lists['versionChecker'];?></td>
    	        </tr>
		      </table>
		  </fieldset>