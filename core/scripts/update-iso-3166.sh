#!/bin/php
<?php

/**
 * @file
 * Updates ISO-3166 codes in standard.inc to latest data.
 *
 * We rely on the Debian ISO code repository, because it is easily accessible,
 * scriptable, in the right human-readable format, and all changes went through
 * sufficient FOSS community discussion already.
 */

// Determine DRUPAL_ROOT.
$cwd = $dir = dirname(__FILE__);
while (!defined('DRUPAL_ROOT')) {
  if (is_dir($dir . '/core')) {
    define('DRUPAL_ROOT', $dir);
  }
  $dir = dirname($dir);
}

// Determine source data file URI to process.
$uri = $cwd . '/iso_3166.xml';
// Despite its actual file name, at least Firefox merges and converts the path
// and filename into a combined filename.
if (!file_exists($uri)) {
  $uri = $cwd . '/iso_3166_iso_3166.xml';
}
// Fall back and default to original Debian source.
if (!file_exists($uri)) {
  $uri = 'http://anonscm.debian.org/gitweb/?p=iso-codes/iso-codes.git;a=blob_plain;f=iso_3166/iso_3166.xml;hb=master';
}

// Read in existing codes.
// @todo Allow to remove previously existing country codes.
// @see http://drupal.org/node/1436754
require_once DRUPAL_ROOT . '/core/lib/Drupal/Core/Locale/CountryManagerInterface.php';
require_once DRUPAL_ROOT . '/core/lib/Drupal/Core/Locale/CountryManager.php';
$existing_countries = \Drupal\Core\Locale\CountryManager::getStandardList();
$countries = $existing_countries;

// Parse the source data into an array.
$data = simplexml_load_file($uri);
foreach ($data->iso_3166_entry as $entry) {
  // Ignore every territory that doesn't have a alpha-2 code.
  if (!isset($entry['alpha_2_code'])) {
    continue;
  }
  $name = isset($entry['name']) ? (string) $entry['name'] : (string) $entry['official_name'];
  $countries[(string) $entry['alpha_2_code']] = $name;
}
if (empty($countries)) {
  echo 'ERROR: Did not find expected alpha_2_code country names.' . PHP_EOL;
  exit;
}
// Sort by country code (to minimize diffs).
ksort($countries);

// Produce PHP code.
$out = '';
foreach ($countries as $code => $name) {
  // For .po translation file's sake, use double-quotes instead of escaped
  // single-quotes.
  $name = (strpos($name, '\'') !== FALSE ? '"' . $name . '"' : "'" . $name . "'");
  $out .= '    ' . var_export($code, TRUE) . ' => t(' . $name . '),' . "\n";
}

// Replace the actual PHP code in standard.inc.
$file = DRUPAL_ROOT . '/core/includes/standard.inc';
$content = file_get_contents($file);
$content = preg_replace('/(\$countries = array\(\n)(.+?)(^\s+\);)/ms', '$1' . $out . '$3', $content);
file_put_contents($file, $content);
