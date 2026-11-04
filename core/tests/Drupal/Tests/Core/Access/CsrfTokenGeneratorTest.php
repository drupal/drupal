<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Access;

use Drupal\Component\Utility\Crypt;
use Drupal\Core\Access\CsrfTokenGenerator;
use Drupal\Core\PrivateKey;
use Drupal\Core\Session\MetadataBag;
use Drupal\Core\Site\Settings;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;

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
   */
  protected PrivateKey&MockObject $privateKey;

  /**
   * The mock session metadata bag.
   */
  protected MetadataBag&MockObject $sessionMetadata;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $settings = [
      'hash_salt' => $this->randomMachineName(),
    ];

    new Settings($settings);
  }

  /**
   * Set up default expectations on the mocks.
   */
  protected function setupDefaultExpectations(): void {
    $key = Crypt::randomBytesBase64();
    $this->privateKey = $this->getMockBuilder(PrivateKey::class)
      ->disableOriginalConstructor()
      ->onlyMethods(['get'])
      ->getMock();
    $this->privateKey->expects($this->atLeastOnce())
      ->method('get')
      ->willReturn($key);

    $seed = Crypt::randomBytesBase64();

    $this->sessionMetadata = $this->getMockBuilder(MetadataBag::class)
      ->disableOriginalConstructor()
      ->getMock();
    $this->sessionMetadata->expects($this->atLeastOnce())
      ->method('getCsrfTokenSeed')
      ->willReturn($seed);
  }

  /**
   * Tests CsrfTokenGenerator::get().
   */
  public function testGet(): void {
    $this->setupDefaultExpectations();
    $this->generator = new CsrfTokenGenerator($this->privateKey, $this->sessionMetadata);

    $this->assertIsString($this->generator->get());
    $this->assertNotSame($this->generator->get(), $this->generator->get($this->randomMachineName()));
    $this->assertNotSame($this->generator->get($this->randomMachineName()), $this->generator->get($this->randomMachineName()));
  }

  /**
   * Tests that a new token seed is generated upon first use.
   *
   * @see ::get
   */
  public function testGenerateSeedOnGet(): void {
    $key = Crypt::randomBytesBase64();
    $privateKey = $this->createMock(PrivateKey::class);
    $privateKey->expects($this->once())
      ->method('get')
      ->willReturn($key);

    $sessionMetadata = $this->createMock(MetadataBag::class);
    $sessionMetadata->expects($this->once())
      ->method('getCsrfTokenSeed')
      ->willReturn(NULL);

    $sessionMetadata->expects($this->once())
      ->method('setCsrfTokenSeed')
      ->with($this->isString());

    $generator = new CsrfTokenGenerator($privateKey, $sessionMetadata);
    $this->assertIsString($generator->get());
  }

  /**
   * Tests CsrfTokenGenerator::validate().
   */
  public function testValidate(): void {
    $this->setupDefaultExpectations();
    $this->generator = new CsrfTokenGenerator($this->privateKey, $this->sessionMetadata);

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
  public function testValidateParameterTypes(bool|int|array $token, string $value): void {
    $this->setupDefaultExpectations();
    $this->generator = new CsrfTokenGenerator($this->privateKey, $this->sessionMetadata);

    // The following check might throw PHP fatal errors and notices, so we
    // disable error assertions.
    set_error_handler(function (): true {
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
   *   An additional value to base the token on.
   *
   * @see ::validate
   */
  #[DataProvider('providerTestInvalidParameterTypes')]
  public function testInvalidParameterTypes(int|string|array|null $token, \stdClass|array $value): void {
    $this->setupDefaultExpectations();
    $this->generator = new CsrfTokenGenerator($this->privateKey, $this->sessionMetadata);

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
    $generator = new CsrfTokenGenerator($this->createStub(PrivateKey::class), $this->createStub(MetadataBag::class));
    $this->expectException(\RuntimeException::class);
    $generator->get();
  }

}
