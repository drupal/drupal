<?php

namespace Drupal\Tests\taxonomy\Functional\Views;

use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Tests the vocabulary argument.
 *
 * @group taxonomy
 */
class TaxonomyVocabularyArgumentTest extends TaxonomyTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['taxonomy', 'taxonomy_test_views', 'views'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  public static $testViews = ['test_argument_taxonomy_vocabulary'];

  /**
   * @var \Drupal\taxonomy\TermInterface[]
   */
  protected $terms = [];

  /**
   * Vocabularies used for creating terms.
   *
   * @var \Drupal\taxonomy\VocabularyInterface[]
   */
  protected $vocabularies;

  /**
   * {@inheritdoc}
   */
  protected function setUp($import_test_views = TRUE): void {
    parent::setUp($import_test_views);

    // Add default vocabulary to list of vocabularies.
    $this->vocabularies[] = $this->vocabulary;
    // Create additional vocabulary.
    $vocabulary = Vocabulary::create([
      'name' => 'Views testing category',
      'vid' => 'views_testing_category',
    ]);
    $vocabulary->save();
    $this->vocabularies[] = $vocabulary;

    // Create some terms.
    $this->terms[0] = $this->createTerm([
      'name' => 'First',
      'vid' => $this->vocabularies[0]->id(),
    ]);
    $this->terms[1] = $this->createTerm([
      'name' => 'Second',
      'vid' => $this->vocabularies[1]->id(),
    ]);
  }

  /**
   * Tests the vocabulary argument handler.
   *
   * @see Drupal\taxonomy\Plugin\views\argument\VocabularyVid
   */
  public function testTermWithVocabularyArgument() {
    $this->drupalGet('test_argument_taxonomy_vocabulary/' . $this->vocabularies[0]->id());
    // First term should be present.
    $this->assertText($this->terms[0]->label());
    // Second term should not be present.
    $this->assertNoText($this->terms[1]->label());
  }

}
