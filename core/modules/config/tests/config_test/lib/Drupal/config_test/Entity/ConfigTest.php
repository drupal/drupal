<?php

/**
 * @file
 * Definition of Drupal\config_test\Entity\ConfigTest.
 */

namespace Drupal\config_test\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\config_test\ConfigTestInterface;

/**
 * Defines the ConfigTest configuration entity.
 *
 * @ConfigEntityType(
 *   id = "config_test",
 *   label = @Translation("Test configuration"),
 *   controllers = {
 *     "storage" = "Drupal\config_test\ConfigTestStorageController",
 *     "list" = "Drupal\config_test\ConfigTestListController",
 *     "form" = {
 *       "default" = "Drupal\config_test\ConfigTestFormController",
 *       "delete" = "Drupal\config_test\Form\ConfigTestDeleteForm"
 *     },
 *     "access" = "Drupal\config_test\ConfigTestAccessController"
 *   },
 *   config_prefix = "dynamic",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "status" = "status"
 *   },
 *   links = {
 *     "edit-form" = "config_test.entity",
 *     "delete-form" = "config_test.entity_delete",
 *     "enable" = "config_test.entity_enable",
 *     "disable" = "config_test.entity_disable"
 *   }
 * )
 */
class ConfigTest extends ConfigEntityBase implements ConfigTestInterface {

  /**
   * The machine name for the configuration entity.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the configuration entity.
   *
   * @var string
   */
  public $label;

  /**
   * The weight of the configuration entity.
   *
   * @var int
   */
  public $weight = 0;

  /**
   * The image style to use.
   *
   * @var string
   */
  public $style;

  /**
   * A protected property of the configuration entity.
   *
   * @var string
   */
  protected $protected_property;

  /**
   * Overrides \Drupal\Core\Config\Entity\ConfigEntityBase::getExportProperties();
   */
  public function getExportProperties() {
    $properties = parent::getExportProperties();
    $protected_names = array(
      'protected_property',
    );
    foreach ($protected_names as $name) {
      $properties[$name] = $this->get($name);
    }
    return $properties;
  }

  /**
   * Overrides \Drupal\Core\Config\Entity\ConfigEntityBase::sort().
   */
  public static function sort($a, $b) {
    \Drupal::state()->set('config_entity_sort', TRUE);
    return parent::sort($a, $b);
  }

}
