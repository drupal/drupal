<?php

/**
 * @file
 * Contains \Drupal\simpletest\AssertContentTrait.
 */

namespace Drupal\simpletest;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Utility\Xss;
use Drupal\Core\Render\RenderContext;
use Symfony\Component\CssSelector\CssSelector;

/**
 * Provides test methods to assert content.
 */
trait AssertContentTrait {

  /**
   * The current raw content.
   *
   * @var string
   */
  protected $content;

  /**
   * The plain-text content of raw $content (text nodes).
   *
   * @var string
   */
  protected $plainTextContent;

  /**
   * The drupalSettings value from the current raw $content.
   *
   * drupalSettings refers to the drupalSettings JavaScript variable.
   *
   * @var array
   */
  protected $drupalSettings;

  /**
   * The XML structure parsed from the current raw $content.
   *
   * @var \SimpleXMLElement
   */
  protected $elements;

  /**
   * Gets the current raw content.
   */
  protected function getRawContent() {
    return $this->content;
  }

  /**
   * Sets the raw content (e.g. HTML).
   *
   * @param string $content
   *   The raw content to set.
   */
  protected function setRawContent($content) {
    $this->content = $content;
    $this->plainTextContent = NULL;
    $this->elements = NULL;
    $this->drupalSettings = array();
    if (preg_match('@<script type="application/json" data-drupal-selector="drupal-settings-json">([^<]*)</script>@', $content, $matches)) {
      $this->drupalSettings = Json::decode($matches[1]);
    }
  }

  /**
   * Retrieves the plain-text content from the current raw content.
   */
  protected function getTextContent() {
    if (!isset($this->plainTextContent)) {
      $raw_content = $this->getRawContent();
      // Strip everything between the HEAD tags.
      $raw_content = preg_replace('@<head>(.+?)</head>@si', '', $raw_content);
      $this->plainTextContent = Xss::filter($raw_content, array());
    }
    return $this->plainTextContent;
  }

  /**
   * Removes all white-space between HTML tags from the raw content.
   *
   * White-space is only removed if there are no non-white-space characters
   * between HTML tags.
   *
   * Use this (once) after performing an operation that sets new raw content,
   * and when you want to use e.g. assertText() but ignore potential white-space
   * caused by HTML output templates.
   */
  protected function removeWhiteSpace() {
    $this->content = preg_replace('@>\s+<@', '><', $this->content);
    $this->plainTextContent = NULL;
    $this->elements = NULL;
  }

  /**
   * Gets the value of drupalSettings for the currently-loaded page.
   *
   * drupalSettings refers to the drupalSettings JavaScript variable.
   */
  protected function getDrupalSettings() {
    return $this->drupalSettings;
  }

  /**
   * Sets the value of drupalSettings for the currently-loaded page.
   *
   * drupalSettings refers to the drupalSettings JavaScript variable.
   */
  protected function setDrupalSettings($settings) {
    $this->drupalSettings = $settings;
  }

  /**
   * Parse content returned from curlExec using DOM and SimpleXML.
   *
   * @return \SimpleXMLElement|FALSE
   *   A SimpleXMLElement or FALSE on failure.
   */
  protected function parse() {
    if (!isset($this->elements)) {
      // DOM can load HTML soup. But, HTML soup can throw warnings, suppress
      // them.
      $html_dom = new \DOMDocument();
      @$html_dom->loadHTML('<?xml encoding="UTF-8">' . $this->getRawContent());
      if ($html_dom) {
        $this->pass(SafeMarkup::format('Valid HTML found on "@path"', array('@path' => $this->getUrl())), 'Browser');
        // It's much easier to work with simplexml than DOM, luckily enough
        // we can just simply import our DOM tree.
        $this->elements = simplexml_import_dom($html_dom);
      }
    }
    if ($this->elements === FALSE) {
      $this->fail('Parsed page successfully.', 'Browser');
    }

    return $this->elements;
  }

  /**
   * Get the current URL from the cURL handler.
   *
   * @return string
   *   The current URL.
   */
  protected function getUrl() {
    return isset($this->url) ? $this->url : 'no-url';
  }

  /**
   * Builds an XPath query.
   *
   * Builds an XPath query by replacing placeholders in the query by the value
   * of the arguments.
   *
   * XPath 1.0 (the version supported by libxml2, the underlying XML library
   * used by PHP) doesn't support any form of quotation. This function
   * simplifies the building of XPath expression.
   *
   * @param string $xpath
   *   An XPath query, possibly with placeholders in the form ':name'.
   * @param array $args
   *   An array of arguments with keys in the form ':name' matching the
   *   placeholders in the query. The values may be either strings or numeric
   *   values.
   *
   * @return string
   *   An XPath query with arguments replaced.
   */
  protected function buildXPathQuery($xpath, array $args = array()) {
    // Replace placeholders.
    foreach ($args as $placeholder => $value) {
      // XPath 1.0 doesn't support a way to escape single or double quotes in a
      // string literal. We split double quotes out of the string, and encode
      // them separately.
      if (is_string($value)) {
        // Explode the text at the quote characters.
        $parts = explode('"', $value);

        // Quote the parts.
        foreach ($parts as &$part) {
          $part = '"' . $part . '"';
        }

        // Return the string.
        $value = count($parts) > 1 ? 'concat(' . implode(', \'"\', ', $parts) . ')' : $parts[0];
      }

      // Use preg_replace_callback() instead of preg_replace() to prevent the
      // regular expression engine from trying to substitute backreferences.
      $replacement = function ($matches) use ($value) {
        return $value;
      };
      $xpath = preg_replace_callback('/' . preg_quote($placeholder) . '\b/', $replacement, $xpath);
    }
    return $xpath;
  }

