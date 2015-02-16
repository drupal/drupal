<?php

/**
 * @file
 * Contains \Drupal\Tests\Component\ProxyBuilder\ProxyBuilderTest.
 */

namespace Drupal\Tests\Component\ProxyBuilder;

use Drupal\Component\ProxyBuilder\ProxyBuilder;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Component\ProxyBuilder\ProxyBuilder
 * @group proxy_builder
 */
class ProxyBuilderTest extends UnitTestCase {

  /**
   * The tested proxy builder.
   *
   * @var \Drupal\Component\ProxyBuilder\ProxyBuilder
   */
  protected $proxyBuilder;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->proxyBuilder = new ProxyBuilder();
  }

  /**
   * @covers ::buildProxyClassName
   */
  public function testBuildProxyClassName() {
    $class_name = $this->proxyBuilder->buildProxyClassName('Drupal\Tests\Component\ProxyBuilder\TestServiceNoMethod');
    $this->assertEquals('Drupal_Tests_Component_ProxyBuilder_TestServiceNoMethod_Proxy', $class_name);
  }

  /**
   * Tests the basic methods like the constructor and the lazyLoadItself method.
   *
   * @covers ::build
   * @covers ::buildConstructorMethod
   * @covers ::buildLazyLoadItselfMethod
   */
  public function testBuildNoMethod() {
    $class = 'Drupal\Tests\Component\ProxyBuilder\TestServiceNoMethod';

    $result = $this->proxyBuilder->build($class);
    $this->assertEquals($this->buildExpectedClass($class, ''), $result);
  }

  /**
   * @covers ::buildMethod
   * @covers ::buildMethodBody
   */
  public function testBuildSimpleMethod() {
    $class = 'Drupal\Tests\Component\ProxyBuilder\TestServiceSimpleMethod';

    $result = $this->proxyBuilder->build($class);

    $method_body = <<<'EOS'

    public function method()
    {
        return $this->lazyLoadItself()->method();
    }

EOS;
    $this->assertEquals($this->buildExpectedClass($class, $method_body), $result);
  }

  /**
   * @covers ::buildMethod
   * @covers ::buildParameter
   * @covers ::buildMethodBody
   */
  public function testBuildMethodWithParameter() {
    $class = 'Drupal\Tests\Component\ProxyBuilder\TestServiceMethodWithParameter';

    $result = $this->proxyBuilder->build($class);

    $method_body = <<<'EOS'

    public function methodWithParameter($parameter)
    {
        return $this->lazyLoadItself()->methodWithParameter($parameter);
    }

EOS;
    $this->assertEquals($this->buildExpectedClass($class, $method_body), $result);
  }

  /**
   * @covers ::buildMethod
   * @covers ::buildParameter
   * @covers ::buildMethodBody
   */
  public function testBuildComplexMethod() {
    $class = 'Drupal\Tests\Component\ProxyBuilder\TestServiceComplexMethod';

    $result = $this->proxyBuilder->build($class);

    // @todo Solve the silly linebreak for array()
    $method_body = <<<'EOS'

    public function complexMethod($parameter, callable $function, \Drupal\Tests\Component\ProxyBuilder\TestServiceNoMethod $test_service = NULL, array &$elements = array (
    ))
    {
        return $this->lazyLoadItself()->complexMethod($parameter, $function, $test_service, $elements);
    }

EOS;

    $this->assertEquals($this->buildExpectedClass($class, $method_body), $result);
  }

  /**
   * @covers ::buildMethod
   * @covers ::buildMethodBody
   */
  public function testBuildReturnReference() {
    $class = 'Drupal\Tests\Component\ProxyBuilder\TestServiceReturnReference';

    $result = $this->proxyBuilder->build($class);

    // @todo Solve the silly linebreak for array()
    $method_body = <<<'EOS'

    public function &returnReference()
    {
        return $this->lazyLoadItself()->returnReference();
    }

EOS;

    $this->assertEquals($this->buildExpectedClass($class, $method_body), $result);
  }

  /**
   * @covers ::buildMethod
   * @covers ::buildParameter
   * @covers ::buildMethodBody
   */
  public function testBuildWithInterface() {
    $class = 'Drupal\Tests\Component\ProxyBuilder\TestServiceWithInterface';

    $result = $this->proxyBuilder->build($class);

    $method_body = <<<'EOS'

    public function testMethod($parameter)
    {
        return $this->lazyLoadItself()->testMethod($parameter);
    }

EOS;

    $interface_string = ' implements \Drupal\Tests\Component\ProxyBuilder\TestInterface';
    $this->assertEquals($this->buildExpectedClass($class, $method_body, $interface_string), $result);
  }

  /**
   * @covers ::build
   */
  public function testBuildWithNestedInterface() {
    $class = 'Drupal\Tests\Component\ProxyBuilder\TestServiceWithChildInterfaces';

    $result = $this->proxyBuilder->build($class);
    $method_body = '';

    $interface_string = ' implements \Drupal\Tests\Component\ProxyBuilder\TestChildInterface';
    $this->assertEquals($this->buildExpectedClass($class, $method_body, $interface_string), $result);
  }

  /**
   * @covers ::buildMethod
   * @covers ::buildParameter
   * @covers ::buildMethodBody
   */
  public function testBuildWithProtectedAndPrivateMethod() {
    $class = 'Drupal\Tests\Component\ProxyBuilder\TestServiceWithProtectedMethods';

    $result = $this->proxyBuilder->build($class);

    $method_body = <<<'EOS'

    public function testMethod($parameter)
    {
        return $this->lazyLoadItself()->testMethod($parameter);
    }

EOS;

$this->assertEquals($this->buildExpectedClass($class, $method_body), $result);
  }

  /**
   * @covers ::buildMethod
   * @covers ::buildParameter
   * @covers ::buildMethodBody
   */
  public function testBuildWithPublicStaticMethod() {
    $class = 'Drupal\Tests\Component\ProxyBuilder\TestServiceWithPublicStaticMethod';

    $result = $this->proxyBuilder->build($class);

    // Ensure that the static method is not wrapped.
    $method_body = <<<'EOS'

    public static function testMethod($parameter)
    {
        \Drupal\Tests\Component\ProxyBuilder\TestServiceWithPublicStaticMethod::testMethod($parameter);
    }

EOS;

    $this->assertEquals($this->buildExpectedClass($class, $method_body), $result);
  }

  /**
   * Constructs the expected class output.
   *
   * @param string $expected_methods_body
   *   The expected body of decorated methods.
   *
   * @return string
   *   The code of the entire proxy.
   */
  protected function buildExpectedClass($class, $expected_methods_body, $interface_string = '') {
    $proxy_class = $this->proxyBuilder->buildProxyClassName($class);
    $expected_string = <<<'EOS'
/**
 * Provides a proxy class for \{{ class }}.
 *
 * @see \Drupal\Component\ProxyBuilder
 */
class {{ proxy_class }}{{ interface_string }}
{

    /**
     * @var string
     */
    protected $serviceId;

    /**
     * @var \{{ class }}
     */
    protected $service;

    /**
     * The service container.
     *
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected $container;

    public function __construct(\Symfony\Component\DependencyInjection\ContainerInterface $container, $serviceId)
    {
        $this->container = $container;
        $this->serviceId = $serviceId;
    }

    protected function lazyLoadItself()
    {
        if (!isset($this->service)) {
            $method_name = 'get' . Container::camelize($this->serviceId) . 'Service';
            $this->service = $this->container->$method_name(false);
        }

        return $this->service;
    }
{{ expected_methods_body }}
}

EOS;
    $expected_string = str_replace('{{ proxy_class }}', $proxy_class, $expected_string);
    $expected_string = str_replace('{{ class }}', $class, $expected_string);
    $expected_string = str_replace('{{ expected_methods_body }}', $expected_methods_body, $expected_string);
    $expected_string = str_replace('{{ interface_string }}', $interface_string, $expected_string);

    return $expected_string;
  }

}

class TestServiceNoMethod {

}

class TestServiceSimpleMethod {

  public function method() {

  }

}

class TestServiceMethodWithParameter {

  public function methodWithParameter($parameter) {

  }

}

class TestServiceComplexMethod {

  public function complexMethod($parameter, callable $function, TestServiceNoMethod $test_service = NULL, array &$elements = array()) {

  }

}

class TestServiceReturnReference {

  public function &returnReference() {

  }

}

interface TestInterface {

  public function testMethod($parameter);

}

class TestServiceWithInterface implements TestInterface {

  public function testMethod($parameter) {

  }

}

class TestServiceWithProtectedMethods {

  public function testMethod($parameter) {

  }

  protected function protectedMethod($parameter) {

  }

  protected function privateMethod($parameter) {

  }

}

class TestServiceWithPublicStaticMethod {

  public static function testMethod($parameter) {
  }

}

interface TestBaseInterface {

}

interface TestChildInterface extends TestBaseInterface {

}

class TestServiceWithChildInterfaces implements TestChildInterface {

}
