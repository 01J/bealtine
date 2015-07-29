<?php

/**
 * Componet for creating Fusion Charts XML source in graphic mode.
 * Can create, modified and deleting assorted Fusion Charts. Charts
 * can display on frontend page by module or content plugin.  
 *
 * @version		$Id$
 * @package     ArtioFusioncharts
 * @copyright	Copyright (C) 2010 ARTIO s.r.o.. All rights reserved. 
 * @author 		ARTIO s.r.o., http://www.artio.net
 * @license     GNU/GPL http://www.gnu.org/copyleft/gpl.html
 * @link        http://www.artio.net Official website
 */

defined('_JEXEC') or die('Restricted access');

$chartName = JText::_('Single Series Line 2D');
$chartType = 1;

$chartSetting = array();
$chartSetting['useTrendLines'] = true;
$chartSetting['sampleMask'] = JText::_('Line');
$chartSetting['addIsSliced'] = false;

$chartLanguages = array();
$chartLanguages['addLabel'] = JText::_('Add data point');
$chartLanguages['removeLabel'] = JText::_('Remove data points');
?>
