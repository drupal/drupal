<?php

namespace Drupal\Tests\taxonomy\Unit;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\taxonomy\VocabularyStorage;
use Drupal\Tests\UnitTestCase;

/**
 * @group taxonomy
 */
class TaxonomyVocabularyTest extends UnitTestCase {

  /**
   * @group legacy
   * @expectedDeprecation taxonomy_vocabulary_get_names() is deprecated in Drupal 8.8.0 and will be removed before Drupal 9.0.0. Use \Drupal::entityTypeManager()->getStorage("taxonomy_vocabulary")->loadMultiple() instead, to get a list of vocabulary entities keyed by vocabulary ID. See https://www.drupal.org/node/3039041.
   * @see taxonomy_vocabulary_get_names()
   */
  public function testTaxonomyVocabularyGetNamesDeprecation() {
    $container = new ContainerBuilder();
    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);
    $vocabulary_storage = $this->prophesize(VocabularyStorage::class);
    $vocabulary_storage->loadMultiple()->willReturn([]);
    $entity_type_manager->getStorage('taxonomy_vocabulary')
      ->willReturn($vocabulary_storage->reveal());
    $container->set('entity_type.manager', $entity_type_manager->reveal());
    \Drupal::setContainer($container);

    require_once $this->root . '/core/modules/taxonomy/taxonomy.module';
    taxonomy_vocabulary_get_names();
  }

  /**
   * @group legacy
   * @expectedDeprecation Using drupal_static_reset() with 'taxonomy_vocabulary_get_names' as parameter is deprecated in Drupal 9.3.0 and is removed from Drupal 10.0.0. There's no replacement for this usage. See https://www.drupal.org/node/3039041
   * @see drupal_static_reset()
   */
  public function testTaxonomyVocabularyGetNamesCacheResetDeprecation() {
    require_once $this->root . '/core/includes/bootstrap.inc';
    drupal_static_reset('taxonomy_vocabulary_get_names');
  }

}
