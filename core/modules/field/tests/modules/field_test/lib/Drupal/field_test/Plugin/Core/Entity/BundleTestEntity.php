<?php

/**
 * @file
 * Contains Drupal\field_test\Plugin\Core\Entity\BundleTestEntity.
 */

namespace Drupal\field_test\Plugin\Core\Entity;

use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Annotation\Translation;

/**
 * Test entity class.
 *
 * @EntityType(
 *   id = "test_entity_bundle",
 *   label = @Translation("Test Entity with a specified bundle"),
 *   module = "field_test",
 *   controllers = {
 *     "storage" = "Drupal\Core\Entity\DatabaseStorageController",
 *     "form" = {
 *       "default" = "Drupal\field_test\TestEntityFormController"
 *     }
 *   },
 *   field_cache = FALSE,
 *   base_table = "test_entity_bundle",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "ftid",
 *     "bundle" = "fttype"
 *   }
 * )
 */
class BundleTestEntity extends TestEntity {

}
