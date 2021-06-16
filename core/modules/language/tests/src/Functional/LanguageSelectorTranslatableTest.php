<?php

namespace Drupal\Tests\language\Functional;

use Drupal\Tests\BrowserTestBase;

/**
 * Tests the content translation settings language selector options.
 *
 * @group language
 */
class LanguageSelectorTranslatableTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
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
  public function testLanguageStringSelector() {
    // Add another language.
    $edit = ['predefined_langcode' => 'es'];
    $this->drupalGet('admin/config/regional/language/add');
    $this->submitForm($edit, 'Add language');

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

}
