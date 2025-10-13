<?php

declare(strict_types=1);

namespace Drupal\Tests\rest\Kernel\Entity;

use Drupal\KernelTests\KernelTestBase;
use Drupal\rest\Entity\RestResourceConfig;
use Drupal\rest\RestResourceConfigInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests Drupal\rest\Entity\RestResourceConfig.
 */
#[CoversClass(RestResourceConfig::class)]
#[Group('rest')]
#[RunTestsInSeparateProcesses]
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
  ];

  /**
   * Tests calculate dependencies.
   *
   * @legacy-covers ::calculateDependencies
   */
  public function testCalculateDependencies(): void {
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

    $rest_config->calculateDependencies();
    $this->assertEquals(['module' => ['basic_auth', 'entity_test', 'serialization', 'user']], $rest_config->getDependencies());
  }

}
