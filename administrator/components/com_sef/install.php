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

// no direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.installer.installer');
jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.file');
jimport('joomla.application.helper');

class com_sefInstallerScript
{
    function preflight($action, $parent) {
        // Check minimum Joomla version
        $jversion = new JVersion();
        
        // Get minimum Joomla version from XML's root element version attribute
        $xml = $parent->get("manifest");
        $minVersion = (string) $xml['version'];
        if (!$jversion->isCompatible($minVersion)) {
            JError::raiseWarning('100', JText::sprintf('COM_SEF_ERR_MIN_JOOMLA_VERSION', $minVersion));
            return false;
        }
        
        
        if($action=='update') {
            if(JFile::exists(JPATH_ADMINISTRATOR.'/components/com_sef/configuration.php')) {
                $tmp_path=JFactory::getApplication()->getCfg('tmp_path');
                JFile::copy(JPATH_ADMINISTRATOR.'/components/com_sef/configuration.php',$tmp_path.'/joomsef-configuration.php');
            }
        }
        
        $db = JFactory::getDBO();
        $query = "SELECT extension_id FROM #__extensions WHERE name=".$db->quote('com_sef');
        $db->setQuery($query);
        $extId = $db->loadResult();
        if (!is_null($extId)) {
            $query = "SELECT COUNT(*) FROM #__schemas WHERE extension_id = ".$db->quote($extId);
            $db->setQuery($query);
            $cnt = $db->loadResult();
            if ($cnt == 0) {
                $query = "INSERT INTO #__schemas SET version_id = ".$db->quote('4.1.0').", extension_id = ".$db->quote($extId);
                $db->setQuery($query);
                $db->query();
            }
        }
    }
    
    public function postflight($action, $installer)
    {
        if ($action == 'install') {
            $db =& JFactory::getDBO();

            // Get component ID
            $db->setQuery("SELECT `extension_id` FROM `#__extensions` WHERE `type` = 'component' AND `element` = 'com_sef'");
            $id = $db->loadResult();
            if (!$id) {
                return;
            }

            // Fix separator links
            $db->setQuery("UPDATE `#__menu` SET `link` = '#' WHERE `type` = 'component' AND `img` = 'separator' AND `component_id` = '{$id}'");
            $db->query();
        }
        if($action=='update') {
            $tmp_path=JFactory::getApplication()->getCfg('tmp_path');
            if(JFile::exists($tmp_path.'/joomsef-configuration.php')) {
                JFile::copy($tmp_path.'/joomsef-configuration.php',JPATH_ADMINISTRATOR.'/components/com_sef/configuration.php');
                JFile::delete($tmp_path.'/joomsef-configuration.php');
            }
        }        
    }

    private function getElement($xml) {
        if(isset($xml->files)) {
            if (count($xml->files->children()))    {
                foreach ($xml->files->children() as $file)    {
                    if ((string)$file->attributes()->sef_ext) {
                        $element = (string)$file->attributes()->sef_ext;
                        if(substr($element,0,13)!='ext_joomsef4_') {
                            $element='ext_joomsef4_'.$element;
                        }
                        return $element;
                    }
                }
            }
        }
        return '';
    }
    
