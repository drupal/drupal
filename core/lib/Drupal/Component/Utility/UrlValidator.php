<?php

/**
 * @file
 * Contains \Drupal\Component\Utility\UrlValidator.
 */

namespace Drupal\Component\Utility;

/**
 * Helper class to support filtering bad protocols from an url.
 */
class UrlValidator {

  /**
   * The list of allowed protocols.
   *
   * @var array
   */
  protected static $allowedProtocols = array('http', 'https');

  /**
   * Processes an HTML attribute value and strips dangerous protocols from URLs.
   *
   * @param string $string
   *   The string with the attribute value.
   *
   * @return string
   *   Cleaned up and HTML-escaped version of $string.
   */
  public static function filterBadProtocol($string) {
    // Get the plain text representation of the attribute value (i.e. its meaning).
    $string = String::decodeEntities($string);

    return String::checkPlain(static::stripDangerousProtocols($string));
  }

  /**
   * Sets the allowed protocols.
   *
   * @param array $protocols
   *   An array of protocols, for example http, https and irc.
   */
  public static function setAllowedProtocols(array $protocols = array()) {
    static::$allowedProtocols = $protocols;
  }

  /**
   * Strips dangerous protocols (e.g. 'javascript:') from a URI.
   *
   * This function must be called for all URIs within user-entered input prior
   * to being output to an HTML attribute value. It is often called as part of
   * check_url() or filter_xss(), but those functions return an HTML-encoded
   * string, so this function can be called independently when the output needs to
   * be a plain-text string for passing to t(), l(),
   * Drupal\Core\Template\Attribute, or another function that will call
   * check_plain() separately.
   *
   * @param string $uri
   *   A plain-text URI that might contain dangerous protocols.
   *
   * @return string
   *   A plain-text URI stripped of dangerous protocols. As with all plain-text
   *   strings, this return value must not be output to an HTML page without
   *   check_plain() being called on it. However, it can be passed to functions
   *   expecting plain-text strings.
   *
   * @see check_url()
   */
  public static function stripDangerousProtocols($uri) {
    $allowed_protocols = array_flip(static::$allowedProtocols);

    // Iteratively remove any invalid protocol found.
    do {
      $before = $uri;
      $colonpos = strpos($uri, ':');
      if ($colonpos > 0) {
        // We found a colon, possibly a protocol. Verify.
        $protocol = substr($uri, 0, $colonpos);
        // If a colon is preceded by a slash, question mark or hash, it cannot
        // possibly be part of the URL scheme. This must be a relative URL, which
        // inherits the (safe) protocol of the base document.
        if (preg_match('![/?#]!', $protocol)) {
          break;
        }
        // Check if this is a disallowed protocol. Per RFC2616, section 3.2.3
        // (URI Comparison) scheme comparison must be case-insensitive.
        if (!isset($allowed_protocols[strtolower($protocol)])) {
          $uri = substr($uri, $colonpos + 1);
        }
      }
    } while ($before != $uri);

    return $uri;
  }

  /**
   * Verifies the syntax of the given URL.
   *
   * This function should only be used on actual URLs. It should not be used for
   * Drupal menu paths, which can contain arbitrary characters.
   * Valid values per RFC 3986.
   *
   * @param string $url
   *   The URL to verify.
   * @param bool $absolute
   *   Whether the URL is absolute (beginning with a scheme such as "http:").
   *
   * @return bool
   *   TRUE if the URL is in a valid format.
   */
  public static function isValid($url, $absolute = FALSE) {
    if ($absolute) {
      return (bool) preg_match("
        /^                                                      # Start at the beginning of the text
        (?:ftp|https?|feed):\/\/                                # Look for ftp, http, https or feed schemes
        (?:                                                     # Userinfo (optional) which is typically
          (?:(?:[\w\.\-\+!$&'\(\)*\+,;=]|%[0-9a-f]{2})+:)*      # a username or a username and password
          (?:[\w\.\-\+%!$&'\(\)*\+,;=]|%[0-9a-f]{2})+@          # combination
        )?
        (?:
          (?:[a-z0-9\-\.]|%[0-9a-f]{2})+                        # A domain name or a IPv4 address
          |(?:\[(?:[0-9a-f]{0,4}:)*(?:[0-9a-f]{0,4})\])         # or a well formed IPv6 address
        )
        (?::[0-9]+)?                                            # Server port number (optional)
        (?:[\/|\?]
          (?:[\w#!:\.\?\+=&@$'~*,;\/\(\)\[\]\-]|%[0-9a-f]{2})   # The path and query (optional)
        *)?
      $/xi", $url);
    }
    else {
      return (bool) preg_match("/^(?:[\w#!:\.\?\+=&@$'~*,;\/\(\)\[\]\-]|%[0-9a-f]{2})+$/i", $url);
    }
  }

}
