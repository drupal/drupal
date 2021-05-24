<?php

namespace Drupal\Tests;

use Behat\Mink\Element\TraversableElement;
use Drupal\Component\Utility\Xss;

/**
 * Document element.
 *
 * This is largely a copy of \Behat\Mink\Element\DocumentElement. This fixes the
 * ::getText() method to remove script tags inside the body element.
 *
 * @see \Behat\Mink\Element\DocumentElement
 * @internal
 */
class DocumentElement extends TraversableElement {

  /**
   * Returns XPath for handled element.
   *
   * @return string
   */
  public function getXpath() {
    return '//html';
  }

  /**
   * Returns document content.
   *
   * @return string
   */
  public function getContent() {
    return trim($this->getDriver()->getContent());
  }

  /**
   * Check whether document has specified content.
   *
   * @param string $content
   *
   * @return bool
   */
  public function hasContent($content) {
    return $this->has('named', ['content', $content]);
  }

  /**
   * {@inheritdoc}
   */
  public function getText() {
    // Trying to simulate what the user sees, given that, it removes:
    // - all text inside the head tags
    // - Drupal settings json.
    $raw_content = preg_replace([
      '@<head>(.+?)</head>@si',
      '@<script type="application/json" data-drupal-selector="drupal-settings-json">([^<]*)</script>@',
    ], '', $this->getContent());
    // Use Xss::filter() to:
    // - remove inline JavaScript
    // - fix all HTML entities
    // - remove dangerous protocols
    // - filter out all HTML tags, as they are not visible in a normal browser.
    return Xss::filter($raw_content, []);
  }

}
