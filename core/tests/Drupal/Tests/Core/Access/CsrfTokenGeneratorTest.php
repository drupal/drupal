<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Access\CsrfTokenGeneratorTest.
 */

namespace Drupal\Tests\Core\Access;

use Drupal\Core\Site\Settings;
use Drupal\Tests\UnitTestCase;
use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Component\Utility\Crypt;
use Symfony\Component\HttpFoundation\Request;

/**
 * Tests the CsrfTokenGenerator class.
 *
 * @group Access
 */
class CsrfTokenGeneratorTest extends UnitTestCase {

  /**
   * The CSRF token generator.
   *
   * @var \Drupal\Core\Access\CsrfTokenGenerator
   */
  protected $generator;

  /**
   * The mock private key instance.
   *
   * @var \Drupal\Core\PrivateKey|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $privateKey;

  /**
   * {@inheritdoc}
   */
  function setUp() {
    parent::setUp();
    $this->key = Crypt::randomBytesBase64(55);

    $this->privateKey = $this->getMockBuilder('Drupal\Core\PrivateKey')
      ->disableOriginalConstructor()
      ->setMethods(array('get'))
      ->getMock();

    $this->privateKey->expects($this->any())
      ->method('get')
      ->will($this->returnValue($this->key));

    $settings = array(
      'hash_salt' => $this->randomMachineName(),
    );

    new Settings($settings);

    $this->generator = new CsrfTokenGenerator($this->privateKey);
  }

  /**
   * Tests CsrfTokenGenerator::get().
   */
  public function testGet() {
    $this->assertInternalType('string', $this->generator->get());
    $this->assertNotSame($this->generator->get(), $this->generator->get($this->randomMachineName()));
    $this->assertNotSame($this->generator->get($this->randomMachineName()), $this->generator->get($this->randomMachineName()));
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
    // Ensure that there is a valid token seed on the session.
    $this->generator->get();

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
    // Ensure that there is a valid token seed on the session.
    $this->generator->get();

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

  /**
   * Tests the exception thrown when no 'hash_salt' is provided in settings.
   *
   * @expectedException \RuntimeException
   */
  public function testGetWithNoHashSalt() {
    // Update settings with no hash salt.
    new Settings(array());
    $generator = new CsrfTokenGenerator($this->privateKey);
    $generator->get();
  }

}
