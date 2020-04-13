<?php

namespace Drupal\Tests\locale\Unit\Menu;

use Drupal\Tests\Core\Menu\LocalTaskIntegrationTestBase;

/**
 * Tests locale local tasks.
 *
 * @group locale
 */
class LocaleLocalTasksTest extends LocalTaskIntegrationTestBase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->directoryList = [
      'locale' => 'core/modules/locale',
    ];
    parent::setUp();
  }

  /**
   * Checks locale listing local tasks.
   *
   * @dataProvider getLocalePageRoutes
   */
  public function testLocalePageLocalTasks($route) {
    $tasks = [
      0 => ['locale.translate_page', 'locale.translate_import', 'locale.translate_export', 'locale.settings'],
    ];
    $this->assertLocalTasks($route, $tasks);
  }

  /**
   * Provides a list of routes to test.
   */
  public function getLocalePageRoutes() {
    return [
      ['locale.translate_page'],
      ['locale.translate_import'],
      ['locale.translate_export'],
      ['locale.settings'],
    ];
  }

}
