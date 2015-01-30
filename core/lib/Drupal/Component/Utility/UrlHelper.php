<?php

/**
 * @file
 * Contains \Drupal\Component\Utility\UrlHelper.
 */

namespace Drupal\Component\Utility;

/**
 * Helper class URL based methods.
 *
 * @ingroup utility
 */
class UrlHelper {

  /**
   * The list of allowed protocols.
   *
   * @var array
   */
  protected static $allowedProtocols = array('http', 'https');

  /**
   * Parses an array into a valid, rawurlencoded query string.
   *
   *
   * rawurlencode() is RFC3986 compliant, and as a consequence RFC3987
   * compliant. The latter defines the required format of "URLs" in HTML5.
   * urlencode() is almost the same as rawurlencode(), except that it encodes
   * spaces as "+" instead of "%20". This makes its result non compliant to
   * RFC3986 and as a consequence non compliant to RFC3987 and as a consequence
   * not valid as a "URL" in HTML5.
   *
   * @todo Remove this function once PHP 5.4 is required as we can use just
   *   http_build_query() directly.
   *
   * @param array $query
   *   The query parameter array to be processed,
   *   e.g. \Drupal::request()->query->all().
   * @param string $parent
   *   Internal use only. Used to build the $query array key for nested items.
   *
   * @return string
   *   A rawurlencoded string which can be used as or appended to the URL query
   *   string.
   *
   * @ingroup php_wrappers
   */
  public static function buildQuery(array $query, $parent = '') {
    $params = array();

    foreach ($query as $key => $value) {
      $key = ($parent ? $parent . '[' . rawurlencode($key) . ']' : rawurlencode($key));

      // Recurse into children.
      if (is_array($value)) {
        $params[] = static::buildQuery($value, $key);
      }
      // If a query parameter value is NULL, only append its key.
      elseif (!isset($value)) {
        $params[] = $key;
      }
      else {
        // For better readability of paths in query strings, we decode slashes.
        $params[] = $key . '=' . str_replace('%2F', '/', rawurlencode($value));
      }
    }

    return implode('&', $params);
  }

  /**
   * Filters a URL query parameter array to remove unwanted elements.
   *
   * @param array $query
   *   An array to be processed.
   * @param array $exclude
   *   (optional) A list of $query array keys to remove. Use "parent[child]" to
   *   exclude nested items.
   * @param string $parent
   *   Internal use only. Used to build the $query array key for nested items.
   *
   * @return
   *   An array containing query parameters.
   */
  public static function filterQueryParameters(array $query, array $exclude = array(), $parent = '') {
    // If $exclude is empty, there is nothing to filter.
    if (empty($exclude)) {
      return $query;
    }
    elseif (!$parent) {
      $exclude = array_flip($exclude);
    }

    $params = array();
    foreach ($query as $key => $value) {
      $string_key = ($parent ? $parent . '[' . $key . ']' : $key);
      if (isset($exclude[$string_key])) {
        continue;
      }

      if (is_array($value)) {
        $params[$key] = static::filterQueryParameters($value, $exclude, $string_key);
      }
      else {
        $params[$key] = $value;
      }
    }

    return $params;
  }

  /**
   * Parses a URL string into its path, query, and fragment components.
   *
   * This function splits both internal paths like @code node?b=c#d @endcode and
   * external URLs like @code https://example.com/a?b=c#d @endcode into their
   * component parts. See
   * @link http://tools.ietf.org/html/rfc3986#section-3 RFC 3986 @endlink for an
   * explanation of what the component parts are.
   *
   * Note that, unlike the RFC, when passed an external URL, this function
   * groups the scheme, authority, and path together into the path component.
   *
   * @param string $url
   *   The internal path or external URL string to parse.
   *
   * @return array
   *   An associative array containing:
   *   - path: The path component of $url. If $url is an external URL, this
   *     includes the scheme, authority, and path.
   *   - query: An array of query parameters from $url, if they exist.
   *   - fragment: The fragment component from $url, if it exists.
   *
   * @see \Drupal\Core\Utility\LinkGenerator
   * @see http://tools.ietf.org/html/rfc3986
   *
   * @ingroup php_wrappers
   */
  public static function parse($url) {
    $options = array(
      'path' => NULL,
      'query' => array(),
      'fragment' => '',
    );

    // External URLs: not using parse_url() here, so we do not have to rebuild
    // the scheme, host, and path without having any use for it.
    if (strpos($url, '://') !== FALSE) {
      // Split off everything before the query string into 'path'.
      $parts = explode('?', $url);

      // Don't support URLs without a path, like 'http://'.
      list(, $path) = explode('://', $parts[0], 2);
      if ($path != '') {
        $options['path'] = $parts[0];
      }
      // If there is a query string, transform it into keyed query parameters.
      if (isset($parts[1])) {
        $query_parts = explode('#', $parts[1]);
        parse_str($query_parts[0], $options['query']);
        // Take over the fragment, if there is any.
        if (isset($query_parts[1])) {
          $options['fragment'] = $query_parts[1];
        }
      }
    }
    // Internal URLs.
    else {
      // parse_url() does not support relative URLs, so make it absolute. E.g. the
      // relative URL "foo/bar:1" isn't properly parsed.
      $parts = parse_url('http://example.com/' . $url);
      // Strip the leading slash that was just added.
      $options['path'] = substr($parts['path'], 1);
      if (isset($parts['query'])) {
        parse_str($parts['query'], $options['query']);
      }
      if (isset($parts['fragment'])) {
        $options['fragment'] = $parts['fragment'];
      }
    }

    return $options;
  }

