<?php

/**
 * @file
 * Definition of Drupal\config_test\ConfigTest.
 */

namespace Drupal\config_test;

use Drupal\config\ConfigEntityBase;

/**
 * Defines the ConfigTest configuration entity.
 */
class ConfigTest extends ConfigEntityBase {

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
   * The image style to use.
   *
   * @var string
   */
  public $style;

}
