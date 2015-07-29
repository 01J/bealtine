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

jimport('joomla.filesystem.file');
jimport('joomla.form.form');

define('_COM_SEF_PRIORITY_DEFAULT_ITEMID', 90);
define('_COM_SEF_PRIORITY_DEFAULT', 95);

JLoader::register('SefExt', JPATH_ROOT.'/components/com_sef/sef.ext.php');
JLoader::register('JoomSEF', JPATH_ROOT.'/components/com_sef/joomsef.php');

class SEFTools
{
    function getSEFVersion()
    {
        static $version;

        if (! isset($version)) {
            $xmlFile = JPATH_ADMINISTRATOR.'/components/com_sef/sef.xml';
            if (JFile::exists($xmlFile)) {
                $xml = simplexml_load_file($xmlFile);
                if ($xml !== false) {
                    $version = (string)$xml->version;
                }
            }
        }

        return $version;
    }

    function getSEFInfo()
    {
        static $info;

        if( !isset($info) ) {
            $info = array();

            $xmlFile = JPATH_ADMINISTRATOR . '/' . 'components' . '/' . 'com_sef' . '/' . 'sef.xml';
            if (JFile::exists($xmlFile)) {
                $xml = simplexml_load_file($xmlFile);
                if ($xml !== false) {
                    $info['version'] = (string)$xml->version;
                    $info['creationDate'] = (string)$xml->creationDate;
                    $info['author'] = (string)$xml->author;
                    $info['authorEmail'] = (string)$xml->authorEmail;
                    $info['authorUrl'] = (string)$xml->authorUrl;
                    $info['copyright'] = (string)$xml->copyright;
                    $info['license'] = (string)$xml->license;
                    $info['description'] = JText::_((string)$xml->description);
                }
            }
        }

        return $info;
    }

    function getExtVersion($extension)
    {
        $xml = SEFTools::getExtXML($extension);
        $version = null;

        if ($xml) {
            $root = $xml;
            $ver = (string)$root['version'];
            if (($root->getName() == 'extension') && version_compare($ver, '1.5', '>=') && ((string)$root['type'] == 'sef_ext')) {
                $element = & $root->version;
                $version = $element ? (string)$element : '';
            }
        }
        return $version;
    }

    /**
     * Returns extension name from its XML file.
     *
     * @return string
     */
    function getExtName($extension)
    {
        $xml = SEFTools::getExtXML($extension);
        $name = null;

        if ($xml) {
            $root = $xml;
            $ver = (string)$root['version'];
            if (($root->getName() == 'extension') && version_compare($ver, '1.5', '>=') && ((string)$root['type'] == 'sef_ext')) {
                $element = & $root->name;
                $name = $element ? (string)$element : '';
            }
        }

        return $name;
    }

    /**
     * Returns the extension XML object
     *
     * @param string $extension     Extension option
     * @return JSimpleXML           Extension XML
     */
    function getExtXML($extension)
    {
        static $xmls;

        if (! isset($xmls)) {
            $xmls = array();
        }

        if (! isset($xmls[$extension])) {
            $xmls[$extension] = null;

            $xmlFile = JPATH_ROOT . '/' . 'components' . '/' . 'com_sef' . '/' . 'sef_ext' . '/' . $extension . '.xml';
            if (JFile::exists($xmlFile)) {
                $xmls[$extension] = simplexml_load_file($xmlFile);
            }
        }

        return $xmls[$extension];
    }

    function &getExtAcceptVars($option, $includeGlobal = true)
    {
        static $acceptVars;

        if( !isset($acceptVars) ) {
            $acceptVars = array();
        }

        if( !isset($acceptVars[$option]) ) {
            $sefConfig = SEFConfig::getConfig();
            $params =& SEFTools::getExtParams($option);
            $aVars = trim($params->get('acceptVars', ''));

            if( $aVars == '' ) {
                $acceptVars[$option] = array();
            }
            else {
                $aVars = explode(';', $aVars);
                $aVars = array_map('trim', $aVars);
                $acceptVars[$option] = $aVars;
            }
        }

        return $acceptVars[$option];
    }

    function &getExtFilters($option, $includeGlobal = true)
    {
        static $filters;

        if( !isset($filters) ) {
            $filters = array();
        }

        if( !isset($filters[$option]) ) {
            $filters[$option] = array();
            $filters[$option]['pos'] = array();
            $filters[$option]['neg'] = array();

            $db = JFactory::getDBO();
            
            $element = str_replace('com_', 'ext_joomsef4_', $option);

            $query = $db->getQuery(true);
            $query->select('custom_data')->from('#__extensions')->where('type='.$db->quote('sef_ext'))->where('enabled=1')->where('element='.$db->quote($element));
            $db->setQuery($query);
            $row = $db->loadResult();

            if ($row) {
                // Parse the filters
                $rules = explode("\n", $row);
                $rules = array_map('trim', $rules);

                if( count($rules) > 0 ) {
                    foreach($rules as $rule) {
                        // Is the rule positive or negative?
                        if( $rule[0] == '+' ) {
                            $type = 'pos';
                        }
                        else if( $rule[0] == '-' ) {
                            $type = 'neg';
                        }
                        else {
                            continue;
                        }

                        $rule = substr($rule, 1);

                        // Split the rule to regexp and variables parts
                        $pos = strrpos($rule, '=');
                        if( $pos === false ) {
                            continue;
                        }

                        $re = substr($rule, 0, $pos);
                        $vars = substr($rule, $pos + 1);
                        if ($re == '') {
                            continue;
                        }

                        // Create the filter object
                        $filter = new stdClass();
                        $filter->rule = $re;
                        if ($vars != '') {
                            $filter->vars = array_map('trim', explode(',', $vars));
                        }
                        else {
                            $filter->vars = array();
                        }

                        // Add the filter to filters
                        $filters[$option][$type][] = $filter;
                    }
                }
            }
        }

        return $filters[$option];
    }

