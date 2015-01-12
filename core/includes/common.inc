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
 * This uses the
 * @link http://php.net/manual/filter.filters.validate.php PHP email validation filter. @endlink
 *
 * @param $mail
 *   A string containing an email address.
 *
 * @return
 *   TRUE if the address is in a valid format.
 */
function valid_email_address($mail) {
  return (bool)filter_var($mail, FILTER_VALIDATE_EMAIL);
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
 * Formats a string containing a count of items.
 *
 * This function ensures that the string is pluralized correctly. Since t() is
 * called by this function, make sure not to pass already-localized strings to
 * it.
 *
 * For example:
 * @code
 *   $output = \Drupal::translation()->formatPlural($node->comment_count, '1 comment', '@count comments');
 * @endcode
 *
 * Example with additional replacements:
 * @code
 *   $output = \Drupal::translation()->formatPlural($update_count,
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
 * @see \Drupal\Component\Datetime\DateFormatter::format()
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
 * Adds a cascading stylesheet to the stylesheet queue.
 *
 * Calling drupal_static_reset('_drupal_add_css') will clear all cascading
 * stylesheets added so far.
 *
 * If CSS aggregation/compression is enabled, all cascading style sheets added
 * with $options['preprocess'] set to TRUE will be merged into one aggregate
 * file and compressed by removing all extraneous white space.
 * Externally hosted stylesheets are never aggregated or compressed.
 *
 * The reason for aggregating the files is outlined quite thoroughly here:
 * http://www.die.net/musings/page_load_time/ "Load fewer external objects. Due
 * to request overhead, one bigger file just loads faster than two smaller ones
 * half its size."
 *
 * $options['preprocess'] should be only set to TRUE when a file is required for
 * all typical visitors and most pages of a site. It is critical that all
 * preprocessed files are added unconditionally on every page, even if the
 * files do not happen to be needed on a page.
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
 *   - 'external': The absolute path to an external CSS file that is not hosted
 *     on the local server. These files will not be aggregated if CSS
 *     aggregation is enabled.
 * @param $options
 *   (optional) A string defining the 'type' of CSS that is being added in the
 *   $data parameter ('file' or 'external'), or an array which can have any or
 *   all of the following keys:
 *   - 'type': The type of stylesheet being added. Available options are 'file'
 *     or 'external'. Defaults to 'file'.
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
 *     so CSS within higher aggregate groups can take precedence over CSS
 *     within lower aggregate groups.
 *   - 'every_page': For optimal front-end performance when aggregation is
 *     enabled, this should be set to TRUE if the stylesheet is present on every
 *     page of the website for users for whom it is present at all. This
 *     defaults to FALSE. It is set to TRUE for stylesheets added via module and
 *     theme .info.yml files. Modules that add stylesheets within
 *     hook_page_attachments() implementations, or from other code that ensures
 *     that the stylesheet is added to all website pages, should also set this flag
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
 *     or it will negatively impact front-end performance. Instead add a @media
 *     block to the included CSS file.
 *   - 'preprocess': If TRUE and CSS aggregation/compression is enabled, the
 *     styles will be aggregated and compressed. Defaults to TRUE.
 *   - 'browsers': An array containing information specifying which browsers
 *     should load the CSS item. See
 *     \Drupal\Core\Render\Element\HtmlTag::preRenderConditionalComments() for
 *     details.
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
function drupal_get_css($css = NULL, $skip_alter = FALSE, $theme_add_css = TRUE) {
  $theme_info = \Drupal::theme()->getActiveTheme();

  if (!isset($css)) {
    $css = _drupal_add_css();
  }

  // Allow modules and themes to alter the CSS items.
  if (!$skip_alter) {
    \Drupal::moduleHandler()->alter('css', $css);
    \Drupal::theme()->alter('css', $css);
  }

  // Sort CSS items, so that they appear in the correct order.
  uasort($css, 'drupal_sort_css_js');

  // Allow themes to remove CSS files by basename.
  if ($stylesheet_remove = $theme_info->getStyleSheetsRemove()) {
    foreach ($css as $key => $options) {
      if (isset($options['basename']) && isset($stylesheet_remove[$options['basename']])) {
        unset($css[$key]);
      }
    }
  }
  // Allow themes to conditionally override CSS files by basename.
  if ($stylesheet_override = $theme_info->getStyleSheetsOverride()) {
    foreach ($css as $key => $options) {
      if (isset($options['basename']) && isset($stylesheet_override[$options['basename']])) {
        $css[$key]['data'] = $stylesheet_override[$options['basename']];
      }
    }
  }

  // Render the HTML needed to load the CSS.
  $styles = array(
    '#type' => 'styles',
    '#items' => $css,
  );

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
 * Adds a JavaScript file or setting to the page.
 *
 * The behavior of this function depends on the parameters it is called with.
 * Generally, it handles the addition of JavaScript to the page. The following
 * actions can be performed using this function:
 * - Add a file ('file'): Adds a reference to a JavaScript file to the page.
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
 *   _drupal_add_js('http://example.com/example.js', 'external');
 *   _drupal_add_js(array('myModule' => array('key' => 'value')), 'setting');
 * @endcode
 *
 * Calling drupal_static_reset('_drupal_add_js') will clear all JavaScript added
 * so far.
 *
 * If JavaScript aggregation is enabled, all JavaScript files added with
 * $options['preprocess'] set to TRUE will be merged into one aggregate file.
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
 * files are not needed on a page.
 *
 * Non-preprocessed files should only be added to the page when they are
 * actually needed.
 *
 * @param $data
 *   (optional) If given, the value depends on the $options parameter, or
 *   $options['type'] if $options is passed as an associative array:
 *   - 'file': Path to the file relative to base_path().
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
 *   the $data parameter ('file'/'setting'/'external'), or an associative array.
 *   JavaScript settings should always pass the string 'setting' only. Other
 *   types can have the following elements in the array:
 *   - type: The type of JavaScript that is to be added to the page. Allowed
 *     values are 'file', 'external' or 'setting'. Defaults
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
 *     hook_page_attachments() implementations, or from other code that ensures
 *     that the JavaScript is added to all website pages, should also set this
 *     flag to TRUE. All JavaScript files within the same group and that have the
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
 *     \Drupal\Core\Render\Element\HtmlTag::preRenderConditionalComments() for
 *     details.
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
        if (!isset($javascript['drupalSettings'])) {
          $javascript['drupalSettings'] = array(
            'type' => 'setting',
            'scope' => 'header',
            'group' => JS_SETTING,
            'every_page' => TRUE,
            'weight' => 0,
            'browsers' => array(),
            'data' => array(),
          );
        }
        $javascript['drupalSettings']['data'] = NestedArray::mergeDeepArray([$javascript['drupalSettings']['data'], $data], TRUE);
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
 * are added to the page. Then, all settings are output. If running update.php,
 * all preprocessing is disabled.
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
function drupal_get_js($scope = 'header', $javascript = NULL, $skip_alter = FALSE, $is_ajax = FALSE, $theme_add_js = TRUE) {
  if (!isset($javascript)) {
    $javascript = _drupal_add_js();
  }
  if (empty($javascript)) {
    return '';
  }

  // Allow modules to alter the JavaScript.
  if (!$skip_alter) {
    \Drupal::moduleHandler()->alter('js', $javascript);
    \Drupal::theme()->alter('js', $javascript);
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
    if (!empty($items['drupalSettings']) || $is_ajax) {
      $theme_key = \Drupal::theme()->getActiveTheme()->getName();
      // Provide the page with information about the theme that's used, so that
      // a later AJAX request can be rendered using the same theme.
      // @see \Drupal\Core\Theme\AjaxBasePageNegotiator
      $ajaxPageState['theme'] = $theme_key;
      // Checks that the DB is available before filling theme_token.
      if (!defined('MAINTENANCE_MODE')) {
        $ajaxPageState['theme_token'] = \Drupal::csrfToken()->get($theme_key);
      }

      // Provide the page with information about the individual JavaScript files
      // used, information not otherwise available when aggregation is enabled.
      $ajaxPageState['js'] = array_fill_keys(array_keys($javascript), 1);
      unset($ajaxPageState['js']['drupalSettings']);

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
        $ajaxPageState['css'] = (object) array_fill_keys(array_keys($css), 1);
      }

      _drupal_add_js(['ajaxPageState' => $ajaxPageState], 'setting');

      // If we're outputting the header scope, then this might be the final time
      // that drupal_get_js() is running, so add the settings to this output as well
      // as to the _drupal_add_js() cache. If $items['drupalSettings'] doesn't
      // exist, it's because drupal_get_js() was intentionally passed a
      // $javascript argument stripped of settings, potentially in order to
      // override how settings get output, so in this case, do not add the
      // setting to this output.
      if ($scope == 'header' && isset($items['drupalSettings'])) {
        $items['drupalSettings']['data']['ajaxPageState'] = $ajaxPageState;
      }
    }
  }

  // Process the 'drupalSettings' JavaScript asset, if any.
  if (!empty($items['drupalSettings'])) {
    $settings = $items['drupalSettings']['data'];

    // Allow modules and themes to alter the JavaScript settings.
    \Drupal::moduleHandler()->alter('js_settings', $settings);
    \Drupal::theme()->alter('js_settings', $settings);

    $items['drupalSettings']['data'] = $settings;
  }

  // Render the HTML needed to load the JavaScript.
  $elements = array(
    '#type' => 'scripts',
    '#items' => $items,
  );

  return drupal_render($elements);
}

/**
 * Merges two #attached arrays.
 *
 * The values under the 'drupalSettings' key are merged in a special way, to
 * match the behavior of
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
 */
function drupal_merge_attached(array $a, array $b) {
  // If both #attached arrays contain drupalSettings, then merge them correctly;
  // adding the same settings multiple times needs to behave idempotently.
  if (!empty($a['drupalSettings']) && !empty($b['drupalSettings'])) {
    $a['drupalSettings'] = NestedArray::mergeDeepArray([$a['drupalSettings'], $b['drupalSettings']], TRUE);
    unset($b['drupalSettings']);
  }
  return NestedArray::mergeDeep($a, $b);
}

/**
 * Adds attachments to a render() structure.
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
 * For example:
 * @code
 * $build['#attached']['http_header'] = array(
 *   array('Content-Type', 'application/rss+xml; charset=utf-8'),
 * );
 * @endcode
 *
 * @param array $elements
 *   The structured array describing the data being rendered.
 * @param bool $dependency_check
 *   When TRUE, will exit if a given library's dependencies are missing. When
 *   set to FALSE, will continue to add the libraries, even though one or more
 *   dependencies are missing. Defaults to FALSE.
 *
 * @return bool
 *   FALSE if there were any missing library dependencies; TRUE if all library
 *   dependencies were met.
 *
 * @see _drupal_add_library()
 * @see _drupal_add_js()
 * @see _drupal_add_css()
 * @see drupal_render()
 */
function drupal_process_attached(array $elements, $dependency_check = FALSE) {
  // Add defaults to the special attached structures that should be processed differently.
  $elements['#attached'] += array(
    'library' => array(),
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

  // Convert every JavaScript settings asset into a regular JavaScript asset.
  // @todo Clean this up in https://www.drupal.org/node/2368797
  if (!empty($elements['#attached']['drupalSettings'])) {
    _drupal_add_js($elements['#attached']['drupalSettings'], ['type' => 'setting']);
    unset($elements['#attached']['drupalSettings']);
  }

  // Add additional types of attachments specified in the render() structure.
  // Libraries, JavaScript and CSS have been added already, as they require
  // special handling.
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
 * @see \Drupal\Core\Asset\LibraryDiscovery
 * @see hook_library_info_alter()
 *
 * @deprecated in Drupal 8.0.x, will be removed before Drupal 8.0.0
 *   Use #attached on render arrays.
 */
function _drupal_add_library($library_name, $every_page = NULL) {
  $added = &drupal_static(__FUNCTION__, array());
  /** @var \Drupal\Core\Asset\LibraryDiscoveryInterface $library_discovery */
  $library_discovery = \Drupal::service('library.discovery');

  list($extension, $name) = explode('/', $library_name, 2);
  // Only process the library if it exists and it was not added already.
  if (!isset($added[$extension][$name])) {
    if ($library = $library_discovery->getLibraryByName($extension, $name)) {
      // Add all components within the library.
      $elements['#attached'] = array(
        'library' => $library['dependencies'],
      );
      if (isset($library['drupalSettings'])) {
        $elements['#attached']['drupalSettings'] = $library['drupalSettings'];
      }
      $added[$extension][$name] = drupal_process_attached($elements, TRUE);

      // Add both the JavaScript and the CSS.
      // The parameters for _drupal_add_js() and _drupal_add_css() require special
      // handling.
      foreach (array('js', 'css') as $type) {
        foreach ($library[$type] as $options) {
          call_user_func('_drupal_add_' . $type, $options['data'], $options);
        }
        unset($elements['#attached'][$type]);
      }
    }
    else {
      // Requested library does not exist.
      $added[$extension][$name] = FALSE;
    }
  }

  return $added[$extension][$name];
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
 * @param \Symfony\Component\HttpFoundation\Response $response
 *   The fully populated response.
 * @param \Symfony\Component\HttpFoundation\Request $request
 *   The request for this page.
 */
function drupal_page_set_cache(Response $response, Request $request) {
  // Check if the current page may be compressed.
  if (extension_loaded('zlib') && !$response->headers->get('Content-Encoding') &&
      \Drupal::config('system.performance')->get('response.gzip')) {

    $content = $response->getContent();
    if ($content) {
      $response->setContent(gzencode($content, 9, FORCE_GZIP));
      $response->headers->set('Content-Encoding', 'gzip');
    }

    // When page compression is enabled, ensure that proxy caches will record
    // and deliver different versions of a page depending on whether the
    // client supports gzip or not.
    $response->setVary('Accept-Encoding', FALSE);
  }

  // Use the actual timestamp from an Expires header, if available.
  $date = $response->getExpires();
  $expire = ($date > (new DateTime())) ? $date->getTimestamp() : Cache::PERMANENT;

  $cid = drupal_page_cache_get_cid($request);
  $tags = explode(' ', $response->headers->get('X-Drupal-Cache-Tags'));
  \Drupal::cache('render')->set($cid, $response, $expire, $tags);
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
  $bin = isset($elements['#cache']['bin']) ? $elements['#cache']['bin'] : 'render';

  if (!empty($cid) && $cache = \Drupal::cache($bin)->get($cid)) {
    $cached_element = $cache->data;
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
  $data['#attached'] = $elements['#attached'];

  // Persist #post_render_cache callbacks associated with this element.
  if (isset($elements['#post_render_cache'])) {
    $data['#post_render_cache'] = $elements['#post_render_cache'];
  }

  // Persist cache tags associated with this element. Also associate the
  // "rendered" cache tag. This allows us to invalidate the entire render cache,
  // regardless of the cache bin.
  $data['#cache']['tags'] = $elements['#cache']['tags'];
  $data['#cache']['tags'][] = 'rendered';

  $bin = isset($elements['#cache']['bin']) ? $elements['#cache']['bin'] : 'render';
  $expire = isset($elements['#cache']['expire']) ? $elements['#cache']['expire'] : Cache::PERMANENT;
  \Drupal::cache($bin)->set($cid, $data, $expire, $data['#cache']['tags']);
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
 * @see drupal_render_cache_get()
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
 * Creates the cache ID for a renderable element.
 *
 * This creates the cache ID string, either by returning the #cache['cid']
 * property if present or by building the cache ID out of the #cache['keys'].
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
    // Cache keys may either be static (just strings) or tokens (placeholders
    // that are converted to static keys by the @cache_contexts service,
    // depending on the request).
    $cache_contexts = \Drupal::service("cache_contexts");
    $keys = $cache_contexts->convertTokensToKeys($elements['#cache']['keys']);
    return implode(':', $keys);
  }
  return FALSE;
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
