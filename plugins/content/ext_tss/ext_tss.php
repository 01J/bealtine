<?php defined('_JEXEC') or die;
/**
 * @package    content.plg_ext_tss
 * @version    1.3.0
 * @copyright  Copyright (C) 2013 joomext.ru All rights reserved.
 * @author     Yuliya Popova, seoelle@gmail.com
 * @license    GNU General Public License version 3 or later; see LICENSE.txt
 */

class plgContentExt_tss extends JPlugin
{
  public function onContentPrepare($context, &$row, &$params, $page = 0)
  {
    if ($context=='com_finder.indexer') return true;
    $document = JFactory::getDocument();
    $jquery = $this->params->get( 'jquery');
    if (version_compare(JVERSION, '3', 'ge')) JHtml::_('jquery.framework');
    if (isset($jquery)){
      $document->addScript('//ajax.googleapis.com/ajax/libs/jquery/'.$jquery.'/jquery.min.js');
    }
    $document->addScriptDeclaration('var elle_sliders_nfa = '.(int)$this->params->get('sliders_nfa',0).';');
    $document->addScript(JURI::base().'media/ext_tss/assets/js/ext_tss.js', "text/javascript", true);
    $document->addStyleSheet(JURI::base().'media/ext_tss/assets/css/ext_tss.css');
    
    if(JFactory::getApplication()->input->getCmd('view')=='article' && preg_match("/{tab=.+?}|{slider=.+?}|{spoiler=.+?}/s", $row->text))
      $this->replace_plugin($row, $params, $page = 0);

    if ($this->params->get('tabs')=='1')
     $this->replace_tab($row, $params, $page = 0);
    if ($this->params->get('sliders')=='1')
      $this->replace_slider($row, $params, $page = 0);
    if ($this->params->get('spoilers')=='1')
      $this->replace_spoiler($row, $params, $page = 0);
    if(!preg_match("#{tab=.+?}|{slider=.+?}|{spoiler=.+?}#s", $row->text)) return;
  }

  // --- Tabs ---
  function replace_tab(&$row, &$params, $page = 0)
  {
    $tclass = $this->params->get('tabs_title_class');
    $dclass = $this->params->get('tabs_content_class');
    $htag = $this->params->get('tabs_title_print_tag');
    if((JFactory::getApplication()->input->getCmd('tmpl')!='component')) {
      $a=1;
      unset($tabs);
      if(preg_match_all("/{tab=.+?}{tab=.+?}|{tab=.+?}|{\/tabs}/", $row->text, $matches, PREG_PATTERN_ORDER) > 0) {
        $row->text = preg_replace('|<[^>]+>{tab(.*)}</[^>]+>|U', '{tab\\1}', $row->text);
        $row->text = preg_replace('|<[^>]+>{/tabs}</[^>]+>|U', '{/tabs}', $row->text);
        foreach($matches[0] as $match) {
          if($a==1 && $match!="{/tabs}") {
            $tabs[] = 1;
            $a=2;
          } elseif($match=="{/tabs}"){
            $tabs[]=3;
            $a=1;
          } elseif(preg_match("/{tab=.+?}{tab=.+?}/", $match)){
            $tabs[]=2;
            $tabs[]=1;
            $a=2;
          } else {
            $tabs[]=2;
          }
        }
      }
      @reset($tabs);
      $tabscount = 0;
      if(preg_match_all("/{tab=.+?}|{\/tabs}/", $row->text, $matches, PREG_PATTERN_ORDER) > 0) {
        $tabsid = 1;
        foreach($matches[0] as $match) {
          $tabid = $tabscount + 1;
          if($tabs[$tabscount]==1) {
            $tabs_id = $tabsid;
            $tabsid++;
            $match = str_replace("{tab=", "", $match);
            $match = str_replace("}", "", $match);
            $matches = explode("|",$match,3);
            if (!isset($matches[2])) $matches[2] = '';
            if (!isset($matches[1])) $matches[1] = '';
            $title_class = trim($tclass.' '.$matches[1]);
            $desc_class = trim($dclass.' '.$matches[2]);
            $row->text = str_replace("{tab=".$match."}", '<dl class="tabs" id="tabs'.$tabs_id.'"><dt id="tab'.$tabid.'" class="'.$title_class.' selected"><a name="tabs'.$tabs_id.'-tab'.$tabid.'"></a>'.$matches[0].'</dt><dd id="ctab'.$tabid.'" class="'.$desc_class.' selected"><div class="tab-content">', $row->text);
          } elseif($tabs[$tabscount]==2) {
            $match = str_replace("{tab=", "", $match);
            $match = str_replace("}", "", $match);
            $matches = explode("|",$match,3);
            if (!isset($matches[2])) $matches[2] = '';
            if (!isset($matches[1])) $matches[1] = '';
            $title_class = trim($tclass.' '.$matches[1]);
            $desc_class = trim($dclass.' '.$matches[2]);
            $row->text = str_replace("{tab=".$match."}", '</div></dd><dt id="tab'.$tabid.'" class="'.$title_class.'"><a name="tabs'.$tabs_id.'-tab'.$tabid.'"></a>'.$matches[0].'</dt><dd id="ctab'.$tabid.'" class="'.$desc_class.'"><div class="tab-content">', $row->text);
            $row->text = str_replace(' class=""', '', $row->text);
         } elseif($tabs[$tabscount]==3) {
            $row->text = str_replace("{/tabs}", '</div></dd></dl><div class="tabs_clr"></div>', $row->text);
          }
          $tabscount++;
        }
      }
    } else {
      if(preg_match_all("/{tab=.+?}/", $row->text, $matches, PREG_PATTERN_ORDER) > 0) {
        foreach($matches[0] as $match) {
          $match = str_replace("{tab=", "", $match);
          $match = str_replace("}", "", $match);
          $matches = explode("|",$match,3);
          $row->text = str_replace("{tab=".$match."}", '<'.$htag.'>'.$matches[0].'</'.$htag.'>', $row->text);
          $row->text = str_replace("{/tabs}", '', $row->text);
        }
      }
    }
  }

