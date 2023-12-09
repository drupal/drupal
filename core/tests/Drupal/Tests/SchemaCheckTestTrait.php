<?php

declare(strict_types=1);

namespace Drupal\Tests;

use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Config\Schema\SchemaCheckTrait;

/**
 * Provides a class for checking configuration schema.
 */
trait SchemaCheckTestTrait {

  use SchemaCheckTrait;

  /**
   * Asserts the TypedConfigManager has a valid schema for the configuration.
   *
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config
   *   The TypedConfigManager.
   * @param string $config_name
   *   The configuration name.
   * @param array $config_data
   *   The configuration data.
   */
  public function assertConfigSchema(TypedConfigManagerInterface $typed_config, $config_name, $config_data) {
    $check = $this->checkConfigSchema($typed_config, $config_name, $config_data);
    $message = '';
    if ($check === FALSE) {
      $message = 'Error: No schema exists.';
    }
    elseif ($check !== TRUE) {
      $this->assertIsArray($check, "The config schema check errors should be in the form of an array.");
      $message = "Errors:\n";
      foreach ($check as $key => $error) {
        $message .= "Schema key $key failed with: $error\n";
      }
    }
    $this->assertTrue($check, "There should be no errors in configuration '$config_name'. $message");
  }

  /**
   * Asserts configuration, specified by name, has a valid schema.
   *
   * @param string $config_name
   *   The configuration name.
   */
  public function assertConfigSchemaByName($config_name) {
    $config = $this->config($config_name);
    $this->assertConfigSchema(\Drupal::service('config.typed'), $config->getName(), $config->get());
  }

}