    function installPlugins() 
    {
        $db=JFactory::getDBO();
        
        // Install JoomSEF plugins
        $plugins=array();
        $plugins['content']=array('joomsef');
        $plugins['extension']=array('joomsefinstall');
        //$plugins['system']=array('joomsef','joomsefgoogle','joomseflang','joomsefurl');
        $plugins['system']=array('joomsef','joomsefgoogle','joomseflang');
        
        
        foreach($plugins as $type=>$items) {
            foreach($items as $item) {
                $src = JPATH_ADMINISTRATOR.'/components/com_sef/plugin/'.$type.'/'.$item.'/';
                $dest = JPATH_ROOT.'/plugins/'.$type.'/'.$item.'/';
                
                $res = JFolder::create($dest);
                $res = $res && JFile::copy($src.$item.'.php', $dest.$item.'.php');
                $res = $res && JFile::copy($src.$item.'.xml', $dest.$item.'.xml');
                
                $query="SELECT COUNT(*) \n";
                $query.="FROM #__extensions \n";
                $query.="WHERE type=".$db->quote('plugin')." AND element=".$db->quote($item)." AND folder=".$db->quote($type);
                $db->setQuery($query);
                $cnt=$db->loadResult();
                
                if($type=='system' && $item=='joomsef') {
                    $db->setQuery("SELECT MIN(`ordering`) FROM `#__extensions` WHERE `type` = 'plugin' AND `folder` = 'system'");
                    $min = $db->loadResult();
                    $min -= 10;
                }
                
                $xml=JApplicationHelper::parseXMLInstallFile($src.$item.'.xml');
                $cache=json_encode($xml);
                if($cnt==0) {
                    $db->setQuery("INSERT INTO `#__extensions`
                                   (name, type, element, folder, client_id, enabled, access, protected, manifest_cache, params, custom_data, system_data, checked_out, checked_out_time, ordering, state)
                                   VALUES ('".$xml["name"]."', 'plugin', '".$item."', '".$type."', 0, 1, 1, 0, '".$cache."', '{}', '', '', 0, '0000-00-00 00:00:00', '{".(isset($min)?$min:'')."}', 0)");
                    $res = $res && $db->query();
                } else {
                    $query="UPDATE #__extensions SET manifest_cache=".$db->quote($cache)." \n";
                    $query.="WHERE name=".$db->quote($xml["name"])." AND element=".$db->quote('plugin')." AND folder=".$db->quote($item);
                    $db->setQuery($query);
                    $res = $res && $db->query();
                }
    
                if (!$res) {
                    JError::raiseWarning(100, JText::_('COM_SEF_WARNING_PLUGIN_NOT_INSTALLED').': '.$src);
                }
                                        
            }
        }
    }

    public function install($installer)
    {
        require_once JPATH_ADMINISTRATOR.'/components/com_sef/classes/seftools.php';

        $db =& JFactory::getDBO();

        // Create the default 404 page if it doesn't already exist
        $db->setQuery("SELECT `id` FROM `#__content` WHERE `title` = '404'");
        $id = $db->loadResult();
        if (empty($id)) {
            // Get user ID
            $user = JFactory::getUser();
            $introtext = "<h1>404: Not Found</h1>\n<h2>Sorry, but the content you requested could not be found</h2>";
            $sql = 'INSERT INTO #__content (title, alias, introtext, `fulltext`, state, catid, created, created_by, publish_up, images, urls, ordering, metakey, metadesc, access, hits, language) '.
            'VALUES ("404", "404", "'.$introtext.'", "", "1", "0", "'.date('Y-m-d H:i:s').'", "'.$user->id.'", "'.date('Y-m-d H:i:s').'", "", "", "0", "", "", "1", "0", "*");';
            $db->setQuery($sql);
            $res = $db->query();
            if (!$res) {
                JError::raiseWarning(100, JText::_('COM_SEF_ERROR_DEFAULT_404_PAGE'));
            }
        }

        $this->installPlugins();           

        // 20.8.2013 dajo: adapters don't need installation anymore, they're registered using our system plugin
        // Install the extension installer adapter if possible
        /*$adapterSrc = JPATH_ADMINISTRATOR.'/components/com_sef/adapters/sef_ext.php';
        $adapterDest = JPATH_LIBRARIES.'/joomla/installer/adapters/sef_ext.php';
        $adapterInstalled = false;
        if( is_writable(dirname($adapterDest)) ) {
            if( @copy($adapterSrc, $adapterDest) ) {
                $adapterInstalled = true;
            }
        }

        $adapterSrc2 = JPATH_ADMINISTRATOR.'/components/com_sef/adapters/sef_update.php';
        $adapterDest2 = JPATH_LIBRARIES.'/joomla/updater/adapters/sef_update.php';
        $adapterInstalled2 = false;
        if( is_writable(dirname($adapterDest2)) ) {
            if( @copy($adapterSrc2, $adapterDest2) ) {
                $adapterInstalled2 = true;
            }
        }*/

        $ext_errs=array();
        $list=JFolder::files(JPATH_SITE.'/components/com_sef/sef_ext');
        JTable::addIncludePath(JPATH_LIBRARIES.'/joomla/database/table');
        foreach($list as $sef) {
            if ((substr($sef, -4) != '.xml') || (substr($sef, 0, 4) != 'com_')) {
                continue;
            }
            $xml=simplexml_load_file(JPATH_SITE.'/components/com_sef/sef_ext/'.$sef);
            $element=$this->getElement($xml);

            $query="SELECT COUNT(*) FROM #__extensions WHERE type=".$db->quote('sef_ext')." AND element=".$db->quote($element);
            $db->setQuery($query);
            $cnt=$db->loadResult();
            if($cnt>0) {
                continue;
            }

            $ext_table=JTable::getInstance('extension');
            $ext_table->name=(string)$xml->name;
            $ext_table->type='sef_ext';
            $ext_table->element=$element;
            $ext_table->enabled=1;
            $ext_table->protected=1; // Make core JoomSEF extensions protected
            $ext_table->access=1;
            $ext_table->client_id=0;
            if(isset($xml->install->defaultParams)) {
                $ext_table->params=SEFTools::getDefaultParams($xml->install->defaultParams);
            }
            if(isset($xml->install->defaultFilters)) {
                $ext_table->custom_data=SEFTools::getDefaultFilters($xml->install->defaultFilters);
            }
            $ext_table->manifest_cache=json_encode(JApplicationHelper::parseXMLInstallFile(JPATH_SITE.'/components/com_sef/sef_ext/'.$sef));
            if(!$ext_table->store()) {
                $ext_errs[]=$db->stderr(true);
            }

            $query="INSERT INTO #__schemas SET extension_id=".$ext_table->extension_id.", version_id=".$db->quote((string)$xml->version);
            $db->setQuery($query);
            $db->query();

            if(isset($xml->updateservers->server)) {
                $query="INSERT INTO #__update_sites SET name=".$db->quote((string)$xml->updateservers->server['name']).", type=".$db->quote((string)$xml->updateservers->server['type']).", \n";
                $query.="location=".$db->quote((string)$xml->updateservers->server).", enabled=1 \n";
                $db->setQuery($query);
                $db->query();

                $id=$db->insertId();

                $query="INSERT INTO #__update_sites_extensions SET update_site_id=".$id.", extension_id=".$ext_table->extension_id." \n";
                $db->setQuery($query);
                $db->query();
            }
        }

        // Check former AceSEF and sh404SEF installations
        $formerSEF = false;

        $tables=$db->getTableList();
        $prefix=JFactory::getApplication()->getCfg('dbprefix');
        
        // AceSEF
        if(in_array(str_replace('#__',$prefix,'#__acesef_urls'),$tables)) {
            $query="SELECT COUNT(*) FROM #__acesef_urls \n";
                $db->setQUery($query);
                $cnt=$db->loadResult();
                if($cnt>0) {
                    $formerSEF = true;
                }
        }
        
        // sh404SEF
        if( !$formerSEF ) {
            if(in_array(str_replace('#__',$prefix,'#__sh404sef_urls'),$tables)) {
                $query="SELECT COUNT(*) FROM #__sh404sef_urls \n";
                $db->setQUery($query);
                $cnt=$db->loadResult();
                if($cnt>0) {
                    $formerSEF = true;
                }
            }
        }
        
        ob_start();

        echo '<div class="quote" style="text-align: center;">';
        echo '<h1>'.JText::_('COM_SEF_INSTALL_SUCCESS').'</h1>';

        if ($formerSEF) {
            echo '<h3>'.JText::sprintf('COM_SEF_INSTALL_FORMER_SEF', '<a href="index.php?option=com_sef&amp;controller=sefurls&amp;task=showimport">', '</a>').'</h3><br />';
        }

        if(count($ext_errs)) {
            ?>
            <p class="message">
            <?php echo JText::_('COM_SEF_INSTALL_EXT_ERRS'); ?>
            <br />
            <?php echo implode("<br />", $ext_errs); ?>
            </p>
            <?php
        }


        // Check the host and set handling to redirect to www if needed
        $uri =& JURI::getInstance();
        $host = $uri->getHost();
        if (substr($host, 0, 4) == 'www.') {
            // Import the SEF config file
            if (!class_exists('SEFConfig')) {
                include(JPATH_ADMINISTRATOR.'/components/com_sef/classes/config.php');
            }
            $sefConfig =& SEFConfig::getConfig();
            $sefConfig->wwwHandling = _COM_SEF_WWW_USE_WWW;
            $sefConfig->saveConfig();
        }

        echo '<h3>'.JText::_('COM_SEF_INSTALL_ACTIVATE_CONFIG').'</h3>';
        $readdocs = '<p class="message">'.JText::sprintf('COM_SEF_INSTALL_EXTRA_CONFIG', '<a href="http://www.artio.net/joomsef/documentation" target="_blank">', '</a>');
        if (strpos($_SERVER['SERVER_SOFTWARE'], 'Microsoft-IIS') !== false) {
            echo $readdocs.'IIS.</p>';
        }
        else {
            // Get the correct rewrite base
            $base = JURI::root(true);
            if( $base == '' ) {
                $base = '/';
            }

            // Create htaccess content
            $htaccess = '
DirectoryIndex index.php
RewriteEngine On
RewriteBase ' . $base . '

########## Begin - Rewrite rules to block out some common exploits
## If you experience problems on your site block out the operations listed below
## This attempts to block the most common type of exploit `attempts` to Joomla!
#
## Deny access to extension xml files (uncomment out to activate)
#<Files ~ "\.xml$">
#Order allow,deny
#Deny from all
#Satisfy all
#</Files>
## End of deny access to extension xml files
# Block out any script trying to set a mosConfig value through the URL
RewriteCond %{QUERY_STRING} mosConfig_[a-zA-Z_]{1,21}(=|\%3D) [OR]
# Block out any script trying to base64_encode crap to send via URL
RewriteCond %{QUERY_STRING} base64_encode.*\(.*\) [OR]
# Block out any script that includes a <script> tag in URL
RewriteCond %{QUERY_STRING} (\<|%3C).*script.*(\>|%3E) [NC,OR]
# Block out any script trying to set a PHP GLOBALS variable via URL
RewriteCond %{QUERY_STRING} GLOBALS(=|\[|\%[0-9A-Z]{0,2}) [OR]
# Block out any script trying to modify a _REQUEST variable via URL
RewriteCond %{QUERY_STRING} _REQUEST(=|\[|\%[0-9A-Z]{0,2})
# Send all blocked request to homepage with 403 Forbidden error!
RewriteRule ^(.*)$ index.php [F,L]
#
########## End - Rewrite rules to block out some common exploits

RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_URI} !^/index.php
RewriteCond %{REQUEST_URI} (/|\.php|\.html|\.htm|\.feed|\.pdf|\.raw|/[^.]*)$  [NC]
RewriteRule (.*) index.php
RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization},L]
';

            //if (substr(PHP_OS, 0, 3) == 'WIN') {
            //    echo '<p class="error">Found apache on windows, .htaccess is an illegal filename for this system.<br/>You must complete the rest of the configuration manually.</p>';
            //    echo $readdocs."the apache .htaccess file.</p>";
            //}
            //else{
            echo '<p style="text-align: center;">'.JText::_('COM_SEF_INSTALL_HTACCESS_CHECK').'<br />';
            $file = JPATH_ROOT.'/.htaccess';
            if( !JFile::exists($file) ) {
                echo JText::_('COM_SEF_INSTALL_HTACCESS_NOT_FOUND').'</p>';

                if( !JFile::write($file, $htaccess) ) {
                    echo '<p style="text-align: center;" class="error">'.JText::_('COM_SEF_INSTALL_HTACCESS_NOT_CREATED').'<br/><pre>'.htmlspecialchars(nl2br($htaccess)).'</pre>';
                }
                else{
                    echo JText::_('COM_SEF_INSTALL_HTACCESS_CREATED').'<br/><pre>'.htmlspecialchars($htaccess).'</pre>';
                }
                echo JText::_('COM_SEF_INSTALL_HTACCESS_REWRITEBASE');
            }
            else {
                echo '<span class="error">'.JText::_('COM_SEF_INSTALL_HTACCESS_FOUND').'</span></p>';
                echo $readdocs.JText::_('COM_SEF_INSTALL_EXTRA_CONFIG_APACHE').'</p>';
            }
            echo '</div>';
        }

        ?>
        <h2 style="color: #A1B754"><?php echo JText::_('COM_SEF_INSTALL_START'); ?></h2>
        <div id="cpanel" class="icons">
            <div class="icon">
                <a href="index.php?option=com_sef" title="<?php echo JText::_('COM_SEF_TT_CONTROL_PANEL'); ?>">
                <img src="components/com_sef/assets/images/icon-48-artio.png" alt="" width="48" height="48" border="0"/>
                <span><?php echo JText::_('COM_SEF_CONTROL_PANEL'); ?></span>
                </a>
            </div>
            <div class="icon">
                <a href="http://www.artio.net/joomsef/documentation" target="_blank" title="<?php echo JText::_('COM_SEF_TT_DOCUMENTATION'); ?>">
                    <img src="components/com_sef/assets/images/icon-48-docs.png" alt="" width="48" height="48" align="middle" border="0"/>
                    <span><?php echo JText::_('COM_SEF_DOCUMENTATION'); ?></span>
                </a>
            </div>
            <div class="icon">
                <a href="index.php?option=com_sef&amp;controller=info&amp;task=changelog" title="<?php echo JText::_('COM_SEF_TT_CHANGELOG'); ?>">
                    <img src="components/com_sef/assets/images/icon-48-info.png" alt="" width="48" height="48" align="middle" border="0"/>
                    <span><?php echo JText::_('COM_SEF_CHANGELOG'); ?></span>
                </a>
            </div>
            <div class="icon">
                <a href="index.php?option=com_sef&amp;controller=info&amp;task=help" title="<?php echo JText::_('COM_SEF_TT_SUPPORT'); ?>">
                    <img src="components/com_sef/assets/images/icon-48-help.png" alt="" width="48" height="48" align="middle" border="0"/>
                    <span><?php echo JText::_('COM_SEF_SUPPORT'); ?></span>
                </a>
            </div>
        </div>

        <?php

        $output = ob_get_contents();
        ob_end_clean();

        echo $output;

        return true;
    }

