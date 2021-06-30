<?php

namespace Drupal\Tests\menu_ui\Functional;

use Drupal\system\Entity\Menu;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests menu settings when creating and editing content types.
 *
 * @group menu_ui
 */
class MenuUiNodeTypeTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'menu_ui',
    'node',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * Asserts that the available menu names are sorted alphabetically.
   */
  private function assertMenuNamesAreSorted(): void {
    // The available menus should be sorted alphabetically.
    $labels = $this->getSession()
      ->getPage()
      ->findAll('css', 'label[for^="edit-menu-options-"]');

    $menu_names = $sorted_menu_names = [];
    foreach ($labels as $label) {
      $menu_names[] = $label->getText();
    }
    foreach (Menu::loadMultiple() as $menu) {
      $sorted_menu_names[] = $menu->label();
    }
    sort($sorted_menu_names);
    $this->assertSame($menu_names, $sorted_menu_names);
  }

  /**
   * Tests node type-specific settings for Menu UI.
   */
  public function testContentTypeMenuSettings(): void {
    $account = $this->drupalCreateUser(['administer content types']);
    $this->drupalLogin($account);

    $this->drupalGet('/admin/structure/types/add');
    $this->assertMenuNamesAreSorted();

    $node_type = $this->drupalCreateContentType()->id();
    $this->drupalGet("/admin/structure/types/manage/$node_type");
    $this->assertMenuNamesAreSorted();
  }

}
