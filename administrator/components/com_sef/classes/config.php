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
defined('_JEXEC') or die();

define('COM_SEF_CONFIG_REMOVE_SID', false);

define('_COM_SEF_LANG_PATH', 0);
define('_COM_SEF_LANG_SUFFIX', 1);
define('_COM_SEF_LANG_NONE', 2);
define('_COM_SEF_LANG_DOMAIN', 3);

define('_COM_SEF_BASE_IGNORE', 0);
define('_COM_SEF_BASE_HOMEPAGE', 1);
define('_COM_SEF_BASE_CURRENT', 2);
define('_COM_SEF_BASE_NONE', 3);

define('_COM_SEF_WWW_NONE', 0);
define('_COM_SEF_WWW_USE_WWW', 1);
define('_COM_SEF_WWW_USE_NONWWW', 2);

define('_COM_SEF_SITENAME_NO', 0);
define('_COM_SEF_SITENAME_BEFORE', 1);
define('_COM_SEF_SITENAME_AFTER', 2);
define('_COM_SEF_SITENAME_GLOBAL', 3);

define('_COM_SEF_META_GEN_EMPTY', 1);
define('_COM_SEF_META_GEN_ALWAYS', 2);
define('_COM_SEF_META_GEN_NEVER', 0);

define('_COM_SEF_META_PR_ORIGINAL', 0);
define('_COM_SEF_META_PR_JOOMSEF', 1);
define('_COM_SEF_META_PR_JOIN', 2);

define('_COM_SEF_WRONG_DOMAIN_REDIRECT', 0);
define('_COM_SEF_WRONG_DOMAIN_404', 1);
define('_COM_SEF_WRONG_DOMAIN_DO_NOTHING', 2);

define('_COM_SEF_MISMATCHED_LANG_DONT_HANDLE', 0);
define('_COM_SEF_MISMATCHED_LANG_DONT_SEF', 1);
define('_COM_SEF_MISMATCHED_LANG_FIX', 2);

define('_COM_SEF_404_DEFAULT', 0);
define('_COM_SEF_404_FRONTPAGE', 9999999);
define('_COM_SEF_404_JOOMLA', -1);

define('COM_SEF_CRON_PERIOD_HOURLY', 1);
define('COM_SEF_CRON_PERIOD_DAILY', 2);
define('COM_SEF_CRON_PERIOD_WEEKLY', 3);
define('COM_SEF_CRON_PERIOD_MONTHLY', 4);
define('COM_SEF_CRON_PERIOD_YEARLY', 5);

define('COM_SEF_CRON_UPDATE_URLS', 1);
define('COM_SEF_CRON_UPDATE_META', 2);
define('COM_SEF_CRON_UPDATE_SITEMAP', 4);
define('COM_SEF_CRON_CRAWL_WEB', 8);
define('COM_SEF_CRON_UPDATE_SITEMAP_XML', 16);

jimport('joomla.application.component.helper');
jimport('joomla.filesystem.file');

