<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\Access\AccessArgumentsResolverTest.
 */

namespace Drupal\Tests\Core\Access {

use Drupal\Core\Access\AccessArgumentsResolver;
use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * @coversDefaultClass \Drupal\Core\Access\AccessArgumentsResolver
 * @group Access
 */
class AccessArgumentsResolverTest extends UnitTestCase {

  /**
   * The mocked account.
   *
   * @var \Drupal\Core\Session\AccountInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $account;

  /**
   * A route object.
   *
   * @var \Symfony\Component\Routing\Route
   */
  protected $route;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    $this->account = $this->getMock('Drupal\Core\Session\AccountInterface');
    $this->route = new Route('/test');
  }

  /**
   * Tests the getArgument() method.
   *
   * @dataProvider providerTestGetArgument
   */
  public function testGetArgument($callable, $request, $expected) {
    $arguments = (new AccessArgumentsResolver())->getArguments($callable, $this->route, $request, $this->account);
    $this->assertSame($expected, $arguments);
  }

  /**
   * Provides test data to testGetArgument().
   */
  public function providerTestGetArgument() {
    $data = array();

    // Test an optional parameter with no provided value.
    $data[] = array(
      function($foo = 'foo') {}, new Request(), array('foo'),
    );

    // Test an optional parameter with a provided value.
    $request = new Request();
    $request->attributes->set('foo', 'bar');
    $data[] = array(
      function($foo = 'foo') {}, $request, array('bar'),
    );

    // Test with a provided value.
    $request = new Request();
    $request->attributes->set('foo', 'bar');
    $data[] = array(
      function($foo) {}, $request, array('bar'),
    );

    // Test with an explicitly NULL value.
    $request = new Request();
    $request->attributes->set('foo', NULL);
    $data[] = array(
      function($foo) {}, $request, array(NULL),
    );

    // Test with a raw value that overrides the provided upcast value, since
    // it is not typehinted.
    $request = new Request();
    $request->attributes->set('foo', 'bar');
    $request->attributes->set('_raw_variables', new ParameterBag(array('foo' => 'baz')));
    $data[] = array(
      function($foo) {}, $request, array('baz'),
    );

    return $data;
  }

  /**
   * Tests getArgument() with a Route object.
   */
  public function testGetArgumentRoute() {
    $callable = function(Route $route) {};
    $request = new Request();

    $arguments = (new AccessArgumentsResolver())->getArguments($callable, $this->route, $request, $this->account);
    $this->assertSame(array($this->route), $arguments);
  }

  /**
   * Tests getArgument() with a Route object for a parameter with a custom name.
   */
  public function testGetArgumentRouteCustomName() {
    $callable = function(Route $custom_name) {};
    $request = new Request();

    $arguments = (new AccessArgumentsResolver())->getArguments($callable, $this->route, $request, $this->account);
    $this->assertSame(array($this->route), $arguments);
  }

  /**
   * Tests getArgument() with a Route, Request, and Account object.
   */
  public function testGetArgumentRouteRequestAccount() {
    $callable = function(Route $route, Request $request, AccountInterface $account) {};
    $request = new Request();

    $arguments = (new AccessArgumentsResolver())->getArguments($callable, $this->route, $request, $this->account);
    $this->assertSame(array($this->route, $request, $this->account), $arguments);

    // Test again, but with the arguments in a different order.
    $callable = function(AccountInterface $account, Request $request, Route $route) {};
    $request = new Request();

    $arguments = (new AccessArgumentsResolver())->getArguments($callable, $this->route, $request, $this->account);
    $this->assertSame(array($this->account, $request, $this->route), $arguments);
  }

  /**
   * Tests getArgument() with a '$route' parameter with no typehint.
   *
   * Without the typehint, the Route object will not be passed to the callable.
   *
   * @expectedException \RuntimeException
   * @expectedExceptionMessage requires a value for the "$route" argument.
   */
  public function testGetArgumentRouteNoTypehint() {
    $callable = function($route) {};
    $request = new Request();

    $arguments = (new AccessArgumentsResolver())->getArguments($callable, $this->route, $request, $this->account);
    $this->assertNull($arguments);
  }

  /**
   * Tests getArgument() with a '$route' parameter with no typehint and a value.
   *
   * Without the typehint, passing a value to a parameter named '$route' will
   * still receive the provided value.
   */
  public function testGetArgumentRouteNoTypehintAndValue() {
    $callable = function($route) {};
    $request = new Request();
    $request->attributes->set('route', 'foo');

    $arguments = (new AccessArgumentsResolver())->getArguments($callable, $this->route, $request, $this->account);
    $this->assertSame(array('foo'), $arguments);
  }

  /**
   * Tests getArgument() when upcasting is bypassed.
   */
  public function testGetArgumentBypassUpcasting() {
    $callable = function(Route $route = NULL) {};

    $request = new Request();
    $request->attributes->set('route', NULL);

    $arguments = (new AccessArgumentsResolver())->getArguments($callable, $this->route, $request, $this->account);
    $this->assertSame(array(NULL), $arguments);
  }

  /**
   * Tests handleUnresolvedArgument() for a non-upcast argument.
   *
   * @expectedException \RuntimeException
   * @expectedExceptionMessage requires a value for the "$foo" argument.
   */
  public function testHandleNotUpcastedArgument() {
    $callable = function(\stdClass $foo) {};

    $request = new Request();
    $request->attributes->set('foo', 'bar');
    $request->attributes->set('_raw_variables', new ParameterBag(array('foo' => 'baz')));

    $arguments = (new AccessArgumentsResolver())->getArguments($callable, $this->route, $request, $this->account);
    $this->assertNull($arguments);
  }

  /**
   * Tests handleUnresolvedArgument() for missing arguments.
   *
   * @expectedException \RuntimeException
   * @expectedExceptionMessage requires a value for the "$foo" argument.
   *
   * @dataProvider providerTestHandleUnresolvedArgument
   */
  public function testHandleUnresolvedArgument($callable) {
    $request = new Request();

    $arguments = (new AccessArgumentsResolver())->getArguments($callable, $this->route, $request, $this->account);
    $this->assertNull($arguments);
  }

  /**
   * Provides test data to testHandleUnresolvedArgument().
   */
  public function providerTestHandleUnresolvedArgument() {
    $data = array();
    $data[] = array(function($foo) {});
    $data[] = array(array(new TestClass(), 'access'));
    $data[] = array('test_access_arguments_resolver_access');
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

}

namespace {
  function test_access_arguments_resolver_access($foo) {
  }
}
