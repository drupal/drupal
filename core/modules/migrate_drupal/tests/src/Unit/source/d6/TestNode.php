<?php

/**
 * @file
 * Contains \Drupal\Tests\migrate_drupal\Unit\source\d6\TestNode.
 */

namespace Drupal\Tests\migrate_drupal\Unit\source\d6;

use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\migrate_drupal\Plugin\migrate\source\d6\Node;

/**
 * Provides a node source plugin used for unit testing.
 */
class TestNode extends Node {

  /**
   * Sets the database connection for this source plugin.
   *
   * @param \Drupal\Core\Database\Connection $database
   */
  public function setDatabase(Connection $database) {
    $this->database = $database;
  }

  /**
   * Sets the module handler for this source plugin.
   *
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   */
  public function setModuleHandler(ModuleHandlerInterface $module_handler) {
    $this->moduleHandler = $module_handler;
  }

}
