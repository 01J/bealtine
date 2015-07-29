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
function updateWarning()
{
    var res = confirm('<?php echo JText::_('COM_SEF_CONFIRM_URL_UPDATE'); ?>');
    if( res ) {
        alert('<?php echo JText::_('COM_SEF_INFO_DONT_INTERRUPT'); ?>');
    }
    return res;
}
function updateMetaWarning()
{
    var res = confirm('<?php echo JText::_('COM_SEF_CONFIRM_METATAGS_UPDATE'); ?>');
    if( res ) {
        alert('<?php echo JText::_('COM_SEF_INFO_DONT_INTERRUPT'); ?>');
    }
    return res;
}
function cacheClearWarning()
{
    var res = confirm('<?php echo JText::_('COM_SEF_CONFIRM_CACHE_CLEAR'); ?>');
    return res;
}
function purgeWarning()
{
    var res = confirm('<?php echo sprintf(JText::_('COM_SEF_CONFIRM_URL_PURGE'), $this->purgeCount); ?>');
    return res;
}
function enableStatus(type)
{
    var form = document.adminForm;
    if( !form ) {
        return;
    }
    
    form.statusType.value = type;
    submitbutton('enableStatus');
}

function disableStatus(type)
{
    var form = document.adminForm;
    if( !form ) {
        return;
    }
    
    form.statusType.value = type;
    submitbutton('disableStatus');
}

function showUpgrade()
{
    submitbutton('showUpgrade');
}
-->
</script>

