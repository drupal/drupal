<?php
// $Id$

/**
 * Test case for typical Drupal tests.
 */
class DrupalWebTestCase {
  protected $_logged_in = FALSE;
  protected $_content;
  protected $_url;
  protected $plain_text;
  protected $ch;
  protected $elements;
  // We do not reuse the cookies in further runs, so we do not need a file
  // but we still need cookie handling, so we set the jar to NULL
  protected $cookie_file = NULL;
  // Overwrite this any time to supply cURL options as necessary,
  // DrupalTestCase itself never sets this but always obeys whats set.
  protected $curl_options = array();
  protected $db_prefix_original;
  protected $original_file_directory;

  var $_results = array('#pass' => 0, '#fail' => 0, '#exception' => 0);
  var $_assertions = array();

  /**
   * Constructor for DrupalWebTestCase.
   *
   * @param @test_id
   *   Tests with the same id are reported together.
   */
  function __construct($test_id = NULL) {
    $this->test_id = $test_id;
  }

  /**
   * This function stores the assert. Do not call directly.
   *
   * @param $status
   *   Can be 'pass', 'fail', 'exception'. TRUE is a synonym for 'pass', FALSE
   *   for 'fail'.
   * @param $message
   *   The message string.
   * @param $group
   *   WHich group this assert belongs to.
   * @param $caller
   *   By default, the assert comes from a function which names start with
   *   'test'. Instead, you can specify where this assert originates from
   *   by passing in an associative array as $caller. Key 'file' is
   *   the name of the source file, 'line' is the line number and 'function'
   *   is the caller function itself.
   */
  protected function _assert($status, $message = '', $group = 'Other', $caller = NULL) {
    global $db_prefix;

    // Convert boolean status to string status.
    if (is_bool($status)) {
      $status = $status ? 'pass' : 'fail';
    }

    // Increment summary result counter.
    $this->_results['#' . $status]++;

    // Get the function information about the call to the assertion method.
    if (!$caller) {
      $caller = $this->getAssertionCall();
    }

    // Switch to non-testing database to store results in.
    $current_db_prefix = $db_prefix;
    $db_prefix = $this->db_prefix_original;

    // Creation assertion array that can be displayed while tests are running.
    $this->_assertions[] = $assertion = array(
      'test_id' => $this->test_id,
      'test_class' => get_class($this),
      'status' => $status,
      'message' => $message,
      'message_group' => $group,
      'function' => $caller['function'],
      'line' => $caller['line'],
      'file' => $caller['file'],
    );

    // Store assertion for display after the test has completed.
    db_insert('simpletest')->fields($assertion)->execute();

    // Return to testing prefix.
    $db_prefix = $current_db_prefix;
    return $status;
  }

  /**
   * Cycles through backtrace until the first non-assertion method is found.
   *
   * @return
   *   Array representing the true caller.
   */
  protected function getAssertionCall() {
    $backtrace = debug_backtrace();

    // The first element is the call. The second element is the caller.
    // We skip calls that occured in one of the methods of DrupalWebTestCase
    // or in an assertion function.
    while (($caller = $backtrace[1]) &&
          ((isset($caller['class']) && $caller['class'] == 'DrupalWebTestCase') ||
            substr($caller['function'], 0, 6) == 'assert')) {
      // We remove that call.
      array_shift($backtrace);
    }

    return _drupal_get_last_caller($backtrace);
  }

  /**
   * Check to see if a value is not false (not an empty string, 0, NULL, or FALSE).
   *
   * @param $value
   *   The value on which the assertion is to be done.
   * @param $message
   *   The message to display along with the assertion.
   * @param $group
   *   The type of assertion - examples are "Browser", "PHP".
   * @return
   *   The status passed in.
   */
  protected function assertTrue($value, $message = '', $group = 'Other') {
    return $this->_assert((bool) $value, $message ? $message : t('Value is TRUE'), $group);
  }

  /**
   * Check to see if a value is false (an empty string, 0, NULL, or FALSE).
   *
   * @param $value
   *   The value on which the assertion is to be done.
   * @param $message
   *   The message to display along with the assertion.
   * @param $group
   *   The type of assertion - examples are "Browser", "PHP".
   * @return
   *   The status passed in.
   */
  protected function assertFalse($value, $message = '', $group = 'Other') {
    return $this->_assert(!$value, $message ? $message : t('Value is FALSE'), $group);
  }

  /**
   * Check to see if a value is NULL.
   *
   * @param $value
   *   The value on which the assertion is to be done.
   * @param $message
   *   The message to display along with the assertion.
   * @param $group
   *   The type of assertion - examples are "Browser", "PHP".
   * @return
   *   The status passed in.
   */
  protected function assertNull($value, $message = '', $group = 'Other') {
    return $this->_assert(!isset($value), $message ? $message : t('Value is NULL'), $group);
  }

  /**
   * Check to see if a value is not NULL.
   *
   * @param $value
   *   The value on which the assertion is to be done.
   * @param $message
   *   The message to display along with the assertion.
   * @param $group
   *   The type of assertion - examples are "Browser", "PHP".
   * @return
   *   The status passed in.
   */
  protected function assertNotNull($value, $message = '', $group = 'Other') {
    return $this->_assert(isset($value), $message ? $message : t('Value is not NULL'), $group);
  }

  /**
   * Check to see if two values are equal.
   *
   * @param $first
   *   The first value to check.
   * @param $second
   *   The second value to check.
   * @param $message
   *   The message to display along with the assertion.
   * @param $group
   *   The type of assertion - examples are "Browser", "PHP".
   * @return
   *   The status passed in.
   */
  protected function assertEqual($first, $second, $message = '', $group = 'Other') {
    return $this->_assert($first == $second, $message ? $message : t('First value is equal to second value'), $group);
  }

  /**
   * Check to see if two values are not equal.
   *
   * @param $first
   *   The first value to check.
   * @param $second
   *   The second value to check.
   * @param $message
   *   The message to display along with the assertion.
   * @param $group
   *   The type of assertion - examples are "Browser", "PHP".
   * @return
   *   The status passed in.
   */
  protected function assertNotEqual($first, $second, $message = '', $group = 'Other') {
    return $this->_assert($first != $second, $message ? $message : t('First value is not equal to second value'), $group);
  }

  /**
   * Check to see if two values are identical.
   *
   * @param $first
   *   The first value to check.
   * @param $second
   *   The second value to check.
   * @param $message
   *   The message to display along with the assertion.
   * @param $group
   *   The type of assertion - examples are "Browser", "PHP".
   * @return
   *   The status passed in.
   */
  protected function assertIdentical($first, $second, $message = '', $group = 'Other') {
    return $this->_assert($first === $second, $message ? $message : t('First value is identical to second value'), $group);
  }

