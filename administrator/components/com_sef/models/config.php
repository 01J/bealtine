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

jimport('joomla.filesystem.folder');

require_once(JPATH_COMPONENT.'/helpers/artio-update.php');

class SEFModelConfig extends SEFModel
{
    private $_menuitems=null;
    function __construct()
    {
        parent::__construct();
    }

    function getLists()
    {
        $db =& JFactory::getDBO();
        $sefConfig = SEFConfig::getConfig();

        $std_opt = 'class="inputbox" size="2"';

        $lists['enabled']           = $this->booleanRadio('enabled',            $std_opt, $sefConfig->enabled);
        $lists['professionalMode']  = $this->booleanRadio('professionalMode',   $std_opt, $sefConfig->professionalMode);
        $lists['lowerCase']         = $this->booleanRadio('lowerCase',          $std_opt, $sefConfig->lowerCase);
        $lists['disableNewSEF']     = $this->booleanRadio('disableNewSEF',      $std_opt, $sefConfig->disableNewSEF);
        $lists['dontRemoveSid']     = $this->booleanRadio('dontRemoveSid',      $std_opt, $sefConfig->dontRemoveSid);
        $lists['setQueryString']    = $this->booleanRadio('setQueryString',     $std_opt, $sefConfig->setQueryString);
        $lists['parseJoomlaSEO']    = $this->booleanRadio('parseJoomlaSEO',     $std_opt, $sefConfig->parseJoomlaSEO);
        $lists['checkJunkUrls']     = $this->booleanRadio('checkJunkUrls',      $std_opt, $sefConfig->checkJunkUrls);
        $lists['preventNonSefOverwrite']    = $this->booleanRadio('preventNonSefOverwrite', $std_opt, $sefConfig->preventNonSefOverwrite);

        $basehrefs[] = JHTML::_('select.option', _COM_SEF_BASE_HOMEPAGE,    JText::_('COM_SEF_ONLY_BASE_URL'));
        $basehrefs[] = JHTML::_('select.option', _COM_SEF_BASE_CURRENT,     JText::_('COM_SEF_FULL_SEO_URL'));
        $basehrefs[] = JHTML::_('select.option', _COM_SEF_BASE_NONE,        JText::_('COM_SEF_DISABLE_BASE_HREF'));
        $basehrefs[] = JHTML::_('select.option', _COM_SEF_BASE_IGNORE,      JText::_('COM_SEF_LEAVE_ORIGINAL'));
        $lists['check_base_href'] = JHTML::_('select.genericlist', $basehrefs, 'check_base_href', 'class="inputbox" size="1"', 'value', 'text', $sefConfig->check_base_href);

        // www and non-www handling
        $wwws[] = JHTML::_('select.option', _COM_SEF_WWW_NONE,          JText::_('COM_SEF_DONT_HANDLE'));
        $wwws[] = JHTML::_('select.option', _COM_SEF_WWW_USE_WWW,       JText::_('COM_SEF_USE_WWW'));
        $wwws[] = JHTML::_('select.option', _COM_SEF_WWW_USE_NONWWW,    JText::_('COM_SEF_USE_NON_WWW'));
        $lists['wwwHandling'] = JHTML::_('select.genericlist', $wwws, 'wwwHandling', 'class="inputbox" size="1"', 'value', 'text', $sefConfig->wwwHandling);

        $lists['langEnable']     = $this->booleanRadio('langEnable', $std_opt, $sefConfig->langEnable);
        $langPlacement=array();
        $langPlacement[] = JHTML::_('select.option', _COM_SEF_LANG_PATH,   JText::_('COM_SEF_INCLUDE_IN_PATH'));
        //$langPlacement[] = JHTML::_('select.option', _COM_SEF_LANG_SUFFIX, JText::_('COM_SEF_ADD_AS_SUFFIX'));
        $langPlacement[] = JHTML::_('select.option', _COM_SEF_LANG_DOMAIN, JText::_('COM_SEF_USE_DIFFERENT_DOMAINS'));
        $lists['langPlacementJoomla'] = JHTML::_('select.genericlist', $langPlacement, 'langPlacementJoomla', 'class="inputbox" size="1"', 'value', 'text', $sefConfig->langPlacementJoomla);
        $lists['alwaysUseLangJoomla']     = $this->booleanRadio('alwaysUseLangJoomla', $std_opt, $sefConfig->alwaysUseLangJoomla);
        $lists['alwaysUseLangHomeJoomla'] = $this->booleanRadio('alwaysUseLangHomeJoomla', $std_opt, $sefConfig->alwaysUseLangHomeJoomla);
        $lists['addLangMulti']     = $this->booleanRadio('addLangMulti', $std_opt, $sefConfig->addLangMulti);
        $lists['translateItems'] = $this->booleanRadio('translateItems',$std_opt,$sefConfig->translateItems);
        $lists['browserLangJoomla']     = $this->booleanRadio('browserLangJoomla', $std_opt, $sefConfig->browserLangJoomla);
        $lists['langCookieJoomla']     = $this->booleanRadio('langCookieJoomla', $std_opt, $sefConfig->langCookieJoomla);
        
        $langs = JLanguageHelper::getLanguages();
        
        $subdomains = array();
        $sitemaps = array();
        $mainlangs = array();
        foreach ($langs as $lang) {
            $l = new stdClass();
            $l->title = $lang->title;
            $l->sef = $lang->sef;
            $l->value = isset($sefConfig->subDomainsJoomla[$l->sef]) ? $sefConfig->subDomainsJoomla[$l->sef] : JFactory::getUri()->getHost();
            $subdomains[] = $l;
            
            $mainlangs[] = JHTML::_('select.option', $lang->sef, $lang->title, 'id', 'title');
            
            $s = new stdClass();
            $s->title = $lang->title;
            $s->sef = $lang->sef;
            $s->value = isset($sefConfig->multipleSitemapsFilenames[$lang->sef]) ? $sefConfig->multipleSitemapsFilenames[$lang->sef] : 'sitemap_'.$lang->sef;
            $sitemaps[] = $s;
        }
        $lists['mainLanguageJoomla'] = JHTML::_('select.genericlist', $mainlangs, 'mainLanguageJoomla', 'class="inputbox"', 'id', 'title', $sefConfig->mainLanguageJoomla);
        $lists['subdomainsJoomla'] = $subdomains;
        $lists['multipleSitemapsFilenames'] = $sitemaps;
        
        // Options for automatic VM currency selection according to language
        if (JFolder::exists(JPATH_ADMINISTRATOR.'/components/com_virtuemart')) {
            $lists['vm_installed'] = true;
            
            $lists['vmCurrencyEnable'] = $this->booleanRadio('vmCurrencyEnable', $std_opt, $sefConfig->vmCurrencyEnable);
            
            // Prepare array of available currencies (used modified SQL from mod_virtuemart_currencies)
            try {
                $query = "SELECT `vendor_accepted_currencies`, `vendor_currency` FROM `#__virtuemart_vendors` LIMIT 1";
                $db->setQuery($query);
                $vendor_currency = $db->loadAssoc();
            } catch (RuntimeException $e) {
                $vendor_currency = null;
            }
            
            if (!is_array($vendor_currency)) {
                $lists['vm_installed'] = false;
            }
            else {
                $accepted = trim($vendor_currency['vendor_accepted_currencies']);
                $currency = trim($vendor_currency['vendor_currency']);
                $allCurrencies = $accepted;
                if ($currency)
                    $allCurrencies .= ($allCurrencies ? ',' : '').$currency;
                
                try {
                    $query = "SELECT `virtuemart_currency_id` AS `id`, CONCAT_WS(' ', `currency_name`, `currency_symbol`) AS `title`"
                        ." FROM `#__virtuemart_currencies` WHERE `virtuemart_currency_id` IN (".$allCurrencies.") AND published = '1' ORDER BY `ordering`, `currency_name`";
                    $db->setQuery($query);
                    $currencies = $db->loadObjectList();
                } catch (RuntimeException $e) {
                    $currencies = null;
                }
                
                if (!is_array($currencies)) {
                    $lists['vm_installed'] = false;
                }
                else {
                    $lists['vmCurrency'] = array();
                    foreach ($langs as $lang) {
                        $obj = new stdClass();
                        $def = isset($sefConfig->vmCurrency[$lang->sef]) ? $sefConfig->vmCurrency[$lang->sef] : null;
                        $obj->list = JHTML::_('select.genericlist', $currencies, 'vmCurrency['.$lang->sef.']', 'class="inputbox"', 'id', 'title', $def);
                        $obj->lang = $lang->title;
                        $lists['vmCurrency'][] = $obj; 
                    }
                }
            }
        }
        else {
            $lists['vm_installed'] = false;
        }
        
        // Options to handle domain and SEF URL languages mismatch
        $opts = array();
        $opts[] = JHTML::_('select.option', _COM_SEF_WRONG_DOMAIN_REDIRECT,     JText::_('COM_SEF_WRONG_DOMAIN_REDIRECT'));
        $opts[] = JHTML::_('select.option', _COM_SEF_WRONG_DOMAIN_404,          JText::_('COM_SEF_WRONG_DOMAIN_SHOW_404'));
        $opts[] = JHTML::_('select.option', _COM_SEF_WRONG_DOMAIN_DO_NOTHING,   JText::_('COM_SEF_WRONG_DOMAIN_DO_NOTHING'));
        $lists['wrongDomainHandling'] = JHTML::_('select.genericlist', $opts, 'wrongDomainHandling', 'class="inputbox" size="1"', 'value', 'text', $sefConfig->wrongDomainHandling);
        
        // Options to handle language and Itemid mismatch
        $disabled = (JPluginHelper::isEnabled('system','falangdriver')) ? ' disabled="disabled"' : '';
        $opts = array();
        $opts[] = JHTML::_('select.option', _COM_SEF_MISMATCHED_LANG_DONT_HANDLE,   JText::_('COM_SEF_MISMATCHED_LANG_DONT_HANDLE'));
        $opts[] = JHTML::_('select.option', _COM_SEF_MISMATCHED_LANG_DONT_SEF,      JText::_('COM_SEF_MISMATCHED_LANG_DONT_SEF'));
        $opts[] = JHTML::_('select.option', _COM_SEF_MISMATCHED_LANG_FIX,      JText::_('COM_SEF_MISMATCHED_LANG_FIX'));
        $lists['mismatchedLangHandling'] = JHTML::_('select.genericlist', $opts, 'mismatchedLangHandling', 'class="inputbox" size="1"'.$disabled, 'value', 'text', $sefConfig->mismatchedLangHandling);

        $lists['record404']             = $this->booleanRadio('record404',              $std_opt, $sefConfig->record404);
        $lists['msg404']                = $this->booleanRadio('showMessageOn404',       $std_opt, $sefConfig->showMessageOn404);
        $lists['use404itemid']          = $this->booleanRadio('use404itemid',           $std_opt, $sefConfig->use404itemid);
        $lists['nonSefRedirect']        = $this->booleanRadio('nonSefRedirect',         $std_opt, $sefConfig->nonSefRedirect);
        $lists['useMoved']              = $this->booleanRadio('useMoved',               $std_opt, $sefConfig->useMoved);
        $lists['useMovedAsk']           = $this->booleanRadio('useMovedAsk',            $std_opt, $sefConfig->useMovedAsk);
        $lists['alwaysUseLang']         = $this->booleanRadio('alwaysUseLang',          $std_opt, $sefConfig->alwaysUseLang);
        $lists['translateNames']        = $this->booleanRadio('translateNames',         $std_opt, $sefConfig->translateNames);
        $lists['contentUseIndex']       = $this->booleanRadio('contentUseIndex',        $std_opt, $sefConfig->contentUseIndex);
        $lists['allowUTF']              = $this->booleanRadio('allowUTF',               $std_opt, $sefConfig->allowUTF);
        $lists['excludeSource']         = $this->booleanRadio('excludeSource',          $std_opt, $sefConfig->excludeSource);
        $lists['reappendSource']        = $this->booleanRadio('reappendSource',         $std_opt, $sefConfig->reappendSource);
        $lists['ignoreSource']          = $this->booleanRadio('ignoreSource',           $std_opt, $sefConfig->ignoreSource);
        $lists['appendNonSef']          = $this->booleanRadio('appendNonSef',           $std_opt, $sefConfig->appendNonSef);
        $lists['transitSlash']          = $this->booleanRadio('transitSlash',           $std_opt, $sefConfig->transitSlash);
        $lists['redirectSlash']         = $this->booleanRadio('redirectSlash',          $std_opt, $sefConfig->redirectSlash);
        $lists['useCache']              = $this->booleanRadio('useCache',               $std_opt, $sefConfig->useCache);
        $lists['numberDuplicates']      = $this->booleanRadio('numberDuplicates',       $std_opt, $sefConfig->numberDuplicates);
        $lists['autoCanonical']         = $this->booleanRadio('autoCanonical',          $std_opt, $sefConfig->autoCanonical);
        $lists['cacheRecordHits']       = $this->booleanRadio('cacheRecordHits',        $std_opt, $sefConfig->cacheRecordHits);
        $lists['cacheShowErr']          = $this->booleanRadio('cacheShowErr',           $std_opt, $sefConfig->cacheShowErr);
        $lists['sefComponentUrls']      = $this->booleanRadio('sefComponentUrls',       $std_opt, $sefConfig->sefComponentUrls);
        $lists['versionChecker']        = $this->booleanRadio('versionChecker',         $std_opt, $sefConfig->versionChecker);
        $lists['artioFeedDisplay']      = $this->booleanRadio('artioFeedDisplay',       $std_opt, $sefConfig->artioFeedDisplay);
        $lists['fixIndexPhp']           = $this->booleanRadio('fixIndexPhp',            $std_opt, $sefConfig->fixIndexPhp);
        $lists['fixDocumentFormat']     = $this->booleanRadio('fixDocumentFormat',      $std_opt, $sefConfig->fixDocumentFormat);
        $lists['nonSefQueryVariables']  = $this->booleanRadio('nonSefQueryVariables',   $std_opt, $sefConfig->nonSefQueryVariables);
        $lists['autolock_urls']         = $this->booleanRadio('autolock_urls',          $std_opt, $sefConfig->autolock_urls);
        $lists['update_urls']           = $this->booleanRadio('update_urls',            $std_opt, $sefConfig->update_urls);
        $lists['rootLangRedirect303']   = $this->booleanRadio('rootLangRedirect303',    $std_opt, $sefConfig->rootLangRedirect303);
        $lists['indexPhpCurrentMenu']   = $this->booleanRadio('indexPhpCurrentMenu',    $std_opt, $sefConfig->indexPhpCurrentMenu);
        $lists['langMenuAssociations']  = $this->booleanRadio('langMenuAssociations',   $std_opt, $sefConfig->langMenuAssociations);
        $lists['homePageHits']          = $this->booleanRadio('homePageHits',           $std_opt, $sefConfig->homePageHits);
        $lists['canonicalsRemove']      = $this->booleanRadio('canonicalsRemove',       $std_opt, $sefConfig->canonicalsRemove);
        $lists['canonicalsFix']         = $this->booleanRadio('canonicalsFix',          $std_opt, $sefConfig->canonicalsFix);
        $lists['cacheSize']         = '<input type="text" name="cacheSize" size="10" class="inputbox" value="'.$sefConfig->cacheSize.'" />';
        $lists['cacheMinHits']      = '<input type="text" name="cacheMinHits" size="10" class="inputbox" value="'.$sefConfig->cacheMinHits.'" />';
        $lists['junkWords']         = '<input type="text" name="junkWords" size="40" class="inputbox" value="'.$sefConfig->junkWords.'" />';
        $lists['junkExclude']       = '<input type="text" name="junkExclude" size="40" class="inputbox" value="'.$sefConfig->junkExclude.'" />';

        $lists['tag_generator']     = '<input type="text" name="tag_generator" size="60" class="inputbox" value="'.$sefConfig->tag_generator.'" />';
        $lists['tag_googlekey']     = '<input type="text" name="tag_googlekey" size="60" class="inputbox" value="'.$sefConfig->tag_googlekey.'" />';
        $lists['tag_livekey']       = '<input type="text" name="tag_livekey" size="60" class="inputbox" value="'.$sefConfig->tag_livekey.'" />';
        $lists['tag_yahookey']      = '<input type="text" name="tag_yahookey" size="60" class="inputbox" value="'.$sefConfig->tag_yahookey.'" />';

        $lists['artioUserName']     = '<input type="text" name="artioUserName" size="60" class="inputbox" value="'.$sefConfig->artioUserName.'" />';
        $lists['artioPassword']     = '<input type="password" name="artioPassword" size="60" class="inputbox" value="'.$sefConfig->artioPassword.'" />';
        $lists['artioDownloadId']   = '<input type="text" name="artioDownloadId" size="60" class="inputbox" value="'.$sefConfig->artioDownloadId.'" />';

        $lists['logErrors']         = $this->booleanRadio('logErrors', $std_opt, $sefConfig->logErrors);
        $lists['trace']             = $this->booleanRadio('trace', $std_opt, $sefConfig->trace);
        $lists['traceLevel']        = '<input type="text" name="traceLevel" size="2" class="inputbox" value="'.$sefConfig->traceLevel.'" />';

        $useSitenameOpts[] = JHTML::_('select.option', _COM_SEF_SITENAME_BEFORE,    JText::_('COM_SEF_BEFORE_PAGE_TITLE'));
        $useSitenameOpts[] = JHTML::_('select.option', _COM_SEF_SITENAME_AFTER,     JText::_('COM_SEF_AFTER_PAGE_TITLE'));
        $useSitenameOpts[] = JHTML::_('select.option', _COM_SEF_SITENAME_NO,        JText::_('COM_SEF_NO'));
        $lists['use_sitename']  = JHTML::_('select.genericlist', $useSitenameOpts, 'use_sitename', 'class="inputbox" size="1"', 'value', 'text', $sefConfig->use_sitename);

        // metadata
        $lists['enable_metadata']       = $this->booleanRadio('enable_metadata',    $std_opt, $sefConfig->enable_metadata);

        $metadataGenerateOpts[] = JHTML::_('select.option', _COM_SEF_META_GEN_EMPTY,    JText::_('COM_SEF_ONLY_IF_ORIGINAL_EMPTY'));
        $metadataGenerateOpts[] = JHTML::_('select.option', _COM_SEF_META_GEN_ALWAYS,   JText::_('COM_SEF_ALWAYS'));
        $metadataGenerateOpts[] = JHTML::_('select.option', _COM_SEF_META_GEN_NEVER,    JText::_('COM_SEF_NEVER'));
        $lists['metadata_auto']  = JHTML::_('select.genericlist', $metadataGenerateOpts, 'metadata_auto', 'class="inputbox" size="1"', 'value', 'text', $sefConfig->metadata_auto);

        $metadataPriorityOpts[] = JHTML::_('select.option', _COM_SEF_META_PR_ORIGINAL,   JText::_('COM_SEF_PREFER_ORIGINAL'));
        $metadataPriorityOpts[] = JHTML::_('select.option', _COM_SEF_META_PR_JOOMSEF,    JText::_('COM_SEF_PREFER_JOOMSEF'));
        $metadataPriorityOpts[] = JHTML::_('select.option', _COM_SEF_META_PR_JOIN,       JText::_('COM_SEF_JOIN_BOTH'));
        $lists['rewrite_keywords']  = JHTML::_('select.genericlist', $metadataPriorityOpts, 'rewrite_keywords', 'class="inputbox" size="1"', 'value', 'text', $sefConfig->rewrite_keywords);
        $lists['rewrite_description']  = JHTML::_('select.genericlist', $metadataPriorityOpts, 'rewrite_description', 'class="inputbox" size="1"', 'value', 'text', $sefConfig->rewrite_description);

        $lists['prefer_joomsef_title']  = $this->booleanRadio('prefer_joomsef_title',    $std_opt, $sefConfig->prefer_joomsef_title);
        $lists['sitename_sep']          = '<input type="text" name="sitename_sep" size="10" class="inputbox" value="'.$sefConfig->sitename_sep.'" />';
        //$lists['rewrite_keywords']      = $this->booleanRadio('rewrite_keywords',    $std_opt, $sefConfig->rewrite_keywords);
        //$lists['rewrite_description']   = $this->booleanRadio('rewrite_description',    $std_opt, $sefConfig->rewrite_description);
        $lists['prevent_dupl']          = $this->booleanRadio('prevent_dupl',    $std_opt, $sefConfig->prevent_dupl);


        $aliases[] = JHTML::_('select.option', '0', JText::_('COM_SEF_FULL_TITLE'));
        $aliases[] = JHTML::_('select.option', '1', JText::_('COM_SEF_TITLE_ALIAS'));
        $lists['useAlias'] = JHTML::_('select.genericlist', $aliases, 'useAlias', 'class="inputbox" size="1"', 'value', 'text', $sefConfig->useAlias);

        // get a list of the static content items for 404 page
        $query = "SELECT id, title"
        ."\n FROM #__content"
        ."\n WHERE title != '404'"
        ."\n AND catid = 0"
        ."\n ORDER BY ordering"
        ;

        $db->setQuery( $query );
        $items = $db->loadObjectList();

        $options = array(JHTML::_('select.option', _COM_SEF_404_DEFAULT, '('.JText::_('COM_SEF_CUSTOM_404_PAGE').')'));
        $options[] = JHTML::_('select.option', _COM_SEF_404_FRONTPAGE, '('.JText::_('COM_SEF_FRONT_PAGE').')');
        $options[] = JHTML::_('select.option', _COM_SEF_404_JOOMLA, '('.JText::_('COM_SEF_404_JOOMLA').')');

        // assemble menu items to the array
        foreach ( $items as $item ) {
            $options[] = JHTML::_('select.option', $item->id, $item->title);
        }

        $lists['page404'] = JHTML::_('select.genericlist', $options, 'page404', 'class="inputbox" size="1"', 'value', 'text', $sefConfig->page404 );

        // Get the menu selection list
        $selections = $this->linkoptions();
        $lists['itemid404'] = JHTML::_('select.genericlist', $selections, 'itemid404', 'class="inputbox" size="1"', 'value', 'text', $sefConfig->itemid404 );

        $sql="SELECT `id`, `introtext` FROM `#__content` WHERE `title` = '404'";
        $row = null;
        $db->setQuery($sql);
        $row = $db->loadObject();

        $lists['txt404'] = isset($row->introtext) ? $row->introtext : JText::_('COM_SEF_ERROR_DEFAULT_404');
        
        $lists["google_email"]=$sefConfig->google_email;
        $lists["google_password"]=$sefConfig->google_password;
        $lists["google_id"]=$sefConfig->google_id;
        $lists["google_apikey"]=$sefConfig->google_apikey;
        $lists["google_enable"]=$this->booleanRadio('google_enable',$std_opt,$sefConfig->google_enable);
        $lists["google_demographic_reports"]=$this->booleanRadio('google_demographic_reports',$std_opt,$sefConfig->google_demographic_reports);
        $lists["google_link_attribution"]=$this->booleanRadio('google_link_attribution',$std_opt,$sefConfig->google_link_attribution);
        $lists["google_exclude_ip"]=$sefConfig->google_exclude_ip;
        $lists["google_exclude_level"]=JHTML::_('access.usergroup','google_exclude_level[]',$sefConfig->google_exclude_level,'class="inputbox" multiple="multiple" size="10"',false);
        
        foreach($this->getLangs() as $sef=>$lang) {
            $lists['subdomains_menus'][$sef] = JHTML::_('select.genericlist',$this->_getMenuItems($lang->lang_code),'subdomain_Itemid');
        }
        $lists['subdomains_remove'] = '<input class="button" type="button" onclick="remove_subdomain(this);" value="'.Jtext::_('COM_SEF_REMOVE_SUBDOMAIN').'" />';
        
        // Joomla cache info
        $conf = JFactory::getConfig();
        if ($conf->get('caching', '0') != '0') {
            $state = '<span style="color: green;">'.JText::_('COM_SEF_ENABLED').'</span>';
        }
        else {
            $state = '<span style="color: red;">'.JText::_('COM_SEF_DISABLED').'</span>';
        }
        
        $lists['cache_info'] = JText::sprintf('COM_SEF_CACHE_INFO', $state);

        $this->_lists = $lists;

        return $this->_lists;
    }
    
