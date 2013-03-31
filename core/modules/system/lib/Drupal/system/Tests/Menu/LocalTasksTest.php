<?php

/**
 * @file
 * Contains Drupal\system\Tests\Menu\LocalTasksTest.
 */

namespace Drupal\system\Tests\Menu;

/**
 * Tests local tasks derived from router and added/altered via hooks.
 */
class LocalTasksTest extends MenuTestBase {

  public static $modules = array('menu_test');

  public static function getInfo() {
    return array(
      'name' => 'Local tasks',
      'description' => 'Tests local tasks derived from router and added/altered via hooks.',
      'group' => 'Menu',
    );
  }

  /**
   * Tests appearance of local tasks.
   *
   * @see menu_test_menu()
   * @see menu_test_menu_local_tasks()
   * @see menu_test_menu_local_tasks_alter()
   */
  function testLocalTasks() {
    // Verify that there is no local tasks markup if none are defined in the
    // router and no module adds any dynamically.
    $this->drupalGet('menu-test/tasks/empty');
    $this->assertNoRaw('tabs');
    $this->drupalGet('menu-test/tasks/default');
    $this->assertNoRaw('tabs');

    // Verify that local tasks appear as defined in the router.
    $this->drupalGet('menu-test/tasks/tasks');
    $this->assertLocalTasks(array(
      // MENU_DEFAULT_LOCAL_TASK is expected to get a default weight of -10
      // (without having to define it manually), so it should appear first,
      // despite that its label is "View".
      'menu-test/tasks/tasks',
      'menu-test/tasks/tasks/edit',
      'menu-test/tasks/tasks/settings',
    ));

    // Enable addition of tasks in menu_test_menu_local_tasks().
    config('menu_test.settings')->set('tasks.add', TRUE)->save();

    // Verify that the added tasks appear even if there are no tasks normally.
    $this->drupalGet('menu-test/tasks/empty');
    $this->assertLocalTasks(array(
      'task/foo',
      'task/bar',
    ));

    // Verify that the default local task appears before the added tasks.
    $this->drupalGet('menu-test/tasks/default');
    $this->assertLocalTasks(array(
      'menu-test/tasks/default',
      'task/foo',
      'task/bar',
    ));

    // Verify that the added tasks appear within normal tasks.
    $this->drupalGet('menu-test/tasks/tasks');
    $this->assertLocalTasks(array(
      'menu-test/tasks/tasks',
      // The Edit task defines no weight, which is expected to sort as 0.
      'menu-test/tasks/tasks/edit',
      'task/foo',
      'task/bar',
      'menu-test/tasks/tasks/settings',
    ));

    // Enable manipulation of tasks in menu_test_menu_local_tasks_alter().
    config('menu_test.settings')->set('tasks.alter', TRUE)->save();

    // Verify that the added tasks appear even if there are no tasks normally.
    $this->drupalGet('menu-test/tasks/empty');
    $this->assertLocalTasks(array(
      'task/bar',
      'task/foo',
    ));
    $this->assertNoText('Show it');
    $this->assertText('Advanced settings');

    // Verify that the default local task appears before the added tasks.
    $this->drupalGet('menu-test/tasks/default');
    $this->assertLocalTasks(array(
      'menu-test/tasks/default',
      'task/bar',
      'task/foo',
    ));
    $this->assertText('Show it');
    $this->assertText('Advanced settings');

    // Verify that the added tasks appear within normal tasks.
    $this->drupalGet('menu-test/tasks/tasks');
    $this->assertLocalTasks(array(
      'menu-test/tasks/tasks',
      'menu-test/tasks/tasks/edit',
      'task/bar',
      'menu-test/tasks/tasks/settings',
      'task/foo',
    ));
    $this->assertText('Show it');
    $this->assertText('Advanced settings');
  }

}
