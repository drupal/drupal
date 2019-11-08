<?php

namespace Drupal\Tests\system\Functional\Update;

use Drupal\FunctionalTests\Update\UpdatePathTestBase;

/**
 * Tests system.theme:admin is updated.
 *
 * @group system
 * @group legacy
 */
class AdminThemeUpdateTest extends UpdatePathTestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setDatabaseDumpFiles() {
    $this->databaseDumpFiles = [
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.6.0.bare.testing.php.gz',
      __DIR__ . '/../../../../../system/tests/fixtures/update/drupal-8.admin_theme_0.php',
    ];
  }

  /**
   * Tests that system.theme:admin is updated as expected.
   */
  public function testUpdateHookN() {
    $this->assertSame('0', $this->config('system.theme')->get('admin'));
    $this->runUpdates();
    $this->assertSame('', $this->config('system.theme')->get('admin'));
  }

}
