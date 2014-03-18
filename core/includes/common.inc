<?php

use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\Json;
use Drupal\Component\Utility\Number;
use Drupal\Component\Utility\Settings;
use Drupal\Component\Utility\SortArray;
use Drupal\Component\Utility\String;
use Drupal\Component\Utility\Tags;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Language\Language;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Exception\ParseException;
use Drupal\Component\PhpStorage\PhpStorageFactory;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Routing\GeneratorNotInitializedException;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Render\Element;

/**
 * @file
 * Common functions that many Drupal modules will need to reference.
 *
 * The functions that are critical and need to be available even when serving
 * a cached page are instead located in bootstrap.inc.
 */

/**
 * @defgroup php_wrappers PHP wrapper functions
 * @{
 * Functions that are wrappers or custom implementations of PHP functions.
 *
 * Certain PHP functions should not be used in Drupal. Instead, Drupal's
 * replacement functions should be used.
 *
 * For example, for improved or more secure UTF8-handling, or RFC-compliant
 * handling of URLs in Drupal.
 *
 * For ease of use and memorizing, all these wrapper functions use the same name
 * as the original PHP function, but prefixed with "drupal_". Beware, however,
 * that not all wrapper functions support the same arguments as the original
 * functions.
 *
 * You should always use these wrapper functions in your code.
 *
 * Wrong:
 * @code
 *   $my_substring = substr($original_string, 0, 5);
 * @endcode
 *
 * Correct:
 * @code
 *   $my_substring = drupal_substr($original_string, 0, 5);
 * @endcode
 *
 * @}
 */

/**
 * Return status for saving which involved creating a new item.
 */
const SAVED_NEW = 1;

/**
 * Return status for saving which involved an update to an existing item.
 */
const SAVED_UPDATED = 2;

/**
 * Return status for saving which deleted an existing item.
 */
const SAVED_DELETED = 3;

/**
 * The default aggregation group for CSS files added to the page.
 */
const CSS_AGGREGATE_DEFAULT = 0;

/**
 * The default aggregation group for theme CSS files added to the page.
 */
const CSS_AGGREGATE_THEME = 100;

/**
 * The default weight for CSS rules that style HTML elements ("base" styles).
 */
const CSS_BASE = -200;

/**
 * The default weight for CSS rules that layout a page.
 */
const CSS_LAYOUT = -100;

/**
 * The default weight for CSS rules that style design components (and their associated states and themes.)
 */
const CSS_COMPONENT = 0;

/**
 * The default weight for CSS rules that style states and are not included with components.
 */
const CSS_STATE = 100;

/**
 * The default weight for CSS rules that style themes and are not included with components.
 */
const CSS_THEME = 200;

/**
 * The default group for JavaScript settings added to the page.
 */
const JS_SETTING = -200;

/**
 * The default group for JavaScript and jQuery libraries added to the page.
 */
const JS_LIBRARY = -100;

/**
 * The default group for module JavaScript code added to the page.
 */
const JS_DEFAULT = 0;

/**
 * The default group for theme JavaScript code added to the page.
 */
const JS_THEME = 100;

/**
 * @defgroup block_caching Block Caching
 * @{
 * Constants that define each block's caching state.
 *
 * Modules specify how their blocks can be cached in their hook_block_info()
 * implementations. Caching can be turned off (DRUPAL_NO_CACHE), managed by the
 * module declaring the block (DRUPAL_CACHE_CUSTOM), or managed by the core
 * Block module. If the Block module is managing the cache, you can specify that
 * the block is the same for every page and user (DRUPAL_CACHE_GLOBAL), or that
 * it can change depending on the page (DRUPAL_CACHE_PER_PAGE) or by user
 * (DRUPAL_CACHE_PER_ROLE or DRUPAL_CACHE_PER_USER). Page and user settings can
 * be combined with a bitwise-binary or operator; for example,
 * DRUPAL_CACHE_PER_ROLE | DRUPAL_CACHE_PER_PAGE means that the block can change
 * depending on the user role or page it is on.
 *
 * The block cache is cleared when the 'content' cache tag is invalidated,
 * following the same pattern as the page cache (node, comment, user, taxonomy
 * added or updated...).
 *
 * Note that user 1 is excluded from block caching.
 */

/**
 * The block should not get cached.
 *
 * This setting should be used:
 * - For simple blocks (notably those that do not perform any db query), where
 *   querying the db cache would be more expensive than directly generating the
 *   content.
 * - For blocks that change too frequently.
 */
const DRUPAL_NO_CACHE = -1;

/**
 * The block is handling its own caching in its hook_block_view().
 *
 * This setting is useful when time based expiration is needed or a site uses a
 * node access which invalidates standard block cache.
 */
const DRUPAL_CACHE_CUSTOM = -2;

/**
 * The block or element can change depending on the user's roles.
 *
 * This is the default setting for blocks, used when the block does not specify
 * anything.
 */
const DRUPAL_CACHE_PER_ROLE = 0x0001;

/**
 * The block or element can change depending on the user.
 *
 * This setting can be resource-consuming for sites with large number of users,
 * and thus should only be used when DRUPAL_CACHE_PER_ROLE is not sufficient.
 */
const DRUPAL_CACHE_PER_USER = 0x0002;

/**
 * The block or element can change depending on the page being viewed.
 */
const DRUPAL_CACHE_PER_PAGE = 0x0004;

/**
 * The block or element is the same for every user and page that it is visible.
 */
const DRUPAL_CACHE_GLOBAL = 0x0008;

/**
 * @} End of "defgroup block_caching".
 */

/**
 * The delimiter used to split plural strings.
 *
 * This is the ETX (End of text) character and is used as a minimal means to
 * separate singular and plural variants in source and translation text. It
 * was found to be the most compatible delimiter for the supported databases.
 */
const LOCALE_PLURAL_DELIMITER = "\03";

/**
 * Adds content to a specified region.
 *
 * @param $region
 *   Page region the content is added to.
 * @param $data
 *   Content to be added.
 */
function drupal_add_region_content($region = NULL, $data = NULL) {
  static $content = array();

  if (isset($region) && isset($data)) {
    $content[$region][] = $data;
  }
  return $content;
}

/**
 * Gets assigned content for a given region.
 *
 * @param $region
 *   A specified region to fetch content for. If NULL, all regions will be
 *   returned.
 * @param $delimiter
 *   Content to be inserted between imploded array elements.
 */
function drupal_get_region_content($region = NULL, $delimiter = ' ') {
  $content = drupal_add_region_content();
  if (isset($region)) {
    if (isset($content[$region]) && is_array($content[$region])) {
      return implode($delimiter, $content[$region]);
    }
  }
  else {
    foreach (array_keys($content) as $region) {
      if (is_array($content[$region])) {
        $content[$region] = implode($delimiter, $content[$region]);
      }
    }
    return $content;
  }
}

/**
 * Gets the name of the currently active installation profile.
 *
 * When this function is called during Drupal's initial installation process,
 * the name of the profile that's about to be installed is stored in the global
 * installation state. At all other times, the "install_profile" setting will be
 * available in settings.php.
 *
 * @return $profile
 *   The name of the installation profile.
 */
function drupal_get_profile() {
  global $install_state;

  if (drupal_installation_attempted()) {
    // If the profile has been selected return it.
    if (isset($install_state['parameters']['profile'])) {
      $profile = $install_state['parameters']['profile'];
    }
    else {
      $profile = '';
    }
  }
  else {
    $profile = settings()->get('install_profile') ?: 'standard';
  }

  return $profile;
}

/**
 * Adds output to the HEAD tag of the HTML page.
 *
 * This function can be called as long as the headers aren't sent. Pass no
 * arguments (or NULL for both) to retrieve the currently stored elements.
 *
 * @param $data
 *   A renderable array. If the '#type' key is not set then 'html_tag' will be
 *   added as the default '#type'.
 * @param $key
 *   A unique string key to allow implementations of hook_html_head_alter() to
 *   identify the element in $data. Required if $data is not NULL.
 *
 * @return
 *   An array of all stored HEAD elements.
 *
 * @see drupal_pre_render_html_tag()
 */
function drupal_add_html_head($data = NULL, $key = NULL) {
  $stored_head = &drupal_static(__FUNCTION__);

  if (!isset($stored_head)) {
    // Make sure the defaults, including Content-Type, come first.
    $stored_head = _drupal_default_html_head();
  }

  if (isset($data) && isset($key)) {
    if (!isset($data['#type'])) {
      $data['#type'] = 'html_tag';
    }
    $stored_head[$key] = $data;
  }
  return $stored_head;
}

/**
 * Returns elements that are always displayed in the HEAD tag of the HTML page.
 */
function _drupal_default_html_head() {
  // Add default elements. Make sure the Content-Type comes first because the
  // IE browser may be vulnerable to XSS via encoding attacks from any content
  // that comes before this META tag, such as a TITLE tag.
  $elements['system_meta_content_type'] = array(
    '#type' => 'html_tag',
    '#tag' => 'meta',
    '#attributes' => array(
      'charset' => 'utf-8',
    ),
    // Security: This always has to be output first.
    '#weight' => -1000,
  );
  // Show Drupal and the major version number in the META GENERATOR tag.
  // Get the major version.
  list($version, ) = explode('.', \Drupal::VERSION);
  $elements['system_meta_generator'] = array(
    '#type' => 'html_tag',
    '#tag' => 'meta',
    '#attributes' => array(
      'name' => 'Generator',
      'content' => 'Drupal ' . $version . ' (http://drupal.org)',
    ),
  );
  // Also send the generator in the HTTP header.
  $elements['system_meta_generator']['#attached']['drupal_add_http_header'][] = array('X-Generator', $elements['system_meta_generator']['#attributes']['content']);
  return $elements;
}

/**
 * Retrieves output to be displayed in the HEAD tag of the HTML page.
  */
function drupal_get_html_head() {
  $elements = drupal_add_html_head();
  \Drupal::moduleHandler()->alter('html_head', $elements);
  return drupal_render($elements);
}

/**
 * Adds a feed URL for the current page.
 *
 * This function can be called as long the HTML header hasn't been sent.
 *
 * @param $url
 *   An internal system path or a fully qualified external URL of the feed.
 * @param $title
 *   The title of the feed.
 */
function drupal_add_feed($url = NULL, $title = '') {
  $stored_feed_links = &drupal_static(__FUNCTION__, array());

  if (isset($url)) {
    $feed_icon = array(
      '#theme' => 'feed_icon',
      '#url' => $url,
      '#title' => $title,
    );

    $feed_icon['#attached']['drupal_add_html_head_link'][][] = array(
      'rel' => 'alternate',
      'type' => 'application/rss+xml',
      'title' => $title,
      // Force the URL to be absolute, for consistency with other <link> tags
      // output by Drupal.
      'href' => url($url, array('absolute' => TRUE)),
    );

    $stored_feed_links[$url] = drupal_render($feed_icon);
  }
  return $stored_feed_links;
}

/**
 * Gets the feed URLs for the current page.
 *
 * @param $delimiter
 *   A delimiter to split feeds by.
 */
function drupal_get_feeds($delimiter = "\n") {
  $feeds = drupal_add_feed();
  return implode($feeds, $delimiter);
}

/**
 * @defgroup http_handling HTTP handling
 * @{
 * Functions to properly handle HTTP responses.
 */

/**
 * Processes a URL query parameter array to remove unwanted elements.
 *
 * @param $query
 *   (optional) An array to be processed. Defaults to \Drupal::request()->query
 *   parameters.
 * @param $exclude
 *   (optional) A list of $query array keys to remove. Use "parent[child]" to
 *   exclude nested items.
 * @param $parent
 *   Internal use only. Used to build the $query array key for nested items.
 *
 * @return
 *   An array containing query parameters, which can be used for url().
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use \Drupal\Component\Utility\UrlHelper::filterQueryParameters().
 */
function drupal_get_query_parameters(array $query = NULL, array $exclude = array(), $parent = '') {
  if (!isset($query)) {
    $query = \Drupal::request()->query->all();
  }
  return UrlHelper::filterQueryParameters($query, $exclude, $parent);
}

/**
 * Parses an array into a valid, rawurlencoded query string.
 *
 * @see drupal_get_query_parameters()
 * @ingroup php_wrappers
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use \Drupal\Component\Utility\UrlHelper::buildQuery().
 */
function drupal_http_build_query(array $query, $parent = '') {
  return UrlHelper::buildQuery($query, $parent);
}

/**
 * Prepares a 'destination' URL query parameter for use with url().
 *
 * Used to direct the user back to the referring page after completing a form.
 * By default the current URL is returned. If a destination exists in the
 * previous request, that destination is returned. As such, a destination can
 * persist across multiple pages.
 *
 * @return
 *   An associative array containing the key:
 *   - destination: The path provided via the destination query string or, if
 *     not available, the current path.
 *
 * @see current_path()
 */
function drupal_get_destination() {
  $destination = &drupal_static(__FUNCTION__);

  if (isset($destination)) {
    return $destination;
  }

  $query = \Drupal::request()->query;
  if ($query->has('destination')) {
    $destination = array('destination' => $query->get('destination'));
  }
  else {
    $path = current_path();
    $query = UrlHelper::buildQuery(UrlHelper::filterQueryParameters($query->all()));
    if ($query != '') {
      $path .= '?' . $query;
    }
    $destination = array('destination' => $path);
  }
  return $destination;
}

/**
 * Parses a system URL string into an associative array suitable for url().
 *
 * This function should only be used for URLs that have been generated by the
 * system, such as via url(). It should not be used for URLs that come from
 * external sources, or URLs that link to external resources.
 *
 * The returned array contains a 'path' that may be passed separately to url().
 * For example:
 * @code
 *   $options = drupal_parse_url(\Drupal::request()->query->get('destination'));
 *   $my_url = url($options['path'], $options);
 *   $my_link = l('Example link', $options['path'], $options);
 * @endcode
 *
 * This is required, because url() does not support relative URLs containing a
 * query string or fragment in its $path argument. Instead, any query string
 * needs to be parsed into an associative query parameter array in
 * $options['query'] and the fragment into $options['fragment'].
 *
 * @param $url
 *   The URL string to parse.
 *
 * @return
 *   An associative array containing the keys:
 *   - 'path': The path of the URL. If the given $url is external, this includes
 *     the scheme and host.
 *   - 'query': An array of query parameters of $url, if existent.
 *   - 'fragment': The fragment of $url, if existent.
 *
 * @see url()
 * @ingroup php_wrappers
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use \Drupal\Component\Utility\UrlHelper::parse().
 */
function drupal_parse_url($url) {
  return UrlHelper::parse($url);
}

/**
 * Encodes a Drupal path for use in a URL.
 *
 * For aesthetic reasons slashes are not escaped.
 *
 * Note that url() takes care of calling this function, so a path passed to that
 * function should not be encoded in advance.
 *
 * @param $path
 *   The Drupal path to encode.
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use \Drupal\Component\Utility\UrlHelper::encodePath().
 */
function drupal_encode_path($path) {
  return UrlHelper::encodePath($path);
}

/**
 * Determines if an external URL points to this Drupal installation.
 *
 * @param $url
 *   A string containing an external URL, such as "http://example.com/foo".
 *
 * @return
 *   TRUE if the URL has the same domain and base path.
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use \Drupal\Component\Utility\UrlHelper::externalIsLocal().
 */
function _external_url_is_local($url) {
  return UrlHelper::externalIsLocal($url, base_path());
}

/**
 * Helper function for determining hosts excluded from needing a proxy.
 *
 * @return
 *   TRUE if a proxy should be used for this host.
 */
function _drupal_http_use_proxy($host) {
  $proxy_exceptions = settings()->get('proxy_exceptions', array('localhost', '127.0.0.1'));
  return !in_array(strtolower($host), $proxy_exceptions, TRUE);
}

/**
 * @} End of "defgroup http_handling".
 */

/**
 * @defgroup validation Input validation
 * @{
 * Functions to validate user input.
 */

/**
 * Verifies the syntax of the given e-mail address.
 *
 * This uses the
 * @link http://php.net/manual/filter.filters.validate.php PHP e-mail validation filter. @endlink
 *
 * @param $mail
 *   A string containing an e-mail address.
 *
 * @return
 *   TRUE if the address is in a valid format.
 */
function valid_email_address($mail) {
  return (bool)filter_var($mail, FILTER_VALIDATE_EMAIL);
}

/**
 * Verifies the syntax of the given URL.
 *
 * This function should only be used on actual URLs. It should not be used for
 * Drupal menu paths, which can contain arbitrary characters.
 * Valid values per RFC 3986.
 * @param $url
 *   The URL to verify.
 * @param $absolute
 *   Whether the URL is absolute (beginning with a scheme such as "http:").
 *
 * @return
 *   TRUE if the URL is in a valid format.
 *
 * @see \Drupal\Component\Utility\UrlHelper::isValid()
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use \Drupal\Component\Utility\UrlHelper::isValid().
 */
function valid_url($url, $absolute = FALSE) {
  return UrlHelper::isValid($url, $absolute);
}

/**
 * @} End of "defgroup validation".
 */

/**
 * @defgroup sanitization Sanitization functions
 * @{
 * Functions to sanitize values.
 *
 * See http://drupal.org/writing-secure-code for information
 * on writing secure code.
 */

/**
 * Strips dangerous protocols from a URI and encodes it for output to HTML.
 *
 * @param $uri
 *   A plain-text URI that might contain dangerous protocols.
 *
 * @return
 *   A URI stripped of dangerous protocols and encoded for output to an HTML
 *   attribute value. Because it is already encoded, it should not be set as a
 *   value within a $attributes array passed to Drupal\Core\Template\Attribute,
 *   because Drupal\Core\Template\Attribute expects those values to be
 *   plain-text strings. To pass a filtered URI to
 *   Drupal\Core\Template\Attribute, call
 *   \Drupal\Component\Utility\UrlHelper::stripDangerousProtocols() instead.
 *
 * @see \Drupal\Component\Utility\UrlHelper::stripDangerousProtocols()
 * @see \Drupal\Component\Utility\String::checkPlain()
 */
function check_url($uri) {
  return String::checkPlain(UrlHelper::stripDangerousProtocols($uri));
}

/**
 * Applies a very permissive XSS/HTML filter for admin-only use.
 *
 * Use only for fields where it is impractical to use the
 * whole filter system, but where some (mainly inline) mark-up
 * is desired (so \Drupal\Component\Utility\String::checkPlain() is not
 * acceptable).
 *
 * Allows all tags that can be used inside an HTML body, save
 * for scripts and styles.
 *
 * @param string $string
 *   The string to apply the filter to.
 *
 * @return string
 *   The filtered string.
 *
 * @see \Drupal\Component\Utility\Xss::filterAdmin()
 */
function filter_xss_admin($string) {
  return Xss::filterAdmin($string);
}

/**
 * Filters HTML to prevent cross-site-scripting (XSS) vulnerabilities.
 *
 * Based on kses by Ulf Harnhammar, see http://sourceforge.net/projects/kses.
 * For examples of various XSS attacks, see: http://ha.ckers.org/xss.html.
 *
 * This code does four things:
 * - Removes characters and constructs that can trick browsers.
 * - Makes sure all HTML entities are well-formed.
 * - Makes sure all HTML tags and attributes are well-formed.
 * - Makes sure no HTML tags contain URLs with a disallowed protocol (e.g.
 *   javascript:).
 *
 * @param $string
 *   The string with raw HTML in it. It will be stripped of everything that can
 *   cause an XSS attack.
 * @param $allowed_tags
 *   An array of allowed tags.
 *
 * @return
 *   An XSS safe version of $string, or an empty string if $string is not
 *   valid UTF-8.
 *
 * @see \Drupal\Component\Utility\Xss::filter()
 */
function filter_xss($string, $allowed_tags = array('a', 'em', 'strong', 'cite', 'blockquote', 'code', 'ul', 'ol', 'li', 'dl', 'dt', 'dd')) {
  return Xss::filter($string, $allowed_tags);
}

/**
 * Processes an HTML attribute value and strips dangerous protocols from URLs.
 *
 * @param string $string
 *   The string with the attribute value.
 *
 * @return string
 *   Cleaned up and HTML-escaped version of $string.
 *
 * @see \Drupal\Component\Utility\UrlHelper::filterBadProtocol()
 */
function filter_xss_bad_protocol($string) {
  return UrlHelper::filterBadProtocol($string);
}

/**
 * @} End of "defgroup sanitization".
 */

/**
 * @defgroup format Formatting
 * @{
 * Functions to format numbers, strings, dates, etc.
 */

/**
 * Formats an RSS channel.
 *
 * Arbitrary elements may be added using the $args associative array.
 */
function format_rss_channel($title, $link, $description, $items, $langcode = NULL, $args = array()) {
  $langcode = $langcode ? $langcode : \Drupal::languageManager()->getCurrentLanguage(Language::TYPE_CONTENT)->id;

  $output = "<channel>\n";
  $output .= ' <title>' . String::checkPlain($title) . "</title>\n";
  $output .= ' <link>' . check_url($link) . "</link>\n";

  // The RSS 2.0 "spec" doesn't indicate HTML can be used in the description.
  // We strip all HTML tags, but need to prevent double encoding from properly
  // escaped source data (such as &amp becoming &amp;amp;).
  $output .= ' <description>' . String::checkPlain(decode_entities(strip_tags($description))) . "</description>\n";
  $output .= ' <language>' . String::checkPlain($langcode) . "</language>\n";
  $output .= format_xml_elements($args);
  $output .= $items;
  $output .= "</channel>\n";

  return $output;
}

/**
 * Formats a single RSS item.
 *
 * Arbitrary elements may be added using the $args associative array.
 */
function format_rss_item($title, $link, $description, $args = array()) {
  $output = "<item>\n";
  $output .= ' <title>' . String::checkPlain($title) . "</title>\n";
  $output .= ' <link>' . check_url($link) . "</link>\n";
  $output .= ' <description>' . String::checkPlain($description) . "</description>\n";
  $output .= format_xml_elements($args);
  $output .= "</item>\n";

  return $output;
}

/**
 * Formats XML elements.
 *
 * @param $array
 *   An array where each item represents an element and is either a:
 *   - (key => value) pair (<key>value</key>)
 *   - Associative array with fields:
 *     - 'key': element name
 *     - 'value': element contents
 *     - 'attributes': associative array of element attributes
 *
 * In both cases, 'value' can be a simple string, or it can be another array
 * with the same format as $array itself for nesting.
 */
