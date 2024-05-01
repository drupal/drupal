<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Session;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccessPolicyInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\CalculatedPermissionsItem;
use Drupal\Core\Session\RefinableCalculatedPermissions;
use Drupal\Core\Session\UserRolesAccessPolicy;
use Drupal\Tests\UnitTestCase;
use Drupal\user\RoleInterface;
use Drupal\user\RoleStorageInterface;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @coversDefaultClass \Drupal\Core\Session\UserRolesAccessPolicy
 * @group Session
 */
class UserRolesAccessPolicyTest extends UnitTestCase {

  /**
   * The access policy to test.
   *
   * @var \Drupal\Core\Session\UserRolesAccessPolicy
   */
  protected $accessPolicy;

  /**
   * The mocked entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->prophesize(EntityTypeManagerInterface::class);
    $this->accessPolicy = new UserRolesAccessPolicy($this->entityTypeManager->reveal());

    $cache_context_manager = $this->prophesize(CacheContextsManager::class);
    $cache_context_manager->assertValidTokens(Argument::any())->willReturn(TRUE);

    $container = $this->prophesize(ContainerInterface::class);
    $container->get('cache_contexts_manager')->willReturn($cache_context_manager->reveal());
    \Drupal::setContainer($container->reveal());
  }

  /**
   * @covers ::applies
   */
  public function testApplies(): void {
    $this->assertTrue($this->accessPolicy->applies(AccessPolicyInterface::SCOPE_DRUPAL));
    $this->assertFalse($this->accessPolicy->applies('another scope'));
    $this->assertFalse($this->accessPolicy->applies($this->randomString()));
  }

  /**
   * Tests the calculatePermissions method.
   *
   * @param array $roles
   *   The roles to grant the account.
   * @param bool $expect_admin_rights
   *   Whether to expect admin rights to be granted.
   *
   * @covers ::calculatePermissions
   * @dataProvider calculatePermissionsProvider
   */
  public function testCalculatePermissions(array $roles, bool $expect_admin_rights): void {
    $account = $this->prophesize(AccountInterface::class);
    $account->getRoles()->willReturn(array_keys($roles));

    $total_permissions = $cache_tags = $mocked_roles = [];
    foreach ($roles as $role_id => $role) {
      $total_permissions = array_merge($total_permissions, $role['permissions']);
      $cache_tags[] = "config:user.role.$role_id";

      $mocked_role = $this->prophesize(RoleInterface::class);
      $mocked_role->getPermissions()->willReturn($role['permissions']);
      $mocked_role->isAdmin()->willReturn($role['is_admin']);
      $mocked_role->getCacheTags()->willReturn(["config:user.role.$role_id"]);
      $mocked_role->getCacheContexts()->willReturn([]);
      $mocked_role->getCacheMaxAge()->willReturn(Cache::PERMANENT);
      $mocked_roles[$role_id] = $mocked_role->reveal();
    }

    $role_storage = $this->prophesize(RoleStorageInterface::class);
    $role_storage->loadMultiple(array_keys($roles))->willReturn($mocked_roles);
    $this->entityTypeManager->getStorage('user_role')->willReturn($role_storage->reveal());

    $calculated_permissions = $this->accessPolicy->calculatePermissions($account->reveal(), AccessPolicyInterface::SCOPE_DRUPAL);

    if (!empty($roles)) {
      $this->assertCount(1, $calculated_permissions->getItems(), 'Only one calculated permissions item was added.');
      $item = $calculated_permissions->getItem();

      if ($expect_admin_rights) {
        $this->assertSame([], $item->getPermissions());
        $this->assertTrue($item->isAdmin());
      }
      else {
        $this->assertSame($total_permissions, $item->getPermissions());
        $this->assertFalse($item->isAdmin());
      }
    }

    $this->assertSame($cache_tags, $calculated_permissions->getCacheTags());
    $this->assertSame(['user.roles'], $calculated_permissions->getCacheContexts());
    $this->assertSame(Cache::PERMANENT, $calculated_permissions->getCacheMaxAge());
  }

  /**
   * Data provider for testCalculatePermissions.
   *
   * @return array
   *   A list of test scenarios.
   */
  public static function calculatePermissionsProvider(): array {
    $cases['no-roles'] = [
      'roles' => [],
      'expect_admin_rights' => FALSE,
    ];
    $cases['some-roles'] = [
      'roles' => [
        'role_foo' => [
          'permissions' => ['foo'],
          'is_admin' => FALSE,
        ],
        'role_bar' => [
          'permissions' => ['bar'],
          'is_admin' => FALSE,
        ],
      ],
      'expect_admin_rights' => FALSE,
    ];
    $cases['admin-role'] = [
      'roles' => [
        'role_foo' => [
          'permissions' => ['foo'],
          'is_admin' => FALSE,
        ],
        'role_bar' => [
          'permissions' => ['bar'],
          'is_admin' => TRUE,
        ],
      ],
      'expect_admin_rights' => TRUE,
    ];
    return $cases;
  }

  /**
   * Tests the alterPermissions method.
   *
   * @covers ::alterPermissions
   */
  public function testAlterPermissions(): void {
    $account = $this->prophesize(AccountInterface::class);

    $calculated_permissions = new RefinableCalculatedPermissions();
    $calculated_permissions->addItem(new CalculatedPermissionsItem(['foo']));
    $calculated_permissions->addCacheTags(['bar']);
    $calculated_permissions->addCacheContexts(['baz']);

    $this->accessPolicy->alterPermissions($account->reveal(), AccessPolicyInterface::SCOPE_DRUPAL, $calculated_permissions);
    $this->assertSame(['foo'], $calculated_permissions->getItem()->getPermissions());
    $this->assertSame(['bar'], $calculated_permissions->getCacheTags());
    $this->assertSame(['baz'], $calculated_permissions->getCacheContexts());
  }

  /**
   * Tests the getPersistentCacheContexts method.
   *
   * @covers ::getPersistentCacheContexts
   */
  public function testGetPersistentCacheContexts(): void {
    $this->assertSame(['user.roles'], $this->accessPolicy->getPersistentCacheContexts(AccessPolicyInterface::SCOPE_DRUPAL));
  }

}
