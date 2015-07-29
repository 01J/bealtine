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

jimport('joomla.plugin.plugin');
require_once JPATH_SITE.'/components/com_sef/joomsef.php';
require_once JPATH_ADMINISTRATOR.'/components/com_sef/classes/config.php';
require_once JPATH_ADMINISTRATOR.'/components/com_sef/helpers/ipaddress.php';

class plgSystemJoomSEFGoogle extends JPlugin {
	function __construct(&$subject,$config) {
		parent::__construct($subject,$config);
	}
	
	function onAfterDispatch() {	
        if(JFactory::getApplication()->isAdmin()) {
			return;
		}
		if(JFactory::getApplication()->getCfg('sef')==0) {
			return;
		}
		if(JFactory::getURI()->getVar('tmpl')=='component') {
			return;
		}
		
		$config=SEFConfig::getConfig();
        if (!$config->enabled) {
            return;
        }
		if($config->google_enable==0) {
			return;
		}
		
		if(JRequest::getInt('google_analytics_exclude',0,'cookie')==1) {
			return;
		}
		
		$ips_exclude=explode("\r\n",$config->google_exclude_ip);
		if(in_array(IPAddressHelper::getip(),$ips_exclude)) {
			return;
		}
		
        $groups = null;
        $user = JFactory::getUser();
        if ($user) {
            $groups = $user->get('groups');
        }
		if (is_array($groups)) {
            foreach ($groups as $group) {
                if (in_array($group, $config->google_exclude_level)) {
                    return;
                }
            }
        }
		
        $script = "  (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,'script','//www.google-analytics.com/analytics.js','ga');

  ga('create', '".htmlspecialchars($config->google_id)."', 'auto');\n";
        if ($config->google_demographic_reports) {
            $script .= "  ga('require', 'displayfeatures');\n";
        }
        if ($config->google_link_attribution) {
            $script .= "  ga('require', 'linkid', 'linkid.js');\n";
        }
        $script .= "  ga('send', 'pageview');\n";
        
		JFactory::getDocument()->addScriptDeclaration($script);
	}
}
?>