    private function _getMenuItems($lang) {
        $db = JFactory::getDbo();
        $db->setQuery(
            'SELECT menutype AS value, title AS text' .
            ' FROM #__menu_types' .
            ' ORDER BY title'
        );
        $menus = $db->loadObjectList();

        $query    = $db->getQuery(true);
        $query->select('a.id AS value, a.title AS text, a.level, a.menutype');
        $query->from('#__menu AS a');
        $query->where('a.parent_id > 0');
        $query->where('a.type <> '.$db->quote('url'));
        $query->where('a.client_id = 0');
        $query->where('a.language IN('.$db->quote($lang).','.$db->quote('*').')');

        $query->order('a.lft');
        //echo str_replace('#__','jos_',$query)."<br>";

        $db->setQuery($query);
        $items = $db->loadObjectList();

        // Collate menu items based on menutype
        $lookup = array();
        foreach ($items as &$item) {
            if (!isset($lookup[$item->menutype])) {
                $lookup[$item->menutype] = array();
            }
            $lookup[$item->menutype][] = &$item;

            $item->text = str_repeat('- ', $item->level).$item->text;
        }
        $items = array();

        foreach ($menus as &$menu) {
            // Start group:
            $items[] = JHtml::_('select.optgroup',    $menu->text);

            // Special "Add to this Menu" option:
            //$items[] = JHtml::_('select.option', '1', JText::_('JLIB_HTML_ADD_TO_THIS_MENU'));

            // Menu items:
            if (isset($lookup[$menu->value])) {
                foreach ($lookup[$menu->value] as &$item) {
                    $items[] = JHtml::_('select.option', $item->value, $item->text);
                }
            }

            // Finish group:
            $items[] = JHtml::_('select.optgroup',    $menu->text);
        }
        return $items;
    }
    
