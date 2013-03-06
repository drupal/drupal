<?php

/**
 * @file
 * Contains Drupal\field_test\Plugin\Core\Entity\BundleKeyTestEntity.
 */

namespace Drupal\field_test\Plugin\Core\Entity;

use Drupal\Component\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Test entity class.
 *
 * @Plugin(
 *   id = "test_entity_bundle_key",
 *   label = @Translation("Test Entity with a bundle key"),
 *   module = "field_test",
 *   controller_class = "Drupal\field_test\TestEntityController",
 *   form_controller_class = {
 *     "default" = "Drupal\field_test\TestEntityFormController"
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
