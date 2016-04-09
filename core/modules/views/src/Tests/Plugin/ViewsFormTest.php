<?php

namespace Drupal\views\Tests\Plugin;

use Drupal\simpletest\WebTestBase;

/**
 * Tests Views forms functionality.
 *
 * @group views
 */
class ViewsFormTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('action_bulk_test');

  /**
   * Tests the Views form wrapper.
   */
  public function testFormWrapper() {
    $this->drupalGet('test_bulk_form');
    // Ensure we have the form tag on the page.
    $xpath = $this->cssSelect('.views-form form');
    $this->assertIdentical(count($xpath), 1, 'There is one views form on the page.');
    // Ensure we don't have nested form elements.
    $result = (bool) preg_match('#<form[^>]*?>(?!/form).*<form#s', $this->getRawContent());
    $this->assertFalse($result, 'The views form element is not nested.');
  }
}
