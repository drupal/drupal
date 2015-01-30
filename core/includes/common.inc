<?php

/**
 * @file
 * Common functions that many Drupal modules will need to reference.
 *
 * The functions that are critical and need to be available even when serving
 * a cached page are instead located in bootstrap.inc.
 */

use Drupal\Component\Serialization\Json;
use Drupal\Component\Serialization\Yaml;
use Drupal\Component\Serialization\Exception\InvalidDataTypeException;
use Drupal\Component\Utility\Bytes;
use Drupal\Component\Utility\Crypt;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Number;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\SortArray;
use Drupal\Component\Utility\String;
use Drupal\Component\Utility\Tags;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Asset\AttachedAssets;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Site\Settings;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\PhpStorage\PhpStorageFactory;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Routing\GeneratorNotInitializedException;
use Drupal\Core\Template\Attribute;
use Drupal\Core\Render\Element;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Session\AnonymousUserSession;

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
 *   $my_substring = Unicode::substr($original_string, 0, 5);
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
 * The delimiter used to split plural strings.
 *
 * This is the ETX (End of text) character and is used as a minimal means to
 * separate singular and plural variants in source and translation text. It
 * was found to be the most compatible delimiter for the supported databases.
 */
const LOCALE_PLURAL_DELIMITER = "\03";

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
 * @see \Drupal\Core\Render\Element\HtmlTag::preRenderHtmlTag()
 *
 * @deprecated in Drupal 8.0.x, will be removed before Drupal 8.0.0
 *   Use #attached on render arrays.
 */
function _drupal_add_html_head($data = NULL, $key = NULL) {
  $stored_head = &drupal_static(__FUNCTION__, array());

  if (isset($data) && isset($key)) {
    if (!isset($data['#type'])) {
      $data['#type'] = 'html_tag';
    }
    $stored_head[$key] = $data;
  }
  return $stored_head;
}

/**
 * Retrieves output to be displayed in the HEAD tag of the HTML page.
 *
 * @param bool $render
 *   If TRUE render the HEAD elements, otherwise return just the elements.
 *
 * @return string|array
 *   Return the rendered HTML head or the elements itself.
 *
 * @deprecated in Drupal 8.0.x, will be removed before Drupal 8.0.0
 *   Use #attached on render arrays.
 */
function drupal_get_html_head($render = TRUE) {
  $elements = _drupal_add_html_head();
  \Drupal::moduleHandler()->alter('html_head', $elements);
  if ($render) {
    return drupal_render($elements);
  }
  else {
    return $elements;
  }
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
 * @ingroup form_api
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
    $path = \Drupal::routeMatch()->getRouteName() ? Url::fromRouteMatch(\Drupal::routeMatch())->getInternalPath() : '';
    $query = UrlHelper::buildQuery(UrlHelper::filterQueryParameters($query->all()));
    if ($query != '') {
      $path .= '?' . $query;
    }
    $destination = array('destination' => $path);
  }
  return $destination;
}

/**
 * @defgroup validation Input validation
 * @{
 * Functions to validate user input.
 */

/**
 * Verifies the syntax of the given email address.
 *
 * @param string $mail
 *   A string containing an email address.
 *
 * @return bool
 *   TRUE if the address is in a valid format.
 *
 * @deprecated in Drupal 8.0.x-dev, will be removed before Drupal 9.0.0.
 *   Use \Drupal::service('email.validator')->isValid().
 */
function valid_email_address($mail) {
  return \Drupal::service('email.validator')->isValid($mail);
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
 * @} End of "defgroup sanitization".
 */

/**
 * @defgroup format Formatting
 * @{
 * Functions to format numbers, strings, dates, etc.
 */

/**
 * Formats XML elements.
 *
 * Note: It is the caller's responsibility to sanitize any input parameters.
 * This function does not perform sanitization.
 *
 * @param $array
 *   An array where each item represents an element and is either a:
 *   - (key => value) pair (<key>value</key>)
 *   - Associative array with fields:
 *     - 'key': The element name. Element names are not sanitized, so do not
 *       pass user input.
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
  // @todo This is marking the output string as safe HTML, but we have only
  //   sanitized the attributes and tag values, not the tag names, and we
  //   cannot guarantee the assembled markup is safe. Consider a fix in:
  //   https://www.drupal.org/node/2296885
  return SafeMarkup::set($output);
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
  if ($size < Bytes::KILOBYTE) {
    return \Drupal::translation()->formatPlural($size, '1 byte', '@count bytes', array(), array('langcode' => $langcode));
  }
  else {
    $size = $size / Bytes::KILOBYTE; // Convert bytes to kilobytes.
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
      if (round($size, 2) >= Bytes::KILOBYTE) {
        $size = $size / Bytes::KILOBYTE;
      }
      else {
        break;
      }
    }
    return str_replace('@size', round($size, 2), $unit);
  }
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
 *   - The name of a date type defined by a date format config entity.
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
 * @see \Drupal\Core\Datetime\DateFormatter::format()
 */
