<?php
// $Id $

/**
 * @file
 * Helper class to clean strings to make them URL safe and translatable.
 *
 * This was copied directly from pathauto and put here to be made available
 * to all, because more things than just pathauto want URL safe strings.
 *
 * To use, simply:
 * @code
 * ctools_include('cleanstring');
 * $output = ctools_cleanstring($string);
 *
 * You can add a variety of settings as an array in the second argument,
 * including words to ignore, how to deal with punctuation, length
 * limits, and more. See the function itself for options.
 */

/**
 * Matches Unicode character classes.
 *
 * See: http://www.unicode.org/Public/UNIDATA/UCD.html#General_Category_Values
 *
 * The index only contains the following character classes:
 *   Lu  Letter, Uppercase
 *   Ll  Letter, Lowercase
 *   Lt  Letter, Titlecase
 *   Lo  Letter, Other
 *   Nd  Number, Decimal Digit
 *   No  Number, Other
 *
 * Copied from search.module's PREG_CLASS_SEARCH_EXCLUDE.
 */
define('CTOOLS_PREG_CLASS_ALNUM',
'\x{0}-\x{2f}\x{3a}-\x{40}\x{5b}-\x{60}\x{7b}-\x{bf}\x{d7}\x{f7}\x{2b0}-'.
'\x{385}\x{387}\x{3f6}\x{482}-\x{489}\x{559}-\x{55f}\x{589}-\x{5c7}\x{5f3}-'.
'\x{61f}\x{640}\x{64b}-\x{65e}\x{66a}-\x{66d}\x{670}\x{6d4}\x{6d6}-\x{6ed}'.
'\x{6fd}\x{6fe}\x{700}-\x{70f}\x{711}\x{730}-\x{74a}\x{7a6}-\x{7b0}\x{901}-'.
'\x{903}\x{93c}\x{93e}-\x{94d}\x{951}-\x{954}\x{962}-\x{965}\x{970}\x{981}-'.
'\x{983}\x{9bc}\x{9be}-\x{9cd}\x{9d7}\x{9e2}\x{9e3}\x{9f2}-\x{a03}\x{a3c}-'.
'\x{a4d}\x{a70}\x{a71}\x{a81}-\x{a83}\x{abc}\x{abe}-\x{acd}\x{ae2}\x{ae3}'.
'\x{af1}-\x{b03}\x{b3c}\x{b3e}-\x{b57}\x{b70}\x{b82}\x{bbe}-\x{bd7}\x{bf0}-'.
'\x{c03}\x{c3e}-\x{c56}\x{c82}\x{c83}\x{cbc}\x{cbe}-\x{cd6}\x{d02}\x{d03}'.
'\x{d3e}-\x{d57}\x{d82}\x{d83}\x{dca}-\x{df4}\x{e31}\x{e34}-\x{e3f}\x{e46}-'.
'\x{e4f}\x{e5a}\x{e5b}\x{eb1}\x{eb4}-\x{ebc}\x{ec6}-\x{ecd}\x{f01}-\x{f1f}'.
'\x{f2a}-\x{f3f}\x{f71}-\x{f87}\x{f90}-\x{fd1}\x{102c}-\x{1039}\x{104a}-'.
'\x{104f}\x{1056}-\x{1059}\x{10fb}\x{10fc}\x{135f}-\x{137c}\x{1390}-\x{1399}'.
'\x{166d}\x{166e}\x{1680}\x{169b}\x{169c}\x{16eb}-\x{16f0}\x{1712}-\x{1714}'.
'\x{1732}-\x{1736}\x{1752}\x{1753}\x{1772}\x{1773}\x{17b4}-\x{17db}\x{17dd}'.
'\x{17f0}-\x{180e}\x{1843}\x{18a9}\x{1920}-\x{1945}\x{19b0}-\x{19c0}\x{19c8}'.
'\x{19c9}\x{19de}-\x{19ff}\x{1a17}-\x{1a1f}\x{1d2c}-\x{1d61}\x{1d78}\x{1d9b}-'.
'\x{1dc3}\x{1fbd}\x{1fbf}-\x{1fc1}\x{1fcd}-\x{1fcf}\x{1fdd}-\x{1fdf}\x{1fed}-'.
'\x{1fef}\x{1ffd}-\x{2070}\x{2074}-\x{207e}\x{2080}-\x{2101}\x{2103}-\x{2106}'.
'\x{2108}\x{2109}\x{2114}\x{2116}-\x{2118}\x{211e}-\x{2123}\x{2125}\x{2127}'.
'\x{2129}\x{212e}\x{2132}\x{213a}\x{213b}\x{2140}-\x{2144}\x{214a}-\x{2b13}'.
'\x{2ce5}-\x{2cff}\x{2d6f}\x{2e00}-\x{3005}\x{3007}-\x{303b}\x{303d}-\x{303f}'.
'\x{3099}-\x{309e}\x{30a0}\x{30fb}-\x{30fe}\x{3190}-\x{319f}\x{31c0}-\x{31cf}'.
'\x{3200}-\x{33ff}\x{4dc0}-\x{4dff}\x{a015}\x{a490}-\x{a716}\x{a802}\x{a806}'.
'\x{a80b}\x{a823}-\x{a82b}\x{d800}-\x{f8ff}\x{fb1e}\x{fb29}\x{fd3e}\x{fd3f}'.
'\x{fdfc}-\x{fe6b}\x{feff}-\x{ff0f}\x{ff1a}-\x{ff20}\x{ff3b}-\x{ff40}\x{ff5b}-'.
'\x{ff65}\x{ff70}\x{ff9e}\x{ff9f}\x{ffe0}-\x{fffd}');


