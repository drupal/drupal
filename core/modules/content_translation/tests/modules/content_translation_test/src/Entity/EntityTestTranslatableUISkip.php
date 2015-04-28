<?php

/**
 * @file
 * Contains Drupal\content_translation_test\Entity\EntityTestTranslatableUISkip.
 */

namespace Drupal\content_translation_test\Entity;

use Drupal\entity_test\Entity\EntityTest;

/**
 * Defines the test entity class.
 *
 * @ContentEntityType(
 *   id = "entity_test_translatable_UI_skip",
 *   label = @Translation("Test entity - Translatable skip UI check"),
 *   base_table = "entity_test_mul",
 *   data_table = "entity_test_mul_property_data",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "bundle" = "type",
 *     "label" = "name",
 *     "langcode" = "langcode",
 *   },
 *   translatable = TRUE,
 *   content_translation_ui_skip = TRUE,
 * )
 */
class EntityTestTranslatableUISkip extends EntityTest {

}
