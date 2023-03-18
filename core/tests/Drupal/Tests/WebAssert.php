<?php

namespace Drupal\Tests;

use Behat\Mink\Exception\ExpectationException;
use Behat\Mink\Exception\ResponseTextException;
use Behat\Mink\WebAssert as MinkWebAssert;
use Behat\Mink\Element\TraversableElement;
use Behat\Mink\Exception\ElementNotFoundException;
use Behat\Mink\Session;
use Drupal\Component\Utility\Html;
use Drupal\Core\Url;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\AssertionFailedError;
use PHPUnit\Framework\Constraint\ArrayHasKey;
use PHPUnit\Framework\Constraint\IsIdentical;
use PHPUnit\Framework\Constraint\IsEqual;
use PHPUnit\Framework\Constraint\LogicalNot;

/**
 * Defines a class with methods for asserting presence of elements during tests.
 */
class WebAssert extends MinkWebAssert {

  /**
   * The absolute URL of the site under test.
   *
   * @var string
   */
  protected $baseUrl = '';

  /**
   * Constructor.
   *
   * @param \Behat\Mink\Session $session
   *   The Behat session object;
   * @param string $base_url
   *   The base URL of the site under test.
   */
  public function __construct(Session $session, $base_url = '') {
    parent::__construct($session);
    $this->baseUrl = $base_url;
  }

  /**
   * {@inheritdoc}
   */
  protected function cleanUrl($url, $include_query = FALSE) {
    if ($url instanceof Url) {
      $url = $url->setAbsolute()->toString();
    }
    // Strip the base URL from the beginning for absolute URLs.
    if ($this->baseUrl !== '' && str_starts_with($url, $this->baseUrl)) {
      $url = substr($url, strlen($this->baseUrl));
    }
    $parts = parse_url($url);
    // Make sure there is a forward slash at the beginning of relative URLs for
    // consistency.
    if (empty($parts['host']) && !str_starts_with($url, '/')) {
      $parts['path'] = '/' . $parts['path'];
    }
    $fragment = empty($parts['fragment']) ? '' : '#' . $parts['fragment'];
    $path = empty($parts['path']) ? '/' : $parts['path'];
    $query = $include_query && !empty($parts['query']) ? '?' . $parts['query'] : '';

    return preg_replace('/^\/[^\.\/]+\.php\//', '/', $path) . $query . $fragment;
  }

  /**
   * Asserts that the current response header has a specific entry.
   *
   * @param string $name
   *   The name of the header entry to check existence of.
   * @param string $message
   *   The failure message.
   */
  public function responseHeaderExists(string $name, string $message = ''): void {
    if ($message === '') {
      $message = "Failed asserting that the response has a '$name' header.";
    }
    $headers = $this->session->getResponseHeaders();
    $constraint = new ArrayHasKey($name);
    Assert::assertThat($headers, $constraint, $message);
  }

  /**
   * Asserts that the current response header does not have a specific entry.
   *
   * @param string $name
   *   The name of the header entry to check existence of.
   * @param string $message
   *   The failure message.
   */
  public function responseHeaderDoesNotExist(string $name, string $message = ''): void {
    if ($message === '') {
      $message = "Failed asserting that the response does not have a '$name' header.";
    }
    $headers = $this->session->getResponseHeaders();
    $constraint = new LogicalNot(
      new ArrayHasKey($name)
    );
    Assert::assertThat($headers, $constraint, $message);
  }

  /**
   * Asserts that the current page text matches regex a number of times.
   *
   * @param int $count
   *   The number of times the pattern is expected to be present.
   * @param string $regex
   *   The regex pattern.
   * @param string $message
   *   (Optional) the failure message.
   */
  public function pageTextMatchesCount(int $count, string $regex, string $message = ''): void {
    $actual = preg_replace('/\s+/u', ' ', $this->session->getPage()->getText());
    $matches = preg_match_all($regex, $actual);
    if ($message === '') {
      $message = "Failed asserting that the page matches the pattern '$regex' $count time(s), $matches found.";
    }
    $constraint = new IsIdentical($count);
    Assert::assertThat($matches, $constraint, $message);
  }

