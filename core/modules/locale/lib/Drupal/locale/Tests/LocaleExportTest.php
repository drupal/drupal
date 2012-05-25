<?php

/**
 * @file
 * Definition of Drupal\locale\Tests\LocaleExportTest.
 */

namespace Drupal\locale\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests for the export of translation files.
 */
class LocaleExportTest extends WebTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Translation export',
      'description' => 'Tests the exportation of locale files.',
      'group' => 'Locale',
    );
  }

  /**
   * A user able to create languages and export translations.
   */
  protected $admin_user = NULL;

  function setUp() {
    parent::setUp('locale');

    $this->admin_user = $this->drupalCreateUser(array('administer languages', 'translate interface', 'access administration pages'));
    $this->drupalLogin($this->admin_user);
  }

  /**
   * Test exportation of translations.
   */
  function testExportTranslation() {
    // First import some known translations.
    // This will also automatically enable the 'fr' language.
    $name = tempnam('temporary://', "po_") . '.po';
    file_put_contents($name, $this->getPoFile());
    $this->drupalPost('admin/config/regional/translate/import', array(
      'langcode' => 'fr',
      'files[file]' => $name,
    ), t('Import'));
    drupal_unlink($name);

    // Get the French translations.
    $this->drupalPost('admin/config/regional/translate/export', array(
      'langcode' => 'fr',
    ), t('Export'));

    // Ensure we have a translation file.
    $this->assertRaw('# French translation of Drupal', t('Exported French translation file.'));
    // Ensure our imported translations exist in the file.
    $this->assertRaw('msgstr "lundi"', t('French translations present in exported file.'));

    // Import some more French translations which will be marked as customized.
    $name = tempnam('temporary://', "po2_") . '.po';
    file_put_contents($name, $this->getCustomPoFile());
    $this->drupalPost('admin/config/regional/translate/import', array(
      'langcode' => 'fr',
      'files[file]' => $name,
      'customized' => 1,
    ), t('Import'));
    drupal_unlink($name);

    // We can't import a string with an empty translation, but calling
    // locale() for an new string creates an entry in the locales_source table.
    locale('February', NULL, 'fr');

    // Export only customized French translations.
    $this->drupalPost('admin/config/regional/translate/export', array(
      'langcode' => 'fr',
      'content_options[not_customized]' => FALSE,
      'content_options[customized]' => TRUE,
      'content_options[not_translated]' => FALSE,
    ), t('Export'));

    // Ensure we have a translation file.
    $this->assertRaw('# French translation of Drupal', t('Exported French translation file with only customized strings.'));
    // Ensure the customized translations exist in the file.
    $this->assertRaw('msgstr "janvier"', t('French custom translation present in exported file.'));
    // Ensure no untranslated strings exist in the file.
    $this->assertNoRaw('msgid "February"', t('Untranslated string not present in exported file.'));

    // Export only untranslated French translations.
    $this->drupalPost('admin/config/regional/translate/export', array(
      'langcode' => 'fr',
      'content_options[not_customized]' => FALSE,
      'content_options[customized]' => FALSE,
      'content_options[not_translated]' => TRUE,
    ), t('Export'));

    // Ensure we have a translation file.
    $this->assertRaw('# French translation of Drupal', t('Exported French translation file with only untranslated strings.'));
    // Ensure no customized translations exist in the file.
    $this->assertNoRaw('msgstr "janvier"', t('French custom translation not present in exported file.'));
    // Ensure the untranslated strings exist in the file.
    $this->assertRaw('msgid "February"', t('Untranslated string present in exported file.'));
  }

  /**
   * Test exportation of translation template file.
   */
  function testExportTranslationTemplateFile() {
    // Get the translation template file.
    $this->drupalPost('admin/config/regional/translate/export', array(), t('Export'));
    // Ensure we have a translation file.
    $this->assertRaw('# LANGUAGE translation of PROJECT', t('Exported translation template file.'));
  }

  /**
   * Helper function that returns a proper .po file.
   */
  function getPoFile() {
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
  function getCustomPoFile() {
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

}