  /**
   * Check to see if two values are not identical.
   *
   * @param $first
   *   The first value to check.
   * @param $second
   *   The second value to check.
   * @param $message
   *   The message to display along with the assertion.
   * @param $group
   *   The type of assertion - examples are "Browser", "PHP".
   * @return
   *   The status passed in.
   */
  protected function assertNotIdentical($first, $second, $message = '', $group = 'Other') {
    return $this->_assert($first !== $second, $message ? $message : t('First value is not identical to second value'), $group);
  }

  /**
   * Fire an assertion that is always positive.
   *
   * @param $message
   *   The message to display along with the assertion.
   * @param $group
   *   The type of assertion - examples are "Browser", "PHP".
   * @return
   *   TRUE.
   */
  protected function pass($message = NULL, $group = 'Other') {
    return $this->_assert(TRUE, $message, $group);
  }

  /**
   * Fire an assertion that is always negative.
   *
   * @param $message
   *   The message to display along with the assertion.
   * @param $group
   *   The type of assertion - examples are "Browser", "PHP".
   * @return
   *   FALSE.
   */
  protected function fail($message = NULL, $group = 'Other') {
    return $this->_assert(FALSE, $message, $group);
  }

  /**
   * Fire an error assertion.
   *
   * @param $message
   *   The message to display along with the assertion.
   * @param $group
   *   The type of assertion - examples are "Browser", "PHP".
   * @param $caller
   *   The caller of the error.
   */
  protected function error($message = '', $group = 'Other', $caller = NULL) {
    return $this->_assert('exception', $message, $group, $caller);
  }

  /**
   * Run all tests in this class.
   */
  function run() {
    set_error_handler(array($this, 'errorHandler'));
    $methods = array();
    // Iterate through all the methods in this class.
    foreach (get_class_methods(get_class($this)) as $method) {
      // If the current method starts with "test", run it - it's a test.
      if (strtolower(substr($method, 0, 4)) == 'test') {
        $this->setUp();
        try {
          $this->$method();
          // Finish up.
        }
        catch (Exception $e) {
          $this->exceptionHandler($e);
        }
        $this->tearDown();
      }
    }
    // Clear out the error messages and restore error handler.
    drupal_get_messages();
    restore_error_handler();
  }

  /**
   * Handle errors.
   *
   * @see set_error_handler
   */
  function errorHandler($severity, $message, $file = NULL, $line = NULL) {
    if ($severity & error_reporting()) {
      $error_map = array(
        E_STRICT => 'Run-time notice',
        E_WARNING => 'Warning',
        E_NOTICE => 'Notice',
        E_CORE_ERROR => 'Core error',
        E_CORE_WARNING => 'Core warning',
        E_USER_ERROR => 'User error',
        E_USER_WARNING => 'User warning',
        E_USER_NOTICE => 'User notice',
        E_RECOVERABLE_ERROR => 'Recoverable error',
      );

      $backtrace = debug_backtrace();
      $this->error($message, $error_map[$severity], _drupal_get_last_caller($backtrace));
    }
    return TRUE;
  }

  /**
   * Handle exceptions.
   *
   * @see set_exception_handler
   */
  function exceptionHandler($exception) {
    $backtrace = $exception->getTrace();
    // Push on top of the backtrace the call that generated the exception.
    array_unshift($backtrace, array(
      'line' => $exception->getLine(),
      'file' => $exception->getFile(),
    ));
    $this->error($exception->getMessage(), 'Uncaught exception', _drupal_get_last_caller($backtrace));
  }

  /**
   * Creates a node based on default settings.
   *
   * @param $settings
   *   An associative array of settings to change from the defaults, keys are
   *   node properties, for example 'body' => 'Hello, world!'.
   * @return object Created node object.
   */
  function drupalCreateNode($settings = array()) {
    // Populate defaults array
    $defaults = array(
      'body'      => $this->randomName(32),
      'title'     => $this->randomName(8),
      'comment'   => 2,
      'changed'   => REQUEST_TIME,
      'format'    => FILTER_FORMAT_DEFAULT,
      'moderate'  => 0,
      'promote'   => 0,
      'revision'  => 1,
      'log'       => '',
      'status'    => 1,
      'sticky'    => 0,
      'type'      => 'page',
      'revisions' => NULL,
      'taxonomy'  => NULL,
    );
    $defaults['teaser'] = $defaults['body'];
    // If we already have a node, we use the original node's created time, and this
    if (isset($defaults['created'])) {
      $defaults['date'] = format_date($defaults['created'], 'custom', 'Y-m-d H:i:s O');
    }
    if (empty($settings['uid'])) {
      global $user;
      $defaults['uid'] = $user->uid;
    }
    $node = ($settings + $defaults);
    $node = (object)$node;

    node_save($node);

    // small hack to link revisions to our test user
    db_query('UPDATE {node_revisions} SET uid = %d WHERE vid = %d', $node->uid, $node->vid);
    return $node;
  }

  /**
   * Creates a custom content type based on default settings.
   *
   * @param $settings
   *   An array of settings to change from the defaults.
   *   Example: 'type' => 'foo'.
   * @return
   *   Created content type.
   */
  function drupalCreateContentType($settings = array()) {
    // find a non-existent random type name.
    do {
      $name = strtolower($this->randomName(3, 'type_'));
    } while (node_get_types('type', $name));

    // Populate defaults array
    $defaults = array(
      'type' => $name,
      'name' => $name,
      'description' => '',
      'help' => '',
      'min_word_count' => 0,
      'title_label' => 'Title',
      'body_label' => 'Body',
      'has_title' => 1,
      'has_body' => 1,
    );
    // imposed values for a custom type
    $forced = array(
      'orig_type' => '',
      'old_type' => '',
      'module' => 'node',
      'custom' => 1,
      'modified' => 1,
      'locked' => 0,
    );
    $type = $forced + $settings + $defaults;
    $type = (object)$type;

    $saved_type = node_type_save($type);
    node_types_rebuild();

    $this->assertEqual($saved_type, SAVED_NEW, t('Created content type %type.', array('%type' => $type->type)));

    // Reset permissions so that permissions for this content type are available.
    $this->checkPermissions(array(), TRUE);

    return $type;
  }

