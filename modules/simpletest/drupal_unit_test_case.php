<?php
// $Id: drupal_unit_test_case.php,v 1.1 2008/04/20 18:34:43 dries Exp $

/**
 * Test case Drupal unit tests.
 */
class DrupalUnitTestCase extends UnitTestCase {
  protected $created_temp_environment = FALSE;
  protected $db_prefix_original;
  protected $original_file_directory;

  /**
   * Retrieve the test information from getInfo().
   *
   * @param string $label Name of the test to be used by the SimpleTest library.
   */
  function __construct($label = NULL) {
    if (!$label) {
      if (method_exists($this, 'getInfo')) {
        $info  = $this->getInfo();
        $label = $info['name'];
      }
    }
    parent::__construct($label);
  }

  /**
   * Generates a random database prefix and runs the install scripts on the prefixed database.
   * After installation many caches are flushed and the internal browser is setup so that the page
   * requests will run on the new prefix. A temporary files directory is created with the same name
   * as the database prefix.
   *
   * @param ... List modules to enable.
   */
  public function setUp() {
    parent::setUp();
  }

  /**
   * Create a temporary environment for tests to take place in so that changes
   * will be reverted and other tests won't be affected.
   *
   * Generates a random database prefix and runs the install scripts on the prefixed database.
   * After installation many caches are flushed and the internal browser is setup so that the page
   * requests will run on the new prefix. A temporary files directory is created with the same name
   * as the database prefix.
   */
  protected function createTempEnvironment() {
    global $db_prefix, $simpletest_ua_key;
    $this->created_temp_environment = TRUE;
    if ($simpletest_ua_key) {
      $this->db_prefix_original = $db_prefix;
      $clean_url_original = variable_get('clean_url', 0);
      $db_prefix = 'simpletest'. mt_rand(1000, 1000000);
      include_once './includes/install.inc';
      drupal_install_system();
      $modules = array_unique(array_merge(func_get_args(), drupal_verify_profile('default', 'en')));
      drupal_install_modules($modules);
      $this->_modules = drupal_map_assoc($modules);
      $this->_modules['system'] = 'system';
      $task = 'profile';
      default_profile_tasks($task, '');
      menu_rebuild();
      actions_synchronize();
      _drupal_flush_css_js();
      variable_set('install_profile', 'default');
      variable_set('install_task', 'profile-finished');
      variable_set('clean_url', $clean_url_original);

      // Use temporary files directory with the same prefix as database.
      $this->original_file_directory = file_directory_path();
      variable_set('file_directory_path', file_directory_path() .'/'. $db_prefix);
      file_check_directory(file_directory_path(), TRUE); // Create the files directory.
    }
  }

  /**
   * Delete created files and temporary files directory, delete the tables created by setUp(),
   * and reset the database prefix.
   */
  public function tearDown() {
    global $db_prefix;
    if ($this->created_temp_environment && preg_match('/simpletest\d+/', $db_prefix)) {
      // Delete temporary files directory and reset files directory path.
      simpletest_clean_temporary_directory(file_directory_path());
      variable_set('file_directory_path', $this->original_file_directory);

      $schema = drupal_get_schema(NULL, TRUE);
      $ret = array();
      foreach ($schema as $name => $table) {
        db_drop_table($ret, $name);
      }
      $db_prefix = $this->db_prefix_original;
    }
    parent::tearDown();
  }
}
