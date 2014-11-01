<?php

/**
* @file
* Provides Unicode-related conversions and operations.
*/

/**
 * Indicates an error during check for PHP unicode support.
 */
define('UNICODE_ERROR', -1);

/**
 * Indicates that standard PHP (emulated) unicode support is being used.
 */
define('UNICODE_SINGLEBYTE', 0);

/**
 * Indicates that full unicode support with the PHP mbstring extension is being
 * used.
 */
define('UNICODE_MULTIBYTE', 1);

/**
 * Matches Unicode characters that are word boundaries.
 *
 * Characters with the following General_category (gc) property values are used
 * as word boundaries. While this does not fully conform to the Word Boundaries
 * algorithm described in http://unicode.org/reports/tr29, as PCRE does not
 * contain the Word_Break property table, this simpler algorithm has to do.
 * - Cc, Cf, Cn, Co, Cs: Other.
 * - Pc, Pd, Pe, Pf, Pi, Po, Ps: Punctuation.
 * - Sc, Sk, Sm, So: Symbols.
 * - Zl, Zp, Zs: Separators.
 *
 * Non-boundary characters include the following General_category (gc) property
 * values:
 * - Ll, Lm, Lo, Lt, Lu: Letters.
 * - Mc, Me, Mn: Combining Marks.
 * - Nd, Nl, No: Numbers.
 *
 * Note that the PCRE property matcher is not used because we wanted to be
 * compatible with Unicode 5.2.0 regardless of the PCRE version used (and any
 * bugs in PCRE property tables).
 *
 * @see http://unicode.org/glossary
 */
define('PREG_CLASS_UNICODE_WORD_BOUNDARY',
  '\x{0}-\x{2F}\x{3A}-\x{40}\x{5B}-\x{60}\x{7B}-\x{A9}\x{AB}-\x{B1}\x{B4}' .
  '\x{B6}-\x{B8}\x{BB}\x{BF}\x{D7}\x{F7}\x{2C2}-\x{2C5}\x{2D2}-\x{2DF}' .
  '\x{2E5}-\x{2EB}\x{2ED}\x{2EF}-\x{2FF}\x{375}\x{37E}-\x{385}\x{387}\x{3F6}' .
  '\x{482}\x{55A}-\x{55F}\x{589}-\x{58A}\x{5BE}\x{5C0}\x{5C3}\x{5C6}' .
  '\x{5F3}-\x{60F}\x{61B}-\x{61F}\x{66A}-\x{66D}\x{6D4}\x{6DD}\x{6E9}' .
  '\x{6FD}-\x{6FE}\x{700}-\x{70F}\x{7F6}-\x{7F9}\x{830}-\x{83E}' .
  '\x{964}-\x{965}\x{970}\x{9F2}-\x{9F3}\x{9FA}-\x{9FB}\x{AF1}\x{B70}' .
  '\x{BF3}-\x{BFA}\x{C7F}\x{CF1}-\x{CF2}\x{D79}\x{DF4}\x{E3F}\x{E4F}' .
  '\x{E5A}-\x{E5B}\x{F01}-\x{F17}\x{F1A}-\x{F1F}\x{F34}\x{F36}\x{F38}' .
  '\x{F3A}-\x{F3D}\x{F85}\x{FBE}-\x{FC5}\x{FC7}-\x{FD8}\x{104A}-\x{104F}' .
  '\x{109E}-\x{109F}\x{10FB}\x{1360}-\x{1368}\x{1390}-\x{1399}\x{1400}' .
  '\x{166D}-\x{166E}\x{1680}\x{169B}-\x{169C}\x{16EB}-\x{16ED}' .
  '\x{1735}-\x{1736}\x{17B4}-\x{17B5}\x{17D4}-\x{17D6}\x{17D8}-\x{17DB}' .
  '\x{1800}-\x{180A}\x{180E}\x{1940}-\x{1945}\x{19DE}-\x{19FF}' .
  '\x{1A1E}-\x{1A1F}\x{1AA0}-\x{1AA6}\x{1AA8}-\x{1AAD}\x{1B5A}-\x{1B6A}' .
  '\x{1B74}-\x{1B7C}\x{1C3B}-\x{1C3F}\x{1C7E}-\x{1C7F}\x{1CD3}\x{1FBD}' .
  '\x{1FBF}-\x{1FC1}\x{1FCD}-\x{1FCF}\x{1FDD}-\x{1FDF}\x{1FED}-\x{1FEF}' .
  '\x{1FFD}-\x{206F}\x{207A}-\x{207E}\x{208A}-\x{208E}\x{20A0}-\x{20B8}' .
  '\x{2100}-\x{2101}\x{2103}-\x{2106}\x{2108}-\x{2109}\x{2114}' .
  '\x{2116}-\x{2118}\x{211E}-\x{2123}\x{2125}\x{2127}\x{2129}\x{212E}' .
  '\x{213A}-\x{213B}\x{2140}-\x{2144}\x{214A}-\x{214D}\x{214F}' .
  '\x{2190}-\x{244A}\x{249C}-\x{24E9}\x{2500}-\x{2775}\x{2794}-\x{2B59}' .
  '\x{2CE5}-\x{2CEA}\x{2CF9}-\x{2CFC}\x{2CFE}-\x{2CFF}\x{2E00}-\x{2E2E}' .
  '\x{2E30}-\x{3004}\x{3008}-\x{3020}\x{3030}\x{3036}-\x{3037}' .
  '\x{303D}-\x{303F}\x{309B}-\x{309C}\x{30A0}\x{30FB}\x{3190}-\x{3191}' .
  '\x{3196}-\x{319F}\x{31C0}-\x{31E3}\x{3200}-\x{321E}\x{322A}-\x{3250}' .
  '\x{3260}-\x{327F}\x{328A}-\x{32B0}\x{32C0}-\x{33FF}\x{4DC0}-\x{4DFF}' .
  '\x{A490}-\x{A4C6}\x{A4FE}-\x{A4FF}\x{A60D}-\x{A60F}\x{A673}\x{A67E}' .
  '\x{A6F2}-\x{A716}\x{A720}-\x{A721}\x{A789}-\x{A78A}\x{A828}-\x{A82B}' .
  '\x{A836}-\x{A839}\x{A874}-\x{A877}\x{A8CE}-\x{A8CF}\x{A8F8}-\x{A8FA}' .
  '\x{A92E}-\x{A92F}\x{A95F}\x{A9C1}-\x{A9CD}\x{A9DE}-\x{A9DF}' .
  '\x{AA5C}-\x{AA5F}\x{AA77}-\x{AA79}\x{AADE}-\x{AADF}\x{ABEB}' .
  '\x{E000}-\x{F8FF}\x{FB29}\x{FD3E}-\x{FD3F}\x{FDFC}-\x{FDFD}' .
  '\x{FE10}-\x{FE19}\x{FE30}-\x{FE6B}\x{FEFF}-\x{FF0F}\x{FF1A}-\x{FF20}' .
  '\x{FF3B}-\x{FF40}\x{FF5B}-\x{FF65}\x{FFE0}-\x{FFFD}');

