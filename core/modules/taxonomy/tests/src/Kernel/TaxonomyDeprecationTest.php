<?php

namespace Drupal\Tests\taxonomy\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\Tests\taxonomy\Traits\TaxonomyTestTrait;

/**
 * @group taxonomy
 * @group legacy
 */
class TaxonomyDeprecationTest extends KernelTestBase {

  use TaxonomyTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['filter', 'taxonomy', 'text', 'user'];

  /**
   * @see taxonomy_vocabulary_get_names()
   * @see taxonomy_term_load_multiple_by_name()
   * @see drupal_static_reset()
   */
  public function testTaxonomyVocabularyGetNamesDeprecation() {
    $this->installEntitySchema('taxonomy_term');
    $vocabulary1 = $this->createVocabulary();
    $term1 = $this->createTerm($vocabulary1, ['name' => 'Foo']);
    $term2 = $this->createTerm($vocabulary1, ['name' => 'Foo']);
    $vocabulary2 = $this->createVocabulary();
    $term3 = $this->createTerm($vocabulary2, ['name' => 'Foo']);

    $this->expectDeprecation("taxonomy_vocabulary_get_names() is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use \Drupal::entityTypeManager()->getStorage('taxonomy_vocabulary')->loadMultiple() instead, to get a list of vocabulary entities keyed by vocabulary ID. See https://www.drupal.org/node/3039041");
    $this->expectDeprecation('taxonomy_term_load_multiple_by_name() is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use \Drupal::entityTypeManager()->getStorage("taxonomy_vocabulary")->loadByProperties(["name" => $name, "vid" => $vid]) instead, to get a list of taxonomy term entities having the same name and keyed by their term ID.. See https://www.drupal.org/node/3039041');

    // Vocabulary names are keyed by machine name.
    $names = taxonomy_vocabulary_get_names();
    $this->assertCount(2, $names);
    $this->assertArrayHasKey($vocabulary1->id(), $names);
    $this->assertSame($vocabulary1->id(), $names[$vocabulary1->id()]);
    $this->assertArrayHasKey($vocabulary2->id(), $names);
    $this->assertSame($vocabulary2->id(), $names[$vocabulary2->id()]);

    // Call the function without a vocabulary, with an exact name.
    $terms = taxonomy_term_load_multiple_by_name('Foo');
    $this->assertCount(3, $terms);
    $this->assertArrayHasKey($term1->id(), $terms);
    $this->assertArrayHasKey($term2->id(), $terms);
    $this->assertArrayHasKey($term3->id(), $terms);

    // Use space concatenated term name.
    $terms = taxonomy_term_load_multiple_by_name('  Foo    ');
    $this->assertCount(3, $terms);
    $this->assertArrayHasKey($term1->id(), $terms);
    $this->assertArrayHasKey($term2->id(), $terms);
    $this->assertArrayHasKey($term3->id(), $terms);

    // Use upper-cased term name.
    $terms = taxonomy_term_load_multiple_by_name('FOO');
    $this->assertCount(3, $terms);
    $this->assertArrayHasKey($term1->id(), $terms);
    $this->assertArrayHasKey($term2->id(), $terms);
    $this->assertArrayHasKey($term3->id(), $terms);

    // Use lower-cased term name.
    $terms = taxonomy_term_load_multiple_by_name('foo');
    $this->assertCount(3, $terms);
    $this->assertArrayHasKey($term1->id(), $terms);
    $this->assertArrayHasKey($term2->id(), $terms);
    $this->assertArrayHasKey($term3->id(), $terms);

    // Use an invalid term name.
    $terms = taxonomy_term_load_multiple_by_name('Invalid term name');
    $this->assertEmpty($terms);

    // Use a substring of term name.
    $terms = taxonomy_term_load_multiple_by_name('Fo');
    $this->assertEmpty($terms);

    // Call the function with a valid vocabulary.
    $terms = taxonomy_term_load_multiple_by_name('Foo', $vocabulary1->id());
    $this->assertCount(2, $terms);
    $this->assertArrayHasKey($term1->id(), $terms);
    $this->assertArrayHasKey($term2->id(), $terms);

    // Test the second vocabulary.
    $terms = taxonomy_term_load_multiple_by_name('foo', $vocabulary2->id());
    $this->assertCount(1, $terms);
    $this->assertArrayHasKey($term3->id(), $terms);

    // Call the function with an invalid vocabulary.
    $terms = taxonomy_term_load_multiple_by_name('Foo', 'invalid');
    $this->assertEmpty($terms);

    $this->expectDeprecation("Using drupal_static_reset() with 'taxonomy_vocabulary_get_names' as parameter is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. There is no replacement for this usage. See https://www.drupal.org/node/3039041");
    drupal_static_reset('taxonomy_vocabulary_get_names');
  }

}
