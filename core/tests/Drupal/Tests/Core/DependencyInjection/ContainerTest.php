<?php

/**
 * @file
 * Contains \Drupal\Tests\Core\DependencyInjection\ContainerTest.
 */

namespace Drupal\Tests\Core\DependencyInjection;

use Drupal\Core\DependencyInjection\Container;
use Drupal\Tests\UnitTestCase;

/**
 * Tests the dependency injection container overrides of Drupal.
 *
 * @see \Drupal\Core\DependencyInjection\Container
 */
class ContainerTest extends UnitTestCase {

  /**
   * The tested container.
   *
   * @var \Drupal\Core\DependencyInjection\Container
   */
  public $container;

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Dependency injection container',
      'description' => 'Tests the dependency injection container overrides of Drupal.',
      'group' => 'System'
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->container = new Container();
  }

  /**
   * Tests the get method.
   *
   * @see \Drupal\Core\DependencyInjection\Container::get()
   */
  public function testGet() {
    $service = new \stdClass();
    $service->key = 'value';

    $this->container->set('test_service', $service);
    $result = $this->container->get('test_service');
    $this->assertSame($service, $result);
    $this->assertEquals('test_service', $result->_serviceId);
  }

}
