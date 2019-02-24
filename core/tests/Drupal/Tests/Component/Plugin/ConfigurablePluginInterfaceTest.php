<?php

namespace Drupal\Tests\Component\Plugin;

use Drupal\Component\Plugin\ConfigurablePluginInterface;
use Drupal\Component\Plugin\PluginBase;
use PHPUnit\Framework\TestCase;

/**
 * Tests ConfigurablePluginInterface deprecation.
 *
 * @group legacy
 * @group plugin
 */
class ConfigurablePluginInterfaceTest extends TestCase {

  /**
   * Tests the deprecation error is thrown.
   *
   * @expectedDeprecation Drupal\Component\Plugin\ConfigurablePluginInterface is deprecated in Drupal 8.7.0 and will be removed before Drupal 9.0.0. You should implement ConfigurableInterface and/or DependentPluginInterface directly as needed. If you implement ConfigurableInterface you may choose to implement ConfigurablePluginInterface in Drupal 8 as well for maximum compatibility, however this must be removed prior to Drupal 9. See https://www.drupal.org/node/2946161
   */
  public function testDeprecation() {
    new ConfigurablePluginInterfaceTestClass([], '', []);
  }

}

/**
 * Test Class to trigger deprecation error.
 */
class ConfigurablePluginInterfaceTestClass extends PluginBase implements ConfigurablePluginInterface {

  /**
   * {@inheritdoc}
   */
  public function getConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function setConfiguration(array $configuration) {}

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDependencies() {
    return [];
  }

}
