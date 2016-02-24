<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\DependencyInjection\Compiler\BackendCompilerPassTest.
 */

namespace Drupal\Tests\Core\DependencyInjection\Compiler;

use Drupal\Core\DependencyInjection\Compiler\BackendCompilerPass;
use Drupal\Tests\UnitTestCase;
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
  protected function setUp() {
    $this->backendPass = new BackendCompilerPass();
  }

  /**
   * Tests the process method.
   *
   * @param string $expected_class
   *   The expected used class.
   * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
   *   The container.
   *
   * @dataProvider providerTestProcess
   *
   * @covers ::process
   */
  public function testProcess($expected_class, ContainerBuilder $container) {
    $this->backendPass->process($container);

    $this->assertEquals($expected_class, get_class($container->get('service')));
  }

  /**
   * Provides test data for testProcess().
   *
   * @return array
   */
  public function providerTestProcess() {
    $data = array();
    // Add a container with no set default_backend.
    $prefix = __NAMESPACE__ . '\\ServiceClass';
    $service = (new Definition($prefix . 'Default'))->addTag('backend_overridable');
    $container = $this->getMysqlContainer($service);

    $data[] = array($prefix . 'Default', $container);

    // Set the default_backend so the mysql service should be used.
    $container = $this->getMysqlContainer($service);
    $container->setParameter('default_backend', 'mysql');
    $data[] = array($prefix . 'Mysql', $container);

    // Configure a manual alias for the service, so ensure that it is not
    // overridden by the default backend.
    $container = $this->getMysqlContainer($service);
    $container->setParameter('default_backend', 'mysql');
    $container->setDefinition('mariadb.service', new Definition($prefix . 'MariaDb'));
    $container->setAlias('service', new Alias('mariadb.service'));
    $data[] = array($prefix . 'MariaDb', $container);

    // Check the database driver is the default.
    $container = $this->getSqliteContainer($service);
    $data[] = array($prefix . 'Sqlite', $container);

    // Test the opt out.
    $container = $this->getSqliteContainer($service);
    $container->setParameter('default_backend', '');
    $data[] = array($prefix . 'Default', $container);

    return $data;
  }

  /**
   * Creates a container with a sqlite database service in it.
   *
   * This is necessary because the container clone does not clone the parameter
   * bag so the setParameter() call effects the parent container as well.
   *
   * @param $service
   * @return ContainerBuilder
   */
  protected function getSqliteContainer($service) {
    $container = new ContainerBuilder();
    $container->setDefinition('service', $service);
    $container->setDefinition('sqlite.service', new Definition(__NAMESPACE__ . '\\ServiceClassSqlite'));
    $mock = $this->getMockBuilder('Drupal\Core\Database\Driver\sqlite\Connection')->setMethods(NULL)->disableOriginalConstructor()->getMock();
    $container->set('database', $mock);
    return $container;
  }

  /**
   * Creates a container with a mysql database service definition in it.
   *
   * This is necessary because the container clone does not clone the parameter
   * bag so the setParameter() call effects the parent container as well.
   *
   * @param $service
   * @return ContainerBuilder
   */
  protected function getMysqlContainer($service) {
    $container = new ContainerBuilder();
    $container->setDefinition('service', $service);
    $container->setDefinition('mysql.service', new Definition(__NAMESPACE__ . '\\ServiceClassMysql'));
    return $container;
  }

}

class ServiceClassDefault {
}

class ServiceClassMysql extends ServiceClassDefault {
}

class ServiceClassMariaDb extends ServiceClassMysql {
}

class ServiceClassSqlite extends ServiceClassDefault {
}