    function getSubDomains() {
        $menu=JHTML::_('menu.menuitems');
        
        $query="SELECT * FROM `#__sef_subdomains`";
        $query.=" WHERE `option`=".$this->_db->quote("");
        $query.=" ORDER BY `subdomain`";
        $this->_db->setQuery($query);
        $subdomains_o=$this->_db->loadObjectList();
        
        $subdomains=array();
        $lang = JFactory::getLanguage();
        $tag = $lang->getDefault();
        $this->_db->setQuery("SELECT sef FROM #__languages WHERE lang_code = ".$this->_db->quote($tag));
        $sef = $this->_db->loadResult();
        foreach($subdomains_o as $subdomain) {
            if (empty($subdomain->lang)) {
                  $subdomain->lang = $sef;
            }
            $subdomains[$subdomain->lang][]=$subdomain;
        }
        
        $data=array();
        
        foreach($subdomains as $lang=>$lsubdomains) {
            foreach($lsubdomains as $i=>$subdomain) {
                $menu=$this->_getMenuItems($this->_langs[$subdomain->lang]->lang_code);
                $item=new stdClass();
                $item->subdomain=$subdomain->subdomain;
                $item->Itemid=JHTML::_('select.genericlist',$menu,'subdomain[Itemid]['.$subdomain->lang.']['.$i.'][]',array('list.attr'=>array('size'=>10,'multiple'=>'multiple'),'list.select'=>explode(",",$subdomain->Itemid)));
                $item->Itemid_titlepage=JHTML::_('select.genericlist',$menu,'subdomain[titlepage]['.$subdomain->lang.']['.$i.']',array('list.select'=>$subdomains_o[$i]->Itemid_titlepage));
                $data[$subdomain->lang][]=$item;    
            }
            
        }
        return $data;
    }   
    
