<?php

/**
 * @file
 * Contains \Drupal\menu_link_content\Tests\MenuLinkContentUITest.
 */

namespace Drupal\menu_link_content\Tests;

use Drupal\content_translation\Tests\ContentTranslationUITest;
use Drupal\menu_link_content\Entity\MenuLinkContent;

/**
 * Tests the menu link content UI.
 *
 * @group Menu
 */
class MenuLinkContentUITest extends ContentTranslationUITest {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array(
    'language',
    'content_translation',
    'menu_link_content',
    'menu_ui',
  );

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->entityTypeId = 'menu_link_content';
    $this->bundle = 'menu_link_content';
    parent::setUp();
  }

  /**
   * {@inheritdoc}
   */
  protected function getTranslatorPermissions() {
    return array_merge(parent::getTranslatorPermissions(), array('administer menu'));
  }

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return array_merge(parent::getAdministratorPermissions(), array('administer themes', 'view the administration theme'));
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity($values, $langcode, $bundle_name = NULL) {
    $values['menu_name'] = 'tools';
    $values['link']['uri'] = 'admin/structure/menu';
    $values['title'] = 'Test title';

    return parent::createEntity($values, $langcode, $bundle_name);
  }

  /**
   * Ensure that a translate link can be found on the menu edit form.
   */
  public function testTranslationLinkOnMenuEditForm() {
    $this->drupalGet('admin/structure/menu/manage/tools');
    $this->assertNoLink(t('Translate'));

    $menu_link_content = MenuLinkContent::create(['menu_name' => 'tools', 'link' => ['uri' => 'admin/structure/menu']]);
    $menu_link_content->save();
    $this->drupalGet('admin/structure/menu/manage/tools');
    $this->assertLink(t('Translate'));
  }

  /**
   * Tests that translation page inherits admin status of edit page.
   */
  function testTranslationLinkTheme() {
    $this->drupalLogin($this->administrator);
    $entityId = $this->createEntity(array(), 'en');

    // Set up Seven as the admin theme to test.
    $this->container->get('theme_handler')->install(array('seven'));
    $edit = array();
    $edit['admin_theme'] = 'seven';
    $this->drupalPostForm('admin/appearance', $edit, t('Save configuration'));
    $this->drupalGet('admin/structure/menu/item/' . $entityId . '/edit');
    $this->assertRaw('core/themes/seven/css/base/elements.css', 'Edit uses admin theme.');
    $this->drupalGet('admin/structure/menu/item/' . $entityId . '/edit/translations');
    $this->assertRaw('core/themes/seven/css/base/elements.css', 'Translation uses admin theme as well.');
  }

}
