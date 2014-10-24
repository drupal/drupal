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
use Drupal\Core\Render\RenderStackFrame;
use Drupal\Core\Site\Settings;
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
 * The delimiter used to split plural strings.
 *
 * This is the ETX (End of text) character and is used as a minimal means to
 * separate singular and plural variants in source and translation text. It
 * was found to be the most compatible delimiter for the supported databases.
 */
const LOCALE_PLURAL_DELIMITER = "\03";

/**
 * Gets the name of the currently active installation profile.
 *
 * When this function is called during Drupal's initial installation process,
 * the name of the profile that's about to be installed is stored in the global
 * installation state. At all other times, the "install_profile" setting will be
 * available in settings.php.
 *
 * @return string|null $profile
 *   The name of the installation profile or NULL if no installation profile is
 *   currently active. This is the case for example during the first steps of
 *   the installer or during unit tests.
 */
function drupal_get_profile() {
  global $install_state;

  if (drupal_installation_attempted()) {
    // If the profile has been selected return it.
    if (isset($install_state['parameters']['profile'])) {
      $profile = $install_state['parameters']['profile'];
    }
    else {
      $profile = NULL;
    }
  }
  else {
    // Fall back to NULL, if there is no 'install_profile' setting.
    $profile = Settings::get('install_profile');
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
 * @see current_path()
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
    return format_plural($size, '1 byte', '@count bytes', array(), array('langcode' => $langcode));
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
 * Returns the path to a system item (module, theme, etc.).
 *
 * @param $type
 *   The type of the item; one of 'core', 'profile', 'module', 'theme', or
 *   'theme_engine'.
 * @param $name
 *   The name of the item for which the path is requested. Ignored for
 *   $type 'core'.
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
 * Deletes old cached CSS files.
 *
 * @deprecated in Drupal 8.x, will be removed before Drupal 9.0.
 *   Use \Drupal\Core\Asset\AssetCollectionOptimizerInterface::deleteAll().
 */
function drupal_clear_css_cache() {
  \Drupal::service('asset.css.collection_optimizer')->deleteAll();
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
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.0.
 *   Use \Drupal\Component\Utility\Html::cleanCssIdentifier()
 */
function drupal_clean_css_identifier($identifier, $filter = array(' ' => '-', '_' => '-', '__' => '__', '/' => '-', '[' => '-', ']' => '')) {
  return Html::cleanCssIdentifier($identifier, $filter);
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
 *
 * @deprecated in Drupal 8.x-dev, will be removed before Drupal 8.0.0.
 *   Use \Drupal\Component\Utility\Html::getClass()
 */
function drupal_html_class($class) {
  return Html::getClass($class);
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
 * files are not needed on a page.
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
          $current_query = \Drupal::service('request_stack')->getCurrentRequest()->query->all();
          _url('', array('script' => &$scriptPath, 'prefix' => &$pathPrefix));
          $current_path = current_path();
          $current_path_is_admin = FALSE;
          // The function path_is_admin() is not available on update.php pages.
          if (!(defined('MAINTENANCE_MODE'))) {
            $current_path_is_admin = \Drupal::service('router.admin_context')->isAdminRoute();
          }
          $path = array(
            'basePath' => base_path(),
            'scriptPath' => $scriptPath,
            'pathPrefix' => $pathPrefix,
            'currentPath' => $current_path,
            'currentPathIsAdmin' => $current_path_is_admin,
            'isFront' => drupal_is_front_page(),
            'currentLanguage' => \Drupal::languageManager()->getCurrentLanguage(LanguageInterface::TYPE_URL)->getId(),
          );
          if (!empty($current_query)) {
            ksort($current_query);
            $path['currentQuery'] = (object) $current_query;
          }
          $javascript['settings']['data'][] = array(
            'path' => $path,
            'locale' => array(
              'pluralDelimiter' => LOCALE_PLURAL_DELIMITER,
            ),
          );
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
    if (!empty($items['settings']) || $is_ajax) {
      $theme_key = \Drupal::theme()->getActiveTheme()->getName();
      // Provide the page with information about the theme that's used, so that
      // a later AJAX request can be rendered using the same theme.
      // @see \Drupal\Core\Theme\AjaxBasePageNegotiator
      $setting['ajaxPageState']['theme'] = $theme_key;
      // Checks that the DB is available before filling theme_token.
      if (!defined('MAINTENANCE_MODE')) {
        $setting['ajaxPageState']['theme_token'] = \Drupal::csrfToken()->get($theme_key);
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
 * function module1_page_attachments(&$page) {
 *   $page['#attached']['js'][] = array(
 *     'type' => 'setting',
 *     'data' => array('foo' => array('a', 'b', 'c')),
 *   );
 * }
 * function module2_page_attachments(&$page) {
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
 * $build['#attached']['http_header'] = array(
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
  if (\Drupal::config('system.performance')->get('response.gzip') &&
    !$response->headers->get('Content-Encoding') && extension_loaded('zlib')) {

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
 * @see hook_page_attachments()
 * @see hook_page_attachments_alter()
 * @see hook_page_top()
 * @see hook_page_bottom()
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

  // Modules can add attachments.
  $attachments = [];
  foreach (\Drupal::moduleHandler()->getImplementations('page_attachments') as $module) {
    $function = $module . '_page_attachments';
    $function($attachments);
  }
  if (array_diff(array_keys($attachments), ['#attached', '#post_render_cache']) !== []) {
    throw new \LogicException('Only #attached and #post_render_cache may be set in hook_page_attachments().');
  }
  // Modules and themes can alter page attachments.
  \Drupal::moduleHandler()->alter('page_attachments', $attachments);
  \Drupal::theme()->alter('page_attachments', $attachments);
  if (array_diff(array_keys($attachments), ['#attached', '#post_render_cache']) !== []) {
    throw new \LogicException('Only #attached and #post_render_cache may be set in hook_page_attachments_alter().');
  }
  if (isset($attachments['#attached'])) {
    $page['#attached'] = $attachments['#attached'];
  }
  if (isset($attachments['#post_render_cache'])) {
    $page['#post_render_cache'] = $attachments['#post_render_cache'];
  }

  // Modules can add renderable arrays to the top and bottom of the page.
  $pseudo_page_top = [];
  $pseudo_page_bottom = [];
  foreach (\Drupal::moduleHandler()->getImplementations('page_top') as $module) {
    $function = $module . '_page_top';
    $function($pseudo_page_top);
  }
  foreach (\Drupal::moduleHandler()->getImplementations('page_bottom') as $module) {
    $function = $module . '_page_bottom';
    $function($pseudo_page_bottom);
  }
  if (!empty($pseudo_page_top)) {
    $page['page_top'] = $pseudo_page_top;
  }
  if (!empty($pseudo_page_bottom)) {
    $page['page_bottom'] = $pseudo_page_bottom;
  }

  // @todo Clean this up as part of https://www.drupal.org/node/2352155.
  if (\Drupal::moduleHandler()->moduleExists('block')) {
    _block_page_build($page);
    // Find all non-empty page regions, and add a theme wrapper function that
    // allows them to be consistently themed.
    $regions = system_region_list(\Drupal::theme()->getActiveTheme()->getName());
    foreach (array_keys($regions) as $region) {
      if (!empty($page[$region])) {
        $page[$region]['#theme_wrappers'][] = 'region';
        $page[$region]['#region'] = $region;
      }
    }
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
 * Renders final HTML given a structured array tree.
 *
 * Calls drupal_render() in such a way that #post_render_cache callbacks are
 * applied.
 *
 * Should therefore only be used in occasions where the final rendering is
 * happening, just before sending a Response:
 * - system internals that are responsible for rendering the final HTML
 * - render arrays for non-HTML responses, such as feeds
 *
 * @param array $elements
 *   The structured array describing the data to be rendered.
 *
 * @return string
 *   The rendered HTML.
 *
 * @see drupal_render()
 */
function drupal_render_root(&$elements) {
  return drupal_render($elements, TRUE);
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
 * An important aspect of rendering is the bubbling of rendering metadata: cache
 * tags, attached assets and #post_render_cache metadata all need to be bubbled
 * up. That information is needed once the rendering to a HTML string is
 * completed: the resulting HTML for the page must know by which cache tags it
 * should be invalidated, which (CSS and JavaScript) assets must be loaded, and
 * which #post_render_cache callbacks should be executed. A stack data structure
 * is used to perform this bubbling.
 *
 * The process of rendering an element is recursive unless the element defines
 * an implemented theme hook in #theme. During each call to drupal_render(), the
 * outermost renderable array (also known as an "element") is processed using
 * the following steps:
 *   - If this element has already been printed (#printed = TRUE) or the user
 *     does not have access to it (#access = FALSE), then an empty string is
 *     returned.
 *   - If no stack data structure has been created yet, it is done now. Next,
 *     an empty \Drupal\Core\Render\RenderStackFrame is pushed onto the stack.
 *   - If this element has #cache defined then the cached markup for this
 *     element will be returned if it exists in drupal_render()'s cache. To use
 *     drupal_render() caching, set the element's #cache property to an
 *     associative array with one or several of the following keys:
 *     - 'keys': An array of one or more keys that identify the element. If
 *       'keys' is set, the cache ID is created automatically from these keys.
 *       Cache keys may either be static (just strings) or tokens (placeholders
 *       that are converted to static keys by the @cache_contexts service,
 *       depending on the request). See drupal_render_cid_create().
 *     - 'cid': Specify the cache ID directly. Either 'keys' or 'cid' is
 *       required. If 'cid' is set, 'keys' is ignored. Use only if you have
 *       special requirements.
 *     - 'expire': Set to one of the cache lifetime constants.
 *     - 'bin': Specify a cache bin to cache the element in. Default is
 *       'default'.
 *     When there is a render cache hit, there is no rendering work left to be
 *     done, so the stack must be updated. The empty (and topmost) frame that
 *     was just pushed onto the stack is updated with all bubbleable rendering
 *     metadata from the element retrieved from render cache. Then, this stack
 *     frame is bubbled: the two topmost frames are popped from the stack, they
 *     are merged, and the result is pushed back onto the stack.
 *   - If this element has #type defined and the default attributes for this
 *     element have not already been merged in (#defaults_loaded = TRUE) then
 *     the defaults for this type of element, defined in hook_element_info(),
 *     are merged into the array. #defaults_loaded is set by functions that
 *     process render arrays and call element_info() before passing the array to
 *     drupal_render(), such as \Drupal::formBuilder()->doBuildForm() in the
 *     Form API.
 *   - If this element has an array of #pre_render functions defined, they are
 *     called sequentially to modify the element before rendering. After all the
 *     #pre_render functions have been called, #printed is checked a second time
 *     in case a #pre_render function flags the element as printed.
 *     If #printed is set, we return early and hence no rendering work is left
 *     to be done, similarly to a render cache hit. Once again, the empty (and
 *     topmost) frame that was just pushed onto the stack is updated with all
 *     bubbleable rendering metadata from the element whose #printed = TRUE.
 *     Then, this stack frame is bubbled: the two topmost frames are popped from
 *     the stack, they are merged, and the result is pushed back onto the stack.
 *   - The child elements of this element are sorted by weight using uasort() in
 *     \Drupal\Core\Render\Element::children(). Since this is expensive, when
 *     passing already sorted elements to drupal_render(), for example from a
 *     database query, set $elements['#sorted'] = TRUE to avoid sorting them a
 *     second time.
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
 *       '#attributes' => array('class' => array('foo')),
 *       '#theme_wrappers' => array('container'),
 *     );
 *     @endcode
 *     and we need to pass the class 'bar' as an attribute for 'container', we
 *     can rewrite our element thus:
 *     @code
 *     array(
 *       '#theme' => 'image',
 *       '#attributes' => array('class' => array('foo')),
 *       '#theme_wrappers' => array(
 *         'container' => array(
 *           '#attributes' => array('class' => array('bar')),
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
 *   - The rendering of this element is now complete. The next step will be
 *     render caching. So this is the perfect time to update the the stack. At
 *     this point, children of this element (if any), have been rendered also,
 *     and if there were any, their bubbleable rendering metadata will have been
 *     bubbled up into the stack frame for the element that is currently being
 *     rendered. The render cache item for this element must contain the
 *     bubbleable rendering metadata for this element and all of its children.
 *     However, right now, the topmost stack frame (the one for this element)
 *     currently only contains the metadata for the children. Therefore, the
 *     topmost stack frame is updated with this element's metadata, and then the
 *     element's metadata is replaced with the metadata in the topmost stack
 *     frame. This element now contains all bubbleable rendering metadata for
 *     this element and all its children, so it's now ready for render caching.
 *   - If this element has #cache defined, the rendered output of this element
 *     is saved to drupal_render()'s internal cache. This includes the changes
 *     made by #post_render.
 *   - If this element has an array of #post_render_cache functions defined, or
 *     any of its children has (which we would know thanks to the stack having
 *     been updated just before the render caching step), they are called
 *     sequentially to replace placeholders in the final #markup and extend
 *     #attached. Placeholders must contain a unique token, to guarantee that
 *     e.g. samples of placeholders are not replaced also.
 *     But, since #post_render_cache callbacks add attach additional assets, the
 *     correct bubbling of those must once again be taken into account. This
 *     final stage of rendering should be considered as if it were the parent of
 *     the current element, because it takes that as its input, and then alters
 *     its #markup. Hence, just before calling the #post_render_cache callbacks,
 *     a new empty frame is pushed onto the stack, where all assets #attached
 *     during the execution of those callbacks will end up in. Then, after the
 *     execution of those callbacks, we merge that back into the element.
 *     Note that these callbacks run always: when hitting the render cache, when
 *     missing, or when render caching is not used at all. This is done to allow
 *     any Drupal module to customize other render arrays without breaking the
 *     render cache if it is enabled, and to not require it to use other logic
 *     when render caching is disabled.
 *   - Just before finishing the rendering of this element, this element's stack
 *     frame (the topmost one) is bubbled: the two topmost frames are popped
 *     from the stack, they are merged and the result is pushed back onto the
 *     stack.
 *     So if this element e.g. was a child element, then a new frame was pushed
 *     onto the stack element at the beginning of rendering this element, it was
 *     updated when the rendering was completed, and now we merge it with the
 *     frame for the parent, so that the parent now has the bubbleable rendering
 *     metadata for its child.
 *   - #printed is set to TRUE for this element to ensure that it is only
 *     rendered once.
 *   - The final value of #children for this element is returned as the rendered
 *     output.
 *
 * @param array $elements
 *   The structured array describing the data to be rendered.
 * @param bool $is_root_call
 *   (Internal use only.) Whether this is a recursive call or not. See
 *   drupal_render_root().
 *
 * @return string
 *   The rendered HTML.
 *
 * @throws \LogicException
 *   If a root call to drupal_render() does not result in an empty stack, this
 *   indicates an erroneous drupal_render() root call (a root call within a root
 *   call, which makes no sense). Therefore, a logic exception is thrown.
 * @throws \Exception
 *   If a #pre_render callback throws an exception, it is caught to reset the
 *   stack used for bubbling rendering metadata, and then the exception is re-
 *   thrown.
 *
 * @see element_info()
 * @see _theme()
 * @see drupal_process_states()
 * @see drupal_process_attached()
 * @see drupal_render_root()
 */
function drupal_render(&$elements, $is_root_call = FALSE) {
  static $stack;

  $update_stack = function(&$element) use (&$stack) {
    // The latest frame represents the bubbleable data for the subtree.
    $frame = $stack->top();
    // Update the frame, but also update the current element, to ensure it
    // contains up-to-date information in case it gets render cached.
    $frame->tags = $element['#cache']['tags'] = Cache::mergeTags($element['#cache']['tags'], $frame->tags);
    $frame->attached = $element['#attached'] = drupal_merge_attached($element['#attached'], $frame->attached);
    $frame->postRenderCache = $element['#post_render_cache'] = NestedArray::mergeDeep($element['#post_render_cache'], $frame->postRenderCache);
  };

  $bubble_stack = function() use (&$stack) {
    // If there's only one frame on the stack, then this is the root call, and
    // we can't bubble up further. Reset the stack for the next root call.
    if ($stack->count() === 1) {
      $stack = NULL;
      return;
    }

    // Merge the current and the parent stack frame.
    $current = $stack->pop();
    $parent = $stack->pop();
    $current->tags = Cache::mergeTags($current->tags, $parent->tags);
    $current->attached = drupal_merge_attached($current->attached, $parent->attached);
    $current->postRenderCache = NestedArray::mergeDeep($current->postRenderCache, $parent->postRenderCache);
    $stack->push($current);
  };
  /** @var \Drupal\Core\Controller\ControllerResolverInterface $controller_resolver */
  $controller_resolver = \Drupal::service('controller_resolver');
  if (!isset($elements['#access']) && isset($elements['#access_callback'])) {
    if (is_string($elements['#access_callback']) && strpos($elements['#access_callback'], '::') === FALSE) {
      $elements['#access_callback'] = $controller_resolver->getControllerFromDefinition($elements['#access_callback']);
    }
    $elements['#access'] = call_user_func($elements['#access_callback'], $elements);
  }

  // Early-return nothing if user does not have access.
  if (empty($elements) || (isset($elements['#access']) && !$elements['#access'])) {
    return '';
  }

  // Do not print elements twice.
  if (!empty($elements['#printed'])) {
    return '';
  }

  if (!isset($stack)) {
    $stack = new \SplStack();
  }
  $stack->push(new RenderStackFrame());

  // Try to fetch the prerendered element from cache, run any #post_render_cache
  // callbacks and return the final markup.
  if (isset($elements['#cache'])) {
    $cached_element = drupal_render_cache_get($elements);
    if ($cached_element !== FALSE) {
      $elements = $cached_element;
      // Only when we're not in a root (non-recursive) drupal_render() call,
      // #post_render_cache callbacks must be executed, to prevent breaking the
      // render cache in case of nested elements with #cache set.
      if ($is_root_call) {
        _drupal_render_process_post_render_cache($elements);
      }
      $elements['#markup'] = SafeMarkup::set($elements['#markup']);
      // The render cache item contains all the bubbleable rendering metadata for
      // the subtree.
      $update_stack($elements);
      // Render cache hit, so rendering is finished, all necessary info collected!
      $bubble_stack();
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
      if (is_string($callable) && strpos($callable, '::') === FALSE) {
        $callable = $controller_resolver->getControllerFromDefinition($callable);
      }
      // Since #pre_render callbacks may be used for generating a render array's
      // content, and we might be rendering the main content for the page, it is
      // possible that a #pre_render callback throws an exception that will
      // cause a different page to be rendered (e.g. throwing
      // \Symfony\Component\HttpKernel\Exception\NotFoundHttpException will
      // cause the 404 page to be rendered). That page might also use
      // drupal_render(), but if exceptions aren't caught here, the stack will
      // be left in an inconsistent state.
      // Hence, catch all exceptions and reset the stack and re-throw them.
      try {
        $elements = call_user_func($callable, $elements);
      }
      catch (\Exception $e) {
        // Reset stack and re-throw exception.
        $stack = NULL;
        throw $e;
      }
    }
  }

  // Defaults for bubbleable rendering metadata.
  $elements['#cache']['tags'] = isset($elements['#cache']['tags']) ? $elements['#cache']['tags'] : array();
  $elements['#attached'] = isset($elements['#attached']) ? $elements['#attached'] : array();
  $elements['#post_render_cache'] = isset($elements['#post_render_cache']) ? $elements['#post_render_cache'] : array();

  // Allow #pre_render to abort rendering.
  if (!empty($elements['#printed'])) {
    // The #printed element contains all the bubbleable rendering metadata for
    // the subtree.
    $update_stack($elements);
    // #printed, so rendering is finished, all necessary info collected!
    $bubble_stack();
    return '';
  }

  // Add any JavaScript state information associated with the element.
  if (!empty($elements['#states'])) {
    drupal_process_states($elements);
  }

  // Get the children of the element, sorted by weight.
  $children = Element::children($elements, TRUE);

  // Initialize this element's #children, unless a #pre_render callback already
  // preset #children.
  if (!isset($elements['#children'])) {
    $elements['#children'] = '';
  }

  // @todo Simplify after https://drupal.org/node/2273925
  if (isset($elements['#markup'])) {
    $elements['#markup'] = SafeMarkup::set($elements['#markup']);
  }

  // Assume that if #theme is set it represents an implemented hook.
  $theme_is_implemented = isset($elements['#theme']);

  // Call the element's #theme function if it is set. Then any children of the
  // element have to be rendered there. If the internal #render_children
  // property is set, do not call the #theme function to prevent infinite
  // recursion.
  if ($theme_is_implemented && !isset($elements['#render_children'])) {
    $elements['#children'] = \Drupal::theme()->render($elements['#theme'], $elements);

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
      $elements['#children'] .= drupal_render($elements[$key]);
    }
    $elements['#children'] = SafeMarkup::set($elements['#children']);
  }

  // If #theme is not implemented and the element has raw #markup as a
  // fallback, prepend the content in #markup to #children. In this case
  // #children will contain whatever is provided by #pre_render prepended to
  // what is rendered recursively above. If #theme is implemented then it is
  // the responsibility of that theme implementation to render #markup if
  // required. Eventually #theme_wrappers will expect both #markup and
  // #children to be a single string as #children.
  if (!$theme_is_implemented && isset($elements['#markup'])) {
    $elements['#children'] = SafeMarkup::set($elements['#markup'] . $elements['#children']);
  }

  // Let the theme functions in #theme_wrappers add markup around the rendered
  // children.
  // #states and #attached have to be processed before #theme_wrappers, because
  // the #type 'page' render array from drupal_prepare_page() would render the
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

      $elements['#children'] = \Drupal::theme()->render($wrapper_hook, $wrapper_elements);
    }
  }

  // Filter the outputted content and make any last changes before the
  // content is sent to the browser. The changes are made on $content
  // which allows the outputted text to be filtered.
  if (isset($elements['#post_render'])) {
    foreach ($elements['#post_render'] as $callable) {
      if (is_string($callable) && strpos($callable, '::') === FALSE) {
        $callable = $controller_resolver->getControllerFromDefinition($callable);
      }
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

  // We've rendered this element (and its subtree!), now update the stack.
  $update_stack($elements);

  // Cache the processed element if #cache is set.
  if (isset($elements['#cache'])) {
    drupal_render_cache_set($elements['#markup'], $elements);
  }

  // Only when we're in a root (non-recursive) drupal_render() call,
  // #post_render_cache callbacks must be executed, to prevent breaking the
  // render cache in case of nested elements with #cache set.
  //
  // By running them here, we ensure that:
  // - they run when #cache is disabled,
  // - they run when #cache is enabled and there is a cache miss.
  // Only the case of a cache hit when #cache is enabled, is not handled here,
  // that is handled earlier in drupal_render().
  if ($is_root_call) {
    // We've already called $update_stack() earlier, which updated both the
    // element and current stack frame. However,
    // _drupal_render_process_post_render_cache() can both change the element
    // further and create and render new child elements, so provide a fresh
    // stack frame to collect those additions, merge them back to the element,
    // and then update the current frame to match the modified element state.
    $stack->push(new RenderStackFrame());
    _drupal_render_process_post_render_cache($elements);
    $post_render_additions = $stack->pop();
    $elements['#cache']['tags'] = Cache::mergeTags($elements['#cache']['tags'], $post_render_additions->tags);
    $elements['#attached'] = drupal_merge_attached($elements['#attached'], $post_render_additions->attached);
    $elements['#post_render_cache'] = NestedArray::mergeDeep($elements['#post_render_cache'], $post_render_additions->postRenderCache);
    if ($stack->count() !== 1) {
      throw new \LogicException('A stray drupal_render() invocation with $is_root_call = TRUE is causing bubbling of attached assets to break.');
    }
  }

  // Rendering is finished, all necessary info collected!
  $bubble_stack();

  $elements['#printed'] = TRUE;
  $elements['#markup'] = SafeMarkup::set($elements['#markup']);
  return $elements['#markup'];
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
    /** @var \Drupal\Core\Controller\ControllerResolverInterface $controller_resolver */
    $controller_resolver = \Drupal::service('controller_resolver');

    // Call all #post_render_cache callbacks, passing the provided context.
    foreach (array_keys($elements['#post_render_cache']) as $callback) {
      if (strpos($callback, '::') === FALSE) {
        $callable = $controller_resolver->getControllerFromDefinition($callback);
      }
      else {
        $callable = $callback;
      }
      foreach ($elements['#post_render_cache'][$callback] as $context) {
        $elements = call_user_func_array($callable, array($elements, $context));
      }
    }
  }
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
