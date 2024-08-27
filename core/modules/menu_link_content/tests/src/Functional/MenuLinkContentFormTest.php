<?php

declare(strict_types=1);

namespace Drupal\Tests\menu_link_content\Functional;

use Drupal\menu_link_content\Entity\MenuLinkContent;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the menu link content UI.
 *
 * @group Menu
 */
class MenuLinkContentFormTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'menu_link_content',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * User with 'administer menu' and 'link to any page' permission.
   *
   * @var \Drupal\user\Entity\User
   */

  protected $adminUser;

  /**
   * User with only 'administer menu' permission.
   *
   * @var \Drupal\user\Entity\User
   */

  protected $basicUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    $this->adminUser = $this->drupalCreateUser([
      'administer menu',
      'link to any page',
    ]);
    $this->basicUser = $this->drupalCreateUser(['administer menu']);
    $this->drupalLogin($this->adminUser);
  }

  /**
   * Tests the 'link to any page' permission for a restricted page.
   */
  public function testMenuLinkContentFormLinkToAnyPage(): void {
    $menu_link = MenuLinkContent::create([
      'title' => 'Menu link test',
      'provider' => 'menu_link_content',
      'menu_name' => 'admin',
      'link' => ['uri' => 'internal:/user/login'],
    ]);
    $menu_link->save();

    // The user should be able to edit a menu link to the page, even though
    // the user cannot access the page itself.
    $this->drupalGet('/admin/structure/menu/item/' . $menu_link->id() . '/edit');
    $this->assertSession()->statusCodeEquals(200);

    // Test that other menus are available when editing existing menu link.
    $this->assertSession()->optionExists('edit-menu-parent', 'main:');

    $this->drupalLogin($this->basicUser);

    $this->drupalGet('/admin/structure/menu/item/' . $menu_link->id() . '/edit');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests the MenuLinkContentForm class.
   */
  public function testMenuLinkContentForm(): void {
    $this->drupalGet('admin/structure/menu/manage/admin/add');
    // Test that other menus are not available when creating a new menu link.
    $this->assertSession()->optionNotExists('edit-menu-parent', 'main:');
    $option = $this->assertSession()->optionExists('edit-menu-parent', 'admin:');
    $this->assertTrue($option->isSelected());
    // Test that the field description is present.
    $this->assertSession()->pageTextContains('The location this menu link points to.');

    $this->submitForm([
      'title[0][value]' => 'Front page',
      'link[0][uri]' => '<front>',
    ], 'Save');
    $this->assertSession()->pageTextContains('The menu link has been saved.');
  }

  /**
   * Tests validation for the MenuLinkContentForm class.
   */
  public function testMenuLinkContentFormValidation(): void {
    $this->drupalGet('admin/structure/menu/manage/admin/add');
    $this->submitForm([
      'title[0][value]' => 'Test page',
      'link[0][uri]' => '<test>',
    ], 'Save');
    $this->assertSession()->pageTextContains('Manually entered paths should start with one of the following characters: / ? #');
  }

  /**
   * Tests the operations links alter related functional for menu_link_content.
   */
  public function testMenuLinkContentOperationsLink(): void {
    \Drupal::service('module_installer')->install(['menu_operations_link_test']);
    $menu_link = MenuLinkContent::create([
      'title' => 'Menu link test',
      'provider' => 'menu_link_content',
      'menu_name' => 'main',
      'link' => ['uri' => 'internal:/user/login'],
    ]);
    $menu_link->save();

    // When we are on the listing page, we should be able to see the altered
    // values by alter hook in the operations link menu.
    $this->drupalGet('/admin/structure/menu/manage/main');
    $this->assertSession()->linkExists('Altered Edit Title');
    $this->assertSession()->linkExists('Custom Home');
  }

}
