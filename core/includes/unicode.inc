<?php

/**
 * @file
 * Provides Unicode-related conversions and operations.
 */

use Drupal\Component\Utility\Unicode;
use Drupal\Component\Utility\String;

/**
 * Returns Unicode library status and errors.
 */
function unicode_requirements() {
  $libraries = array(
    Unicode::STATUS_SINGLEBYTE => t('Standard PHP'),
    Unicode::STATUS_MULTIBYTE => t('PHP Mbstring Extension'),
    Unicode::STATUS_ERROR => t('Error'),
  );
  $severities = array(
    Unicode::STATUS_SINGLEBYTE => REQUIREMENT_WARNING,
    Unicode::STATUS_MULTIBYTE => NULL,
    Unicode::STATUS_ERROR => REQUIREMENT_ERROR,
  );
  $failed_check = Unicode::check();
  $library = Unicode::getStatus();

  $requirements['unicode'] = array(
    'title' => t('Unicode library'),
    'value' => $libraries[$library],
    'severity' => $severities[$library],
  );
  $t_args = array('@url' => 'http://www.php.net/mbstring');
  switch ($failed_check) {
    case 'mb_strlen':
      $requirements['unicode']['description'] = t('Operations on Unicode strings are emulated on a best-effort basis. Install the <a href="@url">PHP mbstring extension</a> for improved Unicode support.', $t_args);
      break;

    case 'mbstring.func_overload':
      $requirements['unicode']['description'] = t('Multibyte string function overloading in PHP is active and must be disabled. Check the php.ini <em>mbstring.func_overload</em> setting. Please refer to the <a href="@url">PHP mbstring documentation</a> for more information.', $t_args);
      break;

    case 'mbstring.encoding_translation':
      $requirements['unicode']['description'] = t('Multibyte string input conversion in PHP is active and must be disabled. Check the php.ini <em>mbstring.encoding_translation</em> setting. Please refer to the <a href="@url">PHP mbstring documentation</a> for more information.', $t_args);
      break;

    case 'mbstring.http_input':
      $requirements['unicode']['description'] = t('Multibyte string input conversion in PHP is active and must be disabled. Check the php.ini <em>mbstring.http_input</em> setting. Please refer to the <a href="@url">PHP mbstring documentation</a> for more information.', $t_args);
      break;

    case 'mbstring.http_output':
      $requirements['unicode']['description'] = t('Multibyte string output conversion in PHP is active and must be disabled. Check the php.ini <em>mbstring.http_output</em> setting. Please refer to the <a href="@url">PHP mbstring documentation</a> for more information.', $t_args);
      break;
  }

  return $requirements;
}

/**
 * Prepares a new XML parser.
 *
 * This is a wrapper around xml_parser_create() which extracts the encoding
 * from the XML data first and sets the output encoding to UTF-8. This function
 * should be used instead of xml_parser_create(), because PHP 4's XML parser
 * doesn't check the input encoding itself. "Starting from PHP 5, the input
 * encoding is automatically detected, so that the encoding parameter specifies
 * only the output encoding."
 *
 * This is also where unsupported encodings will be converted. Callers should
 * take this into account: $data might have been changed after the call.
 *
 * @param $data
 *   The XML data which will be parsed later.
 *
 * @return
 *   An XML parser object or FALSE on error.
 *
 * @ingroup php_wrappers
 */
function drupal_xml_parser_create(&$data) {
  // Default XML encoding is UTF-8
  $encoding = 'utf-8';
  $bom = FALSE;

  // Check for UTF-8 byte order mark (PHP5's XML parser doesn't handle it).
  if (!strncmp($data, "\xEF\xBB\xBF", 3)) {
    $bom = TRUE;
    $data = substr($data, 3);
  }

  // Check for an encoding declaration in the XML prolog if no BOM was found.
  if (!$bom && preg_match('/^<\?xml[^>]+encoding="(.+?)"/', $data, $match)) {
    $encoding = $match[1];
  }

  // Unsupported encodings are converted here into UTF-8.
  $php_supported = array('utf-8', 'iso-8859-1', 'us-ascii');
  if (!in_array(strtolower($encoding), $php_supported)) {
    $out = drupal_convert_to_utf8($data, $encoding);
    if ($out !== FALSE) {
      $encoding = 'utf-8';
      $data = preg_replace('/^(<\?xml[^>]+encoding)="(.+?)"/', '\\1="utf-8"', $out);
    }
    else {
      \Drupal::logger('php')->warning('Could not convert XML encoding %s to UTF-8.', array('%s' => $encoding));
      return FALSE;
    }
  }

  $xml_parser = xml_parser_create($encoding);
  xml_parser_set_option($xml_parser, XML_OPTION_TARGET_ENCODING, 'utf-8');
  return $xml_parser;
}

/**
 * Converts data to UTF-8.
 *
 * @param string $data
 *   The data to be converted.
 * @param string $encoding
 *   The encoding that the data is in.
 *
 * @return string|bool
 *   Converted data or FALSE.
 *
 * @see \Drupal\Component\Utility\Unicode::convertToUtf8().
 */
function drupal_convert_to_utf8($data, $encoding) {
  $out = Unicode::convertToUtf8($data, $encoding);
  if ($out === FALSE) {
    \Drupal::logger('php')->error('Unsupported encoding %s. Please install iconv, GNU recode or mbstring for PHP.', array('%s' => $encoding));
  }

  return $out;
}

