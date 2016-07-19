<?php

namespace Drupal\entity_test\Entity;

/**
 * Defines the test entity class.
 *
 * @ContentEntityType(
 *   id = "entity_test_cache",
 *   label = @Translation("Test entity with field cache"),
 *   handlers = {
 *     "access" = "Drupal\entity_test\EntityTestAccessControlHandler",
 *     "form" = {
 *       "default" = "Drupal\entity_test\EntityTestForm"
 *     },
 *     "translation" = "Drupal\content_translation\ContentTranslationHandler"
 *   },
 *   base_table = "entity_test_cache",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "bundle" = "type"
 *   }
 * )
 */
class EntityTestCache extends EntityTest {

}
