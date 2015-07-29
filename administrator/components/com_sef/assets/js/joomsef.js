/*<BUILD_TAG>*/

var JoomSEF = {
    txtHomePage: '',
    
    ajaxItemTask: function(container, id, task) {
        var containerId = 'sef_' + container + '_';
        JoomSEF.ajaxShowElement(containerId + id, 'working');
        
        var controller = null;
        var controllerEl = document.adminForm.controller;
        if (controllerEl) {
            controller = controllerEl.value;
        }
        var postData = {
            option: 'com_sef',
            task: task,
            cid: [id],
            ajax: '1'
        };
        if (controller != null)
            postData.controller = controller;
        
        new Request.JSON({
            url: 'index.php',
            method: 'POST',
            data: postData,
            onSuccess: function(data, text) {
                for (var i = 0; i < data.length; i++) {
                    JoomSEF.ajaxShowElement(containerId + data[i].id, data[i].newValue);
                }
            }
        }).send();
    },
    
    ajaxShowElement: function(containerId, visibleId) {
        var els = document.getElementById(containerId);
        if (!els) {
            return;
        }
        
        var showId = containerId + '_' + visibleId;
        var nodes = els.childNodes;
        for (var i = 0; i < nodes.length; i++) {
            var node = nodes[i];
            if (!node.id) {
                continue;
            }
            
            node.style.display = (node.id == showId) ? 'block' : 'none';
        }
    },
    
    ajaxEditOrigurl: function(id) {
        var origurl = $('sef_origurl_' + id + '_spn').innerHTML;
        origurl = origurl.replace(/&amp;/g, '&');
        var Itemid = '';
        
        var pos = origurl.indexOf('Itemid=');
        if (pos >= 0) {
            Itemid = origurl.substr(pos + 7);
            origurl = origurl.substr(0, pos - 1);
        }
        
        $('sef_origurl_' + id + '_url').value = origurl;
        $('sef_origurl_' + id + '_itemid').value = Itemid;
        JoomSEF.ajaxShowElement('sef_origurl_' + id, 'edit');
    },
    
    ajaxEditSefurl: function(id) {
        var url = $('sef_sefurl_' + id + '_spn').innerHTML;
        url = url.replace(/^\s+|\s+$/g, ''); // Trim
        
        // Handle homepage
        if (url.substr(0, 1) == '(') {
            url = '';
        }
        
        $('sef_sefurl_' + id + '_url').value = url;
        JoomSEF.ajaxShowElement('sef_sefurl_' + id, 'edit');
    },
    
    ajaxSaveOrigurl: function(id) {
        var containerId = 'sef_origurl_' + id;
        JoomSEF.ajaxShowElement(containerId, 'working');
        
        var postData = {
            option: 'com_sef',
            controller: 'sefurls',
            task: 'setOrigurl',
            cid: [id],
            ajax: '1',
            origurl: $('sef_origurl_' + id + '_url').value,
            Itemid: $('sef_origurl_' + id + '_itemid').value
        };
        new Request.JSON({
            url: 'index.php',
            method: 'POST',
            data: postData,
            onSuccess: function(data, text) {
                if (!data.success) {
                    JoomSEF.ajaxShowElement(containerId, 'edit');
                    alert(data.msg);
                }
                else {
                    $('sef_origurl_' + id + '_spn').innerHTML = data.origurl.replace(/&/g, '&amp;');
                    JoomSEF.ajaxShowElement(containerId, 'txt');
                }
            }
        }).send();
    },
    
    ajaxSaveSefurl: function(id) {
        var containerId = 'sef_sefurl_' + id;
        JoomSEF.ajaxShowElement(containerId, 'working');
        
        var postData = {
            option: 'com_sef',
            controller: 'sefurls',
            task: 'setSefurl',
            cid: [id],
            ajax: '1',
            sefurl: $('sef_sefurl_' + id + '_url').value
        };
        new Request.JSON({
            url: 'index.php',
            method: 'POST',
            data: postData,
            onSuccess: function(data, text) {
                if (!data.success) {
                    JoomSEF.ajaxShowElement(containerId, 'edit');
                    alert(data.msg);
                }
                else {
                    $('sef_sefurl_' + id + '_spn').innerHTML = (data.sefurl == '') ? JoomSEF.txtHomePage : data.sefurl;
                    JoomSEF.ajaxShowElement(containerId, 'txt');
                }
            }
        }).send();
    }
};