    function &getExtFiltersByVars($option, $includeGlobal = true)
    {
        static $byVars;

        if( !isset($byVars) ) {
            $byVars = array();
        }

        if (empty($option)) {
            $option = '_default';
        }

        if( !isset($byVars[$option]) ) {
            $byVars[$option] = array();

            // Get filters
            $filters =& SEFTools::getExtFilters($option, $includeGlobal);
            if( count($filters) > 0 ) {
                // Loop through filter types (pos, neg)
                foreach($filters as $type => $typeFilters) {
                    if( count($typeFilters) > 0 ) {
                        // Loop through filters
                        foreach($typeFilters as $filter) {
                            if( count($filter->vars) > 0 ) {
                                // Loop through variables
                                foreach($filter->vars as $var) {
                                    // Add filter to var and type
                                    if( !isset($byVars[$option][$var]) ) {
                                        $byVars[$option][$var] = array();
                                    }
                                    if( !isset($byVars[$option][$var][$type]) ) {
                                        $byVars[$option][$var][$type] = array();
                                    }
                                    $byVars[$option][$var][$type][] = $filter->rule;
                                }
                            }
                        }
                    }
                }
            }
        }

        return $byVars[$option];
    }

    /**
     * Returns JForm object representing extension's parameters
     *
     * @param string Extension name
     * @return JForm Extension's parameters
     */
    function &getExtParamsForm($option)
    {
        static $forms;

        if (!isset($forms)) {
            $forms = array();
        }
        if (!isset($forms[$option])) {
            $forms[$option] = new JForm($option, array('control' => 'params'));

            // Set the extension's parameters renderer
            $pxml = SEFTools::getExtParamsXML($option);
            if (is_a($pxml, 'SimpleXMLElement')) {
                $forms[$option]->load($pxml);
            }
            else if( is_array($pxml) && count($pxml) > 0 ) {
                for( $i = 0, $n = count($pxml); $i < $n; $i++ ) {
                    if( is_a($pxml[$i], 'SimpleXMLElement') ) {
                        $forms[$option]->load($pxml[$i]);
                    }
                }
            }

            // Set the default parameters renderer
            $xml = SEFTools::getExtsDefaultParamsXML();
            if (is_a($xml, 'SimpleXMLElement')) {
                $forms[$option]->load($xml);
            }
            else if( is_array($xml) && count($xml) > 0 ) {
                for( $i = 0, $n = count($xml); $i < $n; $i++ ) {
                    if( is_a($xml[$i], 'SimpleXMLElement') ) {
                        $forms[$option]->load($xml[$i]);
                    }
                }
            }

            // Bind data
            $forms[$option]->bind(self::getExtParams($option));
        }

        return $forms[$option];
    }

    function &getExtParams($option)
    {
        $db = JFactory::getDBO();
        $element = str_replace('com_', 'ext_joomsef4_', $option);
        
        // Cache all data and parameters!!!
        static $exts, $params;
        
        // Load all params data only once
        if (!isset($exts)) {
            $query = $db->getQuery(true);
            $query->select(array('element', 'params'))->from('#__extensions')->where('type='.$db->quote('sef_ext'));
            $db->setQuery($query);
            $exts = $db->loadObjectList('element');
        }
        
        // Cache params objects
        if (!isset($params)) {
            $params = array();
        }
        if (!isset($params[$element])) {
            // Create new params object if used for the first time
            $data = '';
            if (isset($exts[$element])) {
                $data = $exts[$element]->params;
            }
            $params[$element] = new JRegistry($data);
        }
        
        return $params[$element];
    }

    /**
     * Returns the JSimpleXMLElement object representing
     * the default parameters for every extension
     *
     * @return JSimpleXMLElement	Extensions' default parameters
     */
    function getExtsDefaultParamsXML()
    {
        static $xml;

        if (isset($xml)) {
            return $xml;
        }

        $xml = null;
        $xmlpath = JPATH_ROOT . '/' . 'administrator' . '/' . 'components' . '/' . 'com_sef' . '/' . 'extensions_params.xml';

        if (JFile::exists($xmlpath)) {
            $xml = simplexml_load_file($xmlpath);
        }

        return $xml;
    }

    /**
     * Returns the JSimpleXMLElement object representing
     * the extension's parameters
     *
     * @param string $option		Extension name
     * @return JSimpleXMLElement	Extension's parameters
     */
    function getExtParamsXML($option)
    {
        static $xmls;

        if (! isset($xmls)) {
            $xmls = array();
        }

        if (! isset($xmls[$option])) {
            $xmls[$option] = null;

            $xml = SEFTools::getExtXML($option);

            if ($xml) {
                $form = $xml->form;
                if (!empty($form)) {
                    $xmls[$option] = $form;
                }
            }
        }

        return $xmls[$option];
    }

