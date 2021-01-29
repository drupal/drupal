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
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests the "all terms" field handler.
   */
  public function testViewsHandlerAllTermsField() {
    $this->term1->setName('<em>Markup</em>')->save();
    $view = Views::getView('taxonomy_all_terms_test');
    $this->executeView($view);
    $this->drupalGet('taxonomy_all_terms_test');

    $actual = $this->xpath('//a[@href="' . $this->term1->toUrl()->toString() . '"]');
    $this->assertCount(2, $actual, 'Correct number of taxonomy term1 links');
    $this->assertEqual($this->term1->label(), $actual[0]->getText());
    $this->assertEqual($this->term1->label(), $actual[1]->getText());
    $this->assertSession()->assertEscaped($this->term1->label());

    $actual = $this->xpath('//a[@href="' . $this->term2->toUrl()->toString() . '"]');
    $this->assertCount(2, $actual, 'Correct number of taxonomy term2 links');
    $this->assertEqual($this->term2->label(), $actual[0]->getText());
    $this->assertEqual($this->term2->label(), $actual[1]->getText());
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