class SEFConfig
{
	// Is is set to devel than use testing URLs
	public $devel = 0;
    /**
     * Whether to always add language version.
     * @var bool
     */
    public $alwaysUseLang = true;
    /* boolean, is JoomSEF enabled  */
    public $enabled = true;
    /**
     * Whether Professional mode is enabled
     * @var bool
     */
    public $professionalMode = false;
    /**
     * Whether to show info texts about features in administration
     * @var bool
     */
    public $showInfoTexts = true;
    /* char,  Character to use for url replacement */
    public $replacement = "-";
    /* char,  Character to use for page spacer */
    public $pagerep = "-";
    /* strip these characters */
    public $stripthese = ",|~|!|@|%|^|*|(|)|+|<|>|:|;|{|}|[|]|---|--|..|.";
    /* string,  suffix for "files" */
    public $suffix = "";
    /* string,  file to display when there is none */
    public $addFile = '';
    /* trims friendly characters from where they shouldn't be */
    public $friendlytrim = "-|.";
    /**
     * generate canonical links
     * @var bool
     */
    public $canonicalLink = true;
    /**
     * page text
	 * @var string
     */
    public $pagetext = "JText::_('COM_SEF_PAGE')-%s";
    /**
     * Should lang be part of path or suffix?
     * @var bool
     */
    public $langPlacement = _COM_SEF_LANG_PATH;
    /* boolean, convert url to lowercase */
    public $lowerCase = true;
    /* boolean, use the title_alias instead of the title */
    public $useAlias = true;
    /**
     * should we extract Itemid from URL?
     * @var bool
     */
    public $excludeSource = false;
    /**
     * should we extract Itemid from URL?
     * @var bool
     */
    public $reappendSource = false;
    /**
     * should we ignore multiple Itemids for the same page in database?
     * @var bool
     */
    public $ignoreSource = true;
    /**
     * excludes often changing variables from SEF URL and
     * appends them as non-SEF query
     * @var bool
     */
    public $appendNonSef = true;
    /**
     * consider both URLs with/without / in  theend valid
     * @var bool
     */
    public $transitSlash = true;
    /**
     * redirect URLs with / on the end to URLs without / on the end 
     * @var bool
     */
    public $redirectSlash=false;
    /**
     * whether to use cache
     * @var bool
     */
    public $useCache = true;
    /**
     * maximum count of URLs in cache
     * @var int
     */
    public $cacheSize = 1000;
    /**
     * minimum hits count that URLs must have to get into cache
     * @var int
     */
    public $cacheMinHits = 10;
    /**
     * record hits for URLs in cache?
     * @var bool
     */
    public $cacheRecordHits = false;
    /**
     * Whether to show error message about cache corruption
     *
     * @var bool
     */
    public $cacheShowErr = false;
    /**
     * translate titles in URLs using JoomFish
     * @var bool
     */
    public $translateNames = true;
    /** int, id of #__content item to use for static page */
    public $page404 = 0;
    /**
     * record 404 pages?
     * @var bool
     */
    public $record404 = false;
    /**
     * if set to yes, the standard Joomla message will be also shown when 404
     * @var boolean
     */
    public $showMessageOn404 = false;
    /**
     * whether to set the ItemID variable when Default 404 Page is displayed
     * @var boolean */
    public $use404itemid = false;
    /**
     * ItemID used for the Default 404 page
     * @var int
     */
    public $itemid404 = 0;
    /**
     * Redirect nonSEF URLs to their SEF equivalents with 301 header?
     * @var bool
     */
    public $nonSefRedirect = true;
    /**
     * Use Moved Permanently redirection table?
     * @var bool
     */
    public $useMoved = true;
    /**
     * Use Moved Permanently redirection table?
     * @var bool
     */
    public $useMovedAsk = true;
    /**
     * Definitions of replacement characters.
     * @var string
     */
    public $replacements = "Á|A, Â|A, Å|A, Ă|A, Ä|A, À|A, Æ|A, Ć|C, Ç|C, Č|C, Ď|D, É|E, È|E, Ë|E, Ě|E, Ê|E, Ì|I, Í|I, Î|I, Ï|I, Ĺ|L, ľ|l, Ľ|L, Ń|N, Ň|N, Ñ|N, Ò|O, Ó|O, Ô|O, Õ|O, Ö|O, Ø|O, Ŕ|R, Ř|R, Š|S, Ś|S, Ť|T, Ů|U, Ú|U, Ű|U, Ü|U, Û|U, Ý|Y, Ž|Z, Ź|Z, á|a, â|a, å|a, ä|a, à|a, æ|a, ć|c, ç|c, č|c, ď|d, đ|d, é|e, ę|e, ë|e, ě|e, è|e, ê|e, ì|i, í|i, î|i, ï|i, ĺ|l, ń|n, ň|n, ñ|n, ò|o, ó|o, ô|o, ő|o, ö|o, ø|o, š|s, ś|s, ř|r, ŕ|r, ť|t, ů|u, ú|u, ű|u, ü|u, û|u, ý|y, ž|z, ź|z, ˙|-, ß|ss, Ą|A, µ|u, ą|a, Ę|E, ż|z, Ż|Z, ł|l, Ł|L, А|A, а|a, Б|B, б|b, В|V, в|v, Г|G, г|g, Д|D, д|d, Е|E, е|e, Ж|Zh, ж|zh, З|Z, з|z, И|I, и|i, Й|I, й|i, К|K, к|k, Л|L, л|l, М|M, м|m, Н|N, н|n, О|O, о|o, П|P, п|p, Р|R, р|r, С|S, с|s, Т|T, т|t, У|U, у|u, Ф|F, ф|f, Х|Kh, х|kh, Ц|Tc, ц|tc, Ч|Ch, ч|ch, Ш|Sh, ш|sh, Щ|Shch, щ|shch, Ы|Y, ы|y, Э|E, э|e, Ю|Iu, ю|iu, Я|Ia, я|ia, Ъ| , ъ| , Ь| , ь| , Ё|E, ё|e, ου|ou, ού|ou, α|a, β|b, γ|g, δ|d, ε|e, ζ|z, η|i, θ|th, ι|i, κ|k, λ|l, μ|m, ν|n, ξ|ks, ο|o, π|p, ρ|r, σ|s, τ|t, υ|i, φ|f, χ|x, ψ|ps, ω|o, ά|a, έ|e, ί|i, ή|i, ό|o, ύ|i, ώ|o, Ου|ou, Ού|ou, Α|a, Β|b, Γ|g, Δ|d, Ε|e, Ζ|z, Η|i, Θ|th, Ι|i, Κ|k, Λ|l, Μ|m, Ν|n, Ξ|ks, Ο|o, Π|p, Ρ|r, Σ|s, Τ|t, Υ|i, Φ|f, Χ|x, Ψ|ps, Ω|o, ς|s, Ά|a, Έ|e, Ή|i, Ί|i, Ό|o, Ύ|i, Ώ|o, ϊ|i, ΐ|i";
    /* Array, contains predefined components. */
    public $predefined = array('0' => "com_login",'1' => "com_newsfeeds",'2' => "com_sef",'3' => "com_weblinks",'4' => "com_joomfish");
    /* String, contains URL to new version file located on server */
   