    /** Returns the array of texts used by the extension for creating URLs
     *  in currently selected language (for JoomFish support)
     *
     * @param	string  Extension name
     * @return	array   Extension's texts
     */
    function getExtTexts($option, $lang = '')
    {
        $database = & JFactory::getDBO();

        static $extTexts;

        if ($option == '') {
            return false;
        }

        // Set the language
        if ($lang == '') {
            $lang = JFactory::getLanguage()->getTag();
        }
        if (!isset($extTexts)) {
            $extTexts = array();
        }
        if (!isset($extTexts[$option])) {
            $extTexts[$option] = array();
        }
        if (!isset($extTexts[$option][$lang])) {
            $extTexts[$option][$lang] = array();
            // If lang is different than current language, change it
            if ($lang !=JFactory::getLanguage()->getTag()) {
                $language = & JFactory::getLanguage();
                $oldLang = $language->setLanguage($lang);
                $language->load();
            }

            $query = "SELECT `lang_id` AS `id`";
            $query.= " FROM `#__languages`";
            $query.= " WHERE `lang_code` = ".$database->quote($lang);
            $database->setQuery($query);
            $lang_id = $database->loadResult();

            //$query = "SELECT `id`, `name`, `value` FROM `#__sefexttexts` WHERE `extension` = '$option'";
            $query = "SELECT `lang_id`, `name`, `value`";
            $query.= " FROM `#__sefexttexts`";
            $query.= " WHERE `extension` = ".$database->quote($option);
			$query.= " AND (`lang_id` = 0".($lang_id ? " OR `lang_id` = ".$lang_id : "").")";
			$query.= " ORDER BY `lang_id` DESC";
            $database->setQuery($query);
            $texts = $database->loadObjectList();
            /*$ntexts=array();
            for($i=0;$i<count($texts);$i++) {
            	$ntexts[$texts[$i]->lang_id][$texts[$i]->name]=$texts[$i];
            }*/
            if (is_array($texts) && (count($texts) > 0)) {
                foreach (array_keys($texts) as $i) {
                    $name = $texts[$i]->name;
                    //$value = $texts[$i]->value;
                    if(!isset($extTexts[$option][$lang][$name])) {
                    	$extTexts[$option][$lang][$name] = $texts[$i]->value;
                    }
                    //$extTexts[$option][$lang][$name] = $value;
                }
            }
            // Set the language back to previously selected one
            if (isset($oldLang) && ($oldLang != SEFTools::getLangLongCode())) {
                $language = & JFactory::getLanguage();
                $language->setLanguage($oldLang);
                $language->load();
            }
        }
        return $extTexts[$option][$lang];
    }

    function removeVariable($url, $var, $value = '')
    {
        if ($value == '') {
            //$newurl = eregi_replace("(&|\?)$var=[^&]*", '\\1', $url);

            $regex = "(&|\?)$var=[^&]*";
            $regex = addcslashes($regex, '/');
            $newurl = preg_replace('/' . $regex . '/i', '$1', $url);
        } else {
            $trans = array('?' => '\\?' , '.' => '\\.' , '+' => '\\+' , '*' => '\\*' , '^' => '\\^' , '$' => '\\$');
            $value = strtr($value, $trans);
            //$newurl = eregi_replace("(&|\?)$var=$value(&|\$)", '\\1\\2', $url);
            $regex = "(&|\?)$var=$value(&|\$)";
            $regex = addcslashes($regex, '/');
            $newurl = preg_replace('/' . $regex . '/i', '$1$2', $url);
        }
        $newurl = trim($newurl, '&?');
        $trans = array('&&' => '&' , '?&' => '?');
        $newurl = strtr($newurl, $trans);

        return $newurl;
    }

    function getVariable($url, $var)
    {
        $value = null;
        $matches = array();

        if( preg_match("/[&\?]$var=([^&]*)/", $url, $matches) > 0 ) {
            $value = $matches[1];
        }

        return $value;
    }

    function extractVariable(&$url, $var)
    {
        $value = SEFTools::getVariable($url, $var);
        $url = SEFTools::removeVariable($url, $var);

        return $value;
    }

    /**
     * Fixes numerical variable by converting it to integer in the URI
     */
    function fixVariable(&$uri, $varName)
    {
        $value = $uri->getVar($varName);
        if (!is_null($value)) {
            $value = (int) $value;
            $uri->setVar($varName, $value);
        }
    }

    /**
     * Removes given variables from URI and returns a query string
     * built of them
     *
     * @param JURI $uri
     * @param array $vars Variables to remove
     */
    function RemoveVariables(&$uri, &$vars)
    {
        $query = array();
        if (is_array($vars) && count($vars) > 0) {
            foreach($vars as $var) {
                // Get the variable value
                $value = $uri->getVar($var);

                // Skip variables not present in URL
                if( is_null($value) ) {
                    continue;
                }

                // Add variable to query
                if( is_array($value) ) {
                    // Variable is an array, let's remove all its occurences
                    foreach($value as $key => $val) {
                        $query[] = $var.'['.$key.']='.urlencode($val);
                    }
                }
                else {
                    // Variable is not an array
                    $query[] = $var.'='.urlencode($value);
                }

                // Remove variable from URI
                $uri->delVar($var);
            }
        }
        $query = implode('&amp;', $query);

        return $query;
    }

    function ReplaceAll($search, $replace, $subject)
    {
        while (strpos($subject, $search) !== false) {
            $subject = str_replace($search, $replace, $subject);
        }

        return $subject;
    }

