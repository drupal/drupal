<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Menu\LocalActionTest.
 */

namespace Drupal\system\Tests\Menu;

use Drupal\Core\Url;
use Drupal\simpletest\WebTestBase;

/**
 * Tests local actions derived from router and added/altered via hooks.
 *
 * @group Menu
 */
class LocalActionTest extends WebTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = array('menu_test');

  /**
   * Tests appearance of local actions.
   */
  public function testLocalAction() {
    $this->drupalGet('menu-test-local-action');
    // Ensure that both menu and route based actions are shown.
    $this->assertLocalAction([
      [Url::fromRoute('menu_test.local_action4'), 'My dynamic-title action'],
      [Url::fromRoute('menu_test.local_action2'), 'My hook_menu action'],
      [Url::fromRoute('menu_test.local_action3'), 'My YAML discovery action'],
      [Url::fromRoute('menu_test.local_action5'), 'Title override'],
    ]);
  }

  /**
   * Asserts local actions in the page output.
   *
   * @param array $actions
   *   A list of expected action link titles, keyed by the hrefs.
   */
  protected function assertLocalAction(array $actions) {
    $elements = $this->xpath('//a[contains(@class, :class)]', array(
      ':class' => 'button-action',
    ));
    $index = 0;
    foreach ($actions as $action) {
      /** @var \Drupal\Core\Url $url */
      list($url, $title) = $action;
      $this->assertEqual((string) $elements[$index], $title);
      $this->assertEqual($elements[$index]['href'], $url->toString());
      $index++;
    }
  }

}
