<?php

/**
 * @file
 * Contains Drupal\field_test\Plugin\Core\Entity\BundleKeyTestEntity.
 */

namespace Drupal\field_test\Plugin\Core\Entity;

use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Annotation\Translation;

/**
 * Test entity class.
 *
 * @EntityType(
 *   id = "test_entity_bundle_key",
 *   label = @Translation("Test Entity with a bundle key"),
 *   module = "field_test",
 *   controllers = {
 *     "storage" = "Drupal\Core\Entity\DatabaseStorageController",
 *     "form" = {
 *       "default" = "Drupal\field_test\TestEntityFormController"
 *     }
 *   },
 *   field_cache = FALSE,
 *   base_table = "test_entity_bundle_key",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "ftid",
 *     "bundle" = "fttype"
 *   }
 * )
 */
class BundleKeyTestEntity extends TestEntity {

}