    /**
     * Checks whether to use alias from extension parameter value
     *
     * @param string $params
     * @param string $paramName
     * @return boolean
     */
    function UseAlias(&$params, $paramName)
    {
        $sefConfig = SEFConfig::getConfig();

        $param = $params->get($paramName, 'global');
        if( ($param == 'alias') ||
            ($param == 'global' && $sefConfig->useAlias) )
        {
            return true;
        }

        return false;
    }

	/**
	 * Convert description of extensions from html to plain for metatags
	 *
	 * @param string $text
	 * @return string
	 */
	function cleanDesc($text) {
		// Remove javascript
		$regex = "'<script[^>]*?>.*?</script>'si";
		$text = preg_replace($regex, " ", $text);
		$regex = "'<noscript[^>]*?>.*?</noscript>'si";
		$text = preg_replace($regex, " ", $text);

		// Strip any remaining html tags
        $text = strip_tags($text);

		// Remove any mambot codes
		$regex = '(\{.*?\})';
		$text = preg_replace($regex, " ", $text);

		// Some replacements
        $text = str_replace(array('\n', '\r', '"'), array(' ', '', '&quot;'), $text);
        $text = trim($text);

        return $text;
    }

	/**
	 * Clip text to use as meta description
	 *
	 * @param string $text
	 * @param int $limit
	 * @return string
	 */
	function clipDesc($text, $limit) {
        if (JString::strlen($text) > $limit) {
            $text = JString::substr($text, 0, $limit);
            $pos = JString::strrpos($text, ' ');
            if ($pos !== false) {
                $text = JString::substr($text, 0, $pos);
            }
            $text = JString::trim($text);
        }
		return $text;
	}

	/**
	 * Generate for metatags
	 *
	 * @param string $desc
	 * @param string $blacklist
	 * @param int $count
	 * @param int $minLength
	 * @return string
	 */
	function generateKeywords($desc, $blacklist, $count, $minLength) {
		// Remove any email addresses
		$regex = '/(([_A-Za-z0-9-]+)(\\.[_A-Za-z0-9-]+)*@([A-Za-z0-9-]+)(\\.[A-Za-z0-9-]+)*)/iex';
		$desc = preg_replace($regex, '', $desc);
		// Some unwanted replaces
        $desc = preg_replace('/<[^>]*>/', ' ', $desc);
		$desc = preg_replace('/[\\\\.;:|\'"`,()\[\]]/', ' ', $desc);
        $desc = str_replace(' - ', ' ', $desc);	
		$keysArray = explode(" ", $desc);
		// Sort words from up to down
		$keysArray = array_count_values(array_map(array('JoomSEF', '_utf8LowerCase'), $keysArray));

		if( is_null($blacklist) ) {
		    $blacklist = "a, able, about, above, abroad, according, accordingly, across, actually, adj, after, afterwards, again, against, ago, ahead, ain't, all, allow, allows, almost, alone, along, alongside, already, also, although, always, am, amid, amidst, among, amongst, an, and, another, any, anybody, anyhow, anyone, anything, anyway, anyways, anywhere, apart, appear, appreciate, appropriate, are, aren't, around, as, a's, aside, ask, asking, associated, at, available, away, awfully, b, back, backward, backwards, be, became, because, become, becomes, becoming, been, before, beforehand, begin, behind, being, believe, below, beside, besides, best, better, between, beyond, both, brief, but, by, c, came, can, cannot, cant, can't, caption, cause, causes, certain, certainly, changes, clearly, c'mon, co, co., com, come, comes, concerning, consequently, consider, considering, contain, containing, contains, corresponding, could, couldn't, course, c's, currently, d, dare, daren't, definitely, described, despite, did, didn't, different, directly, do, does, doesn't, doing, done, don't, down, downwards, during, e, each, edu, eg, eight, eighty, either, else, elsewhere, end, ending, enough, entirely, especially, et, etc, even, ever, evermore, every, everybody, everyone, everything, everywhere, ex, exactly, example, except, f, fairly, far, farther, few, fewer, fifth, first, five, followed, following, follows, for, forever, former, formerly, forth, forward, found, four, from, further, furthermore, g, get, gets, getting, given, gives, go, goes, going, gone, got, gotten, greetings, h, had, hadn't, half, happens, hardly, has, hasn't, have, haven't, having, he, he'd, he'll, hello, help, , hence, her, here, hereafter, hereby, herein, here's, hereupon, hers, herself, he's, hi, him, himself, his, hither, hopefully, how, howbeit, however, hundred, i, i'd, ie, if, ignored, i'll, i'm, immediate, in, inasmuch, inc, inc., indeed, indicate, indicated, indicates, inner, inside, insofar, instead, into, inward, is, isn't, it, it'd, it'll, its, it's, itself, i've, j, just, k, keep, keeps, kept, know, known, knows, l, last, lately, later, latter, latterly, least, less, lest, let, let's, like, liked, likely, likewise, little, look, looking, looks, low, lower, ltd, m, made, mainly, make, makes, many, may, maybe, mayn't, me, mean, meantime, meanwhile, merely, might, mightn't, mine, minus, miss, more, moreover, most, mostly, mr, mrs, much, must, mustn't, my, myself, n, name, namely, nd, near, nearly, necessary, need, needn't, needs, neither, never, neverf, neverless, nevertheless, new, next, nine, ninety, no, nobody, non, none, nonetheless, noone, no-one, nor, normally, not, nothing, notwithstanding, novel, now, nowhere, o, obviously, of, off, often, oh, ok, okay, old, on, once, one, ones, one's, only, onto, opposite, or, other, others, otherwise, ought, oughtn't, our, ours, ourselves, out, outside, over, overall, own, p, particular, particularly, past, per, perhaps, placed, please, plus, possible, presumably, probably, provided, provides, q, que, quite, qv, r, rather, rd, re, really, reasonably, recent, recently, regarding, regardless, regards, relatively, respectively, right, round, s, said, same, saw, say, saying, says, second, secondly, , see, seeing, seem, seemed, seeming, seems, seen, self, selves, sensible, sent, serious, seriously, seven, several, shall, shan't, she, she'd, she'll, she's, should, shouldn't, since, six, so, some, somebody, someday, somehow, someone, something, sometime, sometimes, somewhat, somewhere, soon, sorry, specified, specify, specifying, still, sub, such, sup, sure, t, take, taken, taking, tell, tends, th, than, thank, thanks, thanx, that, that'll, thats, that's, that've, the, their, theirs, them, themselves, then, thence, there, thereafter, thereby, there'd, therefore, therein, there'll, there're, theres, there's, thereupon, there've, these, they, they'd, they'll, they're, they've, thing, things, think, third, thirty, this, thorough, thoroughly, those, though, three, through, throughout, thru, thus, till, to, together, too, took, toward, towards, tried, tries, truly, try, trying, t's, twice, two, u, un, under, underneath, undoing, unfortunately, unless, unlike, unlikely, until, unto, up, upon, upwards, us, use, used, useful, uses, using, usually, v, value, various, versus, very, via, viz, vs, w, want, wants, was, wasn't, way, we, we'd, welcome, well, we'll, went, were, we're, weren't, we've, what, whatever, what'll, what's, what've, when, whence, whenever, where, whereafter, whereas, whereby, wherein, where's, whereupon, wherever, whether, which, whichever, while, whilst, whither, who, who'd, whoever, whole, who'll, whom, whomever, who's, whose, why, will, willing, wish, with, within, without, wonder, won't, would, wouldn't, x, y, yes, yet, you, you'd, you'll, your, you're, yours, yourself, yourselves, you've, z, zero";
		}
		$blackArray = explode(",", $blacklist);

	    foreach($blackArray as $blackWord){
		    if(isset($keysArray[trim($blackWord)]))
				unset($keysArray[trim($blackWord)]);
		}

		arsort($keysArray);

		$i = 1;
		$keywords = '';
		foreach($keysArray as $word=>$instances){
			if($i > $count)
				break;
			if(JString::strlen(trim($word)) >= $minLength ) {
				$keywords .= $word . ", ";
				$i++;
			}
		}

		$keywords = rtrim($keywords, ", ");
		return $keywords;
    }

