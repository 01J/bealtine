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

jimport('joomla.application.component.model');

class SEFModelImport extends SEFModel
{
    var $type = 'None';
    var $total = 0;
    var $imported = 0;
    var $notImported = 0;

    var $aceSefTablePresent = false;
    var $shSefTablePresent = false;
    var $dbChecked = false;

    /**
     * Constructor that retrieves variables from the request
     */
    function __construct()
    {
        parent::__construct();
    }

    function import()
    {
        // Get the uploaded file information
        $userfile = JRequest::getVar('importfile', null, 'files', 'array' );

        // Make sure that file uploads are enabled in php
        if (!(bool) ini_get('file_uploads')) {
            JError::raiseWarning(100, JText::_('COM_SEF_UPLOADS_NOT_ALLOWED'));
            return false;
        }

        // If there is no uploaded file, we have a problem...
        if (!is_array($userfile)) {
            JError::raiseWarning(100, JText::_('COM_SEF_NO_FILE_SELECTED'));
            return false;
        }

        // Check if there was a problem uploading the file.
        if ($userfile['error'] || $userfile['size'] < 1) {
            JError::raiseWarning(100, JText::_('COM_SEF_ERROR_FILE_UPLOAD'));
            return false;
        }

        // Build the appropriate paths
        $config     =& JFactory::getConfig();
        $tmp_dest 	= $config->get('tmp_path').'/'.$userfile['name'];
        $tmp_src	= $userfile['tmp_name'];

        // Move uploaded file
        jimport('joomla.filesystem.file');
        $uploaded = JFile::upload($tmp_src, $tmp_dest);
        if( !$uploaded ) {
            JError::raiseWarning( 100, JText::_('COM_SEF_ERROR_UPLOAD_FILE') );
            return false;
        }

        // load SQL
        $lines = file($tmp_dest);

        // We can delete the file now
        JFile::delete($tmp_dest);

        if( !is_array($lines) )
        {
            JError::raiseWarning( 100, JText::_('COM_SEF_ERROR_LOAD_FILE') );
            return false;
        }

        // Determine the export type (JoomSEF, sh404SEF, AceSEF)
        $type = $this->_determineType($lines);

        $result = true;
        $this->total = $this->imported = $this->notImported = 0;
        switch($type)
        {
            case 'JoomSEF':
                $this->type = JText::_('COM_SEF_JOOMSEF_URLS');
                $result = $this->_importJoomSEF($lines);
                break;

            case 'sh404SEF':
                $this->type = JText::_('COM_SEF_SH404SEF_URLS');
                $result = $this->_importSh404SEF($lines);
                break;

            case 'sh404SEFmeta':
                $this->type = JText::_('COM_SEF_SH404SEF_META_TAGS');
                $result = $this->_importSh404SEFmeta($lines);
                break;

            case 'sh404SEF_2_2_7_981':
                $this->type = JText::_('COM_SEF_SH404SEF_URLS_227981');
                $result = $this->_importSh404SEF_2_2_7_981($lines);
                break;

            case 'AceSEF':
                $this->type = JText::_('COM_SEF_ACESEF_URLS');
                $result = $this->_importAceSEF($lines);
                break;

            default:
                $this->type = JText::_('COM_SEF_UNKNOWN');
                JError::raiseWarning( 100, JText::_('COM_SEF_UNRECOGNIZED_FILE_FORMAT') );
                $result = false;
        }
        $this->total = $this->imported + $this->notImported;

        return $result;
    }

    function importDBAce()
    {
        $fieldsMap = array( 'cpt' => 'cpt',
        'sefurl' => 'url_sef',
        'origurl' => 'url_real',
        'Itemid' => 'Itemid',
        'metadesc' => 'metadesc',
        'metakey' => 'metakey',
        'metatitle' => 'metatitle',
        'metalang' => 'metalang',
        'metarobots' => 'metarobots',
        'metagoogle' => 'metagoogle',
        'canonicallink' => 'metacanonical',
        'dateadd' => 'date',
        'priority' => 'ordering');

        $db =& JFactory::getDBO();

        // Get all the data we need
        $query = "SELECT * FROM `#__acesef_urls`";
        $db->setQuery($query);
        $rows = $db->loadAssocList();

        $result = true;
        for( $i = 0, $n = count($rows); $i < $n; $i++ ) {
            $row =& $rows[$i];

            // Modify the assoc array to match our needs
            $row['cpt'] = 0;
            $row['Itemid'] = SEFTools::extractVariable($row['url_real'], 'Itemid');

            // Insert line to database
            if( !SEFModelImport::_insertLine($row, $fieldsMap) ) {
                $result = false;
            }
        }

        $this->type = JText::_('COM_SEF_ACESEF_DATABASE');

        return $result;
    }

