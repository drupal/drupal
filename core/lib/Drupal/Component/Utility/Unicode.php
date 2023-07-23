<?php

namespace Drupal\Component\Utility;

/**
 * Provides Unicode-related conversions and operations.
 *
 * @ingroup utility
 */
class Unicode {

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
  const PREG_CLASS_WORD_BOUNDARY = <<<'EOD'
\x{0}-\x{2F}\x{3A}-\x{40}\x{5B}-\x{60}\x{7B}-\x{A9}\x{AB}-\x{B1}\x{B4}
\x{B6}-\x{B8}\x{BB}\x{BF}\x{D7}\x{F7}\x{2C2}-\x{2C5}\x{2D2}-\x{2DF}
\x{2E5}-\x{2EB}\x{2ED}\x{2EF}-\x{2FF}\x{375}\x{37E}-\x{385}\x{387}\x{3F6}
\x{482}\x{55A}-\x{55F}\x{589}-\x{58A}\x{5BE}\x{5C0}\x{5C3}\x{5C6}
\x{5F3}-\x{60F}\x{61B}-\x{61F}\x{66A}-\x{66D}\x{6D4}\x{6DD}\x{6E9}
\x{6FD}-\x{6FE}\x{700}-\x{70F}\x{7F6}-\x{7F9}\x{830}-\x{83E}
\x{964}-\x{965}\x{970}\x{9F2}-\x{9F3}\x{9FA}-\x{9FB}\x{AF1}\x{B70}
\x{BF3}-\x{BFA}\x{C7F}\x{CF1}-\x{CF2}\x{D79}\x{DF4}\x{E3F}\x{E4F}
\x{E5A}-\x{E5B}\x{F01}-\x{F17}\x{F1A}-\x{F1F}\x{F34}\x{F36}\x{F38}
\x{F3A}-\x{F3D}\x{F85}\x{FBE}-\x{FC5}\x{FC7}-\x{FD8}\x{104A}-\x{104F}
\x{109E}-\x{109F}\x{10FB}\x{1360}-\x{1368}\x{1390}-\x{1399}\x{1400}
\x{166D}-\x{166E}\x{1680}\x{169B}-\x{169C}\x{16EB}-\x{16ED}
\x{1735}-\x{1736}\x{17B4}-\x{17B5}\x{17D4}-\x{17D6}\x{17D8}-\x{17DB}
\x{1800}-\x{180A}\x{180E}\x{1940}-\x{1945}\x{19DE}-\x{19FF}
\x{1A1E}-\x{1A1F}\x{1AA0}-\x{1AA6}\x{1AA8}-\x{1AAD}\x{1B5A}-\x{1B6A}
\x{1B74}-\x{1B7C}\x{1C3B}-\x{1C3F}\x{1C7E}-\x{1C7F}\x{1CD3}\x{1FBD}
\x{1FBF}-\x{1FC1}\x{1FCD}-\x{1FCF}\x{1FDD}-\x{1FDF}\x{1FED}-\x{1FEF}
\x{1FFD}-\x{206F}\x{207A}-\x{207E}\x{208A}-\x{208E}\x{20A0}-\x{20B8}
\x{2100}-\x{2101}\x{2103}-\x{2106}\x{2108}-\x{2109}\x{2114}
\x{2116}-\x{2118}\x{211E}-\x{2123}\x{2125}\x{2127}\x{2129}\x{212E}
\x{213A}-\x{213B}\x{2140}-\x{2144}\x{214A}-\x{214D}\x{214F}
\x{2190}-\x{244A}\x{249C}-\x{24E9}\x{2500}-\x{2775}\x{2794}-\x{2B59}
\x{2CE5}-\x{2CEA}\x{2CF9}-\x{2CFC}\x{2CFE}-\x{2CFF}\x{2E00}-\x{2E2E}
\x{2E30}-\x{3004}\x{3008}-\x{3020}\x{3030}\x{3036}-\x{3037}
\x{303D}-\x{303F}\x{309B}-\x{309C}\x{30A0}\x{30FB}\x{3190}-\x{3191}
\x{3196}-\x{319F}\x{31C0}-\x{31E3}\x{3200}-\x{321E}\x{322A}-\x{3250}
\x{3260}-\x{327F}\x{328A}-\x{32B0}\x{32C0}-\x{33FF}\x{4DC0}-\x{4DFF}
\x{A490}-\x{A4C6}\x{A4FE}-\x{A4FF}\x{A60D}-\x{A60F}\x{A673}\x{A67E}
\x{A6F2}-\x{A716}\x{A720}-\x{A721}\x{A789}-\x{A78A}\x{A828}-\x{A82B}
\x{A836}-\x{A839}\x{A874}-\x{A877}\x{A8CE}-\x{A8CF}\x{A8F8}-\x{A8FA}
\x{A92E}-\x{A92F}\x{A95F}\x{A9C1}-\x{A9CD}\x{A9DE}-\x{A9DF}
\x{AA5C}-\x{AA5F}\x{AA77}-\x{AA79}\x{AADE}-\x{AADF}\x{ABEB}
\x{E000}-\x{F8FF}\x{FB29}\x{FD3E}-\x{FD3F}\x{FDFC}-\x{FDFD}
\x{FE10}-\x{FE19}\x{FE30}-\x{FE6B}\x{FEFF}-\x{FF0F}\x{FF1A}-\x{FF20}
\x{FF3B}-\x{FF40}\x{FF5B}-\x{FF65}\x{FFE0}-\x{FFFD}
EOD;

