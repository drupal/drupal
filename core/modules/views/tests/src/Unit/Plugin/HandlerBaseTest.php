<?php

declare(strict_types=1);

namespace Drupal\Tests\views\Unit\Plugin;

use Drupal\Tests\UnitTestCase;
use Drupal\views\Plugin\views\HandlerBase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests Drupal\views\Plugin\views\HandlerBase.
 */
#[CoversClass(HandlerBase::class)]
#[Group('Views')]
class HandlerBaseTest extends UnitTestCase {

  use HandlerTestTrait;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->setupViewsData();
    $this->setupExecutableAndView();
    $this->setupDisplay();
  }

  /**
   * Tests get entity type for field on base table.
   */
  public function testGetEntityTypeForFieldOnBaseTable(): void {
    $handler = new TestHandler([], 'test_handler', []);
    $handler->init($this->executable, $this->display);

    $this->view->expects($this->any())
      ->method('get')
      ->with('base_table')
      ->willReturn('test_entity_type_table');
    $this->viewsData->expects($this->any())
      ->method('get')
      ->with('test_entity_type_table')
      ->willReturn([
        'table' => ['entity type' => 'test_entity_type'],
      ]);
    $handler->setViewsData($this->viewsData);

    $this->assertEquals('test_entity_type', $handler->getEntityType());
  }

  /**
   * Tests get entity type for field with relationship.
   */
  public function testGetEntityTypeForFieldWithRelationship(): void {
    $handler = new TestHandler([], 'test_handler', []);

    $options = ['relationship' => 'test_relationship'];
    $handler->init($this->executable, $this->display, $options);

    $this->display->expects($this->atLeastOnce())
      ->method('getOption')
      ->with('relationships')
      ->willReturn([
        'test_relationship' => [
          'table' => 'test_entity_type_table',
          'id' => 'test_relationship',
          'field' => 'test_relationship',
        ],
      ]);

    $this->view->expects($this->any())
      ->method('get')
      ->with('base_table')
      ->willReturn('test_entity_type_table');

    $this->viewsData->expects($this->any())
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
    $handler->setViewsData($this->viewsData);

    $this->assertEquals('test_other_entity_type', $handler->getEntityType());
  }

}

/**
 * Allow testing base handler implementation by extending the abstract class.
 */
class TestHandler extends HandlerBase {

}
