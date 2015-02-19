<?php

/**
 * @file
 * Definition of Drupal\user\Tests\UserTranslationUITest.
 */

namespace Drupal\user\Tests;

use Drupal\content_translation\Tests\ContentTranslationUITest;

/**
 * Tests the User Translation UI.
 *
 * @group user
 */
class UserTranslationUITest extends ContentTranslationUITest {

  /**
   * The user name of the test user.
   *
   * @var string
   */
  protected $name;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('language', 'content_translation', 'user', 'views');

  protected function setUp() {
    $this->entityTypeId = 'user';
    $this->testLanguageSelector = FALSE;
    $this->name = $this->randomMachineName();
    parent::setUp();

    \Drupal::entityManager()->getStorage('user')->resetCache();
  }

  /**
   * Overrides \Drupal\content_translation\Tests\ContentTranslationUITest::getTranslatorPermission().
   */
  protected function getTranslatorPermissions() {
    return array_merge(parent::getTranslatorPermissions(), array('administer users'));
  }

  /**
   * Overrides \Drupal\content_translation\Tests\ContentTranslationUITest::getNewEntityValues().
   */
  protected function getNewEntityValues($langcode) {
    // User name is not translatable hence we use a fixed value.
    return array('name' => $this->name) + parent::getNewEntityValues($langcode);
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

}
