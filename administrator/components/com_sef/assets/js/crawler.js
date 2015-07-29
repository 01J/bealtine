var jsCrawlerUrlsBatch = 15;
var jsCrawlerMaxResponseTime = 30;
var jsCrawlerResponseTimer = null;
var jsCrawlerLastResponse = 0;

var jsCrawlerMaxLevel = 0;
var jsCrawlerCurrentUrls = new Array();
var jsCrawlerNextUrls = new Array();
var jsCrawlerCrawledUrls = new Array();
var jsCrawlerCurrentLevel = 0;

var jsCrawlerFoundUrls = 0;

// Crawler's state
// 0 - before started
// 1 - running
// 2 - finished
var jsCrawlerState = 0;
var jsCrawlerRequestCancel = false;

function jsCrawlerStopResponseTimer()
{
    if (jsCrawlerResponseTimer) {
        clearInterval(jsCrawlerResponseTimer);
    }
}

function jsCrawlerResetResponseTimer()
{
    jsCrawlerStopResponseTimer();
    jsCrawlerLastResponse = 0;
    jsCrawlerResponseTimer = setInterval('jsCrawlerCheckResponseTime()', 1000);
    jsCrawlerUpdateResponseTime();
}

function jsCrawlerCheckResponseTime()
{
    jsCrawlerLastResponse++;
    jsCrawlerUpdateResponseTime();
    if (jsCrawlerLastResponse > jsCrawlerMaxResponseTime) {
        jsCrawlerRequestCancel = true;
        jsCrawlerError();
    }
}

function jsCrawlerUpdateResponseTime()
{
    document.id('crawlerResponseTime').innerHTML = jsCrawlerTextResponseTime.replace('%s', jsCrawlerLastResponse);
}

function jsCrawlerButtonClicked()
{
    if (jsCrawlerState == 0) {
        jsCrawlerStartCrawl();
    }
    else if (jsCrawlerState == 1) {
        jsCrawlerCancel();
    }
    else {
        jsCrawlerFinish();
    }
}

function jsCrawlerStartCrawl()
{
    jsCrawlerState = 1;
    
    var root = jsCrawlerRootUrl + document.id('crawlerRootUrl').value;
    jsCrawlerMaxLevel = document.id('crawlerMaxLevel').value;
    jsCrawlerCurrentUrls = new Array(root);
    jsCrawlerFoundUrls = 1;
    
    document.id('crawlerRootUrl').disabled = true;
    document.id('crawlerMaxLevel').disabled = true;
    //document.id('crawlerButton').disabled = true;
    document.id('crawlerButton').value = jsCrawlerTextCancel;
    document.id('crawlerRunningValue').innerHTML = jsCrawlerTextRunning;
    document.id('crawlerRunningImg').style.display = 'block';
    
    jsCrawlerResetResponseTimer();
    
    jsCrawlerCrawl(0);
}

function jsCrawlerRecoverCrawl()
{
    jsCrawlerState = 1;
    jsCrawlerRequestCancel = false;
    
    document.id('crawlerButton').value = jsCrawlerTextCancel;
    document.id('crawlerRunningValue').innerHTML = jsCrawlerTextRunning;
    document.id('crawlerRunningValue').style.fontWeight = 'normal';
    document.id('crawlerRunningValue').style.color = 'black';
    document.id('crawlerRunningImg').style.display = 'block';
    document.id('crawlerContinueButton').style.display = 'none';
    
    jsCrawlerResetResponseTimer();
    
    jsCrawlerCrawl(jsCrawlerCurrentLevel);
}

function jsCrawlerFinish()
{
    submitform();
}

function jsCrawlerSuccess()
{
    jsCrawlerEnd();
    document.id('crawlerRunningValue').innerHTML = jsCrawlerTextSuccess;
    document.id('crawlerRunningValue').style.fontWeight = 'bold';
    document.id('crawlerRunningValue').style.color = 'green';
}

