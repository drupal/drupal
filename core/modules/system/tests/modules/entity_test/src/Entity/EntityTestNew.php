<?php

/**
 * @file
 * Contains \Drupal\entity_test\Entity\EntityTestNew.
 */

namespace Drupal\entity_test\Entity;

/**
 * Defines the test entity class for testing definition addition.
 *
 * This entity type is initially not defined. It is enabled when needed to test
 * the related updates.
 *
 * @ContentEntityType(
 *   id = "entity_test_new",
 *   label = @Translation("New test entity"),
 *   base_table = "entity_test_new",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "bundle" = "type",
 *     "label" = "name",
 *     "langcode" = "langcode",
 *   }
 * )
 */
class EntityTestNew extends EntityTest {
}
