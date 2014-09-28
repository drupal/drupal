<?php

/**
 * @file
 * Contains \Drupal\field_ui_test\Entity\FieldUITestNoBundle.
 */

namespace Drupal\field_ui_test\Entity;

use Drupal\entity_test\Entity\EntityTest;

/**
 * Defines the test Field UI class.
 *
 * @ContentEntityType(
 *   id = "field_ui_test_no_bundle",
 *   label = @Translation("Test Field UI entity, no bundle"),
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *   }
 * )
 */
class FieldUITestNoBundle extends EntityTest {

}
