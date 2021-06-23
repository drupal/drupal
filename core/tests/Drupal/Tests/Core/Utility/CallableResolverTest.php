<?php

namespace Drupal\Tests\Core\Utility;

use Drupal\Core\DependencyInjection\ClassResolver;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Utility\CallableResolver;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * @coversDefaultClass \Drupal\Core\Utility\CallableResolver
 * @group Utility
 */
class CallableResolverTest extends UnitTestCase {

  /**
   * The callable resolver.
   *
   * @var \Drupal\Core\Utility\CallableResolver
   */
  protected $resolver;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $container = new ContainerBuilder();
    $container->set('test_service', $this);

    $class_resolver = new ClassResolver();
    $class_resolver->setContainer($container);

    $this->resolver = new CallableResolver($container, $class_resolver);
  }

  /**
   * @dataProvider callableResolverTestCases
   * @covers ::getCallableFromDefinition
   * @covers ::invokeFromDefinition
   */
  public function testCallbackResolver($definition) {
    $this->assertEquals('foobar', $this->resolver->getCallableFromDefinition($definition)('bar'));
    $this->assertEquals('foobar', $this->resolver->invokeFromDefinition($definition, 'bar'));
  }

  /**
   * Test cases for ::testCallbackResolver.
   */
  public function callableResolverTestCases() {
    return [
      'Inline function' => [
        function ($suffix) {
          return 'foo' . $suffix;
        },
      ],
      'Static function' => [
        '\Drupal\Tests\Core\Utility\NoInstantiationMockStaticCallable::staticMethod',
      ],
      'Static function, array notation' => [
        [NoInstantiationMockStaticCallable::class, 'staticMethod'],
      ],
      'Static function, array notation, with object' => [
        [$this, 'staticMethod'],
      ],
      'Non-static function, array notation, with object' => [
        [$this, 'method'],
      ],
      'Non-static function, instantiated by class resolver' => [
        static::class . '::method',
      ],
      'Non-static function, instantiated by class resolver, container injection' => [
        '\Drupal\Tests\Core\Utility\MockContainerInjection::getResult',
      ],
      'Non-static function, instantiated by class resolver, container aware' => [
        '\Drupal\Tests\Core\Utility\MockContainerAware::getResult',
      ],
      'Service notation' => [
        'test_service:method',
      ],
      'Service notation, static method' => [
        'test_service:staticMethod',
      ],
      'Class with invoke method' => [
        static::class,
      ],
    ];
  }

  /**
   * @dataProvider callableResolverExceptionHandlingTestCases
   * @covers ::getCallableFromDefinition
   * @covers ::invokeFromDefinition
   */
  public function testCallbackResolverExceptionHandling($definition, $exception_class, $exception_message) {
    $this->expectException($exception_class);
    $this->expectExceptionMessage($exception_message);
    $this->resolver->invokeFromDefinition($definition);
  }

  /**
   * Test cases for ::testCallbackResolverExceptionHandling.
   */
  public function callableResolverExceptionHandlingTestCases() {
    return [
      'String function' => [
        'not_a_callable',
        \InvalidArgumentException::class,
        'The callable definition provided was not a valid callable to service method.',
      ],
      'Array notation' => [
        ['not_a_callable', 'not_a_callable'],
        \InvalidArgumentException::class,
        'The callable definition provided was not a valid callable to service method.',
      ],
      'Missing method on class, array notation' => [
        [static::class, 'method_not_exists'],
        \InvalidArgumentException::class,
        'The callable definition provided was not a valid callable to service method.',
      ],
      'Missing method on class, static notation' => [
        static::class . '::method_not_exists',
        \InvalidArgumentException::class,
        'The callable definition provided was invalid. Either class "Drupal\Tests\Core\Utility\CallableResolverTest" does not have a method "method_not_exists", or it is not callable.',
      ],
      'Missing class, static notation' => [
        '\NotARealClass::method',
        \InvalidArgumentException::class,
        'The callable definition provided was invalid. Either class "\NotARealClass" does not have a method "method", or it is not callable.',
      ],
      'Service not in container' => [
        'bad_service:method',
        \InvalidArgumentException::class,
        'The callable definition provided was invalid. No service found with name "bad_service".',
      ],
      'Invalid method on valid service' => [
        'test_service:not_a_callable',
        \InvalidArgumentException::class,
        'The callable definition provided was invalid. No method with name "not_a_callable" found on the "test_service" service.',
      ],
    ];
  }

  /**
   * A test static method that returns "foo".
   *
   * @param string $suffix
   *   A suffix to append.
   *
   * @return string
   *   A test string.
   */
  public static function staticMethod($suffix) {
    return 'foo' . $suffix;
  }

  /**
   * A test method that returns "foo".
   *
   * @param string $suffix
   *   A suffix to append.
   *
   * @return string
   *   A test string.
   *
   * @throws \Exception
   *   Throws an exception when called statically.
   */
  public function method($suffix) {
    if (!isset($this)) {
      throw new \Exception('Non-static method called statically.');
    }
    return 'foo' . $suffix;
  }

  /**
   * A test __invoke method.
   *
   * @param string $suffix
   *   A suffix to append.
   *
   * @return string
   *   A test string.
   */
  public function __invoke($suffix) {
    return 'foo' . $suffix;
  }

}

class MockContainerInjection implements ContainerInjectionInterface {

  protected $injected;

  public function __construct($result) {
    $this->injected = $result;
  }

  public static function create(ContainerInterface $container) {
    return new static('foo');
  }

  public function getResult($suffix) {
    return $this->injected . $suffix;
  }

}

class NoInstantiationMockStaticCallable {

  public function __construct() {
    throw new \Exception(sprintf('The class %s should not require instantiation for the static method to be called.', __CLASS__));
  }

  public static function staticMethod($suffix) {
    return 'foo' . $suffix;
  }

}

class MockContainerAware implements ContainerAwareInterface {

  use ContainerAwareTrait;

  public function getResult($suffix) {
    if (empty($this->container)) {
      throw new \Exception('Container was not injected.');
    }
    return 'foo' . $suffix;
  }

}
