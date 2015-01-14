<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\ProxyBuilder\ProxyBuilderTest.
 */

namespace Drupal\Tests\Core\ProxyBuilder;
use Drupal\Core\ProxyBuilder\ProxyBuilder;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\ProxyBuilder\ProxyBuilder
 * @group proxy_builder
 */
class ProxyBuilderTest extends UnitTestCase {

  /**
   * The tested proxy builder.
   *
   * @var \Drupal\Core\ProxyBuilder\ProxyBuilder
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
   * @covers ::buildMethod()
   * @covers ::buildParameter()
   * @covers ::buildMethodBody()
   */
  public function testBuildComplexMethod() {
    $class = 'Drupal\Tests\Core\ProxyBuilder\TestServiceComplexMethod';

    $result = $this->proxyBuilder->build($class);

    // @todo Solve the silly linebreak for array()
    $method_body = <<<'EOS'

    public function complexMethod($parameter, callable $function, \Drupal\Tests\Core\ProxyBuilder\TestServiceNoMethod $test_service = NULL, array &$elements = array (
    ))
    {
        return $this->lazyLoadItself()->complexMethod($parameter, $function, $test_service, $elements);
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

    use \Drupal\Core\DependencyInjection\DependencySerializationTrait;

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

class TestServiceComplexMethod {

  public function complexMethod($parameter, callable $function, TestServiceNoMethod $test_service = NULL, array &$elements = array()) {

  }

}