    public $serverNewVersionURL = "http://www.artio.cz/updates/joomla/joomsef4/version.xml";	
    /* String, contains URL to automatic upgrade script */
    public $serverAutoUpgrade = 'http://www.artio.net/joomla-auto-upgrade';
    /* String, contains URL to registration check script */
    public $serverLicenser = 'http://www.artio.net/license-check';
    /* Array, contains domains for different languages */
    public $langDomain = array();
    /**
     * If set to yes, new SEF URLs won't be generated and only those already
     * in database will be used
     * @var boolean
     */
    public $disableNewSEF = false;
    /**
     * If set to yes, the sid variable won't be removed from SEF url
     * @var boolean
     */
    public $dontRemoveSid = true;
    /**
     * If set to yes, the $_SERVER['QUERY_STRING'] will be set according to parsed variables
     * @var boolean
     */
    public $setQueryString = true;
    /**
     * If set to yes, URLs that can't be parsed by JoomSEF will be parsed by Joomla's router
     * @var boolean
     */
    public $parseJoomlaSEO = true;
    /**
     * Semicolon separated list of global custom non-sef variables
     * @var string
     */
    public $customNonSef = '';
    /**
     * If enabled, JoomSEF will try to set language according to user's browser setting
     * @var boolean
     */
    public $jfBrowserLang = true;
    /**
     * If enabled, JoomSEF will store the user's language selection in a cookie for next visit
     *
     * @var boolean
     */
    public $jfLangCookie = true;
    /**
     * Array of [lang] => subdomain to use the subdomains for languages
     * @var array
     */
    public $jfSubDomains = array();
    /**
     * Whether to use default index file for content sections and categories
     * @var boolean
     */
    public $contentUseIndex = false;
    /**
     * If set to yes, the URL variables will be checked
     * to not contain the http://something.com or similar junk
     * @var boolean
     */
    public $checkJunkUrls = true;
    /**
     * Pipe (|) separated list of junk words to search for
     * @var string
     */
    public $junkWords = 'http:// http// https:// https// www. @';
    /**
     * Semicolon separated list of variables to exclude from junk check
     * @var boolean
     */
    public $junkExclude = '';
    /**
     * Sets if the non-SEF variables should be prevented from
     * overwriting the parsed ones
     * @var boolean
     */
    public $preventNonSefOverwrite = true;
    /**
     * Main language - this language won't have language code added to URL
     * @var mixed
     */
    public $mainLanguage = 0;
    /**
     * Whether to allow UTF-8 characters in URL
     * @var boolean
     */
    public $allowUTF = false;
    /**
     * Whether to number duplicate URLs or use the duplicates management system
     * @var boolean
     */
    public $numberDuplicates = false;
    /**
     * Artio site login name
     * @var string
     */
    public $artioUserName = '';
    /**
     * Artio site password
     * @var string
     */
    public $artioPassword = '';
    /**
     * Artio download id
     * @var string
     */
    public $artioDownloadId = '';
    /**
     * Enable URL source tracing
     * @var bool
     */
    public $trace = false;
    /**
     * Tracing depth if enabled
     * @var int
     */
    public $traceLevel = 3;
    /**
     * Create canonical link automatically for URLs with nonSEF variables
     *
     * @return SEFConfig
     */
    public $autoCanonical = true;
    /**
     * Whether to SEF URLs containing the tmpl=component variable
     *
     * @var bool
     */
    public $sefComponentUrls = false;
    /**
     * Whether to check for newer versions in control panel
     *
     * @var bool
     */
    public $versionChecker = true;
    /**
     * Generator meta tag
     *
     * @var string
     */
    public $tag_generator = '';
    /**
     * Google key meta tag
     *
     * @var string
     */
    public $tag_googlekey = '';
    /**
     * Live.com key meta tag
     *
     * @var string
     */
    public $tag_livekey = '';
    /**
     * Yahoo key meta tag
     *
     * @var string
     */
    public $tag_yahookey = '';
    /**
     * Custom meta tags
     *
     * @var array
     */
    public $customMetaTags = array();
    /**
     * www and non-www domain handling
     *
     * @var int
     */
    public $wwwHandling = _COM_SEF_WWW_NONE;