    function update() {
        require_once JPATH_ADMINISTRATOR.'/components/com_sef/classes/config.php';
        $db = JFactory::getDBO();
        $jversion = new JVersion();

        // 20.8.2013 dajo: adapters don't need installation anymore, they're registered using our system plugin,
        //                 so remove them from older versions
        $path = JPATH_LIBRARIES.'/joomla/installer/adapters/sef_ext.php';
        if (JFile::exists($path)) {
            JFile::delete($path);
        }
        // Handle Joomla 3
        if ($jversion->isCompatible('3.0.0')) {
            $path = JPATH_LIBRARIES.'/cms/installer/adapter/sef_ext.php';
            if (JFile::exists($path)) {
                JFile::delete($path);
            }
        }
        $path = JPATH_LIBRARIES.'/joomla/updater/adapters/sef_update.php';
        if (JFile::exists($path)) {
            JFile::delete($path);
        }
        
        // Install JoomSEF plugins
        $this->installPlugins();

        JTable::addIncludePath(JPATH_LIBRARIES.'/joomla/database/table');

        $ext_update_dir=dirname(__FILE__).'/site/sef_ext/';
        // Migrate existing extensions from old table
        $db=JFactory::getDBO();
        $query="SELECT * FROM #__sefexts";
        $db->setQuery($query);
        $exts=$db->loadObjectList();
        
        // Delete extensions from old table, so they don't cause problems later
        $db->setQuery("DELETE FROM #__sefexts");
        $db->query();
        
        for($i=0;$i<count($exts);$i++) {
            $xml_file=JPATH_SITE.'/components/com_sef/sef_ext/'.$exts[$i]->file;
            if(file_exists($ext_update_dir.$exts[$i]->file)) {
                $xml_file=$ext_update_dir.$exts[$i]->file;
            }
            
            if (!file_exists($xml_file)) {
                // Extension not available
                continue;
            }
            
            $xml=simplexml_load_file($xml_file);
            $element=$this->getElement($xml);

            $query="SELECT COUNT(*) FROM #__extensions WHERE type=".$db->quote('sef_ext')." AND element=".$db->quote($element);
            $db->setQuery($query);
            $cnt=$db->loadResult();
            if($cnt>0) {
                continue;
            }

            $ext_table=JTable::getInstance('extension');
            $ext_table->name=(string)$xml->name;
            $ext_table->type='sef_ext';
            $ext_table->element=$element;
            $ext_table->enabled=1;
            $ext_table->protected=0;
            $ext_table->access=1;
            $ext_table->client_id=0;
            $params=new JRegistry($exts[$i]->params);
            $download_id=$params->get('downloadId');
            if(strlen($exts[$i]->title)) {
                $params->def('custom_menu_title',$exts[$i]->title);
            }
            $ext_table->params=$params->toString();
            $ext_table->custom_data=$exts[$i]->filters;
            $ext_table->manifest_cache=json_encode(JApplicationHelper::parseXMLInstallFile($xml_file));
            $ext_table->store();

            $query="INSERT INTO #__schemas SET extension_id=".$ext_table->extension_id.", version_id=".$db->quote((string)$xml->version);
            $db->setQuery($query);
            $db->query();

            if(isset($xml->updateservers->server)) {
                $location=(string)$xml->updateservers->server;
                if(isset($download_id) && strlen($download_id)) {
                    $location=str_replace('.xml','-'.$download_id.'.xml',$location);
                }
                $query="SELECT COUNT(*) FROM #__update_sites \n";
                $query.="WHERE type=".$db->quote((string)$xml->updateservers->server['type'])." AND name=".$db->quote((string)$xml->updateservers->server['name']);
                $db->setQuery($query);
                $cnt=$db->loadResult();
                if($cnt) {
                    $query="UPDATE #__update_sites SET location=".$db->quote($location).", enabled=1 \n";
                    $query.="WHERE type=".$db->quote((string)$xml->updateservers->server['type'])." AND name=".$db->quote((string)$xml->updateservers->server['name']);
                } else {
                    $query="INSERT INTO #__update_sites SET name=".$db->quote((string)$xml->updateservers->server['name']).", type=".$db->quote((string)$xml->updateservers->server['type']).", \n";
                    $query.="location=".$db->quote($location).", enabled=1 \n";    
                }
                $db->setQuery($query);
                $db->query();

                $id=$db->insertId();
                
                $query="SELECT COUNT(*) FROM #__update_sites_extensions \n";
                $query.="WHERE update_site_id=".$id;
                $db->setQuery($query);
                $cnt=$db->loadResult();
                
                if($cnt) {
                    $query="UPDATE #__update_sites_extensions \n";
                    $query.="SET extension_id=".$ext_table->extension_id." \n";
                    $query.="WHERE update_site_id=".$id;
                } else {
                    $query="INSERT INTO #__update_sites_extensions SET update_site_id=".$id.", extension_id=".$ext_table->extension_id." \n";    
                }
                $db->setQuery($query);
                $db->query();
            }
        }

        //Add existing extensions to Joomla extensions table
        $list=JFolder::files(JPATH_SITE.'/components/com_sef/sef_ext');

        foreach($list as $sef) {
            if(substr($sef,-4)!='.xml') {
                continue;
            }
            $xml_file=JPATH_SITE.'/components/com_sef/sef_ext/'.$sef;
            if(file_exists($ext_update_dir.$sef)) {
                $xml_file=$ext_update_dir.$sef;
            }
            $xml=simplexml_load_file($xml_file);
            $element=$this->getElement($xml);
            $query="SELECT COUNT(*) FROM #__extensions WHERE type=".$db->quote('sef_ext')." AND element=".$db->quote($element);
            $db->setQuery($query);
            $cnt=$db->loadResult();
            if($cnt>0) {
                continue;
            }
            $ext_table=JTable::getInstance('extension');
            $ext_table->name=(string)$xml->name;
            $ext_table->type='sef_ext';
            $ext_table->element=$element;
            $ext_table->enabled=1;
            $ext_table->protected=0;
            $ext_table->access=1;
            $ext_table->client_id=0;
            if(isset($xml->install->defaultparams)) {
                $ext_table->params=SEFTools::getDefaultParams((string)$xml->install->defaultparams);
            }
            if(isset($xml->install->defaultfilters)) {
                $ext_table->custom_data=SEFTools::getDefaultFilters((string)$xml->install->defaultfilters);
            }
            $ext_table->manifest_cache=json_encode(JApplicationHelper::parseXMLInstallFile($xml_file));
            $ext_table->store();

            $query="INSERT INTO #__schemas SET extension_id=".$ext_table->extension_id.", version_id=".$db->quote((string)$xml->version);
            $db->setQuery($query);
            $db->query();

            if(isset($xml->updateservers->server)) {
                $query="INSERT INTO #__update_sites SET name=".$db->quote((string)$xml->updateservers->server['name']).", type=".$db->quote((string)$xml->updateservers->server['type']).", \n";
                $query.="location=".$db->quote((string)$xml->updateservers->server).", enabled=1 \n";
                $db->setQuery($query);
                $db->query();

                $id=$db->insertId();

                $query="INSERT INTO #__update_sites_extensions SET update_site_id=".$id.", extension_id=".$ext_table->extension_id." \n";
                $db->setQuery($query);
                $db->query();
            }
        }
        
        $fields=$db->getTableColumns('#__sefurls');
        $fields=array_keys($fields);
        if(!in_array('metaauthor',$fields)) {
            $query="ALTER TABLE #__sefurls \n";
            $query.="ADD (metaauthor varchar(30) default '') \n";
            $db->setQuery($query);
            $db->query();
        }
        
        // 30.11.2012 dajo: Move stored configuration from params to custom_data field
        $db->setQuery("UPDATE `#__extensions` SET `custom_data` = `params` WHERE `type` = 'component' AND `element` = 'com_sef' AND (`custom_data` IS NULL OR `custom_data` = '') LIMIT 1");
        $db->query();
        
        // 2.1.2013 dajo: Remove left-over file
        if (JFile::exists(JPATH_ADMINISTRATOR.'/components/com_sef/tables/extension.php')) {
            JFile::delete(JPATH_ADMINISTRATOR.'/components/com_sef/tables/extension.php');
        }
        
        ob_start();
        $tmp_path=JFactory::getApplication()->getCfg('tmp_path');
        if(JFile::exists($tmp_path.'/joomsef-configuration.php')) {
            require_once $tmp_path.'/joomsef-configuration.php';
        }
        if(isset($artioDownloadId) && strlen($artioDownloadId)) {
            $query="SELECT location FROM #__update_sites \n";
            $query.="WHERE name=".$db->quote('com_joomsef');
            $db->setQuery($query);
            $location=$db->loadResult();
            if(!preg_match("/(-([A-Za-z0-9]*)).xml/",$location)) {
                ?>
                <p class="message">
                It was found, that you have an commercial version of Artio JoomSEF and you dont have migrated your download id. Please finish upgrade by clicking <a href="index.php?option=com_sef&task=finish_upgrade">here</a>
                </p>
                <?php
            }
        }
        $output = ob_get_contents();
        ob_end_clean();
        
        echo $output;
        
        return true;
    }

