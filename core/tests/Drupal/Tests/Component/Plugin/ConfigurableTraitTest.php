<?php

namespace Drupal\Tests\Component\Plugin;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\ConfigurableTrait;
use Drupal\Component\Plugin\PluginBase;
use PHPUnit\Framework\TestCase;

/**
 * Tests for ConfigurableTrait.
 *
 * @coversDefaultClass \Drupal\Component\Plugin\ConfigurableTrait
 *
 * @group Plugin
 */
class ConfigurableTraitTest extends TestCase {

  /**
   * Tests ConfigurableTrait::defaultConfiguration.
   *
   * @covers ::defaultConfiguration
   */
  public function testDefaultConfiguration() {
    /** @var \Drupal\Component\Plugin\ConfigurableInterface $configurable_plugin */
    $configurable_plugin = $this->getMockForTrait(ConfigurableTrait::class);
    $this->assertSame([], $configurable_plugin->defaultConfiguration());
  }

  /**
   * Tests ConfigurableTrait::getConfiguration.
   *
   * @covers ::getConfiguration
   */
  public function testGetConfiguration() {
    $test_configuration = [
      'config_key_1' => 'config_value_1',
      'config_key_2' => [
        'nested_key_1' => 'nested_value_1',
        'nested_key_2' => 'nested_value_2',
      ],
    ];
    $configurable_plugin = new ConfigurableTestClass($test_configuration);
    $this->assertSame($test_configuration, $configurable_plugin->getConfiguration());
  }

  /**
   * Tests configurableTrait::setConfiguration.
   *
   * Specifically test the way default and provided configurations are merged.
   *
   * @param array $default_configuration
   *   The default configuration to use for the trait.
   * @param array $test_configuration
   *   The configuration to test.
   * @param array $final_configuration
   *   The expected final plugin configuration.
   *
   * @covers ::setConfiguration
   *
   * @dataProvider setConfigurationDataProvider
   */
  public function testSetConfiguration(array $default_configuration, array $test_configuration, array $final_configuration) {
    $test_object = new ConfigurableTestClass($default_configuration);
    $test_object->setConfiguration($test_configuration);
    $this->assertSame($final_configuration, $test_object->getConfiguration());
  }

  /**
   * Provides data for testSetConfiguration.
   *
   * @return array
   *   The data.
   */
  public function setConfigurationDataProvider() {
    return [
      'Direct Override' => [
        'default_configuration' => [
          'default_key_1' => 'default_value_1',
          'default_key_2' => [
            'default_nested_key_1' => 'default_nested_value_1',
            'default_nested_key_2' => 'default_nested_value_2',
          ],
        ],
        'test_configuration' => [
          'default_key_1' => 'override_value_1',
          'default_key_2' => [
            'default_nested_key_1' => 'override_nested_value_1',
            'default_nested_key_2' => 'override_nested_value_2',
          ],
        ],
        'final_configuration' => [
          'default_key_1' => 'override_value_1',
          'default_key_2' => [
            'default_nested_key_1' => 'override_nested_value_1',
            'default_nested_key_2' => 'override_nested_value_2',
          ],
        ],
      ],
      'Mixed Override' => [
        'default_configuration' => [
          'default_key_1' => 'default_value_1',
          'default_key_2' => [
            'default_nested_key_1' => 'default_nested_value_1',
            'default_nested_key_2' => 'default_nested_value_2',
          ],
        ],
        'test_configuration' => [
          'override_key_1' => 'config_value_1',
          'default_key_2' => [
            'default_nested_key_1' => 'override_value_1',
            'override_nested_key' => 'override_value',
          ],
        ],
        'final_configuration' => [
          'default_key_1' => 'default_value_1',
          'default_key_2' => [
            'default_nested_key_1' => 'override_value_1',
            'default_nested_key_2' => 'default_nested_value_2',
            'override_nested_key' => 'override_value',
          ],
          'override_key_1' => 'config_value_1',
        ],
      ],
      'indexed_override' => [
        'default_configuration' => [
          'config_value_1',
          'config_value_2',
          'config_value_3',
        ],
        'test_configuration' => [
          'override_value_1',
          'override_value_2',
        ],
        'final_configuration' => [
          'override_value_1',
          'override_value_2',
          'config_value_3',
        ],
      ],
      'indexed_override_complex' => [
        'default_configuration' => [
          'config_value_1',
          'config_value_2',
          'config_value_3',
        ],
        'test_configuration' => [
          0 => 'override_value_1',
          2 => 'override_value_3',
        ],
        'final_configuration' => [
          'override_value_1',
          'config_value_2',
          'override_value_3',
        ],
      ],
    ];
  }

}

/**
 * A test class using ConfigurablePluginTrait.
 */
class ConfigurableTestClass extends PluginBase implements ConfigurableInterface {
  use ConfigurableTrait;

  /**
   * A default configuration for the test class to return.
   *
   * @var array
   */
  protected $defaultConfiguration;

  /**
   * Constructs a ConfigurablePluginTestClass object.
   *
   * @param array $default_configuration
   *   The default configuration to return.
   */
  public function __construct(array $default_configuration) {
    $this->defaultConfiguration = $default_configuration;
    parent::__construct([], '', []);
  }

  /**
   * Returns the provided test defaults.
   *
   * @return array
   *   The default configuration.
   */
  public function defaultConfiguration() {
    return $this->defaultConfiguration;
  }

}