  /**
   * Performs an xpath search on the contents of the internal browser.
   *
   * The search is relative to the root element (HTML tag normally) of the page.
   *
   * @param string $xpath
   *   The xpath string to use in the search.
   * @param array $arguments
   *   An array of arguments with keys in the form ':name' matching the
   *   placeholders in the query. The values may be either strings or numeric
   *   values.
   *
   * @return array
   *   The return value of the xpath search. For details on the xpath string
   *   format and return values see the SimpleXML documentation,
   *   http://php.net/manual/function.simplexml-element-xpath.php.
   */
  protected function xpath($xpath, array $arguments = array()) {
    if ($this->parse()) {
      $xpath = $this->buildXPathQuery($xpath, $arguments);
      $result = $this->elements->xpath($xpath);
      // Some combinations of PHP / libxml versions return an empty array
      // instead of the documented FALSE. Forcefully convert any falsish values
      // to an empty array to allow foreach(...) constructions.
      return $result ? $result : array();
    }
    else {
      return FALSE;
    }
  }

  /**
   * Searches elements using a CSS selector in the raw content.
   *
   * The search is relative to the root element (HTML tag normally) of the page.
   *
   * @param string $selector
   *   CSS selector to use in the search.
   *
   * @return \SimpleXMLElement[]
   *   The return value of the XPath search performed after converting the CSS
   *   selector to an XPath selector.
   */
  protected function cssSelect($selector) {
    return $this->xpath(CssSelector::toXPath($selector));
  }

  /**
   * Get all option elements, including nested options, in a select.
   *
   * @param \SimpleXMLElement $element
   *   The element for which to get the options.
   *
   * @return \SimpleXmlElement[]
   *   Option elements in select.
   */
  protected function getAllOptions(\SimpleXMLElement $element) {
    $options = array();
    // Add all options items.
    foreach ($element->option as $option) {
      $options[] = $option;
    }

    // Search option group children.
    if (isset($element->optgroup)) {
      foreach ($element->optgroup as $group) {
        $options = array_merge($options, $this->getAllOptions($group));
      }
    }
    return $options;
  }

  /**
   * Passes if a link with the specified label is found.
   *
   * An optional link index may be passed.
   *
   * @param string $label
   *   Text between the anchor tags.
   * @param int $index
   *   Link position counting from zero.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use strtr() to embed variables in the message text, not
   *   t(). If left blank, a default message will be displayed.
   * @param string $group
   *   (optional) The group this message is in, which is displayed in a column
   *   in test output. Use 'Debug' to indicate this is debugging output. Do not
   *   translate this string. Defaults to 'Other'; most tests do not override
   *   this default.
   *
   * @return bool
   *   TRUE if the assertion succeeded, FALSE otherwise.
   */
  protected function assertLink($label, $index = 0, $message = '', $group = 'Other') {
    $links = $this->xpath('//a[normalize-space(text())=:label]', array(':label' => $label));
    $message = ($message ? $message : strtr('Link with label %label found.', array('%label' => $label)));
    return $this->assert(isset($links[$index]), $message, $group);
  }

  /**
   * Passes if a link with the specified label is not found.
   *
   * @param string $label
   *   Text between the anchor tags.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use format_string() to embed variables in the message text, not
   *   t(). If left blank, a default message will be displayed.
   * @param string $group
   *   (optional) The group this message is in, which is displayed in a column
   *   in test output. Use 'Debug' to indicate this is debugging output. Do not
   *   translate this string. Defaults to 'Other'; most tests do not override
   *   this default.
   *
   * @return bool
   *   TRUE if the assertion succeeded, FALSE otherwise.
   */
  protected function assertNoLink($label, $message = '', $group = 'Other') {
    $links = $this->xpath('//a[normalize-space(text())=:label]', array(':label' => $label));
    $message = ($message ? $message : SafeMarkup::format('Link with label %label not found.', array('%label' => $label)));
    return $this->assert(empty($links), $message, $group);
  }

  /**
   * Passes if a link containing a given href (part) is found.
   *
   * @param string $href
   *   The full or partial value of the 'href' attribute of the anchor tag.
   * @param string $index
   *   Link position counting from zero.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use format_string() to embed variables in the message text, not
   *   t(). If left blank, a default message will be displayed.
   * @param string $group
   *   (optional) The group this message is in, which is displayed in a column
   *   in test output. Use 'Debug' to indicate this is debugging output. Do not
   *   translate this string. Defaults to 'Other'; most tests do not override
   *   this default.
   *
   * @return bool
   *   TRUE if the assertion succeeded, FALSE otherwise.
   */
  protected function assertLinkByHref($href, $index = 0, $message = '', $group = 'Other') {
    $links = $this->xpath('//a[contains(@href, :href)]', array(':href' => $href));
    $message = ($message ? $message : SafeMarkup::format('Link containing href %href found.', array('%href' => $href)));
    return $this->assert(isset($links[$index]), $message, $group);
  }

  /**
   * Passes if a link containing a given href (part) is not found.
   *
   * @param string $href
   *   The full or partial value of the 'href' attribute of the anchor tag.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use format_string() to embed variables in the message text, not
   *   t(). If left blank, a default message will be displayed.
   * @param string $group
   *   (optional) The group this message is in, which is displayed in a column
   *   in test output. Use 'Debug' to indicate this is debugging output. Do not
   *   translate this string. Defaults to 'Other'; most tests do not override
   *   this default.
   *
   * @return bool
   *   TRUE if the assertion succeeded, FALSE otherwise.
   */
  protected function assertNoLinkByHref($href, $message = '', $group = 'Other') {
    $links = $this->xpath('//a[contains(@href, :href)]', array(':href' => $href));
    $message = ($message ? $message : SafeMarkup::format('No link containing href %href found.', array('%href' => $href)));
    return $this->assert(empty($links), $message, $group);
  }

