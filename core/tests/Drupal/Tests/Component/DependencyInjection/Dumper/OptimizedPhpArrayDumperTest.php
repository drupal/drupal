<?php

/**
 * @file
 * Contains \Drupal\Tests\Component\DependencyInjection\Dumper\OptimizedPhpArrayDumperTest.
 */

namespace Drupal\Tests\Component\DependencyInjection\Dumper {

  use Symfony\Component\DependencyInjection\Definition;
  use Symfony\Component\DependencyInjection\Reference;
  use Symfony\Component\DependencyInjection\Parameter;
  use Symfony\Component\ExpressionLanguage\Expression;
  use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
  use Symfony\Component\DependencyInjection\ContainerInterface;

  /**
   * @coversDefaultClass \Drupal\Component\DependencyInjection\Dumper\OptimizedPhpArrayDumper
   * @group DependencyInjection
   */
  class OptimizedPhpArrayDumperTest extends \PHPUnit_Framework_TestCase {

    /**
     * The container builder instance.
     *
     * @var \Symfony\Component\DependencyInjection\ContainerBuilder
     */
    protected $containerBuilder;

    /**
     * The definition for the container to build in tests.
     *
     * @var array
     */
    protected $containerDefinition;

    /**
     * Whether the dumper uses the machine-optimized format or not.
     *
     * @var bool
     */
    protected $machineFormat = TRUE;

    /**
     * Stores the dumper class to use.
     *
     * @var string
     */
    protected $dumperClass = '\Drupal\Component\DependencyInjection\Dumper\OptimizedPhpArrayDumper';

    /**
     * The dumper instance.
     *
     * @var \Symfony\Component\DependencyInjection\Dumper\DumperInterface
     */
    protected $dumper;

    /**
     * {@inheritdoc}
     */
    protected function setUp() {
      // Setup a mock container builder.
      $this->containerBuilder = $this->prophesize('\Symfony\Component\DependencyInjection\ContainerBuilder');
      $this->containerBuilder->getAliases()->willReturn(array());
      $this->containerBuilder->getParameterBag()->willReturn(new ParameterBag());
      $this->containerBuilder->getDefinitions()->willReturn(NULL);
      $this->containerBuilder->isFrozen()->willReturn(TRUE);

      $definition = array();
      $definition['aliases'] = array();
      $definition['parameters'] = array();
      $definition['services'] = array();
      $definition['frozen'] = TRUE;
      $definition['machine_format'] = $this->machineFormat;

      $this->containerDefinition = $definition;

      // Create the dumper.
      $this->dumper = new $this->dumperClass($this->containerBuilder->reveal());
    }

    /**
     * Tests that an empty container works properly.
     *
     * @covers ::dump
     * @covers ::getArray
     * @covers ::supportsMachineFormat
     */
    public function testDumpForEmptyContainer() {
      $serialized_definition = $this->dumper->dump();
      $this->assertEquals(serialize($this->containerDefinition), $serialized_definition);
    }

    /**
     * Tests that alias processing works properly.
     *
     * @covers ::getAliases
     *
     * @dataProvider getAliasesDataProvider
     */
    public function testGetAliases($aliases, $definition_aliases) {
      $this->containerDefinition['aliases'] = $definition_aliases;
      $this->containerBuilder->getAliases()->willReturn($aliases);
      $this->assertEquals($this->containerDefinition, $this->dumper->getArray(), 'Expected definition matches dump.');
    }

    /**
     * Data provider for testGetAliases().
     *
     * @return array[]
     *   Returns data-set elements with:
     *     - aliases as returned by ContainerBuilder.
     *     - aliases as expected in the container definition.
     */
    public function getAliasesDataProvider() {
      return array(
        array(array(), array()),
        array(
          array('foo' => 'foo.alias'),
          array('foo' => 'foo.alias'),
        ),
        array(
          array('foo' => 'foo.alias', 'foo.alias' => 'foo.alias.alias'),
          array('foo' => 'foo.alias.alias', 'foo.alias' => 'foo.alias.alias'),
        ),
      );
    }

