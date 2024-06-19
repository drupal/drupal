<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Security;

use Drupal\Core\Security\Attribute\TrustedCallback;
use Drupal\Core\Security\TrustedCallbackInterface;
use Drupal\Core\Security\DoTrustedCallbackTrait;
use Drupal\Core\Security\UntrustedCallbackException;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Security\DoTrustedCallbackTrait
 * @group Security
 */
class DoTrustedCallbackTraitTest extends UnitTestCase {
  use DoTrustedCallbackTrait;

  /**
   * @covers ::doTrustedCallback
   * @dataProvider providerTestTrustedCallbacks
   */
  public function testTrustedCallbacks(callable $callback, $extra_trusted_interface = NULL): void {
    $return = $this->doTrustedCallback($callback, [], '%s is not trusted', TrustedCallbackInterface::THROW_EXCEPTION, $extra_trusted_interface);
    $this->assertSame('test', $return);
  }

  /**
   * Data provider for ::testTrustedCallbacks().
   */
  public static function providerTestTrustedCallbacks() {
    $closure = function () {
      return 'test';
    };

    $tests['closure'] = [$closure];
    $tests['TrustedCallbackInterface_object'] = [[new TrustedMethods(), 'callback'], TrustedInterface::class];
    $tests['TrustedCallbackInterface_object_attribute'] = [[new TrustedMethods(), 'attributeCallback'], TrustedInterface::class];
    $tests['TrustedCallbackInterface_static_string'] = ['\Drupal\Tests\Core\Security\TrustedMethods::callback', TrustedInterface::class];
    $tests['TrustedCallbackInterface_static_array'] = [[TrustedMethods::class, 'callback'], TrustedInterface::class];
    $tests['TrustedCallbackInterface_static_array_attribute'] = [[TrustedMethods::class, 'attributeCallback'], TrustedInterface::class];
    $tests['extra_trusted_interface_object'] = [[new TrustedObject(), 'callback'], TrustedInterface::class];
    $tests['extra_trusted_interface_static_string'] = ['\Drupal\Tests\Core\Security\TrustedObject::callback', TrustedInterface::class];
    $tests['extra_trusted_interface_static_array'] = [[TrustedObject::class, 'callback'], TrustedInterface::class];
    return $tests;
  }

  /**
   * @covers ::doTrustedCallback
   * @dataProvider providerTestUntrustedCallbacks
   */
  public function testUntrustedCallbacks(callable $callback, $extra_trusted_interface = NULL): void {
    $this->expectException(UntrustedCallbackException::class);
    $this->doTrustedCallback($callback, [], '%s is not trusted', TrustedCallbackInterface::THROW_EXCEPTION, $extra_trusted_interface);
  }

  /**
   * Data provider for ::testUntrustedCallbacks().
   */
  public static function providerTestUntrustedCallbacks() {
    $tests['TrustedCallbackInterface_object'] = [[new TrustedMethods(), 'unTrustedCallback'], TrustedInterface::class];
    $tests['TrustedCallbackInterface_static_string'] = ['\Drupal\Tests\Core\Security\TrustedMethods::unTrustedCallback', TrustedInterface::class];
    $tests['TrustedCallbackInterface_static_array'] = [[TrustedMethods::class, 'unTrustedCallback'], TrustedInterface::class];
    $tests['untrusted_object'] = [[new UntrustedObject(), 'callback'], TrustedInterface::class];
    $tests['untrusted_object_static_string'] = ['\Drupal\Tests\Core\Security\UntrustedObject::callback', TrustedInterface::class];
    $tests['untrusted_object_static_array'] = [[UntrustedObject::class, 'callback'], TrustedInterface::class];
    $tests['invokable_untrusted_object_static_array'] = [new InvokableUntrustedObject(), TrustedInterface::class];
    return $tests;
  }

  /**
   * @dataProvider errorTypeProvider
   */
  public function testException($callback): void {
    $this->expectException(UntrustedCallbackException::class);
    $this->expectExceptionMessage('Drupal\Tests\Core\Security\UntrustedObject::callback is not trusted');
    $this->doTrustedCallback($callback, [], '%s is not trusted');
  }

  /**
   * @dataProvider errorTypeProvider
   * @group legacy
   */
  public function testSilencedDeprecation($callback): void {
    $this->expectDeprecation('Drupal\Tests\Core\Security\UntrustedObject::callback is not trusted');
    $this->doTrustedCallback($callback, [], '%s is not trusted', TrustedCallbackInterface::TRIGGER_SILENCED_DEPRECATION);
  }

  /**
   * Data provider for tests of ::doTrustedCallback $error_type argument.
   */
  public static function errorTypeProvider() {
    $tests['untrusted_object'] = [[new UntrustedObject(), 'callback']];
    $tests['untrusted_object_static_string'] = ['Drupal\Tests\Core\Security\UntrustedObject::callback'];
    $tests['untrusted_object_static_array'] = [[UntrustedObject::class, 'callback']];
    return $tests;
  }

}

interface TrustedInterface {
}

class TrustedObject implements TrustedInterface {

  public static function callback() {
    return 'test';
  }

}

class UntrustedObject {

  public static function callback() {
    return 'test';
  }

}

class InvokableUntrustedObject {

  public function __invoke() {
    return 'test';
  }

}

class TrustedMethods implements TrustedCallbackInterface {

  public static function trustedCallbacks() {
    return ['callback'];
  }

  public static function callback() {
    return 'test';
  }

  #[TrustedCallback]
  public static function attributeCallback() {
    return 'test';
  }

  public static function unTrustedCallback() {
    return 'test';
  }

}
