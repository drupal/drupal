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

    $this->assertInstanceOf($expected_class, $container->get('service'));
  }

  /**
   * Provides test data for testProcess().
   *
   * @return array
   */
  public function providerTestProcess() {
    $data = array();
    // Add a container with no set default_backend.
    $container = new ContainerBuilder();
    $prefix = '\\' . __NAMESPACE__ . '\\';
    $container->setDefinition('service', (new Definition($prefix . 'ServiceClassDefault'))->addTag('backend_overridable'));
    $container->setDefinition('mysql.service', new Definition($prefix . 'ServiceClassMysql'));

    $data[] = array($prefix . 'ServiceClassDefault', $container);

    // Set the default_backend so the mysql service should be used.
    $container = clone $container;
    $container->setParameter('default_backend', 'mysql');
    $data[] = array($prefix . 'ServiceClassMysql', $container);

    // Configure a manual alias for the service, so ensure that it is not
    // overridden by the default backend.
    $container = clone $container;
    $container->setDefinition('mariadb.service', new Definition($prefix . 'ServiceClassMariaDb'));
    $container->setAlias('service', new Alias('mariadb.service'));
    $data[] = array($prefix . 'ServiceClassMariaDb', $container);

    return $data;
  }

}

class ServiceClassDefault {
}

class ServiceClassMysql extends ServiceClassDefault {
}

class ServiceClassMariaDb extends ServiceClassMysql {
}