    function getLangs() {
        return $this->_langs=JLanguageHelper::getLanguages('sef');
    }
    
    /**
     * Method to store a record
     *
     * @access    public
     * @return    boolean    True on success
     */
    function store()
    {
        $db =& JFactory::getDBO();
        $sefConfig =& SEFConfig::getConfig();
        $sef_config_file = JPATH_COMPONENT . '/' . 'configuration.php';
        
        // Get POST variables through Joomla API to correctly support magic_quotes_gpc
        $post = JRequest::get('POST', JREQUEST_NOTRIM | JREQUEST_ALLOWRAW);

        // Unset the empty meta tags
        if (isset($post['metanames']) && is_array($post['metanames'])) {
            for ($i = 0, $n = count($post['metanames']); $i < $n; $i++) {
                if (empty($post['metanames'][$i])) {
                    unset($post['metanames'][$i]);
                    if (isset($post['metacontents'][$i])) {
                        unset($post['metacontents'][$i]);
                    }
                }
            }

            // Create the associative array of custom meta tags
            $post['customMetaTags'] = array_combine($post['metanames'], $post['metacontents']);
        }
        else {
            // No meta tags
            $post['customMetaTags'] = array();
        }

        // Parse the sitemap ping services
        if (isset($post['sitemap_services']) && !empty($post['sitemap_services'])) {
            $services = str_replace("\r", '', $post['sitemap_services']);
            $services = array_map('trim', explode("\n", $services));
            $post['sitemap_services'] = $services;
        }
        else {
            $post['sitemap_services'] = array();
        }
        
        // Check empty google_exclude_level
        if (!isset($post['google_exclude_level'])) {
            $post['google_exclude_level'] = array();
        }
        
        // Set values
        foreach($post as $key => $value) {
            $sefConfig->set($key, $value);
        }

        // 404
        $sql = 'SELECT id,language  FROM #__content WHERE `title` = "404"';
        $db->setQuery( $sql );

        $introtext = $post['introtext'];
        if ($page404 = $db->loadObject()){
            $sql = 'UPDATE #__content SET introtext='.$db->quote($introtext).',  modified ='.$db->quote(date("Y-m-d H:i:s")).(($page404->language == '') ? ', language="*"' : '').' WHERE `id` = '.$db->quote($page404->id).';';
        }
        else {
            $sql = 'INSERT INTO #__content (title, alias, introtext, `fulltext`, state, catid, created, created_by, created_by_alias, modified, modified_by, publish_up, publish_down, images, urls, attribs, version, ordering, metakey, metadesc, access, hits, language) '.
            'VALUES ("404", "404", '.$db->quote($introtext).', "", "1", "0", '.$db->quote(date("Y-m-d H:i:s")).', "42", "", "0000-00-00 00:00:00", "0", "2001-01-01 00:00:00", "0000-00-00 00:00:00", "", "", "menu_image=-1\nitem_title=0\npageclass_sfx=\nback_button=\nrating=0\nauthor=0\ncreatedate=0\nmodifydate=0\npdf=0\nprint=0\nemail=0", "1", "0", "", "", "1", "0", "*");';
        }

        $db->setQuery( $sql );
        if (!$db->query()) {
            echo "<script> alert('".addslashes($db->getErrorMsg())."'); window.history.go(-1); </script>\n";
            exit();
        }

        // Check the domains configuration
        if( count($sefConfig->jfSubDomains) ) {
            foreach($sefConfig->jfSubDomains as $code => $domain) {
                $domain = str_replace(array('http://', 'https://'), '', $domain);
                $domain = preg_replace('#/.*$#', '', $domain);
                $sefConfig->jfSubDomains[$code] = $domain;
            }
        }
        
        $subdomains=JRequest::getVar('subdomain',array(),'post','array');
        
        $query="DELETE FROM #__sef_subdomains \n";
        $query.="WHERE `option`=".$this->_db->quote("");
        $this->_db->setQuery($query);
        if(!$this->_db->query()) {
            $this->setError($this->_db->stderr(true));
            return false;
        }
        
        if (is_array($subdomains['title'])) {
            foreach($subdomains["title"] as $lang=>$items) {
                foreach($items as $i=>$item) {
                    $query="INSERT INTO #__sef_subdomains SET subdomain=".$this->_db->quote($item).", Itemid=".$this->_db->quote(implode(",",$subdomains["Itemid"][$lang][$i])).", \n";
                    $query.="Itemid_titlepage=".$this->_db->quote($subdomains["titlepage"][$lang][$i]).", lang=".$this->_db->quote($lang)." \n";
                    $this->_db->setQuery($query);
                    if(!$this->_db->query()) {
                        $this->setError($this->_db->stderr(true));
                        return false;
                    }
                }
            }
        }

        if (!AUpdateHelper::setUpdateLink('com_joomsef', $sefConfig->artioDownloadId)) {
            return false;
        }

        $config_written = $sefConfig->saveConfig(0);

        if( $config_written != 0 ) {
            if($sefConfig->langEnable) {
                $query=$db->getQuery(true);
                $query->select('enabled')->from('#__extensions')->where('element='.$db->quote('languagefilter'));
                $db->setQuery($query);
                $enabled=$db->loadResult();
                if($enabled==1) {
                    JError::raiseWarning('',JText::_('COM_JOOMSEF_DISABLE_LANGUAGEFILTER'));
                }
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * Fixed version of JHtmlMenu::linkoptions() for Joomla 3.0
     * until the bug with missing m.ordering column is fixed
     */
    protected function linkoptions($all = false, $unassigned = false)
    {
        if (!class_exists('JHtmlMenu')) {
            require_once(JPATH_LIBRARIES.'/joomla/html/html/menu.php');
        }
        
        $db = JFactory::getDbo();
        $query = $db->getQuery(true);

        // Get a list of the menu items
        $query->select('m.id, m.parent_id, m.title, m.menutype');
        $query->from($db->quoteName('#__menu') . ' AS m');
        $query->where($db->quoteName('m.published') . ' = 1');
        $query->order('m.menutype, m.parent_id, m.lft');
        $db->setQuery($query);

        $mitems = $db->loadObjectList();

        if (!$mitems)
        {
            $mitems = array();
        }

        // Establish the hierarchy of the menu
        $children = array();

        // First pass - collect children
        foreach ($mitems as $v)
        {
            $pt = $v->parent_id;
            $list = @$children[$pt] ? $children[$pt] : array();
            array_push($list, $v);
            $children[$pt] = $list;
        }
        // Second pass - get an indent list of the items
        $list = JHtmlMenu::TreeRecurse((int) $mitems[0]->parent_id, '', array(), $children, 9999, 0, 0);

        // Code that adds menu name to Display of Page(s)

        $mitems = array();
        if ($all | $unassigned)
        {
            $mitems[] = JHtml::_('select.option', '<OPTGROUP>', JText::_('JOPTION_MENUS'));

            if ($all)
            {
                $mitems[] = JHtml::_('select.option', 0, JText::_('JALL'));
            }
            if ($unassigned)
            {
                $mitems[] = JHtml::_('select.option', -1, JText::_('JOPTION_UNASSIGNED'));
            }

            $mitems[] = JHtml::_('select.option', '</OPTGROUP>');
        }

        $lastMenuType = null;
        $tmpMenuType = null;
        foreach ($list as $list_a)
        {
            if ($list_a->menutype != $lastMenuType)
            {
                if ($tmpMenuType)
                {
                    $mitems[] = JHtml::_('select.option', '</OPTGROUP>');
                }
                $mitems[] = JHtml::_('select.option', '<OPTGROUP>', $list_a->menutype);
                $lastMenuType = $list_a->menutype;
                $tmpMenuType = $list_a->menutype;
            }

            $mitems[] = JHtml::_('select.option', $list_a->id, $list_a->title);
        }
        if ($lastMenuType !== null)
        {
            $mitems[] = JHtml::_('select.option', '</OPTGROUP>');
        }

        return $mitems;
    }
}
?>