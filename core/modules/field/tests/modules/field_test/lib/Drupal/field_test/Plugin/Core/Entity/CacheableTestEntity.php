<?php

/**
 * @file
 * Contains Drupal\field_test\Plugin\Core\Entity\CacheableTestEntity.
 */

namespace Drupal\field_test\Plugin\Core\Entity;

use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Annotation\Translation;

/**
 * Test entity class.
 *
 * @EntityType(
 *   id = "test_cacheable_entity",
 *   label = @Translation("Test Entity, cacheable"),
 *   module = "field_test",
 *   controllers = {
 *     "storage" = "Drupal\Core\Entity\DatabaseStorageController"
 *   },
 *   field_cache = TRUE,
 *   base_table = "test_entity",
 *   revision_table = "test_entity_revision",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "ftid",
 *     "revision" = "ftvid",
 *     "bundle" = "fttype"
 *   }
 * )
 */
class CacheableTestEntity extends TestEntity {

}