    /**
     * Tests that parameter processing works properly.
     *
     * @covers ::getParameters
     * @covers ::prepareParameters
     * @covers ::escape
     * @covers ::dumpValue
     * @covers ::getReferenceCall
     *
     * @dataProvider getParametersDataProvider
     */
    public function testGetParameters($parameters, $definition_parameters, $is_frozen) {
      $this->containerDefinition['parameters'] = $definition_parameters;
      $this->containerDefinition['frozen'] = $is_frozen;

      $parameter_bag = new ParameterBag($parameters);
      $this->containerBuilder->getParameterBag()->willReturn($parameter_bag);
      $this->containerBuilder->isFrozen()->willReturn($is_frozen);

      if (isset($parameters['reference'])) {
        $definition = new Definition('\stdClass');
        $this->containerBuilder->getDefinition('referenced_service')->willReturn($definition);
      }

      $this->assertEquals($this->containerDefinition, $this->dumper->getArray(), 'Expected definition matches dump.');
    }

    /**
     * Data provider for testGetParameters().
     *
     * @return array[]
     *   Returns data-set elements with:
     *     - parameters as returned by ContainerBuilder.
     *     - parameters as expected in the container definition.
     *     - frozen value
     */
    public function getParametersDataProvider() {
      return array(
        array(array(), array(), TRUE),
        array(
          array('foo' => 'value_foo'),
          array('foo' => 'value_foo'),
          TRUE,
        ),
        array(
          array('foo' => array('llama' => 'yes')),
          array('foo' => array('llama' => 'yes')),
          TRUE,
        ),
        array(
          array('foo' => '%llama%', 'llama' => 'yes'),
          array('foo' => '%%llama%%', 'llama' => 'yes'),
          TRUE,
        ),
        array(
          array('foo' => '%llama%', 'llama' => 'yes'),
          array('foo' => '%llama%', 'llama' => 'yes'),
          FALSE,
        ),
        array(
          array('reference' => new Reference('referenced_service')),
          array('reference' => $this->getServiceCall('referenced_service')),
          TRUE,
        ),
      );
    }

    /**
     * Tests that service processing works properly.
     *
     * @covers ::getServiceDefinitions
     * @covers ::getServiceDefinition
     * @covers ::dumpMethodCalls
     * @covers ::dumpCollection
     * @covers ::dumpCallable
     * @covers ::dumpValue
     * @covers ::getPrivateServiceCall
     * @covers ::getReferenceCall
     * @covers ::getServiceCall
     * @covers ::getParameterCall
     *
     * @dataProvider getDefinitionsDataProvider
     */
    public function testGetServiceDefinitions($services, $definition_services) {
      $this->containerDefinition['services'] = $definition_services;

      $this->containerBuilder->getDefinitions()->willReturn($services);

      $bar_definition = new Definition('\stdClass');
      $this->containerBuilder->getDefinition('bar')->willReturn($bar_definition);

      $private_definition = new Definition('\stdClass');
      $private_definition->setPublic(FALSE);

      $this->containerBuilder->getDefinition('private_definition')->willReturn($private_definition);

      $this->assertEquals($this->containerDefinition, $this->dumper->getArray(), 'Expected definition matches dump.');
    }