  /**
   * Checks that specific button exists on the current page.
   *
   * @param string $button
   *   One of id|name|label|value for the button.
   * @param \Behat\Mink\Element\TraversableElement $container
   *   (optional) The document to check against. Defaults to the current page.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The matching element.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   *   When the element doesn't exist.
   */
  public function buttonExists($button, TraversableElement $container = NULL) {
    $container = $container ?: $this->session->getPage();
    $node = $container->findButton($button);

    if ($node === NULL) {
      throw new ElementNotFoundException($this->session->getDriver(), 'button', 'id|name|label|value', $button);
    }

    return $node;
  }

  /**
   * Checks that the specific button does NOT exist on the current page.
   *
   * @param string $button
   *   One of id|name|label|value for the button.
   * @param \Behat\Mink\Element\TraversableElement $container
   *   (optional) The document to check against. Defaults to the current page.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   *   When the button exists.
   */
  public function buttonNotExists($button, TraversableElement $container = NULL) {
    $container = $container ?: $this->session->getPage();
    $node = $container->findButton($button);

    $this->assert(NULL === $node, sprintf('A button "%s" appears on this page, but it should not.', $button));
  }

  /**
   * Checks that specific select field exists on the current page.
   *
   * @param string $select
   *   One of id|name|label|value for the select field.
   * @param \Behat\Mink\Element\TraversableElement $container
   *   (optional) The document to check against. Defaults to the current page.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The matching element
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   *   When the element doesn't exist.
   */
  public function selectExists($select, TraversableElement $container = NULL) {
    $container = $container ?: $this->session->getPage();
    $node = $container->find('named', [
      'select',
      $select,
    ]);

    if ($node === NULL) {
      throw new ElementNotFoundException($this->session->getDriver(), 'select', 'id|name|label|value', $select);
    }

    return $node;
  }

  /**
   * Checks that specific option in a select field exists on the current page.
   *
   * @param string $select
   *   One of id|name|label|value for the select field.
   * @param string $option
   *   The option value.
   * @param \Behat\Mink\Element\TraversableElement $container
   *   (optional) The document to check against. Defaults to the current page.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The matching option element
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   *   When the element doesn't exist.
   */
  public function optionExists($select, $option, TraversableElement $container = NULL) {
    $container = $container ?: $this->session->getPage();
    $select_field = $container->find('named', [
      'select',
      $select,
    ]);

    if ($select_field === NULL) {
      throw new ElementNotFoundException($this->session->getDriver(), 'select', 'id|name|label|value', $select);
    }

    $option_field = $select_field->find('named_exact', ['option', $option]);

    if ($option_field === NULL) {
      throw new ElementNotFoundException($this->session->getDriver(), 'select', 'id|name|label|value', $option);
    }

    return $option_field;
  }

  /**
   * Checks that an option in a select field does NOT exist on the current page.
   *
   * @param string $select
   *   One of id|name|label|value for the select field.
   * @param string $option
   *   The option value that should not exist.
   * @param \Behat\Mink\Element\TraversableElement $container
   *   (optional) The document to check against. Defaults to the current page.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   *   When the select element doesn't exist.
   */
  public function optionNotExists($select, $option, TraversableElement $container = NULL) {
    $container = $container ?: $this->session->getPage();
    $select_field = $container->find('named', [
      'select',
      $select,
    ]);

    if ($select_field === NULL) {
      throw new ElementNotFoundException($this->session->getDriver(), 'select', 'id|name|label|value', $select);
    }

    $option_field = $select_field->find('named_exact', ['option', $option]);

    $this->assert($option_field === NULL, sprintf('An option "%s" exists in select "%s", but it should not.', $option, $select));
  }