    /**
     * Enable metadata generation?
     *
     * @var bool
     */
    public $enable_metadata = true;

    /**
     * Enable metadata auto-generation?
     * @var int
     */
    public $metadata_auto = _COM_SEF_META_GEN_EMPTY;

    /**
     * Prefer joomsef tile?
     *
     * @var bool
     */
    public $prefer_joomsef_title = true;

    /**
     * How to use sitename?
     *
     * @var int
     */
    public $use_sitename = _COM_SEF_SITENAME_AFTER;
    /**
     * Sitename separator string
     *
     * @var string
     */
    public $sitename_sep = '-';
    /**
     * Rewrite keywords?
     *
     * @var bool
     */
    public $rewrite_keywords = true;
    /**
     * Rewrite description?
     *
     * @var bool
     */
    public $rewrite_description = true;
    /**
     * Prevent sitename duplicity?
     *
     * @var bool
     */
    public $prevent_dupl = true;
    /**
     * Sets the <base> tag behaviour
     * @var int
     */
    public $check_base_href = _COM_SEF_BASE_CURRENT;
    /**
     * Internal variable for sitemap change flag
     *
     * @var bool
     */
    public $sitemap_changed = true;
    /**
     * Sitemap XML file name
     *
     * @var string
     */
    public $sitemap_filename = 'sitemap';
    /**
     * Whether to use HTTPS in sitemap URLs
     * 
     * @var bool
     */
    public $sitemap_ssl = false;
    /**
     * Default Sitemap indexed state
     *
     * @var bool
     */
    public $sitemap_indexed = false;
    /**
     * Default Sitemap change frequency
     *
     * @var string
     */
    public $sitemap_frequency = 'weekly';
    /**
     * Default Sitemap priority
     *
     * @var string
     */
    public $sitemap_priority = '0.5';
    /**
     * Which items show in the sitemap
     *
     * @var bool
     */
    public $sitemap_show_date = true;
    public $sitemap_show_frequency = true;
    public $sitemap_show_priority = true;
    /**
     * Whether to automatically ping generated Sitemap to google, yahoo and bing
     *
     * @var bool
     */
    public $sitemap_pingauto = true;
    /**
     * Yahoo application ID
     *
     * @var string
     */
    public $sitemap_yahooId = '';
    /**
     * Array of sitemap ping services
     *
     * @var array
     */
    public $sitemap_services = array('http://blogsearch.google.com/ping/RPC2', 'http://rpc.pingomatic.com/');
    /**
     * Whether to add the rel="nofollow" to external links
     *
     * @var bool
     */
    public $external_nofollow = false;
    /**
     * Whether internal links for words will be enabled
     *
     * @var bool
     */
    public $internal_enable = true;
    /**
     * Whether to add rel="nofollow" to internal links
     *
     * @var bool
     */
    public $internal_nofollow = false;
    /**
     * Whether to open internal links in new window
     *
     * @var bool
     */
    public $internal_newwindow = false;
    /**
     * How many word occurences will be linked
     *
     * @var int
     */
    public $internal_maxlinks = 1;
    /**
     * Whether to display ARTIO Newsfeed on control panel
     *
     * @var bool
     */
    public $artioFeedDisplay = true;
    /**
     * ARTIO Newsfeed URL
     *
     * @var string
     */
    public $artioFeedUrl = 'http://www.artio.net/joomsef-news/rss';
    /**
     * Whether to rewrite index.php links in content and redirect them to /
     *
     * @var bool
     */
    public $fixIndexPhp = true;
    /**
     * Whether to fix document format after route
     *
     * @var bool
     */
    public $fixDocumentFormat = false;
    /**
     * Whether to filter global variables
     *
     * @var bool
     */
    public $useGlobalFilters = true;
    /**
     * Whether the error log is enabled
     * @var bool
     */
    public $logErrors = false;
    /**
     * Whether cron tasks are enabled
     * @var bool
     */
    public $cronEnabled = false;
    /**
     * Whether cron tasks can only be run from local server
     * @var bool
     */
    public $cronOnlyLocal = true;
    /**
     * Secret key to run cron
     * @var bool
     */
    public $cronKey = '';
    