/**
 * Clean up a string value provided by a module.
 *
 * Resulting string contains only alphanumerics and separators.
 *
 * @param $string
 *   A string to clean.
 * @param $settings
 *   An optional array of settings to use.
 *   - 'clean slash': If set, slashes will be cleaned. Defaults to TRUE,
 *     so you have to explicitly set this to FALSE to not clean the
 *     slashes.
 *   - 'ignore words': Set to an array of words that will be removed
 *     rather than made safe. Defaults to an empty array.
 *   - 'separator': Change spaces and untranslatable characters to
 *     this character. Defaults to '-'.
 *   - 'replacements': An array of direct replacements to be made that will
 *     be implemented via strtr(). Defaults to an empty array.
 *   - 'transliterate': If set, use the transliteration replacements. If set
 *     to an array, use these replacements instead of the defaults in CTools.
 *     Defaults to FALSE.
 *   - 'reduce ascii': If set to TRUE further reduce to ASCII96 only. Defaults
 *      to TRUE.
 *   - 'max length': If set to a number, reduce the resulting string to this
 *      maximum length. Defaults to no maximum length.
 *   - 'lower case': If set to TRUE, convert the result to lower case.
 *     Defaults to false.
 *   These settings will be passed through drupal_alter.
 *
 * @return
 *   The cleaned string.
 */
function ctools_cleanstring($string, $settings = array()) {
  $settings += array(
    'clean slash' => TRUE,
    'ignore words' => array(),
    'separator' => '-',
    'replacements' => array(),
    'transliterate' => FALSE,
    'reduce ascii' => TRUE,
    'max length' => FALSE,
    'lower case' => FALSE,
  );

  // Allow modules to make other changes to the settings.
  if (isset($settings['clean id'])) {
    drupal_alter('ctools_cleanstring_' . $settings['clean id'], $settings);
  }

  drupal_alter('ctools_cleanstring', $settings);

  $output = $string;

  // Do any replacements the user selected up front.
  if (!empty($settings['replacements'])) {
    $output = strtr($output, $settings['replacements']);
  }

  // Remove slashes if instructed to do so.
  if ($settings['clean slash']) {
    $output = str_replace('/', '', $output);
  }

  if (!empty($settings['transliterate']) && module_exists('transliteration')) {
    $output = transliteration_get($output);
  }

  // Reduce to the subset of ASCII96 letters and numbers
  if ($settings['reduce ascii']) {
    $pattern = '/[^a-zA-Z0-9\/]+/';
    $output = preg_replace($pattern, $settings['separator'], $output);
  }

  // Get rid of words that are on the ignore list
  if (!empty($settings['ignore words'])) {
    $ignore_re = '\b'. preg_replace('/,/', '\b|\b', $settings['ignore words']) .'\b';

    if (function_exists('mb_eregi_replace')) {
      $output = mb_eregi_replace($ignore_re, '', $output);
    }
    else {
      $output = preg_replace("/$ignore_re/i", '', $output);
    }
  }

  // Always replace whitespace with the separator.
  $output = preg_replace('/\s+/', $settings['separator'], $output);

  // In preparation for pattern matching,
  // escape the separator if and only if it is not alphanumeric.
  if (isset($settings['separator'])) {
    if (preg_match('/^[^'. CTOOLS_PREG_CLASS_ALNUM .']+$/uD', $settings['separator'])) {
      $seppattern = $settings['separator'];
    }
    else {
      $seppattern = '\\'. $settings['separator'];
    }
    // Trim any leading or trailing separators (note the need to
    $output = preg_replace("/^$seppattern+|$seppattern+$/", '', $output);

    // Replace multiple separators with a single one
    $output = preg_replace("/$seppattern+/", $settings['separator'], $output);
  }

  // Enforce the maximum component length
  if (!empty($settings['max length'])) {
    $output = ctools_cleanstring_truncate($output, $settings['max length'], $settings['separator']);
  }

  if (!empty($settings['lower case'])) {
    $output = drupal_strtolower($output);
  }
  return $output;
}

/**
 * A friendly version of truncate_utf8.
 *
 * @param $string
 *   The string to be truncated.
 * @param $length
 *   An integer for the maximum desired length.
 * @param $separator
 *   A string which contains the word boundary such as - or _.
 *
 * @return
 *  The string truncated below the maxlength.
 */
function ctools_cleanstring_truncate($string, $length, $separator) {
  if (drupal_strlen($string) > $length) {
    $string = drupal_substr($string, 0, $length + 1); // leave one more character
    if ($last_break = strrpos($string, $separator)) { // space exists AND is not on position 0
      $string = substr($string, 0, $last_break);
    }
    else {
      $string = drupal_substr($string, 0, $length);
    }
  }
  return $string;
}