    /**
     * Data provider for testGetServiceDefinitions().
     *
     * @return array[]
     *   Returns data-set elements with:
     *     - parameters as returned by ContainerBuilder.
     *     - parameters as expected in the container definition.
     *     - frozen value
     */
    public function getDefinitionsDataProvider() {
      $base_service_definition = array(
        'class' => '\stdClass',
        'public' => TRUE,
        'file' => FALSE,
        'synthetic' => FALSE,
        'lazy' => FALSE,
        'arguments' => array(),
        'arguments_count' => 0,
        'properties' => array(),
        'calls' => array(),
        'scope' => ContainerInterface::SCOPE_CONTAINER,
        'shared' => TRUE,
        'factory' => FALSE,
        'configurator' => FALSE,
      );

      // Test basic flags.
      $service_definitions[] = array() + $base_service_definition;

      $service_definitions[] = array(
        'public' => FALSE,
      ) + $base_service_definition;

      $service_definitions[] = array(
        'file' => 'test_include.php',
      ) + $base_service_definition;

      $service_definitions[] = array(
        'synthetic' => TRUE,
      ) + $base_service_definition;

      $service_definitions[] = array(
        'lazy' => TRUE,
      ) + $base_service_definition;

      // Test a basic public Reference.
      $service_definitions[] = array(
        'arguments' => array('foo', new Reference('bar')),
        'arguments_count' => 2,
        'arguments_expected' => $this->getCollection(array('foo', $this->getServiceCall('bar'))),
      ) + $base_service_definition;

      // Test a public reference that should not throw an Exception.
      $reference = new Reference('bar', ContainerInterface::NULL_ON_INVALID_REFERENCE);
      $service_definitions[] = array(
        'arguments' => array($reference),
        'arguments_count' => 1,
        'arguments_expected' => $this->getCollection(array($this->getServiceCall('bar', ContainerInterface::NULL_ON_INVALID_REFERENCE))),
      ) + $base_service_definition;

      // Test a private shared service, denoted by having a Reference.
      $private_definition = array(
        'class' => '\stdClass',
        'public' => FALSE,
        'arguments_count' => 0,
      );

      $service_definitions[] = array(
        'arguments' => array('foo', new Reference('private_definition')),
        'arguments_count' => 2,
        'arguments_expected' => $this->getCollection(array(
          'foo',
          $this->getPrivateServiceCall('private_definition', $private_definition, TRUE),
        )),
      ) + $base_service_definition;

      // Test a private non-shared service, denoted by having a Definition.
      $private_definition_object = new Definition('\stdClass');
      $private_definition_object->setPublic(FALSE);

      $service_definitions[] = array(
        'arguments' => array('foo', $private_definition_object),
        'arguments_count' => 2,
        'arguments_expected' => $this->getCollection(array(
          'foo',
          $this->getPrivateServiceCall(NULL, $private_definition),
        )),
      ) + $base_service_definition;

      // Test a deep collection without a reference.
      $service_definitions[] = array(
        'arguments' => array(array(array('foo'))),
        'arguments_count' => 1,
      ) + $base_service_definition;

      // Test a deep collection with a reference to resolve.
      $service_definitions[] = array(
        'arguments' => array(array(new Reference('bar'))),
        'arguments_count' => 1,
        'arguments_expected' => $this->getCollection(array($this->getCollection(array($this->getServiceCall('bar'))))),
      ) + $base_service_definition;

      // Test a collection with a variable to resolve.
      $service_definitions[] = array(
        'arguments' => array(new Parameter('llama_parameter')),
        'arguments_count' => 1,
        'arguments_expected' => $this->getCollection(array($this->getParameterCall('llama_parameter'))),
      ) + $base_service_definition;

      // Test objects that have _serviceId property.
      $drupal_service = new \stdClass();
      $drupal_service->_serviceId = 'bar';

      $service_definitions[] = array(
        'arguments' => array($drupal_service),
        'arguments_count' => 1,
        'arguments_expected' => $this->getCollection(array($this->getServiceCall('bar'))),
      ) + $base_service_definition;

      // Test getMethodCalls.
      $calls = array(
        array('method', $this->getCollection(array())),
        array('method2', $this->getCollection(array())),
      );
      $service_definitions[] = array(
        'calls' => $calls,
      ) + $base_service_definition;

      $service_definitions[] = array(
        'scope' => ContainerInterface::SCOPE_PROTOTYPE,
        'shared' => FALSE,
      ) + $base_service_definition;

      // Test factory.
      $service_definitions[] = array(
        'factory' => array(new Reference('bar'), 'factoryMethod'),
        'factory_expected' => array($this->getServiceCall('bar'), 'factoryMethod'),
      ) + $base_service_definition;

      // Test invalid factory - needed to test deep dumpValue().
      $service_definitions[] = array(
        'factory' => array(array('foo', 'llama'), 'factoryMethod'),
      ) + $base_service_definition;

      // Test properties.
      $service_definitions[] = array(
        'properties' => array('_value' => 'llama'),
      ) + $base_service_definition;

      // Test configurator.
      $service_definitions[] = array(
        'configurator' => array(new Reference('bar'), 'configureService'),
        'configurator_expected' => array($this->getServiceCall('bar'), 'configureService'),
      ) + $base_service_definition;

      $services_provided = array();
      $services_provided[] = array(
        array(),
        array(),
      );

      foreach ($service_definitions as $service_definition) {
        $definition = $this->prophesize('\Symfony\Component\DependencyInjection\Definition');
        $definition->getClass()->willReturn($service_definition['class']);
        $definition->isPublic()->willReturn($service_definition['public']);
        $definition->getFile()->willReturn($service_definition['file']);
        $definition->isSynthetic()->willReturn($service_definition['synthetic']);
        $definition->isLazy()->willReturn($service_definition['lazy']);
        $definition->getArguments()->willReturn($service_definition['arguments']);
        $definition->getProperties()->willReturn($service_definition['properties']);
        $definition->getMethodCalls()->willReturn($service_definition['calls']);
        $definition->getScope()->willReturn($service_definition['scope']);
        $definition->getDecoratedService()->willReturn(NULL);
        $definition->getFactory()->willReturn($service_definition['factory']);
        $definition->getConfigurator()->willReturn($service_definition['configurator']);

        // Preserve order.
        $filtered_service_definition = array();
        foreach ($base_service_definition as $key => $value) {
          $filtered_service_definition[$key] = $service_definition[$key];
          unset($service_definition[$key]);

          if ($key == 'class' || $key == 'arguments_count') {
            continue;
          }

          if ($filtered_service_definition[$key] === $base_service_definition[$key]) {
            unset($filtered_service_definition[$key]);
          }
        }

        // Add remaining properties.
        $filtered_service_definition += $service_definition;

        // Allow to set _expected values.
        foreach (array('arguments', 'factory', 'configurator') as $key) {
          $expected = $key . '_expected';
          if (isset($filtered_service_definition[$expected])) {
            $filtered_service_definition[$key] = $filtered_service_definition[$expected];
            unset($filtered_service_definition[$expected]);
          }
        }

        // Remove any remaining scope.
        unset($filtered_service_definition['scope']);

        if (isset($filtered_service_definition['public']) && $filtered_service_definition['public'] === FALSE) {
          $services_provided[] = array(
            array('foo_service' => $definition->reveal()),
            array(),
          );
          continue;
        }

        $services_provided[] = array(
          array('foo_service' => $definition->reveal()),
          array('foo_service' => $this->serializeDefinition($filtered_service_definition)),
        );
      }

      return $services_provided;
    }