  /**
   * Get a list files that can be used in tests.
   *
   * @param $type
   *   File type, possible values: 'binary', 'html', 'image', 'javascript', 'php', 'sql', 'text'.
   * @param $size
   *   File size in bytes to match. Please check the tests/files folder.
   * @return
   *   List of files that match filter.
   */
  function drupalGetTestFiles($type, $size = NULL) {
    $files = array();

    // Make sure type is valid.
    if (in_array($type, array('binary', 'html', 'image', 'javascript', 'php', 'sql', 'text'))) {
     // Use original file directory instead of one created during setUp().
      $path = $this->original_file_directory . '/simpletest';
      $files = file_scan_directory($path, '/' . $type . '\-.*/');

      // If size is set then remove any files that are not of that size.
      if ($size !== NULL) {
        foreach ($files as $file) {
          $stats = stat($file->filename);
          if ($stats['size'] != $size) {
            unset($files[$file->filename]);
          }
        }
      }
    }
    usort($files, array($this, 'drupalCompareFiles'));
    return $files;
  }

  /**
   * Compare two files based on size and file name.
   */
  function drupalCompareFiles($file1, $file2) {
    // Determine which file is larger.
    $compare_size = (filesize($file1->filename) > filesize($file2->filename));
    if (!$compare_size) {
      // Both files were the same size, so return whichever one is alphabetically greater.
      return strnatcmp($file1->name, $file2->name);
    }
    else {
      // Return TRUE if $file1 is larger than $file2.
      return $compare_size;
    }
  }

  /**
   * Generates a random string.
   *
   * @param $number
   *   Number of characters in length to append to the prefix.
   * @param $prefix
   *   Prefix to use.
   * @return
   *   Randomly generated string.
   */
  function randomName($number = 4, $prefix = 'simpletest_') {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_';
    for ($x = 0; $x < $number; $x++) {
      $prefix .= $chars{mt_rand(0, strlen($chars) - 1)};
      if ($x == 0) {
        $chars .= '0123456789';
      }
    }
    return $prefix;
  }

  /**
   * Create a user with a given set of permissions. The permissions correspond to the
   * names given on the privileges page.
   *
   * @param $permissions
   *   Array of permission names to assign to user.
   * @return
   *   A fully loaded user object with pass_raw property, or FALSE if account
   *   creation fails.
   */
  function drupalCreateUser($permissions = NULL) {
    // Create a role with the given permission set.
    if (!($rid = $this->_drupalCreateRole($permissions))) {
      return FALSE;
    }

    // Create a user assigned to that role.
    $edit = array();
    $edit['name']   = $this->randomName();
    $edit['mail']   = $edit['name'] . '@example.com';
    $edit['roles']  = array($rid => $rid);
    $edit['pass']   = user_password();
    $edit['status'] = 1;

    $account = user_save('', $edit);

    $this->assertTrue(!empty($account->uid), t('User created with name %name and pass %pass', array('%name' => $edit['name'], '%pass' => $edit['pass'])), t('User login'));
    if (empty($account->uid)) {
      return FALSE;
    }

    // Add the raw password so that we can log in as this user.
    $account->pass_raw = $edit['pass'];
    return $account;
  }

  /**
   * Internal helper function; Create a role with specified permissions.
   *
   * @param $permissions
   *   Array of permission names to assign to role.
   * @return
   *   Role ID of newly created role, or FALSE if role creation failed.
   */
  private function _drupalCreateRole($permissions = NULL) {
    // Generate string version of permissions list.
    if ($permissions === NULL) {
      $permissions = array('access comments', 'access content', 'post comments', 'post comments without approval');
    }

    if (!$this->checkPermissions($permissions)) {
      return FALSE;
    }

    // Create new role.
    $role_name = $this->randomName();
    db_query("INSERT INTO {role} (name) VALUES ('%s')", $role_name);
    $role = db_fetch_object(db_query("SELECT * FROM {role} WHERE name = '%s'", $role_name));
    $this->assertTrue($role, t('Created role of name: @role_name, id: @rid', array('@role_name' => $role_name, '@rid' => (isset($role->rid) ? $role->rid : t('-n/a-')))), t('Role'));
    if ($role && !empty($role->rid)) {
      // Assign permissions to role and mark it for clean-up.
      foreach ($permissions as $permission_string) {
        db_query("INSERT INTO {role_permission} (rid, permission) VALUES (%d, '%s')", $role->rid, $permission_string);
      }
      $count = db_result(db_query("SELECT COUNT(*) FROM {role_permission} WHERE rid = %d", $role->rid));
      $this->assertTrue($count == count($permissions), t('Created permissions: @perms', array('@perms' => implode(', ', $permissions))), t('Role'));
      return $role->rid;
    }
    else {
      return FALSE;
    }
  }

  /**
   * Check to make sure that the array of permissions are valid.
   *
   * @param $permissions
   *   Permissions to check.
   * @param $reset
   *   Reset cached available permissions.
   * @return
   *   TRUE or FALSE depending on whether the permissions are valid.
   */
  private function checkPermissions(array $permissions, $reset = FALSE) {
    static $available;

    if (!isset($available) || $reset) {
      $available = array_keys(module_invoke_all('perm'));
    }

    $valid = TRUE;
    foreach ($permissions as $permission) {
      if (!in_array($permission, $available)) {
        $this->fail(t('Invalid permission %permission.', array('%permission' => $permission)), t('Role'));
        $valid = FALSE;
      }
    }
    return $valid;
  }

  /**
   * Logs in a user with the internal browser. If already logged in then logs the current
   * user out before logging in the specified user. If no user is specified then a new
   * user will be created and logged in.
   *
   * @param $user
   *   User object representing the user to login.
   * @return
   *   User that was logged in. Useful if no user was passed in order to retrieve
   *   the created user.
   */
  function drupalLogin($user = NULL) {
    if ($this->_logged_in) {
      $this->drupalLogout();
    }

    if (!isset($user)) {
      $user = $this->_drupalCreateRole();
    }

    $edit = array(
      'name' => $user->name,
      'pass' => $user->pass_raw
    );
    $this->drupalPost('user', $edit, t('Log in'));

    $pass = $this->assertText($user->name, t('Found name: %name', array('%name' => $user->name)), t('User login'));
    $pass = $pass && $this->assertNoText(t('The username %name has been blocked.', array('%name' => $user->name)), t('No blocked message at login page'), t('User login'));
    $pass = $pass && $this->assertNoText(t('The name %name is a reserved username.', array('%name' => $user->name)), t('No reserved message at login page'), t('User login'));

    $this->_logged_in = $pass;

    return $user;
  }

  /*
   * Logs a user out of the internal browser, then check the login page to confirm logout.
   */
  function drupalLogout() {
    // Make a request to the logout page.
    $this->drupalGet('logout');

    // Load the user page, the idea being if you were properly logged out you should be seeing a login screen.
    $this->drupalGet('user');
    $pass = $this->assertField('name', t('Username field found.'), t('Logout'));
    $pass = $pass && $this->assertField('pass', t('Password field found.'), t('Logout'));

    $this->_logged_in = !$pass;
  }

