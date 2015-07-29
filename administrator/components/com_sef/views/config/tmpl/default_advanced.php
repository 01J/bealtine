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

$sefConfig=SEFConfig::getConfig();
if ($sefConfig->professionalMode) {
		  echo JHtml::_('tabs.panel', JText::_('COM_SEF_ADVANCED'), 'advanced');
          $x = 0;
		  ?>
		  
          <div class="fltlft" style="width: 50%">
    		  <fieldset class="adminform">
    		      <legend><?php echo JText::_('COM_SEF_ADVANCED_CONFIGURATION');?></legend>
    		      <table class="adminform table table-striped">
        	        <tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
        	            <td width="20" valign="top"><?php echo $this->tooltip(JText::_('COM_SEF_TT_ALLOW_UTF'), JText::_('COM_SEF_ALLOW_UTF'));?></td>
        	            <td width="200" valign="top"><?php echo JText::_('COM_SEF_ALLOW_UTF');?></td>
        	            <td><?php echo $this->lists['allowUTF'];?></td>
        	        </tr>
        	        <tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
        	            <td valign="top"><?php echo $this->tooltip(JText::_('COM_SEF_TT_REPLACEMENTS'), JText::_('COM_SEF_REPLACEMENTS'));?></td>
        	            <td valign="top"><?php echo JText::_('COM_SEF_REPLACEMENTS');?></td>
        	            <td><textarea name="replacements" cols="40" rows="5"><?php echo $sefConfig->replacements;?></textarea></td>
        	        </tr>
        	        <tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
        	            <td><?php echo $this->tooltip(JText::_('COM_SEF_TT_TRANSIT_SLASH'), JText::_('COM_SEF_BE_TOLERANT_TO_TRAILING_SLASH'));?></td>
        	            <td><?php echo JText::_('COM_SEF_BE_TOLERANT_TO_TRAILING_SLASH');?></td>
        	            <td><?php echo $this->lists['transitSlash'];?></td>
        	        </tr>
        	        <tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
        	        	<td><?php echo $this->tooltip(JText::_('COM_SEF_TT_REDIRECT_SLASH'),JText::_('COM_SEF_REDIRECT_SLASH')); ?></td>
        	        	<td><?php echo JText::_('COM_SEF_REDIRECT_SLASH'); ?></td>
        	        	<td><?php echo $this->lists['redirectSlash']; ?></td>
        	        </tr>
        	        <tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
        	            <td><?php echo $this->tooltip(JText::_('COM_SEF_TT_PARSE_JOOMLA_SEO'), JText::_('COM_SEF_PARSE_JOOMLA_SEO_LINKS'));?></td>
        	            <td><?php echo JText::_('COM_SEF_PARSE_JOOMLA_SEO_LINKS');?></td>
        	            <td><?php echo $this->lists['parseJoomlaSEO'];?></td>
        	        </tr>
        	        <tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
        	            <td><?php echo $this->tooltip(JText::_('COM_SEF_TT_CHECK_BASE_HREF'), JText::_('COM_SEF_SET_PAGE_BASE_HREF_VALUE'));?></td>
        	            <td><?php echo JText::_('COM_SEF_SET_PAGE_BASE_HREF_VALUE');?>:</td>
        	            <td><?php echo $this->lists['check_base_href']; ?></td>
        	        </tr>
        	        <tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
        	            <td><?php echo $this->tooltip(JText::_('COM_SEF_TT_FIX_INDEX_PHP'), JText::_('COM_SEF_FIX_INDEXPHP_LINKS'));?></td>
        	            <td><?php echo JText::_('COM_SEF_FIX_INDEXPHP_LINKS');?>:</td>
        	            <td><?php echo $this->lists['fixIndexPhp']; ?></td>
        	        </tr>
        	        <tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
        	            <td><?php echo $this->tooltip(JText::_('COM_SEF_TT_FIX_DOCUMENT_FORMAT'), JText::_('COM_SEF_FIX_DOCUMENT_FORMAT'));?></td>
        	            <td><?php echo JText::_('COM_SEF_FIX_DOCUMENT_FORMAT');?>:</td>
        	            <td><?php echo $this->lists['fixDocumentFormat']; ?></td>
        	        </tr>
        	        <tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
        	            <td><?php echo $this->tooltip(JText::_('COM_SEF_TT_INDEX_PHP_CURRENT_MENU'), JText::_('COM_SEF_INDEX_PHP_CURRENT_MENU'));?></td>
        	            <td><?php echo JText::_('COM_SEF_INDEX_PHP_CURRENT_MENU');?>:</td>
        	            <td><?php echo $this->lists['indexPhpCurrentMenu']; ?></td>
        	        </tr>
        	        <tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
        	            <td><?php echo $this->tooltip(JText::_('COM_SEF_TT_HOME_PAGE_HITS'), JText::_('COM_SEF_HOME_PAGE_HITS'));?></td>
        	            <td><?php echo JText::_('COM_SEF_HOME_PAGE_HITS');?>:</td>
        	            <td><?php echo $this->lists['homePageHits']; ?></td>
        	        </tr>
        	        </table>
    		    </fieldset>
                
                <?php $x = 0; ?>
                <fieldset class="adminform">
                    <legend><?php echo JText::_('COM_SEF_WORKING_WITH_URLS');?></legend>
                    <table class="adminform table table-striped">
            	        <tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
            	            <td width="20"><?php echo $this->tooltip(JText::_('COM_SEF_TT_USE_MOVED_ASK'), JText::_('COM_SEF_ASK_BEFORE_SAVING_URL_TO_MOVED_PERMANENTLY_TABLE'));?></td>
            	            <td width="200"><?php echo JText::_('COM_SEF_ASK_BEFORE_SAVING_URL_TO_MOVED_PERMANENTLY_TABLE');?></td>
            	            <td><?php echo $this->lists['useMovedAsk'];?></td>
            	        </tr>
            	        <tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
            	        	<td><?php echo $this->tooltip(JText::_('COM_SEF_TT_AUTOLOCK_URLS'),JText::_('COM_SEF_AUTOLOCK_URLS')); ?></td>
            	        	<td><?php echo Jtext::_('COM_SEF_AUTOLOCK_URLS'); ?>:</td>
            	        	<td><?php echo $this->lists['autolock_urls']; ?></td>
            	        </tr>
            	        <tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
            	        	<td><?php echo $this->tooltip(JText::_('COM_SEF_TT_AUTO_UPDATE_URLS'),JText::_('COM_SEF_AUTO_UPDATE_URLS')); ?></td>
            	        	<td><?php echo Jtext::_('COM_SEF_AUTO_UPDATE_URLS'); ?>:</td>
            	        	<td><?php echo $this->lists['update_urls']; ?></td>
            	        </tr>
                    </table>
                </fieldset>
                
                <?php $x = 0; ?>
    			<fieldset class="adminform">
    		      <legend><?php echo JText::_('COM_SEF_URL_DEBUGGING');?></legend>
    		      <table class="adminform table table-striped">    	        
        	        <tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
        	            <td width="20" valign="top"><?php echo $this->tooltip(JText::_('COM_SEF_TT_LOG_ERRORS'), JText::_('COM_SEF_ENABLE_LOG_ERRORS'));?></td>
        	            <td width="200" valign="top"><?php echo JText::_('COM_SEF_ENABLE_LOG_ERRORS');?></td>
        	            <td><?php echo $this->lists['logErrors'];?></td>
        	        </tr>
        	        <tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
        	            <td valign="top"><?php echo $this->tooltip(JText::_('COM_SEF_TT_TRACE'), JText::_('COM_SEF_TRACE_URL_SOURCE'));?></td>
        	            <td valign="top"><?php echo JText::_('COM_SEF_ENABLE_URL_SOURCE_TRACING');?></td>
        	            <td><?php echo $this->lists['trace'];?></td>
        	        </tr>
        	        <tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
        	            <td><?php echo $this->tooltip(JText::_('COM_SEF_TT_TRACE_DEPTH'), JText::_('COM_SEF_TRACING_DEPTH'));?></td>
        	            <td><?php echo JText::_('COM_SEF_TRACING_DEPTH');?>:</td>
        	            <td><?php echo $this->lists['traceLevel'];?></td>
        	        </tr>
    		      </table>
	           </fieldset>
            </div>
            
            <div class="fltlft" style="width: 50%">
                <?php $x = 0; ?>
                <fieldset class="adminform">
                    <legend><?php echo JText::_('COM_SEF_NONSEF_URLS_AND_VARIABLES');?></legend>
                    <table class="adminform table table-striped">
            	        <tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
            	            <td width="20"><?php echo $this->tooltip(JText::_('COM_SEF_TT_NONSEF_REDIRECT'), JText::_('COM_SEF_REDIRECT_NONSEF_URLS_TO_SEF'));?></td>
            	            <td width="200"><?php echo JText::_('COM_SEF_REDIRECT_NONSEF_URLS_TO_SEF');?></td>
            	            <td><?php echo $this->lists['nonSefRedirect'];?></td>
            	        </tr>
            	        <tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
            	            <td><?php echo $this->tooltip(JText::_('COM_SEF_TT_APPEND_NONSEF'), JText::_('COM_SEF_APPEND_NONSEF'));?></td>
            	            <td><?php echo JText::_('COM_SEF_APPEND_NONSEF');?></td>
            	            <td><?php echo $this->lists['appendNonSef'];?></td>
            	        </tr>
            	        <tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
            	            <td><?php echo $this->tooltip(JText::_('COM_SEF_TT_PREVENT_NONSEF_OVERWRITE'), JText::_('COM_SEF_PREVENT_NONSEF_OVERWRITE'));?></td>
            	            <td><?php echo JText::_('COM_SEF_PREVENT_NONSEF_OVERWRITE');?>:</td>
            	            <td><?php echo $this->lists['preventNonSefOverwrite'];?></td>
            	        </tr>
            	        <tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
            	            <td><?php echo $this->tooltip(JText::_('COM_SEF_TT_CUSTOM_NONSEF'), JText::_('COM_SEF_CUSTOM_NONSEF'));?></td>
            	            <td><?php echo JText::_('COM_SEF_CUSTOM_NONSEF');?>:</td>
            	            <td><input type="text" name="customNonSef" value="<?php echo $sefConfig->customNonSef; ?>" size="40"></td>
            	        </tr>
            	        <tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
            	            <td><?php echo $this->tooltip(JText::_('COM_SEF_TT_AUTO_CANONICAL'), JText::_('COM_SEF_AUTO_CANONICAL'));?></td>
            	            <td><?php echo JText::_('COM_SEF_AUTO_CANONICAL');?>:</td>
            	            <td><?php echo $this->lists['autoCanonical']; ?></td>
            	        </tr>
            	        <tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
            	            <td><?php echo $this->tooltip(JText::_('COM_SEF_TT_SEF_COMPONENT_URLS'), JText::_('COM_SEF_SEF_COMPONENT_TEMPLATE'));?></td>
            	            <td><?php echo JText::_('COM_SEF_SEF_COMPONENT_TEMPLATE');?>:</td>
            	            <td><?php echo $this->lists['sefComponentUrls']; ?></td>
            	        </tr>
            	        <tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
            	            <td><?php echo $this->tooltip(JText::_('COM_SEF_TT_NONSEF_QUERY_VARIABLES'), JText::_('COM_SEF_NONSEF_QUERY_VARIABLES'));?></td>
            	            <td><?php echo JText::_('COM_SEF_NONSEF_QUERY_VARIABLES');?>:</td>
            	            <td><?php echo $this->lists['nonSefQueryVariables']; ?></td>
            	        </tr>
                    </table>
                </fieldset>
       	        
                <?php $x = 0; ?>
                <fieldset class="adminform">
                    <legend><?php echo JText::_('COM_SEF_VARIABLES_FILTERING');?></legend>
                    <table class="adminform table table-striped">
            	        <tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
            	            <td width="20"><?php echo $this->tooltip(JText::sprintf('COM_SEF_TT_CHECK_JUNK_URLS', JText::_('COM_SEF_FILTER_THESE_WORDS')), JText::_('COM_SEF_FILTER_VARIABLE_VALUES'));?></td>
            	            <td width="200"><?php echo JText::_('COM_SEF_FILTER_VARIABLE_VALUES');?></td>
            	            <td><?php echo $this->lists['checkJunkUrls'];?></td>
            	        </tr>
            	        <tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
            	            <td><?php echo $this->tooltip(JText::_('COM_SEF_TT_JUNK_WORDS'), JText::_('COM_SEF_FILTER_THESE_WORDS'));?></td>
            	            <td><?php echo JText::_('COM_SEF_FILTER_THESE_WORDS');?>:</td>
            	            <td><?php echo $this->lists['junkWords'];?></td>
            	        </tr>
            	        <tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
            	            <td><?php echo $this->tooltip(JText::_('COM_SEF_TT_JUNK_EXCLUDE'), JText::_('COM_SEF_JUNK_EXCLUDE'));?></td>
            	            <td><?php echo JText::_('COM_SEF_JUNK_EXCLUDE');?>:</td>
            	            <td><?php echo $this->lists['junkExclude'];?></td>
            	        </tr>
                    </table>
                </fieldset>
                
                <?php $x = 0; ?>
                <fieldset class="adminform">
                    <legend><?php echo JText::_('COM_SEF_ITEMID_HANDLING');?></legend>
                    <table class="adminform table table-striped">
            	        <tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
            	            <td width="20"><?php echo $this->tooltip(JText::_('COM_SEF_TT_EXCLUDE_SOURCE'), JText::_('COM_SEF_EXCLUDE_SOURCE'));?></td>
            	            <td width="200"><?php echo JText::_('COM_SEF_EXCLUDE_SOURCE');?></td>
            	            <td><?php echo $this->lists['excludeSource'];?></td>
            	        </tr>
            	        <tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
            	            <td><?php echo $this->tooltip(JText::_('COM_SEF_TT_REAPPEND_SOURCE'), JText::_('COM_SEF_REAPPEND_SOURCE'));?></td>
            	            <td><?php echo JText::_('COM_SEF_REAPPEND_SOURCE');?></td>
            	            <td><?php echo $this->lists['reappendSource'];?></td>
            	        </tr>
            	        <tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
            	            <td><?php echo $this->tooltip(JText::_('COM_SEF_TT_IGNORE_SOURCE'), JText::_('COM_SEF_IGNORE_MULTIPLE_SOURCES'));?></td>
            	            <td><?php echo JText::_('COM_SEF_IGNORE_MULTIPLE_SOURCES');?></td>
            	            <td><?php echo $this->lists['ignoreSource'];?></td>
            	        </tr>
                    </table>
                </fieldset>
            </div>
            <div style="clear: both;"></div>
            
		  <?php
          } // $sefConfig->professionalMode