    // Automatically lock created URLs
    public $autolock_urls=false;
    // Automatically update URLs
   	public $update_urls=false;
   	// Enable languages management from JoomSEF
   	public $langEnable=false;
   	// Where to place language
   	public $langPlacementJoomla=_COM_SEF_LANG_PATH;
   	// Add language to multilanguage content
   	public $addLangMulti=true;
   	// Allways use language
   	public $alwaysUseLangJoomla=true;
    // Always add language code to URL on homepage
    public $alwaysUseLangHomeJoomla = true;
   	// Get language from browser
   	public $browserLangJoomla=true;
   	// Get and save language to cookie
   	public $langCookieJoomla=true;
   	// Main Joomla language
   	public $mainLanguageJoomla="";
   	// Subdomains for languages
   	public $subDomainsJoomla=array();
   	// Translate Items
   	public $translateItems=true;
	// Username for Google Analytics API
	public $google_email="";
	// Password for Google Analytics API
	public $google_password="";
	// Google API key for pageSpeed service
	public $google_apikey="";
	// Google ID
	public $google_id="";
	// Enable google tracking code
	public $google_enable=false;
    // Enable Demographics and Interest Reports in GA tracking code
    public $google_demographic_reports=false;
    // Use enhanced link attribution
    public $google_link_attribution=false;
	// Exclude IPs from tracking
	public $google_exclude_ip="";
	// Exclude this level from Google tracking
	public $google_exclude_level=array();
   	