  /**
   * Generates a random database prefix, runs the install scripts on the
   * prefixed database and enable the specified modules. After installation
   * many caches are flushed and the internal browser is setup so that the
   * page requests will run on the new prefix. A temporary files directory
   * is created with the same name as the database prefix.
   *
   * @param ...
   *   List of modules to enable for the duration of the test.
   */
  function setUp() {
    global $db_prefix;

    // Store necessary current values before switching to prefixed database.
    $this->db_prefix_original = $db_prefix;
    $clean_url_original = variable_get('clean_url', 0);

    // Generate temporary prefixed database to ensure that tests have a clean starting point.
    $db_prefix = 'simpletest' . mt_rand(1000, 1000000);

    include_once DRUPAL_ROOT . '/includes/install.inc';
    drupal_install_system();

    // Add the specified modules to the list of modules in the default profile.
    $args = func_get_args();
    $modules = array_unique(array_merge(drupal_get_profile_modules('default', 'en'), $args));
    drupal_install_modules($modules);

    // Because the schema is static cached, we need to flush
    // it between each run.  If we don't, then it will contain
    // stale data for the previous run's database prefix and all
    // calls to it will fail.
    drupal_get_schema(NULL, TRUE);

    // Run default profile tasks.
    $task = 'profile';
    default_profile_tasks($task, '');

    // Rebuild caches.
    menu_rebuild();
    actions_synchronize();
    _drupal_flush_css_js();
    $this->refreshVariables();
    $this->checkPermissions(array(), TRUE);

    // Restore necessary variables.
    variable_set('install_profile', 'default');
    variable_set('install_task', 'profile-finished');
    variable_set('clean_url', $clean_url_original);

    // Use temporary files directory with the same prefix as database.
    $this->original_file_directory = file_directory_path();
    variable_set('file_directory_path', file_directory_path() . '/' . $db_prefix);
    $directory = file_directory_path();
    file_check_directory($directory, FILE_CREATE_DIRECTORY); // Create the files directory.
  }

  /**
   * Refresh the in-memory set of variables. Useful after a page request is made
   * that changes a variable in a different thread.
   *
   * In other words calling a settings page with $this->drupalPost() with a changed
   * value would update a variable to reflect that change, but in the thread that
   * made the call (thread running the test) the changed variable would not be
   * picked up.
   *
   * This method clears the variables cache and loads a fresh copy from the database
   * to ensure that the most up-to-date set of variables is loaded.
   */
  function refreshVariables() {
    global $conf;
    cache_clear_all('variables', 'cache');
    $conf = variable_init();
  }

  /**
   * Delete created files and temporary files directory, delete the tables created by setUp(),
   * and reset the database prefix.
   */
  function tearDown() {
    global $db_prefix;
    if (preg_match('/simpletest\d+/', $db_prefix)) {
      // Delete temporary files directory and reset files directory path.
      simpletest_clean_temporary_directory(file_directory_path());
      variable_set('file_directory_path', $this->original_file_directory);

      // Remove all prefixed tables (all the tables in the schema).
      $schema = drupal_get_schema(NULL, TRUE);
      $ret = array();
      foreach ($schema as $name => $table) {
        db_drop_table($ret, $name);
      }

      // Return the database prefix to the original.
      $db_prefix = $this->db_prefix_original;

      // Ensure that the internal logged in variable is reset.
      $this->_logged_in = FALSE;

      // Reload module list to ensure that test module hooks aren't called after tests.
      module_list(TRUE);

      // Rebuild caches.
      $this->refreshVariables();

      // Close the CURL handler.
      $this->curlClose();
    }
  }

  /**
   * Initializes the cURL connection and gets a session cookie.
   *
   * This function will add authentication headers as specified in
   * simpletest_httpauth_username and simpletest_httpauth_pass variables.
   * Also, see the description of $curl_options among the properties.
   */
  protected function curlConnect() {
    global $base_url, $db_prefix;
    if (!isset($this->ch)) {
      $this->ch = curl_init();
      $curl_options = $this->curl_options + array(
        CURLOPT_COOKIEJAR => $this->cookie_file,
        CURLOPT_URL => $base_url,
        CURLOPT_FOLLOWLOCATION => TRUE,
        CURLOPT_RETURNTRANSFER => TRUE,
        CURLOPT_SSL_VERIFYPEER => FALSE,  // Required to make the tests run on https://
        CURLOPT_SSL_VERIFYHOST => FALSE,  // Required to make the tests run on https://
      );
      if (preg_match('/simpletest\d+/', $db_prefix)) {
        $curl_options[CURLOPT_USERAGENT] = $db_prefix;
      }
      if (!isset($curl_options[CURLOPT_USERPWD]) && ($auth = variable_get('simpletest_httpauth_username', ''))) {
        if ($pass = variable_get('simpletest_httpauth_pass', '')) {
          $auth .= ':' . $pass;
        }
        $curl_options[CURLOPT_USERPWD] = $auth;
      }
      return $this->curlExec($curl_options);
    }
  }

  /**
   * Performs a cURL exec with the specified options after calling curlConnect().
   *
   * @param
   *   $curl_options Custom cURL options.
   * @return
   *   Content returned from the exec.
   */
  protected function curlExec($curl_options) {
    $this->curlConnect();
    $url = empty($curl_options[CURLOPT_URL]) ? curl_getinfo($this->ch, CURLINFO_EFFECTIVE_URL) : $curl_options[CURLOPT_URL];
    curl_setopt_array($this->ch, $this->curl_options + $curl_options);
    $this->drupalSetContent(curl_exec($this->ch), curl_getinfo($this->ch, CURLINFO_EFFECTIVE_URL));
    $this->assertTrue($this->_content !== FALSE, t('!method to !url, response is !length bytes.', array('!method' => !empty($curl_options[CURLOPT_NOBODY]) ? 'HEAD' : (empty($curl_options[CURLOPT_POSTFIELDS]) ? 'GET' : 'POST'), '!url' => $url, '!length' => strlen($this->_content))), t('Browser'));
    return $this->drupalGetContent();
  }

  /**
   * Close the cURL handler and unset the handler.
   */
  protected function curlClose() {
    if (isset($this->ch)) {
      curl_close($this->ch);
      unset($this->ch);
    }
  }

