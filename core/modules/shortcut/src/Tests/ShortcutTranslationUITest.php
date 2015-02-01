<?php

/**
 * @file
 * Contains \Drupal\shortcut\Tests\ShortcutTranslationUITest.
 */

namespace Drupal\shortcut\Tests;

use Drupal\content_translation\Tests\ContentTranslationUITest;
use Drupal\Core\Language\Language;

/**
 * Tests the shortcut translation UI.
 *
 * @group Shortcut
 */
class ShortcutTranslationUITest extends ContentTranslationUITest {

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
    $values['link']['uri'] = 'user-path:user';
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
        $elements = $this->xpath('//nav[contains(@class, "toolbar-lining")]/ul[@class="menu"]/li/a[contains(@href, :href) and normalize-space(text())=:label]', array(':href' => $expected_path, ':label' => $label));
        $this->assertTrue(!empty($elements), format_string('Translated @language shortcut link @label found.', array('@label' => $label, '@language' => $language->getName())));
      }
    }
  }

}