function format_xml_elements($array) {
  $output = '';
  foreach ($array as $key => $value) {
    if (is_numeric($key)) {
      if ($value['key']) {
        $output .= ' <' . $value['key'];
        if (isset($value['attributes']) && is_array($value['attributes'])) {
          $output .= new Attribute($value['attributes']);
        }

        if (isset($value['value']) && $value['value'] != '') {
          $output .= '>' . (is_array($value['value']) ? format_xml_elements($value['value']) : String::checkPlain($value['value'])) . '</' . $value['key'] . ">\n";
        }
        else {
          $output .= " />\n";
        }
      }
    }
    else {
      $output .= ' <' . $key . '>' . (is_array($value) ? format_xml_elements($value) : String::checkPlain($value)) . "</$key>\n";
    }
  }
  return $output;
}

/**
 * Formats a string containing a count of items.
 *
 * This function ensures that the string is pluralized correctly. Since t() is
 * called by this function, make sure not to pass already-localized strings to
 * it.
 *
 * For example:
 * @code
 *   $output = format_plural($node->comment_count, '1 comment', '@count comments');
 * @endcode
 *
 * Example with additional replacements:
 * @code
 *   $output = format_plural($update_count,
 *     'Changed the content type of 1 post from %old-type to %new-type.',
 *     'Changed the content type of @count posts from %old-type to %new-type.',
 *     array('%old-type' => $info->old_type, '%new-type' => $info->new_type));
 * @endcode
 *
 * @param $count
 *   The item count to display.
 * @param $singular
 *   The string for the singular case. Make sure it is clear this is singular,
 *   to ease translation (e.g. use "1 new comment" instead of "1 new"). Do not
 *   use @count in the singular string.
 * @param $plural
 *   The string for the plural case. Make sure it is clear this is plural, to
 *   ease translation. Use @count in place of the item count, as in
 *   "@count new comments".
 * @param $args
 *   An associative array of replacements to make after translation. Instances
 *   of any key in this array are replaced with the corresponding value.
 *   Based on the first character of the key, the value is escaped and/or
 *   themed. See format_string(). Note that you do not need to include @count
 *   in this array; this replacement is done automatically for the plural case.
 * @param $options
 *   An associative array of additional options. See t() for allowed keys.
 *
 * @return
 *   A translated string.
 *
 * @see t()
 * @see format_string()
 * @see \Drupal\Core\StringTranslation\TranslationManager->formatPlural()
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use \Drupal::translation()->formatPlural().
 */
function format_plural($count, $singular, $plural, array $args = array(), array $options = array()) {
  return \Drupal::translation()->formatPlural($count, $singular, $plural, $args, $options);
}

/**
 * Parses a given byte count.
 *
 * @param $size
 *   A size expressed as a number of bytes with optional SI or IEC binary unit
 *   prefix (e.g. 2, 3K, 5MB, 10G, 6GiB, 8 bytes, 9mbytes).
 *
 * @return
 *   An integer representation of the size in bytes.
 */
function parse_size($size) {
  $unit = preg_replace('/[^bkmgtpezy]/i', '', $size); // Remove the non-unit characters from the size.
  $size = preg_replace('/[^0-9\.]/', '', $size); // Remove the non-numeric characters from the size.
  if ($unit) {
    // Find the position of the unit in the ordered string which is the power of magnitude to multiply a kilobyte by.
    return round($size * pow(DRUPAL_KILOBYTE, stripos('bkmgtpezy', $unit[0])));
  }
  else {
    return round($size);
  }
}

/**
 * Generates a string representation for the given byte count.
 *
 * @param $size
 *   A size in bytes.
 * @param $langcode
 *   Optional language code to translate to a language other than what is used
 *   to display the page.
 *
 * @return
 *   A translated string representation of the size.
 */
function format_size($size, $langcode = NULL) {
  if ($size < DRUPAL_KILOBYTE) {
    return format_plural($size, '1 byte', '@count bytes', array(), array('langcode' => $langcode));
  }
  else {
    $size = $size / DRUPAL_KILOBYTE; // Convert bytes to kilobytes.
    $units = array(
      t('@size KB', array(), array('langcode' => $langcode)),
      t('@size MB', array(), array('langcode' => $langcode)),
      t('@size GB', array(), array('langcode' => $langcode)),
      t('@size TB', array(), array('langcode' => $langcode)),
      t('@size PB', array(), array('langcode' => $langcode)),
      t('@size EB', array(), array('langcode' => $langcode)),
      t('@size ZB', array(), array('langcode' => $langcode)),
      t('@size YB', array(), array('langcode' => $langcode)),
    );
    foreach ($units as $unit) {
      if (round($size, 2) >= DRUPAL_KILOBYTE) {
        $size = $size / DRUPAL_KILOBYTE;
      }
      else {
        break;
      }
    }
    return str_replace('@size', round($size, 2), $unit);
  }
}

/**
 * Formats a time interval with the requested granularity.
 *
 * @param $interval
 *   The length of the interval in seconds.
 * @param $granularity
 *   How many different units to display in the string.
 * @param $langcode
 *   Optional language code to translate to a language other than
 *   what is used to display the page.
 *
 * @return
 *   A translated string representation of the interval.
 *
 * @see \Drupal\Core\Datetime\Date::formatInterval()
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use \Drupal::service('date')->formatInterval().
 */
function format_interval($interval, $granularity = 2, $langcode = NULL) {
  return \Drupal::service('date')->formatInterval($interval, $granularity, $langcode);
}

/**
 * Formats a date, using a date type or a custom date format string.
 *
 * @param $timestamp
 *   A UNIX timestamp to format.
 * @param $type
 *   (optional) The format to use, one of:
 *   - One of the built-in formats: 'short', 'medium',
 *     'long', 'html_datetime', 'html_date', 'html_time',
 *     'html_yearless_date', 'html_week', 'html_month', 'html_year'.
 *   - The name of a date type defined by a module in
 *     hook_date_format_types(), if it's been assigned a format.
 *   - The machine name of an administrator-defined date format.
 *   - 'custom', to use $format.
 *   Defaults to 'medium'.
 * @param $format
 *   (optional) If $type is 'custom', a PHP date format string suitable for
 *   input to date(). Use a backslash to escape ordinary text, so it does not
 *   get interpreted as date format characters.
 * @param $timezone
 *   (optional) Time zone identifier, as described at
 *   http://php.net/manual/timezones.php Defaults to the time zone used to
 *   display the page.
 * @param $langcode
 *   (optional) Language code to translate to. Defaults to the language used to
 *   display the page.
 *
 * @return
 *   A translated date string in the requested format.
 *
 * @see \Drupal\Component\Datetime\Date::format()
 */
function format_date($timestamp, $type = 'medium', $format = '', $timezone = NULL, $langcode = NULL) {
  return \Drupal::service('date')->format($timestamp, $type, $format, $timezone, $langcode);
}

/**
 * Returns an ISO8601 formatted date based on the given date.
 *
 * @param $date
 *   A UNIX timestamp.
 *
 * @return string
 *   An ISO8601 formatted date.
 */
function date_iso8601($date) {
  // The DATE_ISO8601 constant cannot be used here because it does not match
  // date('c') and produces invalid RDF markup.
  return date('c', $date);
}

/**
 * Translates a formatted date string.
 *
 * Callback for preg_replace_callback() within format_date().
 */
function _format_date_callback(array $matches = NULL, $new_langcode = NULL) {
  // We cache translations to avoid redundant and rather costly calls to t().
  static $cache, $langcode;

  if (!isset($matches)) {
    $langcode = $new_langcode;
    return;
  }

  $code = $matches[1];
  $string = $matches[2];

  if (!isset($cache[$langcode][$code][$string])) {
    $options = array(
      'langcode' => $langcode,
    );

    if ($code == 'F') {
      $options['context'] = 'Long month name';
    }

    if ($code == '') {
      $cache[$langcode][$code][$string] = $string;
    }
    else {
      $cache[$langcode][$code][$string] = t($string, array(), $options);
    }
  }
  return $cache[$langcode][$code][$string];
}

/**
 * Retrieves the correct datetime format type for this system.
 *
 * This value is sometimes required when the format type needs to be determined
 * before a date can be created.
 *
 * @return string
 *   A string as defined in \DrupalComponent\Datetime\DateTimePlus.php: either
 *   'intl' or 'php', depending on whether IntlDateFormatter is available.
 */
function datetime_default_format_type() {
  static $drupal_static_fast;
  if (!isset($drupal_static_fast)) {
    $drupal_static_fast['format_type'] = &drupal_static(__FUNCTION__);
  }
  $format_type = &$drupal_static_fast['format_type'];

  if (!isset($format_type)) {
    $date = new DrupalDateTime();
    $format_type = $date->canUseIntl() ? DrupalDateTime::INTL : DrupalDateTime::PHP;
  }
  return $format_type;
}

/**
 * @} End of "defgroup format".
 */

/**
 * Generates an internal or external URL.
 *
 * When creating links in modules, consider whether l() could be a better
 * alternative than url().
 *
 * @see \Drupal\Core\Routing\UrlGeneratorInterface::generateFromPath().
 */
function url($path = NULL, array $options = array()) {
  $generator = \Drupal::urlGenerator();
  try {
    $url = $generator->generateFromPath($path, $options);
  }
  catch (GeneratorNotInitializedException $e) {
    // Fallback to using globals.
    // @todo Remove this once there is no code that calls url() when there is
    //   no request.
    global $base_url, $base_path, $script_path;
    $generator->setBasePath($base_path);
    $generator->setBaseUrl($base_url . '/');
    $generator->setScriptPath($script_path);
    $url = $generator->generateFromPath($path, $options);
  }
  return $url;
}

/**
 * Returns TRUE if a path is external to Drupal (e.g. http://example.com).
 *
 * If a path cannot be assessed by Drupal's menu handler, then we must
 * treat it as potentially insecure.
 *
 * @param $path
 *   The internal path or external URL being linked to, such as "node/34" or
 *   "http://example.com/foo".
 *
 * @return
 *   Boolean TRUE or FALSE, where TRUE indicates an external path.
 */
function url_is_external($path) {
  return UrlHelper::isExternal($path);
}

/**
 * Formats an attribute string for an HTTP header.
 *
 * @param $attributes
 *   An associative array of attributes such as 'rel'.
 *
 * @return
 *   A ; separated string ready for insertion in a HTTP header. No escaping is
 *   performed for HTML entities, so this string is not safe to be printed.
 *
 * @see drupal_add_http_header()
 */
function drupal_http_header_attributes(array $attributes = array()) {
  foreach ($attributes as $attribute => &$data) {
    if (is_array($data)) {
      $data = implode(' ', $data);
    }
    $data = $attribute . '="' . $data . '"';
  }
  return $attributes ? ' ' . implode('; ', $attributes) : '';
}

/**
 * Formats an internal or external URL link as an HTML anchor tag.
 *
 * This function correctly handles aliased paths and adds an 'active' class
 * attribute to links that point to the current page (for theming), so all
 * internal links output by modules should be generated by this function if
 * possible.
 *
 * However, for links enclosed in translatable text you should use t() and
 * embed the HTML anchor tag directly in the translated string. For example:
 * @code
 * t('Visit the <a href="@url">settings</a> page', array('@url' => url('admin')));
 * @endcode
 * This keeps the context of the link title ('settings' in the example) for
 * translators.
 *
 * This function does not support generating links from internal routes. For
 * that use \Drupal\Core\Utility\LinkGenerator::generate(), which is exposed via
 * the 'link_generator' service. It requires an internal route name and does not
 * support external URLs. Using Drupal 7 style system paths should be avoided if
 * possible but l() should still be used when rendering links to external URLs.
 *
 * @param string|array $text
 *   The link text for the anchor tag as a translated string or render array.
 * @param string $path
 *   The internal path or external URL being linked to, such as "node/34" or
 *   "http://example.com/foo". After the url() function is called to construct
 *   the URL from $path and $options, the resulting URL is passed through
 *   \Drupal\Component\Utility\String::checkPlain() before it is inserted into
 *   the HTML anchor tag, to ensure well-formed HTML. See url() for more
 *   information and notes.
 * @param array $options
 *   An associative array of additional options. Defaults to an empty array. It
 *   may contain the following elements.
 *   - 'attributes': An associative array of HTML attributes to apply to the
 *     anchor tag. If element 'class' is included, it must be an array; 'title'
 *     must be a string; other elements are more flexible, as they just need
 *     to work as an argument for the constructor of the class
 *     Drupal\Core\Template\Attribute($options['attributes']).
 *   - 'html' (default FALSE): Whether $text is HTML or just plain-text. For
 *     example, to make an image tag into a link, this must be set to TRUE, or
 *     you will see the escaped HTML image tag. $text is not sanitized if
 *     'html' is TRUE. The calling function must ensure that $text is already
 *     safe.
 *   - 'language': An optional language object. If the path being linked to is
 *     internal to the site, $options['language'] is used to determine whether
 *     the link is "active", or pointing to the current page (the language as
 *     well as the path must match). This element is also used by url().
 *   - 'set_active_class': Whether l() should compare the $path, language and
 *     query options to the current URL to determine whether the link is
 *     "active". Defaults to FALSE. If TRUE, an "active" class will be applied
 *     to the link. It is important to use this sparingly since it is usually
 *     unnecessary and requires extra processing.
 *     For anonymous users, the "active" class will be calculated on the server,
 *     because most sites serve each anonymous user the same cached page anyway.
 *     For authenticated users, the "active" class will be calculated on the
 *     client (through JavaScript), only data- attributes are added to links to
 *     prevent breaking the render cache. The JavaScript is added in
 *     system_page_build().
 *   - Additional $options elements used by the url() function.
 *
 * @return string
 *   An HTML string containing a link to the given path.
 *
 * @see url()
 * @see system_page_build()
 */
function l($text, $path, array $options = array()) {
  // Start building a structured representation of our link to be altered later.
  $variables = array(
    'text' => is_array($text) ? drupal_render($text) : $text,
    'path' => $path,
    'options' => $options,
  );

  // Merge in default options.
  $variables['options'] += array(
    'attributes' => array(),
    'query' => array(),
    'html' => FALSE,
    'language' => NULL,
    'set_active_class' => FALSE,
  );

  // Add a hreflang attribute if we know the language of this link's url and
  // hreflang has not already been set.
  if (!empty($variables['options']['language']) && !isset($variables['options']['attributes']['hreflang'])) {
    $variables['options']['attributes']['hreflang'] = $variables['options']['language']->id;
  }

  // Set the "active" class if the 'set_active_class' option is not empty.
  if (!empty($variables['options']['set_active_class'])) {
    // Add a "data-drupal-link-query" attribute to let the drupal.active-link
    // library know the query in a standardized manner.
    if (!empty($variables['options']['query'])) {
      $query = $variables['options']['query'];
      ksort($query);
      $variables['options']['attributes']['data-drupal-link-query'] = Json::encode($query);
    }

    // Add a "data-drupal-link-system-path" attribute to let the
    // drupal.active-link library know the path in a standardized manner.
    if (!isset($variables['options']['attributes']['data-drupal-link-system-path'])) {
      $variables['options']['attributes']['data-drupal-link-system-path'] = \Drupal::service('path.alias_manager.cached')->getSystemPath($path);
    }
  }

  // Remove all HTML and PHP tags from a tooltip, calling expensive strip_tags()
  // only when a quick strpos() gives suspicion tags are present.
  if (isset($variables['options']['attributes']['title']) && strpos($variables['options']['attributes']['title'], '<') !== FALSE) {
    $variables['options']['attributes']['title'] = strip_tags($variables['options']['attributes']['title']);
  }

  // Allow other modules to modify the structure of the link.
  \Drupal::moduleHandler()->alter('link', $variables);

  // Move attributes out of options. url() doesn't need them.
  $attributes = new Attribute($variables['options']['attributes']);
  unset($variables['options']['attributes']);

  // The result of url() is a plain-text URL. Because we are using it here
  // in an HTML argument context, we need to encode it properly.
  $url = String::checkPlain(url($variables['path'], $variables['options']));

  // Sanitize the link text if necessary.
  $text = $variables['options']['html'] ? $variables['text'] : String::checkPlain($variables['text']);

  return '<a href="' . $url . '"' . $attributes . '>' . $text . '</a>';
}

/**
 * Attempts to set the PHP maximum execution time.
 *
 * This function is a wrapper around the PHP function set_time_limit().
 * When called, set_time_limit() restarts the timeout counter from zero.
 * In other words, if the timeout is the default 30 seconds, and 25 seconds
 * into script execution a call such as set_time_limit(20) is made, the
 * script will run for a total of 45 seconds before timing out.
 *
 * It also means that it is possible to decrease the total time limit if
 * the sum of the new time limit and the current time spent running the
 * script is inferior to the original time limit. It is inherent to the way
 * set_time_limit() works, it should rather be called with an appropriate
 * value every time you need to allocate a certain amount of time
 * to execute a task than only once at the beginning of the script.
 *
 * Before calling set_time_limit(), we check if this function is available
 * because it could be disabled by the server administrator. We also hide all
 * the errors that could occur when calling set_time_limit(), because it is
 * not possible to reliably ensure that PHP or a security extension will
 * not issue a warning/error if they prevent the use of this function.
 *
 * @param $time_limit
 *   An integer specifying the new time limit, in seconds. A value of 0
 *   indicates unlimited execution time.
 *
 * @ingroup php_wrappers
 */
function drupal_set_time_limit($time_limit) {
  if (function_exists('set_time_limit')) {
    @set_time_limit($time_limit);
  }
}

/**
 * Returns the path to a system item (module, theme, etc.).
 *
 * @param $type
 *   The type of the item (i.e. theme, theme_engine, module, profile).
 * @param $name
 *   The name of the item for which the path is requested.
 *
 * @return
 *   The path to the requested item or an empty string if the item is not found.
 */
function drupal_get_path($type, $name) {
  return dirname(drupal_get_filename($type, $name));
}

/**
 * Returns the base URL path (i.e., directory) of the Drupal installation.
 *
 * base_path() adds a "/" to the beginning and end of the returned path if the
 * path is not empty. At the very least, this will return "/".
 *
 * Examples:
 * - http://example.com returns "/" because the path is empty.
 * - http://example.com/drupal/folder returns "/drupal/folder/".
 */
function base_path() {
  return $GLOBALS['base_path'];
}

/**
 * Adds a LINK tag with a distinct 'rel' attribute to the page's HEAD.
 *
 * This function can be called as long the HTML header hasn't been sent, which
 * on normal pages is up through the preprocess step of _theme('html'). Adding
 * a link will overwrite a prior link with the exact same 'rel' and 'href'
 * attributes.
 *
 * @param $attributes
 *   Associative array of element attributes including 'href' and 'rel'.
 * @param $header
 *   Optional flag to determine if a HTTP 'Link:' header should be sent.
 */
function drupal_add_html_head_link($attributes, $header = FALSE) {
  $element = array(
    '#tag' => 'link',
    '#attributes' => $attributes,
  );
  $href = $attributes['href'];

  if ($header) {
    // Also add a HTTP header "Link:".
    $href = '<' . String::checkPlain($attributes['href']) . '>;';
    unset($attributes['href']);
    $element['#attached']['drupal_add_http_header'][] = array('Link',  $href . drupal_http_header_attributes($attributes), TRUE);
  }

  drupal_add_html_head($element, 'drupal_add_html_head_link:' . $attributes['rel'] . ':' . $href);
}