  /**
   * Parse content returned from curlExec using DOM and SimpleXML.
   *
   * @return
   *   A SimpleXMLElement or FALSE on failure.
   */
  protected function parse() {
    if (!$this->elements) {
      // DOM can load HTML soup. But, HTML soup can throw warnings, supress
      // them.
      @$htmlDom = DOMDocument::loadHTML($this->_content);
      if ($htmlDom) {
        $this->pass(t('Valid HTML found on "@path"', array('@path' => $this->getUrl())), t('Browser'));
        // It's much easier to work with simplexml than DOM, luckily enough
        // we can just simply import our DOM tree.
        $this->elements = simplexml_import_dom($htmlDom);
      }
    }
    if (!$this->elements) {
      $this->fail(t('Parsed page successfully.'), t('Browser'));
    }

    return $this->elements;
  }

  /**
   * Retrieves a Drupal path or an absolute path.
   *
   * @param $path
   *   Drupal path or URL to load into internal browser
   * @param $options
   *  Options to be forwarded to url().
   * @return
   *  The retrieved HTML string, also available as $this->drupalGetContent()
   */
  function drupalGet($path, $options = array()) {
    $options['absolute'] = TRUE;

    // We re-using a CURL connection here.  If that connection still has certain
    // options set, it might change the GET into a POST.  Make sure we clear out
    // previous options.
    $out = $this->curlExec(array(CURLOPT_HTTPGET => TRUE, CURLOPT_URL => url($path, $options), CURLOPT_HEADER => FALSE, CURLOPT_NOBODY => FALSE));
    $this->refreshVariables(); // Ensure that any changes to variables in the other thread are picked up.

    // Replace original page output with new output from redirected page(s).
    if (($new = $this->checkForMetaRefresh())) {
      $out = $new;
    }
    return $out;
  }

  /**
   * Execute a POST request on a Drupal page.
   * It will be done as usual POST request with SimpleBrowser.
   *
   * @param $path
   *   Location of the post form. Either a Drupal path or an absolute path or
   *   NULL to post to the current page. For multi-stage forms you can set the
   *   path to NULL and have it post to the last received page. Example:
   *
   *   // First step in form.
   *   $edit = array(...);
   *   $this->drupalPost('some_url', $edit, t('Save'));
   *
   *   // Second step in form.
   *   $edit = array(...);
   *   $this->drupalPost(NULL, $edit, t('Save'));
   * @param  $edit
   *   Field data in an assocative array. Changes the current input fields
   *   (where possible) to the values indicated. A checkbox can be set to
   *   TRUE to be checked and FALSE to be unchecked. Note that when a form
   *   contains file upload fields, other fields cannot start with the '@'
   *   character.
   *
   *   Multiple select fields can be set using name[] and setting each of the
   *   possible values. Example:
   *   $edit = array();
   *   $edit['name[]'] = array('value1', 'value2');
   * @param $submit
   *   Value of the submit button.
   * @param $options
   *   Options to be forwarded to url().
   */
  function drupalPost($path, $edit, $submit, $options = array()) {
    $submit_matches = FALSE;
    if (isset($path)) {
      $html = $this->drupalGet($path, $options);
    }
    if ($this->parse()) {
      $edit_save = $edit;
      // Let's iterate over all the forms.
      $forms = $this->xpath('//form');
      foreach ($forms as $form) {
        // We try to set the fields of this form as specified in $edit.
        $edit = $edit_save;
        $post = array();
        $upload = array();
        $submit_matches = $this->handleForm($post, $edit, $upload, $submit, $form);
        $action = isset($form['action']) ? $this->getAbsoluteUrl($form['action']) : $this->getUrl();

        // We post only if we managed to handle every field in edit and the
        // submit button matches.
        if (!$edit && $submit_matches) {
          if ($upload) {
            // TODO: cURL handles file uploads for us, but the implementation
            // is broken. This is a less than elegant workaround. Alternatives
            // are being explored at #253506.
            foreach ($upload as $key => $file) {
              $post[$key] = '@' . realpath($file);
            }
          }
          else {
            foreach ($post as $key => $value) {
              // Encode according to application/x-www-form-urlencoded
              // Both names and values needs to be urlencoded, according to
              // http://www.w3.org/TR/html4/interact/forms.html#h-17.13.4.1
              $post[$key] = urlencode($key) . '=' . urlencode($value);
            }
            $post = implode('&', $post);
          }
          $out = $this->curlExec(array(CURLOPT_URL => $action, CURLOPT_POST => TRUE, CURLOPT_POSTFIELDS => $post, CURLOPT_HEADER => FALSE));
          // Ensure that any changes to variables in the other thread are picked up.
          $this->refreshVariables();

          // Replace original page output with new output from redirected page(s).
          if (($new = $this->checkForMetaRefresh())) {
            $out = $new;
          }
          return $out;
        }
      }
      // We have not found a form which contained all fields of $edit.
      foreach ($edit as $name => $value) {
        $this->fail(t('Failed to set field @name to @value', array('@name' => $name, '@value' => $value)));
      }
      $this->assertTrue($submit_matches, t('Found the @submit button', array('@submit' => $submit)));
      $this->fail(t('Found the requested form fields at @path', array('@path' => $path)));
    }
  }

  /**
   * Check for meta refresh tag and if found call drupalGet() recursively. This
   * function looks for the http-equiv attribute to be set to "Refresh"
   * and is case-sensitive.
   *
   * @return
   *   Either the new page content or FALSE.
   */
  private function checkForMetaRefresh() {
    if ($this->drupalGetContent() != '' && $this->parse()) {
      $refresh = $this->xpath('//meta[@http-equiv="Refresh"]');
      if (!empty($refresh)) {
        // Parse the content attribute of the meta tag for the format:
        // "[delay]: URL=[page_to_redirect_to]".
        if (preg_match('/\d+;\s*URL=(?P<url>.*)/i', $refresh[0]['content'], $match)) {
          return $this->drupalGet($this->getAbsoluteUrl(decode_entities($match['url'])));
        }
      }
    }
    return FALSE;
  }

  /**
   * Retrieves only the headers for a Drupal path or an absolute path.
   *
   * @param $path
   *   Drupal path or URL to load into internal browser
   * @param $options
   *   Options to be forwarded to url().
   * @return
   *   The retrieved headers, also available as $this->drupalGetContent()
   */
  function drupalHead($path, $options = array()) {
    $options['absolute'] = TRUE;
    $out = $this->curlExec(array(CURLOPT_HEADER => TRUE, CURLOPT_NOBODY => TRUE, CURLOPT_URL => url($path, $options)));
    $this->refreshVariables(); // Ensure that any changes to variables in the other thread are picked up.
    return $out;
  }