    public function uninstall($installer)
    {
        // uninstall JoomSEF plugin
        $path = JPATH_ROOT.'/plugins/system/joomsef';

        $res = JFolder::delete($path);

        $db =& JFactory::getDBO();
        $db->setQuery("DELETE FROM `#__extensions` WHERE `type` = 'plugin' AND `folder` = 'system' AND `element` = 'joomsef' LIMIT 1");
        $res = $res && $db->query();

        if (!$res) {
            JError::raiseWarning(100, JText::_('COM_SEF_WARNING_PLUGIN_NOT_UNINSTALLED'));
        }

        /*$path = JPATH_ROOT.'/plugins/system/joomsefurl';

        $res = JFolder::delete($path);
        
        $db =& JFactory::getDBO();
        $db->setQuery("DELETE FROM `#__extensions` WHERE `type` = 'plugin' AND `folder` = 'system' AND `element` = 'joomsefurl' LIMIT 1");
        $res = $res && $db->query();

        if (!$res) {
            JError::raiseWarning(100, JText::_('COM_SEF_WARNING_PLUGIN_NOT_UNINSTALLED'));
        }*/

        $path = JPATH_ROOT.'/plugins/content/joomsef';

        $res = JFolder::delete($path);
        
        $db =& JFactory::getDBO();
        $db->setQuery("DELETE FROM `#__extensions` WHERE `type` = 'plugin' AND `folder` = 'content' AND `element` = 'joomsef' LIMIT 1");
        $res = $res && $db->query();

        if (!$res) {
            JError::raiseWarning(100, JText::_('COM_SEF_WARNING_PLUGIN_NOT_UNINSTALLED'));
        }

        $path = JPATH_ROOT.'/plugins/system/joomseflang';

        $res = JFolder::delete($path);

        $db =& JFactory::getDBO();
        $db->setQuery("DELETE FROM `#__extensions` WHERE `type` = 'plugin' AND `folder` = 'system' AND `element` = 'joomseflang' LIMIT 1");
        $res = $res && $db->query();

        if (!$res) {
            JError::raiseWarning(100, JText::_('COM_SEF_WARNING_PLUGIN_NOT_UNINSTALLED'));
        }

        $path = JPATH_ROOT.'/plugins/system/joomsefgoogle';

        $res = JFolder::delete($path);

        $db =& JFactory::getDBO();
        $db->setQuery("DELETE FROM `#__extensions` WHERE `type` = 'plugin' AND `folder` = 'system' AND `element` = 'joomsefgoogle' LIMIT 1");
        $res = $res && $db->query();

        if (!$res) {
            JError::raiseWarning(100, JText::_('COM_SEF_WARNING_PLUGIN_NOT_UNINSTALLED'));
        }

        $path = JPATH_ROOT.'/plugins/extension/joomsefinstall';

        $res = JFolder::delete($path);

        $db =& JFactory::getDBO();
        $db->setQuery("DELETE FROM `#__extensions` WHERE `type` = 'plugin' AND `folder` = 'extension' AND `element` = 'joomsefinstall' LIMIT 1");
        $res = $res && $db->query();

        if (!$res) {
            JError::raiseWarning(100, JText::_('COM_SEF_WARNING_PLUGIN_NOT_UNINSTALLED'));
        }

        $query="SELECT extension_id FROM #__extensions \n";
        $query.="WHERE type=".$db->quote('sef_ext');
        $db->setQuery($query);
        $exts=$db->loadColumn();

        $query="DELETE FROM #__extensions WHERE type=".$db->quote('sef_ext');
        $db->setQuery($query);
        $db->query();

        $query="DELETE FROM #__update_sites WHERE type=".$db->quote('sef_update');
        $db->setQuery($query);
        $db->query();

        if (count($exts) > 0) {
            $query="DELETE FROM #__update_sites_extensions \n";
            $query.="WHERE extension_id IN(".implode(",",$exts).")";
            $db->setQuery($query);
            $db->query();
            
            $query = "DELETE FROM #__schemas WHERE extension_id IN (".implode(',', $exts).")";
            $db->setQuery($query);
            $db->query();
        }

        echo '<h3>'.JText::_('COM_SEF_UNINSTALLED').'</h3>';

        return true;
    }
}
?>