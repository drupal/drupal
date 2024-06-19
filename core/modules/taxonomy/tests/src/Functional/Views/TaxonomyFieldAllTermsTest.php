<?php

declare(strict_types=1);

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
  public function testViewsHandlerAllTermsField(): void {
    $this->term1->setName('<em>Markup</em>')->save();
    $view = Views::getView('taxonomy_all_terms_test');
    $this->executeView($view);
    $this->drupalGet('taxonomy_all_terms_test');

    // Test term1 links.
    $xpath = '//a[@href="' . $this->term1->toUrl()->toString() . '"]';
    $this->assertSession()->elementsCount('xpath', $xpath, 2);
    $links = $this->xpath($xpath);
    $this->assertEquals($this->term1->label(), $links[0]->getText());
    $this->assertEquals($this->term1->label(), $links[1]->getText());
    $this->assertSession()->assertEscaped($this->term1->label());

    // Test term2 links.
    $xpath = '//a[@href="' . $this->term2->toUrl()->toString() . '"]';
    $this->assertSession()->elementsCount('xpath', $xpath, 2);
    $links = $this->xpath($xpath);
    $this->assertEquals($this->term2->label(), $links[0]->getText());
    $this->assertEquals($this->term2->label(), $links[1]->getText());
  }

  /**
   * Tests token replacement in the "all terms" field handler.
   */
  public function testViewsHandlerAllTermsWithTokens(): void {
    $view = Views::getView('taxonomy_all_terms_test');
    $this->drupalGet('taxonomy_all_terms_token_test');

    // Term itself: {{ term_node_tid }}
    $this->assertSession()->pageTextContains('Term: ' . $this->term1->getName());

    // The taxonomy term ID for the term: {{ term_node_tid__tid }}
    $this->assertSession()->pageTextContains('The taxonomy term ID for the term: ' . $this->term1->id());

    // The taxonomy term name for the term: {{ term_node_tid__name }}
    $this->assertSession()->pageTextContains('The taxonomy term name for the term: ' . $this->term1->getName());

    // The machine name for the vocabulary the term belongs to: {{ term_node_tid__vocabulary_vid }}
    $this->assertSession()->pageTextContains('The machine name for the vocabulary the term belongs to: ' . $this->term1->bundle());

    // The name for the vocabulary the term belongs to: {{ term_node_tid__vocabulary }}
    $vocabulary = Vocabulary::load($this->term1->bundle());
    $this->assertSession()->pageTextContains('The name for the vocabulary the term belongs to: ' . $vocabulary->label());
  }

}
