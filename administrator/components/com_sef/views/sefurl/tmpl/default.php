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
?>

	<script language="javascript">
	<!--
    var JoomSEF = {};
    JoomSEF.getRadioValue = function(radioGroup) {
        for (var i = 0; i < radioGroup.length; i++) {
            if (radioGroup[i].checked) {
                return radioGroup[i].value;
            }
        }
        
        return null;
    }
    
	Joomla.submitbutton = function(pressbutton)
	{
	    var form = document.adminForm;
	    if (pressbutton == 'cancel') {
	        Joomla.submitform( pressbutton );
	        return;
	    }
        
        // Handle custom URL field
	    if (JoomSEF.getRadioValue(form.customurl) == '1') {
	        form.dateadd.value = "<?php echo date('Y-m-d'); ?>"
	    } else {
	        form.dateadd.value = "0000-00-00"
	    }
        
	    // do field validation
	    if (form.origurl.value == "") {
	        alert( "<?php echo JText::_('COM_SEF_ERROR_REDIRECTION_MISSING'); ?>" );
	        return;
	    }
        if (form.origurl.value.match(/^index.php/)) {
            <?php if( $sefConfig->useMoved ) { ?>
            // Ask to save the changed url to Moved Permanently table
            if( (form.sefurl.value != form.unchanged.value) && (form.id.value != "0" && form.id.value != "") ) {
            	form.urlchanged.value=1;
                <?php if( $sefConfig->useMovedAsk ) { ?>
                if( confirm("<?php echo JText::_('COM_SEF_CONFIRM_AUTO_301'); ?>") ) {
                    form.addtosefmoved.value=1;
                }
                <?php } ?>
            }
            <?php } ?>
            
            
            Joomla.submitform( pressbutton );
        } else {
            alert( "<?php echo JText::_('COM_SEF_ERROR_URL_MISSING_INDEX'); ?>" );
        }
	}
	
    function addMetaTag() {
        var tbl = document.getElementById('tblMetatags');
        if( !tbl ) {
            return;
        }
        var tbody = tbl.getElementsByTagName('tbody')[0];
        if( !tbody ) {
            return;
        }
        
        var row = document.createElement('tr');
        var td1 = document.createElement('td');
        td1.width = '30%';
        td1.innerHTML = '<input type="text" value="" style="width:100%" name="metanames[]" />';
        var td2 = document.createElement('td');
        //td2.width = '70%';
        td2.innerHTML = '<input type="text" value="" style="width:100%" name="metacontents[]" />';
        var td3 = document.createElement('td');
        td3.width = '100';
        td3.innerHTML = '<input type="button" value="<?php echo JText::_('COM_SEF_REMOVE_META_TAG'); ?>" onclick="removeMetaTag(this);" />';
        row.appendChild(td1);
        row.appendChild(td2);
        row.appendChild(td3);
        tbody.appendChild(row);
    }
    
    function removeMetaTag(el) {
        var tbl = document.getElementById('tblMetatags');
        if( !tbl ) {
            return;
        }
        var tbody = tbl.getElementsByTagName('tbody')[0];
        if( !tbody ) {
            return;
        }

        while( el ) {
            if( el.nodeName && (el.nodeName.toLowerCase() == 'tr') ) {
                break;
            }
            el = el.parentNode;
        }
        
        if( el.nodeName && (el.nodeName.toLowerCase() == 'tr') ) {
           tbody.removeChild(el);
        }
    }

	//-->
	</script>
	<ul id="autocomplete" style="display: none;"><li>dummy</li></ul>
	
	<form action="index.php" method="post" name="adminForm" id="adminForm">
	
	<?php
	echo JHtml::_('tabs.start', 'sef-url-tabs', array('useCookie' => 1));
	echo JHtml::_('tabs.panel', JText::_('COM_SEF_URL'), 'url-panel');
	?>
	<fieldset class="adminform">
	   <legend><?php echo JText::_('COM_SEF_URL'); ?></legend>
    	<table class="admintable table">
    		<tr>
    			<td class="key"><?php echo JText::_('COM_SEF_NEW_SEF_URL'); ?></td>
    			<td><input class="inputbox" type="text" size="100" style="width: 500px;" name="sefurl" value="<?php echo $this->sef->sefurl; ?>">
    			<?php echo $this->tooltip(JText::_('COM_SEF_TT_SEF_URL'), JText::_('COM_SEF_NEW_SEF_URL')); ?>
    			</td>
    		</tr>
    		<tr>
    			<td class="key"><?php echo JText::_('COM_SEF_OLD_NON_SEF_URL');?></td>
    			<td align="left"><input class="inputbox" type="text" size="100" style="width: 500px;" name="origurl" value="<?php echo $this->sef->origurl; ?>">
    			<?php echo $this->tooltip(JText::_('COM_SEF_TT_ORIG_URL'), JText::_('COM_SEF_OLD_NON_SEF_URL'));?>
    			</td>
    		</tr>
    		<tr>
    			<td class="key"><?php echo JText::_('COM_SEF_ITEMID');?></td>
    			<td align="left"><input class="inputbox" type="text" size="10" style="width: 100px;" name="Itemid" value="<?php echo $this->sef->Itemid; ?>">
    			<?php echo $this->tooltip(JText::_('COM_SEF_TT_ITEMID'), JText::_('COM_SEF_ITEMID'));?>
    			</td>
    		</tr>		
    		<tr>
          		<td class="key"><?php echo JText::_('COM_SEF_SAVE_AS_CUSTOM_REDIRECT'); ?></td>
          		<td>
          			<?php echo $this->lists['customurl']; ?>
          		</td>
    		</tr>
    		<tr>
    		  <td class="key"><?php echo JText::_('COM_SEF_ENABLED'); ?></td>
    		  <td>
    		      <?php echo $this->lists['enabled']; ?>
    		      <?php echo $this->tooltip(JText::_('COM_SEF_TT_URL_ENABLED'), JText::_('COM_SEF_ENABLED'));?>
    		  </td>
    		</tr>
    		<tr>
    		  <td class="key"><?php echo JText::_('COM_SEF_SEF'); ?></td>
    		  <td>
    		      <?php echo $this->lists['sef']; ?>
    		      <?php echo $this->tooltip(JText::_('COM_SEF_TT_URL_SEF'), JText::_('COM_SEF_SEF'));?>
    		  </td>
    		</tr>
    		<?php
    		if(JRequest::getInt('viewmode')!=6) {
    			?>
	    		<tr>
	    		  <td class="key"><?php echo JText::_('COM_SEF_LOCKED'); ?></td>
	    		  <td>
	    		      <?php echo $this->lists['locked']; ?>
	    		      <?php echo $this->tooltip(JText::_('COM_SEF_TT_URL_LOCKED'), JText::_('COM_SEF_LOCKED'));?>
	    		  </td>
	    		</tr>
	    		<?php
    		}
    		?>
		<?php $config =& SEFConfig::getConfig(); ?>
		<?php if ($config->trace) : ?>		
		<tr><th colspan="2"><?php echo JText::_('COM_SEF_URL_SOURCE_TRACING'); ?></th></tr>
		<tr>
		  <td valign="top" class="key"><?php echo JText::_('COM_SEF_TRACE_INFORMATION'); ?>:</td>
		  <td align="left"><?php echo nl2br(htmlspecialchars($this->sef->trace)); ?>
		  </td>
		</tr>
		<?php endif; ?>
		</table>
	</fieldset>
	
	<?php
	if(JRequest::getInt('viewmode')!=6) {
		echo JHtml::_('tabs.panel', JText::_('COM_SEF_ALIASES'), 'alias-panel');
		?>
		<fieldset class="adminform">
		   <legend><?php echo JText::_('COM_SEF_ALIASES'); ?></legend>
	    	<table class="admintable table">
	    		<tr>
	    			<td class="key" valign="top"><?php echo JText::_('COM_SEF_ALIAS_LIST'); ?></td>
	    			<td>
	        			<textarea class="inputbox" rows="10" cols="80" name="aliases" style="width: 500px;" id="aliases"><?php echo $this->sef->aliases; ?></textarea>
	        			<?php echo $this->tooltip(JText::_('COM_SEF_TT_ALIAS_LIST'), JText::_('COM_SEF_ALIAS_LIST')); ?>
	    			</td>
	    		</tr>
	    	</table>
	    </fieldset>
		
		<?php
	}
	echo JHtml::_('tabs.panel', JText::_('COM_SEF_META_TAGS'), 'meta-panel');
	?>
	
    <div class="sef-width-50 fltlft">
    	<fieldset class="adminform">
    	   <legend><?php echo JText::_('COM_SEF_META_TAGS_PREDEFINED'); ?></legend>
    	   <table class="admintable table" width="100%">
    		<tr><td colspan="2"><?php echo  $this->tooltip(JText::_('COM_SEF_INFO_JOOMSEF_PLUGIN'), JText::_('COM_SEF_JOOMSEF_PLUGIN_NOTICE')); ?></td></tr>
    		<tr>
    		  <td class="key"><?php echo JText::_('COM_SEF_TITLE'); ?>:</td>
    		  <td align="left"><input class="inputbox" type="text" style="width: 100%;" maxlength="255" name="metatitle" value="<?php echo htmlspecialchars($this->sef->metatitle); ?>">
    		  </td>
    		</tr>
    		<tr>
    		  <td class="key"><?php echo JText::_('COM_SEF_META_DESCRITION'); ?>:</td>
    		  <td align="left"><input class="inputbox" type="text" style="width: 100%;" maxlength="255" name="metadesc" value="<?php echo htmlspecialchars($this->sef->metadesc); ?>">
    		  </td>
    		</tr>
    		<tr>
    		  <td class="key"><?php echo JText::_('COM_SEF_META_KEYWORDS'); ?>:</td>
    		  <td align="left"><input class="inputbox" type="text" style="width: 100%;" maxlength="255" name="metakey" value="<?php echo htmlspecialchars($this->sef->metakey); ?>">
    		  </td>
    		</tr>
    		<tr>
    		  <td class="key"><?php echo JText::_('COM_SEF_META_CONTENT_LANGUAGE'); ?>:</td>
    		  <td align="left"><input class="inputbox" type="text" style="width: 100%;" maxlength="30" name="metalang" value="<?php echo htmlspecialchars($this->sef->metalang); ?>">
    		  </td>
    		</tr>
    		<tr>
    		  <td class="key"><?php echo JText::_('COM_SEF_META_ROBOTS'); ?>:</td>
    		  <td align="left"><input class="inputbox" type="text" style="width: 100%;" maxlength="30" name="metarobots" value="<?php echo htmlspecialchars($this->sef->metarobots); ?>">
    		  </td>
    		</tr>
    		<tr>
    		  <td class="key"><?php echo JText::_('COM_SEF_META_GOOGLEBOT'); ?>:</td>
    		  <td align="left"><input class="inputbox" type="text" style="width: 100%;" maxlength="30" name="metagoogle" value="<?php echo htmlspecialchars($this->sef->metagoogle); ?>">
    		  </td>
    		</tr>
    		<tr>
    		  <td class="key"><?php echo JText::_('COM_SEF_CANONICAL_LINK'); ?>:</td>
    		  <td align="left"><input class="inputbox" type="text" style="width: 100%;" maxlength="255" name="canonicallink" value="<?php echo htmlspecialchars($this->sef->canonicallink); ?>">
    		  </td>
    		</tr>
    	</table>
    	</fieldset>
        <fieldset class="adminform">
            <legend><?php echo JText::_('COM_SEF_OPTIONS'); ?></legend>
            <table class="admintable table">
                <tr>
                    <td class="key"><?php echo JText::_('COM_SEF_USE_SITENAME_IN_TITLE'); ?>:</td>
                    <td align="left"><?php echo $this->lists['showsitename']; ?></td>
                </tr>
            </table>
        </fieldset>
	</div>
    <div class="sef-width-50 fltlft">
    	<fieldset class="adminform">
    	   <legend><?php echo JText::_('COM_SEF_CUSTOM_META_TAGS'); ?></legend>
           <table class="adminform" id="tblMetatags">
              <tr>
                  <th width="30%"><?php echo JText::_('COM_SEF_NAME'); ?></th>
                  <th><?php echo JText::_('COM_SEF_CONTENT'); ?></th>
                  <th width="100">&nbsp;</th>
              </tr>
              <?php
              // Custom meta tags
              if (is_array($this->sef->metacustom)) {
                  foreach ($this->sef->metacustom as $name => $content) {
                      ?>
                      <tr>
                          <td width="30%"><input type="text" name="metanames[]" style="width:100%" value="<?php echo $name; ?>" /></td>
                          <td><input type="text" name="metacontents[]" style="width:100%" value="<?php echo htmlspecialchars($content); ?>" /></td>
                          <td width="100"><input type="button" value="<?php echo JText::_('COM_SEF_REMOVE_META_TAG'); ?>" onclick="removeMetaTag(this);" /></td>
                      </tr>
                      <?php
                  }
              }
              ?>
           </table>
           <input type="button" value="<?php echo JText::_('COM_SEF_ADD_META_TAG'); ?>" onclick="addMetaTag();" />
        </fieldset>
    </div>
    <div style="clear: both;"></div>
    
	<?php
	if(JRequest::getInt('viewmode')!=6) {
		echo JHtml::_('tabs.panel', JText::_('COM_SEF_SITEMAP'), 'sitemap-panel');
    	JoomSEF::OnlyPaidVersion();
	}
    
	if(JRequest::getInt('viewmode')!=6) {
		echo JHtml::_('tabs.panel', JText::_('COM_SEF_INTERNAL_LINKS'), 'internal-panel');
        JoomSEF::OnlyPaidVersion();
	}
	echo JHtml::_('tabs.end');
	?>

<input type="hidden" name="option" value="com_sef" />
<input type="hidden" name="task" value="" />
<input type="hidden" name="controller" value="sefurls" />
<input type="hidden" name="unchanged" value="<?php echo $this->sef->sefurl; ?>" />
<input type="hidden" name="urlchanged" value="0" />
<input type="hidden" name="addtosefmoved" value="0" />
<input type="hidden" name="dateadd" value="<?php echo $this->sef->dateadd; ?>" />
<input type="hidden" name="id" value="<?php echo JRequest::getInt('viewmode')!=6?$this->sef->id:$this->sef->sefurl; ?>" />
<input type="hidden" name="wordsArray" value="" />
<input type="hidden" name="host" value="<?php echo $this->sef->host; ?>" />
</form>