  /**
   * Indicates that standard PHP (emulated) unicode support is being used.
   */
  const STATUS_SINGLEBYTE = 0;

  /**
   * Indicates that full unicode support with PHP mbstring extension is used.
   */
  const STATUS_MULTIBYTE = 1;

  /**
   * Indicates an error during check for PHP unicode support.
   */
  const STATUS_ERROR = -1;

  /**
   * Gets the current status of unicode/multibyte support on this environment.
   *
   * @return int
   *   The status of multibyte support. It can be one of:
   *   - \Drupal\Component\Utility\Unicode::STATUS_MULTIBYTE
   *     Full unicode support using an extension.
   *   - \Drupal\Component\Utility\Unicode::STATUS_SINGLEBYTE
   *     Standard PHP (emulated) unicode support.
   *   - \Drupal\Component\Utility\Unicode::STATUS_ERROR
   *     An error occurred. No unicode support.
   */
  public static function getStatus() {
    switch (static::check()) {
      case 'mb_strlen':
        return Unicode::STATUS_SINGLEBYTE;

      case '':
        return Unicode::STATUS_MULTIBYTE;
    }
    return Unicode::STATUS_ERROR;
  }

  /**
   * Checks for Unicode support in PHP and sets the proper settings if possible.
   *
   * Because of the need to be able to handle text in various encodings, we do
   * not support mbstring function overloading. HTTP input/output conversion
   * must be disabled for similar reasons.
   *
   * @return string
   *   A string identifier of a failed multibyte extension check, if any.
   *   Otherwise, an empty string.
   */
  public static function check() {
    // Set appropriate configuration.
    mb_internal_encoding('utf-8');
    mb_language('uni');

    // Check for mbstring extension.
    if (!extension_loaded('mbstring')) {
      return 'mb_strlen';
    }

    // Check mbstring configuration.
    if (ini_get('mbstring.encoding_translation') != 0) {
      return 'mbstring.encoding_translation';
    }

    return '';
  }

  /**
   * Decodes UTF byte-order mark (BOM) to the encoding name.
   *
   * @param string $data
   *   The data possibly containing a BOM. This can be the entire contents of
   *   a file, or just a fragment containing at least the first five bytes.
   *
   * @return string|bool
   *   The name of the encoding, or FALSE if no byte order mark was present.
   */
  public static function encodingFromBOM($data) {
    static $bomMap = [
      "\xEF\xBB\xBF" => 'UTF-8',
      "\xFE\xFF" => 'UTF-16BE',
      "\xFF\xFE" => 'UTF-16LE',
      "\x00\x00\xFE\xFF" => 'UTF-32BE',
      "\xFF\xFE\x00\x00" => 'UTF-32LE',
      "\x2B\x2F\x76\x38" => 'UTF-7',
      "\x2B\x2F\x76\x39" => 'UTF-7',
      "\x2B\x2F\x76\x2B" => 'UTF-7',
      "\x2B\x2F\x76\x2F" => 'UTF-7',
      "\x2B\x2F\x76\x38\x2D" => 'UTF-7',
    ];

    foreach ($bomMap as $bom => $encoding) {
      if (str_starts_with($data, $bom)) {
        return $encoding;
      }
    }
    return FALSE;
  }

