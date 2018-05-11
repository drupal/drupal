<?php

namespace Drupal\Tests\views\Unit\Plugin\views\field;

use Drupal\Tests\UnitTestCase;
use Drupal\views\Plugin\views\field\EntityOperations;
use Drupal\views\ResultRow;

/**
 * @coversDefaultClass \Drupal\views\Plugin\views\field\EntityOperations
 * @group Views
 */
class EntityOperationsUnitTest extends UnitTestCase {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityManager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $languageManager;

  /**
   * The plugin under test.
   *
   * @var \Drupal\views\Plugin\views\field\EntityOperations
   */
  protected $plugin;

  /**
   * {@inheritdoc}
   *
   * @covers ::__construct
   */
  protected function setUp() {
    $this->entityManager = $this->getMock('\Drupal\Core\Entity\EntityManagerInterface');
    $this->languageManager = $this->getMock('\Drupal\Core\Language\LanguageManagerInterface');

    $configuration = [];
    $plugin_id = $this->randomMachineName();
    $plugin_definition = [
      'title' => $this->randomMachineName(),
    ];
    $this->plugin = new EntityOperations($configuration, $plugin_id, $plugin_definition, $this->entityManager, $this->languageManager);

    $redirect_service = $this->getMock('Drupal\Core\Routing\RedirectDestinationInterface');
    $redirect_service->expects($this->any())
      ->method('getAsArray')
      ->willReturn(['destination' => 'foobar']);
    $this->plugin->setRedirectDestination($redirect_service);

    $view = $this->getMockBuilder('\Drupal\views\ViewExecutable')
      ->disableOriginalConstructor()
      ->getMock();
    $display = $this->getMockBuilder('\Drupal\views\Plugin\views\display\DisplayPluginBase')
      ->disableOriginalConstructor()
      ->getMockForAbstractClass();
    $view->display_handler = $display;
    $this->plugin->init($view, $display);
  }

  /**
   * @covers ::usesGroupBy
   */
  public function testUsesGroupBy() {
    $this->assertFalse($this->plugin->usesGroupBy());
  }

  /**
   * @covers ::defineOptions
   */
  public function testDefineOptions() {
    $options = $this->plugin->defineOptions();
    $this->assertInternalType('array', $options);
    $this->assertArrayHasKey('destination', $options);
  }

  /**
   * @covers ::render
   */
  public function testRenderWithDestination() {
    $entity_type_id = $this->randomMachineName();
    $entity = $this->getMockBuilder('\Drupal\user\Entity\Role')
      ->disableOriginalConstructor()
      ->getMock();
    $entity->expects($this->any())
      ->method('getEntityTypeId')
      ->will($this->returnValue($entity_type_id));

    $operations = [
      'foo' => [
        'title' => $this->randomMachineName(),
      ],
    ];
    $list_builder = $this->getMock('\Drupal\Core\Entity\EntityListBuilderInterface');
    $list_builder->expects($this->once())
      ->method('getOperations')
      ->with($entity)
      ->will($this->returnValue($operations));

    $this->entityManager->expects($this->once())
      ->method('getListBuilder')
      ->with($entity_type_id)
      ->will($this->returnValue($list_builder));

    $this->plugin->options['destination'] = TRUE;

    $result = new ResultRow();
    $result->_entity = $entity;

    $expected_build = [
      '#type' => 'operations',
      '#links' => $operations,
    ];
    $expected_build['#links']['foo']['query'] = ['destination' => 'foobar'];
    $build = $this->plugin->render($result);
    $this->assertSame($expected_build, $build);
  }

  /**
   * @covers ::render
   */
  public function testRenderWithoutDestination() {
    $entity_type_id = $this->randomMachineName();
    $entity = $this->getMockBuilder('\Drupal\user\Entity\Role')
      ->disableOriginalConstructor()
      ->getMock();
    $entity->expects($this->any())
      ->method('getEntityTypeId')
      ->will($this->returnValue($entity_type_id));

    $operations = [
      'foo' => [
        'title' => $this->randomMachineName(),
      ],
    ];
    $list_builder = $this->getMock('\Drupal\Core\Entity\EntityListBuilderInterface');
    $list_builder->expects($this->once())
      ->method('getOperations')
      ->with($entity)
      ->will($this->returnValue($operations));

    $this->entityManager->expects($this->once())
      ->method('getListBuilder')
      ->with($entity_type_id)
      ->will($this->returnValue($list_builder));

    $this->plugin->options['destination'] = FALSE;

    $result = new ResultRow();
    $result->_entity = $entity;

    $expected_build = [
      '#type' => 'operations',
      '#links' => $operations,
    ];
    $build = $this->plugin->render($result);
    $this->assertSame($expected_build, $build);
  }

}