    /**
     * Helper function to serialize a definition.
     *
     * Used to override serialization.
     */
    protected function serializeDefinition(array $service_definition) {
      return serialize($service_definition);
    }

    /**
     * Helper function to return a service definition.
     */
    protected function getServiceCall($id, $invalid_behavior = ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE) {
      return (object) array(
        'type' => 'service',
        'id' => $id,
        'invalidBehavior' => $invalid_behavior,
      );
    }

    /**
     * Tests that the correct InvalidArgumentException is thrown for getScope().
     *
     * @covers ::getServiceDefinition
     *
     * @expectedException \Symfony\Component\DependencyInjection\Exception\InvalidArgumentException
     */
    public function testGetServiceDefinitionWithInvalidScope() {
      $bar_definition = new Definition('\stdClass');
      $bar_definition->setScope('foo_scope');
      $services['bar'] = $bar_definition;

      $this->containerBuilder->getDefinitions()->willReturn($services);
      $this->dumper->getArray();
    }

    /**
     * Tests that getDecoratedService() is unsupported.
     *
     * Tests that the correct InvalidArgumentException is thrown for
     * getDecoratedService().
     *
     * @covers ::getServiceDefinition
     *
     * @expectedException \Symfony\Component\DependencyInjection\Exception\InvalidArgumentException
     */
    public function testGetServiceDefinitionForDecoratedService() {
      $bar_definition = new Definition('\stdClass');
      $bar_definition->setDecoratedService(new Reference('foo'));
      $services['bar'] = $bar_definition;

      $this->containerBuilder->getDefinitions()->willReturn($services);
      $this->dumper->getArray();
    }