    function importDBSh()
    {
        $fieldsMap = array( 'cpt' => 'cpt',
        'sefurl' => 'oldurl',
        'origurl' => 'newurl',
        'Itemid' => 'Itemid',
        'metadesc' => 'metadesc',
        'metakey' => 'metakey',
        'metatitle' => 'metatitle',
        'metalang' => 'metalang',
        'metarobots' => 'metarobots',
        'metagoogle' => 'metagoogle',
        'canonicallink' => 'canonicallink',
        'dateadd' => 'dateadd',
        'priority' => 'rank');

        $db =& JFactory::getDBO();

        // Get all the data we need
        $query = "SELECT `r`.*, `m`.`metadesc`, `m`.`metakey`, `m`.`metatitle`, `m`.`metalang`, `m`.`metarobots` FROM `#__sh404sef_urls` AS `r` LEFT JOIN `#__sh404sef_metas` AS `m` ON (`m`.`newurl` = `r`.`newurl`)";
        $db->setQuery($query);
        $rows = $db->loadAssocList();
        
        $result = true;
        for( $i = 0, $n = count($rows); $i < $n; $i++ ) {
            $row =& $rows[$i];

            // Modify the assoc array to match our needs
            $row['Itemid'] = SEFTools::extractVariable($row['newurl'], 'Itemid');
            if (isset($row['rank'])) {
                if ($row['rank'] != '0') {
                    $row['rank'] = '100';
                }
            }
            else {
                if( !empty($row['Itemid']) ) {
                    $row['rank'] = 90;
                } else {
                    $row['rank'] = 95;
                }
            }

            // Insert line to database
            if( !SEFModelImport::_insertLine($row, $fieldsMap) ) {
                $result = false;
            }
        }

        $this->type = JText::_('COM_SEF_SH404SEF_DATABASE');

        return $result;
    }

    function getAceSefTablePresent()
    {
        if( !$this->dbChecked )
        {
            $this->_checkDB();
        }

        return $this->aceSefTablePresent;
    }

    function getShTablePresent()
    {
        if( !$this->dbChecked )
        {
            $this->_checkDB();
        }

        return $this->shSefTablePresent;
    }

    function _determineType(&$lines)
    {
        $type = 'Unknown';

        $n = count($lines);
        if( $n == 0 )
        {
            return $type;
        }

        // Loop through lines trying to determine file format
        for( $i = 0; $i < $n; $i++ )
        {
            $line = trim($lines[$i]);

            if( preg_match('/^INSERT\s+INTO\s+`?\w+(redirection|sefurls)`?/i', $line) > 0 )
            {
                $type = 'JoomSEF';
                break;
            }

            if( preg_match('/^INSERT\s+INTO\s+`?\w+acesef_urls`?/i', $line) > 0 )
            {
                $type = 'AceSEF';
                break;
            }

            if( strpos(strtolower($line), '"id","count","rank","sef url","non-sef url","date added"') !== false )
            {
                $type = 'sh404SEF';
                break;
            }

            if( strpos(strtolower($line), '"id","newurl","metadesc","metakey","metatitle","metalang","metarobots"') !== false )
            {
                $type = 'sh404SEFmeta';
                break;
            }
            
            if( strpos(strtolower($line), '"nbr","sef url","non sef url","hits","rank","date added","page title","page description","page keywords","page language","robots tag"') !== false )
            {
                $type = 'sh404SEF_2_2_7_981';
                break;
            }
        }

        return $type;
    }

