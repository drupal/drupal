<?php

/**
 * @file
 * Contains \Drupal\field_ui_test\Plugin\Core\Entity\FieldUITestNoBundle.
 */

namespace Drupal\field_ui_test\Plugin\Core\Entity;

use Drupal\Core\Entity\Entity;
use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Annotation\Translation;

/**
 * Defines the test Field UI class.
 *
 * @EntityType(
 *   id = "field_ui_test_no_bundle",
 *   label = @Translation("Test Field UI entity, no bundle"),
 *   module = "field_ui_test",
 *   controllers = {
 *     "storage" = "Drupal\Core\Entity\DatabaseStorageController"
 *   },
 *   fieldable = TRUE,
 *   route_base_path = "field-ui-test-no-bundle/manage"
 * )
 */
class FieldUITestNoBundle extends Entity {

  /**
   * The entity ID.
   *
   * @var int
   */
  public $id;

}