function format_date($timestamp, $type = 'medium', $format = '', $timezone = NULL, $langcode = NULL) {
  return \Drupal::service('date.formatter')->format($timestamp, $type, $format, $timezone, $langcode);
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
 * @} End of "defgroup format".
 */

/**
 * Generates an internal or external URL.
 *
 * When creating links in modules, consider whether l() could be a better
 * alternative than url().
 *
 * @see \Drupal\Core\Url::fromUri()
 * @see \Drupal\Core\Url::fromRoute()
 * @see \Drupal\Core\Url::toString()
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.0.
 *   Use \Drupal\Core\Url::fromRoute() for internal paths served by Drupal
 *   controllers or \Drupal\Core\Url::fromUri() for external paths or
 *   non-controller or sub-domain URIs such as core/install.php. Note that
 *   \Drupal\Core\Url::fromUri() expects a valid URI including the scheme. URIs
 *   from the same sub-domain that are not handled by Drupal controllers should
 *   be prepended with base://. For example:
 * @code
 * $installer_url = \Drupal\Core\Url::fromUri('base://core/install.php')->toString();
 * $external_url = \Drupal\Core\Url::fromUri('http://example.com', ['query' => ['foo' => 'bar']])->toString();
 * $internal_url = \Drupal\Core\Url::fromRoute('system.admin')->toString();
 * @endcode
 */
