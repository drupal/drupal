<?php

declare(strict_types=1);

namespace Drupal\Tests\menu_ui\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\system\Entity\Menu;

/**
 * Tests that uninstalling menu does not remove custom menus.
 *
 * @group menu_ui
 */
class MenuUninstallTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['menu_ui'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Tests Menu uninstall.
   */
  public function testMenuUninstall(): void {
    \Drupal::service('module_installer')->uninstall(['menu_ui']);

    \Drupal::entityTypeManager()->getStorage('menu')->resetCache(['admin']);

    $this->assertNotEmpty(Menu::load('admin'), 'The \'admin\' menu still exists after uninstalling Menu UI module.');
  }

}