/**
 * Truncates a UTF-8-encoded string safely to a number of bytes.
 *
 * @param string $string
 *   The string to truncate.
 * @param int $len
 *   An upper limit on the returned string length.
 *
 * @return string
 *   The truncated string.
 *
 * @see \Drupal\Component\Utility\Unicode::truncateBytes().
 */
function drupal_truncate_bytes($string, $len) {
  return Unicode::truncateBytes($string, $len);
}

/**
 * Truncates a UTF-8-encoded string safely to a number of characters.
 *
 * @param $string
 *   The string to truncate.
 * @param $max_length
 *   An upper limit on the returned string length, including trailing ellipsis
 *   if $add_ellipsis is TRUE.
 * @param $wordsafe
 *   If TRUE, attempt to truncate on a word boundary. Word boundaries are
 *   spaces, punctuation, and Unicode characters used as word boundaries in
 *   non-Latin languages; see Unicode::PREG_CLASS_WORD_BOUNDARY for more
 *   information. If a word boundary cannot be found that would make the length
 *   of the returned string fall within length guidelines (see parameters
 *   $max_length and $min_wordsafe_length), word boundaries are ignored.
 * @param $add_ellipsis
 *   If TRUE, add t('...') to the end of the truncated string (defaults to
 *   FALSE). The string length will still fall within $max_length.
 * @param $min_wordsafe_length
 *   If $wordsafe is TRUE, the minimum acceptable length for truncation (before
 *   adding an ellipsis, if $add_ellipsis is TRUE). Has no effect if $wordsafe
 *   is FALSE. This can be used to prevent having a very short resulting string
 *   that will not be understandable. For instance, if you are truncating the
 *   string "See myverylongurlexample.com for more information" to a word-safe
 *   return length of 20, the only available word boundary within 20 characters
 *   is after the word "See", which wouldn't leave a very informative string. If
 *   you had set $min_wordsafe_length to 10, though, the function would realise
 *   that "See" alone is too short, and would then just truncate ignoring word
 *   boundaries, giving you "See myverylongurl..." (assuming you had set
 *   $add_ellipses to TRUE).
 *
 * @return string
 *   The truncated string.
 *
 * @see \Drupal\Component\Utility\Unicode::truncate().
 */
function truncate_utf8($string, $max_length, $wordsafe = FALSE, $add_ellipsis = FALSE, $min_wordsafe_length = 1) {
  return Unicode::truncate($string, $max_length, $wordsafe, $add_ellipsis, $min_wordsafe_length);
}

/**
 * Encodes MIME/HTTP header values that contain incorrectly encoded characters.
 *
 * @param $string
 *   The header to encode.
 *
 * @return string
 *   The mime-encoded header.
 *
 * @see mime_header_decode()
 * @see \Drupal\Component\Utility\Unicode::mimeHeaderEncode().
 */
function mime_header_encode($string) {
  return Unicode::mimeHeaderEncode($string);
}

/**
 * Decodes MIME/HTTP encoded header values.
 *
 * @param $header
 *   The header to decode.
 *
 * @return string
 *   The mime-decoded header.
 *
 * @see mime_header_encode()
 * @see \Drupal\Component\Utility\Unicode::mimeHeaderDecode().
 */
function mime_header_decode($header) {
  return Unicode::mimeHeaderDecode($header);
}

/**
 * Decodes all HTML entities (including numerical ones) to regular UTF-8 bytes.
 *
 * @param $text
 *   The text to decode entities in.
 *
 * @return
 *   The input $text, with all HTML entities decoded once.
 *
 * @see \Drupal\Component\Utility\String::decodeEntities().
 */
function decode_entities($text) {
  return String::decodeEntities($text);
}

/**
 * Counts the number of characters in a UTF-8 string.
 *
 * @param $text
 *   The string to run the operation on.
 *
 * @return integer
 *   The length of the string.
 *
 * @see \Drupal\Component\Utility\Unicode::strlen().
 * @ingroup php_wrappers
 */
function drupal_strlen($text) {
  return Unicode::strlen($text);
}

/**
 * Uppercase a UTF-8 string.
 *
 * @param $text
 *   The string to run the operation on.
 *
 * @return string
 *   The string in uppercase.
 *
 * @see \Drupal\Component\Utility\Unicode::strtoupper().
 * @ingroup php_wrappers
 */
function drupal_strtoupper($text) {
  return Unicode::strtoupper($text);
}

/**
 * Lowercase a UTF-8 string.
 *
 * @param $text
 *   The string to run the operation on.
 *
 * @return string
 *   The string in lowercase.
 *
 * @see \Drupal\Component\Utility\Unicode::strtolower().
 * @ingroup php_wrappers
 */
function drupal_strtolower($text) {
  return Unicode::strtolower($text);
}

/**
 * Capitalizes the first letter of a UTF-8 string.
 *
 * @param $text
 *   The string to convert.
 *
 * @return
 *   The string with the first letter as uppercase.
 *
 * @see \Drupal\Component\Utility\Unicode::ucfirst().
 * @ingroup php_wrappers
 */
function drupal_ucfirst($text) {
  return Unicode::ucfirst($text);
}

/**
 * Cuts off a piece of a string based on character indices and counts.
 *
 * @param $text
 *   The input string.
 * @param $start
 *   The position at which to start reading.
 * @param $length
 *   The number of characters to read.
 *
 * @return
 *   The shortened string.
 *
 * @see \Drupal\Component\Utility\Unicode::substr().
 * @ingroup php_wrappers
 */
function drupal_substr($text, $start, $length = NULL) {
  return Unicode::substr($text, $start, $length);
}
