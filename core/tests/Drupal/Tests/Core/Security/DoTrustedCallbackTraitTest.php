<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Security;

use Drupal\Core\Security\Attribute\TrustedCallback;
use Drupal\Core\Security\DoTrustedCallbackTrait;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\Security\UntrustedCallbackException;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\IgnoreDeprecations;

/**
 * Tests Drupal\Core\Security\DoTrustedCallbackTrait.
 */
#[CoversClass(DoTrustedCallbackTrait::class)]
#[Group('Security')]
class DoTrustedCallbackTraitTest extends UnitTestCase {
  use DoTrustedCallbackTrait;

  /**
   * Tests trusted callbacks.
   *
   * @legacy-covers ::doTrustedCallback
   */
  #[DataProvider('providerTestTrustedCallbacks')]
  public function testTrustedCallbacks(callable $callback, $extra_trusted_interface = NULL): void {
    $return = $this->doTrustedCallback($callback, [], '%s is not trusted', TrustedCallbackInterface::THROW_EXCEPTION, $extra_trusted_interface);
    $this->assertSame('test', $return);
  }

  /**
   * Data provider for ::testTrustedCallbacks().
   */
  public static function providerTestTrustedCallbacks(): array {
    $closure = function () {
      return 'test';
    };

    $tests['closure'] = [$closure];
    $tests['TrustedCallbackInterface_object'] = [
      [
        new TrustedMethods(),
        'callback',
      ],
      TrustedInterface::class,
    ];
    $tests['TrustedCallbackInterface_object_attribute'] = [
      [
        new TrustedMethods(),
        'attributeCallback',
      ],
      TrustedInterface::class,
    ];
    $tests['TrustedCallbackInterface_static_string'] = [
      '\Drupal\Tests\Core\Security\TrustedMethods::callback',
      TrustedInterface::class,
    ];
    $tests['TrustedCallbackInterface_static_array'] = [
      [
        TrustedMethods::class,
        'callback',
      ],
      TrustedInterface::class,
    ];
    $tests['TrustedCallbackInterface_static_array_attribute'] = [
      [
        TrustedMethods::class,
        'attributeCallback',
      ],
      TrustedInterface::class,
    ];
    $tests['extra_trusted_interface_object'] = [
      [
        new TrustedObject(),
        'callback',
      ],
      TrustedInterface::class,
    ];
    $tests['extra_trusted_interface_static_string'] = [
      '\Drupal\Tests\Core\Security\TrustedObject::callback',
      TrustedInterface::class,
    ];
    $tests['extra_trusted_interface_static_array'] = [
      [
        TrustedObject::class,
        'callback',
      ],
      TrustedInterface::class,
    ];
    return $tests;
  }

  /**
   * Tests untrusted callbacks.
   *
   * @legacy-covers ::doTrustedCallback
   */
  #[DataProvider('providerTestUntrustedCallbacks')]
  public function testUntrustedCallbacks(callable $callback, $extra_trusted_interface = NULL): void {
    $this->expectException(UntrustedCallbackException::class);
    $this->doTrustedCallback($callback, [], '%s is not trusted', TrustedCallbackInterface::THROW_EXCEPTION, $extra_trusted_interface);
  }

  /**
   * Data provider for ::testUntrustedCallbacks().
   */
  public static function providerTestUntrustedCallbacks(): array {
    $tests['TrustedCallbackInterface_object'] = [
      [
        new TrustedMethods(),
        'unTrustedCallback',
      ],
      TrustedInterface::class,
    ];
    $tests['TrustedCallbackInterface_static_string'] = [
      '\Drupal\Tests\Core\Security\TrustedMethods::unTrustedCallback',
      TrustedInterface::class,
    ];
    $tests['TrustedCallbackInterface_static_array'] = [
      [
        TrustedMethods::class,
        'unTrustedCallback',
      ],
      TrustedInterface::class,
    ];
    $tests['untrusted_object'] = [
      [new UntrustedObject(), 'callback'],
      TrustedInterface::class,
    ];
    $tests['untrusted_object_static_string'] = [
      '\Drupal\Tests\Core\Security\UntrustedObject::callback',
      TrustedInterface::class,
    ];
    $tests['untrusted_object_static_array'] = [
      [
        UntrustedObject::class,
        'callback',
      ],
      TrustedInterface::class,
    ];
    $tests['invokable_untrusted_object_static_array'] = [
      new InvokableUntrustedObject(),
      TrustedInterface::class,
    ];
    return $tests;
  }

  /**
 * Tests exception.
 */
  #[DataProvider('errorTypeProvider')]
  public function testException($callback): void {
    $this->expectException(UntrustedCallbackException::class);
    $this->expectExceptionMessage('Drupal\Tests\Core\Security\UntrustedObject::callback is not trusted');
    $this->doTrustedCallback($callback, [], '%s is not trusted');
  }

  /**
 * Tests silenced deprecation.
 */
  #[DataProvider('errorTypeProvider')]
  #[IgnoreDeprecations]
  public function testSilencedDeprecation($callback): void {
    $this->expectDeprecation('Drupal\Tests\Core\Security\UntrustedObject::callback is not trusted');
    $this->doTrustedCallback($callback, [], '%s is not trusted', TrustedCallbackInterface::TRIGGER_SILENCED_DEPRECATION);
  }

  /**
   * Data provider for tests of ::doTrustedCallback $error_type argument.
   */
  public static function errorTypeProvider(): array {
    $tests['untrusted_object'] = [[new UntrustedObject(), 'callback']];
    $tests['untrusted_object_static_string'] = ['Drupal\Tests\Core\Security\UntrustedObject::callback'];
    $tests['untrusted_object_static_array'] = [[UntrustedObject::class, 'callback']];
    return $tests;
  }

}

/**
 * Interface representing classes with trusted callbacks.
 */
interface TrustedInterface {
}

/**
 * Class with a trusted interface implementation with callback.
 */
class TrustedObject implements TrustedInterface {

  public static function callback(): string {
    return 'test';
  }

}

/**
 * Class representing untrusted callback.
 */
class UntrustedObject {

  public static function callback(): string {
    return 'test';
  }

}

/**
 * Invokable untrusted test class.
 */
class InvokableUntrustedObject {

  public function __invoke(): string {
    return 'test';
  }

}

/**
 * Test class with implemented trusted callbacks.
 */
class TrustedMethods implements TrustedCallbackInterface {

  public static function trustedCallbacks(): array {
    return ['callback'];
  }

  public static function callback(): string {
    return 'test';
  }

  #[TrustedCallback]
  public static function attributeCallback(): string {
    return 'test';
  }

  public static function unTrustedCallback(): string {
    return 'test';
  }

}
