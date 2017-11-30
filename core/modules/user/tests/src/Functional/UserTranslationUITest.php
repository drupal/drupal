<?php

namespace Drupal\Tests\user\Functional;

use Drupal\Tests\content_translation\Functional\ContentTranslationUITestBase;

/**
 * Tests the User Translation UI.
 *
 * @group user
 */
class UserTranslationUITest extends ContentTranslationUITestBase {

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
  public static $modules = ['language', 'content_translation', 'user', 'views'];

  protected function setUp() {
    $this->entityTypeId = 'user';
    $this->testLanguageSelector = FALSE;
    $this->name = $this->randomMachineName();
    parent::setUp();

    \Drupal::entityManager()->getStorage('user')->resetCache();
  }

  /**
   * {@inheritdoc}
   */
  protected function getTranslatorPermissions() {
    return array_merge(parent::getTranslatorPermissions(), ['administer users']);
  }

  /**
   * {@inheritdoc}
   */
  protected function getNewEntityValues($langcode) {
    // User name is not translatable hence we use a fixed value.
    return ['name' => $this->name] + parent::getNewEntityValues($langcode);
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
