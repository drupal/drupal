<?php

/**
 * @file
 * Contains \Drupal\config_test\Plugin\Core\Entity\ConfigQueryTest.
 */

namespace Drupal\config_test\Plugin\Core\Entity;

use Drupal\Core\Entity\Annotation\EntityType;
use Drupal\Core\Annotation\Translation;

/**
 * Defines the ConfigQueryTest configuration entity used by the query test.
 *
 * @EntityType(
 *   id = "config_query_test",
 *   label = @Translation("Test configuration for query"),
 *   module = "config_test",
 *   controller_class = "Drupal\config_test\ConfigTestStorageController",
 *   list_controller_class = "Drupal\Core\Config\Entity\ConfigEntityListController",
 *   form_controller_class = {
 *     "default" = "Drupal\config_test\ConfigTestFormController"
 *   },
 *   uri_callback = "config_test_uri",
 *   config_prefix = "config_query_test.dynamic",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid"
 *   }
 * )
 *
 * @see \Drupal\entity\Tests\ConfigEntityQueryTest
 */
class ConfigQueryTest extends ConfigTest {

  /**
   * A number used by the sort tests.
   *
   * @var int
   */
  public $number;

  /**
   * An array used by the wildcard tests.
   *
   * @var array
   */
  public $array;

}
