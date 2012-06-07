<?php

/**
 * @file
 * Definition of Drupal\simpletest\Tests\SimpleTestTest.
 */

namespace Drupal\simpletest\Tests;

use Drupal\simpletest\WebTestBase;
use SimpleXMLElement;

class SimpleTestTest extends WebTestBase {
  /**
   * The results array that has been parsed by getTestResults().
   */
  protected $childTestResults;

  /**
   * Store the test ID from each test run for comparison, to ensure they are
   * incrementing.
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
    if (!$this->inCURL()) {
      parent::setUp('simpletest');

      // Create and login user
      $admin_user = $this->drupalCreateUser(array('administer unit tests'));
      $this->drupalLogin($admin_user);
    }
    else {
      parent::setUp('non_existent_module');
    }
  }

  /**
   * Test the internal browsers functionality.
   */
  function testInternalBrowser() {
    global $conf;
    if (!$this->inCURL()) {
      $this->drupalGet('node');
      $this->assertTrue($this->drupalGetHeader('Date'), t('An HTTP header was received.'));
      $this->assertTitle(t('Welcome to @site-name | @site-name', array('@site-name' => variable_get('site_name', 'Drupal'))), t('Site title matches.'));
      $this->assertNoTitle('Foo', t('Site title does not match.'));
      // Make sure that we are locked out of the installer when prefixing
      // using the user-agent header. This is an important security check.
      global $base_url;

      $this->drupalGet($base_url . '/core/install.php', array('external' => TRUE));
      $this->assertResponse(403, 'Cannot access install.php with a "simpletest" user-agent header.');

      $user = $this->drupalCreateUser();
      $this->drupalLogin($user);
      $headers = $this->drupalGetHeaders(TRUE);
      $this->assertEqual(count($headers), 2, t('There was one intermediate request.'));
      $this->assertTrue(strpos($headers[0][':status'], '302') !== FALSE, t('Intermediate response code was 302.'));
      $this->assertFalse(empty($headers[0]['location']), t('Intermediate request contained a Location header.'));
      $this->assertEqual($this->getUrl(), $headers[0]['location'], t('HTTP redirect was followed'));
      $this->assertFalse($this->drupalGetHeader('Location'), t('Headers from intermediate request were reset.'));
      $this->assertResponse(200, t('Response code from intermediate request was reset.'));

      // Test the maximum redirection option.
      $this->drupalLogout();
      $edit = array(
        'name' => $user->name,
        'pass' => $user->pass_raw
      );
      variable_set('simpletest_maximum_redirects', 1);
      $this->drupalPost('user', $edit, t('Log in'), array(
        'query' => array('destination' => 'user/logout'),
      ));
      $headers = $this->drupalGetHeaders(TRUE);
      $this->assertEqual(count($headers), 2, t('Simpletest stopped following redirects after the first one.'));
    }
  }

  /**
   * Test validation of the User-Agent header we use to perform test requests.
   */
  function testUserAgentValidation() {
    if (!$this->inCURL()) {
      global $base_url;
      $system_path = $base_url . '/' . drupal_get_path('module', 'system');
      $HTTP_path = $system_path .'/tests/http.php?q=node';
      $https_path = $system_path .'/tests/https.php?q=node';
      // Generate a valid simpletest User-Agent to pass validation.
      $this->assertTrue(preg_match('/simpletest\d+/', $this->databasePrefix, $matches), t('Database prefix contains simpletest prefix.'));
      $test_ua = drupal_generate_test_ua($matches[0]);
      $this->additionalCurlOptions = array(CURLOPT_USERAGENT => $test_ua);

      // Test pages only available for testing.
      $this->drupalGet($HTTP_path);
      $this->assertResponse(200, t('Requesting http.php with a legitimate simpletest User-Agent returns OK.'));
      $this->drupalGet($https_path);
      $this->assertResponse(200, t('Requesting https.php with a legitimate simpletest User-Agent returns OK.'));

      // Now slightly modify the HMAC on the header, which should not validate.
      $this->additionalCurlOptions = array(CURLOPT_USERAGENT => $test_ua . 'X');
      $this->drupalGet($HTTP_path);
      $this->assertResponse(403, t('Requesting http.php with a bad simpletest User-Agent fails.'));
      $this->drupalGet($https_path);
      $this->assertResponse(403, t('Requesting https.php with a bad simpletest User-Agent fails.'));

      // Use a real User-Agent and verify that the special files http.php and
      // https.php can't be accessed.
      $this->additionalCurlOptions = array(CURLOPT_USERAGENT => 'Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10.6; en-US; rv:1.9.2.12) Gecko/20101026 Firefox/3.6.12');
      $this->drupalGet($HTTP_path);
      $this->assertResponse(403, t('Requesting http.php with a normal User-Agent fails.'));
      $this->drupalGet($https_path);
      $this->assertResponse(403, t('Requesting https.php with a normal User-Agent fails.'));
    }
  }

