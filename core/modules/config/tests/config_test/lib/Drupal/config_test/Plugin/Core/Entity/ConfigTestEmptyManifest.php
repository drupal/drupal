<?php

/**
 * @file
 * Definition of Drupal\config_test\Plugin\Core\Entity\ConfigTestEmptyManifest.
 */

namespace Drupal\config_test\Plugin\Core\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Annotation\Translation;

/**
 * Defines the ConfigTestEmptyManifest configuration entity.
 *
 * @EntityType(
 *   id = "config_test_empty_manifest",
 *   label = @Translation("Test empty manifest creation"),
 *   module = "config_test",
 *   controller_class = "Drupal\config_test\ConfigTestStorageController",
 *   config_prefix = "config_test.empty_manifest",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   }
 * )
 */
class ConfigTestEmptyManifest extends ConfigEntityBase {

  /**
   * The machine name for the configuration entity.
   *
   * @var string
   */
  public $id;

  /**
   * The UUID for the configuration entity.
   *
   * @var string
   */
  public $uuid;

  /**
   * The human-readable name of the configuration entity.
   *
   * @var string
   */
  public $label;

}
