<?php

/**
 * @file
 * Contains Drupal\locale\Tests\LocaleUpdateTest.
 */

namespace Drupal\locale\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests for update translations.
 */
class LocaleUpdateTest extends WebTestBase {

  /**
   * The path of the translations directory where local translations are stored.
   *
   * @var string
   */
  private $tranlations_directory;

  /**
   * Timestamp for an old translation.
   *
   * @var integer
   */
  private $timestamp_old;

  /**
   * Timestamp for a medium aged translation.
   *
   * @var integer
   */
  private $timestamp_medium;

  /**
   * Timestamp for a new translation.
   *
   * @var integer
   */
  private $timestamp_new;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('update', 'locale', 'locale_test');

  public static function getInfo() {
    return array(
      'name' => 'Update translations',
      'description' => 'Tests for updating the interface translations of projects.',
      'group' => 'Locale',
    );
  }

  function setUp() {
    parent::setUp();
    module_load_include('compare.inc', 'locale');
    module_load_include('fetch.inc', 'locale');
    $admin_user = $this->drupalCreateUser(array('administer modules', 'administer site configuration', 'administer languages', 'access administration pages', 'translate interface'));
    $this->drupalLogin($admin_user);
    // We use German as test language. This language must match the translation
    // file that come with the locale_test module (test.de.po) and can therefore
    // not be chosen randomly.
    $this->drupalPost('admin/config/regional/language/add', array('predefined_langcode' => 'de'), t('Add language'));

    // Setup timestamps to identify old and new translation sources.
    $this->timestamp_old = REQUEST_TIME - 300;
    $this->timestamp_medium = REQUEST_TIME - 200;
    $this->timestamp_new = REQUEST_TIME - 100;
    $this->timestamp_now = REQUEST_TIME;
  }

  /**
   * Sets the value of the default translations directory.
   *
   * @param string $path
   *   Path of the translations directory relative to the drupal installation
   *   directory.
   */
  private function setTranslationsDirectory($path) {
    $this->tranlations_directory = $path;
    file_prepare_directory($path, FILE_CREATE_DIRECTORY);
    config('locale.settings')->set('translation.path', $path)->save();
  }

  /**
   * Adds a language.
   *
   * @param $langcode
   *   The language code of the language to add.
   */
  function addLanguage($langcode) {
    $edit = array('predefined_langcode' => $langcode);
    $this->drupalPost('admin/config/regional/language/add', $edit, t('Add language'));
    drupal_static_reset('language_list');
    $this->assertTrue(language_load($langcode), t('Language %langcode added.', array('%langcode' => $langcode)));
  }

