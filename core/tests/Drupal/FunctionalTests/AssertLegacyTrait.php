<?php

namespace Drupal\FunctionalTests;

use Drupal\KernelTests\AssertLegacyTrait as BaseAssertLegacyTrait;

/**
 * Provides convenience methods for assertions in browser tests.
 *
 * @deprecated Scheduled for removal in Drupal 9.0.0. Use the methods on
 *   \Drupal\Tests\WebAssert instead, for example
 * @code
 *    $this->assertSession()->statusCodeEquals(200);
 * @endcode
 */
trait AssertLegacyTrait {

  use BaseAssertLegacyTrait;

  /**
   * Asserts that the element with the given CSS selector is present.
   *
   * @param string $css_selector
   *   The CSS selector identifying the element to check.
   *
   * @deprecated Scheduled for removal in Drupal 9.0.0.
   *   Use $this->assertSession()->elementExists() instead.
   */
  protected function assertElementPresent($css_selector) {
    $this->assertSession()->elementExists('css', $css_selector);
  }

  /**
   * Asserts that the element with the given CSS selector is not present.
   *
   * @param string $css_selector
   *   The CSS selector identifying the element to check.
   *
   * @deprecated Scheduled for removal in Drupal 9.0.0.
   *   Use $this->assertSession()->elementNotExists() instead.
   */
  protected function assertElementNotPresent($css_selector) {
    $this->assertSession()->elementNotExists('css', $css_selector);
  }

  /**
   * Passes if the page (with HTML stripped) contains the text.
   *
   * Note that stripping HTML tags also removes their attributes, such as
   * the values of text fields.
   *
   * @param string $text
   *   Plain text to look for.
   *
   * @deprecated Scheduled for removal in Drupal 9.0.0.
   *   Use $this->assertSession()->pageTextContains() or
   *   $this->assertSession()->responseContains() instead.
   */
  protected function assertText($text) {
    $content_type = $this->getSession()->getResponseHeader('Content-type');
    // In case of a Non-HTML response (example: XML) check the original
    // response.
    if (strpos($content_type, 'html') === FALSE) {
      $this->assertSession()->responseContains($text);
    }
    else {
      $this->assertSession()->pageTextContains($text);
    }
  }

  /**
   * Passes if the page (with HTML stripped) does not contains the text.
   *
   * Note that stripping HTML tags also removes their attributes, such as
   * the values of text fields.
   *
   * @param string $text
   *   Plain text to look for.
   *
   * @deprecated Scheduled for removal in Drupal 9.0.0.
   *   Use $this->assertSession()->pageTextNotContains() or
   *   $this->assertSession()->responseNotContains() instead.
   */
  protected function assertNoText($text) {
    $content_type = $this->getSession()->getResponseHeader('Content-type');
    // In case of a Non-HTML response (example: XML) check the original
    // response.
    if (strpos($content_type, 'html') === FALSE) {
      $this->assertSession()->responseNotContains($text);
    }
    else {
      $this->assertSession()->pageTextNotContains($text);
    }
  }