/**
 * Wrapper around _unicode_check().
 */
function unicode_check() {
  list($GLOBALS['multibyte']) = _unicode_check();
}

/**
 * Perform checks about Unicode support in PHP, and set the right settings if
 * needed.
 *
 * Because Drupal needs to be able to handle text in various encodings, we do
 * not support mbstring function overloading. HTTP input/output conversion must
 * be disabled for similar reasons.
 *
 * @param $errors
 *   Whether to report any fatal errors with form_set_error().
 */
function _unicode_check() {
  // Ensure translations don't break during installation.
  $t = get_t();

  // Check for mbstring extension
  if (!function_exists('mb_strlen')) {
    return array(UNICODE_SINGLEBYTE, $t('Operations on Unicode strings are emulated on a best-effort basis. Install the <a href="@url">PHP mbstring extension</a> for improved Unicode support.', array('@url' => 'http://www.php.net/mbstring')));
  }

  // Check mbstring configuration
  if (ini_get('mbstring.func_overload') != 0) {
    return array(UNICODE_ERROR, $t('Multibyte string function overloading in PHP is active and must be disabled. Check the php.ini <em>mbstring.func_overload</em> setting. Please refer to the <a href="@url">PHP mbstring documentation</a> for more information.', array('@url' => 'http://www.php.net/mbstring')));
  }
  if (ini_get('mbstring.encoding_translation') != 0) {
    return array(UNICODE_ERROR, $t('Multibyte string input conversion in PHP is active and must be disabled. Check the php.ini <em>mbstring.encoding_translation</em> setting. Please refer to the <a href="@url">PHP mbstring documentation</a> for more information.', array('@url' => 'http://www.php.net/mbstring')));
  }
  // mbstring.http_input and mbstring.http_output are deprecated and empty by
  // default in PHP 5.6.
  if (version_compare(PHP_VERSION, '5.6.0') == -1) {
    if (ini_get('mbstring.http_input') != 'pass') {
      return array(UNICODE_ERROR, $t('Multibyte string input conversion in PHP is active and must be disabled. Check the php.ini <em>mbstring.http_input</em> setting. Please refer to the <a href="@url">PHP mbstring documentation</a> for more information.', array('@url' => 'http://www.php.net/mbstring')));
    }
    if (ini_get('mbstring.http_output') != 'pass') {
      return array(UNICODE_ERROR, $t('Multibyte string output conversion in PHP is active and must be disabled. Check the php.ini <em>mbstring.http_output</em> setting. Please refer to the <a href="@url">PHP mbstring documentation</a> for more information.', array('@url' => 'http://www.php.net/mbstring')));
    }
  }

  // Set appropriate configuration
  mb_internal_encoding('utf-8');
  mb_language('uni');
  return array(UNICODE_MULTIBYTE, '');
}

