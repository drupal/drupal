<?php

declare(strict_types=1);

namespace Drupal\Tests\language\Functional;

use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\language\Traits\LanguageTestTrait;

/**
 * Tests the content translation settings language selector options.
 *
 * @covers \Drupal\language\Form\ContentLanguageSettingsForm
 * @group language
 */
class LanguageSelectorTranslatableTest extends BrowserTestBase {

  use LanguageTestTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'language',
    'content_translation',
    'node',
    'comment',
    'field_ui',
    'entity_test',
    'locale',
  ];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * The user with administrator privileges.
   *
   * @var \Drupal\user\Entity\User
   */
  public $administrator;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    // Create user and set permissions.
    $this->administrator = $this->drupalCreateUser($this->getAdministratorPermissions(), 'administrator');
    $this->drupalLogin($this->administrator);
  }

  /**
   * Returns an array of permissions needed for the translator.
   */
  protected function getAdministratorPermissions() {
    return array_filter(
      ['translate interface',
        'administer content translation',
        'create content translations',
        'update content translations',
        'delete content translations',
        'administer languages',
      ]
    );
  }

  /**
   * Tests content translation language selectors are correctly translated.
   */
  public function testLanguageStringSelector(): void {
    // Add another language.
    static::createLanguageFromLangcode('es');

    // Translate the string English in Spanish (Inglés). Override config entity.
    $name_translation = 'Inglés';
    \Drupal::languageManager()
      ->getLanguageConfigOverride('es', 'language.entity.en')
      ->set('label', $name_translation)
      ->save();

    // Check content translation overview selector.
    $path = 'es/admin/config/regional/content-language';
    $this->drupalGet($path);

    // Get en language from selector.
    $option = $this->assertSession()->optionExists('edit-settings-user-user-settings-language-langcode', 'en');

    // Check that the language text is translated.
    $this->assertSame($name_translation, $option->getText());
  }

  /**
   * Tests that correct title is displayed for content translation page.
   */
  public function testContentTranslationPageTitle(): void {
    $this->drupalGet('admin/config/regional/content-language');
    $this->assertSession()->pageTextContains('Content language and translation');
    $this->assertSession()->pageTextNotMatches('#Content language$#');

    \Drupal::service('module_installer')->uninstall(['content_translation']);
    $this->drupalGet('admin/config/regional/content-language');
    $this->assertSession()->pageTextContains('Content language');
    $this->assertSession()->pageTextNotContains('Content language and translation');
  }

}
