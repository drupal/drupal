<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Session\PermissionsHashTest.
 */

namespace Drupal\Tests\Core\Session {

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Session\PermissionsHashGenerator;
use Drupal\Core\Site\Settings;
use Drupal\Tests\UnitTestCase;


/**
 * @coversDefaultClass \Drupal\Core\Session\PermissionsHashGenerator
 * @group Session
 */
class PermissionsHashTest extends UnitTestCase {

  /**
   * A mocked account.
   *
   * @var \Drupal\user\UserInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $account1;

  /**
   * An "updated" mocked account.
   *
   * @var \Drupal\user\UserInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $account1Updated;

  /**
   * A different account.
   *
   * @var \Drupal\user\UserInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $account2;

  /**
   * The mocked private key service.
   *
   * @var \Drupal\Core\PrivateKey|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $privateKey;

  /**
   * The mocked cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $cache;

  /**
   * The permission hash class being tested.
   *
   * @var \Drupal\Core\Session\PermissionsHashGeneratorInterface
   */
  protected $permissionsHash;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    new Settings(array('hash_salt' => 'test'));

    // Account 1: 'administrator' and 'authenticated' roles.
    $roles_1 = array('administrator', 'authenticated');
    $this->account1 = $this->getMockBuilder('Drupal\user\Entity\User')
      ->disableOriginalConstructor()
      ->setMethods(array('getRoles'))
      ->getMock();
    $this->account1->expects($this->any())
      ->method('getRoles')
      ->will($this->returnValue($roles_1));

    // Account 2: 'authenticated' and 'administrator' roles (different order).
    $roles_2 = array('authenticated', 'administrator');
    $this->account2 = $this->getMockBuilder('Drupal\user\Entity\User')
      ->disableOriginalConstructor()
      ->setMethods(array('getRoles'))
      ->getMock();
    $this->account2->expects($this->any())
      ->method('getRoles')
      ->will($this->returnValue($roles_2));

    // Updated account 1: now also 'editor' role.
    $roles_1_updated = array('editor', 'administrator', 'authenticated');
    $this->account1Updated = $this->getMockBuilder('Drupal\user\Entity\User')
      ->disableOriginalConstructor()
      ->setMethods(array('getRoles'))
      ->getMock();
    $this->account1Updated->expects($this->any())
      ->method('getRoles')
      ->will($this->returnValue($roles_1_updated));

    // Mocked private key + cache services.
    $random = Crypt::randomBytesBase64(55);
    $this->privateKey = $this->getMockBuilder('Drupal\Core\PrivateKey')
      ->disableOriginalConstructor()
      ->setMethods(array('get'))
      ->getMock();
    $this->privateKey->expects($this->any())
      ->method('get')
      ->will($this->returnValue($random));
    $this->cache = $this->getMockBuilder('Drupal\Core\Cache\CacheBackendInterface')
      ->disableOriginalConstructor()
      ->getMock();

    $this->permissionsHash = new PermissionsHashGenerator($this->privateKey, $this->cache);
  }

  /**
   * Tests the generate() method.
   */
  public function testGenerate() {
    // Ensure that two user accounts with the same roles generate the same hash.
    $hash_1 = $this->permissionsHash->generate($this->account1);
    $hash_2 = $this->permissionsHash->generate($this->account2);
    $this->assertSame($hash_1, $hash_2, 'Different users with the same roles generate the same permissions hash.');

    // Compare with hash for user account 1 with an additional role.
    $updated_hash_1 = $this->permissionsHash->generate($this->account1Updated);
    $this->assertNotSame($hash_1, $updated_hash_1, 'Same user with updated roles generates different permissions hash.');
  }

  /**
   * Tests the generate method with cache returned.
   */
  public function testGenerateCache() {
    // Set expectations for the mocked cache backend.
    $expected_cid = 'user_permissions_hash:administrator,authenticated';

    $mock_cache = new \stdClass();
    $mock_cache->data = 'test_hash_here';

    $this->cache->expects($this->once())
      ->method('get')
      ->with($expected_cid)
      ->will($this->returnValue($mock_cache));
    $this->cache->expects($this->never())
      ->method('set');

    $this->permissionsHash->generate($this->account1);
  }

  /**
   * Tests the generate method with no cache returned.
   */
  public function testGenerateNoCache() {
    // Set expectations for the mocked cache backend.
    $expected_cid = 'user_permissions_hash:administrator,authenticated';

    $this->cache->expects($this->once())
      ->method('get')
      ->with($expected_cid)
      ->will($this->returnValue(FALSE));
    $this->cache->expects($this->once())
      ->method('set')
      ->with($expected_cid, $this->isType('string'));

    $this->permissionsHash->generate($this->account1);
  }

}

}

namespace {

  // @todo remove once user_role_permissions() can be injected.
  if (!function_exists('user_role_permissions')) {
    function user_role_permissions(array $roles) {
      $role_permissions = array();
      foreach ($roles as $rid) {
        $role_permissions[$rid] = array();
      }
      return $role_permissions;
    }
  }

}
