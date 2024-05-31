<?php

declare(strict_types=1);

namespace Drupal\FunctionalTests\Update;

use Drupal\Core\Url;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests that update.php is accessible even if there are unstable modules.
 *
 * @group Update
 */
class UpdateReducedThemeRegistryTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['update_test_broken_theme_hook'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests that the update page can be accessed.
   */
  public function testUpdatePageWithBrokenThemeHook(): void {
    require_once $this->root . '/core/includes/update.inc';
    $this->writeSettings([
      'settings' => [
        'update_free_access' => (object) [
          'value' => TRUE,
          'required' => TRUE,
        ],
      ],
    ]);
    $this->drupalGet(Url::fromRoute('system.db_update'));
    $this->assertSession()->statusCodeEquals(200);
  }

}
