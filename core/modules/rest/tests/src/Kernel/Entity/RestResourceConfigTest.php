<?php

namespace Drupal\Tests\rest\Kernel\Entity;

use Drupal\KernelTests\KernelTestBase;
use Drupal\rest\Entity\RestResourceConfig;
use Drupal\rest\RestResourceConfigInterface;

/**
 * @coversDefaultClass \Drupal\rest\Entity\RestResourceConfig
 *
 * @group rest
 */
class RestResourceConfigTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'rest',
    'entity_test',
    'serialization',
    'basic_auth',
    'user',
    'hal',
  ];

  /**
   * @covers ::calculateDependencies
   */
  public function testCalculateDependencies() {
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

    $rest_config->calculateDependencies();
    $this->assertEquals(['module' => ['basic_auth', 'entity_test', 'hal', 'serialization', 'user']], $rest_config->getDependencies());
  }

}
