<?php

declare(strict_types=1);

namespace Drupal\Tests\Core\Entity;

use Drupal\Core\Entity\Display\EntityViewDisplayInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Entity\EntityViewBuilder
 * @group Entity
 */
class EntityViewBuilderTest extends UnitTestCase {

  const string ENTITY_TYPE_ID = 'test_entity_type';

  /**
   * The entity view builder under test.
   *
   * @var \Drupal\Core\Entity\EntityViewBuilder
   */
  protected EntityViewBuilder $viewBuilder;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->viewBuilder = new class() extends EntityViewBuilder {

      public function __construct() {
        $this->entityTypeId = EntityViewBuilderTest::ENTITY_TYPE_ID;
      }

    };
  }

  /**
   * Tests build components using a mocked Iterator.
   */
  public function testBuildComponents(): void {
    $field_name = $this->randomMachineName();
    $bundle = $this->randomMachineName();
    $entity_id = mt_rand(20, 30);
    $field_item_list = $this->createStub(FieldItemListInterface::class);
    $item = new \stdClass();
    $this->setupMockIterator($field_item_list, [$item]);
    $entity = $this->createConfiguredStub(FieldableEntityInterface::class, [
      'bundle' => $bundle,
      'hasField' => TRUE,
      'get' => $field_item_list,
    ]);
    $formatter_result = [
      $entity_id => ['#' . $this->randomMachineName() => $this->randomString()],
    ];
    $display = $this->createConfiguredStub(EntityViewDisplayInterface::class, [
      'getComponents' => [$field_name => []],
      'buildMultiple' => $formatter_result,
    ]);
    $entities = [$entity_id => $entity];
    $displays = [$bundle => $display];
    $build = [$entity_id => []];
    $view_mode = $this->randomMachineName();
    // Assert the hook is invoked.
    $module_handler = $this->createMock(ModuleHandlerInterface::class);
    $module_handler->expects($this->once())
      ->method('invokeAll')
      ->with('entity_prepare_view', [self::ENTITY_TYPE_ID, $entities, $displays, $view_mode]);
    $this->viewBuilder->setModuleHandler($module_handler);
    $this->viewBuilder->buildComponents($build, $entities, $displays, $view_mode);
    $this->assertSame([], $item->_attributes);
    $this->assertSame($formatter_result, $build);
  }

}
