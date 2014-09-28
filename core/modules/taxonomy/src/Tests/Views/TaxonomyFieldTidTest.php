<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Tests\Views\TaxonomyFieldTidTest.
 */

namespace Drupal\taxonomy\Tests\Views;

use Drupal\views\Views;

/**
 * Tests the taxonomy term TID field handler.
 *
 * @group taxonomy
 */
class TaxonomyFieldTidTest extends TaxonomyTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('test_taxonomy_tid_field');

  function testViewsHandlerTidField() {
    $view = Views::getView('test_taxonomy_tid_field');
    $this->executeView($view);

    $actual = $view->field['name']->advancedRender($view->result[0]);
    $expected = \Drupal::linkGenerator()->generateFromUrl($this->term1->label(), $this->term1->urlInfo());

    $this->assertEqual($expected, $actual);
  }

}
