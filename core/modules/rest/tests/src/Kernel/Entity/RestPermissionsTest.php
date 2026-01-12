<?php

declare(strict_types=1);

namespace Drupal\Tests\rest\Kernel\Entity;

use Drupal\KernelTests\KernelTestBase;
use Drupal\rest\Entity\RestResourceConfig;
use Drupal\rest\RestPermissions;
use Drupal\rest\RestResourceConfigInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

/**
 * Tests Drupal\rest\RestPermissions.
 */
#[CoversClass(RestPermissions::class)]
#[Group('rest')]
#[RunTestsInSeparateProcesses]
class RestPermissionsTest extends KernelTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'rest',
    'dblog',
    'serialization',
    'user',
  ];

  /**
   * Tests permissions.
   */
  public function testPermissions(): void {
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