    function _importJoomSEF(&$lines)
    {
        $config =& JFactory::getConfig();
        $dbprefix = $config->get('dbprefix');
        
        // Get allowed columns
        $fields = $this->_db->getTableColumns('#__sefurls');
        $columns = array_keys($fields);

        $result = true;
        for ($i = 0, $n = count($lines); $i < $n; $i++) {
            // Trim line
            $line = trim($lines[$i]);
            // Ignore empty lines
            if (strlen($line) == 0) continue;

            // If the query continues at the next line.
            while (substr($line, -1) != ';' && $i + 1 < count($lines)) {
                $i++;
                $newLine = trim($lines[$i]);
                if (strlen($newLine) == 0) continue;
                $line .= ' '.$lines[$i];
            }

            if (preg_match('/^INSERT(\s)+INTO(\s)+`?(\w)+(redirection|sefurls)`?/i', $line) > 0) {
                // fix for files exported from versions older than 1.3.0
                if (strstr($line, "redirection` VALUES") != false) {
                    $line = str_replace("redirection` VALUES", "redirection` (id, cpt, sefurl, origurl, dateadd) VALUES", $line);
                }

                // fix for files exported from versions prior to 2.0.0
                if (!strstr($line, 'origurl') && stristr($line, "newurl, Itemid") == false) {
                    $url = preg_replace('/.*VALUES\s*\(\'[^\']*\',\s*\'[^\']*\',\s*\'[^\']*\',\s*\'([^\']*)\'.*/', '$1', $line);
                    $itemid = preg_replace('/.*[&?]Itemid=([^&]*).*/', '$1', $url);

                    //$newurl = eregi_replace("Itemid=[^&]*", '', $url);
                    
                    $newurl = preg_replace('/Itemid=[^&]*/i', '', $url);
                    $newurl = trim($newurl, '&?');
                    $trans = array( '&&' => '&', '?&' => '?' );
                    $newurl = strtr($newurl, $trans);

                    $line = str_replace('newurl,', 'newurl, Itemid,', $line);
                    $line = str_replace("'$url'", "'$newurl','$itemid'", $line);
                }

                // upgrade to 3.3.0
                $line = str_replace(array('redirection', 'newurl', 'oldurl'), array('sefurls', 'origurl', 'sefurl'), $line);

                // Fix the table name for different prefix
                $line = str_replace('jos_sefurls', "{$dbprefix}sefurls", $line);
                
                // REMOVE UNKNOWN COLUMNS
                // Parse columns and values
                $matches = array();
                if (preg_match('#^INSERT\s+INTO[^(]+\(([^)]+)\)\s+VALUES[^(]+\((.+)\)[^)]*$#i', $line, $matches) > 0) {
                    $cols = $this->_parseCsvLine($matches[1]);
                    $vals = $this->_parseCsvLine($matches[2], ',', "'");
                    
                    // Create associative array of columns and values
                    if (count($cols) == count($vals)) {
                        $cols = array_map('trim', $cols);
                        $values = array_combine($cols, $vals);
                        
                        // Remove unknown columns
                        foreach ($cols as $col) {
                            if (!in_array($col, $columns)) {
                                unset($values[$col]);
                            }
                        }
                        
                        // Update SQL
                        $cols = implode(', ', array_keys($values));
                        $vals = array();
                        foreach ($values as $val) {
                            $vals[] = "'".str_replace("'", "\\'", $val)."'";
                        }
                        $vals = implode(', ', $vals);
                        $line = str_replace(array($matches[1], $matches[2]), array($cols, $vals), $line);
                    }
                }
                
                $this->_db->setQuery($line);
                if (!$this->_db->query()) {
                    $this->notImported++;
                    JError::raiseWarning( 100, JText::_('COM_SEF_ERROR_IMPORTING_LINE') . ': ' . $line . '<br />' . $this->_db->getErrorMsg());
                    $result = false;
                }
                else {
                    $this->imported++;
                }
            }
            else {
                JError::raiseWarning(100, JText::_('COM_SEF_IGNORING_LINE') . ': ' . $line);
            }
        }

        return $result;
    }

