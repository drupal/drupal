<?php

/**
 * @file
 * Contains \Drupal\book\Tests\Menu\BookLocalTasksTest.
 */

namespace Drupal\book\Tests\Menu;

use Drupal\Tests\Core\Menu\LocalTaskIntegrationTest;

/**
 * Tests existence of book local tasks.
 *
 * @group Drupal
 * @group Book
 */
class BookLocalTasksTest extends LocalTaskIntegrationTest {

  public static function getInfo() {
    return array(
      'name' => 'Book local tasks test',
      'description' => 'Test existence of book local tasks.',
      'group' => 'Book',
    );
  }

  public function setUp() {
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
      0 => array('book.outline', 'node.view', 'node.page_edit', 'node.delete_confirm', 'node.revision_overview',),
    ));
  }

  /**
   * Provides a list of routes to test.
   */
  public function getBookNodeRoutes() {
    return array(
      array('node.view'),
      array('book.outline'),
    );
  }

}
