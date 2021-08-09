<?php

namespace Drupal\Tests\menu_link_content\Functional;

use Drupal\Tests\content_translation\Functional\ContentTranslationUITestBase;
use Drupal\menu_link_content\Entity\MenuLinkContent;

/**
 * Tests the menu link content translation UI.
 *
 * @group Menu
 */
class MenuLinkContentTranslationUITest extends ContentTranslationUITestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultCacheContexts = ['languages:language_interface', 'session', 'theme', 'url.path', 'url.query_args', 'user.permissions', 'user.roles:authenticated'];

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'language',
    'content_translation',
    'menu_link_content',
    'menu_ui',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'classy';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
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
    $this->assertSession()->linkNotExists('Translate');

    $menu_link_content = MenuLinkContent::create([
      'menu_name' => 'tools',
      'link' => ['uri' => 'internal:/admin/structure/menu'],
      'title' => 'Link test',
    ]);
    $menu_link_content->save();
    $this->drupalGet('admin/structure/menu/manage/tools');
    $this->assertSession()->linkExists('Translate');
  }

  /**
   * Tests that translation page inherits admin status of edit page.
   */
  public function testTranslationLinkTheme() {
    $this->drupalLogin($this->administrator);
    $entityId = $this->createEntity([], 'en');

    // Set up Seven as the admin theme to test.
    $this->container->get('theme_installer')->install(['seven']);
    $edit = [];
    $edit['admin_theme'] = 'seven';
    $this->drupalGet('admin/appearance');
    $this->submitForm($edit, 'Save configuration');
    // Check that edit uses the admin theme.
    $this->drupalGet('admin/structure/menu/item/' . $entityId . '/edit');
    $this->assertRaw('core/themes/seven/css/base/elements.css');
    // Check that translation uses admin theme as well.
    $this->drupalGet('admin/structure/menu/item/' . $entityId . '/edit/translations');
    $this->assertRaw('core/themes/seven/css/base/elements.css');
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
        $url = $entity->toUrl('edit-form', $options);
        $this->drupalGet($url);
        $this->assertSession()->pageTextContains("{$entity->getTranslation($langcode)->label()} [{$languages[$langcode]->getName()} translation]");
      }
    }
  }

}
