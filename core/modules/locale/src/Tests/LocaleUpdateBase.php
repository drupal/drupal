<?php

namespace Drupal\locale\Tests;

use Drupal\Core\StreamWrapper\PublicStream;
use Drupal\file\Entity\File;
use Drupal\simpletest\WebTestBase;
use Drupal\Component\Utility\SafeMarkup;

/**
 * Base class for testing updates to string translations.
 */
abstract class LocaleUpdateBase extends WebTestBase {

  /**
   * Timestamp for an old translation.
   *
   * @var int
   */
  protected $timestampOld;

  /**
   * Timestamp for a medium aged translation.
   *
   * @var int
   */
  protected $timestampMedium;

  /**
   * Timestamp for a new translation.
   *
   * @var int
   */
  protected $timestampNew;

  /**
   * Timestamp for current time.
   *
   * @var int
   */
  protected $timestampNow;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['locale', 'locale_test'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    // Setup timestamps to identify old and new translation sources.
    $this->timestampOld = REQUEST_TIME - 300;
    $this->timestampMedium = REQUEST_TIME - 200;
    $this->timestampNew = REQUEST_TIME - 100;
    $this->timestampNow = REQUEST_TIME;

    // Enable import of translations. By default this is disabled for automated
    // tests.
    $this->config('locale.settings')
      ->set('translation.import_enabled', TRUE)
      ->save();
  }

  /**
   * Sets the value of the default translations directory.
   *
   * @param string $path
   *   Path of the translations directory relative to the drupal installation
   *   directory.
   */
  protected function setTranslationsDirectory($path) {
    file_prepare_directory($path, FILE_CREATE_DIRECTORY);
    $this->config('locale.settings')->set('translation.path', $path)->save();
  }

  /**
   * Adds a language.
   *
   * @param string $langcode
   *   The language code of the language to add.
   */
  protected function addLanguage($langcode) {
    $edit = ['predefined_langcode' => $langcode];
    $this->drupalPostForm('admin/config/regional/language/add', $edit, t('Add language'));
    $this->container->get('language_manager')->reset();
    $this->assertTrue(\Drupal::languageManager()->getLanguage($langcode), SafeMarkup::format('Language %langcode added.', ['%langcode' => $langcode]));
  }

  /**
   * Creates a translation file and tests its timestamp.
   *
   * @param string $path
   *   Path of the file relative to the public file path.
   * @param string $filename
   *   Name of the file to create.
   * @param int $timestamp
   *   (optional) Timestamp to set the file to. Defaults to current time.
   * @param array $translations
   *   (optional) Array of source/target value translation strings. Only
   *   singular strings are supported, no plurals. No double quotes are allowed
   *   in source and translations strings.
   */
  protected function makePoFile($path, $filename, $timestamp = NULL, array $translations = []) {
    $timestamp = $timestamp ? $timestamp : REQUEST_TIME;
    $path = 'public://' . $path;
    $text = '';
    $po_header = <<<EOF
msgid ""
msgstr ""
"Project-Id-Version: Drupal 8\\n"
"MIME-Version: 1.0\\n"
"Content-Type: text/plain; charset=UTF-8\\n"
"Content-Transfer-Encoding: 8bit\\n"
"Plural-Forms: nplurals=2; plural=(n > 1);\\n"

EOF;

    // Convert array of translations to Gettext source and translation strings.
    if ($translations) {
      foreach ($translations as $source => $target) {
        $text .= 'msgid "' . $source . '"' . "\n";
        $text .= 'msgstr "' . $target . '"' . "\n";
      }
    }

    file_prepare_directory($path, FILE_CREATE_DIRECTORY);
    $file = File::create([
      'uid' => 1,
      'filename' => $filename,
      'uri' => $path . '/' . $filename,
      'filemime' => 'text/x-gettext-translation',
      'timestamp' => $timestamp,
      'status' => FILE_STATUS_PERMANENT,
    ]);
    file_put_contents($file->getFileUri(), $po_header . $text);
    touch(drupal_realpath($file->getFileUri()), $timestamp);
    $file->save();
  }

