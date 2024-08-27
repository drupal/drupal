<?php

declare(strict_types=1);

namespace Drupal\Tests\locale\Functional;

use Drupal\Core\File\FileExists;
use Drupal\Tests\BrowserTestBase;

// cspell:ignore janvier lundi

/**
 * Tests the exportation of locale files.
 *
 * @group locale
 */
class LocaleExportTest extends BrowserTestBase {

  /**
   * {@inheritdoc}
   */
  protected static $modules = ['locale'];

  /**
   * {@inheritdoc}
   */
  protected $defaultTheme = 'stark';

  /**
   * A user able to create languages and export translations.
   */
  protected $adminUser = NULL;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->adminUser = $this->drupalCreateUser([
      'administer languages',
      'translate interface',
      'access administration pages',
    ]);
    $this->drupalLogin($this->adminUser);

    // Copy test po files to the translations directory.
    \Drupal::service('file_system')->copy(__DIR__ . '/../../../tests/test.de.po', 'translations://', FileExists::Replace);
    \Drupal::service('file_system')->copy(__DIR__ . '/../../../tests/test.xx.po', 'translations://', FileExists::Replace);
  }

  /**
   * Tests exportation of translations.
   */
  public function testExportTranslation(): void {
    $file_system = \Drupal::service('file_system');
    // First import some known translations.
    // This will also automatically add the 'fr' language.
    $name = $file_system->tempnam('temporary://', "po_") . '.po';
    file_put_contents($name, $this->getPoFile());
    $this->drupalGet('admin/config/regional/translate/import');
    $this->submitForm([
      'langcode' => 'fr',
      'files[file]' => $name,
    ], 'Import');
    $file_system->unlink($name);

    // Get the French translations.
    $this->drupalGet('admin/config/regional/translate/export');
    $this->submitForm(['langcode' => 'fr'], 'Export');

    // Ensure we have a translation file.
    $this->assertSession()->pageTextContains('# French translation of Drupal');
    // Ensure our imported translations exist in the file.
    $this->assertSession()->pageTextContains('msgstr "lundi"');

    // Import some more French translations which will be marked as customized.
    $name = $file_system->tempnam('temporary://', "po2_") . '.po';
    file_put_contents($name, $this->getCustomPoFile());
    $this->drupalGet('admin/config/regional/translate/import');
    $this->submitForm([
      'langcode' => 'fr',
      'files[file]' => $name,
      'customized' => 1,
    ], 'Import');
    $file_system->unlink($name);

    // Create string without translation in the locales_source table.
    $this->container
      ->get('locale.storage')
      ->createString()
      ->setString('February')
      ->save();

    // Export only customized French translations.
    $this->drupalGet('admin/config/regional/translate/export');
    $this->submitForm([
      'langcode' => 'fr',
      'content_options[not_customized]' => FALSE,
      'content_options[customized]' => TRUE,
      'content_options[not_translated]' => FALSE,
    ], 'Export');

    // Ensure we have a translation file.
    $this->assertSession()->pageTextContains('# French translation of Drupal');
    // Ensure the customized translations exist in the file.
    $this->assertSession()->pageTextContains('msgstr "janvier"');
    // Ensure no untranslated strings exist in the file.
    $this->assertSession()->responseNotContains('msgid "February"');

    // Export only untranslated French translations.
    $this->drupalGet('admin/config/regional/translate/export');
    $this->submitForm([
      'langcode' => 'fr',
      'content_options[not_customized]' => FALSE,
      'content_options[customized]' => FALSE,
      'content_options[not_translated]' => TRUE,
    ], 'Export');

    // Ensure we have a translation file.
    $this->assertSession()->pageTextContains('# French translation of Drupal');
    // Ensure no customized translations exist in the file.
    $this->assertSession()->responseNotContains('msgstr "janvier"');
    // Ensure the untranslated strings exist in the file, and with right quotes.
    $this->assertSession()->responseContains($this->getUntranslatedString());
  }

  /**
   * Tests exportation of translation template file.
   */
  public function testExportTranslationTemplateFile(): void {
    // Load an admin page with JavaScript so _drupal_add_library() fires at
    // least once and _locale_parse_js_file() gets to run at least once so that
    // the locales_source table gets populated with something.
    $this->drupalGet('admin/config/regional/language');
    // Get the translation template file.
    $this->drupalGet('admin/config/regional/translate/export');
    $this->submitForm([], 'Export');
    // Ensure we have a translation file.
    $this->assertSession()->pageTextContains('# LANGUAGE translation of PROJECT');
  }

  /**
   * Helper function that returns a proper .po file.
   */
  public function getPoFile() {
    return <<< EOF
msgid ""
msgstr ""
"Project-Id-Version: Drupal 8\\n"
"MIME-Version: 1.0\\n"
"Content-Type: text/plain; charset=UTF-8\\n"
"Content-Transfer-Encoding: 8bit\\n"
"Plural-Forms: nplurals=2; plural=(n > 1);\\n"

msgid "Monday"
msgstr "lundi"
EOF;
  }

  /**
   * Returns a .po file that will be marked as customized.
   */
  public function getCustomPoFile() {
    return <<< EOF
msgid ""
msgstr ""
"Project-Id-Version: Drupal 8\\n"
"MIME-Version: 1.0\\n"
"Content-Type: text/plain; charset=UTF-8\\n"
"Content-Transfer-Encoding: 8bit\\n"
"Plural-Forms: nplurals=2; plural=(n > 1);\\n"

msgid "January"
msgstr "janvier"
EOF;
  }

  /**
   * Returns a .po file fragment with an untranslated string.
   *
   * @return string
   *   A .po file fragment with an untranslated string.
   */
  public function getUntranslatedString() {
    return <<< EOF
msgid "February"
msgstr ""
EOF;
  }

}