    function _importSh404SEF(&$lines)
    {
        $fieldsMap = array( 'cpt' => 'Count',
        'sefurl' => 'SEF URL',
        'origurl' => 'non-SEF URL',
        'Itemid' => 'Itemid',
        'metadesc' => 'metadesc',
        'metakey' => 'metakey',
        'metatitle' => 'metatitle',
        'metalang' => 'metalang',
        'metarobots' => 'metarobots',
        'metagoogle' => 'metagoogle',
        'canonicallink' => 'canonicallink',
        'dateadd' => 'Date added',
        'priority' => 'priority');

        $result = true;
        $fields = array();
        for ($i = 0, $n = count($lines); $i < $n; $i++) {
            // Trim line
            $line = trim($lines[$i]);
            // Ignore empty lines
            if (strlen($line) == 0) continue;

            // Split the line to values
            $values = $this->_parseCsvLine($line);
            $this->_cleanFields($values);

            // If this is the first parsed line, use it for field names
            if( count($fields) == 0 ) {
                $fields = $values;
                continue;
            }

            // Create the associative array of fields and values
            $assoc = array_combine($fields, $values);

            // Modify the assoc array to match our needs
            $assoc['Itemid'] = SEFTools::extractVariable($assoc['non-SEF URL'], 'Itemid');
            if( !empty($assoc['Itemid']) ) {
                $assoc['priority'] = 90;
            } else {
                $assoc['priority'] = 95;
            }

            // Insert line to database
            if( !SEFModelImport::_insertLine($assoc, $fieldsMap) ) {
                $result = false;
            }
        }

        return $result;
    }

    function _importSh404SEF_2_2_7_981(&$lines)
    {
        $fieldsMap = array( 'cpt' => 'Hits',
        'sefurl' => 'Sef url',
        'origurl' => 'Non sef url',
        'Itemid' => 'Itemid',
        'metadesc' => 'Page description',
        'metakey' => 'Page keywords',
        'metatitle' => 'Page title',
        'metalang' => 'Page language',
        'metarobots' => 'Robots tag',
        'metagoogle' => 'metagoogle',
        'canonicallink' => 'canonicallink',
        'dateadd' => 'Date added',
        'priority' => 'Rank');

        $result = true;
        $fields = array();
        for ($i = 0, $n = count($lines); $i < $n; $i++) {
            // Trim line
            $line = trim($lines[$i]);
            // Ignore empty lines
            if (strlen($line) == 0) continue;

            // Split the line to values
            $values = $this->_parseCsvLine($line);
            $this->_cleanFields($values);

            // If this is the first parsed line, use it for field names
            if( count($fields) == 0 ) {
                $fields = $values;
                continue;
            }
            
            // Create the associative array of fields and values
            $assoc = array_combine($fields, $values);

            // Modify the assoc array to match our needs
            $assoc['Itemid'] = SEFTools::extractVariable($assoc['Non sef url'], 'Itemid');
            if (isset($assoc['Rank'])) {
                if ($assoc['Rank'] != '0') {
                    $assoc['Rank'] = '100';
                }
            }

            // Insert line to database
            if( !SEFModelImport::_insertLine($assoc, $fieldsMap) ) {
                $result = false;
            }
        }

        return $result;
    }
    
