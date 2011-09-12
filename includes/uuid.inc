<?php

/**
 * @file
 * Handling of universally unique identifiers.
 */

/**
 * Interface that defines a UUID backend.
 */
interface UuidInterface {

  /**
   * Generates a Universally Unique IDentifier (UUID).
   *
   * @return
   *   A 32 byte integer represented as a hex string formatted with 4 hypens.
   */
  public function generate();

}

/**
 * Factory class for UUIDs.
 *
 * Determines which UUID implementation to use, and uses that to generate
 * and validate UUIDs.
 */
class Uuid {

  /**
   * Holds the UUID implementation.
   */
  protected $plugin;

  /**
   * This constructor instantiates the correct UUID object.
   */
  public function __construct() {
    $class = $this->determinePlugin();
    $this->plugin = new $class();
  }

  /**
   * Generates an universally unique identifier.
   *
   * @see UuidInterface::generate()
   */
  public function generate() {
    return $this->plugin->generate();
  }

  /**
   * Check that a string appears to be in the format of a UUID.
   *
   * Plugins should not implement validation, since UUIDs should be in a
   * consistent format across all plugins.
   *
   * @param $uuid
   *   The string to test.
   *
   * @return
   *   TRUE if the string is well formed.
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
   * @return
   *  The class name for the optimal UUID generator.
   */
  protected function determinePlugin() {
    static $plugin;
    if (!empty($plugin)) {
      return $plugin;
    }

    $plugin = 'UuidPhp';

    // Debian/Ubuntu uses the (broken) OSSP extension as their UUID
    // implementation. The OSSP implementation is not compatible with the
    // PECL functions.
    if (function_exists('uuid_create') && !function_exists('uuid_make')) {
      $plugin = 'UuidPecl';
    }
    // Try to use the COM implementation for Windows users.
    elseif (function_exists('com_create_guid')) {
      $plugin = 'UuidCom';
    }
    return $plugin;
  }
}

/**
 * UUID implementation using the PECL extension.
 */
class UuidPecl implements UuidInterface {
  public function generate() {
    return uuid_create(UUID_TYPE_DEFAULT);
  }
}

/**
 * UUID implementation using the Windows internal GUID extension.
 *
 * @see http://php.net/com_create_guid
 */
class UuidCom implements UuidInterface {
  public function generate() {
    // Remove {} wrapper and make lower case to keep result consistent.
    return drupal_strtolower(trim(com_create_guid(), '{}'));
  }
}

/**
 * Generates an UUID v4 using PHP code.
 *
 * Loosely based on Ruby's UUIDTools generate_random logic.
 *
 * @see http://uuidtools.rubyforge.org/api/classes/UUIDTools/UUID.html
 */
class UuidPhp implements UuidInterface {
  public function generate() {
    $hex = substr(hash('sha256', drupal_random_bytes(16)), 0, 32);

    // The field names refer to RFC 4122 section 4.1.2.
    $time_low = substr($hex, 0, 8);
    $time_mid = substr($hex, 8, 4);

    $time_hi_and_version = base_convert(substr($hex, 12, 4), 16, 10);
    $time_hi_and_version &= 0x0FFF;
    $time_hi_and_version |= (4 << 12);

    $clock_seq_hi_and_reserved = base_convert(substr($hex, 16, 4), 16, 10);
    $clock_seq_hi_and_reserved &= 0x3F;
    $clock_seq_hi_and_reserved |= 0x80;

    $clock_seq_low = substr($hex, 20, 2);
    $nodes = substr($hex, 20);

    $uuid = sprintf('%s-%s-%04x-%02x%02x-%s',
      $time_low, $time_mid,
      $time_hi_and_version, $clock_seq_hi_and_reserved,
      $clock_seq_low, $nodes);

    return $uuid;
  }
}