/**
 * Returns Unicode library status and errors.
 */
function unicode_requirements() {
  // Ensure translations don't break during installation.
  $t = get_t();

  $libraries = array(
    UNICODE_SINGLEBYTE => $t('Standard PHP'),
    UNICODE_MULTIBYTE => $t('PHP Mbstring Extension'),
    UNICODE_ERROR => $t('Error'),
  );
  $severities = array(
    UNICODE_SINGLEBYTE => REQUIREMENT_WARNING,
    UNICODE_MULTIBYTE => REQUIREMENT_OK,
    UNICODE_ERROR => REQUIREMENT_ERROR,
  );
  list($library, $description) = _unicode_check();

  $requirements['unicode'] = array(
    'title' => $t('Unicode library'),
    'value' => $libraries[$library],
  );
  if ($description) {
    $requirements['unicode']['description'] = $description;
  }

  $requirements['unicode']['severity'] = $severities[$library];

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
      watchdog('php', 'Could not convert XML encoding %s to UTF-8.', array('%s' => $encoding), WATCHDOG_WARNING);
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
 * Requires the iconv, GNU recode or mbstring PHP extension.
 *
 * @param $data
 *   The data to be converted.
 * @param $encoding
 *   The encoding that the data is in.
 *
 * @return
 *   Converted data or FALSE.
 */
function drupal_convert_to_utf8($data, $encoding) {
  if (function_exists('iconv')) {
    $out = @iconv($encoding, 'utf-8', $data);
  }
  elseif (function_exists('mb_convert_encoding')) {
    $out = @mb_convert_encoding($data, 'utf-8', $encoding);
  }
  elseif (function_exists('recode_string')) {
    $out = @recode_string($encoding . '..utf-8', $data);
  }
  else {
    watchdog('php', 'Unsupported encoding %s. Please install iconv, GNU recode or mbstring for PHP.', array('%s' => $encoding), WATCHDOG_ERROR);
    return FALSE;
  }

  return $out;
}

/**
 * Truncates a UTF-8-encoded string safely to a number of bytes.
 *
 * If the end position is in the middle of a UTF-8 sequence, it scans backwards
 * until the beginning of the byte sequence.
 *
 * Use this function whenever you want to chop off a string at an unsure
 * location. On the other hand, if you're sure that you're splitting on a
 * character boundary (e.g. after using strpos() or similar), you can safely
 * use substr() instead.
 *
 * @param $string
 *   The string to truncate.
 * @param $len
 *   An upper limit on the returned string length.
 *
 * @return
 *   The truncated string.
 */
function drupal_truncate_bytes($string, $len) {
  if (strlen($string) <= $len) {
    return $string;
  }
  if ((ord($string[$len]) < 0x80) || (ord($string[$len]) >= 0xC0)) {
    return substr($string, 0, $len);
  }
  // Scan backwards to beginning of the byte sequence.
  while (--$len >= 0 && ord($string[$len]) >= 0x80 && ord($string[$len]) < 0xC0);

  return substr($string, 0, $len);
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
 *   non-Latin languages; see PREG_CLASS_UNICODE_WORD_BOUNDARY for more
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
 */
function truncate_utf8($string, $max_length, $wordsafe = FALSE, $add_ellipsis = FALSE, $min_wordsafe_length = 1) {
  $ellipsis = '';
  $max_length = max($max_length, 0);
  $min_wordsafe_length = max($min_wordsafe_length, 0);

  if (drupal_strlen($string) <= $max_length) {
    // No truncation needed, so don't add ellipsis, just return.
    return $string;
  }

  if ($add_ellipsis) {
    // Truncate ellipsis in case $max_length is small.
    $ellipsis = drupal_substr(t('...'), 0, $max_length);
    $max_length -= drupal_strlen($ellipsis);
    $max_length = max($max_length, 0);
  }

  if ($max_length <= $min_wordsafe_length) {
    // Do not attempt word-safe if lengths are bad.
    $wordsafe = FALSE;
  }

  if ($wordsafe) {
    $matches = array();
    // Find the last word boundary, if there is one within $min_wordsafe_length
    // to $max_length characters. preg_match() is always greedy, so it will
    // find the longest string possible.
    $found = preg_match('/^(.{' . $min_wordsafe_length . ',' . $max_length . '})[' . PREG_CLASS_UNICODE_WORD_BOUNDARY . ']/u', $string, $matches);
    if ($found) {
      $string = $matches[1];
    }
    else {
      $string = drupal_substr($string, 0, $max_length);
    }
  }
  else {
    $string = drupal_substr($string, 0, $max_length);
  }

  if ($add_ellipsis) {
    $string .= $ellipsis;
  }

  return $string;
}

/**
 * Encodes MIME/HTTP header values that contain incorrectly encoded characters.
 *
 * For example, mime_header_encode('tést.txt') returns "=?UTF-8?B?dMOpc3QudHh0?=".
 *
 * See http://www.rfc-editor.org/rfc/rfc2047.txt for more information.
 *
 * Notes:
 * - Only encode strings that contain non-ASCII characters.
 * - We progressively cut-off a chunk with truncate_utf8(). This is to ensure
 *   each chunk starts and ends on a character boundary.
 * - Using \n as the chunk separator may cause problems on some systems and may
 *   have to be changed to \r\n or \r.
 *
 * @param $string
 *   The header to encode.
 *
 * @return string
 *   The mime-encoded header.
 *
 * @see mime_header_decode()
 */
function mime_header_encode($string) {
  if (preg_match('/[^\x20-\x7E]/', $string)) {
    $chunk_size = 47; // floor((75 - strlen("=?UTF-8?B??=")) * 0.75);
    $len = strlen($string);
    $output = '';
    while ($len > 0) {
      $chunk = drupal_truncate_bytes($string, $chunk_size);
      $output .= ' =?UTF-8?B?' . base64_encode($chunk) . "?=\n";
      $c = strlen($chunk);
      $string = substr($string, $c);
      $len -= $c;
    }
    return trim($output);
  }
  return $string;
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
 */
function mime_header_decode($header) {
  // First step: encoded chunks followed by other encoded chunks (need to collapse whitespace)
  $header = preg_replace_callback('/=\?([^?]+)\?(Q|B)\?([^?]+|\?(?!=))\?=\s+(?==\?)/', '_mime_header_decode', $header);
  // Second step: remaining chunks (do not collapse whitespace)
  return preg_replace_callback('/=\?([^?]+)\?(Q|B)\?([^?]+|\?(?!=))\?=/', '_mime_header_decode', $header);
}

/**
 * Decodes encoded header data passed from mime_header_decode().
 *
 * Callback for preg_replace_callback() within mime_header_decode().
 *
 * @param $matches
 *   The array of matches from preg_replace_callback().
 *
 * @return string
 *   The mime-decoded string.
 *
 * @see mime_header_decode()
 */
function _mime_header_decode($matches) {
  // Regexp groups:
  // 1: Character set name
  // 2: Escaping method (Q or B)
  // 3: Encoded data
  $data = ($matches[2] == 'B') ? base64_decode($matches[3]) : str_replace('_', ' ', quoted_printable_decode($matches[3]));
  if (strtolower($matches[1]) != 'utf-8') {
    $data = drupal_convert_to_utf8($data, $matches[1]);
  }
  return $data;
}

/**
 * Decodes all HTML entities (including numerical ones) to regular UTF-8 bytes.
 *
 * Double-escaped entities will only be decoded once ("&amp;lt;" becomes "&lt;"
 * , not "<"). Be careful when using this function, as decode_entities can
 * revert previous sanitization efforts (&lt;script&gt; will become <script>).
 *
 * @param $text
 *   The text to decode entities in.
 *
 * @return
 *   The input $text, with all HTML entities decoded once.
 */
function decode_entities($text) {
  return html_entity_decode($text, ENT_QUOTES, 'UTF-8');
}

/**
 * Counts the number of characters in a UTF-8 string.
 *
 * This is less than or equal to the byte count.
 *
 * @param $text
 *   The string to run the operation on.
 *
 * @return integer
 *   The length of the string.
 *
 * @ingroup php_wrappers
 */
function drupal_strlen($text) {
  global $multibyte;
  if ($multibyte == UNICODE_MULTIBYTE) {
    return mb_strlen($text);
  }
  else {
    // Do not count UTF-8 continuation bytes.
    return strlen(preg_replace("/[\x80-\xBF]/", '', $text));
  }
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
 * @ingroup php_wrappers
 */
function drupal_strtoupper($text) {
  global $multibyte;
  if ($multibyte == UNICODE_MULTIBYTE) {
    return mb_strtoupper($text);
  }
  else {
    // Use C-locale for ASCII-only uppercase
    $text = strtoupper($text);
    // Case flip Latin-1 accented letters
    $text = preg_replace_callback('/\xC3[\xA0-\xB6\xB8-\xBE]/', '_unicode_caseflip', $text);
    return $text;
  }
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
 * @ingroup php_wrappers
 */
function drupal_strtolower($text) {
  global $multibyte;
  if ($multibyte == UNICODE_MULTIBYTE) {
    return mb_strtolower($text);
  }
  else {
    // Use C-locale for ASCII-only lowercase
    $text = strtolower($text);
    // Case flip Latin-1 accented letters
    $text = preg_replace_callback('/\xC3[\x80-\x96\x98-\x9E]/', '_unicode_caseflip', $text);
    return $text;
  }
}

/**
 * Flips U+C0-U+DE to U+E0-U+FD and back.
 *
 * @param $matches
 *   An array of matches.
 *
 * @return array
 *   The Latin-1 version of the array of matches.
 *
 * @see drupal_strtolower()
 */
function _unicode_caseflip($matches) {
  return $matches[0][0] . chr(ord($matches[0][1]) ^ 32);
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
 * @ingroup php_wrappers
 */
function drupal_ucfirst($text) {
  // Note: no mbstring equivalent!
  return drupal_strtoupper(drupal_substr($text, 0, 1)) . drupal_substr($text, 1);
}

/**
 * Cuts off a piece of a string based on character indices and counts.
 *
 * Follows the same behavior as PHP's own substr() function. Note that for
 * cutting off a string at a known character/substring location, the usage of
 * PHP's normal strpos/substr is safe and much faster.
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
 * @ingroup php_wrappers
 */
function drupal_substr($text, $start, $length = NULL) {
  global $multibyte;
  if ($multibyte == UNICODE_MULTIBYTE) {
    return $length === NULL ? mb_substr($text, $start) : mb_substr($text, $start, $length);
  }
  else {
    $strlen = strlen($text);
    // Find the starting byte offset.
    $bytes = 0;
    if ($start > 0) {
      // Count all the continuation bytes from the start until we have found
      // $start characters or the end of the string.
      $bytes = -1; $chars = -1;
      while ($bytes < $strlen - 1 && $chars < $start) {
        $bytes++;
        $c = ord($text[$bytes]);
        if ($c < 0x80 || $c >= 0xC0) {
          $chars++;
        }
      }
    }
    elseif ($start < 0) {
      // Count all the continuation bytes from the end until we have found
      // abs($start) characters.
      $start = abs($start);
      $bytes = $strlen; $chars = 0;
      while ($bytes > 0 && $chars < $start) {
        $bytes--;
        $c = ord($text[$bytes]);
        if ($c < 0x80 || $c >= 0xC0) {
          $chars++;
        }
      }
    }
    $istart = $bytes;

    // Find the ending byte offset.
    if ($length === NULL) {
      $iend = $strlen;
    }
    elseif ($length > 0) {
      // Count all the continuation bytes from the starting index until we have
      // found $length characters or reached the end of the string, then
      // backtrace one byte.
      $iend = $istart - 1;
      $chars = -1;
      $last_real = FALSE;
      while ($iend < $strlen - 1 && $chars < $length) {
        $iend++;
        $c = ord($text[$iend]);
        $last_real = FALSE;
        if ($c < 0x80 || $c >= 0xC0) {
          $chars++;
          $last_real = TRUE;
        }
      }
      // Backtrace one byte if the last character we found was a real character
      // and we don't need it.
      if ($last_real && $chars >= $length) {
        $iend--;
      }
    }
    elseif ($length < 0) {
      // Count all the continuation bytes from the end until we have found
      // abs($start) characters, then backtrace one byte.
      $length = abs($length);
      $iend = $strlen; $chars = 0;
      while ($iend > 0 && $chars < $length) {
        $iend--;
        $c = ord($text[$iend]);
        if ($c < 0x80 || $c >= 0xC0) {
          $chars++;
        }
      }
      // Backtrace one byte if we are not at the beginning of the string.
      if ($iend > 0) {
        $iend--;
      }
    }
    else {
      // $length == 0, return an empty string.
      return '';
    }

    return substr($text, $istart, max(0, $iend - $istart + 1));
  }
}
