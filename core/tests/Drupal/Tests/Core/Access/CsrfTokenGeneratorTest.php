<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Access\CsrfTokenGeneratorTest.
 */

namespace Drupal\Tests\Core\Access {

use Drupal\Tests\UnitTestCase;
use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Component\Utility\Crypt;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the CSRF token generator.
 */
class CsrfTokenGeneratorTest extends UnitTestCase {

  /**
   * The CSRF token generator.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator
   */
  protected $generator;

  public static function getInfo() {
    return array(
      'name' => 'CsrfTokenGenerator test',
      'description' => 'Tests the CsrfTokenGenerator class.',
      'group' => 'Access'
    );
  }

  /**
   * {@inheritdoc}
   */
  function setUp() {
    parent::setUp();
    $this->key = Crypt::randomStringHashed(55);

    $private_key = $this->getMockBuilder('Drupal\Core\PrivateKey')
      ->disableOriginalConstructor()
      ->setMethods(array('get'))
      ->getMock();

    $private_key->expects($this->any())
      ->method('get')
      ->will($this->returnValue($this->key));

    $this->generator = new CsrfTokenGenerator($private_key);
    $this->generator->setRequest(new Request());
  }

  /**
   * Tests CsrfTokenGenerator::get().
   */
  public function testGet() {
    $this->assertInternalType('string', $this->generator->get());
    $this->assertNotSame($this->generator->get(), $this->generator->get($this->randomName()));
    $this->assertNotSame($this->generator->get($this->randomName()), $this->generator->get($this->randomName()));
  }

  /**
   * Tests CsrfTokenGenerator::validate().
   */
  public function testValidate() {
    $token = $this->generator->get();
    $this->assertTrue($this->generator->validate($token));
    $this->assertFalse($this->generator->validate($token, 'foo'));


    $token = $this->generator->get('bar');
    $this->assertTrue($this->generator->validate($token, 'bar'));

    // Check the skip_anonymous option with both a anonymous user and a real
    // user.
    $account = $this->getMock('Drupal\Core\Session\AccountInterface');
    $account->expects($this->once())
      ->method('isAnonymous')
      ->will($this->returnValue(TRUE));
    $request = new Request();
    $request->attributes->set('_account', $account);
    $this->generator->setRequest($request);
    $this->assertTrue($this->generator->validate($token, 'foo', TRUE));

    $account = $this->getMock('Drupal\Core\Session\AccountInterface');
    $account->expects($this->once())
      ->method('isAnonymous')
      ->will($this->returnValue(FALSE));
    $request = new Request();
    $request->attributes->set('_account', $account);
    $this->generator->setRequest($request);

    $this->assertFalse($this->generator->validate($token, 'foo', TRUE));
  }

}

}

/**
 * @todo Remove this when https://drupal.org/node/2036259 is resolved.
 */
namespace {
  if (!function_exists('drupal_get_hash_salt')) {
    function drupal_get_hash_salt() {
      return hash('sha256', 'test_hash_salt');
    }
  }
}
