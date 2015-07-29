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

<form action="index.php" method="post" name="adminForm" id="adminForm">

<div class="sef-width-60 fltlft">
    <fieldset class="adminform">
        <legend><?php echo JText::_( 'Parameters' ); ?></legend>

        <?php
        echo JHtml::_('tabs.start', 'sef-extension-tabs', array('useCookie' => 1));

        // Render each parameters group
        $fieldsets = $this->extension->form->getFieldsets();
        if (is_array($fieldsets) && count($fieldsets) > 0) {
            $i = 0;
            foreach ($fieldsets as $name => $fieldset) {
                if ($name == 'varfilter') {
                    continue;
                }

                $fields = $this->extension->form->getFieldset($name);
                if (count($fields) > 0) {
                    $label = JText::_($name);
                    $i++;
                    echo JHtml::_('tabs.panel', $label, 'page-'.$i);

                    $this->renderParams($this->extension->form, $name);
                }
            }
        }
        
        echo JHTML::_('tabs.panel',Jtext::_('COM_SEF_SUBDOMAINS'),'subdomains');
        ?>
        <fieldset class="adminform">
        	<legend><?php echo Jtext::_('COM_SEF_SUBDOMAINS'); ?></legend>
        	<table class="adminform table table-striped">
        		<tr>
        			<th>
        			<?php echo Jtext::_('COM_SEF_SUBDOMAIN'); ?>
        			</th>
        			<th>
        			<?php echo Jtext::_('COM_SEF_TITLEPAGE'); ?>
        			</th>
        			<th>
        			<?php echo Jtext::_('COM_SEF_LANGUAGE'); ?>
        			</th>
        		</tr>
        		<?php
        		foreach($this->langs as $lang) {
        			$sef=$lang->sef;
        			?>
        			<tr>
        				<td>
        				<input class="inputbox" type="textbox" size="10" style="text-align:right" name="subdomain[<?php echo $sef; ?>][title]" value="<?php echo @$this->subdomains[$sef]->subdomain; ?>" />.<?php echo $this->rootDomain; ?>
        				</td>
        				<td>
        				<?php echo JHTML::_('select.genericlist',$this->menus[$sef],"subdomain[".$sef."][titlepage]",array('list.select'=>@$this->subdomains[$sef]->Itemid_titlepage)); ?>
        				</td>
        				<td>
        				<?php echo $lang->title; ?>
        				</td>
        			</tr>
        			<?php
        		}
        		?>
        	</table>
        </fieldset>
        <?php

        echo JHtml::_('tabs.panel', JText::_('COM_SEF_VARIABLES_FILTERING'), 'varfilter');
        ?>

        <fieldset class="panelform">
        <div id="filterdiv">
        <?php
        JoomSEF::OnlyPaidVersion();
        ?>
        </div>
        </fieldset>
        <?php
        if(count($this->strings)>0) {
			echo JHtml::_('tabs.panel', JText::_('COM_SEF_TEXTS'), 'texts');
			echo JHTML::_('tabs.start','sef-extension-texts');
			echo JHTML::_('tabs.panel',JText::_('COM_SEF_Default'),'default');
			?>
			<fieldset class="adminform">
				<table class="adminlist">
				<tr>
					<th>
					<?php echo JText::_('COM_SEF_TEXT_NAME'); ?>
					</th>
					<th>
					<?php echo JText::_('COM_SEF_TEXT_VALUE'); ?>
					</th>
				</tr>
					<?php
					for($j=0;$j<count($this->strings);$j++) {
						$name=$this->strings[$j]->name;
						?>
							<tr>
								<td>
								<?php echo $name; ?>
								</td>
								<td>
								<input class="inputbox" type="text" size="50" value="<?php echo $this->translation[0][$name]; ?>" name="texts[0][<?php echo $name; ?>];"/>
								</td>
							</tr>
						<?php
					}
					?>
				</table>
			</fieldset>
			<?php
			for($i=0;$i<count($this->langs);$i++) {
				//echo JHTML::_('tabs.panel',JHTML::_('image','../media/mod_languages/images/'.$this->langs[$i]->image.'.gif',$this->langs[$i]->code)."&nbsp;".$this->langs[$i]->code,$this->langs[$i]->code);
                echo JHTML::_('tabs.panel','<img src=../media/mod_languages/images/'.$this->langs[$i]->image.'.gif alt="'.$this->langs[$i]->lang_code.'"/>&nbsp;'.$this->langs[$i]->lang_code,$this->langs[$i]->lang_code);
				?>
				<fieldset class="adminform">
					<table class="adminlist">
						<tr>
							<th>
							<?php echo JText::_('COM_SEF_TEXT_NAME'); ?>
							</th>
							<th>
							<?php echo JText::_('COM_SEF_TEXT_VALUE'); ?>
							</th>
						</tr>
						<?php
						for($j=0;$j<count($this->strings);$j++) {
							$name=$this->strings[$j]->name;
							?>
								<tr>
									<td>
									<?php echo $this->strings[$j]->name; ?>
									</td>
									<td>
                                    <input class="inputbox" type="text" size="50" value="<?php echo @$this->translation[$this->langs[$i]->lang_id][$name]; ?>" name="texts[<?php echo $this->langs[$i]->lang_id; ?>][<?php echo $name; ?>]"/>
									</td>
								</tr>
							<?php
						}
						?>
					</table>
				</fieldset>
				<?php
			}
			echo JHTML::_('tabs.end');
        }
		?>
        <?php
        echo JHtml::_('tabs.end');
        ?>
    </fieldset>