    function GetSEFGlobalMeta() {
        return 'f0babd6cbf2a613a8f796312bb7c8918'; // sef.global.meta
    }
    
    /**
     * Sends the request and follows redirects
     * 
     * @param string $url
     * @param string $referer
     * @param string|array $_data
     * @param string $method
     * @param string $userAgent
     * @param array $headers
     * @param int $maxRedirs
     * @return object
     */
    function PostRequestFollowRedirects($url, $referer = null, $_data = null, $method = 'post', $userAgent = null, $headers = null, $maxRedirs = 5)
    {
        $response = false;
        
        for ($redirs = 0; $redirs <= $maxRedirs; $redirs++) {
            $response = self::PostRequest($url, $referer, $_data, $method, $userAgent, $headers);
            
            // Check if we should redirect
            if (is_object($response) && $response->code >= 300 && $response->code <= 399) {
                // We got a redirect response code, try to parse new location from headers
                $matches = array();
                if (preg_match('/^Location:\s*(.*)$/im', $response->header, $matches)) {
                    // Found, update URL and send request again
                    $url = $matches[1];
                    continue;
                }
            }
            
            // No redirect, return response
            return $response;
        }
        
        return $response;
    }
    
    /**
     * Sends the POST request
     *
     * @param string $url
     * @param string $referer
     * @param array $_data
     * @return object
     */
    function PostRequest($url, $referer = null, $_data = null, $method = 'post', $userAgent = null, $headers = null) {

        // convert variables array to string:
        $data = '';
        if( is_array($_data) && count($_data) > 0 ) {
            // format --> test1=a&test2=b etc.
            $data = array();
            while( list($n, $v) = each($_data) ) {
                $data[] = "$n=$v";
            }
            $data = implode('&', $data);
            $contentType = "Content-type: application/x-www-form-urlencoded\r\n";
        }
        else {
            $data = $_data;
            $contentType = "Content-type: text/xml\r\n";
        }

        if( is_null($referer) ) {
            $referer = JURI::root();
        }

        // parse the given URL
        $url = parse_url($url);
        if( !isset($url['scheme']) ) {
            return false;
        }

        // extract host and path:
        $host = $url['host'];
        $path = isset($url['path']) ? $url['path'] : '/';

        // Prepare host and port to connect to
        $connhost = $host;
        $port = 80;
        
        // Workaround for some PHP versions, where fsockopen can't connect to
        // 'localhost' string on Windows servers
        if ($connhost == 'localhost') {
            $connhost = gethostbyname('localhost');
        }
        
        // Handle scheme
        if ($url['scheme'] == 'https') {
            $connhost = 'ssl://'.$connhost;
            $port = 443;
        }
        else if ($url['scheme'] != 'http') {
            return false;
        }
        
        // open a socket connection
        $errno = null;
        $errstr = null;
        $fp = @fsockopen($connhost, $port, $errno, $errstr, 5);
        if (!is_resource($fp) || ($fp === false)) {
            return false;
        }

        if (!is_null($userAgent)) {
            $userAgent = "User-Agent: ".$userAgent."\r\n";
        }

        // send the request
        if ($method == 'post') {
            // Check the first fputs, sometimes fsockopen doesn't fail, but fputs doesn't work
            if (!@fputs($fp, "POST $path HTTP/1.1\r\n")) {
                @fclose($fp);
                return false;
            }
            
            if (!is_null($userAgent)) {
                fputs($fp, $userAgent);
            }
            fputs($fp, "Host: $host\r\n");
            fputs($fp, "Referer: $referer\r\n");
            fputs($fp, $contentType);
            fputs($fp, "Content-length: ". strlen($data) ."\r\n");
            
            // Send additional headers if set
            if (is_array($headers)) {
                foreach ($headers as $h) {
                    $h = rtrim($h);
                    $h .= "\r\n";
                    fputs($fp, $h);
                }
            }
            
            fputs($fp, "Connection: close\r\n\r\n");
            fputs($fp, $data);
        }
        elseif ($method == 'get') {
            $query = '';
            if (isset($url['query'])) {
                $query = '?'.$url['query'];
            }
            // Check the first fputs, sometimes fsockopen doesn't fail, but fputs doesn't work
            if (!@fputs($fp, "GET {$path}{$query} HTTP/1.1\r\n")) {
                @fclose($fp);
                return false;
            }
            if (!is_null($userAgent)) {
                fputs($fp, $userAgent);
            }
            fputs($fp, "Host: $host\r\n");
            
            // Send additional headers if set
            if (is_array($headers)) {
                foreach ($headers as $h) {
                    $h = rtrim($h);
                    $h .= "\r\n";
                    fputs($fp, $h);
                }
            }
            
            fputs($fp, "Connection: close\r\n\r\n");
        }

        $result = '';
        while(!feof($fp)) {
            // receive the results of the request
            $result .= fgets($fp, 128);
        }

        // close the socket connection:
        fclose($fp);

        // split the result header from the content
        $result = explode("\r\n\r\n", $result, 2);

        $header = isset($result[0]) ? $result[0] : '';
        $content = isset($result[1]) ? $result[1] : '';

        $response = new stdClass();
        $response->header = $header;
        $response->content = $content;

        // Handle chunked transfer if needed
        if( strpos(strtolower($response->header), 'transfer-encoding: chunked') !== false ) {
            $parsed = '';
            $left = $response->content;

            while( true ) {
                $pos = strpos($left, "\r\n");
                if( $pos === false ) {
                    return $response;
                }

                $chunksize = substr($left, 0, $pos);
                $pos += strlen("\r\n");
                $left = substr($left, $pos);

                $pos = strpos($chunksize, ';');
                if( $pos !== false ) {
                    $chunksize = substr($chunksize, 0, $pos);
                }
                $chunksize = hexdec($chunksize);

                if( $chunksize == 0 ) {
                    break;
                }

                $parsed .= substr($left, 0, $chunksize);
                $left = substr($left, $chunksize + strlen("\r\n"));
            }

            $response->content = $parsed;
        }

        // Get the response code from header
        $headerLines = explode("\n", $response->header);
        $header1 = explode(' ', trim($headerLines[0]));
        $code = intval($header1[1]);
        $response->code = $code;

        return $response;
    }

