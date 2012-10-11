<?php

/**
 * @file
 * Definition of Drupal\locale\Tests\LocaleCompareTest.
 */

namespace Drupal\locale\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests for comparing status of existing project translations with available translations.
 */
class LocaleCompareTest extends WebTestBase {

  /**
   * The path of the translations directory where local translations are stored.
   *
   * @var string
   */
  private $tranlations_directory;

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('update', 'locale', 'locale_test');

  public static function getInfo() {
    return array(
      'name' => 'Compare project states',
      'description' => 'Tests for comparing status of existing project translations with available translations.',
      'group' => 'Locale',
    );
  }

  /**
   * Setup the test environment.
   *
   * We use German as default test language. Due to hardcoded configurations in
   * the locale_test module, the language can not be chosen randomly.
   */
  function setUp() {
    parent::setUp();
    module_load_include('compare.inc', 'locale');
    $admin_user = $this->drupalCreateUser(array('administer site configuration', 'administer languages', 'access administration pages', 'translate interface'));
    $this->drupalLogin($admin_user);
    $this->drupalPost('admin/config/regional/language/add', array('predefined_langcode' => 'de'), t('Add language'));
  }

  /**
   * Set the value of the default translations directory.
   *
   * @param string $path
   *   Path of the translations directory relative to the drupal installation
   *   directory.
   */
  private function setTranslationsDirectory($path) {
    $this->tranlations_directory = $path;
    file_prepare_directory($path, FILE_CREATE_DIRECTORY);
    variable_set('locale_translate_file_directory', $path);
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
   * @param string $data
   *   Translation data to put into the file. Po header data will be added.
   */
  private function makePoFile($path, $filename, $timestamp = NULL, $data = '') {
    $timestamp = $timestamp ? $timestamp : REQUEST_TIME;
    $path = 'public://' . $path;
    $po_header = <<<EOF
msgid ""
msgstr ""
"Project-Id-Version: Drupal 8\\n"
"MIME-Version: 1.0\\n"
"Content-Type: text/plain; charset=UTF-8\\n"
"Content-Transfer-Encoding: 8bit\\n"
"Plural-Forms: nplurals=2; plural=(n > 1);\\n"

EOF;

    file_prepare_directory($path, FILE_CREATE_DIRECTORY);
    $file = entity_create('file', array(
      'uid' => 1,
      'filename' => $filename,
      'uri' => $path . '/' . $filename,
      'filemime' => 'text/x-gettext-translation',
      'timestamp' => $timestamp,
      'status' => FILE_STATUS_PERMANENT,
    ));
    file_put_contents($file->uri, $po_header . $data);
    touch(drupal_realpath($file->uri), $timestamp);
    $file->save();
  }

  /**
   * Test for translation status storage and translation status comparison.
   */
  function testLocaleCompare() {
    // Create and login user.
    $admin_user = $this->drupalCreateUser(array('administer site configuration', 'administer languages', 'access administration pages'));
    $this->drupalLogin($admin_user);

    module_load_include('compare.inc', 'locale');

    // Check if hidden modules are not included.
    $projects = locale_translation_project_list();
    $this->assertFalse(isset($projects['locale_test']), 'Hidden module not found');

    // Make the test modules look like a normal custom module. i.e. make the
    // modules not hidden. locale_test_system_info_alter() modifies the project
    // info of the locale_test and locale_test_disabled modules.
    state()->set('locale_translation_test_system_info_alter', TRUE);

    // Reset static system list caches to reflect info changes.
    drupal_static_reset('locale_translation_project_list');
    system_list_reset();

    // Check if interface translation data is collected from hook_info.
    $projects = locale_translation_project_list();
    $this->assertEqual($projects['locale_test']['info']['interface translation server pattern'], 'core/modules/locale/test/test.%language.po', 'Interface translation parameter found in project info.');
    $this->assertEqual($projects['locale_test']['name'] , 'locale_test', format_string('%key found in project info.', array('%key' => 'interface translation project')));

    // Get the locale settings.
    $config = config('locale.settings');

    // Check if disabled modules are detected.
    $config->set('translation.check_disabled_modules', TRUE)->save();
    drupal_static_reset('locale_translation_project_list');
    $projects = locale_translation_project_list();
    $this->assertTrue(isset($projects['locale_test_disabled']), 'Disabled module found');

    // Check the fully processed list of project data of both enabled and
    // disabled modules.
    $config->set('translation.check_disabled_modules', TRUE)->save();
    drupal_static_reset('locale_translation_project_list');
    $projects = locale_translation_get_projects();
    $this->assertEqual($projects['drupal']->name, 'drupal', 'Core project found');
    $this->assertEqual($projects['locale_test']->server_pattern, 'core/modules/locale/test/test.%language.po', 'Interface translation parameter found in project info.');
    $this->assertEqual($projects['locale_test_disabled']->status, '0', 'Disabled module found');
    $config->delete('translation.check_disabled_modules');

    // Return the locale test modules back to their hidden state.
    state()->delete('locale_translation_test_system_info_alter');
  }

  /**
   * Checks if local or remote translation sources are detected.
   *
   * This test requires a simulated environment for local and remote files.
   * Normally remote files are located at a remote server (e.g. ftp.drupal.org).
   * For testing we can not rely on this. A directory in the file system of the
   * test site is designated for remote files and is addressed using an absolute
   * URL. Because Drupal does not allow files with a po extension to be accessed
   * (denied in .htaccess) the translation files get a txt extension. Another
   * directory is designated for local translation files.
   *
   * The translation status process by default checks the status of the
   * installed projects. For testing purpose a predefined set of modules with
   * fixed file names and release versions is used. Using a
   * hook_locale_translation_projects_alter implementation in the locale_test
   * module this custom project definition is applied.
   *
   * This test generates a set of local and remote translation files in their
   * respective local and remote translation directory. The test checks whether
   * the most recent files are selected in the different check scenarios: check
   * for local files only, check for remote files only, check for both local and
   * remote files.
   */
  function testCompareCheckLocal() {
    $config = config('locale.settings');

    // A flag is set to let the locale_test module replace the project data with
    // a set of test projects.
    state()->set('locale_translation_test_projects', TRUE);

    // Setup timestamps to identify old and new translation sources.
    $timestamp_old = REQUEST_TIME - 100;
    $timestamp_new = REQUEST_TIME;

    // Set up the environment.
    $public_path = variable_get('file_public_path', conf_path() . '/files');
    $this->setTranslationsDirectory($public_path . '/local');
    $config->set('translation.default_filename', '%project-%version.%language.txt')->save();

    // Add a number of files to the local file system to serve as remote
    // translation server and match the project definitions set in
    // locale_test_locale_translation_projects_alter().
    $this->makePoFile('remote/8.x/contrib_module_one', 'contrib_module_one-8.x-1.1.de.txt', $timestamp_new);
    $this->makePoFile('remote/8.x/contrib_module_two', 'contrib_module_two-8.x-2.0-beta4.de.txt', $timestamp_old);
    $this->makePoFile('remote/8.x/contrib_module_three', 'contrib_module_three-8.x-1.0.de.txt', $timestamp_old);

    // Add a number of files to the local file system to serve as local
    // translation files and match the project definitions set in
    // locale_test_locale_translation_projects_alter().
    $this->makePoFile('local', 'contrib_module_one-8.x-1.1.de.txt', $timestamp_old);
    $this->makePoFile('local', 'contrib_module_two-8.x-2.0-beta4.de.txt', $timestamp_new);
    $this->makePoFile('local', 'custom_module_one.de.po', $timestamp_new);

    // Get status of translation sources at local file system.
    $config->set('translation.use_source', LOCALE_TRANSLATION_USE_SOURCE_LOCAL)->save();
    $this->drupalGet('admin/reports/translations/check');
    $result = state()->get('locale_translation_status');
    $this->assertEqual($result['contrib_module_one']['de']->type, 'local', 'Translation of contrib_module_one found');
    $this->assertEqual($result['contrib_module_one']['de']->timestamp, $timestamp_old, 'Translation timestamp found');
    $this->assertEqual($result['contrib_module_two']['de']->type, 'local', 'Translation of contrib_module_two found');
    $this->assertEqual($result['contrib_module_two']['de']->timestamp, $timestamp_new, 'Translation timestamp found');
    $this->assertEqual($result['locale_test']['de']->type, 'local', 'Translation of locale_test found');
    $this->assertEqual($result['custom_module_one']['de']->type, 'local', 'Translation of custom_module_one found');

    // Get status of translation sources at both local and remote the locations.
    $config->set('translation.use_source', LOCALE_TRANSLATION_USE_SOURCE_REMOTE_AND_LOCAL)->save();
    $this->drupalGet('admin/reports/translations/check');
    $result = state()->get('locale_translation_status');
    $this->assertEqual($result['contrib_module_one']['de']->type, 'remote', 'Translation of contrib_module_one found');
    $this->assertEqual($result['contrib_module_one']['de']->timestamp, $timestamp_new, 'Translation timestamp found');
    $this->assertEqual($result['contrib_module_two']['de']->type, 'local', 'Translation of contrib_module_two found');
    $this->assertEqual($result['contrib_module_two']['de']->timestamp, $timestamp_new, 'Translation timestamp found');
    $this->assertEqual($result['contrib_module_three']['de']->type, 'remote', 'Translation of contrib_module_three found');
    $this->assertEqual($result['contrib_module_three']['de']->timestamp, $timestamp_old, 'Translation timestamp found');
    $this->assertEqual($result['locale_test']['de']->type, 'local', 'Translation of locale_test found');
    $this->assertEqual($result['custom_module_one']['de']->type, 'local', 'Translation of custom_module_one found');
  }
}
