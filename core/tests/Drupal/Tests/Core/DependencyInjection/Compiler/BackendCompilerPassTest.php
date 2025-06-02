<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\DependencyInjection\Compiler;

use Drupal\Core\Database\Connection;
use Drupal\Core\DependencyInjection\Compiler\BackendCompilerPass;
use Drupal\Tests\UnitTestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

/**
 * @coversDefaultClass \Drupal\Core\DependencyInjection\Compiler\BackendCompilerPass
 * @group DependencyInjection
 */
class BackendCompilerPassTest extends UnitTestCase {

  /**
   * The tested backend compiler pass.
   *
   * @var \Drupal\Core\DependencyInjection\Compiler\BackendCompilerPass
   */
  protected $backendPass;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->backendPass = new BackendCompilerPass();
  }

  /**
   * Tests the process method.
   *
   * @covers ::process
   */
  public function testProcess(): void {
    // Add a container with no set default_backend.
    $prefix = __NAMESPACE__ . '\\ServiceClass';
    $service = (new Definition($prefix . 'Default'))->addTag('backend_overridable');
    $container = $this->getMysqlContainer($service);
    $this->backendPass->process($container);
    $this->assertEquals($prefix . 'Default', get_class($container->get('service')));

    // Set the default_backend so the mysql service should be used.
    $container = $this->getMysqlContainer($service);
    $container->setParameter('default_backend', 'mysql');
    $this->backendPass->process($container);
    $this->assertEquals($prefix . 'Mysql', get_class($container->get('service')));

    // Configure a manual alias for the service, so ensure that it is not
    // overridden by the default backend.
    $container = $this->getMysqlContainer($service);
    $container->setParameter('default_backend', 'mysql');
    $container->setDefinition('mariadb.service', new Definition($prefix . 'MariaDb'));
    $container->setAlias('service', new Alias('mariadb.service'));
    $this->backendPass->process($container);
    $this->assertEquals($prefix . 'MariaDb', get_class($container->get('service')));

    // Check the database driver is the default.
    $container = $this->getSqliteContainer($service);
    $this->backendPass->process($container);
    $this->assertEquals($prefix . 'Sqlite', get_class($container->get('service')));

    // Test the opt out.
    $container = $this->getSqliteContainer($service);
    $container->setParameter('default_backend', '');
    $this->backendPass->process($container);
    $this->assertEquals($prefix . 'Default', get_class($container->get('service')));

    // Set the mysql and the DriverTestMysql service, now the DriverTestMysql
    // service, as it is the driver override, should be used.
    $container = $this->getDriverTestMysqlContainer($service);
    $container->setDefinition('mysql.service', new Definition(__NAMESPACE__ . '\\ServiceClassMysql'));
    $container->setDefinition('DriverTestMysql.service', new Definition(__NAMESPACE__ . '\\ServiceClassDriverTestMysql'));
    $this->backendPass->process($container);
    $this->assertEquals($prefix . 'DriverTestMysql', get_class($container->get('service')));

    // Set the mysql service, now the mysql service, as it is the database_type
    // override, should be used.
    $container = $this->getDriverTestMysqlContainer($service);
    $container->setDefinition('mysql.service', new Definition(__NAMESPACE__ . '\\ServiceClassMysql'));
    $this->backendPass->process($container);
    $this->assertEquals($prefix . 'Mysql', get_class($container->get('service')));

    // Set the DriverTestMysql service, now the DriverTestMysql service, as it
    // is the driver override, should be used.
    $container = $this->getDriverTestMysqlContainer($service);
    $container->setDefinition('DriverTestMysql.service', new Definition(__NAMESPACE__ . '\\ServiceClassDriverTestMysql'));
    $this->backendPass->process($container);
    $this->assertEquals($prefix . 'DriverTestMysql', get_class($container->get('service')));

    // Verify that if the container has a default_backend parameter,
    // and there is a service named ".my-service", the right alias is created.
    $container = $this->getMockDriverContainerWithDefaultBackendParameterArgumentAndDotPrefixedService();
    $this->backendPass->process($container);

    // Verify that if the db service returns no driver, no invalid aliases are
    // created.
    $container = $this->getMockDriverContainerWithNullDriverBackend();
    $this->backendPass->process($container);
  }

  /**
   * Creates a container with a sqlite database service in it.
   *
   * This is necessary because the container clone does not clone the parameter
   * bag so the setParameter() call effects the parent container as well.
   *
   * @param \Symfony\Component\DependencyInjection\Definition $service
   *   The service definition.
   *
   * @return \Symfony\Component\DependencyInjection\ContainerBuilder
   *   The container with a sqlite database service in it.
   */
  protected function getSqliteContainer($service) {
    $container = new ContainerBuilder();
    $container->setDefinition('service', $service);
    $container->setDefinition('sqlite.service', new Definition(__NAMESPACE__ . '\\ServiceClassSqlite'));
    $mock = $this->getMockBuilder('Drupal\sqlite\Driver\Database\sqlite\Connection')->onlyMethods([])->disableOriginalConstructor()->getMock();
    $container->set('database', $mock);
    return $container;
  }

  /**
   * Creates a container with a mysql database service definition in it.
   *
   * This is necessary because the container clone does not clone the parameter
   * bag so the setParameter() call effects the parent container as well.
   *
   * @param \Symfony\Component\DependencyInjection\Definition $service
   *   The service definition.
   *
   * @return \Symfony\Component\DependencyInjection\ContainerBuilder
   *   The container with a mysql database service in it.
   */
  protected function getMysqlContainer($service) {
    $container = new ContainerBuilder();
    $container->setDefinition('service', $service);
    $container->setDefinition('mysql.service', new Definition(__NAMESPACE__ . '\\ServiceClassMysql'));
    return $container;
  }

  /**
   * Creates a container with a DriverTestMysql database mock definition in it.
   *
   * This is necessary because the container clone does not clone the parameter
   * bag so the setParameter() call effects the parent container as well.
   *
   * @param \Symfony\Component\DependencyInjection\Definition $service
   *   The service definition.
   *
   * @return \Symfony\Component\DependencyInjection\ContainerBuilder
   *   The container with a DriverTestMysql database service in it.
   */
  protected function getDriverTestMysqlContainer($service) {
    $container = new ContainerBuilder();
    $container->setDefinition('service', $service);
    $mock = $this->getMockBuilder('Drupal\driver_test\Driver\Database\DriverTestMysql\Connection')->onlyMethods([])->disableOriginalConstructor()->getMock();
    $container->set('database', $mock);
    return $container;
  }

  /**
   * Creates a container with a database mock definition in it.
   *
   * This mock won't declare a driver nor databaseType to ensure no invalid
   * aliases are set.
   *
   * @return \Symfony\Component\DependencyInjection\ContainerBuilder
   *   The container with a mock database service in it.
   */
  protected function getMockDriverContainerWithNullDriverBackend(): ContainerBuilder&MockObject {
    $container = $this->getMockBuilder(ContainerBuilder::class)->getMock();
    $mock = $this->getMockBuilder(Connection::class)->disableOriginalConstructor()->getMock();
    $mock->expects($this->once())
      ->method('driver')
      ->willReturn(NULL);
    $mock->expects($this->once())
      ->method('databaseType')
      ->willReturn(NULL);
    $container->expects($this->any())
      ->method('get')
      ->with('database')
      ->willReturn($mock);
    $container->expects($this->once())
      ->method('findTaggedServiceIds')
      ->willReturn(['fakeService' => ['class' => 'fakeServiceClass']]);
    $container->expects($this->never())
      ->method('hasDefinition')
      ->with('.fakeService')
      ->willReturn(TRUE);
    $container->expects($this->never())
      ->method('setAlias');
    return $container;
  }

  /**
   * Creates a container with a database mock definition in it.
   *
   * This mock container has a default_backend parameter and a dot-prefixed
   * service to verify the right aliases are set.
   *
   * @return \Symfony\Component\DependencyInjection\ContainerBuilder
   *   The container with a mock database service in it.
   */
  protected function getMockDriverContainerWithDefaultBackendParameterArgumentAndDotPrefixedService(): ContainerBuilder&MockObject {
    $container = $this->getMockBuilder(ContainerBuilder::class)->getMock();
    $container->expects($this->once())
      ->method('hasParameter')
      ->with('default_backend')
      ->willReturn(TRUE);
    $container->expects($this->once())
      ->method('getParameter')
      ->with('default_backend')
      ->willReturn('a_valid_default_backend');

    $mock = $this->getMockBuilder(Connection::class)->disableOriginalConstructor()->getMock();
    $mock->expects($this->never())
      ->method('driver');
    $mock->expects($this->never())
      ->method('databaseType');
    $container->expects($this->any())
      ->method('get')
      ->with('database')
      ->willReturn($mock);
    $container->expects($this->once())
      ->method('findTaggedServiceIds')
      ->willReturn(['fakeService' => ['class' => 'fakeServiceClass']]);
    $container->expects($this->once())
      ->method('hasDefinition')
      ->with('a_valid_default_backend.fakeService')
      ->willReturn(TRUE);
    $container->expects($this->once())
      ->method('setAlias')
      ->with('fakeService', new Alias('a_valid_default_backend.fakeService'));
    return $container;
  }

}

/**
 * A class used for testing the backend compiler passes.
 */
class ServiceClassDefault {
}

/**
 * A class used for testing the backend compiler passes.
 */
class ServiceClassMysql extends ServiceClassDefault {
}

/**
 * A class used for testing the backend compiler passes.
 */
class ServiceClassMariaDb extends ServiceClassMysql {
}

/**
 * A class used for testing the backend compiler passes.
 */
class ServiceClassSqlite extends ServiceClassDefault {
}

/**
 * A class used for testing the backend compiler passes.
 */
class ServiceClassDriverTestMysql extends ServiceClassDefault {
}
