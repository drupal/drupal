<?php

namespace Drupal\Tests\locale\Functional;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Tests\BrowserTestBase;

/**
 * Tests the exportation of locale files.
 *
 * @group locale
 */
class LocaleExportTest extends BrowserTestBase {

  /**
   * Modules to enable.
   *
   * @var array
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
    \Drupal::service('file_system')->copy(__DIR__ . '/../../../tests/test.de.po', 'translations://', FileSystemInterface::EXISTS_REPLACE);
    \Drupal::service('file_system')->copy(__DIR__ . '/../../../tests/test.xx.po', 'translations://', FileSystemInterface::EXISTS_REPLACE);
  }

  /**
   * Test exportation of translations.
   */
  public function testExportTranslation() {
    $file_system = \Drupal::service('file_system');
    // First import some known translations.
    // This will also automatically add the 'fr' language.
    $name = $file_system->tempnam('temporary://', "po_") . '.po';
    file_put_contents($name, $this->getPoFile());
    $this->drupalPostForm('admin/config/regional/translate/import', [
      'langcode' => 'fr',
      'files[file]' => $name,
    ], t('Import'));
    $file_system->unlink($name);

    // Get the French translations.
    $this->drupalPostForm('admin/config/regional/translate/export', [
      'langcode' => 'fr',
    ], t('Export'));

    // Ensure we have a translation file.
    $this->assertRaw('# French translation of Drupal', 'Exported French translation file.');
    // Ensure our imported translations exist in the file.
    $this->assertRaw('msgstr "lundi"', 'French translations present in exported file.');

    // Import some more French translations which will be marked as customized.
    $name = $file_system->tempnam('temporary://', "po2_") . '.po';
    file_put_contents($name, $this->getCustomPoFile());
    $this->drupalPostForm('admin/config/regional/translate/import', [
      'langcode' => 'fr',
      'files[file]' => $name,
      'customized' => 1,
    ], t('Import'));
    $file_system->unlink($name);

    // Create string without translation in the locales_source table.
    $this->container
      ->get('locale.storage')
      ->createString()
      ->setString('February')
      ->save();

    // Export only customized French translations.
    $this->drupalPostForm('admin/config/regional/translate/export', [
      'langcode' => 'fr',
      'content_options[not_customized]' => FALSE,
      'content_options[customized]' => TRUE,
      'content_options[not_translated]' => FALSE,
    ], t('Export'));

    // Ensure we have a translation file.
    $this->assertRaw('# French translation of Drupal', 'Exported French translation file with only customized strings.');
    // Ensure the customized translations exist in the file.
    $this->assertRaw('msgstr "janvier"', 'French custom translation present in exported file.');
    // Ensure no untranslated strings exist in the file.
    $this->assertNoRaw('msgid "February"', 'Untranslated string not present in exported file.');

    // Export only untranslated French translations.
    $this->drupalPostForm('admin/config/regional/translate/export', [
      'langcode' => 'fr',
      'content_options[not_customized]' => FALSE,
      'content_options[customized]' => FALSE,
      'content_options[not_translated]' => TRUE,
    ], t('Export'));

    // Ensure we have a translation file.
    $this->assertRaw('# French translation of Drupal', 'Exported French translation file with only untranslated strings.');
    // Ensure no customized translations exist in the file.
    $this->assertNoRaw('msgstr "janvier"', 'French custom translation not present in exported file.');
    // Ensure the untranslated strings exist in the file, and with right quotes.
    $this->assertRaw($this->getUntranslatedString(), 'Empty string present in exported file.');
  }

  /**
   * Test exportation of translation template file.
   */
  public function testExportTranslationTemplateFile() {
    // Load an admin page with JavaScript so _drupal_add_library() fires at
    // least once and _locale_parse_js_file() gets to run at least once so that
    // the locales_source table gets populated with something.
    $this->drupalGet('admin/config/regional/language');
    // Get the translation template file.
    $this->drupalPostForm('admin/config/regional/translate/export', [], t('Export'));
    // Ensure we have a translation file.
    $this->assertRaw('# LANGUAGE translation of PROJECT', 'Exported translation template file.');
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
   * Helper function that returns a .po file which strings will be marked
   * as customized.
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
