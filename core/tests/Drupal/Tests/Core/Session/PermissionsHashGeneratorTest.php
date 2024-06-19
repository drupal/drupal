<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Session;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\PrivateKey;
use Drupal\Core\Session\AccessPolicyProcessorInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Session\CalculatedPermissions;
use Drupal\Core\Session\CalculatedPermissionsItem;
use Drupal\Core\Session\PermissionsHashGenerator;
use Drupal\Core\Session\RefinableCalculatedPermissions;
use Drupal\Core\Site\Settings;
use Drupal\Tests\UnitTestCase;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\Core\Session\PermissionsHashGenerator
 * @group Session
 */
class PermissionsHashGeneratorTest extends UnitTestCase {

  /**
   * The mocked user 1 account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account1;

  /**
   * The mocked user 2 account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account2;

  /**
   * The mocked cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $staticCache;

  /**
   * The mocked access policy processor.
   *
   * @var \Drupal\Core\Session\AccessPolicyProcessorInterface|\Prophecy\Prophecy\ObjectProphecy
   */
  protected $processor;

  /**
   * The permission hash class being tested.
   *
   * @var \Drupal\Core\Session\PermissionsHashGeneratorInterface
   */
  protected $permissionsHash;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    new Settings(['hash_salt' => 'test']);

    $this->account1 = $this->prophesize(AccountInterface::class);
    $this->account1->id()->willReturn(1);
    $this->account1 = $this->account1->reveal();

    $this->account2 = $this->prophesize(AccountInterface::class);
    $this->account2->id()->willReturn(2);
    $this->account2 = $this->account2->reveal();

    $private_key = $this->prophesize(PrivateKey::class);
    $private_key->get()->willReturn(Crypt::randomBytesBase64(55));

    $this->staticCache = $this->prophesize(CacheBackendInterface::class);
    $this->staticCache->get(Argument::any())->willReturn(FALSE);
    $this->staticCache->set(Argument::cetera())->shouldBeCalled();

    $this->processor = $this->prophesize(AccessPolicyProcessorInterface::class);

    $this->permissionsHash = new PermissionsHashGenerator(
      $private_key->reveal(),
      $this->staticCache->reveal(),
      $this->processor->reveal()
    );
  }

  /**
   * Tests the generate method for regular accounts.
   *
   * @covers ::generate
   */
  public function testGenerateRegular(): void {
    $permissions = new CalculatedPermissions(
      (new RefinableCalculatedPermissions())->addItem(new CalculatedPermissionsItem([
        'permission foo',
        'permission bar',
      ]))
    );
    $this->processor->processAccessPolicies($this->account1)->willReturn($permissions);
    $this->processor->processAccessPolicies($this->account2)->willReturn($permissions);

    // Check that two accounts with the same permissions generate the same hash.
    $hash_1 = $this->permissionsHash->generate($this->account1);
    $hash_2 = $this->permissionsHash->generate($this->account2);
    $this->assertSame($hash_1, $hash_2, 'Different users with the same permissions generate the same permissions hash.');
  }

  /**
   * Tests the generate method for admin users.
   *
   * @covers ::generate
   */
  public function testGenerateAdmin(): void {
    $permissions = new CalculatedPermissions((new RefinableCalculatedPermissions())->addItem(new CalculatedPermissionsItem([], TRUE)));
    $this->processor->processAccessPolicies($this->account1)->willReturn($permissions);
    $this->processor->processAccessPolicies($this->account2)->willReturn($permissions);

    // Check that two accounts with the same permissions generate the same hash.
    $hash_1 = $this->permissionsHash->generate($this->account1);
    $hash_2 = $this->permissionsHash->generate($this->account2);
    $this->assertSame($hash_1, $hash_2, 'Different admins generate the same permissions hash.');

    // Check that the generated hash is simply 'is-admin'.
    $this->assertSame('is-admin', $hash_1, 'Admins generate the string "is-admin" as their permissions hash.');
  }

  /**
   * Tests the generate method with no access policies.
   *
   * @covers ::generate
   */
  public function testGenerateNoAccessPolicies(): void {
    $permissions = new CalculatedPermissions(new RefinableCalculatedPermissions());
    $this->processor->processAccessPolicies($this->account1)->willReturn($permissions);
    $this->processor->processAccessPolicies($this->account2)->willReturn($permissions);

    // Check that two accounts with the same permissions generate the same hash.
    $hash_1 = $this->permissionsHash->generate($this->account1);
    $hash_2 = $this->permissionsHash->generate($this->account2);
    $this->assertSame($hash_1, $hash_2, 'Different accounts generate the same permissions hash when there are no policies.');

    // Check that the generated hash is simply 'no-access-policies'.
    $this->assertSame('no-access-policies', $hash_1, 'Accounts generate the string "is-admin" as their permissions hash when no policies are defined.');
  }

  /**
   * Tests the generate method's caching.
   *
   * @covers ::generate
   */
  public function testGenerateCache(): void {
    $permissions = new CalculatedPermissions(new RefinableCalculatedPermissions());
    $this->processor->processAccessPolicies($this->account1)->willReturn($permissions);
    $this->processor->processAccessPolicies($this->account2)->willReturn($permissions);

    // Test that set is called with the right cache ID.
    $this->staticCache->set('permissions_hash_1', 'no-access-policies', Cache::PERMANENT, [])->shouldBeCalledOnce();
    $this->staticCache->set('permissions_hash_2', 'no-access-policies', Cache::PERMANENT, [])->shouldBeCalledOnce();
    $this->permissionsHash->generate($this->account1);
    $this->permissionsHash->generate($this->account2);

    // Verify that ::set() isn't called more when ::get() returns something.
    $cache_return = new \stdClass();
    $cache_return->data = 'no-access-policies';
    $this->staticCache->get('permissions_hash_1')->willReturn($cache_return);
    $this->staticCache->get('permissions_hash_2')->willReturn($cache_return);

    $this->permissionsHash->generate($this->account1);
    $this->permissionsHash->generate($this->account2);
  }

}
