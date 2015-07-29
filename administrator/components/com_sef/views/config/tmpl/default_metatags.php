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

 echo JHtml::_('tabs.panel', JText::_('COM_SEF_TITLE_AND_META_TAGS'), 'metatags');
          $sefConfig =& SEFConfig::getConfig();
          $x = 0;
		  ?>
		  
		  <div class="fltlft" style="width: 50%">
		      <fieldset class="adminform">
		          <legend><?php echo JText::_('COM_SEF_TITLE_AND_META_TAGS_CONFIGURATION'); ?></legend>
    		      <table class="adminform table table-striped">
        	        <tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
        	            <td width="20"><?php echo $this->tooltip(JText::_('COM_SEF_TT_ENABLE_METADATA'), JText::_('COM_SEF_ENABLE_METADATA_GENERATION'));?></td>
        	            <td width="200"><?php echo JText::_('COM_SEF_ENABLE_METADATA_GENERATION');?>:</td>
        	            <td><?php echo $this->lists['enable_metadata'];?></td>
        	        </tr>
        	        <tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
                        <td><?php echo $this->tooltip(JText::_('COM_SEF_TT_METADATA_AUTO'), JText::_('COM_SEF_METADATA_AUTO_GENERATION'));?></td>
                        <td><?php echo JText::_('COM_SEF_METADATA_AUTO_GENERATION');?>:</td>
                        <td><?php echo $this->lists['metadata_auto'];?></td>
                    </tr>      
                    <tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
        	            <td><?php echo $this->tooltip(JText::_('COM_SEF_TT_PREFER_JOOMSEF_TITLE'), JText::_('COM_SEF_PREFER_JOOMSEF_TITLES'));?></td>
        	            <td><?php echo JText::_('COM_SEF_PREFER_JOOMSEF_TITLES');?>:</td>
        	            <td><?php echo $this->lists['prefer_joomsef_title'];?></td>
        	        </tr>
        	        <tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
        	            <td><?php echo $this->tooltip(JText::_('COM_SEF_TT_USE_SITENAME'), JText::_('COM_SEF_USE_SITENAME_IN_PAGE_TITLES'));?></td>
        	            <td><?php echo JText::_('COM_SEF_USE_SITENAME_IN_PAGE_TITLES');?>:</td>
        	            <td><?php echo $this->lists['use_sitename'];?></td>
        	        </tr>
        	        <tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
        	            <td><?php echo $this->tooltip(JText::_('COM_SEF_TT_SITENAME_SEPARATOR'), JText::_('COM_SEF_SITENAME_SEPARATOR'));?></td>
        	            <td><?php echo JText::_('COM_SEF_SITENAME_SEPARATOR');?>:</td>
        	            <td><?php echo $this->lists['sitename_sep'];?></td>
        	        </tr>
        	        <tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
        	            <td><?php echo $this->tooltip(JText::_('COM_SEF_TT_REWRITE_KEYWORDS'), JText::_('COM_SEF_META_KEYWORDS_PREFERENCE'));?></td>
        	            <td><?php echo JText::_('COM_SEF_META_KEYWORDS_PREFERENCE');?>:</td>
        	            <td><?php echo $this->lists['rewrite_keywords'];?></td>
        	        </tr>
        	        <tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
        	            <td><?php echo $this->tooltip(JText::_('COM_SEF_TT_REWRITE_DESC'), JText::_('COM_SEF_META_DESCRIPTION_PREFERENCE'));?></td>
        	            <td><?php echo JText::_('COM_SEF_META_DESCRIPTION_PREFERENCE');?>:</td>
        	            <td><?php echo $this->lists['rewrite_description'];?></td>
        	        </tr>
        	        <tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
        	            <td><?php echo $this->tooltip(JText::_('COM_SEF_TT_NO_SITENAME_DUPLICITY'), JText::_('COM_SEF_PREVENT_SITENAME_DUPLICITY'));?></td>
        	            <td><?php echo JText::_('COM_SEF_PREVENT_SITENAME_DUPLICITY');?>:</td>
        	            <td><?php echo $this->lists['prevent_dupl'];?></td>
        	        </tr>
        	      </table>
		      </fieldset>
		  </div>
		  <div class="fltrt" style="width: 50%">
              <?php $x = 0; ?>
		      <fieldset class="adminform">
		      <legend><?php echo JText::_('COM_SEF_GLOBAL_META_TAGS_CONFIGURATION'); ?></legend>
		      <fieldset class="adminform">
		          <legend><?php echo JText::_('COM_SEF_STANDARD').' '.JText::_('COM_SEF_META_TAGS'); ?></legend>
    		      <table class="adminform table table-striped">
        	        <tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
        	            <td width="20"><?php echo $this->tooltip(JText::_('COM_SEF_TT_TAG_GENERATOR'), JText::_('COM_SEF_GENERATOR_TAG'));?></td>
        	            <td width="200"><?php echo JText::_('COM_SEF_GENERATOR_TAG');?>:</td>
        	            <td><?php echo $this->lists['tag_generator'];?></td>
        	        </tr>
        	        <tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
        	            <td><?php echo $this->tooltip(JText::_('COM_SEF_TT_TAG_GOOGLE_KEY'), JText::_('COM_SEF_GOOGLE_KEY'));?></td>
        	            <td><?php echo JText::_('COM_SEF_GOOGLE_KEY');?>:</td>
        	            <td><?php echo $this->lists['tag_googlekey'];?></td>
        	        </tr>
        	        <tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
        	            <td><?php echo $this->tooltip(JText::_('COM_SEF_TT_TAG_LIVE_KEY'), JText::_('COM_SEF_LIVECOM_KEY'));?></td>
        	            <td><?php echo JText::_('COM_SEF_LIVECOM_KEY');?>:</td>
        	            <td><?php echo $this->lists['tag_livekey'];?></td>
        	        </tr>
        	        <tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
        	            <td><?php echo $this->tooltip(JText::_('COM_SEF_TT_TAG_YAHOO_KEY'), JText::_('COM_SEF_YAHOO_KEY'));?></td>
        	            <td><?php echo JText::_('COM_SEF_YAHOO_KEY');?>:</td>
        	            <td><?php echo $this->lists['tag_yahookey'];?></td>
        	        </tr>
        	      </table>
		      </fieldset>
              
              <?php $x = 0; ?>
		      <fieldset class="adminform">
		          <legend><?php echo JText::_('COM_SEF_CUSTOM').' '.JText::_('COM_SEF_META_TAGS'); ?></legend>
		          <table class="adminform table table-striped" id="tblMetatags">
		              <tr>
		                  <th width="200"><?php echo JText::_('COM_SEF_NAME'); ?></th>
		                  <th colspan="2"><?php echo JText::_('COM_SEF_CONTENT'); ?></th>
		              </tr>
		              <?php
		              // Custom meta tags
		              if (is_array($sefConfig->customMetaTags)) {
		                  foreach($sefConfig->customMetaTags as $name => $content) {
		                      ?>
		                      <tr>
		                          <td width="200"><input type="text" name="metanames[]" size="40" value="<?php echo $name; ?>" /></td>
		                          <td width="250"><input type="text" name="metacontents[]" size="60" value="<?php echo $content; ?>" /></td>
		                          <td><input type="button" value="<?php echo JText::_('COM_SEF_REMOVE_META_TAG'); ?>" onclick="removeMetaTag(this);" /></td>
		                      </tr>
		                      <?php
		                  }
		              }
		              ?>
		          </table>
		          <input type="button" value="<?php echo JText::_('COM_SEF_ADD_META_TAG'); ?>" onclick="addMetaTag();" />
		      </fieldset>
		      </fieldset>
		  </div>
		  <div style="clear: both;"></div>