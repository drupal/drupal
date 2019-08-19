<?php

namespace Drupal\simpletest\Tests;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Test\TestDatabase;
use Drupal\simpletest\WebTestBase;

/**
 * Tests SimpleTest's web interface: check that the intended tests were run and
 * ensure that test reports display the intended results. Also test SimpleTest's
 * internal browser and APIs implicitly.
 *
 * @group simpletest
 * @group WebTestBase
 * @group legacy
 */
class SimpleTestTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['simpletest'];

  /**
   * The results array that has been parsed by getTestResults().
   *
   * @var array
   */
  protected $childTestResults;

  /**
   * Stores the test ID from each test run for comparison.
   *
   * Used to ensure they are incrementing.
   */
  protected $testIds = [];

  /**
   * Translated fail message.
   *
   * @var string
   */
  private $failMessage = '';

  /**
   * Translated pass message.
   * @var string
   */
  private $passMessage = '';

  /**
   * A valid and recognized permission.
   *
   * @var string
   */
  protected $validPermission;

  /**
   * An invalid or unrecognized permission.
   *
   * @var string
   */
  protected $invalidPermission;

  protected function setUp() {
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

      file_put_contents($this->siteDirectory . '/' . 'settings.testing.php', $php);
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

      $original_container = $this->originalContainer;
      parent::setUp();
      $this->assertNotIdentical(\Drupal::getContainer(), $original_container, 'WebTestBase test creates a new container.');
      // Create and log in an admin user.
      $this->drupalLogin($this->drupalCreateUser(['administer unit tests']));
    }
    else {
      // This causes three of the five fails that are asserted in
      // confirmStubResults().
      self::$modules = ['non_existent_module'];
      parent::setUp();
    }
  }

  /**
   * Ensures the tests selected through the web interface are run and displayed.
   */
  public function testWebTestRunner() {
    $this->passMessage = t('SimpleTest pass.');
    $this->failMessage = t('SimpleTest fail.');
    $this->validPermission = 'access administration pages';
    $this->invalidPermission = 'invalid permission';

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

        $edit = [];
        $edit['tests[Drupal\simpletest\Tests\SimpleTestTest]'] = TRUE;
        $this->drupalPostForm(NULL, $edit, t('Run tests'));

        // Parse results and confirm that they are correct.
        $this->getTestResults();
        $this->confirmStubTestResults();
      }

      // Regression test for #290316.
      // Check that test_id is incrementing.
      $this->assertTrue($this->testIds[0] != $this->testIds[1], 'Test ID is incrementing.');
    }
  }

  /**
   * Test to be run and the results confirmed.
   *
   * Here we force test results which must match the expected results from
   * confirmStubResults().
   */
  public function stubTest() {
    // Ensure the .htkey file exists since this is only created just before a
    // request. This allows the stub test to make requests. The event does not
    // fire here and drupal_generate_test_ua() can not generate a key for a
    // test in a test since the prefix has changed.
    // @see \Drupal\Core\Test\HttpClientMiddleware\TestHttpClientMiddleware::onBeforeSendRequest()
    // @see drupal_generate_test_ua();
    $test_db = new TestDatabase($this->databasePrefix);
    $key_file = DRUPAL_ROOT . '/' . $test_db->getTestSitePath() . '/.htkey';
    $private_key = Crypt::randomBytesBase64(55);
    $site_path = $this->container->get('site.path');
    file_put_contents($key_file, $private_key);

    // Check to see if runtime assertions are indeed on, if successful this
    // will be the first of sixteen passes asserted in confirmStubResults()
    try {
      // Test with minimum possible arguments to make sure no notice for
      // missing argument is thrown.
      assert(FALSE);
      $this->fail('Runtime assertions are not working.');
    }
    catch (\AssertionError $e) {
      try {
        // Now test with an error message to ensure it is correctly passed
        // along by the rethrow.
        assert(FALSE, 'Lorem Ipsum');
      }
      catch (\AssertionError $e) {
        $this->assertEqual($e->getMessage(), 'Lorem Ipsum', 'Runtime assertions Enabled and running.');
      }
    }
    // This causes the second of the sixteen passes asserted in
    // confirmStubResults().
    $this->pass($this->passMessage);

    // The first three fails are caused by enabling a non-existent module in
    // setUp().

    // This causes the fourth of the five fails asserted in
    // confirmStubResults().
    $this->fail($this->failMessage);

    // This causes the third to fifth of the sixteen passes asserted in
    // confirmStubResults().
    $user = $this->drupalCreateUser([$this->validPermission], 'SimpleTestTest');

    // This causes the fifth of the five fails asserted in confirmStubResults().
    $this->drupalCreateUser([$this->invalidPermission]);

    // Test logging in as a user.
    // This causes the sixth to tenth of the sixteen passes asserted in
    // confirmStubResults().
    $this->drupalLogin($user);

    // This causes the eleventh of the sixteen passes asserted in
    // confirmStubResults().
    $this->pass('Test ID is ' . $this->testId . '.');

    // These cause the twelfth to fifteenth of the sixteen passes asserted in
    // confirmStubResults().
    $this->assertTrue(file_exists($site_path . '/settings.testing.php'));
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

    // This causes the sixteenth of the sixteen passes asserted in
    // confirmStubResults().
    $this->assertNothing();

    // This causes the debug message asserted in confirmStubResults().
    debug('Foo', 'Debug', FALSE);
  }

  /**
   * Assert nothing.
   */
  public function assertNothing() {
    $this->pass('This is nothing.');
  }

  /**
   * Confirm that the stub test produced the desired results.
   */
  public function confirmStubTestResults() {
    $this->assertAssertion(t('Unable to install modules %modules due to missing modules %missing.', ['%modules' => 'non_existent_module', '%missing' => 'non_existent_module']), 'Other', 'Fail', 'SimpleTestTest.php', 'Drupal\simpletest\Tests\SimpleTestTest->setUp()');

    $this->assertAssertion($this->passMessage, 'Other', 'Pass', 'SimpleTestTest.php', 'Drupal\simpletest\Tests\SimpleTestTest->stubTest()');
    $this->assertAssertion($this->failMessage, 'Other', 'Fail', 'SimpleTestTest.php', 'Drupal\simpletest\Tests\SimpleTestTest->stubTest()');

    $this->assertAssertion(t('Created permissions: @perms', ['@perms' => $this->validPermission]), 'Role', 'Pass', 'SimpleTestTest.php', 'Drupal\simpletest\Tests\SimpleTestTest->stubTest()');
    $this->assertAssertion(t('Invalid permission %permission.', ['%permission' => $this->invalidPermission]), 'Role', 'Fail', 'SimpleTestTest.php', 'Drupal\simpletest\Tests\SimpleTestTest->stubTest()');

    // Check that the user was logged in successfully.
    $this->assertAssertion('User SimpleTestTest successfully logged in.', 'User login', 'Pass', 'SimpleTestTest.php', 'Drupal\simpletest\Tests\SimpleTestTest->stubTest()');

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

    $this->assertEqual('16 passes, 3 fails, 2 exceptions, 3 debug messages', $this->childTestResults['summary']);

    $this->testIds[] = $test_id = $this->getTestIdFromResults();
    $this->assertTrue($test_id, 'Found test ID in results.');
  }

  /**
   * Fetch the test id from the test results.
   */
  public function getTestIdFromResults() {
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
   * @param string $message
   *   Assertion message.
   * @param string $type
   *   Assertion type.
   * @param string $status
   *   Assertion status.
   * @param string $file
   *   File where the assertion originated.
   * @param string $function
   *   Function where the assertion originated.
   *
   * @return Assertion result.
   */
  public function assertAssertion($message, $type, $status, $file, $function) {
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
    return $this->assertTrue($found, new FormattableMarkup('Found assertion {"@message", "@type", "@status", "@file", "@function"}.', ['@message' => $message, '@type' => $type, '@status' => $status, "@file" => $file, "@function" => $function]));
  }

  /**
   * Get the results from a test and store them in the class array $results.
   */
  public function getTestResults() {
    $results = [];
    if ($this->parse()) {
      if ($details = $this->getResultFieldSet()) {
        // Code assumes this is the only test in group.
        $results['summary'] = $this->asText($details->div->div[1]);
        $results['name'] = $this->asText($details->summary);

        $results['assertions'] = [];
        $tbody = $details->div->table->tbody;
        foreach ($tbody->tr as $row) {
          $assertion = [];
          $assertion['message'] = $this->asText($row->td[0]);
          $assertion['type'] = $this->asText($row->td[1]);
          $assertion['file'] = $this->asText($row->td[2]);
          $assertion['line'] = $this->asText($row->td[3]);
          $assertion['function'] = $this->asText($row->td[4]);
          $ok_url = file_url_transform_relative(file_create_url('core/misc/icons/73b355/check.svg'));
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
  public function getResultFieldSet() {
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
  public function asText(\SimpleXMLElement $element) {
    if (!is_object($element)) {
      return $this->fail('The element is not an element.');
    }
    return trim(html_entity_decode(strip_tags($element->asXML())));
  }

}
