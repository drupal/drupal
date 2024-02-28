<?php

declare(strict_types=1);

namespace Drupal\Tests\shortcut\Functional;

use Drupal\Tests\content_translation\Functional\ContentTranslationUITestBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Language\Language;

/**
 * Tests the shortcut translation UI.
 *
 * @group Shortcut
 */
class ShortcutTranslationUITest extends ContentTranslationUITestBase {

  /**
   * {@inheritdoc}
   */
  protected $defaultCacheContexts = ['languages:language_interface', 'session', 'theme', 'user', 'url.path', 'url.query_args', 'url.site'];

  /**
   * Modules to enable.
   *
   * @var array
   */
  protected static $modules = [
    'language',
    'content_translation',
    'link',
    'shortcut',
    'toolbar',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->entityTypeId = 'shortcut';
    $this->bundle = 'default';
    parent::setUp();
    $this->doSetup();
  }

  /**
   * {@inheritdoc}
   */
  protected function getTranslatorPermissions() {
    return array_merge(parent::getTranslatorPermissions(), ['access shortcuts', 'administer shortcuts', 'access toolbar']);
  }

  /**
   * {@inheritdoc}
   */
  protected function createEntity($values, $langcode, $bundle_name = NULL) {
    $values['link']['uri'] = 'internal:/user';
    return parent::createEntity($values, $langcode, $bundle_name);
  }

  /**
   * {@inheritdoc}
   */
  protected function getNewEntityValues($langcode) {
    return ['title' => [['value' => $this->randomMachineName()]]] + parent::getNewEntityValues($langcode);
  }

  protected function doTestBasicTranslation() {
    parent::doTestBasicTranslation();

    $storage = $this->container->get('entity_type.manager')
      ->getStorage($this->entityTypeId);
    $storage->resetCache([$this->entityId]);
    $entity = $storage->load($this->entityId);
    foreach ($this->langcodes as $langcode) {
      if ($entity->hasTranslation($langcode)) {
        $language = new Language(['id' => $langcode]);
        // Request the front page in this language and assert that the right
        // translation shows up in the shortcut list with the right path.
        $this->drupalGet('<front>', ['language' => $language]);
        $expected_path = \Drupal::urlGenerator()->generateFromRoute('user.page', [], ['language' => $language]);
        $label = $entity->getTranslation($langcode)->label();
        $this->assertSession()->elementExists('xpath', "//nav[contains(@class, 'toolbar-lining')]/ul[@class='toolbar-menu']/li/a[contains(@href, '{$expected_path}') and normalize-space(text())='{$label}']");
      }
    }
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

  /**
   * Tests the basic translation workflow.
   */
  protected function doTestTranslationChanged() {
    $storage = $this->container->get('entity_type.manager')
      ->getStorage($this->entityTypeId);
    $storage->resetCache([$this->entityId]);
    $entity = $storage->load($this->entityId);

    $this->assertFalse(
      $entity instanceof EntityChangedInterface,
      "$this->entityTypeId is not implementing EntityChangedInterface."
    );
  }

}