/**
 * Adds a cascading stylesheet to the stylesheet queue.
 *
 * Calling drupal_static_reset('_drupal_add_css') will clear all cascading
 * stylesheets added so far.
 *
 * If CSS aggregation/compression is enabled, all cascading style sheets added
 * with $options['preprocess'] set to TRUE will be merged into one aggregate
 * file and compressed by removing all extraneous white space.
 * Preprocessed inline stylesheets will not be aggregated into this single file;
 * instead, they are just compressed upon output on the page. Externally hosted
 * stylesheets are never aggregated or compressed.
 *
 * The reason for aggregating the files is outlined quite thoroughly here:
 * http://www.die.net/musings/page_load_time/ "Load fewer external objects. Due
 * to request overhead, one bigger file just loads faster than two smaller ones
 * half its size."
 *
 * $options['preprocess'] should be only set to TRUE when a file is required for
 * all typical visitors and most pages of a site. It is critical that all
 * preprocessed files are added unconditionally on every page, even if the
 * files do not happen to be needed on a page. This is normally done by calling
 * _drupal_add_css() in a hook_page_build() implementation.
 *
 * Non-preprocessed files should only be added to the page when they are
 * actually needed.
 *
 * @param $data
 *   (optional) The stylesheet data to be added, depending on what is passed
 *   through to the $options['type'] parameter:
 *   - 'file': The path to the CSS file relative to the base_path(), or a
 *     stream wrapper URI. For example: "modules/devel/devel.css" or
 *     "public://generated_css/stylesheet_1.css". Note that Modules should
 *     always prefix the names of their CSS files with the module name; for
 *     example, system-menus.css rather than simply menus.css. Themes can
 *     override module-supplied CSS files based on their filenames, and this
 *     prefixing helps prevent confusing name collisions for theme developers.
 *     See drupal_get_css() where the overrides are performed.
 *   - 'inline': A string of CSS that should be placed in the given scope. Note
 *     that it is better practice to use 'file' stylesheets, rather than
 *     'inline', as the CSS would then be aggregated and cached.
 *   - 'external': The absolute path to an external CSS file that is not hosted
 *     on the local server. These files will not be aggregated if CSS
 *     aggregation is enabled.
 * @param $options
 *   (optional) A string defining the 'type' of CSS that is being added in the
 *   $data parameter ('file', 'inline', or 'external'), or an array which can
 *   have any or all of the following keys:
 *   - 'type': The type of stylesheet being added. Available options are 'file',
 *     'inline' or 'external'. Defaults to 'file'.
 *   - 'basename': Force a basename for the file being added. Modules are
 *     expected to use stylesheets with unique filenames, but integration of
 *     external libraries may make this impossible. The basename of
 *     'core/modules/node/node.css' is 'node.css'. If the external library
 *     "node.js" ships with a 'node.css', then a different, unique basename
 *     would be 'node.js.css'.
 *   - 'group': A number identifying the aggregation group in which to add the
 *     stylesheet. Available constants are:
 *     - CSS_AGGREGATE_DEFAULT: (default) Any module-layer CSS.
 *     - CSS_AGGREGATE_THEME: Any theme-layer CSS.
 *     The aggregate group number affects load order and the CSS cascade.
 *     Stylesheets in an aggregate with a lower group number will be output to
 *     the page before stylesheets in an aggregate with a higher group number,
 *     so CSS within higher aggregate groups can take precendence over CSS
 *     within lower aggregate groups.
 *   - 'every_page': For optimal front-end performance when aggregation is
 *     enabled, this should be set to TRUE if the stylesheet is present on every
 *     page of the website for users for whom it is present at all. This
 *     defaults to FALSE. It is set to TRUE for stylesheets added via module and
 *     theme .info.yml files. Modules that add stylesheets within
 *     hook_page_build() implementations, or from other code that ensures that
 *     the stylesheet is added to all website pages, should also set this flag
 *     to TRUE. All stylesheets within the same group that have the 'every_page'
 *     flag set to TRUE and do not have 'preprocess' set to FALSE are aggregated
 *     together into a single aggregate file, and that aggregate file can be
 *     reused across a user's entire site visit, leading to faster navigation
 *     between pages.
 *     However, stylesheets that are only needed on pages less frequently
 *     visited, can be added by code that only runs for those particular pages,
 *     and that code should not set the 'every_page' flag. This minimizes the
 *     size of the aggregate file that the user needs to download when first
 *     visiting the website. Stylesheets without the 'every_page' flag are
 *     aggregated into a separate aggregate file. This other aggregate file is
 *     likely to change from page to page, and each new aggregate file needs to
 *     be downloaded when first encountered, so it should be kept relatively
 *     small by ensuring that most commonly needed stylesheets are added to
 *     every page.
 *   - 'weight': The weight of the stylesheet specifies the order in which the
 *     CSS will appear relative to other stylesheets with the same aggregate
 *     group and 'every_page' flag. The exact ordering of stylesheets is as
 *     follows:
 *     - First by aggregate group.
 *     - Then by the 'every_page' flag, with TRUE coming before FALSE.
 *     - Then by weight.
 *     - Then by the order in which the CSS was added. For example, all else
 *       being the same, a stylesheet added by a call to _drupal_add_css() that
 *       happened later in the page request gets added to the page after one for
 *       which _drupal_add_css() happened earlier in the page request.
 *     Available constants are:
 *     - CSS_BASE: Styles for HTML elements ("base" styles).
 *     - CSS_LAYOUT: Styles that layout a page.
 *     - CSS_COMPONENT: Styles for design components (and their associated
 *       states and themes.)
 *     - CSS_STATE: Styles for states that are not included with components.
 *     - CSS_THEME: Styles for themes that are not included with components.
 *     The weight numbers follow the SMACSS convention of CSS categorization.
 *     See http://drupal.org/node/1887922
 *   - 'media': The media type for the stylesheet, e.g., all, print, screen.
 *     Defaults to 'all'. It is extremely important to leave this set to 'all'
 *     or it will negatively impact front-end peformance. Instead add a @media
 *     block to the included CSS file.
 *   - 'preprocess': If TRUE and CSS aggregation/compression is enabled, the
 *     styles will be aggregated and compressed. Defaults to TRUE.
 *   - 'browsers': An array containing information specifying which browsers
 *     should load the CSS item. See drupal_pre_render_conditional_comments()
 *     for details.
 *
 * @return
 *   An array of queued cascading stylesheets.
 *
 * @deprecated as of Drupal 8.0. Use the #attached key in render arrays instead.
 *
 * @see drupal_get_css()
 */
function _drupal_add_css($data = NULL, $options = NULL) {
  $css = &drupal_static(__FUNCTION__, array());

  // Construct the options, taking the defaults into consideration.
  if (isset($options)) {
    if (!is_array($options)) {
      $options = array('type' => $options);
    }
  }
  else {
    $options = array();
  }

  // Create an array of CSS files for each media type first, since each type needs to be served
  // to the browser differently.
  if (isset($data)) {
    $options += array(
      'type' => 'file',
      'group' => CSS_AGGREGATE_DEFAULT,
      'weight' => 0,
      'every_page' => FALSE,
      'media' => 'all',
      'preprocess' => TRUE,
      'data' => $data,
      'browsers' => array(),
    );
    $options['browsers'] += array(
      'IE' => TRUE,
      '!IE' => TRUE,
    );

    // Files with a query string cannot be preprocessed.
    if ($options['type'] === 'file' && $options['preprocess'] && strpos($options['data'], '?') !== FALSE) {
      $options['preprocess'] = FALSE;
    }

    // Always add a tiny value to the weight, to conserve the insertion order.
    $options['weight'] += count($css) / 1000;

    // Add the data to the CSS array depending on the type.
    switch ($options['type']) {
      case 'inline':
        // For inline stylesheets, we don't want to use the $data as the array
        // key as $data could be a very long string of CSS.
        $css[] = $options;
        break;

      case 'file':
        // Local CSS files are keyed by basename; if a file with the same
        // basename is added more than once, it gets overridden.
        // By default, take over the filename as basename.
        if (!isset($options['basename'])) {
          $options['basename'] = drupal_basename($data);
        }
        $css[$options['basename']] = $options;
        break;

      default:
        // External files are keyed by their full URI, so the same CSS file is
        // not added twice.
        $css[$data] = $options;
    }
  }

  return $css;
}

/**
 * Returns a themed representation of all stylesheets to attach to the page.
 *
 * It loads the CSS in order, with 'module' first, then 'theme' afterwards.
 * This ensures proper cascading of styles so themes can easily override
 * module styles through CSS selectors.
 *
 * Themes may replace module-defined CSS files by adding a stylesheet with the
 * same filename. For example, themes/bartik/system-menus.css would replace
 * modules/system/system-menus.css. This allows themes to override complete
 * CSS files, rather than specific selectors, when necessary.
 *
 * @param $css
 *   (optional) An array of CSS files. If no array is provided, the default
 *   stylesheets array is used instead.
 * @param $skip_alter
 *   (optional) If set to TRUE, this function skips calling
 *   \Drupal::moduleHandler->alter() on $css, useful when the calling function
 *   passes a $css array that has already been altered.
 *
 * @return
 *   A string of XHTML CSS tags.
 *
 * @see _drupal_add_css()
 */
function drupal_get_css($css = NULL, $skip_alter = FALSE) {
  global $theme_info;

  if (!isset($css)) {
    $css = _drupal_add_css();
  }

  // Allow modules and themes to alter the CSS items.
  if (!$skip_alter) {
    \Drupal::moduleHandler()->alter('css', $css);
  }

  // Sort CSS items, so that they appear in the correct order.
  uasort($css, 'drupal_sort_css_js');

  // Allow themes to remove CSS files by basename.
  if (!empty($theme_info->stylesheets_remove)) {
    foreach ($css as $key => $options) {
      if (isset($options['basename']) && isset($theme_info->stylesheets_remove[$options['basename']])) {
        unset($css[$key]);
      }
    }
  }
  // Allow themes to conditionally override CSS files by basename.
  if (!empty($theme_info->stylesheets_override)) {
    foreach ($css as $key => $options) {
      if (isset($options['basename']) && isset($theme_info->stylesheets_override[$options['basename']])) {
        $css[$key]['data'] = $theme_info->stylesheets_override[$options['basename']];
      }
    }
  }

  // Render the HTML needed to load the CSS.
  $styles = array(
    '#type' => 'styles',
    '#items' => $css,
  );
  if (!empty($setting)) {
    $styles['#attached']['js'][] = array('type' => 'setting', 'data' => $setting);
  }

  return drupal_render($styles);
}

/**
 * Sorts CSS and JavaScript resources.
 *
 * Callback for uasort() within:
 * - drupal_get_css()
 * - drupal_get_js()
 *
 * This sort order helps optimize front-end performance while providing modules
 * and themes with the necessary control for ordering the CSS and JavaScript
 * appearing on a page.
 *
 * @param $a
 *   First item for comparison. The compared items should be associative arrays
 *   of member items from _drupal_add_css() or _drupal_add_js().
 * @param $b
 *   Second item for comparison.
 *
 * @see _drupal_add_css()
 * @see _drupal_add_js()
 */
function drupal_sort_css_js($a, $b) {
  // First order by group, so that all items in the CSS_AGGREGATE_DEFAULT group
  // appear before items in the CSS_AGGREGATE_THEME group. Modules may create
  // additional groups by defining their own constants.
  if ($a['group'] < $b['group']) {
    return -1;
  }
  elseif ($a['group'] > $b['group']) {
    return 1;
  }
  // Within a group, order all infrequently needed, page-specific files after
  // common files needed throughout the website. Separating this way allows for
  // the aggregate file generated for all of the common files to be reused
  // across a site visit without being cut by a page using a less common file.
  elseif ($a['every_page'] && !$b['every_page']) {
    return -1;
  }
  elseif (!$a['every_page'] && $b['every_page']) {
    return 1;
  }
  // Finally, order by weight.
  elseif ($a['weight'] < $b['weight']) {
    return -1;
  }
  elseif ($a['weight'] > $b['weight']) {
    return 1;
  }
  else {
    return 0;
  }
}

/**
 * Pre-render callback: Adds the elements needed for CSS tags to be rendered.
 *
 * For production websites, LINK tags are preferable to STYLE tags with @import
 * statements, because:
 * - They are the standard tag intended for linking to a resource.
 * - On Firefox 2 and perhaps other browsers, CSS files included with @import
 *   statements don't get saved when saving the complete web page for offline
 *   use: http://drupal.org/node/145218.
 * - On IE, if only LINK tags and no @import statements are used, all the CSS
 *   files are downloaded in parallel, resulting in faster page load, but if
 *   @import statements are used and span across multiple STYLE tags, all the
 *   ones from one STYLE tag must be downloaded before downloading begins for
 *   the next STYLE tag. Furthermore, IE7 does not support media declaration on
 *   the @import statement, so multiple STYLE tags must be used when different
 *   files are for different media types. Non-IE browsers always download in
 *   parallel, so this is an IE-specific performance quirk:
 *   http://www.stevesouders.com/blog/2009/04/09/dont-use-import/.
 *
 * However, IE has an annoying limit of 31 total CSS inclusion tags
 * (http://drupal.org/node/228818) and LINK tags are limited to one file per
 * tag, whereas STYLE tags can contain multiple @import statements allowing
 * multiple files to be loaded per tag. When CSS aggregation is disabled, a
 * Drupal site can easily have more than 31 CSS files that need to be loaded, so
 * using LINK tags exclusively would result in a site that would display
 * incorrectly in IE. Depending on different needs, different strategies can be
 * employed to decide when to use LINK tags and when to use STYLE tags.
 *
 * The strategy employed by this function is to use LINK tags for all aggregate
 * files and for all files that cannot be aggregated (e.g., if 'preprocess' is
 * set to FALSE or the type is 'external'), and to use STYLE tags for groups
 * of files that could be aggregated together but aren't (e.g., if the site-wide
 * aggregation setting is disabled). This results in all LINK tags when
 * aggregation is enabled, a guarantee that as many or only slightly more tags
 * are used with aggregation disabled than enabled (so that if the limit were to
 * be crossed with aggregation enabled, the site developer would also notice the
 * problem while aggregation is disabled), and an easy way for a developer to
 * view HTML source while aggregation is disabled and know what files will be
 * aggregated together when aggregation becomes enabled.
 *
 * This function evaluates the aggregation enabled/disabled condition on a group
 * by group basis by testing whether an aggregate file has been made for the
 * group rather than by testing the site-wide aggregation setting. This allows
 * this function to work correctly even if modules have implemented custom
 * logic for grouping and aggregating files.
 *
 * @param $element
 *   A render array containing:
 *   - '#items': The CSS items as returned by _drupal_add_css() and altered by
 *     drupal_get_css().
 *
 * @return
 *   A render array that will render to a string of XHTML CSS tags.
 *
 * @see drupal_get_css()
 */
function drupal_pre_render_styles($elements) {
  $css_assets = $elements['#items'];

  // Aggregate the CSS if necessary, but only during normal site operation.
  if (!defined('MAINTENANCE_MODE') && \Drupal::config('system.performance')->get('css.preprocess')) {
    $css_assets = \Drupal::service('asset.css.collection_optimizer')->optimize($css_assets);
  }
  return \Drupal::service('asset.css.collection_renderer')->render($css_assets);
}

/**
 * Deletes old cached CSS files.
 */
function drupal_clear_css_cache() {
  \Drupal::state()->delete('drupal_css_cache_files');
  file_scan_directory('public://css', '/.*/', array('callback' => 'drupal_delete_file_if_stale'));
}

/**
 * Deletes files modified more than a set time ago.
 *
 * Callback for file_scan_directory() within:
 * - drupal_clear_css_cache()
 * - drupal_clear_js_cache()
 */
function drupal_delete_file_if_stale($uri) {
  // Default stale file threshold is 30 days.
  if (REQUEST_TIME - filemtime($uri) > \Drupal::config('system.performance')->get('stale_file_threshold')) {
    file_unmanaged_delete($uri);
  }
}

/**
 * Prepares a string for use as a CSS identifier (element, class, or ID name).
 *
 * http://www.w3.org/TR/CSS21/syndata.html#characters shows the syntax for valid
 * CSS identifiers (including element names, classes, and IDs in selectors.)
 *
 * @param $identifier
 *   The identifier to clean.
 * @param $filter
 *   An array of string replacements to use on the identifier.
 *
 * @return
 *   The cleaned identifier.
 */
function drupal_clean_css_identifier($identifier, $filter = array(' ' => '-', '_' => '-', '__' => '__', '/' => '-', '[' => '-', ']' => '')) {
  // By default, we filter using Drupal's coding standards.
  $identifier = strtr($identifier, $filter);

  // Valid characters in a CSS identifier are:
  // - the hyphen (U+002D)
  // - a-z (U+0030 - U+0039)
  // - A-Z (U+0041 - U+005A)
  // - the underscore (U+005F)
  // - 0-9 (U+0061 - U+007A)
  // - ISO 10646 characters U+00A1 and higher
  // We strip out any character not in the above list.
  $identifier = preg_replace('/[^\x{002D}\x{0030}-\x{0039}\x{0041}-\x{005A}\x{005F}\x{0061}-\x{007A}\x{00A1}-\x{FFFF}]/u', '', $identifier);

  // Identifiers cannot start with a digit, two hyphens, or a hyphen followed by a digit.
  $identifier = preg_replace(array('/^[0-9]/', '/^(-[0-9])|^(--)/'), array('_', '__') , $identifier);

  return $identifier;
}

/**
 * Prepares a string for use as a valid class name.
 *
 * Do not pass one string containing multiple classes as they will be
 * incorrectly concatenated with dashes, i.e. "one two" will become "one-two".
 *
 * @param $class
 *   The class name to clean.
 *
 * @return
 *   The cleaned class name.
 */
function drupal_html_class($class) {
  // The output of this function will never change, so this uses a normal
  // static instead of drupal_static().
  static $classes = array();

  if (!isset($classes[$class])) {
    $classes[$class] = drupal_clean_css_identifier(drupal_strtolower($class));
  }
  return $classes[$class];
}

/**
 * Prepares a string for use as a valid HTML ID and guarantees uniqueness.
 *
 * This function ensures that each passed HTML ID value only exists once on the
 * page. By tracking the already returned ids, this function enables forms,
 * blocks, and other content to be output multiple times on the same page,
 * without breaking (X)HTML validation.
 *
 * For already existing IDs, a counter is appended to the ID string. Therefore,
 * JavaScript and CSS code should not rely on any value that was generated by
 * this function and instead should rely on manually added CSS classes or
 * similarly reliable constructs.
 *
 * Two consecutive hyphens separate the counter from the original ID. To manage
 * uniqueness across multiple Ajax requests on the same page, Ajax requests
 * POST an array of all IDs currently present on the page, which are used to
 * prime this function's cache upon first invocation.
 *
 * To allow reverse-parsing of IDs submitted via Ajax, any multiple consecutive
 * hyphens in the originally passed $id are replaced with a single hyphen.
 *
 * @param $id
 *   The ID to clean.
 *
 * @return
 *   The cleaned ID.
 */
function drupal_html_id($id) {
  // If this is an Ajax request, then content returned by this page request will
  // be merged with content already on the base page. The HTML IDs must be
  // unique for the fully merged content. Therefore, initialize $seen_ids to
  // take into account IDs that are already in use on the base page.
  $seen_ids_init = &drupal_static(__FUNCTION__ . ':init');
  if (!isset($seen_ids_init)) {
    $ajax_html_ids = \Drupal::request()->request->get('ajax_html_ids');
    // Ideally, Drupal would provide an API to persist state information about
    // prior page requests in the database, and we'd be able to add this
    // function's $seen_ids static variable to that state information in order
    // to have it properly initialized for this page request. However, no such
    // page state API exists, so instead, ajax.js adds all of the in-use HTML
    // IDs to the POST data of Ajax submissions. Direct use of $_POST is
    // normally not recommended as it could open up security risks, but because
    // the raw POST data is cast to a number before being returned by this
    // function, this usage is safe.
    if (empty($ajax_html_ids)) {
      $seen_ids_init = array();
    }
    else {
      // This function ensures uniqueness by appending a counter to the base id
      // requested by the calling function after the first occurrence of that
      // requested id. $_POST['ajax_html_ids'] contains the ids as they were
      // returned by this function, potentially with the appended counter, so
      // we parse that to reconstruct the $seen_ids array.
      $ajax_html_ids = explode(' ', $ajax_html_ids);
      foreach ($ajax_html_ids as $seen_id) {
        // We rely on '--' being used solely for separating a base id from the
        // counter, which this function ensures when returning an id.
        $parts = explode('--', $seen_id, 2);
        if (!empty($parts[1]) && is_numeric($parts[1])) {
          list($seen_id, $i) = $parts;
        }
        else {
          $i = 1;
        }
        if (!isset($seen_ids_init[$seen_id]) || ($i > $seen_ids_init[$seen_id])) {
          $seen_ids_init[$seen_id] = $i;
        }
      }
    }
  }
  $seen_ids = &drupal_static(__FUNCTION__, $seen_ids_init);

  $id = drupal_clean_id_identifier($id);
  // Ensure IDs are unique by appending a counter after the first occurrence.
  // The counter needs to be appended with a delimiter that does not exist in
  // the base ID. Requiring a unique delimiter helps ensure that we really do
  // return unique IDs and also helps us re-create the $seen_ids array during
  // Ajax requests.
  if (isset($seen_ids[$id])) {
    $id = $id . '--' . ++$seen_ids[$id];
  }
  else {
    $seen_ids[$id] = 1;
  }

  return $id;
}

/**
 * Prepares a string for use as a valid HTML ID.
 *
 * Only use this function when you want to intentionally skip the uniqueness
 * guarantee of drupal_html_id().
 *
 * @param string $id
 *   The ID to clean.
 *
 * @return string
 *   The cleaned ID.
 *
 * @see drupal_html_id()
 */
function drupal_clean_id_identifier($id) {
  $id = strtr(drupal_strtolower($id), array(' ' => '-', '_' => '-', '[' => '-', ']' => ''));

  // As defined in http://www.w3.org/TR/html4/types.html#type-name, HTML IDs can
  // only contain letters, digits ([0-9]), hyphens ("-"), underscores ("_"),
  // colons (":"), and periods ("."). We strip out any character not in that
  // list. Note that the CSS spec doesn't allow colons or periods in identifiers
  // (http://www.w3.org/TR/CSS21/syndata.html#characters), so we strip those two
  // characters as well.
  $id = preg_replace('/[^A-Za-z0-9\-_]/', '', $id);

  // Removing multiple consecutive hyphens.
  $id = preg_replace('/\-+/', '-', $id);
  return $id;
}

