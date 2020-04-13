<?php

namespace Drupal\Tests\views\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\views\Plugin\ViewsHandlerManager;

/**
 * Tests the ViewsHandlerManager class.
 *
 * @group views
 *
 * @coversDefaultClass \Drupal\views\Plugin\ViewsHandlerManager
 */
class ViewsHandlerManagerTest extends UnitTestCase {

  /**
   * @var \Drupal\views\Plugin\ViewsHandlerManager
   */
  protected $handlerManager;

  /**
   * @var \Drupal\Core\Extension\ModuleHandlerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $moduleHandler;

  /**
   * The mocked views data.
   *
   * @var \Drupal\views\ViewsData|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $viewsData;

  /**
   * The mocked factory.
   *
   * @var \Drupal\Component\Plugin\Factory\FactoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $factory;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->viewsData = $this->getMockBuilder('Drupal\views\ViewsData')
      ->disableOriginalConstructor()
      ->getMock();
    $cache_backend = $this->createMock('Drupal\Core\Cache\CacheBackendInterface');
    $this->moduleHandler = $this->createMock('Drupal\Core\Extension\ModuleHandlerInterface');
    $this->handlerManager = new ViewsHandlerManager('test', new \ArrayObject([]), $this->viewsData, $cache_backend, $this->moduleHandler);
  }

  /**
   * Setups of the plugin factory.
   */
  protected function setupMockedFactory() {
    $this->factory = $this->createMock('Drupal\Component\Plugin\Factory\FactoryInterface');

    $reflection = new \ReflectionClass($this->handlerManager);
    $property = $reflection->getProperty('factory');
    $property->setAccessible(TRUE);
    $property->setValue($this->handlerManager, $this->factory);
  }

  /**
   * Tests that hook_views_plugins_TYPE_alter() is invoked for a handler type.
   *
   * @covers ::__construct
   * @covers ::getDefinitions
   */
  public function testAlterHookInvocation() {
    $this->moduleHandler->expects($this->once())
      ->method('alter')
      ->with('views_plugins_test', []);

    $this->handlerManager->getDefinitions();
  }

  /**
   * Tests getHandler() and its base information propagation.
   */
  public function testGetHandlerBaseInformationPropagation() {
    $this->setupMockedFactory();

    $item = [];
    $item['table'] = 'test_table';
    $item['field'] = 'test_field';

    $views_data = [];
    $views_data['test_field']['test']['id'] = 'test_id';
    $views_data['test_field']['test']['more_information'] = 'test_id';
    $views_data['test_field']['group'] = 'test_group';
    $views_data['test_field']['title'] = 'test title';
    $views_data['test_field']['real field'] = 'test real field';
    $views_data['test_field']['real table'] = 'test real table';
    $views_data['test_field']['entity field'] = 'test entity field';

    $this->viewsData->expects($this->once())
      ->method('get')
      ->with('test_table')
      ->willReturn($views_data);

    $expected_definition = [
      'id' => 'test_id',
      'more_information' => 'test_id',
      'group' => 'test_group',
      'title' => 'test title',
      'real field' => 'test real field',
      'real table' => 'test real table',
      'entity field' => 'test entity field',
    ];
    $plugin = $this->createMock('Drupal\views\Plugin\views\ViewsHandlerInterface');
    $this->factory->expects($this->once())
      ->method('createInstance')
      ->with('test_id', $expected_definition)
      ->willReturn($plugin);

    $result = $this->handlerManager->getHandler($item);
    $this->assertSame($plugin, $result);
  }

  /**
   * Tests getHandler() with an override.
   */
  public function testGetHandlerOverride() {
    $this->setupMockedFactory();

    $item = [];
    $item['table'] = 'test_table';
    $item['field'] = 'test_field';

    $views_data = [];
    $views_data['test_field']['test']['id'] = 'test_id';

    $this->viewsData->expects($this->once())
      ->method('get')
      ->with('test_table')
      ->willReturn($views_data);

    $plugin = $this->createMock('Drupal\views\Plugin\views\ViewsHandlerInterface');
    $this->factory->expects($this->once())
      ->method('createInstance')
      ->with('test_override')
      ->willReturn($plugin);

    $result = $this->handlerManager->getHandler($item, 'test_override');
    $this->assertSame($plugin, $result);
  }

  /**
   * Tests getHandler() without an override.
   */
  public function testGetHandlerNoOverride() {
    $this->setupMockedFactory();

    $item = [];
    $item['table'] = 'test_table';
    $item['field'] = 'test_field';

    $views_data = [];
    $views_data['test_field']['test']['id'] = 'test_id';

    $this->viewsData->expects($this->once())
      ->method('get')
      ->with('test_table')
      ->willReturn($views_data);

    $plugin = $this->createMock('Drupal\views\Plugin\views\ViewsHandlerInterface');
    $this->factory->expects($this->once())
      ->method('createInstance')
      ->with('test_id')
      ->willReturn($plugin);

    $result = $this->handlerManager->getHandler($item);
    $this->assertSame($plugin, $result);
  }

}
