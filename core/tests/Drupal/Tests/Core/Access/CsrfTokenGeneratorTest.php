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
    $this->key = Crypt::randomBytesBase64(55);

    $private_key = $this->getMockBuilder('Drupal\Core\PrivateKey')
      ->disableOriginalConstructor()
      ->setMethods(array('get'))
      ->getMock();

    $private_key->expects($this->any())
      ->method('get')
      ->will($this->returnValue($this->key));

    $this->generator = new CsrfTokenGenerator($private_key);
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
    $this->generator->setCurrentUser($account);
    $this->assertTrue($this->generator->validate($token, 'foo', TRUE));

    $account = $this->getMock('Drupal\Core\Session\AccountInterface');
    $account->expects($this->once())
      ->method('isAnonymous')
      ->will($this->returnValue(FALSE));
    $this->generator->setCurrentUser($account);

    $this->assertFalse($this->generator->validate($token, 'foo', TRUE));
  }

  /**
   * Tests CsrfTokenGenerator::validate() with different parameter types.
   *
   * @param mixed $token
   *   The token to be validated.
   * @param mixed $value
   *   (optional) An additional value to base the token on.
   *
   * @dataProvider providerTestValidateParameterTypes
   */
  public function testValidateParameterTypes($token, $value) {
    // The following check might throw PHP fatals and notices, so we disable
    // error assertions.
    set_error_handler(function () {return TRUE;});
    $this->assertFalse($this->generator->validate($token, $value));
    restore_error_handler();
  }

  /**
   * Provides data for testValidateParameterTypes.
   *
   * @return array
   *   An array of data used by the test.
   */
  public function providerTestValidateParameterTypes() {
    return array(
      array(array(), ''),
      array(TRUE, 'foo'),
      array(0, 'foo'),
    );
  }

  /**
   * Tests CsrfTokenGenerator::validate() with invalid parameter types.
   *
   * @param mixed $token
   *   The token to be validated.
   * @param mixed $value
   *   (optional) An additional value to base the token on.
   *
   * @dataProvider providerTestInvalidParameterTypes
   * @expectedException InvalidArgumentException
   */
  public function testInvalidParameterTypes($token, $value = '') {
    $this->generator->validate($token, $value);
  }

  /**
   * Provides data for testInvalidParameterTypes.
   *
   * @return array
   *   An array of data used by the test.
   */
  public function providerTestInvalidParameterTypes() {
    return array(
      array(NULL, new \stdClass()),
      array(0, array()),
      array('', array()),
      array(array(), array()),
    );
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