/**
 * Adds a JavaScript file, setting, or inline code to the page.
 *
 * The behavior of this function depends on the parameters it is called with.
 * Generally, it handles the addition of JavaScript to the page, either as
 * reference to an existing file or as inline code. The following actions can be
 * performed using this function:
 * - Add a file ('file'): Adds a reference to a JavaScript file to the page.
 * - Add inline JavaScript code ('inline'): Executes a piece of JavaScript code
 *   on the current page by placing the code directly in the page (for example,
 *   to tell the user that a new message arrived, by opening a pop up, alert
 *   box, etc.). This should only be used for JavaScript that cannot be executed
 *   from a file. When adding inline code, make sure that you are not relying on
 *   $() being the jQuery function. Wrap your code in
 *   @code (function ($) {... })(jQuery); @endcode
 *   or use jQuery() instead of $().
 * - Add external JavaScript ('external'): Allows the inclusion of external
 *   JavaScript files that are not hosted on the local server. Note that these
 *   external JavaScript references do not get aggregated when preprocessing is
 *   on.
 * - Add settings ('setting'): Adds settings to Drupal's global storage of
 *   JavaScript settings. Per-page settings are required by some modules to
 *   function properly. All settings will be accessible at drupalSettings.
 *
 * Examples:
 * @code
 *   _drupal_add_js('core/misc/collapse.js');
 *   _drupal_add_js('core/misc/collapse.js', 'file');
 *   _drupal_add_js('jQuery(document).ready(function () { alert("Hello!"); });', 'inline');
 *   _drupal_add_js('jQuery(document).ready(function () { alert("Hello!"); });',
 *     array('type' => 'inline', 'scope' => 'footer', 'weight' => 5)
 *   );
 *   _drupal_add_js('http://example.com/example.js', 'external');
 *   _drupal_add_js(array('myModule' => array('key' => 'value')), 'setting');
 * @endcode
 *
 * Calling drupal_static_reset('_drupal_add_js') will clear all JavaScript added
 * so far.
 *
 * If JavaScript aggregation is enabled, all JavaScript files added with
 * $options['preprocess'] set to TRUE will be merged into one aggregate file.
 * Preprocessed inline JavaScript will not be aggregated into this single file.
 * Externally hosted JavaScripts are never aggregated.
 *
 * The reason for aggregating the files is outlined quite thoroughly here:
 * http://www.die.net/musings/page_load_time/ "Load fewer external objects. Due
 * to request overhead, one bigger file just loads faster than two smaller ones
 * half its size."
 *
 * $options['preprocess'] should be only set to TRUE when a file is required for
 * all typical visitors and most pages of a site. It is critical that all
 * preprocessed files are added unconditionally on every page, even if the
 * files are not needed on a page. This is normally done by calling
 * _drupal_add_js() in a hook_page_build() implementation.
 *
 * Non-preprocessed files should only be added to the page when they are
 * actually needed.
 *
 * @param $data
 *   (optional) If given, the value depends on the $options parameter, or
 *   $options['type'] if $options is passed as an associative array:
 *   - 'file': Path to the file relative to base_path().
 *   - 'inline': The JavaScript code that should be placed in the given scope.
 *   - 'external': The absolute path to an external JavaScript file that is not
 *     hosted on the local server. These files will not be aggregated if
 *     JavaScript aggregation is enabled.
 *   - 'setting': An associative array with configuration options. The array is
 *     merged directly into drupalSettings. All modules should wrap their
 *     actual configuration settings in another variable to prevent conflicts in
 *     the drupalSettings namespace. Items added with a string key will replace
 *     existing settings with that key; items with numeric array keys will be
 *     added to the existing settings array.
 * @param $options
 *   (optional) A string defining the type of JavaScript that is being added in
 *   the $data parameter ('file'/'setting'/'inline'/'external'), or an
 *   associative array. JavaScript settings should always pass the string
 *   'setting' only. Other types can have the following elements in the array:
 *   - type: The type of JavaScript that is to be added to the page. Allowed
 *     values are 'file', 'inline', 'external' or 'setting'. Defaults
 *     to 'file'.
 *   - scope: The location in which you want to place the script. Possible
 *     values are 'header' or 'footer'. If your theme implements different
 *     regions, you can also use these. Defaults to 'header'.
 *   - group: A number identifying the group in which to add the JavaScript.
 *     Available constants are:
 *     - JS_LIBRARY: Any libraries, settings, or jQuery plugins.
 *     - JS_DEFAULT: Any module-layer JavaScript.
 *     - JS_THEME: Any theme-layer JavaScript.
 *     The group number serves as a weight: JavaScript within a lower weight
 *     group is presented on the page before JavaScript within a higher weight
 *     group.
 *   - every_page: For optimal front-end performance when aggregation is
 *     enabled, this should be set to TRUE if the JavaScript is present on every
 *     page of the website for users for whom it is present at all. This
 *     defaults to FALSE. It is set to TRUE for JavaScript files that are added
 *     via module and theme .info.yml files. Modules that add JavaScript within
 *     hook_page_build() implementations, or from other code that ensures that
 *     the JavaScript is added to all website pages, should also set this flag
 *     to TRUE. All JavaScript files within the same group and that have the
 *     'every_page' flag set to TRUE and do not have 'preprocess' set to FALSE
 *     are aggregated together into a single aggregate file, and that aggregate
 *     file can be reused across a user's entire site visit, leading to faster
 *     navigation between pages. However, JavaScript that is only needed on
 *     pages less frequently visited, can be added by code that only runs for
 *     those particular pages, and that code should not set the 'every_page'
 *     flag. This minimizes the size of the aggregate file that the user needs
 *     to download when first visiting the website. JavaScript without the
 *     'every_page' flag is aggregated into a separate aggregate file. This
 *     other aggregate file is likely to change from page to page, and each new
 *     aggregate file needs to be downloaded when first encountered, so it
 *     should be kept relatively small by ensuring that most commonly needed
 *     JavaScript is added to every page.
 *   - weight: A number defining the order in which the JavaScript is added to
 *     the page relative to other JavaScript with the same 'scope', 'group',
 *     and 'every_page' value. In some cases, the order in which the JavaScript
 *     is presented on the page is very important. jQuery, for example, must be
 *     added to the page before any jQuery code is run, so jquery.js uses the
 *     JS_LIBRARY group and a weight of -20, jquery.once.js (a library drupal.js
 *     depends on) uses the JS_LIBRARY group and a weight of -19, drupal.js uses
 *     the JS_LIBRARY group and a weight of -1, other libraries use the
 *     JS_LIBRARY group and a weight of 0 or higher, and all other scripts use
 *     one of the other group constants. The exact ordering of JavaScript is as
 *     follows:
 *     - First by scope, with 'header' first, 'footer' last, and any other
 *       scopes provided by a custom theme coming in between, as determined by
 *       the theme.
 *     - Then by group.
 *     - Then by the 'every_page' flag, with TRUE coming before FALSE.
 *     - Then by weight.
 *     - Then by the order in which the JavaScript was added. For example, all
 *       else being the same, JavaScript added by a call to _drupal_add_js() that
 *       happened later in the page request gets added to the page after one for
 *       which _drupal_add_js() happened earlier in the page request.
 *   - cache: If set to FALSE, the JavaScript file is loaded anew on every page
 *     call; in other words, it is not cached. Used only when 'type' references
 *     a JavaScript file. Defaults to TRUE.
 *   - preprocess: If TRUE and JavaScript aggregation is enabled, the script
 *     file will be aggregated. Defaults to TRUE.
 *   - attributes: An associative array of attributes for the <script> tag. This
 *     may be used to add 'defer', 'async', or custom attributes. Note that
 *     setting any attributes will disable preprocessing as though the
 *     'preprocess' option was set to FALSE.
 *   - browsers: An array containing information specifying which browsers
 *     should load the JavaScript item. See
 *     drupal_pre_render_conditional_comments() for details.
 *
 * @return
 *   The current array of JavaScript files, settings, and in-line code,
 *   including Drupal defaults, anything previously added with calls to
 *   _drupal_add_js(), and this function call's additions.
 *
 * @deprecated as of Drupal 8.0. Use the #attached key in render arrays instead.
 *
 * @see drupal_get_js()
 */
function _drupal_add_js($data = NULL, $options = NULL) {
  $javascript = &drupal_static(__FUNCTION__, array());

  // Construct the options, taking the defaults into consideration.
  if (isset($options)) {
    if (!is_array($options)) {
      $options = array('type' => $options);
    }
  }
  else {
    $options = array();
  }
  $options += drupal_js_defaults($data);

  // Preprocess can only be set if caching is enabled and no attributes are set.
  $options['preprocess'] = $options['cache'] && empty($options['attributes']) ? $options['preprocess'] : FALSE;

  // Tweak the weight so that files of the same weight are included in the
  // order of the calls to _drupal_add_js().
  $options['weight'] += count($javascript) / 1000;
  if (isset($data)) {
    switch ($options['type']) {
      case 'setting':
        // If the setting array doesn't exist, add defaults values.
        if (!isset($javascript['settings'])) {
          $javascript['settings'] = array(
            'type' => 'setting',
            'scope' => 'header',
            'group' => JS_SETTING,
            'every_page' => TRUE,
            'weight' => 0,
            'browsers' => array(),
          );
          // url() generates the script and prefix using hook_url_outbound_alter().
          // Instead of running the hook_url_outbound_alter() again here, extract
          // them from url().
          // @todo Make this less hacky: http://drupal.org/node/1547376.
          $scriptPath = $GLOBALS['script_path'];
          $pathPrefix = '';
          $current_query = \Drupal::service('request')->query->all();
          url('', array('script' => &$scriptPath, 'prefix' => &$pathPrefix));
          $current_path = current_path();
          $current_path_is_admin = FALSE;
          // The function path_is_admin() is not available on update.php pages.
          if (!(defined('MAINTENANCE_MODE') && MAINTENANCE_MODE === 'update')) {
            $current_path_is_admin = path_is_admin($current_path);
          }
          $path = array(
            'basePath' => base_path(),
            'scriptPath' => $scriptPath,
            'pathPrefix' => $pathPrefix,
            'currentPath' => $current_path,
            'currentPathIsAdmin' => $current_path_is_admin,
            'isFront' => drupal_is_front_page(),
            'currentLanguage' => \Drupal::languageManager()->getCurrentLanguage(Language::TYPE_URL)->id,
          );
          if (!empty($current_query)) {
            ksort($current_query);
            $path['currentQuery'] = (object) $current_query;
          }
          $javascript['settings']['data'][] = array('path' => $path);
        }
        // All JavaScript settings are placed in the header of the page with
        // the library weight so that inline scripts appear afterwards.
        $javascript['settings']['data'][] = $data;
        break;

      case 'inline':
        $javascript[] = $options;
        break;

      default: // 'file' and 'external'
        // Local and external files must keep their name as the associative key
        // so the same JavaScript file is not added twice.
        $javascript[$options['data']] = $options;
    }
  }
  return $javascript;
}

/**
 * Constructs an array of the defaults that are used for JavaScript items.
 *
 * @param $data
 *   (optional) The default data parameter for the JavaScript item array.
 *
 * @see drupal_get_js()
 * @see _drupal_add_js()
 */
function drupal_js_defaults($data = NULL) {
  return array(
    'type' => 'file',
    'group' => JS_DEFAULT,
    'every_page' => FALSE,
    'weight' => 0,
    'scope' => 'header',
    'cache' => TRUE,
    'preprocess' => TRUE,
    'attributes' => array(),
    'version' => NULL,
    'data' => $data,
    'browsers' => array(),
  );
}

/**
 * Returns a themed presentation of all JavaScript code for the current page.
 *
 * References to JavaScript files are placed in a certain order: first, all
 * 'core' files, then all 'module' and finally all 'theme' JavaScript files
 * are added to the page. Then, all settings are output, followed by 'inline'
 * JavaScript code. If running update.php, all preprocessing is disabled.
 *
 * Note that hook_js_alter(&$javascript) is called during this function call
 * to allow alterations of the JavaScript during its presentation. Calls to
 * _drupal_add_js() from hook_js_alter() will not be added to the output
 * presentation. The correct way to add JavaScript during hook_js_alter()
 * is to add another element to the $javascript array, deriving from
 * drupal_js_defaults(). See locale_js_alter() for an example of this.
 *
 * @param $scope
 *   (optional) The scope for which the JavaScript rules should be returned.
 *   Defaults to 'header'.
 * @param $javascript
 *   (optional) An array with all JavaScript code. Defaults to the default
 *   JavaScript array for the given scope.
 * @param bool $skip_alter
 *   (optional) If set to TRUE, this function skips calling
 *   \Drupal::moduleHandler->alter() on $javascript, useful when the calling
 *   function passes a $javascript array that has already been altered.
 * @param bool $is_ajax
 *   (optional) If set to TRUE, this function is called from an Ajax request and
 *   adds javascript settings to update ajaxPageState values.
 *
 * @return
 *   All JavaScript code segments and includes for the scope as HTML tags.
 *
 * @see _drupal_add_js()
 * @see locale_js_alter()
 * @see drupal_js_defaults()
 */
function drupal_get_js($scope = 'header', $javascript = NULL, $skip_alter = FALSE, $is_ajax = FALSE) {
  if (!isset($javascript)) {
    $javascript = _drupal_add_js();
  }
  if (empty($javascript)) {
    return '';
  }

  // Allow modules to alter the JavaScript.
  if (!$skip_alter) {
    \Drupal::moduleHandler()->alter('js', $javascript);
  }

  // Filter out elements of the given scope.
  $items = array();
  foreach ($javascript as $key => $item) {
    if ($item['scope'] == $scope) {
      $items[$key] = $item;
    }
  }

  if (!empty($items)) {
    // Sort the JavaScript files so that they appear in the correct order.
    uasort($items, 'drupal_sort_css_js');
    // Don't add settings if there is no other JavaScript on the page, unless
    // this is an AJAX request.
    if (!empty($items['settings']) || $is_ajax) {
      global $theme_key;
      // Provide the page with information about the theme that's used, so that
      // a later AJAX request can be rendered using the same theme.
      // @see \Drupal\Core\Theme\AjaxBasePageNegotiator
      $setting['ajaxPageState']['theme'] = $theme_key;
      // Checks that the DB is available before filling theme_token.
      if (!defined('MAINTENANCE_MODE')) {
        $setting['ajaxPageState']['theme_token'] = drupal_get_token($theme_key);
      }

      // Provide the page with information about the individual JavaScript files
      // used, information not otherwise available when aggregation is enabled.
      $setting['ajaxPageState']['js'] = array_fill_keys(array_keys($javascript), 1);
      unset($setting['ajaxPageState']['js']['settings']);

      // Provide the page with information about the individual CSS files used,
      // information not otherwise available when CSS aggregation is enabled.
      // The setting is attached later in this function, but is set here, so
      // that CSS files removed in drupal_process_attached() are still
      // considered "used" and prevented from being added in a later AJAX
      // request.
      // Skip if no files were added to the page otherwise jQuery.extend() will
      // overwrite the drupalSettings.ajaxPageState.css object with an empty
      // array.
      $css = _drupal_add_css();
      if (!empty($css)) {
        // Cast the array to an object to be on the safe side even if not empty.
        $setting['ajaxPageState']['css'] = (object) array_fill_keys(array_keys($css), 1);
      }

      _drupal_add_js($setting, 'setting');

      // If we're outputting the header scope, then this might be the final time
      // that drupal_get_js() is running, so add the settings to this output as well
      // as to the _drupal_add_js() cache. If $items['settings'] doesn't exist, it's
      // because drupal_get_js() was intentionally passed a $javascript argument
      // stripped of settings, potentially in order to override how settings get
      // output, so in this case, do not add the setting to this output.
      if ($scope == 'header' && isset($items['settings'])) {
        $items['settings']['data'][] = $setting;
      }
    }
  }

  // Render the HTML needed to load the JavaScript.
  $elements = array(
    '#type' => 'scripts',
    '#items' => $items,
  );

  return drupal_render($elements);
}

/**
 * Merges an array of settings arrays into a single settings array.
 *
 * This function merges the items in the same way that
 *
 * @code
 *   jQuery.extend(true, {}, $settings_items[0], $settings_items[1], ...)
 * @endcode
 *
 * would. This means integer indices are preserved just like string indices are,
 * rather than re-indexed as is common in PHP array merging.
 *
 * Example:
 * @code
 * function module1_page_build(&$page) {
 *   $page['#attached']['js'][] = array(
 *     'type' => 'setting',
 *     'data' => array('foo' => array('a', 'b', 'c')),
 *   );
 * }
 * function module2_page_build(&$page) {
 *   $page['#attached']['js'][] = array(
 *     'type' => 'setting',
 *     'data' => array('foo' => array('d')),
 *   );
 * }
 * // When the page is rendered after the above code, and the browser runs the
 * // resulting <SCRIPT> tags, the value of drupalSettings.foo is
 * // ['d', 'b', 'c'], not ['a', 'b', 'c', 'd'].
 * @endcode
 *
 * By following jQuery.extend() merge logic rather than common PHP array merge
 * logic, the following are ensured:
 * - _drupal_add_js() is idempotent: calling it twice with the same parameters
 *   does not change the output sent to the browser.
 * - If pieces of the page are rendered in separate PHP requests and the
 *   returned settings are merged by JavaScript, the resulting settings are the
 *   same as if rendered in one PHP request and merged by PHP.
 *
 * @param $settings_items
 *   An array of settings arrays, as returned by:
 *   @code
 *     $js = _drupal_add_js();
 *     $settings_items = $js['settings']['data'];
 *   @endcode
 *
 * @return
 *   A merged $settings array, suitable for JSON encoding and returning to the
 *   browser.
 *
 * @see _drupal_add_js()
 * @see drupal_pre_render_scripts()
 */
function drupal_merge_js_settings($settings_items) {
  return NestedArray::mergeDeepArray($settings_items, TRUE);
}

/**
 * Merges two #attached arrays.
 *
 * @param array $a
 *   An #attached array.
 * @param array $b
 *   Another #attached array.
 *
 * @return array
 *   The merged #attached array.
 */
function drupal_merge_attached(array $a, array $b) {
  return NestedArray::mergeDeep($a, $b);
}

/**
 * #pre_render callback to add the elements needed for JavaScript tags to be rendered.
 *
 * This function evaluates the aggregation enabled/disabled condition on a group
 * by group basis by testing whether an aggregate file has been made for the
 * group rather than by testing the site-wide aggregation setting. This allows
 * this function to work correctly even if modules have implemented custom
 * logic for grouping and aggregating files.
 *
 * @param $element
 *   A render array containing:
 *   - #items: The JavaScript items as returned by _drupal_add_js() and
 *     altered by drupal_get_js().
 *   - #group_callback: A function to call to group #items. Following
 *     this function, #aggregate_callback is called to aggregate items within
 *     the same group into a single file.
 *   - #aggregate_callback: A function to call to aggregate the items within
 *     the groups arranged by the #group_callback function.
 *
 * @return
 *   A render array that will render to a string of JavaScript tags.
 *
 * @see drupal_get_js()
 */
function drupal_pre_render_scripts($elements) {
  $js_assets = $elements['#items'];

  // Aggregate the JavaScript if necessary, but only during normal site
  // operation.
  if (!defined('MAINTENANCE_MODE') && \Drupal::config('system.performance')->get('js.preprocess')) {
    $js_assets = \Drupal::service('asset.js.collection_optimizer')->optimize($js_assets);
  }
  return \Drupal::service('asset.js.collection_renderer')->render($js_assets);
}

/**
 * Adds attachments to a render() structure.
 *
 * Libraries, JavaScript, CSS and other types of custom structures are attached
 * to elements using the #attached property. The #attached property is an
 * associative array, where the keys are the the attachment types and the values
 * are the attached data. For example:
 *
 * @code
 * $build['#attached'] = array(
 *   'library' => array(array('taxonomy', 'taxonomy')),
 *   'css' => array(drupal_get_path('module', 'taxonomy') . '/css/taxonomy.module.css'),
 * );
 * @endcode
 *
 * 'js', 'css', and 'library' are types that get special handling. For any
 * other kind of attached data, the array key must be the full name of the
 * callback function and each value an array of arguments. For example:
 * @code
 * $build['#attached']['drupal_add_http_header'] = array(
 *   array('Content-Type', 'application/rss+xml; charset=utf-8'),
 * );
 * @endcode
 *
 * External 'js' and 'css' files can also be loaded. For example:
 * @code
 * $build['#attached']['js'] = array(
 *   'http://code.jquery.com/jquery-1.4.2.min.js' => array(
 *     'type' => 'external',
 *   ),
 * );
 * @endcode
 *
 * @param $elements
 *   The structured array describing the data being rendered.
 * @param $dependency_check
 *   When TRUE, will exit if a given library's dependencies are missing. When
 *   set to FALSE, will continue to add the libraries, even though one or more
 *   dependencies are missing. Defaults to FALSE.
 *
 * @return
 *   FALSE if there were any missing library dependencies; TRUE if all library
 *   dependencies were met.
 *
 * @see _drupal_add_library()
 * @see _drupal_add_js()
 * @see _drupal_add_css()
 * @see drupal_render()
 */
function drupal_process_attached($elements, $dependency_check = FALSE) {
  // Add defaults to the special attached structures that should be processed differently.
  $elements['#attached'] += array(
    'library' => array(),
    'js' => array(),
    'css' => array(),
  );

  // Add the libraries first.
  $success = TRUE;
  foreach ($elements['#attached']['library'] as $library) {
    if (_drupal_add_library($library) === FALSE) {
      $success = FALSE;
      // Exit if the dependency is missing.
      if ($dependency_check) {
        return $success;
      }
    }
  }
  unset($elements['#attached']['library']);

  // Add both the JavaScript and the CSS.
  // The parameters for _drupal_add_js() and _drupal_add_css() require special
  // handling.
  foreach (array('js', 'css') as $type) {
    foreach ($elements['#attached'][$type] as $data => $options) {
      // If the value is not an array, it's a filename and passed as first
      // (and only) argument.
      if (!is_array($options)) {
        $data = $options;
        $options = NULL;
      }
      // In some cases, the first parameter ($data) is an array. Arrays can't be
      // passed as keys in PHP, so we have to get $data from the value array.
      if (is_numeric($data)) {
        $data = $options['data'];
        unset($options['data']);
      }
      call_user_func('_drupal_add_' . $type, $data, $options);
    }
    unset($elements['#attached'][$type]);
  }

  // Add additional types of attachments specified in the render() structure.
  // Libraries, JavaScript and CSS have been added already, as they require
  // special handling.
  foreach ($elements['#attached'] as $callback => $options) {
    foreach ($elements['#attached'][$callback] as $args) {
      call_user_func_array($callback, $args);
    }
  }

  return $success;
}

/**
 * Adds JavaScript to change the state of an element based on another element.
 *
 * A "state" means a certain property on a DOM element, such as "visible" or
 * "checked". A state can be applied to an element, depending on the state of
 * another element on the page. In general, states depend on HTML attributes and
 * DOM element properties, which change due to user interaction.
 *
 * Since states are driven by JavaScript only, it is important to understand
 * that all states are applied on presentation only, none of the states force
 * any server-side logic, and that they will not be applied for site visitors
 * without JavaScript support. All modules implementing states have to make
 * sure that the intended logic also works without JavaScript being enabled.
 *
 * #states is an associative array in the form of:
 * @code
 * array(
 *   STATE1 => CONDITIONS_ARRAY1,
 *   STATE2 => CONDITIONS_ARRAY2,
 *   ...
 * )
 * @endcode
 * Each key is the name of a state to apply to the element, such as 'visible'.
 * Each value is a list of conditions that denote when the state should be
 * applied.
 *
 * Multiple different states may be specified to act on complex conditions:
 * @code
 * array(
 *   'visible' => CONDITIONS,
 *   'checked' => OTHER_CONDITIONS,
 * )
 * @endcode
 *
 * Every condition is a key/value pair, whose key is a jQuery selector that
 * denotes another element on the page, and whose value is an array of
 * conditions, which must bet met on that element:
 * @code
 * array(
 *   'visible' => array(
 *     JQUERY_SELECTOR => REMOTE_CONDITIONS,
 *     JQUERY_SELECTOR => REMOTE_CONDITIONS,
 *     ...
 *   ),
 * )
 * @endcode
 * All conditions must be met for the state to be applied.
 *
 * Each remote condition is a key/value pair specifying conditions on the other
 * element that need to be met to apply the state to the element:
 * @code
 * array(
 *   'visible' => array(
 *     ':input[name="remote_checkbox"]' => array('checked' => TRUE),
 *   ),
 * )
 * @endcode
 *
 * For example, to show a textfield only when a checkbox is checked:
 * @code
 * $form['toggle_me'] = array(
 *   '#type' => 'checkbox',
 *   '#title' => t('Tick this box to type'),
 * );
 * $form['settings'] = array(
 *   '#type' => 'textfield',
 *   '#states' => array(
 *     // Only show this field when the 'toggle_me' checkbox is enabled.
 *     'visible' => array(
 *       ':input[name="toggle_me"]' => array('checked' => TRUE),
 *     ),
 *   ),
 * );
 * @endcode
 *
 * The following states may be applied to an element:
 * - enabled
 * - disabled
 * - required
 * - optional
 * - visible
 * - invisible
 * - checked
 * - unchecked
 * - expanded
 * - collapsed
 *
 * The following states may be used in remote conditions:
 * - empty
 * - filled
 * - checked
 * - unchecked
 * - expanded
 * - collapsed
 * - value
 *
 * The following states exist for both elements and remote conditions, but are
 * not fully implemented and may not change anything on the element:
 * - relevant
 * - irrelevant
 * - valid
 * - invalid
 * - touched
 * - untouched
 * - readwrite
 * - readonly
 *
 * When referencing select lists and radio buttons in remote conditions, a
 * 'value' condition must be used:
 * @code
 *   '#states' => array(
 *     // Show the settings if 'bar' has been selected for 'foo'.
 *     'visible' => array(
 *       ':input[name="foo"]' => array('value' => 'bar'),
 *     ),
 *   ),
 * @endcode
 *
 * @param $elements
 *   A renderable array element having a #states property as described above.
 *
 * @see form_example_states_form()
 */
