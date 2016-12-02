<?php

/**
 * @file
 * Contains \Drupal\Tests\Component\DependencyInjection\ContainerTest.
 */

namespace Drupal\Tests\Component\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Exception\InvalidArgumentException;
use Prophecy\Argument;

/**
 * @coversDefaultClass \Drupal\Component\DependencyInjection\Container
 * @group DependencyInjection
 */
class ContainerTest extends \PHPUnit_Framework_TestCase {

  /**
   * The tested container.
   *
   * @var \Symfony\Component\DependencyInjection\ContainerInterface
   */
  protected $container;

  /**
   * The container definition used for the test.
   *
   * @var array
   */
  protected $containerDefinition;

  /**
   * The container class to be tested.
   *
   * @var bool
   */
  protected $containerClass;

  /**
   * Whether the container uses the machine-optimized format or not.
   *
   * @var bool
   */
  protected $machineFormat;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->machineFormat = TRUE;
    $this->containerClass = '\Drupal\Component\DependencyInjection\Container';
    $this->containerDefinition = $this->getMockContainerDefinition();
    $this->container = new $this->containerClass($this->containerDefinition);
  }

  /**
   * Tests that passing a non-supported format throws an InvalidArgumentException.
   *
   * @covers ::__construct
   *
   * @expectedException \Symfony\Component\DependencyInjection\Exception\InvalidArgumentException
   */
  public function testConstruct() {
    $container_definition = $this->getMockContainerDefinition();
    $container_definition['machine_format'] = !$this->machineFormat;
    $container = new $this->containerClass($container_definition);
  }

  /**
   * Tests that Container::getParameter() works properly.
   *
   * @covers ::getParameter
   */
  public function testGetParameter() {
    $this->assertEquals($this->containerDefinition['parameters']['some_config'], $this->container->getParameter('some_config'), 'Container parameter matches for %some_config%.');
    $this->assertEquals($this->containerDefinition['parameters']['some_other_config'], $this->container->getParameter('some_other_config'), 'Container parameter matches for %some_other_config%.');
  }

  /**
   * Tests that Container::getParameter() works properly for non-existing
   * parameters.
   *
   * @covers ::getParameter
   * @covers ::getParameterAlternatives
   * @covers ::getAlternatives
   *
   * @expectedException \Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException
   */
  public function testGetParameterIfNotFound() {
    $this->container->getParameter('parameter_that_does_not_exist');
  }

  /**
   * Tests that Container::getParameter() works properly for NULL parameters.
   *
   * @covers ::getParameter
   *
   * @expectedException \Symfony\Component\DependencyInjection\Exception\ParameterNotFoundException
   */
  public function testGetParameterIfNotFoundBecauseNull() {
    $this->container->getParameter(NULL);
  }

  /**
   * Tests that Container::hasParameter() works properly.
   *
   * @covers ::hasParameter
   */
  public function testHasParameter() {
    $this->assertTrue($this->container->hasParameter('some_config'), 'Container parameters include %some_config%.');
    $this->assertFalse($this->container->hasParameter('some_config_not_exists'), 'Container parameters do not include %some_config_not_exists%.');
  }

  /**
   * Tests that Container::setParameter() in an unfrozen case works properly.
   *
   * @covers ::setParameter
   */
  public function testSetParameterWithUnfrozenContainer() {
    $container_definition = $this->containerDefinition;
    $container_definition['frozen'] = FALSE;
    $this->container = new $this->containerClass($container_definition);
    $this->container->setParameter('some_config', 'new_value');
    $this->assertEquals('new_value', $this->container->getParameter('some_config'), 'Container parameters can be set.');
  }

  /**
   * Tests that Container::setParameter() in a frozen case works properly.
   *
   * @covers ::setParameter
   *
   * @expectedException \Symfony\Component\DependencyInjection\Exception\LogicException
   */
  public function testSetParameterWithFrozenContainer() {
    $this->container = new $this->containerClass($this->containerDefinition);
    $this->container->setParameter('some_config', 'new_value');
  }

  /**
   * Tests that Container::get() works properly.
   *
   * @covers ::get
   * @covers ::createService
   */
  public function testGet() {
    $container = $this->container->get('service_container');
    $this->assertSame($this->container, $container, 'Container can be retrieved from itself.');

    // Retrieve services of the container.
    $other_service_class = $this->containerDefinition['services']['other.service']['class'];
    $other_service = $this->container->get('other.service');
    $this->assertInstanceOf($other_service_class, $other_service, 'other.service has the right class.');

    $some_parameter = $this->containerDefinition['parameters']['some_config'];
    $some_other_parameter = $this->containerDefinition['parameters']['some_other_config'];

    $service = $this->container->get('service.provider');

    $this->assertEquals($other_service, $service->getSomeOtherService(), '@other.service was injected via constructor.');
    $this->assertEquals($some_parameter, $service->getSomeParameter(), '%some_config% was injected via constructor.');
    $this->assertEquals($this->container, $service->getContainer(), 'Container was injected via setter injection.');
    $this->assertEquals($some_other_parameter, $service->getSomeOtherParameter(), '%some_other_config% was injected via setter injection.');
    $this->assertEquals($service->_someProperty, 'foo', 'Service has added properties.');
  }

  /**
   * Tests that Container::get() for non-shared services works properly.
   *
   * @covers ::get
   * @covers ::createService
   */
  public function testGetForNonSharedService() {
    $service = $this->container->get('non_shared_service');
    $service2 = $this->container->get('non_shared_service');

    $this->assertNotSame($service, $service2, 'Non shared services are always re-instantiated.');
  }

  /**
   * Tests that Container::get() works properly for class from parameters.
   *
   * @covers ::get
   * @covers ::createService
   */
  public function testGetForClassFromParameter() {
    $container_definition = $this->containerDefinition;
    $container_definition['frozen'] = FALSE;
    $container = new $this->containerClass($container_definition);

    $other_service_class = $this->containerDefinition['parameters']['some_parameter_class'];
    $other_service = $container->get('other.service_class_from_parameter');
    $this->assertInstanceOf($other_service_class, $other_service, 'other.service_class_from_parameter has the right class.');
  }

  /**
   * Tests that Container::set() works properly.
   *
   * @covers ::set
   */
  public function testSet() {
    $this->assertNull($this->container->get('new_id', ContainerInterface::NULL_ON_INVALID_REFERENCE));
    $mock_service = new MockService();
    $this->container->set('new_id', $mock_service);

    $this->assertSame($mock_service, $this->container->get('new_id'), 'A manual set service works as expected.');
  }

  /**
   * Tests that Container::has() works properly.
   *
   * @covers ::has
   */
  public function testHas() {
    $this->assertTrue($this->container->has('other.service'));
    $this->assertFalse($this->container->has('another.service'));

    // Set the service manually, ensure that its also respected.
    $mock_service = new MockService();
    $this->container->set('another.service', $mock_service);
    $this->assertTrue($this->container->has('another.service'));
  }

  /**
   * Tests that Container::has() for aliased services works properly.
   *
   * @covers ::has
   */
  public function testHasForAliasedService() {
    $service = $this->container->has('service.provider');
    $aliased_service = $this->container->has('service.provider_alias');
    $this->assertSame($service, $aliased_service);
  }

  /**
   * Tests that Container::get() for circular dependencies works properly.
   * @expectedException \Symfony\Component\DependencyInjection\Exception\ServiceCircularReferenceException
   * @covers ::get
   * @covers ::createService
   */
  public function testGetForCircularServices() {
    $this->container->get('circular_dependency');
  }

  /**
   * Tests that Container::get() for non-existent services works properly.
   *
   * @covers ::get
   * @covers ::createService
   * @covers ::getAlternatives
   * @covers ::getServiceAlternatives
   *
   * @expectedException \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
   */
  public function testGetForNonExistantService() {
    $this->container->get('service_not_exists');
  }

  /**
   * Tests that Container::get() for a serialized definition works properly.
   *
   * @covers ::get
   * @covers ::createService
   */
  public function testGetForSerializedServiceDefinition() {
    $container_definition = $this->containerDefinition;
    $container_definition['services']['other.service'] = serialize($container_definition['services']['other.service']);
    $container = new $this->containerClass($container_definition);

    // Retrieve services of the container.
    $other_service_class = $this->containerDefinition['services']['other.service']['class'];
    $other_service = $container->get('other.service');
    $this->assertInstanceOf($other_service_class, $other_service, 'other.service has the right class.');

    $service = $container->get('service.provider');
    $this->assertEquals($other_service, $service->getSomeOtherService(), '@other.service was injected via constructor.');
  }

  /**
   * Tests that Container::get() for non-existent parameters works properly.
   *
   * @covers ::get
   * @covers ::createService
   * @covers ::resolveServicesAndParameters
   */
  public function testGetForNonExistantParameterDependency() {
    $service = $this->container->get('service_parameter_not_exists', ContainerInterface::NULL_ON_INVALID_REFERENCE);
    $this->assertNull($service, 'Service is NULL.');
  }

  /**
   * Tests Container::get() with an exception due to missing parameter on the second call.
   *
   * @covers ::get
   * @covers ::createService
   * @covers ::resolveServicesAndParameters
   *
   * @expectedException \Symfony\Component\DependencyInjection\Exception\InvalidArgumentException
   */
  public function testGetForParameterDependencyWithExceptionOnSecondCall() {
    $service = $this->container->get('service_parameter_not_exists', ContainerInterface::NULL_ON_INVALID_REFERENCE);
    $this->assertNull($service, 'Service is NULL.');

    // Reset the service.
    $this->container->set('service_parameter_not_exists', NULL);
    $this->container->get('service_parameter_not_exists');
  }

  /**
   * Tests that Container::get() for non-existent parameters works properly.
   *
   * @covers ::get
   * @covers ::createService
   * @covers ::resolveServicesAndParameters
   *
   * @expectedException \Symfony\Component\DependencyInjection\Exception\InvalidArgumentException
   */
  public function testGetForNonExistantParameterDependencyWithException() {
    $this->container->get('service_parameter_not_exists');
  }

  /**
   * Tests that Container::get() for non-existent dependencies works properly.
   *
   * @covers ::get
   * @covers ::createService
   * @covers ::resolveServicesAndParameters
   */
  public function testGetForNonExistantServiceDependency() {
    $service = $this->container->get('service_dependency_not_exists', ContainerInterface::NULL_ON_INVALID_REFERENCE);
    $this->assertNull($service, 'Service is NULL.');
  }

  /**
   * Tests that Container::get() for non-existent dependencies works properly.
   *
   * @covers ::get
   * @covers ::createService
   * @covers ::resolveServicesAndParameters
   * @covers ::getAlternatives
   *
   * @expectedException \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
   */
  public function testGetForNonExistantServiceDependencyWithException() {
    $this->container->get('service_dependency_not_exists');
  }

  /**
   * Tests that Container::get() for non-existent services works properly.
   *
   * @covers ::get
   * @covers ::createService
   */
  public function testGetForNonExistantServiceWhenUsingNull() {
    $this->assertNull($this->container->get('service_not_exists', ContainerInterface::NULL_ON_INVALID_REFERENCE), 'Not found service does not throw exception.');
  }

  /**
   * Tests that Container::get() for NULL service works properly.
   * @covers ::get
   * @covers ::createService
   *
   * @expectedException \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
   */
  public function testGetForNonExistantNULLService() {
    $this->container->get(NULL);
  }

  /**
   * Tests multiple Container::get() calls for non-existing dependencies work.
   *
   * @covers ::get
   * @covers ::createService
   */
  public function testGetForNonExistantServiceMultipleTimes() {
    $container = new $this->containerClass();

    $this->assertNull($container->get('service_not_exists', ContainerInterface::NULL_ON_INVALID_REFERENCE), 'Not found service does not throw exception.');
    $this->assertNull($container->get('service_not_exists', ContainerInterface::NULL_ON_INVALID_REFERENCE), 'Not found service does not throw exception on second call.');
  }

  /**
   * Tests multiple Container::get() calls with exception on the second time.
   *
   * @covers ::get
   * @covers ::createService
   * @covers ::getAlternatives
   *
   * @expectedException \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException
   */
  public function testGetForNonExistantServiceWithExceptionOnSecondCall() {
    $this->assertNull($this->container->get('service_not_exists', ContainerInterface::NULL_ON_INVALID_REFERENCE), 'Not found service does nto throw exception.');
    $this->container->get('service_not_exists');
  }

  /**
   * Tests that Container::get() for aliased services works properly.
   *
   * @covers ::get
   * @covers ::createService
   */
  public function testGetForAliasedService() {
    $service = $this->container->get('service.provider');
    $aliased_service = $this->container->get('service.provider_alias');
    $this->assertSame($service, $aliased_service);
  }

  /**
   * Tests that Container::get() for synthetic services works - if defined.
   *
   * @covers ::get
   * @covers ::createService
   */
  public function testGetForSyntheticService() {
    $synthetic_service = new \stdClass();
    $this->container->set('synthetic', $synthetic_service);
    $test_service = $this->container->get('synthetic');
    $this->assertSame($synthetic_service, $test_service);
  }

  /**
   * Tests that Container::get() for synthetic services throws an Exception if not defined.
   *
   * @covers ::get
   * @covers ::createService
   *
   * @expectedException \Symfony\Component\DependencyInjection\Exception\RuntimeException
   */
  public function testGetForSyntheticServiceWithException() {
    $this->container->get('synthetic');
  }

  /**
   * Tests that Container::get() for services with file includes works.
   *
   * @covers ::get
   * @covers ::createService
   */
  public function testGetWithFileInclude() {
    $file_service = $this->container->get('container_test_file_service_test');
    $this->assertTrue(function_exists('container_test_file_service_test_service_function'));
    $this->assertEquals('Hello Container', container_test_file_service_test_service_function());
  }

  /**
   * Tests that Container::get() for various arguments lengths works.
   *
   * @covers ::get
   * @covers ::createService
   * @covers ::resolveServicesAndParameters
   */
  public function testGetForInstantiationWithVariousArgumentLengths() {
    $args = array();
    for ($i = 0; $i < 12; $i++) {
      $instantiation_service = $this->container->get('service_test_instantiation_' . $i);
      $this->assertEquals($args, $instantiation_service->getArguments());
      $args[] = 'arg_' . $i;
    }
  }

  /**
   * Tests that Container::get() for wrong factories works correctly.
   *
   * @covers ::get
   * @covers ::createService
   *
   * @expectedException \Symfony\Component\DependencyInjection\Exception\RuntimeException
   */
  public function testGetForWrongFactory() {
    $this->container->get('wrong_factory');
  }

  /**
   * Tests Container::get() for factories via services (Symfony 2.7.0).
   *
   * @covers ::get
   * @covers ::createService
   */
  public function testGetForFactoryService() {
    $factory_service = $this->container->get('factory_service');
    $factory_service_class = $this->container->getParameter('factory_service_class');
    $this->assertInstanceOf($factory_service_class, $factory_service);
  }

  /**
   * Tests that Container::get() for factories via class works (Symfony 2.7.0).
   *
   * @covers ::get
   * @covers ::createService
   */
  public function testGetForFactoryClass() {
    $service = $this->container->get('service.provider');
    $factory_service = $this->container->get('factory_class');

    $this->assertInstanceOf(get_class($service), $factory_service);
    $this->assertEquals('bar', $factory_service->getSomeParameter(), 'Correct parameter was passed via the factory class instantiation.');
    $this->assertEquals($this->container, $factory_service->getContainer(), 'Container was injected via setter injection.');
  }

  /**
   * Tests that Container::get() for configurable services throws an Exception.
   *
   * @covers ::get
   * @covers ::createService
   *
   * @expectedException \Symfony\Component\DependencyInjection\Exception\InvalidArgumentException
   */
  public function testGetForConfiguratorWithException() {
    $this->container->get('configurable_service_exception');
  }

  /**
   * Tests that Container::get() for configurable services works.
   *
   * @covers ::get
   * @covers ::createService
   */
  public function testGetForConfigurator() {
    $container = $this->container;

    // Setup a configurator.
    $configurator = $this->prophesize('\Drupal\Tests\Component\DependencyInjection\MockConfiguratorInterface');
    $configurator->configureService(Argument::type('object'))
      ->shouldBeCalled(1)
      ->will(function($args) use ($container) {
        $args[0]->setContainer($container);
      });
    $container->set('configurator', $configurator->reveal());

    // Test that the configurator worked.
    $service = $container->get('configurable_service');
    $this->assertSame($container, $service->getContainer(), 'Container was injected via configurator.');
  }

  /**
   * Tests that private services work correctly.
   *
   * @covers ::get
   * @covers ::createService
   * @covers ::resolveServicesAndParameters
   */
  public function testResolveServicesAndParametersForPrivateService() {
    $service = $this->container->get('service_using_private');
    $private_service = $service->getSomeOtherService();
    $this->assertEquals($private_service->getSomeParameter(), 'really_private_lama', 'Private was found successfully.');

    // Test that sharing the same private services works.
    $service = $this->container->get('another_service_using_private');
    $another_private_service = $service->getSomeOtherService();
    $this->assertNotSame($private_service, $another_private_service, 'Private service is not shared.');
    $this->assertEquals($private_service->getSomeParameter(), 'really_private_lama', 'Private was found successfully.');
  }

  /**
   * Tests that private service sharing works correctly.
   *
   * @covers ::get
   * @covers ::createService
   * @covers ::resolveServicesAndParameters
   */
  public function testResolveServicesAndParametersForSharedPrivateService() {
    $service = $this->container->get('service_using_shared_private');
    $private_service = $service->getSomeOtherService();
    $this->assertEquals($private_service->getSomeParameter(), 'really_private_lama', 'Private was found successfully.');

    // Test that sharing the same private services works.
    $service = $this->container->get('another_service_using_shared_private');
    $same_private_service = $service->getSomeOtherService();
    $this->assertSame($private_service, $same_private_service, 'Private service is shared.');
    $this->assertEquals($private_service->getSomeParameter(), 'really_private_lama', 'Private was found successfully.');
  }

  /**
   * Tests that services with an array of arguments work correctly.
   *
   * @covers ::get
   * @covers ::createService
   * @covers ::resolveServicesAndParameters
   */
  public function testResolveServicesAndParametersForArgumentsUsingDeepArray() {
    $service = $this->container->get('service_using_array');
    $other_service = $this->container->get('other.service');
    $this->assertEquals($other_service, $service->getSomeOtherService(), '@other.service was injected via constructor.');
  }

  /**
   * Tests that services that are optional work correctly.
   *
   * @covers ::get
   * @covers ::createService
   * @covers ::resolveServicesAndParameters
   */
  public function testResolveServicesAndParametersForOptionalServiceDependencies() {
    $service = $this->container->get('service_with_optional_dependency');
    $this->assertNull($service->getSomeOtherService(), 'other service was NULL was expected.');
  }

  /**
   * Tests that an invalid argument throw an Exception.
   *
   * @covers ::get
   * @covers ::createService
   * @covers ::resolveServicesAndParameters
   *
   * @expectedException \Symfony\Component\DependencyInjection\Exception\InvalidArgumentException
   */
  public function testResolveServicesAndParametersForInvalidArgument() {
    $this->container->get('invalid_argument_service');
  }

  /**
   * Tests that invalid arguments throw an Exception.
   *
   * @covers ::get
   * @covers ::createService
   * @covers ::resolveServicesAndParameters
   *
   * @expectedException \Symfony\Component\DependencyInjection\Exception\InvalidArgumentException
   */
  public function testResolveServicesAndParametersForInvalidArguments() {
    // In case the machine-optimized format is not used, we need to simulate the
    // test failure.
    if (!$this->machineFormat) {
      throw new InvalidArgumentException('Simulating the test failure.');
    }
    $this->container->get('invalid_arguments_service');
  }

  /**
   * Tests that a parameter that points to a service works correctly.
   *
   * @covers ::get
   * @covers ::createService
   * @covers ::resolveServicesAndParameters
   */
  public function testResolveServicesAndParametersForServiceInstantiatedFromParameter() {
    $service = $this->container->get('service.provider');
    $test_service = $this->container->get('service_with_parameter_service');
    $this->assertSame($service, $test_service->getSomeOtherService(), 'Service was passed via parameter.');
  }

  /**
   * Tests that Container::initialized works correctly.
   *
   * @covers ::initialized
   */
  public function testInitialized() {
    $this->assertFalse($this->container->initialized('late.service'), 'Late service is not initialized.');
    $this->container->get('late.service');
    $this->assertTrue($this->container->initialized('late.service'), 'Late service is initialized after it was retrieved once.');
  }

  /**
   * Tests that Container::initialized works correctly for aliases.
   *
   * @covers ::initialized
   */
  public function testInitializedForAliases() {
    $this->assertFalse($this->container->initialized('late.service_alias'), 'Late service is not initialized.');
    $this->container->get('late.service');
    $this->assertTrue($this->container->initialized('late.service_alias'), 'Late service is initialized after it was retrieved once.');
  }

  /**
   * Tests that unsupported methods throw an Exception.
   *
   * @covers ::enterScope
   * @covers ::leaveScope
   * @covers ::addScope
   * @covers ::hasScope
   * @covers ::isScopeActive
   *
   * @expectedException \BadMethodCallException
   *
   * @dataProvider scopeExceptionTestProvider
   */
  public function testScopeFunctionsWithException($method, $argument) {
    $callable = array(
      $this->container,
      $method,
    );

    $callable($argument);
  }

  /**
   * Data provider for scopeExceptionTestProvider().
   *
   * @return array[]
   *   Returns per data set an array with:
   *     - method name to call
   *     - argument to pass
   */
  public function scopeExceptionTestProvider() {
    $scope = $this->prophesize('\Symfony\Component\DependencyInjection\ScopeInterface')->reveal();
    return array(
      array('enterScope', 'test_scope'),
      array('leaveScope', 'test_scope'),
      array('hasScope', 'test_scope'),
      array('isScopeActive', 'test_scope'),
      array('addScope', $scope),
    );
  }

  /**
   * Tests that Container::getServiceIds() works properly.
   *
   * @covers ::getServiceIds
   */
  public function testGetServiceIds() {
    $service_definition_keys = array_keys($this->containerDefinition['services']);
    $this->assertEquals($service_definition_keys, $this->container->getServiceIds(), 'Retrieved service IDs match definition.');

    $mock_service = new MockService();
    $this->container->set('bar', $mock_service);
    $this->container->set('service.provider', $mock_service);
    $service_definition_keys[] = 'bar';

    $this->assertEquals($service_definition_keys, $this->container->getServiceIds(), 'Retrieved service IDs match definition after setting new services.');
  }

  /**
   * Gets a mock container definition.
   *
   * @return array
   *   Associated array with parameters and services.
   */
  protected function getMockContainerDefinition() {
    $fake_service = new \stdClass();
    $parameters = array();
    $parameters['some_parameter_class'] = get_class($fake_service);
    $parameters['some_private_config'] = 'really_private_lama';
    $parameters['some_config'] = 'foo';
    $parameters['some_other_config'] = 'lama';
    $parameters['factory_service_class'] = get_class($fake_service);
    // Also test alias resolving.
    $parameters['service_from_parameter'] = $this->getServiceCall('service.provider_alias');

    $services = array();
    $services['service_container'] = array(
      'class' => '\Drupal\service_container\DependencyInjection\Container',
    );
    $services['other.service'] = array(
      'class' => get_class($fake_service),
    );

    $services['non_shared_service'] = array(
      'class' => get_class($fake_service),
      'shared' => FALSE,
    );

    $services['other.service_class_from_parameter'] = array(
      'class' => $this->getParameterCall('some_parameter_class'),
    );
    $services['late.service'] = array(
      'class' => get_class($fake_service),
    );
    $services['service.provider'] = array(
      'class' => '\Drupal\Tests\Component\DependencyInjection\MockService',
      'arguments' => $this->getCollection(array(
        $this->getServiceCall('other.service'),
        $this->getParameterCall('some_config'),
      )),
      'properties' => $this->getCollection(array('_someProperty' => 'foo')),
      'calls' => array(
        array('setContainer', $this->getCollection(array(
          $this->getServiceCall('service_container'),
        ))),
        array('setOtherConfigParameter', $this->getCollection(array(
          $this->getParameterCall('some_other_config'),
        ))),
      ),
      'priority' => 0,
    );

    // Test private services.
    $private_service = array(
      'class' => '\Drupal\Tests\Component\DependencyInjection\MockService',
      'arguments' => $this->getCollection(array(
        $this->getServiceCall('other.service'),
        $this->getParameterCall('some_private_config'),
      )),
      'public' => FALSE,
    );

    $services['service_using_private'] = array(
      'class' => '\Drupal\Tests\Component\DependencyInjection\MockService',
      'arguments' => $this->getCollection(array(
        $this->getPrivateServiceCall(NULL, $private_service),
        $this->getParameterCall('some_config'),
      )),
    );
    $services['another_service_using_private'] = array(
      'class' => '\Drupal\Tests\Component\DependencyInjection\MockService',
      'arguments' => $this->getCollection(array(
        $this->getPrivateServiceCall(NULL, $private_service),
        $this->getParameterCall('some_config'),
      )),
    );

    // Test shared private services.
    $id = 'private_service_shared_1';

    $services['service_using_shared_private'] = array(
      'class' => '\Drupal\Tests\Component\DependencyInjection\MockService',
      'arguments' => $this->getCollection(array(
        $this->getPrivateServiceCall($id, $private_service, TRUE),
        $this->getParameterCall('some_config'),
      )),
    );
    $services['another_service_using_shared_private'] = array(
      'class' => '\Drupal\Tests\Component\DependencyInjection\MockService',
      'arguments' => $this->getCollection(array(
        $this->getPrivateServiceCall($id, $private_service, TRUE),
        $this->getParameterCall('some_config'),
      )),
    );

    // Tests service with invalid argument.
    $services['invalid_argument_service'] = array(
      'class' => '\Drupal\Tests\Component\DependencyInjection\MockService',
      'arguments' => $this->getCollection(array(
        1, // Test passing non-strings, too.
        (object) array(
          'type' => 'invalid',
        ),
      )),
    );

    $services['invalid_arguments_service'] = array(
      'class' => '\Drupal\Tests\Component\DependencyInjection\MockService',
      'arguments' => (object) array(
        'type' => 'invalid',
      ),
    );

    // Test service that needs deep-traversal.
    $services['service_using_array'] = array(
      'class' => '\Drupal\Tests\Component\DependencyInjection\MockService',
      'arguments' => $this->getCollection(array(
        $this->getCollection(array(
          $this->getServiceCall('other.service'),
        )),
        $this->getParameterCall('some_private_config'),
      )),
    );

    $services['service_with_optional_dependency'] = array(
      'class' => '\Drupal\Tests\Component\DependencyInjection\MockService',
      'arguments' => $this->getCollection(array(
        $this->getServiceCall('service.does_not_exist', ContainerInterface::NULL_ON_INVALID_REFERENCE),
        $this->getParameterCall('some_private_config'),
      )),

    );

    $services['factory_service'] = array(
      'class' => '\Drupal\service_container\ServiceContainer\ControllerInterface',
      'factory' => array(
        $this->getServiceCall('service.provider'),
        'getFactoryMethod',
      ),
      'arguments' => $this->getCollection(array(
        $this->getParameterCall('factory_service_class'),
      )),
    );
    $services['factory_class'] = array(
      'class' => '\Drupal\service_container\ServiceContainer\ControllerInterface',
      'factory' => '\Drupal\Tests\Component\DependencyInjection\MockService::getFactoryMethod',
      'arguments' => array(
        '\Drupal\Tests\Component\DependencyInjection\MockService',
        array(NULL, 'bar'),
      ),
      'calls' => array(
        array('setContainer', $this->getCollection(array(
          $this->getServiceCall('service_container'),
        ))),
      ),
    );

    $services['wrong_factory'] = array(
      'class' => '\Drupal\service_container\ServiceContainer\ControllerInterface',
      'factory' => (object) array('I am not a factory, but I pretend to be.'),
    );

    $services['circular_dependency'] = array(
      'class' => '\Drupal\Tests\Component\DependencyInjection\MockService',
      'arguments' => $this->getCollection(array(
        $this->getServiceCall('circular_dependency'),
      )),
    );
    $services['synthetic'] = array(
      'synthetic' => TRUE,
    );
    // The file could have been named as a .php file. The reason it is a .data
    // file is that SimpleTest tries to load it. SimpleTest does not like such
    // fixtures and hence we use a neutral name like .data.
    $services['container_test_file_service_test'] = array(
      'class' => '\stdClass',
      'file' => __DIR__ . '/Fixture/container_test_file_service_test_service_function.data',
    );

    // Test multiple arguments.
    $args = array();
    for ($i = 0; $i < 12; $i++) {
      $services['service_test_instantiation_' . $i] = array(
        'class' => '\Drupal\Tests\Component\DependencyInjection\MockInstantiationService',
        // Also test a collection that does not need resolving.
        'arguments' => $this->getCollection($args, FALSE),
      );
      $args[] = 'arg_' . $i;
    }

    $services['service_parameter_not_exists'] = array(
      'class' => '\Drupal\Tests\Component\DependencyInjection\MockService',
      'arguments' => $this->getCollection(array(
        $this->getServiceCall('service.provider'),
        $this->getParameterCall('not_exists'),
      )),
    );
    $services['service_dependency_not_exists'] = array(
      'class' => '\Drupal\Tests\Component\DependencyInjection\MockService',
      'arguments' => $this->getCollection(array(
        $this->getServiceCall('service_not_exists'),
        $this->getParameterCall('some_config'),
      )),
    );

    $services['service_with_parameter_service'] = array(
      'class' => '\Drupal\Tests\Component\DependencyInjection\MockService',
      'arguments' => $this->getCollection(array(
        $this->getParameterCall('service_from_parameter'),
        // Also test deep collections that don't need resolving.
        $this->getCollection(array(
          1,
        ), FALSE),
      )),
    );

    // To ensure getAlternatives() finds something.
    $services['service_not_exists_similar'] = array(
      'synthetic' => TRUE,
    );

    // Test configurator.
    $services['configurator'] = array(
      'synthetic' => TRUE,
    );
    $services['configurable_service'] = array(
      'class' => '\Drupal\Tests\Component\DependencyInjection\MockService',
      'arguments' => array(),
      'configurator' => array(
        $this->getServiceCall('configurator'),
        'configureService'
      ),
    );
    $services['configurable_service_exception'] = array(
      'class' => '\Drupal\Tests\Component\DependencyInjection\MockService',
      'arguments' => array(),
      'configurator' => 'configurator_service_test_does_not_exist',
    );

    $aliases = array();
    $aliases['service.provider_alias'] = 'service.provider';
    $aliases['late.service_alias'] = 'late.service';

    return array(
      'aliases' => $aliases,
      'parameters' => $parameters,
      'services' => $services,
      'frozen' => TRUE,
      'machine_format' => $this->machineFormat,
    );
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
   * Helper function to return a service definition.
   */
  protected function getParameterCall($name) {
    return (object) array(
      'type' => 'parameter',
      'name' => $name,
    );
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

}

/**
 * Helper interface to test Container::get() with configurator.
 *
 * @group DependencyInjection
 */
interface MockConfiguratorInterface {

  /**
   * Configures a service.
   *
   * @param object $service
   *   The service to configure.
   */
  public function configureService($service);

}


/**
 * Helper class to test Container::get() method for varying number of parameters.
 *
 * @group DependencyInjection
 */
class MockInstantiationService {

  /**
   * @var mixed[]
   */
  protected $arguments;

  /**
   * Construct a mock instantiation service.
   */
  public function __construct() {
    $this->arguments = func_get_args();
  }

  /**
   * Return arguments injected into the service.
   *
   * @return mixed[]
   *   Return the passed arguments.
   */
  public function getArguments() {
    return $this->arguments;
  }

}


/**
 * Helper class to test Container::get() method.
 *
 * @group DependencyInjection
 */
class MockService {

  /**
   * @var ContainerInterface
   */
  protected $container;

  /**
   * @var object
   */
  protected $someOtherService;

  /**
   * @var string
   */
  protected $someParameter;

  /**
   * @var string
   */
  protected $someOtherParameter;

  /**
   * Constructs a MockService object.
   *
   * @param object $some_other_service
   *   (optional) Another injected service.
   * @param string $some_parameter
   *   (optional) An injected parameter.
   */
  public function __construct($some_other_service = NULL, $some_parameter = NULL) {
    if (is_array($some_other_service)) {
      $some_other_service = $some_other_service[0];
    }
    $this->someOtherService = $some_other_service;
    $this->someParameter = $some_parameter;
  }

  /**
   * Sets the container object.
   *
   * @param ContainerInterface $container
   *   The container to inject via setter injection.
   */
  public function setContainer(ContainerInterface $container) {
    $this->container = $container;
  }

  /**
   * Gets the container object.
   *
   * @return ContainerInterface
   *   The internally set container.
   */
  public function getContainer() {
    return $this->container;
  }

  /**
   * Gets the someOtherService object.
   *
   * @return object
   *   The injected service.
   */
  public function getSomeOtherService() {
    return $this->someOtherService;
  }

  /**
   * Gets the someParameter property.
   *
   * @return string
   *   The injected parameter.
   */
  public function getSomeParameter() {
    return $this->someParameter;
  }

  /**
   * Sets the someOtherParameter property.
   *
   * @param string $some_other_parameter
   *   The setter injected parameter.
   */
  public function setOtherConfigParameter($some_other_parameter) {
    $this->someOtherParameter = $some_other_parameter;
  }

  /**
   * Gets the someOtherParameter property.
   *
   * @return string
   *   The injected parameter.
   */
  public function getSomeOtherParameter() {
    return $this->someOtherParameter;
  }

  /**
   * Provides a factory method to get a service.
   *
   * @param string $class
   *   The class name of the class to instantiate
   * @param array $arguments
   *   (optional) Arguments to pass to the new class.
   *
   * @return object
   *   The instantiated service object.
   */
  public static function getFactoryMethod($class, $arguments = array()) {
    $r = new \ReflectionClass($class);
    $service = ($r->getConstructor() === NULL) ? $r->newInstance() : $r->newInstanceArgs($arguments);

    return $service;
  }

}