    function getSEOStatus()
    {
        static $status;

        if( !isset($status) ) {
            $sefConfig = SEFConfig::getConfig();
            $status = array();

            $config = JFactory::getConfig();
            $status['sef'] = (bool)$config->get('sef');
            $status['mod_rewrite'] = (bool)$config->get('sef_rewrite');
            $status['sef_suffix'] = (bool)$config->get('sef_suffix');
            $status['joomsef'] = (bool)$sefConfig->enabled;
            $status['plugin'] = JPluginHelper::isEnabled('system', 'joomsef');
            $status['newurls'] = !$sefConfig->disableNewSEF;
        }

        return $status;
    }

    function getNonSefVars(&$uri, $nonSefVars, $ignoreVars)
    {
        $mainframe = JFactory::getApplication();
        $sefConfig = SEFConfig::getConfig();

        // Get the parameters for this component
        if( !is_null($uri->getVar('option')) ) {
            $params =& SEFTools::getExtParams($uri->getVar('option'));
        }

        // Build array of nonSef vars if set to
        $nonSef = array();
        if( $sefConfig->appendNonSef ) {
            // Save the given nonsef vars
            $nonSef = $nonSefVars;

            // load the nonSEF vars from option parameters
            $paramNonSef = array();
            if( isset($params) ) {
                $nsef = $params->get('customNonSef', '');
                if( !empty($nsef) ) {
                    // Some variables are set, let's explode them
                    $paramNonSef = explode(';', $nsef);
                }
            }

            // get globally configured nonSEF vars
            $configNonSef = array();
            if( !empty($sefConfig->customNonSef) ) {
                $configNonSef = explode(';', $sefConfig->customNonSef);
            }

            // Get nonSEF vars from variable filter test if set to
            $failedVars = array();

            // combine all the nonSEF vars arrays
            $nsefvars = array_merge($paramNonSef, $configNonSef, $failedVars);
            if (!empty($nsefvars)) {
                foreach($nsefvars as $nsefvar) {
                    // add each variable, that isn't already set, and that is present in our URL
                    if( !isset($nonSef[$nsefvar]) && !is_null($uri->getVar($nsefvar)) ) {
                        $nonSef[$nsefvar] = $uri->getVar($nsefvar);
                    }
                }
            }

            // if $nonSefVars mixes with $GLOBALS['JOOMSEF_NONSEFVARS'], exclude the mixed vars
            // this is important to prevent duplicating params by adding JOOMSEF_NONSEFVARS to
            // $ignoreSefVars
            $gNonSef = JoomSEF::get('sef.global.nonsefvars');
            if (!empty($gNonSef)) {
                foreach (array_keys($gNonSef) as $key) {
                    if (isset($nonSef[$key])) unset($gNonSef[$key]);
                }
                JoomSEF::set('sef.global.nonsefvars', $gNonSef);
            }
        }

        // Combine nonSef and ignore vars
        if (!empty($ignoreVars)) {
            $nonSef = array_merge($nonSef, $ignoreVars);
        }
        
        // Globally add the Smart Search's highlight variable
        if (!is_null($uri->getVar('highlight'))) {
            $nonSef['highlight'] = $uri->getVar('highlight');
        }
        
        // If the component requests strict accept variables filtering, add the ones that don't match
        if( isset($params) && ($params->get('acceptStrict', '0') == '1') ) {
            $acceptVars =& SEFTools::getExtAcceptVars($uri->getVar('option'));
            $uriVars = $uri->getQuery(true);
            if( (count($acceptVars) > 0) && (count($uriVars) > 0) ) {
                foreach($uriVars as $name => $value) {
                    // Standard Joomla variables
                    if( in_array($name, array('option', 'Itemid', 'limit', 'limitstart', 'format', 'tmpl', 'lang')) ) {
                        continue;
                    }
                    // Accepted variables
                    if( in_array($name, $acceptVars) ) {
                        continue;
                    }

                    // Variable not accepted, add it to non-SEF
                    $nonSef[$name] = $value;
                }
            }
        }

        return $nonSef;
    }