    function _parseCsvLine($line, $comma = ',', $quote = '"')
    {
        if (strpos($line, $quote) === false) {
            return explode($comma, $line);
        }
        
        $len = strlen($line);
        $values = array();
        while ($len > 0) {
            $pos_comma = strpos($line, $comma);
            $pos_quote = strpos($line, $quote);
            
            if (is_int($pos_comma)) {
                // More values
                if (!is_int($pos_quote) || ($pos_comma < $pos_quote)) {
                    // Value without enclosure
                    $value = substr($line, 0, $pos_comma);
                    $line = substr($line, $pos_comma + 1);
                }
                else {
                    // Enclosed value
                    $found = false;
                    $offset = $pos_quote + 1;
                    while (!$found) {
                        $pos_quote2 = strpos($line, $quote, $offset);
                        if ($pos_quote2 === false) {
                            return false;
                        }
                        if (($pos_quote2 == $offset) || ($line[$pos_quote2 - 1] != '\\')) {
                            // Ending enclosure
                            $value = substr($line, $pos_quote + 1, $pos_quote2 - $pos_quote - 1);
                            $value = str_replace('\\'.$quote, $quote, $value);
                            
                            // Truncate line
                            $pos_comma = strpos($line, $comma, $pos_quote2 + 1);
                            if (is_int($pos_comma)) {
                                $line = substr($line, $pos_comma + 1);
                            }
                            else {
                                $line = substr($line, $pos_quote2 + 1);
                            }
                            $found = true;
                        }
                        else {
                            // Escaped enclosure
                            $offset = $pos_quote2 + 1;
                        }
                    }
                }
            }
            else if (!is_int($pos_comma)) {
                // Last value
                if (is_int($pos_quote)) {
                    // Enclosed
                    $value = trim($line);
                    $value = trim($value, $quote);
                    $value = str_replace('\\'.$quote, $quote, $value);
                }
                else {
                    // Not enclosed
                    $value = $line;
                }
                $line = '';
            }
            
            $values[] = $value;
            $len = strlen($line);
        }
        
        return $values;
    }

    function _importSh404SEFmeta(&$lines)
    {
        $fieldsMap = array( 'origurl' => 'newurl',
        'Itemid' => 'Itemid',
        'metadesc' => 'metadesc',
        'metakey' => 'metakey',
        'metatitle' => 'metatitle',
        'metalang' => 'metalang',
        'metarobots' => 'metarobots');

        $updateKeys = array('origurl', 'Itemid');

        $result = true;
        $fields = array();
        for ($i = 0, $n = count($lines); $i < $n; $i++) {
            // Trim line
            $line = trim($lines[$i]);
            // Ignore empty lines
            if (strlen($line) == 0) continue;

            // Split the line to values
            $values = $this->_parseCsvLine($line);
            $this->_cleanFields($values);
            $this->_shUnEmptyFields($values);

            // If this is the first parsed line, use it for field names
            if( count($fields) == 0 ) {
                $fields = $values;
                continue;
            }

            // Create the associative array of fields and values
            $assoc = array_combine($fields, $values);

            // Modify the assoc array to match our needs
            $assoc['Itemid'] = SEFTools::extractVariable($assoc['newurl'], 'Itemid');

            // Update line in database
            if( !SEFModelImport::_updateLine($assoc, $fieldsMap, $updateKeys) ) {
                $result = false;
            }
        }

        return $result;
    }

    function _importAceSEF(&$lines)
    {
        $fieldsMap = array( 'cpt' => 'cpt',
        'sefurl' => 'url_sef',
        'origurl' => 'url_real',
        'Itemid' => 'Itemid',
        'metadesc' => 'metadesc',
        'metakey' => 'metakey',
        'metatitle' => 'metatitle',
        'metalang' => 'metalang',
        'metarobots' => 'metarobots',
        'metagoogle' => 'metagoogle',
        'canonicallink' => 'metacanonical',
        'dateadd' => 'date',
        'priority' => 'ordering');

        $result = true;
        for ($i = 0, $n = count($lines); $i < $n; $i++) {
            // Trim line
            $line = trim($lines[$i]);
            // Ignore empty lines
            if (strlen($line) == 0) continue;

            // If the query continues at the next line.
            while (substr($line, -1) != ';' && $i + 1 < count($lines)) {
                $i++;
                $newLine = trim($lines[$i]);
                if (strlen($newLine) == 0) continue;
                $line .= ' '.$lines[$i];
            }

            if (preg_match('/^INSERT\s+INTO\s+`?\w+acesef_urls`?/i', $line) > 0) {
                // Parse the line
                $pos = strpos($line, '(');
                if( $pos !== false ) {
                    $line = substr($line, $pos+1);
                }
                $line = str_replace(');', '', $line);

                // Split the line to fields and values
                list($fields, $values) = explode(') VALUES (', $line);

                $fields = explode(',', $fields);
                $values = explode("', '", $values);

                $this->_cleanFields($fields);
                $this->_cleanFields($values);

                // Create the associative array of fields and values
                $assoc = array_combine($fields, $values);

                // Modify the assoc array to match our needs
                $assoc['cpt'] = 0;
                $assoc['Itemid'] = SEFTools::extractVariable($assoc['url_real'], 'Itemid');

                // Insert line to database
                if( !SEFModelImport::_insertLine($assoc, $fieldsMap) ) {
                    $result = false;
                }
            }
            else {
                JError::raiseWarning(100, JText::_('COM_SEF_IGNORING_LINE') . ': ' . $line);
            }
        }

        return $result;
    }