function drupal_process_states(&$elements) {
  $elements['#attached']['library'][] = 'core/drupal.states';
  // Elements of '#type' => 'item' are not actual form input elements, but we
  // still want to be able to show/hide them. Since there's no actual HTML input
  // element available, setting #attributes does not make sense, but a wrapper
  // is available, so setting #wrapper_attributes makes it work.
  $key = ($elements['#type'] == 'item') ? '#wrapper_attributes' : '#attributes';
  $elements[$key]['data-drupal-states'] = JSON::encode($elements['#states']);
}

/**
 * Adds multiple JavaScript or CSS files at the same time.
 *
 * A library defines a set of JavaScript and/or CSS files, optionally using
 * settings, and optionally requiring another library. For example, a library
 * can be a jQuery plugin, a JavaScript framework, or a CSS framework. This
 * function allows modules to load a library defined/shipped by itself or a
 * depending module, without having to add all files of the library separately.
 * Each library is only loaded once.
 *
 * @param $library_name
 *   The name of the library to add.
 * @param $every_page
 *   Set to TRUE if this library is added to every page on the site.
 *
 * @return
 *   TRUE if the library was successfully added; FALSE if the library or one of
 *   its dependencies could not be added.
 *
 * @see drupal_get_library()
 * @see hook_library_info_alter()
 */
function _drupal_add_library($library_name, $every_page = NULL) {
  $added = &drupal_static(__FUNCTION__, array());

  list($extension, $name) = explode('/', $library_name, 2);
  // Only process the library if it exists and it was not added already.
  if (!isset($added[$extension][$name])) {
    if ($library = drupal_get_library($library_name)) {
      // Allow modules and themes to dynamically attach request and context
      // specific data for this library; e.g., localization.
      \Drupal::moduleHandler()->alter('library', $library, $library_name);

      // Add all components within the library.
      $elements['#attached'] = array(
        'library' => $library['dependencies'],
        'js' => $library['js'],
        'css' => $library['css'],
      );
      foreach (array('js', 'css') as $type) {
        foreach ($elements['#attached'][$type] as $data => $options) {
          // Set the every_page flag if one was passed.
          if (isset($every_page)) {
            $elements['#attached'][$type][$data]['every_page'] = $every_page;
          }
        }
      }

      $added[$extension][$name] = drupal_process_attached($elements, TRUE);
    }
    else {
      // Requested library does not exist.
      $added[$extension][$name] = FALSE;
    }
  }

  return $added[$extension][$name];
}

/**
 * Retrieves information for a JavaScript/CSS library.
 *
 * Library information is statically cached. Libraries are keyed by module for
 * several reasons:
 * - Libraries are not unique. Multiple modules might ship with the same library
 *   in a different version or variant. This registry cannot (and does not
 *   attempt to) prevent library conflicts.
 * - Modules implementing and thereby depending on a library that is registered
 *   by another module can only rely on that module's library.
 * - Two (or more) modules can still register the same library and use it
 *   without conflicts in case the libraries are loaded on certain pages only.
 *
 * @param $library_name
 *   The name of a registered library to retrieve. By default, all
 *   libraries registered by the extension are returned.
 *
 * @return
 *   The definition of the requested library, if $name was passed and it exists,
 *   or FALSE if it does not exist. If no $name was passed, an associative array
 *   of libraries registered by the module is returned (which may be empty).
 *
 * @see _drupal_add_library()
 * @see hook_library_info_alter()
 *
 * @todo The purpose of drupal_get_*() is completely different to other page
 *   requisite API functions; find and use a different name.
 */
function drupal_get_library($library_name) {
  $libraries = &drupal_static(__FUNCTION__, array());

  $library_info = explode('/', $library_name, 2);
  $extension = $library_info[0];
  $name = isset($library_info[1]) ? $library_info[1] : NULL;
  if (!isset($libraries[$extension]) && ($cache = \Drupal::cache()->get('library:info:' . $extension))) {
    $libraries[$extension] = $cache->data;
  }

  if (!isset($libraries[$extension])) {
    $libraries[$extension] = array();
    if ($extension === 'core') {
      $path = 'core';
      $extension_type = 'core';
    }
    else {
      // @todo Add a $type argument OR automatically figure out the type based
      //   on current extension data, possibly using a module->theme fallback.
      $path = drupal_get_path('module', $extension);
      $extension_type = 'module';
      if (!$path) {
        $path = drupal_get_path('theme', $extension);
        $extension_type = 'theme';
      }
    }
    $library_file = $path . '/' . $extension . '.libraries.yml';

    if ($library_file && file_exists(DRUPAL_ROOT . '/' . $library_file)) {
      $libraries[$extension] = array();
      $parser = new Parser();
      try {
        $libraries[$extension] = $parser->parse(file_get_contents(DRUPAL_ROOT . '/' . $library_file));
      }
      catch (ParseException $e) {
        // Rethrow a more helpful exception, since ParseException lacks context.
        throw new \RuntimeException(sprintf('Invalid library definition in %s: %s', $library_file, $e->getMessage()), 0, $e);
      }
      // Allow modules to alter the module's registered libraries.
      \Drupal::moduleHandler()->alter('library_info', $libraries[$extension], $extension);
    }

    foreach ($libraries[$extension] as $id => &$library) {
      if (!isset($library['js']) && !isset($library['css']) && !isset($library['settings'])) {
        throw new \RuntimeException(sprintf("Incomplete library definition for '%s' in %s", $id, $library_file));
      }
      $library += array('dependencies' => array(), 'js' => array(), 'css' => array());

      if (isset($library['version'])) {
        // @todo Retrieve version of a non-core extension.
        if ($library['version'] === 'VERSION') {
          $library['version'] = \Drupal::VERSION;
        }
        // Remove 'v' prefix from external library versions.
        elseif ($library['version'][0] === 'v') {
          $library['version'] = substr($library['version'], 1);
        }
      }

      foreach (array('js', 'css') as $type) {
        // Prepare (flatten) the SMACSS-categorized definitions.
        // @todo After Asset(ic) changes, retain the definitions as-is and
        //   properly resolve dependencies for all (css) libraries per category,
        //   and only once prior to rendering out an HTML page.
        if ($type == 'css' && !empty($library[$type])) {
          foreach ($library[$type] as $category => $files) {
            foreach ($files as $source => $options) {
              if (!isset($options['weight'])) {
                $options['weight'] = 0;
              }
              // Apply the corresponding weight defined by CSS_* constants.
              $options['weight'] += constant('CSS_' . strtoupper($category));
              $library[$type][$source] = $options;
            }
            unset($library[$type][$category]);
          }
        }
        foreach ($library[$type] as $source => $options) {
          unset($library[$type][$source]);
          // Allow to omit the options hashmap in YAML declarations.
          if (!is_array($options)) {
            $options = array();
          }
          if ($type == 'js' && isset($options['weight']) && $options['weight'] > 0) {
            throw new \UnexpectedValueException("The $extension/$id library defines a positive weight for '$source'. Only negative weights are allowed (but should be avoided). Instead of a positive weight, specify accurate dependencies for this library.");
          }
          // Unconditionally apply default groups for the defined asset files.
          // The library system is a dependency management system. Each library
          // properly specifies its dependencies instead of relying on a custom
          // processing order.
          if ($type == 'js') {
            $options['group'] = JS_LIBRARY;
          }
          elseif ($type == 'css') {
            $options['group'] = $extension_type == 'theme' ? CSS_AGGREGATE_THEME : CSS_AGGREGATE_DEFAULT;
          }
          // By default, all library assets are files.
          if (!isset($options['type'])) {
            $options['type'] = 'file';
          }
          if ($options['type'] == 'external') {
            $options['data'] = $source;
          }
          // Determine the file asset URI.
          else {
            if ($source[0] === '/') {
              // An absolute path maps to DRUPAL_ROOT / base_path().
              if ($source[1] !== '/') {
                $options['data'] = substr($source, 1);
              }
              // A protocol-free URI (e.g., //cdn.com/example.js) is external.
              else {
                $options['type'] = 'external';
                $options['data'] = $source;
              }
            }
            // A stream wrapper URI (e.g., public://generated_js/example.js).
            elseif (file_valid_uri($source)) {
              $options['data'] = $source;
            }
            // By default, file paths are relative to the registering extension.
            else {
              $options['data'] = $path . '/' . $source;
            }
          }
          $options['version'] = $library['version'];

          $library[$type][] = $options;
        }
      }
      // @todo Introduce drupal_add_settings().
      if (isset($library['settings'])) {
        $library['js'][] = array(
          'type' => 'setting',
          'data' => $library['settings'],
        );
        unset($library['settings']);
      }
    }
    \Drupal::cache()->set('library:info:' . $extension, $libraries[$extension], Cache::PERMANENT, array(
      'extension' => array(TRUE, $extension),
      'library_info' => array(TRUE),
    ));
  }

  if (isset($name)) {
    if (!isset($libraries[$extension][$name])) {
      $libraries[$extension][$name] = FALSE;
    }
    return $libraries[$extension][$name];
  }
  return $libraries[$extension];
}

/**
 * Assists in attaching the tableDrag JavaScript behavior to a themed table.
 *
 * Draggable tables should be used wherever an outline or list of sortable items
 * needs to be arranged by an end-user. Draggable tables are very flexible and
 * can manipulate the value of form elements placed within individual columns.
 *
 * To set up a table to use drag and drop in place of weight select-lists or in
 * place of a form that contains parent relationships, the form must be themed
 * into a table. The table must have an ID attribute set. If using
 * theme_table(), the ID may be set as follows:
 * @code
 * $table = array(
 *   '#type' => 'table',
 *   '#header' => $header,
 *   '#rows' => $rows,
 *   '#attributes' => array(
 *     'id' => 'my-module-table',
 *   ),
 * );
 * return drupal_render($table);
 * @endcode
 *
 * In the theme function for the form, a special class must be added to each
 * form element within the same column, "grouping" them together.
 *
 * In a situation where a single weight column is being sorted in the table, the
 * classes could be added like this (in the theme function):
 * @code
 * $form['my_elements'][$delta]['weight']['#attributes']['class'] = array('my-elements-weight');
 * @endcode
 *
 * Each row of the table must also have a class of "draggable" in order to
 * enable the drag handles:
 * @code
 * $row = array(...);
 * $rows[] = array(
 *   'data' => $row,
 *   'class' => array('draggable'),
 * );
 * @endcode
 *
 * When tree relationships are present, the two additional classes
 * 'tabledrag-leaf' and 'tabledrag-root' can be used to refine the behavior:
 * - Rows with the 'tabledrag-leaf' class cannot have child rows.
 * - Rows with the 'tabledrag-root' class cannot be nested under a parent row.
 *
 * Calling drupal_attach_tabledrag() would then be written as such:
 * @code
 * drupal_attach_tabledrag('my-module-table', array(
 *   'action' => 'order',
 *   'relationship' => 'sibling',
 *   'group' => 'my-elements-weight',
 * );
 * @endcode
 *
 * In a more complex case where there are several groups in one column (such as
 * the block regions on the admin/structure/block page), a separate subgroup
 * class must also be added to differentiate the groups.
 * @code
 * $form['my_elements'][$region][$delta]['weight']['#attributes']['class'] = array('my-elements-weight', 'my-elements-weight-' . $region);
 * @endcode
 *
 * The 'group' option is still 'my-element-weight', and the additional
 * 'subgroup' option will be passed in as 'my-elements-weight-' . $region. This
 * also means that you'll need to call drupal_attach_tabledrag() once for every
 * region added.
 *
 * @code
 * foreach ($regions as $region) {
 *   drupal_attach_tabledrag('my-module-table', array(
 *     'action' => 'order',
 *     'relationship' => sibling',
 *     'group' => 'my-elements-weight',
 *     'subgroup' => my-elements-weight-' . $region,
 *   ));
 * }
 * @endcode
 *
 * In a situation where tree relationships are present, adding multiple
 * subgroups is not necessary, because the table will contain indentations that
 * provide enough information about the sibling and parent relationships. See
 * theme_menu_overview_form() for an example creating a table containing parent
 * relationships.
 *
 * @param $element
 *   A form element to attach the tableDrag behavior to.
 * @param array $options
 *   These options are used to generate JavaScript settings necessary to
 *   configure the tableDrag behavior appropriately for this particular table.
 *   An associative array containing the following keys:
 *   - 'table_id': String containing the target table's id attribute.
 *     If the table does not have an id, one will need to be set,
 *     such as <table id="my-module-table">.
 *   - 'action': String describing the action to be done on the form item.
 *      Either 'match' 'depth', or 'order':
 *     - 'match' is typically used for parent relationships.
 *     - 'order' is typically used to set weights on other form elements with
 *       the same group.
 *     - 'depth' updates the target element with the current indentation.
 *   - 'relationship': String describing where the "action" option
 *     should be performed. Either 'parent', 'sibling', 'group', or 'self':
 *     - 'parent' will only look for fields up the tree.
 *     - 'sibling' will look for fields in the same group in rows above and
 *       below it.
 *     - 'self' affects the dragged row itself.
 *     - 'group' affects the dragged row, plus any children below it (the entire
 *       dragged group).
 *   - 'group': A class name applied on all related form elements for this action.
 *   - 'subgroup': (optional) If the group has several subgroups within it, this
 *     string should contain the class name identifying fields in the same
 *     subgroup.
 *   - 'source': (optional) If the $action is 'match', this string should contain
 *     the classname identifying what field will be used as the source value
 *     when matching the value in $subgroup.
 *   - 'hidden': (optional) The column containing the field elements may be
 *     entirely hidden from view dynamically when the JavaScript is loaded. Set
 *     to FALSE if the column should not be hidden.
 *   - 'limit': (optional) Limit the maximum amount of parenting in this table.
 *
 * @see theme_menu_overview_form()
 */
function drupal_attach_tabledrag(&$element, array $options) {
  // Add default values to elements.
  $options = $options + array(
    'subgroup' => NULL,
    'source' => NULL,
    'hidden' => TRUE,
    'limit' => 0
  );

  $group = $options['group'];

  $tabledrag_id = &drupal_static(__FUNCTION__);
  $tabledrag_id = (!isset($tabledrag_id)) ? 0 : $tabledrag_id + 1;

  // If a subgroup or source isn't set, assume it is the same as the group.
  $target = isset($options['subgroup']) ? $options['subgroup'] : $group;
  $source = isset($options['source']) ? $options['source'] : $target;
  $settings['tableDrag'][$options['table_id']][$group][$tabledrag_id] = array(
    'target' => $target,
    'source' => $source,
    'relationship' => $options['relationship'],
    'action' => $options['action'],
    'hidden' => $options['hidden'],
    'limit' => $options['limit'],
  );

  $element['#attached']['library'][] = 'core/drupal.tabledrag';
  $element['#attached']['js'][] = array('data' => $settings, 'type' => 'setting');
}

/**
 * Deletes old cached JavaScript files and variables.
 */
function drupal_clear_js_cache() {
  \Drupal::state()->delete('system.javascript_parsed');
  \Drupal::state()->delete('system.js_cache_files');
  file_scan_directory('public://js', '/.*/', array('callback' => 'drupal_delete_file_if_stale'));
}

/**
 * Converts a PHP variable into its JavaScript equivalent.
 *
 * We use HTML-safe strings, with several characters escaped.
 *
 * @see drupal_json_decode()
 *
 * @ingroup php_wrappers
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use \Drupal\Component\Utility\Json::encode().
 */
function drupal_json_encode($var) {
  return Json::encode($var);
}

/**
 * Converts an HTML-safe JSON string into its PHP equivalent.
 *
 * @see drupal_json_encode()
 *
 * @ingroup php_wrappers
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use \Drupal\Component\Utility\Json::decode().
 */
function drupal_json_decode($var) {
  return Json::decode($var);
}

/**
 * Ensures the private key variable used to generate tokens is set.
 *
 * @return string
 *   The private key.
 *
 * @see \Drupal\Core\Access\CsrfTokenGenerator
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use \Drupal::service('private_key')->get().
 */
function drupal_get_private_key() {
  return \Drupal::service('private_key')->get();
}

/**
 * Generates a token based on $value, the user session, and the private key.
 *
 * @param string $value
 *   An additional value to base the token on.
 *
 * The generated token is based on the session ID of the current user. Normally,
 * anonymous users do not have a session, so the generated token will be
 * different on every page request. To generate a token for users without a
 * session, manually start a session prior to calling this function.
 *
 * @return string
 *   A 43-character URL-safe token for validation, based on the user session ID,
 *   the hash salt provided from drupal_get_hash_salt(), and the
 *   'drupal_private_key' configuration variable.
 *
 * @see drupal_get_hash_salt()
 * @see \Drupal\Core\Access\CsrfTokenGenerator
 * @see drupal_session_start()
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use \Drupal::csrfToken()->get().
 */
function drupal_get_token($value = '') {
  return \Drupal::csrfToken()->get($value);
}

/**
 * Validates a token based on $value, the user session, and the private key.
 *
 * @param string $token
 *   The token to be validated.
 * @param string $value
 *   An additional value to base the token on.
 * @param bool $skip_anonymous
 *   Set to true to skip token validation for anonymous users.
 *
 * @return bool
 *   True for a valid token, false for an invalid token. When $skip_anonymous
 *   is true, the return value will always be true for anonymous users.
 *
 * @see \Drupal\Core\Access\CsrfTokenGenerator
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use return \Drupal::csrfToken()->validate().
 */
function drupal_valid_token($token, $value = '', $skip_anonymous = FALSE) {
  return \Drupal::csrfToken()->validate($token, $value, $skip_anonymous);
}

/**
 * Loads code for subsystems and modules, and registers stream wrappers.
 */
function _drupal_bootstrap_code() {
  require_once __DIR__ . '/../../' . settings()->get('path_inc', 'core/includes/path.inc');
  require_once __DIR__ . '/module.inc';
  require_once __DIR__ . '/theme.inc';
  require_once __DIR__ . '/pager.inc';
  require_once __DIR__ . '/../../' . settings()->get('menu_inc', 'core/includes/menu.inc');
  require_once __DIR__ . '/tablesort.inc';
  require_once __DIR__ . '/file.inc';
  require_once __DIR__ . '/unicode.inc';
  require_once __DIR__ . '/form.inc';
  require_once __DIR__ . '/mail.inc';
  require_once __DIR__ . '/ajax.inc';
  require_once __DIR__ . '/errors.inc';
  require_once __DIR__ . '/schema.inc';
  require_once __DIR__ . '/entity.inc';

  // Load all enabled modules
  \Drupal::moduleHandler()->loadAll();

  // Make sure all stream wrappers are registered.
  file_get_stream_wrappers();
  // Ensure mt_rand() is reseeded to prevent random values from one page load
  // being exploited to predict random values in subsequent page loads.
  $seed = unpack("L", Crypt::randomBytes(4));
  mt_srand($seed[1]);

  // Set the allowed protocols once we have the config available.
  $allowed_protocols = \Drupal::config('system.filter')->get('protocols');
  if (!isset($allowed_protocols)) {
    // filter_xss_admin() is called by the installer and update.php, in which
    // case the configuration may not exist (yet). Provide a minimal default set
    // of allowed protocols for these cases.
    $allowed_protocols = array('http', 'https');
  }
  UrlHelper::setAllowedProtocols($allowed_protocols);
}

/**
 * Temporary BC function for scripts not using DrupalKernel.
 *
 * DrupalKernel skips this and replicates it via event listeners.
 *
 * @see \Drupal\Core\EventSubscriber\PathSubscriber;
 * @see \Drupal\Core\EventSubscriber\LegacyRequestSubscriber;
 */
function _drupal_bootstrap_full($skip = FALSE) {
  static $called = FALSE;

  if ($called || $skip) {
    $called = TRUE;
    return;
  }

  // Let all modules take action before the menu system handles the request.
  // We do not want this while running update.php.
  if (!defined('MAINTENANCE_MODE') || MAINTENANCE_MODE != 'update') {
    drupal_theme_initialize();
  }
}

/**
 * Stores the current page in the cache.
 *
 * If page_compression is enabled, a gzipped version of the page is stored in
 * the cache to avoid compressing the output on each request. The cache entry
 * is unzipped in the relatively rare event that the page is requested by a
 * client without gzip support.
 *
 * Page compression requires the PHP zlib extension
 * (http://php.net/manual/ref.zlib.php).
 *
 * @param $body
 *   The response body.
 * @return
 *   The cached object or NULL if the page cache was not set.
 *
 * @see drupal_page_header()
 */
function drupal_page_set_cache(Response $response, Request $request) {
  if (drupal_page_is_cacheable()) {

    // Check if the current page may be compressed.
    $page_compressed = \Drupal::config('system.performance')->get('response.gzip') && extension_loaded('zlib');

    $cache = (object) array(
      'cid' => drupal_page_cache_get_cid($request),
      'data' => array(
        'body' => $response->getContent(),
        'headers' => array(),
        // We need to store whether page was compressed or not,
        // because by the time it is read, the configuration might change.
        'page_compressed' => $page_compressed,
      ),
      'tags' => array('content' => TRUE) + drupal_cache_tags_page_get($response),
      'expire' => Cache::PERMANENT,
      'created' => REQUEST_TIME,
    );

    $cache->data['headers'] = $response->headers->all();

    // Hack: exclude the x-drupal-cache header; it may make it in here because
    // of awkwardness in how we defer sending it over in _drupal_page_get_cache.
    if (isset($cache->data['headers']['x-drupal-cache'])) {
      unset($cache->data['headers']['x-drupal-cache']);
    }

     // Use the actual timestamp from an Expires header, if available.
    if ($date = $response->getExpires()) {
      $date = DrupalDateTime::createFromDateTime($date);
      $cache->expire = $date->getTimestamp();
    }

    if ($cache->data['body']) {
      if ($page_compressed) {
        $cache->data['body'] = gzencode($cache->data['body'], 9, FORCE_GZIP);
      }
      \Drupal::cache('page')->set($cache->cid, $cache->data, $cache->expire, $cache->tags);
    }
    return $cache;
  }
}

