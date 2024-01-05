<?php

declare(strict_types=1);

namespace Drupal\Tests\rest\Unit\Entity;

use Drupal\rest\Entity\RestResourceConfig;
use Drupal\rest\RestResourceConfigInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\rest\Entity\RestResourceConfig
 *
 * @group rest
 */
class RestResourceConfigTest extends UnitTestCase {

  /**
   * Asserts that rest methods are normalized to upper case.
   *
   * This also tests that no exceptions are thrown during that method so that
   * alternate methods such as OPTIONS and PUT are supported.
   */
  public function testNormalizeRestMethod() {
    $expected = ['GET', 'PUT', 'POST', 'PATCH', 'DELETE', 'OPTIONS', 'FOO'];
    $methods = ['get', 'put', 'post', 'patch', 'delete', 'options', 'foo'];
    $configuration = [];
    foreach ($methods as $method) {
      $configuration[$method] = [
        'supported_auth' => ['cookie'],
        'supported_formats' => ['json'],
      ];
    }

    $entity = new RestResourceConfig([
      'plugin_id' => 'entity:entity_test',
      'granularity' => RestResourceConfigInterface::METHOD_GRANULARITY,
      'configuration' => $configuration,
    ], 'rest_resource_config');

    $this->assertEquals($expected, $entity->getMethods());
  }

}
