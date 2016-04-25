<?php

namespace Drupal\content_translation_test\Entity;

use Drupal\entity_test\Entity\EntityTest;

/**
 * Defines the test entity class.
 *
 * @ContentEntityType(
 *   id = "entity_test_translatable_no_skip",
 *   label = @Translation("Test entity - Translatable check UI"),
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
 * )
 */
class EntityTestTranslatableNoUISkip extends EntityTest {

}
