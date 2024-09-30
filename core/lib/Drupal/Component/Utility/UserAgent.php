<?php

namespace Drupal\Component\Utility;

/**
 * Provides user agent related utility functions.
 *
 * @ingroup utility
 */
class UserAgent {

  /**
   * Identifies user agent language from the Accept-language HTTP header.
   *
   * The algorithm works as follows:
   * - map user agent language codes to available language codes.
   * - order all user agent language codes by qvalue from high to low.
   * - add generic user agent language codes if they aren't already specified
   *   but with a slightly lower qvalue.
   * - find the most specific available language code with the highest qvalue.
   * - if 2 or more languages are having the same qvalue, respect the order of
   *   them inside the $languages array.
   *
   * We perform user agent accept-language parsing only if page cache is
   * disabled, otherwise we would cache a user-specific preference.
   *
   * @param string $http_accept_language
   *   The value of the "Accept-Language" HTTP header.
   * @param array $langcodes
   *   An array of available language codes to pick from.
   * @param array $mappings
   *   (optional) Custom mappings to support user agents that are sending non
   *   standard language codes. No mapping is assumed by default.
   *
   * @return string
   *   The selected language code or FALSE if no valid language can be
   *   identified.
   */
  public static function getBestMatchingLangcode($http_accept_language, $langcodes, $mappings = []) {
    // The Accept-Language header contains information about the language
    // preferences configured in the user's user agent / operating system.
    // RFC 2616 (section 14.4) defines the Accept-Language header as follows:
    // @code
    //   Accept-Language = "Accept-Language" ":"
    //                  1#( language-range [ ";" "q" "=" qvalue ] )
    //   language-range  = ( ( 1*8ALPHA *( "-" 1*8ALPHA ) ) | "*" )
    // @endcode
    // Samples: "hu, en-us;q=0.66, en;q=0.33", "hu,en-us;q=0.5"
    $ua_langcodes = [];
    if (preg_match_all('@(?<=[, ]|^)([a-zA-Z-]+|\*)(?:;q=([0-9.]+))?(?:$|\s*,\s*)@', trim($http_accept_language), $matches, PREG_SET_ORDER)) {
      foreach ($matches as $match) {
        if ($mappings) {
          $langcode = strtolower($match[1]);
          foreach ($mappings as $ua_langcode => $standard_langcode) {
            if ($langcode == $ua_langcode) {
              $match[1] = $standard_langcode;
            }
          }
        }
        // We can safely use strtolower() here, tags are ASCII.
        // RFC2616 mandates that the decimal part is no more than three digits,
        // so we multiply the qvalue by 1000 to avoid floating point
        // comparisons.
        $langcode = strtolower($match[1]);
        $qvalue = isset($match[2]) ? (float) $match[2] : 1;
        // Take the highest qvalue for this langcode. Although the request
        // supposedly contains unique langcodes, our mapping possibly resolves
        // to the same langcode for different qvalues. Keep the highest.
        $ua_langcodes[$langcode] = max(
          (int) ($qvalue * 1000),
          ($ua_langcodes[$langcode] ?? 0)
        );
      }
    }

    // We should take pristine values from the HTTP headers, but Internet
    // Explorer from version 7 sends only specific language tags (eg. fr-CA)
    // without the corresponding generic tag (fr) unless explicitly configured.
    // In that case, we assume that the lowest value of the specific tags is the
    // value of the generic language to be as close to the HTTP 1.1 spec as
    // possible.
    // See https://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html#sec14.4 and
    // http://blogs.msdn.com/b/ie/archive/2006/10/17/accept-language-header-for-internet-explorer-7.aspx
    asort($ua_langcodes);
    foreach ($ua_langcodes as $langcode => $qvalue) {
      // For Chinese languages the generic tag is either zh-hans or zh-hant, so
      // we need to handle this separately, we can not split $langcode on the
      // first occurrence of '-' otherwise we get a non-existing language zh.
      // All other languages use a langcode without a '-', so we can safely
      // split on the first occurrence of it.
      if (strlen($langcode) > 7 && (str_starts_with($langcode, 'zh-hant') || str_starts_with($langcode, 'zh-hans'))) {
        $generic_tag = substr($langcode, 0, 7);
      }
      else {
        $generic_tag = strtok($langcode, '-');
      }
      if (!empty($generic_tag) && !isset($ua_langcodes[$generic_tag])) {
        // Add the generic langcode, but make sure it has a lower qvalue as the
        // more specific one, so the more specific one gets selected if it's
        // defined by both the user agent and us.
        $ua_langcodes[$generic_tag] = $qvalue - 0.1;
      }
    }

    // Find the added language with the greatest qvalue, following the rules
    // of RFC 2616 (section 14.4). If several languages have the same qvalue,
    // prefer the one with the greatest weight.
    $best_match_langcode = FALSE;
    $max_qvalue = 0;
    foreach ($langcodes as $langcode_case_sensitive) {
      // Language tags are case insensitive (RFC2616, sec 3.10).
      $langcode = strtolower($langcode_case_sensitive);

      // If nothing matches below, the default qvalue is the one of the wildcard
      // language, if set, or is 0 (which will never match).
      $qvalue = $ua_langcodes['*'] ?? 0;

      // Find the longest possible prefix of the user agent supplied language
      // ('the language-range') that matches this site language ('the language
      // tag').
      $prefix = $langcode;
      do {
        if (isset($ua_langcodes[$prefix])) {
          $qvalue = $ua_langcodes[$prefix];
          break;
        }
      } while ($prefix = substr($prefix, 0, strrpos($prefix, '-')));

      // Find the best match.
      if ($qvalue > $max_qvalue) {
        $best_match_langcode = $langcode_case_sensitive;
        $max_qvalue = $qvalue;
      }
    }

    return $best_match_langcode;
  }

}