  /**
   * Handle form input related to drupalPost(). Ensure that the specified fields
   * exist and attempt to create POST data in the correct manner for the particular
   * field type.
   *
   * @param $post
   *   Reference to array of post values.
   * @param $edit
   *   Reference to array of edit values to be checked against the form.
   * @param $submit
   *   Form submit button value.
   * @param $form
   *   Array of form elements.
   * @return
   *   Submit value matches a valid submit input in the form.
   */
  protected function handleForm(&$post, &$edit, &$upload, $submit, $form) {
    // Retrieve the form elements.
    $elements = $form->xpath('.//input|.//textarea|.//select');
    $submit_matches = FALSE;
    foreach ($elements as $element) {
      // SimpleXML objects need string casting all the time.
      $name = (string) $element['name'];
      // This can either be the type of <input> or the name of the tag itself
      // for <select> or <textarea>.
      $type = isset($element['type']) ? (string)$element['type'] : $element->getName();
      $value = isset($element['value']) ? (string)$element['value'] : '';
      $done = FALSE;
      if (isset($edit[$name])) {
        switch ($type) {
          case 'text':
          case 'textarea':
          case 'password':
            $post[$name] = $edit[$name];
            unset($edit[$name]);
            break;
          case 'radio':
            if ($edit[$name] == $value) {
              $post[$name] = $edit[$name];
              unset($edit[$name]);
            }
            break;
          case 'checkbox':
            // To prevent checkbox from being checked.pass in a FALSE,
            // otherwise the checkbox will be set to its value regardless
            // of $edit.
            if ($edit[$name] === FALSE) {
              unset($edit[$name]);
              continue 2;
            }
            else {
              unset($edit[$name]);
              $post[$name] = $value;
            }
            break;
          case 'select':
            $new_value = $edit[$name];
            $index = 0;
            $key = preg_replace('/\[\]$/', '', $name);
            $options = $this->getAllOptions($element);
            foreach ($options as $option) {
              if (is_array($new_value)) {
                $option_value= (string)$option['value'];
                if (in_array($option_value, $new_value)) {
                  $post[$key . '[' . $index++ . ']'] = $option_value;
                  $done = TRUE;
                  unset($edit[$name]);
                }
              }
              elseif ($new_value == $option['value']) {
                $post[$name] = $new_value;
                unset($edit[$name]);
                $done = TRUE;
              }
            }
            break;
          case 'file':
            $upload[$name] = $edit[$name];
            unset($edit[$name]);
            break;
        }
      }
      if (!isset($post[$name]) && !$done) {
        switch ($type) {
          case 'textarea':
            $post[$name] = (string)$element;
            break;
          case 'select':
            $single = empty($element['multiple']);
            $first = TRUE;
            $index = 0;
            $key = preg_replace('/\[\]$/', '', $name);
            $options = $this->getAllOptions($element);
            foreach ($options as $option) {
              // For single select, we load the first option, if there is a
              // selected option that will overwrite it later.
              if ($option['selected'] || ($first && $single)) {
                $first = FALSE;
                if ($single) {
                  $post[$name] = (string)$option['value'];
                }
                else {
                  $post[$key . '[' . $index++ . ']'] = (string)$option['value'];
                }
              }
            }
            break;
          case 'file':
            break;
          case 'submit':
          case 'image':
            if ($submit == $value) {
              $post[$name] = $value;
              $submit_matches = TRUE;
            }
            break;
          case 'radio':
          case 'checkbox':
            if (!isset($element['checked'])) {
              break;
            }
            // Deliberate no break.
          default:
            $post[$name] = $value;
        }
      }
    }
    return $submit_matches;
  }

  /**
   * Peform an xpath search on the contents of the internal browser. The search
   * is relative to the root element (HTML tag normally) of the page.
   *
   * @param $xpath
   *   The xpath string to use in the search.
   * @return
   *   The return value of the xpath search. For details on the xpath string
   *   format and return values see the SimpleXML documentation.
   *   http://us.php.net/manual/function.simplexml-element-xpath.php
   */
  public function xpath($xpath) {
    if ($this->parse()) {
      return $this->elements->xpath($xpath);
    }
    return FALSE;
  }

  /**
   * Get all option elements, including nested options, in a select.
   *
   * @param $element
   *   The element for which to get the options.
   * @return
   *   Option elements in select.
   */
  private function getAllOptions(SimpleXMLElement $element) {
    $options = array();
    // Add all options items.
    foreach ($element->option as $option) {
      $options[] = $option;
    }

    // Search option group children.
    if (isset($element->optgroup)) {
      foreach ($element->optgroup as $group) {
        $options = array_merge($options, $this->getAllOptions($group));
      }
    }
    return $options;
  }

  /**
   * Pass if a link with the specified label is found, and optional with the
   * specified index.
   *
   * @param $label
   *   Text between the anchor tags.
   * @param $index
   *   Link position counting from zero.
   * @param $message
   *   Message to display.
   * @param $group
   *   The group this message belongs to, defaults to 'Other'.
   */
  public function assertLink($label, $index = 0, $message = '', $group = 'Other') {
    $links = $this->xpath('//a[text()="' . $label . '"]');
    $message = ($message ?  $message : t('Link with label "!label" found.', array('!label' => $label)));
    $this->_assert(isset($links[$index]), $message, $group);
  }

  /**
   * Pass if a link with the specified label is not found.
   *
   * @param $label
   *   Text between the anchor tags.
   * @param $index
   *   Link position counting from zero.
   * @param $message
   *   Message to display.
   * @param $group
   *   The group this message belongs to, defaults to 'Other'.
   */
  public function assertNoLink($label, $message = '', $group = 'Other') {
    $links = $this->xpath('//a[text()="' . $label . '"]');
    $message = ($message ?  $message : t('Link with label "!label" not found.', array('!label' => $label)));
    $this->_assert(empty($links), $message, $group);
  }

  /**
   * Follows a link by name.
   *
   * Will click the first link found with this link text by default, or a
   * later one if an index is given. Match is case insensitive with
   * normalized space. The label is translated label. There is an assert
   * for successful click.
   *
   * @param $label
   *   Text between the anchor tags.
   * @param $index
   *   Link position counting from zero.
   * @return
   *   Page on success, or FALSE on failure.
   */
  function clickLink($label, $index = 0) {
    $url_before = $this->getUrl();
    $urls = $this->xpath('//a[text()="' . $label . '"]');

    if (isset($urls[$index])) {
      $url_target = $this->getAbsoluteUrl($urls[$index]['href']);
    }

    $this->assertTrue(isset($urls[$index]), t('Clicked link "!label" (!url_target) from !url_before', array('!label' => $label, '!url_target' => $url_target, '!url_before' => $url_before)), t('Browser'));

    if (isset($urls[$index])) {
      return $this->drupalGet($url_target);
    }
    return FALSE;
  }

