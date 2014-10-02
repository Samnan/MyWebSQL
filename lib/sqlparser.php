<?php
/**
 * This file is a part of MyWebSQL package
 * Sql parser functionality provider
 * includes some part of code taken and modified from PHPMyAdmin
 *
 * @file:      lib/sqlparser.php
 * @author     Samnan ur Rehman
 * @copyright  (c) 2008-2014 Samnan ur Rehman
 * @web        http://mywebsql.net
 * @license    http://mywebsql.net/license
 */

if (!defined("CLASS_SQLPARSER_INCLUDED"))
{
	define("CLASS_SQLPARSER_INCLUDED", "1");

//todo: fill stats while parsing and executing queries and display afterwards
class SqlParserStats {
	var $queriesFailed;
	var $dbChanged;  // if database got changed
	var $dbAltered;  // if database got altered
	var $tablesCreated, $tablesDropped, $tablesAltered; // table stats
	var $viewsCreated, $viewsDropped, $procsCreated, $procsDropped, $triggersCreated, $triggersDropped; // other object stats
	var $rowsAffected; // records

	function __construct() {
		$this->queriesFailed = 0;
		$this->dbChanged = FALSE;
		$this->tablesCreated = $this->tablesDropped = $this->tablesAltered = 0;
		$this->viewsCreated = $this->viewsDropped = $this->procsCreated = $this->procsDropped = $this->triggersCreated = $this->triggersDropped = 0;
		$this->rowsAffected = 0;
	}
};

class SqlParser {
	var $stopOnError;
	var $db;
	var $filePath;
	var $fileName;
	var $fileSize;
	var $fileHandle;
	var $string;
	var $parse_complete;
	var $offset;
	var $charset_conversion;
	var $compression;

	// internal
	var $read_multiply;
	var $errorMsg;
	var $lastQuery;
	var $numQueriesExecuted;
	var $numQueriesFailed;
	var $numRowsAffected;
	var $executionTime;
	var $callback;
	var $callback_param;

	// this contain list of changes done by the batch import
	var $stats;

	function SqlParser(&$db) {
		$this->db = $db;
		$this->stopOnError = true;
		$this->filePath = "";
		$this->fileName = "";
		$this->fileSize = "";
		$this->fileHandle = "";
		$this->string = "";
		$this->stats = FALSE;

		$this->callback = false;
	}

	function getError() {
		return $this->errorMsg;
	}

	function getLastQuery() {
		return $this->lastQuery;
	}

	function getExecutedQueries() {
		return $this->numQueriesExecuted;
	}

	function getFailedQueries() {
		return $this->numQueriesFailed;
	}

	function getRowsAffected() {
		return $this->numRowsAffected;
	}

	function getExecutedTime() {
		// return formatted time output
		return $this->db->getQueryTime($this->executionTime);
	}

	function stopOnError($bool=true) {
		$this->stopOnError = $bool;
	}

	function collectStats() {
		$this->stats = new SqlParserStats();
	}

	function getStats() {
		return $this->stats;
	}

	function setCallback( $fn, $param ) {
		$this->callback = $fn;
		$this->callback_param = $param;
	}

	function set_compression($compression = FALSE) {
		if ($compression) {
			if ($compression == 'none' || $compression == 'gzip' || $compression == 'bzip')
				$this->compression = $compression;
		}
		else {
			if (substr($this->fileName, -5) == '.gzip' || substr($this->fileName, -3) == '.gz')
				$this->compression = 'gzip';
			else if (substr($this->fileName, -5) == '.bzip' || substr($this->fileName, -3) == '.bz' || substr($this->fileName, -4) == '.bz2')
				$this->compression = 'bzip';
			else
				$this->compression = 'none';
		}
	}

	function parse($filePath, $fileSize, $fileName) {
		$this->filePath = $filePath;
		$this->fileSize = $fileSize;
		$this->fileName = $fileName;

		$this->set_compression();

		$mode = $this->compression == 'none' ? 'rt' : 'r';
		$func = 'fopen';
		$func2 = 'fclose';
		switch($this->compression) {
			case 'bzip':
				$func = 'bzopen';
				$func2 = 'bzclose';
			break;
			case 'gzip':
				$func = 'gzopen';
				$func2 = 'gzclose';
			break;
		}

		if (! ($handle = $func($this->filePath, $mode)) )
			return false;

		$x = $this->parseFile($handle);
		$func2($handle);

		return $x;
	}

	function parseFile($handle)
	{
		$this->numQueriesExecuted = 0;
		$this->numQueriesFailed = 0;
		$this->numRowsAffected = 0;
		$this->fileHandle = $handle;
		$this->read_multiply = 1;
		$status = false;
		$this->parse_complete = false;
		$this->charset_conversion = false;
		$this->executionTime = 0;

		$buffer = '';
		$sql = '';
		$start_pos = 0;
		$i = 0;
		$len = 0;
		$big_value = 2147483647;
		$sql_delimiter = ';';
		$error = FALSE;
		$timeout_passed = FALSE;
		$this->offset = 0;

		// Handle compatibility option
		//if (isset($_REQUEST['sql_compatibility'])) {
		//	DBI_try_query('SET SQL_MODE="' . $_REQUEST['sql_compatibility'] . '"');
		//}

		$this->executionTime = $this->db->getMicroTime();
		while (!($this->parse_complete && $i >= $len) && !$error && !$timeout_passed) {
			$data = $this->GetNextChunk();
			if ($data === FALSE) {
				// subtract data we didn't handle yet and stop processing
				$this->offset -= strlen($buffer);
				break;
			} elseif ($data === TRUE) {
				// Handle rest of buffer
			} else {
				// Append new data to buffer
				$buffer .= $data;
				// free memory
				unset($data);
				// Do not parse string when we're not at the end and don't have ; inside
				if ((strpos($buffer, $sql_delimiter, $i) === FALSE) && !$this->parse_complete)  {
					continue;
				}
			}
			// Current length of our buffer
			$len = strlen($buffer);

			// Grab some SQL queries out of it
			while ($i < $len) {
				$found_delimiter = false;
				// Find first interesting character
				$old_i = $i;
				if (preg_match('/(\'|"|#|--|\/\*|`|(?i)DELIMITER )/', $buffer, $matches, PREG_OFFSET_CAPTURE, $i)) {
					// in $matches, index 0 contains the match for the complete
					// expression but we don't use it
					$first_position = $matches[1][1];
				} else {
					$first_position = $big_value;
				}
				/**
				 * @todo we should not look for a delimiter that might be
				 *       inside quotes (or even double-quotes)
				 */
				// the cost of doing this one with preg_match() would be too high
				$first_sql_delimiter = strpos($buffer, $sql_delimiter, $i);
				if ($first_sql_delimiter === FALSE) {
					$first_sql_delimiter = $big_value;
				} else {
					$found_delimiter = true;
				}

				// set $i to the position of the first quote, comment.start or delimiter found
				$i = min($first_position, $first_sql_delimiter);

				if ($i == $big_value) {
					// none of the above was found in the string

					$i = $old_i;
					if (!$this->parse_complete) {
						break;
					}
					// at the end there might be some whitespace...
					if (trim($buffer) == '') {
						$buffer = '';
						$len = 0;
						break;
					}
					// We hit end of query, go there!
					$i = strlen($buffer) - 1;
				}

				// Grab current character
				$ch = $buffer[$i];

				// Quotes
				if (strpos('\'"`', $ch) !== FALSE) {
					$quote = $ch;
					$endq = FALSE;
					while (!$endq) {
						// Find next quote
						$pos = strpos($buffer, $quote, $i + 1);
						// No quote? Too short string
						if ($pos === FALSE) {
							// We hit end of string => unclosed quote, but we handle it as end of query
							if ($this->parse_complete) {
								$endq = TRUE;
								$i = $len - 1;
							}
							$found_delimiter = false;
							break;
						}
						// Was not the quote escaped?
						$j = $pos - 1;
						while ($buffer[$j] == '\\') $j--;
						// Even count means it was not escaped
						$endq = (((($pos - 1) - $j) % 2) == 0);
						// Skip the string
						$i = $pos;

						if ($first_sql_delimiter < $pos) {
							$found_delimiter = false;
						}
					}
					if (!$endq) {
						break;
					}
					$i++;
					// Aren't we at the end?
					if ($this->parse_complete && $i == $len) {
						$i--;
					} else {
						continue;
					}
				}

				// Not enough data to decide
				if ((($i == ($len - 1) && ($ch == '-' || $ch == '/'))
				  || ($i == ($len - 2) && (($ch == '-' && $buffer[$i + 1] == '-')
					|| ($ch == '/' && $buffer[$i + 1] == '*')))) && !$this->parse_complete) {
					break;
				}

				// Comments
				if ($ch == '#'
				 || ($i < ($len - 1) && $ch == '-' && $buffer[$i + 1] == '-'
				  && (($i < ($len - 2))
				   || ($i == ($len - 1)  && $this->parse_complete)))
				 || ($i < ($len - 1) && $ch == '/' && $buffer[$i + 1] == '*')
						) {
					// Copy current string to SQL
					if ($start_pos != $i) {
						$sql .= substr($buffer, $start_pos, $i - $start_pos);
					}
					// Skip the rest
					$j = $i;
					$i = strpos($buffer, $ch == '/' ? '*/' : "\n", $i);
					// didn't we hit end of string?
					if ($i === FALSE) {
						if ($this->parse_complete) {
							$i = $len - 1;
						} else {
							break;
						}
					}

					// Skip *
					if ($ch == '/') {
						// Check for MySQL conditional comments and include them as-is
						if ($buffer[$j + 2] == '!') {
							$comment = substr($buffer, $j + 3, $i - $j - 3);
							if (preg_match('/^[0-9]{5}/', $comment, $version)) {
								//if ($version[0] <= MYSQL_INT_VERSION) {
									$sql .= substr($comment, 5);
								//}
							} else {
								$sql .= $comment;
							}
						}
						$i++;
					}
					// Skip last char
					$i++;
					// Next query part will start here
					$start_pos = $i;
					// Aren't we at the end?
					if ($i == $len) {
						$i--;
					} else {
						continue;
					}
				}
				// Change delimiter, if redefined, and skip it (don't send to server!)
				if( strtoupper( substr( $buffer, $i, 10 ) ) == "DELIMITER " && ($i + 10 < $len) ) {
					$new_line_pos = strpos( $buffer, "\n", $i + 10 );
					// it might happen that there is no EOL
					if( FALSE === $new_line_pos ) {
						$new_line_pos = $len;
					}
					$sql_delimiter = substr( $buffer, $i + 10, $new_line_pos - $i - 10 );
					$i = $new_line_pos + 1;
					// Next query part will start here
					$start_pos = $i;
					continue;
				}

				// End of SQL
				if ($found_delimiter || ($this->parse_complete && ($i == $len - 1))) {
					$tmp_sql = $sql;
					if ($start_pos < $len) {
						$length_to_grab = $i - $start_pos;

						if (! $found_delimiter) {
							$length_to_grab++;
						}
						$tmp_sql .= substr($buffer, $start_pos, $length_to_grab);
						unset($length_to_grab);
					}
					// Do not try to execute empty SQL
					$sql = trim($tmp_sql);
					if (!preg_match('/^([\s]*;)*$/', $sql)) {
						$query_success = $this->db->query($sql);
						if ($query_success) {
							$this->numQueriesExecuted++;
							$affectedRows = $this->db->getAffectedRows();
							if ($this->stats !== FALSE)
								$this->updateStats($sql, $affectedRows);
							if (!$this->db->hasResult())
								$this->numRowsAffected += $affectedRows;
						} else {
							$this->numQueriesFailed++;
							if ($this->stats !== FALSE)
								$this->stats->queriesFailed++;
							$this->lastQuery = $sql;
							$this->errorMsg = $this->db->getError();
							if ($this->stopOnError) {
								$this->executionTime = $this->db->getMicroTime() - $this->executionTime;
								return FALSE;
							}
						}
						if ($this->callback)
							call_user_func( $this->callback, $this->callback_param, $this->numQueriesExecuted );

						$buffer = substr($buffer, $i + strlen($sql_delimiter));
						// Reset parser:
						$len = strlen($buffer);
						$sql = '';
						$i = 0;
						$start_pos = 0;

						// Any chance we will get a complete query?
						//if ((strpos($buffer, ';') === FALSE) && !$this->parse_complete) {
						if ((strpos($buffer, $sql_delimiter) === FALSE) && !$this->parse_complete) {
							break;
						}
					} else {
						$i++;
						$start_pos = $i;
					}
				}
			} // End of parser loop
			$status = true;
		} // End of import loop

		$this->executionTime = $this->db->getMicroTime() - $this->executionTime;
		return $status;
	}

	function GetNextChunk($size = 32768)
	{
		// Add some progression while reading large amount of data
		if ($this->read_multiply <= 8) {
			$size *= $this->read_multiply;
		} else {
			$size *= 8;
		}
		$this->read_multiply++;

		/*if (checkTimeout()) {
			return FALSE;
		}*/

		if ($this->parse_complete) {
			return TRUE;
		}

		$result = '';
		switch ($this->compression) {
			case 'bzip':
				$result = bzread($this->fileHandle, $size);
				$this->parse_complete = feof($this->fileHandle);
				break;
			case 'gzip':
				$result = gzread($this->fileHandle, $size);
				$this->parse_complete = feof($this->fileHandle);
				break;
			default:
				$result = fread($this->fileHandle, $size);
				$this->parse_complete = feof($this->fileHandle);
				break;
		}
		$this->offset += $size;

		if ($this->charset_conversion) {
			return $result;
			//return convert_string($charset_of_file, $charset, $result);
		} else {
			/**
			 * Skip possible byte order marks (I do not think we need more
			 * charsets, but feel free to add more, you can use wikipedia for
			 * reference: <http://en.wikipedia.org/wiki/Byte_Order_Mark>)
			 *
			 * @todo BOM could be used for charset autodetection
			 */
			if ($this->offset == $size) {
				// UTF-8
				if (strncmp($result, "\xEF\xBB\xBF", 3) == 0) {
					$result = substr($result, 3);
				// UTF-16 BE, LE
				} elseif (strncmp($result, "\xFE\xFF", 2) == 0 || strncmp($result, "\xFF\xFE", 2) == 0) {
					$result = substr($result, 2);
				}
			}
			return $result;
		}
	}

	function updateStats(&$sql, $affectedRows) {
		$info = getCommandInfo($sql);
		if ($info['dbChanged'])
			$this->stats->dbChanged = TRUE;
		if ($info['dbAltered'])
			$this->stats->dbAltered = TRUE;
	}
}

}
?>