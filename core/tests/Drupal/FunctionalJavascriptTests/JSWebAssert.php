<?php

namespace Drupal\FunctionalJavascriptTests;

use Behat\Mink\Element\NodeElement;
use Behat\Mink\Exception\ElementHtmlException;
use Behat\Mink\Exception\ElementNotFoundException;
use Behat\Mink\Exception\UnsupportedDriverActionException;
use Drupal\Tests\WebAssert;

/**
 * Defines a class with methods for asserting presence of elements during tests.
 */
class JSWebAssert extends WebAssert {

  /**
   * Waits for AJAX request to be completed.
   *
   * @param int $timeout
   *   (Optional) Timeout in milliseconds, defaults to 10000.
   * @param string $message
   *   (optional) A message for exception.
   *
   * @throws \RuntimeException
   *   When the request is not completed. If left blank, a default message will
   *   be displayed.
   */
  public function assertWaitOnAjaxRequest($timeout = 10000, $message = 'Unable to complete AJAX request.') {
    $result = $this->session->wait($timeout, '(typeof(jQuery)=="undefined" || (0 === jQuery.active && 0 === jQuery(\':animated\').length))');
    if (!$result) {
      throw new \RuntimeException($message);
    }
  }

  /**
   * Waits for the jQuery autocomplete delay duration.
   *
   * @see https://api.jqueryui.com/autocomplete/#option-delay
   */
  public function waitOnAutocomplete() {
    // Drupal is using the default delay value of 300 milliseconds.
    $this->session->wait(300);
    $this->assertWaitOnAjaxRequest();
  }

  /**
   * Test that a node, or it's specific corner, is visible in the viewport.
   *
   * Note: Always set the viewport size. This can be done with a PhantomJS
   * startup parameter or in your test with \Behat\Mink\Session->resizeWindow().
   * Drupal CI Javascript tests by default use a viewport of 1024x768px.
   *
   * @param string $selector_type
   *   The element selector type (CSS, XPath).
   * @param string|array $selector
   *   The element selector. Note: the first found element is used.
   * @param bool|string $corner
   *   (Optional) The corner to test:
   *   topLeft, topRight, bottomRight, bottomLeft.
   *   Or FALSE to check the complete element (default).
   * @param string $message
   *   (optional) A message for the exception.
   *
   * @throws \Behat\Mink\Exception\ElementHtmlException
   *   When the element doesn't exist.
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   *   When the element is not visible in the viewport.
   */
  public function assertVisibleInViewport($selector_type, $selector, $corner = FALSE, $message = 'Element is not visible in the viewport.') {
    $node = $this->session->getPage()->find($selector_type, $selector);
    if ($node === NULL) {
      if (is_array($selector)) {
        $selector = implode(' ', $selector);
      }
      throw new ElementNotFoundException($this->session->getDriver(), 'element', $selector_type, $selector);
    }

    // Check if the node is visible on the page, which is a prerequisite of
    // being visible in the viewport.
    if (!$node->isVisible()) {
      throw new ElementHtmlException($message, $this->session->getDriver(), $node);
    }

    $result = $this->checkNodeVisibilityInViewport($node, $corner);

    if (!$result) {
      throw new ElementHtmlException($message, $this->session->getDriver(), $node);
    }
  }

  /**
   * Test that a node, or its specific corner, is not visible in the viewport.
   *
   * Note: the node should exist in the page, otherwise this assertion fails.
   *
   * @param string $selector_type
   *   The element selector type (CSS, XPath).
   * @param string|array $selector
   *   The element selector. Note: the first found element is used.
   * @param bool|string $corner
   *   (Optional) Corner to test: topLeft, topRight, bottomRight, bottomLeft.
   *   Or FALSE to check the complete element (default).
   * @param string $message
   *   (optional) A message for the exception.
   *
   * @throws \Behat\Mink\Exception\ElementHtmlException
   *   When the element doesn't exist.
   * @throws \Behat\Mink\Exception\ElementNotFoundException
   *   When the element is not visible in the viewport.
   *
   * @see \Drupal\FunctionalJavascriptTests\JSWebAssert::assertVisibleInViewport()
   */
  public function assertNotVisibleInViewport($selector_type, $selector, $corner = FALSE, $message = 'Element is visible in the viewport.') {
    $node = $this->session->getPage()->find($selector_type, $selector);
    if ($node === NULL) {
      if (is_array($selector)) {
        $selector = implode(' ', $selector);
      }
      throw new ElementNotFoundException($this->session->getDriver(), 'element', $selector_type, $selector);
    }

    $result = $this->checkNodeVisibilityInViewport($node, $corner);

    if ($result) {
      throw new ElementHtmlException($message, $this->session->getDriver(), $node);
    }
  }

  /**
   * Check the visibility of a node, or it's specific corner.
   *
   * @param \Behat\Mink\Element\NodeElement $node
   *   A valid node.
   * @param bool|string $corner
   *   (Optional) Corner to test: topLeft, topRight, bottomRight, bottomLeft.
   *   Or FALSE to check the complete element (default).
   *
   * @return bool
   *   Returns TRUE if the node is visible in the viewport, FALSE otherwise.
   *
   * @throws \Behat\Mink\Exception\UnsupportedDriverActionException
   *   When an invalid corner specification is given.
   */
  private function checkNodeVisibilityInViewport(NodeElement $node, $corner = FALSE) {
    $xpath = $node->getXpath();

    // Build the Javascript to test if the complete element or a specific corner
    // is in the viewport.
    switch ($corner) {
      case 'topLeft':
        $test_javascript_function = <<<JS
          function t(r, lx, ly) {
            return (
              r.top >= 0 &&
              r.top <= ly &&
              r.left >= 0 &&
              r.left <= lx
            )
          }
JS;
        break;

      case 'topRight':
        $test_javascript_function = <<<JS
          function t(r, lx, ly) {
            return (
              r.top >= 0 &&
              r.top <= ly &&
              r.right >= 0 &&
              r.right <= lx
            );
          }
JS;
        break;

      case 'bottomRight':
        $test_javascript_function = <<<JS
          function t(r, lx, ly) {
            return (
              r.bottom >= 0 &&
              r.bottom <= ly &&
              r.right >= 0 &&
              r.right <= lx
            );
          }
JS;
        break;

      case 'bottomLeft':
        $test_javascript_function = <<<JS
          function t(r, lx, ly) {
            return (
              r.bottom >= 0 &&
              r.bottom <= ly &&
              r.left >= 0 &&
              r.left <= lx
            );
          }
JS;
        break;

      case FALSE:
        $test_javascript_function = <<<JS
          function t(r, lx, ly) {
            return (
              r.top >= 0 &&
              r.left >= 0 &&
              r.bottom <= ly &&
              r.right <= lx
            );
          }
JS;
        break;

      // Throw an exception if an invalid corner parameter is given.
      default:
        throw new UnsupportedDriverActionException($corner, $this->session->getDriver());
    }

    // Build the full Javascript test. The shared logic gets the corner
    // specific test logic injected.
    $full_javascript_visibility_test = <<<JS
      (function(t){
        var w = window,
        d = document,
        e = d.documentElement,
        n = d.evaluate("$xpath", d, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null).singleNodeValue,
        r = n.getBoundingClientRect(),
        lx = (w.innerWidth || e.clientWidth),
        ly = (w.innerHeight || e.clientHeight);

        return t(r, lx, ly);
      }($test_javascript_function));
JS;

    // Check the visibility by injecting and executing the full Javascript test
    // script in the page.
    return $this->session->evaluateScript($full_javascript_visibility_test);
  }

}