  /**
   * Takes a path and returns an absolute path.
   *
   * @param $path
   *   The path, can be a Drupal path or a site-relative path. It might have a
   *   query, too. Can even be an absolute path which is just passed through.
   * @return
   *   An absolute path.
   */
  function getAbsoluteUrl($path) {
    $options = array('absolute' => TRUE);
    $parts = parse_url($path);
    // This is more crude than the menu_is_external but enough here.
    if (empty($parts['host'])) {
      $path = $parts['path'];
      $base_path = base_path();
      $n = strlen($base_path);
      if (substr($path, 0, $n) == $base_path) {
        $path = substr($path, $n);
      }
      if (isset($parts['query'])) {
        $options['query'] = $parts['query'];
      }
      $path = url($path, $options);
    }
    return $path;
  }

  /**
   * Get the current url from the cURL handler.
   *
   * @return
   *   The current url.
   */
  function getUrl() {
    return $this->_url;
  }

  /**
   * Gets the current raw HTML of requested page.
   */
  function drupalGetContent() {
    return $this->_content;
  }

  /**
   * Sets the raw HTML content. This can be useful when a page has been fetched
   * outside of the internal browser and assertions need to be made on the
   * returned page.
   *
   * A good example would be when testing drupal_http_request(). After fetching
   * the page the content can be set and page elements can be checked to ensure
   * that the function worked properly.
   */
  function drupalSetContent($content, $url = 'internal:') {
    $this->_content = $content;
    $this->_url = $url;
    $this->plain_text = FALSE;
    $this->elements = FALSE;
  }

  /**
   * Pass if the raw text IS found on the loaded page, fail otherwise. Raw text
   * refers to the raw HTML that the page generated.
   *
   * @param $raw
   *  Raw (HTML) string to look for.
   * @param $message
   *   Message to display.
   * @param $group
   *   The group this message belongs to, defaults to 'Other'.
   * @return
   *   TRUE on pass, FALSE on fail.
   */
  function assertRaw($raw, $message = '%s found', $group = 'Other') {
    return $this->_assert(strpos($this->_content, $raw) !== FALSE, $message, $group);
  }

  /**
   * Pass if the raw text is NOT found on the loaded page, fail otherwise. Raw text
   * refers to the raw HTML that the page generated.
   *
   * @param $raw
   *   Raw (HTML) string to look for.
   * @param $message
   *   Message to display.
   * @param $group
   *   The group this message belongs to, defaults to 'Other'.
   * @return
   *   TRUE on pass, FALSE on fail.
   */
  function assertNoRaw($raw, $message = '%s found', $group = 'Other') {
    return $this->_assert(strpos($this->_content, $raw) === FALSE, $message, $group);
  }

  /**
   * Pass if the text IS found on the text version of the page. The text version
   * is the equivilent of what a user would see when viewing through a web browser.
   * In other words the HTML has been filtered out of the contents.
   *
   * @param $text
   *  Plain text to look for.
   * @param $message
   *   Message to display.
   * @param $group
   *   The group this message belongs to, defaults to 'Other'.
   * @return
   *   TRUE on pass, FALSE on fail.
   */
  function assertText($text, $message = '', $group = 'Other') {
    return $this->assertTextHelper($text, $message, $group, FALSE);
  }

  /**
   * Pass if the text is NOT found on the text version of the page. The text version
   * is the equivilent of what a user would see when viewing through a web browser.
   * In other words the HTML has been filtered out of the contents.
   *
   * @param $text
   *   Plain text to look for.
   * @param $message
   *   Message to display.
   * @param $group
   *   The group this message belongs to, defaults to 'Other'.
   * @return
   *   TRUE on pass, FALSE on fail.
   */
  function assertNoText($text, $message = '', $group = 'Other') {
    return $this->assertTextHelper($text, $message, $group, TRUE);
  }

  /**
   * Helper for assertText and assertNoText.
   *
   * It is not recommended to call this function directly.
   *
   * @param $text
   *   Plain text to look for.
   * @param $message
   *   Message to display.
   * @param $group
   *   The group this message belongs to.
   * @param $not_exists
   *   TRUE if this text should not exist, FALSE if it should.
   * @return
   *   TRUE on pass, FALSE on fail.
   */
  protected function assertTextHelper($text, $message, $group, $not_exists) {
    if ($this->plain_text === FALSE) {
      $this->plain_text = filter_xss($this->_content, array());
    }
    if (!$message) {
      $message = '"' . $text . '"' . ($not_exists ? ' not found' : ' found');
    }
    return $this->_assert($not_exists == (strpos($this->plain_text, $text) === FALSE), $message, $group);
  }

  /**
   * Will trigger a pass if the Perl regex pattern is found in the raw content.
   *
   * @param $pattern
   *   Perl regex to look for including the regex delimiters.
   * @param $message
   *   Message to display.
   * @param $group
   *   The group this message belongs to.
   * @return
   *   TRUE on pass, FALSE on fail.
   */
  function assertPattern($pattern, $message = 'Pattern %s found', $group = 'Other') {
    return $this->_assert((bool) preg_match($pattern, $this->drupalGetContent()), $message, $group);
  }

  /**
   * Will trigger a pass if the perl regex pattern is not present in raw content.
   *
   * @param $pattern
   *   Perl regex to look for including the regex delimiters.
   * @param $message
   *   Message to display.
   * @param $group
   *   The group this message belongs to.
   * @return
   *   TRUE on pass, FALSE on fail.
   */
  function assertNoPattern($pattern, $message = 'Pattern %s not found', $group = 'Other') {
    return $this->_assert(!preg_match($pattern, $this->drupalGetContent()), $message, $group);
  }

  /**
   * Pass if the page title is the given string.
   *
   * @param $title
   *  The string the title should be.
   * @param $message
   *   Message to display.
   * @param $group
   *   The group this message belongs to.
   * @return
   *   TRUE on pass, FALSE on fail.
   */
  function assertTitle($title, $message, $group = 'Other') {
    return $this->_assert($this->xpath('//title[text()="' . $title . '"]') !== FALSE, $message, $group);
  }