  // --- Slider ---
  function replace_slider(&$row, &$params, $page = 0)
  {
    $tclass = $this->params->get('sliders_title_class');
    $dclass = $this->params->get('sliders_content_class');
    $htag = $this->params->get('sliders_title_print_tag');
    if((JFactory::getApplication()->input->getCmd('tmpl')!='component')) {
      $b=1;
      unset($sliders);
      if(preg_match_all("/{slider=.+?}{slider=.+?}|{slider=.+?}|{\/sliders}/", $row->text, $matches, PREG_PATTERN_ORDER) > 0) {
        $row->text = preg_replace('|<[^>]+>{slider(.*)}</[^>]+>|U', '{slider\\1}', $row->text);
        $row->text = preg_replace('|<[^>]+>{/sliders}</[^>]+>|U', '{/sliders}', $row->text);
        foreach($matches[0] as $match) {
          if($b==1 && $match!="{/sliders}") {
            $sliders[] = 1;
            $b=2;
          } elseif($match=="{/sliders}"){
            $sliders[]=3;
            $b=1;
          } elseif(preg_match("/{slider=.+?}{slider=.+?}/", $match)){
            $sliders[]=2;
            $sliders[]=1;
            $b=2;
          } else {
            $sliders[]=2;
          }
        }
      }
      @reset($sliders);
      $sliderscount = 0;
      if(preg_match_all("/{slider=.+?}|{\/sliders}/", $row->text, $matches, PREG_PATTERN_ORDER) > 0) {
        $sliderid=1;
        foreach($matches[0] as $match) {
          if($sliders[$sliderscount]==1) {
            $match = str_replace("{slider=", "", $match);
            $match = str_replace("}", "", $match);
            $matches = explode("|",$match,3);
            if (!isset($matches[2])) $matches[2] = '';
            if (!isset($matches[1])) $matches[1] = '';
            $title_class = trim('title '.$tclass.' '.$matches[1]);
            $desc_class = trim('desc '.$dclass.' '.$matches[2]);
            $row->text = str_replace("{slider=".$match."}", '<div class="sliders" id="slider'.$sliderid.'"><div class="'.$title_class.'">'.$matches[0].'<div class="mark"></div></div><div class="'.$desc_class.'">', $row->text);
            $sliderid++;
          } elseif($sliders[$sliderscount]==2) {
            $match = str_replace("{slider=", "", $match);
            $match = str_replace("}", "", $match);
            $matches = explode("|",$match,3);
            if (!isset($matches[2])) $matches[2] = '';
            if (!isset($matches[1])) $matches[1] = '';
            $title_class = trim('title '.$tclass.' '.$matches[1]);
            $desc_class = trim('desc '.$dclass.' '.$matches[2]);
            $row->text = str_replace("{slider=".$match."}", '</div><div class="'.$title_class.'">'.$matches[0].'<div class="mark"></div></div><div class="'.$desc_class.'">', $row->text);
          } elseif($sliders[$sliderscount]==3) {
            $row->text = str_replace("{/sliders}", '</div></div><div class="sliders_clr"></div>', $row->text);
          }
          $sliderscount++;
        }
      }
    } else {
      if(preg_match_all("/{slider=.+?}/", $row->text, $matches, PREG_PATTERN_ORDER) > 0) {
        foreach($matches[0] as $match) {
          $match = str_replace("{slider=", "", $match);
          $match = str_replace("}", "", $match);
          $matches = explode("|",$match,3);
          $row->text = str_replace("{slider=".$match."}", '<'.$htag.'>'.$matches[0].'</'.$htag.'>', $row->text);
          $row->text = str_replace("{/sliders}", '', $row->text);
        }
      }
    }
  }

