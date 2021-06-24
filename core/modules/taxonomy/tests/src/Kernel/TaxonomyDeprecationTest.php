<?php

namespace Drupal\Tests\taxonomy\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * @group taxonomy
 * @group legacy
 */
class TaxonomyDeprecationTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['taxonomy'];

  /**
   * @see taxonomy_vocabulary_get_names()
   * @see drupal_static_reset()
   */
  public function testTaxonomyVocabularyGetNamesDeprecation() {
    $this->expectDeprecation("taxonomy_vocabulary_get_names() is deprecated in drupal:9.3.0 and is removed from drupal:10.0.0. Use \Drupal::entityTypeManager()->getStorage('taxonomy_vocabulary')->loadMultiple() instead, to get a list of vocabulary entities keyed by vocabulary ID. See https://www.drupal.org/node/3039041");
    taxonomy_vocabulary_get_names();
    $this->expectDeprecation("Using drupal_static_reset() with 'taxonomy_vocabulary_get_names' as parameter is deprecated in Drupal 9.3.0 and is removed from Drupal 10.0.0. There's no replacement for this usage. See https://www.drupal.org/node/3039041");
    drupal_static_reset('taxonomy_vocabulary_get_names');
  }

}
