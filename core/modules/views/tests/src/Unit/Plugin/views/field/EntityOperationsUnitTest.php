<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Unit\Plugin\views\field;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\Tests\views\Traits\ViewsLoggerTestTrait;
use Drupal\views\Plugin\views\field\EntityOperations;
use Drupal\views\ResultRow;

/**
 * @coversDefaultClass \Drupal\views\Plugin\views\field\EntityOperations
 * @group Views
 */
class EntityOperationsUnitTest extends UnitTestCase {

  use ViewsLoggerTestTrait;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityTypeManager;

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface|\PHPUnit\Framework\MockObject\MockObject
   */
  protected $entityRepository;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface|\PHPUnit\Framework\MockObject\MockObject
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
  protected function setUp(): void {
    parent::setUp();

    $this->entityTypeManager = $this->createMock(EntityTypeManagerInterface::class);
    $this->entityRepository = $this->createMock(EntityRepositoryInterface::class);
    $this->languageManager = $this->createMock('\Drupal\Core\Language\LanguageManagerInterface');

    $configuration = ['entity_type' => 'foo', 'entity field' => 'bar'];
    $plugin_id = $this->randomMachineName();
    $plugin_definition = [
      'title' => $this->randomMachineName(),
    ];
    $this->plugin = new EntityOperations($configuration, $plugin_id, $plugin_definition, $this->entityTypeManager, $this->languageManager, $this->entityRepository);

    $redirect_service = $this->createMock('Drupal\Core\Routing\RedirectDestinationInterface');
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
  public function testUsesGroupBy(): void {
    $this->assertFalse($this->plugin->usesGroupBy());
  }

  /**
   * @covers ::defineOptions
   */
  public function testDefineOptions(): void {
    $options = $this->plugin->defineOptions();
    $this->assertIsArray($options);
    $this->assertArrayHasKey('destination', $options);
  }

  /**
   * @covers ::render
   */
  public function testRenderWithDestination(): void {
    $entity_type_id = $this->randomMachineName();
    $entity = $this->getMockBuilder('\Drupal\user\Entity\Role')
      ->disableOriginalConstructor()
      ->getMock();
    $entity->expects($this->any())
      ->method('getEntityTypeId')
      ->willReturn($entity_type_id);

    $operations = [
      'foo' => [
        'title' => $this->randomMachineName(),
      ],
    ];
    $list_builder = $this->createMock('\Drupal\Core\Entity\EntityListBuilderInterface');
    $list_builder->expects($this->once())
      ->method('getOperations')
      ->with($entity)
      ->willReturn($operations);

    $this->entityTypeManager->expects($this->once())
      ->method('getListBuilder')
      ->with($entity_type_id)
      ->willReturn($list_builder);

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
  public function testRenderWithoutDestination(): void {
    $entity_type_id = $this->randomMachineName();
    $entity = $this->getMockBuilder('\Drupal\user\Entity\Role')
      ->disableOriginalConstructor()
      ->getMock();
    $entity->expects($this->any())
      ->method('getEntityTypeId')
      ->willReturn($entity_type_id);

    $operations = [
      'foo' => [
        'title' => $this->randomMachineName(),
      ],
    ];
    $list_builder = $this->createMock('\Drupal\Core\Entity\EntityListBuilderInterface');
    $list_builder->expects($this->once())
      ->method('getOperations')
      ->with($entity)
      ->willReturn($operations);

    $this->entityTypeManager->expects($this->once())
      ->method('getListBuilder')
      ->with($entity_type_id)
      ->willReturn($list_builder);

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

  /**
   * @covers ::render
   */
  public function testRenderWithoutEntity(): void {
    $this->setUpMockLoggerWithMissingEntity();

    $entity = NULL;

    $result = new ResultRow();
    $result->_entity = $entity;

    $expected_build = '';
    $build = $this->plugin->render($result);
    $this->assertSame($expected_build, $build);
  }

}
