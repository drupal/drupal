<?php

declare(strict_types=1);

namespace Drupal\Tests\user\Unit;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Tests\UnitTestCase;
use Drupal\user\Access\PermissionAccessCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Routing\Route;

/**
 * Tests Drupal\user\Access\PermissionAccessCheck.
 */
#[CoversClass(PermissionAccessCheck::class)]
#[Group('Routing')]
#[Group('Access')]
class PermissionAccessCheckTest extends UnitTestCase {

  /**
   * The tested access checker.
   *
   * @var \Drupal\user\Access\PermissionAccessCheck
   */
  public $accessCheck;

  /**
   * The dependency injection container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerBuilder
   */
  protected $container;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->container = new ContainerBuilder();
    $cache_contexts_manager = $this->prophesize(CacheContextsManager::class);
    $cache_contexts_manager->assertValidTokens()->willReturn(TRUE);
    $cache_contexts_manager->reveal();
    $this->container->set('cache_contexts_manager', $cache_contexts_manager);
    \Drupal::setContainer($this->container);

    $this->accessCheck = new PermissionAccessCheck();
  }

  /**
   * Provides data for the testAccess method.
   *
   * @return array
   *   An array of test data.
   */
  public static function providerTestAccess() {
    return [
      [[], FALSE],
      [['_permission' => 'allowed'], TRUE, ['user.permissions']],
      [
        ['_permission' => 'denied'],
        FALSE,
        ['user.permissions'],
        "The 'denied' permission is required.",
      ],
      [['_permission' => 'allowed+denied'], TRUE, ['user.permissions']],
      [['_permission' => 'allowed+denied+other'], TRUE, ['user.permissions']],
      [
        ['_permission' => 'allowed,denied'],
        FALSE,
        ['user.permissions'],
        "The following permissions are required: 'allowed' AND 'denied'.",
      ],
    ];
  }

  /**
   * Tests the access check method.
   *
   * @legacy-covers ::access
   */
  #[DataProvider('providerTestAccess')]
  public function testAccess($requirements, $access, array $contexts = [], $message = ''): void {
    $access_result = AccessResult::allowedIf($access)->addCacheContexts($contexts);
    if (!empty($message)) {
      $access_result->setReason($message);
    }
    $user = $this->createMock('Drupal\Core\Session\AccountInterface');
    $user->expects($this->any())
      ->method('hasPermission')
      ->willReturnMap([
        ['allowed', TRUE],
        ['denied', FALSE],
        ['other', FALSE],
      ]);
    $route = new Route('', [], $requirements);

    $this->assertEquals($access_result, $this->accessCheck->access($route, $user));
  }

}
