<?php

namespace Drupal\Tests\taxonomy\Kernel;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\KernelTests\KernelTestBase;
use Drupal\taxonomy\Entity\Term;
use Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait;

/**
 * Tests the loading of multiple taxonomy terms at once.
 *
 * @group taxonomy
 */
class LoadMultipleTest extends KernelTestBase {

  use TaxonomyTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'system',
    'taxonomy',
    'user',
    'text',
    'filter',
  ];

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installEntitySchema('taxonomy_term');
  }

  /**
   * Tests loading multiple taxonomy terms by term ID and vocabulary.
   */
  public function testTaxonomyTermMultipleLoad() {
    // Create a vocabulary.
    $vocabulary = $this->createVocabulary();

    // Create five terms in the vocabulary.
    $i = 0;
    while ($i < 5) {
      $i++;
      $this->createTerm($vocabulary);
    }
    // Load the terms from the vocabulary.
    $term_storage = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
    $terms = $term_storage->loadByProperties(['vid' => $vocabulary->id()]);
    $count = count($terms);
    $this->assertEquals(5, $count, new FormattableMarkup('Correct number of terms were loaded. @count terms.', ['@count' => $count]));

    // Load the same terms again by tid.
    $terms2 = Term::loadMultiple(array_keys($terms));
    $this->assertEquals($terms, $terms2, 'Both arrays contain the same terms.');

    // Remove one term from the array, then delete it.
    $deleted = array_shift($terms2);
    $deleted->delete();
    $deleted_term = Term::load($deleted->id());
    $this->assertNull($deleted_term);

    // Load terms from the vocabulary by vid.
    $terms3 = $term_storage->loadByProperties(['vid' => $vocabulary->id()]);
    $this->assertCount(4, $terms3, 'Correct number of terms were loaded.');
    $this->assertFalse(isset($terms3[$deleted->id()]));

    // Create a single term and load it by name.
    $term = $this->createTerm($vocabulary);
    $loaded_terms = $term_storage->loadByProperties(['name' => $term->getName()]);
    $this->assertCount(1, $loaded_terms, 'One term was loaded.');
    $loaded_term = reset($loaded_terms);
    $this->assertEquals($term->id(), $loaded_term->id(), 'Term loaded by name successfully.');
  }

}
