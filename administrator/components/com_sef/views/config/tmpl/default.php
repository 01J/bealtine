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
        <?php
		$config =& JFactory::getConfig();
		$sefConfig =& SEFConfig::getConfig();
		$lists = $this->lists;
		
        if (!$config->get('sef')) {
			JError::raiseNotice('100', JText::sprintf('COM_SEF_INFO_SEF_DISABLED', '<a href="index.php?option=com_config">', '</a>'));
		}
		$x = 0;
	    ?>
	    <script language="Javascript">
	    Joomla.submitbutton = function(pressbutton) {
	        <?php
	        jimport( 'joomla.html.editor' );
	        $editor =& JFactory::getEditor();
	        echo $editor->save('introtext');
	        ?>
            
            // Check duplicate subdomains
            // Get all subdomain[title] elements
            var eles = [];
            var inputs = document.getElementsByTagName('input');
            for (var i = 0; i < inputs.length; i++) {
                if (inputs[i].name.indexOf('subdomain[title]') == 0) {
                    eles.push(inputs[i]);
                }
            }
            
            // Check whether there are duplicate subdomains
            for (var i = 0; i < eles.length; i++) {
                var value = eles[i].value;
                for (var j = i + 1; j < eles.length; j++) {
                    if (value == eles[j].value) {
                        // There are duplicate subdomains
                        alert('You have two or more duplicate subdomains configured.');
                        return false;
                    }
                }
            }
            
	        Joomla.submitform(pressbutton);
	    }
        
        var jsSubdomainMenus=new Array();
        <?php
        foreach($this->lists["subdomains_menus"] as $lang=>$menu) {
        	?>
        	jsSubdomainMenus["<?php echo $lang; ?>"]=<?php echo json_encode($menu); ?>;
        	<?php
        }
        ?>
        var jsSubdomainRemove = <?php echo json_encode($this->lists['subdomains_remove']); ?>;
        
        var jsSubdomainNextId = {};
        <?php
        foreach ($this->subdomains as $lng => $subs) {
            echo "jsSubdomainNextId['{$lng}'] = ".count($subs).";\n";
        }
        ?>
	    
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
	        td1.width = '200';
	        td1.innerHTML = '<input type="text" value="" size="40" name="metanames[]" />';
	        var td2 = document.createElement('td');
	        td2.width = '200';
	        td2.innerHTML = '<input type="text" value="" size="60" name="metacontents[]" />';
	        var td3 = document.createElement('td');
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
        
        function setcookie (name, value, expires, path, domain, secure) {
		    // http://kevin.vanzonneveld.net
		    // +   original by: Jonas Raoni Soares Silva (http://www.jsfromhell.com)
		    // +   bugfixed by: Andreas
		    // +   bugfixed by: Onno Marsman
		    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
		    // -    depends on: setrawcookie
		    // *     example 1: setcookie('author_name', 'Kevin van Zonneveld');
		    // *     returns 1: true
		    return setrawcookie(name, encodeURIComponent(value), expires, path, domain, secure);
		}
		        
        
        function setrawcookie (name, value, expires, path, domain, secure) {
		    // http://kevin.vanzonneveld.net
		    // +   original by: Brett Zamir (http://brett-zamir.me)
		    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
		    // +   derived from: setcookie
		    // +   input by: Michael
		    // +   bugfixed by: Brett Zamir (http://brett-zamir.me)
		    // *     example 1: setcookie('author_name', 'Kevin van Zonneveld');
		    // *     returns 1: true
		    if (typeof expires === 'string' && (/^\d+$/).test(expires)) {
		        expires = parseInt(expires, 10);
		    }
		
		    if (expires instanceof Date) {
		        expires = expires.toGMTString();
		    } else if (typeof(expires) === 'number') {
		        expires = (new Date(expires * 1e3)).toGMTString();
		    }
		
		    var r = [name + '=' + value],
		        s = {},
		        i = '';
		    s = {
		        expires: expires,
		        path: path,
		        domain: domain
		    };
		    for (i in s) {
		        if (s.hasOwnProperty(i)) { // Exclude items on Object.prototype
		            s[i] && r.push(i + '=' + s[i]);
		        }
		    }
		
		    return secure && r.push('secure'), this.window.document.cookie = r.join(";"), true;
		}
	        
        
        function set_cookie() {
        	setcookie('google_analytics_exclude',1,<?php echo time()+(60*60*24*30); ?>,'/');
        	$('set_google_cookie').style.display="none";
        	$('remove_google_cookie').style.display="";
        }
        
        function remove_cookie() {
        	setcookie('google_analytics_exclude',0,<?php echo time()-(60*60*24*30); ?>,'/');
        	$('set_google_cookie').style.display="";
        	$('remove_google_cookie').style.display="none";
        }
        
        function add_subdomain(lang) {
        	var table=document.getElementById('subdomains_tbl_'+lang);
        	var tr=table.insertRow(table.rows.length - 1);
        	var td=tr.insertCell(0);
            td.vAlign = 'top';
        	td.appendChild(new Element('input',{
        		'type':'text',
        		'class':'inputbox',
        		'name':'subdomain[title]['+lang+']['+jsSubdomainNextId[lang]+']',
        		'size':10
        	}));
        	td.appendChild(document.createTextNode('.<?php echo $this->rootDomain; ?>'));
            
        	var td1=tr.insertCell(1);
        	td1.vAlign = 'top';
			td1.innerHTML=jsSubdomainMenus[lang];
			td1.getElementsByTagName('select')[0].setAttribute('name',td1.getElementsByTagName('select')[0].getAttribute('name').replace("subdomain_Itemid","subdomain[Itemid]["+lang+"]["+jsSubdomainNextId[lang]+"][]"));
			td1.getElementsByTagName('select')[0].setAttribute('multiple','mupltiple');
			td1.getElementsByTagName('select')[0].setAttribute('size','10');
            
        	var td2=tr.insertCell(2);
            td2.vAlign = 'top';
			td2.innerHTML=jsSubdomainMenus[lang];
			td2.getElementsByTagName('select')[0].setAttribute('name',td2.getElementsByTagName('select')[0].getAttribute('name').replace("subdomain_Itemid","subdomain[titlepage]["+lang+"]["+jsSubdomainNextId[lang]+"]"));
			
            var td3=tr.insertCell(3);
            td3.vAlign='top';
            td3.innerHTML=jsSubdomainRemove;
            
            jsSubdomainNextId[lang] = jsSubdomainNextId[lang] + 1;
        }
        
        function remove_subdomain(obj) {
            if (!obj) {
                return;
            }
            var row = obj.getParent('tr');
            if (!row) {
                return;
            }
            var table = row.getParent('tbody');
            if (!table) {
                return;
            }
            
            table.removeChild(row);
        }
        
        function disableLanguagePlugin(btn) {
            // Disable button
            btn.disabled = true;
            
            // Show the progress animation
            $('sefAjaxProgressImg').style.display = '';
            
            // Send request
            new Request.JSON({
                'url': 'index.php?option=com_sef&controller=config&task=disable_plugin&tmpl=component',
                'method': 'post',
                'onSuccess': function(json, text) {
                    if (json.success) {
                        $('sefAjaxProgressImg').style.display = 'none';
                        $('sefConfigLanguageMsg').style.display = 'none';
                        $('sefConfigLanguageConfig').style.display = '';
                    }
                }
            }).send();
        }
	    </script>
		
		<?php
		if (!is_null($this->tab)) {
		    JRequest::setVar('jpanetabs_sef-config-tabs', $this->tab, 'cookie');
		}
		//$tabs = array('default','advanced','cache','metatags','seo','sitemap','language','analytics','subdomains','404','registration');
		echo JHtml::_('tabs.start', 'sef-config-tabs', array('startOffset' => $this->tab, 'useCookie' => true));
		foreach($this->tabs as $tab) {
			echo $this->loadTemplate($tab);	
		}
		echo JHtml::_('tabs.end');
		?>
<input type="hidden" name="id" value="" />
<input type="hidden" name="section" value="config" />
<input type="hidden" name="option" value="com_sef" />
<input type="hidden" name="task" value="" />
<input type="hidden" name="controller" value="config" />
<input type="hidden" name="statusType" value="" />
<input type="hidden" name="return" value="index.php?option=com_sef&amp;controller=config&amp;task=edit" />
<?php echo JHTML::_('form.token'); ?>
</form>
