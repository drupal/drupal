<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Plugin;

use Drupal\Component\Plugin\ConfigurableInterface;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Plugin\ConfigurableTrait;
use PHPUnit\Framework\TestCase;

/**
 * Tests for ConfigurableTrait.
 *
 * @group Plugin
 *
 * @coversDefaultClass \Drupal\Core\Plugin\ConfigurableTrait
 */
class ConfigurableTraitTest extends TestCase {

  /**
   * Tests ConfigurableTrait::defaultConfiguration.
   *
   * @covers ::defaultConfiguration
   */
  public function testDefaultConfiguration(): void {
    /** @var \Drupal\Component\Plugin\ConfigurableInterface $configurable_plugin */
    $configurable_plugin = new ConfigurableTestClass();
    $this->assertSame([], $configurable_plugin->defaultConfiguration());
  }

  /**
   * Tests ConfigurableTrait::getConfiguration.
   *
   * @covers ::getConfiguration
   */
  public function testGetConfiguration(): void {
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
  public function testSetConfiguration(array $default_configuration, array $test_configuration, array $final_configuration): void {
    $test_object = new ConfigurableTestClass();
    $test_object->setDefaultConfiguration($default_configuration);
    $test_object->setConfiguration($test_configuration);
    $this->assertSame($final_configuration, $test_object->getConfiguration());
  }

  /**
   * Provides data for testSetConfiguration.
   *
   * @return array
   *   The data.
   */
  public static function setConfigurationDataProvider(): array {
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
 * A test class using ConfigurablePluginTrait that can modify the de.
 */
class ConfigurableTestClass extends PluginBase implements ConfigurableInterface {
  use ConfigurableTrait {
    defaultConfiguration as traitDefaultConfiguration;
  }

  /**
   * A default configuration for the test class to return.
   *
   * @var array|null
   */
  protected ?array $defaultConfiguration = NULL;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration = [], string $plugin_id = '', array $plugin_definition = []) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->setConfiguration($configuration);
  }

  /**
   * Sets the default configuration this test will return.
   *
   * @param array $default_configuration
   *   The default configuration to use.
   */
  public function setDefaultConfiguration(array $default_configuration): void {
    $this->defaultConfiguration = $default_configuration;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return $this->defaultConfiguration ?? $this->traitDefaultConfiguration();
  }

}
