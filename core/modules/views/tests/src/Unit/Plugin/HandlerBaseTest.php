<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Unit\Plugin;

use Drupal\Core\Access\AccessException;
use Drupal\Core\Session\AccountInterface;
use Drupal\Tests\UnitTestCase;
use Drupal\views\Entity\View;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\Plugin\views\HandlerBase;
use Drupal\views\ViewExecutable;
use Drupal\views\ViewsData;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Drupal\views\Plugin\views\HandlerBase.
 */
#[CoversClass(HandlerBase::class)]
#[Group('Views')]
class HandlerBaseTest extends UnitTestCase {

  /**
   * Tests get entity type for field on base table.
   */
  public function testGetEntityTypeForFieldOnBaseTable(): void {
    $view = $this->createMock(View::class);
    $executable = $this->createStub(ViewExecutable::class);
    $executable->storage = $view;
    $viewsData = $this->createMock(ViewsData::class);

    $handler = new TestHandler([], 'test_handler', []);
    $handler->init($executable, $this->createStub(DisplayPluginBase::class));

    $view->expects($this->once())
      ->method('get')
      ->with('base_table')
      ->willReturn('test_entity_type_table');
    $viewsData->expects($this->once())
      ->method('get')
      ->with('test_entity_type_table')
      ->willReturn([
        'table' => ['entity type' => 'test_entity_type'],
      ]);
    $handler->setViewsData($viewsData);

    $this->assertEquals('test_entity_type', $handler->getEntityType());
  }

  /**
   * Tests get entity type for field with relationship.
   */
  public function testGetEntityTypeForFieldWithRelationship(): void {
    $display = $this->createMock(DisplayPluginBase::class);
    $viewsData = $this->createMock(ViewsData::class);
    $handler = new TestHandler([], 'test_handler', []);

    $options = ['relationship' => 'test_relationship'];
    $handler->init($this->createStub(ViewExecutable::class), $display, $options);

    $display->expects($this->atLeastOnce())
      ->method('getOption')
      ->with('relationships')
      ->willReturn([
        'test_relationship' => [
          'table' => 'test_entity_type_table',
          'id' => 'test_relationship',
          'field' => 'test_relationship',
        ],
      ]);

    $viewsData->expects($this->atLeastOnce())
      ->method('get')
      ->willReturnMap([
        [
          'test_entity_type_table',
          [
            'table' => ['entity type' => 'test_entity_type'],
            'test_relationship' => [
              'relationship' => [
                'base' => 'test_other_entity_type_table',
                'base field' => 'id',
              ],
            ],
          ],
        ],
        [
          'test_other_entity_type_table',
          ['table' => ['entity type' => 'test_other_entity_type']],
        ],
      ]);
    $handler->setViewsData($viewsData);

    $this->assertEquals('test_other_entity_type', $handler->getEntityType());
  }

  /**
   * Tests that access throws AccessException when access callback is set.
   */
  public function testAccessThrowsExceptionWithAccessCallback(): void {
    $definition = ['access callback' => 'test_callback'];
    $handler = new TestHandler([], 'test_handler', $definition);

    $this->expectException(AccessException::class);
    $this->expectExceptionMessage("The 'access callback' key in Views handler definitions is no longer supported in drupal:12.0.0. Use a custom access method on the handler instead.");
    $handler->access($this->createStub(AccountInterface::class));
  }

}

/**
 * Allow testing base handler implementation by extending the abstract class.
 */
class TestHandler extends HandlerBase {

}