  /**
   * Make sure that tests selected through the web interface are run and
   * that the results are displayed correctly.
   */
  function testWebTestRunner() {
    $this->pass = t('SimpleTest pass.');
    $this->fail = t('SimpleTest fail.');
    $this->valid_permission = 'access content';
    $this->invalid_permission = 'invalid permission';

    if ($this->inCURL()) {
      // Only run following code if this test is running itself through a CURL request.
      $this->stubTest();
    }
    else {

      // Run twice so test_ids can be accumulated.
      for ($i = 0; $i < 2; $i++) {
        // Run this test from web interface.
        $this->drupalGet('admin/config/development/testing');

        $edit = array();
        $edit['Drupal\simpletest\Tests\SimpleTestTest'] = TRUE;
        $this->drupalPost(NULL, $edit, t('Run tests'));

        // Parse results and confirm that they are correct.
        $this->getTestResults();
        $this->confirmStubTestResults();
      }

      // Regression test for #290316.
      // Check that test_id is incrementing.
      $this->assertTrue($this->test_ids[0] != $this->test_ids[1], t('Test ID is incrementing.'));
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

    // Generates a warning.
    $i = 1 / 0;

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

    // Check that a warning is caught by simpletest.
    $this->assertAssertion('Division by zero', 'Warning', 'Fail', 'SimpleTestTest.php', 'Drupal\simpletest\Tests\SimpleTestTest->stubTest()');

    // Check that the backtracing code works for specific assert function.
    $this->assertAssertion('This is nothing.', 'Other', 'Pass', 'SimpleTestTest.php', 'Drupal\simpletest\Tests\SimpleTestTest->stubTest()');

    // Check that errors that occur inside PHP internal functions are correctly reported.
    // The exact error message differs between PHP versions so we check only
    // the function name 'array_key_exists'.
    $this->assertAssertion('array_key_exists', 'Warning', 'Fail', 'SimpleTestTest.php', 'Drupal\simpletest\Tests\SimpleTestTest->stubTest()');

    $this->assertAssertion("Debug: 'Foo'", 'Debug', 'Fail', 'SimpleTestTest.php', 'Drupal\simpletest\Tests\SimpleTestTest->stubTest()');

    $this->assertEqual('6 passes, 5 fails, 2 exceptions, and 1 debug message', $this->childTestResults['summary'], 'Stub test summary is correct');

    $this->test_ids[] = $test_id = $this->getTestIdFromResults();
    $this->assertTrue($test_id, t('Found test ID in results.'));
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
   * Assert that an assertion with the specified values is displayed
   * in the test results.
   *
   * @param string $message Assertion message.
   * @param string $type Assertion type.
   * @param string $status Assertion status.
   * @param string $file File where the assertion originated.
   * @param string $functuion Function where the assertion originated.
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
    return $this->assertTrue($found, t('Found assertion {"@message", "@type", "@status", "@file", "@function"}.', array('@message' => $message, '@type' => $type, '@status' => $status, "@file" => $file, "@function" => $function)));
  }

  /**
   * Get the results from a test and store them in the class array $results.
   */
  function getTestResults() {
    $results = array();
    if ($this->parse()) {
      if ($fieldset = $this->getResultFieldSet()) {
        // Code assumes this is the only test in group.
        $results['summary'] = $this->asText($fieldset->div->div[1]);
        $results['name'] = $this->asText($fieldset->legend);

        $results['assertions'] = array();
        $tbody = $fieldset->div->table->tbody;
        foreach ($tbody->tr as $row) {
          $assertion = array();
          $assertion['message'] = $this->asText($row->td[0]);
          $assertion['type'] = $this->asText($row->td[1]);
          $assertion['file'] = $this->asText($row->td[2]);
          $assertion['line'] = $this->asText($row->td[3]);
          $assertion['function'] = $this->asText($row->td[4]);
          $ok_url = file_create_url('core/misc/watchdog-ok.png');
          $assertion['status'] = ($row->td[5]->img['src'] == $ok_url) ? 'Pass' : 'Fail';
          $results['assertions'][] = $assertion;
        }
      }
    }
    $this->childTestResults = $results;
  }

  /**
   * Get the fieldset containing the results for group this test is in.
   */
  function getResultFieldSet() {
    $fieldsets = $this->xpath('//fieldset');
    $info = $this->getInfo();
    foreach ($fieldsets as $fieldset) {
      if ($this->asText($fieldset->legend) == $info['name']) {
        return $fieldset;
      }
    }
    return FALSE;
  }

  /**
   * Extract the text contained by the element.
   *
   * @param $element
   *   Element to extract text from.
   * @return
   *   Extracted text.
   */
  function asText(SimpleXMLElement $element) {
    if (!is_object($element)) {
      return $this->fail('The element is not an element.');
    }
    return trim(html_entity_decode(strip_tags($element->asXML())));
  }

  /**
   * Check if the test is being run from inside a CURL request.
   */
  function inCURL() {
    return (bool) drupal_valid_test_ua();
  }
}
