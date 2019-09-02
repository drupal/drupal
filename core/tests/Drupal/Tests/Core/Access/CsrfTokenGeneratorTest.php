<?php

namespace Drupal\Tests\Core\Access;

use Drupal\Core\Site\Settings;
use Drupal\Tests\UnitTestCase;
use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Component\Utility\Crypt;

/**
 * Tests the CsrfTokenGenerator class.
 *
 * @group Access
 * @coversDefaultClass \Drupal\Core\Access\CsrfTokenGenerator
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
   * @var \Drupal\Core\PrivateKey|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $privateKey;

  /**
   * The mock session metadata bag.
   *
   * @var \Drupal\Core\Session\MetadataBag|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $sessionMetadata;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->privateKey = $this->getMockBuilder('Drupal\Core\PrivateKey')
      ->disableOriginalConstructor()
      ->setMethods(['get'])
      ->getMock();

    $this->sessionMetadata = $this->getMockBuilder('Drupal\Core\Session\MetadataBag')
      ->disableOriginalConstructor()
      ->getMock();

    $settings = [
      'hash_salt' => $this->randomMachineName(),
    ];

    new Settings($settings);

    $this->generator = new CsrfTokenGenerator($this->privateKey, $this->sessionMetadata);
  }

  /**
   * Set up default expectations on the mocks.
   */
  protected function setupDefaultExpectations() {
    $key = Crypt::randomBytesBase64();
    $this->privateKey->expects($this->any())
      ->method('get')
      ->will($this->returnValue($key));

    $seed = Crypt::randomBytesBase64();
    $this->sessionMetadata->expects($this->any())
      ->method('getCsrfTokenSeed')
      ->will($this->returnValue($seed));
  }

  /**
   * Tests CsrfTokenGenerator::get().
   *
   * @covers ::get
   */
  public function testGet() {
    $this->setupDefaultExpectations();

    $this->assertInternalType('string', $this->generator->get());
    $this->assertNotSame($this->generator->get(), $this->generator->get($this->randomMachineName()));
    $this->assertNotSame($this->generator->get($this->randomMachineName()), $this->generator->get($this->randomMachineName()));
  }

  /**
   * Tests that a new token seed is generated upon first use.
   *
   * @covers ::get
   */
  public function testGenerateSeedOnGet() {
    $key = Crypt::randomBytesBase64();
    $this->privateKey->expects($this->any())
      ->method('get')
      ->will($this->returnValue($key));

    $this->sessionMetadata->expects($this->once())
      ->method('getCsrfTokenSeed')
      ->will($this->returnValue(NULL));

    $this->sessionMetadata->expects($this->once())
      ->method('setCsrfTokenSeed')
      ->with($this->isType('string'));

    $this->assertInternalType('string', $this->generator->get());
  }

  /**
   * Tests CsrfTokenGenerator::validate().
   *
   * @covers ::validate
   */
  public function testValidate() {
    $this->setupDefaultExpectations();

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
   * @covers ::validate
   * @dataProvider providerTestValidateParameterTypes
   */
  public function testValidateParameterTypes($token, $value) {
    $this->setupDefaultExpectations();

    // The following check might throw PHP fatals and notices, so we disable
    // error assertions.
    set_error_handler(function () {
      return TRUE;
    });
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
    return [
      [[], ''],
      [TRUE, 'foo'],
      [0, 'foo'],
    ];
  }

  /**
   * Tests CsrfTokenGenerator::validate() with invalid parameter types.
   *
   * @param mixed $token
   *   The token to be validated.
   * @param mixed $value
   *   (optional) An additional value to base the token on.
   *
   * @covers ::validate
   * @dataProvider providerTestInvalidParameterTypes
   */
  public function testInvalidParameterTypes($token, $value = '') {
    $this->setupDefaultExpectations();

    $this->expectException(\InvalidArgumentException::class);
    $this->generator->validate($token, $value);
  }

  /**
   * Provides data for testInvalidParameterTypes.
   *
   * @return array
   *   An array of data used by the test.
   */
  public function providerTestInvalidParameterTypes() {
    return [
      [NULL, new \stdClass()],
      [0, []],
      ['', []],
      [[], []],
    ];
  }

  /**
   * Tests the exception thrown when no 'hash_salt' is provided in settings.
   *
   * @covers ::get
   */
  public function testGetWithNoHashSalt() {
    // Update settings with no hash salt.
    new Settings([]);
    $generator = new CsrfTokenGenerator($this->privateKey, $this->sessionMetadata);
    $this->expectException(\RuntimeException::class);
    $generator->get();
  }

}
