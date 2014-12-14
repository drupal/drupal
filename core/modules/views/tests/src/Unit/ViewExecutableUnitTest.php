<?php

/**
 * @file
 * Contains \Drupal\Tests\views\Unit\ViewExecutableUnitTest.
 */

namespace Drupal\Tests\views\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\views\Entity\View;
use Drupal\views\ViewExecutable;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * @coversDefaultClass \Drupal\views\ViewExecutable
 * @group views
 */
class ViewExecutableUnitTest extends UnitTestCase {

  /**
   * The mocked views data.
   *
   * @var \Drupal\views\ViewsData|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $viewsData;

  /**
   * The mocked user.
   *
   * @var \Drupal\Core\Session\AccountInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $user;

  /**
   * A mocked display collection.
   *
   * @var \Drupal\views\DisplayPluginCollection|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $displayCollection;

  /**
   * The mocked view executable.
   *
   * @var \Drupal\views\ViewExecutableFactory|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $viewExecutableFactory;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->viewsData = $this->getMockBuilder('Drupal\views\ViewsData')
      ->disableOriginalConstructor()
      ->getMock();
    $this->user = $this->getMock('Drupal\Core\Session\AccountInterface');

    $this->displayCollection = $this->getMockBuilder('Drupal\views\DisplayPluginCollection')
      ->disableOriginalConstructor()
      ->getMock();

    $this->viewExecutableFactory = $this->getMockBuilder('Drupal\views\ViewExecutableFactory')
      ->disableOriginalConstructor()
      ->getMock();

    $translation = $this->getStringTranslationStub();
    $container = new ContainerBuilder();
    $container->set('string_translation', $translation);
    $container->set('views.executable', $this->viewExecutableFactory);
    \Drupal::setContainer($container);
  }

  /**
   * Tests the buildThemeFunctions() method.
   */
  public function testBuildThemeFunctions() {
    /** @var \Drupal\views\ViewExecutable|\PHPUnit_Framework_MockObject_MockObject $view */
    /** @var \Drupal\views\Plugin\views\display\DisplayPluginBase|\PHPUnit_Framework_MockObject_MockObject $display */
    list($view, $display) = $this->setupBaseViewAndDisplay();

    unset($view->display_handler);
    $expected = array(
      'test_hook__test_view',
      'test_hook'
    );
    $this->assertEquals($expected, $view->buildThemeFunctions('test_hook'));

    $view->display_handler = $display;
    $expected = array(
      'test_hook__test_view__default',
      'test_hook__default',
      'test_hook__one',
      'test_hook__two',
      'test_hook__and_three',
      'test_hook__test_view',
      'test_hook'
    );
    $this->assertEquals($expected, $view->buildThemeFunctions('test_hook'));

    //Change the name of the display plugin and make sure that is in the array.
    $view->display_handler->display['display_plugin'] = 'default2';

    $expected = array(
      'test_hook__test_view__default',
      'test_hook__default',
      'test_hook__one',
      'test_hook__two',
      'test_hook__and_three',
      'test_hook__test_view__default2',
      'test_hook__default2',
      'test_hook__test_view',
      'test_hook'
    );
    $this->assertEquals($expected, $view->buildThemeFunctions('test_hook'));
  }

  /**
   * Tests the generateHandlerId method().
   *
   * @covers ::generateHandlerId()
   */
  public function testGenerateHandlerId() {
    // Test the generateHandlerId() method.
    $test_ids = ['test' => 'test', 'test_1' => 'test_1'];
    $this->assertEquals(ViewExecutable::generateHandlerId('new', $test_ids), 'new');
    $this->assertEquals(ViewExecutable::generateHandlerId('test', $test_ids), 'test_2');
  }

  /**
   * Tests the addHandler method().
   *
   * @covers ::addHandler()
   */
  public function testAddHandler() {
    /** @var \Drupal\views\ViewExecutable|\PHPUnit_Framework_MockObject_MockObject $view */
    /** @var \Drupal\views\Plugin\views\display\DisplayPluginBase|\PHPUnit_Framework_MockObject_MockObject $display */
    list($view, $display) = $this->setupBaseViewAndDisplay();

    $views_data = [];
    $views_data['test_field'] = [
      'field' => ['id' => 'standard'],
      'filter' => ['id' => 'standard'],
      'argument' => ['id' => 'standard'],
      'sort' => ['id' => 'standard'],
    ];

    $this->viewsData->expects($this->atLeastOnce())
      ->method('get')
      ->with('test_entity')
      ->willReturn($views_data);

    foreach (['field', 'filter', 'argument', 'sort'] as $handler_type) {
      $display->expects($this->atLeastOnce())
        ->method('setOption')
        ->with($this->callback(function($argument) {
          return $argument;
        }), ['test_field' => [
          'id' => 'test_field',
          'table' => 'test_entity',
          'field' => 'test_field',
          'plugin_id' => 'standard',
        ]]);
    }

    foreach (['field', 'filter', 'argument', 'sort'] as $handler_type) {
      $view->addHandler('default', $handler_type, 'test_entity', 'test_field');
    }
  }