    /**
     * Tests that the correct RuntimeException is thrown for expressions.
     *
     * @covers ::dumpValue
     *
     * @expectedException \Symfony\Component\DependencyInjection\Exception\RuntimeException
     */
    public function testGetServiceDefinitionForExpression() {
      $expression = new Expression();

      $bar_definition = new Definition('\stdClass');
      $bar_definition->addArgument($expression);
      $services['bar'] = $bar_definition;

      $this->containerBuilder->getDefinitions()->willReturn($services);
      $this->dumper->getArray();
    }

    /**
     * Tests that the correct RuntimeException is thrown for dumping an object.
     *
     * @covers ::dumpValue
     *
     * @expectedException \Symfony\Component\DependencyInjection\Exception\RuntimeException
     */
    public function testGetServiceDefinitionForObject() {
      $service = new \stdClass();

      $bar_definition = new Definition('\stdClass');
      $bar_definition->addArgument($service);
      $services['bar'] = $bar_definition;

      $this->containerBuilder->getDefinitions()->willReturn($services);
      $this->dumper->getArray();
    }

    /**
     * Tests that the correct RuntimeException is thrown for dumping a resource.
     *
     * @covers ::dumpValue
     *
     * @expectedException \Symfony\Component\DependencyInjection\Exception\RuntimeException
     */
    public function testGetServiceDefinitionForResource() {
      $resource = fopen('php://memory', 'r');

      $bar_definition = new Definition('\stdClass');
      $bar_definition->addArgument($resource);
      $services['bar'] = $bar_definition;

      $this->containerBuilder->getDefinitions()->willReturn($services);
      $this->dumper->getArray();
    }

    /**
     * Helper function to return a private service definition.
     */
    protected function getPrivateServiceCall($id, $service_definition, $shared = FALSE) {
      if (!$id) {
        $hash = sha1(serialize($service_definition));
        $id = 'private__' . $hash;
      }
      return (object) array(
        'type' => 'private_service',
        'id' => $id,
        'value' => $service_definition,
        'shared' => $shared,
      );
    }

    /**
     * Helper function to return a machine-optimized collection.
     */
    protected function getCollection($collection, $resolve = TRUE) {
      return (object) array(
        'type' => 'collection',
        'value' => $collection,
        'resolve' => $resolve,
      );
    }

    /**
     * Helper function to return a parameter definition.
     */
    protected function getParameterCall($name) {
      return (object) array(
        'type' => 'parameter',
        'name' => $name,
      );
    }

  }

}

/**
 * As Drupal Core does not ship with ExpressionLanguage component we need to
 * define a dummy, else it cannot be tested.
 */
namespace Symfony\Component\ExpressionLanguage {
  if (!class_exists('\Symfony\Component\ExpressionLanguage\Expression')) {
    /**
     * Dummy class to ensure non-existent Symfony component can be tested.
     */
    class Expression {

      /**
       * Gets the string representation of the expression.
       */
      public function __toString() {
        return 'dummy_expression';
      }

    }
  }
}