  /**
   * Passes if a link containing a given href is not found in the main region.
   *
   * @param string $href
   *   The full or partial value of the 'href' attribute of the anchor tag.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use format_string() to embed variables in the message text, not
   *   t(). If left blank, a default message will be displayed.
   * @param string $group
   *   (optional) The group this message is in, which is displayed in a column
   *   in test output. Use 'Debug' to indicate this is debugging output. Do not
   *   translate this string. Defaults to 'Other'; most tests do not override
   *   this default.
   *
   * @return bool
   *   TRUE if the assertion succeeded, FALSE otherwise.
   */
  protected function assertNoLinkByHrefInMainRegion($href, $message = '', $group = 'Other') {
    $links = $this->xpath('//main//a[contains(@href, :href)]', array(':href' => $href));
    $message = ($message ? $message : SafeMarkup::format('No link containing href %href found.', array('%href' => $href)));
    return $this->assert(empty($links), $message, $group);
  }

  /**
   * Passes if the raw text IS found on the loaded page, fail otherwise.
   *
   * Raw text refers to the raw HTML that the page generated.
   *
   * @param string $raw
   *   Raw (HTML) string to look for.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use format_string() to embed variables in the message text, not
   *   t(). If left blank, a default message will be displayed.
   * @param string $group
   *   (optional) The group this message is in, which is displayed in a column
   *   in test output. Use 'Debug' to indicate this is debugging output. Do not
   *   translate this string. Defaults to 'Other'; most tests do not override
   *   this default.
   *
   * @return bool
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertRaw($raw, $message = '', $group = 'Other') {
    if (!$message) {
      $message = SafeMarkup::format('Raw "@raw" found', array('@raw' => $raw));
    }
    return $this->assert(strpos($this->getRawContent(), (string) $raw) !== FALSE, $message, $group);
  }

  /**
   * Passes if the raw text is NOT found on the loaded page, fail otherwise.
   *
   * Raw text refers to the raw HTML that the page generated.
   *
   * @param string $raw
   *   Raw (HTML) string to look for.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use format_string() to embed variables in the message text, not
   *   t(). If left blank, a default message will be displayed.
   * @param string $group
   *   (optional) The group this message is in, which is displayed in a column
   *   in test output. Use 'Debug' to indicate this is debugging output. Do not
   *   translate this string. Defaults to 'Other'; most tests do not override
   *   this default.
   *
   * @return bool
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertNoRaw($raw, $message = '', $group = 'Other') {
    if (!$message) {
      $message = SafeMarkup::format('Raw "@raw" not found', array('@raw' => $raw));
    }
    return $this->assert(strpos($this->getRawContent(), (string) $raw) === FALSE, $message, $group);
  }

  /**
   * Passes if the raw text IS found escaped on the loaded page, fail otherwise.
   *
   * Raw text refers to the raw HTML that the page generated.
   *
   * @param string $raw
   *   Raw (HTML) string to look for.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use format_string() to embed variables in the message text, not
   *   t(). If left blank, a default message will be displayed.
   * @param string $group
   *   (optional) The group this message is in, which is displayed in a column
   *   in test output. Use 'Debug' to indicate this is debugging output. Do not
   *   translate this string. Defaults to 'Other'; most tests do not override
   *   this default.
   *
   * @return bool
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertEscaped($raw, $message = '', $group = 'Other') {
    if (!$message) {
      $message = SafeMarkup::format('Escaped "@raw" found', array('@raw' => $raw));
    }
    return $this->assert(strpos($this->getRawContent(), Html::escape($raw)) !== FALSE, $message, $group);
  }

  /**
   * Passes if the raw text IS NOT found escaped on the loaded page, fail
   * otherwise.
   *
   * Raw text refers to the raw HTML that the page generated.
   *
   * @param string $raw
   *   Raw (HTML) string to look for.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use format_string() to embed variables in the message text, not
   *   t(). If left blank, a default message will be displayed.
   * @param string $group
   *   (optional) The group this message is in, which is displayed in a column
   *   in test output. Use 'Debug' to indicate this is debugging output. Do not
   *   translate this string. Defaults to 'Other'; most tests do not override
   *   this default.
   *
   * @return bool
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertNoEscaped($raw, $message = '', $group = 'Other') {
    if (!$message) {
      $message = SafeMarkup::format('Escaped "@raw" not found', array('@raw' => $raw));
    }
    return $this->assert(strpos($this->getRawContent(), Html::escape($raw)) === FALSE, $message, $group);
  }

  /**
   * Passes if the page (with HTML stripped) contains the text.
   *
   * Note that stripping HTML tags also removes their attributes, such as
   * the values of text fields.
   *
   * @param string $text
   *   Plain text to look for.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use format_string() to embed variables in the message text, not
   *   t(). If left blank, a default message will be displayed.
   * @param string $group
   *   (optional) The group this message is in, which is displayed in a column
   *   in test output. Use 'Debug' to indicate this is debugging output. Do not
   *   translate this string. Defaults to 'Other'; most tests do not override
   *   this default.
   *
   * @return bool
   *   TRUE on pass, FALSE on fail.
   *
   * @see \Drupal\simpletest\AssertContentTrait::assertRaw()
   */
  protected function assertText($text, $message = '', $group = 'Other') {
    return $this->assertTextHelper($text, $message, $group, FALSE);
  }

  /**
   * Passes if the page (with HTML stripped) does not contains the text.
   *
   * Note that stripping HTML tags also removes their attributes, such as
   * the values of text fields.
   *
   * @param string $text
   *   Plain text to look for.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use format_string() to embed variables in the message text, not
   *   t(). If left blank, a default message will be displayed.
   * @param string $group
   *   (optional) The group this message is in, which is displayed in a column
   *   in test output. Use 'Debug' to indicate this is debugging output. Do not
   *   translate this string. Defaults to 'Other'; most tests do not override
   *   this default.
   *
   * @return bool
   *   TRUE on pass, FALSE on fail.
   *
   * @see \Drupal\simpletest\AssertContentTrait::assertNoRaw()
   */
  protected function assertNoText($text, $message = '', $group = 'Other') {
    return $this->assertTextHelper($text, $message, $group, TRUE);
  }