  /**
   * Pass if the page title is the given string.
   *
   * @param string $expected_title
   *   The string the page title should be.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   *   Thrown when element doesn't exist, or the title is a different one.
   */
  public function titleEquals($expected_title) {
    $title_element = $this->session->getPage()->find('css', 'title');
    if (!$title_element) {
      throw new ExpectationException('No title element found on the page', $this->session->getDriver());
    }
    $actual_title = $title_element->getText();
    $this->assert($expected_title === $actual_title, 'Title found');
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
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   *   Thrown when element doesn't exist, or the link label is a different one.
   */
  public function linkExists($label, $index = 0, $message = '') {
    $message = ($message ? $message : strtr('Link with label %label not found.', ['%label' => $label]));
    $links = $this->session->getPage()->findAll('named', ['link', $label]);
    $this->assert(!empty($links[$index]), $message);
  }

  /**
   * Passes if a link with the exactly specified label is found.
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
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   *   Thrown when element doesn't exist, or the link label is a different one.
   */
  public function linkExistsExact($label, $index = 0, $message = '') {
    $message = ($message ? $message : strtr('Link with label %label not found.', ['%label' => $label]));
    $links = $this->session->getPage()->findAll('named_exact', ['link', $label]);
    $this->assert(!empty($links[$index]), $message);
  }

  /**
   * Passes if a link with the specified label is not found.
   *
   * An optional link index may be passed.
   *
   * @param string $label
   *   Text between the anchor tags.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use strtr() to embed variables in the message text, not
   *   t(). If left blank, a default message will be displayed.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   *   Thrown when element doesn't exist, or the link label is a different one.
   */
  public function linkNotExists($label, $message = '') {
    $message = ($message ? $message : strtr('Link with label %label found.', ['%label' => $label]));
    $links = $this->session->getPage()->findAll('named', ['link', $label]);
    $this->assert(empty($links), $message);
  }

  /**
   * Passes if a link with the exactly specified label is not found.
   *
   * An optional link index may be passed.
   *
   * @param string $label
   *   Text between the anchor tags.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use strtr() to embed variables in the message text, not
   *   t(). If left blank, a default message will be displayed.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   *   Thrown when element doesn't exist, or the link label is a different one.
   */
  public function linkNotExistsExact($label, $message = '') {
    $message = ($message ? $message : strtr('Link with label %label found.', ['%label' => $label]));
    $links = $this->session->getPage()->findAll('named_exact', ['link', $label]);
    $this->assert(empty($links), $message);
  }

  /**
   * Passes if a link containing a given href (part) is found.
   *
   * @param string $href
   *   The full or partial value of the 'href' attribute of the anchor tag.
   * @param int $index
   *   Link position counting from zero.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use \Drupal\Component\Render\FormattableMarkup to embed
   *   variables in the message text, not t(). If left blank, a default message
   *   will be displayed.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   *   Thrown when element doesn't exist, or the link label is a different one.
   */
  public function linkByHrefExists($href, $index = 0, $message = '') {
    $xpath = $this->buildXPathQuery('//a[contains(@href, :href)]', [':href' => $href]);
    $message = ($message ? $message : strtr('No link containing href %href found.', ['%href' => $href]));
    $links = $this->session->getPage()->findAll('xpath', $xpath);
    $this->assert(!empty($links[$index]), $message);
  }

  /**
   * Passes if a link containing a given href (part) is not found.
   *
   * @param string $href
   *   The full or partial value of the 'href' attribute of the anchor tag.
   * @param string $message
   *   (optional) A message to display with the assertion. Do not translate
   *   messages: use \Drupal\Component\Render\FormattableMarkup to embed
   *   variables in the message text, not t(). If left blank, a default message
   *   will be displayed.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   *   Thrown when element doesn't exist, or the link label is a different one.
   */
  public function linkByHrefNotExists($href, $message = '') {
    $xpath = $this->buildXPathQuery('//a[contains(@href, :href)]', [':href' => $href]);
    $message = ($message ? $message : strtr('Link containing href %href found.', ['%href' => $href]));
    $links = $this->session->getPage()->findAll('xpath', $xpath);
    $this->assert(empty($links), $message);
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
  public function buildXPathQuery($xpath, array $args = []) {
    // Replace placeholders.
    foreach ($args as $placeholder => $value) {
      if (is_object($value)) {
        throw new \InvalidArgumentException('Just pass in scalar values for $args and remove all t() calls from your test.');
      }
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
   * Passes if the raw text IS NOT found escaped on the loaded page.
   *
   * Raw text refers to the raw HTML that the page generated.
   *
   * @param string $raw
   *   Raw (HTML) string to look for.
   */
  public function assertNoEscaped($raw) {
    $this->responseNotContains(Html::escape($raw));
  }

  /**
   * Passes if the raw text IS found escaped on the loaded page.
   *
   * Raw text refers to the raw HTML that the page generated.
   *
   * @param string $raw
   *   Raw (HTML) string to look for.
   */
  public function assertEscaped($raw) {
    $this->responseContains(Html::escape($raw));
  }

  /**
   * Checks that page HTML (response content) contains text.
   *
   * @param string|object $text
   *   Text value. Any non-string value will be cast to string.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function responseContains($text) {
    parent::responseContains((string) $text);
  }

  /**
   * Checks that page HTML (response content) does not contains text.
   *
   * @param string|object $text
   *   Text value. Any non-string value will be cast to string.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function responseNotContains($text) {
    parent::responseNotContains((string) $text);
  }

  /**
   * Asserts a condition.
   *
   * The parent method is overridden because it is a private method.
   *
   * @param bool $condition
   *   The condition.
   * @param string $message
   *   The success message.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   *   When the condition is not fulfilled.
   */
  public function assert($condition, $message) {
    if ($condition) {
      return;
    }

    throw new ExpectationException($message, $this->session->getDriver());
  }

  /**
   * Checks that a given form field element is disabled.
   *
   * @param string $field
   *   One of id|name|label|value for the field.
   * @param \Behat\Mink\Element\TraversableElement $container
   *   (optional) The document to check against. Defaults to the current page.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The matching element.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function fieldDisabled($field, TraversableElement $container = NULL) {
    $container = $container ?: $this->session->getPage();
    $node = $container->findField($field);

    if ($node === NULL) {
      throw new ElementNotFoundException($this->session->getDriver(), 'field', 'id|name|label|value', $field);
    }

    if (!$node->hasAttribute('disabled')) {
      throw new ExpectationException("Field $field is disabled", $this->session->getDriver());
    }

    return $node;
  }

  /**
   * Checks that a given form field element is enabled.
   *
   * @param string $field
   *   One of id|name|label|value for the field.
   * @param \Behat\Mink\Element\TraversableElement $container
   *   (optional) The document to check against. Defaults to the current page.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The matching element.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function fieldEnabled($field, TraversableElement $container = NULL) {
    $container = $container ?: $this->session->getPage();
    $node = $container->findField($field);

    if ($node === NULL) {
      throw new ElementNotFoundException($this->session->getDriver(), 'field', 'id|name|label|value', $field);
    }

    if ($node->hasAttribute('disabled')) {
      throw new ExpectationException("Field $field is not enabled", $this->session->getDriver());
    }

    return $node;
  }

  /**
   * Checks that specific hidden field exists.
   *
   * @param string $field
   *   One of id|name|value for the hidden field.
   * @param \Behat\Mink\Element\TraversableElement $container
   *   (optional) The document to check against. Defaults to the current page.
   *
   * @return \Behat\Mink\Element\NodeElement
   *   The matching element.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   */
  public function hiddenFieldExists($field, TraversableElement $container = NULL) {
    $container = $container ?: $this->session->getPage();
    if ($node = $container->find('hidden_field_selector', ['hidden_field', $field])) {
      return $node;
    }
    throw new ElementNotFoundException($this->session->getDriver(), 'form hidden field', 'id|name|value', $field);
  }

  /**
   * Checks that specific hidden field does not exist.
   *
   * @param string $field
   *   One of id|name|value for the hidden field.
   * @param \Behat\Mink\Element\TraversableElement $container
   *   (optional) The document to check against. Defaults to the current page.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function hiddenFieldNotExists($field, TraversableElement $container = NULL) {
    $container = $container ?: $this->session->getPage();
    $node = $container->find('hidden_field_selector', ['hidden_field', $field]);
    $this->assert($node === NULL, "A hidden field '$field' exists on this page, but it should not.");
  }

  /**
   * Checks that specific hidden field have provided value.
   *
   * @param string $field
   *   One of id|name|value for the hidden field.
   * @param string $value
   *   The hidden field value that needs to be checked.
   * @param \Behat\Mink\Element\TraversableElement $container
   *   (optional) The document to check against. Defaults to the current page.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function hiddenFieldValueEquals($field, $value, TraversableElement $container = NULL) {
    $node = $this->hiddenFieldExists($field, $container);
    $actual = $node->getValue();
    $regex = '/^' . preg_quote($value, '/') . '$/ui';
    $message = "The hidden field '$field' value is '$actual', but '$value' expected.";
    $this->assert((bool) preg_match($regex, $actual), $message);
  }

  /**
   * Checks that specific hidden field doesn't have the provided value.
   *
   * @param string $field
   *   One of id|name|value for the hidden field.
   * @param string $value
   *   The hidden field value that needs to be checked.
   * @param \Behat\Mink\Element\TraversableElement $container
   *   (optional) The document to check against. Defaults to the current page.
   *
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function hiddenFieldValueNotEquals($field, $value, TraversableElement $container = NULL) {
    $node = $this->hiddenFieldExists($field, $container);
    $actual = $node->getValue();
    $regex = '/^' . preg_quote($value, '/') . '$/ui';
    $message = "The hidden field '$field' value is '$actual', but it should not be.";
    $this->assert(!preg_match($regex, $actual), $message);
  }

  /**
   * Checks that current page contains text only once.
   *
   * @param string $text
   *   The string to look for.
   *
   * @see \Behat\Mink\WebAssert::pageTextContains()
   */
  public function pageTextContainsOnce($text) {
    $regex = '/' . preg_quote($text, '/') . '/ui';
    try {
      $this->pageTextMatchesCount(1, $regex);
    }
    catch (AssertionFailedError $e) {
      throw new ResponseTextException($e->getMessage(), $this->session->getDriver());
    }
  }

  /**
   * Asserts that each HTML ID is used for just a single element on the page.
   *
   * @throws \Behat\Mink\Exception\ExpectationException
   */
  public function pageContainsNoDuplicateId() {
    $seen_ids = [];
    foreach ($this->session->getPage()->findAll('xpath', '//*[@id]') as $element) {
      $id = $element->getAttribute('id');
      if (isset($seen_ids[$id])) {
        throw new ExpectationException(sprintf('The page contains a duplicate HTML ID "%s".', $id), $this->session->getDriver());
      }
      $seen_ids[$id] = TRUE;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function addressEquals($page) {
    $expected = $this->cleanUrl($page, TRUE);
    $actual = $this->cleanUrl($this->session->getCurrentUrl(), str_contains($expected, '?'));

    $this->assert($actual === $expected, sprintf('Current page is "%s", but "%s" expected.', $actual, $expected));
  }

  /**
   * {@inheritdoc}
   */
  public function addressNotEquals($page) {
    $expected = $this->cleanUrl($page, TRUE);
    $actual = $this->cleanUrl($this->session->getCurrentUrl(), str_contains($expected, '?'));

    $this->assert($actual !== $expected, sprintf('Current page is "%s", but should not be.', $actual));
  }

  /**
   * Asserts a specific element's text equals an expected text.
   *
   * @param string $selectorType
   *   Element selector type (css, xpath).
   * @param string|array $selector
   *   Element selector.
   * @param string $text
   *   Expected text.
   */
  public function elementTextEquals(string $selectorType, $selector, string $text): void {
    $selector_string = is_array($selector) ? '[' . implode(', ', $selector) . ']' : $selector;
    $message = "Failed asserting that the text of the element identified by '$selector_string' equals '$text'.";
    $constraint = new IsEqual($text);
    Assert::assertThat($this->elementExists($selectorType, $selector)->getText(), $constraint, $message);
  }

  /**
   * Asserts that a status message exists.
   *
   * @param string|null $type
   *   The optional message type: status, error, or warning.
   */
  public function statusMessageExists(string $type = NULL): void {
    $selector = $this->buildStatusMessageSelector(NULL, $type);
    try {
      $this->elementExists('xpath', $selector);
    }
    catch (ExpectationException $e) {
      Assert::fail($e->getMessage());
    }
  }

  /**
   * Asserts that a status message does not exist.
   *
   * @param string|null $type
   *   The optional message type: status, error, or warning.
   */
  public function statusMessageNotExists(string $type = NULL): void {
    $selector = $this->buildStatusMessageSelector(NULL, $type);
    try {
      $this->elementNotExists('xpath', $selector);
    }
    catch (ExpectationException $e) {
      Assert::fail($e->getMessage());
    }
  }

  /**
   * Asserts that a status message containing a given string exists.
   *
   * @param string $message
   *   The partial message to assert.
   * @param string|null $type
   *   The optional message type: status, error, or warning.
   */
  public function statusMessageContains(string $message, string $type = NULL): void {
    $selector = $this->buildStatusMessageSelector($message, $type);
    try {
      $this->elementExists('xpath', $selector);
    }
    catch (ExpectationException $e) {
      Assert::fail($e->getMessage());
    }
  }

  /**
   * Asserts that a status message containing a given string does not exist.
   *
   * @param string $message
   *   The partial message to assert.
   * @param string|null $type
   *   The optional message type: status, error, or warning.
   */
  public function statusMessageNotContains(string $message, string $type = NULL): void {
    $selector = $this->buildStatusMessageSelector($message, $type);
    try {
      $this->elementNotExists('xpath', $selector);
    }
    catch (ExpectationException $e) {
      Assert::fail($e->getMessage());
    }
  }

  /**
   * Builds a xpath selector for a message with given type and text.
   *
   * The selector is designed to work with the status-messages.html.twig
   * template in the system module.
   *
   * See Drupal\Core\Render\Element\StatusMessages for aria label definition.
   *
   * @param string|null $message
   *   The optional message or partial message to assert.
   * @param string|null $type
   *   The optional message type: status, error, or warning.
   *
   * @return string
   *   The xpath selector for the message.
   *
   * @throws \InvalidArgumentException
   *   Thrown when $type is not an allowed type.
   */
  protected function buildStatusMessageSelector(string $message = NULL, string $type = NULL): string {
    $allowed_types = [
      'status',
      'error',
      'warning',
      NULL,
    ];
    if (!in_array($type, $allowed_types, TRUE)) {
      throw new \InvalidArgumentException(sprintf("If a status message type is specified, the allowed values are 'status', 'error', 'warning'. The value provided was '%s'.", $type));
    }
    $selector = '//div[@data-drupal-messages]';
    $aria_label = NULL;
    switch ($type) {
      case 'status':
        $aria_label = 'Status message';
        break;

      case 'error':
        $aria_label = 'Error message';
        break;

      case 'warning':
        $aria_label = 'Warning message';
    }

    if ($message && $aria_label && $type) {
      $selector = $this->buildXPathQuery($selector . '//div[(contains(@aria-label, :aria_label) or contains(@aria-labelledby, :type)) and contains(., :message)]', [
        // Value of the 'aria-label' attribute, used in Stark.
        ':aria_label' => $aria_label,
        // Value of the 'aria-labelledby' attribute, used in Claro and Olivero.
        ':type' => $type,
        ':message' => $message,
      ]);
    }
    elseif ($message) {
      $selector = $this->buildXPathQuery($selector . '//div[contains(., :message)]', [
        ':message' => $message,
      ]);
    }
    elseif ($aria_label) {
      $selector = $this->buildXPathQuery($selector . '//div[@aria-label=:aria_label]', [
        ':aria_label' => $aria_label,
      ]);
    }

    return $selector;
  }

}
