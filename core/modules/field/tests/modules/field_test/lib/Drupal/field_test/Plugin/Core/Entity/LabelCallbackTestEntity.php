<?php

/**
 * @file
 * Contains Drupal\field_test\Plugin\Core\Entity\LabelCallbackTestEntity.
 */

namespace Drupal\field_test\Plugin\Core\Entity;

use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Test entity class.
 *
 * @Plugin(
 *   id = "test_entity_label_callback",
 *   label = @Translation("Test entity label callback"),
 *   module = "field_test",
 *   controller_class = "Drupal\field_test\TestEntityController",
 *   field_cache = FALSE,
 *   base_table = "test_entity",
 *   revision_table = "test_entity_revision",
 *   label_callback = "field_test_entity_label_callback",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "ftid",
 *     "revision" = "ftvid",
 *     "bundle" = "fttype"
 *   }
 * )
 */
class LabelCallbackTestEntity extends TestEntity {

}