  /**
   * Helper for assertText and assertNoText.
   *
   * It is not recommended to call this function directly.
   *
   * @param string $text
   *   Plain text to look for.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use format_string() to embed variables in the message text, not
   *   t(). If left blank, a default message will be displayed.
   * @param string $group
   *   (optional) The group this message is in, which is displayed in a column
   *   in test output. Use 'Debug' to indicate this is debugging output. Do not
   *   translate this string. Defaults to 'Other'; most tests do not override
   *   this default. Defaults to 'Other'.
   * @param bool $not_exists
   *   (optional) TRUE if this text should not exist, FALSE if it should.
   *   Defaults to TRUE.
   *
   * @return bool
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertTextHelper($text, $message = '', $group = 'Other', $not_exists = TRUE) {
    if (!$message) {
      $message = !$not_exists ? SafeMarkup::format('"@text" found', array('@text' => $text)) : SafeMarkup::format('"@text" not found', array('@text' => $text));
    }
    return $this->assert($not_exists == (strpos($this->getTextContent(), (string) $text) === FALSE), $message, $group);
  }

  /**
   * Passes if the text is found ONLY ONCE on the text version of the page.
   *
   * The text version is the equivalent of what a user would see when viewing
   * through a web browser. In other words the HTML has been filtered out of
   * the contents.
   *
   * @param string $text
   *   Plain text to look for.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use format_string() to embed variables in the message text, not
   *   t(). If left blank, a default message will be displayed.
   * @param string $group
   *   (optional) The group this message is in, which is displayed in a column
   *   in test output. Use 'Debug' to indicate this is debugging output. Do not
   *   translate this string. Defaults to 'Other'; most tests do not override
   *   this default.
   *
   * @return bool
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertUniqueText($text, $message = '', $group = 'Other') {
    return $this->assertUniqueTextHelper($text, $message, $group, TRUE);
  }

  /**
   * Passes if the text is found MORE THAN ONCE on the text version of the page.
   *
   * The text version is the equivalent of what a user would see when viewing
   * through a web browser. In other words the HTML has been filtered out of
   * the contents.
   *
   * @param string $text
   *   Plain text to look for.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use format_string() to embed variables in the message text, not
   *   t(). If left blank, a default message will be displayed.
   * @param string $group
   *   (optional) The group this message is in, which is displayed in a column
   *   in test output. Use 'Debug' to indicate this is debugging output. Do not
   *   translate this string. Defaults to 'Other'; most tests do not override
   *   this default.
   *
   * @return bool
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertNoUniqueText($text, $message = '', $group = 'Other') {
    return $this->assertUniqueTextHelper($text, $message, $group, FALSE);
  }

  /**
   * Helper for assertUniqueText and assertNoUniqueText.
   *
   * It is not recommended to call this function directly.
   *
   * @param string $text
   *   Plain text to look for.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use format_string() to embed variables in the message text, not
   *   t(). If left blank, a default message will be displayed.
   * @param string $group
   *   (optional) The group this message is in, which is displayed in a column
   *   in test output. Use 'Debug' to indicate this is debugging output. Do not
   *   translate this string. Defaults to 'Other'; most tests do not override
   *   this default. Defaults to 'Other'.
   * @param bool $be_unique
   *   (optional) TRUE if this text should be found only once, FALSE if it
   *   should be found more than once. Defaults to FALSE.
   *
   * @return bool
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertUniqueTextHelper($text, $message = '', $group = 'Other', $be_unique = FALSE) {
    if (!$message) {
      $message = '"' . $text . '"' . ($be_unique ? ' found only once' : ' found more than once');
    }
    $first_occurrence = strpos($this->getTextContent(), $text);
    if ($first_occurrence === FALSE) {
      return $this->assert(FALSE, $message, $group);
    }
    $offset = $first_occurrence + strlen($text);
    $second_occurrence = strpos($this->getTextContent(), $text, $offset);
    return $this->assert($be_unique == ($second_occurrence === FALSE), $message, $group);
  }

  /**
   * Triggers a pass if the Perl regex pattern is found in the raw content.
   *
   * @param string $pattern
   *   Perl regex to look for including the regex delimiters.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use format_string() to embed variables in the message text, not
   *   t(). If left blank, a default message will be displayed.
   * @param string $group
   *   (optional) The group this message is in, which is displayed in a column
   *   in test output. Use 'Debug' to indicate this is debugging output. Do not
   *   translate this string. Defaults to 'Other'; most tests do not override
   *   this default.
   *
   * @return bool
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertPattern($pattern, $message = '', $group = 'Other') {
    if (!$message) {
      $message = SafeMarkup::format('Pattern "@pattern" found', array('@pattern' => $pattern));
    }
    return $this->assert((bool) preg_match($pattern, $this->getRawContent()), $message, $group);
  }

  /**
   * Triggers a pass if the perl regex pattern is not found in raw content.
   *
   * @param string $pattern
   *   Perl regex to look for including the regex delimiters.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use format_string() to embed variables in the message text, not
   *   t(). If left blank, a default message will be displayed.
   * @param string $group
   *   (optional) The group this message is in, which is displayed in a column
   *   in test output. Use 'Debug' to indicate this is debugging output. Do not
   *   translate this string. Defaults to 'Other'; most tests do not override
   *   this default.
   *
   * @return bool
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertNoPattern($pattern, $message = '', $group = 'Other') {
    if (!$message) {
      $message = SafeMarkup::format('Pattern "@pattern" not found', array('@pattern' => $pattern));
    }
    return $this->assert(!preg_match($pattern, $this->getRawContent()), $message, $group);
  }

  /**
   * Asserts that a Perl regex pattern is found in the plain-text content.
   *
   * @param string $pattern
   *   Perl regex to look for including the regex delimiters.
   * @param string $message
   *   (optional) A message to display with the assertion.
   * @param string $group
   *   (optional) The group this message is in, which is displayed in a column
   *   in test output. Use 'Debug' to indicate this is debugging output. Do not
   *   translate this string. Defaults to 'Other'; most tests do not override
   *   this default.
   *
   * @return bool
   *   TRUE on pass, FALSE on failure.
   */
  protected function assertTextPattern($pattern, $message = NULL, $group = 'Other') {
    if (!isset($message)) {
      $message = SafeMarkup::format('Pattern "@pattern" found', array('@pattern' => $pattern));
    }
    return $this->assert((bool) preg_match($pattern, $this->getTextContent()), $message, $group);
  }

