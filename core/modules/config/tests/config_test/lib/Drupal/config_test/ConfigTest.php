<?php

/**
 * @file
 * Definition of Drupal\config_test\ConfigTest.
 */

namespace Drupal\config_test;

use Drupal\config\ConfigurableBase;

/**
 * Defines the ConfigTest configurable entity.
 */
class ConfigTest extends ConfigurableBase {

  /**
   * The machine name for the configurable.
   *
   * @var string
   */
  public $id;

  /**
   * The UUID for the configurable.
   *
   * @var string
   */
  public $uuid;

  /**
   * The human-readable name of the configurable.
   *
   * @var string
   */
  public $label;

  /**
   * The image style to use.
   *
   * @var string
   */
  public $style;

}