<div class="sef-width-60 fltlft">
	<div class="icons" id="cpanel">
	    
        <?php
		echo JHtml::_('tabs.start', 'sef-cpanel-tabs', array('useCookie' => 1));
		echo JHtml::_('tabs.panel', JText::_('COM_SEF_JOOMSEF_CONFIGURATION'), 'config');
        ?>
        
		<div class="config">
	    	<!-- Global Configuration -->
	    	<div class="icon">
	    		<a href="index.php?option=com_sef&amp;controller=config&amp;task=edit" title="Configure all ARTIO JoomSEF functionality">
	       		<img src="components/com_sef/assets/images/icon-48-config.png" alt="" width="48" height="48" border="0"/>
	       		<span><?php echo JText::_('COM_SEF_GLOBAL_CONFIGURATION'); ?></span>
	       	</a>
	       </div>
	       <!--  Extensions Management -->
	       <div class="icon">
	    		<a href="index.php?option=com_sef&amp;controller=extension" title="Extensions Management">
	       		<img src="components/com_sef/assets/images/icon-48-plugin.png" alt="" width="48" height="48" border="0"/>
	       		<span><?php echo JText::_('COM_SEF_EXTENSIONS_MANAGEMENT'); ?></span>
	       	</a>
	       </div>
	       <!--  Edit .htaccess -->
	      	<div class="icon">
	       	<a href="index.php?option=com_sef&amp;controller=htaccess" title="Edit .htaccess file">
	      			<img src="components/com_sef/assets/images/icon-48-edit.png" alt="" width="48" height="48" border="0"/>
	      			<span><?php echo JText::_('COM_SEF_EDIT') . ' .htaccess'; ?></span>
	      		</a>
	      	</div>
	       <!--  Updates -->
	      	<div class="icon">
	       	<a href="index.php?option=com_sef&amp;task=showUpgrade" title="Component and plugin online and local upgrades">
	      			<img src="components/com_sef/assets/images/icon-48-update.png" alt="" width="48" height="48" border="0"/>
	      			<span><?php echo JText::_('COM_SEF_CHECK_UPDATES'); ?></span>
	      		</a>
	      	</div>            	
	
	      	<div style="clear: both;"></div>
	    </div>
	          
        <?php
        echo JHtml::_('tabs.panel', JText::_('COM_SEF_URLS_MANAGEMENT'), 'urls');
        ?>
                
		<div class="urls">
	   		<!-- URLs Edit -->
	   		<div class="icon">
	   			<a href="index.php?option=com_sef&amp;controller=sefurls&amp;viewmode=3" title="View/Edit SEF Urls">
	      			<img src="components/com_sef/assets/images/icon-48-url-edit.png" alt="" width="48" height="48" border="0"/>
	      			<span><?php echo JText::_('COM_SEF_MANAGE') . ' ' . JText::_('COM_SEF_SEF_URLS'); ?></span>
	      		</a>
	   		</div>
	   		<!--  Custom URLs -->
	    	<div class="icon">
				<a href="index.php?option=com_sef&amp;controller=sefurls&amp;viewmode=2" title="View/Edit Custom Redirects">
	       			<img src="components/com_sef/assets/images/icon-48-url-user.png" alt="" width="48" height="48" border="0"/>
	       			<span><?php echo JText::_('COM_SEF_MANAGE_CUSTOM_URLS'); ?></span>
	      		</a>
	      	</div>	        	
	   		<!--  Manage Meta Tags -->
	   		<div class="icon">
	   			<a href="index.php?option=com_sef&amp;controller=metatags" title="Manage Meta Tags">
	        		 <img src="components/com_sef/assets/images/icon-48-manage-tags.png" alt="" width="48" height="48" align="middle" border="0"/>
	      			<span><?php echo JText::_('COM_SEF_MANAGE_META_TAGS'); ?></span>
	      		</a>
	      	</div>
	      	<!--  Edit Internal Redirects -->
	      	<div class="icon">
	      		<a href="index.php?option=com_sef&amp;controller=movedurls" title="View/Edit Moved Permanently Redirects">
	      			<img src="components/com_sef/assets/images/icon-48-301-redirects.png" alt="" width="48" height="48" border="0"/>
	      			<span><?php echo JText::_('COM_SEF_MANAGE') . ' ' . JText::_('COM_SEF_INTERNAL_301_REDIRECTS'); ?></span>
	      		</a>
	      	</div>
	   		<div style="clear: both;"></div>
	   	</div>
	   		
        <?php
        echo JHtml::_('tabs.panel', JText::_('COM_SEF_EXTRAS_MANAGEMENT'), 'extras');
        ?>
                
		<div class="extras">
            <!--  Manage Sitemap -->
	   		<div class="icon">
	   			<a href="index.php?option=com_sef&amp;controller=sitemap" title="Manage SiteMap">
	        		 <img src="components/com_sef/assets/images/icon-48-manage-sitemap.png" alt="" width="48" height="48" align="middle" border="0"/>
	      			<span><?php echo JText::_('COM_SEF_MANAGE_SITEMAP'); ?></span>
	      		</a>
	      	</div>
            <!--  Manage Words -->
	   		<div class="icon">
	   			<a href="index.php?option=com_sef&amp;controller=words" title="Manage Words for Internal Links">
	        		 <img src="components/com_sef/assets/images/icon-48-manage-words.png" alt="" width="48" height="48" align="middle" border="0"/>
	      			<span><?php echo JText::_('COM_SEF_MANAGE_WORDS'); ?></span>
	      		</a>
	      	</div>
            <div style="clear: both;"></div>
            
            <!--  Statistics -->
            <div class="icon">
	   			<a href="index.php?option=com_sef&amp;view=statistics" title="Statistics">
	        		 <img src="components/com_sef/assets/images/icon-48-statistics.png" alt="" width="48" height="48" align="middle" border="0"/>
	      			<span><?php echo JText::_('COM_SEF_STATISTICS'); ?></span>
	      		</a>
	      	</div>
            <!--  Crawl Web -->
	   		<div class="icon">
	   			<a href="index.php?option=com_sef&amp;controller=crawler" title="Crawl Website">
	        		 <img src="components/com_sef/assets/images/icon-48-web-crawl.png" alt="" width="48" height="48" align="middle" border="0"/>
	      			<span><?php echo JText::_('COM_SEF_CRAWL_WEB'); ?></span>
	      		</a>
	      	</div>
            <!--  Set Up Cron -->
            <div class="icon">
                <a href="index.php?option=com_sef&amp;controller=cron" title="Set up cron job to run specified tasks automatically.">
                    <img src="components/com_sef/assets/images/icon-48-cron.png" alt="" width="48" height="48" align="middle" border="0"/>
                    <span><?php echo JText::_('COM_SEF_CRON'); ?></span>
                </a>
            </div>          
	   		<div style="clear: both;"></div>
	   	</div>
	   	
        <?php
        echo JHtml::_('tabs.panel', JText::_('COM_SEF_URLS_MAINTENANCE'), 'maintenance');
        ?>
        
		<div class="extras">
	      	<!--  Update URLs -->
	   		<div class="icon">
	   			<a href="index.php?option=com_sef&amp;controller=sefurls&amp;task=updateurls" onclick="return updateWarning();" title="Update stored URLs after configuration change">
	         		<img src="components/com_sef/assets/images/icon-48-url-update.png" alt="" width="48" height="48" align="middle" border="0"/>
	      			<span><?php echo JText::_('COM_SEF_UPDATE_URLS'); ?></span>
	      		</a>
	      	</div>
            <!--  Update Metatags -->
            <div class="icon">
                <a href="index.php?option=com_sef&amp;controller=sefurls&amp;task=updatemeta" onclick="return updateMetaWarning();" title="Update stored meta tags after configuration change">
                    <img src="components/com_sef/assets/images/icon-48-update-tags.png" alt="" width="48" height="48" align="middle" border="0"/>
                    <span><?php echo JText::_('COM_SEF_UPDATE_META_TAGS'); ?></span>
                </a>
            </div>
            <div style="clear: both;"></div>
            
	   		<!-- URLs Purge -->
	    	<div class="icon">
				<a href="index.php?option=com_sef&amp;controller=urls&amp;task=purge&amp;type=0&amp;confirmed=1" onclick="return purgeWarning();" title="Purge auto-generated SEF Urls">
	       			<img src="components/com_sef/assets/images/icon-48-url-delete.png" alt="" width="48" height="48" border="0"/>
	       			<span><?php echo JText::_('COM_SEF_PURGE') . ' ' . JText::_('COM_SEF_SEF_URLS'); ?></span>
	      		</a>
	      	</div>
	   		<!--  Clear Cache -->
	   		<div class="icon">
	   			<a href="index.php?option=com_sef&amp;task=cleancache" onclick="return cacheClearWarning();" title="Clear URLs included in JoomSEF cache">
	        		 <img src="components/com_sef/assets/images/icon-48-clear.png" alt="" width="48" height="48" align="middle" border="0"/>
	      			<span><?php echo JText::_('COM_SEF_CLEAR_CACHE'); ?></span>
	      		</a>
	      	</div>
	      	<!--  404 Logs -->
	      	<div class="icon">
	      		<a href="index.php?option=com_sef&amp;controller=sefurls&amp;viewmode=1" title="View/Edit 404 Logs">
	      			<img src="components/com_sef/assets/images/icon-48-404-logs.png" alt="" width="48" height="48" border="0"/>
	      			<span><?php echo JText::_('COM_SEF_VIEW') . ' ' . JText::_('COM_SEF_404_LOGS'); ?></span>
	     		</a>
	     	</div>
	      	<!--  Error Logs -->
	      	<div class="icon">
	      		<a href="index.php?option=com_sef&amp;controller=logger" title="View log">
	      			<img src="components/com_sef/assets/images/icon-48-error-logs.png" alt="" width="48" height="48" border="0"/>
	      			<span><?php echo JText::_('COM_SEF_VIEW_LOGS'); ?></span>
	     		</a>
	     	</div>
            <div style="clear: both;"></div>
        </div>
        
        <?php
        echo JHtml::_('tabs.panel', JText::_('COM_SEF_HELP_AND_SUPPORT'), 'help');
        ?>
                
	   	<?php 
	   	include JPath::clean(JPATH_COMPONENT_ADMINISTRATOR.'/views/info/tmpl/help.php'); 
	   	?>
	
	  <?php
	  echo JHtml::_('tabs.end');
	  ?>
          
    </div>
