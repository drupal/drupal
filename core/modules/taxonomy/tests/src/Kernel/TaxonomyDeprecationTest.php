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
  protected static $modules = ['taxonomy'];

  /**
   * @see taxonomy_vocabulary_get_names()
   * @see drupal_static_reset()
   */
  public function testTaxonomyVocabularyGetNamesDeprecation() {
    $vocabulary1 = $this->createVocabulary();
    $vocabulary2 = $this->createVocabulary();

    $this->expectDeprecation("taxonomy_vocabulary_get_names() is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use \Drupal::entityTypeManager()->getStorage('taxonomy_vocabulary')->loadMultiple() instead, to get a list of vocabulary entities keyed by vocabulary ID. See https://www.drupal.org/node/3039041");
    // Vocabulary names are keyed by machine name.
    $names = taxonomy_vocabulary_get_names();
    $this->assertCount(2, $names);
    $this->assertArrayHasKey($vocabulary1->id(), $names);
    $this->assertSame($vocabulary1->id(), $names[$vocabulary1->id()]);
    $this->assertArrayHasKey($vocabulary2->id(), $names);
    $this->assertSame($vocabulary2->id(), $names[$vocabulary2->id()]);

    $this->expectDeprecation("Using drupal_static_reset() with 'taxonomy_vocabulary_get_names' as parameter is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. There is no replacement for this usage. See https://www.drupal.org/node/3039041");
    drupal_static_reset('taxonomy_vocabulary_get_names');
  }

}
