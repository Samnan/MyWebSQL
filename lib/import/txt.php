<?php
/**
 * This file is a part of MyWebSQL package
 * import functionality for CSV using plain text
 *
 * @file:      lib/import/text.php
 * @author     Samnan ur Rehman
 * @copyright  (c) 2008-2012 Samnan ur Rehman
 * @web        http://mywebsql.net
 * @license    http://mywebsql.net/license
 */
 

if (defined("CLASS_IMPORT_TXT_INCLUDED"))
	return true;

define("CLASS_IMPORT_TXT_INCLUDED", "1");

require( dirname(__FILE__) . '/csv.php');
class Import_txt extends Import_csv {}

?>