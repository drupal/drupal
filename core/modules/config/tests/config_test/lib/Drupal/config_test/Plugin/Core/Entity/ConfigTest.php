<?php

/**
 * @file
 * Definition of Drupal\config_test\Plugin\Core\Entity\ConfigTest.
 */

namespace Drupal\config_test\Plugin\Core\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Annotation\Translation;
use Drupal\config_test\ConfigTestInterface;

/**
 * Defines the ConfigTest configuration entity.
 *
 * @EntityType(
 *   id = "config_test",
 *   label = @Translation("Test configuration"),
 *   module = "config_test",
 *   controllers = {
 *     "storage" = "Drupal\config_test\ConfigTestStorageController",
 *     "list" = "Drupal\Core\Config\Entity\ConfigEntityListController",
 *     "form" = {
 *       "default" = "Drupal\config_test\ConfigTestFormController",
 *       "delete" = "Drupal\config_test\Form\ConfigTestDeleteForm"
 *     }
 *   },
 *   uri_callback = "config_test_uri",
 *   config_prefix = "config_test.dynamic",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "status" = "status"
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
