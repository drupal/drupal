<?php

declare(strict_types=1);

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
   * {@inheritdoc}
   */
  protected $defaultCacheContexts = [
    'languages:language_interface',
    'theme',
    'url.query_args:_wrapper_format',
    'user.permissions',
    'url.site',
  ];

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'language',
    'content_translation',
    'user',
    'views',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    $this->entityTypeId = 'user';
    $this->testLanguageSelector = FALSE;
    $this->name = $this->randomMachineName();
    parent::setUp();
    $this->doSetup();

    \Drupal::entityTypeManager()->getStorage('user')->resetCache();
  }

  /**
   * {@inheritdoc}
   */
  protected function getTranslatorPermissions(): array {
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
  protected function doTestTranslationEdit(): void {
    $storage = $this->container->get('entity_type.manager')
      ->getStorage($this->entityTypeId);
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
   * Tests translated user deletion.
   */
  public function testTranslatedUserDeletion(): void {
    $this->drupalLogin($this->administrator);
    $entity_id = $this->createEntity($this->getNewEntityValues('en'), 'en');

    $entity = $this->container->get('entity_type.manager')
      ->getStorage($this->entityTypeId)
      ->load($entity_id);
    $translated_entity = $entity->addTranslation('fr');
    $translated_entity->save();

    $url = $entity->toUrl(
      'edit-form',
      ['language' => $this->container->get('language_manager')->getLanguage('en')]
    );
    $this->drupalGet($url);
    $this->clickLink('Cancel account');
    $this->assertSession()->statusCodeEquals(200);
  }

}