</div>

<div class="sef-width-40 fltrt">
	<?php
	$sefInfo = SEFTools::getSEFInfo();
	?>
	
	<?php
	echo JHtml::_('sliders.start', 'sef-info-pane', array('useCookie' => 1, 'allowAllClose' => true));
	echo JHtml::_('sliders.panel', JText::_('COM_SEF_ARTIO_JOOMSEF'), 'info-panel');
	?>
	
	<table class="adminlist table table-striped">
	   <tr>
			<th></td>
			<td>
	      		<a href="http://www.artio.net/en/joomla-extensions/artio-joomsef" target="_blank">
	          		<img src="components/com_sef/assets/images/box.png" align="middle" alt="JoomSEF logo" style="border: none; margin: 8px;" />
	        	</a>
			</td>
		</tr>
	   <tr>
	      <th width="120"></td>
	      <td><a href="http://www.artio.net/joomla-extensions/joomsef" target="_blank">ARTIO JoomSEF</a></td>
	   </tr>	
	   <tr>
	      <th><?php echo JText::_('COM_SEF_VERSION'); ?>:</td>
	      <td><?php echo $sefInfo['version']; ?></td>
	   </tr>
	   <tr>
	      <th><?php echo JText::_('COM_SEF_NEWEST_VERSION'); ?>:</td>
	      <td><?php echo $this->newestVersion; ?></td>
	   </tr>
	   <tr>
	      <th><?php echo JText::_('COM_SEF_DATE'); ?>:</td>
	      <td><?php echo $sefInfo['creationDate']; ?></td>
	   </tr>
	   <tr>
	      <th valign="top"><?php echo JText::_('COM_SEF_COPYRIGHT'); ?>:</td>
	      <td>Copyright &copy; 2006 - <?php echo date('Y', strtotime($sefInfo['creationDate'])); ?> <?php echo $sefInfo['copyright']; ?></td>
	   </tr>
	   <tr>
	      <th><?php echo JText::_('COM_SEF_AUTHOR'); ?>:</td>
	      <td><a href="<?php echo $sefInfo['authorUrl']; ?>" target="_blank"><?php echo $sefInfo['author']; ?></a>,
	      <a href="mailto:<?php echo $sefInfo['authorEmail']; ?>"><?php echo $sefInfo['authorEmail']; ?></a></td>
	   </tr>
	   <tr>
	      <th valign="top"><?php echo JText::_('COM_SEF_DESCRIPTION'); ?>:</td>
	      <td><?php echo $sefInfo['description']; ?></td>
	   </tr>
	   <tr>
	      <th><?php echo JText::_('COM_SEF_LICENSE'); ?>:</td>
	      <td><a href="http://www.gnu.org/copyleft/gpl.html" target="_blank">GNU/GPL</a></td>
	   </tr>
	   <tr>
	      <th><?php echo JText::_('COM_SEF_SUPPORT_US'); ?>:</td>
	      <td>
	          <form action="https://www.paypal.com/cgi-bin/webscr" method="post" style="margin: 0;">
	          <input name="cmd" type="hidden" value="_s-xclick"></input>
	          <input name="submit" type="image" style="border: none;" src="https://www.paypal.com/en_US/i/btn/x-click-but04.gif" title="Support JoomSEF"></input>
	          <img src="https://www.paypal.com/en_US/i/scr/pixel.gif" border="0" alt="" width="1" height="1" />
	          <input name="encrypted" type="hidden" value="-----BEGIN PKCS7-----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-----END PKCS7-----"></input>
	          </form>
	      </td>
	   </tr>
	</table>
	
	<?php
	echo JHtml::_('sliders.panel', JText::_('COM_SEF_SEF_STATUS'), 'status-panel');
	?>

	   <table class="adminlist table table-striped">
	       <tr>
	           <th width="150"><?php echo JText::_('COM_SEF_GLOBAL_SEF_URLS'); ?></td>
	           <td><?php $this->showStatus('sef'); ?></td>
	       </tr>
	       <tr>
	           <th><?php echo JText::_('COM_SEF_APACHE_MOD_REWRITE'); ?></td>
	           <td><?php $this->showStatus('mod_rewrite'); ?></td>
	       </tr>
	       <tr>
	           <th><?php echo JText::_('COM_SEF_JOOMSEF'); ?></td>
	           <td><?php $this->showStatus('joomsef'); ?></td>
	       </tr>
	       <tr>
	           <th><?php echo JText::_('COM_SEF_JOOMSEF_PLUGIN'); ?></td>
	           <td><?php $this->showStatus('plugin'); ?></td>
	       </tr>
	       <tr>
	           <th><?php echo JText::_('COM_SEF_CREATION_OF_NEW_URLS'); ?></td>
	           <td><?php $this->showStatus('newurls'); ?></td>
	       </tr>
	   </table>
	
	<?php
	$sefConfig =& SEFConfig::getConfig();
	if ($sefConfig->artioFeedDisplay) {
        echo JHtml::_('sliders.panel', JText::_('COM_SEF_ARTIO_NEWSFEED'), 'feed-panel');
	    ?>
	       <div class="joomsef_feed">
	       <?php echo $this->feed; ?>
	       </div>
	    <?php
	}
	?>
	
	<?php
	echo JHtml::_('sliders.panel', JText::_('COM_SEF_STATISTICS'), 'stat-panel');
	?>
    	
	   <table class="adminlist table table-striped">
	       <?php
	       if (is_array($this->stats)) {
	           foreach($this->stats as $stat) {
	               if ($stat->text == '') {
	                   ?>
	                   <tr>
	                       <th width="150">&nbsp;</td>
	                       <td>&nbsp;</td>
	                   </tr>
	                   <?php
	               } else {
	                   $isTotal = (strpos(strtolower($stat->text), 'total') !== false);
	                   $strong1 = $isTotal ? '<strong>' : '';
	                   $strong2 = $isTotal ? '</strong>' : '';
    	               
                       $text = $stat->text.':';
	                   if (isset($stat->link) && !empty($stat->link)) {
	                       $span1 = '<span class="hasTip" title="'.JText::_('View').' '.$stat->text.'">';
	                       $text = '<a href="'.$stat->link.'">'.$text.'</a>';
	                       $span2 = '</span>';
	                       $text = $span1 . $text . $span2;
	                   }
                       ?>
    	               <tr>
    	                   <th><?php echo $text; ?></td>
    	                   <td><?php echo $strong1 . $stat->value . $strong2; ?></td>
    	               </tr>
    	               <?php
	               }
	           }
	       }
	       ?>
	   </table>

	<?php
	echo JHtml::_('sliders.end');
	?>
    	
</div>

<form action="index.php" method="post" name="adminForm" id="adminForm">

<input type="hidden" name="option" value="com_sef" />
<input type="hidden" name="boxchecked" value="0" />
<input type="hidden" name="task" value="" />
<input type="hidden" name="statusType" value="" />
<input type="hidden" name="controller" value="" />
<?php echo JHTML::_('form.token'); ?>
</form>