  /**
   * Asserts the page responds with the specified response code.
   *
   * @param int $code
   *   Response code. For example 200 is a successful page request. For a list
   *   of all codes see http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html.
   *
   * @deprecated Scheduled for removal in Drupal 9.0.0.
   *   Use $this->assertSession()->statusCodeEquals() instead.
   */
  protected function assertResponse($code) {
    $this->assertSession()->statusCodeEquals($code);
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
   *
   * @deprecated Scheduled for removal in Drupal 9.0.0.
   *   Use $this->assertSession()->fieldExists() or
   *   $this->assertSession()->fieldValueEquals() instead.
   */
  protected function assertFieldByName($name, $value = NULL) {
    $this->assertSession()->fieldExists($name);
    if ($value !== NULL) {
      $this->assertSession()->fieldValueEquals($name, (string) $value);
    }
  }

  /**
   * Asserts that a field exists with the given ID and value.
   *
   * @param string $id
   *   ID of field to assert.
   * @param string|\Drupal\Component\Render\MarkupInterface $value
   *   (optional) Value for the field to assert. You may pass in NULL to skip
   *   checking the value, while still checking that the field exists.
   *   However, the default value ('') asserts that the field value is an empty
   *   string.
   *
   * @deprecated Scheduled for removal in Drupal 9.0.0.
   *   Use $this->assertSession()->fieldExists() or
   *   $this->assertSession()->fieldValueEquals() instead.
   */
  protected function assertFieldById($id, $value = NULL) {
    $this->assertFieldByName($id, $value);
  }

  /**
   * Asserts that a field exists with the given name or ID.
   *
   * @param string $field
   *   Name or ID of field to assert.
   *
   * @deprecated Scheduled for removal in Drupal 9.0.0.
   *   Use $this->assertSession()->fieldExists() instead.
   */
  protected function assertField($field) {
    $this->assertSession()->fieldExists($field);
  }

  /**
   * Asserts that a field exists with the given name or ID does NOT exist.
   *
   * @param string $field
   *   Name or ID of field to assert.
   *
   * @deprecated Scheduled for removal in Drupal 9.0.0.
   *   Use $this->assertSession()->fieldNotExists() instead.
   */
  protected function assertNoField($field) {
    $this->assertSession()->fieldNotExists($field);
  }

  /**
   * Passes if the raw text IS found on the loaded page, fail otherwise.
   *
   * Raw text refers to the raw HTML that the page generated.
   *
   * @param string $raw
   *   Raw (HTML) string to look for.
   *
   * @deprecated Scheduled for removal in Drupal 9.0.0.
   *   Use $this->assertSession()->responseContains() instead.
   */
  protected function assertRaw($raw) {
    $this->assertSession()->responseContains($raw);
  }

  /**
   * Passes if the raw text IS not found on the loaded page, fail otherwise.
   *
   * Raw text refers to the raw HTML that the page generated.
   *
   * @param string $raw
   *   Raw (HTML) string to look for.
   *
   * @deprecated Scheduled for removal in Drupal 9.0.0.
   *   Use $this->assertSession()->responseNotContains() instead.
   */
  protected function assertNoRaw($raw) {
    $this->assertSession()->responseNotContains($raw);
  }

  /**
   * Pass if the page title is the given string.
   *
   * @param string $expected_title
   *   The string the page title should be.
   *
   * @deprecated Scheduled for removal in Drupal 9.0.0.
   *   Use $this->assertSession()->titleEquals() instead.
   */
  protected function assertTitle($expected_title) {
    // Cast MarkupInterface to string.
    $expected_title = (string) $expected_title;
    return $this->assertSession()->titleEquals($expected_title);
  }

  /**
   * Passes if a link with the specified label is found.
   *
   * An optional link index may be passed.
   *
   * @param string|\Drupal\Component\Render\MarkupInterface $label
   *   Text between the anchor tags.
   * @param int $index
   *   Link position counting from zero.
   *
   * @deprecated Scheduled for removal in Drupal 9.0.0.
   *   Use $this->assertSession()->linkExists() instead.
   */
  protected function assertLink($label, $index = 0) {
    return $this->assertSession()->linkExists($label, $index);
  }

  /**
   * Passes if a link with the specified label is not found.
   *
   * @param string|\Drupal\Component\Render\MarkupInterface $label
   *   Text between the anchor tags.
   *
   * @deprecated Scheduled for removal in Drupal 9.0.0.
   *   Use $this->assertSession()->linkNotExists() instead.
   */
  protected function assertNoLink($label) {
    return $this->assertSession()->linkNotExists($label);
  }

  /**
   * Passes if a link containing a given href (part) is found.
   *
   * @param string $href
   *   The full or partial value of the 'href' attribute of the anchor tag.
   * @param int $index
   *   Link position counting from zero.
   *
   * @deprecated Scheduled for removal in Drupal 9.0.0.
   *   Use $this->assertSession()->linkByHref() instead.
   */
  protected function assertLinkByHref($href, $index = 0) {
    $this->assertSession()->linkByHrefExists($href, $index);
  }

  /**
   * Passes if a link containing a given href (part) is not found.
   *
   * @param string $href
   *   The full or partial value of the 'href' attribute of the anchor tag.
   *
   * @deprecated Scheduled for removal in Drupal 9.0.0.
   *   Use $this->assertSession()->linkByHrefNotExists() instead.
   */
  protected function assertNoLinkByHref($href) {
    $this->assertSession()->linkByHrefNotExists($href);
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
   *
   * @deprecated Scheduled for removal in Drupal 9.0.0.
   *   Use $this->assertSession()->fieldNotExists() or
   *   $this->assertSession()->fieldValueNotEquals() instead.
   */
  protected function assertNoFieldById($id, $value = '') {
    if ($this->getSession()->getPage()->findField($id)) {
      $this->assertSession()->fieldValueNotEquals($id, (string) $value);
    }
    else {
      $this->assertSession()->fieldNotExists($id);
    }
  }

  /**
   * Passes if the internal browser's URL matches the given path.
   *
   * @param \Drupal\Core\Url|string $path
   *   The expected system path or URL.
   *
   * @deprecated Scheduled for removal in Drupal 9.0.0.
   *   Use $this->assertSession()->addressEquals() instead.
   */
  protected function assertUrl($path) {
    $this->assertSession()->addressEquals($path);
  }

  /**
   * Asserts that a select option in the current page exists.
   *
   * @param string $id
   *   ID of select field to assert.
   * @param string $option
   *   Option to assert.
   *
   * @deprecated Scheduled for removal in Drupal 9.0.0.
   *   Use $this->assertSession()->optionExists() instead.
   */
  protected function assertOption($id, $option) {
    return $this->assertSession()->optionExists($id, $option);
  }

  /**
   * Asserts that a select option does NOT exist in the current page.
   *
   * @param string $id
   *   ID of select field to assert.
   * @param string $option
   *   Option to assert.
   *
   * @deprecated Scheduled for removal in Drupal 9.0.0.
   *   Use $this->assertSession()->optionNotExists() instead.
   */
  protected function assertNoOption($id, $option) {
    return $this->assertSession()->optionNotExists($id, $option);
  }

  /**
   * Passes if the raw text IS found escaped on the loaded page, fail otherwise.
   *
   * Raw text refers to the raw HTML that the page generated.
   *
   * @param string $raw
   *   Raw (HTML) string to look for.
   *
   * @deprecated Scheduled for removal in Drupal 9.0.0.
   *   Use $this->assertSession()->assertEscaped() instead.
   */
  protected function assertEscaped($raw) {
    $this->assertSession()->assertEscaped($raw);
  }

  /**
   * Passes if the raw text is not found escaped on the loaded page.
   *
   * Raw text refers to the raw HTML that the page generated.
   *
   * @param string $raw
   *   Raw (HTML) string to look for.
   *
   * @deprecated Scheduled for removal in Drupal 9.0.0.
   *   Use $this->assertSession()->assertNoEscaped() instead.
   */
  protected function assertNoEscaped($raw) {
    $this->assertSession()->assertNoEscaped($raw);
  }

  /**
   * Asserts whether an expected cache tag was present in the last response.
   *
   * @param string $expected_cache_tag
   *   The expected cache tag.
   *
   * @deprecated Scheduled for removal in Drupal 9.0.0.
   *   Use $this->assertSession()->responseHeaderContains() instead.
   */
  protected function assertCacheTag($expected_cache_tag) {
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Tags', $expected_cache_tag);
  }

  /**
   * Returns WebAssert object.
   *
   * @param string $name
   *   (optional) Name of the session. Defaults to the active session.
   *
   * @return \Drupal\Tests\WebAssert
   *   A new web-assert option for asserting the presence of elements with.
   */
  abstract public function assertSession($name = NULL);

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
   *
   * @deprecated Scheduled for removal in Drupal 9.0.0.
   *   Use $this->assertSession()->buildXPathQuery() instead.
   */
  protected function buildXPathQuery($xpath, array $args = array()) {
    return $this->assertSession()->buildXPathQuery($xpath, $args);
  }

}