  /**
   * Pass if the page title is the given string.
   *
   * @param string $title
   *   The string the title should be.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use format_string() to embed variables in the message text, not
   *   t(). If left blank, a default message will be displayed.
   * @param string $group
   *   (optional) The group this message is in, which is displayed in a column
   *   in test output. Use 'Debug' to indicate this is debugging output. Do not
   *   translate this string. Defaults to 'Other'; most tests do not override
   *   this default.
   *
   * @return bool
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertTitle($title, $message = '', $group = 'Other') {
    $actual = (string) current($this->xpath('//title'));
    if (!$message) {
      $message = SafeMarkup::format('Page title @actual is equal to @expected.', array(
        '@actual' => var_export($actual, TRUE),
        '@expected' => var_export($title, TRUE),
      ));
    }
    return $this->assertEqual($actual, $title, $message, $group);
  }

  /**
   * Pass if the page title is not the given string.
   *
   * @param string $title
   *   The string the title should not be.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use format_string() to embed variables in the message text, not
   *   t(). If left blank, a default message will be displayed.
   * @param string $group
   *   (optional) The group this message is in, which is displayed in a column
   *   in test output. Use 'Debug' to indicate this is debugging output. Do not
   *   translate this string. Defaults to 'Other'; most tests do not override
   *   this default.
   *
   * @return bool
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertNoTitle($title, $message = '', $group = 'Other') {
    $actual = (string) current($this->xpath('//title'));
    if (!$message) {
      $message = SafeMarkup::format('Page title @actual is not equal to @unexpected.', array(
        '@actual' => var_export($actual, TRUE),
        '@unexpected' => var_export($title, TRUE),
      ));
    }
    return $this->assertNotEqual($actual, $title, $message, $group);
  }

  /**
   * Asserts themed output.
   *
   * @param string $callback
   *   The name of the theme hook to invoke; e.g. 'links' for links.html.twig.
   * @param string $variables
   *   An array of variables to pass to the theme function.
   * @param string $expected
   *   The expected themed output string.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use format_string() to embed variables in the message text, not
   *   t(). If left blank, a default message will be displayed.
   * @param string $group
   *   (optional) The group this message is in, which is displayed in a column
   *   in test output. Use 'Debug' to indicate this is debugging output. Do not
   *   translate this string. Defaults to 'Other'; most tests do not override
   *   this default.
   *
   * @return bool
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertThemeOutput($callback, array $variables = array(), $expected = '', $message = '', $group = 'Other') {
    /** @var \Drupal\Core\Render\RendererInterface $renderer */
    $renderer = \Drupal::service('renderer');

