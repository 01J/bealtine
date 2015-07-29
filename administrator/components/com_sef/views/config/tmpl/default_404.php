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

echo JHtml::_('tabs.panel', JText::_('COM_SEF_404_PAGE'), '404');
          $x = 0;
		  ?>
		  
		  <div class="fltlft" style="width: 50%">
		  <fieldset class="adminform">
		      <legend><?php echo JText::_('COM_SEF_404_PAGE'); ?></legend>
		      <table class="adminform table table-striped">
    	        <tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
    	            <td width="20"><?php echo $this->tooltip(JText::_('COM_SEF_TT_404_PAGE'), JText::_('COM_SEF_404_PAGE'));?></td>
    	            <td width="200"><?php echo JText::_('COM_SEF_404_PAGE');?></td>
    	            <td><?php echo $this->lists['page404'];?></td>
    	        </tr>
    	        <tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
    	            <td><?php echo $this->tooltip(JText::_('COM_SEF_TT_404_MESSAGE'), JText::_('COM_SEF_SHOW_404_MESSAGE'));?></td>
    	            <td><?php echo JText::_('COM_SEF_SHOW_404_MESSAGE');?></td>
    	            <td><?php echo $this->lists['msg404'];?></td>
    	        </tr>
    	        <tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
    	            <td><?php echo $this->tooltip(JText::_('COM_SEF_TT_404_RECORD_HITS'), JText::_('COM_SEF_RECORD_404_PAGE_HITS'));?></td>
    	            <td><?php echo JText::_('COM_SEF_RECORD_404_PAGE_HITS');?></td>
    	            <td><?php echo $this->lists['record404'];?></td>
    	        </tr>
		      </table>
		  </fieldset>
		  
          <?php $x = 0; ?>
		  <fieldset class="adminform">
		      <legend><?php echo JText::_('COM_SEF_DEFAULT_404_PAGE').' - '.JText::_('COM_SEF_ITEMID');?></legend>
		      <table class="adminform table table-striped">
    	        <tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
    	            <td width="20" valign="top"><?php echo $this->tooltip(JText::_('COM_SEF_TT_USE_404_ITEMID'), JText::_('COM_SEF_USE_ITEMID_FOR_DEFAULT_404_PAGE'));?></td>
    	            <td width="200" valign="top"><?php echo JText::_('COM_SEF_USE_ITEMID_FOR_DEFAULT_404_PAGE');?></td>
    	            <td><?php echo $this->lists['use404itemid'];?></td>
    	        </tr>
    	        <tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
    	            <td valign="top"><?php echo $this->tooltip(JText::_('COM_SEF_TT_SELECT_ITEMID'), JText::_('COM_SEF_SELECT_ITEMID'));?></td>
    	            <td valign="top"><?php echo JText::_('COM_SEF_SELECT_ITEMID');?></td>
    	            <td><?php echo $this->lists['itemid404'];?></td>
    	        </tr>
		      </table>
		  </fieldset>
		  </div>
		  
		  <div class="fltrt" style="width: 50%">
          <?php $x = 0; ?>
		  <fieldset class="adminform">
		      <legend><?php echo JText::_('COM_SEF_CUSTOM_404_PAGE');?></legend>
    		  <?php
    		  // parameters : hidden field, content, width, height, cols, rows
    		  jimport( 'joomla.html.editor' );
    		  $editor =& JFactory::getEditor();
    		  echo $editor->display('introtext', $this->lists['txt404'], '450', '250', '50', '11');
    		  ?>
		  </fieldset>
		  </div>
		  <div class="clr"></div>