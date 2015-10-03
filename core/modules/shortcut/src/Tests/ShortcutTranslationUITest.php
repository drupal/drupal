<?php

/**
 * @file
 * Contains \Drupal\shortcut\Tests\ShortcutTranslationUITest.
 */

namespace Drupal\shortcut\Tests;

use Drupal\content_translation\Tests\ContentTranslationUITestBase;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Language\Language;

/**
 * Tests the shortcut translation UI.
 *
 * @group Shortcut
 */
class ShortcutTranslationUITest extends ContentTranslationUITestBase {

  /**
   * {inheritdoc}
   */
  protected $defaultCacheContexts = ['languages:language_interface', 'session', 'theme', 'user', 'url.path', 'url.query_args', 'url.site'];

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array(
    'language',
    'content_translation',
    'link',
    'shortcut',
    'toolbar'
  );

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    $this->entityTypeId = 'shortcut';
    $this->bundle = 'default';
    parent::setUp();
  }

  /**
   * {@inheritdoc}
   */
  protected function getTranslatorPermissions() {
    return array_merge(parent::getTranslatorPermissions(), array('access shortcuts', 'administer shortcuts', 'access toolbar'));
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
    return array('title' => array(array('value' => $this->randomMachineName()))) + parent::getNewEntityValues($langcode);
  }

  protected function doTestBasicTranslation() {
    parent::doTestBasicTranslation();

    $entity = entity_load($this->entityTypeId, $this->entityId, TRUE);
    foreach ($this->langcodes as $langcode) {
      if ($entity->hasTranslation($langcode)) {
        $language = new Language(array('id' => $langcode));
        // Request the front page in this language and assert that the right
        // translation shows up in the shortcut list with the right path.
        $this->drupalGet('<front>', array('language' => $language));
        $expected_path = \Drupal::urlGenerator()->generateFromRoute('user.page', array(), array('language' => $language));
        $label = $entity->getTranslation($langcode)->label();
        $elements = $this->xpath('//nav[contains(@class, "toolbar-lining")]/ul[@class="toolbar-menu"]/li/a[contains(@href, :href) and normalize-space(text())=:label]', array(':href' => $expected_path, ':label' => $label));
        $this->assertTrue(!empty($elements), format_string('Translated @language shortcut link @label found.', array('@label' => $label, '@language' => $language->getName())));
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function doTestTranslationEdit() {
    $entity = entity_load($this->entityTypeId, $this->entityId, TRUE);
    $languages = $this->container->get('language_manager')->getLanguages();

    foreach ($this->langcodes as $langcode) {
      // We only want to test the title for non-english translations.
      if ($langcode != 'en') {
        $options = array('language' => $languages[$langcode]);
        $url = $entity->urlInfo('edit-form', $options);
        $this->drupalGet($url);

        $title = t('@title [%language translation]', array(
          '@title' => $entity->getTranslation($langcode)->label(),
          '%language' => $languages[$langcode]->getName(),
        ));
        $this->assertRaw($title);
      }
    }
  }

  /**
   * Tests the basic translation workflow.
   */
  protected function doTestTranslationChanged() {
    $entity = entity_load($this->entityTypeId, $this->entityId, TRUE);

    $this->assertFalse(
      $entity instanceof EntityChangedInterface,
      format_string('%entity is not implementing EntityChangedInterface.' , array('%entity' => $this->entityTypeId))
    );
  }

}
