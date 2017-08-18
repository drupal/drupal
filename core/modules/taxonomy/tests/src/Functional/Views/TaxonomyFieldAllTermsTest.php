<?php

namespace Drupal\Tests\taxonomy\Functional\Views;

use Drupal\views\Views;
use Drupal\taxonomy\Entity\Vocabulary;

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
  public static $testViews = ['taxonomy_all_terms_test'];

  /**
   * Tests the "all terms" field handler.
   */
  public function testViewsHandlerAllTermsField() {
    $this->term1->setName('<em>Markup</em>')->save();
    $view = Views::getView('taxonomy_all_terms_test');
    $this->executeView($view);
    $this->drupalGet('taxonomy_all_terms_test');

    $actual = $this->xpath('//a[@href="' . $this->term1->url() . '"]');
    $this->assertEqual(count($actual), 2, 'Correct number of taxonomy term1 links');
    $this->assertEqual($actual[0]->getText(), $this->term1->label());
    $this->assertEqual($actual[1]->getText(), $this->term1->label());
    $this->assertEscaped($this->term1->label());

    $actual = $this->xpath('//a[@href="' . $this->term2->url() . '"]');
    $this->assertEqual(count($actual), 2, 'Correct number of taxonomy term2 links');
    $this->assertEqual($actual[0]->getText(), $this->term2->label());
    $this->assertEqual($actual[1]->getText(), $this->term2->label());
  }

  /**
   * Tests token replacement in the "all terms" field handler.
   */
  public function testViewsHandlerAllTermsWithTokens() {
    $view = Views::getView('taxonomy_all_terms_test');
    $this->drupalGet('taxonomy_all_terms_token_test');

    // Term itself: {{ term_node_tid }}
    $this->assertText('Term: ' . $this->term1->getName());

    // The taxonomy term ID for the term: {{ term_node_tid__tid }}
    $this->assertText('The taxonomy term ID for the term: ' . $this->term1->id());

    // The taxonomy term name for the term: {{ term_node_tid__name }}
    $this->assertText('The taxonomy term name for the term: ' . $this->term1->getName());

    // The machine name for the vocabulary the term belongs to: {{ term_node_tid__vocabulary_vid }}
    $this->assertText('The machine name for the vocabulary the term belongs to: ' . $this->term1->bundle());

    // The name for the vocabulary the term belongs to: {{ term_node_tid__vocabulary }}
    $vocabulary = Vocabulary::load($this->term1->bundle());
    $this->assertText('The name for the vocabulary the term belongs to: ' . $vocabulary->label());
  }

}
