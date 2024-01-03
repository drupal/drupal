<?php

declare(strict_types=1);

namespace Drupal\Tests\book\Unit\Menu;

use Drupal\Tests\Core\Menu\LocalTaskIntegrationTestBase;

/**
 * Tests existence of book local tasks.
 *
 * @group book
 */
class BookLocalTasksTest extends LocalTaskIntegrationTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->directoryList = [
      'book' => 'core/modules/book',
      'node' => 'core/modules/node',
    ];
    parent::setUp();
  }

  /**
   * Tests local task existence.
   *
   * @dataProvider getBookAdminRoutes
   */
  public function testBookAdminLocalTasks($route) {

    $this->assertLocalTasks($route, [
      0 => ['book.admin', 'book.settings'],
    ]);
  }

  /**
   * Provides a list of routes to test.
   */
  public function getBookAdminRoutes() {
    return [
      ['book.admin'],
      ['book.settings'],
    ];
  }

  /**
   * Tests local task existence.
   *
   * @dataProvider getBookNodeRoutes
   */
  public function testBookNodeLocalTasks($route) {
    $this->assertLocalTasks($route, [
      0 => ['entity.node.book_outline_form', 'entity.node.canonical', 'entity.node.edit_form', 'entity.node.delete_form', 'entity.node.version_history'],
    ]);
  }

  /**
   * Provides a list of routes to test.
   */
  public function getBookNodeRoutes() {
    return [
      ['entity.node.canonical'],
      ['entity.node.book_outline_form'],
    ];
  }

}
