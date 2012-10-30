<?php

/**
 * @file
 * Contains Drupal\field_test\Plugin\Core\Entity\NoLabelTestEntity.
 */

namespace Drupal\field_test\Plugin\Core\Entity;

use Drupal\Core\Annotation\Plugin;
use Drupal\Core\Annotation\Translation;

/**
 * Test entity class.
 *
 * @Plugin(
 *   id = "test_entity_no_label",
 *   label = @Translation("Test Entity without label"),
 *   module = "field_test",
 *   controller_class = "Drupal\field_test\TestEntityController",
 *   form_controller_class = {
 *     "default" = "Drupal\field_test\TestEntityFormController"
 *   },
 *   field_cache = FALSE,
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
class NoLabelTestEntity extends TestEntity {

}
