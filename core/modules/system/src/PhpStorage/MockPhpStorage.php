<?php

namespace Drupal\system\PhpStorage;

/**
 * Mock PHP storage class used for testing.
 */
class MockPhpStorage {

  /**
   * Constructs a MockPhpStorage object.
   *
   * @param array $configuration
   *   The storage configuration.
   */
  public function __construct(
    protected array $configuration,
  ) {
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
    return $this->configuration[$key] ?? NULL;
  }

}
