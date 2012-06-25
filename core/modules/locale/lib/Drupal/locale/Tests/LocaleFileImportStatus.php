<?php

/**
 * @file
 * Definition of Drupal\locale\Tests\LocaleFileImportStatus.
 */

namespace Drupal\locale\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Functional tests for the import of translation files.
 */
class LocaleFileImportStatus extends WebTestBase {
  public static function getInfo() {
    return array(
      'name' => 'Translation file import status',
      'description' => 'Tests the status of imported translation files.',
      'group' => 'Locale',
    );
  }

  function setUp() {
    parent::setUp('locale');

    // Create and login user.
    $admin_user = $this->drupalCreateUser(array('administer site configuration', 'administer languages', 'access administration pages'));
    $this->drupalLogin($admin_user);

    // Set the translation file directory.
    variable_set('locale_translate_file_directory', drupal_get_path('module', 'locale') . '/tests');
  }

  /**
   * Add a language.
   *
   * @param $langcode
   *   The language of the langcode to add.
   */
  function addLanguage($langcode) {
    $edit = array('predefined_langcode' => $langcode);
    $this->drupalPost('admin/config/regional/language/add', $edit, t('Add language'));
    drupal_static_reset('language_list');
    $this->assertTrue(language_load($langcode), t('Language %langcode added.', array('%langcode' => $langcode)));
  }

  /**
   * Get translations for an array of strings.
   *
   * @param $strings
   *   An array of strings to translate.
   * @param $langcode
   *   The language code of the language to translate to.
   */
  function checkTranslations($strings, $langcode) {
    foreach ($strings as $source => $translation) {
      $db_translation = db_query('SELECT translation FROM {locales_target} lt INNER JOIN {locales_source} ls ON ls.lid = lt.lid WHERE ls.source = :source AND lt.language = :langcode', array(':source' => $source, ':langcode' => $langcode))->fetchField();
      $this->assertEqual((string) $translation, (string) $db_translation);
    }
  }

  /**
   * Import a single interface translation file.
   *
   * @param $langcode
   *   Langcode of the po file and language to import.
   * @param int $timestamp_difference
   *  (optional) Timestamp offset, used to mock older or newer files.
   *
   * @return stdClass
   *   A file object of type stdClass.
   */
  function mockImportedPoFile($langcode, $timestamp_difference = 0) {
    $dir = variable_get('locale_translate_file_directory', drupal_get_path('module', 'locale') . '/tests');
    $testfile_uri = $dir . '/test.' . $langcode . '.po';

    $file = locale_translate_file_create($testfile_uri);
    $file->original_timestamp = $file->timestamp;
    $file->timestamp = $file->timestamp + $timestamp_difference;
    $file->langcode = $langcode;

    // Fill the {locale_file} with a custom timestamp.
    if ($timestamp_difference != 0) {
      locale_translate_update_file_history($file);
    }

    $count = db_query('SELECT COUNT(*) FROM {locale_file} WHERE langcode = :langcode', array(':langcode' => $langcode))->fetchField();
    $this->assertEqual(1, $count, format_plural($count, '@count file registered in {locale_file}.', '@count files registered in {locale_file}.'));

    $result = db_query('SELECT langcode, uri FROM {locale_file}')->fetchAssoc();
    $this->assertEqual($result['uri'], $testfile_uri, t('%uri is in {locale_file}.', array('%uri' => $result['uri'])));
    $this->assertEqual($result['langcode'], $langcode, t('Langcode is %langcode.', array('%langcode' => $langcode)));
    return $file;
  }

  /**
   * Test the basic bulk import functionality.
   */
  function testBulkImport() {
    $langcode = 'de';

    // Translations should not exist.
    $strings = array(
      'Monday' => '',
      'Tuesday' => '',
    );
    $this->checkTranslations($strings, $langcode);

    // Add language.
    $this->addLanguage($langcode);

    // The file was imported, translations should exist.
    $strings = array(
      'Monday' => 'Montag',
      'Tuesday' => 'Dienstag',
    );
    $this->checkTranslations($strings, $langcode);
  }

  /**
   * Update a pre-existing file.
   */
  function testBulkImportUpdateExisting() {
    $langcode = 'de';

    // Translations should not exist.
    $strings = array(
      'Monday' => '',
      'Tuesday' => '',
    );
    $this->checkTranslations($strings, $langcode);

    // Fill the {locale_file} table with an older file.
    $file = $this->mockImportedPoFile($langcode, -1);

    // Add language.
    $this->addLanguage($langcode);

    // The file was imported, translations should exist.
    $strings = array(
      'Monday' => 'Montag',
      'Tuesday' => 'Dienstag',
    );
    $this->checkTranslations($strings, $langcode);

    $timestamp = db_query('SELECT timestamp FROM {locale_file} WHERE uri = :uri', array(':uri' => $file->uri))->fetchField();
    $this->assertEqual($timestamp, $file->original_timestamp, t('File is updated.'));
  }

  /**
   * Don't update a pre-existing file.
   */
  function testBulkImportNotUpdateExisting() {
    $langcode = 'de';

    // Translations should not exist.
    $strings = array(
      'Monday' => '',
      'Tuesday' => '',
    );
    $this->checkTranslations($strings, $langcode);

    // Fill the {locale_file} table with a newer file.
    $file = $this->mockImportedPoFile($langcode, 1);

    // Add language.
    $this->addLanguage($langcode);

    // The file was not imported, the translation should not exist.
    $strings = array(
      'Monday' => '',
      'Tuesday' => '',
    );
    $this->checkTranslations($strings, $langcode);

    $timestamp = db_query('SELECT timestamp FROM {locale_file} WHERE uri = :uri', array(':uri' => $file->uri))->fetchField();
    $this->assertEqual($timestamp, $file->timestamp);
  }

  /**
   * Delete translation files after deleting a language.
   */
  function testDeleteLanguage() {
    $dir = conf_path() . '/files/translations';
    file_prepare_directory($dir, FILE_CREATE_DIRECTORY);
    variable_set('locale_translate_file_directory', $dir);
    $langcode = 'de';
    $this->addLanguage($langcode);
    $file_uri = $dir . '/po_' . $this->randomString() . '.' . $langcode . '.po';
    file_put_contents($file_uri, $this->randomString());
    $this->assertTrue(is_file($file_uri), 'Translation file is created.');
    language_delete($langcode);
    $this->assertTrue($file_uri);
    $this->assertFalse(is_file($file_uri), 'Translation file deleted after deleting language');
  }
}
