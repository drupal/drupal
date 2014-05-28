<?php

/**
 * @file
 * Contains \Drupal\locale\Tests\Menu\LocaleLocalTasksTest.
 */

namespace Drupal\locale\Tests\Menu;

use Drupal\Tests\Core\Menu\LocalTaskIntegrationTest;

/**
 * Tests existence of locale local tasks.
 *
 * @group Drupal
 * @group Locale
 */
class LocaleLocalTasksTest extends LocalTaskIntegrationTest {

  public static function getInfo() {
    return array(
      'name' => 'Locale local tasks test',
      'description' => 'Test locale local tasks.',
      'group' => 'Locale',
    );
  }

  public function setUp() {
    $this->directoryList = array(
      'locale' => 'core/modules/locale',
    );
    parent::setUp();
  }

  /**
   * Checks locale listing local tasks.
   *
   * @dataProvider getLocalePageRoutes
   */
  public function testLocalePageLocalTasks($route) {
    $tasks = array(
      0 => array('locale.translate_page', 'locale.translate_import', 'locale.translate_export','locale.settings'),
    );
    $this->assertLocalTasks($route, $tasks);
  }

  /**
   * Provides a list of routes to test.
   */
  public function getLocalePageRoutes() {
    return array(
      array('locale.translate_page'),
      array('locale.translate_import'),
      array('locale.translate_export'),
      array('locale.settings'),
    );
  }

}
