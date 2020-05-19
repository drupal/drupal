<?php
// @codingStandardsIgnoreFile
namespace Drupal\FunctionalTests;

use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ExpectationException;
use Behat\Mink\Selector\Xpath\Escaper;
use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Xss;
use Drupal\KernelTests\AssertLegacyTrait as BaseAssertLegacyTrait;

/**
 * Provides convenience methods for assertions in browser tests.
 *
 * @deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use
 *   the methods from \Drupal\Tests\WebAssert instead.
 *
 * @see https://www.drupal.org/node/3129738
 */
trait AssertLegacyTrait {

  use BaseAssertLegacyTrait;

  /**
   * Asserts that the element with the given CSS selector is present.
   *
   * @param string $css_selector
   *   The CSS selector identifying the element to check.
   *
   * @deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use
   *   $this->assertSession()->elementExists() instead.
   *
   * @see https://www.drupal.org/node/3129738
   */
  protected function assertElementPresent($css_selector) {
    @trigger_error('AssertLegacyTrait::assertElementPresent() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->assertSession()->elementExists() instead. See https://www.drupal.org/node/3129738', E_USER_DEPRECATED);
    $this->assertSession()->elementExists('css', $css_selector);
  }

  /**
   * Asserts that the element with the given CSS selector is not present.
   *
   * @param string $css_selector
   *   The CSS selector identifying the element to check.
   *
   * @deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use
   *   $this->assertSession()->elementNotExists() instead.
   *
   * @see https://www.drupal.org/node/3129738
   */
  protected function assertElementNotPresent($css_selector) {
    @trigger_error('AssertLegacyTrait::assertElementNotPresent() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->assertSession()->elementNotExists() instead. See https://www.drupal.org/node/3129738', E_USER_DEPRECATED);
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
   * @deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use
   *   - $this->assertSession()->responseContains() for non-HTML responses,
   *     like XML or Json.
   *   - $this->assertSession()->pageTextContains() for HTML responses. Unlike
   *     the deprecated assertText(), the passed text should be HTML decoded,
   *     exactly as a human sees it in the browser.
   *
   * @see https://www.drupal.org/node/3129738
   */
  protected function assertText($text) {
    @trigger_error('AssertLegacyTrait::assertText() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->assertSession()->responseContains() or $this->assertSession()->pageTextContains() instead. See https://www.drupal.org/node/3129738', E_USER_DEPRECATED);
    // Cast MarkupInterface to string.
    $text = (string) $text;

    $content_type = $this->getSession()->getResponseHeader('Content-type');
    // In case of a Non-HTML response (example: XML) check the original
    // response.
    if (strpos($content_type, 'html') === FALSE) {
      $this->assertSession()->responseContains($text);
    }
    else {
      $this->assertTextHelper($text, FALSE);
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
   * @deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use
   *   - $this->assertSession()->responseNotContains() for non-HTML responses,
   *     like XML or Json.
   *   - $this->assertSession()->pageTextNotContains() for HTML responses.
   *     Unlike the deprecated assertNoText(), the passed text should be HTML
   *     decoded, exactly as a human sees it in the browser.
   *
   * @see https://www.drupal.org/node/3129738
   */
  protected function assertNoText($text) {
    @trigger_error('AssertLegacyTrait::assertNoText() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->assertSession()->responseNotContains() or $this->assertSession()->pageTextNotContains() instead. See https://www.drupal.org/node/3129738', E_USER_DEPRECATED);
    // Cast MarkupInterface to string.
    $text = (string) $text;

    $content_type = $this->getSession()->getResponseHeader('Content-type');
    // In case of a Non-HTML response (example: XML) check the original
    // response.
    if (strpos($content_type, 'html') === FALSE) {
      $this->assertSession()->responseNotContains($text);
    }
    else {
      $this->assertTextHelper($text);
    }
  }

  /**
   * Helper for assertText and assertNoText.
   *
   * @param string $text
   *   Plain text to look for.
   * @param bool $not_exists
   *   (optional) TRUE if this text should not exist, FALSE if it should.
   *   Defaults to TRUE.
   *
   * @return bool
   *   TRUE on pass, FALSE on fail.
   *
   * @deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use
   *   $this->assertSession()->pageTextContains() or
   *   $this->assertSession()->pageTextNotContains() instead.
   *
   * @see https://www.drupal.org/node/3129738
   */
  protected function assertTextHelper($text, $not_exists = TRUE) {
    @trigger_error('AssertLegacyTrait::assertText() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->assertSession()->pageTextContains() or $this->assertSession()->pageTextNotContains() instead. See https://www.drupal.org/node/3129738', E_USER_DEPRECATED);
    $args = ['@text' => $text];
    $message = $not_exists ? new FormattableMarkup('"@text" not found', $args) : new FormattableMarkup('"@text" found', $args);

    $raw_content = $this->getSession()->getPage()->getContent();
    // Trying to simulate what the user sees, given that it removes all text
    // inside the head tags, removes inline Javascript, fix all HTML entities,
    // removes dangerous protocols and filtering out all HTML tags, as they are
    // not visible in a normal browser.
    $raw_content = preg_replace('@<head>(.+?)</head>@si', '', $raw_content);
    $page_text = Xss::filter($raw_content, []);

    $actual = $not_exists == (strpos($page_text, (string) $text) === FALSE);
    $this->assertTrue($actual, $message);

    return $actual;
  }

  /**
   * Passes if the text is found ONLY ONCE on the text version of the page.
   *
   * The text version is the equivalent of what a user would see when viewing
   * through a web browser. In other words the HTML has been filtered out of
   * the contents.
   *
   * @param string|\Drupal\Component\Render\MarkupInterface $text
   *   Plain text to look for.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages with t(). If left blank, a default message will be displayed.
   *
   * @deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use
   *   $this->getSession()->getPage()->getText() and substr_count() instead.
   *
   * @see https://www.drupal.org/node/3129738
   */
  protected function assertUniqueText($text, $message = NULL) {
    @trigger_error('AssertLegacyTrait::assertUniqueText() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->getSession()->getPage()->getText() and substr_count() instead. See https://www.drupal.org/node/3129738', E_USER_DEPRECATED);
    // Cast MarkupInterface objects to string.
    $text = (string) $text;

    $message = $message ?: "'$text' found only once on the page";
    $page_text = $this->getSession()->getPage()->getText();
    $nr_found = substr_count($page_text, $text);
    $this->assertSame(1, $nr_found, $message);
  }

  /**
   * Passes if the text is found MORE THAN ONCE on the text version of the page.
   *
   * The text version is the equivalent of what a user would see when viewing
   * through a web browser. In other words the HTML has been filtered out of
   * the contents.
   *
   * @param string|\Drupal\Component\Render\MarkupInterface $text
   *   Plain text to look for.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages with t(). If left blank, a default message will be displayed.
   *
   * @deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use
   *   $this->getSession()->getPage()->getText() and substr_count() instead.
   *
   * @see https://www.drupal.org/node/3129738
   */
  protected function assertNoUniqueText($text, $message = '') {
    @trigger_error('AssertLegacyTrait::assertNoUniqueText() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->getSession()->getPage()->getText() and substr_count() instead. See https://www.drupal.org/node/3129738', E_USER_DEPRECATED);
    // Cast MarkupInterface objects to string.
    $text = (string) $text;

    $message = $message ?: "'$text' found more than once on the page";
    $page_text = $this->getSession()->getPage()->getText();
    $nr_found = substr_count($page_text, $text);
    $this->assertGreaterThan(1, $nr_found, $message);
  }

  /**
   * Asserts the page responds with the specified response code.
   *
   * @param int $code
   *   Response code. For example 200 is a successful page request. For a list
   *   of all codes see http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html.
   *
   * @deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use
   *   $this->assertSession()->statusCodeEquals() instead.
   *
   * @see https://www.drupal.org/node/3129738
   */
  protected function assertResponse($code) {
    @trigger_error('AssertLegacyTrait::assertResponse() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->assertSession()->statusCodeEquals() instead. See https://www.drupal.org/node/3129738', E_USER_DEPRECATED);
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
   * @deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use
   *   $this->assertSession()->fieldExists() or
   *   $this->assertSession()->buttonExists() or
   *   $this->assertSession()->fieldValueEquals() instead.
   *
   * @see https://www.drupal.org/node/3129738
   */
  protected function assertFieldByName($name, $value = NULL) {
    @trigger_error('AssertLegacyTrait::assertFieldByName() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->assertSession()->fieldExists() or $this->assertSession()->buttonExists() or $this->assertSession()->fieldValueEquals() instead. See https://www.drupal.org/node/3129738', E_USER_DEPRECATED);
    $this->assertFieldByXPath($this->constructFieldXpath('name', $name), $value);
  }

  /**
   * Asserts that a field does not exist with the given name and value.
   *
   * @param string $name
   *   Name of field to assert.
   * @param string $value
   *   (optional) Value for the field, to assert that the field's value on the
   *   page does not match it. You may pass in NULL to skip checking the
   *   value, while still checking that the field does not exist. However, the
   *   default value ('') asserts that the field value is not an empty string.
   *
   * @deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use
   *   $this->assertSession()->fieldNotExists() or
   *   $this->assertSession()->buttonNotExists() or
   *   $this->assertSession()->fieldValueNotEquals() instead.
   *
   * @see https://www.drupal.org/node/3129738
   */
  protected function assertNoFieldByName($name, $value = '') {
    @trigger_error('AssertLegacyTrait::assertNoFieldByName() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->assertSession()->fieldNotExists() or $this->assertSession()->buttonNotExists() or $this->assertSession()->fieldValueNotEquals() instead. See https://www.drupal.org/node/3129738', E_USER_DEPRECATED);
    $this->assertNoFieldByXPath($this->constructFieldXpath('name', $name), $value);
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
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   *
   * @deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use
   *   $this->assertSession()->fieldExists() or
   *   $this->assertSession()->buttonExists() or
   *   $this->assertSession()->fieldValueEquals() instead.
   *
   * @see https://www.drupal.org/node/3129738
   */
  protected function assertFieldById($id, $value = '') {
    @trigger_error('AssertLegacyTrait::assertFieldById() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->assertSession()->fieldExists() or $this->assertSession()->buttonExists() or $this->assertSession()->fieldValueEquals() instead. See https://www.drupal.org/node/3129738', E_USER_DEPRECATED);
    $this->assertFieldByXPath($this->constructFieldXpath('id', $id), $value);
  }

  /**
   * Asserts that a field exists with the given name or ID.
   *
   * @param string $field
   *   Name or ID of field to assert.
   *
   * @deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use
   *   $this->assertSession()->fieldExists() or
   *   $this->assertSession()->buttonExists() instead.
   *
   * @see https://www.drupal.org/node/3129738
   */
  protected function assertField($field) {
    @trigger_error('AssertLegacyTrait::assertField() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->assertSession()->fieldExists() or $this->assertSession()->buttonExists() instead. See https://www.drupal.org/node/3129738', E_USER_DEPRECATED);
    $this->assertFieldByXPath($this->constructFieldXpath('name', $field) . '|' . $this->constructFieldXpath('id', $field));
  }

  /**
   * Asserts that a field does NOT exist with the given name or ID.
   *
   * @param string $field
   *   Name or ID of field to assert.
   *
   * @deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use
   *   $this->assertSession()->fieldNotExists() or
   *   $this->assertSession()->buttonNotExists() instead.
   *
   * @see https://www.drupal.org/node/3129738
   */
  protected function assertNoField($field) {
    @trigger_error('AssertLegacyTrait::assertNoField() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->assertSession()->fieldNotExists() or $this->assertSession()->buttonNotExists() instead. See https://www.drupal.org/node/3129738', E_USER_DEPRECATED);
    $this->assertNoFieldByXPath($this->constructFieldXpath('name', $field) . '|' . $this->constructFieldXpath('id', $field));
  }

  /**
   * Passes if the raw text IS found on the loaded page, fail otherwise.
   *
   * Raw text refers to the raw HTML that the page generated.
   *
   * @param string $raw
   *   Raw (HTML) string to look for.
   *
   * @deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use
   *   $this->assertSession()->responseContains() instead.
   *
   * @see https://www.drupal.org/node/3129738
   */
  protected function assertRaw($raw) {
    @trigger_error('AssertLegacyTrait::assertRaw() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->assertSession()->responseContains() instead. See https://www.drupal.org/node/3129738', E_USER_DEPRECATED);
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
   * @deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use
   *   $this->assertSession()->responseNotContains() instead.
   *
   * @see https://www.drupal.org/node/3129738
   */
  protected function assertNoRaw($raw) {
    @trigger_error('AssertLegacyTrait::assertNoRaw() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->assertSession()->responseNotContains() instead. See https://www.drupal.org/node/3129738', E_USER_DEPRECATED);
    $this->assertSession()->responseNotContains($raw);
  }

  /**
   * Pass if the page title is the given string.
   *
   * @param string $expected_title
   *   The string the page title should be.
   *
   * @deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use
   *   $this->assertSession()->titleEquals() instead.
   *
   * @see https://www.drupal.org/node/3129738
   */
  protected function assertTitle($expected_title) {
    @trigger_error('AssertLegacyTrait::assertTitle() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->assertSession()->titleEquals() instead. See https://www.drupal.org/node/3129738', E_USER_DEPRECATED);
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
   * @deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use
   *   $this->assertSession()->linkExists() instead.
   *
   * @see https://www.drupal.org/node/3129738
   */
  protected function assertLink($label, $index = 0) {
    @trigger_error('AssertLegacyTrait::assertLink() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->assertSession()->linkExists() instead. See https://www.drupal.org/node/3129738', E_USER_DEPRECATED);
    return $this->assertSession()->linkExists($label, $index);
  }

  /**
   * Passes if a link with the specified label is not found.
   *
   * @param string|\Drupal\Component\Render\MarkupInterface $label
   *   Text between the anchor tags.
   *
   * @deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use
   *   $this->assertSession()->linkNotExists() instead.
   *
   * @see https://www.drupal.org/node/3129738
   */
  protected function assertNoLink($label) {
    @trigger_error('AssertLegacyTrait::assertNoLink() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->assertSession()->linkNotExists() instead. See https://www.drupal.org/node/3129738', E_USER_DEPRECATED);
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
   * @deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use
   *   $this->assertSession()->linkByHrefExists() instead.
   *
   * @see https://www.drupal.org/node/3129738
   */
  protected function assertLinkByHref($href, $index = 0) {
    @trigger_error('AssertLegacyTrait::assertLinkByHref() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->assertSession()->linkByHrefExists() instead. See https://www.drupal.org/node/3129738', E_USER_DEPRECATED);
    $this->assertSession()->linkByHrefExists($href, $index);
  }

  /**
   * Passes if a link containing a given href (part) is not found.
   *
   * @param string $href
   *   The full or partial value of the 'href' attribute of the anchor tag.
   *
   * @deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use
   *   $this->assertSession()->linkByHrefNotExists() instead.
   *
   * @see https://www.drupal.org/node/3129738
   */
  protected function assertNoLinkByHref($href) {
    @trigger_error('AssertLegacyTrait::assertNoLinkByHref() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->assertSession()->linkByHrefNotExists() instead. See https://www.drupal.org/node/3129738', E_USER_DEPRECATED);
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
   * @throws \Behat\Mink\Exception\ExpectationException
   *
   * @deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use
   *   $this->assertSession()->fieldNotExists() or
   *   $this->assertSession()->buttonNotExists() or
   *   $this->assertSession()->fieldValueNotEquals() instead.
   *
   * @see https://www.drupal.org/node/3129738
   */
  protected function assertNoFieldById($id, $value = '') {
    @trigger_error('AssertLegacyTrait::assertNoFieldById() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->assertSession()->fieldNotExists() or $this->assertSession()->buttonNotExists() or $this->assertSession()->fieldValueNotEquals() instead. See https://www.drupal.org/node/3129738', E_USER_DEPRECATED);
    $this->assertNoFieldByXPath($this->constructFieldXpath('id', $id), $value);
  }

  /**
   * Passes if the internal browser's URL matches the given path.
   *
   * @param \Drupal\Core\Url|string $path
   *   The expected system path or URL.
   *
   * @deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use
   *   $this->assertSession()->addressEquals() instead.
   *
   * @see https://www.drupal.org/node/3129738
   */
  protected function assertUrl($path) {
    @trigger_error('AssertLegacyTrait::assertUrl() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->assertSession()->addressEquals() instead. See https://www.drupal.org/node/3129738', E_USER_DEPRECATED);
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
   * @deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use
   *   $this->assertSession()->optionExists() instead.
   *
   * @see https://www.drupal.org/node/3129738
   */
  protected function assertOption($id, $option) {
    @trigger_error('AssertLegacyTrait::assertOption() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->assertSession()->optionExists() instead. See https://www.drupal.org/node/3129738', E_USER_DEPRECATED);
    return $this->assertSession()->optionExists($id, $option);
  }

  /**
   * Asserts that a select option with the visible text exists.
   *
   * @param string $id
   *   The ID of the select field to assert.
   * @param string $text
   *   The text for the option tag to assert.
   *
   * @deprecated in drupal:8.4.0 and is removed from drupal:10.0.0. Use
   *   $this->assertSession()->optionExists() instead.
   *
   * @see https://www.drupal.org/node/3129738
   */
  protected function assertOptionByText($id, $text) {
    @trigger_error('AssertLegacyTrait::assertOptionByText() is deprecated in drupal:8.4.0 and is removed from drupal:10.0.0. Use $this->assertSession()->optionExists() instead. See https://www.drupal.org/node/3129738', E_USER_DEPRECATED);
    return $this->assertSession()->optionExists($id, $text);
  }

  /**
   * Asserts that a select option does NOT exist in the current page.
   *
   * @param string $id
   *   ID of select field to assert.
   * @param string $option
   *   Option to assert.
   *
   * @deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use
   *   $this->assertSession()->optionNotExists() instead.
   *
   * @see https://www.drupal.org/node/3129738
   */
  protected function assertNoOption($id, $option) {
    @trigger_error('AssertLegacyTrait::assertNoOption() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->assertSession()->optionNotExists() instead. See https://www.drupal.org/node/3129738', E_USER_DEPRECATED);
    return $this->assertSession()->optionNotExists($id, $option);
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
   *   messages with t(). If left blank, a default message will be displayed.
   *
   * @deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use
   *   $this->assertSession()->optionExists() instead and check the
   *   "selected" attribute yourself.
   *
   * @see https://www.drupal.org/node/3129738
   */
  protected function assertOptionSelected($id, $option, $message = NULL) {
    @trigger_error('AssertLegacyTrait::assertOptionSelected() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->assertSession()->optionExists() instead and check the "selected" attribute. See https://www.drupal.org/node/3129738', E_USER_DEPRECATED);
    $option_field = $this->assertSession()->optionExists($id, $option);
    $message = $message ?: "Option $option for field $id is selected.";
    $this->assertTrue($option_field->hasAttribute('selected'), $message);
  }

  /**
   * Asserts that a checkbox field in the current page is checked.
   *
   * @param string $id
   *   ID of field to assert.
   *
   * @deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use
   *   $this->assertSession()->checkboxChecked() instead.
   *
   * @see https://www.drupal.org/node/3129738
   */
  protected function assertFieldChecked($id) {
    @trigger_error('AssertLegacyTrait::assertFieldChecked() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->assertSession()->checkboxChecked() instead. See https://www.drupal.org/node/3129738', E_USER_DEPRECATED);
    $this->assertSession()->checkboxChecked($id);
  }

  /**
   * Asserts that a checkbox field in the current page is not checked.
   *
   * @param string $id
   *   ID of field to assert.
   *
   * @deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use
   *   $this->assertSession()->checkboxNotChecked() instead.
   *
   * @see https://www.drupal.org/node/3129738
   */
  protected function assertNoFieldChecked($id) {
    @trigger_error('AssertLegacyTrait::assertNoFieldChecked() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->assertSession()->checkboxNotChecked() instead. See https://www.drupal.org/node/3129738', E_USER_DEPRECATED);
    $this->assertSession()->checkboxNotChecked($id);
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
   *   messages with t().
   *
   * @deprecated in drupal:8.3.0 and is removed from drupal:10.0.0. Use
   *   $this->xpath() instead and check the values directly in the test.
   *
   * @see https://www.drupal.org/node/3129738
   */
  protected function assertFieldByXPath($xpath, $value = NULL, $message = '') {
    @trigger_error('AssertLegacyTrait::assertFieldByXPath() is deprecated in drupal:8.3.0 and is removed from drupal:10.0.0. Use $this->xpath() instead and check the values directly in the test. See https://www.drupal.org/node/3129738', E_USER_DEPRECATED);
    $fields = $this->xpath($xpath);

    $this->assertFieldsByValue($fields, $value, $message);
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
   *   messages with t().
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   *
   * @deprecated in drupal:8.3.0 and is removed from drupal:10.0.0. Use
   *   $this->xpath() instead and assert that the result is empty.
   *
   * @see https://www.drupal.org/node/3129738
   */
  protected function assertNoFieldByXPath($xpath, $value = NULL, $message = '') {
    @trigger_error('AssertLegacyTrait::assertNoFieldByXPath() is deprecated in drupal:8.3.0 and is removed from drupal:10.0.0. Use $this->xpath() instead and assert that the result is empty. See https://www.drupal.org/node/3129738', E_USER_DEPRECATED);
    $fields = $this->xpath($xpath);

    if (!empty($fields)) {
      if (isset($value)) {
        $found = FALSE;
        try {
          $this->assertFieldsByValue($fields, $value);
          $found = TRUE;
        }
        catch (\Exception $e) {
        }

        if ($found) {
          throw new ExpectationException(sprintf('The field resulting from %s was found with the provided value %s.', $xpath, $value), $this->getSession()->getDriver());
        }
      }
      else {
        throw new ExpectationException(sprintf('The field resulting from %s was found.', $xpath), $this->getSession()->getDriver());
      }
    }
  }

  /**
   * Asserts that a field exists in the current page with a given Xpath result.
   *
   * @param \Behat\Mink\Element\NodeElement[] $fields
   *   Xml elements.
   * @param string $value
   *   (optional) Value of the field to assert. You may pass in NULL (default) to skip
   *   checking the actual value, while still checking that the field exists.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages with t().
   *
   * @deprecated in drupal:8.3.0 and is removed from drupal:10.0.0. Use
   *   iteration over the fields yourself instead and directly check the values
   *   in the test.
   *
   * @see https://www.drupal.org/node/3129738
   */
  protected function assertFieldsByValue($fields, $value = NULL, $message = '') {
    @trigger_error('AssertLegacyTrait::assertFieldsByValue() is deprecated in drupal:8.3.0 and is removed from drupal:10.0.0. Use iteration over the fields yourself instead and directly check the values in the test. See https://www.drupal.org/node/3129738', E_USER_DEPRECATED);
    // If value specified then check array for match.
    $found = TRUE;
    if (isset($value)) {
      $found = FALSE;
      if ($fields) {
        foreach ($fields as $field) {
          if ($field->getAttribute('type') == 'checkbox') {
            if (is_bool($value)) {
              $found = $field->isChecked() == $value;
            }
            else {
              $found = TRUE;
            }
          }
          elseif ($field->getAttribute('value') == $value) {
            // Input element with correct value.
            $found = TRUE;
          }
          elseif ($field->find('xpath', '//option[@value = ' . (new Escaper())->escapeLiteral($value) . ' and @selected = "selected"]')) {
            // Select element with an option.
            $found = TRUE;
          }
          elseif ($field->getTagName() === 'textarea' && $field->getValue() == $value) {
            // Text area with correct text. Use getValue() here because
            // getText() would remove any newlines in the value.
            $found = TRUE;
          }
          elseif ($field->getTagName() !== 'input' && $field->getText() == $value) {
            $found = TRUE;
          }
        }
      }
    }
    $this->assertTrue($fields && $found, $message);
  }

  /**
   * Passes if the raw text IS found escaped on the loaded page, fail otherwise.
   *
   * Raw text refers to the raw HTML that the page generated.
   *
   * @param string $raw
   *   Raw (HTML) string to look for.
   *
   * @deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use
   *   $this->assertSession()->assertEscaped() instead.
   *
   * @see https://www.drupal.org/node/3129738
   */
  protected function assertEscaped($raw) {
    @trigger_error('AssertLegacyTrait::assertEscaped() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->assertSession()->assertEscaped() instead. See https://www.drupal.org/node/3129738', E_USER_DEPRECATED);
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
   * @deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use
   *   $this->assertSession()->assertNoEscaped() instead.
   *
   * @see https://www.drupal.org/node/3129738
   */
  protected function assertNoEscaped($raw) {
    @trigger_error('AssertLegacyTrait::assertNoEscaped() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->assertSession()->assertNoEscaped() instead. See https://www.drupal.org/node/3129738', E_USER_DEPRECATED);
    $this->assertSession()->assertNoEscaped($raw);
  }

  /**
   * Triggers a pass if the Perl regex pattern is found in the raw content.
   *
   * @param string $pattern
   *   Perl regex to look for including the regex delimiters.
   *
   * @deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use
   *   $this->assertSession()->responseMatches() instead.
   *
   * @see https://www.drupal.org/node/3129738
   */
  protected function assertPattern($pattern) {
    @trigger_error('AssertLegacyTrait::assertPattern() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->assertSession()->responseMatches() instead. See https://www.drupal.org/node/3129738', E_USER_DEPRECATED);
    $this->assertSession()->responseMatches($pattern);
  }

  /**
   * Triggers a pass if the Perl regex pattern is not found in the raw content.
   *
   * @param string $pattern
   *   Perl regex to look for including the regex delimiters.
   *
   * @deprecated in drupal:8.4.0 and is removed from drupal:10.0.0. Use
   *   $this->assertSession()->responseNotMatches() instead.
   *
   * @see https://www.drupal.org/node/3129738
   */
  protected function assertNoPattern($pattern) {
    @trigger_error('AssertLegacyTrait::assertNoPattern() is deprecated in drupal:8.4.0 and is removed from drupal:10.0.0. Use $this->assertSession()->responseNotMatches() instead. See https://www.drupal.org/node/3129738', E_USER_DEPRECATED);
    $this->assertSession()->responseNotMatches($pattern);
  }

  /**
   * Asserts whether an expected cache tag was present in the last response.
   *
   * @param string $expected_cache_tag
   *   The expected cache tag.
   *
   * @deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use
   *   $this->assertSession()->responseHeaderContains() instead.
   *
   * @see https://www.drupal.org/node/3129738
   */
  protected function assertCacheTag($expected_cache_tag) {
    @trigger_error('AssertLegacyTrait::assertCacheTag() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->assertSession()->responseHeaderContains() instead. See https://www.drupal.org/node/3129738', E_USER_DEPRECATED);
    $this->assertSession()->responseHeaderContains('X-Drupal-Cache-Tags', $expected_cache_tag);
  }

  /**
   * Asserts whether an expected cache tag was absent in the last response.
   *
   * @param string $cache_tag
   *   The cache tag to check.
   *
   * @deprecated in drupal:8.4.0 and is removed from drupal:10.0.0. Use
   *   $this->assertSession()->responseHeaderNotContains() instead.
   *
   * @see https://www.drupal.org/node/3129738
   */
  protected function assertNoCacheTag($cache_tag) {
    @trigger_error('AssertLegacyTrait::assertNoCacheTag() is deprecated in drupal:8.4.0 and is removed from drupal:10.0.0. Use $this->assertSession()->responseHeaderNotContains() instead. See https://www.drupal.org/node/3129738', E_USER_DEPRECATED);
    $this->assertSession()->responseHeaderNotContains('X-Drupal-Cache-Tags', $cache_tag);
  }

  /**
   * Checks that current response header equals value.
   *
   * @param string $name
   *   Name of header to assert.
   * @param string $value
   *   Value of the header to assert
   *
   * @deprecated in drupal:8.3.0 and is removed from drupal:10.0.0. Use
   *   $this->assertSession()->responseHeaderEquals() instead.
   *
   * @see https://www.drupal.org/node/3129738
   */
  protected function assertHeader($name, $value) {
    @trigger_error('AssertLegacyTrait::assertHeader() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->assertSession()->responseHeaderEquals() instead. See https://www.drupal.org/node/3129738', E_USER_DEPRECATED);
    $this->assertSession()->responseHeaderEquals($name, $value);
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
   * @deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use
   *   $this->assertSession()->buildXPathQuery() instead.
   *
   * @see https://www.drupal.org/node/3129738
   */
  protected function buildXPathQuery($xpath, array $args = []) {
    @trigger_error('AssertLegacyTrait::buildXPathQuery() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->assertSession()->buildXPathQuery() instead. See https://www.drupal.org/node/3129738', E_USER_DEPRECATED);
    return $this->assertSession()->buildXPathQuery($xpath, $args);
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
   *
   * @deprecated in drupal:8.5.0 and is removed from drupal:10.0.0. Use
   *   $this->getSession()->getPage()->findField() instead.
   *
   * @see https://www.drupal.org/node/3129738
   */
  protected function constructFieldXpath($attribute, $value) {
    @trigger_error('AssertLegacyTrait::constructFieldXpath() is deprecated in drupal:8.5.0 and is removed from drupal:10.0.0. Use $this->getSession()->getPage()->findField() instead. See https://www.drupal.org/node/3129738', E_USER_DEPRECATED);
    $xpath = '//textarea[@' . $attribute . '=:value]|//input[@' . $attribute . '=:value]|//select[@' . $attribute . '=:value]';
    return $this->buildXPathQuery($xpath, [':value' => $value]);
  }

  /**
   * Gets the current raw content.
   *
   * @deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use
   *   $this->getSession()->getPage()->getContent() instead.
   *
   * @see https://www.drupal.org/node/3129738
   */
  protected function getRawContent() {
    @trigger_error('AssertLegacyTrait::getRawContent() is deprecated in drupal:8.2.0 and is removed from drupal:10.0.0. Use $this->getSession()->getPage()->getContent() instead. See https://www.drupal.org/node/3129738', E_USER_DEPRECATED);
    return $this->getSession()->getPage()->getContent();
  }

  /**
   * Get all option elements, including nested options, in a select.
   *
   * @param \Behat\Mink\Element\NodeElement $element
   *   The element for which to get the options.
   *
   * @return \Behat\Mink\Element\NodeElement[]
   *   Option elements in select.
   *
   * @deprecated in drupal:8.5.0 and is removed from drupal:10.0.0. Use
   *   $element->findAll('xpath', 'option') instead.
   *
   * @see https://www.drupal.org/node/3129738
   */
  protected function getAllOptions(NodeElement $element) {
    @trigger_error('AssertLegacyTrait::getAllOptions() is deprecated in drupal:8.5.0 and is removed from drupal:10.0.0. Use $element->findAll(\'xpath\', \'option\') instead. See https://www.drupal.org/node/3129738', E_USER_DEPRECATED);
    return $element->findAll('xpath', '//option');
  }

}
