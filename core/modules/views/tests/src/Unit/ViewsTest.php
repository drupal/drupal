<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Unit;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\views\Views;
use Drupal\views\Entity\View;
use Drupal\views\ViewExecutableFactory;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * @coversDefaultClass \Drupal\views\Views
 * @group views
 */
class ViewsTest extends UnitTestCase {

  /**
   * The test container.
   *
   * @var \Drupal\Core\DependencyInjection\ContainerBuilder
   */
  protected $container;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->container = new ContainerBuilder();
    $user = $this->createMock('Drupal\Core\Session\AccountInterface');
    $request_stack = new RequestStack();
    $request_stack->push(new Request());
    $views_data = $this->getMockBuilder('Drupal\views\ViewsData')
      ->disableOriginalConstructor()
      ->getMock();
    $route_provider = $this->createMock('Drupal\Core\Routing\RouteProviderInterface');
    $display_plugin_manager = $this->getMockBuilder('\Drupal\views\Plugin\ViewsPluginManager')
      ->disableOriginalConstructor()
      ->getMock();
    $this->container->set('views.executable', new ViewExecutableFactory($user, $request_stack, $views_data, $route_provider, $display_plugin_manager));

    \Drupal::setContainer($this->container);
  }

  /**
   * Tests the getView() method.
   *
   * @covers ::getView
   */
  public function testGetView(): void {
    $view = new View(['id' => 'test_view'], 'view');

    $view_storage = $this->getMockBuilder('Drupal\Core\Config\Entity\ConfigEntityStorage')
      ->disableOriginalConstructor()
      ->getMock();
    $view_storage->expects($this->once())
      ->method('load')
      ->with('test_view')
      ->willReturn($view);

    $entity_type_manager = $this->createMock('Drupal\Core\Entity\EntityTypeManagerInterface');
    $entity_type_manager->expects($this->once())
      ->method('getStorage')
      ->with('view')
      ->willReturn($view_storage);
    $this->container->set('entity_type.manager', $entity_type_manager);

    $executable = Views::getView('test_view');
    $this->assertInstanceOf('Drupal\views\ViewExecutable', $executable);
    $this->assertEquals($view->id(), $executable->storage->id());
    $this->assertEquals(spl_object_hash($view), spl_object_hash($executable->storage));
  }

  /**
   * Tests the getView() method against a non-existent view.
   *
   * @covers ::getView
   */
  public function testGetNonExistentView(): void {
    $entity_type_manager = $this->prophesize(EntityTypeManagerInterface::class);
    $storage = $this->prophesize(EntityStorageInterface::class);
    $storage->load('test_view_non_existent')->willReturn(NULL);
    $entity_type_manager->getStorage('view')->willReturn($storage->reveal());
    $this->container->set('entity_type.manager', $entity_type_manager->reveal());
    $executable_does_not_exist = Views::getView('test_view_non_existent');
    $this->assertNull($executable_does_not_exist);
  }

  /**
   * @covers ::getApplicableViews
   *
   * @dataProvider providerTestGetApplicableViews
   */
  public function testGetApplicableViews($applicable_type, $expected): void {
    $view_1 = new View([
      'id' => 'test_view_1',
      'display' => [
        'default' => [
          'display_plugin' => 'default',
          'display_options' => [],
        ],
        'type_a' => [
          'display_plugin' => 'type_a',
          'display_options' => [],
        ],
      ],
    ], 'view');
    $view_2 = new View([
      'id' => 'test_view_2',
      'display' => [
        'default' => [
          'display_plugin' => 'default',
          'display_options' => [],
        ],
        'type_b' => [
          'display_plugin' => 'type_b',
          'display_options' => [
            'enabled' => TRUE,
          ],
        ],
        'type_b_2' => [
          'display_plugin' => 'type_b',
          'display_options' => [
            'enabled' => FALSE,
          ],
        ],
      ],
    ], 'view');
    $view_3 = new View([
      'id' => 'test_view_3',
      'display' => [
        'default' => [
          'display_plugin' => 'default',
          'display_options' => [],
        ],
        // Test with Type A but a disabled display.
        'type_a' => [
          'display_plugin' => 'type_a',
          'display_options' => [
            'enabled' => FALSE,
          ],
        ],
        // Type D intentionally doesn't exist.
        'type_d' => [
          'display_plugin' => 'type_d',
          'display_options' => [],
        ],
      ],
    ], 'view');

    $query = $this->createMock('Drupal\Core\Entity\Query\QueryInterface');
    $query->expects($this->exactly(2))
      ->method('condition')
      ->willReturnSelf();
    $query->expects($this->once())
      ->method('execute')
      ->willReturn(['test_view_1', 'test_view_2', 'test_view_3']);

    $view_storage = $this->getMockBuilder('Drupal\Core\Config\Entity\ConfigEntityStorage')
      ->disableOriginalConstructor()
      ->getMock();
    $view_storage->expects($this->once())
      ->method('getQuery')
      ->willReturn($query);

    $view_storage->expects($this->once())
      ->method('loadMultiple')
      ->with(['test_view_1', 'test_view_2', 'test_view_3'])
      ->willReturn([
        'test_view_1' => $view_1,
        'test_view_2' => $view_2,
        'test_view_3' => $view_3,
      ]);

    $entity_type_manager = $this->createMock(EntityTypeManagerInterface::class);
    $entity_type_manager->expects($this->exactly(2))
      ->method('getStorage')
      ->with('view')
      ->willReturn($view_storage);
    $this->container->set('entity_type.manager', $entity_type_manager);

    $definitions = [
      'type_a' => [
        'type_a' => TRUE,
        'type_b' => FALSE,
      ],
      'type_b' => [
        'type_a' => FALSE,
        'type_b' => TRUE,
      ],
    ];

    $display_manager = $this->createMock('Drupal\Component\Plugin\PluginManagerInterface');
    $display_manager->expects($this->once())
      ->method('getDefinitions')
      ->willReturn($definitions);
    $this->container->set('plugin.manager.views.display', $display_manager);

    $result = Views::getApplicableViews($applicable_type);
    $this->assertEquals($expected, $result);
  }

  /**
   * Data provider for testGetApplicableViews.
   *
   * @return array
   */
  public static function providerTestGetApplicableViews() {
    return [
      ['type_a', [['test_view_1', 'type_a']]],
      ['type_b', [['test_view_2', 'type_b']]],
      ['type_c', []],
    ];
  }

}