</div>

<div class="sef-width-40 fltrt">
    <?php
    if( !empty($this->extension->name) ) {
        ?>
        <fieldset class="adminform">
            <legend><?php echo JText::_( 'Extension Details' ); ?></legend>

            <table class="adminlist table">
                <tr>
                    <th width="150">
                        <?php echo JText::_('COM_SEF_NAME'); ?>:
                    </th>
                    <td>
                        <?php echo $this->extension->name; ?>
                    </td>
                </tr>
                <tr>
                    <th>
                        <?php echo JText::_('COM_SEF_VERSION'); ?>:
                    </th>
                    <td>
                        <?php echo $this->extension->version; ?>
                    </td>
                </tr>
                <tr>
                    <th>
                        <?php echo JText::_('COM_SEF_DESCRIPTION'); ?>:
                    </th>
                    <td>
                        <?php echo $this->extension->description; ?>
                    </td>
                </tr>
            </table>
        </fieldset>
        <?php
    }
    ?>

    <?php
    if( !is_null($this->extension->component) ) {
        ?>
        <fieldset class="adminform">
            <legend><?php echo JText::_( 'Component Details' ); ?></legend>

            <table class="adminlist table">
                <tr>
                    <th width="150">
                        <?php echo JText::_('COM_SEF_NAME'); ?>:
                    </th>
                    <td>
                        <?php echo $this->extension->component->name; ?>
                    </td>
                </tr>
                <tr>
                    <th>
                        <?php echo JText::_('COM_SEF_OPTION'); ?>:
                    </th>
                    <td>
                        <?php echo $this->extension->component->option; ?>
                    </td>
                </tr>
            </table>
        </fieldset>
        <?php
    }
    ?>
</div>
<div class="clr"></div>

<input type="hidden" name="option" value="com_sef" />
<input type="hidden" name="controller" value="extension" />
<input type="hidden" name="task" value="" />
<input type="hidden" name="element" value="<?php echo $this->extension->id; ?>" />
<input type="hidden" name="redirto" value="<?php echo $this->redirto; ?>" />
<input type="hidden" name="filters" value="" />

<?php echo JHTML::_( 'form.token' ); ?>
</form>
