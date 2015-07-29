function jsCronGetRadioValue(radioObj)
{
    if (!radioObj) {
        return false;
    }
    
    var radioLength = radioObj.length;
    if (radioLength == undefined) {
        if (radioObj.checked)
            return radioObj.value;
        else
            return false;
    }
    
    for (var i = 0; i < radioLength; i++) {
        if (radioObj[i].checked) {
            return radioObj[i].value;
        }
    }
    
    return false;
}

function jsCronSetRadioGroupDisabled(radioObj, state)
{
    if (!radioObj) {
        return;
    }
    
    var radioLength = radioObj.length;
    if (radioLength == undefined) {
        radioObj.disabled = state;
    }
    
    for (var i = 0; i < radioLength; i++) {
        radioObj[i].disabled = state;
    }
}

function jsCronUpdateFields()
{
    if (jsCronGetRadioValue(document.adminForm.cronUpdateUrls) == '1') {
        // Disable meta tags and sitemap
        jsCronSetRadioGroupDisabled(document.adminForm.cronUpdateMeta, true);
        jsCronSetRadioGroupDisabled(document.adminForm.cronUpdateSitemap, true);
    }
    else {
        // Enable meta tags and sitemap
        jsCronSetRadioGroupDisabled(document.adminForm.cronUpdateMeta, false);
        jsCronSetRadioGroupDisabled(document.adminForm.cronUpdateSitemap, false);
    }
    
    if (jsCronGetRadioValue(document.adminForm.cronCrawlWeb) == '1') {
        // Enable max crawl level
        document.adminForm.cronCrawlMaxLevel.disabled = false;
    }
    else {
        // Disable max crawl level
        document.adminForm.cronCrawlMaxLevel.disabled = true;
    }
    
    // Enable generate button if at least one option is enabled
    if ((jsCronGetRadioValue(document.adminForm.cronUpdateUrls) == '1') ||
        (jsCronGetRadioValue(document.adminForm.cronUpdateMeta) == '1') ||
        (jsCronGetRadioValue(document.adminForm.cronUpdateSitemap) == '1') ||
        (jsCronGetRadioValue(document.adminForm.cronUpdateSitemapXml) == '1') ||
        (jsCronGetRadioValue(document.adminForm.cronCrawlWeb) == '1'))
    {
        document.adminForm.cronGenerateButton.disabled = false;
    }
    else
    {
        document.adminForm.cronGenerateButton.disabled = true;
    }
}

function jsCronButtonClicked()
{
    Joomla.submitbutton('getfile');
}