  /**
   * Tests the addHandler method() with an entity field.
   *
   * @covers ::addHandler()
   */
  public function testAddHandlerWithEntityField() {
    /** @var \Drupal\views\ViewExecutable|\PHPUnit_Framework_MockObject_MockObject $view */
    /** @var \Drupal\views\Plugin\views\display\DisplayPluginBase|\PHPUnit_Framework_MockObject_MockObject $display */
    list($view, $display) = $this->setupBaseViewAndDisplay();

    $views_data = [];
    $views_data['table']['entity type'] = 'test_entity_type';
    $views_data['test_field'] = [
      'entity field' => 'test_field',
      'field' => ['id' => 'standard'],
      'filter' => ['id' => 'standard'],
      'argument' => ['id' => 'standard'],
      'sort' => ['id' => 'standard'],
    ];

    $this->viewsData->expects($this->atLeastOnce())
      ->method('get')
      ->with('test_entity')
      ->willReturn($views_data);

    foreach (['field', 'filter', 'argument', 'sort'] as $handler_type) {
      $display->expects($this->atLeastOnce())
        ->method('setOption')
        ->with($this->callback(function($argument) {
          return $argument;
        }), ['test_field' => [
          'id' => 'test_field',
          'table' => 'test_entity',
          'field' => 'test_field',
          'entity_type' => 'test_entity_type',
          'entity_field' => 'test_field',
          'plugin_id' => 'standard',
        ]]);
    }

    foreach (['field', 'filter', 'argument', 'sort'] as $handler_type) {
      $view->addHandler('default', $handler_type, 'test_entity', 'test_field');
    }
  }

  /**
   * Tests attachDisplays().
   *
   * @covers ::attachDisplays()
   */
  public function testAttachDisplays() {
    /** @var \Drupal\views\ViewExecutable|\PHPUnit_Framework_MockObject_MockObject $view */
    /** @var \Drupal\views\Plugin\views\display\DisplayPluginBase|\PHPUnit_Framework_MockObject_MockObject $display */
    list($view, $display) = $this->setupBaseViewAndDisplay();

    $display->expects($this->atLeastOnce())
      ->method('acceptAttachments')
      ->willReturn(TRUE);
    $display->expects($this->atLeastOnce())
      ->method('getAttachedDisplays')
      ->willReturn(['page_1']);

    $cloned_view = $this->getMockBuilder('Drupal\views\ViewExecutable')
      ->disableOriginalConstructor()
      ->getMock();
    $this->viewExecutableFactory->expects($this->atLeastOnce())
      ->method('get')
      ->willReturn($cloned_view);

    $page_display = $this->getMockBuilder('Drupal\views\Plugin\views\display\DisplayPluginBase')
      ->disableOriginalConstructor()
      ->getMock();

    $display_collection = $this->getMockBuilder('Drupal\views\DisplayPluginCollection')
      ->disableOriginalConstructor()
      ->getMock();

    $display_collection->expects($this->atLeastOnce())
      ->method('get')
      ->with('page_1')
      ->willReturn($page_display);
    $view->displayHandlers = $display_collection;

    // Setup the expectations.
    $page_display->expects($this->once())
      ->method('attachTo')
      ->with($cloned_view, 'default', $view->element);

    $view->attachDisplays();
  }

  /**
   * Setups a view executable and default display.
   *
   * @return array
   *   Returns the view executable and default display.
   */
  protected function setupBaseViewAndDisplay() {
    $config = array(
      'id' => 'test_view',
      'tag' => 'OnE, TWO, and three',
      'display' => [
        'default' => [
          'id' => 'default',
          'display_plugin' => 'default',
          'display_title' => 'Default',
        ],
      ],
    );

    $storage = new View($config, 'view');
    $view = new ViewExecutable($storage, $this->user, $this->viewsData);
    $display = $this->getMockBuilder('Drupal\views\Plugin\views\display\DisplayPluginBase')
      ->disableOriginalConstructor()
      ->getMock();
    $display->display = $config['display']['default'];

    $view->current_display = 'default';
    $view->display_handler = $display;
    $view->displayHandlers = $this->displayCollection;
    $view->displayHandlers->expects($this->any())
      ->method('get')
      ->with('default')
      ->willReturn($display);
    $view->displayHandlers->expects($this->any())
      ->method('has')
      ->with('default')
      ->willReturn(TRUE);

    return array($view, $display);
  }

}
