<?php

namespace Drupal\Tests\menu_link_content\Functional;

use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\system\Entity\Menu;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the menu link content delete UI.
 *
 * @group Menu
 */
class MenuLinkContentDeleteFormTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = ['menu_link_content'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $web_user = $this->drupalCreateUser(['administer menu']);
    $this->drupalLogin($web_user);
  }

  /**
   * Tests the MenuLinkContentDeleteForm class.
   */
  public function testMenuLinkContentDeleteForm() {
    // Add new menu item.
    $this->drupalGet('admin/structure/menu/manage/admin/add');
    $this->submitForm([
      'title[0][value]' => t('Front page'),
      'link[0][uri]' => '<front>',
    ], 'Save');
    $this->assertSession()->pageTextContains('The menu link has been saved.');

    $menu_link = MenuLinkContent::load(1);
    $this->drupalGet($menu_link->toUrl('delete-form'));
    $this->assertSession()->pageTextContains("Are you sure you want to delete the custom menu link {$menu_link->label()}?");
    $this->assertSession()->linkExists('Cancel');
    // Make sure cancel link points to link edit
    $this->assertSession()->linkByHrefExists($menu_link->toUrl('edit-form')->toString());

    \Drupal::service('module_installer')->install(['menu_ui']);

    // Make sure cancel URL points to menu_ui route now.
    $this->drupalGet($menu_link->toUrl('delete-form'));
    $menu = Menu::load($menu_link->getMenuName());
    $this->assertSession()->linkByHrefExists($menu->toUrl('edit-form')->toString());
    $this->submitForm([], 'Delete');
    $this->assertSession()->pageTextContains("The menu link {$menu_link->label()} has been deleted.");
  }

}
