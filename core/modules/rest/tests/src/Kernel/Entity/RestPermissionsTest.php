<?php

namespace Drupal\Tests\rest\Kernel\Entity;

use Drupal\KernelTests\KernelTestBase;
use Drupal\rest\Entity\RestResourceConfig;
use Drupal\rest\RestResourceConfigInterface;

/**
 * @coversDefaultClass \Drupal\rest\RestPermissions
 *
 * @group rest
 */
class RestPermissionsTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'rest',
    'dblog',
    'serialization',
    'basic_auth',
    'user',
    'hal',
  ];

  /**
   * @covers ::permissions
   */
  public function testPermissions() {
    RestResourceConfig::create([
      'id' => 'dblog',
      'plugin_id' => 'dblog',
      'granularity' => RestResourceConfigInterface::METHOD_GRANULARITY,
      'configuration' => [
        'GET' => [
          'supported_auth' => ['cookie'],
          'supported_formats' => ['json'],
        ],
      ],
    ])->save();

    $permissions = $this->container->get('user.permissions')->getPermissions();
    $this->assertArrayHasKey('restful get dblog', $permissions);
    $this->assertSame(['config' => ['rest.resource.dblog']], $permissions['restful get dblog']['dependencies']);
  }

}