    /**
     * How should we handle the case when domain and SEF URL
     * have different languages?
     * @var int
     */
    public $wrongDomainHandling = _COM_SEF_WRONG_DOMAIN_REDIRECT;

    /**
     * How should we handle the case when language doesn't match Itemid?
     * @var int
     */
    public $mismatchedLangHandling = _COM_SEF_MISMATCHED_LANG_DONT_HANDLE;
    
    /**
     * Whether root URL should be redirected to correct language with 303 or 301 code
     * @var bool
     */
    public $rootLangRedirect303 = true;

    /**
     * Whether variables in SEF URL's query string should be
     * handled as non-SEF for other URLs on the page
     * @var bool
     */
    public $nonSefQueryVariables = true;
    
    /**
     * Whether VM currency is automatically selected according to language
     */
    public $vmCurrencyEnable = false;
    
    /**
     * Language to VM currency association
     */
    public $vmCurrency = array();
    
    /**
     * Whether to replace index.php with current menu query (default Joomla behaviour)
     */
    public $indexPhpCurrentMenu = true;
    
    /**
     * Whether to generate multiple sitemap XML files for different language domains
     */
    public $multipleSitemaps = false;
    
    /**
     * File names for multiple sitemaps [lang] => filename
     */
    public $multipleSitemapsFilenames = array();
    
    /**
     * Whether menu associations should be used on multilanguage website
     */
    public $langMenuAssociations = false;
    
    /**
     * Whether to update hits count for homepage URLs
     */
    public $homePageHits = true;
    
    /**
     * Whether Joomla generated canonical links should be removed
     */
    public $canonicalsRemove = false;
    
    /**
     * Whether canonical links URLs should be fixed
     */
    public $canonicalsFix = true;


    private function __construct()
    {
        // If the configuration file exists, load data from file
        $sef_config_file = JPATH_ROOT.'/administrator/components/com_sef/configuration.php';
        if (JFile::exists($sef_config_file)) {
            include($sef_config_file);
        }
        
        // Get parameters
        // We cannot use the JComponentHelper::getParams() function as it
        // (for some strange reason) caches the component parameters!
        // 30.11.2012 dajo: we need to store parameters in custom_data column, as parameters get cleared
        //                  by Joomla when permissions are saved (as from Joomla's point of view JoomSEF doesn't have parameters)
        $db = JFactory::getDbo();
        $db->setQuery("SELECT `custom_data` FROM `#__extensions` WHERE `type` = 'component' AND `element` = 'com_sef'");
        $tmp = $db->loadResult();
        $params = new JRegistry();
        if (is_string($tmp)) {
            $params->loadString($tmp);
        }
        
        // Assign values
        $boolFalse = serialize(false);
        $dontLoad = array('serverNewVersionURL', 'serverAutoUpgrade', 'serverLicenser');
        foreach ($this as $key => $value)
        {
            if (in_array($key, $dontLoad)) {
                // Don't load, use default values
                continue;
            }
            
            // Check if we have value from file
            if (isset($$key)) {
                $val = $$key;
            }
            else {
                // Otherwise get value from params
                $val = $params->get($key);
                if (!is_null($val)) {
                    $newVal = @unserialize($val);
                    if (($newVal !== false) || ($val === $boolFalse)) {
                        $val = $newVal;
                    }
                }
            }
            
            if (!is_null($val)) {
                // Change objects to associative arrays
                if (is_object($val)) {
                    $this->$key = (array)$val;
                }
                else {
                    $this->$key = $val;
                }
            }
        }
        
        if ($this->devel == 1) {
        	$this->serverNewVersionURL = 'http://demo.tramin.artio.cz/new-update/joomsef4/version.xml';
        	$this->serverAutoUpgrade = 'http://demo.tramin.artio.cz/new-update/joomla-auto-upgrade';
        	$this->serverLicenser = 'http://demo.tramin.artio.cz/new-update/licenser2.php';
        }
    }

