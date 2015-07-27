<?php

/**
 * @file
 * Contains \Drupal\taxonomy\Tests\Views\TaxonomyFieldAllTerms.
 */

namespace Drupal\taxonomy\Tests\Views;

use Drupal\views\Views;

/**
 * Tests the "All terms" taxonomy term field handler.
 *
 * @group taxonomy
 */
class TaxonomyFieldAllTermsTest extends TaxonomyTestBase {

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = array('taxonomy_all_terms_test');

  function testViewsHandlerAllTermsField() {
    $view = Views::getView('taxonomy_all_terms_test');
    $this->executeView($view);
    $this->drupalGet('taxonomy_all_terms_test');

    $actual = $this->xpath('//a[@href="' . $this->term1->url() . '"]');
    $this->assertEqual(count($actual), 2, 'Correct number of taxonomy term1 links');
    $this->assertEqual($actual[0]->__toString(), $this->term1->label());
    $this->assertEqual($actual[1]->__toString(), $this->term1->label());

    $actual = $this->xpath('//a[@href="' . $this->term2->url() . '"]');
    $this->assertEqual(count($actual), 2, 'Correct number of taxonomy term2 links');
    $this->assertEqual($actual[0]->__toString(), $this->term2->label());
    $this->assertEqual($actual[1]->__toString(), $this->term2->label());
  }

}
