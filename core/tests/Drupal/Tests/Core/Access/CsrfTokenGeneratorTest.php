<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Access;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\Site\Settings;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the CsrfTokenGenerator class.
 */
#[CoversClass(CsrfTokenGenerator::class)]
#[Group('Access')]
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
  protected function setUp(): void {
    parent::setUp();

    $this->privateKey = $this->getMockBuilder('Drupal\Core\PrivateKey')
      ->disableOriginalConstructor()
      ->onlyMethods(['get'])
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
  protected function setupDefaultExpectations(): void {
    $key = Crypt::randomBytesBase64();
    $this->privateKey->expects($this->any())
      ->method('get')
      ->willReturn($key);

    $seed = Crypt::randomBytesBase64();
    $this->sessionMetadata->expects($this->any())
      ->method('getCsrfTokenSeed')
      ->willReturn($seed);
  }

  /**
   * Tests CsrfTokenGenerator::get().
   */
  public function testGet(): void {
    $this->setupDefaultExpectations();

    $this->assertIsString($this->generator->get());
    $this->assertNotSame($this->generator->get(), $this->generator->get($this->randomMachineName()));
    $this->assertNotSame($this->generator->get($this->randomMachineName()), $this->generator->get($this->randomMachineName()));
  }

  /**
   * Tests that a new token seed is generated upon first use.
   *
   * @legacy-covers ::get
   */
  public function testGenerateSeedOnGet(): void {
    $key = Crypt::randomBytesBase64();
    $this->privateKey->expects($this->any())
      ->method('get')
      ->willReturn($key);

    $this->sessionMetadata->expects($this->once())
      ->method('getCsrfTokenSeed')
      ->willReturn(NULL);

    $this->sessionMetadata->expects($this->once())
      ->method('setCsrfTokenSeed')
      ->with($this->isString());

    $this->assertIsString($this->generator->get());
  }

  /**
   * Tests CsrfTokenGenerator::validate().
   */
  public function testValidate(): void {
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
   */
  #[DataProvider('providerTestValidateParameterTypes')]
  public function testValidateParameterTypes($token, $value): void {
    $this->setupDefaultExpectations();

    // The following check might throw PHP fatal errors and notices, so we
    // disable error assertions.
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
  public static function providerTestValidateParameterTypes(): array {
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
   * @legacy-covers ::validate
   */
  #[DataProvider('providerTestInvalidParameterTypes')]
  public function testInvalidParameterTypes($token, $value = ''): void {
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
  public static function providerTestInvalidParameterTypes(): array {
    return [
      [NULL, new \stdClass()],
      [0, []],
      ['', []],
      [[], []],
    ];
  }

  /**
   * Tests the exception thrown when no 'hash_salt' is provided in settings.
   */
  public function testGetWithNoHashSalt(): void {
    // Update settings with no hash salt.
    new Settings([]);
    $generator = new CsrfTokenGenerator($this->privateKey, $this->sessionMetadata);
    $this->expectException(\RuntimeException::class);
    $generator->get();
  }

}
