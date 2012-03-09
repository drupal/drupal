<?php

/**
 * @file
 * Definition of Drupal\Component\Uuid\Uuid.
 */

namespace Drupal\Component\Uuid;

/**
 * Factory class for UUIDs.
 *
 * Determines which UUID implementation to use, and uses that to generate
 * and validate UUIDs.
 */
class Uuid {

  /**
   * Holds the UUID implementation.
   *
   * @var Drupal\Component\Uuid\UuidInterface
   */
  protected $plugin;

  /**
   * Instantiates the correct UUID object.
   */
  public function __construct() {
    $class = $this->determinePlugin();
    $this->plugin = new $class();
  }

  /**
   * Generates an universally unique identifier.
   *
   * @see Drupal\Component\Uuid\UuidInterface::generate()
   */
  public function generate() {
    return $this->plugin->generate();
  }

  /**
   * Checks that a string appears to be in the format of a UUID.
   *
   * Plugins should not implement validation, since UUIDs should be in a
   * consistent format across all plugins.
   *
   * @param string $uuid
   *   The string to test.
   *
   * @return bool
   *   TRUE if the string is well formed, FALSE otherwise.
   */
  public function isValid($uuid) {
    return preg_match("/^[0-9a-f]{8}-([0-9a-f]{4}-){3}[0-9a-f]{12}$/", $uuid);
  }

  /**
   * Determines the optimal implementation to use for generating UUIDs.
   *
   * The selection is made based on the enabled PHP extensions with the
   * most performant available option chosen.
   *
   * @return string
   *  The class name for the optimal UUID generator.
   */
  protected function determinePlugin() {
    static $plugin;
    if (!empty($plugin)) {
      return $plugin;
    }

    $plugin = 'Drupal\Component\Uuid\Php';

    // Debian/Ubuntu uses the (broken) OSSP extension as their UUID
    // implementation. The OSSP implementation is not compatible with the
    // PECL functions.
    if (function_exists('uuid_create') && !function_exists('uuid_make')) {
      $plugin = 'Drupal\Component\Uuid\Pecl';
    }
    // Try to use the COM implementation for Windows users.
    elseif (function_exists('com_create_guid')) {
      $plugin = 'Drupal\Component\Uuid\Com';
    }
    return $plugin;
  }
}
