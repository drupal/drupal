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

  public $id;

  public $uuid;

  public $label;

  /**
   * The image style to use.
   *
   * @var string
   */
  public $style;

}
