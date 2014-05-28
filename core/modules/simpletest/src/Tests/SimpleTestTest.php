<?php

/**
 * @file
 * Definition of \Drupal\simpletest\Tests\SimpleTestTest.
 */

namespace Drupal\simpletest\Tests;

use Drupal\Core\Database\Driver\pgsql\Select;
use Drupal\simpletest\WebTestBase;

/**
 * Tests the Simpletest UI test runner and internal browser.
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

  public static function getInfo() {
    return array(
      'name' => 'SimpleTest functionality',
      'description' => "Test SimpleTest's web interface: check that the intended tests were run and ensure that test reports display the intended results. Also test SimpleTest's internal browser and APIs both explicitly and implicitly.",
      'group' => 'SimpleTest'
    );
  }

  function setUp() {
    if (!$this->isInChildSite()) {
      parent::setUp();
      // Create and log in an admin user.
      $this->drupalLogin($this->drupalCreateUser(array('administer unit tests')));
    }
    else {
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
      $this->assertTrue(strpos($headers[0][':status'], '302') !== FALSE, 'Intermediate response code was 302.');
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
    $this->pass($this->pass);
    $this->fail($this->fail);

    $this->drupalCreateUser(array($this->valid_permission));
    $this->drupalCreateUser(array($this->invalid_permission));

    $this->pass(t('Test ID is @id.', array('@id' => $this->testId)));

    // Call trigger_error() without the required argument to trigger an E_WARNING.
    trigger_error();

    // Call an assert function specific to that class.
    $this->assertNothing();

    // Generates a warning inside a PHP function.
    array_key_exists(NULL, NULL);

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

    $this->assertEqual('6 passes, 5 fails, 2 exceptions, 1 debug message', $this->childTestResults['summary']);

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
    $info = $this->getInfo();
    foreach ($all_details as $details) {
      if ($this->asText($details->summary) == $info['name']) {
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
