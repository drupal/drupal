<?php

namespace Drupal\Tests\Core\Session;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Session\PermissionsHashGenerator;
use Drupal\Core\Site\Settings;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Session\PermissionsHashGenerator
 * @group Session
 */
class PermissionsHashGeneratorTest extends UnitTestCase {

  /**
   * The mocked super user account.
   *
   * @var \Drupal\user\UserInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $account1;

  /**
   * A mocked account.
   *
   * @var \Drupal\user\UserInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $account2;

  /**
   * An "updated" mocked account.
   *
   * @var \Drupal\user\UserInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $account2Updated;

  /**
   * A different account.
   *
   * @var \Drupal\user\UserInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $account3;

  /**
   * The mocked private key service.
   *
   * @var \Drupal\Core\PrivateKey|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $privateKey;

  /**
   * The mocked cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $cache;

  /**
   * The mocked cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $staticCache;

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

    // The mocked super user account, with the same roles as Account 2.
    $this->account1 = $this->getMockBuilder('Drupal\user\Entity\User')
      ->disableOriginalConstructor()
      ->onlyMethods(['getRoles', 'id'])
      ->getMock();
    $this->account1->expects($this->any())
      ->method('id')
      ->willReturn(1);
    $this->account1->expects($this->never())
      ->method('getRoles');

    // Account 2: 'administrator' and 'authenticated' roles.
    $roles_1 = ['administrator', 'authenticated'];
    $this->account2 = $this->getMockBuilder('Drupal\user\Entity\User')
      ->disableOriginalConstructor()
      ->onlyMethods(['getRoles', 'id'])
      ->getMock();
    $this->account2->expects($this->any())
      ->method('getRoles')
      ->will($this->returnValue($roles_1));
    $this->account2->expects($this->any())
      ->method('id')
      ->willReturn(2);

    // Account 3: 'authenticated' and 'administrator' roles (different order).
    $roles_3 = ['authenticated', 'administrator'];
    $this->account3 = $this->getMockBuilder('Drupal\user\Entity\User')
      ->disableOriginalConstructor()
      ->onlyMethods(['getRoles', 'id'])
      ->getMock();
    $this->account3->expects($this->any())
      ->method('getRoles')
      ->will($this->returnValue($roles_3));
    $this->account3->expects($this->any())
      ->method('id')
      ->willReturn(3);

    // Updated account 2: now also 'editor' role.
    $roles_2_updated = ['editor', 'administrator', 'authenticated'];
    $this->account2Updated = $this->getMockBuilder('Drupal\user\Entity\User')
      ->disableOriginalConstructor()
      ->onlyMethods(['getRoles', 'id'])
      ->getMock();
    $this->account2Updated->expects($this->any())
      ->method('getRoles')
      ->will($this->returnValue($roles_2_updated));
    $this->account2Updated->expects($this->any())
      ->method('id')
      ->willReturn(2);

    // Mocked private key + cache services.
    $random = Crypt::randomBytesBase64(55);
    $this->privateKey = $this->getMockBuilder('Drupal\Core\PrivateKey')
      ->disableOriginalConstructor()
      ->onlyMethods(['get'])
      ->getMock();
    $this->privateKey->expects($this->any())
      ->method('get')
      ->will($this->returnValue($random));
    $this->cache = $this->getMockBuilder('Drupal\Core\Cache\CacheBackendInterface')
      ->disableOriginalConstructor()
      ->getMock();
    $this->staticCache = $this->getMockBuilder('Drupal\Core\Cache\CacheBackendInterface')
      ->disableOriginalConstructor()
      ->getMock();

    $this->permissionsHash = new PermissionsHashGenerator($this->privateKey, $this->cache, $this->staticCache);
  }

  /**
   * @covers ::generate
   */
  public function testGenerate() {
    // Ensure that the super user (user 1) always gets the same hash.
    $super_user_hash = $this->permissionsHash->generate($this->account1);

    // Ensure that two user accounts with the same roles generate the same hash.
    $hash_2 = $this->permissionsHash->generate($this->account2);
    $hash_3 = $this->permissionsHash->generate($this->account3);
    $this->assertSame($hash_2, $hash_3, 'Different users with the same roles generate the same permissions hash.');

    $this->assertNotSame($hash_2, $super_user_hash, 'User 1 has a different hash despite having the same roles');

    // Compare with hash for user account 1 with an additional role.
    $updated_hash_2 = $this->permissionsHash->generate($this->account2Updated);
    $this->assertNotSame($hash_2, $updated_hash_2, 'Same user with updated roles generates different permissions hash.');
  }

  /**
   * @covers ::generate
   */
  public function testGeneratePersistentCache() {
    // Set expectations for the mocked cache backend.
    $expected_cid = 'user_permissions_hash:administrator,authenticated';

    $mock_cache = new \stdClass();
    $mock_cache->data = 'test_hash_here';

    $this->staticCache->expects($this->once())
      ->method('get')
      ->with($expected_cid)
      ->will($this->returnValue(FALSE));
    $this->staticCache->expects($this->once())
      ->method('set')
      ->with($expected_cid, $this->isType('string'));

    $this->cache->expects($this->once())
      ->method('get')
      ->with($expected_cid)
      ->will($this->returnValue($mock_cache));
    $this->cache->expects($this->never())
      ->method('set');

    $this->permissionsHash->generate($this->account2);
  }

  /**
   * @covers ::generate
   */
  public function testGenerateStaticCache() {
    // Set expectations for the mocked cache backend.
    $expected_cid = 'user_permissions_hash:administrator,authenticated';

    $mock_cache = new \stdClass();
    $mock_cache->data = 'test_hash_here';

    $this->staticCache->expects($this->once())
      ->method('get')
      ->with($expected_cid)
      ->will($this->returnValue($mock_cache));
    $this->staticCache->expects($this->never())
      ->method('set');

    $this->cache->expects($this->never())
      ->method('get');
    $this->cache->expects($this->never())
      ->method('set');

    $this->permissionsHash->generate($this->account2);
  }

  /**
   * Tests the generate method with no cache returned.
   */
  public function testGenerateNoCache() {
    // Set expectations for the mocked cache backend.
    $expected_cid = 'user_permissions_hash:administrator,authenticated';

    $this->staticCache->expects($this->once())
      ->method('get')
      ->with($expected_cid)
      ->will($this->returnValue(FALSE));
    $this->staticCache->expects($this->once())
      ->method('set')
      ->with($expected_cid, $this->isType('string'));

    $this->cache->expects($this->once())
      ->method('get')
      ->with($expected_cid)
      ->will($this->returnValue(FALSE));
    $this->cache->expects($this->once())
      ->method('set')
      ->with($expected_cid, $this->isType('string'));

    $this->permissionsHash->generate($this->account2);
  }

}

namespace Drupal\Core\Session;

// @todo remove once user_role_permissions() can be injected.
if (!function_exists('user_role_permissions')) {

  function user_role_permissions(array $roles) {
    $role_permissions = [];
    foreach ($roles as $rid) {
      $role_permissions[$rid] = [];
    }
    return $role_permissions;
  }

}
