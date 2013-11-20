<?php

/**
 * @file
 * Contains \Drupal\migrate\Plugin\migrate\destination\DestinationBase.
 */


namespace Drupal\migrate\Plugin\migrate\destination;

use Drupal\Core\Plugin\PluginBase;
use Drupal\migrate\Plugin\MigrateDestinationInterface;

abstract class DestinationBase extends PluginBase implements MigrateDestinationInterface {

  /**
   * Modify the Row before it is imported.
   */
  public function preImport() {
    // TODO: Implement preImport() method.
  }

  /**
   * Modify the Row before it is rolled back.
   */
  public function preRollback() {
    // TODO: Implement preRollback() method.
  }

  public function postImport() {
    // TODO: Implement postImport() method.
  }

  public function postRollback() {
    // TODO: Implement postRollback() method.
  }

  public function rollbackMultiple(array $destination_identifiers) {
    // TODO: Implement rollbackMultiple() method.
  }

  public function getCreated() {
    // TODO: Implement getCreated() method.
  }

  public function getUpdated() {
    // TODO: Implement getUpdated() method.
  }

  public function resetStats() {
    // TODO: Implement resetStats() method.
  }
}