    // The string cast is necessary because theme functions return
    // SafeStringInterface objects. This means we can assert that $expected
    // matches the theme output without having to worry about 0 == ''.
    $output = (string) $renderer->executeInRenderContext(new RenderContext(), function() use ($callback, $variables) {
      return \Drupal::theme()->render($callback, $variables);
    });
    $this->verbose(
      '<hr />' . 'Result:' . '<pre>' . SafeMarkup::checkPlain(var_export($output, TRUE)) . '</pre>'
      . '<hr />' . 'Expected:' . '<pre>' . SafeMarkup::checkPlain(var_export($expected, TRUE)) . '</pre>'
      . '<hr />' . $output
    );
    if (!$message) {
      $message = '%callback rendered correctly.';
    }
    $message = format_string($message, array('%callback' => 'theme_' . $callback . '()'));
    return $this->assertIdentical($output, $expected, $message, $group);
  }

  /**
   * Asserts that a field exists in the current page with a given Xpath result.
   *
   * @param \SimpleXmlElement[] $fields
   *   Xml elements.
   * @param string $value
   *   (optional) Value of the field to assert. You may pass in NULL (default) to skip
   *   checking the actual value, while still checking that the field exists.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use format_string() to embed variables in the message text, not
   *   t(). If left blank, a default message will be displayed.
   * @param string $group
   *   (optional) The group this message is in, which is displayed in a column
   *   in test output. Use 'Debug' to indicate this is debugging output. Do not
   *   translate this string. Defaults to 'Other'; most tests do not override
   *   this default.
   *
   * @return bool
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertFieldsByValue($fields, $value = NULL, $message = '', $group = 'Other') {
    // If value specified then check array for match.
    $found = TRUE;
    if (isset($value)) {
      $found = FALSE;
      if ($fields) {
        foreach ($fields as $field) {
          if (isset($field['value']) && $field['value'] == $value) {
            // Input element with correct value.
            $found = TRUE;
          }
          elseif (isset($field->option) || isset($field->optgroup)) {
            // Select element found.
            $selected = $this->getSelectedItem($field);
            if ($selected === FALSE) {
              // No item selected so use first item.
              $items = $this->getAllOptions($field);
              if (!empty($items) && $items[0]['value'] == $value) {
                $found = TRUE;
              }
            }
            elseif ($selected == $value) {
              $found = TRUE;
            }
          }
          elseif ((string) $field == $value) {
            // Text area with correct text.
            $found = TRUE;
          }
        }
      }
    }
    return $this->assertTrue($fields && $found, $message, $group);
  }

  /**
   * Asserts that a field exists in the current page by the given XPath.
   *
   * @param string $xpath
   *   XPath used to find the field.
   * @param string $value
   *   (optional) Value of the field to assert. You may pass in NULL (default)
   *   to skip checking the actual value, while still checking that the field
   *   exists.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use format_string() to embed variables in the message text, not
   *   t(). If left blank, a default message will be displayed.
   * @param string $group
   *   (optional) The group this message is in, which is displayed in a column
   *   in test output. Use 'Debug' to indicate this is debugging output. Do not
   *   translate this string. Defaults to 'Other'; most tests do not override
   *   this default.
   *
   * @return bool
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertFieldByXPath($xpath, $value = NULL, $message = '', $group = 'Other') {
    $fields = $this->xpath($xpath);

    return $this->assertFieldsByValue($fields, $value, $message, $group);
  }

  /**
   * Get the selected value from a select field.
   *
   * @param \SimpleXmlElement $element
   *   SimpleXMLElement select element.
   *
   * @return bool
   *   The selected value or FALSE.
   */
  protected function getSelectedItem(\SimpleXMLElement $element) {
    foreach ($element->children() as $item) {
      if (isset($item['selected'])) {
        return $item['value'];
      }
      elseif ($item->getName() == 'optgroup') {
        if ($value = $this->getSelectedItem($item)) {
          return $value;
        }
      }
    }
    return FALSE;
  }

  /**
   * Asserts that a field does not exist or its value does not match, by XPath.
   *
   * @param string $xpath
   *   XPath used to find the field.
   * @param string $value
   *   (optional) Value of the field, to assert that the field's value on the
   *   page does not match it.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use format_string() to embed variables in the message text, not
   *   t(). If left blank, a default message will be displayed.
   * @param string $group
   *   (optional) The group this message is in, which is displayed in a column
   *   in test output. Use 'Debug' to indicate this is debugging output. Do not
   *   translate this string. Defaults to 'Other'; most tests do not override
   *   this default.
   *
   * @return bool
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertNoFieldByXPath($xpath, $value = NULL, $message = '', $group = 'Other') {
    $fields = $this->xpath($xpath);

    // If value specified then check array for match.
    $found = TRUE;
    if (isset($value)) {
      $found = FALSE;
      if ($fields) {
        foreach ($fields as $field) {
          if ($field['value'] == $value) {
            $found = TRUE;
          }
        }
      }
    }
    return $this->assertFalse($fields && $found, $message, $group);
  }

  /**
   * Asserts that a field exists with the given name and value.
   *
   * @param string $name
   *   Name of field to assert.
   * @param string $value
   *   (optional) Value of the field to assert. You may pass in NULL (default)
   *   to skip checking the actual value, while still checking that the field
   *   exists.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use format_string() to embed variables in the message text, not
   *   t(). If left blank, a default message will be displayed.
   * @param string $group
   *   (optional) The group this message is in, which is displayed in a column
   *   in test output. Use 'Debug' to indicate this is debugging output. Do not
   *   translate this string. Defaults to 'Browser'; most tests do not override
   *   this default.
   *
   * @return bool
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertFieldByName($name, $value = NULL, $message = NULL, $group = 'Browser') {
    if (!isset($message)) {
      if (!isset($value)) {
        $message = SafeMarkup::format('Found field with name @name', array(
          '@name' => var_export($name, TRUE),
        ));
      }
      else {
        $message = SafeMarkup::format('Found field with name @name and value @value', array(
          '@name' => var_export($name, TRUE),
          '@value' => var_export($value, TRUE),
        ));
      }
    }
    return $this->assertFieldByXPath($this->constructFieldXpath('name', $name), $value, $message, $group);
  }

  /**
   * Asserts that a field does not exist with the given name and value.
   *
   * @param string $name
   *   Name of field to assert.
   * @param string $value
   *   (optional) Value for the field, to assert that the field's value on the
   *   page doesn't match it. You may pass in NULL to skip checking the
   *   value, while still checking that the field doesn't exist. However, the
   *   default value ('') asserts that the field value is not an empty string.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use format_string() to embed variables in the message text, not
   *   t(). If left blank, a default message will be displayed.
   * @param string $group
   *   (optional) The group this message is in, which is displayed in a column
   *   in test output. Use 'Debug' to indicate this is debugging output. Do not
   *   translate this string. Defaults to 'Browser'; most tests do not override
   *   this default.
   *
   * @return bool
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertNoFieldByName($name, $value = '', $message = '', $group = 'Browser') {
    return $this->assertNoFieldByXPath($this->constructFieldXpath('name', $name), $value, $message ? $message : SafeMarkup::format('Did not find field by name @name', array('@name' => $name)), $group);
  }

  /**
   * Asserts that a field exists with the given ID and value.
   *
   * @param string $id
   *   ID of field to assert.
   * @param string $value
   *   (optional) Value for the field to assert. You may pass in NULL to skip
   *   checking the value, while still checking that the field exists.
   *   However, the default value ('') asserts that the field value is an empty
   *   string.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use format_string() to embed variables in the message text, not
   *   t(). If left blank, a default message will be displayed.
   * @param string $group
   *   (optional) The group this message is in, which is displayed in a column
   *   in test output. Use 'Debug' to indicate this is debugging output. Do not
   *   translate this string. Defaults to 'Browser'; most tests do not override
   *   this default.
   *
   * @return bool
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertFieldById($id, $value = '', $message = '', $group = 'Browser') {
    return $this->assertFieldByXPath($this->constructFieldXpath('id', $id), $value, $message ? $message : SafeMarkup::format('Found field by id @id', array('@id' => $id)), $group);
  }

  /**
   * Asserts that a field does not exist with the given ID and value.
   *
   * @param string $id
   *   ID of field to assert.
   * @param string $value
   *   (optional) Value for the field, to assert that the field's value on the
   *   page doesn't match it. You may pass in NULL to skip checking the value,
   *   while still checking that the field doesn't exist. However, the default
   *   value ('') asserts that the field value is not an empty string.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use format_string() to embed variables in the message text, not
   *   t(). If left blank, a default message will be displayed.
   * @param string $group
   *   (optional) The group this message is in, which is displayed in a column
   *   in test output. Use 'Debug' to indicate this is debugging output. Do not
   *   translate this string. Defaults to 'Browser'; most tests do not override
   *   this default.
   *
   * @return bool
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertNoFieldById($id, $value = '', $message = '', $group = 'Browser') {
    return $this->assertNoFieldByXPath($this->constructFieldXpath('id', $id), $value, $message ? $message : SafeMarkup::format('Did not find field by id @id', array('@id' => $id)), $group);
  }

  /**
   * Asserts that a checkbox field in the current page is checked.
   *
   * @param string $id
   *   ID of field to assert.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use format_string() to embed variables in the message text, not
   *   t(). If left blank, a default message will be displayed.
   * @param string $group
   *   (optional) The group this message is in, which is displayed in a column
   *   in test output. Use 'Debug' to indicate this is debugging output. Do not
   *   translate this string. Defaults to 'Browser'; most tests do not override
   *   this default.
   *
   * @return bool
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertFieldChecked($id, $message = '', $group = 'Browser') {
    $elements = $this->xpath('//input[@id=:id]', array(':id' => $id));
    return $this->assertTrue(isset($elements[0]) && !empty($elements[0]['checked']), $message ? $message : SafeMarkup::format('Checkbox field @id is checked.', array('@id' => $id)), $group);
  }

  /**
   * Asserts that a checkbox field in the current page is not checked.
   *
   * @param string $id
   *   ID of field to assert.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use format_string() to embed variables in the message text, not
   *   t(). If left blank, a default message will be displayed.
   * @param string $group
   *   (optional) The group this message is in, which is displayed in a column
   *   in test output. Use 'Debug' to indicate this is debugging output. Do not
   *   translate this string. Defaults to 'Browser'; most tests do not override
   *   this default.
   *
   * @return bool
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertNoFieldChecked($id, $message = '', $group = 'Browser') {
    $elements = $this->xpath('//input[@id=:id]', array(':id' => $id));
    return $this->assertTrue(isset($elements[0]) && empty($elements[0]['checked']), $message ? $message : SafeMarkup::format('Checkbox field @id is not checked.', array('@id' => $id)), $group);
  }

  /**
   * Asserts that a select option in the current page exists.
   *
   * @param string $id
   *   ID of select field to assert.
   * @param string $option
   *   Option to assert.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use format_string() to embed variables in the message text, not
   *   t(). If left blank, a default message will be displayed.
   * @param string $group
   *   (optional) The group this message is in, which is displayed in a column
   *   in test output. Use 'Debug' to indicate this is debugging output. Do not
   *   translate this string. Defaults to 'Browser'; most tests do not override
   *   this default.
   *
   * @return bool
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertOption($id, $option, $message = '', $group = 'Browser') {
    $options = $this->xpath('//select[@id=:id]//option[@value=:option]', array(':id' => $id, ':option' => $option));
    return $this->assertTrue(isset($options[0]), $message ? $message : SafeMarkup::format('Option @option for field @id exists.', array('@option' => $option, '@id' => $id)), $group);
  }

  /**
   * Asserts that a select option in the current page exists.
   *
   * @param string $drupal_selector
   *   The data drupal selector of select field to assert.
   * @param string $option
   *   Option to assert.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use format_string() to embed variables in the message text, not
   *   t(). If left blank, a default message will be displayed.
   * @param string $group
   *   (optional) The group this message is in, which is displayed in a column
   *   in test output. Use 'Debug' to indicate this is debugging output. Do not
   *   translate this string. Defaults to 'Browser'; most tests do not override
   *   this default.
   *
   * @return bool
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertOptionWithDrupalSelector($drupal_selector, $option, $message = '', $group = 'Browser') {
    $options = $this->xpath('//select[@data-drupal-selector=:data_drupal_selector]//option[@value=:option]', array(':data_drupal_selector' => $drupal_selector, ':option' => $option));
    return $this->assertTrue(isset($options[0]), $message ? $message : SafeMarkup::format('Option @option for field @data_drupal_selector exists.', array('@option' => $option, '@data_drupal_selector' => $drupal_selector)), $group);
  }

  /**
   * Asserts that a select option in the current page does not exist.
   *
   * @param string $id
   *   ID of select field to assert.
   * @param string $option
   *   Option to assert.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use format_string() to embed variables in the message text, not
   *   t(). If left blank, a default message will be displayed.
   * @param string $group
   *   (optional) The group this message is in, which is displayed in a column
   *   in test output. Use 'Debug' to indicate this is debugging output. Do not
   *   translate this string. Defaults to 'Browser'; most tests do not override
   *   this default.
   *
   * @return bool
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertNoOption($id, $option, $message = '', $group = 'Browser') {
    $selects = $this->xpath('//select[@id=:id]', array(':id' => $id));
    $options = $this->xpath('//select[@id=:id]//option[@value=:option]', array(':id' => $id, ':option' => $option));
    return $this->assertTrue(isset($selects[0]) && !isset($options[0]), $message ? $message : SafeMarkup::format('Option @option for field @id does not exist.', array('@option' => $option, '@id' => $id)), $group);
  }

  /**
   * Asserts that a select option in the current page is checked.
   *
   * @param string $id
   *   ID of select field to assert.
   * @param string $option
   *   Option to assert.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use format_string() to embed variables in the message text, not
   *   t(). If left blank, a default message will be displayed.
   * @param string $group
   *   (optional) The group this message is in, which is displayed in a column
   *   in test output. Use 'Debug' to indicate this is debugging output. Do not
   *   translate this string. Defaults to 'Browser'; most tests do not override
   *   this default.
   *
   * @return bool
   *   TRUE on pass, FALSE on fail.
   *
   * @todo $id is unusable. Replace with $name.
   */
  protected function assertOptionSelected($id, $option, $message = '', $group = 'Browser') {
    $elements = $this->xpath('//select[@id=:id]//option[@value=:option]', array(':id' => $id, ':option' => $option));
    return $this->assertTrue(isset($elements[0]) && !empty($elements[0]['selected']), $message ? $message : SafeMarkup::format('Option @option for field @id is selected.', array('@option' => $option, '@id' => $id)), $group);
  }

  /**
   * Asserts that a select option in the current page is checked.
   *
   * @param string $drupal_selector
   *   The data drupal selector of select field to assert.
   * @param string $option
   *   Option to assert.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use format_string() to embed variables in the message text, not
   *   t(). If left blank, a default message will be displayed.
   * @param string $group
   *   (optional) The group this message is in, which is displayed in a column
   *   in test output. Use 'Debug' to indicate this is debugging output. Do not
   *   translate this string. Defaults to 'Browser'; most tests do not override
   *   this default.
   *
   * @return bool
   *   TRUE on pass, FALSE on fail.
   *
   * @todo $id is unusable. Replace with $name.
   */
  protected function assertOptionSelectedWithDrupalSelector($drupal_selector, $option, $message = '', $group = 'Browser') {
    $elements = $this->xpath('//select[@data-drupal-selector=:data_drupal_selector]//option[@value=:option]', array(':data_drupal_selector' => $drupal_selector, ':option' => $option));
    return $this->assertTrue(isset($elements[0]) && !empty($elements[0]['selected']), $message ? $message : SafeMarkup::format('Option @option for field @data_drupal_selector is selected.', array('@option' => $option, '@data_drupal_selector' => $drupal_selector)), $group);
  }

  /**
   * Asserts that a select option in the current page is not checked.
   *
   * @param string $id
   *   ID of select field to assert.
   * @param string $option
   *   Option to assert.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use format_string() to embed variables in the message text, not
   *   t(). If left blank, a default message will be displayed.
   * @param string $group
   *   (optional) The group this message is in, which is displayed in a column
   *   in test output. Use 'Debug' to indicate this is debugging output. Do not
   *   translate this string. Defaults to 'Browser'; most tests do not override
   *   this default.
   *
   * @return bool
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertNoOptionSelected($id, $option, $message = '', $group = 'Browser') {
    $elements = $this->xpath('//select[@id=:id]//option[@value=:option]', array(':id' => $id, ':option' => $option));
    return $this->assertTrue(isset($elements[0]) && empty($elements[0]['selected']), $message ? $message : SafeMarkup::format('Option @option for field @id is not selected.', array('@option' => $option, '@id' => $id)), $group);
  }

  /**
   * Asserts that a field exists with the given name or ID.
   *
   * @param string $field
   *   Name or ID of field to assert.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use format_string() to embed variables in the message text, not
   *   t(). If left blank, a default message will be displayed.
   * @param string $group
   *   (optional) The group this message is in, which is displayed in a column
   *   in test output. Use 'Debug' to indicate this is debugging output. Do not
   *   translate this string. Defaults to 'Other'; most tests do not override
   *   this default.
   *
   * @return bool
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertField($field, $message = '', $group = 'Other') {
    return $this->assertFieldByXPath($this->constructFieldXpath('name', $field) . '|' . $this->constructFieldXpath('id', $field), NULL, $message, $group);
  }

  /**
   * Asserts that a field does not exist with the given name or ID.
   *
   * @param string $field
   *   Name or ID of field to assert.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use format_string() to embed variables in the message text, not
   *   t(). If left blank, a default message will be displayed.
   * @param string $group
   *   (optional) The group this message is in, which is displayed in a column
   *   in test output. Use 'Debug' to indicate this is debugging output. Do not
   *   translate this string. Defaults to 'Other'; most tests do not override
   *   this default.
   *
   * @return bool
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertNoField($field, $message = '', $group = 'Other') {
    return $this->assertNoFieldByXPath($this->constructFieldXpath('name', $field) . '|' . $this->constructFieldXpath('id', $field), NULL, $message, $group);
  }

  /**
   * Asserts that each HTML ID is used for just a single element.
   *
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use format_string() to embed variables in the message text, not
   *   t(). If left blank, a default message will be displayed.
   * @param string $group
   *   (optional) The group this message is in, which is displayed in a column
   *   in test output. Use 'Debug' to indicate this is debugging output. Do not
   *   translate this string. Defaults to 'Other'; most tests do not override
   *   this default.
   * @param array $ids_to_skip
   *   An optional array of ids to skip when checking for duplicates. It is
   *   always a bug to have duplicate HTML IDs, so this parameter is to enable
   *   incremental fixing of core code. Whenever a test passes this parameter,
   *   it should add a "todo" comment above the call to this function explaining
   *   the legacy bug that the test wishes to ignore and including a link to an
   *   issue that is working to fix that legacy bug.
   *
   * @return bool
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertNoDuplicateIds($message = '', $group = 'Other', $ids_to_skip = array()) {
    $status = TRUE;
    foreach ($this->xpath('//*[@id]') as $element) {
      $id = (string) $element['id'];
      if (isset($seen_ids[$id]) && !in_array($id, $ids_to_skip)) {
        $this->fail(SafeMarkup::format('The HTML ID %id is unique.', array('%id' => $id)), $group);
        $status = FALSE;
      }
      $seen_ids[$id] = TRUE;
    }
    return $this->assert($status, $message, $group);
  }

  /**
   * Helper: Constructs an XPath for the given set of attributes and value.
   *
   * @param string $attribute
   *   Field attributes.
   * @param string $value
   *   Value of field.
   *
   * @return string
   *   XPath for specified values.
   */
  protected function constructFieldXpath($attribute, $value) {
    $xpath = '//textarea[@' . $attribute . '=:value]|//input[@' . $attribute . '=:value]|//select[@' . $attribute . '=:value]';
    return $this->buildXPathQuery($xpath, array(':value' => $value));
  }

}