    function _cleanFields(&$fields)
    {
        for( $i = 0, $n = count($fields); $i < $n; $i++ ) {
            $fields[$i] = trim($fields[$i], " `'\"");
        }
    }

    function _shUnEmptyFields(&$fields)
    {
        for( $i = 0, $n = count($fields); $i < $n; $i++ ) {
            if( $fields[$i] == '&nbsp' ) {
                $fields[$i] = '';
            }
        }
    }

    function _insertLine(&$assoc, &$fieldsMap)
    {
        // Build the SQL query
        $query = "INSERT INTO `#__sefurls` (";
        $keys = array_keys($fieldsMap);
        $query .= '`' . implode('`, `', $keys) . '`) VALUES (';

        for( $j = 0, $n2 = count($keys); $j < $n2; $j++ )
        {
            $key = $keys[$j];
            $map = $fieldsMap[$key];

            if( isset($assoc[$map]) ) {
                $query .= "'" . $assoc[$map] . "'";
            }
            else {
                $query .= "''";
            }

            if( $j < ($n2 - 1) ) {
                $query .= ', ';
            }
        }
        $query .= ')';

        // Try to run the query
        $this->_db->setQuery($query);
        if (!$this->_db->query()) {
            $this->notImported++;
            JError::raiseWarning( 100, JText::_('COM_SEF_ERROR_IMPORTING_LINE') . ': ' . $query . '<br />' . $this->_db->getErrorMsg());
            return false;
        }
        else {
            $this->imported++;
        }

        return true;
    }

    function _updateLine(&$assoc, &$fieldsMap, &$updateKeys)
    {
        // Build the SQL query
        $query = "UPDATE `#__sefurls` SET ";
        $keys = array_keys($fieldsMap);

        $set = array();
        $where = array();

        for( $j = 0, $n2 = count($keys); $j < $n2; $j++ )
        {
            $key = $keys[$j];
            $map = $fieldsMap[$key];

            if( isset($assoc[$map]) ) {
                $value = "`$key` = '{$assoc[$map]}'";
                if( in_array($key, $updateKeys) ) {
                    $where[] = '(' . $value . ')';
                }
                else {
                    $set[] = $value;
                }
            }
        }

        if( (count($set) == 0 )|| (count($where) == 0) ) {
            return false;
        }

        // Add the set, where and limit parts
        $query .= implode(', ', $set);
        $query .= ' WHERE ' . implode(' AND ', $where);
        $query .= ' LIMIT 1';

        // Try to run the query
        $this->_db->setQuery($query);
        if (!$this->_db->query()) {
            $this->notImported++;
            JError::raiseWarning( 100, JText::_('COM_SEF_ERROR_IMPORTING_LINE') . ': ' . $query . '<br />' . $this->_db->getErrorMsg());
            return false;
        }
        else {
            $this->imported++;
        }

        return true;
    }

    function _checkDB()
    {
        $db = JFactory::getDBO();
        $config = JFactory::getConfig();
        $prefix = $config->get('dbprefix');

        $tables = $db->getTableList();
        
        // Check AceSEF installation
        if (in_array($prefix.'acesef_urls', $tables)) {
            $this->aceSefTablePresent = true;
        }

        // Check sh404SEF installation
        if (in_array($prefix.'sh404sef_urls', $tables)) {
            $query = "SELECT * FROM `#__sh404sef_urls` LIMIT 1";
            $db->setQuery($query);
            $row = $db->loadObject();
            if(isset($row->oldurl) && isset($row->newurl)) {
                $this->shSefTablePresent = true;
            }
        }

        $this->dbChecked = true;
    }
}
?>