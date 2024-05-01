<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Session;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\Context\CacheContextsManager;
use Drupal\Core\Session\AccessPolicyInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\CalculatedPermissionsItem;
use Drupal\Core\Session\RefinableCalculatedPermissions;
use Drupal\Core\Session\SuperUserAccessPolicy;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @coversDefaultClass \Drupal\Core\Session\SuperUserAccessPolicy
 * @group Session
 */
class SuperUserAccessPolicyTest extends UnitTestCase {

  /**
   * The access policy to test.
   *
   * @var \Drupal\Core\Session\SuperUserAccessPolicy
   */
  protected $accessPolicy;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->accessPolicy = new SuperUserAccessPolicy();

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
   * @param int $uid
   *   The UID for the account the policy checks.
   * @param bool $expect_admin_rights
   *   Whether to expect admin rights to be granted.
   *
   * @covers ::calculatePermissions
   * @dataProvider calculatePermissionsProvider
   */
  public function testCalculatePermissions(int $uid, bool $expect_admin_rights): void {
    $account = $this->prophesize(AccountInterface::class);
    $account->id()->willReturn($uid);
    $calculated_permissions = $this->accessPolicy->calculatePermissions($account->reveal(), AccessPolicyInterface::SCOPE_DRUPAL);

    if ($expect_admin_rights) {
      $this->assertCount(1, $calculated_permissions->getItems(), 'Only one calculated permissions item was added.');
      $item = $calculated_permissions->getItem();
      $this->assertSame([], $item->getPermissions());
      $this->assertTrue($item->isAdmin());
    }

    $this->assertSame([], $calculated_permissions->getCacheTags());
    $this->assertSame(['user.is_super_user'], $calculated_permissions->getCacheContexts());
    $this->assertSame(Cache::PERMANENT, $calculated_permissions->getCacheMaxAge());
  }

  /**
   * Data provider for testCalculatePermissions.
   *
   * @return array
   *   A list of test scenarios.
   */
  public static function calculatePermissionsProvider(): array {
    $cases['is-super-user'] = [1, TRUE];
    $cases['is-normal-user'] = [2, FALSE];
    return $cases;
  }

  /**
   * Tests the alterPermissions method.
   *
   * @param int $uid
   *   The UID for the account the policy checks.
   *
   * @covers ::alterPermissions
   * @dataProvider alterPermissionsProvider
   */
  public function testAlterPermissions(int $uid): void {
    $account = $this->prophesize(AccountInterface::class);
    $account->id()->willReturn($uid);

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
   * Data provider for testAlterPermissions.
   *
   * @return array
   *   A list of test scenarios.
   */
  public static function alterPermissionsProvider(): array {
    $cases['is-super-user'] = [1];
    $cases['is-normal-user'] = [2];
    return $cases;
  }

  /**
   * Tests the getPersistentCacheContexts method.
   *
   * @covers ::getPersistentCacheContexts
   */
  public function testGetPersistentCacheContexts(): void {
    $this->assertSame(['user.is_super_user'], $this->accessPolicy->getPersistentCacheContexts(AccessPolicyInterface::SCOPE_DRUPAL));
  }

}