function jsCrawlerError()
{
    jsCrawlerEnd();
    document.id('crawlerRunningValue').innerHTML = jsCrawlerTextError;
    document.id('crawlerRunningValue').style.fontWeight = 'bold';
    document.id('crawlerRunningValue').style.color = 'red';
    document.id('crawlerResponseTime').innerHTML = jsCrawlerTextErrorMsg;
    document.id('crawlerContinueButton').style.display = 'inline';
}

function jsCrawlerCancel()
{
    jsCrawlerRequestCancel = true;
    jsCrawlerEnd();
    document.id('crawlerRunningValue').innerHTML = jsCrawlerTextCancelled;
    document.id('crawlerRunningValue').style.fontWeight = 'bold';
    document.id('crawlerRunningValue').style.color = 'red';
}

function jsCrawlerEnd()
{
    jsCrawlerState = 2;
    jsCrawlerStopResponseTimer();
    document.id('crawlerRunningImg').style.display = 'none';
    document.id('crawlerResponseTime').innerHTML = '&nbsp;';
    document.id('crawlerButton').value = jsCrawlerTextFinish;
    //document.id('crawlerButton').disabled = false;
}

function jsCrawlerCrawl(level)
{
    // Store current level
    jsCrawlerCurrentLevel = level;
    
    // Update counts
    document.id('crawlerCrawledValue').innerHTML = jsCrawlerCrawledUrls.length;
    document.id('crawlerUrlsValue').innerHTML = jsCrawlerFoundUrls;
    
    // Check cancelled
    if (jsCrawlerRequestCancel) {
        return;
    }
    
    // Check level
    if (level > jsCrawlerMaxLevel) {
        jsCrawlerSuccess();
        return;
    }
    
    // Update level
    document.id('crawlerLevelValue').innerHTML = level + ' / ' + jsCrawlerMaxLevel;
    
    // Prepare URL
    var url = jsCrawlerScriptUrl;
    var max = (jsCrawlerCurrentUrls.length < jsCrawlerUrlsBatch) ? jsCrawlerCurrentUrls.length : jsCrawlerUrlsBatch;
    
    var crawlUrls = new Array();
    for (var i = 0; i < max; i++) {
        crawlUrls.push(jsCrawlerCurrentUrls[i]);
    }
    
    // Call request
    new Request.JSON({
            'url': url,
            'method': 'POST',
            'data': { 'url': crawlUrls },
            'onSuccess': function(data, text) {
                // Check cancelled
                if (jsCrawlerRequestCancel) {
                    return;
                }
                
                // Update response timer
                jsCrawlerResetResponseTimer();
                
                // Move crawled URLs to another array
                for (var i = 0; i < data.crawled; i++) {
                    var cur = jsCrawlerCurrentUrls.shift();
                    jsCrawlerCrawledUrls.push(cur);
                }
                
                // Handle found URLs if this is not last level
                if (level < jsCrawlerMaxLevel) {
                    for (var i = 0; i < data.found.length; i++) {
                        var cur = data.found[i];
                        
                        // Check if URL has already been crawled or is already scheduled to be crawled
                        if (jsCrawlerCrawledUrls.contains(cur) || jsCrawlerCurrentUrls.contains(cur) || jsCrawlerNextUrls.contains(cur)) {
                            continue;
                        }
                        
                        jsCrawlerNextUrls.push(cur);
                        jsCrawlerFoundUrls++;
                    }
                }
                
                // Check if there are any URLs left for current level
                if (jsCrawlerCurrentUrls.length > 0) {
                    // Continue with current level
                    jsCrawlerCrawl(level);
                }
                else {
                    // No more URLs, continue with next level
                    jsCrawlerCurrentUrls = jsCrawlerNextUrls;
                    jsCrawlerNextUrls = new Array();
                    jsCrawlerCrawl(level + 1);
                }
            },
            'onError': function(text, error) {
                jsCrawlerError();
            },
            'onFailure': function(xhr) {
                jsCrawlerError();
            }
    }).send();
}