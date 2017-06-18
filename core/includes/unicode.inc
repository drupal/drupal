<?php

/**
 * @file
 * Provides Unicode-related conversions and operations.
 */

use Drupal\Component\Utility\Unicode;

/**
 * Returns Unicode library status and errors.
 */
function unicode_requirements() {
  $libraries = [
    Unicode::STATUS_SINGLEBYTE => t('Standard PHP'),
    Unicode::STATUS_MULTIBYTE => t('PHP Mbstring Extension'),
    Unicode::STATUS_ERROR => t('Error'),
  ];
  $severities = [
    Unicode::STATUS_SINGLEBYTE => REQUIREMENT_WARNING,
    Unicode::STATUS_MULTIBYTE => NULL,
    Unicode::STATUS_ERROR => REQUIREMENT_ERROR,
  ];
  $failed_check = Unicode::check();
  $library = Unicode::getStatus();

  $requirements['unicode'] = [
    'title' => t('Unicode library'),
    'value' => $libraries[$library],
    'severity' => $severities[$library],
  ];
  switch ($failed_check) {
    case 'mb_strlen':
      $requirements['unicode']['description'] = t('Operations on Unicode strings are emulated on a best-effort basis. Install the <a href="http://php.net/mbstring">PHP mbstring extension</a> for improved Unicode support.');
      break;

    case 'mbstring.func_overload':
      $requirements['unicode']['description'] = t('Multibyte string function overloading in PHP is active and must be disabled. Check the php.ini <em>mbstring.func_overload</em> setting. Please refer to the <a href="http://php.net/mbstring">PHP mbstring documentation</a> for more information.');
      break;

    case 'mbstring.encoding_translation':
      $requirements['unicode']['description'] = t('Multibyte string input conversion in PHP is active and must be disabled. Check the php.ini <em>mbstring.encoding_translation</em> setting. Please refer to the <a href="http://php.net/mbstring">PHP mbstring documentation</a> for more information.');
      break;

    case 'mbstring.http_input':
      $requirements['unicode']['description'] = t('Multibyte string input conversion in PHP is active and must be disabled. Check the php.ini <em>mbstring.http_input</em> setting. Please refer to the <a href="http://php.net/mbstring">PHP mbstring documentation</a> for more information.');
      break;

    case 'mbstring.http_output':
      $requirements['unicode']['description'] = t('Multibyte string output conversion in PHP is active and must be disabled. Check the php.ini <em>mbstring.http_output</em> setting. Please refer to the <a href="http://php.net/mbstring">PHP mbstring documentation</a> for more information.');
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
 *
 * @deprecated in Drupal 8.3.0 and will bre removed in Drupal 9.0.0. Use
 *   xml_parser_create() and
 *   xml_parser_set_option($xml_parser, XML_OPTION_TARGET_ENCODING, 'utf-8')
 *   instead.
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
  $php_supported = ['utf-8', 'iso-8859-1', 'us-ascii'];
  if (!in_array(strtolower($encoding), $php_supported)) {
    $out = Unicode::convertToUtf8($data, $encoding);
    if ($out !== FALSE) {
      $encoding = 'utf-8';
      $data = preg_replace('/^(<\?xml[^>]+encoding)="(.+?)"/', '\\1="utf-8"', $out);
    }
    else {
      \Drupal::logger('php')->warning('Could not convert XML encoding %s to UTF-8.', ['%s' => $encoding]);
      return FALSE;
    }
  }

  $xml_parser = xml_parser_create($encoding);
  xml_parser_set_option($xml_parser, XML_OPTION_TARGET_ENCODING, 'utf-8');
  return $xml_parser;
}