  /**
   * Converts data to UTF-8.
   *
   * Requires the iconv, GNU recode or mbstring PHP extension.
   *
   * @param string $data
   *   The data to be converted.
   * @param string $encoding
   *   The encoding that the data is in.
   *
   * @return string|bool
   *   Converted data or FALSE.
   */
  public static function convertToUtf8($data, $encoding) {
    return @iconv($encoding, 'utf-8', $data);
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
   * @param string $string
   *   The string to truncate.
   * @param int $len
   *   An upper limit on the returned string length.
   *
   * @return string
   *   The truncated string.
   */
  public static function truncateBytes($string, $len) {
    if (strlen($string) <= $len) {
      return $string;
    }
    if ((ord($string[$len]) < 0x80) || (ord($string[$len]) >= 0xC0)) {
      return substr($string, 0, $len);
    }
    // Scan backwards to beginning of the byte sequence.
    // @todo Make the code more readable in https://www.drupal.org/node/2911497.
    while (--$len >= 0 && ord($string[$len]) >= 0x80 && ord($string[$len]) < 0xC0) {
    }

    return substr($string, 0, $len);
  }

  /**
   * Capitalizes the first character of a UTF-8 string.
   *
   * @param string $text
   *   The string to convert.
   *
   * @return string
   *   The string with the first character as uppercase.
   */
  public static function ucfirst($text) {
    return mb_strtoupper(mb_substr($text, 0, 1)) . mb_substr($text, 1);
  }

  /**
   * Converts the first character of a UTF-8 string to lowercase.
   *
   * @param string $text
   *   The string that will be converted.
   *
   * @return string
   *   The string with the first character as lowercase.
   *
   * @ingroup php_wrappers
   */
  public static function lcfirst($text) {
    // Note: no mbstring equivalent!
    return mb_strtolower(mb_substr($text, 0, 1)) . mb_substr($text, 1);
  }

  /**
   * Capitalizes the first character of each word in a UTF-8 string.
   *
   * @param string $text
   *   The text that will be converted.
   *
   * @return string
   *   The input $text with each word capitalized.
   *
   * @ingroup php_wrappers
   */
  public static function ucwords($text) {
    $regex = '/(^|[' . static::PREG_CLASS_WORD_BOUNDARY . '])([^' . static::PREG_CLASS_WORD_BOUNDARY . '])/u';
    return preg_replace_callback($regex, function (array $matches) {
      return $matches[1] . mb_strtoupper($matches[2]);
    }, $text);
  }

  /**
   * Truncates a UTF-8-encoded string safely to a number of characters.
   *
   * @param string $string
   *   The string to truncate.
   * @param int $max_length
   *   An upper limit on the returned string length, including trailing ellipsis
   *   if $add_ellipsis is TRUE.
   * @param bool $wordsafe
   *   If TRUE, attempt to truncate on a word boundary. Word boundaries are
   *   spaces, punctuation, and Unicode characters used as word boundaries in
   *   non-Latin languages; see Unicode::PREG_CLASS_WORD_BOUNDARY for more
   *   information. If a word boundary cannot be found that would make the length
   *   of the returned string fall within length guidelines (see parameters
   *   $max_length and $min_wordsafe_length), word boundaries are ignored.
   * @param bool $add_ellipsis
   *   If TRUE, add '...' to the end of the truncated string (defaults to
   *   FALSE). The string length will still fall within $max_length.
   * @param int $min_wordsafe_length
   *   If $wordsafe is TRUE, the minimum acceptable length for truncation (before
   *   adding an ellipsis, if $add_ellipsis is TRUE). Has no effect if $wordsafe
   *   is FALSE. This can be used to prevent having a very short resulting string
   *   that will not be understandable. For instance, if you are truncating the
   *   string "See MyVeryLongURLExample.com for more information" to a word-safe
   *   return length of 20, the only available word boundary within 20 characters
   *   is after the word "See", which wouldn't leave a very informative string. If
   *   you had set $min_wordsafe_length to 10, though, the function would realise
   *   that "See" alone is too short, and would then just truncate ignoring word
   *   boundaries, giving you "See MyVeryLongURL..." (assuming you had set
   *   $add_ellipsis to TRUE).
   *
   * @return string
   *   The truncated string.
   */
  public static function truncate($string, $max_length, $wordsafe = FALSE, $add_ellipsis = FALSE, $min_wordsafe_length = 1) {
    $ellipsis = '';
    $max_length = max($max_length, 0);
    $min_wordsafe_length = max($min_wordsafe_length, 0);

    if (mb_strlen($string) <= $max_length) {
      // No truncation needed, so don't add ellipsis, just return.
      return $string;
    }

    if ($add_ellipsis) {
      // Truncate ellipsis in case $max_length is small.
      $ellipsis = mb_substr('…', 0, $max_length);
      $max_length -= mb_strlen($ellipsis);
      $max_length = max($max_length, 0);
    }

    if ($max_length <= $min_wordsafe_length) {
      // Do not attempt word-safe if lengths are bad.
      $wordsafe = FALSE;
    }

    if ($wordsafe) {
      $matches = [];
      // Find the last word boundary, if there is one within $min_wordsafe_length
      // to $max_length characters. preg_match() is always greedy, so it will
      // find the longest string possible.
      $found = preg_match('/^(.{' . $min_wordsafe_length . ',' . $max_length . '})[' . Unicode::PREG_CLASS_WORD_BOUNDARY . ']/us', $string, $matches);
      if ($found) {
        $string = $matches[1];
      }
      else {
        $string = mb_substr($string, 0, $max_length);
      }
    }
    else {
      $string = mb_substr($string, 0, $max_length);
    }

    if ($add_ellipsis) {
      // If we're adding an ellipsis, remove any trailing periods.
      $string = rtrim($string, '.');

      $string .= $ellipsis;
    }

    return $string;
  }

  /**
   * Compares UTF-8-encoded strings in a binary safe case-insensitive manner.
   *
   * @param string $str1
   *   The first string.
   * @param string $str2
   *   The second string.
   *
   * @return int
   *   Returns < 0 if $str1 is less than $str2; > 0 if $str1 is greater than
   *   $str2, and 0 if they are equal.
   */
  public static function strcasecmp($str1, $str2) {
    return strcmp(mb_strtoupper($str1), mb_strtoupper($str2));
  }

  /**
   * Checks whether a string is valid UTF-8.
   *
   * All functions designed to filter input should use drupal_validate_utf8
   * to ensure they operate on valid UTF-8 strings to prevent bypass of the
   * filter.
   *
   * When text containing an invalid UTF-8 lead byte (0xC0 - 0xFF) is presented
   * as UTF-8 to Internet Explorer 6, the program may misinterpret subsequent
   * bytes. When these subsequent bytes are HTML control characters such as
   * quotes or angle brackets, parts of the text that were deemed safe by filters
   * end up in locations that are potentially unsafe; An onerror attribute that
   * is outside of a tag, and thus deemed safe by a filter, can be interpreted
   * by the browser as if it were inside the tag.
   *
   * The function does not return FALSE for strings containing character codes
   * above U+10FFFF, even though these are prohibited by RFC 3629.
   *
   * @param string $text
   *   The text to check.
   *
   * @return bool
   *   TRUE if the text is valid UTF-8, FALSE if not.
   */
  public static function validateUtf8($text) {
    if (strlen($text) == 0) {
      return TRUE;
    }
    // With the PCRE_UTF8 modifier 'u', preg_match() fails silently on strings
    // containing invalid UTF-8 byte sequences. It does not reject character
    // codes above U+10FFFF (represented by 4 or more octets), though.
    return (preg_match('/^./us', $text) == 1);
  }

}
