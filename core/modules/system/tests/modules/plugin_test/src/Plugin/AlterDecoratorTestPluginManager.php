<?php

/**
 * @file
 * Definition of Drupal\plugin_test\Plugin\plugin_test\AlterDecoratorTestPluginManager.
 */

namespace Drupal\plugin_test\Plugin;

use Drupal\Core\Plugin\Discovery\AlterDecorator;

/**
 * Defines a plugin manager used by AlterDecorator unit tests.
 */
class AlterDecoratorTestPluginManager extends TestPluginManager {
  public function __construct() {
    parent::__construct();
    $this->discovery = new AlterDecorator($this->discovery, 'plugin_test');
  }
}
