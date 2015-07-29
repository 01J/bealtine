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

echo JHtml::_('tabs.panel', JText::_('COM_SEF_ANALYTICS'), 'analytics');
          $x = 0;
          ?>
          <div class="fltflt">
          <fieldset class="adminform">
            <legend><?php echo JText::_('COM_SEF_ANALYTICS'); ?></legend>
          	<table class="adminform table table-striped">
          		<tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
          			<td width="20"><?php echo $this->tooltip(JText::_('COM_SEF_TT_GOOGLE_EMAIL'),JText::_('COM_SEF_GOOGLE_EMAIL')); ?></td>
          			<td width="200"><?php echo Jtext::_('COM_SEF_GOOGLE_EMAIL'); ?></td>
          			<td><input class="inputbox" type="text" name="google_email" id="google_email" value="<?php echo $this->lists["google_email"]; ?>" /></td>
          		</tr>
          		<tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
          			<td><?php echo $this->tooltip(JText::_('COM_SEF_TT_GOOGLE_PASSWORD'),JText::_('COM_SEF_GOOGLE_PASSWORD')); ?></td>
          			<td><?php echo JText::_('COM_SEF_GOOGLE_PASSWORD'); ?></td>
          			<td><input class="inputbox" type="password" name="google_password" id="google_password" autocomplete="off" value="<?php echo $this->lists["google_password"]; ?>" /></td>
          		</tr>
          		<tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
          			<td><?php echo $this->tooltip(JText::_('COM_SEF_TT_GOOGLE_WEB_ID'),JText::_('COM_SEF_WEB_ID')); ?></td>
          			<td><?php echo Jtext::_('COM_SEF_WEB_ID'); ?></td>
          			<td><input class="inputbox" type="text" name="google_id" id="google_id" value="<?php echo $this->lists["google_id"]; ?>" /></td>
          		</tr>
          		<tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
          			<td><?php echo $this->tooltip(JText::_('COM_SEF_TT_GOOGLE_APIKEY'),JText::_('COM_SEF_GOOGLE_APIKEY')); ?></td>
          			<td><?php echo JText::_('COM_SEF_GOOGLE_APIKEY'); ?></td>
          			<td><input class="inputbox" type="text" name="google_apikey" id="google_apikey" value="<?php echo $this->lists["google_apikey"]; ?>" /></td>
          		</tr>
          		<tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
          			<td><?php echo $this->tooltip(JText::_('COM_SEF_TT_GOOGLE_ENABLE'),JText::_('COM_SEF_GOOGLE_ENABLE')); ?></td>
          			<td><?php echo JText::_('COM_SEF_GOOGLE_ENABLE'); ?></td>
          			<td><?php echo $this->lists["google_enable"]; ?></td>
          		</tr>
          		<tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
          			<td><?php echo $this->tooltip(JText::_('COM_SEF_TT_GOOGLE_DEMOGRAPHIC'),JText::_('COM_SEF_GOOGLE_DEMOGRAPHIC')); ?></td>
          			<td><?php echo JText::_('COM_SEF_GOOGLE_DEMOGRAPHIC'); ?></td>
          			<td><?php echo $this->lists["google_demographic_reports"]; ?></td>
          		</tr>
          		<tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
          			<td><?php echo $this->tooltip(JText::_('COM_SEF_TT_GOOGLE_ATTRIBUTION'),JText::_('COM_SEF_GOOGLE_ATTRIBUTION')); ?></td>
          			<td><?php echo JText::_('COM_SEF_GOOGLE_ATTRIBUTION'); ?></td>
          			<td><?php echo $this->lists["google_link_attribution"]; ?></td>
          		</tr>
          		<tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
          			<td><?php echo $this->tooltip(JText::_('COM_SEF_TT_GOOGLE_EXCLUDE_IP'),JText::_('COM_SEF_GOOGLE_EXCLUDE_IP')); ?></td>
          			<td><?php echo JText::_('COM_SEF_GOOGLE_EXCLUDE_IP'); ?></td>
          			<td><textarea class="inputbox" style="width:200px;height:100px;" name="google_exclude_ip" id="google_exclude_ip"><?php echo $this->lists["google_exclude_ip"]; ?></textarea></td>
          		</tr>
          		<tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
          			<td><?php echo $this->tooltip(JText::_('COM_SEF_TT_GOOGLE_EXCLUDE_LEVEL'),JText::_('COM_SEF_GOOGLE_EXCLUDE_LEVEL')); ?></td>
          			<td><?php echo Jtext::_('COM_SEF_GOOGLE_EXCLUDE_LEVEL'); ?></td>
          			<td><?php echo $this->lists["google_exclude_level"]; ?></td>
          		</tr>
          		<tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
          			<td><?php echo $this->tooltip(JText::_('COM_SEF_TT_GOOGLE_EXCLUDE_COOKIE'),JText::_('COM_SEF_GOOGLE_EXCLUDE_COOKIE')); ?></td>
          			<td><?php echo JText::_('COM_SEF_GOOGLE_EXCLUDE_COOKIE'); ?></td>
          			<td>
          				<input class="button" type="button" id="remove_google_cookie" onclick="remove_cookie();" value="<?php echo JText::_('COM_SEF_GOOGLE_REMOVE_COOKIE'); ?>" <?php echo (JRequest::getInt('google_analytics_exclude',0,'cookie')==0)?'style="display:none;"':''; ?>/>
          				<input class="button" type="button" id="set_google_cookie" onclick="set_cookie();" value="<?php echo JText::_('COM_SEF_GOOGLE_SET_COOKIE'); ?>" <?php echo (JRequest::getInt('google_analytics_exclude',0,'cookie')==1)?'style="display:none;"':''; ?> />
          			</td>
          		</tr>
          		<tr<?php $x++; echo (($x % 2) ? '':' class="row1"' );?>>
          		</tr>
          	</table>
          </fieldset>
          </div>