    function getHomeQueries($includeLang = true)
    {
        // Cache result to save DB queries!!!
        static $items;
        
        if (!isset($items)) {
            $db = JFactory::getDBO();
            $query = $db->getQuery(true);
            $query->select('id, link, language')->from('#__menu')->where('home=1');
            $db->setQuery($query);
            $items = $db->loadObjectList('id');
        }
        
        return $items;
    }
	
	function getMenuItemSubDomains($id)
    {
        // Cache results to save DB queries!!!
        static $items;
        
        if (!isset($items)) {
            $items = array();
        }
        if (!isset($items[$id])) {
            $db = JFactory::getDBO();
            $query = $db->getQuery(true);
            $query->select('link')->from('#__menu')->where('id='.$id);
            $db->setQuery($query);
            $items[$id] = $db->loadResult();
        }
        
        return $items[$id];
	}
	
    function getAllSubdomains()
    {
        // Cache subdomains data to save DB queries!
        static $subdomains;
        
        if (!isset($subdomains)) {
            $subdomains = array();
            
            // Load all subdomains
            $db = JFactory::getDBO();
            $query = $db->getQuery(true);
            $query->select('*')->from('#__sef_subdomains');
            $db->setQuery($query);
            $rows = $db->loadObjectList();
            
            // Create structure of subdomains according to languages
            foreach ($rows as $row) {
                // Add subdomain to correct language
                if (!empty($row->lang)) {
                    $subdomains[$row->lang][] = $row;
                }
                if ($row->lang != '*') {
                    $subdomains['*'][] = $row;
                }
            }
        }

        return $subdomains;
    }
    
	function getSubDomain($Itemid,$uri,&$titlepage)
    {
		$sefConfig=SEFConfig::getConfig();
		$titlepage=false;
		$option=$uri->getVar('option');
		$db=JFactory::getDBO();
        
        $lang = '*';
        if ($sefConfig->langEnable) {
            $lang = $uri->getVar('lang', '*');
        }
        
        // Get subdomains
        $subdomains = self::getAllSubdomains();
        
        if (!array_key_exists($lang, $subdomains)) {
            // No subdomain for given language
            return null;
        }
        
		for($i=0;$i<count($subdomains[$lang]);$i++) {
			$Itemids=explode(",",$subdomains[$lang][$i]->Itemid);
			if($Itemid==$subdomains[$lang][$i]->Itemid_titlepage) {
				$link=new JURI(self::getMenuItemSubDomains($Itemid));
				$uri_query=$uri->getQuery(true);
				$titlepage=true;
				foreach($link->getQuery(true) as $opt=>$val) {
					if($val!=@$uri_query[$opt]) {
						$titlepage=false;
					}
				}
				return $subdomains[$lang][$i]->subdomain;
			}
			if($option==$subdomains[$lang][$i]->option) {
				return $subdomains[$lang][$i]->subdomain;	
			}
			if(strlen($subdomains[$lang][$i]->Itemid) && in_array($Itemid,$Itemids)) {
				return $subdomains[$lang][$i]->subdomain;
			}
		}
		return null;
	}
	
