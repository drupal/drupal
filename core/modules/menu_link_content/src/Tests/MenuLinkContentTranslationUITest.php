<?php

namespace Drupal\menu_link_content\Tests;

use Drupal\content_translation\Tests\ContentTranslationUITestBase;
use Drupal\menu_link_content\Entity\MenuLinkContent;

/**
 * Tests the menu link content translation UI.
 *
 * @group Menu
 */
class MenuLinkContentTranslationUITest extends ContentTranslationUITestBase {

  /**
   * {inheritdoc}
   */
  protected $defaultCacheContexts = ['languages:language_interface', 'session', 'theme', 'url.path', 'url.query_args', 'user.permissions', 'user.roles:authenticated'];

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'language',
    'content_translation',
    'menu_link_content',
    'menu_ui',
  ];

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
    return array_merge(parent::getTranslatorPermissions(), ['administer menu']);
  }

  /**
   * {@inheritdoc}
   */
  protected function getAdministratorPermissions() {
    return array_merge(parent::getAdministratorPermissions(), ['administer themes', 'view the administration theme']);
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity($values, $langcode, $bundle_name = NULL) {
    $values['menu_name'] = 'tools';
    $values['link']['uri'] = 'internal:/admin/structure/menu';
    $values['title'] = 'Test title';

    return parent::createEntity($values, $langcode, $bundle_name);
  }

  /**
   * Ensure that a translate link can be found on the menu edit form.
   */
  public function testTranslationLinkOnMenuEditForm() {
    $this->drupalGet('admin/structure/menu/manage/tools');
    $this->assertNoLink(t('Translate'));

    $menu_link_content = MenuLinkContent::create(['menu_name' => 'tools', 'link' => ['uri' => 'internal:/admin/structure/menu']]);
    $menu_link_content->save();
    $this->drupalGet('admin/structure/menu/manage/tools');
    $this->assertLink(t('Translate'));
  }

  /**
   * Tests that translation page inherits admin status of edit page.
   */
  public function testTranslationLinkTheme() {
    $this->drupalLogin($this->administrator);
    $entityId = $this->createEntity([], 'en');

    // Set up Seven as the admin theme to test.
    $this->container->get('theme_handler')->install(['seven']);
    $edit = [];
    $edit['admin_theme'] = 'seven';
    $this->drupalPostForm('admin/appearance', $edit, t('Save configuration'));
    $this->drupalGet('admin/structure/menu/item/' . $entityId . '/edit');
    $this->assertRaw('core/themes/seven/css/base/elements.css', 'Edit uses admin theme.');
    $this->drupalGet('admin/structure/menu/item/' . $entityId . '/edit/translations');
    $this->assertRaw('core/themes/seven/css/base/elements.css', 'Translation uses admin theme as well.');
  }

  /**
   * {@inheritdoc}
   */
  protected function doTestTranslationEdit() {
    $storage = $this->container->get('entity_type.manager')
      ->getStorage($this->entityTypeId);
    $storage->resetCache([$this->entityId]);
    $entity = $storage->load($this->entityId);
    $languages = $this->container->get('language_manager')->getLanguages();

    foreach ($this->langcodes as $langcode) {
      // We only want to test the title for non-english translations.
      if ($langcode != 'en') {
        $options = ['language' => $languages[$langcode]];
        $url = $entity->urlInfo('edit-form', $options);
        $this->drupalGet($url);

        $title = t('@title [%language translation]', [
          '@title' => $entity->getTranslation($langcode)->label(),
          '%language' => $languages[$langcode]->getName(),
        ]);
        $this->assertRaw($title);
      }
    }
  }

}