  /**
   * Encodes a Drupal path for use in a URL.
   *
   * For aesthetic reasons slashes are not escaped.
   *
   * @param string $path
   *   The Drupal path to encode.
   *
   * @return string
   *   The encoded path.
   */
  public static function encodePath($path) {
    return str_replace('%2F', '/', rawurlencode($path));
  }

  /**
   * Returns whether a path is external to Drupal (e.g. http://example.com).
   *
   * If a path cannot be assessed by Drupal's menu handler, then we must
   * treat it as potentially insecure.
   *
   * @param string $path
   *   The internal path or external URL being linked to, such as "node/34" or
   *   "http://example.com/foo".
   *
   * @return bool
   *   TRUE or FALSE, where TRUE indicates an external path.
   */
  public static function isExternal($path) {
    $colonpos = strpos($path, ':');
    // Avoid calling stripDangerousProtocols() if there is any
    // slash (/), hash (#) or question_mark (?) before the colon (:)
    // occurrence - if any - as this would clearly mean it is not a URL.
    return $colonpos !== FALSE && !preg_match('![/?#]!', substr($path, 0, $colonpos)) && static::stripDangerousProtocols($path) == $path;
  }

  /**
   * Determines if an external URL points to this installation.
   *
   * @param string $url
   *   A string containing an external URL, such as "http://example.com/foo".
   * @param string $base_url
   *   The base URL string to check against, such as "http://example.com/"
   *
   * @return bool
   *   TRUE if the URL has the same domain and base path.
   *
   * @throws \InvalidArgumentException
   *   Exception thrown when a either $url or $bath_url are not fully qualified.
   */
  public static function externalIsLocal($url, $base_url) {
    $url_parts = parse_url($url);
    $base_parts = parse_url($base_url);

    if (empty($base_parts['host']) || empty($url_parts['host'])) {
      throw new \InvalidArgumentException(String::format('A path was passed when a fully qualified domain was expected.'));
    }

    if (!isset($url_parts['path']) || !isset($base_parts['path'])) {
      return (!isset($base_parts['path']) || $base_parts['path'] == '/')
        && ($url_parts['host'] == $base_parts['host']);
    }
    else {
      // When comparing base paths, we need a trailing slash to make sure a
      // partial URL match isn't occurring. Since base_path() always returns
      // with a trailing slash, we don't need to add the trailing slash here.
      return ($url_parts['host'] == $base_parts['host'] && stripos($url_parts['path'], $base_parts['path']) === 0);
    }
  }

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
    // Get the plain text representation of the attribute value (i.e. its
    // meaning).
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
   * check_url() or Drupal\Component\Utility\Xss::filter(), but those functions
   * return an HTML-encoded string, so this function can be called independently
   * when the output needs to be a plain-text string for passing to functions
   * that will call \Drupal\Component\Utility\String::checkPlain() separately.
   *
   * @param string $uri
   *   A plain-text URI that might contain dangerous protocols.
   *
   * @return string
   *   A plain-text URI stripped of dangerous protocols. As with all plain-text
   *   strings, this return value must not be output to an HTML page without
   *   being sanitized first. However, it can be passed to functions
   *   expecting plain-text strings.
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
   *   TRUE if the URL is in a valid format, FALSE otherwise.
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