  /**
   * Setup the environment containing local and remote translation files.
   *
   * Update tests require a simulated environment for local and remote files.
   * Normally remote files are located at a remote server (e.g. ftp.drupal.org).
   * For testing we can not rely on this. A directory in the file system of the
   * test site is designated for remote files and is addressed using an absolute
   * URL. Because Drupal does not allow files with a po extension to be accessed
   * (denied in .htaccess) the translation files get a _po extension. Another
   * directory is designated for local translation files.
   *
   * The environment is set up with the following files. File creation times are
   * set to create different variations in test conditions.
   *   contrib_module_one
   *    - remote file: timestamp new
   *    - local file:  timestamp old
   *   contrib_module_two
   *    - remote file: timestamp old
   *    - local file:  timestamp new
   *   contrib_module_three
   *    - remote file: timestamp old
   *    - local file:  timestamp old
   *   custom_module_one
   *    - local file:  timestamp new
   * Time stamp of current translation set by setCurrentTranslations() is always
   * timestamp medium. This makes it easy to predict which translation will be
   * imported.
   */
  protected function setTranslationFiles() {
    $config = $this->config('locale.settings');

    // A flag is set to let the locale_test module replace the project data with
    // a set of test projects which match the below project files.
    \Drupal::state()->set('locale.test_projects_alter', TRUE);
    \Drupal::state()->set('locale.remove_core_project', FALSE);

    // Setup the environment.
    $public_path = PublicStream::basePath();
    $this->setTranslationsDirectory($public_path . '/local');
    $config->set('translation.default_filename', '%project-%version.%language._po')->save();

    // Setting up sets of translations for the translation files.
    $translations_one = ['January' => 'Januar_1', 'February' => 'Februar_1', 'March' => 'Marz_1'];
    $translations_two = ['February' => 'Februar_2', 'March' => 'Marz_2', 'April' => 'April_2'];
    $translations_three = ['April' => 'April_3', 'May' => 'Mai_3', 'June' => 'Juni_3'];

    // Add a number of files to the local file system to serve as remote
    // translation server and match the project definitions set in
    // locale_test_locale_translation_projects_alter().
    $this->makePoFile('remote/8.x/contrib_module_one', 'contrib_module_one-8.x-1.1.de._po', $this->timestampNew, $translations_one);
    $this->makePoFile('remote/8.x/contrib_module_two', 'contrib_module_two-8.x-2.0-beta4.de._po', $this->timestampOld, $translations_two);
    $this->makePoFile('remote/8.x/contrib_module_three', 'contrib_module_three-8.x-1.0.de._po', $this->timestampOld, $translations_three);

    // Add a number of files to the local file system to serve as local
    // translation files and match the project definitions set in
    // locale_test_locale_translation_projects_alter().
    $this->makePoFile('local', 'contrib_module_one-8.x-1.1.de._po', $this->timestampOld, $translations_one);
    $this->makePoFile('local', 'contrib_module_two-8.x-2.0-beta4.de._po', $this->timestampNew, $translations_two);
    $this->makePoFile('local', 'contrib_module_three-8.x-1.0.de._po', $this->timestampOld, $translations_three);
    $this->makePoFile('local', 'custom_module_one.de.po', $this->timestampNew);
  }

  /**
   * Setup existing translations in the database and set up the status of
   * existing translations.
   */
  protected function setCurrentTranslations() {
    // Add non customized translations to the database.
    $langcode = 'de';
    $context = '';
    $non_customized_translations = [
      'March' => 'Marz',
      'June' => 'Juni',
    ];
    foreach ($non_customized_translations as $source => $translation) {
      $string = $this->container->get('locale.storage')->createString([
        'source' => $source,
        'context' => $context,
      ])
        ->save();
      $this->container->get('locale.storage')->createTranslation([
        'lid' => $string->getId(),
        'language' => $langcode,
        'translation' => $translation,
        'customized' => LOCALE_NOT_CUSTOMIZED,
      ])->save();
    }

    // Add customized translations to the database.
    $customized_translations = [
      'January' => 'Januar_customized',
      'February' => 'Februar_customized',
      'May' => 'Mai_customized',
    ];
    foreach ($customized_translations as $source => $translation) {
      $string = $this->container->get('locale.storage')->createString([
        'source' => $source,
        'context' => $context,
      ])
        ->save();
      $this->container->get('locale.storage')->createTranslation([
        'lid' => $string->getId(),
        'language' => $langcode,
        'translation' => $translation,
        'customized' => LOCALE_CUSTOMIZED,
      ])->save();
    }

    // Add a state of current translations in locale_files.
    $default = [
      'langcode' => $langcode,
      'uri' => '',
      'timestamp' => $this->timestampMedium,
      'last_checked' => $this->timestampMedium,
    ];
    $data[] = [
      'project' => 'contrib_module_one',
      'filename' => 'contrib_module_one-8.x-1.1.de._po',
      'version' => '8.x-1.1',
    ];
    $data[] = [
      'project' => 'contrib_module_two',
      'filename' => 'contrib_module_two-8.x-2.0-beta4.de._po',
      'version' => '8.x-2.0-beta4',
    ];
    $data[] = [
      'project' => 'contrib_module_three',
      'filename' => 'contrib_module_three-8.x-1.0.de._po',
      'version' => '8.x-1.0',
    ];
    $data[] = [
      'project' => 'custom_module_one',
      'filename' => 'custom_module_one.de.po',
      'version' => '',
    ];
    foreach ($data as $file) {
      $file = array_merge($default, $file);
      db_insert('locale_file')->fields($file)->execute();
    }
  }

  /**
   * Checks the translation of a string.
   *
   * @param string $source
   *   Translation source string.
   * @param string $translation
   *   Translation to check. Use empty string to check for a not existing
   *   translation.
   * @param string $langcode
   *   Language code of the language to translate to.
   * @param string $message
   *   (optional) A message to display with the assertion.
   */
  protected function assertTranslation($source, $translation, $langcode, $message = '') {
    $db_translation = db_query('SELECT translation FROM {locales_target} lt INNER JOIN {locales_source} ls ON ls.lid = lt.lid WHERE ls.source = :source AND lt.language = :langcode', [':source' => $source, ':langcode' => $langcode])->fetchField();
    $db_translation = $db_translation == FALSE ? '' : $db_translation;
    $this->assertEqual($translation, $db_translation, $message ? $message : format_string('Correct translation of %source (%language)', ['%source' => $source, '%language' => $langcode]));
  }

}