function _url($path = NULL, array $options = array()) {
  return \Drupal::urlGenerator()->generateFromPath($path, $options);
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
 * t('Visit the <a href="@url">settings</a> page', array('@url' => \Drupal::url('system.admin')));
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
 *     system_page_attachments().
 *   - Additional $options elements used by the url() function.
 *
 * @return string
 *   An HTML string containing a link to the given path.
 *
 * @see _url()
 * @see system_page_attachments()
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.0.
 *   Use \Drupal::l($text, $url) where $url is an instance of
 *   \Drupal\Core\Url. To build a \Drupal\Core\Url object for internal paths
 *   served by Drupal controllers use \Drupal\Core\Url::fromRoute(). For
 *   external paths or non-controller or sub-domain URIs such as
 *   core/install.php use \Drupal\Core\Url::fromUri(). Note that
 *   \Drupal\Core\Url::fromUri() expects a valid URI including the scheme. URIs
 *   from the same sub-domain that are not handled by Drupal controllers should
 *   be prepended with base://. For example:
 * @code
 * $installer_url = \Drupal\Core\Url::fromUri('base://core/install.php')->toString();
 * $installer_link = \Drupal::l($text, $installer_url);
 * $external_url = \Drupal\Core\Url::fromUri('http://example.com', ['query' => ['foo' => 'bar']])->toString();
 * $external_link = \Drupal::l($text, $external_url);
 * $internal_url = \Drupal\Core\Url::fromRoute('system.admin')->toString();
 * $internal_link = \Drupal::l($text, $internal_url);
 * @endcode
 */
function _l($text, $path, array $options = array()) {
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
    $variables['options']['attributes']['hreflang'] = $variables['options']['language']->getId();
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
      $variables['options']['attributes']['data-drupal-link-system-path'] = \Drupal::service('path.alias_manager')->getPathByAlias($path);
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
  $url = String::checkPlain(_url($variables['path'], $variables['options']));

  // Sanitize the link text if necessary.
  $text = $variables['options']['html'] ? $variables['text'] : String::checkPlain($variables['text']);
  return SafeMarkup::set('<a href="' . $url . '"' . $attributes . '>' . $text . '</a>');
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
 * If the current time limit is not unlimited it is possible to decrease the
 * total time limit if the sum of the new time limit and the current time spent
 * running the script is inferior to the original time limit. It is inherent to
 * the way set_time_limit() works, it should rather be called with an
 * appropriate value every time you need to allocate a certain amount of time
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
    $current = ini_get('max_execution_time');
    // Do not set time limit if it is currently unlimited.
    if ($current != 0) {
      @set_time_limit($time_limit);
    }
  }
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
 *
 * @deprecated in Drupal 8.0.x, will be removed before Drupal 8.0.0
 *   Use #attached on render arrays.
 */
function _drupal_add_html_head_link($attributes, $header = FALSE) {
  $element = array(
    '#tag' => 'link',
    '#attributes' => $attributes,
  );
  $href = $attributes['href'];

  if ($header) {
    // Also add a HTTP header "Link:".
    $href = '<' . String::checkPlain($attributes['href']) . '>;';
    unset($attributes['href']);
    $element['#attached']['http_header'][] = array('Link',  $href . drupal_http_header_attributes($attributes), TRUE);
  }

  _drupal_add_html_head($element, 'html_head_link:' . $attributes['rel'] . ':' . $href);
}

/**
 * Deletes old cached CSS files.
 *
 * @deprecated in Drupal 8.x, will be removed before Drupal 9.0.
 *   Use \Drupal\Core\Asset\AssetCollectionOptimizerInterface::deleteAll().
 */
function drupal_clear_css_cache() {
  \Drupal::service('asset.css.collection_optimizer')->deleteAll();
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
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.0.
 *   Use \Drupal\Component\Utility\Html::getUniqueId()
 */
function drupal_html_id($id) {
  return Html::getUniqueId($id);
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
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.0.
 *   Use \Drupal\Component\Utility\Html::getId()
 */
function drupal_clean_id_identifier($id) {
  return Html::getId($id);
}

/**
 * Constructs an array of the defaults that are used for JavaScript assets.
 *
 * @param $data
 *   (optional) The default data parameter for the JavaScript asset array.
 *
 * @see hook_js_alter()
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
 * Merges two #attached arrays.
 *
 * The values under the 'drupalSettings' key are merged in a special way, to
 * match the behavior of:
 *
 * @code
 *   jQuery.extend(true, {}, $settings_items[0], $settings_items[1], ...)
 * @endcode
 *
 * This means integer indices are preserved just like string indices are,
 * rather than re-indexed as is common in PHP array merging.
 *
 * Example:
 * @code
 * function module1_page_attachments(&$page) {
 *   $page['a']['#attached']['drupalSettings']['foo'] = ['a', 'b', 'c'];
 * }
 * function module2_page_attachments(&$page) {
 *   $page['#attached']['drupalSettings']['foo'] = ['d'];
 * }
 * // When the page is rendered after the above code, and the browser runs the
 * // resulting <SCRIPT> tags, the value of drupalSettings.foo is
 * // ['d', 'b', 'c'], not ['a', 'b', 'c', 'd'].
 * @endcode
 *
 * By following jQuery.extend() merge logic rather than common PHP array merge
 * logic, the following are ensured:
 * - Attaching JavaScript settings is idempotent: attaching the same settings
 *   twice does not change the output sent to the browser.
 * - If pieces of the page are rendered in separate PHP requests and the
 *   returned settings are merged by JavaScript, the resulting settings are the
 *   same as if rendered in one PHP request and merged by PHP.
 *
 * @param array $a
 *   An #attached array.
 * @param array $b
 *   Another #attached array.
 *
 * @return array
 *   The merged #attached array.
 *
 * @deprecated To be removed in Drupal 8.0.x. Use
 *   \Drupal\Core\Render\Renderer::mergeAttachments() instead.
 */
function drupal_merge_attached(array $a, array $b) {
  return Renderer::mergeAttachments($a, $b);
}

/**
 * Processes non-asset attachments in a render() structure.
 *
 * Libraries, JavaScript settings, feeds, HTML <head> tags and HTML <head> links
 * are attached to elements using the #attached property. The #attached property
 * is an associative array, where the keys are the attachment types and the
 * values are the attached data. For example:
 *
 * @code
 * $build['#attached'] = [
 *   'library' => ['core/jquery']
 * ];
 * $build['#attached']['http_header'] = [
 *   ['Content-Type', 'application/rss+xml; charset=utf-8'],
 * ];
 * @endcode
 *
 * The available keys are:
 * - 'library' (asset libraries)
 * - 'drupalSettings' (JavaScript settings)
 * - 'feed' (RSS feeds)
 * - 'html_head' (tags in HTML <head>)
 * - 'html_head_link' (<link> tags in HTML <head>)
 * - 'http_header' (HTTP headers)
 *
 * This function processes all non-asset attachments, to apply them to the
 * current response (that means all keys except 'library' and 'drupalSettings').
 *
 * @param array $elements
 *   The structured array describing the data being rendered.
 *
 * @see drupal_render()
 * @see \Drupal\Core\Asset\AssetResolver
 *
 * @throws LogicException
 *   When attaching something of a non-existing attachment type.
 */
function drupal_process_attached(array $elements) {
  // Asset attachments are handled by \Drupal\Core\Asset\AssetResolver.
  foreach (array('library', 'drupalSettings') as $type) {
    unset($elements['#attached'][$type]);
  }

  // Add additional types of attachments specified in the render() structure.
  foreach ($elements['#attached'] as $callback => $options) {
    foreach ($elements['#attached'][$callback] as $args) {
      // Limit the amount allowed entries.
      switch ($callback) {
        case 'html_head':
          call_user_func_array('_drupal_add_html_head', $args);
          break;
        case 'feed':
          $args = [[
            'href' => $args[0],
            'rel' => 'alternate',
            'title' => $args[1],
            'type' => 'application/rss+xml',
          ]];
          call_user_func_array('_drupal_add_html_head_link', $args);
          break;
        case 'html_head_link':
          call_user_func_array('_drupal_add_html_head_link', $args);
          break;
        case 'http_header':
          call_user_func_array('_drupal_add_http_header', $args);
          break;
        default:
          throw new \LogicException(sprintf('You are not allowed to use %s in #attached', $callback));
      }
    }
  }
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
 * Assists in attaching the tableDrag JavaScript behavior to a themed table.
 *
 * Draggable tables should be used wherever an outline or list of sortable items
 * needs to be arranged by an end-user. Draggable tables are very flexible and
 * can manipulate the value of form elements placed within individual columns.
 *
 * To set up a table to use drag and drop in place of weight select-lists or in
 * place of a form that contains parent relationships, the form must be themed
 * into a table. The table must have an ID attribute set and it
 * may be set as follows:
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
 * MenuForm::BuildOverviewForm for an example creating a table
 * containing parent relationships.
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
 * @see MenuForm::BuildOverviewForm()
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
  $element['#attached']['drupalSettings']['tableDrag'][$options['table_id']][$group][$tabledrag_id] = array(
    'target' => $target,
    'source' => $source,
    'relationship' => $options['relationship'],
    'action' => $options['action'],
    'hidden' => $options['hidden'],
    'limit' => $options['limit'],
  );

  $element['#attached']['library'][] = 'core/drupal.tabledrag';
}

/**
 * Deletes old cached JavaScript files and variables.
 *
 * @deprecated in Drupal 8.x, will be removed before Drupal 9.0.
 *   Use \Drupal\Core\Asset\AssetCollectionOptimizerInterface::deleteAll().
 */
function drupal_clear_js_cache() {
  \Drupal::service('asset.js.collection_optimizer')->deleteAll();
}

/**
 * Pre-render callback: Renders a link into #markup.
 *
 * @deprecated Use \Drupal\Core\Render\Element\Link::preRenderLink().
 */
function drupal_pre_render_link($element) {
  return Element\Link::preRenderLink($element);
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
 * $build['links'] = array(
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
 * (where a node's content has been transformed into $content before handing
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
  foreach (Element::children($element) as $key) {
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
 * Renders final HTML given a structured array tree.
 *
 * @deprecated as of Drupal 8.0.x, will be removed before Drupal 9.0.0. Use the
 *   'renderer' service instead.
 *
 * @see \Drupal\Core\Render\RendererInterface::renderRoot()
 */
function drupal_render_root(&$elements) {
  return \Drupal::service('renderer')->renderRoot($elements);
}

/**
 * Renders HTML given a structured array tree.
 *
 * @deprecated as of Drupal 8.0.x, will be removed before Drupal 9.0.0. Use the
 *   'renderer' service instead.
 *
 * @see \Drupal\Core\Render\RendererInterface::render()
 */
function drupal_render(&$elements, $is_recursive_call = FALSE) {
  return \Drupal::service('renderer')->render($elements, $is_recursive_call);
}

/**
 * Renders children of an element and concatenates them.
 *
 * @param array $element
 *   The structured array whose children shall be rendered.
 * @param array $children_keys
 *   (optional) If the keys of the element's children are already known, they
 *   can be passed in to save another run of
 *   \Drupal\Core\Render\Element::children().
 *
 * @return string
 *   The rendered HTML of all children of the element.
 * @see drupal_render()
 */
function drupal_render_children(&$element, $children_keys = NULL) {
  if ($children_keys === NULL) {
    $children_keys = Element::children($element);
  }
  $output = '';
  foreach ($children_keys as $key) {
    if (!empty($element[$key])) {
      $output .= drupal_render($element[$key]);
    }
  }
  return SafeMarkup::set($output);
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
    // Early return if this element was pre-rendered (no need to re-render).
    if (isset($element['#printed']) && $element['#printed'] == TRUE && isset($element['#markup']) && strlen($element['#markup']) > 0) {
      return $element['#markup'];
    }
    show($element);
    return drupal_render($element);
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
 * Generates a render cache placeholder.
 *
 * This is used by drupal_pre_render_render_cache_placeholder() to generate
 * placeholders, but should also be called by #post_render_cache callbacks that
 * want to replace the placeholder with the final markup.
 *
 * @param string $callback
 *   The #post_render_cache callback that will replace the placeholder with its
 *   eventual markup.
 * @param array $context
 *   An array providing context for the #post_render_cache callback. This array
 *   will be altered to provide a 'token' key/value pair, if not already
 *   provided, to uniquely identify the generated placeholder.
 *
 * @return string
 *   The generated placeholder HTML.
 *
 * @throws \Exception
 *
 * @see \Drupal\Core\Render\Renderer::getFromCache()
 */
function drupal_render_cache_generate_placeholder($callback, array &$context) {
  if (is_string($callback) && strpos($callback, '::') === FALSE) {
    /** @var \Drupal\Core\Controller\ControllerResolverInterface $controller_resolver */
    $controller_resolver = \Drupal::service('controller_resolver');
    $callable = \Drupal::service('controller_resolver')->getControllerFromDefinition($callback);
  }
  else {
   $callable = $callback;
  }

  if (!is_callable($callable)) {
    throw new Exception(t('$callable must be a callable function or of the form service_id:method.'));
  }

  // Generate a unique token if one is not already provided.
  $context += array(
    'token' => \Drupal\Component\Utility\Crypt::randomBytesBase64(55),
  );

  return '<drupal-render-cache-placeholder callback="' . $callback . '" token="' . $context['token'] . '"></drupal-render-cache-placeholder>';
}

/**
 * Retrieves the default properties for the defined element type.
 *
 * @param $type
 *   An element type as defined by hook_element_info().
 *
 * @deprecated As of Drupal 8.0, use \Drupal::service('element_info')->getInfo()
 *   instead.
 */
function element_info($type) {
  return \Drupal::service('element_info')->getInfo($type);
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
 * Flushes all persistent caches, resets all variables, and rebuilds all data structures.
 *
 * At times, it is necessary to re-initialize the entire system to account for
 * changed or new code. This function:
 * - Clears all persistent caches:
 *   - The bootstrap cache bin containing base system, module system, and theme
 *     system information.
 *   - The common 'default' cache bin containing arbitrary caches.
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
    $cache_backend->deleteAll();
  }

  // Flush asset file caches.
  \Drupal::service('asset.css.collection_optimizer')->deleteAll();
  \Drupal::service('asset.js.collection_optimizer')->deleteAll();
  _drupal_flush_css_js();

  // Reset all static caches.
  drupal_static_reset();

  // Wipe the PHP Storage caches.
  PhpStorageFactory::get('service_container')->deleteAll();
  PhpStorageFactory::get('twig')->deleteAll();

  // Rebuild module and theme data.
  $module_data = system_rebuild_module_data();
  /** @var \Drupal\Core\Extension\ThemeHandlerInterface $theme_handler */
  $theme_handler = \Drupal::service('theme_handler');
  $theme_handler->refreshInfo();
  // Remove the cache of the active theme's info file information in state.
  // @see \Drupal\Core\Theme\ThemeInitialization::getActiveByThemeName()
  $keys = [];
  foreach ($theme_handler->listInfo() as $theme_name => $theme) {
    $keys[] = 'theme.active_theme.' . $theme_name;
  }
  \Drupal::state()->deleteMultiple($keys);
  // In case the active theme gets requested later in the same request we need
  // to reset the theme manager.
  \Drupal::theme()->resetActiveTheme();


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

  // Clear all plugin caches.
  \Drupal::service('plugin.cache_clearer')->clearCachedDefinitions();

  // Rebuild the menu router based on all rebuilt data.
  // Important: This rebuild must happen last, so the menu router is guaranteed
  // to be based on up to date information.
  \Drupal::service('router.builder')->rebuild();

  // Re-initialize the maintenance theme, if the current request attempted to
  // use it. Unlike regular usages of this function, the installer and update
  // scripts need to flush all caches during GET requests/page building.
  if (function_exists('_drupal_maintenance_theme')) {
    \Drupal::theme()->resetActiveTheme();
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
