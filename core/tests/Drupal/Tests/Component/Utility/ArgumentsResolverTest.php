<?php

declare(strict_types=1);

namespace Drupal\Tests\Component\Utility;

use Drupal\Component\Utility\ArgumentsResolver;
use PHPUnit\Framework\TestCase;

/**
 * @coversDefaultClass \Drupal\Component\Utility\ArgumentsResolver
 * @group Access
 */
class ArgumentsResolverTest extends TestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
  }

  /**
   * Tests the getArgument() method.
   *
   * @dataProvider providerTestGetArgument
   */
  public function testGetArgument($callable, $scalars, $objects, $wildcards, $expected): void {
    $arguments = (new ArgumentsResolver($scalars, $objects, $wildcards))->getArguments($callable);
    $this->assertSame($expected, $arguments);
  }

  /**
   * Provides test data to testGetArgument().
   */
  public static function providerTestGetArgument() {
    $data = [];

    // Test an optional parameter with no provided value.
    $data[] = [
      function ($foo = 'foo') {}, [], [], [] , ['foo'],
    ];

    // Test an optional parameter with a provided value.
    $data[] = [
      function ($foo = 'foo') {}, ['foo' => 'bar'], [], [], ['bar'],
    ];

    // Test with a provided value.
    $data[] = [
      function ($foo) {}, ['foo' => 'bar'], [], [], ['bar'],
    ];

    // Test with an explicitly NULL value.
    $data[] = [
      function ($foo) {}, [], ['foo' => NULL], [], [NULL],
    ];

    // Test with a raw value that overrides the provided upcast value, since
    // it is not type hinted.
    $scalars = ['foo' => 'baz'];
    $objects = ['foo' => new \stdClass()];
    $data[] = [
      function ($foo) {}, $scalars, $objects, [], ['baz'],
    ];

    // Test a static method string.
    $data[] = [
      TestStaticMethodClass::class . "::access",
      [],
      ['foo' => NULL],
      [],
      [NULL],
    ];

    return $data;
  }

  /**
   * Tests getArgument() with an object.
   */
  public function testGetArgumentObject(): void {
    $callable = function (\stdClass $object) {};

    $object = new \stdClass();
    $arguments = (new ArgumentsResolver([], ['object' => $object], []))->getArguments($callable);
    $this->assertSame([$object], $arguments);
  }

  /**
   * Tests getArgument() with a wildcard object for a parameter with a custom name.
   */
  public function testGetWildcardArgument(): void {
    $callable = function (\stdClass $custom_name) {};

    $object = new \stdClass();
    $arguments = (new ArgumentsResolver([], [], [$object]))->getArguments($callable);
    $this->assertSame([$object], $arguments);
  }

  /**
   * Tests getArgument() with a Route, Request, and Account object.
   */
  public function testGetArgumentOrder(): void {
    $a1 = $this->getMockBuilder('\Drupal\Tests\Component\Utility\Test1Interface')->getMock();
    $a2 = $this->getMockBuilder('\Drupal\Tests\Component\Utility\TestClass')->getMock();
    $a3 = $this->getMockBuilder('\Drupal\Tests\Component\Utility\Test2Interface')->getMock();

    $objects = [
      't1' => $a1,
      'tc' => $a2,
    ];
    $wildcards = [$a3];
    $resolver = new ArgumentsResolver([], $objects, $wildcards);

    $callable = function (Test1Interface $t1, TestClass $tc, Test2Interface $t2) {};
    $arguments = $resolver->getArguments($callable);
    $this->assertSame([$a1, $a2, $a3], $arguments);

    // Test again, but with the arguments in a different order.
    $callable = function (Test2Interface $t2, TestClass $tc, Test1Interface $t1) {};
    $arguments = $resolver->getArguments($callable);
    $this->assertSame([$a3, $a2, $a1], $arguments);
  }

  /**
   * Tests getArgument() with a wildcard parameter with no type hint.
   *
   * Without the type hint, the wildcard object will not be passed to the callable.
   */
  public function testGetWildcardArgumentNoTypeHint(): void {
    $a = $this->getMockBuilder('\Drupal\Tests\Component\Utility\Test1Interface')->getMock();
    $wildcards = [$a];
    $resolver = new ArgumentsResolver([], [], $wildcards);

    $callable = function ($route) {};
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('requires a value for the "$route" argument.');
    $resolver->getArguments($callable);
  }

  /**
   * Tests getArgument() with a named parameter with no type hint and a value.
   *
   * Without the type hint, passing a value to a named parameter will still
   * receive the provided value.
   */
  public function testGetArgumentRouteNoTypeHintAndValue(): void {
    $scalars = ['route' => 'foo'];
    $resolver = new ArgumentsResolver($scalars, [], []);

    $callable = function ($route) {};
    $arguments = $resolver->getArguments($callable);
    $this->assertSame(['foo'], $arguments);
  }

  /**
   * Tests handleUnresolvedArgument() for a scalar argument.
   */
  public function testHandleNotUpcastedArgument(): void {
    $objects = ['foo' => 'bar'];
    $scalars = ['foo' => 'baz'];
    $resolver = new ArgumentsResolver($scalars, $objects, []);

    $callable = function (\stdClass $foo) {};
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('requires a value for the "$foo" argument.');
    $resolver->getArguments($callable);
  }

  /**
   * Tests handleUnresolvedArgument() for missing arguments.
   *
   * @dataProvider providerTestHandleUnresolvedArgument
   */
  public function testHandleUnresolvedArgument($callable): void {
    $resolver = new ArgumentsResolver([], [], []);
    $this->expectException(\RuntimeException::class);
    $this->expectExceptionMessage('requires a value for the "$foo" argument.');
    $resolver->getArguments($callable);
  }

  /**
   * Provides test data to testHandleUnresolvedArgument().
   */
  public static function providerTestHandleUnresolvedArgument() {
    $data = [];
    $data[] = [function ($foo) {}];
    $data[] = [[new TestClass(), 'access']];
    $data[] = ['Drupal\Tests\Component\Utility\test_access_arguments_resolver_access'];
    return $data;
  }

}

/**
 * Provides a test class.
 */
class TestClass {

  public function access($foo) {
  }

}

class TestStaticMethodClass {

  public static function access($foo) {
  }

}

/**
 * Provides a test interface.
 */
interface Test1Interface {
}

/**
 * Provides a different test interface.
 */
interface Test2Interface {
}

function test_access_arguments_resolver_access($foo) {
}
