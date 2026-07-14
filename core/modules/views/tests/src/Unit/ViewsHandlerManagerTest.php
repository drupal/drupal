<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Unit;

use Drupal\Component\Plugin\Factory\FactoryInterface;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\views\Plugin\views\ViewsHandlerInterface;
use Drupal\views\Plugin\ViewsHandlerManager;
use Drupal\views\ViewsData;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Tests the ViewsHandlerManager class.
 */
#[CoversClass(ViewsHandlerManager::class)]
#[Group('views')]
class ViewsHandlerManagerTest extends UnitTestCase {

  /**
   * The views handler manager.
   *
   * @var \Drupal\views\Plugin\ViewsHandlerManager
   */
  protected $handlerManager;

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The mocked views data.
   *
   * @var \Drupal\views\ViewsData
   */
  protected $viewsData;

  /**
   * The mocked factory.
   */
  protected FactoryInterface&MockObject $factory;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->viewsData = $this->createStub(ViewsData::class);
    $cache_backend = $this->createStub(CacheBackendInterface::class);
    $this->moduleHandler = $this->createStub(ModuleHandlerInterface::class);
    $this->handlerManager = new ViewsHandlerManager('test', new \ArrayObject([]), $this->viewsData, $cache_backend, $this->moduleHandler);
  }

  /**
   * Setups of the plugin factory.
   */
  protected function setupMockedFactory(): void {
    $this->factory = $this->createMock('Drupal\Component\Plugin\Factory\FactoryInterface');

    $reflection = new \ReflectionClass($this->handlerManager);
    $property = $reflection->getProperty('factory');
    $property->setValue($this->handlerManager, $this->factory);
  }

  /**
   * Reinitializes the module handler as a mock object.
   */
  protected function setUpMockModuleHandler(): void {
    $this->moduleHandler = $this->createMock(ModuleHandlerInterface::class);
    $reflection = new \ReflectionProperty($this->handlerManager, 'moduleHandler');
    $reflection->setValue($this->handlerManager, $this->moduleHandler);
  }

  /**
   * Reinitializes the views data as a mock object.
   */
  protected function setUpMockViewsData(): void {
    $this->viewsData = $this->createMock(ViewsData::class);
    $reflection = new \ReflectionProperty($this->handlerManager, 'viewsData');
    $reflection->setValue($this->handlerManager, $this->viewsData);
  }

  /**
   * Tests that hook_views_plugins_TYPE_alter() is invoked for a handler type.
   *
   * @legacy-covers ::__construct
   * @legacy-covers ::getDefinitions
   */
  public function testAlterHookInvocation(): void {
    $this->setUpMockModuleHandler();

    $this->moduleHandler->expects($this->once())
      ->method('alter')
      ->with('views_plugins_test', []);

    $this->handlerManager->getDefinitions();
  }

  /**
   * Tests getHandler() and its base information propagation.
   */
  public function testGetHandlerBaseInformationPropagation(): void {
    $this->setupMockedFactory();
    $this->setUpMockViewsData();

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
    $plugin = $this->createStub(ViewsHandlerInterface::class);
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
  public function testGetHandlerOverride(): void {
    $this->setupMockedFactory();
    $this->setUpMockViewsData();

    $item = [];
    $item['table'] = 'test_table';
    $item['field'] = 'test_field';

    $views_data = [];
    $views_data['test_field']['test']['id'] = 'test_id';

    $this->viewsData->expects($this->once())
      ->method('get')
      ->with('test_table')
      ->willReturn($views_data);

    $plugin = $this->createStub(ViewsHandlerInterface::class);
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
  public function testGetHandlerNoOverride(): void {
    $this->setupMockedFactory();
    $this->setUpMockViewsData();

    $item = [];
    $item['table'] = 'test_table';
    $item['field'] = 'test_field';

    $views_data = [];
    $views_data['test_field']['test']['id'] = 'test_id';

    $this->viewsData->expects($this->once())
      ->method('get')
      ->with('test_table')
      ->willReturn($views_data);

    $plugin = $this->createStub(ViewsHandlerInterface::class);
    $this->factory->expects($this->once())
      ->method('createInstance')
      ->with('test_id')
      ->willReturn($plugin);

    $result = $this->handlerManager->getHandler($item);
    $this->assertSame($plugin, $result);
  }

}