/**
 * Sets the main page content value for later use.
 *
 * Given the nature of the Drupal page handling, this will be called once with
 * a string or array. We store that and return it later as the block is being
 * displayed.
 *
 * @param $content
 *   A string or renderable array representing the body of the page.
 *
 * @return
 *   If called without $content, a renderable array representing the body of
 *   the page.
 */
function drupal_set_page_content($content = NULL) {
  $content_block = &drupal_static(__FUNCTION__, NULL);
  $main_content_display = &drupal_static('system_main_content_added', FALSE);

  // Filter out each empty value, though allow '0' and 0, which would be
  // filtered out by empty().
  if ($content !== NULL && $content !== '') {
    $content_block = (is_array($content) ? $content : array('main' => array('#markup' => $content)));
  }
  else {
    // Indicate that the main content has been requested. We assume that
    // the module requesting the content will be adding it to the page.
    // A module can indicate that it does not handle the content by setting
    // the static variable back to FALSE after calling this function.
    $main_content_display = TRUE;
    return $content_block;
  }
}

/**
 * Pre-render callback: Renders #browsers into #prefix and #suffix.
 *
 * @param $elements
 *   A render array with a '#browsers' property. The '#browsers' property can
 *   contain any or all of the following keys:
 *   - 'IE': If FALSE, the element is not rendered by Internet Explorer. If
 *     TRUE, the element is rendered by Internet Explorer. Can also be a string
 *     containing an expression for Internet Explorer to evaluate as part of a
 *     conditional comment. For example, this can be set to 'lt IE 7' for the
 *     element to be rendered in Internet Explorer 6, but not in Internet
 *     Explorer 7 or higher. Defaults to TRUE.
 *   - '!IE': If FALSE, the element is not rendered by browsers other than
 *     Internet Explorer. If TRUE, the element is rendered by those browsers.
 *     Defaults to TRUE.
 *   Examples:
 *   - To render an element in all browsers, '#browsers' can be left out or set
 *     to array('IE' => TRUE, '!IE' => TRUE).
 *   - To render an element in Internet Explorer only, '#browsers' can be set
 *     to array('!IE' => FALSE).
 *   - To render an element in Internet Explorer 6 only, '#browsers' can be set
 *     to array('IE' => 'lt IE 7', '!IE' => FALSE).
 *   - To render an element in Internet Explorer 8 and higher and in all other
 *     browsers, '#browsers' can be set to array('IE' => 'gte IE 8').
 *
 * @return
 *   The passed-in element with markup for conditional comments potentially
 *   added to '#prefix' and '#suffix'.
 */
function drupal_pre_render_conditional_comments($elements) {
  $browsers = isset($elements['#browsers']) ? $elements['#browsers'] : array();
  $browsers += array(
    'IE' => TRUE,
    '!IE' => TRUE,
  );

  // If rendering in all browsers, no need for conditional comments.
  if ($browsers['IE'] === TRUE && $browsers['!IE']) {
    return $elements;
  }

  // Determine the conditional comment expression for Internet Explorer to
  // evaluate.
  if ($browsers['IE'] === TRUE) {
    $expression = 'IE';
  }
  elseif ($browsers['IE'] === FALSE) {
    $expression = '!IE';
  }
  else {
    $expression = $browsers['IE'];
  }

  // Wrap the element's potentially existing #prefix and #suffix properties with
  // conditional comment markup. The conditional comment expression is evaluated
  // by Internet Explorer only. To control the rendering by other browsers,
  // either the "downlevel-hidden" or "downlevel-revealed" technique must be
  // used. See http://en.wikipedia.org/wiki/Conditional_comment for details.
  $elements += array(
    '#prefix' => '',
    '#suffix' => '',
  );
  if (!$browsers['!IE']) {
    // "downlevel-hidden".
    $elements['#prefix'] = "\n<!--[if $expression]>\n" . $elements['#prefix'];
    $elements['#suffix'] .= "<![endif]-->\n";
  }
  else {
    // "downlevel-revealed".
    $elements['#prefix'] = "\n<!--[if $expression]><!-->\n" . $elements['#prefix'];
    $elements['#suffix'] .= "<!--<![endif]-->\n";
  }

  return $elements;
}

/**
 * Pre-render callback: Renders a generic HTML tag with attributes into #markup.
 *
 * @param array $element
 *   An associative array containing:
 *   - #tag: The tag name to output. Typical tags added to the HTML HEAD:
 *     - meta: To provide meta information, such as a page refresh.
 *     - link: To refer to stylesheets and other contextual information.
 *     - script: To load JavaScript.
 *   - #attributes: (optional) An array of HTML attributes to apply to the
 *     tag.
 *   - #value: (optional) A string containing tag content, such as inline
 *     CSS.
 *   - #value_prefix: (optional) A string to prepend to #value, e.g. a CDATA
 *     wrapper prefix.
 *   - #value_suffix: (optional) A string to append to #value, e.g. a CDATA
 *     wrapper suffix.
 */
function drupal_pre_render_html_tag($element) {
  $attributes = isset($element['#attributes']) ? new Attribute($element['#attributes']) : '';
  if (!isset($element['#value'])) {
    $markup = '<' . $element['#tag'] . $attributes . " />\n";
  }
  else {
    $markup = '<' . $element['#tag'] . $attributes . '>';
    if (isset($element['#value_prefix'])) {
      $markup .= $element['#value_prefix'];
    }
    $markup .= $element['#value'];
    if (isset($element['#value_suffix'])) {
      $markup .= $element['#value_suffix'];
    }
    $markup .= '</' . $element['#tag'] . ">\n";
  }
  if (!empty($element['#noscript'])) {
    $element['#markup'] = '<noscript>' . $markup . '</noscript>';
  }
  else {
    $element['#markup'] = $markup;
  }
  return $element;
}

/**
 * Pre-render callback: Renders a link into #markup.
 *
 * Doing so during pre_render gives modules a chance to alter the link parts.
 *
 * @param $elements
 *   A structured array whose keys form the arguments to l():
 *   - #title: The link text to pass as argument to l().
 *   - One of the following
 *     - #route_name and (optionally) and a #route_parameters array; The route
 *       name and route parameters which will be passed into the link generator.
 *     - #href: The system path or URL to pass as argument to l().
 *   - #options: (optional) An array of options to pass to l() or the link
 *     generator.
 *
 * @return
 *   The passed-in elements containing a rendered link in '#markup'.
 */
function drupal_pre_render_link($element) {
  // By default, link options to pass to l() are normally set in #options.
  $element += array('#options' => array());
  // However, within the scope of renderable elements, #attributes is a valid
  // way to specify attributes, too. Take them into account, but do not override
  // attributes from #options.
  if (isset($element['#attributes'])) {
    $element['#options'] += array('attributes' => array());
    $element['#options']['attributes'] += $element['#attributes'];
  }

  // This #pre_render callback can be invoked from inside or outside of a Form
  // API context, and depending on that, a HTML ID may be already set in
  // different locations. #options should have precedence over Form API's #id.
  // #attributes have been taken over into #options above already.
  if (isset($element['#options']['attributes']['id'])) {
    $element['#id'] = $element['#options']['attributes']['id'];
  }
  elseif (isset($element['#id'])) {
    $element['#options']['attributes']['id'] = $element['#id'];
  }

  // Conditionally invoke ajax_pre_render_element(), if #ajax is set.
  if (isset($element['#ajax']) && !isset($element['#ajax_processed'])) {
    // If no HTML ID was found above, automatically create one.
    if (!isset($element['#id'])) {
      $element['#id'] = $element['#options']['attributes']['id'] = drupal_html_id('ajax-link');
    }
    // If #ajax['path] was not specified, use the href as Ajax request URL.
    if (!isset($element['#ajax']['path'])) {
      $element['#ajax']['path'] = $element['#href'];
      $element['#ajax']['options'] = $element['#options'];
    }
    $element = ajax_pre_render_element($element);
  }

  if (isset($element['#route_name'])) {
    $element['#route_parameters'] = empty($element['#route_parameters']) ? array() : $element['#route_parameters'];
    $element['#markup'] = \Drupal::linkGenerator()->generate($element['#title'], $element['#route_name'], $element['#route_parameters'], $element['#options']);
  }
  else {
    $element['#markup'] = l($element['#title'], $element['#href'], $element['#options']);
  }
  return $element;
}

/**
 * Pre-render callback: Collects child links into a single array.
 *
 * This function can be added as a pre_render callback for a renderable array,
 * usually one which will be themed by links.html.twig. It iterates through all
 * unrendered children of the element, collects any #links properties it finds,
 * merges them into the parent element's #links array, and prevents those
 * children from being rendered separately.
 *
 * The purpose of this is to allow links to be logically grouped into related
 * categories, so that each child group can be rendered as its own list of
 * links if drupal_render() is called on it, but calling drupal_render() on the
 * parent element will still produce a single list containing all the remaining
 * links, regardless of what group they were in.
 *
 * A typical example comes from node links, which are stored in a renderable
 * array similar to this:
 * @code
 * $node->content['links'] = array(
 *   '#theme' => 'links__node',
 *   '#pre_render' => array('drupal_pre_render_links'),
 *   'comment' => array(
 *     '#theme' => 'links__node__comment',
 *     '#links' => array(
 *       // An array of links associated with node comments, suitable for
 *       // passing in to links.html.twig.
 *     ),
 *   ),
 *   'statistics' => array(
 *     '#theme' => 'links__node__statistics',
 *     '#links' => array(
 *       // An array of links associated with node statistics, suitable for
 *       // passing in to links.html.twig.
 *     ),
 *   ),
 *   'translation' => array(
 *     '#theme' => 'links__node__translation',
 *     '#links' => array(
 *       // An array of links associated with node translation, suitable for
 *       // passing in to links.html.twig.
 *     ),
 *   ),
 * );
 * @endcode
 *
 * In this example, the links are grouped by functionality, which can be
 * helpful to themers who want to display certain kinds of links independently.
 * For example, adding this code to node.html.twig will result in the comment
 * links being rendered as a single list:
 * @code
 * {{ content.links.comment }}
 * @endcode
 *
 * (where $node->content has been transformed into $content before handing
 * control to the node.html.twig template).
 *
 * The pre_render function defined here allows the above flexibility, but also
 * allows the following code to be used to render all remaining links into a
 * single list, regardless of their group:
 * @code
 * {{ content.links }}
 * @endcode
 *
 * In the above example, this will result in the statistics and translation
 * links being rendered together in a single list (but not the comment links,
 * which were rendered previously on their own).
 *
 * Because of the way this function works, the individual properties of each
 * group (for example, a group-specific #theme property such as
 * 'links__node__comment' in the example above, or any other property such as
 * #attributes or #pre_render that is attached to it) are only used when that
 * group is rendered on its own. When the group is rendered together with other
 * children, these child-specific properties are ignored, and only the overall
 * properties of the parent are used.
 */
function drupal_pre_render_links($element) {
  $element += array('#links' => array(), '#attached' => array());
  foreach (element_children($element) as $key) {
    $child = &$element[$key];
    // If the child has links which have not been printed yet and the user has
    // access to it, merge its links in to the parent.
    if (isset($child['#links']) && empty($child['#printed']) && (!isset($child['#access']) || $child['#access'])) {
      $element['#links'] += $child['#links'];
      // Mark the child as having been printed already (so that its links
      // cannot be mistakenly rendered twice).
      $child['#printed'] = TRUE;
    }
    // Merge attachments.
    if (isset($child['#attached'])) {
      $element['#attached'] = drupal_merge_attached($element['#attached'], $child['#attached']);
    }
  }
  return $element;
}

/**
 * Pre-render callback: Attaches the dropbutton library and required markup.
 */
function drupal_pre_render_dropbutton($element) {
  $element['#attached']['library'][] = 'core/drupal.dropbutton';
  $element['#attributes']['class'][] = 'dropbutton';
  if (!isset($element['#theme_wrappers'])) {
    $element['#theme_wrappers'] = array();
  }
  array_unshift($element['#theme_wrappers'], 'dropbutton_wrapper');

  // Enable targeted theming of specific dropbuttons (e.g., 'operations' or
  // 'operations__node').
  if (isset($element['#subtype'])) {
    $element['#theme'] .= '__' . $element['#subtype'];
  }

  return $element;
}

/**
 * Processes the page render array, enhancing it as necessary.
 *
 * @param $page
 *   A string or array representing the content of a page. The array consists of
 *   the following keys:
 *   - #type: Value is always 'page'. This pushes the theming through
 *     the page template (required).
 *   - #show_messages: Suppress drupal_get_message() items. Used by Batch
 *     API (optional).
 *
 * @return array
 *   The processed render array for the page.
 *
 * @see hook_page_alter()
 * @see element_info()
 */
function drupal_prepare_page($page) {
  $main_content_display = &drupal_static('system_main_content_added', FALSE);

  // Pull out the page title to set it back later.
  if (is_array($page) && isset($page['#title'])) {
    $title = $page['#title'];
  }

  // Allow menu callbacks to return strings or arbitrary arrays to render.
  // If the array returned is not of #type page directly, we need to fill
  // in the page with defaults.
  if (is_string($page) || (is_array($page) && (!isset($page['#type']) || ($page['#type'] != 'page')))) {
    drupal_set_page_content($page);
    $page = element_info('page');
  }

  // Modules can add elements to $page as needed in hook_page_build().
  foreach (\Drupal::moduleHandler()->getImplementations('page_build') as $module) {
    $function = $module . '_page_build';
    $function($page);
  }
  // Modules alter the $page as needed. Blocks are populated into regions like
  // 'sidebar_first', 'footer', etc.
  \Drupal::moduleHandler()->alter('page', $page);

  // The "main" and "secondary" menus are never part of the page-level render
  // array and therefore their cache tags will never bubble up into the page
  // cache, even though they should be. This happens because they're rendered
  // directly by the theme system.
  // @todo Remove this once https://drupal.org/node/1869476 lands.
  if (theme_get_setting('features.main_menu') && count(menu_main_menu())) {
    $main_links_source = _menu_get_links_source('main_links', 'main');
    $page['page_top']['#cache']['tags']['menu'][$main_links_source] = $main_links_source;
  }
  if (theme_get_setting('features.secondary_menu') && count(menu_secondary_menu())) {
    $secondary_links_source = _menu_get_links_source('secondary_links', 'account');
    $page['page_top']['#cache']['tags']['menu'][$secondary_links_source] = $secondary_links_source;
  }

  // If no module has taken care of the main content, add it to the page now.
  // This allows the site to still be usable even if no modules that
  // control page regions (for example, the Block module) are enabled.
  if (!$main_content_display) {
    $page['content']['system_main'] = drupal_set_page_content();
  }

  // Set back the previously stored title.
  if (isset($title)) {
    $page['#title'] = $title;
  }

  return $page;
}

/**
 * Renders the page, including all theming.
 *
 * @param string|array $page
 *   A string or array representing the content of a page. The array consists of
 *   the following keys:
 *   - #type: Value is always 'page'. This pushes the theming through
 *     the page template (required).
 *   - #show_messages: Suppress drupal_get_message() items. Used by Batch
 *     API (optional).
 *
 * @return string
 *   Returns the rendered string.
 *
 * @see hook_page_alter()
 * @see element_info()
 */
function drupal_render_page($page) {
  $page = drupal_prepare_page($page);
  return drupal_render($page);
}

/**
 * Renders HTML given a structured array tree.
 *
 * Renderable arrays have two kinds of key/value pairs: properties and children.
 * Properties have keys starting with '#' and their values influence how the
 * array will be rendered. Children are all elements whose keys do not start
 * with a '#'. Their values should be renderable arrays themselves, which will
 * be rendered during the rendering of the parent array. The markup provided by
 * the children is typically inserted into the markup generated by the parent
 * array.
 *
 * The process of rendering an element is recursive unless the element defines
 * an implemented theme hook in #theme. During each call to drupal_render(), the
 * outermost renderable array (also known as an "element") is processed using
 * the following steps:
 *   - If this element has already been printed (#printed = TRUE) or the user
 *     does not have access to it (#access = FALSE), then an empty string is
 *     returned.
 *   - If this element has #cache defined then the cached markup for this
 *     element will be returned if it exists in drupal_render()'s cache. To use
 *     drupal_render() caching, set the element's #cache property to an
 *     associative array with one or several of the following keys:
 *     - 'keys': An array of one or more keys that identify the element. If
 *       'keys' is set, the cache ID is created automatically from these keys.
 *       See drupal_render_cid_create().
 *     - 'granularity' (optional): Define the cache granularity using binary
 *       combinations of the cache granularity constants, e.g.
 *       DRUPAL_CACHE_PER_USER to cache for each user separately or
 *       DRUPAL_CACHE_PER_PAGE | DRUPAL_CACHE_PER_ROLE to cache separately for
 *       each page and role. If not specified the element is cached globally for
 *       each theme and language.
 *     - 'cid': Specify the cache ID directly. Either 'keys' or 'cid' is
 *       required. If 'cid' is set, 'keys' and 'granularity' are ignored. Use
 *       only if you have special requirements.
 *     - 'expire': Set to one of the cache lifetime constants.
 *     - 'bin': Specify a cache bin to cache the element in. Default is 'cache'.
 *   - If this element has #type defined and the default attributes for this
 *     element have not already been merged in (#defaults_loaded = TRUE) then
 *     the defaults for this type of element, defined in hook_element_info(),
 *     are merged into the array. #defaults_loaded is set by functions that
 *     process render arrays and call element_info() before passing the array to
 *     drupal_render(), such as form_builder() in the Form API.
 *   - If this element has an array of #pre_render functions defined, they are
 *     called sequentially to modify the element before rendering. After all the
 *     #pre_render functions have been called, #printed is checked a second time
 *     in case a #pre_render function flags the element as printed.
 *   - The child elements of this element are sorted by weight using uasort() in
 *     element_children(). Since this is expensive, when passing already sorted
 *     elements to drupal_render(), for example from a database query, set
 *     $elements['#sorted'] = TRUE to avoid sorting them a second time.
 *   - The main render phase to produce #children for this element takes place:
 *     - If this element has #theme defined and #theme is an implemented theme
 *       hook/suggestion then _theme() is called and must render both the element
 *       and its children. If #render_children is set, _theme() will not be
 *       called. #render_children is usually only set internally by _theme() so
 *       that we can avoid the situation where drupal_render() called from
 *       within a theme preprocess function creates an infinite loop.
 *     - If this element does not have a defined #theme, or the defined #theme
 *       hook is not implemented, or #render_children is set, then
 *       drupal_render() is called recursively on each of the child elements of
 *       this element, and the result of each is concatenated onto #children.
 *       This is skipped if #children is not empty at this point.
 *     - Once #children has been rendered for this element, if #theme is not
 *       implemented and #markup is set for this element, #markup will be
 *       prepended to #children.
 *   - If this element has #states defined then JavaScript state information is
 *     added to this element's #attached attribute by drupal_process_states().
 *   - If this element has #attached defined then any required libraries,
 *     JavaScript, CSS, or other custom data are added to the current page by
 *     drupal_process_attached().
 *   - If this element has an array of #theme_wrappers defined and
 *     #render_children is not set, #children is then re-rendered by passing the
 *     element in its current state to _theme() successively for each item in
 *     #theme_wrappers. Since #theme and #theme_wrappers hooks often define
 *     variables with the same names it is possible to explicitly override each
 *     attribute passed to each #theme_wrappers hook by setting the hook name as
 *     the key and an array of overrides as the value in #theme_wrappers array.
 *     For example, if we have a render element as follows:
 *     @code
 *     array(
 *       '#theme' => 'image',
 *       '#attributes' => array('class' => 'foo'),
 *       '#theme_wrappers' => array('container'),
 *     );
 *     @endcode
 *     and we need to pass the class 'bar' as an attribute for 'container', we
 *     can rewrite our element thus:
 *     @code
 *     array(
 *       '#theme' => 'image',
 *       '#attributes' => array('class' => 'foo'),
 *       '#theme_wrappers' => array(
 *         'container' => array(
 *           '#attributes' => array('class' => 'bar'),
 *         ),
 *       ),
 *     );
 *     @endcode
 *   - If this element has an array of #post_render functions defined, they are
 *     called sequentially to modify the rendered #children. Unlike #pre_render
 *     functions, #post_render functions are passed both the rendered #children
 *     attribute as a string and the element itself.
 *   - If this element has #prefix and/or #suffix defined, they are concatenated
 *     to #children.
 *   - If this element has #cache defined, the rendered output of this element
 *     is saved to drupal_render()'s internal cache. This includes the changes
 *     made by #post_render.
 *   - If this element (or any of its children) has an array of
 *     #post_render_cache functions defined, they are called sequentially to
 *     replace placeholders in the final #markup and extend #attached.
 *     Placeholders must contain a unique token, to guarantee that e.g. samples
 *     of placeholders are not replaced also. For this, a special element named
 *     'render_cache_placeholder' is provided.
 *     Note that these callbacks run always: when hitting the render cache, when
 *     missing, or when render caching is not used at all. This is done to allow
 *     any Drupal module to customize other render arrays without breaking the
 *     render cache if it is enabled, and to not require it to use other logic
 *     when render caching is disabled.
 *   - #printed is set to TRUE for this element to ensure that it is only
 *     rendered once.
 *   - The final value of #children for this element is returned as the rendered
 *     output.
 *
 * @param array $elements
 *   The structured array describing the data to be rendered.
 * @param bool $is_recursive_call
 *   Whether this is a recursive call or not, for internal use.
 *
 * @return string
 *   The rendered HTML.
 *
 * @see element_info()
 * @see _theme()
 * @see drupal_process_states()
 * @see drupal_process_attached()
 */