    function normalizeURI(&$uri)
    {
        $option = $uri->getVar('option');
        if (!is_null($option)) {
            $extfile = JPATH_ROOT.'/components/com_sef/sef_ext/'.$option.'.php';
            if (file_exists($extfile)) {
                require_once($extfile);
                $class = 'SefExt_'.$option;

                $ext = new $class();

                $ext->beforeCreate($uri);

                list($nonsef, $ignore) = $ext->getNonSefVars($uri);
                if (!empty($ignore)) {
                    $nonsef = array_merge($nonsef, $ignore);
                }
                $keys = array_keys($nonsef);

                SEFTools::RemoveVariables($uri, $keys);
            }
        }
    }

    /**
     * Checks, whether the given Itemid is ignored for the given component
     *
     * @param string $option
     * @param string $Itemid
     * @return bool
     */
    function isItemidIgnored($option, $Itemid)
    {
        $params =& SEFTools::getExtParams($option);
        $ignoredIds = trim($params->get('ignoreItemids', ''));

        if (empty($ignoredIds)) {
            return false;
        }

        $ids = array_map('trim', explode(',', $ignoredIds));

        if (in_array($Itemid, $ids)) {
            return true;
        }

        return false;
    }

    public static function getInstalledComponents()
    {
        static $components;

        if (!isset($components)) {
            $db = JFactory::getDbo();

            // Get components
            $db->setQuery("SELECT `name`, `element` AS `option` FROM `#__extensions` WHERE `type` = 'component'");
            $components = $db->loadObjectList('option');
            if (is_null($components)) {
                $components = array();
                return $components;
            }

            // Remove system components
            $remove = array('com_sef', 'com_admin', 'com_cache', 'com_categories', 'com_checkin', 'com_config', 'com_cpanel', 'com_installer', 'com_joomfish', 'com_joomlaupdate', 'com_languages', 'com_login', 'com_media', 'com_menus', 'com_messages', 'com_modules', 'com_plugins', 'com_redirect', 'com_templates', 'com_falang');
            foreach($remove as $r) {
                if (isset($components[$r])) {
                    unset($components[$r]);
                }
            }

            // Translate names
            $lang = JFactory::getLanguage();
            foreach($components as &$item) {
                $extension = $item->option;
                $source = JPATH_ADMINISTRATOR . '/components/' . $extension;
                    $lang->load("$extension.sys", JPATH_ADMINISTRATOR, null, false, false)
                ||  $lang->load("$extension.sys", $source, null, false, false)
                ||  $lang->load("$extension.sys", JPATH_ADMINISTRATOR, $lang->getDefault(), false, false)
                ||  $lang->load("$extension.sys", $source, $lang->getDefault(), false, false);
                $item->name = JText::_($item->name);
            }

            // Sort by name
            uasort($components, array('SEFTools', 'cmpComponents'));
        }

        return $components;
    }

    protected static function cmpComponents(&$a, &$b)
    {
        return strnatcasecmp($a->name, $b->name);
    }

    function getDefaultParams($element)
    {
        //$element = $this->manifest->getElementByPath('install/defaultparams');
        //$element=$this->manifest->install->defaultparams;

        if( !is_a($element, 'SimpleXMLElement') || !count($element->children()) ) {
            return '';
        }

		$defaultParams = $element->children();
		if( count($defaultParams) == 0 ) {
			return '';
		}

		$params = array();
		foreach($defaultParams as $param) {
		    if( $param->getName() != 'defaultParam' ) {
		        continue;
		    }

		    $name = $param->attributes()->name;
		    $value = $param->attributes()->value;

		    $params[] = $name . '=' . $value;
		}

		if( count($params) > 0 ) {
		    return implode("\n", $params);
		}
		else {
		    return '';
		}
    }

    function getDefaultFilters($element)
    {
        //$element = $this->manifest->getElementByPath('install/defaultfilters');
        //$element=$this->manigest->install->defaultfilters;

        if( !is_a($element, 'SimpleXMLElement') || !count($element->children()) ) {
            return '';
        }

		$defaultFilters = $element->children();
		if( count($defaultFilters) == 0 ) {
			return '';
		}

		$filters = array();
		foreach($defaultFilters as $filter) {
		    if( $filter->getName() != 'defaultFilter' ) {
		        continue;
		    }

		    $filters[] = (string)$filter;
		}

		if( count($filters) > 0 ) {
		    return implode("\n", $filters);
		}
		else {
		    return '';
		}
    }
    
    /**
     * Redirects current page to given URL, handles different Joomla versions.
     * By default uses 303 code, if $moved is true, uses 301 code.
     * Optionally you can also specify message.
     * 
     * @param string $url
     * @param bool $moved
     * @param string $message
     * @param string $type
     */
    public static function redirect($url, $moved = false, $message = '', $type = 'message')
    {
        $app = JFactory::getApplication();
        
        // Handle redirect according to Joomla version
        if (version_compare(JVERSION, '3.2', 'ge')) {
            // New handling
            if ($message) {
                $app->enqueueMessage($message, $type);
            }
            $app->redirect($url, $moved);
        }
        else {
            // Old handling
            $app->redirect($url, $message, $type, $moved);
        }
        
        $app->close();
    }
}
?>