<?php

namespace Drupal\Tests\Core\Controller;

use Drupal\Tests\UnitTestCase;

/**
 * Tests that the base controller class.
 *
 * @group Controller
 */
class ControllerBaseTest extends UnitTestCase {

  /**
   * The tested controller base class.
   *
   * @var \Drupal\Core\Controller\ControllerBase|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $controllerBase;

  protected function setUp() {
    $this->controllerBase = $this->getMockForAbstractClass('Drupal\Core\Controller\ControllerBase');
  }

  /**
   * Tests the config method.
   */
  public function testGetConfig() {
    $config_factory = $this->getConfigFactoryStub([
      'config_name' => [
        'key' => 'value',
      ],
      'config_name2' => [
        'key2' => 'value2',
      ],
    ]);

    $container = $this->createMock('Symfony\Component\DependencyInjection\ContainerInterface');
    $container->expects($this->once())
      ->method('get')
      ->with('config.factory')
      ->will($this->returnValue($config_factory));
    \Drupal::setContainer($container);

    $config_method = new \ReflectionMethod('Drupal\Core\Controller\ControllerBase', 'config');
    $config_method->setAccessible(TRUE);

    // Call config twice to ensure that the container is just called once.
    $config = $config_method->invoke($this->controllerBase, 'config_name');
    $this->assertEquals('value', $config->get('key'));

    $config = $config_method->invoke($this->controllerBase, 'config_name2');
    $this->assertEquals('value2', $config->get('key2'));
  }

}
