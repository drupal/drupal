<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Utility;

use Drupal\Core\DependencyInjection\ClassResolver;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Utility\CallableResolver;
use Drupal\Tests\UnitTestCase;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
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
  protected CallableResolver $resolver;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $container = new ContainerBuilder();
    $container->set('test_service', $this);

    $class_resolver = new ClassResolver($container);

    $this->resolver = new CallableResolver($class_resolver);
  }

  /**
   * @covers ::getCallableFromDefinition
   * @group legacy
   */
  public function testCallbackResolver(): void {
    $cases = [
      'Inline function' => [
        function ($suffix) {
          return __METHOD__ . '+' . $suffix;
        },
        'Drupal\Tests\Core\Utility\{closure}',
      ],
      'First-class callable function' => [
        $this->method(...),
        __CLASS__ . '::method',
      ],
      'First-class callable static' => [
        static::staticMethod(...),
        __CLASS__ . '::staticMethod',
      ],
      'Arrow function' => [
        fn($suffix) => __METHOD__ . '+' . $suffix,
        'Drupal\Tests\Core\Utility\{closure}',
      ],
      'Static function' => [
        '\Drupal\Tests\Core\Utility\NoInstantiationMockStaticCallable::staticMethod',
        'Drupal\Tests\Core\Utility\NoInstantiationMockStaticCallable::staticMethod',
      ],
      'Static function, array notation' => [
        [NoInstantiationMockStaticCallable::class, 'staticMethod'],
        'Drupal\Tests\Core\Utility\NoInstantiationMockStaticCallable::staticMethod',
      ],
      'Static function, array notation, with object' => [
        [$this, 'staticMethod'],
        __CLASS__ . '::staticMethod',
      ],
      'Non-static function, array notation, with object' => [
        [$this, 'method'],
        __CLASS__ . '::method',
      ],
      'Non-static function, instantiated by class resolver' => [
        static::class . '::method',
        __CLASS__ . '::method',
      ],
      'Non-static function, instantiated by class resolver, container injection' => [
        '\Drupal\Tests\Core\Utility\MockContainerInjection::getResult',
        'Drupal\Tests\Core\Utility\MockContainerInjection::getResult-foo',
      ],
      'Non-static function, instantiated by class resolver, container aware' => [
        '\Drupal\Tests\Core\Utility\MockContainerAware::getResult',
        'Drupal\Tests\Core\Utility\MockContainerAware::getResult',
        'Implementing \Symfony\Component\DependencyInjection\ContainerAwareInterface is deprecated in drupal:10.3.0 and it will be removed in drupal:11.0.0. Implement \Drupal\Core\DependencyInjection\ContainerInjectionInterface and use dependency injection instead. See https://www.drupal.org/node/3428661',
      ],
      'Service notation' => [
        'test_service:method',
        __CLASS__ . '::method',
      ],
      'Service notation, static method' => [
        'test_service:staticMethod',
        __CLASS__ . '::staticMethod',
      ],
      'Class with invoke method' => [
        static::class,
        __CLASS__ . '::__invoke',
      ],
    ];

    $argument = 'bar';
    foreach ($cases as $label => [$definition, $result]) {
      if (isset($cases[$label][2])) {
        $this->expectDeprecation($cases[$label][2]);
      }

      $this->assertEquals($result . '+' . $argument, $this->resolver->getCallableFromDefinition($definition)($argument), $label);
    }
  }

  /**
   * @dataProvider callableResolverExceptionHandlingTestCases
   * @covers ::getCallableFromDefinition
   */
  public function testCallbackResolverExceptionHandling($definition, $exception_class, $exception_message): void {
    $this->expectException($exception_class);
    $this->expectExceptionMessage($exception_message);
    $this->resolver->getCallableFromDefinition($definition);
  }

  /**
   * Test cases for ::testCallbackResolverExceptionHandling.
   */
  public static function callableResolverExceptionHandlingTestCases() {
    return [
      'String function' => [
        'not_a_callable',
        \InvalidArgumentException::class,
        'Class "not_a_callable" does not exist.',
      ],
      'Array notation' => [
        ['not_a_callable', 'not_a_callable'],
        \InvalidArgumentException::class,
        'The callable definition provided "[not_a_callable,not_a_callable]" is not a valid callable.',
      ],
      'Missing method on class, array notation' => [
        [static::class, 'method_not_exists'],
        \InvalidArgumentException::class,
        'The callable definition provided "[Drupal\Tests\Core\Utility\CallableResolverTest,method_not_exists]" is not a valid callable.',
      ],
      'Missing method on class, static notation' => [
        static::class . '::method_not_exists',
        \InvalidArgumentException::class,
        'The callable definition provided was invalid. Either class "Drupal\Tests\Core\Utility\CallableResolverTest" does not have a method "method_not_exists", or it is not callable.',
      ],
      'Missing class, static notation' => [
        '\NotARealClass::method',
        \InvalidArgumentException::class,
        'Class "\NotARealClass" does not exist.',
      ],
      'No method, static notation' => [
        NoMethodCallable::class . "::",
        \InvalidArgumentException::class,
        'The callable definition provided was invalid. Could not get class and method from definition "Drupal\Tests\Core\Utility\NoMethodCallable::".',
      ],
      'Service not in container' => [
        'bad_service:method',
        \InvalidArgumentException::class,
        'Class "bad_service" does not exist.',
      ],
      'Invalid method on valid service' => [
        'test_service:not_a_callable',
        \InvalidArgumentException::class,
        'The callable definition provided was invalid. Either class "Drupal\Tests\Core\Utility\CallableResolverTest" does not have a method "not_a_callable", or it is not callable.',
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
    return __METHOD__ . '+' . $suffix;
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
    return __METHOD__ . '+' . $suffix;
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
    return __METHOD__ . '+' . $suffix;
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
    return __METHOD__ . '-' . $this->injected . '+' . $suffix;
  }

}

class NoInstantiationMockStaticCallable {

  public function __construct() {
    throw new \Exception(sprintf('The class %s should not require instantiation for the static method to be called.', __CLASS__));
  }

  public static function staticMethod($suffix) {
    return __METHOD__ . '+' . $suffix;
  }

}

class NoMethodCallable {
}

class MockContainerAware implements ContainerAwareInterface {

  /**
   * The service container.
   */
  protected ContainerInterface $container;

  /**
   * Sets the service container.
   */
  public function setContainer(?ContainerInterface $container): void {
    $this->container = $container;
  }

  public function getResult($suffix) {
    if (empty($this->container)) {
      throw new \Exception('Container was not injected.');
    }
    return __METHOD__ . '+' . $suffix;
  }

}
