<?php

namespace Drupal\system\PhpStorage;

/**
 * Mock PHP storage class used for testing.
 */
class MockPhpStorage {

  /**
   * The storage configuration.
   *
   * @var array
   */
  protected $configuration;

  /**
   * Constructs a MockPhpStorage object.
   *
   * @param array $configuration
   */
  public function __construct(array $configuration) {
    $this->configuration = $configuration;
  }

  /**
   * Gets the configuration data.
   */
  public function getConfiguration() {
    return $this->configuration;
  }

  /**
   * Gets a single configuration key.
   */
  public function getConfigurationValue($key) {
    return isset($this->configuration[$key]) ? $this->configuration[$key] : NULL;
  }

}
