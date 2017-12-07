<?php

namespace Drupal\Tests\taxonomy\Functional;

/**
 * Tests views contextual links on terms.
 *
 * @group taxonomy
 */
class TermContextualLinksTest extends TaxonomyTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'contextual',
  ];

  /**
   * Tests contextual links.
   */
  public function testTermContextualLinks() {
    $vocabulary = $this->createVocabulary();
    $term = $this->createTerm($vocabulary);

    $user = $this->drupalCreateUser([
      'administer taxonomy',
      'access contextual links',
    ]);
    $this->drupalLogin($user);

    $this->drupalGet('taxonomy/term/' . $term->id());
    $this->assertSession()->elementAttributeContains('css', 'div[data-contextual-id]', 'data-contextual-id', 'taxonomy_term:taxonomy_term=' . $term->id() . ':');
  }

}
