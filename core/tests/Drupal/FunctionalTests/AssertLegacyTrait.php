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
   *   Use $this->assertSession()->responseContains() instead.
   */
  protected function assertText($text) {
    $this->assertSession()->responseContains($text);
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
   *   Use $this->assertSession()->responseNotContains() instead.
   */
  protected function assertNoText($text) {
    $this->assertSession()->responseNotContains($text);
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
      $this->assertSession()->fieldValueEquals($name, $value);
    }
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
   * Pass if the page title is the given string.
   *
   * @param string $expected_title
   *   The string the page title should be.
   */
  protected function assertTitle($expected_title) {
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
   */
  protected function assertLink($label, $index = 0) {
    return $this->assertSession()->linkExists($label, $index);
  }

}
