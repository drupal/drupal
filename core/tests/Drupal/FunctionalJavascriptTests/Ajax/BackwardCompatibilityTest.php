<?php

namespace Drupal\FunctionalJavascriptTests\Ajax;

use Drupal\FunctionalJavascriptTests\WebDriverTestBase;

/**
 * Tests the compatibility of the ajax.es6.js file.
 *
 * @group Ajax
 */
class BackwardCompatibilityTest extends WebDriverTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'js_ajax_test',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Ensures Drupal.Ajax.element_settings BC layer.
   */
  public function testAjaxBackwardCompatibility() {
    $this->drupalGet('/js_ajax_test');
    $this->click('#edit-test-button');

    $this->assertSession()
      ->waitForElement('css', '#js_ajax_test_form_element');
    $elements = $this->cssSelect('#js_ajax_test_form_element');
    $this->assertCount(1, $elements);
    $json = $elements[0]->getText();
    $data = json_decode($json, TRUE);
    $this->assertEquals([
      'element_settings' => 'catbro',
      'elementSettings' => 'catbro',
    ], $data);
  }

}