  // --- Spoiler ---
  function replace_spoiler(&$row, &$params, $page = 0)
  {
    $tclass = $this->params->get('spoilers_title_class');
    $dclass = $this->params->get('spoilers_content_class');
    $htag = $this->params->get('spoilers_title_print_tag');
    if((JFactory::getApplication()->input->getCmd('tmpl')!='component')) {
      $c=1;
      unset($spoilers);
      if(preg_match_all("/{spoiler=.+?}{spoiler=.+?}|{spoiler=.+?}|{\/spoilers}/", $row->text, $matches, PREG_PATTERN_ORDER) > 0) {
        $row->text = preg_replace('|<[^>]+>{spoiler(.*)}</[^>]+>|U', '{spoiler\\1}', $row->text);
        $row->text = preg_replace('|<[^>]+>{/spoilers}</[^>]+>|U', '{/spoilers}', $row->text);
        foreach($matches[0] as $match) {
          if($c==1 && $match!="{/spoilers}") {
            $spoilers[] = 1;
            $c=2;
          } elseif($match=="{/spoilers}"){
            $spoilers[]=3;
            $c=1;
          } elseif(preg_match("/{spoiler=.+?}{spoiler=.+?}/", $match)){
            $spoilers[]=2;
            $spoilers[]=1;
            $c=2;
          } else {
            $spoilers[]=2;
          }
        }
      }
      @reset($spoilers);
      $spoilerscount = 0;
      if(preg_match_all("/{spoiler=.+?}|{\/spoilers}/", $row->text, $matches, PREG_PATTERN_ORDER) > 0) {
        $spoilerid=1;
        foreach($matches[0] as $match) {
          $ereg = '/}(.+){/';
          $mat = str_replace($ereg, "", $match);
          if($spoilers[$spoilerscount]==1) {
            $match = str_replace("{spoiler=", "", $match);
            $match = str_replace("}", "", $match);
            $matches = explode("|",$match,3);
            if (!isset($matches[2])) $matches[2] = '';
            if (!isset($matches[1])) $matches[1] = '';
            $title_class = trim('title '.$tclass.' '.$matches[1]);
            $desc_class = trim('desc '.$dclass.' '.$matches[2]);
            $row->text = str_replace("{spoiler=".$match."}", '<div class="spoilers" id="spoiler'.$spoilerid.'"><div class="'.$title_class.'">'.$matches[0].'<div class="mark"></div></div><div class="'.$desc_class.'">', $row->text);
            $spoilerid++;
          } elseif($spoilers[$spoilerscount]==2) {
            $match = str_replace("{spoiler=", "", $match);
            $match = str_replace("}", "", $match);
            $matches = explode("|",$match,3);
            if (!isset($matches[2])) $matches[2] = '';
            if (!isset($matches[1])) $matches[1] = '';
            $title_class = trim('title '.$tclass.' '.$matches[1]);
            $desc_class = trim('desc '.$dclass.' '.$matches[2]);
            $row->text = str_replace("{spoiler=".$match."}", '</div><div class="'.$title_class.'">'.$matches[0].'<div class="mark"></div></div><div class="'.$desc_class.'">', $row->text);
          } elseif($spoilers[$spoilerscount]==3) {
            $row->text = str_replace("{/spoilers}", '</div></div><div class="spoilers_clr"></div>', $row->text);
          }
          $spoilerscount++;
        }
      }
    } else {
      if(preg_match_all("/{spoiler=.+?}/", $row->text, $matches, PREG_PATTERN_ORDER) > 0) {
        foreach($matches[0] as $match) {
          $match = str_replace("{spoiler=", "", $match);
          $match = str_replace("}", "", $match);
          $matches = explode("|",$match,3);
          $row->text = str_replace("{spoiler=".$match."}", '<'.$htag.'>'.$matches[0].'</'.$htag.'>', $row->text);
          $row->text = str_replace("{/spoilers}", '', $row->text);
        }
      }
    }
  }

  function replace_plugin(&$row, &$params, $page = 0)
  {
    $this->loadLanguage(); 
    $row->text .= JText::_('PLG_EXT_TSS_PLUGIN');
  }
}