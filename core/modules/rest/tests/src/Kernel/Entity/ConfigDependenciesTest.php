<?php

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
  public static $modules = ['rest', 'entity_test', 'serialization'];

  /**
   * @covers ::calculateDependencies
   * @covers ::calculateDependenciesForMethodGranularity
   */
  public function testCalculateDependencies() {
    $config_dependencies = new ConfigDependencies(['hal_json' => 'hal', 'json' => 'serialization'], ['basic_auth' => 'basic_auth']);

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
          'supported_formats' => ['hal_json'],
        ],
      ],
    ]);

    $result = $config_dependencies->calculateDependencies($rest_config);
    $this->assertEquals(['module' => [
      'serialization', 'basic_auth', 'hal',
    ]], $result);
  }

  /**
   * @covers ::onDependencyRemoval
   * @covers ::calculateDependenciesForMethodGranularity
   */
  public function testOnDependencyRemovalRemoveUnrelatedDependency() {
    $config_dependencies = new ConfigDependencies(['hal_json' => 'hal', 'json' => 'serialization'], ['basic_auth' => 'basic_auth']);

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
          'supported_formats' => ['hal_json'],
        ],
      ],
    ]);

    $this->assertFalse($config_dependencies->onDependencyRemoval($rest_config, ['module' => ['node']]));
    $this->assertEquals([
      'GET' => [
        'supported_auth' => ['cookie'],
        'supported_formats' => ['json'],
      ],
      'POST' => [
        'supported_auth' => ['basic_auth'],
        'supported_formats' => ['hal_json'],
      ],
    ], $rest_config->get('configuration'));
  }

  /**
   * @covers ::onDependencyRemoval
   * @covers ::calculateDependenciesForMethodGranularity
   */
  public function testOnDependencyRemovalRemoveFormat() {
    $config_dependencies = new ConfigDependencies(['hal_json' => 'hal', 'json' => 'serialization'], ['basic_auth' => 'basic_auth']);

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
          'supported_formats' => ['hal_json'],
        ],
      ],
    ]);

    $this->assertTrue($config_dependencies->onDependencyRemoval($rest_config, ['module' => ['hal']]));
    $this->assertEquals(['json'], $rest_config->getFormats('GET'));
    $this->assertEquals([], $rest_config->getFormats('POST'));
    $this->assertEquals([
      'GET' => [
        'supported_auth' => ['cookie'],
        'supported_formats' => ['json'],
      ],
      'POST' => [
        'supported_auth' => ['basic_auth'],
      ],
    ], $rest_config->get('configuration'));
  }

  /**
   * @covers ::onDependencyRemoval
   * @covers ::calculateDependenciesForMethodGranularity
   */
  public function testOnDependencyRemovalRemoveAuth() {
    $config_dependencies = new ConfigDependencies(['hal_json' => 'hal', 'json' => 'serialization'], ['basic_auth' => 'basic_auth']);

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
          'supported_formats' => ['hal_json'],
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
        'supported_formats' => ['hal_json'],
      ],
    ], $rest_config->get('configuration'));
  }

  /**
   * @covers ::onDependencyRemoval
   * @covers ::calculateDependenciesForMethodGranularity
   */
  public function testOnDependencyRemovalRemoveAuthAndFormats() {
    $config_dependencies = new ConfigDependencies(['hal_json' => 'hal', 'json' => 'serialization'], ['basic_auth' => 'basic_auth']);

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
          'supported_formats' => ['hal_json'],
        ],
      ],
    ]);

    $this->assertTrue($config_dependencies->onDependencyRemoval($rest_config, ['module' => ['basic_auth', 'hal']]));
    $this->assertEquals(['json'], $rest_config->getFormats('GET'));
    $this->assertEquals(['cookie'], $rest_config->getAuthenticationProviders('GET'));
    $this->assertEquals([], $rest_config->getFormats('POST'));
    $this->assertEquals([], $rest_config->getAuthenticationProviders('POST'));
    $this->assertEquals([
      'GET' => [
        'supported_auth' => ['cookie'],
        'supported_formats' => ['json'],
      ],
    ], $rest_config->get('configuration'));
  }

}