  /**
   * Assert that a field exists in the current page by the given XPath.
   *
   * @param $xpath
   *   XPath used to find the field.
   * @param $value
   *   Value of the field to assert.
   * @param $message
   *   Message to display.
   * @param $group
   *   The group this message belongs to.
   * @return
   *   TRUE on pass, FALSE on fail.
   */
  function assertFieldByXPath($xpath, $value, $message, $group = 'Other') {
    $fields = $this->xpath($xpath);

    // If value specified then check array for match.
    $found = TRUE;
    if ($value) {
      $found = FALSE;
      if ($fields) {
        foreach ($fields as $field) {
          if (isset($field['value']) && $field['value'] == $value) {
            // Input element with correct value.
            $found = TRUE;
          }
          elseif (isset($field->option)) {
            // Select element found.
            if ($this->getSelectedItem($field) == $value) {
              $found = TRUE;
            }
            else {
              // No item selected so use first item.
              $items = $this->getAllOptions($field);
              if (!empty($items) && $items[0]['value'] == $value) {
                $found = TRUE;
              }
            }
          }
          elseif (isset($field[0]) && $field[0] == $value) {
            // Text area with correct text.
            $found = TRUE;
          }
        }
      }
    }
    return $this->assertTrue($fields && $found, $message, $group);
  }

  /**
   * Get the selected value from a select field.
   *
   * @param $element
   *   SimpleXMLElement select element.
   * @return
   *   The selected value or FALSE.
   */
  function getSelectedItem(SimpleXMLElement $element) {
    foreach ($element->children() as $item) {
      if (isset($item['selected'])) {
        return $item['value'];
      }
      elseif ($item->getName() == 'optgroup') {
        if ($value = $this->getSelectedItem($item)) {
          return $value;
        }
      }
    }
    return FALSE;
  }

  /**
   * Assert that a field does not exist in the current page by the given XPath.
   *
   * @param $xpath
   *   XPath used to find the field.
   * @param $value
   *   Value of the field to assert.
   * @param $message
   *   Message to display.
   * @param $group
   *   The group this message belongs to.
   * @return
   *   TRUE on pass, FALSE on fail.
   */
  function assertNoFieldByXPath($xpath, $value, $message, $group = 'Other') {
    $fields = $this->xpath($xpath);

    // If value specified then check array for match.
    $found = TRUE;
    if ($value) {
      $found = FALSE;
      if ($fields) {
        foreach ($fields as $field) {
          if ($field['value'] == $value) {
            $found = TRUE;
          }
        }
      }
    }
    return $this->assertFalse($fields && $found, $message, $group);
  }

  /**
   * Assert that a field exists in the current page with the given name and value.
   *
   * @param $name
   *   Name of field to assert.
   * @param $value
   *   Value of the field to assert.
   * @param $message
   *   Message to display.
   * @param $group
   *   The group this message belongs to.
   * @return
   *   TRUE on pass, FALSE on fail.
   */
  function assertFieldByName($name, $value = '', $message = '') {
    return $this->assertFieldByXPath($this->_constructFieldXpath('name', $name), $value, $message ? $message : t('Found field by name @name', array('@name' => $name)), t('Browser'));
  }

  /**
   * Assert that a field does not exist with the given name and value.
   *
   * @param $name
   *   Name of field to assert.
   * @param $value
   *   Value of the field to assert.
   * @param $message
   *   Message to display.
   * @param $group
   *   The group this message belongs to.
   * @return
   *   TRUE on pass, FALSE on fail.
   */
  function assertNoFieldByName($name, $value = '', $message = '') {
    return $this->assertNoFieldByXPath($this->_constructFieldXpath('name', $name), $value, $message ? $message : t('Did not find field by name @name', array('@name' => $name)), t('Browser'));
  }

  /**
   * Assert that a field exists in the current page with the given id and value.
   *
   * @param $id
   *  Id of field to assert.
   * @param $value
   *   Value of the field to assert.
   * @param $message
   *   Message to display.
   * @param $group
   *   The group this message belongs to.
   * @return
   *   TRUE on pass, FALSE on fail.
   */
  function assertFieldById($id, $value = '', $message = '') {
    return $this->assertFieldByXPath($this->_constructFieldXpath('id', $id), $value, $message ? $message : t('Found field by id @id', array('@id' => $id)), t('Browser'));
  }

  /**
   * Assert that a field does not exist with the given id and value.
   *
   * @param $id
   *  Id of field to assert.
   * @param $value
   *   Value of the field to assert.
   * @param $message
   *   Message to display.
   * @param $group
   *   The group this message belongs to.
   * @return
   *   TRUE on pass, FALSE on fail.
   */
  function assertNoFieldById($id, $value = '', $message = '') {
    return $this->assertNoFieldByXPath($this->_constructFieldXpath('id', $id), $value, $message ? $message : t('Did not find field by id @id', array('@id' => $id)), t('Browser'));
  }

  /**
   * Assert that a field exists with the given name or id.
   *
   * @param $field
   *  Name or id of field to assert.
   * @param $message
   *   Message to display.
   * @param $group
   *   The group this message belongs to.
   * @return
   *   TRUE on pass, FALSE on fail.
   */
  function assertField($field, $message = '', $group = 'Other') {
    return $this->assertFieldByXPath($this->_constructFieldXpath('name', $field) . '|' . $this->_constructFieldXpath('id', $field), '', $message, $group);
  }

  /**
   * Assert that a field does not exist with the given name or id.
   *
   * @param $field
   *  Name or id of field to assert.
   * @param $message
   *   Message to display.
   * @param $group
   *   The group this message belongs to.
   * @return
   *   TRUE on pass, FALSE on fail.
   */
  function assertNoField($field, $message = '', $group = 'Other') {
    return $this->assertNoFieldByXPath($this->_constructFieldXpath('name', $field) . '|' . $this->_constructFieldXpath('id', $field), '', $message, $group);
  }

  /**
   * Construct an XPath for the given set of attributes and value.
   *
   * @param $attribute
   *  Field attributes.
   * @param $value
   *  Value of field.
   * @return
   *  XPath for specified values.
   */
  function _constructFieldXpath($attribute, $value) {
    return '//textarea[@' . $attribute . '="' . $value . '"]|//input[@' . $attribute . '="' . $value . '"]|//select[@' . $attribute . '="' . $value . '"]';
  }

  /**
   * Assert the page responds with the specified response code.
   *
   * @param $code
   *   Reponse code. For example 200 is a successful page request. For a list
   *   of all codes see http://www.w3.org/Protocols/rfc2616/rfc2616-sec10.html.
   * @param $message
   *   Message to display.
   * @return
   *   Assertion result.
   */
  function assertResponse($code, $message = '') {
    $curl_code = curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
    $match = is_array($code) ? in_array($curl_code, $code) : $curl_code == $code;
    return $this->assertTrue($match, $message ? $message : t('HTTP response expected !code, actual !curl_code', array('!code' => $code, '!curl_code' => $curl_code)), t('Browser'));
  }
}
