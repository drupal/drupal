#!/bin/php
<?php

/**
 * @file
 * Updates CLDR codes in CountryManager.php to latest data.
 *
 * We rely on the CLDR data set, because it is easily accessible, scriptable,
 * and in the right human-readable format.
 */

use Drupal\Core\Locale\CountryManager;

// cspell:ignore localenames

// Determine DRUPAL_ROOT.
$dir = dirname(__FILE__);
while (!defined('DRUPAL_ROOT')) {
  if (is_dir($dir . '/core')) {
    define('DRUPAL_ROOT', $dir);
  }
  $dir = dirname($dir);
}

// Determine source data file URI to process.
$uri = DRUPAL_ROOT . '/territories.json';

if (!file_exists($uri)) {
  $usage = <<< USAGE
- Download territories.json from
  https://github.com/unicode-org/cldr-json/blob/main/cldr-json/cldr-localenames-full/main/en/territories.json
  and place it in the Drupal root directory.
- Run this script.
USAGE;
  exit('CLDR data file not found. (' . $uri . ")\n\n" . $usage . "\n");
}

// Fake the t() function used in CountryManager.php instead of attempting a full
// Drupal bootstrap of core/includes/bootstrap.inc (where t() is declared).
if (!function_exists('t')) {

  function t($string): string {
    return $string;
  }

}

// Read in existing codes.
// @todo Allow to remove previously existing country codes.
// @see https://www.drupal.org/node/1436754
require_once DRUPAL_ROOT . '/core/lib/Drupal/Core/Locale/CountryManagerInterface.php';
require_once DRUPAL_ROOT . '/core/lib/Drupal/Core/Locale/CountryManager.php';
$existing_countries = CountryManager::getStandardList();
$countries = $existing_countries;

// Parse the source data into an array.
$data = json_decode(file_get_contents($uri));

foreach ($data->main->en->localeDisplayNames->territories as $code => $name) {
  // Use any alternate codes the Drupal community wishes to.
  $alt_codes = [
    // 'CI-alt-variant', // Use CI-alt-variant instead of the CI entry.
  ];
  if (in_array($code, $alt_codes)) {
    // Just use the first 2 character part of the alt code.
    $code = strtok($code, '-');
  }

  // Skip any codes we wish to exclude from our country list.
  $exclude_codes = [
    // The European Union is not a country.
    'EU',
    // The Eurozone is not a country.
    'EZ',
    // The United Nations is not a country.
    'UN',
    // "Pseudo-Accents" is not a country.
    'XA',
    // "Pseudo-Bidi" is not a country.
    'XB',
    // Don't allow "Unknown Region".
    'ZZ',
  ];
  if (in_array($code, $exclude_codes)) {
    continue;
  }

  // Ignore every territory that doesn't have a 2 character code.
  if (strlen($code) !== 2) {
    continue;
  }
  $countries[(string) $code] = $name;
}
if (empty($countries)) {
  echo 'ERROR: Did not find expected country names.' . PHP_EOL;
  exit;
}
// Sort by country code (to minimize diffs).
ksort($countries);

// Produce PHP code.
$out = '';
foreach ($countries as $code => $name) {
  // For .po translation file's sake, use double-quotes instead of escaped
  // single-quotes.
  $name = str_contains($name, '\'') ? '"' . $name . '"' : "'" . $name . "'";
  $out .= '      ' . var_export($code, TRUE) . ' => t(' . $name . '),' . "\n";
}

// Replace the actual PHP code in standard.inc.
$file = DRUPAL_ROOT . '/core/lib/Drupal/Core/Locale/CountryManager.php';
$content = file_get_contents($file);
$content = preg_replace('/(\$countries = \[\n)(.+?)(^\s+\];)/ms', '$1' . $out . '$3', $content, -1, $count);
file_put_contents($file, $content);
