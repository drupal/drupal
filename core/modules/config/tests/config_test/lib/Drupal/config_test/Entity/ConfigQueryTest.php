<?php

/**
 * @file
 * Contains \Drupal\config_test\Entity\ConfigQueryTest.
 */

namespace Drupal\config_test\Entity;

/**
 * Defines the ConfigQueryTest configuration entity used by the query test.
 *
 * @ConfigEntityType(
 *   id = "config_query_test",
 *   label = @Translation("Test configuration for query"),
 *   controllers = {
 *     "storage" = "Drupal\config_test\ConfigTestStorageController",
 *     "list" = "Drupal\Core\Config\Entity\ConfigEntityListController",
 *     "form" = {
 *       "default" = "Drupal\config_test\ConfigTestFormController"
 *     }
 *   },
 *   config_prefix = "query",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label"
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
