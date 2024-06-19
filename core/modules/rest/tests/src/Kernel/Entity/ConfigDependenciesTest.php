<?php

declare(strict_types=1);

namespace Drupal\Tests\rest\Kernel\Entity;

use Drupal\KernelTests\KernelTestBase;
use Drupal\rest\Entity\ConfigDependencies;
use Drupal\rest\Entity\RestResourceConfig;
use Drupal\rest\RestResourceConfigInterface;

/**
 * @coversDefaultClass \Drupal\rest\Entity\ConfigDependencies
 *
 * @group rest
 */
class ConfigDependenciesTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['rest', 'entity_test', 'serialization'];

  /**
   * @covers ::calculateDependencies
   *
   * @dataProvider providerBasicDependencies
   */
  public function testCalculateDependencies(array $configuration): void {
    $config_dependencies = new ConfigDependencies(['json' => 'serialization'], ['basic_auth' => 'basic_auth']);

    $rest_config = RestResourceConfig::create($configuration);

    $result = $config_dependencies->calculateDependencies($rest_config);
    $this->assertEquals([
      'module' => ['basic_auth', 'serialization'],
    ], $result);
  }

  /**
   * @covers ::onDependencyRemoval
   * @covers ::onDependencyRemovalForMethodGranularity
   * @covers ::onDependencyRemovalForResourceGranularity
   *
   * @dataProvider providerBasicDependencies
   */
  public function testOnDependencyRemovalRemoveUnrelatedDependency(array $configuration): void {
    $config_dependencies = new ConfigDependencies(['json' => 'serialization'], ['basic_auth' => 'basic_auth']);

    $rest_config = RestResourceConfig::create($configuration);

    $this->assertFalse($config_dependencies->onDependencyRemoval($rest_config, ['module' => ['node']]));
    $this->assertEquals($configuration['configuration'], $rest_config->get('configuration'));
  }

  /**
   * @return array
   *   An array with numerical keys:
   *   0. The original REST resource configuration.
   */
  public static function providerBasicDependencies() {
    return [
      'method' => [
        [
          'plugin_id' => 'entity:entity_test',
          'granularity' => RestResourceConfigInterface::METHOD_GRANULARITY,
          'configuration' => [
            'GET' => [
              'supported_auth' => ['basic_auth'],
              'supported_formats' => ['json'],
            ],
            'POST' => [
              'supported_auth' => ['cookie'],
              'supported_formats' => ['xml'],
            ],
          ],
        ],
      ],
      'resource' => [
        [
          'plugin_id' => 'entity:entity_test',
          'granularity' => RestResourceConfigInterface::RESOURCE_GRANULARITY,
          'configuration' => [
            'methods' => ['GET', 'POST'],
            'formats' => ['json'],
            'authentication' => ['cookie', 'basic_auth'],
          ],
        ],
      ],
    ];
  }

  /**
   * @covers ::onDependencyRemoval
   * @covers ::onDependencyRemovalForMethodGranularity
   */
  public function testOnDependencyRemovalRemoveAuth(): void {
    $config_dependencies = new ConfigDependencies(['json' => 'serialization'], ['basic_auth' => 'basic_auth']);

    $rest_config = RestResourceConfig::create([
      'plugin_id' => 'entity:entity_test',
      'granularity' => RestResourceConfigInterface::METHOD_GRANULARITY,
      'configuration' => [
        'GET' => [
          'supported_auth' => ['cookie'],
          'supported_formats' => ['json'],
        ],
        'POST' => [
          'supported_auth' => ['basic_auth'],
          'supported_formats' => ['json'],
        ],
      ],
    ]);

    $this->assertTrue($config_dependencies->onDependencyRemoval($rest_config, ['module' => ['basic_auth']]));
    $this->assertEquals(['cookie'], $rest_config->getAuthenticationProviders('GET'));
    $this->assertEquals([], $rest_config->getAuthenticationProviders('POST'));
    $this->assertEquals([
      'GET' => [
        'supported_auth' => ['cookie'],
        'supported_formats' => ['json'],
      ],
      'POST' => [
        'supported_formats' => ['json'],
      ],
    ], $rest_config->get('configuration'));
  }

  /**
   * @covers ::onDependencyRemoval
   * @covers ::onDependencyRemovalForResourceGranularity
   *
   * @dataProvider providerOnDependencyRemovalForResourceGranularity
   */
  public function testOnDependencyRemovalForResourceGranularity(array $configuration, $module, $expected_configuration): void {
    assert(is_string($module));
    assert($expected_configuration === FALSE || is_array($expected_configuration));

    $config_dependencies = new ConfigDependencies(['json' => 'serialization'], ['basic_auth' => 'basic_auth']);

    $rest_config = RestResourceConfig::create($configuration);

    $this->assertSame(!empty($expected_configuration), $config_dependencies->onDependencyRemoval($rest_config, ['module' => [$module]]));
    if (!empty($expected_configuration)) {
      $this->assertEquals($expected_configuration, $rest_config->get('configuration'));
    }
  }

  /**
   * @return array
   *   An array with numerical keys:
   *   0. The original REST resource configuration.
   *   1. The module to uninstall (the dependency that is about to be removed).
   *   2. The expected configuration after uninstalling this module.
   */
  public static function providerOnDependencyRemovalForResourceGranularity() {
    return [
      'resource with multiple formats' => [
        [
          'plugin_id' => 'entity:entity_test',
          'granularity' => RestResourceConfigInterface::RESOURCE_GRANULARITY,
          'configuration' => [
            'methods' => ['GET', 'POST'],
            'formats' => ['xml', 'json'],
            'authentication' => ['cookie', 'basic_auth'],
          ],
        ],
        'serialization',
        [
          'methods' => ['GET', 'POST'],
          'formats' => ['xml'],
          'authentication' => ['cookie', 'basic_auth'],
        ],
      ],
      'resource with multiple authentication providers' => [
        [
          'plugin_id' => 'entity:entity_test',
          'granularity' => RestResourceConfigInterface::RESOURCE_GRANULARITY,
          'configuration' => [
            'methods' => ['GET', 'POST'],
            'formats' => ['json', 'xml'],
            'authentication' => ['cookie', 'basic_auth'],
          ],
        ],
        'basic_auth',
        [
          'methods' => ['GET', 'POST'],
          'formats' => ['json', 'xml'],
          'authentication' => ['cookie'],
        ],
      ],
      'resource with only basic_auth authentication' => [
        [
          'plugin_id' => 'entity:entity_test',
          'granularity' => RestResourceConfigInterface::RESOURCE_GRANULARITY,
          'configuration' => [
            'methods' => ['GET', 'POST'],
            'formats' => ['json', 'xml'],
            'authentication' => ['basic_auth'],
          ],
        ],
        'basic_auth',
        FALSE,
      ],
    ];
  }

}