function drupal_render(&$elements, $is_recursive_call = FALSE) {
  // Early-return nothing if user does not have access.
  if (empty($elements) || (isset($elements['#access']) && !$elements['#access'])) {
    return '';
  }

  // Do not print elements twice.
  if (!empty($elements['#printed'])) {
    return '';
  }

  // Try to fetch the prerendered element from cache, run any #post_render_cache
  // callbacks and return the final markup.
  if (isset($elements['#cache'])) {
    $cached_element = drupal_render_cache_get($elements);
    if ($cached_element !== FALSE) {
      $elements = $cached_element;
      // Only when we're not in a recursive drupal_render() call,
      // #post_render_cache callbacks must be executed, to prevent breaking the
      // render cache in case of nested elements with #cache set.
      if (!$is_recursive_call) {
        _drupal_render_process_post_render_cache($elements);
      }
      return $elements['#markup'];
    }
  }

  // If the default values for this element have not been loaded yet, populate
  // them.
  if (isset($elements['#type']) && empty($elements['#defaults_loaded'])) {
    $elements += element_info($elements['#type']);
  }

  // Make any final changes to the element before it is rendered. This means
  // that the $element or the children can be altered or corrected before the
  // element is rendered into the final text.
  if (isset($elements['#pre_render'])) {
    foreach ($elements['#pre_render'] as $callable) {
      $elements = call_user_func($callable, $elements);
    }
  }

  // Allow #pre_render to abort rendering.
  if (!empty($elements['#printed'])) {
    return '';
  }

  // Collect all #post_render_cache callbacks associated with this element when:
  // - about to store this element in the render cache, or when;
  // - about to apply #post_render_cache callbacks.
  if (isset($elements['#cache']) || !$is_recursive_call) {
    $post_render_cache = drupal_render_collect_post_render_cache($elements);
    if ($post_render_cache) {
      $elements['#post_render_cache'] = $post_render_cache;
    }
  }

  // Add any JavaScript state information associated with the element.
  if (!empty($elements['#states'])) {
    drupal_process_states($elements);
  }

  // Add additional libraries, CSS, JavaScript and other custom
  // attached data associated with this element.
  if (!empty($elements['#attached'])) {
    drupal_process_attached($elements);
  }

  // Get the children of the element, sorted by weight.
  $children = Element::children($elements, TRUE);

  // Initialize this element's #children, unless a #pre_render callback already
  // preset #children.
  if (!isset($elements['#children'])) {
    $elements['#children'] = '';
  }

  // Assume that if #theme is set it represents an implemented hook.
  $theme_is_implemented = isset($elements['#theme']);

  // Call the element's #theme function if it is set. Then any children of the
  // element have to be rendered there. If the internal #render_children
  // property is set, do not call the #theme function to prevent infinite
  // recursion.
  if ($theme_is_implemented && !isset($elements['#render_children'])) {
    $elements['#children'] = _theme($elements['#theme'], $elements);

    // If _theme() returns FALSE this means that the hook in #theme was not
    // found in the registry and so we need to update our flag accordingly. This
    // is common for theme suggestions.
    $theme_is_implemented = ($elements['#children'] !== FALSE);
  }

  // If #theme is not implemented or #render_children is set and the element has
  // an empty #children attribute, render the children now. This is the same
  // process as drupal_render_children() but is inlined for speed.
  if ((!$theme_is_implemented || isset($elements['#render_children'])) && empty($elements['#children'])) {
    foreach ($children as $key) {
      $elements['#children'] .= drupal_render($elements[$key], TRUE);
    }
  }

  // If #theme is not implemented and the element has raw #markup as a
  // fallback, prepend the content in #markup to #children. In this case
  // #children will contain whatever is provided by #pre_render prepended to
  // what is rendered recursively above. If #theme is implemented then it is
  // the responsibility of that theme implementation to render #markup if
  // required. Eventually #theme_wrappers will expect both #markup and
  // #children to be a single string as #children.
  if (!$theme_is_implemented && isset($elements['#markup'])) {
    $elements['#children'] = $elements['#markup'] . $elements['#children'];
  }

  // Let the theme functions in #theme_wrappers add markup around the rendered
  // children.
  // #states and #attached have to be processed before #theme_wrappers, because
  // the #type 'page' render array from drupal_render_page() would render the
  // $page and wrap it into the html.html.twig template without the attached
  // assets otherwise.
  // If the internal #render_children property is set, do not call the
  // #theme_wrappers function(s) to prevent infinite recursion.
  if (isset($elements['#theme_wrappers']) && !isset($elements['#render_children'])) {
    foreach ($elements['#theme_wrappers'] as $key => $value) {
      // If the value of a #theme_wrappers item is an array then the theme hook
      // is found in the key of the item and the value contains attribute
      // overrides. Attribute overrides replace key/value pairs in $elements for
      // only this _theme() call. This allows #theme hooks and #theme_wrappers
      // hooks to share variable names without conflict or ambiguity.
      $wrapper_elements = $elements;
      if (is_string($key)) {
        $wrapper_hook = $key;
        foreach ($value as $attribute => $override) {
          $wrapper_elements[$attribute] = $override;
        }
      }
      else {
        $wrapper_hook = $value;
      }

      $elements['#children'] = _theme($wrapper_hook, $wrapper_elements);
    }
  }

  // Filter the outputted content and make any last changes before the
  // content is sent to the browser. The changes are made on $content
  // which allows the output'ed text to be filtered.
  if (isset($elements['#post_render'])) {
    foreach ($elements['#post_render'] as $callable) {
      $elements['#children'] = call_user_func($callable, $elements['#children'], $elements);
    }
  }

  // We store the resulting output in $elements['#markup'], to be consistent
  // with how render cached output gets stored. This ensures that
  // #post_render_cache callbacks get the same data to work with, no matter if
  // #cache is disabled, #cache is enabled, there is a cache hit or miss.
  $prefix = isset($elements['#prefix']) ? $elements['#prefix'] : '';
  $suffix = isset($elements['#suffix']) ? $elements['#suffix'] : '';
  $elements['#markup'] = $prefix . $elements['#children'] . $suffix;

  // Collect all cache tags. This allows the caller of drupal_render() to also
  // access the complete list of cache tags.
  if (!$is_recursive_call || isset($elements['#cache'])) {
    $elements['#cache']['tags'] = drupal_render_collect_cache_tags($elements);
  }

  // Cache the processed element if #cache is set.
  if (isset($elements['#cache'])) {
    drupal_render_cache_set($elements['#markup'], $elements);
  }

  // Only when we're not in a recursive drupal_render() call,
  // #post_render_cache callbacks must be executed, to prevent breaking the
  // render cache in case of nested elements with #cache set.
  //
  // By running them here, we ensure that:
  // - they run when #cache is disabled,
  // - they run when #cache is enabled and there is a cache miss.
  // Only the case of a cache hit when #cache is enabled, is not handled here,
  // that is handled earlier in drupal_render().
  if (!$is_recursive_call) {
    _drupal_render_process_post_render_cache($elements);
  }

  $elements['#printed'] = TRUE;
  return $elements['#markup'];
}

/**
 * Renders children of an element and concatenates them.
 *
 * @param array $element
 *   The structured array whose children shall be rendered.
 * @param array $children_keys
 *   (optional) If the keys of the element's children are already known, they
 *   can be passed in to save another run of element_children().
 *
 * @return string
 *   The rendered HTML of all children of the element.

 * @see drupal_render()
 */
function drupal_render_children(&$element, $children_keys = NULL) {
  if ($children_keys === NULL) {
    $children_keys = element_children($element);
  }
  $output = '';
  foreach ($children_keys as $key) {
    if (!empty($element[$key])) {
      $output .= drupal_render($element[$key]);
    }
  }
  return $output;
}

/**
 * Renders an element.
 *
 * This function renders an element using drupal_render(). The top level
 * element is shown with show() before rendering, so it will always be rendered
 * even if hide() had been previously used on it.
 *
 * @param $element
 *   The element to be rendered.
 *
 * @return
 *   The rendered element.
 *
 * @see drupal_render()
 * @see show()
 * @see hide()
 */
function render(&$element) {
  if (!$element && $element !== 0) {
    return NULL;
  }
  if (is_array($element)) {
    show($element);
    return drupal_render($element, TRUE);
  }
  else {
    // Safe-guard for inappropriate use of render() on flat variables: return
    // the variable as-is.
    return $element;
  }
}

/**
 * Hides an element from later rendering.
 *
 * The first time render() or drupal_render() is called on an element tree,
 * as each element in the tree is rendered, it is marked with a #printed flag
 * and the rendered children of the element are cached. Subsequent calls to
 * render() or drupal_render() will not traverse the child tree of this element
 * again: they will just use the cached children. So if you want to hide an
 * element, be sure to call hide() on the element before its parent tree is
 * rendered for the first time, as it will have no effect on subsequent
 * renderings of the parent tree.
 *
 * @param $element
 *   The element to be hidden.
 *
 * @return
 *   The element.
 *
 * @see render()
 * @see show()
 */
function hide(&$element) {
  $element['#printed'] = TRUE;
  return $element;
}

/**
 * Shows a hidden element for later rendering.
 *
 * You can also use render($element), which shows the element while rendering
 * it.
 *
 * The first time render() or drupal_render() is called on an element tree,
 * as each element in the tree is rendered, it is marked with a #printed flag
 * and the rendered children of the element are cached. Subsequent calls to
 * render() or drupal_render() will not traverse the child tree of this element
 * again: they will just use the cached children. So if you want to show an
 * element, be sure to call show() on the element before its parent tree is
 * rendered for the first time, as it will have no effect on subsequent
 * renderings of the parent tree.
 *
 * @param $element
 *   The element to be shown.
 *
 * @return
 *   The element.
 *
 * @see render()
 * @see hide()
 */
function show(&$element) {
  $element['#printed'] = FALSE;
  return $element;
}

/**
 * Gets the cached, prerendered element of a renderable element from the cache.
 *
 * @param array $elements
 *   A renderable array.
 *
 * @return array
 *   A renderable array, with the original element and all its children pre-
 *   rendered, or FALSE if no cached copy of the element is available.
 *
 * @see drupal_render()
 * @see drupal_render_cache_set()
 */
function drupal_render_cache_get(array $elements) {
  if (!\Drupal::request()->isMethodSafe() || !$cid = drupal_render_cid_create($elements)) {
    return FALSE;
  }
  $bin = isset($elements['#cache']['bin']) ? $elements['#cache']['bin'] : 'cache';

  if (!empty($cid) && $cache = \Drupal::cache($bin)->get($cid)) {
    $cached_element = $cache->data;
    // Add additional libraries, JavaScript, CSS and other data attached
    // to this element.
    if (isset($cached_element['#attached'])) {
      drupal_process_attached($cached_element);
    }
    // Return the cached element.
    return $cached_element;
  }
  return FALSE;
}

/**
 * Caches the rendered output of a renderable element.
 *
 * This is called by drupal_render() if the #cache property is set on an
 * element.
 *
 * @param $markup
 *   The rendered output string of $elements.
 * @param array $elements
 *   A renderable array.
 *
 * @see drupal_render_cache_get()
 */
function drupal_render_cache_set(&$markup, array $elements) {
  // Create the cache ID for the element.
  if (!\Drupal::request()->isMethodSafe() || !$cid = drupal_render_cid_create($elements)) {
    return FALSE;
  }

  // Cache implementations are allowed to modify the markup, to support
  // replacing markup with edge-side include commands. The supporting cache
  // backend will store the markup in some other key (like
  // $data['#real-value']) and return an include command instead. When the
  // ESI command is executed by the content accelerator, the real value can
  // be retrieved and used.
  $data['#markup'] = $markup;

  // Persist attached data associated with this element.
  $attached = drupal_render_collect_attached($elements, TRUE);
  if ($attached) {
    $data['#attached'] = $attached;
  }

  // Persist #post_render_cache callbacks associated with this element.
  if (isset($elements['#post_render_cache'])) {
    $data['#post_render_cache'] = $elements['#post_render_cache'];
  }

  // Persist cache tags associated with this element.
  if (isset($elements['#cache']['tags'])) {
    $data['#cache']['tags'] = $elements['#cache']['tags'];
  }

  $bin = isset($elements['#cache']['bin']) ? $elements['#cache']['bin'] : 'cache';
  $expire = isset($elements['#cache']['expire']) ? $elements['#cache']['expire'] : Cache::PERMANENT;
  \Drupal::cache($bin)->set($cid, $data, $expire, $elements['#cache']['tags']);
}

/**
 * Generates a render cache placeholder.
 *
 * This is used by drupal_pre_render_render_cache_placeholder() to generate
 * placeholders, but should also be called by #post_render_cache callbacks that
 * want to replace the placeholder with the final markup.
 *
 * @param callable $callback
 *   The #post_render_cache callback that will replace the placeholder with its
 *   eventual markup.
 * @param array $context
 *   An array providing context for the #post_render_cache callback.
 * @param string $token
 *   A unique token to uniquely identify the placeholder.
 *
 * @see drupal_render_cache_get()
 */
function drupal_render_cache_generate_placeholder($callback, array $context, $token) {
  // Serialize the context into a HTML attribute; unserializing is unnecessary.
  $context_attribute = '';
  foreach ($context as $key => $value) {
    $context_attribute .= $key . ':' . $value . ';';
  }
  return '<drupal:render-cache-placeholder callback="' . $callback . '" context="' . $context_attribute . '" token="' . $token . '" />';
}

/**
 * Pre-render callback: Renders a render cache placeholder into #markup.
 *
 * @param $elements
 *   A structured array whose keys form the arguments to l():
 *   - #callback: The #post_render_cache callback that will replace the
 *     placeholder with its eventual markup.
 *   - #context: An array providing context for the #post_render_cache callback.
 *
 * @return
 *   The passed-in element containing a render cache placeholder in '#markup'
 *   and a callback with context, keyed by a generated unique token in
 *   '#post_render_cache'.
 *
 * @see drupal_render_cache_generate_placeholder()
 */
function drupal_pre_render_render_cache_placeholder($element) {
  $callback = $element['#callback'];
  if (!is_callable($callback)) {
    throw new Exception(t('#callback must be a callable function.'));
  }
  $context = $element['#context'];
  if (!is_array($context)) {
    throw new Exception(t('#context must be an array.'));
  }
  $token = \Drupal\Component\Utility\Crypt::randomBytesBase64(55);

  // Generate placeholder markup and store #post_render_cache callback.
  $element['#markup'] = drupal_render_cache_generate_placeholder($callback, $context, $token);
  $element['#post_render_cache'][$callback][$token] = $context;

  return $element;
}

/**
 * Processes #post_render_cache callbacks.
 *
 * #post_render_cache callbacks may modify:
 * - #markup: to replace placeholders
 * - #attached: to add libraries or JavaScript settings
 *
 * Note that in either of these cases, #post_render_cache callbacks are
 * implicitly idempotent: a placeholder that has been replaced can't be replaced
 * again, and duplicate attachments are ignored.
 *
 * @param array &$elements
 *   The structured array describing the data being rendered.
 *
 * @see drupal_render()
 * @see drupal_render_collect_post_render_cache
 */
function _drupal_render_process_post_render_cache(array &$elements) {
  if (isset($elements['#post_render_cache'])) {
    // Call all #post_render_cache callbacks, while passing the provided context
    // and if keyed by a number, no token is passed, otherwise, the token string
    // is passed to the callback as well. This token is used to uniquely
    // identify the placeholder in the markup.
    $modified_elements = $elements;
    foreach ($elements['#post_render_cache'] as $callback => $options) {
      foreach ($elements['#post_render_cache'][$callback] as $token => $context) {
        // The advanced option, when setting #post_render_cache directly.
        if (is_numeric($token)) {
          $modified_elements = call_user_func_array($callback, array($modified_elements, $context));
        }
        // The simple option, when using the standard placeholders, and hence
        // also when using #type => render_cache_placeholder.
        else {
          // Call #post_render_cache callback to generate the element that will
          // fill in the placeholder.
          $generated_element = call_user_func_array($callback, array($context));

          // Update #attached based on the generated element.
          if (isset($generated_element['#attached'])) {
            if (!isset($modified_elements['#attached'])) {
              $modified_elements['#attached'] = array();
            }
            $modified_elements['#attached'] = drupal_merge_attached($modified_elements['#attached'], drupal_render_collect_attached($generated_element, TRUE));
          }

          // Replace the placeholder with the rendered markup of the generated
          // element.
          $placeholder = drupal_render_cache_generate_placeholder($callback, $context, $token);
          $modified_elements['#markup'] = str_replace($placeholder, drupal_render($generated_element), $modified_elements['#markup']);
        }
      }
    }
    // Only retain changes to the #markup and #attached properties, as would be
    // the case when the render cache was actually being used.
    $elements['#markup'] = $modified_elements['#markup'];
    if (isset($modified_elements['#attached'])) {
      $elements['#attached'] = $modified_elements['#attached'];
    }

    // Make sure that any attachments added in #post_render_cache callbacks are
    // also executed.
    if (isset($elements['#attached'])) {
      drupal_process_attached($elements);
    }
  }
}

/**
 * Collects #post_render_cache for an element and its children into a single
 * array.
 *
 * When caching elements, it is necessary to collect all #post_render_cache
 * callbacks into a single array, from both the element itself and all child
 * elements. This allows drupal_render() to execute all of them when the element
 * is retrieved from the render cache.
 *
 * Note: the theme system may render child elements directly (e.g. rendering a
 * node causes its template to be rendered, which causes the node links to be
 * drupal_render()ed). On top of that, the theme system transforms render arrays
 * into HTML strings. These two facts combined imply that it is impossible for
 * #post_render_cache callbacks to bubble up to the root of the render array.
 * Therefore, drupal_render_collect_post_render_cache() must be called *before*
 * #theme callbacks, so that it has a chance to examine the full render array.
 * In short: in order to examine the full render array for #post_render_cache
 * callbacks, it must use post-order tree traversal, whereas drupal_render()
 * itself uses pre-order tree traversal.
 *
 * @param array &$elements
 *   The element to collect #post_render_cache callbacks for.
 * @param array $callbacks
 *   Internal use only. The #post_render_callbacks array so far.
 * @param bool $is_root_element
 *   Internal use only. Whether the element being processed is the root or not.
 *
 * @return
 *   The #post_render_cache array for this element and its descendants.
 *
 * @see drupal_render()
 * @see _drupal_render_process_post_render_cache()
 */
function drupal_render_collect_post_render_cache(array &$elements, array $callbacks = array(), $is_root_element = TRUE) {
  // Try to fetch the prerendered element from cache, to determine
  // #post_render_cache callbacks for this element and all its children. If we
  // don't do this, then the #post_render_cache tokens will be re-generated, but
  // they would no longer match the tokens in the render cached markup, causing
  // the render cache placeholder markup to be sent to the end user!
  $retrieved_from_cache = FALSE;
  if (!$is_root_element && isset($elements['#cache'])) {
    $cached_element = drupal_render_cache_get($elements);
    if ($cached_element !== FALSE && isset($cached_element['#post_render_cache'])) {
      $elements['#post_render_cache'] = $cached_element['#post_render_cache'];
      $retrieved_from_cache = TRUE;
    }
  }

  // If this is a render cache placeholder that hasn't been rendered yet, then
  // render it now, because we must be able to collect its #post_render_cache
  // callback.
  if (!isset($elements['#post_render_cache']) && isset($elements['#type']) && $elements['#type'] === 'render_cache_placeholder') {
    $elements = drupal_pre_render_render_cache_placeholder($elements);
  }

  // Collect all #post_render_cache callbacks for this element.
  if (isset($elements['#post_render_cache'])) {
    $callbacks = NestedArray::mergeDeep($callbacks, $elements['#post_render_cache']);
  }

  // Collect the #post_render_cache callbacks for all child elements, unless
  // we've already collected them above by retrieving this element (and its
  // children) from the render cache.
  if (!$retrieved_from_cache && $children = element_children($elements)) {
    foreach ($children as $child) {
      $callbacks = drupal_render_collect_post_render_cache($elements[$child], $callbacks, FALSE);
    }
  }

  return $callbacks;
}

/**
 * Collects #attached for an element and its children into a single array.
 *
 * When caching elements, it is necessary to collect all libraries, JavaScript
 * and CSS into a single array, from both the element itself and all child
 * elements. This allows drupal_render() to add these back to the page when the
 * element is returned from cache.
 *
 * @param $elements
 *   The element to collect #attached from.
 * @param $return
 *   Whether to return the attached elements and reset the internal static.
 *
 * @return
 *   The #attached array for this element and its descendants.
 */
function drupal_render_collect_attached($elements, $return = FALSE) {
  $attached = &drupal_static(__FUNCTION__, array());

  // Collect all #attached for this element.
  if (isset($elements['#attached'])) {
    $attached = drupal_merge_attached($attached, $elements['#attached']);
  }
  if ($children = element_children($elements)) {
    foreach ($children as $child) {
      drupal_render_collect_attached($elements[$child]);
    }
  }

  // If this was the first call to the function, return all attached elements
  // and reset the static cache.
  if ($return) {
    $return = $attached;
    $attached = array();
    return $return;
  }
}

/**
 * Collects cache tags for an element and its children into a single array.
 *
 * The cache tags array is returned in a format that is valid for
 * \Drupal\Core\Cache\CacheBackendInterface::set().
 *
 * When caching elements, it is necessary to collect all cache tags into a
 * single array, from both the element itself and all child elements. This
 * allows items to be invalidated based on all tags attached to the content
 * they're constituted from.
 *
 * @param array $element
 *   The element to collect cache tags from.
 * @param array $tags
 *   (optional) An array of already collected cache tags (i.e. from a parent
 *   element). Defaults to an empty array.
 *
 * @return array
 *   The cache tags array for this element and its descendants.
 */
function drupal_render_collect_cache_tags($element, $tags = array()) {
  if (isset($element['#cache']['tags'])) {
    foreach ($element['#cache']['tags'] as $namespace => $values) {
      if (is_array($values)) {
        foreach ($values as $value) {
          $tags[$namespace][$value] = $value;
        }
      }
      else {
        if (!isset($tags[$namespace])) {
          $tags[$namespace] = $values;
        }
      }
    }
  }
  if ($children = element_children($element)) {
    foreach ($children as $child) {
      $tags = drupal_render_collect_cache_tags($element[$child], $tags);
    }
  }

  return $tags;
}

/**
 * Return the cache tags that were stored during drupal_render_page().
 *
 * @param \Symfony\Component\HttpFoundation\Response $response
 *   The response object.
 * @return array
 *   An array of cache tags.
 *
 * @see \Drupal\Core\EventSubscriber\HtmlViewSubscriber::onHtmlPage()
 */
