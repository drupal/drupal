<?php

/**
 * @file
 * Contains \Drupal\Tests\book\Unit\Menu\BookLocalTasksTest.
 */

namespace Drupal\Tests\book\Unit\Menu;

use Drupal\Tests\Core\Menu\LocalTaskIntegrationTest;

/**
 * Tests existence of book local tasks.
 *
 * @group book
 */
class BookLocalTasksTest extends LocalTaskIntegrationTest {

  protected function setUp() {
    $this->directoryList = array(
      'book' => 'core/modules/book',
      'node' => 'core/modules/node',
    );
    parent::setUp();
  }

  /**
   * Tests local task existence.
   *
   * @dataProvider getBookAdminRoutes
   */
  public function testBookAdminLocalTasks($route) {

    $this->assertLocalTasks($route, array(
      0 => array('book.admin', 'book.settings'),
    ));
  }

  /**
   * Provides a list of routes to test.
   */
  public function getBookAdminRoutes() {
    return array(
      array('book.admin'),
      array('book.settings'),
    );
  }

  /**
   * Tests local task existence.
   *
   * @dataProvider getBookNodeRoutes
   */
  public function testBookNodeLocalTasks($route) {
    $this->assertLocalTasks($route, array(
      0 => array('entity.node.book_outline_form', 'entity.node.canonical', 'entity.node.edit_form', 'entity.node.delete_form', 'entity.node.version_history',),
    ));
  }

  /**
   * Provides a list of routes to test.
   */
  public function getBookNodeRoutes() {
    return array(
      array('entity.node.canonical'),
      array('entity.node.book_outline_form'),
    );
  }

}