    public function saveConfig($return_data = 0)
    {
        // Create params
        $params = new JRegistry();
        
        // Set data to params
        foreach ($this as $key => $value)
        {
            $params->set($key, serialize($value));
        }
        
        if ($return_data == 1) {
            return $params->toString();
        }
        else {
            // Save params to database
            // 30.11.2012 dajo: to the custom_data column!
            $db = JFactory::getDBO();
            $query = "UPDATE `#__extensions` SET `custom_data` = ".$db->quote($params->toString())." WHERE `type` = 'component' AND `element` = 'com_sef' LIMIT 1";
            $db->setQuery($query);
            if (!$db->query()) {
                return false;
            }
            else {
                // Check if there's an old configuration file present and delete it
                $sef_config_file = JPATH_ROOT.'/administrator/components/com_sef/configuration.php';
                if (JFile::exists($sef_config_file)) {
                    JFile::delete($sef_config_file);
                }
                return true;
            }
        }
    }

    public function getPageText()
    {
        $pagetext = $this->pagetext;

        // if JText is used, parse out the string
        if (strpos($pagetext, 'JText::_(') === 0) {
            // make sure we use single quotes
            $pagetext = str_replace('"', '\'', $pagetext);
            // find first and second quote
            $quot1 = strpos($pagetext, '\'');
            $quot2 = strpos($pagetext, '\'', $quot1 + 1);
            // replace JText text with real text
            $pagetext = JText::_(substr($pagetext, $quot1 + 1, $quot2 - strlen($pagetext))) . substr($pagetext, strpos($pagetext, ')', $quot2) + 1);
        }

        return $pagetext;
    }

    /**
     * Return array of URL characters to be replaced.
     *
     * @return array
     */
    public function getReplacements()
    {
        static $replacements;

        if( isset($replacements) ) {
            return $replacements;
        }

        $replacements = array();

        $str = trim($this->replacements);
        if( $str != '' ) {
            $items = explode(',', $str);
            foreach ($items as $item) {
                @list ($src, $dst) = explode('|', trim($item));

                // $dst can be empty, so the character can be removed
                if( trim($src) == '' ) {
                    continue;
                }

                $replacements[trim($src)] = trim($dst);
            }
        }

        return $replacements;
    }

    public function &getJunkWords()
    {
        static $words;

        if (!isset($words)) {
            $words = explode(' ', $this->junkWords);

            if( count($words) ) {
                foreach($words as $key => $val) {
                    $words[$key] = trim($val);

                    if( empty($words[$key]) ) {
                        unset($words[$key]);
                    }
                }
            }
        }

        return $words;
    }

    public function &getJunkExclude()
    {
        static $excludes;

        if (!isset($excludes)) {
            $excludes = explode(';', $this->junkExclude);

            if( count($excludes) ) {
                foreach($excludes as $key => $val) {
                    $excludes[$key] = trim($val);

                    if( empty($excludes[$key]) ) {
                        unset($excludes[$key]);
                    }
                }
            }
        }

        return $excludes;
    }

    /**
     * Set config variables.
     *
     * @param string $var
     * @param mixed $val
     * @return bool
     */
    public function set($var, $val)
    {
        if (isset($this->$var)) {
            $this->$var = $val;
            return true;
        }
        return false;
    }

    public function getConfig()
    {
        static $instance;
        if (!isset($instance)) {
            $instance = new SEFConfig();
        }
        return $instance;
    }

}
?>