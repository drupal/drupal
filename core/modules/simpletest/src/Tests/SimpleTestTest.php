<?php

/**
 * @file
 * Definition of \Drupal\simpletest\Tests\SimpleTestTest.
 */

namespace Drupal\simpletest\Tests;

use Drupal\simpletest\WebTestBase;

/**
 * Tests SimpleTest's web interface: check that the intended tests were run and
 * ensure that test reports display the intended results. Also test SimpleTest's
 * internal browser and APIs both explicitly and implicitly.
 *
 * @group simpletest
 */
class SimpleTestTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = array('simpletest', 'test_page_test');

  /**
   * The results array that has been parsed by getTestResults().
   */
  protected $childTestResults;

  /**
   * Stores the test ID from each test run for comparison.
   *
   * Used to ensure they are incrementing.
   */
  protected $test_ids = array();

  function setUp() {
    if (!$this->isInChildSite()) {
      $php = <<<'EOD'
<?php

# Make sure that the $test_class variable is defined when this file is included.
if ($test_class) {
}

# Define a function to be able to check that this file was loaded with
# function_exists().
if (!function_exists('simpletest_test_stub_settings_function')) {
  function simpletest_test_stub_settings_function() {}
}
EOD;

      file_put_contents($this->siteDirectory. '/' . 'settings.testing.php', $php);
      // @see \Drupal\system\Tests\DrupalKernel\DrupalKernelSiteTest
      $class = __CLASS__;
      $yaml = <<<EOD
services:
  # Add a new service.
  site.service.yml:
    class: $class
  # Swap out a core service.
  cache.backend.database:
    class: Drupal\Core\Cache\MemoryBackendFactory
EOD;
      file_put_contents($this->siteDirectory . '/testing.services.yml', $yaml);

      $original_container = \Drupal::getContainer();
      parent::setUp();
      $this->assertNotIdentical(\Drupal::getContainer(), $original_container, 'WebTestBase test creates a new container.');
      // Create and log in an admin user.
      $this->drupalLogin($this->drupalCreateUser(array('administer unit tests')));
    }
    else {
      // This causes three of the five fails that are asserted in
      // confirmStubResults().
      self::$modules = array('non_existent_module');
      parent::setUp();
    }
  }

  /**
   * Test the internal browsers functionality.
   */
  function testInternalBrowser() {
    if (!$this->isInChildSite()) {
      // Retrieve the test page and check its title and headers.
      $this->drupalGet('test-page');
      $this->assertTrue($this->drupalGetHeader('Date'), 'An HTTP header was received.');
      $this->assertTitle(t('Test page | @site-name', array(
        '@site-name' => \Drupal::config('system.site')->get('name'),
      )));
      $this->assertNoTitle('Foo');

      $old_user_id = $this->container->get('current_user')->id();
      $user = $this->drupalCreateUser();
      $this->drupalLogin($user);
      // Check that current user service updated.
      $this->assertNotEqual($old_user_id, $this->container->get('current_user')->id(), 'Current user service updated.');
      $headers = $this->drupalGetHeaders(TRUE);
      $this->assertEqual(count($headers), 2, 'There was one intermediate request.');
      $this->assertTrue(strpos($headers[0][':status'], '303') !== FALSE, 'Intermediate response code was 303.');
      $this->assertFalse(empty($headers[0]['location']), 'Intermediate request contained a Location header.');
      $this->assertEqual($this->getUrl(), $headers[0]['location'], 'HTTP redirect was followed');
      $this->assertFalse($this->drupalGetHeader('Location'), 'Headers from intermediate request were reset.');
      $this->assertResponse(200, 'Response code from intermediate request was reset.');

      // Test the maximum redirection option.
      $this->drupalLogout();
      // Check that current user service updated to anonymous user.
      $this->assertEqual(0, $this->container->get('current_user')->id(), 'Current user service updated.');
      $edit = array(
        'name' => $user->getUsername(),
        'pass' => $user->pass_raw
      );
      $this->maximumRedirects = 1;
      $this->drupalPostForm('user', $edit, t('Log in'), array(
        'query' => array('destination' => 'user/logout'),
      ));
      $headers = $this->drupalGetHeaders(TRUE);
      $this->assertEqual(count($headers), 2, 'Simpletest stopped following redirects after the first one.');

      // Remove the Simpletest private key file so we can test the protection
      // against requests that forge a valid testing user agent to gain access
      // to the installer.
      // @see drupal_valid_test_ua()
      // Not using File API; a potential error must trigger a PHP warning.
      unlink($this->siteDirectory . '/.htkey');
      global $base_url;
      $this->drupalGet(url($base_url . '/core/install.php', array('external' => TRUE, 'absolute' => TRUE)));
      $this->assertResponse(403, 'Cannot access install.php.');
    }
  }

  /**
   * Test validation of the User-Agent header we use to perform test requests.
   */
  function testUserAgentValidation() {
    if (!$this->isInChildSite()) {
      global $base_url;
      $system_path = $base_url . '/' . drupal_get_path('module', 'system');
      $HTTP_path = $system_path .'/tests/http.php?q=node';
      $https_path = $system_path .'/tests/https.php?q=node';
      // Generate a valid simpletest User-Agent to pass validation.
      $this->assertTrue(preg_match('/simpletest\d+/', $this->databasePrefix, $matches), 'Database prefix contains simpletest prefix.');
      $test_ua = drupal_generate_test_ua($matches[0]);
      $this->additionalCurlOptions = array(CURLOPT_USERAGENT => $test_ua);

      // Test pages only available for testing.
      $this->drupalGet($HTTP_path);
      $this->assertResponse(200, 'Requesting http.php with a legitimate simpletest User-Agent returns OK.');
      $this->drupalGet($https_path);
      $this->assertResponse(200, 'Requesting https.php with a legitimate simpletest User-Agent returns OK.');

      // Now slightly modify the HMAC on the header, which should not validate.
      $this->additionalCurlOptions = array(CURLOPT_USERAGENT => $test_ua . 'X');
      $this->drupalGet($HTTP_path);
      $this->assertResponse(403, 'Requesting http.php with a bad simpletest User-Agent fails.');
      $this->drupalGet($https_path);
      $this->assertResponse(403, 'Requesting https.php with a bad simpletest User-Agent fails.');

      // Use a real User-Agent and verify that the special files http.php and
      // https.php can't be accessed.
      $this->additionalCurlOptions = array(CURLOPT_USERAGENT => 'Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10.6; en-US; rv:1.9.2.12) Gecko/20101026 Firefox/3.6.12');
      $this->drupalGet($HTTP_path);
      $this->assertResponse(403, 'Requesting http.php with a normal User-Agent fails.');
      $this->drupalGet($https_path);
      $this->assertResponse(403, 'Requesting https.php with a normal User-Agent fails.');
    }
  }

  /**
   * Ensures the tests selected through the web interface are run and displayed.
   */
  function testWebTestRunner() {
    $this->pass = t('SimpleTest pass.');
    $this->fail = t('SimpleTest fail.');
    $this->valid_permission = 'access administration pages';
    $this->invalid_permission = 'invalid permission';

    if ($this->isInChildSite()) {
      // Only run following code if this test is running itself through a CURL
      // request.
      $this->stubTest();
    }
    else {

      // Run twice so test_ids can be accumulated.
      for ($i = 0; $i < 2; $i++) {
        // Run this test from web interface.
        $this->drupalGet('admin/config/development/testing');

        $edit = array();
        $edit['tests[Drupal\simpletest\Tests\SimpleTestTest]'] = TRUE;
        $this->drupalPostForm(NULL, $edit, t('Run tests'));

        // Parse results and confirm that they are correct.
        $this->getTestResults();
        $this->confirmStubTestResults();
      }

      // Regression test for #290316.
      // Check that test_id is incrementing.
      $this->assertTrue($this->test_ids[0] != $this->test_ids[1], 'Test ID is incrementing.');
    }
  }

  /**
   * Test to be run and the results confirmed.
   */
  function stubTest() {
    // This causes the first of the ten passes asserted in confirmStubResults().
    $this->pass($this->pass);
    // The first three fails are caused by enabling a non-existent module in
    // setUp(). This causes the fourth of the five fails asserted in
    // confirmStubResults().
    $this->fail($this->fail);

    // This causes the second to fourth of the ten passes asserted in
    // confirmStubResults().
    $this->drupalCreateUser(array($this->valid_permission));
    // This causes the fifth of the five fails asserted in confirmStubResults().
    $this->drupalCreateUser(array($this->invalid_permission));

    // This causes the fifth of the ten passes asserted in confirmStubResults().
    $this->pass(t('Test ID is @id.', array('@id' => $this->testId)));

    // These cause the sixth to ninth of the ten passes asserted in
    // confirmStubResults().
    $this->assertTrue(file_exists(conf_path() . '/settings.testing.php'));
    // Check the settings.testing.php file got included.
    $this->assertTrue(function_exists('simpletest_test_stub_settings_function'));
    // Check that the test-specific service file got loaded.
    $this->assertTrue($this->container->has('site.service.yml'));
    $this->assertIdentical(get_class($this->container->get('cache.backend.database')), 'Drupal\Core\Cache\MemoryBackendFactory');

    // These cause the two exceptions asserted in confirmStubResults().
    // Call trigger_error() without the required argument to trigger an E_WARNING.
    trigger_error();
    // Generates a warning inside a PHP function.
    array_key_exists(NULL, NULL);

    // This causes the tenth of the ten passes asserted in
    // confirmStubResults().
    $this->assertNothing();

    // This causes the debug message asserted in confirmStubResults().
    debug('Foo', 'Debug');
  }

  /**
   * Assert nothing.
   */
  function assertNothing() {
    $this->pass("This is nothing.");
  }

  /**
   * Confirm that the stub test produced the desired results.
   */
  function confirmStubTestResults() {
    $this->assertAssertion(t('Enabled modules: %modules', array('%modules' => 'non_existent_module')), 'Other', 'Fail', 'SimpleTestTest.php', 'Drupal\simpletest\Tests\SimpleTestTest->setUp()');

    $this->assertAssertion($this->pass, 'Other', 'Pass', 'SimpleTestTest.php', 'Drupal\simpletest\Tests\SimpleTestTest->stubTest()');
    $this->assertAssertion($this->fail, 'Other', 'Fail', 'SimpleTestTest.php', 'Drupal\simpletest\Tests\SimpleTestTest->stubTest()');

    $this->assertAssertion(t('Created permissions: @perms', array('@perms' => $this->valid_permission)), 'Role', 'Pass', 'SimpleTestTest.php', 'Drupal\simpletest\Tests\SimpleTestTest->stubTest()');
    $this->assertAssertion(t('Invalid permission %permission.', array('%permission' => $this->invalid_permission)), 'Role', 'Fail', 'SimpleTestTest.php', 'Drupal\simpletest\Tests\SimpleTestTest->stubTest()');

    // Check that a warning is caught by simpletest. The exact error message
    // differs between PHP versions so only the function name is checked.
    $this->assertAssertion('trigger_error()', 'Warning', 'Fail', 'SimpleTestTest.php', 'Drupal\simpletest\Tests\SimpleTestTest->stubTest()');

    // Check that the backtracing code works for specific assert function.
    $this->assertAssertion('This is nothing.', 'Other', 'Pass', 'SimpleTestTest.php', 'Drupal\simpletest\Tests\SimpleTestTest->stubTest()');

    // Check that errors that occur inside PHP internal functions are correctly
    // reported. The exact error message differs between PHP versions so we
    // check only the function name 'array_key_exists'.
    $this->assertAssertion('array_key_exists', 'Warning', 'Fail', 'SimpleTestTest.php', 'Drupal\simpletest\Tests\SimpleTestTest->stubTest()');

    $this->assertAssertion("Debug: 'Foo'", 'Debug', 'Fail', 'SimpleTestTest.php', 'Drupal\simpletest\Tests\SimpleTestTest->stubTest()');

    $this->assertEqual('10 passes, 5 fails, 2 exceptions, 1 debug message', $this->childTestResults['summary']);

    $this->test_ids[] = $test_id = $this->getTestIdFromResults();
    $this->assertTrue($test_id, 'Found test ID in results.');
  }

  /**
   * Fetch the test id from the test results.
   */
  function getTestIdFromResults() {
    foreach ($this->childTestResults['assertions'] as $assertion) {
      if (preg_match('@^Test ID is ([0-9]*)\.$@', $assertion['message'], $matches)) {
        return $matches[1];
      }
    }
    return NULL;
  }

  /**
   * Asserts that an assertion with specified values is displayed in results.
   *
   * @param string $message Assertion message.
   * @param string $type Assertion type.
   * @param string $status Assertion status.
   * @param string $file File where the assertion originated.
   * @param string $functuion Function where the assertion originated.
   *
   * @return Assertion result.
   */
  function assertAssertion($message, $type, $status, $file, $function) {
    $message = trim(strip_tags($message));
    $found = FALSE;
    foreach ($this->childTestResults['assertions'] as $assertion) {
      if ((strpos($assertion['message'], $message) !== FALSE) &&
          $assertion['type'] == $type &&
          $assertion['status'] == $status &&
          $assertion['file'] == $file &&
          $assertion['function'] == $function) {
        $found = TRUE;
        break;
      }
    }
    return $this->assertTrue($found, format_string('Found assertion {"@message", "@type", "@status", "@file", "@function"}.', array('@message' => $message, '@type' => $type, '@status' => $status, "@file" => $file, "@function" => $function)));
  }

  /**
   * Get the results from a test and store them in the class array $results.
   */
  function getTestResults() {
    $results = array();
    if ($this->parse()) {
      if ($details = $this->getResultFieldSet()) {
        // Code assumes this is the only test in group.
        $results['summary'] = $this->asText($details->div->div[1]);
        $results['name'] = $this->asText($details->summary);

        $results['assertions'] = array();
        $tbody = $details->div->table->tbody;
        foreach ($tbody->tr as $row) {
          $assertion = array();
          $assertion['message'] = $this->asText($row->td[0]);
          $assertion['type'] = $this->asText($row->td[1]);
          $assertion['file'] = $this->asText($row->td[2]);
          $assertion['line'] = $this->asText($row->td[3]);
          $assertion['function'] = $this->asText($row->td[4]);
          $ok_url = file_create_url('core/misc/icons/73b355/check.png');
          $assertion['status'] = ($row->td[5]->img['src'] == $ok_url) ? 'Pass' : 'Fail';
          $results['assertions'][] = $assertion;
        }
      }
    }
    $this->childTestResults = $results;
  }

  /**
   * Get the details containing the results for group this test is in.
   */
  function getResultFieldSet() {
    $all_details = $this->xpath('//details');
    foreach ($all_details as $details) {
      if ($this->asText($details->summary) == __CLASS__) {
        return $details;
      }
    }
    return FALSE;
  }

  /**
   * Extract the text contained by the element.
   *
   * @param $element
   *   Element to extract text from.
   *
   * @return
   *   Extracted text.
   */
  function asText(\SimpleXMLElement $element) {
    if (!is_object($element)) {
      return $this->fail('The element is not an element.');
    }
    return trim(html_entity_decode(strip_tags($element->asXML())));
  }

}