function drupal_cache_tags_page_get(Response $response) {
  if (($tags = $response->headers->get('cache_tags')) && $tags = unserialize($tags)) {
    return $tags;
  }
  return array();
}

/**
 * Prepares an element for caching based on a query.
 *
 * This smart caching strategy saves Drupal from querying and rendering to HTML
 * when the underlying query is unchanged.
 *
 * Expensive queries should use the query builder to create the query and then
 * call this function. Executing the query and formatting results should happen
 * in a #pre_render callback.
 *
 * @param $query
 *   A select query object as returned by db_select().
 * @param $function
 *   The name of the function doing this caching. A _pre_render suffix will be
 *   added to this string and is also part of the cache key in
 *   drupal_render_cache_set() and drupal_render_cache_get().
 * @param $expire
 *   The cache expire time, passed eventually to \Drupal::cache()->set().
 * @param $granularity
 *   One or more granularity constants passed to drupal_render_cid_parts().
 *
 * @return
 *   A renderable array with the following keys and values:
 *   - #query: The passed-in $query.
 *   - #pre_render: $function with a _pre_render suffix.
 *   - #cache: An associative array prepared for drupal_render_cache_set().
 */
function drupal_render_cache_by_query($query, $function, $expire = Cache::PERMANENT, $granularity = NULL) {
  $cache_keys = array_merge(array($function), drupal_render_cid_parts($granularity));
  $query->preExecute();
  $cache_keys[] = hash('sha256', serialize(array((string) $query, $query->getArguments())));
  return array(
    '#query' => $query,
    '#pre_render' => array($function . '_pre_render'),
    '#cache' => array(
      'keys' => $cache_keys,
      'expire' => $expire,
    ),
  );
}

/**
 * Returns cache ID parts for building a cache ID.
 *
 * @param $granularity
 *   One or more cache granularity constants. For example, to cache separately
 *   for each user, use DRUPAL_CACHE_PER_USER. To cache separately for each
 *   page and role, use the expression:
 *   @code
 *   DRUPAL_CACHE_PER_PAGE | DRUPAL_CACHE_PER_ROLE
 *   @endcode
 *
 * @return
 *   An array of cache ID parts, always containing the active theme. If the
 *   locale module is enabled it also contains the active language. If
 *   $granularity was passed in, more parts are added.
 */
function drupal_render_cid_parts($granularity = NULL) {
  global $theme, $base_root;

  $cid_parts[] = $theme;

  // If we have only one language enabled we do not need it as cid part.
  $language_manager = \Drupal::languageManager();
  if ($language_manager->isMultilingual()) {
    foreach ($language_manager->getLanguageTypes() as $type) {
      $cid_parts[] = $language_manager->getCurrentLanguage($type)->id;
    }
  }

  if (!empty($granularity)) {
    // 'PER_ROLE' and 'PER_USER' are mutually exclusive. 'PER_USER' can be a
    // resource drag for sites with many users, so when a module is being
    // equivocal, we favor the less expensive 'PER_ROLE' pattern.
    if ($granularity & DRUPAL_CACHE_PER_ROLE) {
      $cid_parts[] = 'r.' . implode(',', \Drupal::currentUser()->getRoles());
    }
    elseif ($granularity & DRUPAL_CACHE_PER_USER) {
      $cid_parts[] = 'u.' . \Drupal::currentUser()->id();
    }

    if ($granularity & DRUPAL_CACHE_PER_PAGE) {
      $cid_parts[] = $base_root . request_uri();
    }
  }

  return $cid_parts;
}

/**
 * Creates the cache ID for a renderable element.
 *
 * This creates the cache ID string, either by returning the #cache['cid']
 * property if present or by building the cache ID out of the #cache['keys']
 * and, optionally, the #cache['granularity'] properties.
 *
 * @param $elements
 *   A renderable array.
 *
 * @return
 *   The cache ID string, or FALSE if the element may not be cached.
 */
function drupal_render_cid_create($elements) {
  if (isset($elements['#cache']['cid'])) {
    return $elements['#cache']['cid'];
  }
  elseif (isset($elements['#cache']['keys'])) {
    $granularity = isset($elements['#cache']['granularity']) ? $elements['#cache']['granularity'] : NULL;
    // Merge in additional cache ID parts based provided by drupal_render_cid_parts().
    $cid_parts = array_merge($elements['#cache']['keys'], drupal_render_cid_parts($granularity));
    return implode(':', $cid_parts);
  }
  return FALSE;
}

/**
 * Sorts a structured array by '#weight' property.
 *
 * Callback for uasort() within element_children().
 *
 * @param $a
 *   First item for comparison. The compared items should be associative arrays
 *   that optionally include a '#weight' key.
 * @param $b
 *   Second item for comparison.
 *
 * @return int
 *   The comparison result for uasort().
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use \Drupal\Component\Utility\SortArray::sortByWeightProperty().
 */
function element_sort($a, $b) {
  return SortArray::sortByWeightProperty($a, $b);
}

/**
 * Sorts a structured array by '#title' property.
 *
 * Callback for uasort() within:
 * - system_modules()
 * - theme_simpletest_test_table()
 *
 * @param $a
 *   First item for comparison. The compared items should be associative arrays
 *   that optionally include a '#title' key.
 * @param $b
 *   Second item for comparison.
 *
 * @return int
 *   The comparison result for uasort().
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use \Drupal\Component\Utility\SortArray::sortByTitleProperty().
 */
function element_sort_by_title($a, $b) {
  return SortArray::sortByTitleProperty($a, $b);
}

/**
 * Retrieves the default properties for the defined element type.
 *
 * @param $type
 *   An element type as defined by hook_element_info().
 */
function element_info($type) {
  // Use the advanced drupal_static() pattern, since this is called very often.
  static $drupal_static_fast;
  if (!isset($drupal_static_fast)) {
    $drupal_static_fast['cache'] = &drupal_static(__FUNCTION__);
  }
  $cache = &$drupal_static_fast['cache'];

  if (!isset($cache)) {
    $cache = \Drupal::moduleHandler()->invokeAll('element_info');
    foreach ($cache as $element_type => $info) {
      $cache[$element_type]['#type'] = $element_type;
    }
    // Allow modules to alter the element type defaults.
    \Drupal::moduleHandler()->alter('element_info', $cache);
  }

  return isset($cache[$type]) ? $cache[$type] : array();
}

/**
 * Retrieves a single property for the defined element type.
 *
 * @param $type
 *   An element type as defined by hook_element_info().
 * @param $property_name
 *   The property within the element type that should be returned.
 * @param $default
 *   (Optional) The value to return if the element type does not specify a
 *   value for the property. Defaults to NULL.
 */
function element_info_property($type, $property_name, $default = NULL) {
  return (($info = element_info($type)) && array_key_exists($property_name, $info)) ? $info[$property_name] : $default;
}

/**
 * Sorts a structured array by 'title' key (no # prefix).
 *
 * Callback for uasort() within system_admin_index().
 *
 * @param $a
 *   First item for comparison. The compared items should be associative arrays
 *   that optionally include a 'title' key.
 * @param $b
 *   Second item for comparison.
 *
 * @return int
 *   The comparison result for uasort().
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use \Drupal\Component\Utility\SortArray::sortByTitleElement().
 */
function drupal_sort_title($a, $b) {
  return SortArray::sortByTitleElement($a, $b);
}

/**
 * Checks if the key is a property.
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use \Drupal\Core\Render\Element::property().
 */
function element_property($key) {
  return Element::property($key);
}

/**
 * Gets properties of a structured array element (keys beginning with '#').
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use \Drupal\Core\Render\Element::properties().
 */
function element_properties($element) {
  return Element::properties($element);
}

/**
 * Checks if the key is a child.
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use \Drupal\Core\Render\Element::child().
 */
function element_child($key) {
  return Element::child($key);
}

/**
 * Identifies the children of an element array, optionally sorted by weight.
 *
 * The children of a element array are those key/value pairs whose key does
 * not start with a '#'. See drupal_render() for details.
 *
 * @param $elements
 *   The element array whose children are to be identified.
 * @param $sort
 *   Boolean to indicate whether the children should be sorted by weight.
 *
 * @return
 *   The array keys of the element's children.
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use \Drupal\Core\Render\Element::children().
 */
function element_children(&$elements, $sort = FALSE) {
  return Element::children($elements, $sort);
}

/**
 * Returns the visible children of an element.
 *
 * @param $elements
 *   The parent element.
 *
 * @return
 *   The array keys of the element's visible children.
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use \Drupal\Core\Render\Element::getVisibleChildren().
 */
function element_get_visible_children(array $elements) {
  return Element::getVisibleChildren($elements);
}

/**
 * Sets HTML attributes based on element properties.
 *
 * @param $element
 *   The renderable element to process.
 * @param $map
 *   An associative array whose keys are element property names and whose values
 *   are the HTML attribute names to set for corresponding the property; e.g.,
 *   array('#propertyname' => 'attributename'). If both names are identical
 *   except for the leading '#', then an attribute name value is sufficient and
 *   no property name needs to be specified.
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use \Drupal\Core\Render\Element::setAttributes().
 */
function element_set_attributes(array &$element, array $map) {
  Element::setAttributes($element, $map);
}

/**
 * Returns a list of severity levels, as defined in RFC 3164.
 *
 * @return
 *   Array of the possible severity levels for log messages.
 *
 * @see http://www.ietf.org/rfc/rfc3164.txt
 * @see watchdog()
 * @ingroup logging_severity_levels
 */
function watchdog_severity_levels() {
  return array(
    WATCHDOG_EMERGENCY   => t('Emergency'),
    WATCHDOG_ALERT   => t('Alert'),
    WATCHDOG_CRITICAL    => t('Critical'),
    WATCHDOG_ERROR     => t('Error'),
    WATCHDOG_WARNING => t('Warning'),
    WATCHDOG_NOTICE  => t('Notice'),
    WATCHDOG_INFO    => t('Info'),
    WATCHDOG_DEBUG   => t('Debug'),
  );
}


/**
 * Explodes a string of tags into an array.
 *
 * @see drupal_implode_tags()
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use \Drupal\Component\Utility\Tags::explode().
 */
function drupal_explode_tags($tags) {
  return Tags::explode($tags);
}

/**
 * Implodes an array of tags into a string.
 *
 * @see drupal_explode_tags()
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.
 *   Use \Drupal\Component\Utility\Tags::implode().
 */
function drupal_implode_tags($tags) {
  return Tags::implode($tags);
}

/**
 * Flushes all persistent caches, resets all variables, and rebuilds all data structures.
 *
 * At times, it is necessary to re-initialize the entire system to account for
 * changed or new code. This function:
 * - Clears all persistent caches:
 *   - The bootstrap cache bin containing base system, module system, and theme
 *     system information.
 *   - The common 'cache' cache bin containing arbitrary caches.
 *   - The page cache.
 *   - The URL alias path cache.
 * - Resets all static variables that have been defined via drupal_static().
 * - Clears asset (JS/CSS) file caches.
 * - Updates the system with latest information about extensions (modules and
 *   themes).
 * - Updates the bootstrap flag for modules implementing bootstrap_hooks().
 * - Rebuilds the full database schema information (invoking hook_schema()).
 * - Rebuilds data structures of all modules (invoking hook_rebuild()). In
 *   core this means
 *   - blocks, node types, date formats and actions are synchronized with the
 *     database
 *   - The 'active' status of fields is refreshed.
 * - Rebuilds the menu router.
 *
 * This means the entire system is reset so all caches and static variables are
 * effectively empty. After that is guaranteed, information about the currently
 * active code is updated, and rebuild operations are successively called in
 * order to synchronize the active system according to the current information
 * defined in code.
 *
 * All modules need to ensure that all of their caches are flushed when
 * hook_cache_flush() is invoked; any previously known information must no
 * longer exist. All following hook_rebuild() operations must be based on fresh
 * and current system data. All modules must be able to rely on this contract.
 *
 * @see \Drupal\Core\Cache\CacheHelper::getBins()
 * @see hook_cache_flush()
 * @see hook_rebuild()
 *
 * This function also resets the theme, which means it is not initialized
 * anymore and all previously added JavaScript and CSS is gone. Normally, this
 * function is called as an end-of-POST-request operation that is followed by a
 * redirect, so this effect is not visible. Since the full reset is the whole
 * point of this function, callers need to take care for backing up all needed
 * variables and properly restoring or re-initializing them on their own. For
 * convenience, this function automatically re-initializes the maintenance theme
 * if it was initialized before.
 *
 * @todo Try to clear page/JS/CSS caches last, so cached pages can still be
 *   served during this possibly long-running operation. (Conflict on bootstrap
 *   cache though.)
 * @todo Add a global lock to ensure that caches are not primed in concurrent
 *   requests.
 */
function drupal_flush_all_caches() {
  $module_handler = \Drupal::moduleHandler();
  // Flush all persistent caches.
  // This is executed based on old/previously known information, which is
  // sufficient, since new extensions cannot have any primed caches yet.
  $module_handler->invokeAll('cache_flush');
  foreach (Cache::getBins() as $service_id => $cache_backend) {
    if ($service_id != 'cache.menu') {
      $cache_backend->deleteAll();
    }
  }

  // Flush asset file caches.
  drupal_clear_css_cache();
  drupal_clear_js_cache();
  _drupal_flush_css_js();

  // Reset all static caches.
  drupal_static_reset();

  // Clear all non-drupal_static() static caches.
  \Drupal::entityManager()->clearCachedDefinitions();

  // Wipe the PHP Storage caches.
  PhpStorageFactory::get('service_container')->deleteAll();
  PhpStorageFactory::get('twig')->deleteAll();

  // Rebuild module and theme data.
  $module_data = system_rebuild_module_data();
  system_rebuild_theme_data();

  // Rebuild and reboot a new kernel. A simple DrupalKernel reboot is not
  // sufficient, since the list of enabled modules might have been adjusted
  // above due to changed code.
  $files = array();
  foreach ($module_data as $name => $extension) {
    if ($extension->status) {
      $files[$name] = $extension;
    }
  }
  \Drupal::service('kernel')->updateModules($module_handler->getModuleList(), $files);
  // New container, new module handler.
  $module_handler = \Drupal::moduleHandler();

  // Ensure that all modules that are currently supposed to be enabled are
  // actually loaded.
  $module_handler->loadAll();

  // Rebuild the schema and cache a fully-built schema based on new module data.
  // This is necessary for any invocation of index.php, because setting cache
  // table entries requires schema information and that occurs during bootstrap
  // before any modules are loaded, so if there is no cached schema,
  // drupal_get_schema() will try to generate one, but with no loaded modules,
  // it will return nothing.
  drupal_get_schema(NULL, TRUE);

  // Rebuild all information based on new module data.
  $module_handler->invokeAll('rebuild');

  // Rebuild the menu router based on all rebuilt data.
  // Important: This rebuild must happen last, so the menu router is guaranteed
  // to be based on up to date information.
  \Drupal::service('router.builder')->rebuild();

  // Re-initialize the maintenance theme, if the current request attempted to
  // use it. Unlike regular usages of this function, the installer and update
  // scripts need to flush all caches during GET requests/page building.
  if (function_exists('_drupal_maintenance_theme')) {
    unset($GLOBALS['theme']);
    drupal_maintenance_theme();
  }
}

/**
 * Changes the dummy query string added to all CSS and JavaScript files.
 *
 * Changing the dummy query string appended to CSS and JavaScript files forces
 * all browsers to reload fresh files.
 */
function _drupal_flush_css_js() {
  // The timestamp is converted to base 36 in order to make it more compact.
  Drupal::state()->set('system.css_js_query_string', base_convert(REQUEST_TIME, 10, 36));
}

/**
 * Outputs debug information.
 *
 * The debug information is passed on to trigger_error() after being converted
 * to a string using _drupal_debug_message().
 *
 * @param $data
 *   Data to be output.
 * @param $label
 *   Label to prefix the data.
 * @param $print_r
 *   Flag to switch between print_r() and var_export() for data conversion to
 *   string. Set $print_r to TRUE when dealing with a recursive data structure
 *   as var_export() will generate an error.
 */
function debug($data, $label = NULL, $print_r = FALSE) {
  // Print $data contents to string.
  $string = String::checkPlain($print_r ? print_r($data, TRUE) : var_export($data, TRUE));

  // Display values with pre-formatting to increase readability.
  $string = '<pre>' . $string . '</pre>';

  trigger_error(trim($label ? "$label: $string" : $string));
}

/**
 * Checks whether a version is compatible with a given dependency.
 *
 * @param $v
 *   A parsed dependency structure e.g. from ModuleHandler::parseDependency().
 * @param $current_version
 *   The version to check against (like 4.2).
 *
 * @return
 *   NULL if compatible, otherwise the original dependency version string that
 *   caused the incompatibility.
 *
 * @see \Drupal\Core\Extension\ModuleHandler::parseDependency()
 */
function drupal_check_incompatibility($v, $current_version) {
  if (!empty($v['versions'])) {
    foreach ($v['versions'] as $required_version) {
      if ((isset($required_version['op']) && !version_compare($current_version, $required_version['version'], $required_version['op']))) {
        return $v['original_version'];
      }
    }
  }
}

/**
 * Returns a string of supported archive extensions.
 *
 * @return
 *   A space-separated string of extensions suitable for use by the file
 *   validation system.
 */
function archiver_get_extensions() {
  $valid_extensions = array();
  foreach (\Drupal::service('plugin.manager.archiver')->getDefinitions() as $archive) {
    foreach ($archive['extensions'] as $extension) {
      foreach (explode('.', $extension) as $part) {
        if (!in_array($part, $valid_extensions)) {
          $valid_extensions[] = $part;
        }
      }
    }
  }
  return implode(' ', $valid_extensions);
}

/**
 * Creates the appropriate archiver for the specified file.
 *
 * @param $file
 *   The full path of the archive file. Note that stream wrapper paths are
 *   supported, but not remote ones.
 *
 * @return
 *   A newly created instance of the archiver class appropriate
 *   for the specified file, already bound to that file.
 *   If no appropriate archiver class was found, will return FALSE.
 */
function archiver_get_archiver($file) {
  // Archivers can only work on local paths
  $filepath = drupal_realpath($file);
  if (!is_file($filepath)) {
    throw new Exception(t('Archivers can only operate on local files: %file not supported', array('%file' => $file)));
  }
  return \Drupal::service('plugin.manager.archiver')->getInstance(array('filepath' => $filepath));
}

/**
 * Assembles the Drupal Updater registry.
 *
 * An Updater is a class that knows how to update various parts of the Drupal
 * file system, for example to update modules that have newer releases, or to
 * install a new theme.
 *
 * @return array
 *   The Drupal Updater class registry.
 *
 * @see \Drupal\Core\Updater\Updater
 * @see hook_updater_info()
 * @see hook_updater_info_alter()
 */
function drupal_get_updaters() {
  $updaters = &drupal_static(__FUNCTION__);
  if (!isset($updaters)) {
    $updaters = \Drupal::moduleHandler()->invokeAll('updater_info');
    \Drupal::moduleHandler()->alter('updater_info', $updaters);
    uasort($updaters, array('Drupal\Component\Utility\SortArray', 'sortByWeightElement'));
  }
  return $updaters;
}

/**
 * Assembles the Drupal FileTransfer registry.
 *
 * @return
 *   The Drupal FileTransfer class registry.
 *
 * @see \Drupal\Core\FileTransfer\FileTransfer
 * @see hook_filetransfer_info()
 * @see hook_filetransfer_info_alter()
 */
function drupal_get_filetransfer_info() {
  $info = &drupal_static(__FUNCTION__);
  if (!isset($info)) {
    $info = \Drupal::moduleHandler()->invokeAll('filetransfer_info');
    \Drupal::moduleHandler()->alter('filetransfer_info', $info);
    uasort($info, array('Drupal\Component\Utility\SortArray', 'sortByWeightElement'));
  }
  return $info;
}

/**
 * @defgroup queue Queue operations
 * @{
 * Queue items to allow later processing.
 *
 * The queue system allows placing items in a queue and processing them later.
 * The system tries to ensure that only one consumer can process an item.
 *
 * Before a queue can be used it needs to be created by
 * Drupal\Core\Queue\QueueInterface::createQueue().
 *
 * Items can be added to the queue by passing an arbitrary data object to
 * Drupal\Core\Queue\QueueInterface::createItem().
 *
 * To process an item, call Drupal\Core\Queue\QueueInterface::claimItem() and
 * specify how long you want to have a lease for working on that item.
 * When finished processing, the item needs to be deleted by calling
 * Drupal\Core\Queue\QueueInterface::deleteItem(). If the consumer dies, the
 * item will be made available again by the Drupal\Core\Queue\QueueInterface
 * implementation once the lease expires. Another consumer will then be able to
 * receive it when calling Drupal\Core\Queue\QueueInterface::claimItem().
 * Due to this, the processing code should be aware that an item might be handed
 * over for processing more than once.
 *
 * The $item object used by the Drupal\Core\Queue\QueueInterface can contain
 * arbitrary metadata depending on the implementation. Systems using the
 * interface should only rely on the data property which will contain the
 * information passed to Drupal\Core\Queue\QueueInterface::createItem().
 * The full queue item returned by Drupal\Core\Queue\QueueInterface::claimItem()
 * needs to be passed to Drupal\Core\Queue\QueueInterface::deleteItem() once
 * processing is completed.
 *
 * There are two kinds of queue backends available: reliable, which preserves
 * the order of messages and guarantees that every item will be executed at
 * least once. The non-reliable kind only does a best effort to preserve order
 * in messages and to execute them at least once but there is a small chance
 * that some items get lost. For example, some distributed back-ends like
 * Amazon SQS will be managing jobs for a large set of producers and consumers
 * where a strict FIFO ordering will likely not be preserved. Another example
 * would be an in-memory queue backend which might lose items if it crashes.
 * However, such a backend would be able to deal with significantly more writes
 * than a reliable queue and for many tasks this is more important. See
 * aggregator_cron() for an example of how to effectively utilize a
 * non-reliable queue. Another example is doing Twitter statistics -- the small
 * possibility of losing a few items is insignificant next to power of the
 * queue being able to keep up with writes. As described in the processing
 * section, regardless of the queue being reliable or not, the processing code
 * should be aware that an item might be handed over for processing more than
 * once (because the processing code might time out before it finishes).
 */

/**
 * @} End of "defgroup queue".
 */
