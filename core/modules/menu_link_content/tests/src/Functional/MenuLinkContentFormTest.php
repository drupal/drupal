<?php

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
   * Modules to enable.
   *
   * @var array
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
  public function testMenuLinkContentFormLinkToAnyPage() {
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

    $this->drupalLogin($this->basicUser);

    $this->drupalGet('/admin/structure/menu/item/' . $menu_link->id() . '/edit');
    $this->assertSession()->statusCodeEquals(403);
  }

  /**
   * Tests the MenuLinkContentForm class.
   */
  public function testMenuLinkContentForm() {
    $this->drupalGet('admin/structure/menu/manage/admin/add');
    $element = $this->xpath('//select[@id = :id]/option[@selected]', [':id' => 'edit-menu-parent']);
    $this->assertNotEmpty($element, 'A default menu parent was found.');
    $this->assertEqual('admin:', $element[0]->getValue(), '<Administration> menu is the parent.');
    // Test that the field description is present.
    $this->assertRaw('The location this menu link points to.');

    $this->drupalPostForm(
      NULL,
      [
        'title[0][value]' => t('Front page'),
        'link[0][uri]' => '<front>',
      ],
      t('Save')
    );
    $this->assertText(t('The menu link has been saved.'));
  }

  /**
   * Tests validation for the MenuLinkContentForm class.
   */
  public function testMenuLinkContentFormValidation() {
    $this->drupalGet('admin/structure/menu/manage/admin/add');
    $this->drupalPostForm(
      NULL,
      [
        'title[0][value]' => t('Test page'),
        'link[0][uri]' => '<test>',
      ],
      t('Save')
    );
    $this->assertText(t('Manually entered paths should start with one of the following characters: / ? #'));
  }

}