  /**
   * Creates a translation file and tests its timestamp.
   *
   * @param string $path
   *   Path of the file relative to the public file path.
   * @param string $filename
   *   Name of the file to create.
   * @param integer $timestamp
   *   Timestamp to set the file to. Defaults to current time.
   * @param array $translations
   *   Array of source/target value translation strings. Only singular strings
   *   are supported, no plurals. No double quotes are allowed in source and
   *   translations strings.
   */
  private function makePoFile($path, $filename, $timestamp = NULL, $translations = array()) {
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
        $text .= 'msgid "'. $source . '"' . "\n";
        $text .= 'msgstr "'. $target . '"' . "\n";
      }
    }

    file_prepare_directory($path, FILE_CREATE_DIRECTORY);
    $file = entity_create('file', array(
      'uid' => 1,
      'filename' => $filename,
      'uri' => $path . '/' . $filename,
      'filemime' => 'text/x-gettext-translation',
      'timestamp' => $timestamp,
      'status' => FILE_STATUS_PERMANENT,
    ));
    file_put_contents($file->uri, $po_header . $text);
    touch(drupal_realpath($file->uri), $timestamp);
    $file->save();
  }

  /**
   * Setup the environment containting local and remote translation files.
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
  private function setTranslationFiles() {
    $config = config('locale.settings');

    // A flag is set to let the locale_test module replace the project data with
    // a set of test projects which match the below project files.
    \Drupal::state()->set('locale.test_projects_alter', TRUE);

    // Setup the environment.
    $public_path = variable_get('file_public_path', conf_path() . '/files');
    $this->setTranslationsDirectory($public_path . '/local');
    $config->set('translation.default_filename', '%project-%version.%language._po')->save();

    // Setting up sets of translations for the translation files.
    $translations_one = array('January' => 'Januar_1', 'February' => 'Februar_1', 'March' => 'Marz_1');
    $translations_two = array( 'February' => 'Februar_2', 'March' => 'Marz_2', 'April' => 'April_2');
    $translations_three = array('April' => 'April_3', 'May' => 'Mai_3', 'June' => 'Juni_3');

    // Add a number of files to the local file system to serve as remote
    // translation server and match the project definitions set in
    // locale_test_locale_translation_projects_alter().
    $this->makePoFile('remote/8.x/contrib_module_one', 'contrib_module_one-8.x-1.1.de._po', $this->timestamp_new, $translations_one);
    $this->makePoFile('remote/8.x/contrib_module_two', 'contrib_module_two-8.x-2.0-beta4.de._po', $this->timestamp_old, $translations_two);
    $this->makePoFile('remote/8.x/contrib_module_three', 'contrib_module_three-8.x-1.0.de._po', $this->timestamp_old, $translations_three);

    // Add a number of files to the local file system to serve as local
    // translation files and match the project definitions set in
    // locale_test_locale_translation_projects_alter().
    $this->makePoFile('local', 'contrib_module_one-8.x-1.1.de._po', $this->timestamp_old, $translations_one);
    $this->makePoFile('local', 'contrib_module_two-8.x-2.0-beta4.de._po', $this->timestamp_new, $translations_two);
    $this->makePoFile('local', 'contrib_module_three-8.x-1.0.de._po', $this->timestamp_old, $translations_three);
    $this->makePoFile('local', 'custom_module_one.de.po', $this->timestamp_new);
  }

  /**
   * Setup existing translations in the database and set up the status of
   * existing translations.
   */
  private function setCurrentTranslations() {
    // Add non customized translations to the database.
    $langcode = 'de';
    $context = '';
    $non_customized_translations = array(
      'March' => 'Marz',
      'June' => 'Juni',
    );
    foreach ($non_customized_translations as $source => $translation) {
      $string = locale_storage()->createString(array('source' => $source, 'context' => $context))
        ->save();
      $target = locale_storage()->createTranslation(array(
        'lid' => $string->getId(),
        'language' => $langcode,
        'translation' => $translation,
        'customized' => LOCALE_NOT_CUSTOMIZED,
      ))->save();
    }

    // Add customized translations to the database.
    $customized_translations = array(
      'January' => 'Januar_customized',
      'February' => 'Februar_customized',
      'May' => 'Mai_customized',
    );
    foreach ($customized_translations as $source => $translation) {
      $string = locale_storage()->createString(array('source' => $source, 'context' => $context))
        ->save();
      $target = locale_storage()->createTranslation(array(
        'lid' => $string->getId(),
        'language' => $langcode,
        'translation' => $translation,
        'customized' => LOCALE_CUSTOMIZED,
      ))->save();
    }

    // Add a state of current translations in locale_files.
    $default = array(
      'langcode' => $langcode,
      'uri' => '',
      'timestamp' => $this->timestamp_medium,
      'last_checked' => $this->timestamp_medium,
    );
    $data[] = array(
      'project' => 'contrib_module_one',
      'filename' => 'contrib_module_one-8.x-1.1.de._po',
      'version' => '8.x-1.1',
    );
    $data[] = array(
      'project' => 'contrib_module_two',
      'filename' => 'contrib_module_two-8.x-2.0-beta4.de._po',
      'version' => '8.x-2.0-beta4',
    );
    $data[] = array(
      'project' => 'contrib_module_three',
      'filename' => 'contrib_module_three-8.x-1.0.de._po',
      'version' => '8.x-1.0',
    );
    $data[] = array(
      'project' => 'custom_module_one',
      'filename' => 'custom_module_one.de.po',
      'version' => '',
    );
    foreach ($data as $file) {
      $file = (object) array_merge($default, $file);
      drupal_write_record('locale_file', $file);
    }
  }

  /**
   * Checks the translation of a string.
   *
   * @param string $source
   *   Translation source string
   * @param string $translation
   *   Translation to check. Use empty string to check for a not existing
   *   translation.
   * @param string $langcode
   *   Language code of the language to translate to.
   * @param string $message
   *   (optional) A message to display with the assertion.
   */
  function assertTranslation($source, $translation, $langcode, $message = '') {
    $db_translation = db_query('SELECT translation FROM {locales_target} lt INNER JOIN {locales_source} ls ON ls.lid = lt.lid WHERE ls.source = :source AND lt.language = :langcode', array(':source' => $source, ':langcode' => $langcode))->fetchField();
    $db_translation = $db_translation == FALSE ? '' : $db_translation;
    $this->assertEqual($translation, $db_translation, $message ? $message : format_string('Correct translation of %source (%language)', array('%source' => $source, '%language' => $langcode)));
  }

  /**
   * Checks if a list of translatable projects gets build.
   */
  function testUpdateProjects() {
    module_load_include('compare.inc', 'locale');

    // Make the test modules look like a normal custom module. i.e. make the
    // modules not hidden. locale_test_system_info_alter() modifies the project
    // info of the locale_test and locale_test_translate modules.
    \Drupal::state()->set('locale.test_system_info_alter', TRUE);
    $this->resetAll();

    // Check if interface translation data is collected from hook_info.
    $projects = locale_translation_project_list();
    $this->assertFalse(isset($projects['locale_test_translate']), 'Hidden module not found');
    $this->assertEqual($projects['locale_test']['info']['interface translation server pattern'], 'core/modules/locale/test/test.%language.po', 'Interface translation parameter found in project info.');
    $this->assertEqual($projects['locale_test']['name'] , 'locale_test', format_string('%key found in project info.', array('%key' => 'interface translation project')));
  }

  /**
   * Check if a list of translatable projects can include hidden projects.
   */
  function testUpdateProjectsHidden() {
    module_load_include('compare.inc', 'locale');
    $config = config('locale.settings');

    // Make the test modules look like a normal custom module.
    \Drupal::state()->set('locale.test_system_info_alter', TRUE);
    $this->resetAll();

    // Set test condition: include disabled modules when building a project list.
    $edit = array(
      'check_disabled_modules' => TRUE,
    );
    $this->drupalPost('admin/config/regional/translate/settings', $edit, t('Save configuration'));

    $projects = locale_translation_project_list();
    $this->assertTrue(isset($projects['locale_test_translate']), 'Disabled module found');
    $this->assertTrue(isset($projects['locale_test']), 'Enabled module found');
  }

  /**
   * Checks if local or remote translation sources are detected.
   *
   * The translation status process by default checks the status of the
   * installed projects. For testing purpose a predefined set of modules with
   * fixed file names and release versions is used. This custom project
   * definition is applied using a hook_locale_translation_projects_alter
   * implementation in the locale_test module.
   *
   * This test generates a set of local and remote translation files in their
   * respective local and remote translation directory. The test checks whether
   * the most recent files are selected in the different check scenarios: check
   * for local files only, check for both local and remote files.
   */
  function testUpdateCheckStatus() {
    $config = config('locale.settings');
    // Set a flag to let the locale_test module replace the project data with a
    // set of test projects.
    \Drupal::state()->set('locale.test_projects_alter', TRUE);

    // Create local and remote translations files.
    $this->setTranslationFiles();
    $config->set('translation.default_filename', '%project-%version.%language._po')->save();

    // Set the test conditions.
    $edit = array(
      'use_source' => LOCALE_TRANSLATION_USE_SOURCE_LOCAL,
    );
    $this->drupalPost('admin/config/regional/translate/settings', $edit, t('Save configuration'));

    // Get status of translation sources at local file system.
    $this->drupalGet('admin/reports/translations/check');
    $result = \Drupal::state()->get('locale.translation_status');
    $this->assertEqual($result['contrib_module_one']['de']->type, LOCALE_TRANSLATION_LOCAL, 'Translation of contrib_module_one found');
    $this->assertEqual($result['contrib_module_one']['de']->timestamp, $this->timestamp_old, 'Translation timestamp found');
    $this->assertEqual($result['contrib_module_two']['de']->type, LOCALE_TRANSLATION_LOCAL, 'Translation of contrib_module_two found');
    $this->assertEqual($result['contrib_module_two']['de']->timestamp, $this->timestamp_new, 'Translation timestamp found');
    $this->assertEqual($result['locale_test']['de']->type, LOCALE_TRANSLATION_LOCAL, 'Translation of locale_test found');
    $this->assertEqual($result['custom_module_one']['de']->type, LOCALE_TRANSLATION_LOCAL, 'Translation of custom_module_one found');

    // Set the test conditions.
    $edit = array(
      'use_source' => LOCALE_TRANSLATION_USE_SOURCE_REMOTE_AND_LOCAL,
    );
    $this->drupalPost('admin/config/regional/translate/settings', $edit, t('Save configuration'));

    // Get status of translation sources at both local and remote locations.
    $this->drupalGet('admin/reports/translations/check');
    $result = \Drupal::state()->get('locale.translation_status');
    $this->assertEqual($result['contrib_module_one']['de']->type, 'remote', 'Translation of contrib_module_one found');
    $this->assertEqual($result['contrib_module_one']['de']->timestamp, $this->timestamp_new, 'Translation timestamp found');
    $this->assertEqual($result['contrib_module_two']['de']->type, LOCALE_TRANSLATION_LOCAL, 'Translation of contrib_module_two found');
    $this->assertEqual($result['contrib_module_two']['de']->timestamp, $this->timestamp_new, 'Translation timestamp found');
    $this->assertEqual($result['contrib_module_three']['de']->type, LOCALE_TRANSLATION_LOCAL, 'Translation of contrib_module_three found');
    $this->assertEqual($result['contrib_module_three']['de']->timestamp, $this->timestamp_old, 'Translation timestamp found');
    $this->assertEqual($result['locale_test']['de']->type, LOCALE_TRANSLATION_LOCAL, 'Translation of locale_test found');
    $this->assertEqual($result['custom_module_one']['de']->type, LOCALE_TRANSLATION_LOCAL, 'Translation of custom_module_one found');
  }

  /**
   * Tests translation import from remote sources.
   *
   * Test conditions:
   *  - Source: remote and local files
   *  - Import overwrite: all existing translations
   *  - Translation directory: available
   */
  function testUpdateImportSourceRemote() {
    $config = config('locale.settings');

    // Build the test environment.
    $this->setTranslationFiles();
    $this-> setCurrentTranslations();
    $config->set('translation.default_filename', '%project-%version.%language._po');

    // Set the update conditions for this test.
    $edit = array(
      'use_source' => LOCALE_TRANSLATION_USE_SOURCE_REMOTE_AND_LOCAL,
      'overwrite' => LOCALE_TRANSLATION_OVERWRITE_ALL,
    );
    $this->drupalPost('admin/config/regional/translate/settings', $edit, t('Save configuration'));

    // Get the translation status.
    $this->drupalGet('admin/reports/translations/check');

    // Check the status on the Available translation status page.
    $this->assertRaw('<label for="edit-langcodes-de" class="language-name">German</label>', 'German language found');
    $this->assertText('Updates for: Contributed module one, Contributed module two, Custom module one, Locale test', 'Updates found');
    $this->assertText('Updates for: Contributed module one, Contributed module two, Custom module one, Locale test', 'Updates found');
    $this->assertText('Contributed module one (' . format_date($this->timestamp_now, 'html_date') . ')', 'Updates for Contrib module one');
    $this->assertText('Contributed module two (' . format_date($this->timestamp_new, 'html_date') . ')', 'Updates for Contrib module two');

    // Execute the translation update.
    $this->drupalPost('admin/reports/translations', array(), t('Update translations'));

    // Check if the translation has been updated, using the status cache.
    $status = \Drupal::state()->get('locale.translation_status');
    $this->assertEqual($status['contrib_module_one']['de']->type, LOCALE_TRANSLATION_CURRENT, 'Translation of contrib_module_one found');
    $this->assertEqual($status['contrib_module_two']['de']->type, LOCALE_TRANSLATION_CURRENT, 'Translation of contrib_module_two found');
    $this->assertEqual($status['contrib_module_three']['de']->type, LOCALE_TRANSLATION_CURRENT, 'Translation of contrib_module_three found');

    // Check the new translation status.
    // The static cache needs to be flushed first to get the most recent data
    // from the database. The function was called earlier during this test.
    drupal_static_reset('locale_translation_get_file_history');
    $history = locale_translation_get_file_history();
    $this->assertTrue($history['contrib_module_one']['de']->timestamp >= $this->timestamp_now, 'Translation of contrib_module_one is imported');
    $this->assertTrue($history['contrib_module_one']['de']->last_checked >= $this->timestamp_now, 'Translation of contrib_module_one is updated');
    $this->assertEqual($history['contrib_module_two']['de']->timestamp, $this->timestamp_new, 'Translation of contrib_module_two is imported');
    $this->assertTrue($history['contrib_module_two']['de']->last_checked >= $this->timestamp_now, 'Translation of contrib_module_two is updated');
    $this->assertEqual($history['contrib_module_three']['de']->timestamp, $this->timestamp_medium, 'Translation of contrib_module_three is not imported');
    $this->assertEqual($history['contrib_module_three']['de']->last_checked, $this->timestamp_medium, 'Translation of contrib_module_three is not updated');

    // Check whether existing translations have (not) been overwritten.
    $this->assertEqual(t('January', array(), array('langcode' => 'de')), 'Januar_1', 'Translation of January');
    $this->assertEqual(t('February', array(), array('langcode' => 'de')), 'Februar_2', 'Translation of February');
    $this->assertEqual(t('March', array(), array('langcode' => 'de')), 'Marz_2', 'Translation of March');
    $this->assertEqual(t('April', array(), array('langcode' => 'de')), 'April_2', 'Translation of April');
    $this->assertEqual(t('May', array(), array('langcode' => 'de')), 'Mai_customized', 'Translation of May');
    $this->assertEqual(t('June', array(), array('langcode' => 'de')), 'Juni', 'Translation of June');
    $this->assertEqual(t('Monday', array(), array('langcode' => 'de')), 'Montag', 'Translation of Monday');
  }

  /**
   * Tests translation import from local sources.
   *
   * Test conditions:
   *  - Source: local files only
   *  - Import overwrite: all existing translations
   *  - Translation directory: available
   */
  function testUpdateImportSourceLocal() {
    $config = config('locale.settings');

    // Build the test environment.
    $this->setTranslationFiles();
    $this-> setCurrentTranslations();
    $config->set('translation.default_filename', '%project-%version.%language._po');

    // Set the update conditions for this test.
    $edit = array(
      'use_source' => LOCALE_TRANSLATION_USE_SOURCE_LOCAL,
      'overwrite' => LOCALE_TRANSLATION_OVERWRITE_ALL,
    );
    $this->drupalPost('admin/config/regional/translate/settings', $edit, t('Save configuration'));

    // Execute the translation update.
    $this->drupalGet('admin/reports/translations/check');
    $this->drupalPost('admin/reports/translations', array(), t('Update translations'));

    // Check if the translation has been updated, using the status cache.
    $status = \Drupal::state()->get('locale.translation_status');
    $this->assertEqual($status['contrib_module_one']['de']->type, LOCALE_TRANSLATION_CURRENT, 'Translation of contrib_module_one found');
    $this->assertEqual($status['contrib_module_two']['de']->type, LOCALE_TRANSLATION_CURRENT, 'Translation of contrib_module_two found');
    $this->assertEqual($status['contrib_module_three']['de']->type, LOCALE_TRANSLATION_CURRENT, 'Translation of contrib_module_three found');

    // Check the new translation status.
    // The static cache needs to be flushed first to get the most recent data
    // from the database. The function was called earlier during this test.
    drupal_static_reset('locale_translation_get_file_history');
    $history = locale_translation_get_file_history();
    $this->assertTrue($history['contrib_module_one']['de']->timestamp >= $this->timestamp_medium, 'Translation of contrib_module_one is imported');
    $this->assertEqual($history['contrib_module_one']['de']->last_checked, $this->timestamp_medium, 'Translation of contrib_module_one is updated');
    $this->assertEqual($history['contrib_module_two']['de']->timestamp, $this->timestamp_new, 'Translation of contrib_module_two is imported');
    $this->assertTrue($history['contrib_module_two']['de']->last_checked >= $this->timestamp_now, 'Translation of contrib_module_two is updated');
    $this->assertEqual($history['contrib_module_three']['de']->timestamp, $this->timestamp_medium, 'Translation of contrib_module_three is not imported');
    $this->assertEqual($history['contrib_module_three']['de']->last_checked, $this->timestamp_medium, 'Translation of contrib_module_three is not updated');

    // Check whether existing translations have (not) been overwritten.
    $this->assertEqual(t('January', array(), array('langcode' => 'de')), 'Januar_customized', 'Translation of January');
    $this->assertEqual(t('February', array(), array('langcode' => 'de')), 'Februar_2', 'Translation of February');
    $this->assertEqual(t('March', array(), array('langcode' => 'de')), 'Marz_2', 'Translation of March');
    $this->assertEqual(t('April', array(), array('langcode' => 'de')), 'April_2', 'Translation of April');
    $this->assertEqual(t('May', array(), array('langcode' => 'de')), 'Mai_customized', 'Translation of May');
    $this->assertEqual(t('June', array(), array('langcode' => 'de')), 'Juni', 'Translation of June');
    $this->assertEqual(t('Monday', array(), array('langcode' => 'de')), 'Montag', 'Translation of Monday');
  }

  /**
   * Tests translation import without a translations directory.
   *
   * Test conditions:
   *  - Source: remote and local files
   *  - Import overwrite: all existing translations
   *  - Translation directory: not available
   */
  function testUpdateImportWithoutDirectory() {
    $config = config('locale.settings');

    // Build the test environment.
    $this->setTranslationFiles();
    $this-> setCurrentTranslations();
    $config->set('translation.default_filename', '%project-%version.%language._po');

    // Set the update conditions for this test.
    $this->setTranslationsDirectory('');
    $edit = array(
      'use_source' => LOCALE_TRANSLATION_USE_SOURCE_REMOTE_AND_LOCAL,
      'overwrite' => LOCALE_TRANSLATION_OVERWRITE_ALL,
    );
    $this->drupalPost('admin/config/regional/translate/settings', $edit, t('Save configuration'));

    // Execute the translation update.
    $this->drupalGet('admin/reports/translations/check');
    $this->drupalPost('admin/reports/translations', array(), t('Update translations'));

    // Check if the translation has been updated, using the status cache.
    $status = \Drupal::state()->get('locale.translation_status');
    $this->assertEqual($status['contrib_module_one']['de']->type, LOCALE_TRANSLATION_CURRENT, 'Translation of contrib_module_one found');
    $this->assertEqual($status['contrib_module_two']['de']->type, LOCALE_TRANSLATION_CURRENT, 'Translation of contrib_module_two found');
    $this->assertEqual($status['contrib_module_three']['de']->type, LOCALE_TRANSLATION_CURRENT, 'Translation of contrib_module_three found');

    // Check the new translation status.
    // The static cache needs to be flushed first to get the most recent data
    // from the database. The function was called earlier during this test.
    drupal_static_reset('locale_translation_get_file_history');
    $history = locale_translation_get_file_history();
    $this->assertTrue($history['contrib_module_one']['de']->timestamp >= $this->timestamp_now, 'Translation of contrib_module_one is imported');
    $this->assertTrue($history['contrib_module_one']['de']->last_checked >= $this->timestamp_now, 'Translation of contrib_module_one is updated');
    $this->assertEqual($history['contrib_module_two']['de']->timestamp, $this->timestamp_medium, 'Translation of contrib_module_two is imported');
    $this->assertEqual($history['contrib_module_two']['de']->last_checked, $this->timestamp_medium, 'Translation of contrib_module_two is updated');
    $this->assertEqual($history['contrib_module_three']['de']->timestamp, $this->timestamp_medium, 'Translation of contrib_module_three is not imported');
    $this->assertEqual($history['contrib_module_three']['de']->last_checked, $this->timestamp_medium, 'Translation of contrib_module_three is not updated');

    // Check whether existing translations have (not) been overwritten.
    $this->assertEqual(t('January', array(), array('langcode' => 'de')), 'Januar_1', 'Translation of January');
    $this->assertEqual(t('February', array(), array('langcode' => 'de')), 'Februar_1', 'Translation of February');
    $this->assertEqual(t('March', array(), array('langcode' => 'de')), 'Marz_1', 'Translation of March');
    $this->assertEqual(t('May', array(), array('langcode' => 'de')), 'Mai_customized', 'Translation of May');
    $this->assertEqual(t('June', array(), array('langcode' => 'de')), 'Juni', 'Translation of June');
    $this->assertEqual(t('Monday', array(), array('langcode' => 'de')), 'Montag', 'Translation of Monday');
  }

  /**
   * Tests translation import with a translations directory and only overwrite
   * non-customized translations.
   *
   * Test conditions:
   *  - Source: remote and local files
   *  - Import overwrite: only overwrite non-customized translations
   *  - Translation directory: available
   */
  function testUpdateImportModeNonCustomized() {
    $config = config('locale.settings');

    // Build the test environment.
    $this->setTranslationFiles();
    $this-> setCurrentTranslations();
    $config->set('translation.default_filename', '%project-%version.%language._po');

    // Set the test conditions.
    $edit = array(
      'use_source' => LOCALE_TRANSLATION_USE_SOURCE_REMOTE_AND_LOCAL,
      'overwrite' => LOCALE_TRANSLATION_OVERWRITE_NON_CUSTOMIZED,
    );
    $this->drupalPost('admin/config/regional/translate/settings', $edit, t('Save configuration'));

    // Execute translation update.
    $this->drupalGet('admin/reports/translations/check');
    $this->drupalPost('admin/reports/translations', array(), t('Update translations'));

    // Check whether existing translations have (not) been overwritten.
    $this->assertEqual(t('January', array(), array('langcode' => 'de')), 'Januar_customized', 'Translation of January');
    $this->assertEqual(t('February', array(), array('langcode' => 'de')), 'Februar_customized', 'Translation of February');
    $this->assertEqual(t('March', array(), array('langcode' => 'de')), 'Marz_2', 'Translation of March');
    $this->assertEqual(t('April', array(), array('langcode' => 'de')), 'April_2', 'Translation of April');
    $this->assertEqual(t('May', array(), array('langcode' => 'de')), 'Mai_customized', 'Translation of May');
    $this->assertEqual(t('June', array(), array('langcode' => 'de')), 'Juni', 'Translation of June');
    $this->assertEqual(t('Monday', array(), array('langcode' => 'de')), 'Montag', 'Translation of Monday');
  }

  /**
   * Tests translation import with a translations directory and don't overwrite
   * any translation.
   *
   * Test conditions:
   *  - Source: remote and local files
   *  - Import overwrite: don't overwrite any existing translation
   *  - Translation directory: available
   */
  function testUpdateImportModeNone() {
    $config = config('locale.settings');

    // Build the test environment.
    $this->setTranslationFiles();
    $this-> setCurrentTranslations();
    $config->set('translation.default_filename', '%project-%version.%language._po');

    // Set the test conditions.
    $edit = array(
      'use_source' => LOCALE_TRANSLATION_USE_SOURCE_REMOTE_AND_LOCAL,
      'overwrite' => LOCALE_TRANSLATION_OVERWRITE_NONE,
    );
    $this->drupalPost('admin/config/regional/translate/settings', $edit, t('Save configuration'));

    // Execute translation update.
    $this->drupalGet('admin/reports/translations/check');
    $this->drupalPost('admin/reports/translations', array(), t('Update translations'));

    // Check whether existing translations have (not) been overwritten.
    $this->assertTranslation('January', 'Januar_customized', 'de');
    $this->assertTranslation('February', 'Februar_customized', 'de');
    $this->assertTranslation('March', 'Marz', 'de');
    $this->assertTranslation('April', 'April_2', 'de');
    $this->assertTranslation('May', 'Mai_customized', 'de');
    $this->assertTranslation('June', 'Juni', 'de');
    $this->assertTranslation('Monday', 'Montag', 'de');
  }

  /**
   * Tests automatic translation import when a module is enabled.
   */
  function testEnableDisableModule() {
    // Make the hidden test modules look like a normal custom module.
    \Drupal::state()->set('locale.test_system_info_alter', TRUE);

    // Check if there is no translation yet.
    $this->assertTranslation('Tuesday', '', 'de');

    // Enable a module.
    $edit = array(
      'modules[Testing][locale_test_translate][enable]' => 'locale_test_translate',
    );
    $this->drupalPost('admin/modules', $edit, t('Save configuration'));

    // Check if translations have been imported.
    $this->assertRaw(t('One translation file imported. %number translations were added, %update translations were updated and %delete translations were removed.',
      array('%number' => 7, '%update' => 0, '%delete' => 0)), 'One translation file imported.');
    $this->assertTranslation('Tuesday', 'Dienstag', 'de');

    // Disable and uninstall a module.
    $edit = array(
      'modules[Testing][locale_test_translate][enable]' => FALSE,
    );
    $this->drupalPost('admin/modules', $edit, t('Save configuration'));
    $edit = array(
      'uninstall[locale_test_translate]' => 1,
    );
    $this->drupalPost('admin/modules/uninstall', $edit, t('Uninstall'));
    $this->drupalPost(NULL, array(), t('Uninstall'));

    // Check if the file data is removed from the database.
    $history = locale_translation_get_file_history();
    $this->assertFalse(isset($history['locale_test_translate']), 'Project removed from the file history');
    $projects = locale_translation_get_projects();
    $this->assertFalse(isset($projects['locale_test_translate']), 'Project removed from the project list');
  }

  /**
   * Tests automatic translation import when a langauge is enabled.
   *
   * When a language is added, the system will check for translations files of
   * enabled modules and will import them. When a language is removed the system
   * will remove all translations of that langugue from the database.
   */
  function testEnableDisableLanguage() {
    // Make the hidden test modules look like a normal custom module.
    \Drupal::state()->set('locale.test_system_info_alter', TRUE);

    // Enable a module.
    $edit = array(
      'modules[Testing][locale_test_translate][enable]' => 'locale_test_translate',
    );
    $this->drupalPost('admin/modules', $edit, t('Save configuration'));

    // Check if there is no Dutch translation yet.
    $this->assertTranslation('Extraday', '', 'nl');
    $this->assertTranslation('Tuesday', 'Dienstag', 'de');

    // Add a language.
    $edit = array(
      'predefined_langcode' => 'nl',
    );
    $this->drupalPost('admin/config/regional/language/add', $edit, t('Add language'));

    // Check if the right number of translations are added.
    $this->assertRaw(t('One translation file imported. %number translations were added, %update translations were updated and %delete translations were removed.',
      array('%number' => 8, '%update' => 0, '%delete' => 0)), 'One language added.');
    $this->assertTranslation('Extraday', 'extra dag', 'nl');

    // Check if the language data is added to the database.
    $result = db_query("SELECT project FROM {locale_file} WHERE langcode='nl'")->fetchField();
    $this->assertTrue((boolean) $result, 'Files removed from file history');

    // Remove a language.
    $this->drupalPost('admin/config/regional/language/delete/nl', array(), t('Delete'));

    // Check if the language data is removed from the database.
    $result = db_query("SELECT project FROM {locale_file} WHERE langcode='nl'")->fetchField();
    $this->assertFalse($result, 'Files removed from file history');

    // Check that the Dutch translation is gone.
    $this->assertTranslation('Extraday', '', 'nl');
    $this->assertTranslation('Tuesday', 'Dienstag', 'de');
  }

  /**
   * Tests automatic translation import when a custom langauge is enabled.
   */
  function testEnableCustomLanguage() {
    // Make the hidden test modules look like a normal custom module.
    \Drupal::state()->set('locale.test_system_info_alter', TRUE);

    // Enable a module.
    $edit = array(
      'modules[Testing][locale_test_translate][enable]' => 'locale_test_translate',
    );
    $this->drupalPost('admin/modules', $edit, t('Save configuration'));

    // Create and enable a custom language with language code 'xx' and a random
    // name.
    $langcode = 'xx';
    $name = $this->randomName(16);
    $edit = array(
      'predefined_langcode' => 'custom',
      'langcode' => $langcode,
      'name' => $name,
      'direction' => '0',
    );
    $this->drupalPost('admin/config/regional/language/add', $edit, t('Add custom language'));

    // Ensure the translation file is automatically imported when the language
    // was added.
    $this->assertText(t('One translation file imported.'), t('Language file automatically imported.'));
    $this->assertText(t('One translation string was skipped because of disallowed or malformed HTML'), t('Language file automatically imported.'));

    // Ensure the strings were successfully imported.
    $search = array(
      'string' => 'lundi',
      'langcode' => $langcode,
      'translation' => 'translated',
    );
    $this->drupalPost('admin/config/regional/translate/translate', $search, t('Filter'));
    $this->assertNoText(t('No strings available.'), t('String successfully imported.'));

    // Ensure the multiline string was imported.
    $search = array(
      'string' => 'Source string for multiline translation',
      'langcode' => $langcode,
      'translation' => 'all',
    );
    $this->drupalPost('admin/config/regional/translate/translate', $search, t('Filter'));
    $this->assertText('Multiline translation string to make sure that import works with it.', t('String successfully imported.'));

    // Ensure 'Allowed HTML source string' was imported but the translation for
    // 'Another allowed HTML source string' was not because it contains invalid
    // HTML.
    $search = array(
      'string' => 'HTML source string',
      'langcode' => $langcode,
      'translation' => 'all',
    );
    $this->drupalPost('admin/config/regional/translate/translate', $search, t('Filter'));
    $this->assertText('Allowed HTML source string', t('String successfully imported.'));
    $this->assertNoText('Another allowed HTML source string', t('String with disallowed translation not imported.'));
  }

}
